<?php

namespace Rhymix\Modules\Log_admin\Controllers;

use Context;
use Rhymix\Framework\Debug;
use Rhymix\Framework\URL;
use Rhymix\Modules\Log_admin\Models\AccessLog as AccessLogModel;
use Rhymix\Modules\Log_admin\Models\Classifier as ClassifierModel;
use Rhymix\Modules\Log_admin\Models\Config as ConfigModel;

/**
 * 개인정보 접속기록 관리 모듈 - 이벤트 핸들러
 *
 * @author YJSoft <yjsoft@yjsoft.xyz>
 */
class EventHandlers extends Base
{
	/**
	 * 현재 요청의 자동 기록을 건너뛸지 여부 (직접 기록한 경우 중복 방지)
	 */
	protected static $_skip_current_request = false;

	public static function skipCurrentRequest(): void
	{
		self::$_skip_current_request = true;
	}

	/**
	 * 모든 모듈 실행 후 (moduleHandler.proc after)
	 *
	 * 관리자 화면뿐 아니라 게시판 등 프런트 화면에서의 조회·수정·삭제·다운로드도 기록한다.
	 */
	public function afterModuleHandlerProc($oModule)
	{
		try
		{
			$config = ConfigModel::getConfig();
			if (empty($config->enabled) || self::$_skip_current_request)
			{
				return;
			}

			$logged_info = Context::get('logged_info');
			if (!$logged_info || empty($logged_info->member_srl))
			{
				return;
			}

			$act = strval(Context::get('act') ?: (is_object($oModule) ? ($oModule->act ?? '') : ''));
			if (in_array($act, ClassifierModel::SKIP_ACTS, true) || in_array($act, $config->exclude_acts ?: [], true))
			{
				return;
			}

			$grant = (is_object($oModule) && isset($oModule->grant)) ? $oModule->grant : Context::get('grant');
			$role = ClassifierModel::getRole($logged_info, $grant);
			if (!$role)
			{
				return;
			}

			$module_info = Context::get('current_module_info');
			$module = strval((is_object($oModule) ? ($oModule->module ?? '') : '') ?: ($module_info->module ?? '') ?: Context::get('module'));
			$vars = Context::getRequestVars() ?: new \stdClass;
			$target = ClassifierModel::resolveTarget($vars, $module, $act);
			$task_type = ClassifierModel::classifyTask($act, !in_array($target->target_type, ['', 'list'], true));

			// 기록 범위: 관리자 화면 + 등록·수정·삭제·다운로드·권한·설정 + 회원정보 조회만 기록
			if ($config->log_scope === 'privacy' && in_array($task_type, ['list', 'view', 'other'], true)
				&& stripos($act, 'Admin') === false && $module !== 'admin' && $target->target_type !== 'member')
			{
				return;
			}

			$is_success = !(is_object($oModule) && method_exists($oModule, 'toBool') && !$oModule->toBool());
			self::writeLog($logged_info, $role, [
				'module' => $module,
				'mid' => $module_info->mid ?? '',
				'act' => $act,
				'task_type' => $task_type,
				'target_type' => $target->target_type,
				'target_srl' => $target->target_srl,
				'subject_member_srl' => $target->subject_member_srl,
				'subject_info' => $target->subject_info,
				'result' => $is_success ? 'success' : 'fail',
				'result_message' => $is_success ? '' : strval($oModule->getMessage()),
				'request_vars' => ClassifierModel::sanitizeRequestVars($vars),
			]);

			// 큐(예약 작업)를 쓸 수 없는 환경에서만 요청 시점에 하루 한 번 정리
			if (!config('queue.enabled'))
			{
				self::purgeIfNeeded();
			}
		}
		catch (\Throwable $e)
		{
			// 기록 실패가 사이트 동작을 막지 않도록 한다.
			Debug::addError(\E_USER_WARNING, 'log_admin: ' . $e->getMessage(), $e->getFile(), $e->getLine());
		}
	}

	/**
	 * 로그인 성공 후 (member.doLogin after)
	 */
	public function afterDoLogin($member_info)
	{
		try
		{
			$config = ConfigModel::getConfig();
			if (empty($config->enabled) || !$member_info || empty($member_info->member_srl))
			{
				return;
			}
			$role = ClassifierModel::getRole($member_info, null, true);
			if (!$role)
			{
				return;
			}
			self::writeLog($member_info, $role, [
				'module' => 'member',
				'act' => strval(Context::get('act')),
				'task_type' => 'login',
				'target_type' => 'member',
				'target_srl' => $member_info->member_srl,
				'subject_member_srl' => 0,
				'subject_info' => '',
				'result' => 'success',
			]);
		}
		catch (\Throwable $e)
		{
			Debug::addError(\E_USER_WARNING, 'log_admin: ' . $e->getMessage(), $e->getFile(), $e->getLine());
		}
	}

	/**
	 * 로그아웃 직전 (member.doLogout before)
	 */
	public function beforeDoLogout($logged_info)
	{
		try
		{
			$config = ConfigModel::getConfig();
			if (empty($config->enabled) || !$logged_info || empty($logged_info->member_srl))
			{
				return;
			}
			$role = ClassifierModel::getRole($logged_info, null, true);
			if (!$role)
			{
				return;
			}
			self::writeLog($logged_info, $role, [
				'module' => 'member',
				'act' => strval(Context::get('act')),
				'task_type' => 'logout',
				'target_type' => 'member',
				'target_srl' => $logged_info->member_srl,
				'result' => 'success',
			]);
		}
		catch (\Throwable $e)
		{
			Debug::addError(\E_USER_WARNING, 'log_admin: ' . $e->getMessage(), $e->getFile(), $e->getLine());
		}
	}

	/**
	 * 접속기록 저장 (공통)
	 */
	public static function writeLog(?object $member_info, string $role, array $data): int
	{
		$data += [
			'member_srl' => intval($member_info->member_srl ?? 0),
			'user_id' => strval($member_info->user_id ?? ''),
			'nick_name' => strval($member_info->nick_name ?? ''),
			'user_role' => $role,
			'ipaddress' => \RX_CLIENT_IP,
			'user_agent' => strval($_SERVER['HTTP_USER_AGENT'] ?? ''),
			'request_method' => strval($_SERVER['REQUEST_METHOD'] ?? (\PHP_SAPI === 'cli' ? 'CLI' : '')),
			'request_url' => \PHP_SAPI === 'cli' ? '' : URL::getCurrentURL(),
		];
		return AccessLogModel::insert($data);
	}

	/**
	 * 예약 작업(Queue::addTaskAtInterval) 핸들러: 보관기간이 지난 기록 삭제
	 */
	public static function runScheduledPurge($args = null, $options = null): void
	{
		$config = ConfigModel::getConfig();
		$config->last_purge_date = date('Ymd');
		ConfigModel::setConfig($config);
		self::purge();
	}

	/**
	 * 큐 미사용 환경: 하루 한 번 보관기간이 지난 기록 삭제
	 */
	public static function purgeIfNeeded(): void
	{
		$config = ConfigModel::getConfig();
		if ($config->last_purge_date === date('Ymd'))
		{
			return;
		}
		$config->last_purge_date = date('Ymd');
		ConfigModel::setConfig($config);
		self::purge();
	}

	/**
	 * 보관기간이 지난 기록 삭제 및 삭제 사실 기록
	 */
	public static function purge(): void
	{
		$config = ConfigModel::getConfig();
		$deleted = [
			AccessLogModel::CHAIN_ACCESS => AccessLogModel::purge(AccessLogModel::CHAIN_ACCESS, intval($config->access_retention_years)),
			AccessLogModel::CHAIN_PERMISSION => AccessLogModel::purge(AccessLogModel::CHAIN_PERMISSION, intval($config->permission_retention_years)),
		];
		if (array_sum($deleted) > 0)
		{
			// 삭제 사실 자체도 기록으로 남긴다.
			AccessLogModel::insert([
				'member_srl' => 0,
				'user_id' => 'system',
				'nick_name' => 'system',
				'user_role' => 'system',
				'ipaddress' => \RX_CLIENT_IP,
				'module' => 'log_admin',
				'act' => 'purge',
				'task_type' => 'purge',
				'subject_info' => sprintf('retention purge: access=%d (%d years), permission=%d (%d years)',
					$deleted['access'], $config->access_retention_years, $deleted['permission'], $config->permission_retention_years),
				'result' => 'success',
			]);
		}
	}
}

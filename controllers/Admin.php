<?php

namespace Rhymix\Modules\Log_admin\Controllers;

use Context;
use MemberModel;
use Rhymix\Framework\Exceptions\InvalidRequest;
use Rhymix\Framework\Exceptions\TargetNotFound;
use Rhymix\Modules\Log_admin\Models\AccessLog as AccessLogModel;
use Rhymix\Modules\Log_admin\Models\Classifier as ClassifierModel;
use Rhymix\Modules\Log_admin\Models\Config as ConfigModel;

/**
 * 개인정보 접속기록 관리 모듈 - 관리자 화면
 *
 * Admin이 포함된 액션은 코어 기본값에 따라 최고관리자(root)만 접근할 수 있다.
 *
 * @author YJSoft <yjsoft@yjsoft.xyz>
 */
class Admin extends Base
{
	/**
	 * 검색 필터 키. 요청 변수 이름은 코어 예약 변수(act, module, mid 등)와 겹치지 않도록
	 * FILTER_PREFIX를 붙여 사용한다. (예: 액션 검색 → filter_act)
	 */
	public const FILTER_PREFIX = 'filter_';
	public const FILTER_KEYS = ['start_date', 'end_date', 'user_id', 'ipaddress', 'task_type', 'act', 'subject_member_srl', 'chain', 'result'];

	public function init()
	{
		$this->setTemplatePath($this->module_path . 'views/admin/');
		Context::set('task_types', ClassifierModel::TASK_TYPES);
	}

	/**
	 * 접속기록 목록
	 */
	public function dispLog_adminAdminList()
	{
		$args = $this->_getFilterArgs();
		$args->page = max(1, intval(Context::get('page')));
		$args->list_count = 30;
		$output = executeQueryArray('log_admin.getAccessLogList', $args);

		Context::set('filters', $this->_getFilters());
		Context::set('log_list', $output->data ?: []);
		Context::set('total_count', $output->total_count ?? 0);
		Context::set('total_page', $output->total_page ?? 1);
		Context::set('page', $output->page ?? 1);
		Context::set('page_navigation', $output->page_navigation ?? null);
		Context::set('config', ConfigModel::getConfig());
		$this->setTemplateFile('list');
	}

	/**
	 * 접속기록 상세
	 */
	public function dispLog_adminAdminDetail()
	{
		$log_id = intval(Context::get('log_id'));
		$output = executeQuery('log_admin.getAccessLog', ['log_id' => $log_id]);
		if (!$output->toBool() || !$output->data)
		{
			throw new TargetNotFound;
		}
		Context::set('log', $output->data);
		Context::set('signature_valid', AccessLogModel::verifyRecord($output->data));
		$this->setTemplateFile('detail');
	}

	/**
	 * 무결성 검증
	 */
	public function dispLog_adminAdminVerify()
	{
		$results = [];
		if (Context::get('do_verify') === 'Y')
		{
			foreach (AccessLogModel::CHAINS as $chain)
			{
				$results[$chain] = AccessLogModel::verifyChain($chain);
			}
		}
		else
		{
			foreach (AccessLogModel::CHAINS as $chain)
			{
				$results[$chain] = (object)['chain' => $chain, 'head' => AccessLogModel::getChainHead($chain), 'checked' => null, 'errors' => []];
			}
		}
		Context::set('verify_results', $results);
		Context::set('verify_run', Context::get('do_verify') === 'Y');
		$this->setTemplateFile('verify');
	}

	/**
	 * 점검 기록 및 점검 보조 통계 (제8조제2항)
	 */
	public function dispLog_adminAdminReview()
	{
		$start_date = $this->_checkDate(Context::get(self::FILTER_PREFIX . 'start_date')) ?: date('Y-m-01', strtotime('first day of last month'));
		$end_date = $this->_checkDate(Context::get(self::FILTER_PREFIX . 'end_date')) ?: date('Y-m-t', strtotime('last day of last month'));
		$range = ['start_date' => $start_date . ' 00:00:00', 'end_date' => $end_date . ' 23:59:59'];

		$by_task = [];
		foreach (executeQueryArray('log_admin.getAccessLogStatsByTask', $range)->data ?: [] as $row)
		{
			$by_task[$row->task_type] = intval($row->count);
		}
		$by_hour = array_fill(0, 24, 0);
		foreach (executeQueryArray('log_admin.getAccessLogStatsByHour', $range)->data ?: [] as $row)
		{
			$by_hour[intval($row->hour)] = intval($row->count);
		}

		Context::set('start_date', $start_date);
		Context::set('end_date', $end_date);
		Context::set('stats_by_task', $by_task);
		Context::set('stats_by_user', executeQueryArray('log_admin.getAccessLogStatsByUser', $range)->data ?: []);
		Context::set('stats_by_hour', $by_hour);
		Context::set('night_count', array_sum(array_slice($by_hour, 0, 6)) + $by_hour[22] + $by_hour[23]);

		$output = executeQueryArray('log_admin.getReviewList', ['page' => max(1, intval(Context::get('page')))]);
		Context::set('review_list', $output->data ?: []);
		Context::set('page_navigation', $output->page_navigation ?? null);
		$this->setTemplateFile('review');
	}

	/**
	 * 설정 화면
	 */
	public function dispLog_adminAdminConfig()
	{
		Context::set('config', ConfigModel::getConfig());
		Context::set('group_list', MemberModel::getGroups());
		Context::set('access_retention_choices', ConfigModel::ACCESS_RETENTION_CHOICES);
		Context::set('permission_retention_choices', ConfigModel::PERMISSION_RETENTION_CHOICES);
		Context::set('queue_enabled', (bool)config('queue.enabled'));
		Context::set('purge_task_registered', config('queue.enabled') ? Install::isPurgeTaskRegistered() : false);
		$this->setTemplateFile('config');
	}

	/**
	 * 설정 저장
	 */
	public function procLog_adminAdminSaveConfig()
	{
		$vars = Context::getRequestVars();
		$config = ConfigModel::getConfig();

		$config->enabled = ($vars->enabled ?? 'Y') === 'Y';
		$config->include_managers = ($vars->include_managers ?? 'Y') === 'Y';
		$config->target_groups = array_values(array_filter(array_map('intval', (array)($vars->target_groups ?? []))));
		$config->log_scope = ($vars->log_scope ?? 'all') === 'privacy' ? 'privacy' : 'all';
		$config->exclude_acts = $this->_splitLines($vars->exclude_acts ?? '', '/^[a-zA-Z0-9_]+$/');
		$config->store_request_vars = ($vars->store_request_vars ?? 'Y') === 'Y';
		$config->max_value_length = max(100, min(100000, intval($vars->max_value_length ?? 2000)));
		$config->mask_keys = $this->_splitLines($vars->mask_keys ?? '', '/^[a-zA-Z0-9_\-]+$/') ?: ConfigModel::DEFAULT_MASK_KEYS;

		$access_years = intval($vars->access_retention_years ?? 2);
		$permission_years = intval($vars->permission_retention_years ?? 3);
		if (!in_array($access_years, ConfigModel::ACCESS_RETENTION_CHOICES, true) || !in_array($permission_years, ConfigModel::PERMISSION_RETENTION_CHOICES, true))
		{
			throw new InvalidRequest;
		}
		$config->access_retention_years = $access_years;
		$config->permission_retention_years = $permission_years;

		$output = ConfigModel::setConfig($config);
		if (!$output->toBool())
		{
			return $output;
		}
		Install::registerPurgeTask();

		$this->setMessage('success_registed');
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispLog_adminAdminConfig'));
	}

	/**
	 * 점검 기록 등록
	 */
	public function procLog_adminAdminInsertReview()
	{
		$period_start = $this->_checkDate(Context::get('period_start'));
		$period_end = $this->_checkDate(Context::get('period_end'));
		if (!$period_start || !$period_end || $period_start > $period_end)
		{
			throw new InvalidRequest;
		}

		$output = executeQuery('log_admin.insertReview', [
			'member_srl' => $this->user->member_srl,
			'user_id' => $this->user->user_id,
			'nick_name' => $this->user->nick_name,
			'period_start' => str_replace('-', '', $period_start),
			'period_end' => str_replace('-', '', $period_end),
			'review_method' => utf8_trim(strval(Context::get('review_method'))),
			'findings' => utf8_trim(strval(Context::get('findings'))),
			'actions_taken' => utf8_trim(strval(Context::get('actions_taken'))),
			'regdate' => date('Y-m-d H:i:s'),
		]);
		if (!$output->toBool())
		{
			return $output;
		}

		$this->setMessage('success_registed');
		$this->setRedirectUrl(Context::get('success_return_url') ?: getNotEncodedUrl('', 'module', 'admin', 'act', 'dispLog_adminAdminReview'));
	}

	/**
	 * 검색 결과 CSV 내보내기
	 *
	 * 내보내기도 다운로드 행위이므로 직접 기록한 뒤 출력한다. (출력 후 종료하므로 자동 기록이 실행되지 않음)
	 */
	public function procLog_adminAdminExport()
	{
		$args = $this->_getFilterArgs();
		$filters = $this->_getFilters();

		EventHandlers::skipCurrentRequest();
		EventHandlers::writeLog($this->user, 'root', [
			'module' => 'log_admin',
			'act' => 'procLog_adminAdminExport',
			'task_type' => 'download',
			'target_type' => 'access_log',
			'subject_info' => 'export filters: ' . http_build_query(array_filter($filters)),
			'result' => 'success',
			'request_vars' => ClassifierModel::sanitizeRequestVars(Context::getRequestVars()),
		]);

		$columns = ['log_id', 'chain', 'seq', 'regdate', 'member_srl', 'user_id', 'nick_name', 'user_role', 'ipaddress',
			'user_agent', 'request_method', 'request_url', 'module', 'mid', 'act', 'task_type', 'target_type', 'target_srl',
			'subject_member_srl', 'subject_info', 'result', 'result_message', 'prev_hash', 'hash'];

		while (ob_get_level())
		{
			ob_end_clean();
		}
		header('Content-Type: text/csv; charset=UTF-8');
		header('Content-Disposition: attachment; filename="log_admin_' . date('YmdHis') . '.csv"');
		header('Cache-Control: no-store');
		$fp = fopen('php://output', 'w');
		fwrite($fp, "\xEF\xBB\xBF");
		fputcsv($fp, $columns, ',', '"', '');

		$args->after_log_id = 0;
		$args->list_count = 1000;
		do
		{
			$rows = executeQueryArray('log_admin.getAccessLogExport', $args)->data ?: [];
			foreach ($rows as $row)
			{
				$line = [];
				foreach ($columns as $column)
				{
					$line[] = $this->_csvCell($row->$column ?? '');
				}
				fputcsv($fp, $line, ',', '"', '');
				$args->after_log_id = $row->log_id;
			}
		}
		while (count($rows) === $args->list_count);

		fclose($fp);
		\Context::close();
		exit;
	}

	/**
	 * 검색 필터 (화면 표시용)
	 */
	protected function _getFilters(): array
	{
		$filters = [];
		foreach (self::FILTER_KEYS as $key)
		{
			$value = Context::get(self::FILTER_PREFIX . $key);
			$filters[$key] = is_scalar($value) ? trim(strval($value)) : '';
		}
		return $filters;
	}

	/**
	 * 검색 필터 → 쿼리 인자
	 */
	protected function _getFilterArgs(): object
	{
		$filters = $this->_getFilters();
		$args = new \stdClass;
		if ($date = $this->_checkDate($filters['start_date']))
		{
			$args->start_date = $date . ' 00:00:00';
		}
		if ($date = $this->_checkDate($filters['end_date']))
		{
			$args->end_date = $date . ' 23:59:59';
		}
		foreach (['user_id', 'ipaddress', 'act'] as $key)
		{
			if ($filters[$key] !== '')
			{
				$args->$key = $filters[$key];
			}
		}
		if (in_array($filters['task_type'], ClassifierModel::TASK_TYPES, true))
		{
			$args->task_type = $filters['task_type'];
		}
		if (in_array($filters['chain'], AccessLogModel::CHAINS, true))
		{
			$args->chain = $filters['chain'];
		}
		if (in_array($filters['result'], ['success', 'fail'], true))
		{
			$args->result = $filters['result'];
		}
		if (ctype_digit($filters['subject_member_srl']))
		{
			$args->subject_member_srl = intval($filters['subject_member_srl']);
		}
		return $args;
	}

	protected function _checkDate($value): ?string
	{
		$value = is_scalar($value) ? trim(strval($value)) : '';
		return preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) && strtotime($value) ? $value : null;
	}

	protected function _splitLines($value, string $regexp): array
	{
		$lines = array_map('trim', preg_split('/[\r\n,]+/', is_scalar($value) ? strval($value) : ''));
		return array_values(array_unique(array_filter($lines, fn($line) => preg_match($regexp, $line))));
	}

	/**
	 * CSV 수식 주입(CSV injection) 방지: 스프레드시트가 수식으로 해석하는 첫 글자 앞에 ' 추가
	 */
	protected function _csvCell($value): string
	{
		$value = strval($value);
		return preg_match('/^[=+\-@\t\r]/', $value) ? "'" . $value : $value;
	}
}

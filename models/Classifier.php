<?php

namespace Rhymix\Modules\Log_admin\Models;

use CommentModel;
use DocumentModel;
use FileModel;
use MemberModel;

/**
 * 개인정보 접속기록 관리 모듈 - 요청 분류 모델
 *
 * 개인정보취급자 판별, 수행업무 분류, 처리한 정보주체 식별, 요청 변수 마스킹을 담당한다.
 *
 * @author YJSoft <yjsoft@yjsoft.xyz>
 */
class Classifier
{
	/**
	 * 수행업무 유형
	 */
	public const TASK_TYPES = [
		'login', 'logout', 'list', 'view', 'create', 'update', 'delete',
		'download', 'permission', 'config', 'purge', 'other',
	];

	/**
	 * 권한 부여·변경·말소로 분류할 액션 패턴 (제5조제3항, 3년 보관 대상)
	 */
	public const PERMISSION_ACT_REGEXP = '/^proc[A-Za-z_]*(Grant|Manager|Permission|Scope|Group|procMemberAdminInsert$|SelectedMemberManage|MemberAdminInsert$|ChangeAdmin|IsAdmin)/';

	/**
	 * 다운로드(제8조제2항 점검 대상)로 분류할 액션 패턴
	 */
	public const DOWNLOAD_ACT_REGEXP = '/(Download|Export|Excel|Csv|Backup|FileOutput)/i';

	/**
	 * 로그인/로그아웃은 별도 트리거로 기록하므로 요청 단위 기록에서 제외
	 */
	public const SKIP_ACTS = ['procMemberLogin', 'procMemberLogout', 'dispMemberLogout'];

	/**
	 * 개인정보취급자 역할 판별
	 *
	 * @param object|null $member_info 회원 정보 (logged_info 또는 회원 정보 객체)
	 * @param object|null $grant 현재 요청 모듈의 권한 객체 (manager 판별용)
	 * @param bool $check_managed_modules grant 대신 module_admins 테이블로 모듈 관리자 여부 판별
	 * @return string|null root | handler | manager | null(대상 아님)
	 */
	public static function getRole($member_info, $grant = null, bool $check_managed_modules = false): ?string
	{
		if (!$member_info || empty($member_info->member_srl))
		{
			return null;
		}
		if (($member_info->is_admin ?? 'N') === 'Y')
		{
			return 'root';
		}

		$config = Config::getConfig();
		if (!empty($config->target_groups))
		{
			$group_list = $member_info->group_list ?? null;
			if (!is_array($group_list))
			{
				$group_list = MemberModel::getMemberGroups($member_info->member_srl) ?: [];
			}
			if (array_intersect(array_map('intval', array_keys($group_list)), array_map('intval', $config->target_groups)))
			{
				return 'handler';
			}
		}

		if (!empty($config->include_managers))
		{
			if ($grant && !empty($grant->manager))
			{
				return 'manager';
			}
			if ($check_managed_modules)
			{
				// 코어 module.getModuleAdmin 쿼리: 회원이 관리자로 지정된 모듈 목록
				$output = executeQueryArray('module.getModuleAdmin', ['member_srl' => $member_info->member_srl]);
				if ($output->toBool() && !empty($output->data))
				{
					return 'manager';
				}
			}
		}

		return null;
	}

	/**
	 * 액션 이름으로 수행업무 유형 분류
	 */
	public static function classifyTask(string $act, bool $has_target): string
	{
		if ($act === '')
		{
			return $has_target ? 'view' : 'list';
		}
		if (preg_match(self::DOWNLOAD_ACT_REGEXP, $act))
		{
			return 'download';
		}
		if (str_starts_with($act, 'proc'))
		{
			if (preg_match(self::PERMISSION_ACT_REGEXP, $act))
			{
				return 'permission';
			}
			if (preg_match('/(Delete|Remove|Trash|Destroy|Clear|Empty|Purge|Truncate|Withdraw|Leave)/', $act))
			{
				return 'delete';
			}
			if (preg_match('/(Config|Setup|Setting|Option)/', $act))
			{
				return 'config';
			}
			if (preg_match('/(Insert|Write|Add|Create|Register|Copy|Upload|Send)/', $act))
			{
				return 'create';
			}
			return 'update';
		}
		if (preg_match('/^(disp|get)/', $act))
		{
			return $has_target ? 'view' : 'list';
		}
		return 'other';
	}

	/**
	 * 처리 대상(정보주체) 식별
	 *
	 * @return object {target_type, target_srl, subject_member_srl, subject_info}
	 */
	public static function resolveTarget(object $vars, string $module, string $act): object
	{
		$result = (object)[
			'target_type' => '',
			'target_srl' => 0,
			'subject_member_srl' => 0,
			'subject_info' => '',
		];

		$member_srl = self::_toSrl($vars->member_srl ?? null);
		$file_srl = self::_toSrl($vars->file_srl ?? null);
		$comment_srl = self::_toSrl($vars->comment_srl ?? null);
		$document_srl = self::_toSrl($vars->document_srl ?? null);
		$is_member_act = ($module === 'member' || stripos($act, 'Member') !== false || stripos($act, 'Point') !== false);

		if ($member_srl && $is_member_act)
		{
			self::_fillMember($result, $member_srl);
		}
		elseif ($file_srl)
		{
			$result->target_type = 'file';
			$result->target_srl = $file_srl;
			$file = FileModel::getFile($file_srl, ['file_srl', 'sid', 'upload_target_srl', 'member_srl', 'source_filename']);
			if (is_object($file) && !empty($file->file_srl))
			{
				$result->subject_member_srl = intval($file->member_srl);
				$result->subject_info = 'file: ' . $file->source_filename . ' / upload_target_srl=' . $file->upload_target_srl;
				if ($result->subject_member_srl)
				{
					$result->subject_info .= ' / ' . self::_memberLabel($result->subject_member_srl);
				}
			}
		}
		elseif ($comment_srl)
		{
			$result->target_type = 'comment';
			$result->target_srl = $comment_srl;
			$oComment = CommentModel::getComment($comment_srl);
			if ($oComment->isExists())
			{
				$result->subject_member_srl = intval($oComment->get('member_srl'));
				$result->subject_info = self::_authorLabel($result->subject_member_srl, $oComment->getUserID(), $oComment->getNickName());
			}
		}
		elseif ($document_srl)
		{
			$result->target_type = 'document';
			$result->target_srl = $document_srl;
			$oDocument = DocumentModel::getDocument($document_srl, false, false, false);
			if ($oDocument->isExists())
			{
				$result->subject_member_srl = intval($oDocument->get('member_srl'));
				$result->subject_info = self::_authorLabel($result->subject_member_srl, $oDocument->getUserID(), $oDocument->getNickName());
			}
		}
		elseif ($member_srl)
		{
			self::_fillMember($result, $member_srl);
		}
		elseif (!empty($vars->cart))
		{
			// 여러 대상을 일괄 처리하는 경우 (게시물 관리 등)
			$cart = is_array($vars->cart) ? $vars->cart : preg_split('/[,|@]+/', strval($vars->cart));
			$cart = array_slice(array_filter(array_map([self::class, '_toSrl'], $cart)), 0, 30);
			$result->target_type = 'multiple';
			$result->subject_info = 'srls: ' . implode(',', $cart);
		}
		else
		{
			// 목록 조회: 조회 조건을 기록
			$conditions = [];
			foreach (['search_target', 'search_keyword', 'category_srl', 'selected_group_srl', 'filter_type', 'is_admin', 'page'] as $key)
			{
				if (isset($vars->$key) && is_scalar($vars->$key) && strval($vars->$key) !== '')
				{
					$conditions[] = $key . '=' . strval($vars->$key);
				}
			}
			if ($conditions)
			{
				$result->target_type = 'list';
				$result->subject_info = implode(', ', $conditions);
			}
		}

		$result->subject_info = mb_substr($result->subject_info, 0, 250);
		return $result;
	}

	/**
	 * 요청 변수 마스킹 및 길이 제한 후 JSON 문자열로 반환
	 */
	public static function sanitizeRequestVars($vars): string
	{
		$config = Config::getConfig();
		if (empty($config->store_request_vars))
		{
			return '';
		}
		$mask_regexp = self::_getMaskRegexp($config->mask_keys ?: Config::DEFAULT_MASK_KEYS);
		$max_length = max(100, intval($config->max_value_length ?: 2000));
		$data = self::_sanitize(json_decode(json_encode($vars ?: new \stdClass), true) ?: [], $mask_regexp, $max_length, 0);
		return json_encode($data, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES | \JSON_PRETTY_PRINT | \JSON_INVALID_UTF8_SUBSTITUTE) ?: '';
	}

	protected static function _sanitize($data, string $mask_regexp, int $max_length, int $depth)
	{
		if ($depth > 5)
		{
			return '(depth limit)';
		}
		if (is_array($data))
		{
			$result = [];
			foreach ($data as $key => $value)
			{
				if (is_string($key) && preg_match($mask_regexp, $key))
				{
					$result[$key] = ($value === '' || $value === null) ? $value : '********';
				}
				else
				{
					$result[$key] = self::_sanitize($value, $mask_regexp, $max_length, $depth + 1);
				}
			}
			return $result;
		}
		if (is_string($data) && mb_strlen($data) > $max_length)
		{
			return mb_substr($data, 0, $max_length) . sprintf('...(%d chars truncated)', mb_strlen($data) - $max_length);
		}
		return $data;
	}

	protected static function _getMaskRegexp(array $keys): string
	{
		$keys = array_filter(array_map('trim', $keys));
		return '/(' . implode('|', array_map(fn($key) => preg_quote($key, '/'), $keys)) . ')/i';
	}

	protected static function _toSrl($value): int
	{
		return (is_scalar($value) && ctype_digit(strval($value))) ? intval($value) : 0;
	}

	protected static function _fillMember(object $result, int $member_srl): void
	{
		$result->target_type = 'member';
		$result->target_srl = $member_srl;
		$result->subject_member_srl = $member_srl;
		$result->subject_info = self::_memberLabel($member_srl);
	}

	protected static function _memberLabel(int $member_srl): string
	{
		$member_info = MemberModel::getMemberInfoByMemberSrl($member_srl);
		if ($member_info && !empty($member_info->member_srl))
		{
			return sprintf('member_srl=%d / %s / %s', $member_srl, $member_info->user_id, $member_info->nick_name);
		}
		return sprintf('member_srl=%d', $member_srl);
	}

	protected static function _authorLabel(int $member_srl, $user_id, $nick_name): string
	{
		if ($member_srl)
		{
			return sprintf('author member_srl=%d / %s / %s', $member_srl, $user_id, $nick_name);
		}
		return sprintf('author (guest) / %s', $nick_name);
	}
}

<?php

namespace Rhymix\Modules\Log_admin\Models;

use Rhymix\Framework\DB;
use Rhymix\Framework\Security;

/**
 * 개인정보 접속기록 관리 모듈 - 접속기록 모델
 *
 * 접속기록은 체인(access / permission)별로 서명 체인으로 연결하여 저장한다.
 * - 각 기록의 hash = Security::createSignature(직전 hash + 기록 내용) (코어 HMAC 서명, 사이트 인증키 사용)
 * - (chain, seq) 유니크 인덱스로 순번 중복을 막고, 동시 기록 시 재시도한다.
 * - 체인 헤드(log_admin_chain)에 마지막 seq/hash를 따로 저장하여 끝부분 삭제도 탐지한다.
 * 이를 통해 「개인정보의 안전성 확보조치 기준」 제8조제3항(위ㆍ변조 방지)에 대응한다.
 *
 * @author YJSoft <yjsoft@yjsoft.xyz>
 */
class AccessLog
{
	public const CHAIN_ACCESS = 'access';
	public const CHAIN_PERMISSION = 'permission';
	public const CHAINS = [self::CHAIN_ACCESS, self::CHAIN_PERMISSION];

	/**
	 * 서명에 포함되는 컬럼 (순서 고정)
	 */
	public const HASHED_FIELDS = [
		'chain', 'seq', 'regdate', 'member_srl', 'user_id', 'nick_name', 'user_role',
		'ipaddress', 'user_agent', 'request_method', 'request_url', 'module', 'mid', 'act',
		'task_type', 'target_type', 'target_srl', 'subject_member_srl', 'subject_info',
		'result', 'result_message', 'request_vars',
	];

	/**
	 * 컬럼 길이 제한
	 */
	public const FIELD_LIMITS = [
		'user_id' => 80, 'nick_name' => 80, 'user_role' => 20, 'ipaddress' => 60, 'user_agent' => 250,
		'request_method' => 10, 'module' => 80, 'mid' => 80, 'act' => 100, 'task_type' => 20,
		'target_type' => 40, 'subject_info' => 250, 'result' => 10, 'result_message' => 250,
	];

	public const INT_FIELDS = ['seq', 'member_srl', 'target_srl', 'subject_member_srl'];

	/**
	 * 접속기록 1건 저장
	 *
	 * @param array $data 기록 내용 (chain, seq, prev_hash, hash는 자동 계산)
	 * @return int 저장된 log_id (실패 시 0)
	 */
	public static function insert(array $data): int
	{
		$data['chain'] = ($data['task_type'] ?? '') === 'permission' ? self::CHAIN_PERMISSION : self::CHAIN_ACCESS;
		$data['regdate'] = $data['regdate'] ?? date('Y-m-d H:i:s');
		$data = self::_normalize($data);

		for ($attempt = 0; $attempt < 5; $attempt++)
		{
			$last = executeQuery('log_admin.getLastAccessLog', ['chain' => $data['chain']])->data;
			if (is_array($last))
			{
				$last = reset($last) ?: null;
			}
			$data['seq'] = intval($last->seq ?? 0) + 1;
			$data['prev_hash'] = strval($last->hash ?? '');
			$data['hash'] = Security::createSignature(self::_getMessage($data, $data['prev_hash']));

			// (chain, seq) 유니크 인덱스: 동시에 같은 seq로 기록하려 하면 실패하므로 재시도
			$output = executeQuery('log_admin.insertAccessLog', $data);
			if ($output->toBool())
			{
				$log_id = DB::getInstance()->getInsertID();
				self::_updateHead($data['chain'], $data['seq'], $log_id, $data['hash']);
				return $log_id;
			}
			usleep(mt_rand(1000, 20000));
		}
		return 0;
	}

	/**
	 * 체인 헤드 조회
	 */
	public static function getChainHead(string $chain): ?object
	{
		$output = executeQuery('log_admin.getChainHead', ['chain' => $chain]);
		return ($output->toBool() && $output->data) ? $output->data : null;
	}

	/**
	 * 기록 1건의 서명 확인
	 */
	public static function verifyRecord(object $record): bool
	{
		return Security::verifySignature(self::_getMessage($record, strval($record->prev_hash)), strval($record->hash));
	}

	/**
	 * 체인 무결성 검증
	 *
	 * @return object {chain, checked, first_seq, last_seq, head, errors[]}
	 */
	public static function verifyChain(string $chain, int $max_errors = 100): object
	{
		$result = (object)['chain' => $chain, 'checked' => 0, 'first_seq' => 0, 'last_seq' => 0, 'errors' => [], 'head' => null];
		$head = self::getChainHead($chain);
		$result->head = $head;
		$expected_seq = $head ? intval($head->first_seq) : 1;
		$result->first_seq = $expected_seq;
		$prev_hash = null;
		$after_seq = 0;

		while (true)
		{
			$output = executeQueryArray('log_admin.getAccessLogsForVerify', ['chain' => $chain, 'after_seq' => $after_seq, 'list_count' => 1000]);
			if (!$output->toBool())
			{
				$result->errors[] = (object)['seq' => $after_seq, 'log_id' => 0, 'type' => 'query_error', 'message' => $output->getMessage()];
				break;
			}
			if (!$output->data)
			{
				break;
			}
			foreach ($output->data as $row)
			{
				$seq = intval($row->seq);
				if ($seq !== $expected_seq)
				{
					$result->errors[] = (object)['seq' => $expected_seq, 'log_id' => $row->log_id, 'type' => 'missing', 'message' => sprintf('seq %d ~ %d', $expected_seq, $seq - 1)];
					$prev_hash = null;
				}
				if ($prev_hash !== null && $prev_hash !== $row->prev_hash)
				{
					$result->errors[] = (object)['seq' => $seq, 'log_id' => $row->log_id, 'type' => 'broken_link', 'message' => 'prev_hash'];
				}
				if ($seq === 1 && $row->prev_hash !== '')
				{
					$result->errors[] = (object)['seq' => $seq, 'log_id' => $row->log_id, 'type' => 'broken_link', 'message' => 'first prev_hash'];
				}
				if (!self::verifyRecord($row))
				{
					$result->errors[] = (object)['seq' => $seq, 'log_id' => $row->log_id, 'type' => 'tampered', 'message' => 'signature'];
				}
				$prev_hash = $row->hash;
				$expected_seq = $seq + 1;
				$after_seq = $seq;
				$result->checked++;
				if (count($result->errors) >= $max_errors)
				{
					break 2;
				}
			}
		}

		$result->last_seq = $expected_seq - 1;
		if ($head)
		{
			if (intval($head->last_seq) !== $result->last_seq)
			{
				$result->errors[] = (object)['seq' => intval($head->last_seq), 'log_id' => 0, 'type' => 'truncated', 'message' => sprintf('head=%d, actual=%d', $head->last_seq, $result->last_seq)];
			}
			elseif ($prev_hash !== null && $head->last_hash !== $prev_hash)
			{
				$result->errors[] = (object)['seq' => $result->last_seq, 'log_id' => 0, 'type' => 'head_mismatch', 'message' => 'last_hash'];
			}
		}
		elseif ($result->checked > 0)
		{
			$result->errors[] = (object)['seq' => 0, 'log_id' => 0, 'type' => 'head_missing', 'message' => ''];
		}
		return $result;
	}

	/**
	 * 보관기간이 지난 기록 삭제
	 *
	 * 체인의 앞부분만 삭제하고 헤드의 first_seq를 갱신하므로, 삭제 후에도 나머지 체인 검증이 가능하다.
	 *
	 * @return int 삭제 건수
	 */
	public static function purge(string $chain, int $retention_years): int
	{
		if ($retention_years <= 0)
		{
			return 0;
		}
		$before = self::_getMinSeq($chain);
		$before_date = date('Y-m-d H:i:s', strtotime(sprintf('-%d years', $retention_years)));
		executeQuery('log_admin.deleteOldAccessLogs', ['chain' => $chain, 'before_date' => $before_date]);
		$after = self::_getMinSeq($chain);
		$deleted = $before->count - $after->count;
		if ($deleted > 0 && ($head = self::getChainHead($chain)))
		{
			executeQuery('log_admin.updateChainHeadFirst', [
				'chain' => $chain,
				'first_seq' => $after->count ? $after->min_seq : intval($head->last_seq) + 1,
				'updated' => date('Y-m-d H:i:s'),
			]);
		}
		return max(0, $deleted);
	}

	protected static function _updateHead(string $chain, int $seq, int $log_id, string $hash): void
	{
		$args = [
			'chain' => $chain,
			'first_seq' => 1,
			'last_seq' => $seq,
			'last_log_id' => $log_id,
			'last_hash' => $hash,
			'updated' => date('Y-m-d H:i:s'),
		];
		if (!self::getChainHead($chain))
		{
			executeQuery('log_admin.insertChainHead', $args);
		}
		// last_seq가 더 작은 경우에만 갱신하므로 동시 기록 시에도 헤드가 뒤로 가지 않는다.
		executeQuery('log_admin.updateChainHeadLast', $args);
	}

	protected static function _getMinSeq(string $chain): object
	{
		$output = executeQuery('log_admin.getMinSeq', ['chain' => $chain]);
		return (object)[
			'min_seq' => intval($output->data->min_seq ?? 0),
			'count' => intval($output->data->count ?? 0),
		];
	}

	protected static function _getMessage($record, string $prev_hash): string
	{
		$record = (array)$record;
		$payload = [$prev_hash];
		foreach (self::HASHED_FIELDS as $field)
		{
			$payload[] = strval($record[$field] ?? '');
		}
		return json_encode($payload, \JSON_UNESCAPED_UNICODE | \JSON_UNESCAPED_SLASHES);
	}

	protected static function _normalize(array $data): array
	{
		foreach (self::HASHED_FIELDS as $field)
		{
			$value = $data[$field] ?? '';
			if (in_array($field, self::INT_FIELDS, true))
			{
				$value = intval($value);
			}
			else
			{
				$value = utf8_clean(strval($value));
				if (isset(self::FIELD_LIMITS[$field]))
				{
					$value = mb_substr($value, 0, self::FIELD_LIMITS[$field]);
				}
			}
			$data[$field] = $value;
		}
		return $data;
	}
}

<?php

namespace Rhymix\Modules\Log_admin\Models;

use ModuleController;
use ModuleModel;

/**
 * 개인정보 접속기록 관리 모듈 - 설정 모델
 *
 * @author YJSoft <yjsoft@yjsoft.xyz>
 */
class Config
{
	/**
	 * 접속기록 보관기간(년) 선택지.
	 * 「개인정보의 안전성 확보조치 기준」 제8조제1항: 1년 이상, 일부 경우 2년 이상.
	 * 0은 자동 삭제하지 않음.
	 */
	public const ACCESS_RETENTION_CHOICES = [1, 2, 3, 5, 0];

	/**
	 * 권한 부여·변경·말소 기록 보관기간(년) 선택지.
	 * 같은 기준 제5조제3항: 최소 3년.
	 */
	public const PERMISSION_RETENTION_CHOICES = [3, 5, 0];

	/**
	 * 기본 마스킹 대상 요청 변수 (정규식 조각, 대소문자 무시)
	 */
	public const DEFAULT_MASK_KEYS = [
		'password', 'passwd', 'pw', 'pass', 'secret', 'token', 'csrf',
		'auth_key', 'authkey', 'auth_code', 'otp', 'captcha', 'api_key', 'apikey', 'private_key', 'cookie',
	];

	/**
	 * 기본값
	 */
	public const DEFAULTS = [
		'enabled' => true,
		'include_managers' => true,
		'target_groups' => [],
		'log_scope' => 'all',
		'exclude_acts' => [],
		'store_request_vars' => true,
		'max_value_length' => 2000,
		'mask_keys' => self::DEFAULT_MASK_KEYS,
		'access_retention_years' => 2,
		'permission_retention_years' => 3,
		'last_purge_date' => '',
		'purge_task_srl' => 0,
	];

	protected static $_cache = null;

	/**
	 * 설정 가져오기
	 */
	public static function getConfig(): object
	{
		if (self::$_cache === null)
		{
			$config = ModuleModel::getModuleConfig('log_admin') ?: new \stdClass;
			foreach (self::DEFAULTS as $key => $default)
			{
				if (!isset($config->$key))
				{
					$config->$key = $default;
				}
			}
			self::$_cache = $config;
		}
		return self::$_cache;
	}

	/**
	 * 설정 저장
	 */
	public static function setConfig(object $config): object
	{
		$output = ModuleController::getInstance()->insertModuleConfig('log_admin', $config);
		if ($output->toBool())
		{
			self::$_cache = $config;
		}
		return $output;
	}
}

<?php

namespace Rhymix\Modules\Log_admin\Controllers;

use BaseObject;
use Rhymix\Framework\Queue;
use Rhymix\Modules\Log_admin\Models\Config as ConfigModel;

/**
 * 개인정보 접속기록 관리 모듈 - 설치/업데이트
 *
 * @author YJSoft <yjsoft@yjsoft.xyz>
 */
class Install extends Base
{
	/**
	 * 모듈 설치
	 */
	public function moduleInstall()
	{
		// 기본 설정 저장 (설치 직후부터 기록 시작)
		ConfigModel::setConfig(ConfigModel::getConfig());
		self::registerPurgeTask();
		return new BaseObject;
	}

	/**
	 * 업데이트 필요 여부
	 */
	public function checkUpdate()
	{
		// 큐가 켜져 있는데 보관기간 정리 예약 작업이 없으면 업데이트 필요
		return config('queue.enabled') && !self::isPurgeTaskRegistered();
	}

	/**
	 * 업데이트
	 */
	public function moduleUpdate()
	{
		self::registerPurgeTask();
		return new BaseObject(0, 'success_updated');
	}

	/**
	 * 모듈 삭제
	 *
	 * 접속기록은 법정 보관기간 동안 보관해야 하므로 테이블을 삭제하지 않는다.
	 * 데이터 삭제가 필요하면 보관기간 경과 여부를 확인한 뒤 DB에서 직접 처리해야 한다.
	 */
	public function moduleUninstall()
	{
		$config = ConfigModel::getConfig();
		if (!empty($config->purge_task_srl) && config('queue.enabled'))
		{
			Queue::cancelScheduledTask(intval($config->purge_task_srl));
		}
		return new BaseObject;
	}

	/**
	 * 보관기간 정리 예약 작업 등록 여부
	 */
	public static function isPurgeTaskRegistered(): bool
	{
		$config = ConfigModel::getConfig();
		return !empty($config->purge_task_srl) && Queue::getScheduledTask(intval($config->purge_task_srl)) !== null;
	}

	/**
	 * 보관기간 정리 예약 작업 등록 (매일 03:30, 코어 Queue 사용)
	 */
	public static function registerPurgeTask(): void
	{
		if (!config('queue.enabled') || self::isPurgeTaskRegistered())
		{
			return;
		}
		$config = ConfigModel::getConfig();
		$config->purge_task_srl = Queue::addTaskAtInterval('30 3 * * *', EventHandlers::class . '::runScheduledPurge');
		ConfigModel::setConfig($config);
	}

	/**
	 * 캐시 재생성
	 */
	public function recompileCache()
	{

	}
}

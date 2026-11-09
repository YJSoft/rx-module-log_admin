<div class="x_page-header">
	<h1>{{ $lang->log_admin }}</h1>
</div>

@if (!empty($XE_VALIDATOR_MESSAGE) && str_starts_with(strval($XE_VALIDATOR_ID ?? ''), 'modules/log_admin/'))
	<div class="message {{ $XE_VALIDATOR_MESSAGE_TYPE }}">
		<p>{{ $XE_VALIDATOR_MESSAGE }}</p>
	</div>
@endif

<ul class="x_nav x_nav-tabs">
	<li @class(['x_active' => $act === 'dispLog_adminAdminList' || $act === 'dispLog_adminAdminDetail'])>
		<a href="@url(['module' => 'admin', 'act' => 'dispLog_adminAdminList'])">{{ $lang->log_admin_list }}</a>
	</li>
	<li @class(['x_active' => $act === 'dispLog_adminAdminVerify'])>
		<a href="@url(['module' => 'admin', 'act' => 'dispLog_adminAdminVerify'])">{{ $lang->log_admin_verify }}</a>
	</li>
	<li @class(['x_active' => $act === 'dispLog_adminAdminReview'])>
		<a href="@url(['module' => 'admin', 'act' => 'dispLog_adminAdminReview'])">{{ $lang->log_admin_review }}</a>
	</li>
	<li @class(['x_active' => $act === 'dispLog_adminAdminConfig'])>
		<a href="@url(['module' => 'admin', 'act' => 'dispLog_adminAdminConfig'])">{{ $lang->log_admin_config }}</a>
	</li>
</ul>

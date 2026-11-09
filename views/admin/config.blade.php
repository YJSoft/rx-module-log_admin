@include('header')

<form action="./" method="post" class="x_form-horizontal">
	<input type="hidden" name="module" value="log_admin" />
	<input type="hidden" name="act" value="procLog_adminAdminSaveConfig" />
	<input type="hidden" name="success_return_url" value="{{ getRequestUriByServerEnviroment() }}" />
	<input type="hidden" name="error_return_url" value="{{ getRequestUriByServerEnviroment() }}" />
	<input type="hidden" name="xe_validator_id" value="modules/log_admin/config/1" />

	<section class="section">
		<div class="x_control-group">
			<label class="x_control-label" for="enabled">{{ $lang->log_admin_enabled }}</label>
			<div class="x_controls">
				<select name="enabled" id="enabled">
					<option value="Y" @selected($config->enabled)>{{ $lang->cmd_yes }}</option>
					<option value="N" @selected(!$config->enabled)>{{ $lang->cmd_no }}</option>
				</select>
			</div>
		</div>
		<div class="x_control-group">
			<label class="x_control-label" for="include_managers">{{ $lang->log_admin_include_managers }}</label>
			<div class="x_controls">
				<select name="include_managers" id="include_managers">
					<option value="Y" @selected($config->include_managers)>{{ $lang->cmd_yes }}</option>
					<option value="N" @selected(!$config->include_managers)>{{ $lang->cmd_no }}</option>
				</select>
				<p class="x_help-block">{{ $lang->log_admin_include_managers_help }}</p>
			</div>
		</div>
		<div class="x_control-group">
			<label class="x_control-label">{{ $lang->log_admin_target_groups }}</label>
			<div class="x_controls">
				@foreach ($group_list as $group)
					<label class="x_inline">
						<input type="checkbox" name="target_groups[]" value="{{ $group->group_srl }}" @checked(in_array(intval($group->group_srl), $config->target_groups)) />
						{{ $group->title }}
					</label>
				@endforeach
				<p class="x_help-block">{{ $lang->log_admin_target_groups_help }}</p>
			</div>
		</div>
		<div class="x_control-group">
			<label class="x_control-label">{{ $lang->log_admin_log_scope }}</label>
			<div class="x_controls">
				<label class="x_inline"><input type="radio" name="log_scope" value="all" @checked($config->log_scope !== 'privacy') /> {{ $lang->log_admin_log_scope_all }}</label><br />
				<label class="x_inline"><input type="radio" name="log_scope" value="privacy" @checked($config->log_scope === 'privacy') /> {{ $lang->log_admin_log_scope_privacy }}</label>
				<p class="x_help-block">{{ $lang->log_admin_log_scope_help }}</p>
			</div>
		</div>
		<div class="x_control-group">
			<label class="x_control-label" for="exclude_acts">{{ $lang->log_admin_exclude_acts }}</label>
			<div class="x_controls">
				<textarea name="exclude_acts" id="exclude_acts" rows="4">{{ implode("\n", $config->exclude_acts) }}</textarea>
				<p class="x_help-block">{{ $lang->log_admin_exclude_acts_help }}</p>
			</div>
		</div>
	</section>

	<section class="section">
		<div class="x_control-group">
			<label class="x_control-label" for="store_request_vars">{{ $lang->log_admin_store_request_vars }}</label>
			<div class="x_controls">
				<select name="store_request_vars" id="store_request_vars">
					<option value="Y" @selected($config->store_request_vars)>{{ $lang->cmd_yes }}</option>
					<option value="N" @selected(!$config->store_request_vars)>{{ $lang->cmd_no }}</option>
				</select>
			</div>
		</div>
		<div class="x_control-group">
			<label class="x_control-label" for="max_value_length">{{ $lang->log_admin_max_value_length }}</label>
			<div class="x_controls">
				<input type="number" name="max_value_length" id="max_value_length" min="100" max="100000" value="{{ $config->max_value_length }}" />
			</div>
		</div>
		<div class="x_control-group">
			<label class="x_control-label" for="mask_keys">{{ $lang->log_admin_mask_keys }}</label>
			<div class="x_controls">
				<textarea name="mask_keys" id="mask_keys" rows="4">{{ implode("\n", $config->mask_keys) }}</textarea>
				<p class="x_help-block">{{ $lang->log_admin_mask_keys_help }}</p>
			</div>
		</div>
	</section>

	<section class="section">
		<div class="x_control-group">
			<label class="x_control-label" for="access_retention_years">{{ $lang->log_admin_access_retention }}</label>
			<div class="x_controls">
				<select name="access_retention_years" id="access_retention_years">
					@foreach ($access_retention_choices as $years)
						<option value="{{ $years }}" @selected(intval($config->access_retention_years) === $years)>{{ $years ? sprintf($lang->log_admin_years, $years) : $lang->log_admin_forever }}</option>
					@endforeach
				</select>
				<p class="x_help-block">{{ $lang->log_admin_access_retention_help }}</p>
			</div>
		</div>
		<div class="x_control-group">
			<label class="x_control-label" for="permission_retention_years">{{ $lang->log_admin_permission_retention }}</label>
			<div class="x_controls">
				<select name="permission_retention_years" id="permission_retention_years">
					@foreach ($permission_retention_choices as $years)
						<option value="{{ $years }}" @selected(intval($config->permission_retention_years) === $years)>{{ $years ? sprintf($lang->log_admin_years, $years) : $lang->log_admin_forever }}</option>
					@endforeach
				</select>
				<p class="x_help-block">{{ $lang->log_admin_permission_retention_help }}</p>
			</div>
		</div>
		<div class="x_control-group">
			<label class="x_control-label">{{ $lang->log_admin_purge_task }}</label>
			<div class="x_controls">
				@if (!$queue_enabled)
					{{ $lang->log_admin_purge_task_no_queue }}
				@elseif ($purge_task_registered)
					{{ $lang->log_admin_purge_task_ok }}
				@else
					{{ $lang->log_admin_purge_task_missing }}
				@endif
			</div>
		</div>
	</section>

	<div class="btnArea x_clearfix">
		<button type="submit" class="x_btn x_btn-primary x_pull-right">{{ $lang->cmd_registration }}</button>
	</div>
</form>

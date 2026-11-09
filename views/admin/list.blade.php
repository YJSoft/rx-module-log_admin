@include('header')

<p class="x_help-block">{{ $lang->log_admin_about }}</p>

<form action="./" method="get" class="x_form-inline search" style="margin:12px 0">
	<input type="hidden" name="module" value="admin" />
	<input type="hidden" name="act" value="dispLog_adminAdminList" />
	<input type="date" name="filter_start_date" value="{{ $filters['start_date'] }}" title="{{ $lang->log_admin_start_date }}" />
	~
	<input type="date" name="filter_end_date" value="{{ $filters['end_date'] }}" title="{{ $lang->log_admin_end_date }}" />
	<input type="text" name="filter_user_id" value="{{ $filters['user_id'] }}" placeholder="{{ $lang->user_id }}" style="width:110px" />
	<input type="text" name="filter_ipaddress" value="{{ $filters['ipaddress'] }}" placeholder="{{ $lang->log_admin_ipaddress }}" style="width:110px" />
	<input type="text" name="filter_subject_member_srl" value="{{ $filters['subject_member_srl'] }}" placeholder="{{ $lang->log_admin_subject_member_srl }}" style="width:120px" />
	<input type="text" name="filter_act" value="{{ $filters['act'] }}" placeholder="{{ $lang->log_admin_act }}" style="width:140px" />
	<select name="filter_task_type" title="{{ $lang->log_admin_task_type }}">
		<option value="">{{ $lang->log_admin_task_type }}: {{ $lang->log_admin_all }}</option>
		@foreach ($task_types as $task_type)
			<option value="{{ $task_type }}" @selected($filters['task_type'] === $task_type)>{{ $lang->log_admin_task_types[$task_type] }}</option>
		@endforeach
	</select>
	<select name="filter_chain" title="{{ $lang->log_admin_chain }}">
		<option value="">{{ $lang->log_admin_chain }}: {{ $lang->log_admin_all }}</option>
		<option value="access" @selected($filters['chain'] === 'access')>{{ $lang->log_admin_chains['access'] }}</option>
		<option value="permission" @selected($filters['chain'] === 'permission')>{{ $lang->log_admin_chains['permission'] }}</option>
	</select>
	<select name="filter_result" title="{{ $lang->log_admin_result }}">
		<option value="">{{ $lang->log_admin_result }}: {{ $lang->log_admin_all }}</option>
		<option value="success" @selected($filters['result'] === 'success')>{{ $lang->log_admin_results['success'] }}</option>
		<option value="fail" @selected($filters['result'] === 'fail')>{{ $lang->log_admin_results['fail'] }}</option>
	</select>
	<button type="submit" class="x_btn x_btn-inverse">{{ $lang->cmd_search }}</button>
	<a href="@url(['module' => 'admin', 'act' => 'dispLog_adminAdminList'])" class="x_btn">{{ $lang->cmd_cancel }}</a>
</form>

<form action="./" method="post" style="margin:0 0 12px">
	<input type="hidden" name="module" value="log_admin" />
	<input type="hidden" name="act" value="procLog_adminAdminExport" />
	@foreach ($filters as $key => $value)
		<input type="hidden" name="filter_{{ $key }}" value="{{ $value }}" />
	@endforeach
	<span>{{ sprintf($lang->log_admin_total, number_format($total_count)) }}</span>
	<button type="submit" class="x_btn x_btn-small">{{ $lang->log_admin_export }}</button>
	<span class="x_help-inline">{{ $lang->log_admin_export_help }}</span>
</form>

@if (count($log_list))
	<table class="x_table x_table-striped x_table-hover">
		<thead>
			<tr>
				<th>#</th>
				<th>{{ $lang->log_admin_regdate }}</th>
				<th>{{ $lang->log_admin_user }}</th>
				<th>{{ $lang->log_admin_ipaddress }}</th>
				<th>{{ $lang->log_admin_task_type }}</th>
				<th>{{ $lang->log_admin_act }}</th>
				<th>{{ $lang->log_admin_subject }}</th>
				<th>{{ $lang->log_admin_result }}</th>
			</tr>
		</thead>
		<tbody>
			@foreach ($log_list as $log)
				<tr>
					<td><a href="@url(['module' => 'admin', 'act' => 'dispLog_adminAdminDetail', 'log_id' => $log->log_id])">{{ $log->log_id }}</a></td>
					<td style="white-space:nowrap">{{ $log->regdate }}</td>
					<td>
						{{ $log->user_id }} ({{ $log->nick_name }})<br />
						<small>{{ $lang->log_admin_roles[$log->user_role] ?? $log->user_role }}</small>
					</td>
					<td>{{ $log->ipaddress }}</td>
					<td>{{ $lang->log_admin_task_types[$log->task_type] ?? $log->task_type }}</td>
					<td>
						{{ $log->act }}
						@if ($log->mid)
							<br /><small>mid: {{ $log->mid }}</small>
						@endif
					</td>
					<td>
						@if ($log->target_type)
							<small>{{ $log->target_type }} {{ $log->target_srl ? "#" . $log->target_srl : "" }}</small><br />
						@endif
						{{ $log->subject_info }}
					</td>
					<td>
						{{ $lang->log_admin_results[$log->result] ?? $log->result }}
						@if ($log->result_message)
							<br /><small>{{ $log->result_message }}</small>
						@endif
					</td>
				</tr>
			@endforeach
		</tbody>
	</table>
	@include('pagination')
@else
	<p class="x_empty-message">{{ $lang->log_admin_no_records }}</p>
@endif

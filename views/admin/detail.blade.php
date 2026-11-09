@include('header')

<h2>{{ $lang->log_admin_detail }} #{{ $log->log_id }}</h2>

<table class="x_table x_table-bordered">
	<tbody>
		<tr><th style="width:200px">{{ $lang->log_admin_signature }}</th>
			<td>
				@if ($signature_valid)
					<strong style="color:green">{{ $lang->log_admin_signature_valid }}</strong>
				@else
					<strong style="color:red">{{ $lang->log_admin_signature_invalid }}</strong>
				@endif
				<br /><small>{{ $lang->log_admin_chains[$log->chain] ?? $log->chain }} / seq {{ $log->seq }}</small>
			</td>
		</tr>
		<tr><th>{{ $lang->log_admin_regdate }}</th><td>{{ $log->regdate }}</td></tr>
		<tr><th>{{ $lang->log_admin_user }}</th><td>member_srl={{ $log->member_srl }} / {{ $log->user_id }} / {{ $log->nick_name }}</td></tr>
		<tr><th>{{ $lang->log_admin_user_role }}</th><td>{{ $lang->log_admin_roles[$log->user_role] ?? $log->user_role }}</td></tr>
		<tr><th>{{ $lang->log_admin_ipaddress }}</th><td>{{ $log->ipaddress }}</td></tr>
		<tr><th>{{ $lang->log_admin_user_agent }}</th><td>{{ $log->user_agent }}</td></tr>
		<tr><th>{{ $lang->log_admin_request_method }}</th><td>{{ $log->request_method }}</td></tr>
		<tr><th>{{ $lang->log_admin_request_url }}</th><td style="word-break:break-all">{{ $log->request_url }}</td></tr>
		<tr><th>{{ $lang->log_admin_task_type }}</th><td>{{ $lang->log_admin_task_types[$log->task_type] ?? $log->task_type }}</td></tr>
		<tr><th>{{ $lang->log_admin_act }}</th><td>module={{ $log->module }} / mid={{ $log->mid }} / act={{ $log->act }}</td></tr>
		<tr><th>{{ $lang->log_admin_target }}</th><td>{{ $log->target_type }} #{{ $log->target_srl }}</td></tr>
		<tr><th>{{ $lang->log_admin_subject }}</th>
			<td>
				@if ($log->subject_member_srl)
					<a href="@url(['module' => 'admin', 'act' => 'dispLog_adminAdminList', 'filter_subject_member_srl' => $log->subject_member_srl])">member_srl={{ $log->subject_member_srl }}</a><br />
				@endif
				{{ $log->subject_info }}
			</td>
		</tr>
		<tr><th>{{ $lang->log_admin_result }}</th><td>{{ $lang->log_admin_results[$log->result] ?? $log->result }} {{ $log->result_message }}</td></tr>
		<tr><th>{{ $lang->log_admin_request_vars }}</th><td><pre style="max-height:500px;overflow:auto;white-space:pre-wrap;word-break:break-all">{{ $log->request_vars }}</pre></td></tr>
		<tr><th>prev_hash / hash</th><td><code>{{ $log->prev_hash }}</code><br /><code>{{ $log->hash }}</code></td></tr>
	</tbody>
</table>

<p><a href="@url(['module' => 'admin', 'act' => 'dispLog_adminAdminList'])" class="x_btn">{{ $lang->cmd_list }}</a></p>

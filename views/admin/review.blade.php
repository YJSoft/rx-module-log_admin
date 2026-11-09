@include('header')

<p class="x_help-block">{{ $lang->log_admin_review_help }}</p>

<form action="./" method="get" class="x_form-inline" style="margin:12px 0">
	<input type="hidden" name="module" value="admin" />
	<input type="hidden" name="act" value="dispLog_adminAdminReview" />
	<input type="date" name="filter_start_date" value="{{ $start_date }}" /> ~ <input type="date" name="filter_end_date" value="{{ $end_date }}" />
	<button type="submit" class="x_btn x_btn-inverse">{{ $lang->cmd_search }}</button>
</form>

<section class="section">
	<h2>{{ $lang->log_admin_review_stats }} ({{ $start_date }} ~ {{ $end_date }})</h2>
	<div style="display:flex;flex-wrap:wrap;gap:24px">
		<div>
			<h3>{{ $lang->log_admin_review_by_task }}</h3>
			<table class="x_table x_table-bordered" style="width:auto">
				<tbody>
					@foreach ($task_types as $task_type)
						<tr>
							<th>{{ $lang->log_admin_task_types[$task_type] }}</th>
							<td style="text-align:right">
								<a href="@url(['module' => 'admin', 'act' => 'dispLog_adminAdminList', 'filter_task_type' => $task_type, 'filter_start_date' => $start_date, 'filter_end_date' => $end_date])">{{ number_format($stats_by_task[$task_type] ?? 0) }}</a>
							</td>
						</tr>
					@endforeach
					<tr><th>{{ $lang->log_admin_review_night }}</th><td style="text-align:right">{{ number_format($night_count) }}</td></tr>
				</tbody>
			</table>
		</div>
		<div>
			<h3>{{ $lang->log_admin_review_by_user }}</h3>
			<table class="x_table x_table-bordered" style="width:auto">
				<thead>
					<tr><th>{{ $lang->log_admin_user }}</th><th>{{ $lang->log_admin_count }}</th><th>{{ $lang->log_admin_review_ip_count }}</th></tr>
				</thead>
				<tbody>
					@forelse ($stats_by_user as $row)
						<tr>
							<td><a href="@url(['module' => 'admin', 'act' => 'dispLog_adminAdminList', 'filter_user_id' => $row->user_id, 'filter_start_date' => $start_date, 'filter_end_date' => $end_date])">{{ $row->user_id }}</a> ({{ $row->nick_name }})</td>
							<td style="text-align:right">{{ number_format($row->count) }}</td>
							<td style="text-align:right">{{ number_format($row->ip_count) }}</td>
						</tr>
					@empty
						<tr><td colspan="3">{{ $lang->log_admin_no_records }}</td></tr>
					@endforelse
				</tbody>
			</table>
		</div>
		<div>
			<h3>{{ $lang->log_admin_review_by_hour }}</h3>
			<table class="x_table x_table-bordered" style="width:auto">
				<tbody>
					@foreach ($stats_by_hour as $hour => $count)
						<tr><th>{{ sprintf('%02d', $hour) }}</th><td style="text-align:right">{{ number_format($count) }}</td></tr>
					@endforeach
				</tbody>
			</table>
		</div>
	</div>
</section>

<section class="section">
	<h2>{{ $lang->log_admin_review_insert }}</h2>
	<form action="./" method="post" class="x_form-horizontal">
		<input type="hidden" name="module" value="log_admin" />
		<input type="hidden" name="act" value="procLog_adminAdminInsertReview" />
		<input type="hidden" name="success_return_url" value="{{ getRequestUriByServerEnviroment() }}" />
		<input type="hidden" name="error_return_url" value="{{ getRequestUriByServerEnviroment() }}" />
		<input type="hidden" name="xe_validator_id" value="modules/log_admin/review/1" />
		<div class="x_control-group">
			<label class="x_control-label">{{ $lang->log_admin_period }}</label>
			<div class="x_controls">
				<input type="date" name="period_start" value="{{ $start_date }}" required /> ~ <input type="date" name="period_end" value="{{ $end_date }}" required />
			</div>
		</div>
		<div class="x_control-group">
			<label class="x_control-label" for="review_method">{{ $lang->log_admin_review_method }}</label>
			<div class="x_controls"><input type="text" name="review_method" id="review_method" class="x_full-width" maxlength="250" /></div>
		</div>
		<div class="x_control-group">
			<label class="x_control-label" for="findings">{{ $lang->log_admin_findings }}</label>
			<div class="x_controls"><textarea name="findings" id="findings" class="x_full-width" rows="4" required></textarea></div>
		</div>
		<div class="x_control-group">
			<label class="x_control-label" for="actions_taken">{{ $lang->log_admin_actions_taken }}</label>
			<div class="x_controls"><textarea name="actions_taken" id="actions_taken" class="x_full-width" rows="3"></textarea></div>
		</div>
		<div class="btnArea x_clearfix">
			<button type="submit" class="x_btn x_btn-primary x_pull-right">{{ $lang->cmd_registration }}</button>
		</div>
	</form>
</section>

<section class="section">
	<h2>{{ $lang->log_admin_review_list }}</h2>
	@if (count($review_list))
		<table class="x_table x_table-striped">
			<thead>
				<tr>
					<th>{{ $lang->log_admin_period }}</th>
					<th>{{ $lang->log_admin_reviewer }}</th>
					<th>{{ $lang->log_admin_review_method }}</th>
					<th>{{ $lang->log_admin_findings }}</th>
					<th>{{ $lang->log_admin_actions_taken }}</th>
					<th>{{ $lang->regdate }}</th>
				</tr>
			</thead>
			<tbody>
				@foreach ($review_list as $review)
					<tr>
						<td style="white-space:nowrap">{{ zdate($review->period_start, 'Y-m-d') }} ~ {{ zdate($review->period_end, 'Y-m-d') }}</td>
						<td>{{ $review->user_id }} ({{ $review->nick_name }})</td>
						<td>{{ $review->review_method }}</td>
						<td>{{ $review->findings|nl2br }}</td>
						<td>{{ $review->actions_taken|nl2br }}</td>
						<td style="white-space:nowrap">{{ $review->regdate }}</td>
					</tr>
				@endforeach
			</tbody>
		</table>
		@include('pagination')
	@else
		<p class="x_empty-message">{{ $lang->log_admin_no_records }}</p>
	@endif
</section>

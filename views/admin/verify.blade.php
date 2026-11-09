@include('header')

<p class="x_help-block">{{ $lang->log_admin_verify_help }}</p>

<form action="./" method="get" style="margin:12px 0">
	<input type="hidden" name="module" value="admin" />
	<input type="hidden" name="act" value="dispLog_adminAdminVerify" />
	<input type="hidden" name="do_verify" value="Y" />
	<button type="submit" class="x_btn x_btn-primary">{{ $lang->log_admin_verify_run }}</button>
</form>

@foreach ($verify_results as $chain => $result)
	<section class="section">
		<h2>{{ $lang->log_admin_chains[$chain] ?? $chain }}</h2>
		<table class="x_table x_table-bordered">
			<tbody>
				<tr>
					<th style="width:200px">{{ $lang->log_admin_verify_head }}</th>
					<td>
						@if ($result->head)
							seq {{ $result->head->first_seq }} ~ {{ $result->head->last_seq }} (log_id {{ $result->head->last_log_id }}, {{ $result->head->updated }})
						@else
							-
						@endif
					</td>
				</tr>
				<tr>
					<th>{{ $lang->log_admin_result }}</th>
					<td>
						@if (!$verify_run)
							{{ $lang->log_admin_verify_not_run }}
						@elseif (count($result->errors))
							<strong style="color:red">{{ $lang->log_admin_verify_error }} ({{ count($result->errors) }})</strong>
						@else
							<strong style="color:green">{{ $lang->log_admin_verify_ok }}</strong>
						@endif
					</td>
				</tr>
				@if ($verify_run)
					<tr>
						<th>{{ $lang->log_admin_verify_checked }}</th>
						<td>{{ number_format($result->checked) }} ({{ $lang->log_admin_verify_range }}: {{ $result->first_seq }} ~ {{ $result->last_seq }})</td>
					</tr>
				@endif
			</tbody>
		</table>
		@if (count($result->errors))
			<table class="x_table x_table-striped">
				<thead>
					<tr><th>seq</th><th>log_id</th><th>{{ $lang->log_admin_verify_error }}</th><th></th></tr>
				</thead>
				<tbody>
					@foreach ($result->errors as $error)
						<tr>
							<td>{{ $error->seq }}</td>
							<td>
								@if ($error->log_id)
									<a href="@url(['module' => 'admin', 'act' => 'dispLog_adminAdminDetail', 'log_id' => $error->log_id])">{{ $error->log_id }}</a>
								@endif
							</td>
							<td>{{ $lang->log_admin_verify_error_types[$error->type] ?? $error->type }}</td>
							<td>{{ $error->message }}</td>
						</tr>
					@endforeach
				</tbody>
			</table>
		@endif
	</section>
@endforeach

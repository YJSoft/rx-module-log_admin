@if ($page_navigation && $page_navigation->total_page > 1)
	<div class="x_pagination x_pagination-centered">
		<ul>
			<li @class(['x_disabled' => $page_navigation->cur_page == 1])>
				<a href="{{ getUrl('page', '') }}">&laquo; {{ $lang->first_page }}</a>
			</li>
			@while ($page_no = $page_navigation->getNextPage())
				<li @class(['x_active' => $page_no == $page_navigation->cur_page])>
					<a href="{{ getUrl('page', $page_no) }}">{{ $page_no }}</a>
				</li>
			@endwhile
			<li @class(['x_disabled' => $page_navigation->cur_page == $page_navigation->last_page])>
				<a href="{{ getUrl('page', $page_navigation->last_page) }}">{{ $lang->last_page }} &raquo;</a>
			</li>
		</ul>
	</div>
@endif

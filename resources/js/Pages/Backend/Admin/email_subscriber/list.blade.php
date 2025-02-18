<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Email Subscribers" page="home" subpage="list" />

		<div class="box">
			<div class="box-header sm:flex items-center justify-between">
				<h5>{{ _lang('Email Subscribers') }}</h5>
				<div>
					<x-secondary-button>
						<a href="{{ route('email_subscribers.export') }}"><i class="ri-file-excel-2-line mr-1"></i>{{ _lang('Export') }}</a>
					</x-secondary-button>
					<x-primary-button>
						<a href="{{ route('email_subscribers.send_email') }}"><i class="ri-send-plane-line mr-1"></i>{{ _lang('Send Email') }}</a>
					</x-primary-button>
				</div>
			</div>
			<div class="box-body">
				<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
					<table id="datatable" class="ti-custom-table ti-custom-table-head whitespace-nowrap">
						<thead>
							<tr>
								<th>{{ _lang('Subscribed At') }}</th>
								<th>{{ _lang('Email Address') }}</th>
								<th>{{ _lang('Ip Address') }}</th>
								<th class="text-center">{{ _lang('Action') }}</th>
							</tr>
						</thead>
						<tbody>
							@foreach($emailsubscribers as $emailsubscriber)
							<tr data-id="row_{{ $emailsubscriber->id }}">
								<td class='name'>{{ $emailsubscriber->created_at }}</td>
								<td class='name'>{{ $emailsubscriber->email_address }}</td>
								<td class='name'>{{ $emailsubscriber->ip_address }}</td>

								<td>
									<div class="hs-dropdown ti-dropdown">
										<button id="hs-dropdown-with-icons" type="button" class="hs-dropdown-toggle ti-dropdown-toggle">
											Actions
											<svg class="hs-dropdown-open:rotate-180 ti-dropdown-caret" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path d="M2 5L8.16086 10.6869C8.35239 10.8637 8.64761 10.8637 8.83914 10.6869L15 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
											</svg>
										</button>
										<div class="hs-dropdown-menu ti-dropdown-menu divide-y divide-gray-200" aria-labelledby="hs-dropdown-with-icons">
											<div class="ti-dropdown-divider">
												<a class="ti-dropdown-item" href="javascript:void(0);">
													<i class="ri-delete-bin-line text-lg"></i>
													<form action="{{ route('email_subscribers.destroy', $emailsubscriber['id']) }}" method="post">
														{{ csrf_field() }}
														<input name="_method" type="hidden" value="DELETE">
														<button type="submit">Delete</button>
													</form>
												</a>
											</div>
										</div>
									</div>
								</td>
							</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>

@section('js-script')
<script>
	(function($) {
		"use strict";

		$('#email_subscribers_table').DataTable({
			processing: true,
			serverSide: true,
			ajax: '{{ url('
			admin / email_subscribers / get_table_data ') }}',
			"columns": [{
					data: 'created_at',
					name: 'created_at'
				},
				{
					data: 'email_address',
					name: 'email_address'
				},
				{
					data: 'ip_address',
					name: 'ip_address'
				},
				{
					data: "action",
					name: "action"
				},
			],
			responsive: true,
			"bStateSave": true,
			"bAutoWidth": false,
			"ordering": false,
			"language": {
				"decimal": "",
				"emptyTable": "{{ _lang('No Data Found') }}",
				"info": "{{ _lang('Showing') }} _START_ {{ _lang('to') }} _END_ {{ _lang('of') }} _TOTAL_ {{ _lang('Entries') }}",
				"infoEmpty": "{{ _lang('Showing 0 To 0 Of 0 Entries') }}",
				"infoFiltered": "(filtered from _MAX_ total entries)",
				"infoPostFix": "",
				"thousands": ",",
				"lengthMenu": "{{ _lang('Show') }} _MENU_ {{ _lang('Entries') }}",
				"loadingRecords": "{{ _lang('Loading...') }}",
				"processing": "{{ _lang('Processing...') }}",
				"search": "{{ _lang('Search') }}",
				"zeroRecords": "{{ _lang('No matching records found') }}",
				"paginate": {
					"first": "{{ _lang('First') }}",
					"last": "{{ _lang('Last') }}",
					"previous": "<i class='fas fa-angle-left'></i>",
					"next": "<i class='fas fa-angle-right'></i>"
				}
			}
		});
	})(jQuery);
</script>
@endsection
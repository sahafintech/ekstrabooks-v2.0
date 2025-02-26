<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Received Payments" page="user" subpage="list" />

		<div class="box">
			<div class="box-header flex items-center justify-between">
				<h5>{{ _lang('Received Payments') }}</h5>
				<x-primary-button>
					<a href="{{ route('receive_payments.create') }}"><i class="ri-add-line mr-1"></i>{{ _lang('Add New') }}</a>
				</x-primary-button>
			</div>
			<div class="box-body">
				<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
					<table id="datatable" class="ti-custom-table ti-custom-table-head whitespace-nowrap">
						<thead>
							<tr>
								<th>{{ _lang('Date') }}</th>
								<th>{{ _lang('Customer') }}</th>
								<th>{{ _lang('Invoice') }}</th>
								<th>{{ _lang('Method') }}</th>
								<th>{{ _lang('Type') }}</th>
								<th>{{ _lang('Amount') }}</th>
								<th>{{ _lang('Action') }}</th>
							</tr>
						</thead>
						<tbody>
							@foreach($receive_payments as $receive_payment)
							<tr>
								<td>{{ $receive_payment->date }}</td>
								<td>{{ $receive_payment->customer->name }}</td>
								<td class="!text-wrap">
									<a href="{{ route('receive_payments.show', $receive_payment['id']) }}" class="hover:text-primary hover:underline">
										@if(count($receive_payment->invoices) > 1)
										<span><span class="font-bold">{{ count($receive_payment->invoices) }}</span>, {{ _lang('Invoices Payment') }}</span>
										@else
										<span><span class="font-bold">{{ count($receive_payment->invoices) }}</span>, {{ _lang('Invoice Payment') }}</span>
										@endif
									</a>
								</td>
								<td>{{ $receive_payment->payment_method}}</td>
								<td>
									@if($receive_payment->type == 'offline')
									<span class="text-secondary">{{ _lang('Offline') }}</span>
									@else
									<span class="text-success">{{ _lang('Online') }}</span>
									@endif
								</td>
								<td>{{ formatAmount($receive_payment->amount, currency_symbol(request()->activeBusiness->currency), $receive_payment->business_id) }}</td>
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
												<a class="ti-dropdown-item" href="{{ route('receive_payments.edit', $receive_payment['id']) }}">
													<i class="ri-edit-box-line text-lg"></i>
													Edit
												</a>
												<a class="ti-dropdown-item" href="{{ route('receive_payments.show', $receive_payment['id']) }}">
													<i class="ri-eye-line text-lg"></i>
													View
												</a>
												<a class="ti-dropdown-item" href="javascript:void(0);" data-hs-overlay="#delete-modal" data-id="{{ $receive_payment['id'] }}" id="delete">
													<i class="ri-delete-bin-line text-lg"></i>
													Delete
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
		<x-modal>
			<form method="post">
				{{ csrf_field() }}
				<input name="_method" type="hidden" value="DELETE">
				<div class="ti-modal-header">
					<h3 class="ti-modal-title">
						Delete Modal
					</h3>
					<button type="button" class="hs-dropdown-toggle ti-modal-close-btn" data-hs-overlay="#delete-modal">
						<span class="sr-only">Close</span>
						<i class="ri-close-line text-xl"></i>
					</button>
				</div>
				<div class="ti-modal-body">

					<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
						{{ __('Are you sure you want to delete the payment?') }}
					</h2>

				</div>
				<div class="ti-modal-footer">
					<x-secondary-button data-hs-overlay="#delete-modal">
						{{ __('Cancel') }}
					</x-secondary-button>

					<x-danger-button class="ml-3 submit-btn" type="submit">
						{{ __('Delete Payment') }}
					</x-danger-button>
				</div>
			</form>
		</x-modal>
	</div>
</x-app-layout>

<script>
	$(document).on('click', '#delete', function() {
		var id = $(this).data('id');
		$('#delete-modal form').attr('action', '/user/receive_payments/' + id);
	});
</script>
<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Deffered Invoices" page="user" subpage="list" />

		<div class="grid grid-cols-12 gap-x-3">
			<!-- <div class="col-span-12">
				<div class="box">
					<div class="box-body">
						<div class="flex flex-col">
							<a class="ti-list-group gap-x-2 ti-icon-link focus:ring-primary dark:border-white/10 {{ isset($_GET['tab']) ? '' : 'text-primary'  }}" href="{{ route('deffered_invoices.index') }}">
								<i class="ri-article-line text-xl"></i>
								{{ _lang('Invoice List') }}
							</a>
							<a class="ti-list-group gap-x-2 ti-icon-link focus:ring-primary dark:border-white/10 {{ isset($_GET['tab']) && $_GET['tab'] == 'received_payments' ? 'text-primary' : ''  }}" href="{{ route('deffered_invoices.index') }}?tab=received_payments">
								<i class="ri-wallet-3-line text-xl"></i>
								{{ _lang('Received Payments') }}
							</a>
							<a class="ti-list-group gap-x-2 ti-icon-link focus:ring-primary dark:border-white/10 {{ isset($_GET['tab']) && $_GET['tab'] == 'create_payments' ? 'text-primary' : ''  }}" href="{{ route('deffered_invoices.index') }}?tab=create_payments">
								<i class="ri-money-dollar-box-line text-xl"></i>
								{{ _lang('Create Payments') }}
							</a>
						</div>
					</div>
				</div>
			</div> -->

			<div class="col-span-12">
				<!-- @if(! isset($_GET['tab'])) -->
				<div class="box">
					<div class="box-header flex items-center justify-between">
						<h5>{{ _lang('Invoices') }}</h5>
						<div>
							<a href="{{ route('deffered_invoices.create') }}">
								<x-primary-button>
									<i class="ri-add-line mr-1"></i>
									{{ _lang('Add New Invoice') }}
								</x-primary-button>
							</a>
							<x-secondary-button data-hs-overlay="#import-modal">
								<i class="ri-upload-cloud-2-line ml-1"></i>
								Import Invoices
							</x-secondary-button>
						</div>
					</div>
					<div class="box-body">
						<form method="POST" action="{{ route('deffered_invoices.filter') }}">
							@csrf
							<div class="grid grid-cols-12 gap-x-2">
								<div class="lg:col-span-3 col-span-12 mb-2">
									<select class="w-full selectize auto-select" name="customer_id" data-selected="{{ $customer_id ?? '' }}">
										<option value="">{{ _lang('All Customers') }}</option>
										@foreach(\App\Models\Customer::all() as $customer)
										<option value="{{ $customer->id }}">{{ $customer->name }}</option>
										@endforeach
									</select>
								</div>

								<div class="lg:col-span-3 col-span-12 mb-2">
									<select class="w-full selectize auto-select" name="status" data-selected="{{ $status ?? '' }}">
										<option value="1">{{ _lang('Active') }}</option>
										<option value="4">{{ _lang('Expired') }}</option>
									</select>
								</div>

								<div class="lg:col-span-3 col-span-12">
									<input type="text" class="flatpickr-input w-full" value="{{ $date_range ?? '' }}" id="daterange" autocomplete="off" placeholder="{{ _lang('Date Range') }}" name="date_range">
								</div>

								<div class="lg:col-span-3 col-span-12">
									<x-secondary-button type="submit">
										<i class="ri-filter-line"></i>
										{{ _lang('Filter') }}
									</x-secondary-button>
								</div>
							</div>
						</form>
						<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto mt-3">
							<table id="datatable" class="ti-custom-table ti-custom-table-head whitespace-nowrap">
								<thead>
									<tr>
										<th>{{ _lang('Policy Start') }}</th>
										<th>{{ _lang('Policy End') }}</th>
										<th>{{ _lang('Policy Number') }}</th>
										<th>{{ _lang('Customer') }}</th>
										<th class="text-right">{{ _lang('Grand Total') }}</th>
										<th class="text-right">{{ _lang('Due Amount') }}</th>
										<th class="text-center">{{ _lang('Status') }}</th>
										<th class="text-center">{{ _lang('Action') }}</th>
									</tr>
								</thead>
								<tbody>
									@foreach($invoices as $invoice)
									<tr data-id="row_{{ $invoice->id }}">
										<td>{{ $invoice->deffered_start }}</td>
										<td>{{ $invoice->deffered_end }}</td>
										<td>{{ $invoice->order_number }}</td>
										<td>{{ $invoice->customer->name }}</td>
										<td>
											{{ formatAmount($invoice->grand_total, currency_symbol(request()->activeBusiness->currency), $invoice->business_id) }}
											@if($invoice->grand_total != $invoice->converted_total)
											({{ formatAmount($invoice->converted_total, currency_symbol($invoice->currency), $invoice->business_id) }})
											@endif
										</td>
										<td>
											{{ formatAmount($invoice->grand_total - $invoice->paid, currency_symbol(request()->activeBusiness->currency), $invoice->business_id) }}
											@if($invoice->grand_total != $invoice->converted_total)
											({{ formatAmount($invoice->converted_total - $invoice->converted_paid, currency_symbol($invoice->currency), $invoice->business_id) }})
											@endif
										</td>
										<td>
											{!! xss_clean(policy_status($invoice)) !!}
										</td>
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
														<a class="ti-dropdown-item" href="{{ route('deffered_invoices.edit', $invoice['id']) }}">
															<i class="ri-edit-box-line text-lg"></i>
															Edit
														</a>
														<a class="ti-dropdown-item" href="{{ route('invoices.show', $invoice['id']) }}">
															<i class="ri-eye-line text-lg"></i>
															View
														</a>
														<a class="ti-dropdown-item ajax-modal" href="{{ route('deffered_invoices.earnings', $invoice['id']) }}" data-hs-overlay="#payments-modal">
															<i class="ri-exchange-dollar-fill text-lg"></i>
															Earnings
														</a>
														<a class="ti-dropdown-item" href="javascript:void(0);" data-hs-overlay="#delete-modal" data-id="{{ $invoice['id'] }}" id="delete">
															<i class="ri-delete-bin-line text-lg"></i>
															Delete
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
				<!-- @else
				<div class="lg:col-span-9 col-span-12">
					@include('backend.user.invoice.deffered.tabs.'.$_GET['tab'])
				</div>
				@endif -->
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
						{{ __('Are you sure you want to delete the deffered invoice?') }}
					</h2>

				</div>
				<div class="ti-modal-footer">
					<x-secondary-button data-hs-overlay="#delete-modal">
						{{ __('Cancel') }}
					</x-secondary-button>

					<x-danger-button class="ml-3 submit-btn" type="submit">
						{{ __('Delete Deffered Invoice') }}
					</x-danger-button>
				</div>
			</form>
		</x-modal>

		<div id="import-modal" class="hs-overlay hidden ti-modal">
			<div class="hs-overlay-open:mt-7 ti-modal-box mt-0 ease-out lg:!max-w-4xl lg:w-full m-3 lg:!mx-auto">
				<div class="ti-modal-content">
					<div class="modal-body">
						<div class="ti-modal-header">
							<h3 class="text-lg font-bold">{{ _lang('Import Invoices') }}</h3>
							<button class="ti-modal-close" data-hs-overlay="#import-modal">
								<span class="sr-only">Close</span>
								<i class="ri-close-line text-xl"></i>
							</button>
						</div>
						<form action="{{ route('invoices.import') }}" method="post" id="import_invoices" enctype="multipart/form-data">
							@csrf
							<div class="ti-modal-body grid grid-cols-12">
								<div class="col-span-12">
									<div class="flex items-center justify-between">
										<x-input-label value="{{ _lang('Invoices File') }}" />
										<a href="/uploads/media/default/sample_invoices.xlsx" download>
											<x-secondary-button type="button" class="mb-3">
												Use This Sample File
											</x-secondary-button>
										</a>
									</div>
									<input type="file" class="w-full dropify" name="invoices_file" class="w-full" required />
								</div>
								<div class="col-span-12 mt-4">
									<ul class="space-y-3 text-sm">
										<li class="flex space-x-3">
											<i class="ri-check-line text-primary bg-primary/20 rounded-full px-1"></i>
											<span class="text-gray-800 dark:text-white/70">
												Maximum File Size: 1 MB
											</span>
										</li>

										<li class="flex space-x-3">
											<i class="ri-check-line text-primary bg-primary/20 rounded-full px-1"></i>
											<span class="text-gray-800 dark:text-white/70">
												File format Supported: CSV, TSV, XLS
											</span>
										</li>

										<li class="flex space-x-3">
											<i class="ri-check-line text-primary bg-primary/20 rounded-full px-1"></i>
											<span class="text-gray-800 dark:text-white/70">
												Make sure the format of the import file matches our sample file by comparing them.
											</span>
										</li>
									</ul>
								</div>
							</div>
							<div class="ti-modal-footer">
								<x-secondary-button type="button" data-hs-overlay="#import-modal">
									Close
								</x-secondary-button>
								<x-primary-button type="submit" class="submit-btn">
									Import Invoices
								</x-primary-button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>

		<div id="payments-modal" class="hs-overlay hidden ti-modal">
			<div class="hs-overlay-open:mt-7 ti-modal-box mt-0 ease-out lg:!max-w-4xl lg:w-full m-3 lg:!mx-auto">
				<div class="ti-modal-content">
					<div class="ti-modal-body hidden" id="modal_spinner">
						<div class="text-center spinner">
							<div class="ti-spinner text-primary" role="status" aria-label="loading"> <span class="sr-only">Loading...</span> </div>
						</div>
					</div>
					<div id="main-modal">

					</div>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>

<script>
	$(document).on('click', '#delete', function() {
		var id = $(this).data('id');
		$('#delete-modal form').attr('action', '/user/deffered_invoices/' + id);
	});

	$(document).on("change", ".customers", function() {
		$.ajax({
			url: "/user/customer/get_deffered_invoices/" +
				$(".customers").find(":selected").val(),
			success: function(data) {
				$("#invoices-table tbody").html("");
				data['invoices'].map(invoice => {
					// Append Invoice details
					$("#invoices-table tbody").append(
						`
							<tr>
								<td></td>
								<td class="font-semibold underline">
									Policy #${invoice['order_number']}
								</td>
								<td></td>
								<td>${invoice['grand_total']}</td>
								<td>${(invoice['grand_total'] - invoice['paid']).toFixed(2)}</td>
								<td></td>
								<td></td>
							</tr>
						`
					);
					invoice.deffered_payments.map(payment => {
						if (payment.status == 0 || payment.status == 1) {
							$("#invoices-table tbody").append(
								`
									<tr>
										<td>
											<input type="checkbox" name="payments[]" value="${payment['id']}">
											<input type="hidden" name="invoices[]" value="${invoice['id']}">
										</td>
										<td>
											Deffered Payment #${payment['id']} (${payment['date']} - ${payment['due_date']})
										</td>
										<td>${payment['due_date']}</td>
										<td>${payment['amount']}</td>
										<td>${(payment['amount'] - payment['paid']).toFixed(2)}</td>
										<td>
											<input type="number" step="any" name="amount[${payment['id']}]" value="${payment['amount'] - payment['paid']}" required>
										</td>
									</tr>
								`
							);
						}
					});
				});
			},
		});
	});
</script>
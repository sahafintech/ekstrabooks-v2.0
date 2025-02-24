<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Cash Invoice" page="user" subpage="list" />

		<div class="grid grid-cols-12">
			<div class="col-span-12">
				<div class="box">
					<div class="box-header flex items-center justify-between">
						<h5>{{ _lang('Cash Invoice') }}</h5>
						<div>
							<a href="{{ route('receipts.create') }}">
								<x-primary-button>
									<i class="ri-add-line mr-1"></i>
									{{ _lang('Add New Cash Invoice') }}
								</x-primary-button>
							</a>
							<x-secondary-button data-hs-overlay="#import-modal">
								<i class="ri-upload-cloud-2-line ml-1"></i>
								Import Invoices
							</x-secondary-button>
							<a href="{{ route('receipts.export') }}">
								<x-secondary-button>
									<i class="ri-download-2-line mr-1"></i>
									Export Invoices
								</x-secondary-button>
							</a>
						</div>
					</div>
					<div class="box-body">
						<form method="POST" action="{{ route('receipts.filter') }}">
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
							<form method="POST" action="{{ route('receipts.all') }}">
								<div class="p-4">
									@csrf
									<div class="hs-dropdown ti-dropdown">
										<button id="hs-dropdown-with-icons" type="button" class="hs-dropdown-toggle ti-dropdown-toggle">
											Actions
											<svg class="hs-dropdown-open:rotate-180 ti-dropdown-caret" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path d="M2 5L8.16086 10.6869C8.35239 10.8637 8.64761 10.8637 8.83914 10.6869L15 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
											</svg>
										</button>
										<div class="hs-dropdown-menu ti-dropdown-menu divide-y divide-gray-200" aria-labelledby="hs-dropdown-with-icons">
											<div class="ti-dropdown-divider">
												<button class="ti-dropdown-item w-full" type="button" data-hs-overlay="#delete-all-modal">
													<i class="ri-delete-bin-line text-lg"></i>
													Delete
												</button>
											</div>
										</div>
									</div>
								</div>
								<div id="delete-all-modal" class="hs-overlay hidden ti-modal">
									<div class="hs-overlay-open:mt-7 ti-modal-box mt-0 ease-out lg:!max-w-4xl lg:w-full m-3 lg:!mx-auto">
										<div class="ti-modal-content">
											<div class="modal-body">
												<div class="ti-modal-header">
													<h3 class="text-lg font-bold">{{ _lang('Delete All Selected Invoices') }}</h3>
													<button class="ti-modal-close" data-hs-overlay="#delete-all-modal">
														<span class="sr-only">Close</span>
														<i class="ri-close-line text-xl"></i>
													</button>
												</div>
												<div class="ti-modal-body">
													<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
														{{ __('Are you sure you want to delete all selected invoices?') }}
													</h2>
												</div>
												<div class="ti-modal-footer">
													<x-secondary-button type="button" data-hs-overlay="#delete-all-modal">
														Close
													</x-secondary-button>
													<x-danger-button type="submit" class="submit-btn">
														Delete All
													</x-danger-button>
												</div>
											</div>
										</div>
									</div>
								</div>
								<table id="datatable" class="ti-custom-table ti-custom-table-head whitespace-nowrap">
									<thead>
										<tr>
											<th>
												<input type="checkbox" id="check_all">
											</th>
											<th>{{ _lang('Date') }}</th>
											<th>{{ _lang('Receipt Number') }}</th>
											<th>{{ _lang('Customer') }}</th>
											<th class="text-right">{{ _lang('Grand Total') }}</th>
											<th class="text-center">{{ _lang('Action') }}</th>
										</tr>
									</thead>
									<tbody>
										@foreach($receipts as $receipt)
										<tr data-id="row_{{ $receipt->id }}">
											<td>
												<input type="checkbox" name="receipts[]" id="check_{{ $receipt->id }}" value="{{ $receipt->id }}">
											</td>
											<td>{{ $receipt->receipt_date }}</td>
											<td>{{ $receipt->receipt_number }}</td>
											<td>{{ $receipt->customer->name }}</td>
											<td>
												{{ formatAmount($receipt->grand_total, currency_symbol(request()->activeBusiness->currency), $receipt->business_id) }}
												@if($receipt->grand_total != $receipt->converted_total)
												({{ formatAmount($receipt->converted_total, currency_symbol($receipt->currency), $receipt->business_id) }})
												@endif
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
															<a class="ti-dropdown-item" href="{{ route('receipts.edit', $receipt['id']) }}">
																<i class="ri-edit-box-line text-lg"></i>
																Edit
															</a>
															<a class="ti-dropdown-item" href="{{ route('receipts.show', $receipt['id']) }}">
																<i class="ri-eye-line text-lg"></i>
																View
															</a>
															<a class="ti-dropdown-item" href="{{ route('receipts.invoice_pos', $receipt['id']) }}" target="_blank">
																<i class="ri-printer-line text-lg"></i>
																POS Invoice
															</a>
															<a class="ti-dropdown-item" href="javascript:void(0);" data-hs-overlay="#delete-modal" data-id="{{ $receipt['id'] }}" id="delete">
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
							</form>
						</div>
					</div>
				</div>
			</div>
		</div>
		<x-modal>
			<form method="post">
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
					{{ csrf_field() }}

					<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
						{{ __('Are you sure you want to delete the Cash invoice?') }}
					</h2>

					<input name="_method" type="hidden" value="DELETE">
				</div>
				<div class="ti-modal-footer">
					<x-secondary-button data-hs-overlay="#delete-modal">
						{{ __('Cancel') }}
					</x-secondary-button>

					<x-danger-button class="ml-3 submit-btn" type="submit">
						{{ __('Delete Cash Invoice') }}
					</x-danger-button>
				</div>
			</form>
		</x-modal>

		<div id="import-modal" class="hs-overlay hidden ti-modal">
			<div class="hs-overlay-open:mt-7 ti-modal-box mt-0 ease-out lg:!max-w-4xl lg:w-full m-3 lg:!mx-auto">
				<div class="ti-modal-content">
					<div class="ti-modal-body hidden" id="modal_spinner">
						<div class="text-center spinner">
							<div class="ti-spinner text-primary" role="status" aria-label="loading"> <span class="sr-only">Loading...</span> </div>
						</div>
					</div>
					<div class="modal-body">
						<div class="ti-modal-header">
							<h3 class="text-lg font-bold">{{ _lang('Import Invoices') }}</h3>
							<button class="ti-modal-close" data-hs-overlay="#import-modal">
								<span class="sr-only">Close</span>
								<i class="ri-close-line text-xl"></i>
							</button>
						</div>
						<form action="{{ route('receipts.import') }}" method="post" id="import_receipts" enctype="multipart/form-data">
							@csrf
							<div class="ti-modal-body grid grid-cols-12">
								<div class="col-span-12">
									<div class="flex items-center justify-between">
										<x-input-label value="{{ _lang('Cash Invoice File') }}" />
										<a href="/uploads/media/default/sample_cash_invoices.xlsx" download>
											<x-secondary-button type="button" class="mb-3">
												Use This Sample File
											</x-secondary-button>
										</a>
									</div>
									<input type="file" class="w-full dropify" name="receipts_file" class="w-full" required />
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
	</div>
</x-app-layout>

<script>
	$(document).on('click', '#delete', function() {
		var id = $(this).data('id');
		$('#delete-modal form').attr('action', '/user/receipts/' + id);
	});

	$(document).ready(function() {
		$('#check_all').on('click', function() {
			if (this.checked) {
				$('input[type="checkbox"]').each(function() {
					this.checked = true;
				});

			} else {
				$('input[type="checkbox"]').each(function() {
					this.checked = false;
				});

			}
		});

		$('input[type="checkbox"]').on('click', function() {
			if ($('input[type="checkbox"]:checked').length == $('input[type="checkbox"]').length) {
				$('#check_all').prop('checked', true);
			} else {
				$('#check_all').prop('checked', false);
			}
		});
	});
</script>
<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Bill Payment" page="user" subpage="create" />

		<div class="box">
			<div class="box-header">
				<h5>Create Bill Payment</h5>
			</div>
			<div class="box-body">
				<form method="post" class="ajax-screen-submit" autocomplete="off" action="{{ route('bill_payments.store') }}" enctype="multipart/form-data">
					{{ csrf_field() }}
					<div class="grid grid-cols-12 gap-x-2">
						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Supplier') }}" />
							<select id="vendors" class="w-full auto-select selectize" data-selected="{{ old('vendor_id') }}" name="vendor_id" required>
								<option value="">{{ _lang('Select Supplier') }}</option>
								<option value="all">{{ _lang('ALL Bill') }}</option>
								@foreach(\App\Models\Vendor::all() as $vendor)
								<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
								@endforeach
							</select>
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Date') }}" />
							<x-text-input type="text" class="flatpickr" id="date" name="trans_date" value="{{ \Carbon\Carbon::now()->format('d-m-Y') }}" required />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Credit Account') }}" />
							<select class="w-full auto-select selectize" data-selected="{{ old('account_id') }}" name="account_id" required>
								<option value="">{{ _lang('Select Account') }}</option>
								@foreach(\App\Models\Account::where(function ($query) {
								$query->where('account_type', '=', 'Bank')
								->orWhere('account_type', '=', 'Cash');
								})->where('business_id', '=', request()->activeBusiness->id)
								->get() as $account)
								<option value="{{ $account->id }}">{{ $account->account_name }}</option>
								@endforeach
							</select>
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Payment Method') }}" />
							<select class="w-full auto-select selectize" data-selected="{{ old('method') }}" name="method">
								<option value="">{{ _lang('Select Payment Method') }}</option>
								@foreach(\App\Models\TransactionMethod::all() as $method)
								<option value="{{ $method->name }}">{{ $method->name }}</option>
								@endforeach
							</select>
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Reference') }}" />
							<x-text-input type="text" name="reference" value="{{ old('reference') }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<span class="text-lg">{{ _lang('Total Amount Selected') }}</span>
							<br>
							<span id="total_amount_selected" class="text-2xl">{{ currency_symbol(request()->activeBusiness->currency) }} 0.00</span>
						</div>

						<div class="col-span-12 mt-3">
							<h5 class="text-md">Standing Invoices</h5>
							<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto mt-3 bg-white">
								<table id="bill-table" class="ti-custom-table ti-custom-table-head whitespace-nowrap">
									<thead>
										<tr>
											<th>
												<div class="custom-control custom-checkbox">
													<input type="checkbox" class="custom-control-input" id="select_all">
													<label class="custom-control-label" for="select_all"></label>
												</div>
											</th>
											<th>{{ _lang('Description') }}</th>
											<th>{{ _lang('Due Date') }}</th>
											<th>{{ _lang('Grand Total') }}</th>
											<th>{{ _lang('Due Amount') }}</th>
											<th>{{ _lang('Amount') }}</th>
										</tr>
									</thead>
									<tbody>
									</tbody>
								</table>
							</div>
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Attachment') }}" />
							<input type="file" class="dropify" name="attachment">
						</div>

						<div class="col-span-12 my-4">
							<x-primary-button type="submit" class="submit-btn">{{ _lang('Submit') }}</x-primary-button>
						</div>
					</div>
				</form>
			</div>
		</div>
</x-app-layout>

<script>
	$(document).ready(function() {
		// Select All
		$('#select_all').click(function() {
			if ($(this).is(':checked')) {
				$('#bill-table tbody input[type="checkbox"]').prop('checked', true);
			} else {
				$('#bill-table tbody input[type="checkbox"]').prop('checked', false);
			}

			$('#bill-table tbody input[type="checkbox"]').trigger('change');
		});

		// total_amount_selected
		$('#bill-table tbody').on('change', 'input[type="checkbox"]', function() {
			var total = 0;
			$('#bill-table tbody input[type="checkbox"]:checked').each(function() {
				total += parseFloat($(this).closest('tr').find('td:eq(4)').text().replace(/[^0-9.-]+/g, ""));
			});
			$('#total_amount_selected').text('{{ currency_symbol(request()->activeBusiness->currency) }} ' + total.toFixed(2));
		});
	});
</script>
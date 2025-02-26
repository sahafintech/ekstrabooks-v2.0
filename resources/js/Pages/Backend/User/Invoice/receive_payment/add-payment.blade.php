<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Receive Payment" page="user" subpage="create" />

		<div class="box">
			<div class="box-body">
				<form method="post" class="ajax-screen-submit" autocomplete="off" action="{{ route('receive_payments.store') }}" enctype="multipart/form-data">
					{{ csrf_field() }}
					<div class="grid grid-cols-12 gap-x-5">
						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Customer') }}" />
							<select id="customers" class="w-full auto-select selectize" data-selected="{{ old('customer_id') }}" name="customer_id" required>
								<option value="">{{ _lang('Select Customer') }}</option>
								@foreach(\App\Models\Customer::all() as $customer)
								<option value="{{ $customer->id }}">{{ $customer->name }}</option>
								@endforeach
							</select>
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Date') }}" />
							<x-text-input type="text" class="flatpickr" id="date" name="trans_date" value="{{ \Carbon\Carbon::now()->format('d-m-Y') }}" required />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Debit Account') }}" />
							<select class="w-full auto-select selectize" data-selected="{{ old('account_id') }}" name="account_id" required>
								<option value="">{{ _lang('Select Account') }}</option>
								@foreach(\App\Models\Account::where(function ($query) {
								$query->where('account_type', '=', 'Bank')
								->orWhere('account_type', '=', 'Cash');
								})->where(function ($query) {
								$query->where('business_id', '=', request()->activeBusiness->id)
								->orWhere('business_id', '=', null);
								})
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
								<table id="invoices-table" class="ti-custom-table ti-custom-table-head whitespace-nowrap">
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
	</div>
</x-app-layout>

<script>
	$(document).ready(function() {
		// Select All
		$('#select_all').click(function() {
			if ($(this).is(':checked')) {
				$('#invoices-table tbody input[type="checkbox"]').prop('checked', true);
			} else {
				$('#invoices-table tbody input[type="checkbox"]').prop('checked', false);
			}

			$('#invoices-table tbody input[type="checkbox"]').trigger('change');
		});

		// total_amount_selected
		$('#invoices-table tbody').on('change', 'input[type="checkbox"]', function() {
			var total = 0;
			$('#invoices-table tbody input[type="checkbox"]:checked').each(function() {
				total += parseFloat($(this).closest('tr').find('td:eq(4)').text().replace(/[^0-9.-]+/g, ""));
			});
			$('#total_amount_selected').text('{{ currency_symbol(request()->activeBusiness->currency) }} ' + total.toFixed(2));
		});
	});
</script>
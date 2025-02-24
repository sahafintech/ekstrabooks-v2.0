<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Cash Invoice" page="user" subpage="create" />

		<form method="post" class="validate" autocomplete="off" action="{{ route('receipts.update', $id) }}" enctype="multipart/form-data">
			@csrf
			<input name="_method" type="hidden" value="PATCH">
			<div class="grid grid-cols-12 gap-x-2">
				<div class="xl:col-span-9 lg:col-span-8 col-span-12">
					<div class="box invoice-form">
						<div class="box-body">
							<div class="grid grid-cols-8 gap-y-3">
								<div class="lg:col-span-4 col-span-8">
									<div class="invoice-logo">
										<img class="w-32" src="{{ asset('/uploads/media/' . request()->activeBusiness->logo) }}" alt="logo">
									</div>
								</div>

								<div class="lg:col-span-4 col-span-8">
									<div class="grid grid-cols-12 gap-y-3">
										<div class="col-span-12">
											<x-text-input class="text-xl font-bold" type="text" name="title" value="{{ $receipt->title }}" placeholder="{{ _lang('Receipt Title') }}" required />
										</div>

										<div class="col-span-12">
											<x-text-input type="text" name="order_number" value="{{ $receipt->order_number }}" placeholder="{{ _lang('Sales Order No or any other reference') }}" />
										</div>
									</div>
								</div>
							</div>

							<div class="col-span-12 my-8">
								<hr>
							</div>

							<div class="grid grid-cols-12 gap-x-2">
								<div class="md:col-span-4 col-span-12">
									<x-input-label value="{{ _lang('Date') }}" />
									<x-text-input type="text" class="flatpickr-input" id="date" name="receipt_date" value="{{ \Carbon\Carbon::createFromFormat(get_business_option('date_format', request()->activeBusiness->id),$receipt->receipt_date)->format('d-m-Y') }}" required placeholder="Select Date" />
								</div>
								<div class="md:col-span-4 col-span-12">
									<x-input-label value="{{ _lang('Deposit account') }}" />
									<select class="w-full auto-select selectize" data-selected="{{ $transaction->account->id }}" name="account_id">
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
								<div class="md:col-span-4 col-span-12">
									<x-input-label value="{{ _lang('Payment Method') }}" />
									<select class="w-full auto-select selectize" data-selected="{{ $transaction->transaction_method }}" name="method">
										<option value="">{{ _lang('Select Method') }}</option>
										@foreach(\App\Models\TransactionMethod::where('business_id', request()->activeBusiness->id)->get() as $method)
										<option value="{{ $method->name }}">{{ $method->name }}</option>
										@endforeach
									</select>
								</div>
							</div>

							<div class="grid grid-cols-12 gap-x-2">
								<div class="md:col-span-5 col-span-12 mt-5">
									<div class="select-customer">
										<x-input-label value="{{ _lang('Select Customer') }}" />
										<select class="w-full auto-select selectize" data-selected="{{ $receipt->customer_id }}" name="customer_id">
											<option value="">{{ _lang('Select Customer') }}</option>
											@foreach(\App\Models\Customer::where('business_id', request()->activeBusiness->id)->get() as $customer)
											<option value="{{ $customer->id }}">{{ $customer->name }}</option>
											@endforeach
										</select>
									</div>
								</div>
								<div class="md:col-span-5 col-span-12 mt-5">
									<x-input-label value="{{ _lang('Reference') }}" />
									<x-text-input type="text" name="reference" value="{{ $receipt->reference }}" />
								</div>
							</div>

							<div class="grid grid-cols-12 my-10 gap-x-2">
								<div class="md:col-span-4 col-span-12">
									<x-input-label value="Select Product / Service" />
									<select id="products" data-type="sell" class="w-full selectize">
										<option value="">Select Item</option>
										@foreach(\App\Models\Product::where('business_id', request()->activeBusiness->id)->get() as $product)
										<option value="{{ $product->id }}">
											{{ $product->name }}
										</option>
										@endforeach
									</select>
								</div>
								<div class="md:col-span-4 col-span-12">
									<x-input-label value="{{ _lang('Transaction Currency') }}" />
									<select class="auto-select selectize" id="currency" data-selected="{{ $receipt->currency }}" name="currency" required>
										<option value="">{{ _lang('Select One') }}</option>
										{{ get_currency_list() }}
									</select>
								</div>
								<div class="md:col-span-4 col-span-12 {{ $receipt->currency == request()->activeBusiness->currency ? 'hidden' : '' }}">
									<x-input-label value="{{ _lang('Exchange Rate') }}" />
									<x-text-input id="exchange_rate" type="number" step="any" name="exchange_rate" value="{{ $receipt->exchange_rate }}" readonly />
								</div>
							</div>

							<div class="grid grid-cols-12 mt-3">
								<div class="col-span-12">
									<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
										<table id="invoice-table" class="ti-custom-table ti-custom-table-head whitespace-nowrap bg-gray-100">
											<thead>
												<tr>
													<th class="input-lg">{{ _lang('Name') }}</th>
													<th class="input-md">{{ _lang('Item Taxes') }}</th>
													<th class="input-xs text-center">{{ _lang('Quantity') }}</th>
													<th class="input-sm text-right">{{ _lang('Price') }}</th>
													<th class="input-sm text-right">{{ _lang('Amount') }}</th>
													<th class="text-center"><i class="fas fa-minus-circle"></i></th>
												</tr>
											</thead>
											<tbody>
												@foreach($receipt->items as $item)
												<tr class="line-item">
													<td class="input-lg align-top">
														<input type="hidden" class="product_id" name="product_id[]" value="{{ $item->product_id }}">
														<input type="hidden" class="product_type" name="product_type[]" value="{{ $item->product->type }}">
														<input type="text" class="w-72 product_name" name="product_name[]" value="{{ $item->product_name }}"><br>
														<textarea class="w-72 mt-2 description" name="description[]" placeholder="{{ _lang('Descriptions') }}">{{ $item->description }}</textarea>
													</td>
													<td class="input-md align-top">
														<div class="w-52">
															<select name="taxes[{{ $item->product_id }}][]" class="selectize auto-select product_taxes" multiple data-selected="{{ $item->taxes->pluck('tax_id') }}" data-placeholder="{{ _lang('Select Taxes') }}" multiple>
																@foreach(\App\Models\Tax::all() as $tax)
																<option value="{{ $tax->id }}" data-tax-rate="{{ $tax->rate }}" data-tax-name="{{ $tax->name }} {{ $tax->rate }} %">{{ $tax->name }} {{ $tax->rate }} %</option>
																@endforeach
															</select>
														</div>
													</td>
													<td class="input-xs text-center align-top"><input type="number" class="w-36 quantity" name="quantity[]" value="{{ $item->quantity }}" min="1" {{ $item->product->type == 'product' && $item->product->stock_management == 1 ? 'max=' . $item->product->stock + $item->quantity : '' }}></td>
													<td class="input-sm align-top"><input type="text" class="w-36 text-right unit_cost" name="unit_cost[]" value="{{ $item->unit_cost }}"></td>
													<td class="input-sm align-top"><input type="text" class="w-36 text-right sub_total" name="sub_total[]" value="{{ $item->sub_total }}" readonly></td>
													<td class="input-xxs text-center align-top">
														<button type="button" class="bg-red-600 btn-remove-row px-1 rounded-md">
															<i class="ri-close-circle-line text-xl text-white"></i>
														</button>
													</td>
												</tr>
												@endforeach
											</tbody>
										</table>
									</div>
								</div>
							</div>

							<div class="col-span-12 my-8">
								<hr>
							</div>

							<div class="grid grid-cols-12 md:text-right">
								<div class="xl:col-start-6 col-span-12 space-y-3">
									<div class="grid grid-cols-12 gap-x-2" id="before-tax">
										<x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Sub Total') }}" />
										<div class="md:col-span-6 col-span-12">
											<x-text-input type="hidden" class="md:text-right border-none" name="sub_total" id="sub_total" value="{{ $receipt->sub_total }}" readonly />
											<span class="md:text-right sub_total_span">
												{{ formatAmount($receipt->sub_total) }}
											</span>
										</div>
									</div>

									@foreach($receipt->taxes as $tax)
									<div class="grid grid-cols-12 gap-x-2 old-tax {{ count($receipt->taxes) > 0 ? ''  : 'hidden'}}" id="after-tax">
										<x-input-label class="md:col-span-6 col-span-12" value="{{ $tax->name }}" />
										<div class="md:col-span-6 col-span-12">
											<x-text-input type="hidden" class="md:text-right border-none tax-input-field" name="tax_amount[{{ $tax->tax_id }}]" id="tax-{{ $tax->tax_id }}" value="0" readonly />
											<span class="md:text-right tax_span-{{ $tax->tax_id }}">
												{{ formatAmount($tax->amount) }}
											</span>
										</div>
									</div>
									@endforeach

									<div class="grid grid-cols-12 gap-x-2" id="after-tax">
										<x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Discount Amount') }}" />
										<div class="md:col-span-6 col-span-12">
											<x-text-input type="hidden" class="md:text-right border-none" name="discount_amount" id="discount_amount" value="{{ $receipt->discount }}" readonly />
											<span class="md:text-right discount_span">
												{{ formatAmount($receipt->discount) }}
											</span>
										</div>
									</div>

									<div class="grid grid-cols-12 gap-x-2">
										<x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Grand Total') }}" />
										<div class="md:col-span-6 col-span-12">
											<x-text-input type="hidden" class="md:text-right border-none" name="grand_total" id="grand_total" value="{{ $receipt->grand_total }}" readonly />
											<span class="md:text-right grand_total_span">
												{{ formatAmount($receipt->grand_total) }}
											</span>
										</div>
									</div>

									<div class="{{ $receipt->currency == request()->activeBusiness->currency ? 'hidden' : 'grid' }} grid-cols-12 gap-x-2">
										<x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Converted Total') }}" />
										<div class="md:col-span-6 col-span-12">
											<x-text-input type="hidden" class="md:text-right border-none" name="converted_total" id="converted_total" value="{{ $receipt->converted_total }}" readonly placeholder="0.00" />
											<span class="md:text-right converted_total_span">
												{{ formatAmount($receipt->converted_total) }}
											</span>
										</div>
									</div>
								</div>
							</div>

							<div class="col-span-12 my-8">
								<hr>
							</div>

							<div class="grid grid-cols-12">
								<div class="col-span-12">
									<x-input-label value="{{ _lang('Notes') }}" />
									<textarea class="w-full" name="note">{{ $receipt->note }}</textarea>
								</div>

								<div class="col-span-12">
									<x-input-label value="{{ _lang('Footer') }}" />
									<textarea class="w-full" name="footer">{!! xss_clean(get_business_option('invoice_footer', $receipt->footer)) !!}</textarea>
								</div>

								<div class="col-span-12 mt-4">
									<x-primary-button type="submit" class="submit-btn">{{ _lang('Update Cash Invoice') }}</x-primary-button>
								</div>
							</div>
						</div>

					</div>
				</div>

				<div class="xl:col-span-3 lg:col-span-4 col-span-12">
					<div class="box">
						<div class="box-body">
							<div class="grid grid-cols-12">
								<div class="col-span-12 mt-3">
									<x-input-label value="{{ _lang('Discount Value') }}" />
									<div class="col-span-12 grid grid-cols-12">
										<div class="col-span-3">
											<select class="discount_type w-full auto-select" id="discount_type" name="discount_type" data-selected="{{ $receipt->discount_type }}">
												<option value="0">%</option>
												<option value="1">{{ currency_symbol(request()->activeBusiness->currency) }}</option>
											</select>
										</div>
										<input type="number" step="any" class="col-span-9" name="discount_value" id="discount_value" value="{{ $receipt->discount_value }}">
									</div>
								</div>

								<div class="col-span-12 mt-4">
									<x-primary-button type="submit" class="submit-btn">{{ _lang('Update Cash Invoice') }}</x-primary-button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</form>


		<table class="hidden">
			<tr class="line-item" id="copy-line">
				<td class="align-top">
					<input type="hidden" class="product_id" name="product_id[]">
					<input type="hidden" class="product_type" name="product_type[]">
					<input type="text" class="w-72 product_name" name="product_name[]"><br>
					<textarea class="w-72 mt-2 description" name="description[]" placeholder="{{ _lang('Descriptions') }}"></textarea>
				</td>
				<td class="input-md align-top">
					<div class="w-52">
						<select name="taxes[][]" class="multi-selector product_taxes w-full" data-placeholder="{{ _lang('Select Taxes') }}" multiple>

						</select>
					</div>
				</td>
				<td class="input-xs text-center align-top"><input type="number" class="w-36 quantity" name="quantity[]" min="1"></td>
				<td class="input-sm align-top"><input type="text" class="w-36 text-right unit_cost" name="unit_cost[]"></td>
				<td class="input-sm align-top"><input type="text" class="w-36 text-right sub_total" name="sub_total[]" readonly></td>
				<td class="input-xxs text-center align-top">
					<button type="button" class="bg-red-600 btn-remove-row px-1 rounded-md">
						<i class="ri-close-circle-line text-xl text-white"></i>
					</button>
				</td>
			</tr>
		</table>
	</div>
</x-app-layout>

<script src="{{ asset('/backend/assets/js/invoice.js?v=1.2') }}"></script>

<style>
	.align-top {
		vertical-align: top;
	}

	td textarea,
	td input {
		border: none;
	}

	td .select2-container--default .select2-selection--single {
		border: none;
	}
</style>
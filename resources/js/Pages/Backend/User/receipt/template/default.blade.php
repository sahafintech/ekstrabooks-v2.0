<!-- Default Invoice template -->
<div class="box">
	<div class="box-body">
		<div id="invoice" class="{{ $type }}">
			<div class="default-invoice">
				<div class="invoice-header">
					<div class="grid grid-cols-12 items-center p-4">
						<div class="col-span-6 float-left">
							@if($type == 'pdf')
							<img class="w-28" src="{{ public_path('uploads/media/' . $receipt->business->logo) }}">
							@else
							<img class="w-52" src="{{ asset('/uploads/media/' . $receipt->business->logo) }}">
							@endif
							<h2 class="text-xl font-bold">{{ $receipt->title }}</h2>
						</div>
						<div class="col-span-6 float-right sm:text-right">
							<h4 class="text-2xl font-bold">{{ $receipt->business->name }}</h4>
							<p>{{ $receipt->business->address }}, {{ $receipt->business->zip }}</p>
							<p>{{ $receipt->business->phone }}</p>
							<p>{{ $receipt->business->email }}</p>
							<p>{{ $receipt->business->country }}</p>
						</div>
						<div class="clear"></div>
					</div>
				</div>

				<div class="invoice-details">
					<!-- bank account details -->
					<div class="grid grid-cols-12">
						<div class="col-span-6 float-left">
							@if($receipt->customer_id != null)
							<h5 class="bill-to-heading font-semibold underline">{{ _lang('BILLING DETAILS') }}</h5>

							<h4 class="bill-to">{{ $receipt->customer?->name }}</h4>
							<p>{{ $receipt->customer?->address }}</<p>
							<p>{{ $receipt->customer?->zip }}</<p>
							<p>{{ $receipt->customer?->city }}</<p>
							<p>{{ $receipt->customer?->country }}</p>
							@endif

							@if($receipt->business->bank_accounts->count() > 0)
							@foreach($receipt->business->bank_accounts as $bank_account)
							@if($bank_account->display_on_invoice == 1)
							<div class="mt-3">
								<h5>{{ _lang('Bank Name') }}: {{ $bank_account->bank_name }}</h5>
								<h5>{{ _lang('Account Name') }}: {{ $bank_account->account_name }}</h5>
								<h5 class="underline font-bold">{{ _lang('Account Number') }}: {{ $bank_account->account_number }}</h5>
							</div>
							@endif
							@endforeach
							@endif
						</div>
						<div class="col-span-6 text-right float-right space-y-2">
							<h5 class="mb-2">{{ _lang('Sales Receipt') }}#: {{ $receipt->receipt_number }}</h5>
							@if($receipt->order_number != '')
							<p>{{ _lang('Sales Order No') }}: {{ $receipt->order_number }}</p>
							@endif
							<p>{{ _lang('Receipt Date') }}: {{ $receipt->receipt_date }}</p>
							<p><strong>{{ _lang('Grand Total') }}: {{ formatAmount($receipt->grand_total, currency_symbol($receipt->business->currency), $receipt->business_id) }}</strong></p>
						</div>
						<div class="clear"></div>
					</div>
				</div>

				@php $receiptColumns = json_decode(get_business_option('receipt_column', null, $receipt->business_id)); @endphp

				<div class="invoice-body mt-3">
					<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
						<table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
							<thead>
								<tr>
									@if(isset($receiptColumns->name->status))
									@if($receiptColumns->name->status != '0')
									<th style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ isset($receiptColumns->name->label) ? $receiptColumns->name->label : _lang('Name') }}</th>
									@endif
									@else
									<th style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ _lang('Name') }}</th>
									@endif

									@if(isset($receiptColumns->quantity->status))
									@if($receiptColumns->quantity->status != '0')
									<th class="text-center" style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ isset($receiptColumns->quantity->label) ? $receiptColumns->quantity->label : _lang('Quantity') }}</th>
									@endif
									@else
									<th class="text-center" style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ _lang('Quantity') }}</th>
									@endif

									@if(isset($receiptColumns->price->status))
									@if($receiptColumns->price->status != '0')
									<th class="text-right" style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ isset($receiptColumns->price->label) ? $receiptColumns->price->label : _lang('Price') }}</th>
									@endif
									@else
									<th class="text-right" style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ _lang('Price') }}</th>
									@endif

									@if(isset($receiptColumns->amount->status))
									@if($receiptColumns->amount->status != '0')
									<th class="text-right" style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ isset($receiptColumns->amount->label) ? $receiptColumns->amount->label : _lang('Amount') }}</th>
									@endif
									@else
									<th class="text-right" style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ _lang('Amount') }}</th>
									@endif
								</tr>
							</thead>
							<tbody>
								@foreach($receipt->items as $item)
								<tr>
									<td class="product-name">
										@if(isset($receiptColumns->name->status))
										@if($receiptColumns->name->status != '0')
										<p>{{ $item->product_name }}</p>
										@endif
										@else
										<p>{{ $item->product_name }}</p>
										@endif

										@if(isset($receiptColumns->description->status))
										@if($receiptColumns->description->status != '0')
										<p>{{ $item->description }}</p>
										@endif
										@else
										<p>{{ $item->description }}</p>
										@endif
									</td>

									@if(isset($receiptColumns->quantity->status))
									@if($receiptColumns->quantity->status != '0')
									<td class="text-center">{{ $item->quantity.' '.$item->product->product_unit->unit }}</td>
									@endif
									@else
									<td class="text-center">{{ $item->quantity.' '.$item->product->product_unit->unit }}</td>
									@endif

									@if(isset($receiptColumns->price->status))
									@if($receiptColumns->price->status != '0')
									<td class="text-right text-nowrap">{{ formatAmount($item->unit_cost, currency_symbol($receipt->business->currency), $receipt->business_id) }}</td>
									@endif
									@else
									<td class="text-right text-nowrap">{{ formatAmount($item->unit_cost, currency_symbol($receipt->business->currency), $receipt->business_id) }}</td>
									@endif

									@if(isset($receiptColumns->amount->status))
									@if($receiptColumns->amount->status != '0')
									<td class="text-right text-nowrap">{{ formatAmount($item->sub_total, currency_symbol($receipt->business->currency), $receipt->business_id) }}</td>
									@endif
									@else
									<td class="text-right text-nowrap">{{ formatAmount($item->sub_total, currency_symbol($receipt->business->currency), $receipt->business_id) }}</td>
									@endif
								</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				</div>

				<div class="invoice-summary mt-6">
					<div class="grid grid-cols-12">
						<div class="xl:col-span-7 lg:col-span-6 col-span-12 float-left">
							<div class="invoice-note">
								<p><b>{{ _lang('Notes / Terms') }}:</b> {!! xss_clean($receipt->note) !!}</p>
							</div>
						</div>
						<div class="xl:col-span-5 lg:col-span-6 col-span-12 float-right">
							<table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
								<tr>
									<td>{{ _lang('Sub Total') }}</td>
									<td class="text-nowrap">{{ formatAmount($receipt->sub_total, currency_symbol($receipt->business->currency), $receipt->business_id) }}</td>
								</tr>
								@foreach($receipt->taxes as $tax)
								<tr>
									<td>{{ $tax->name }}</td>
									<td class="text-nowrap">+ {{ formatAmount($tax->amount, currency_symbol($receipt->business->currency), $receipt->business_id) }}</td>
								</tr>
								@endforeach
								@if($receipt->discount > 0)
								<tr>
									<td>{{ _lang('Discount') }}</td>
									<td class="text-nowrap">- {{ formatAmount($receipt->discount, currency_symbol($receipt->business->currency), $receipt->business_id) }}</td>
								</tr>
								@endif
								<tr>
									<td><b>{{ _lang('Grand Total') }}</b></td>
									<td class="text-nowrap"><b>{{ formatAmount($receipt->grand_total, currency_symbol($receipt->business->currency), $receipt->business_id) }}</b></td>
								</tr>
								@if($receipt->grand_total != $receipt->converted_total)
								<tr>
									<td><b>{{ _lang('Converted Total') }}</b></td>
									<td class="text-nowrap"><b>{{ formatAmount($receipt->converted_total, currency_symbol($receipt->currency), $receipt->business_id) }}</b></td>
								</tr>
								@endif
							</table>
						</div>
						<div class="clear"></div>
					</div>
				</div>
			</div>

			<div class="invoice-footer">
				<p>{!! xss_clean($receipt->footer) !!}</p>
			</div>
		</div>
	</div>
</div>

<style>
	/* print with background colors and images */
	@media print {
		* {
			-webkit-print-color-adjust: exact !important;
			color-adjust: exact !important;
		}
	}

	.product-name {
		white-space: wrap !important;
	}

	#invoice, .default-invoice {
		font-family: century-gothic, sans-serif;
	}
</style>
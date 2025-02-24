@php $type = isset($type) ? $type : 'preview'; @endphp
<!-- Default Invoice template -->
<div class="box">
	<div class="box-body">
		<div id="invoice" class="{{ $type }}">
			<div class="default-invoice">
				<div class="invoice-header">
					<div class="grid grid-cols-12 items-center p-4">
						<div class="col-span-6 float-left">
							@if($type == 'pdf')
							<img class="w-28" src="{{ public_path('uploads/media/' . $purchase->business->logo) }}">
							@else
							<img class="w-52" src="{{ asset('/uploads/media/' . $purchase->business->logo) }}">
							@endif
							<h2 class="text-3xl font-bold">{{ $purchase->title }}</h2>
						</div>
						<div class="col-span-6 float-right sm:text-right right">
							<h4 class="text-2xl font-bold">{{ $purchase->business->name }}</h4>
							<p>{{ $purchase->business->address }}, {{ $purchase->business->zip }}</p>
							<p>{{ $purchase->business->phone }}</p>
							<p>{{ $purchase->business->email }}</p>
							<p>{{ $purchase->business->country }}</p>
						</div>
						<div class="clear"></div>
					</div>
				</div>

				<div class="invoice-details mt-10">
					<div class="grid grid-cols-12 items-bottom">
						<div class="col-span-6 float-left">
							<h5 class="bill-to-heading">{{ _lang('BILLING DETAILS') }}</h5>

							<h4 class="bill-to">{{ $purchase->vendor->name }}</h4>
							<p>{{ $purchase->vendor->address }}</<p>
							<p>{{ $purchase->vendor->zip }}</<p>
							<p>{{ $purchase->vendor->city }}</<p>
							<p>{{ $purchase->vendor->country }}</p>
						</div>
						<div class="col-span-6 text-right float-right">
							<h5 class="mb-2">{{ _lang('Order No') }}#: {{ $purchase->bill_no }}</h4>
								<p>{{ _lang('Order Date') }}: {{ $purchase->purchase_date }}</p>
								<p><strong>{{ _lang('Grand Total') }}: {{ formatAmount($purchase->grand_total, currency_symbol($purchase->business->currency), $purchase->business_id) }}</strong></p>
								<p><strong>{{ _lang('Due Amount') }}: {{ formatAmount($purchase->grand_total - $purchase->paid, currency_symbol($purchase->business->currency), $purchase->business_id) }}</strong></p>
						</div>
						<div class="clear"></div>
					</div>
				</div>

				@php $invoiceColumns = json_decode(get_business_option('invoice_column', null, $purchase->business_id)); @endphp

				<div class="invoice-body">
					<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
						<table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
							<thead>
								<tr>
									@if(isset($invoiceColumns->name->status))
									@if($invoiceColumns->name->status != '0')
									<th style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ isset($invoiceColumns->name->label) ? $invoiceColumns->name->label : _lang('Name') }}</th>
									@endif
									@else
									<th style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ _lang('Name') }}</th>
									@endif

									@if(isset($invoiceColumns->quantity->status))
									@if($invoiceColumns->quantity->status != '0')
									<th class="text-center" style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ isset($invoiceColumns->quantity->label) ? $invoiceColumns->quantity->label : _lang('Quantity') }}</th>
									@endif
									@else
									<th class="text-center" style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ _lang('Quantity') }}</th>
									@endif

									@if(isset($invoiceColumns->price->status))
									@if($invoiceColumns->price->status != '0')
									<th class="text-right" style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ isset($invoiceColumns->price->label) ? $invoiceColumns->price->label : _lang('Price') }}</th>
									@endif
									@else
									<th class="text-right" style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ _lang('Price') }}</th>
									@endif

									@if(isset($invoiceColumns->amount->status))
									@if($invoiceColumns->amount->status != '0')
									<th class="text-right" style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ isset($invoiceColumns->amount->label) ? $invoiceColumns->amount->label : _lang('Amount') }}</th>
									@endif
									@else
									<th class="text-right" style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ _lang('Amount') }}</th>
									@endif
								</tr>
							</thead>
							<tbody>
								@foreach($purchase->items as $item)
								<tr>
									<td class="product-name">
										@if(isset($invoiceColumns->name->status))
										@if($invoiceColumns->name->status != '0')
										<p>{{ $item->product_name }}</p>
										@endif
										@else
										<p>{{ $item->product_name }}</p>
										@endif

										@if(isset($invoiceColumns->description->status))
										@if($invoiceColumns->description->status != '0')
										<p>{{ $item->description }}</p>
										@endif
										@else
										<p>{{ $item->description }}</p>
										@endif
									</td>

									@if(isset($invoiceColumns->quantity->status))
									@if($invoiceColumns->quantity->status != '0')
									<td class="text-center">{{ $item->quantity.' '.$item->product->product_unit->unit }}</td>
									@endif
									@else
									<td class="text-center">{{ $item->quantity.' '.$item->product->product_unit->unit }}</td>
									@endif

									@if(isset($invoiceColumns->price->status))
									@if($invoiceColumns->price->status != '0')
									<td class="text-right text-nowrap">{{ formatAmount($item->unit_cost, currency_symbol($purchase->business->currency), $purchase->business_id) }}</td>
									@endif
									@else
									<td class="text-right text-nowrap">{{ formatAmount($item->unit_cost, currency_symbol($purchase->business->currency), $purchase->business_id) }}</td>
									@endif

									@if(isset($invoiceColumns->amount->status))
									@if($invoiceColumns->amount->status != '0')
									<td class="text-right text-nowrap">{{ formatAmount($item->sub_total, currency_symbol($purchase->business->currency), $purchase->business_id) }}</td>
									@endif
									@else
									<td class="text-right text-nowrap">{{ formatAmount($item->sub_total, currency_symbol($purchase->business->currency), $purchase->business_id) }}</td>
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
								<p><b>{{ _lang('Notes / Terms') }}:</b> {!! xss_clean($purchase->note) !!}</p>
							</div>
						</div>
						<div class="xl:col-span-5 lg:col-span-6 col-span-12 float-right">
							<table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
								<tr>
									<td>{{ _lang('Sub Total') }}</td>
									<td class="text-nowrap">{{ formatAmount($purchase->sub_total, currency_symbol($purchase->business->currency), $purchase->business_id) }}</td>
								</tr>
								@foreach($purchase->taxes as $tax)
								<tr>
									<td>{{ $tax->name }}</td>
									<td class="text-nowrap">+ {{ formatAmount($tax->amount, currency_symbol($purchase->business->currency), $purchase->business_id) }}</td>
								</tr>
								@endforeach
								@if($purchase->discount > 0)
								<tr>
									<td>{{ _lang('Discount') }}</td>
									<td class="text-nowrap">- {{ formatAmount($purchase->discount, currency_symbol($purchase->business->currency), $purchase->business_id) }}</td>
								</tr>
								@endif
								<tr>
									<td><b>{{ _lang('Grand Total') }}</b></td>
									<td class="text-nowrap"><b>{{ formatAmount($purchase->grand_total, currency_symbol($purchase->business->currency), $purchase->business_id) }}</b></td>
								</tr>
								@if($purchase->grand_total != $purchase->converted_total)
								<tr>
									<td><b>{{ _lang('Converted Total') }}</b></td>
									<td class="text-nowrap"><b>{{ formatAmount($purchase->converted_total, currency_symbol($purchase->currency), $purchase->business_id) }}</b></td>
								</tr>
								@endif
							</table>
						</div>
						<div class="clear"></div>
					</div>
				</div>
			</div>

			<div class="invoice-footer">
				<p>{!! xss_clean($purchase->footer) !!}</p>
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
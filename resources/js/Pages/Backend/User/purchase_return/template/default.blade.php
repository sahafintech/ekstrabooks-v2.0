@php $type = isset($type) ? $type : 'preview'; @endphp
<!-- Default Invoice template -->
<div class="box">
	<div class="box-body">
		<div id="invoice" class="{{ $type }}">
			<div class="default-invoice">
				<div class="invoice-header">
					<div class="grid grid-cols-12 items-center">
						<div class="col-span-6 float-left">
							@if($type == 'pdf')
							<img class="w-24" src="{{ public_path('uploads/media/' . $purchase_return->business->logo) }}">
							@else
							<img class="w-28" src="{{ asset('/uploads/media/' . $purchase_return->business->logo) }}">
							@endif
							<h2 class="text-3xl font-bold">{{ $purchase_return->title }}</h2>
						</div>
						<div class="col-span-6 float-right sm:text-right right">
							<h4 class="text-2xl font-bold">{{ $purchase_return->business->name }}</h4>
							<p>{{ $purchase_return->business->address }}</p>
							<p>{{ $purchase_return->business->phone }}</p>
							<p>{{ $purchase_return->business->email }}</p>
							<p>{{ $purchase_return->business->country }}</p>
						</div>
						<div class="clear"></div>
					</div>
				</div>

				<div class="invoice-details mt-10 mb-5">
					<div class="grid grid-cols-12 items-bottom">
						<div class="col-span-6 float-left">
							<h5 class="bill-to-heading">{{ _lang('BILLING DETAILS') }}</h5>

							<h4 class="bill-to">{{ $purchase_return->vendor->name }}</h4>
							<p>{{ $purchase_return->vendor->address }}</<p>
							<p>{{ $purchase_return->vendor->city }}</<p>
							<p>{{ $purchase_return->vendor->zip }}</<p>
							<p>{{ $purchase_return->vendor->country }}</p>
						</div>
						<div class="col-span-6 text-right float-right">
							<h5 class="mb-2">{{ _lang('Sales Return') }}#: {{ $purchase_return->return_number }}</h4>
								<p>{{ _lang('Return Date') }}: {{ $purchase_return->return_date }}</p>
								<p><strong>{{ _lang('Grand Total') }}: {{ formatAmount($purchase_return->grand_total, currency_symbol($purchase_return->business->currency), $purchase_return->business_id) }}</strong></p>
						</div>
						<div class="clear"></div>
					</div>
				</div>

				@php $salesReturnColumns = json_decode(get_business_option('purchase_return-column', null, $purchase_return->business_id)); @endphp

				<div class="invoice-body">
					<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
						<table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
							<thead>
								<tr>
									@if(isset($salesReturnColumns->name->status))
									@if($salesReturnColumns->name->status != '0')
									<th>{{ isset($salesReturnColumns->name->label) ? $salesReturnColumns->name->label : _lang('Name') }}</th>
									@endif
									@else
									<th>{{ _lang('Name') }}</th>
									@endif

									@if(isset($salesReturnColumns->quantity->status))
									@if($salesReturnColumns->quantity->status != '0')
									<th class="text-center">{{ isset($salesReturnColumns->quantity->label) ? $salesReturnColumns->quantity->label : _lang('Quantity') }}</th>
									@endif
									@else
									<th class="text-center">{{ _lang('Quantity') }}</th>
									@endif

									@if(isset($salesReturnColumns->price->status))
									@if($salesReturnColumns->price->status != '0')
									<th class="text-right">{{ isset($salesReturnColumns->price->label) ? $salesReturnColumns->price->label : _lang('Price') }}</th>
									@endif
									@else
									<th class="text-right">{{ _lang('Price') }}</th>
									@endif

									@if(isset($salesReturnColumns->amount->status))
									@if($salesReturnColumns->amount->status != '0')
									<th class="text-right">{{ isset($salesReturnColumns->amount->label) ? $salesReturnColumns->amount->label : _lang('Amount') }}</th>
									@endif
									@else
									<th class="text-right">{{ _lang('Amount') }}</th>
									@endif
								</tr>
							</thead>
							<tbody>
								@foreach($purchase_return->items as $item)
								<tr>
									<td class="product-name">
										@if(isset($salesReturnColumns->name->status))
										@if($salesReturnColumns->name->status != '0')
										<p>{{ $item->product_name }}</p>
										@endif
										@else
										<p>{{ $item->product_name }}</p>
										@endif

										@if(isset($salesReturnColumns->description->status))
										@if($salesReturnColumns->description->status != '0')
										<p>{{ $item->description }}</p>
										@endif
										@else
										<p>{{ $item->description }}</p>
										@endif
									</td>

									@if(isset($salesReturnColumns->quantity->status))
									@if($salesReturnColumns->quantity->status != '0')
									<td class="text-center">{{ $item->quantity.' '.$item->product->product_unit->unit }}</td>
									@endif
									@else
									<td class="text-center">{{ $item->quantity.' '.$item->product->product_unit->unit }}</td>
									@endif

									@if(isset($salesReturnColumns->price->status))
									@if($salesReturnColumns->price->status != '0')
									<td class="text-right text-nowrap">{{ formatAmount($item->unit_cost, currency_symbol($purchase_return->business->currency), $purchase_return->business_id) }}</td>
									@endif
									@else
									<td class="text-right text-nowrap">{{ formatAmount($item->unit_cost, currency_symbol($purchase_return->business->currency), $purchase_return->business_id) }}</td>
									@endif

									@if(isset($salesReturnColumns->amount->status))
									@if($salesReturnColumns->amount->status != '0')
									<td class="text-right text-nowrap">{{ formatAmount($item->sub_total, currency_symbol($purchase_return->business->currency), $purchase_return->business_id) }}</td>
									@endif
									@else
									<td class="text-right text-nowrap">{{ formatAmount($item->sub_total, currency_symbol($purchase_return->business->currency), $purchase_return->business_id) }}</td>
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
								<p><b>{{ _lang('Notes / Terms') }}:</b> {!! xss_clean($purchase_return->note) !!}</p>
							</div>
						</div>
						<div class="xl:col-span-5 lg:col-span-6 col-span-12 float-right">
							<table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
								<tr>
									<td>{{ _lang('Sub Total') }}</td>
									<td class="text-nowrap">{{ formatAmount($purchase_return->sub_total, currency_symbol($purchase_return->business->currency), $purchase_return->business_id) }}</td>
								</tr>
								@foreach($purchase_return->taxes as $tax)
								<tr>
									<td>{{ $tax->name }}</td>
									<td class="text-nowrap">+ {{ formatAmount($tax->amount, currency_symbol($purchase_return->business->currency), $purchase_return->business_id) }}</td>
								</tr>
								@endforeach
								@if($purchase_return->discount > 0)
								<tr>
									<td>{{ _lang('Discount') }}</td>
									<td class="text-nowrap">- {{ formatAmount($purchase_return->discount, currency_symbol($purchase_return->business->currency), $purchase_return->business_id) }}</td>
								</tr>
								@endif
								<tr>
									<td><b>{{ _lang('Grand Total') }}</b></td>
									<td class="text-nowrap"><b>{{ formatAmount($purchase_return->grand_total, currency_symbol($purchase_return->business->currency), $purchase_return->business_id) }}</b></td>
								</tr>
								@if($purchase_return->grand_total != $purchase_return->converted_total)
								<tr>
									<td><b>{{ _lang('Converted Total') }}</b></td>
									<td class="text-nowrap"><b>{{ formatAmount($purchase_return->converted_total, currency_symbol($purchase_return->currency), $purchase_return->business_id) }}</b></td>
								</tr>
								@endif
							</table>
						</div>
						<div class="clear"></div>
					</div>
				</div>
			</div>

			<div class="invoice-footer">
				<p>{!! xss_clean($purchase_return->footer) !!}</p>
			</div>
		</div>
	</div>
</div>
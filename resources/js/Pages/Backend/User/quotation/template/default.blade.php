@php $type = isset($type) ? $type : 'preview'; @endphp
<!-- Default quotation template -->
<div class="box">
	<div class="box-body">
		<div id="quotation" class="{{ $type }}">
			<div class="default-quotation">
				<div class="quotation-header">
					<div class="grid grid-cols-12 items-center p-4" style="background-color: {{ get_business_option('quotation_primary_color') }}; color: {{ get_business_option('quotation_text_color') }}">
						<div class="col-span-6 float-left">
							@if($type == 'pdf')
							<img class="w-24" src="{{ public_path('uploads/media/' . $quotation->business->logo) }}">
							@else
							<img class="w-28" src="{{ asset('/uploads/media/' . $quotation->business->logo) }}">
							@endif
							<h2 class="text-xl font-bold">{{ $quotation->title }}</h2>
						</div>
						<div class="col-span-6 float-right sm:text-right">
							<h4 class="text-2xl font-bold">{{ $quotation->business->name }}</h4>
							<p>{{ $quotation->business->address }}, {{ $quotation->business->zip }}</p>
							<p>{{ $quotation->business->phone }}</p>
							<p>{{ $quotation->business->email }}</p>
							<p>{{ $quotation->business->country }}</p>
						</div>
						<div class="clear"></div>
					</div>
				</div>

				<div class="quotation-details">
					<div class="grid grid-cols-12">
						<div class="col-span-6 float-left">
							<h5 class="bill-to-heading font-semibold underline">{{ _lang('BILLING DETAILS') }}</h5>

							<h4 class="bill-to">{{ $quotation->customer->name }}</h4>
							<p>{{ $quotation->customer->address }}</<p>
							<p>{{ $quotation->customer->zip }}</<p>
							<p>{{ $quotation->customer->city }}</<p>
							<p>{{ $quotation->customer->country }}</p>

							<!-- bank account details -->
							@if($quotation->business->bank_accounts->count() > 0)
							@foreach($quotation->business->bank_accounts as $bank_account)
							@if($bank_account->display_on_quotation == 1)
							<div class="mt-3">
								<h5>{{ _lang('Bank Name') }}: {{ $bank_account->bank_name }}</h5>
								<h5>{{ _lang('Account Name') }}: {{ $bank_account->account_name }}</h5>
								<h5 class="underline font-bold">{{ _lang('Account Number') }}: {{ $bank_account->account_number }}</h5>
							</div>
							@endif
							@endforeach
							@endif
						</div>
						@if($quotation->is_deffered == 0)
						<div class="col-span-6 text-right float-right">
							<h5 class="mb-2">{{ _lang('quotation') }}#: {{ $quotation->is_recurring == 0 ? $quotation->quotation_number : _lang('Automatic') }}</h5>
							@if($quotation->order_number != '')
							@if($quotation->is_deffered == 0)
							<p>{{ _lang('Sales Order No') }}: {{ $quotation->order_number }}</p>
							@endif
							@endif
							<p>{{ _lang('quotation Date') }}: {{ $quotation->is_recurring == 0 ? $quotation->quotation_date : $quotation->recurring_quotation_date }}</p>
							<p class="mb-2">{{ _lang('Due Date') }}: {{ $quotation->is_recurring == 0 ? $quotation->due_date : $quotation->recurring_due_date }}</p>
							<p><strong>{{ _lang('Grand Total') }}: {{ formatAmount($quotation->grand_total, currency_symbol($quotation->business->currency), $quotation->business_id) }}</strong></p>
							@if($quotation->status != 2)
							<p><strong>{{ _lang('Due Amount') }}: {{ formatAmount($quotation->grand_total - $quotation->paid, currency_symbol($quotation->business->currency), $quotation->business_id) }}</strong></p>
							@endif
							@if($quotation->is_recurring == 0 && $quotation->is_deffered == 0)
							<p><strong>{!! xss_clean(quotation_status($quotation)) !!}</strong></p>
							@endif
						</div>
						@endif
						@if($quotation->is_deffered == 1)
						<div class="col-span-6 float-right text-right">
							<h5 class="font-semibold underline">{{ _lang('POLICY DETAILS') }}</h5>
							<p>{{ _lang('Policy Number') }}: {{ $quotation->order_number }}</p>
							<p>{{ _lang('Policy Start Date') }}: {{ $quotation->deffered_start }}</p>
							<p>{{ _lang('Policy End Date') }}: {{ $quotation->deffered_end }}</p>
							<p>{{ _lang('Active Days') }}: {{ $quotation->active_days }}</p>
						</div>
						@endif
					</div>
					<div class="clear"></div>
				</div>

				@php $quotationColumns = json_decode(get_business_option('quotation_column', null, $quotation->business_id)); @endphp

				<div class="quotation-body">
					<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
						<table class="ti-custom-table ti-custom-table-head">
							<thead>
								<tr>
									@if(isset($quotationColumns->name->status))
									@if($quotationColumns->name->status != '0')
									<th style="background-color: {{ get_business_option('quotation_primary_color') }}; color: {{ get_business_option('quotation_text_color') }}">{{ isset($quotationColumns->name->label) ? $quotationColumns->name->label : _lang('Name') }}</th>
									@endif
									@else
									<th style="background-color: {{ get_business_option('quotation_primary_color') }}; color: {{ get_business_option('quotation_text_color') }}">{{ _lang('Name') }}</th>
									@endif

									@if(isset($quotationColumns->quantity->status))
									@if($quotationColumns->quantity->status != '0')
									<th class="text-center" style="background-color: {{ get_business_option('quotation_primary_color') }}; color: {{ get_business_option('quotation_text_color') }}">{{ isset($quotationColumns->quantity->label) ? $quotationColumns->quantity->label : _lang('Quantity') }}</th>
									@endif
									@else
									<th class="text-center" style="background-color: {{ get_business_option('quotation_primary_color') }}; color: {{ get_business_option('quotation_text_color') }}">{{ _lang('Quantity') }}</th>
									@endif

									@if(isset($quotationColumns->price->status))
									@if($quotationColumns->price->status != '0')
									<th class="text-right" style="background-color: {{ get_business_option('quotation_primary_color') }}; color: {{ get_business_option('quotation_text_color') }}">{{ isset($quotationColumns->price->label) ? $quotationColumns->price->label : _lang('Price') }}</th>
									@endif
									@else
									<th class="text-right" style="background-color: {{ get_business_option('quotation_primary_color') }}; color: {{ get_business_option('quotation_text_color') }}">{{ _lang('Price') }}</th>
									@endif

									@if(isset($quotationColumns->amount->status))
									@if($quotationColumns->amount->status != '0')
									<th class="text-right" style="background-color: {{ get_business_option('quotation_primary_color') }}; color: {{ get_business_option('quotation_text_color') }}">{{ isset($quotationColumns->amount->label) ? $quotationColumns->amount->label : _lang('Amount') }}</th>
									@endif
									@else
									<th class="text-right" style="background-color: {{ get_business_option('quotation_primary_color') }}; color: {{ get_business_option('quotation_text_color') }}">{{ _lang('Amount') }}</th>
									@endif
								</tr>
							</thead>
							<tbody>
								@foreach($quotation->items as $item)
								<tr>
									<td class="product-name">
										@if(isset($quotationColumns->name->status))
										@if($quotationColumns->name->status != '0')
										<p>{{ $item->product_name }}</p>
										@endif
										@else
										<p>{{ $item->product_name }}</p>
										@endif

										@if(isset($quotationColumns->description->status))
										@if($quotationColumns->description->status != '0')
										<p>{{ $item->description }}</p>
										@endif
										@else
										<p>{{ $item->description }}</p>
										@endif
									</td>

									@if(isset($quotationColumns->quantity->status))
									@if($quotationColumns->quantity->status != '0')
									<td class="text-center">{{ $item->quantity.' '.$item->product->product_unit->unit }}</td>
									@endif
									@else
									<td class="text-center">{{ $item->quantity.' '.$item->product->product_unit->unit }}</td>
									@endif

									@if(isset($quotationColumns->price->status))
									@if($quotationColumns->price->status != '0')
									<td class="text-right text-nowrap">{{ formatAmount($item->unit_cost, currency_symbol($quotation->business->currency), $quotation->business_id) }}</td>
									@endif
									@else
									<td class="text-right text-nowrap">{{ formatAmount($item->unit_cost, currency_symbol($quotation->business->currency), $quotation->business_id) }}</td>
									@endif

									@if(isset($quotationColumns->amount->status))
									@if($quotationColumns->amount->status != '0')
									<td class="text-right text-nowrap">{{ formatAmount($item->sub_total, currency_symbol($quotation->business->currency), $quotation->business_id) }}</td>
									@endif
									@else
									<td class="text-right text-nowrap">{{ formatAmount($item->sub_total, currency_symbol($quotation->business->currency), $quotation->business_id) }}</td>
									@endif
								</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				</div>

				@if($quotation->is_deffered == 1)
				<h1 class="font-semibold underline my-2">PAYMENT SCHEDULES</h1>
				<!-- payment schedules in a table -->
				<div class="quotation-body">
					<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
						<table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
							<thead>
								<tr>
									<th>{{ _lang('Date') }}</th>
									<th>{{ _lang('Due Date') }}</th>
									<th class="text-right">{{ _lang('Amount') }}</th>
								</tr>
							</thead>
							<tbody>
								@foreach($quotation->payments as $payment)
								<tr>
									<td>{{ $payment->date }}</td>
									<td>{{ $payment->due_date }}</td>
									<td>{{ formatAmount($payment->amount, currency_symbol($quotation->business->currency), $quotation->business_id) }}</td>
								</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				</div>
				@endif

				<div class="quotation-summary mt-6">
					<div class="grid grid-cols-12">
						<div class="xl:col-span-7 lg:col-span-6 col-span-12 float-left">
							<div class="quotation-note">
								<p><b>{{ _lang('Notes / Terms') }}:</b> {!! xss_clean($quotation->note) !!}</p>
							</div>
						</div>
						<div class="xl:col-span-5 lg:col-span-6 col-span-12 float-right">
							<table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
								<tr>
									<td>{{ _lang('Sub Total') }}</td>
									<td class="text-nowrap">{{ formatAmount($quotation->sub_total, currency_symbol($quotation->business->currency), $quotation->business_id) }}</td>
								</tr>
								@foreach($quotation->taxes as $tax)
								<tr>
									<td>{{ $tax->name }}</td>
									<td class="text-nowrap">+ {{ formatAmount($tax->amount, currency_symbol($quotation->business->currency), $quotation->business_id) }}</td>
								</tr>
								@endforeach
								@if($quotation->discount > 0)
								<tr>
									<td>{{ _lang('Discount') }}</td>
									<td class="text-nowrap">- {{ formatAmount($quotation->discount, currency_symbol($quotation->business->currency), $quotation->business_id) }}</td>
								</tr>
								@endif
								<tr>
									<td><b>{{ _lang('Grand Total') }}</b></td>
									<td class="text-nowrap"><b>{{ formatAmount($quotation->grand_total, currency_symbol($quotation->business->currency), $quotation->business_id) }}</b></td>
								</tr>
								@if($quotation->grand_total != $quotation->converted_total)
								<tr>
									<td><b>{{ _lang('Converted Total') }}</b></td>
									<td class="text-nowrap"><b>{{ formatAmount($quotation->converted_total, currency_symbol($quotation->currency), $quotation->business_id) }}</b></td>
								</tr>
								@endif
							</table>
						</div>
						<div class="clear"></div>
					</div>
				</div>
			</div>

			<div class="quotation-footer">
				<p>{!! xss_clean($quotation->footer) !!}</p>
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
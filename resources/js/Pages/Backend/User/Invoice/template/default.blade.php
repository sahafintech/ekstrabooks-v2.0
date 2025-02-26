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
							<img class="w-28" src="{{ public_path('uploads/media/' . $invoice->business->logo) }}">
							@else
							<img class="w-52" src="{{ asset('/uploads/media/' . $invoice->business->logo) }}">
							@endif
							<h2 class="text-xl font-bold">{{ $invoice->title }}</h2>
						</div>
						<div class="col-span-6 float-right sm:text-right">
							<h4 class="text-2xl font-bold">{{ $invoice->business->name }}</h4>
							<p>{{ $invoice->business->address }}, {{ $invoice->business->zip }}</p>
							<p>{{ $invoice->business->phone }}</p>
							<p>{{ $invoice->business->email }}</p>
							<p>{{ $invoice->business->country }}</p>
						</div>
						<div class="clear"></div>
					</div>
				</div>

				<div class="invoice-details">
					<div class="grid grid-cols-12">
						<div class="col-span-6 float-left">
							<h5 class="bill-to-heading font-semibold underline">{{ _lang('BILLING DETAILS') }}</h5>

							<h4 class="bill-to">{{ $invoice->customer->name }}</h4>
							<p>{{ $invoice->customer->address }}</<p>
							<p>{{ $invoice->customer->zip }}</<p>
							<p>{{ $invoice->customer->city }}, {{ $invoice->customer->country }}</p>

							@if($invoice->client_id != '' && ($invoice->client_id != $invoice->customer_id))
							<h5 class="bill-to-heading font-semibold underline">{{ _lang('CLIENT DETAILS') }}</h5>

							<h4 class="bill-to">{{ $invoice->client->name }}</h4>
							<p><strong>Address</strong> {{ $invoice->client->address }}</<p>
							<p><strong>Contract NO:</strong> {{ $invoice->client->contract_no }}</<p>
							<p>{{ $invoice->client->city }}, {{ $invoice->client->country }}</<p>
								@endif

								<!-- bank account details -->
								@if($invoice->business->bank_accounts->count() > 0)
								@foreach($invoice->business->bank_accounts as $bank_account)
								@if($bank_account->display_on_invoice == 1)
							<div class="mt-3">
								<h5>{{ _lang('Bank Name') }}: {{ $bank_account->bank_name }}</h5>
								<h5>{{ _lang('Account Name') }}: {{ $bank_account->account_name }}</h5>
								<h5 class="underline font-bold">{{ _lang('Account Number') }}: {{ $bank_account->account_number }}</h5>
								<h5>{{ _lang('Currency') }}: {{ $bank_account->account_currency }}</h5>
								<h5>{{ _lang('Branch') }}: {{ $bank_account->branch }}</h5>
								<h5>{{ _lang('Swift Code') }}: {{ $bank_account->swift_code }}</h5>
							</div>
							@endif
							@endforeach
							@endif
						</div>
						@if($invoice->is_deffered == 0)
						<div class="col-span-6 text-right float-right">
							<h5 class="mb-2">{{ _lang('Invoice') }}#: {{ $invoice->is_recurring == 0 ? $invoice->invoice_number : _lang('Automatic') }}</h5>
							@if($invoice->order_number != '')
							@if($invoice->is_deffered == 0)
							<p>{{ _lang('Sales Order No') }}: {{ $invoice->order_number }}</p>
							@endif
							@endif
							<p>{{ _lang('Invoice Date') }}: {{ $invoice->is_recurring == 0 ? $invoice->invoice_date : $invoice->recurring_invoice_date }}</p>
							<p class="mb-2">{{ _lang('Due Date') }}: {{ $invoice->is_recurring == 0 ? $invoice->due_date : $invoice->recurring_due_date }}</p>
							<p><strong>{{ _lang('Grand Total') }}: {{ formatAmount($invoice->grand_total, currency_symbol($invoice->business->currency), $invoice->business_id) }}</strong></p>
							@if($invoice->status != 2)
							<p><strong>{{ _lang('Due Amount') }}: {{ formatAmount($invoice->grand_total - $invoice->paid, currency_symbol($invoice->business->currency), $invoice->business_id) }}</strong></p>
							@endif
							@if($invoice->is_recurring == 0 && $invoice->is_deffered == 0)
							<p><strong>{!! xss_clean(invoice_status($invoice)) !!}</strong></p>
							@endif
						</div>
						@endif
						@if($invoice->is_deffered == 1)
						<div class="col-span-6 float-right text-right">
							<h5 class="font-semibold underline">{{ _lang('POLICY DETAILS') }}</h5>
							<p>{{ _lang('Policy Number') }}: {{ $invoice->order_number }}</p>
							<p>{{ _lang('Policy Start Date') }}: {{ $invoice->deffered_start }}</p>
							<p>{{ _lang('Policy End Date') }}: {{ $invoice->deffered_end }}</p>
							<p>{{ _lang('Active Days') }}: {{ $invoice->active_days }}</p>
						</div>
						@endif
					</div>
					<div class="clear"></div>
				</div>

				@php $invoiceColumns = json_decode(get_business_option('invoice_column', null, $invoice->business_id)); @endphp

				<div class="invoice-body">
					<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
						<table class="ti-custom-table ti-custom-table-head">
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

									@if($invoice->is_deffered == 1 && $invoice->invoice_category == 'other')
									<th class="text-center" style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ _lang('Sum Insured') }}</th>
									@endif

									@if($invoice->is_deffered == 1)
									<th class="text-center" style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ _lang('Benefits') }}</th>
									@endif

									@if($invoice->is_deffered == 1 && $invoice->invoice_category == 'medical')
									<th class="text-center" style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ _lang('Family Size') }}</th>
									@endif

									@if($invoice->is_deffered == 1 && $invoice->invoice_category != 'other')
									<th class="text-center" style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ _lang('Limits') }}</th>
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
								@foreach($invoice->items as $item)
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

									@if($invoice->is_deffered == 1 && $invoice->invoice_category == 'other')
									<td class="text-center">{{ formatAmount($item->sum_insured, currency_symbol($invoice->business->currency), $invoice->business_id) }}</td>
									@endif

									@if($invoice->is_deffered == 1)
									<td class="text-center">{{ $item->benefits }}</td>
									@endif

									@if($invoice->is_deffered == 1 && $invoice->invoice_category != 'other')
									<td class="text-center">{{ formatAmount($item->limits, currency_symbol($invoice->business->currency), $invoice->business_id) }}</td>
									@endif

									@if($invoice->is_deffered == 1 && $invoice->invoice_category == 'medical')
									<td class="text-center">{{ $item->family_size }}</td>
									@endif

									@if(isset($invoiceColumns->price->status))
									@if($invoiceColumns->price->status != '0')
									<td class="text-right text-nowrap">{{ formatAmount($item->unit_cost, currency_symbol($invoice->business->currency), $invoice->business_id) }}</td>
									@endif
									@else
									<td class="text-right text-nowrap">{{ formatAmount($item->unit_cost, currency_symbol($invoice->business->currency), $invoice->business_id) }}</td>
									@endif

									@if(isset($invoiceColumns->amount->status))
									@if($invoiceColumns->amount->status != '0')
									<td class="text-right text-nowrap">{{ formatAmount($item->sub_total, currency_symbol($invoice->business->currency), $invoice->business_id) }}</td>
									@endif
									@else
									<td class="text-right text-nowrap">{{ formatAmount($item->sub_total, currency_symbol($invoice->business->currency), $invoice->business_id) }}</td>
									@endif
								</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				</div>

				@if($invoice->is_deffered == 1)
				<!-- <h1 class="font-semibold underline my-2">PAYMENT SCHEDULES</h1> -->
				<!-- payment schedules in a table -->
				<!-- <div class="invoice-body">
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
								@foreach($invoice->payments as $payment)
								<tr>
									<td>{{ $payment->date }}</td>
									<td>{{ $payment->due_date }}</td>
									<td>{{ formatAmount($payment->amount, currency_symbol($invoice->business->currency), $invoice->business_id) }}</td>
								</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				</div> -->
				@endif

				<div class="invoice-summary mt-6">
					<div class="grid grid-cols-12">
						<div class="xl:col-span-7 lg:col-span-6 col-span-12 float-left">
							<div class="invoice-note">
								<p><b>{{ _lang('Notes / Terms') }}:</b> {!! xss_clean($invoice->note) !!}</p>
							</div>
						</div>
						<div class="xl:col-span-5 lg:col-span-6 col-span-12 float-right">
							<table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
								<tr>
									<td>{{ _lang('Sub Total') }}</td>
									<td class="text-nowrap">{{ formatAmount($invoice->sub_total, currency_symbol($invoice->business->currency), $invoice->business_id) }}</td>
								</tr>
								@foreach($invoice->taxes as $tax)
								<tr>
									<td>{{ $tax->name }}</td>
									<td class="text-nowrap">+ {{ formatAmount($tax->amount, currency_symbol($invoice->business->currency), $invoice->business_id) }}</td>
								</tr>
								@endforeach
								@if($invoice->discount > 0)
								<tr>
									<td>{{ _lang('Discount') }}</td>
									<td class="text-nowrap">- {{ formatAmount($invoice->discount, currency_symbol($invoice->business->currency), $invoice->business_id) }}</td>
								</tr>
								@endif
								<tr>
									<td><b>{{ _lang('Grand Total') }}</b></td>
									<td class="text-nowrap"><b>{{ formatAmount($invoice->grand_total, currency_symbol($invoice->business->currency), $invoice->business_id) }}</b></td>
								</tr>
								@if($invoice->grand_total != $invoice->converted_total)
								<tr>
									<td><b>{{ _lang('Converted Total') }}</b></td>
									<td class="text-nowrap"><b>{{ formatAmount($invoice->converted_total, currency_symbol($invoice->currency), $invoice->business_id) }}</b></td>
								</tr>
								@endif
							</table>
						</div>
						<div class="clear"></div>
					</div>
				</div>
			</div>

			<div class="invoice-footer">
				<p>{!! xss_clean($invoice->footer) !!}</p>
			</div>
		</div>
	</div>
</div>

<!-- page break -->
<div id="page-break"></div>

@if($invoice->is_deffered == 1)
<div class="box">
	<div class="box-body">
		<div class="default-invoice">
			<h1 class="font-semibold underline my-2">REVENUE SCHEDULE</h1>
			<div class="invoice-body">
				<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
					<table class="ti-custom-table ti-custom-table-head">
						<thead>
							<tr>
								<th style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ _lang('Policy Active Days') }}</th>
								<th style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ _lang('Cost Per Day') }}</th>
								<th style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ _lang('Additional Cost') }}</th>
								<th style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ _lang('Deduction Cost') }}</th>
								<th style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ _lang('Total Cost') }}</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td>{{ $invoice->active_days }}</td>
								<td>{{ formatAmount($invoice->cost_per_day, currency_symbol($invoice->business->currency), $invoice->business_id) }}</td>
								<td>{{ formatAmount($invoice->deffered_additions->sum('amount'), currency_symbol($invoice->business->currency), $invoice->business_id) }}</td>
								<td>{{ formatAmount($invoice->deffered_deductions->sum('amount'), currency_symbol($invoice->business->currency), $invoice->business_id) }}</td>
								<td>{{ formatAmount($invoice->sub_total, currency_symbol($invoice->business->currency), $invoice->business_id) }}</td>
							</tr>
						</tbody>
					</table>

					<!-- deffered revenue schedule -->
					<h1 class="font-semibold underline my-2">DEFFERED REVENUE SCHEDULE</h1>

					<table class="ti-custom-table ti-custom-table-head">
						<thead>
							<tr>
								<th style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ _lang('Revenue Start Date') }}</th>
								<th style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ _lang('Revenue End Date') }}</th>
								<th style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ _lang('No. of Days') }}</th>
								<th style="background-color: {{ get_business_option('invoice_primary_color') }}; color: {{ get_business_option('invoice_text_color') }}">{{ _lang('Revenue Recognized Amount') }}</th>
							</tr>
						</thead>
						<tbody>
							@foreach($invoice->deffered_earnings as $earning)
							<tr>
								<td>{{ $earning->start_date }}</td>
								<td>{{ $earning->end_date }}</td>
								<td>{{ $earning->days }}</td>
								<td>{{ formatAmount($earning->amount, currency_symbol($invoice->business->currency), $invoice->business_id) }}</td>
							</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</div>
@endif

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
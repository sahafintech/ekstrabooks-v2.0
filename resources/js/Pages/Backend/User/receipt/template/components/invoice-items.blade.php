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

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

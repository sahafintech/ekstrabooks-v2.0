<tr>
    <td>{{ _lang('Sub Total') }}</td>
    <td class="text-nowrap text-right">{{ formatAmount($quotation->sub_total, currency_symbol($quotation->business->currency), $quotation->business_id) }}</td>
</tr>
@foreach($quotation->taxes as $tax)
<tr>
    <td>{{ $tax->name }}</td>						
    <td class="text-nowrap text-right">+ {{ formatAmount($tax->amount, currency_symbol($quotation->business->currency), $quotation->business_id) }}</td>
</tr>
@endforeach	
@if($quotation->discount > 0)
<tr>
    <td>{{ _lang('Discount') }}</td>
    <td class="text-nowrap text-right">- {{ formatAmount($quotation->discount, currency_symbol($quotation->business->currency), $quotation->business_id) }}</td>
</tr>
@endif
<tr>
    <td><b>{{ _lang('Grand Total') }}</b></td>
    <td class="text-nowrap text-right"><b>{{ formatAmount($quotation->grand_total, currency_symbol($quotation->business->currency), $quotation->business_id) }}</b></td>
</tr>
@if($quotation->grand_total != $quotation->converted_total)
<tr>
    <td><b>{{ _lang('Converted Total') }}</b></td>
    <td class="text-nowrap text-right"><b>{{ formatAmount($quotation->converted_total, currency_symbol($quotation->customer->currency), $quotation->business_id) }}</b></td>
</tr>
@endif
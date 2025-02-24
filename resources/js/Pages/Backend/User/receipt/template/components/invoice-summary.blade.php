<tr>
    <td>{{ _lang('Sub Total') }}</td>
    <td class="text-nowrap text-right">{{ formatAmount($invoice->sub_total, currency_symbol($invoice->business->currency), $invoice->business_id) }}</td>
</tr>
@foreach($invoice->taxes as $tax)
<tr>
    <td>{{ $tax->name }}</td>						
    <td class="text-nowrap text-right">+ {{ formatAmount($tax->amount, currency_symbol($invoice->business->currency), $invoice->business_id) }}</td>
</tr>
@endforeach	
@if($invoice->discount > 0)
<tr>
    <td>{{ _lang('Discount') }}</td>
    <td class="text-nowrap text-right">- {{ formatAmount($invoice->discount, currency_symbol($invoice->business->currency), $invoice->business_id) }}</td>
</tr>
@endif
<tr>
    <td><b>{{ _lang('Grand Total') }}</b></td>
    <td class="text-nowrap text-right"><b>{{ formatAmount($invoice->grand_total, currency_symbol($invoice->business->currency), $invoice->business_id) }}</b></td>
</tr>
@if($invoice->grand_total != $invoice->converted_total)
<tr>
    <td><b>{{ _lang('Converted Total') }}</b></td>
    <td class="text-nowrap text-right"><b>{{ formatAmount($invoice->converted_total, currency_symbol($invoice->customer->currency), $invoice->business_id) }}</b></td>
</tr>
@endif
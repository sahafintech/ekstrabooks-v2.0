@foreach($quotation->taxes as $tax)
<span class="tax-loop-name">{{ $tax->name }}:</span>
<span class="tax-loop-amount">{{ formatAmount($tax->amount, currency_symbol($quotation->business->currency), $quotation->business_id) }}</span><br>
@endforeach	
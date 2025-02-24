@foreach($invoice->taxes as $tax)
<span class="tax-loop-name">{{ $tax->name }}:</span>
<span class="tax-loop-amount">{{ formatAmount($tax->amount, currency_symbol($invoice->business->currency), $invoice->business_id) }}</span><br>
@endforeach	
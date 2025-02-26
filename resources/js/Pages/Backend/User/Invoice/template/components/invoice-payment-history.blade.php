@if($invoice->transactions->count() > 0)
    @foreach($invoice->transactions as $transaction)
    <tr>
        <td>{{ $transaction->trans_date }}</td>
        <td>{{ $transaction->method }}</td>
        <td class="text-right">{{ formatAmount($transaction->amount, currency_symbol($transaction->account->currency), $invoice->business_id) }}</td>
        <td class="text-right">{{ formatAmount($transaction->ref_amount, currency_symbol($invoice->business->currency), $invoice->business_id) }}</td>
    </tr>
    @endforeach
@else
<tr>
    <td colspan="4" class="text-center">{{ _lang('No Transaction available') }}</td>
</tr>
@endif
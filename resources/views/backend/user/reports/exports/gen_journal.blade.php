<table>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td style="text-align: center; font-size: 12px;">
            <b>{{ request()->activeBusiness->name }}</b>
        </td>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td style="text-align: center; font-size: 12px;">
            <b>General Journal Report</b>
        </td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td style="text-align: center; font-size: 12px;">
            <b>{{ $date1->format(get_date_format()) }} - {{ $date2->format(get_date_format()) }}</b>
        </td>
    </tr>
    <tr>
        <td style="background-color: #d1d5db; font-size: 11px; font-weight: bold; border: 1px solid #9ca3af;">{{ _lang('Date') }}</td>
        <td style="background-color: #d1d5db; font-size: 11px; font-weight: bold; border: 1px solid #9ca3af;">{{ _lang('Account') }}</td>
        <td style="background-color: #d1d5db; font-size: 11px; font-weight: bold; border: 1px solid #9ca3af;">{{ _lang('Description') }}</td>
        <td style="background-color: #d1d5db; font-size: 11px; font-weight: bold; border: 1px solid #9ca3af;">{{ _lang('Ref Type') }}</td>
        <td style="background-color: #d1d5db; font-size: 11px; font-weight: bold; border: 1px solid #9ca3af;">{{ _lang('Ref ID') }}</td>
        <td style="background-color: #d1d5db; font-size: 11px; font-weight: bold; border: 1px solid #9ca3af;">{{ _lang('Name') }}</td>
        <td style="background-color: #d1d5db; font-size: 11px; font-weight: bold; border: 1px solid #9ca3af;">{{ _lang('Transaction Currency') }}</td>
        <td style="background-color: #d1d5db; font-size: 11px; font-weight: bold; border: 1px solid #9ca3af; text-align: right;">{{ _lang('Transaction Amount[debit]') }}</td>
        <td style="background-color: #d1d5db; font-size: 11px; font-weight: bold; border: 1px solid #9ca3af; text-align: right;">{{ _lang('Transaction Amount[credit]') }}</td>
        <td style="background-color: #d1d5db; font-size: 11px; font-weight: bold; border: 1px solid #9ca3af;">{{ _lang('Currency Rate') }}</td>
        <td style="background-color: #d1d5db; font-size: 11px; font-weight: bold; border: 1px solid #9ca3af;">{{ _lang('Rate') }}</td>
        <td style="background-color: #d1d5db; font-size: 11px; font-weight: bold; border: 1px solid #9ca3af;">{{ _lang('Base Currency') }}</td>
        <td style="background-color: #d1d5db; font-size: 11px; font-weight: bold; border: 1px solid #9ca3af; text-align: right;">{{ _lang('Base Amount[debit]') }}</td>
        <td style="background-color: #d1d5db; font-size: 11px; font-weight: bold; border: 1px solid #9ca3af; text-align: right;">{{ _lang('Base Amount[credit]') }}</td>
    </tr>
    <tbody>
        @if(isset($report_data))
        @foreach($report_data as $transaction)
        <tr>
            <td style="font-size: 10px; border: 1px solid #e5e7eb;">{{ $transaction->trans_date }}</td>
            <td style="font-size: 10px; border: 1px solid #e5e7eb;">{{ $transaction->account->account_name }}</td>
            <td style="font-size: 10px; border: 1px solid #e5e7eb;">{{ $transaction->description }}</td>
            <td style="font-size: 10px; border: 1px solid #e5e7eb;">
                @if($transaction->ref_type == 'receipt')
                cash invoice
                @elseif($transaction->ref_type == 'bill invoice')
                credit purchase
                @elseif($transaction->ref_type == 'bill payment')
                credit purchase payment
                @else
                {{ $transaction->ref_type }}
                @endif
            </td>
            <td style="font-size: 10px; border: 1px solid #e5e7eb;">
                @if($transaction->ref_type == 'receipt')
                {{ $transaction->receipt->receipt_number }}
                @elseif($transaction->ref_type == 'bill invoice')
                {{ $transaction->purchase->bill_no }}
                @elseif($transaction->ref_type == 'bill payment')
                {{ $transaction->purchase->bill_no }}
                @elseif($transaction->ref_type == 'journal')
                {{ $transaction->journal->journal_number }}
                @elseif($transaction->ref_type == 'cash purchase')
                {{ $transaction->purchase->bill_no }}
                @else
                {{ $transaction->ref_id }}
                @endif
            </td>
            <td style="font-size: 10px; border: 1px solid #e5e7eb;">
                {{ $transaction->payee_name }}
            </td>
            <td style="font-size: 10px; border: 1px solid #e5e7eb;">
                {{ $transaction->transaction_currency }}
            </td>
            <td style="font-size: 10px; border: 1px solid #e5e7eb; text-align: right;">
                @if($transaction->dr_cr == 'dr')
                {{ $transaction->transaction_amount }}
                @else
                {{ 0 }}
                @endif
            </td>
            <td style="font-size: 10px; border: 1px solid #e5e7eb; text-align: right;">
                @if($transaction->dr_cr == 'cr')
                {{ $transaction->transaction_amount }}
                @else
                {{ 0 }}
                @endif
            </td>
            <td style="font-size: 10px; border: 1px solid #e5e7eb;">{{ $transaction->transaction_currency }}</td>
            <td style="font-size: 10px; border: 1px solid #e5e7eb;">
                {{ $transaction->currency_rate }}
            </td>
            <td style="font-size: 10px; border: 1px solid #e5e7eb;">{{ request()->activeBusiness->currency }}</td>
            <td style="font-size: 10px; border: 1px solid #e5e7eb; text-align: right;">
                @if($transaction->dr_cr == 'dr')
                {{ $transaction->base_currency_amount }}
                @else
                {{ 0 }}
                @endif
            </td>
            <td style="font-size: 10px; border: 1px solid #e5e7eb; text-align: right;">
                @if($transaction->dr_cr == 'cr')
                {{ $transaction->base_currency_amount }}
                @else
                {{ 0 }}
                @endif
            </td>
        </tr>
        @endforeach
        <tr>
            <td style="background-color: #d1d5db; font-size: 10px; border: 2px solid #6b7280; font-weight: bold;">{{ _lang('Total') }}</td>
            <td style="background-color: #d1d5db; font-size: 10px; border: 2px solid #6b7280;"></td>
            <td style="background-color: #d1d5db; font-size: 10px; border: 2px solid #6b7280;"></td>
            <td style="background-color: #d1d5db; font-size: 10px; border: 2px solid #6b7280;"></td>
            <td style="background-color: #d1d5db; font-size: 10px; border: 2px solid #6b7280;"></td>
            <td style="background-color: #d1d5db; font-size: 10px; border: 2px solid #6b7280;"></td>
            <td style="background-color: #d1d5db; font-size: 10px; border: 2px solid #6b7280;"></td>
            <td style="background-color: #d1d5db; font-size: 10px; border: 2px solid #6b7280;"></td>
            <td style="background-color: #d1d5db; font-size: 10px; border: 2px solid #6b7280;"></td>
            <td style="background-color: #d1d5db; font-size: 10px; border: 2px solid #6b7280;"></td>
            <td style="background-color: #d1d5db; font-size: 10px; border: 2px solid #6b7280;"></td>
            <td style="background-color: #d1d5db; font-size: 10px; border: 2px solid #6b7280;"></td>
            <td style="background-color: #d1d5db; font-size: 10px; border: 2px solid #6b7280; text-align: right; font-weight: bold;">
                {{ $report_data->where('dr_cr', 'dr')->sum('base_currency_amount') ?? 0 }}
            </td>
            <td style="background-color: #d1d5db; font-size: 10px; border: 2px solid #6b7280; text-align: right; font-weight: bold;">
                {{ $report_data->where('dr_cr', 'cr')->sum('base_currency_amount') ?? 0 }}
            </td>
        </tr>
        @endif
    </tbody>
</table>
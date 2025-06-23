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
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('Date') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('Account') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('Description') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('Ref Type') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('Ref ID') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('Name') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('Transaction Currency') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('Transaction Amount[debit]') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('Transaction Amount[credit]') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('Currency Rate') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('Rate') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('Base Currency') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('Base Amount[debit]') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('Base Amount[credit]') }}</td>
    </tr>
    <tbody>
        @if(isset($report_data))
        @foreach($report_data as $transaction)
        <tr>
            <td>{{ $transaction->trans_date }}</td>
            <td>{{ $transaction->account->account_name }}</td>
            <td>{{ $transaction->description }}</td>
            <td>
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
            <td>
                @if($transaction->ref_type == 'receipt')
                {{ $transaction->receipt->receipt_number }}
                @elseif($transaction->ref_type == 'bill invoice')
                {{ $transaction->purchase->purchase_number }}
                @elseif($transaction->ref_type == 'bill payment')
                {{ $transaction->purchase->purchase_number }}
                @elseif($transaction->ref_type == 'journal')
                {{ $transaction->journal->journal_number }}
                @else
                {{ $transaction->ref_id }}
                @endif
            </td>
            <td>
                {{ $transaction->payee_name }}
            </td>
            <td>
                {{ $transaction->transaction_currency }}
            </td>
            <td>
                @if($transaction->dr_cr == 'dr')
                {{ $transaction->transaction_amount }}
                @else
                0
                @endif
            </td>
            <td>
                @if($transaction->dr_cr == 'cr')
                {{ $transaction->transaction_amount }}
                @else
                0
                @endif
            </td>
            <td>{{ $transaction->transaction_currency }}</td>
            <td>
                {{ $transaction->currency_rate }}
            </td>
            <td>{{ request()->activeBusiness->currency }}</td>
            <td>
                @if($transaction->dr_cr == 'dr')
                {{ $transaction->base_currency_amount }}
                @else
                0
                @endif
            </td>
            <td>
                @if($transaction->dr_cr == 'cr')
                {{ $transaction->base_currency_amount }}
                @else
                0
                @endif
            </td>
        </tr>
        @endforeach
        <tr>
            <td style="background-color: lightgray; font-size: 12px;"><b>{{ _lang('Total') }}</b></td>
            <td style="background-color: lightgray;"></td>
            <td style="background-color: lightgray;"></td>
            <td style="background-color: lightgray;"></td>
            <td style="background-color: lightgray;"></td>
            <td style="background-color: lightgray;"></td>
            <td style="background-color: lightgray;"></td>
            <td style="background-color: lightgray;"></td>
            <td style="background-color: lightgray;"></td>
            <td style="background-color: lightgray;"></td>
            <td style="background-color: lightgray;"></td>
            <td style="background-color: lightgray;"></td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $report_data->where('dr_cr', 'dr')->sum('base_currency_amount') ?? 0 }}</b>
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $report_data->where('dr_cr', 'cr')->sum('base_currency_amount') ?? 0 }}</b>
            </td>
        </tr>
        @endif
    </tbody>
</table>
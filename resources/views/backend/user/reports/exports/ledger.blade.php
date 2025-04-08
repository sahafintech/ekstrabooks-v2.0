<table>
    <tr>
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
        <td style="text-align: center; font-size: 12px;">
            <b>Ledger Report</b>
        </td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td></td>
        <td style="text-align: center; font-size: 12px;">
            <b>{{ $date1->format(get_date_format()) }} - {{ $date2->format(get_date_format()) }}</b>
        </td>
    </tr>
    <tr>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('Reference Id') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('Date') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('Account Name') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('Account Code') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('Account Type') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('Description') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('Transaction Type') }}</td>
        <td style="background-color: lightgray; font-size: 12px;">{{ _lang('Transaction Number') }}</td>
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
            <td>
                @if($transaction->ref_type == 'journal')
                {{ \App\Models\Journal::find($transaction->ref_id)->journal_number }}
                @else
                {{ $transaction->ref_id }}
                @endif
            </td>
            <td>{{ $transaction->trans_date }}</td>
            <td>{{ $transaction->account->account_name }}</td>
            <td>{{ $transaction->account->account_code }}</td>
            <td>{{ $transaction->account->account_type }}</td>
            <td>{{ $transaction->description }}</td>
            <td>
                @if($transaction->ref_type == 'receipt')
                cash invoice
                @elseif($transaction->ref_type == 'invoice')
                credit invoice
                @elseif($transaction->ref_type == 'invoice payment')
                credit invoice payment
                @elseif($transaction->ref_type == 'open')
                OB
                @else
                {{ $transaction->ref_type }}
                @endif
            </td>
            <td>
                @if($transaction->ref_type == 'invoice')
                INV-{{ $transaction->ref_id }}
                @elseif($transaction->ref_type == 'receipt')
                CIN-{{ $transaction->ref_id }}
                @elseif ($transaction->ref_type == 'invoice payment')
                IPay-{{ substr($transaction->description, strpos($transaction->description, "#") + 1) }}
                @elseif ($transaction->ref_type == 'cash purchase')
                CPR-{{ $transaction->ref_id }}
                @elseif ($transaction->ref_type == 'bill payment')
                BPay-{{ substr($transaction->description, strpos($transaction->description, "#") + 1) }}
                @elseif ($transaction->ref_type == 'bill invoice')
                BPR-{{ $transaction->ref_id }}
                @elseif ($transaction->ref_type == 'journal')
                JE-{{ $transaction->ref_id }}
                @elseif ($transaction->ref_type == 'product')
                P-{{ $transaction->ref_id }}
                @elseif ($transaction->ref_type == 'product adjustment')
                PAD-{{ $transaction->ref_id }}
                @elseif ($transaction->ref_type == 'cash purchase payment')
                CPRPay-{{ $transaction->ref_id }}
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
            <td style="background-color: lightgray;"></td>
            <td style="background-color: lightgray;"></td>
            <td style="background-color: lightgray;"></td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $report_data->where('dr_cr', 'dr')->sum('base_currency_amount') }}</b>
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $report_data->where('dr_cr', 'cr')->sum('base_currency_amount') }}</b>
            </td>
        </tr>
        @endif
    </tbody>
</table>
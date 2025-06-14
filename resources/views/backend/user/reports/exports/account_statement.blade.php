<table>
    <thead>
        <tr>
            <th>{{ _lang('Transaction Date') }}</th>
            <th>{{ _lang('Description') }}</th>
            <th>{{ _lang('Debit') }}</th>
            <th>{{ _lang('Credit') }}</th>
        </tr>
    </thead>
    <tbody>
        @foreach($account->transactions as $index => $transaction)
        <tr>
            <td>
                {{ $transaction->trans_date }}
            </td>
            <td>{{ $transaction->description }}</td>
            <td>
                @if($transaction->dr_cr == 'dr')
                @if($account->currency != NULL)
                {{ $transaction->transaction_amount }}
                @else
                {{ $transaction->transaction_amount }}
                @endif
                @endif
            </td>
            <td>
                @if($transaction->dr_cr == 'cr')
                @if($account->currency != NULL)
                {{ $transaction->transaction_amount }}
                @else
                {{ $transaction->transaction_amount }}
                @endif
                @endif
            </td>
        </tr>
        @endforeach
        <tr>
            <td></td>
            <td>
                <span>TOTAL</span>
            </td>
            <td>
                <span>
                    @if($account->currency != NULL)
                    {{ $account->transactions->where('dr_cr', 'dr')->sum('transaction_amount') }}
                    @else
                    {{ $account->transactions->where('dr_cr', 'dr')->sum('transaction_amount') }}
                    @endif
                    </span>
            </td>
            <td>
                <span>
                    @if($account->currency != NULL)
                    {{ $account->transactions->where('dr_cr', 'cr')->sum('transaction_amount') }}
                    @else
                    {{ $account->transactions->where('dr_cr', 'cr')->sum('transaction_amount') }}
                    @endif
                </span>
            </td>
            <td>
                <span>
                    @if($account->currency != NULL)
                    <!-- if account dr_cr = cr -->
                    @if($account->dr_cr == 'cr')
                    {{ $account->transactions->where('dr_cr', 'cr')->sum('transaction_amount') - $account->transactions->where('dr_cr', 'dr')->sum('transaction_amount') }}
                    @else
                    {{ $account->transactions->where('dr_cr', 'dr')->sum('transaction_amount') - $account->transactions->where('dr_cr', 'cr')->sum('transaction_amount') }}
                    @endif
                    @else
                    <!-- if account dr_cr = cr -->
                    @if($account->dr_cr == 'cr')
                    {{ $account->transactions->where('dr_cr', 'cr')->sum('transaction_amount') - $account->transactions->where('dr_cr', 'dr')->sum('transaction_amount') }}
                    @else
                    {{ $account->transactions->where('dr_cr', 'dr')->sum('transaction_amount') - $account->transactions->where('dr_cr', 'cr')->sum('transaction_amount') }}
                    @endif
                    @endif
                </span>
            </td>
        </tr>
    </tbody>
</table>
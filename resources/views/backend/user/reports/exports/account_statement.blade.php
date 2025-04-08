<table>
    <thead>
        <tr>
            <th>{{ _lang('Transaction Date') }}</th>
            <th>{{ _lang('Description') }}</th>
            <th>{{ _lang('Debit') }}</th>
            <th>{{ _lang('Credit') }}</th>
            <th>{{ _lang('Balance') }}</th>
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
                {{ $transaction->base_currency_amount }}
                @else
                {{ $transaction->base_currency_amount }}
                @endif
                @endif
            </td>
            <td>
                @if($transaction->dr_cr == 'cr')
                @if($account->currency != NULL)
                {{ $transaction->base_currency_amount }}
                @else
                {{ $transaction->base_currency_amount }}
                @endif
                @endif
            </td>
            <td>
                @if($account->dr_cr == 'dr')
                @if($index == 0)
                @if($account->currency != NULL)
                {{ $transaction->base_currency_amount }}
                @else
                {{ $transaction->base_currency_amount }}
                @endif
                @else
                @if($account->currency != NULL)
                {{ $account->transactions->where('dr_cr', 'cr')->take($index + 1)->sum('base_currency_amount') - $account->transactions->where('dr_cr', 'dr')->take($index + 1)->sum('base_currency_amount') }}
                @else
                {{ $account->transactions->where('dr_cr', 'dr')->take($index + 1)->sum('base_currency_amount') - $account->transactions->where('dr_cr', 'cr')->take($index + 1)->sum('base_currency_amount') }}
                @endif
                @endif
                @else
                @if($index == 0)
                @if($account->currency != NULL)
                {{ $transaction->base_currency_amount }}
                @else
                {{ $transaction->base_currency_amount}}
                @endif
                @else
                @if($account->currency != NULL)
                {{ $account->transactions->where('dr_cr', 'cr')->take($index + 1)->sum('base_currency_amount') - $account->transactions->where('dr_cr', 'dr')->take($index + 1)->sum('base_currency_amount') }}
                @else
                {{ $account->transactions->where('dr_cr', 'cr')->take($index + 1)->sum('base_currency_amount') - $account->transactions->where('dr_cr', 'dr')->take($index + 1)->sum('base_currency_amount') }}
                @endif
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
                    {{ $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount') }}
                    @else
                    {{ $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount') }}
                    @endif
                    </span>
            </td>
            <td>
                <span>
                    @if($account->currency != NULL)
                    {{ $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount') }}
                    @else
                    {{ $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount') }}
                    @endif
                </span>
            </td>
            <td>
                <span>
                    @if($account->currency != NULL)
                    <!-- if account dr_cr = cr -->
                    @if($account->dr_cr == 'cr')
                    {{ $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount') - $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount') }}
                    @else
                    {{ $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount') - $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount') }}
                    @endif
                    @else
                    <!-- if account dr_cr = cr -->
                    @if($account->dr_cr == 'cr')
                    {{ $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount') - $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount') }}
                    @else
                    {{ $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount') - $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount') }}
                    @endif
                    @endif
                </span>
            </td>
        </tr>
    </tbody>
</table>
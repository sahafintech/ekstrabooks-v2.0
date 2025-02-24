<div class="box">
    <div class="box-header">
        <h5>{{ _lang('Transactions') }}</h5>
    </div>
    <div class="box-body">
        <div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
            <table id="datatable" class="ti-custom-table ti-custom-table-head whitespace-nowrap">
                <thead>
                    <tr>
                        <th>{{ _lang('Date') }}</th>
                        <th>{{ _lang('Account') }}</th>
                        <th>{{ _lang('Description') }}</th>
                        <th class="text-right">{{ _lang('Amount') }}</th>
                        <th class="text-center">{{ _lang('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $transaction)
                    <tr>
                        @php
                        if($transaction->account->dr_cr == 'dr'){
                        $symbol = $transaction->dr_cr == 'cr' ? '-' : '+';
                        $class = $transaction->dr_cr == 'cr' ? 'text-danger' : 'text-success';
                        }else{
                        $symbol = $transaction->dr_cr == 'dr' ? '-' : '+';
                        $class = $transaction->dr_cr == 'dr' ? 'text-danger' : 'text-success';
                        }
                        @endphp

                        <td>{{ $transaction->trans_date }}</td>
                        <td>{{ $transaction->account->account_name }} ({{ $transaction->account->currency }})</td>
                        <td>{{ $transaction->description }}</td>
                        <td class="text-right"><span class="{{ $class }}">{{ $symbol.' '.formatAmount($transaction->base_currency_amount, currency_symbol($transaction->account->currency)) }}</span></td>
                        <td class="text-center">
                            <a class="btn btn-xs btn-outline-primary ajax-modal" data-title="{{ _lang('Transaction Details') }}" href="{{ route('transactions.show', $transaction['id']) }}"><i class="far fa-eye mr-1"></i>{{ _lang('Preview') }}</a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
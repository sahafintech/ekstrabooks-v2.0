<div class="ti-modal-header">
    <h3 class="ti-modal-title">
        Deffered Payments
    </h3>
    <button type="button" class="hs-dropdown-toggle ti-modal-close-btn" data-hs-overlay="#payments-modal">
        <span class="sr-only">Close</span>
        <i class="ri-close-line text-xl"></i>
    </button>
</div>
<div class="ti-modal-body grid grid-cols-12">
    <div class="table-bordered rounded-sm ti-custom-table-head overflow-auto col-span-12">
        <table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
            <thead>
                <tr>
                    <th>{{ _lang('Date') }}</th>
                    <th>{{ _lang('Due Date') }}</th>
                    <th class="text-right">{{ _lang('Amount') }}</th>
                    <th class="text-right">{{ _lang('Due') }}</th>
                    <th>{{ _lang('Status') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                <tr>
                    <td>{{ $payment->date }}</td>
                    <td>{{ $payment->due_date }}</td>
                    <td>{{ formatAmount($payment->amount, currency_symbol(request()->activeBusiness->currency), $payment->business_id) }}</td>
                    <td>{{ formatAmount($payment->amount - $payment->paid, currency_symbol(request()->activeBusiness->currency), $payment->business_id) }}</td>
                    <td>
                        @if($payment->status == 0 && $payment->due_date >= date(get_date_format()))
                        <span class='text-secondary'>Active</span>
                        @elseif($payment->status == 0 && $payment->due_date < date(get_date_format())) <span class='text-danger'>Overdue</span>
                            @elseif($payment->status == 1)
                            <span class="text-warning">Partial Paid</span>
                            @else
                            <span class="text-success">Paid</span>
                            @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
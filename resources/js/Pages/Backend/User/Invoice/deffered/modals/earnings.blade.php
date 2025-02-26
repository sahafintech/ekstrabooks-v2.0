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
                    <th>{{ _lang('Revenue Start Date') }}</th>
                    <th>{{ _lang('Revenue End Date') }}</th>
                    <th class="text-right">{{ _lang('Amount') }}</th>
                    <th>{{ _lang('Status') }}</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payments as $payment)
                <tr>
                    <td>{{ $payment->start_date }}</td>
                    <td>{{ $payment->end_date }}</td>
                    <td>{{ $payment->amount }}</td>
                    <td>
                        @if($payment->status == 0)
                        <span class='text-danger'>Unearned</span>
                        @else
                        <span class="text-secondary">Earned</span>
                        @endif
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
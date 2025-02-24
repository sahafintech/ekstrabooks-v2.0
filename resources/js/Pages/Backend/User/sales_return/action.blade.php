<div class="box">
    <div class="box-header flex items-center justify-between">
        <span class="panel-title">{{ _lang('Sales Return') }} #{{ $sales_return->return_number }} </span>
        <div>
            <div class="hs-dropdown ti-dropdown">
                <button id="hs-dropdown-with-icons" type="button" class="hs-dropdown-toggle ti-dropdown-toggle">
                    Actions
                    <svg class="hs-dropdown-open:rotate-180 ti-dropdown-caret" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M2 5L8.16086 10.6869C8.35239 10.8637 8.64761 10.8637 8.83914 10.6869L15 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                </button>

                <div class="hs-dropdown-menu ti-dropdown-menu divide-y divide-gray-200" aria-labelledby="hs-dropdown-with-icons">
                    <div class="ti-dropdown-divider">
                        <hr>
                        <a href="#" class="ti-dropdown-item print" data-print="invoice">
                            <i class="ri-printer-line text-lg"></i>
                            {{ _lang('Print Return') }}
                        </a>
                        <a href="{{ route('sales_returns.export_pdf', $sales_return->id) }}" class="ti-dropdown-item">
                            <i class="ri-file-pdf-line text-lg"></i>
                            {{ _lang('Export PDF') }}
                        </a>
                        <hr>
                        @if($sales_return->status == 0)
                        <a href="{{ route('sales_returns.edit', $sales_return->id) }}" class="ti-dropdown-item">
                            <i class="ri-file-edit-line text-lg"></i>
                            {{ _lang('Edit') }}
                        </a>
                        @endif
                        <a class="w-full ti-dropdown-item" href="javascript:void(0);" data-hs-overlay="#delete-modal" data-id="{{ $sales_return['id'] }}" id="delete">
                            <i class="ri-delete-bin-line text-lg"></i>
                            {{ _lang('Delete') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="box-body">
        <div class="md:flex justify-between border border-secondary rounded-md p-3 mb-3">
            <div>
                <span>{{ _lang('Customer') }}:</span>
                <span>{{ $sales_return->customer->name }}</span>
            </div>
            <div>
                <span>{{ _lang('Grand Total') }}:</span>
                <span>{{ formatAmount($sales_return->grand_total, currency_symbol($sales_return->business->currency), $sales_return->business_id) }}</span>
            </div>
        </div>

        @if($sales_return->transactions->count() > 0)
        <!-- <table class="table border mt-4">
            <thead>
                <th>{{ _lang('Date') }}</th>
                <th>{{ _lang('Method') }}</th>
                <th class="text-right">{{ _lang('Amount') }}</th>
                <th class="text-right">{{ _lang('Invoice Amount') }}</th>
            </thead>
            <tbody>
                @foreach($sales_return->transactions as $transaction)
                <tr>
                    <td>{{ $transaction->trans_date }}</td>
                    <td>{{ $transaction->method }}</td>
                    <td class="text-right">{{ formatAmount($transaction->amount, currency_symbol($transaction->account->currency)) }}</td>
                    <td class="text-right">{{ formatAmount($transaction->ref_amount, currency_symbol($sales_return->business->currency)) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table> -->
        @endif
    </div>
</div>

<x-modal>
    <form method="post">
        <div class="ti-modal-header">
            <h3 class="ti-modal-title">
                Delete Modal
            </h3>
            <button type="button" class="hs-dropdown-toggle ti-modal-close-btn" data-hs-overlay="#delete-modal">
                <span class="sr-only">Close</span>
                <i class="ri-close-line text-xl"></i>
            </button>
        </div>
        <div class="ti-modal-body">
            {{ csrf_field() }}

            <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                {{ __('Are you sure you want to delete the sales return?') }}
            </h2>

            <input name="_method" type="hidden" value="DELETE">
        </div>
        <div class="ti-modal-footer">
            <x-secondary-button data-hs-overlay="#delete-modal">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ml-3 submit-btn" type="submit">
                {{ __('Delete Sales Return') }}
            </x-danger-button>
        </div>
    </form>
</x-modal>

<script>
    $(document).on('click', '#delete', function() {
        var id = $(this).data('id');
        $('#delete-modal form').attr('action', '/user/sales_returns/' + id);
    });
</script>
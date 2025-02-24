<div class="box">
    <div class="box-header flex items-center justify-between">
        <h5>{{ _lang('Purchase Order') }} #{{ $purchase->bill_no }} </h5>
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
                        <a href="#" class="ti-dropdown-item print" data-print="invoice"><i class="fas fa-print mr-2"></i>{{ _lang('Print Invoice') }}</a>
                        <hr>
                        @if($purchase->status != 2)
                        <a href="{{ route('bill_invoices.edit', $purchase->id) }}" class="ti-dropdown-item"><i class="far fa-edit mr-2"></i>{{ _lang('Edit') }}</a>
                        @endif
                        <a class="w-full ti-dropdown-item" href="javascript:void(0);" data-hs-overlay="#delete-modal" data-id="{{ $purchase['id'] }}" id="delete"><i class="far fa-trash-alt mr-2"></i>{{ _lang('Delete') }}</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="box-body">
        <div class="flex justify-between border border-secondary rounded-md p-3">
            <div>
                <span>{{ _lang('Status') }}:</span>
                <span>
                    @if($purchase->status == 0 && $purchase->due_date < date('Y-m-d'))
                        <span class="text-red">{{ _lang('Overdue') }}</span>
                @elseif($purchase->status == 0 && $purchase->due_date >= date('Y-m-d'))
                <span class="text-secondary">{{ _lang('Unpaid') }}</span>
                @elseif($purchase->status == 1)
                <span class="text-success">{{ _lang('Partially Paid') }}</span>
                @elseif($purchase->status == 2)
                <span class="text-danger">{{ _lang('Paid') }}</span>
                @endif
                </span>
            </div>
            <div>
                <span>{{ _lang('Vendor') }}:</span>
                <span>{{ $purchase->vendor->name }}</span>
            </div>
            <div>
                <span>{{ _lang('Grand Total') }}:</span>
                <span>{{ formatAmount($purchase->grand_total, currency_symbol($purchase->business->currency), $purchase->business_id) }}</span>
            </div>
            <div>
                <span>{{ _lang('Amount Due') }}:</span>
                <span>{{ formatAmount($purchase->grand_total - $purchase->paid, currency_symbol($purchase->business->currency), $purchase->business_id) }}</span>
            </div>
        </div>
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
                {{ __('Are you sure you want to delete the purchase?') }}
            </h2>

            <input name="_method" type="hidden" value="DELETE">
        </div>
        <div class="ti-modal-footer">
            <x-secondary-button data-hs-overlay="#delete-modal">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ml-3 submit-btn" type="submit">
                {{ __('Delete Purchase') }}
            </x-danger-button>
        </div>
    </form>
</x-modal>

<script>
    $(document).on('click', '#delete', function() {
        var id = $(this).data('id');
        $('#delete-modal form').attr('action', '/user/purchases/' + id);
    });
</script>
<div class="box">
    <div class="box-header flex items-center justify-between">
        <span class="panel-title">{{ _lang('Sales Receipt') }} #{{ $receipt->receipt_number }} </span>
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
                        @if($receipt->customer_id != null)
                        <a href="{{ route('receipts.send_email', $receipt->id) }}" class="ti-dropdown-item ajax-modal" data-hs-overlay="#modal">
                            <i class="ri-send-plane-line text-lg"></i>
                            {{ _lang('Send Email') }}
                        </a>
                        @endif
                        <hr>
                        <a href="#" class="ti-dropdown-item print" data-print="invoice">
                            <i class="ri-printer-line text-lg"></i>
                            {{ _lang('Print Receipt') }}
                        </a>
                        <a href="{{ route('receipts.export_pdf', $receipt->id) }}" class="ti-dropdown-item">
                            <i class="ri-file-pdf-line text-lg"></i>
                            {{ _lang('Export PDF') }}
                        </a>
                        <hr>
                        @if($receipt->status != 2)
                        <a href="{{ route('receipts.edit', $receipt->id) }}" class="ti-dropdown-item">
                            <i class="ri-file-edit-line text-lg"></i>
                            {{ _lang('Edit') }}
                        </a>
                        @endif
                        <a class="w-full ti-dropdown-item" href="javascript:void(0);" data-hs-overlay="#delete-modal" data-id="{{ $receipt['id'] }}" id="delete">
                            <i class="ri-delete-bin-line text-lg"></i>
                            {{ _lang('Delete') }}
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="box-body">
        @if($receipt->customer_id != null)
        <div class="md:flex justify-between border border-secondary rounded-md p-3 mb-3">
            <div>
                <span>{{ _lang('Customer') }}:</span>
                <span>{{ $receipt->customer->name }}</span>
            </div>
            <div>
                <span>{{ _lang('Grand Total') }}:</span>
                <span>{{ formatAmount($receipt->grand_total, currency_symbol($receipt->business->currency), $receipt->business_id) }}</span>
            </div>
        </div>
        @endif

        @if($receipt->customer_id != null)
        <div class="md:flex justify-between items-center border border-secondary rounded-md p-3">
            <div>
                <span>{{ _lang('Email Status') }}:</span>
                <span>{!! $receipt->email_send == 1 ? xss_clean(show_status(_lang('Sent'), 'success')) : xss_clean(show_status(_lang('Not Sent'), 'danger')) !!}</span>
            </div>
            <div>
                @if($receipt->email_send == 1)
                <span>{{ _lang('Sent At') }}:</span>
                <span>{{ $receipt->email_send_at }}</span>
                @else

                <a href="{{ route('receipts.send_email', $receipt->id) }}" class="ajax-modal" data-hs-overlay="#modal">
                    <x-primary-button>
                        <i class="ri-send-plane-line"></i>
                        {{ _lang('Send Email') }}
                    </x-primary-button>
                </a>
                @endif
            </div>
        </div>
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
                {{ __('Are you sure you want to delete the invoice?') }}
            </h2>

            <input name="_method" type="hidden" value="DELETE">
        </div>
        <div class="ti-modal-footer">
            <x-secondary-button data-hs-overlay="#delete-modal">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ml-3 submit-btn" type="submit">
                {{ __('Delete Invoice') }}
            </x-danger-button>
        </div>
    </form>
</x-modal>

<script>
    $(document).on('click', '#delete', function() {
        var id = $(this).data('id');
        $('#delete-modal form').attr('action', '/user/receipts/' + id);
    });
</script>
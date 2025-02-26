@if($invoice->is_recurring == 0)
<div class="box">
    <div class="box-header flex items-center justify-between">
        <span class="panel-title">{{ _lang('Invoice') }} #{{ $invoice->invoice_number }} </span>
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
                        <a href="{{ route('invoices.send_email', $invoice->id) }}" class="ti-dropdown-item ajax-modal" data-hs-overlay="#modal">
                            <i class="ri-send-plane-line text-lg"></i>
                            {{ _lang('Send Email') }}
                        </a>
                        <hr>
                        <a href="#" class="ti-dropdown-item print" data-print="invoice">
                            <i class="ri-printer-line text-lg"></i>
                            {{ _lang('Print Invoice') }}
                        </a>
                        <a href="{{ route('invoices.export_pdf', $invoice->id) }}" class="ti-dropdown-item">
                            <i class="ri-file-pdf-line text-lg"></i>
                            {{ _lang('Export PDF') }}
                        </a>
                        <a href="{{ route('invoices.get_invoice_link', $invoice->id) }}" class="ti-dropdown-item ajax-modal" data-hs-overlay="#modal">
                            <i class="ri-share-line text-lg"></i>
                            {{ _lang('Share Invoice') }}
                        </a>
                        <hr>
                        @if($invoice->is_deffered != 1)
                        @if($invoice->status != 2)
                        <a href="{{ route('invoices.edit', $invoice->id) }}" class="ti-dropdown-item">
                            <i class="ri-file-edit-line text-lg"></i>
                            {{ _lang('Edit') }}
                        </a>
                        @endif
                        @else
                        <a href="{{ route('deffered_invoices.edit', $invoice->id) }}" class="ti-dropdown-item">
                            <i class="ri-file-edit-line text-lg"></i>
                            {{ _lang('Edit') }}
                        </a>
                        @endif
                        <a class="w-full ti-dropdown-item" href="javascript:void(0);" data-hs-overlay="#delete-modal" data-id="{{ $invoice['id'] }}" id="delete">
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
                <span>{{ _lang('Status') }}:</span>
                <span>{!! xss_clean(invoice_status($invoice)) !!}</span>
            </div>
            <div>
                <span>{{ _lang('Customer') }}:</span>
                <span>{{ $invoice->customer->name }}</span>
            </div>
            <div>
                <span>{{ _lang('Grand Total') }}:</span>
                <span>{{ formatAmount($invoice->grand_total, currency_symbol($invoice->business->currency), $invoice->business_id) }}</span>
            </div>
            <div>
                <span>{{ _lang('Amount Due') }}:</span>
                <span>{{ formatAmount($invoice->grand_total - $invoice->paid, currency_symbol($invoice->business->currency), $invoice->business_id) }}</span>
            </div>
        </div>

        <div class="md:flex justify-between items-center border border-secondary rounded-md p-3">
            <div>
                <span>{{ _lang('Email Status') }}:</span>
                <span>{!! $invoice->email_send == 1 ? xss_clean(show_status(_lang('Sent'), 'success')) : xss_clean(show_status(_lang('Not Sent'), 'danger')) !!}</span>
            </div>
            <div>
                @if($invoice->email_send == 1)
                <span>{{ _lang('Sent At') }}:</span>
                <span>{{ $invoice->email_send_at }}</span>
                @else

                <a href="{{ route('invoices.send_email', $invoice->id) }}" class="ajax-modal" data-hs-overlay="#modal">
                    <x-primary-button>
                        <i class="ri-send-plane-line"></i>
                        {{ _lang('Send Email') }}
                    </x-primary-button>
                </a>
                @endif
            </div>
        </div>

        <!-- created by -->
        <h1 class="font-semibold text-lg mt-2">Created by {{ $invoice->createdBy->name }} on {{ $invoice->created_at->format('d M, Y') }}</h1>
        <!-- updated by -->
        @if($invoice->updated_by != null)
        <h1 class="font-semibold text-lg mt-2">Last updated by {{ $invoice->updatedBy->name }} on {{ $invoice->updated_at->format('d M, Y') }}</h1>
        @endif

        @if($invoice->status == 0)
        <div class="bg-warning/10 border border-warning/10 alert text-warning flex items-center justify-between mt-4" role="alert">
            <span><strong><i class="ri-information-line mr-2"></i>{{ _lang('You need to approve this draft invoice before further action.') }}</strong></span>
            <a href="{{ route('invoices.approve', $invoice->id) }}">
                <x-secondary-button>
                    <i class="ri-checkbox-circle-line mr-2"></i>
                    {{ _lang('Approve') }}
                </x-secondary-button>
            </a>
        </div>
        @endif

        @if($invoice->transactions->count() > 0)
        <!-- <table class="table border mt-4">
            <thead>
                <th>{{ _lang('Date') }}</th>
                <th>{{ _lang('Method') }}</th>
                <th class="text-right">{{ _lang('Amount') }}</th>
                <th class="text-right">{{ _lang('Invoice Amount') }}</th>
            </thead>
            <tbody>
                @foreach($invoice->transactions as $transaction)
                <tr>
                    <td>{{ $transaction->trans_date }}</td>
                    <td>{{ $transaction->method }}</td>
                    <td class="text-right">{{ formatAmount($transaction->amount, currency_symbol($transaction->account->currency)) }}</td>
                    <td class="text-right">{{ formatAmount($transaction->ref_amount, currency_symbol($invoice->business->currency)) }}</td>
                </tr>
                @endforeach
            </tbody>
        </table> -->
        @endif
    </div>
</div>


@elseif($invoice->is_recurring == 1)
<div class="box">
    <div class="box-header d-flex align-items-center justify-content-between">
        <span class="panel-title">{{ _lang('Recurring invoice') }}</span>
        <div>
            <div class="dropdown">
                <button class="btn btn-outline-primary btn-xs dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-cog mr-1"></i>{{ _lang('Actions') }}
                </button>
                <div class="dropdown-menu">
                    <a href="{{ route('recurring_invoices.duplicate', $invoice->id) }}" class="dropdown-item"><i class="far fa-copy mr-2"></i>{{ _lang('Duplicate') }}</a>
                    <a href="{{ route('recurring_invoices.end_recurring', $invoice->id) }}" class="dropdown-item"><i class="far fa-stop-circle mr-2"></i>{{ _lang('End Recurring') }}</a>
                    <div class="dropdown-divider"></div>
                    <a href="{{ route('recurring_invoices.edit', $invoice->id) }}" class="dropdown-item"><i class="far fa-edit mr-2"></i>{{ _lang('Edit') }}</a>
                    <form action="{{ route('recurring_invoices.destroy', $invoice->id) }}" method="post">
                        @csrf
                        <input name="_method" type="hidden" value="DELETE">
                        <button class="dropdown-item btn-remove" type="submit"><i class="far fa-trash-alt mr-2"></i>{{ _lang('Delete') }}</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="box-body">
        <div class="d-md-flex justify-content-between border border-secondary rounded p-3">
            <div>
                <span>{{ _lang('Status') }}:</span>
                <span>{!! xss_clean(recurring_invoice_status($invoice->status)) !!}</span>
            </div>
            <div>
                <span>{{ _lang('Customer') }}:</span>
                <span>{{ $invoice->customer->name }}</span>
            </div>
            <div>
                <span>{{ _lang('Recurring') }}:</span>
                <span class="text-primary"><b>{{ _lang('Every') . ' ' . $invoice->recurring_value . ' ' . $invoice->recurring_type }}</b></span>
            </div>
            <div>
                <span>{{ _lang('Grand Total') }}:</span>
                <span>{{ formatAmount($invoice->grand_total, currency_symbol($invoice->business->currency), $invoice->business_id) }}</span>
            </div>
        </div>

        @if($invoice->status == 0)
        <div class="alert alert-warning d-flex align-items-center justify-content-between mt-4">
            <span><strong><i class="ti-info-alt mr-2"></i>{{ _lang('You need to approve this draft invoice before further action.') }}</strong></span>
            <a href="{{ route('recurring_invoices.approve', $invoice->id) }}" class="btn btn-primary btn-xs"><i class="fas fa-check-circle mr-2"></i>{{ _lang('Approve') }}</a>
        </div>
        @endif
    </div>
</div>
@endif

<div id="modal" class="hs-overlay hidden ti-modal">
    <div class="hs-overlay-open:mt-7 ti-modal-box mt-0 ease-out lg:!max-w-4xl lg:w-full m-3 lg:!mx-auto">
        <div class="ti-modal-content">
            <div class="ti-modal-body hidden" id="modal_spinner">
                <div class="text-center spinner">
                    <div class="ti-spinner text-primary" role="status" aria-label="loading"> <span class="sr-only">Loading...</span> </div>
                </div>
            </div>
            <div id="main-modal">

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
        @if($invoice->is_deffered == 1)
        $('#delete-modal form').attr('action', '/user/deffered_invoices/' + id);
        @else
        $('#delete-modal form').attr('action', '/user/invoices/' + id);
        @endif
    });
</script>
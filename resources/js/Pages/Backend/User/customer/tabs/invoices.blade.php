<div class="box">
    <div class="box-header">
        <h5>{{ _lang('Invoices') }}</h5>
    </div>
    <div class="box-body">
        <div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
            <table id="datatable" class="ti-custom-table ti-custom-table-head whitespace-nowrap">
                <thead>
                    <tr>
                        <th>{{ _lang('Date') }}</th>
                        <th>{{ _lang('Due Date') }}</th>
                        <th>{{ _lang('Invoice Number') }}</th>
                        <th class="text-right">{{ _lang('Grand Total') }}</th>
                        <th class="text-right">{{ _lang('Amount Due') }}</th>
                        <th class="text-center">{{ _lang('Status') }}</th>
                        <th class="text-center">{{ _lang('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoices as $invoice)
                    <tr>
                        <td>{{ $invoice->invoice_date }}</td>
                        <td>{{ $invoice->due_date }}</td>
                        <td>{{ $invoice->invoice_number }}</td>
                        <td class="text-right">{{ formatAmount($invoice->grand_total, currency_symbol(request()->activeBusiness->currency)) }}</td>
                        <td class="text-right">{{ formatAmount($invoice->grand_total - $invoice->paid, currency_symbol(request()->activeBusiness->currency)) }}</td>
                        <td class="text-center">{!! xss_clean(invoice_status($invoice)) !!}</td>
                        <td class="text-center">
                            <a class="flex items-center" href="{{ route('invoices.show', $invoice['id']) }}">
                                <i class="ri-eye-line mr-1"></i>
                                {{ _lang('Preview') }}
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
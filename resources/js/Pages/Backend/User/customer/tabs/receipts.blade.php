<div class="box">
    <div class="box-header">
        <h5>{{ _lang('Cash Invoices') }}</h5>
    </div>
    <div class="box-body">
        <div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
            <table id="datatable" class="ti-custom-table ti-custom-table-head whitespace-nowrap">
                <thead>
                    <tr>
                        <th>{{ _lang('Date') }}</th>
                        <th>{{ _lang('Invoice Number') }}</th>
                        <th class="text-right">{{ _lang('Grand Total') }}</th>
                        <th class="text-center">{{ _lang('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($receipts as $receipt)
                    <tr>
                        <td>{{ $receipt->receipt_date }}</td>
                        <td>{{ $receipt->receipt_number }}</td>
                        <td class="text-right">{{ formatAmount($receipt->grand_total, currency_symbol(request()->activeBusiness->currency)) }}</td>
                        <td>
                            <a class="flex items-center" href="{{ route('receipts.show', $receipt['id']) }}">
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
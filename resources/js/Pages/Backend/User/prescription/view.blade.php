<x-app-layout>
    <!-- Start::main-content -->
    <div class="main-content">
        <x-page-header title="Prescription" page="user" subpage="create" />

        <div class="box">
            <div class="box-header flex items-center justify-between">
                <h5>{{ _lang('Prescription') }}</h5>
                <x-secondary-button class="print" data-print="report" type="button" id="report-print-btn">
                    <i class="ri-printer-line mr-1"></i>
                    {{ _lang('Print Record') }}
                </x-secondary-button>
            </div>
            <div class="box-body">
                <div class="grid grid-cols-12 gap-x-2">
                    <div class="col-span-12" id="report">
                        <div class="flex items-center justify-between">
                            <!-- logo -->
                            <img class="w-28" src="{{ asset('/uploads/media/' . request()->activeBusiness->logo) }}">
                            <div>
                                <h2 class="text-center text-lg font-semibold">{{ request()->activeBusiness->name }}</h2>
                                <h2 class="underline pb-2 text-center text-lg font-semibold">Patient Prescription</h2>
                            </div>
                            <div></div>
                        </div>
                        <div class="table-bordered rounded-sm ti-custom-table-head overflow-auto mt-5">
                            <table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
                                <tr>
                                    <td>name</td>
                                    <td>{{ $prescription->customer->name }}</td>
                                </tr>
                                <tr>
                                    <td>Age</td>
                                    <td>{{ $prescription->customer->age }}</td>
                                </tr>
                                <tr>
                                    <td>Phone</td>
                                    <td>{{ $prescription->customer->mobile }}</td>
                                </tr>
                                <tr>
                                    <td>Date</td>
                                    <td>{{ $prescription->date }}</td>
                                </tr>
                                <tr>
                                    <td>Result Date</td>
                                    <td>{{ $prescription->result_date }}</td>
                                </tr>
                            </table>
                        </div>
                        <div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
                            <table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
                                <thead>
                                    <tr>
                                        <th>RX</th>
                                        <th colspan="4">Right Eye</th>
                                        <th colspan="4">Left Eye</th>
                                    </tr>
                                    <tr>
                                        <th></th>
                                        <th>Sph</th>
                                        <th>Cyl</th>
                                        <th>Axis</th>
                                        <th>VA</th>
                                        <th>Sph</th>
                                        <th>Cyl</th>
                                        <th>Axis</th>
                                        <th>VA</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>Dist</td>
                                        <td>
                                            {{ $prescription->dist_sph_re }}
                                        </td>
                                        <td>
                                            {{ $prescription->dist_cyl_re }}
                                        </td>
                                        <td>
                                            {{ $prescription->dist_axis_re }}
                                        </td>
                                        <td>
                                            {{ $prescription->dist_va_re }}
                                        </td>
                                        <td>
                                            {{ $prescription->dist_sph_le }}
                                        </td>
                                        <td>
                                            {{ $prescription->dist_cyl_le }}
                                        </td>
                                        <td>
                                            {{ $prescription->dist_axis_le }}
                                        </td>
                                        <td>
                                            {{ $prescription->dist_va_le }}
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Near</td>
                                        <td>
                                            {{ $prescription->near_sph_re }}
                                        </td>
                                        <td>
                                            {{ $prescription->near_cyl_re }}
                                        </td>
                                        <td>
                                            {{ $prescription->near_axis_re }}
                                        </td>
                                        <td>
                                            {{ $prescription->near_va_re }}
                                        </td>
                                        <td>
                                            {{ $prescription->near_sph_le }}
                                        </td>
                                        <td>
                                            {{ $prescription->near_cyl_le }}
                                        </td>
                                        <td>
                                            {{ $prescription->near_axis_le }}
                                        </td>
                                        <td>
                                            {{ $prescription->near_va_le }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
                            <table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
                                <tr>
                                    <td>IPD</td>
                                    <td colspan="3">
                                        {{ $prescription->ipd }}
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="flex items-center">
                                            @if($prescription->glasses == 1)
                                            <i class="ri-checkbox-line text-lg"></i>
                                            @else
                                            <i class="ri-checkbox-blank-line text-lg"></i>
                                            @endif
                                            <label for="glasses" class="ml-2">{{ _lang('Glasses') }}</label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center">
                                            @if($prescription->photochromatic_lenses == 1)
                                            <i class="ri-checkbox-line text-lg"></i>
                                            @else
                                            <i class="ri-checkbox-blank-line text-lg"></i>
                                            @endif
                                            <label for="photochromatic_lenses" class="ml-2">{{ _lang('Photochromatic Lenses') }}</label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center">
                                            @if($prescription->photochromatic_lenses == 1)
                                            <i class="ri-checkbox-line text-lg"></i>
                                            @else
                                            <i class="ri-checkbox-blank-line text-lg"></i>
                                            @endif
                                            <label for="high_index_lenses" class="ml-2">{{ _lang('High Index Lenses') }}</label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="flex items-center">
                                            @if($prescription->polycarbonate == 1)
                                            <i class="ri-checkbox-line text-lg"></i>
                                            @else
                                            <i class="ri-checkbox-blank-line text-lg"></i>
                                            @endif
                                            <label for="polycarbonate" class="ml-2">{{ _lang('Polycarbonate') }}</label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center">
                                            @if($prescription->contact_lenses == 1)
                                            <i class="ri-checkbox-line text-lg"></i>
                                            @else
                                            <i class="ri-checkbox-blank-line text-lg"></i>
                                            @endif
                                            <label for="contact_lenses" class="ml-2">{{ _lang('Contact Lenses') }}</label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center">
                                            @if($prescription->bi_focal_lenses == 1)
                                            <i class="ri-checkbox-line text-lg"></i>
                                            @else
                                            <i class="ri-checkbox-blank-line text-lg"></i>
                                            @endif
                                            <label for="bi_focal_lenses" class="ml-2">{{ _lang('Bi Focal Lenses') }}</label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="flex items-center">
                                            @if($prescription->progressive_lenses == 1)
                                            <i class="ri-checkbox-line text-lg"></i>
                                            @else
                                            <i class="ri-checkbox-blank-line text-lg"></i>
                                            @endif
                                            <label for="progressive_lenses" class="ml-2">{{ _lang('Progressive Lenses') }}</label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center">
                                            @if($prescription->anti_reflection_coating == 1)
                                            <i class="ri-checkbox-line text-lg"></i>
                                            @else
                                            <i class="ri-checkbox-blank-line text-lg"></i>
                                            @endif
                                            <label for="anti_reflection_coating" class="ml-2">{{ _lang('Anti Reflection Coating') }}</label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center">
                                            @if($prescription->single_vision == 1)
                                            <i class="ri-checkbox-line text-lg"></i>
                                            @else
                                            <i class="ri-checkbox-blank-line text-lg"></i>
                                            @endif
                                            <label for="single_vision" class="ml-2">{{ _lang('Single Vision') }}</label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <div class="flex items-center">
                                            @if($prescription->plastic == 1)
                                            <i class="ri-checkbox-line text-lg"></i>
                                            @else
                                            <i class="ri-checkbox-blank-line text-lg"></i>
                                            @endif
                                            <label for="plastic" class="ml-2">{{ _lang('Plastic') }}</label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center">
                                            @if($prescription->white_lenses == 1)
                                            <i class="ri-checkbox-line text-lg"></i>
                                            @else
                                            <i class="ri-checkbox-blank-line text-lg"></i>
                                            @endif
                                            <label for="white_lenses" class="ml-2">{{ _lang('White Lenses') }}</label>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="flex items-center">
                                            @if($prescription->blue_cut == 1)
                                            <i class="ri-checkbox-line text-lg"></i>
                                            @else
                                            <i class="ri-checkbox-blank-line text-lg"></i>
                                            @endif
                                            <label for="blue_cut" class="ml-2">{{ _lang('Blue Cut') }}</label>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Description</td>
                                    <td colspan="3">
                                        {{ $prescription->description }}
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    <div class="col-span-12">
                        <div class="mt-6">
                            <!-- todays invoices for this customer -->
                            @if($invoices->count() > 0)
                            <div class="box">
                                <div class="box-header flex items-center justify-between">
                                    <h5>{{ _lang('Credit Invoices') }}</h5>
                                </div>
                                <div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
                                    <table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
                                        <thead>
                                            <tr>
                                                <th>{{ _lang('Date') }}</th>
                                                <th>{{ _lang('Invoice Number') }}</th>
                                                <th>{{ _lang('Total') }}</th>
                                                <th>{{ _lang('Status') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($invoices as $invoice)
                                            <tr class="bg-green-100">
                                                <td>{{ $invoice->invoice_date }}</td>
                                                <td>{{ $invoice->invoice_number }}</td>
                                                <td>{{ formatAmount($invoice->grand_total, currency_symbol(request()->activeBusiness->currency), $invoice->business_id) }}</td>
                                                <td>
                                                    {!! xss_clean(invoice_status($invoice)) !!}
                                                </td>
                                            </tr>
                                            <!-- invoice items -->
                                            <tr>
                                                <td colspan="5">
                                                    <table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
                                                        <thead>
                                                            <tr>
                                                                <th>{{ _lang('Product') }}</th>
                                                                <th>{{ _lang('Quantity') }}</th>
                                                                <th>{{ _lang('Unit Cost') }}</th>
                                                                <th>{{ _lang('Sub Total') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($invoice->items as $item)
                                                            <tr>
                                                                <td>{{ $item->product->name }}</td>
                                                                <td>{{ $item->quantity }}</td>
                                                                <td>{{ formatAmount($item->unit_cost, currency_symbol(request()->activeBusiness->currency), $invoice->business_id) }}</td>
                                                                <td>{{ formatAmount($item->sub_total, currency_symbol(request()->activeBusiness->currency), $invoice->business_id) }}</td>
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endif

                            <!-- todays receipts for customer -->
                            @if($receipts->count() > 0)
                            <div class="box">
                                <div class="box-header flex items-center justify-between">
                                    <h5>{{ _lang('Cash Invoices') }}</h5>
                                </div>
                                <div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
                                    <table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
                                        <thead>
                                            <tr>
                                                <th>{{ _lang('Date') }}</th>
                                                <th>{{ _lang('Invoice Number') }}</th>
                                                <th>{{ _lang('Total') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($receipts as $receipt)
                                            <tr class="bg-green-100">
                                                <td>{{ $receipt->receipt_date }}</td>
                                                <td>{{ $receipt->receipt_number }}</td>
                                                <td>{{ formatAmount($receipt->grand_total, currency_symbol(request()->activeBusiness->currency), $receipt->business_id) }}</td>
                                            </tr>
                                            <tr>
                                                <td colspan="5">
                                                    <table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
                                                        <thead>
                                                            <tr>
                                                                <th>{{ _lang('Product') }}</th>
                                                                <th>{{ _lang('Quantity') }}</th>
                                                                <th>{{ _lang('Unit Cost') }}</th>
                                                                <th>{{ _lang('Sub Total') }}</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach($receipt->items as $item)
                                                            <tr>
                                                                <td>{{ $item->product->name }}</td>
                                                                <td>{{ $item->quantity }}</td>
                                                                <td>{{ formatAmount($item->unit_cost, currency_symbol(request()->activeBusiness->currency), $receipt->business_id) }}</td>
                                                                <td>{{ formatAmount($item->sub_total, currency_symbol(request()->activeBusiness->currency), $receipt->business_id) }}</td>
                                                            </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
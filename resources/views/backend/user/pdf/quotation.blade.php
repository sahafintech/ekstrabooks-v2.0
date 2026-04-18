@extends('backend.user.pdf.layout')

@php
    $primaryColor = get_business_option('invoice_primary_color', '#6d0e47');
    $textColor = get_business_option('invoice_text_color', '#ffffff');
    $businessName = $quotation->business->business_name ?? $quotation->business->name ?? 'Business';
    $businessEmail = $quotation->business->business_email ?? $quotation->business->email ?? '';
    $quantityLabel = $quotation->is_deffered && $quotation->invoice_category === 'medical' ? 'Members' : 'Quantity';
    $coverageSummary = $quotation->coverage_summary ?: $quotation->note;
    $exclusionsRemarks = $quotation->exclusions_remarks ?: $quotation->footer;
    $quoteToRows = [
        ['label' => 'Quote To', 'value' => $quotation->customer->name ?? '-'],
        ['label' => 'Quotation Type', 'value' => $quotation->is_deffered ? 'Deferred' : 'Normal'],
        ['label' => $quotation->is_deffered ? 'Policy Number' : 'Order Number', 'value' => $quotation->po_so_number ?: '-'],
    ];

    if ($quotation->is_deffered && $quotation->invoice_category) {
        $quoteToRows[] = ['label' => 'Category', 'value' => strtoupper($quotation->invoice_category)];
    }

    $quoteToRows[] = ['label' => 'Valid Until', 'value' => $quotation->expired_date ?: '-'];
    $medicalCoverageSections = [
        'inpatient' => 'Inpatient',
        'maternity' => 'Maternity',
        'outpatient' => 'Outpatient',
        'dental' => 'Dental',
        'optical' => 'Optical',
        'telemedicine' => 'Telemedicine',
    ];
    $medicalTableColumnWidths = array_merge(['5%', '6%', '8%'], array_fill(0, count($medicalCoverageSections) * 2, '6%'), ['9%']);
@endphp

@push('styles')
<style>
    @media print {
        body {
            margin: 0;
            padding: 0;
            background: white;
        }

        @page {
            size: A4 {{ $isMedicalDeferredQuotation ? 'landscape' : 'portrait' }};
            margin: 8mm;
        }
    }
</style>
@endpush

@section('content')
<div class="w-full bg-white">
    <div class="{{ $isMedicalDeferredQuotation ? 'border-[8px] p-2' : 'border-[10px] p-3' }}" style="border-color: {{ $primaryColor }};">
        <div class="mb-4 h-4" style="background-color: {{ $primaryColor }};"></div>

        <div class="grid grid-cols-12 {{ $isMedicalDeferredQuotation ? 'gap-4' : 'gap-6' }}">
            <div class="col-span-7">
                <div class="mb-6 flex items-start justify-between gap-4">
                    <div class="max-w-[260px]">
                        @if($quotation->business->logo)
                            <img
                                src="{{ public_path('/uploads/media/' . $quotation->business->logo) }}"
                                alt="Business Logo"
                                class="max-h-24 object-contain"
                            />
                        @else
                            <div class="text-3xl font-bold uppercase" style="color: {{ $primaryColor }};">
                                {{ $businessName }}
                            </div>
                        @endif
                    </div>
                    <div class="shrink-0">
                        {!! QrCode::size(100)->errorCorrection('H')->margin(10)->generate(route('quotations.show_public_quotation', $quotation->short_code)) !!}
                    </div>
                </div>

                <table class="w-full text-sm">
                    <tbody>
                        @foreach($quoteToRows as $row)
                            <tr>
                                <td class="w-40 py-0.5 font-semibold text-slate-900">{{ $row['label'] }}:</td>
                                <td class="py-0.5 text-slate-700">{{ $row['value'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="col-span-5">
                <div class="border border-slate-900">
                    <div class="border-b-2 px-4 py-2 text-center text-2xl font-bold uppercase" style="color: {{ $primaryColor }}; border-color: {{ $primaryColor }};">
                        Quotation
                    </div>
                    <div class="px-4 py-2">
                        <div class="flex items-start justify-between gap-4 py-0.5 text-sm">
                            <span class="font-semibold text-slate-900">Quotation Date:</span>
                            <span class="text-slate-700">{{ $quotation->quotation_date }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-4 py-0.5 text-sm">
                            <span class="font-semibold text-slate-900">Currency Type:</span>
                            <span class="text-slate-700">{{ $quotation->currency }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-4 py-0.5 text-sm">
                            <span class="font-semibold text-slate-900">Quotation No:</span>
                            <span class="text-slate-700">{{ $quotation->quotation_number }}</span>
                        </div>
                        <div class="flex items-start justify-between gap-4 py-0.5 text-sm">
                            <span class="font-semibold text-slate-900">Quotation Validity:</span>
                            <span class="text-slate-700">{{ $quotation->expired_date }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="mt-5 border border-slate-900">
            <div class="border-b border-slate-900 px-2 py-1 text-xs font-bold uppercase" style="background-color: {{ $primaryColor }}; color: {{ $textColor }}; letter-spacing: 0.12em;">
                {{ $quotation->invoice_category ? strtoupper($quotation->invoice_category) . ' Quote' : 'Quotation Items' }}
            </div>

            @if($isMedicalDeferredQuotation)
                <table class="w-full border-collapse table-fixed" style="font-size: 7px; line-height: 1.2;">
                    <colgroup>
                        @foreach($medicalTableColumnWidths as $columnWidth)
                            <col style="width: {{ $columnWidth }};">
                        @endforeach
                    </colgroup>
                    <thead>
                        <tr>
                            <th class="border border-slate-900 px-1 py-0.5" colspan="3"></th>
                            @foreach($medicalCoverageSections as $sectionKey => $sectionLabel)
                                <th class="border border-slate-900 px-1 py-0.5 text-center font-bold uppercase" colspan="2" style="background-color: {{ $primaryColor }}; color: {{ $textColor }};">
                                    {{ $sectionLabel }}
                                </th>
                            @endforeach
                            <th class="border border-slate-900 px-1 py-0.5 text-center font-bold uppercase" style="background-color: {{ $primaryColor }}; color: {{ $textColor }};">
                                Total
                            </th>
                        </tr>
                        <tr style="background-color: #f8fafc;">
                            <th class="border border-slate-900 px-1 py-0.5" colspan="3"></th>
                            @foreach($medicalCoverageSections as $sectionKey => $sectionLabel)
                                <th class="border border-slate-900 px-1 py-0.5 text-center font-semibold" colspan="2">
                                    Limit: {{ $formatPlainNumber($getMedicalSectionLimit($sectionKey)) }} / Family
                                </th>
                            @endforeach
                            <th class="border border-slate-900 px-1 py-0.5"></th>
                        </tr>
                        <tr style="background-color: #f1f5f9;">
                            <th class="border border-slate-900 px-1 py-0.5 text-left font-semibold uppercase">Family Size</th>
                            <th class="border border-slate-900 px-1 py-0.5 text-right font-semibold uppercase">Staff</th>
                            <th class="border border-slate-900 px-1 py-0.5 text-right font-semibold uppercase">Staff + Family</th>
                            @foreach($medicalCoverageSections as $sectionKey => $sectionLabel)
                                <th class="border border-slate-900 px-1 py-0.5 text-right font-semibold uppercase">Contrib./Family</th>
                                <th class="border border-slate-900 px-1 py-0.5 text-right font-semibold uppercase">Total Contrib.</th>
                            @endforeach
                            <th class="border border-slate-900 px-1 py-0.5 text-right font-semibold uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($medicalQuotationItems as $item)
                            <tr>
                                <td class="border border-slate-900 px-1 py-1 font-semibold" style="white-space: nowrap;">{{ $item->family_size }}</td>
                                <td class="border border-slate-900 px-1 py-1 text-right" style="white-space: nowrap;">{{ $formatPlainNumber($item->quantity) }}</td>
                                <td class="border border-slate-900 px-1 py-1 text-right" style="white-space: nowrap;">{{ $formatPlainNumber($calculateMembersAndFamilyValue($item->quantity, $item->family_size)) }}</td>
                                @foreach($medicalCoverageSections as $sectionKey => $sectionLabel)
                                    <td class="border border-slate-900 px-1 py-1 text-right" style="white-space: nowrap;">{{ $formatPlainNumber($item->{"{$sectionKey}_contribution_per_family"}) }}</td>
                                    <td class="border border-slate-900 px-1 py-1 text-right" style="white-space: nowrap;">{{ $formatPlainNumber($item->{"{$sectionKey}_total_contribution"}) }}</td>
                                @endforeach
                                <td class="border border-slate-900 px-1 py-1 text-right font-semibold" style="white-space: nowrap;">{{ $formatPlainNumber($item->sub_total) }}</td>
                            </tr>
                        @endforeach
                        <tr style="background-color: #f8fafc;">
                            <td class="border border-slate-900 px-1 py-1 font-semibold">Total</td>
                            <td class="border border-slate-900 px-1 py-1 text-right font-semibold" style="white-space: nowrap;">{{ $formatPlainNumber($medicalTotalMembers) }}</td>
                            <td class="border border-slate-900 px-1 py-1 text-right font-semibold" style="white-space: nowrap;">{{ $formatPlainNumber($medicalTotalMembersAndFamily) }}</td>
                            @foreach($medicalCoverageSections as $sectionKey => $sectionLabel)
                                <td class="border border-slate-900 px-1 py-1"></td>
                                <td class="border border-slate-900 px-1 py-1 text-right font-semibold" style="white-space: nowrap;">{{ $formatPlainNumber($medicalSectionTotals[$sectionKey] ?? 0) }}</td>
                            @endforeach
                            <td class="border border-slate-900 px-1 py-1 text-right font-bold" style="white-space: nowrap;">{{ $formatPlainNumber($quotation->sub_total) }}</td>
                        </tr>
                        @foreach($medicalTaxAllocations as $tax)
                            <tr style="background-color: #fff1f2;">
                                <td class="border border-slate-900 px-1 py-1 font-semibold uppercase" colspan="3">{{ $tax['name'] }}</td>
                                @foreach($medicalCoverageSections as $sectionKey => $sectionLabel)
                                    <td class="border border-slate-900 px-1 py-1"></td>
                                    <td class="border border-slate-900 px-1 py-1 text-right" style="white-space: nowrap;">{{ $formatPlainNumber($tax['allocations'][$sectionKey] ?? 0) }}</td>
                                @endforeach
                                <td class="border border-slate-900 px-1 py-1 text-right font-semibold" style="white-space: nowrap;">{{ $formatPlainNumber($tax['amount']) }}</td>
                            </tr>
                        @endforeach
                        @if($quotation->discount > 0 && $medicalDiscountAllocations)
                            <tr style="background-color: #fffbeb;">
                                <td class="border border-slate-900 px-1 py-1 font-semibold uppercase" colspan="3">Discount</td>
                                @foreach($medicalCoverageSections as $sectionKey => $sectionLabel)
                                    <td class="border border-slate-900 px-1 py-1"></td>
                                    <td class="border border-slate-900 px-1 py-1 text-right" style="white-space: nowrap;">-{{ $formatPlainNumber($medicalDiscountAllocations[$sectionKey] ?? 0) }}</td>
                                @endforeach
                                <td class="border border-slate-900 px-1 py-1 text-right font-semibold" style="white-space: nowrap;">-{{ $formatPlainNumber($quotation->discount) }}</td>
                            </tr>
                        @endif
                        <tr style="background-color: #f1f5f9;">
                            <td class="border border-slate-900 px-1 py-1 font-bold uppercase" colspan="3">Gross Total</td>
                            @foreach($medicalCoverageSections as $sectionKey => $sectionLabel)
                                <td class="border border-slate-900 px-1 py-1"></td>
                                <td class="border border-slate-900 px-1 py-1 text-right font-bold" style="white-space: nowrap;">{{ $formatPlainNumber($medicalGrossTotals[$sectionKey] ?? 0) }}</td>
                            @endforeach
                            <td class="border border-slate-900 px-1 py-1 text-right font-bold" style="white-space: nowrap;">{{ $formatPlainNumber($quotation->grand_total) }}</td>
                        </tr>
                    </tbody>
                </table>
            @else
                <table class="w-full border-collapse text-xs">
                    <thead>
                        <tr>
                            <th class="border border-slate-900 px-2 py-1 text-left" style="background-color: {{ $primaryColor }}; color: {{ $textColor }};">Coverage</th>
                            <th class="border border-slate-900 px-2 py-1 text-left" style="background-color: {{ $primaryColor }}; color: {{ $textColor }};">Item</th>
                            <th class="border border-slate-900 px-2 py-1 text-left" style="background-color: {{ $primaryColor }}; color: {{ $textColor }};">Description</th>
                            @if($quotation->is_deffered && $quotation->invoice_category === 'medical')
                                <th class="border border-slate-900 px-2 py-1 text-left" style="background-color: {{ $primaryColor }}; color: {{ $textColor }};">Coverage Configuration</th>
                                <th class="border border-slate-900 px-2 py-1 text-left" style="background-color: {{ $primaryColor }}; color: {{ $textColor }};">Family Size</th>
                            @endif
                            @if($quotation->is_deffered && $quotation->invoice_category === 'other')
                                <th class="border border-slate-900 px-2 py-1 text-right" style="background-color: {{ $primaryColor }}; color: {{ $textColor }};">Sum Insured</th>
                            @endif
                            <th class="border border-slate-900 px-2 py-1 text-right" style="background-color: {{ $primaryColor }}; color: {{ $textColor }};">{{ $quantityLabel }}</th>
                            <th class="border border-slate-900 px-2 py-1 text-right" style="background-color: {{ $primaryColor }}; color: {{ $textColor }};">{{ $quotation->is_deffered ? 'Rate' : 'Unit Cost' }}</th>
                            <th class="border border-slate-900 px-2 py-1 text-right" style="background-color: {{ $primaryColor }}; color: {{ $textColor }};">Premium</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($quotation->items as $item)
                            <tr>
                                <td class="border border-slate-900 px-2 py-2 align-top">{{ $item->product_name }}</td>
                                <td class="border border-slate-900 px-2 py-2 align-top">{{ $item->product_name }}</td>
                                <td class="border border-slate-900 px-2 py-2 align-top">{{ $item->description }}</td>
                                @if($quotation->is_deffered && $quotation->invoice_category === 'medical')
                                    <td class="border border-slate-900 px-2 py-2 align-top">
                                        -
                                    </td>
                                    <td class="border border-slate-900 px-2 py-2 align-top">{{ $item->family_size }}</td>
                                @endif
                                @if($quotation->is_deffered && $quotation->invoice_category === 'other')
                                    <td class="border border-slate-900 px-2 py-2 text-right align-top">{{ formatCurrency($item->sum_insured) }}</td>
                                @endif
                                <td class="border border-slate-900 px-2 py-2 text-right align-top">{{ $item->quantity }}</td>
                                <td class="border border-slate-900 px-2 py-2 text-right align-top">{{ formatCurrency($item->unit_cost) }}</td>
                                <td class="border border-slate-900 px-2 py-2 text-right align-top">{{ formatCurrency($item->sub_total) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </div>

        <div class="mt-5 grid grid-cols-12 gap-0 border border-slate-900">
            <div class="{{ $isMedicalDeferredQuotation ? 'col-span-6' : 'col-span-4' }} border-r border-slate-900">
                <div class="px-2 py-1 text-xs font-bold uppercase" style="background-color: {{ $primaryColor }}; color: {{ $textColor }}; letter-spacing: 0.12em;">
                    Coverage Summary
                </div>
                <div class="min-h-[150px] p-3 text-sm leading-6 text-slate-800" style="white-space: pre-line;">
                    {{ $coverageSummary ?: 'No additional coverage summary provided.' }}
                </div>
            </div>

            <div class="{{ $isMedicalDeferredQuotation ? 'col-span-6' : 'col-span-4 border-r border-slate-900' }}">
                <div class="px-2 py-1 text-xs font-bold uppercase" style="background-color: {{ $primaryColor }}; color: {{ $textColor }}; letter-spacing: 0.12em;">
                    Exclusions and Remarks
                </div>
                <div class="min-h-[150px] p-3 text-sm leading-6 text-slate-800" style="white-space: pre-line;">
                    {{ $exclusionsRemarks ?: 'No additional exclusions or remarks provided.' }}
                </div>
            </div>

            @unless($isMedicalDeferredQuotation)
                <div class="col-span-4">
                    <div class="px-2 py-1 text-xs font-bold uppercase" style="background-color: {{ $primaryColor }}; color: {{ $textColor }}; letter-spacing: 0.12em;">
                        Premium Summary
                    </div>
                    <table class="w-full border-collapse text-sm">
                        <tbody>
                            <tr>
                                <td class="border-b border-slate-900 px-3 py-2 font-semibold">Sub-Total</td>
                                <td class="border-b border-slate-900 px-3 py-2 text-right">{{ formatCurrency($quotation->sub_total) }}</td>
                            </tr>
                            @foreach($quotation->taxes as $tax)
                                <tr>
                                    <td class="border-b border-slate-900 px-3 py-2 font-semibold">{{ $tax->name }}</td>
                                    <td class="border-b border-slate-900 px-3 py-2 text-right">{{ formatCurrency($tax->amount) }}</td>
                                </tr>
                            @endforeach
                            @if($quotation->discount > 0)
                                <tr>
                                    <td class="border-b border-slate-900 px-3 py-2 font-semibold">Discount</td>
                                    <td class="border-b border-slate-900 px-3 py-2 text-right">-{{ formatCurrency($quotation->discount) }}</td>
                                </tr>
                            @endif
                            <tr>
                                <td class="px-3 py-2 font-bold uppercase">Total Premium</td>
                                <td class="px-3 py-2 text-right font-bold">{{ formatCurrency($quotation->grand_total) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            @endunless
        </div>

        <div class="border-x border-b border-slate-900 px-4 py-3 text-center text-sm text-slate-800">
            We trust you will find our quotation competitive and await your placement instructions.
        </div>

        <div class="mt-4 px-4 py-4 text-center text-sm font-semibold" style="background-color: {{ $primaryColor }}; color: {{ $textColor }};">
            {{ collect([$quotation->business->phone ?? null, $businessEmail, $quotation->business->website ?? null])->filter()->join(' | ') }}
        </div>
    </div>
</div>
@endsection

@extends('backend.user.pdf.layout')

@php
    $primaryColor = get_business_option('invoice_primary_color', '#6d0e47');
    $textColor    = get_business_option('invoice_text_color', '#ffffff');
    $businessName = $quotation->business->business_name ?? $quotation->business->name ?? 'Business';
    $businessEmail = $quotation->business->business_email ?? $quotation->business->email ?? '';
    $isUnderwritingQuotation = (int) ($quotation->is_deffered ?? 0) === 1 && ! empty($quotation->insurance_category_id);
    $pageOrientation = $isUnderwritingQuotation ? 'landscape' : 'portrait';

    $quotationSections = $quotation->sections ?? collect();

    $quoteToRows = [
        ['label' => 'Quote To',    'value' => $quotation->customer->name ?? '-'],
        ['label' => 'Valid Until', 'value' => $quotation->expired_date ?: '-'],
    ];
@endphp

@push('styles')
<style>
    html {
        font-size: 12px;
    }

    body {
        margin: 0;
        padding: 0;
        background: white;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    @page {
        size: A4 {{ $pageOrientation }};
        margin: 7mm 7mm 14mm 7mm;
    }

    .quotation-pdf {
        color: #0f172a;
        font-size: 0.875rem;
        line-height: 1.25;
    }

    .quotation-header,
    .quotation-section,
    .quotation-summary,
    .quotation-closing {
        page-break-inside: avoid;
        break-inside: avoid;
    }

    .quotation-section-title,
    .quotation-table-title {
        page-break-after: avoid;
        break-after: avoid;
    }

    .quotation-pdf table {
        page-break-inside: auto;
        break-inside: auto;
    }

    .quotation-pdf thead {
        display: table-header-group;
    }

    .quotation-pdf tr,
    .quotation-pdf img {
        page-break-inside: avoid;
        break-inside: avoid;
    }

    .quotation-pdf td,
    .quotation-pdf th {
        line-height: 1.2;
    }

    @media print {
        html {
            font-size: 12px;
        }

        body {
            margin: 0;
            padding: 0;
            background: white;
        }
    }

    .quotation-sections-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 8px;
        margin-top: 12px;
    }

    .quotation-section-full {
        grid-column: 1 / -1;
    }

    @media screen and (max-width: 767px) {
        .quotation-sections-grid {
            grid-template-columns: 1fr;
        }

        .quotation-section-full {
            grid-column: auto;
        }
    }
</style>
@endpush

@section('content')
<div class="quotation-pdf w-full bg-white">
    <div class="quotation-shell border-[10px] p-3" style="border-color: {{ $primaryColor }};">
        <div class="mb-4 h-4" style="background-color: {{ $primaryColor }};"></div>

        <div class="quotation-header grid grid-cols-12 gap-6">
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

        <div class="quotation-items mt-5 border border-slate-900">
            <div class="quotation-table-title border-b border-slate-900 px-2 py-1 text-xs font-bold uppercase" style="background-color: {{ $primaryColor }}; color: {{ $textColor }}; letter-spacing: 0.12em;">
                Quote Items
            </div>

            <table class="w-full border-collapse text-xs">
                <thead>
                    <tr>
                        <th class="border border-slate-900 px-2 py-1 text-left" style="background-color: {{ $primaryColor }}; color: {{ $textColor }};">Item</th>
                        <th class="border border-slate-900 px-2 py-1 text-left" style="background-color: {{ $primaryColor }}; color: {{ $textColor }};">Description</th>
                        <th class="border border-slate-900 px-2 py-1 text-right" style="background-color: {{ $primaryColor }}; color: {{ $textColor }};">Qty</th>
                        <th class="border border-slate-900 px-2 py-1 text-right" style="background-color: {{ $primaryColor }}; color: {{ $textColor }};">Rate</th>
                        <th class="border border-slate-900 px-2 py-1 text-right" style="background-color: {{ $primaryColor }}; color: {{ $textColor }};">Premium</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quotation->items as $item)
                        <tr>
                            <td class="border border-slate-900 px-2 py-2 align-top">{{ $item->product_name }}</td>
                            <td class="border border-slate-900 px-2 py-2 align-top">{{ $item->description }}</td>
                            <td class="border border-slate-900 px-2 py-2 text-right align-top">{{ $item->quantity }}</td>
                            <td class="border border-slate-900 px-2 py-2 text-right align-top">
                                @if($item->calculation_type === 'percentage_of_amount')
                                    {{ $item->rate_value ?? 0 }}%
                                @else
                                    {{ formatCurrency($item->unit_cost) }}
                                @endif
                            </td>
                            <td class="border border-slate-900 px-2 py-2 text-right align-top">{{ formatCurrency($item->sub_total) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        @if($quotationSections->isNotEmpty())
            <div class="quotation-sections-grid">
                @foreach($quotationSections as $section)
                    @php
                        $sectionData = $section->data_json ?? [];
                        $fields  = $sectionData['fields']  ?? [];
                        $columns = $sectionData['columns'] ?? [];
                        $rows    = $sectionData['rows']    ?? [];
                        $isLastOddSection = $quotationSections->count() % 2 === 1 && $loop->last;
                    @endphp

                    <div class="quotation-section border border-slate-900 {{ $isLastOddSection ? 'quotation-section-full' : '' }}">
                        <div class="quotation-section-title px-2 py-1 text-xs font-bold uppercase" style="background-color: {{ $primaryColor }}; color: {{ $textColor }}; letter-spacing: 0.12em;">
                            {{ $section->title }}
                        </div>

                        @if($section->type === 'fields')
                            <table class="w-full border-collapse text-sm">
                                <tbody>
                                    @foreach($fields as $field)
                                        <tr>
                                            <td class="w-1/3 border-t border-slate-900 px-3 py-2 font-semibold">{{ $field['label'] ?? '-' }}</td>
                                            <td class="border-t border-slate-900 px-3 py-2">{{ $field['value'] ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @elseif($section->type === 'table')
                            <table class="w-full border-collapse text-sm">
                                <thead>
                                    <tr>
                                        @foreach($columns as $column)
                                            <th class="border-t border-slate-900 px-3 py-2 text-left">{{ $column }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($rows as $row)
                                        <tr>
                                            @foreach($row as $cell)
                                                <td class="border-t border-slate-900 px-3 py-2">{{ $cell ?: '-' }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="min-h-[80px] p-3 text-sm leading-6 text-slate-800" style="white-space: pre-line;">
                                {{ $section->content ?: '-' }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div class="quotation-summary mt-5 border border-slate-900">
            <div class="quotation-section-title px-2 py-1 text-xs font-bold uppercase" style="background-color: {{ $primaryColor }}; color: {{ $textColor }}; letter-spacing: 0.12em;">
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

        <div class="quotation-closing border-x border-b border-slate-900 px-4 py-3 text-center text-sm text-slate-800">
            We trust you will find our quotation competitive and await your placement instructions.
        </div>

    </div>
</div>
@endsection

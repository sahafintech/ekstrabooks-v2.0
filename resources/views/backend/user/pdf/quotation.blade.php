@extends('backend.user.pdf.layout')

@php
    $primaryColor = get_business_option('invoice_primary_color', '#6d0e47');
    $textColor    = get_business_option('invoice_text_color', '#ffffff');
    $businessName = $quotation->business->business_name ?? $quotation->business->name ?? 'Business';
    $businessEmail = $quotation->business->business_email ?? $quotation->business->email ?? '';
    $createdByName = optional($quotation->createdBy)->name ?: '-';
    $dateTimeFormat = trim(get_date_format() . ' ' . get_time_format());
    $createdDate = $quotation->created_at ? $quotation->created_at->format($dateTimeFormat) : '-';
    $updatedDate = $quotation->updated_at ? $quotation->updated_at->format($dateTimeFormat) : '-';
    $isUnderwritingQuotation = (int) ($quotation->is_deffered ?? 0) === 1 && ! empty($quotation->insurance_category_id);
    $pageOrientation = $isUnderwritingQuotation ? 'landscape' : 'portrait';
    $pageHeight = $isUnderwritingQuotation ? '210mm' : '297mm';

    $quotationSections = $quotation->sections ?? collect();

    $quoteToRows = [
        ['label' => 'Quote To',    'value' => $quotation->customer->name ?? '-'],
        ['label' => 'Valid Until', 'value' => $quotation->expired_date ?: '-'],
    ];

    $quotationInfoRows = [
        ['label' => 'Quotation Date',     'value' => $quotation->quotation_date ?: '-'],
        ['label' => 'Currency Type',      'value' => $quotation->currency ?: '-'],
        ['label' => 'Quotation No',       'value' => $quotation->quotation_number ?: '-'],
        ['label' => 'Quotation Validity', 'value' => $quotation->expired_date ?: '-'],
        ['label' => 'Created By',         'value' => $createdByName],
        ['label' => 'Created Date',       'value' => $createdDate],
        ['label' => 'Last Updated',       'value' => $updatedDate],
    ];
@endphp

@push('styles')
<style>
    html {
        font-size: 11px;
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
        margin: 0;
    }

    .quotation-pdf {
        color: #0f172a;
        font-size: 0.875rem;
        line-height: 1.18;
    }

    .quotation-shell {
        box-sizing: border-box;
        display: flex;
        min-height: {{ $pageHeight }};
        flex-direction: column;
        border-width: 6px;
        padding: 7px;
    }

    .quotation-pdf thead {
        display: table-header-group;
    }

    .quotation-pdf td,
    .quotation-pdf th {
        padding-top: 3px;
        padding-bottom: 3px;
        line-height: 1.15;
    }

    @media print {
        html {
            font-size: 11px;
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
        gap: 6px;
        margin-top: 7px;
    }

    .quotation-header {
        gap: 8px;
    }

    .quotation-items,
    .quotation-summary {
        margin-top: 7px;
    }

    .quotation-section-content {
        min-height: 36px;
        line-height: 1.25;
    }

    .quotation-section-full {
        grid-column: 1 / -1;
    }

    .quotation-contact-footer {
        margin-top: auto;
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
        <div class="mb-2 h-3" style="background-color: {{ $primaryColor }};"></div>

        <div class="quotation-header grid grid-cols-12 gap-2">
            <div class="col-span-7">
                <div class="mb-2 flex items-start justify-between gap-2">
                    <div class="max-w-[260px]">
                        @if($quotation->business->logo)
                            <img
                                src="{{ public_path('/uploads/media/' . $quotation->business->logo) }}"
                                alt="Business Logo"
                                class="max-h-20 object-contain"
                            />
                        @else
                            <div class="text-3xl font-bold uppercase" style="color: {{ $primaryColor }};">
                                {{ $businessName }}
                            </div>
                        @endif
                    </div>
                    <div class="shrink-0">
                        {!! QrCode::size(82)->errorCorrection('H')->margin(6)->generate(route('quotations.show_public_quotation', $quotation->short_code)) !!}
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
                    <div class="border-b-2 px-3 py-1 text-center text-xl font-bold uppercase" style="color: {{ $primaryColor }}; border-color: {{ $primaryColor }};">
                        Quotation
                    </div>
                    <div class="px-3 py-1">
                        @foreach($quotationInfoRows as $row)
                            <div class="flex items-start justify-between gap-2 py-0.5 text-sm">
                                <span class="font-semibold text-slate-900">{{ $row['label'] }}:</span>
                                <span class="text-right text-slate-700">{{ $row['value'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="quotation-items border border-slate-900">
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
                            <td class="border border-slate-900 px-2 py-1 align-top">{{ $item->product_name }}</td>
                            <td class="border border-slate-900 px-2 py-1 align-top">{{ $item->description }}</td>
                            <td class="border border-slate-900 px-2 py-1 text-right align-top">{{ $item->quantity }}</td>
                            <td class="border border-slate-900 px-2 py-1 text-right align-top">
                                @if($item->calculation_type === 'percentage_of_amount')
                                    {{ $item->rate_value ?? 0 }}%
                                @else
                                    {{ formatCurrency($item->unit_cost) }}
                                @endif
                            </td>
                            <td class="border border-slate-900 px-2 py-1 text-right align-top">{{ formatCurrency($item->sub_total) }}</td>
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
                            <div class="quotation-section-content p-2 text-sm text-slate-800" style="white-space: pre-line;">
                                {{ $section->content ?: '-' }}
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif

        <div class="quotation-summary border border-slate-900">
            <div class="quotation-section-title px-2 py-1 text-xs font-bold uppercase" style="background-color: {{ $primaryColor }}; color: {{ $textColor }}; letter-spacing: 0.12em;">
                Premium Summary
            </div>
            <table class="w-full border-collapse text-sm">
                <tbody>
                    <tr>
                        <td class="border-b border-slate-900 px-3 py-1 font-semibold">Sub-Total</td>
                        <td class="border-b border-slate-900 px-3 py-1 text-right">{{ formatCurrency($quotation->sub_total) }}</td>
                    </tr>
                    @foreach($quotation->taxes as $tax)
                        <tr>
                            <td class="border-b border-slate-900 px-3 py-1 font-semibold">{{ $tax->name }}</td>
                            <td class="border-b border-slate-900 px-3 py-1 text-right">{{ formatCurrency($tax->amount) }}</td>
                        </tr>
                    @endforeach
                    @if($quotation->discount > 0)
                        <tr>
                            <td class="border-b border-slate-900 px-3 py-1 font-semibold">Discount</td>
                            <td class="border-b border-slate-900 px-3 py-1 text-right">-{{ formatCurrency($quotation->discount) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="px-3 py-1 font-bold uppercase">Total Premium</td>
                        <td class="px-3 py-1 text-right font-bold">{{ formatCurrency($quotation->grand_total) }}</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div class="quotation-closing border-x border-b border-slate-900 px-3 py-1.5 text-center text-sm text-slate-800">
            We trust you will find our quotation competitive and await your placement instructions.
        </div>

        <div class="quotation-contact-footer px-3 py-1.5 text-center text-xs font-semibold" style="background-color: {{ $primaryColor }}; color: {{ $textColor }};">
            {{ collect([$quotation->business->phone ?? null, $businessEmail, $quotation->business->website ?? null])->filter()->join(' | ') }}
        </div>

    </div>
</div>
@endsection

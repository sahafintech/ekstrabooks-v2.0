@extends('backend.user.pdf.layout')

@php
    $primaryColor = get_business_option('invoice_primary_color', '#6d0e47');
    $textColor = get_business_option('invoice_text_color', '#ffffff');
    $businessName = $invoice->business->business_name ?? $invoice->business->name ?? 'Business';
    $businessEmail = $invoice->business->business_email ?? $invoice->business->email ?? '';
    $businessPhone = $invoice->business->phone ?? $invoice->business->mobile ?? '';
    $businessWebsite = $invoice->business->website ?? '';
    $preparedBy = optional($invoice->createdBy)->name ?? $businessName;
    $quantityLabel = $invoice->invoice_category === 'medical' ? 'Members' : 'Quantity';
    $policyPeriod = collect([$invoice->deffered_start, $invoice->deffered_end])->filter()->join(' - ');
    $items = $invoice->items ?? collect();
    $bankAccounts = collect($invoice->business->bank_accounts ?? []);
    $fillerRowCount = max(0, 10 - count($items));
    $paidAmount = (float) ($invoice->paid ?? 0);
    $outstandingAmount = max(0, (float) $invoice->grand_total - $paidAmount);
@endphp

@push('styles')
<style>
    html,
    body {
        margin: 0;
        padding: 0;
        background: white;
    }

    @page {
        size: A4 portrait;
        margin: 0;
    }
</style>
@endpush

@section('content')
<div class="m-0 w-full bg-white">
    <div class="border-[10px] p-3" style="border-color: {{ $primaryColor }};">
        <div class="mb-4 h-4" style="background-color: {{ $primaryColor }};"></div>

        <div class="mb-4 flex items-start justify-between gap-4">
            <div class="max-w-[260px]">
                @if($invoice->business->logo)
                    <img
                        src="{{ public_path('/uploads/media/' . $invoice->business->logo) }}"
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
                {!! QrCode::size(100)->errorCorrection('H')->margin(10)->generate(route('deffered_invoices.show_public_deffered_invoice', $invoice->short_code)) !!}
            </div>
        </div>

        <div class="grid grid-cols-12 gap-0 border border-slate-900 text-sm">
            <div class="col-span-7 border-r border-slate-900 px-3 py-3">
                <div class="space-y-1.5">
                    <div>
                        <span class="font-semibold uppercase">Invoice To:</span>
                        {{ $invoice->customer->name ?? '-' }}
                    </div>
                    <div>
                        <span class="font-semibold uppercase">Policy No:</span>
                        {{ $invoice->order_number ?: '-' }}
                    </div>
                    @if($policyPeriod)
                        <div>
                            <span class="font-semibold uppercase">Policy Period:</span>
                            {{ $policyPeriod }}
                        </div>
                    @endif
                    @if($invoice->invoice_category)
                        <div>
                            <span class="font-semibold uppercase">Insurance Class:</span>
                            {{ strtoupper($invoice->invoice_category) }}
                        </div>
                    @endif
                </div>
            </div>

            <div class="col-span-5 px-3 py-3">
                <div class="space-y-1.5">
                    <div class="flex justify-between gap-4">
                        <span class="font-semibold uppercase">Invoice Number:</span>
                        <span>{{ $invoice->invoice_number ?: '-' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="font-semibold uppercase">Invoice Date:</span>
                        <span>{{ $invoice->invoice_date ?: '-' }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="font-semibold uppercase">Due Date:</span>
                        <span>{{ $invoice->due_date ?: '-' }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div
            class="mt-4 px-2 py-1 text-center text-2xl font-bold uppercase leading-none"
            style="background-color: {{ $primaryColor }}; color: {{ $textColor }}; letter-spacing: 0.12em;"
        >
            Invoice
        </div>

        <div class="border-x border-b border-slate-900">
            <table class="w-full border-collapse text-xs">
                <thead>
                    <tr>
                        <th class="border border-slate-900 px-2 py-1 text-left" style="background-color: {{ $primaryColor }}; color: {{ $textColor }};">Class</th>
                        <th class="border border-slate-900 px-2 py-1 text-left" style="background-color: {{ $primaryColor }}; color: {{ $textColor }};">Description</th>
                        <th class="border border-slate-900 px-2 py-1 text-right" style="background-color: {{ $primaryColor }}; color: {{ $textColor }};">{{ $quantityLabel }}</th>
                        <th class="border border-slate-900 px-2 py-1 text-right" style="background-color: {{ $primaryColor }}; color: {{ $textColor }};">Rate</th>
                        <th class="border border-slate-900 px-2 py-1 text-right" style="background-color: {{ $primaryColor }}; color: {{ $textColor }};">Amount</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($items as $item)
                        <tr>
                            <td class="border border-slate-900 px-2 py-2 align-top">
                                {{ $item->product_name ?: '-' }}
                            </td>
                            <td class="border border-slate-900 px-2 py-2 align-top">
                                <div class="font-medium text-slate-900">
                                    {{ $item->description ?: '-' }}
                                </div>
                                <div class="mt-1 space-y-0.5 text-[11px] text-slate-700">
                                    @if($item->benefits)
                                        <div>Benefits: {{ $item->benefits }}</div>
                                    @endif
                                    @if($invoice->invoice_category === 'medical' && $item->family_size)
                                        <div>Family Size: {{ $item->family_size }}</div>
                                    @endif
                                    @if($invoice->invoice_category === 'other' && (float) $item->sum_insured > 0)
                                        <div>Sum Insured: {{ formatCurrency($item->sum_insured) }}</div>
                                    @endif
                                </div>
                            </td>
                            <td class="border border-slate-900 px-2 py-2 text-right align-top">
                                {{ $item->quantity }}
                            </td>
                            <td class="border border-slate-900 px-2 py-2 text-right align-top">
                                {{ formatCurrency($item->unit_cost) }}
                            </td>
                            <td class="border border-slate-900 px-2 py-2 text-right align-top">
                                {{ formatCurrency($item->quantity * $item->unit_cost) }}
                            </td>
                        </tr>
                    @endforeach

                    @for($i = 0; $i < $fillerRowCount; $i++)
                        <tr class="h-8">
                            <td class="border border-slate-900 px-2 py-2"></td>
                            <td class="border border-slate-900 px-2 py-2"></td>
                            <td class="border border-slate-900 px-2 py-2"></td>
                            <td class="border border-slate-900 px-2 py-2"></td>
                            <td class="border border-slate-900 px-2 py-2"></td>
                        </tr>
                    @endfor
                </tbody>
            </table>
        </div>

        <div class="grid grid-cols-12 gap-0 border border-slate-900 border-t-0">
            <div class="col-span-7 border-r border-slate-900">
                <div class="px-2 py-1 text-xs font-bold uppercase" style="background-color: {{ $primaryColor }}; color: {{ $textColor }}; letter-spacing: 0.12em;">
                    Bank Details
                </div>

                <table class="w-full border-collapse text-xs">
                    <tbody>
                        @if($bankAccounts->count() > 0)
                            @foreach($bankAccounts as $bank)
                                <tr>
                                    <td class="w-40 border-b border-slate-900 px-2 py-2 font-semibold align-top">
                                        {{ $bank->bank_name }}
                                    </td>
                                    <td class="border-b border-slate-900 px-2 py-2 align-top">
                                        <div>{{ $bank->account_number ?: '-' }}</div>
                                        @if($bank->account_name)
                                            <div class="text-[11px] text-slate-600">{{ $bank->account_name }}</div>
                                        @endif
                                        @if($bank->swift_code)
                                            <div class="text-[11px] text-slate-600">Swift: {{ $bank->swift_code }}</div>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        @else
                            <tr>
                                <td colspan="2" class="px-2 py-4 text-center text-slate-500">
                                    No bank details available.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>

                @if($invoice->note || $invoice->footer)
                    <div class="border-t border-slate-900 text-xs">
                        @if($invoice->note)
                            <div class="px-3 py-2">
                                <span class="font-semibold uppercase">Notes:</span>
                                {{ $invoice->note }}
                            </div>
                        @endif
                        @if($invoice->footer)
                            <div class="px-3 py-2 {{ $invoice->note ? 'border-t border-slate-900' : '' }}">
                                <span class="font-semibold uppercase">Terms:</span>
                                {{ $invoice->footer }}
                            </div>
                        @endif
                    </div>
                @endif
            </div>

            <div class="col-span-5">
                <div class="px-2 py-1 text-xs font-bold uppercase" style="background-color: {{ $primaryColor }}; color: {{ $textColor }}; letter-spacing: 0.12em;">
                    Invoice Summary
                </div>

                <table class="w-full border-collapse text-sm">
                    <tbody>
                        <tr>
                            <td class="border-b border-slate-900 px-3 py-2 font-semibold">Subtotal</td>
                            <td class="border-b border-slate-900 px-3 py-2 text-right">{{ formatCurrency($invoice->sub_total) }}</td>
                        </tr>

                        @foreach($invoice->taxes as $tax)
                            <tr>
                                <td class="border-b border-slate-900 px-3 py-2 font-semibold">{{ $tax->name }}</td>
                                <td class="border-b border-slate-900 px-3 py-2 text-right">{{ formatCurrency($tax->amount) }}</td>
                            </tr>
                        @endforeach

                        @if($invoice->discount > 0)
                            <tr>
                                <td class="border-b border-slate-900 px-3 py-2 font-semibold">Discount</td>
                                <td class="border-b border-slate-900 px-3 py-2 text-right">-{{ formatCurrency($invoice->discount) }}</td>
                            </tr>
                        @endif

                        <tr>
                            <td class="border-b border-slate-900 px-3 py-2 font-semibold">Total Invoice Amount</td>
                            <td class="border-b border-slate-900 px-3 py-2 text-right">{{ formatCurrency($invoice->grand_total) }}</td>
                        </tr>
                        <tr>
                            <td class="border-b border-slate-900 px-3 py-2 font-semibold">Credit Applied</td>
                            <td class="border-b border-slate-900 px-3 py-2 text-right">{{ formatCurrency($paidAmount) }}</td>
                        </tr>
                        <tr>
                            <td class="px-3 py-2 font-bold uppercase">Net Amount</td>
                            <td class="px-3 py-2 text-right font-bold">{{ formatCurrency($outstandingAmount) }}</td>
                        </tr>
                    </tbody>
                </table>

                @if($invoice->currency !== $invoice->business->currency)
                    <div class="border-t border-slate-900 px-3 py-2 text-[11px] text-slate-700">
                        <div>Exchange Rate: 1 {{ $invoice->business->currency }} = {{ formatCurrency($invoice->exchange_rate) }}</div>
                        <div>Equivalent Total: {{ formatCurrency($invoice->converted_total) }}</div>
                    </div>
                @endif
            </div>
        </div>

        <div class="grid grid-cols-12 gap-0 border-x border-b border-slate-900">
            <div class="col-span-7 px-4 py-6 text-sm">
                <div class="font-semibold">Prepared By: {{ $preparedBy }}</div>
                <div class="mt-3 font-semibold">Signature:</div>
                <div class="mt-6 w-40 border-b border-slate-900"></div>
            </div>

            <div class="col-span-5 px-4 py-6 text-xs text-slate-700">
                @if($invoice->invoice_category)
                    <div class="mb-1">
                        <span class="font-semibold uppercase">Category:</span>
                        {{ strtoupper($invoice->invoice_category) }}
                    </div>
                @endif
                @if($policyPeriod)
                    <div class="mb-1">
                        <span class="font-semibold uppercase">Policy Period:</span>
                        {{ $policyPeriod }}
                    </div>
                @endif
                @if($invoice->currency)
                    <div>
                        <span class="font-semibold uppercase">Currency:</span>
                        {{ $invoice->currency }}
                    </div>
                @endif
            </div>
        </div>

        <div class="mt-4 px-4 py-4 text-center text-sm font-semibold" style="background-color: {{ $primaryColor }}; color: {{ $textColor }};">
            {{ collect([$businessPhone, $businessEmail, $businessWebsite])->filter()->join(' | ') }}
        </div>
    </div>
</div>
@endsection

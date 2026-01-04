@extends('backend.user.pdf.layout')

@push('styles')
<style>
    @media print {
        body {
            margin: 0;
            padding: 0;
        }

        @page {
            size: A4;
            margin: 10mm;
        }
    }
</style>
@endpush

@section('content')
<div class="w-full max-w-4xl mx-auto bg-white">
    <div class="p-6 sm:p-8">
        <!-- Invoice Header -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
            <div>
                @if($invoice->business->logo)
                <div class="mb-3">
                    <img
                        src="{{ public_path('/uploads/media/' . $invoice->business->logo) }}"
                        alt="Business Logo"
                        class="max-h-32 object-contain" />
                </div>
                @endif
                <div class="mt-2 text-sm">
                    <p>{{ $invoice->business->address }}</p>
                    <p>{{ $invoice->business->business_email }}</p>
                    <p>{{ $invoice->business->phone }}</p>
                </div>
            </div>
            <div class="sm:text-right">
                <h1 class="text-2xl font-bold">{{ $invoice->title }}</h1>
                <div class="mt-2 text-sm">
                    <p><span class="font-medium">Invoice #:</span> {{ $invoice->invoice_number }}</p>
                    <p><span class="font-medium">Invoice Date:</span> {{ $invoice->invoice_date }}</p>
                    <p><span class="font-medium">Due Date:</span> {{ $invoice->due_date }}</p>
                    @if($invoice->order_number)
                    <p><span class="font-medium">Order Number:</span> {{ $invoice->order_number }}</p>
                    @endif
                    @if($invoice->paid > 0)
                    <div class="mt-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                            Paid: {{ formatCurrency($invoice->paid) }}
                        </span>
                    </div>
                    @endif
                </div>
                <div class="mt-4 sm:flex sm:justify-end">
                    <!-- QR Code -->
                    <div class="w-24 h-24 border-2 border-gray-300 border-dashed flex items-center justify-center text-xs text-gray-500">
                        {!! QrCode::size(200)->generate(route('invoices.show_public_invoice', $invoice->short_code)) !!}
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-6 border-gray-200">

        <!-- Customer Information -->
        <div class="mb-8">
            <h3 class="font-medium text-lg mb-2">Bill To:</h3>
            <div class="text-sm">
                <p class="font-medium">{{ $invoice->customer->name ?? '' }}</p>
                @if(isset($invoice->customer->company_name) && $invoice->customer->company_name)
                <p>{{ $invoice->customer->company_name }}</p>
                @endif
                <p>{{ $invoice->customer->address ?? '' }}</p>
                <p>{{ $invoice->customer->email ?? '' }}</p>
                <p>{{ $invoice->customer->mobile ?? '' }}</p>
            </div>

            @if($invoice->project_id !== null && isset($invoice->project))
            <h3 class="font-medium text-lg mt-4 mb-2">Project:</h3>
            <div class="text-sm">
                <p>Project Name: {{ $invoice->project->project_name }}</p>
                <p>Project Code: {{ $invoice->project->project_code }}</p>
                <p>Start Date: {{ $invoice->project->start_date }}</p>
                <p>End Date: {{ $invoice->project->end_date }}</p>
            </div>
            @endif
        </div>

        <!-- Bank Details -->
        @if($invoice->business->bank_accounts && count($invoice->business->bank_accounts) > 0)
        <div class="mb-8">
            <h3 class="font-medium text-lg mb-2">Bank Details:</h3>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                @foreach($invoice->business->bank_accounts as $bank)
                <div class="text-sm bg-gray-100 p-3 rounded-md">
                    <p><span class="font-medium">Bank Name:</span> {{ $bank->bank_name }}</p>
                    <p><span class="font-medium">Account Number:</span> {{ $bank->account_number }}</p>
                    <p><span class="font-medium">Account Name:</span> {{ $bank->account_name }}</p>
                </div>
                @endforeach
            </div>
        </div>
        @endif

        <!-- Invoice Items -->
        <div class="mb-8">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 font-medium">Item</th>
                        <th class="text-left py-3 font-medium">Description</th>
                        <th class="text-right py-3 font-medium">Quantity</th>
                        <th class="text-right py-3 font-medium">Unit Cost</th>
                        <th class="text-right py-3 font-medium">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($invoice->items as $item)
                    <tr class="border-b border-gray-100">
                        <td class="py-3 font-medium">{{ $item->product_name }}</td>
                        <td class="py-3">{{ $item->description }}</td>
                        <td class="py-3 text-right">{{ $item->quantity }}</td>
                        <td class="py-3 text-right">{{ formatCurrency($item->unit_cost) }}</td>
                        <td class="py-3 text-right">
                            {{ formatCurrency($item->quantity * $item->unit_cost) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Invoice Summary -->
        <div class="flex justify-end">
            <div class="w-full md:w-1/2 lg:w-1/3 space-y-2">
                <div class="flex justify-between py-2 border-t border-gray-200">
                    <span class="font-medium">Subtotal:</span>
                    <span>{{ formatCurrency($invoice->sub_total) }}</span>
                </div>

                <!-- Tax details -->
                @foreach($invoice->taxes as $tax)
                <div class="flex justify-between py-2">
                    <span>{{ $tax->name }}:</span>
                    <span>{{ formatCurrency($tax->amount) }}</span>
                </div>
                @endforeach

                <!-- Discount -->
                @if($invoice->discount > 0)
                <div class="flex justify-between py-2">
                    <span>Discount:</span>
                    <span>-{{ formatCurrency($invoice->discount) }}</span>
                </div>
                @endif

                <!-- Total -->
                <div class="flex justify-between py-3 border-t border-b border-gray-200 font-bold text-lg">
                    <span>Total:</span>
                    <span>{{ formatCurrency($invoice->grand_total) }}</span>
                </div>

                <!-- Base currency equivalent if different currency -->
                @if($invoice->currency !== $invoice->business->currency)
                <div class="flex justify-between py-2 text-gray-500 text-sm">
                    <span>Exchange Rate:</span>
                    <span>
                        1 {{ $invoice->business->currency }} = {{ formatCurrency($invoice->exchange_rate) }}
                    </span>
                </div>
                @endif

                <!-- Base currency equivalent total -->
                @if($invoice->currency !== $invoice->business->currency)
                <div class="flex justify-between py-2 text-sm text-gray-600">
                    <span>Equivalent to:</span>
                    <span>
                        {{ formatCurrency($invoice->converted_total) }}
                    </span>
                </div>
                @endif
            </div>
        </div>

        <!-- Notes & Terms -->
        @if($invoice->note || $invoice->footer)
        <div class="mt-8 space-y-4">
            @if($invoice->note)
            <div>
                <h3 class="font-medium mb-1">Notes:</h3>
                <p class="text-sm">{{ $invoice->note }}</p>
            </div>
            @endif

            @if($invoice->footer)
            <div>
                <h3 class="font-medium mb-1">Terms & Conditions:</h3>
                <p class="text-sm">{{ $invoice->footer }}</p>
            </div>
            @endif
        </div>
        @endif
    </div>
</div>
@endsection
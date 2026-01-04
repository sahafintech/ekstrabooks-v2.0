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
        <!-- Payment Header -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
            <div>
                @if($payment->business->logo)
                <div class="mb-3">
                    <img
                        src="{{ public_path('/uploads/media/' . $payment->business->logo) }}"
                        alt="Business Logo"
                        class="max-h-32 object-contain" />
                </div>
                @endif
                <div class="mt-2 text-sm">
                    <p>{{ $payment->business->address }}</p>
                    <p>{{ $payment->business->business_email }}</p>
                    <p>{{ $payment->business->phone }}</p>
                </div>
            </div>
            <div class="sm:text-right">
                <h1 class="text-2xl font-bold">Payment Voucher</h1>
                <div class="mt-2 text-sm">
                    <p><span class="font-medium">Payment #:</span> {{ $payment->id }}</p>
                    <p><span class="font-medium">Payment Date:</span> {{ $payment->date }}</p>
                    <p><span class="font-medium">Payment Method:</span> {{ $payment->payment_method }}</p>
                    <p><span class="font-medium">Payment Type:</span> {{ $payment->type }}</p>
                    @if($payment->reference)
                    <p><span class="font-medium">Reference:</span> {{ $payment->reference }}</p>
                    @endif
                </div>
                <div class="mt-4 sm:flex sm:justify-end">
                    <!-- QR Code -->
                    <div class="w-24 h-24 border-2 border-gray-300 border-dashed flex items-center justify-center text-xs text-gray-500">
                        {!! QrCode::size(200)->generate(route('receive_payments.show_public_receive_payment', $payment->id)) !!}
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-6 border-gray-200">

        <!-- Customer Information -->
        <div class="mb-8">
            <h3 class="font-medium text-lg mb-2">Received From:</h3>
            <div class="text-sm">
                <p class="font-medium">{{ $payment->customer->name ?? '' }}</p>
                @if(isset($payment->customer->company_name) && $payment->customer->company_name)
                <p>{{ $payment->customer->company_name }}</p>
                @endif
                <p>{{ $payment->customer->address ?? '' }}</p>
                <p>{{ $payment->customer->email ?? '' }}</p>
                <p>{{ $payment->customer->mobile ?? '' }}</p>
            </div>
        </div>

        <!-- Payment Items - Invoices -->
        <div class="mb-8">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 font-medium">Invoice Number</th>
                        <th class="text-left py-3 font-medium">Invoice Date</th>
                        <th class="text-right py-3 font-medium">Grand Total</th>
                        <th class="text-right py-3 font-medium">Amount Paid</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($payment->invoices as $invoice)
                    <tr class="border-b border-gray-100">
                        <td class="py-3 font-medium">{{ $invoice->invoice_number }}</td>
                        <td class="py-3">{{ $invoice->invoice_date }}</td>
                        <td class="py-3 text-right">{{ formatCurrency($invoice->grand_total) }}</td>
                        <td class="py-3 text-right">
                            {{ formatCurrency($invoice->paid) }}
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Payment Summary -->
        <div class="flex justify-end">
            <div class="w-full md:w-1/2 lg:w-1/3 space-y-2">
                <div class="flex justify-between py-3 border-t border-b border-gray-200 font-bold text-lg">
                    <span>Total Paid:</span>
                    <span>{{ formatCurrency($payment->amount) }}</span>
                </div>
            </div>
        </div>

        <!-- Payment Details -->
        <div class="mt-8">
            <div class="bg-gray-50 p-4 rounded-md">
                <h3 class="font-medium mb-2">Payment Details:</h3>
                <div class="text-sm space-y-1">
                    <p><span class="font-medium">Account:</span> {{ $payment->account->account_name ?? 'N/A' }}</p>
                    <p><span class="font-medium">Payment Method:</span> {{ $payment->payment_method ?? 'N/A' }}</p>
                    @if($payment->reference)
                    <p><span class="font-medium">Reference:</span> {{ $payment->reference }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Footer Note -->
        <div class="mt-8 text-center text-sm text-gray-600">
            <p>Thank you for your payment!</p>
        </div>
    </div>
</div>
@endsection
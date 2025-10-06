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
                                class="max-h-32 object-contain"
                            />
                        </div>
                    @endif
                    <h2 class="text-2xl font-bold text-blue-600">
                        {{ $payment->business->business_name }}
                    </h2>
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
                        @if($payment->reference)
                            <p><span class="font-medium">Reference:</span> {{ $payment->reference }}</p>
                        @endif
                        <div class="mt-2">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                Amount: {{ formatCurrency($payment->amount) }}
                            </span>
                        </div>
                    </div>
                    <div class="mt-4 sm:flex sm:justify-end">
                        <!-- QR Code -->
                        <div class="w-24 h-24 border-2 border-gray-300 border-dashed flex items-center justify-center text-xs text-gray-500">
                            {!! QrCode::size(200)->generate(route('bill_payments.show_public_bill_payment', $payment->id)) !!}
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-6 border-gray-200">

            <!-- Vendor Information -->
            @if($payment->vendor)
            <div class="mb-8">
                <h3 class="font-medium text-lg mb-2">Paid To:</h3>
                <div class="text-sm">
                    <p class="font-medium">{{ $payment->vendor->name }}</p>
                    @if($payment->vendor->company_name)
                        <p>{{ $payment->vendor->company_name }}</p>
                    @endif
                    <p>{{ $payment->vendor->address }}</p>
                    <p>{{ $payment->vendor->email }}</p>
                    <p>{{ $payment->vendor->mobile }}</p>
                </div>
            </div>
            @endif

            <!-- Payment Details -->
            <div class="mb-8">
                <table class="w-full border-collapse">
                    <thead>
                        <tr class="border-b border-gray-200">
                            <th class="text-left py-3 font-medium">Bill Number</th>
                            <th class="text-left py-3 font-medium">Supplier</th>
                            <th class="text-left py-3 font-medium">Bill Date</th>
                            <th class="text-right py-3 font-medium">Grand Total</th>
                            <th class="text-right py-3 font-medium">Amount Paid</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($payment->purchases as $purchase)
                            <tr class="border-b border-gray-100">
                                <td class="py-3 font-medium">{{ $purchase->bill_no }}</td>
                                <td class="py-3">{{ $payment->vendor ? $payment->vendor->name : 'N/A' }}</td>
                                <td class="py-3">{{ $purchase->purchase_date }}</td>
                                <td class="py-3 text-right">{{ formatCurrency($purchase->grand_total) }}</td>
                                <td class="py-3 text-right">
                                    {{ formatCurrency($purchase->pivot->amount) }}
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

            <!-- Payment Method Details -->
            @if($payment->payment_method || $payment->reference)
                <div class="mt-8 space-y-4">
                    <div>
                        <h3 class="font-medium mb-1">Payment Details:</h3>
                        <div class="text-sm space-y-1">
                            @if($payment->payment_method)
                                <p><span class="font-medium">Method:</span> {{ $payment->payment_method }}</p>
                            @endif
                            @if($payment->reference)
                                <p><span class="font-medium">Reference:</span> {{ $payment->reference }}</p>
                            @endif
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
@endsection

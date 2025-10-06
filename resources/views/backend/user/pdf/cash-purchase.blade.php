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
                    @if($bill->business->logo)
                        <div class="mb-3">
                            <img
                                src="{{ public_path('/uploads/media/' . $bill->business->logo) }}"
                                alt="Business Logo"
                                class="max-h-32 object-contain"
                            />
                        </div>
                    @endif
                    <h2 class="text-2xl font-bold text-blue-600">
                        {{ $bill->business->business_name }}
                    </h2>
                    <div class="mt-2 text-sm">
                        <p>{{ $bill->business->address }}</p>
                        <p>{{ $bill->business->business_email }}</p>
                        <p>{{ $bill->business->phone }}</p>
                    </div>
                </div>
                <div class="sm:text-right">
                    <h1 class="text-2xl font-bold">{{ $bill->title }}</h1>
                    <div class="mt-2 text-sm">
                        <p><span class="font-medium">Cash Purchase #:</span> {{ $bill->bill_no }}</p>
                        <p><span class="font-medium">Cash Purchase Date:</span> {{ $bill->purchase_date }}</p>
                        <p><span class="font-medium">Due Date:</span> {{ $bill->due_date }}</p>
                        @if($bill->order_number)
                            <p><span class="font-medium">Order Number:</span> {{ $bill->order_number }}</p>
                        @endif
                        @if($bill->paid > 0)
                            <div class="mt-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                    Paid: {{ formatCurrency($bill->paid) }}
                                </span>
                            </div>
                        @endif
                    </div>
                    <div class="mt-4 sm:flex sm:justify-end">
                        <!-- QR Code placeholder - you may need to implement QR code generation -->
                        <div class="w-24 h-24 border-2 border-gray-300 border-dashed flex items-center justify-center text-xs text-gray-500">
                            {!! QrCode::size(200)->generate(route('cash_purchases.show_public_cash_purchase', $bill->short_code)) !!}
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-6 border-gray-200">

            <!-- Vendor Information -->
            <div class="mb-8">
                <h3 class="font-medium text-lg mb-2">Bill To:</h3>
                <div class="text-sm">
                    <p class="font-medium">{{ $bill->vendor->name ?? '' }}</p>
                    @if(isset($bill->vendor->company_name) && $bill->vendor->company_name)
                        <p>{{ $bill->vendor->company_name }}</p>
                    @endif
                    <p>{{ $bill->vendor->address ?? '' }}</p>
                    <p>{{ $bill->vendor->email ?? '' }}</p>
                    <p>{{ $bill->vendor->mobile ?? '' }}</p>
                </div>
            </div>

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
                        @foreach($bill->items as $item)
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
                        <span>{{ formatCurrency($bill->sub_total) }}</span>
                    </div>

                    <!-- Tax details -->
                    @foreach($bill->taxes as $tax)
                        <div class="flex justify-between py-2">
                            <span>{{ $tax->name }}:</span>
                            <span>{{ formatCurrency($tax->amount) }}</span>
                        </div>
                    @endforeach

                    <!-- Discount -->
                    @if($bill->discount > 0)
                        <div class="flex justify-between py-2">
                            <span>Discount:</span>
                            <span>-{{ formatCurrency($bill->discount) }}</span>
                        </div>
                    @endif

                    <!-- Total -->
                    <div class="flex justify-between py-3 border-t border-b border-gray-200 font-bold text-lg">
                        <span>Total:</span>
                        <span>{{ formatCurrency($bill->grand_total) }}</span>
                    </div>

                    <!-- Base currency equivalent if different currency -->
                    @if($bill->currency !== $bill->business->currency)
                        <div class="flex justify-between py-2 text-gray-500 text-sm">
                            <span>Exchange Rate:</span>
                            <span>
                                1 {{ $bill->business->currency }} = {{ formatCurrency($bill->exchange_rate) }}
                            </span>
                        </div>
                    @endif

                    <!-- Base currency equivalent total -->
                    @if($bill->currency !== $bill->business->currency)
                        <div class="flex justify-between py-2 text-sm text-gray-600">
                            <span>Equivalent to:</span>
                            <span>
                                {{ formatCurrency($bill->converted_total) }}
                            </span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Notes & Terms -->
            @if($bill->note || $bill->footer)
                <div class="mt-8 space-y-4">
                    @if($bill->note)
                        <div>
                            <h3 class="font-medium mb-1">Notes:</h3>
                            <p class="text-sm">{{ $bill->note }}</p>
                        </div>
                    @endif

                    @if($bill->footer)
                        <div>
                            <h3 class="font-medium mb-1">Terms & Conditions:</h3>
                            <p class="text-sm">{{ $bill->footer }}</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection
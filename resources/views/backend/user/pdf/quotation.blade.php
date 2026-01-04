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
        <!-- Quotation Header -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
            <div>
                @if($quotation->business->logo)
                <div class="mb-3">
                    <img
                        src="{{ public_path('/uploads/media/' . $quotation->business->logo) }}"
                        alt="Business Logo"
                        class="max-h-32 object-contain" />
                </div>
                @endif
                <div class="mt-2 text-sm">
                    <p>{{ $quotation->business->address }}</p>
                    <p>{{ $quotation->business->business_email }}</p>
                    <p>{{ $quotation->business->phone }}</p>
                </div>
            </div>
            <div class="sm:text-right">
                <h1 class="text-2xl font-bold">{{ $quotation->title }}</h1>
                <div class="mt-2 text-sm">
                    <p><span class="font-medium">Quotation #:</span> {{ $quotation->quotation_number }}</p>
                    <p><span class="font-medium">Quotation Date:</span> {{ $quotation->quotation_date }}</p>
                    <p><span class="font-medium">Valid Until:</span> {{ $quotation->expired_date }}</p>
                    @if($quotation->po_so_number)
                    <p><span class="font-medium">PO/SO Number:</span> {{ $quotation->po_so_number }}</p>
                    @endif
                    @if($quotation->expired_date && Carbon\Carbon::createFromFormat(get_date_format(), $quotation->expired_date)->isPast())
                    <div class="mt-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 border border-red-200">
                            Expired
                        </span>
                    </div>
                    @elseif($quotation->expired_date && Carbon\Carbon::createFromFormat(get_date_format(), $quotation->expired_date)->isFuture())
                    <div class="mt-2">
                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                            Valid
                        </span>
                    </div>
                    @endif
                </div>
                <div class="mt-4 sm:flex sm:justify-end">
                    <!-- QR Code for public quotation link -->
                    <div class="w-24 h-24 border-2 border-gray-300 border-dashed flex items-center justify-center text-xs text-gray-500">
                        {!! QrCode::size(200)->generate(route('quotations.show_public_quotation', $quotation->short_code)) !!}
                    </div>
                </div>
            </div>
        </div>

        <hr class="my-6 border-gray-200">

        <!-- Customer Information -->
        <div class="mb-8">
            <h3 class="font-medium text-lg mb-2">Quote To:</h3>
            <div class="text-sm">
                <p class="font-medium">{{ $quotation->customer->name ?? '' }}</p>
                @if(isset($quotation->customer->company_name) && $quotation->customer->company_name)
                <p>{{ $quotation->customer->company_name }}</p>
                @endif
                <p>{{ $quotation->customer->address ?? '' }}</p>
                <p>{{ $quotation->customer->email ?? '' }}</p>
                <p>{{ $quotation->customer->mobile ?? '' }}</p>
            </div>
        </div>

        <!-- Quotation Items -->
        <div class="mb-8">
            <table class="w-full border-collapse">
                <thead>
                    <tr class="border-b border-gray-200">
                        <th class="text-left py-3 font-medium">Item</th>
                        <th class="text-left py-3 font-medium">Description</th>
                        <th class="text-right py-3 font-medium">Quantity</th>
                        <th class="text-right py-3 font-medium">Unit Price</th>
                        <th class="text-right py-3 font-medium">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($quotation->items as $item)
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

        <!-- Quotation Summary -->
        <div class="flex justify-end">
            <div class="w-full md:w-1/2 lg:w-1/3 space-y-2">
                <div class="flex justify-between py-2 border-t border-gray-200">
                    <span class="font-medium">Subtotal:</span>
                    <span>{{ formatCurrency($quotation->sub_total) }}</span>
                </div>

                <!-- Tax details -->
                @foreach($quotation->taxes as $tax)
                <div class="flex justify-between py-2">
                    <span>{{ $tax->name }}:</span>
                    <span>{{ formatCurrency($tax->amount) }}</span>
                </div>
                @endforeach

                <!-- Discount -->
                @if($quotation->discount > 0)
                <div class="flex justify-between py-2">
                    <span>Discount:</span>
                    <span>-{{ formatCurrency($quotation->discount) }}</span>
                </div>
                @endif

                <!-- Total -->
                <div class="flex justify-between py-3 border-t border-b border-gray-200 font-bold text-lg">
                    <span>Total:</span>
                    <span>{{ formatCurrency($quotation->grand_total) }}</span>
                </div>

                <!-- Base currency equivalent if different currency -->
                @if($quotation->currency !== $quotation->business->currency)
                <div class="flex justify-between py-2 text-gray-500 text-sm">
                    <span>Exchange Rate:</span>
                    <span>
                        1 {{ $quotation->business->currency }} = {{ formatCurrency($quotation->exchange_rate) }}
                    </span>
                </div>
                @endif

                <!-- Base currency equivalent total -->
                @if($quotation->currency !== $quotation->business->currency)
                <div class="flex justify-between py-2 text-sm text-gray-600">
                    <span>Equivalent to:</span>
                    <span>
                        {{ formatCurrency($quotation->converted_total) }}
                    </span>
                </div>
                @endif
            </div>
        </div>

        <!-- Notes & Terms -->
        @if($quotation->note || $quotation->footer)
        <div class="mt-8 space-y-4">
            @if($quotation->note)
            <div>
                <h3 class="font-medium mb-1">Notes:</h3>
                <p class="text-sm">{{ $quotation->note }}</p>
            </div>
            @endif

            @if($quotation->footer)
            <div>
                <h3 class="font-medium mb-1">Terms & Conditions:</h3>
                <p class="text-sm">{{ $quotation->footer }}</p>
            </div>
            @endif
        </div>
        @endif

        <!-- Validity Notice -->
        @if($quotation->expired_date)
        <div class="mt-8 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
            <p class="text-sm text-yellow-800">
                <strong>Valid Until:</strong> {{ $quotation->expired_date }}.
                This quotation is valid for the period specified above.
            </p>
        </div>
        @endif
    </div>
</div>
@endsection
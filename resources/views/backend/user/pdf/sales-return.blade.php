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
            <!-- Sales Return Header -->
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
                <div>
                    @if($sales_return->business->logo)
                        <div class="mb-3">
                            <img
                                src="{{ public_path('/uploads/media/' . $sales_return->business->logo) }}"
                                alt="Business Logo"
                                class="max-h-32 object-contain"
                            />
                        </div>
                    @endif
                    <h2 class="text-2xl font-bold text-blue-600">
                        {{ $sales_return->business->business_name }}
                    </h2>
                    <div class="mt-2 text-sm">
                        <p>{{ $sales_return->business->address }}</p>
                        <p>{{ $sales_return->business->business_email }}</p>
                        <p>{{ $sales_return->business->phone }}</p>
                    </div>
                </div>
                <div class="sm:text-right">
                    <h1 class="text-2xl font-bold">{{ $sales_return->title }}</h1>
                    <div class="mt-2 text-sm">
                        <p><span class="font-medium">Sales Return #:</span> {{ $sales_return->return_number }}</p>
                        <p><span class="font-medium">Return Date:</span> {{ $sales_return->return_date }}</p>
                        @if($sales_return->type)
                            <p><span class="font-medium">Type:</span> {{ ucfirst($sales_return->type) }}</p>
                        @endif
                        @if($sales_return->paid > 0)
                            <div class="mt-2">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800 border border-green-200">
                                    Refunded: {{ formatCurrency($sales_return->paid) }}
                                </span>
                            </div>
                        @endif
                    </div>
                    <div class="mt-4 sm:flex sm:justify-end">
                        <!-- QR Code -->
                        <div class="w-24 h-24 border-2 border-gray-300 border-dashed flex items-center justify-center text-xs text-gray-500">
                            {!! QrCode::size(200)->generate(route('sales_returns.show_public_sales_return', $sales_return->short_code)) !!}
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-6 border-gray-200">

            <!-- Customer Information -->
            <div class="mb-8">
                <h3 class="font-medium text-lg mb-2">Returned From:</h3>
                <div class="text-sm">
                    <p class="font-medium">{{ $sales_return->customer->name ?? 'N/A' }}</p>
                    @if(isset($sales_return->customer->company_name) && $sales_return->customer->company_name)
                        <p>{{ $sales_return->customer->company_name }}</p>
                    @endif
                    <p>{{ $sales_return->customer->address ?? '' }}</p>
                    <p>{{ $sales_return->customer->email ?? '' }}</p>
                    <p>{{ $sales_return->customer->mobile ?? '' }}</p>
                </div>
            </div>

            <!-- Sales Return Items -->
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
                        @foreach($sales_return->items as $item)
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

            <!-- Sales Return Summary -->
            <div class="flex justify-end">
                <div class="w-full md:w-1/2 lg:w-1/3 space-y-2">
                    <div class="flex justify-between py-2 border-t border-gray-200">
                        <span class="font-medium">Subtotal:</span>
                        <span>{{ formatCurrency($sales_return->sub_total) }}</span>
                    </div>

                    <!-- Tax details -->
                    @foreach($sales_return->taxes as $tax)
                        <div class="flex justify-between py-2">
                            <span>{{ $tax->name }}:</span>
                            <span>{{ formatCurrency($tax->amount) }}</span>
                        </div>
                    @endforeach

                    <!-- Discount -->
                    @if($sales_return->discount > 0)
                        <div class="flex justify-between py-2">
                            <span>Discount:</span>
                            <span>-{{ formatCurrency($sales_return->discount) }}</span>
                        </div>
                    @endif

                    <!-- Total -->
                    <div class="flex justify-between py-3 border-t border-b border-gray-200 font-bold text-lg">
                        <span>Total:</span>
                        <span>{{ formatCurrency($sales_return->grand_total) }}</span>
                    </div>

                    <!-- Base currency equivalent if different currency -->
                    @if($sales_return->currency !== $sales_return->business->currency)
                        <div class="flex justify-between py-2 text-gray-500 text-sm">
                            <span>Exchange Rate:</span>
                            <span>
                                1 {{ $sales_return->business->currency }} = {{ formatCurrency($sales_return->exchange_rate) }}
                            </span>
                        </div>
                    @endif

                    <!-- Base currency equivalent total -->
                    @if($sales_return->currency !== $sales_return->business->currency)
                        <div class="flex justify-between py-2 text-sm text-gray-600">
                            <span>Equivalent to:</span>
                            <span>
                                {{ formatCurrency($sales_return->converted_total) }}
                            </span>
                        </div>
                    @endif

                    <!-- Refunded Amount -->
                    @if($sales_return->paid > 0)
                        <div class="flex justify-between py-2 text-green-600">
                            <span>Refunded:</span>
                            <span>{{ formatCurrency($sales_return->paid) }}</span>
                        </div>
                    @endif

                    <!-- Due Amount -->
                    @if($sales_return->grand_total - $sales_return->paid > 0)
                        <div class="flex justify-between py-2 text-red-600">
                            <span>Due:</span>
                            <span>{{ formatCurrency($sales_return->grand_total - $sales_return->paid) }}</span>
                        </div>
                    @endif
                </div>
            </div>

            <!-- Notes & Terms -->
            @if($sales_return->note || $sales_return->footer)
                <div class="mt-8 space-y-4">
                    @if($sales_return->note)
                        <div>
                            <h3 class="font-medium mb-1">Notes:</h3>
                            <p class="text-sm">{{ $sales_return->note }}</p>
                        </div>
                    @endif

                    @if($sales_return->footer)
                        <div>
                            <h3 class="font-medium mb-1">Terms & Conditions:</h3>
                            <p class="text-sm">{{ $sales_return->footer }}</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
@endsection


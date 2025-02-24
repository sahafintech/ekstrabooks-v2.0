<x-guest-layout>
    <div class="flex items-center justify-center max-w-sm mx-auto bg-white p-3">
        <div>
            <div class="mb-2 grid grid-cols-12 gap-x-2">
                <x-secondary-button class="print col-span-6" data-print="invoice-pos">
                    print
                </x-secondary-button>
                <!-- back -->
                <a href="{{ route('receipts.pos') }}" class="col-span-6">
                    <x-secondary-button class="w-full">
                        Back
                    </x-secondary-button>
                </a>
            </div>
            <div id="invoice-pos">
                <div>
                    <div class="text-[11px] leading-4">
                        <div class="relative">
                            <h2 class="font-semibold text-2xl uppercase underline">{{ request()->activeBusiness->name }}</h2>

                            @if(package()->medical_record == 1 && package()->prescription == 1)
                            @if($invoice->queue_number && $invoice->queue_number !== 0)
                            <h2 class="text-7xl absolute right-0 top-0">{{ $invoice->queue_number }}</h2>
                            @endif
                            @endif
                        </div>

                        <p>
                            <span>Date: {{ $invoice->invoice_date }}<br></span>
                            <span>Invoice Number : {{ $invoice->invoice_number }} <br></span>
                            <span>Address : {{ request()->activeBusiness->address }}
                                <br></span>
                            <span>Email : {{ request()->activeBusiness->email }} <br></span>
                            <span>Phone : {{ request()->activeBusiness->phone }}
                                <br></span>
                        <p class="font-semibold uppercase">Billing Details</p>
                        <span>Name : {{ $invoice->customer?->name }}</span><br>
                        <span>Address : {{ $invoice->customer?->email }}</span><br>
                        <span>{{ $invoice->customer?->city }}, {{ $invoice->customer?->country }}</span><br>
                        </p>

                        @if($invoice->client_id != '' && ($invoice->client_id != $invoice->customer_id))
                        <p class="font-semibold uppercase">Client Details</p>
                        <p>
                            <span>Client : {{ $invoice->client->name }}</span><br>
                            <span>Address : {{ $invoice->client->email }}</span><br>
                            <span>Contract NO: {{ $invoice->client->contract_no }}</span><br>
                            <span>{{ $invoice->client->city }}, {{ $invoice->client->country }}</span><br>
                        </p>
                        @endif
                    </div>

                    <table class="ti-custom-table ti-custom-table-head whitespace-nowrap mt-3">
                        <tbody>
                            @foreach($invoice->items as $item)
                            <tr>
                                <td colspan="3" class="!text-[11px]">
                                    {{ $item->product->name }}
                                    <br>
                                    <span>
                                        {{ $item->quantity }} x {{ formatAmount($item->unit_cost, currency_symbol(request()->activeBusiness->currency), $invoice->business_id) }}
                                    </span>
                                </td>
                                <td class="text-right !text-[11px]">
                                    <b>{{ formatAmount($item->sub_total, currency_symbol(request()->activeBusiness->currency), $invoice->business_id) }}</b>
                                </td>
                            </tr>
                            @endforeach

                            @foreach($invoice->taxes as $tax)
                            <tr class="mt-10">
                                <td colspan="3" class="!text-[11px]"><b>{{ $tax->name }}</b></td>
                                <td class="text-right !text-[11px]">
                                    <b>{{ formatAmount($tax->amount, currency_symbol(request()->activeBusiness->currency), $invoice->business_id) }}</b>
                                </td>
                            </tr>
                            @endforeach

                            @if($invoice->discount > 0)
                            <tr class="mt-10">
                                <td colspan="3" class="!text-[11px]"><b>Discount</b></td>
                                <td class="text-right !text-[11px]">
                                    <b>{{ formatAmount($invoice->discount, currency_symbol(request()->activeBusiness->currency), $invoice->business_id) }}</b>
                                </td>
                            </tr>
                            @endif

                            <tr class="mt-10">
                                <td colspan="3" class="!text-[11px]"><b>Grand Total</b></td>
                                <td class="text-right !text-[11px]">
                                    <b>{{ formatAmount($invoice->grand_total, currency_symbol(request()->activeBusiness->currency), $invoice->business_id) }}</b>
                                </td>
                            </tr>

                            @if($invoice->grand_total !== $invoice->converted_total)
                            <tr class="mt-10">
                                <td colspan="3" class="!text-[11px]"><b>Converted Total</b></td>
                                <td class="text-right !text-[11px]">
                                    <b>{{ formatAmount($invoice->converted_total, currency_symbol($invoice->currency), $invoice->business_id) }}</b>
                                </td>
                            </tr>
                            @endif
                        </tbody>
                    </table>

                    <p class="!text-[11px]">
                        <strong>Thank You For Shopping With Us Please Come Again</strong>
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-guest-layout>
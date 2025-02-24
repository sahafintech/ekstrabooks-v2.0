<x-app-layout>
    <!-- Start::main-content -->
    <div class="main-content">
        <x-page-header title="Payment Voucher" page="user" subpage="view" />

        <!-- <link rel="stylesheet" href="{{ asset('/backend/assets/css/invoice.css?v=1.0') }}"> -->
        <div class="grid grid-cols-12">
            <div class="col-span-12">
                <!-- Default Invoice template -->
                <div class="box">
                    <div class="box-body">
                        <div class="flex items-center justify-end py-3">
                            <div class="hs-dropdown ti-dropdown">
                                <button id="hs-dropdown-with-icons" type="button" class="hs-dropdown-toggle ti-dropdown-toggle">
                                    Actions
                                    <svg class="hs-dropdown-open:rotate-180 ti-dropdown-caret" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                        <path d="M2 5L8.16086 10.6869C8.35239 10.8637 8.64761 10.8637 8.83914 10.6869L15 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    </svg>
                                </button>

                                <div class="hs-dropdown-menu ti-dropdown-menu divide-y divide-gray-200" aria-labelledby="hs-dropdown-with-icons">
                                    <div class="ti-dropdown-divider">
                                        <a href="#" class="ti-dropdown-item print" data-print="invoice">
                                            <i class="ri-printer-line text-lg"></i>
                                            {{ _lang('Print Voucher') }}
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div id="invoice">
                            <div>
                                <div>
                                    <div class="grid grid-cols-12 items-center">
                                        <div class="col-span-6 float-left">
                                            <img class="w-28" src="{{ asset('/uploads/media/' . request()->activeBusiness->logo) }}">
                                            <h2 class="text-3xl font-bold">Payment Voucher</h2>
                                        </div>
                                        <div class="col-span-6 float-right sm:text-right right">
                                            <h4 class="text-2xl font-bold">{{ request()->activeBusiness->name }}</h4>
                                            <p>{{ request()->activeBusiness->address }}</p>
                                            <p>{{ request()->activeBusiness->phone }}</p>
                                            <p>{{ request()->activeBusiness->email }}</p>
                                            <p>{{ request()->activeBusiness->country }}</p>
                                        </div>
                                        <div class="clear"></div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-12 items-center mt-10">
                                    <div class="table-bordered rounded-sm ti-custom-table-head overflow-auto col-span-12">
                                        <table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
                                            <tr>
                                                <td colspan="2"><span class="text-lg font-bold">Bill Payment</span></td>
                                            </tr>
                                            <tr>
                                                <td>{{ _lang('Bill #') }}</td>
                                                <td>{{ $bill->bill_no }}</td>
                                            </tr>
                                            <tr>
                                                <td>{{ _lang('Date') }}</td>
                                                <td>{{ $bill->purchase_date }}</td>
                                            </tr>
                                            <tr>
                                                <td>{{ _lang('Credited Account') }}</td>
                                                <td>{{ $credit_transaction->account->account_name }}</td>
                                            </tr>
                                        </table>
                                    </div>
                                </div>

                                <div class="mt-10 mb-5">
                                    <div class="grid grid-cols-12 items-bottom">
                                        <div class="col-span-6 float-left">
                                            <h5>{{ _lang('PAID TO') }}</h5>

                                            <h4>{{ $bill->vendor?->name }}</h4>
                                            <p>{{ $bill->vendor?->address }}</<p>
                                            <p>{{ $bill->vendor?->zip }}</<p>
                                            <p>{{ $bill->vendor?->city }}</<p>
                                            <p>{{ $bill->vendor?->country }}</p>
                                        </div>
                                    </div>
                                </div>

                                <div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
                                    <table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
                                        <thead>
                                            <tr>
                                                <th>{{ _lang('Name') }}</th>
                                                <th>{{ _lang('Quantity') }}</th>
                                                <th>{{ _lang('Price') }}</th>
                                                <th>{{ _lang('Amount Paid') }}</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($bill->items as $item)
                                            <tr>
                                                <td>{{ $item->product_name }}</td>
                                                <td>{{ $item->quantity }}</td>
                                                <td>{{ formatAmount($item->unit_cost, currency_symbol($bill->currency), $bill->business_id) }}</td>
                                                <td>{{ formatAmount($item->sub_total, currency_symbol($bill->currency), $bill->business_id) }}</td>
                                            </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-6">
                                    <div class="grid grid-cols-12">
                                        <div class="col-span-12">
                                            <div class="flex items-center justify-end">
                                                <div class="bg-gray-100 p-4 text-lg rounded-md">
                                                    <p>
                                                        <b class="mr-2">{{ _lang('Total Paid') }}:</b>
                                                        <b>{{ formatAmount($bill->items->sum('sub_total'), currency_symbol(request()->activeBusiness->currency), $bill->business_id) }}</b>
                                                    </p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
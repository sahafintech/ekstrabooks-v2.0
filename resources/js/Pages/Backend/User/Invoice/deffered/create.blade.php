<x-app-layout>
    <!-- Start::main-content -->
    <div class="main-content">
        <x-page-header title="Deferred Invoices" page="user" subpage="create" />

        <form method="post" class="validate" autocomplete="off" action="{{ route('deffered_invoices.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-12 gap-x-2">
                <div class="xl:col-span-9 lg:col-span-8 col-span-12 grid grid-cols-12">
                    <div class="box invoice-form col-span-12">
                        <div class="box-body">
                            <div class="grid grid-cols-8 gap-y-3">
                                <div class="lg:col-span-4 col-span-8">
                                    <div class="invoice-logo">
                                        <img class="w-32" src="{{ asset('/uploads/media/' . request()->activeBusiness->logo) }}" alt="logo">
                                    </div>
                                </div>

                                <div class="lg:col-span-4 col-span-8">
                                    <div class="grid grid-cols-12 gap-y-3">
                                        <div class="col-span-12">
                                            <x-text-input class="text-xl font-bold" type="text" name="title" value="Invoice" placeholder="{{ _lang('Invoice Title') }}" />
                                        </div>

                                        <div class="col-span-12">
                                            <x-text-input type="text" name="order_number" value="{{ old('order_number') }}" placeholder="{{ _lang('Policy Number') }}" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-span-12 my-8">
                                <hr>
                            </div>

                            <div class="grid grid-cols-12 mt-5 space-x-2">
                                <div class="md:col-span-4 col-span-12">
                                    <x-input-label value="{{ _lang('Invoice Category') }}" />
                                    <select class="w-full selectize auto-select" id="invoice_category" data-selected="{{ old('invoice_category') }}" name="invoice_category">
                                        <option value="medical">Medical Insurance Invoice</option>
                                        <option value="gpa">GPA Insurance Invoice</option>
                                        <option value="other">Other Insurance Invoice</option>
                                    </select>
                                </div>
                                <div class="md:col-span-4 col-span-12">
                                    <x-input-label class="xl:col-span-2 col-span-12" value="{{ _lang('Invoice Date') }}" />
                                    <x-text-input type="text" class="flatpickr-input" id="date" name="invoice_date" value="{{ old('invoice_date') }}" placeholder="Select Date" />
                                </div>

                                <div class="md:col-span-4 col-span-12">
                                    <x-input-label value="{{ _lang('Due Date') }}" />
                                    <x-text-input type="text" class="flatpickr-input" id="date" name="due_date" value="{{ old('due_date') }}" placeholder="Select Date" />
                                </div>
                            </div>

                            <div class="grid grid-cols-12 mt-2">
                                <div class="md:col-span-8 col-span-12 select-customer">
                                    <x-input-label value="{{ _lang('Customer') }}" />
                                    <select class="w-full selectize auto-select" data-selected="{{ old('customer_id') }}" name="customer_id">
                                        <option value="">{{ _lang('Select Customer') }}</option>
                                        @foreach(\App\Models\Customer::where('business_id', request()->activeBusiness->id)->get() as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-12 my-8 gap-x-2">
                                <div class="md:col-span-6 col-span-12">
                                    <x-input-label value="{{ _lang('Transaction Currency') }}" />
                                    <select class="w-full selectize auto-select" id="currency" data-selected="{{ old('currency') }}" name="currency">
                                        <option value="">{{ _lang('Select One') }}</option>
                                        {{ get_currency_list() }}
                                    </select>
                                </div>
                                <div class="md:col-span-6 col-span-12 hidden">
                                    <x-input-label value="{{ _lang('Exchange Rate') }}" />
                                    <x-text-input id="exchange_rate" type="number" name="exchange_rate" value="{{ old('exchange_rate') }}" readonly />
                                </div>

                                <!-- product taxes -->
                                <div class="col-span-12 mt-2">
                                    <x-input-label value="{{ _lang('Product Taxes') }}" />
                                    <select name="taxes[][]" class="multi-selector auto-select selectize product_taxes" data-placeholder="{{ _lang('Select Taxes') }}" multiple data-selected="[ {{ is_array(old('tax_amount')) ? implode(',', array_keys(old('tax_amount'))) : old('tax_amount') }} ]">
                                        @foreach(\App\Models\Tax::all() as $tax)
                                        <option value="{{ $tax->id }}" data-tax-rate="{{ $tax->rate }}" data-tax-name="{{ $tax->name }}">{{ $tax->name }} ({{ $tax->rate }})</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="grid grid-cols-12 mt-3">
                                <div class="col-span-12">
                                    <div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
                                        <table id="invoice-table" class="ti-custom-table ti-custom-table-head whitespace-nowrap bg-gray-100">
                                            @if(old('product_id') != null)
                                            @foreach(old('product_id') as $index => $product_id)
                                            @if(old('invoice_category') == 'medical')
                                            <thead>
                                                <tr>
                                                    <th class="input-lg">{{ _lang('Service') }}</th>
                                                    <th class="input-md">{{ _lang('Members') }}</th>
                                                    <th class="input-xs">{{ _lang('Family Size') }}</th>
                                                    <th class="input-xs">{{ _lang('Benefits') }}</th>
                                                    <th class="input-sm text-right">{{ _lang('Limits') }}</th>
                                                    <th class="input-sm text-right">{{ _lang('Rate') }}</th>
                                                    <th class="input-sm text-right">{{ _lang('Premium') }}</th>
                                                    <th class="text-center"><i class="fas fa-minus-circle"></i></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="line-item">
                                                    <td class="input-lg align-top">
                                                        <div class="w-72">
                                                            <select name="product_id[]" class="selectize product_id auto-select" data-selected="{{ old('product_id')[$index] }}">
                                                                <option value="">Select Item</option>
                                                                @foreach(\App\Models\Product::where('type', 'service')->get() as $product)
                                                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </td>
                                                    <td class="align-top input-md">
                                                        <input type="number" class="w-36 quantity" name="quantity[]" value="{{ old('quantity')[$index] }}">
                                                    </td>
                                                    <td class="align-top input-xs text-center">
                                                        <div class="w-36">
                                                            <select name="family_size[]" class="selectize family_size auto-select" data-selected="{{ old('family_size')[$index] }}">
                                                                <option value="">Select One</option>
                                                                @foreach(['M+0', 'M+1', 'M+2', 'M+3', 'M+4', 'M+5', 'M+6', 'M+7', 'M+8', 'M+9'] as $size)
                                                                <option value="{{ $size }}">{{ $size }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </td>
                                                    <td class="align-top input-xs text-center">
                                                        <div class="w-52">
                                                            <select name="benefits[]" class="selectize benefits auto-select" data-selected="{{ old('benefits')[$index] }}">
                                                                <option value="">Select One</option>
                                                                @foreach(['Inpatient', 'Outpatient', 'Maternity', 'Dental', 'Optical'] as $benefit)
                                                                <option value="{{ $benefit }}">{{ $benefit }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </td>
                                                    <td class="align-top input-sm">
                                                        <input type="text" class="w-36 text-right limits" name="limits[]" value="{{ old('limits')[$index] }}">
                                                    </td>
                                                    <td class="align-top input-sm">
                                                        <input type="text" class="w-36 text-right unit_cost" name="unit_cost[]" value="{{ old('unit_cost')[$index] }}">
                                                    </td>
                                                    <td class="align-top input-sm">
                                                        <input type="text" class="w-36 text-right sub_total" name="sub_total[]" value="{{ old('sub_total')[$index] }}" readonly>
                                                    </td>
                                                    <td class="align-top input-xxs text-center">
                                                        <button type="button" class="bg-red-600 btn-remove-row px-1 rounded-md">
                                                            <i class="ri-close-circle-line text-xl text-white"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                            @elseif(old('invoice_category') == 'gpa')
                                            <thead>
                                                <tr>
                                                    <th class="input-lg">{{ _lang('Service') }}</th>
                                                    <th class="input-md">{{ _lang('Quantity') }}</th>
                                                    <th class="input-xs">{{ _lang('Benefits') }}</th>
                                                    <th class="input-sm text-right">{{ _lang('Limits') }}</th>
                                                    <th class="input-sm text-right">{{ _lang('Rate') }}</th>
                                                    <th class="input-sm text-right">{{ _lang('Premium') }}</th>
                                                    <th class="text-center"><i class="fas fa-minus-circle"></i></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="line-item">
                                                    <td class="input-lg align-top">
                                                        <div class="w-72">
                                                            <select name="product_id[]" class="selectize product_id auto-select" data-selected="{{ old('product_id')[$index] }}">
                                                                <option value="">Select Item</option>
                                                                @foreach(\App\Models\Product::where('type', 'service')->get() as $product)
                                                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </td>
                                                    <td class="align-top input-md">
                                                        <input type="number" class="w-36 quantity" name="quantity[]" value="{{ old('quantity')[$index] }}">
                                                    </td>
                                                    <td class="align-top input-xs text-center">
                                                        <div class="w-52">
                                                            <select name="benefits[]" class="selectize benefits auto-select" data-selected="{{ old('benefits')[$index] }}">
                                                                <option value="">Select One</option>
                                                                @foreach(['Temporary Total Disability', 'Permanent Total Disability', 'Medical Expenses', 'Death', 'Up to 2 Yrs Earnings', 'Up to 3 Yrs Earnings'] as $benefit)
                                                                <option value="{{ $benefit }}">{{ $benefit }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </td>
                                                    <td class="align-top input-sm">
                                                        <input type="text" class="w-36 text-right limits" name="limits[]" value="{{ old('limits')[$index] }}">
                                                    </td>
                                                    <td class="align-top input-sm">
                                                        <input type="text" class="w-36 text-right unit_cost" name="unit_cost[]" value="{{ old('unit_cost')[$index] }}">
                                                    </td>
                                                    <td class="align-top input-sm">
                                                        <input type="text" class="w-36 text-right sub_total" name="sub_total[]" value="{{ old('sub_total')[$index] }}" readonly>
                                                    </td>
                                                    <td class="align-top input-xxs text-center">
                                                        <button type="button" class="bg-red-600 btn-remove-row px-1 rounded-md">
                                                            <i class="ri-close-circle-line text-xl text-white"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                            @else
                                            <thead>
                                                <tr>
                                                    <th class="input-lg">{{ _lang('Service') }}</th>
                                                    <th class="input-md">{{ _lang('Quantity') }}</th>
                                                    <th class="input-xs">{{ _lang('Benefits') }}</th>
                                                    <th class="input-sm text-right">{{ _lang('Sum Insured') }}</th>
                                                    <th class="input-sm text-right">{{ _lang('Rate') }}</th>
                                                    <th class="input-sm text-right">{{ _lang('Premium') }}</th>
                                                    <th class="text-center"><i class="fas fa-minus-circle"></i></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr class="line-item">
                                                    <td class="input-lg align-top">
                                                        <div class="w-72">
                                                            <select name="product_id[]" class="selectize product_id auto-select" data-selected="{{ old('product_id')[$index] }}">
                                                                <option value="">Select Item</option>
                                                                @foreach(\App\Models\Product::where('type', 'service')->get() as $product)
                                                                <option value="{{ $product->id }}">{{ $product->name }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </td>
                                                    <td class="align-top input-md">
                                                        <input type="number" class="w-36 quantity" name="quantity[]" value="{{ old('quantity')[$index] }}">
                                                    </td>
                                                    <td class="align-top input-xs text-center">
                                                        <div class="w-52">
                                                            <select name="benefits[]" class="selectize benefits auto-select" data-selected="{{ old('benefits')[$index] }}">
                                                                <option value="">Select One</option>
                                                                @foreach(['Comprehensive', 'Consignment', "Driver's Liability", 'Machineries', 'Property', 'Radio & Casette', 'Third Party', 'Wind Screen'] as $benefit)
                                                                <option value="{{ $benefit }}">{{ $benefit }}</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </td>
                                                    <td class="align-top input-sm">
                                                        <input type="text" class="w-36 text-right sum_insured" name="sum_insured[]" value="{{ old('sum_insured')[$index] }}">
                                                    </td>
                                                    <td class="align-top input-sm">
                                                        <input type="text" class="w-36 text-right unit_cost" name="unit_cost[]" value="{{ old('unit_cost')[$index] }}">
                                                    </td>
                                                    <td class="align-top input-sm">
                                                        <input type="text" class="w-36 text-right sub_total" name="sub_total[]" value="{{ old('sub_total')[$index] }}" readonly>
                                                    </td>
                                                    <td class="align-top input-xxs text-center">
                                                        <button type="button" class="bg-red-600 btn-remove-row px-1 rounded-md">
                                                            <i class="ri-close-circle-line text-xl text-white"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                            </tbody>
                                            @endif
                                            @endforeach
                                            @endif
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <!-- add new row -->
                            <div class="col-span-12 mt-4">
                                <x-secondary-button type="button" id="add_new_row" class="btn-add-row bg-success"><i class="fas fa-plus-circle"></i> {{ _lang('Add New Row') }}</x-secondary-button>
                            </div>

                            <div class="col-span-12 my-8">
                                <hr>
                            </div>

                            <div class="grid grid-cols-12 md:text-right">
                                <div class="xl:col-start-6 col-span-12 space-y-3">
                                    <div class="grid grid-cols-12 gap-x-2" id="before-tax">
                                        <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Sub Total') }}" />
                                        <div class="md:col-span-6 col-span-12">
                                            <x-text-input type="hidden" class="md:text-right border-none" name="sub_total" id="sub_total" value="{{ old('sub_total') }}" readonly />
                                            <span class="md:text-right sub_total_span">
                                                {{ old('sub_total') }}
                                            </span>
                                        </div>
                                    </div>

                                    @if(old('tax_amount') != null)
                                    @foreach(old('tax_amount') as $index => $value)
                                    <div class="grid grid-cols-12 gap-x-2 old-tax" id="after-tax">
                                        <x-input-label class="md:col-span-6 col-span-12" value="{{ \App\Models\Tax::where('id', $index)->first()->name }} {{ \App\Models\Tax::where('id', $index)->first()->rate }}" />
                                        <div class="md:col-span-6 col-span-12">
                                            <x-text-input type="text" class="md:text-right border-none" name="tax[]" id="tax" value="{{ old('tax_amount')[$index] }}" readonly />
                                        </div>
                                    </div>
                                    @endforeach
                                    @endif

                                    <div class="grid grid-cols-12 gap-x-2" id="after-tax">
                                        <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Discount Amount') }}" />
                                        <div class="md:col-span-6 col-span-12">
                                            <x-text-input type="hidden" class="md:text-right border-none" name="discount_amount" id="discount_amount" value="{{ old('discount_amount') }}" readonly />
                                            <span class="md:text-right discount_span">
                                                {{ old('discount_amount') }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-12 gap-x-2">
                                        <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Grand Total') }}" />
                                        <div class="md:col-span-6 col-span-12">
                                            <x-text-input type="hidden" class="md:text-right border-none" name="grand_total" id="grand_total" value="{{ old('grand_total') }}" readonly />
                                            <span class="md:text-right grand_total_span">
                                                {{ old('grand_total') }}
                                            </span>
                                        </div>
                                    </div>

                                    <div class="hidden grid-cols-12 gap-x-2">
                                        <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Converted Total') }}" />
                                        <div class="md:col-span-6 col-span-12">
                                            <x-text-input type="hidden" class="md:text-right border-none" name="converted_total" id="converted_total" value="{{ old ('converted_total') }}" readonly placeholder="0.00" />
                                            <span class="md:text-right converted_total_span">
                                                {{ old('converted_total') }}
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-span-12 my-8">
                                <hr>
                            </div>

                            <!-- deffered dates -->
                            <div class="col-span-12 my-5 grid grid-cols-12 gap-x-2">
                                <div class="col-span-6">
                                    <x-input-label value="{{ _lang('Policy Start Date') }}" />
                                    <x-text-input type="text" class="flatpickr-input deffered_start" id="date" name="deffered_start" value="{{ old('deffered_start') }}" placeholder="Select Date" />
                                </div>

                                <div class="col-span-6">
                                    <x-input-label value="{{ _lang('Policy End Date') }}" />
                                    <x-text-input type="text" class="flatpickr-input deffered_end" id="date" name="deffered_end" value="{{ old('deffered_end') }}" placeholder="Select Date" />
                                </div>

                                <div class="col-span-6 mt-2">
                                    <x-input-label value="{{ _lang('Active Days') }}" />
                                    <x-text-input type="text" name="active_days" value="{{ old('active_days') }}" readonly id="active_days" />
                                </div>

                                <!-- cost per day -->
                                <div class="col-span-6 mt-2">
                                    <x-input-label value="{{ _lang('Cost Per Day') }}" />
                                    <x-text-input type="number" name="cost_per_day" id="cost_per_day" value="{{ old('cost_per_day') }}" readonly />
                                </div>
                            </div>

                            <p class="font-bold bg-gray-100 p-2">Deferred Earning Schedule</p>

                            <div class="col-span-12">
                                <div class="table-bordered rounded-sm ti-custom-table-head overflow-x-scroll">
                                    <table class="ti-custom-table ti-custom-table-head whitespace-nowrap bg-gray-100" id="earnings">
                                        <thead class="bg-white">
                                            <th>{{ _lang('Earning Start Date') }}</th>
                                            <th>{{ _lang('Earning End Date') }}</th>
                                            <th>{{ _lang('NO.Days') }}</th>
                                            <th>{{ _lang('Earning Recongnized') }}</th>
                                        </thead>
                                        <tbody>
                                            @if(is_array(old('earnings')) && isset(old('earnings')['amount']))
                                            @foreach(old('earnings')['start_date'] as $key => $value)
                                            <tr>
                                                <td>
                                                    <x-text-input type="text" class="flatpickr revenue_start_date" id="date" name="earnings[start_date][]" value="{{ old('earnings')['start_date'][$key] }}" placeholder="{{ _lang('Date') }}" />
                                                </td>
                                                <td>
                                                    <x-text-input type="text" class="flatpickr revenue_end_date" id="date" name="earnings[end_date][]" value="{{ old('earnings')['end_date'][$key] }}" placeholder="{{ _lang('Date') }}" />
                                                </td>
                                                <td>
                                                    <x-text-input type="number" id="days" name="earnings[days][]" value="{{ old('earnings')['days'][$key] }}" placeholder="{{ _lang('Days') }}" readonly />
                                                </td>
                                                <td>
                                                    <x-text-input type="number" step="any" id="amount" name="earnings[amount][]" value="{{ old('earnings')['amount'][$key] }}" placeholder="{{ _lang('Amount') }}" readonly />
                                                </td>
                                            </tr>
                                            @endforeach
                                            @else
                                            <tr>
                                                <td>
                                                    <x-text-input type="text" class="flatpickr revenue_start_date" id="date" name="earnings[start_date][]" placeholder="{{ _lang('Date') }}" />
                                                </td>
                                                <td>
                                                    <x-text-input type="text" class="flatpickr revenue_end_date" id="date" name="earnings[end_date][]" placeholder="{{ _lang('Date') }}" />
                                                </td>
                                                <td>
                                                    <x-text-input type="number" id="days" name="earnings[days][]" placeholder="{{ _lang('Days') }}" readonly />
                                                </td>
                                                <td>
                                                    <x-text-input type="number" step="any" id="amount" name="earnings[amount][]" placeholder="{{ _lang('Amount') }}" readonly />
                                                </td>
                                            </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                                <div class="md:flex md:justify-between items-center mt-7 bg-gray-200 p-3 rounded-md">
                                    <div></div>
                                    <div class="grid grid-cols-12 items-center space-x-2">
                                        <x-input-label class="md:col-span-3 col-span-12 font-bold !mb-0" value="Total Amount" />

                                        <div class="md:col-span-9 col-span-12 md:text-right">
                                            <x-text-input type="hidden" name="total_amount" id="total_amount" value="{{ isset(old('earnings')['amount']) ? array_sum(old('earnings')['amount']) : 0 }}" />
                                            <span id="total_amount_span">{{ isset(old('earnings')['amount']) ? array_sum(old('earnings')['amount']) : 0 }}</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- <div class="col-span-12 my-8">
                                <hr>
                            </div>

                            <p class="font-bold bg-gray-100 p-2">Deferred Payment Schedule</p>

                            <div class="col-span-12">
                                <div class="table-bordered rounded-sm ti-custom-table-head overflow-x-scroll">
                                    <table class="ti-custom-table ti-custom-table-head whitespace-nowrap bg-gray-100" id="payments">
                                        <thead class="bg-white">
                                            <th>{{ _lang('Payment Date') }}</th>
                                            <th>{{ _lang('Payment Due Date') }}</th>
                                            <th>{{ _lang('amount') }}</th>
                                            <th>{{ _lang('Action') }}</th>
                                        </thead>
                                        <tbody>
                                            @if(is_array(old('payments')) && isset(old('payments')['amount']))
                                            @foreach(old('payments')['date'] as $key => $value)
                                            <tr>
                                                <td>
                                                    <x-text-input type="text" class="flatpickr" id="date" name="payments[date][]" value="{{ old('payments')['date'][$key] }}" placeholder="{{ _lang('Date') }}" />
                                                </td>
                                                <td>
                                                    <x-text-input type="text" class="flatpickr" id="date" name="payments[due_date][]" value="{{ old('payments')['due_date'][$key] }}" placeholder="{{ _lang('Date') }}" />
                                                </td>
                                                <td>
                                                    <x-text-input type="number" step="any" id="amount" name="payments[amount][]" value="{{ old('payments')['amount'][$key] }}" placeholder="{{ _lang('Amount') }}" />
                                                </td>
                                                <td class="text-center !p-[7px]">
                                                    <button class="text-red-600 text-lg rounded-md remove-item"><i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            @endforeach
                                            @else
                                            <tr>
                                                <td>
                                                    <x-text-input type="text" class="flatpickr" id="date" name="payments[date][]" placeholder="{{ _lang('Date') }}" />
                                                </td>
                                                <td>
                                                    <x-text-input type="text" class="flatpickr" id="date" name="payments[due_date][]" placeholder="{{ _lang('Date') }}" />
                                                </td>
                                                <td>
                                                    <x-text-input type="number" step="any" id="amount" name="payments[amount][]" placeholder="{{ _lang('Amount') }}" />
                                                </td>
                                                <td class="text-center !p-[7px]">
                                                    <button class="text-red-600 text-lg rounded-md remove-payment"><i class="ri-delete-bin-line"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            @endif
                                        </tbody>
                                    </table>
                                </div>
                                <div class="md:flex md:justify-between items-center mt-7 bg-gray-200 p-3 rounded-md">
                                    <div>
                                        <x-primary-button type="button" id="add-payment" class="bg-blue-400 text-white hover:bg-blue-500">
                                            <i class="ri-add-line"></i>
                                            Add New Payment
                                        </x-primary-button>
                                    </div>
                                </div>
                            </div>

                            <div class="col-span-12 my-8">
                                <hr>
                            </div> -->

                            <div class="col-span-12 my-8">
                                <hr>
                            </div>

                            <div class="grid grid-cols-12">
                                <!-- table attachments -->
								<div class="col-span-12 grid grid-cols-12">
									<h1 class="text-xl font-bold mb-4 col-span-12">Attachments</h1>
									<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto col-span-12">
										<table class="ti-custom-table ti-custom-table-head whitespace-nowrap bg-gray-100" id="attachments-table">
											<thead>
												<tr class="bg-gray-200">
													<th>#</th>
													<th>File Name</th>
													<th>Attachment</th>
													<th>Actions</th>
												</tr>
											</thead>
											<tbody id="attachmentTable">
												@if(old('attachments.file_name') != null)
												@foreach(old('attachments.file_name') as $index => $value)
												<tr>
													<td class="border border-gray-300 px-4 py-2">{{ $index + 1 }}</td>
													<td class="border border-gray-300 px-4 py-2">
														<input type="text" name="attachments[file_name][]" value="{{ $value }}" placeholder="Enter file name" class="w-full px-2 py-1 border rounded">
													</td>
													<td class="border border-gray-300 px-4 py-2">
														<input type="file" name="attachments[file][]" class="dropify" data-allowed-file-extensions="pdf jpg jpeg png doc docx xls xlsx" data-max-file-size="2M" data-default-file="{{ old('attachment') }}" data-height="50" />
													</td>
													<td class="border border-gray-300 px-4 py-2">
														<button class="text-red-500 hover:text-red-700" type="button" onclick="removeAttachmentRow(this)">Remove</button>
													</td>
												</tr>
												@endforeach
												@else
												<tr>
													<td class="border border-gray-300 px-4 py-2">1</td>
													<td class="border border-gray-300 px-4 py-2">
														<input type="text" name="attachments[file_name][]" placeholder="Enter file name" class="w-full px-2 py-1 border rounded">
													</td>
													<td class="border border-gray-300 px-4 py-2">
														<input type="file" name="attachments[file][]" class="dropify" data-allowed-file-extensions="pdf jpg jpeg png doc docx xls xlsx" data-max-file-size="2M" data-height="50" />
													</td>
													<td class="border border-gray-300 px-4 py-2">
														<button class="text-red-500 hover:text-red-700" type="button" onclick="removeAttachmentRow(this)">Remove</button>
													</td>
												</tr>
												@endif
											</tbody>
										</table>
									</div>
									<div class="col-span-12 my-4">
										<x-secondary-button type="button" onclick="addAttachmentRow()">Add Attachment Row</x-secondary-button>
									</div>
								</div>

                                <div class="col-span-12">
                                    <x-input-label value="{{ _lang('Notes') }}" />
                                    <textarea class="w-full" name="note">{{ old('note') }}</textarea>
                                </div>

                                <div class="col-span-12">
                                    <x-input-label value="{{ _lang('Footer') }}" />
                                    <textarea class="w-full" name="footer">{!! xss_clean(get_business_option('invoice_footer', old('footer'))) !!}</textarea>
                                </div>

                                <div class="col-span-12 mt-4">
                                    <x-primary-button type="submit" class="submit-btn">{{ _lang('Save Invoice') }}</x-primary-button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="xl:col-span-3 lg:col-span-4 col-span-12">
                    <div class="box">
                        <div class="box-body">
                            <div class="grid grid-cols-12">
                                <div class="col-span-12">
                                    <x-input-label value="{{ _lang('Invoice Template') }}" />
                                    <select class="w-full selectize" data-selected="{{ old('template', 'default') }}" name="template">
                                        <option value="default">{{ _lang('Default') }}</option>
                                        @foreach(\App\Models\InvoiceTemplate::where('type', 'invoice')->get() as $template)
                                        <option value="{{ $template->id }}">{{ $template->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="col-span-12 mt-3">
                                    <x-input-label value="{{ _lang('Discount Value') }}" />
                                    <div class="col-span-12 grid grid-cols-12">
                                        <div class="col-span-3">
                                            <select class="w-full discount_type" id="discount_type" name="discount_type" value="{{ old('discount_type') }}">
                                                <option value="0">%</option>
                                                <option value="1">{{ currency_symbol(request()->activeBusiness->currency) }}</option>
                                            </select>
                                        </div>
                                        <input type="number" step="any" class="col-span-9" name="discount_value" id="discount_value" value="{{ old('discount_value',0) }}">
                                    </div>
                                </div>

                                <div class="col-span-12 mt-4">
                                    <x-primary-button type="submit" class="submit-btn">{{ _lang('Save Invoice') }}</x-primary-button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="box">
                        <div class="box-body">
                            <div class="grid grid-cols-12">
                                <div class="col-span-12 flex justify-center space-x-2">
                                    <a href="{{ route('insurance_benefits.index') }}" class="ajax-modal" data-hs-overlay="#benefits-family-sizes-modal">
                                        <x-secondary-button>Benefit</x-secondary-button>
                                    </a>
                                    <a href="{{ route('insurance_family_sizes.index') }}" class="ajax-modal" data-hs-overlay="#benefits-family-sizes-modal">
                                        <x-secondary-button>Family Size</x-secondary-button>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <div id="benefits-family-sizes-modal" class="hs-overlay hidden overflow-y-auto ti-offcanvas ti-offcanvas-right px-10" tabindex="-1">
            <div class="ti-offcanvas-header">
                <h3 class="ti-offcanvas-title">
                    Benefits
                </h3>
                <button type="button" class="ti-btn flex-shrink-0 h-8 w-8 p-0 transition-none text-gray-500 hover:text-gray-700 focus:ring-gray-400 focus:ring-offset-white dark:text-white/70 dark:hover:text-white/80 dark:focus:ring-white/10 dark:focus:ring-offset-white/10" data-hs-overlay="#benefits-family-sizes-modal">
                    <span class="sr-only">Close modal</span>
                    <svg class="w-3.5 h-3.5" width="8" height="8" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.258206 1.00652C0.351976 0.912791 0.479126 0.860131 0.611706 0.860131C0.744296 0.860131 0.871447 0.912791 0.965207 1.00652L3.61171 3.65302L6.25822 1.00652C6.30432 0.958771 6.35952 0.920671 6.42052 0.894471C6.48152 0.868271 6.54712 0.854471 6.61352 0.853901C6.67992 0.853321 6.74572 0.865971 6.80722 0.891111C6.86862 0.916251 6.92442 0.953381 6.97142 1.00032C7.01832 1.04727 7.05552 1.1031 7.08062 1.16454C7.10572 1.22599 7.11842 1.29183 7.11782 1.35822C7.11722 1.42461 7.10342 1.49022 7.07722 1.55122C7.05102 1.61222 7.01292 1.6674 6.96522 1.71352L4.31871 4.36002L6.96522 7.00648C7.05632 7.10078 7.10672 7.22708 7.10552 7.35818C7.10442 7.48928 7.05182 7.61468 6.95912 7.70738C6.86642 7.80018 6.74102 7.85268 6.60992 7.85388C6.47882 7.85498 6.35252 7.80458 6.25822 7.71348L3.61171 5.06702L0.965207 7.71348C0.870907 7.80458 0.744606 7.85498 0.613506 7.85388C0.482406 7.85268 0.357007 7.80018 0.264297 7.70738C0.171597 7.61468 0.119017 7.48928 0.117877 7.35818C0.116737 7.22708 0.167126 7.10078 0.258206 7.00648L2.90471 4.36002L0.258206 1.71352C0.164476 1.61976 0.111816 1.4926 0.111816 1.36002C0.111816 1.22744 0.164476 1.10028 0.258206 1.00652Z" fill="currentColor" />
                    </svg>
                </button>
            </div>
            <div class="ti-modal-body hidden mt-10" id="canvas_spinner">
                <div class="text-center spinner">
                    <div class="ti-spinner text-primary w-16 h-16" role="status" aria-label="loading"> <span class="sr-only">Loading...</span> </div>
                </div>
            </div>
            <div id="main-canvas">

            </div>
        </div>

        <table class="ti-custom-table ti-custom-table-head whitespace-nowrap hidden">
            <tr class="line-item" id="copy-line-medical">
                <td class="input-lg align-top">
                    <div class="w-72">
                        <select name="product_id[]" class="selectize product_id auto-select">
                            <option value="">Select Item</option>
                            @foreach(\App\Models\Product::where('business_id', request()->activeBusiness->id)->get() as $product)
                            <option value="{{ $product->id }}">
                                {{ $product->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </td>
                <td class="align-top input-md">
                    <input type="number" class="w-36 quantity" name="quantity[]">
                </td>
                <!-- select family size -->
                <td class="align-top input-xs text-center">
                    <div class="w-36">
                        <select name="family_size[]" class="selectize family_size">
                            <option value="">Select One</option>
                            @foreach(\App\Models\InsuranceFamilySize::where('business_id', request()->activeBusiness->id)->get() as $familySize)
                            <option value="{{ $familySize->size }}">
                                {{ $familySize->size }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </td>
                <!-- select benefits -->
                <td class="align-top input-xs text-center">
                    <div class="w-52">
                        <select name="benefits[]" class="selectize benefits auto-select">
                            <option value="">Select One</option>
                            @foreach(\App\Models\InsuranceBenefit::where('invoice_category', 'Medical')->get() as $benefit)
                            <option value="{{ $benefit->name }}">
                                {{ $benefit->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </td>
                <td class="align-top input-sm">
                    <input type="text" class="w-36 text-right limits" name="limits[]">
                </td>
                <td class="align-top input-sm">
                    <input type="text" class="w-36 text-right unit_cost" name="unit_cost[]">
                </td>
                <td class="align-top input-sm">
                    <input type="text" class="w-36 text-right sub_total" name="sub_total[]" readonly>
                </td>
                <td class="align-top input-xxs text-center">
                    <button type="button" class="bg-red-600 btn-remove-row px-1 rounded-md">
                        <i class="ri-close-circle-line text-xl text-white"></i>
                    </button>
                </td>
            </tr>
        </table>

        <table class="ti-custom-table ti-custom-table-head whitespace-nowrap hidden">
            <tr class="line-item" id="copy-line-gpa">
                <td class="input-lg align-top">
                    <div class="w-72">
                        <select name="product_id[]" class="selectize product_id auto-select">
                            <option value="">Select Item</option>
                            @foreach(\App\Models\Product::where('business_id', request()->activeBusiness->id)->get() as $product)
                            <option value="{{ $product->id }}">
                                {{ $product->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </td>
                <td class="align-top input-md">
                    <input type="number" class="w-36 quantity" name="quantity[]">
                </td>
                <!-- select benefits -->
                <td class="align-top input-xs text-center">
                    <div class="w-52">
                        <select name="benefits[]" class="selectize benefits auto-select">
                            <option value="">Select One</option>
                            @foreach(\App\Models\InsuranceBenefit::where('invoice_category', 'GPA')->get() as $benefit)
                            <option value="{{ $benefit->name }}">
                                {{ $benefit->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </td>
                <td class="align-top input-sm">
                    <input type="text" class="w-36 text-right limits" name="limits[]">
                </td>
                <td class="align-top input-sm">
                    <input type="text" class="w-36 text-right unit_cost" name="unit_cost[]">
                </td>
                <td class="align-top input-sm">
                    <input type="text" class="w-36 text-right sub_total" name="sub_total[]" readonly>
                </td>
                <td class="align-top input-xxs text-center">
                    <button type="button" class="bg-red-600 btn-remove-row px-1 rounded-md">
                        <i class="ri-close-circle-line text-xl text-white"></i>
                    </button>
                </td>
            </tr>
        </table>

        <table class="ti-custom-table ti-custom-table-head whitespace-nowrap hidden">
            <tr class="line-item" id="copy-line-other">
                <td class="input-lg align-top">
                    <div class="w-72">
                        <select name="product_id[]" class="selectize product_id auto-select">
                            <option value="">Select Item</option>
                            @foreach(\App\Models\Product::where('business_id', request()->activeBusiness->id)->get() as $product)
                            <option value="{{ $product->id }}">
                                {{ $product->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </td>
                <td class="align-top input-md">
                    <input type="number" class="w-36 quantity" name="quantity[]">
                </td>
                <!-- select benefits -->
                <td class="align-top input-xs text-center">
                    <div class="w-52">
                        <select name="benefits[]" class="selectize benefits">
                            <option value="">Select One</option>
                            @foreach(\App\Models\InsuranceBenefit::where('invoice_category', 'Other')->get() as $benefit)
                            <option value="{{ $benefit->name }}">
                                {{ $benefit->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </td>
                <td class="align-top input-sm">
                    <input type="text" class="w-36 text-right sum_insured" name="sum_insured[]">
                </td>
                <td class="align-top input-sm">
                    <input type="text" class="w-36 text-right unit_cost" name="unit_cost[]">
                </td>
                <td class="align-top input-sm">
                    <input type="text" class="w-36 text-right sub_total" name="sub_total[]" readonly>
                </td>
                <td class="align-top input-xxs text-center">
                    <button type="button" class="bg-red-600 btn-remove-row px-1 rounded-md">
                        <i class="ri-close-circle-line text-xl text-white"></i>
                    </button>
                </td>
            </tr>
        </table>
    </div>
</x-app-layout>

<script src="{{ asset('/backend/assets/js/deffered-invoice.js?v=1.2') }}"></script>

<script>
    $(document).on('click', '#add-payment', function() {
        $("#payments tbody").append(
            `<tr>
                <td>
                    <x-text-input type="text" class="flatpickr" id="date" name="payments[date][]" placeholder="{{ _lang('Date') }}" />
                </td>
                <td>
                    <x-text-input type="text" class="flatpickr" id="date" name="payments[due_date][]" placeholder="{{ _lang('Date') }}" />
                </td>
                <td>
                    <x-text-input type="number" step="any" id="amount" name="payments[amount][]" placeholder="{{ _lang('Amount') }}" />
                </td>
                <td class="text-center !p-[7px]">
                    <button class="text-red-600 text-lg rounded-md remove-item"><i class="ri-delete-bin-line"></i>
                    </button>
                </td>
            </tr>`
        );

        flatpickr("#date", {
            dateFormat: "d-m-Y",
        });
    });

    $(document).on('change', '#invoice_category', function() {
        $('#invoice-table').html('');
        if ($('#invoice_category').val() == 'medical') {
            $('#invoice-table').append(`
                <thead>
                    <tr>
                        <th class="input-lg">{{ _lang('Service') }}</th>
                        <th class="input-md">{{ _lang('Members') }}</th>
                        <th class="input-xs">{{ _lang('Family Size') }}</th>
                        <th class="input-xs">{{ _lang('Benefits') }}</th>
                        <th class="input-sm text-right">{{ _lang('Limits') }}</th>
                        <th class="input-sm text-right">{{ _lang('Rate') }}</th>
                        <th class="input-sm text-right">{{ _lang('Premium') }}</th>
                        <th class="text-center"><i class="fas fa-minus-circle"></i></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="line-item">
                        <td class="input-lg align-top">
                            <div class="w-72">
                                <select name="product_id[]" class="selectize product_id auto-select">
                                    <option value="">Select Item</option>
                                    @foreach(\App\Models\Product::where('type', 'service')->get() as $product)
                                    <option value="{{ $product->id }}">
                                        {{ $product->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </td>
                        <td class="align-top input-md">
                            <input type="number" class="w-36 quantity" name="quantity[]">
                        </td>
                        <td class="align-top input-xs text-center">
                            <!-- select family size -->
                            <div class="w-36">
                                <select name="family_size[]" class="selectize family_size auto-select">
                                    <option value="">Select One</option>
                                    @foreach(\App\Models\InsuranceFamilySize::where('business_id', request()->activeBusiness->id)->get() as $familySize)
                                    <option value="{{ $familySize->size }}">
                                        {{ $familySize->size }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </td>
                        <td class="align-top input-xs text-center">
                            <!-- select benefits -->
                            <div class="w-52">
                                <select name="benefits[]" class="selectize benefits auto-select">
                                    <option value="">Select One</option>
                                    @foreach(\App\Models\InsuranceBenefit::where('invoice_category', 'Medical')->get() as $benefit)
                                    <option value="{{ $benefit->name }}">
                                        {{ $benefit->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </td>
                        <td class="align-top input-sm">
                            <input type="text" class="w-36 text-right limits" name="limits[]">
                        </td>
                        <td class="align-top input-sm">
                            <input type="text" class="w-36 text-right unit_cost" name="unit_cost[]">
                        </td>
                        <td class="align-top input-sm">
                            <input type="text" class="w-36 text-right sub_total" name="sub_total[]" readonly>
                        </td>
                        <td class="align-top input-xxs text-center">
                            <button type="button" class="bg-red-600 btn-remove-row px-1 rounded-md">
                                <i class="ri-close-circle-line text-xl text-white"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            `);
        } else if ($('#invoice_category').val() == 'gpa') {
            $('#invoice-table').append(`
                <thead>
                    <tr>
                        <th class="input-lg">{{ _lang('Service') }}</th>
                        <th class="input-md">{{ _lang('Quantity') }}</th>
                        <th class="input-xs">{{ _lang('Benefits') }}</th>
                        <th class="input-sm text-right">{{ _lang('Limits') }}</th>
                        <th class="input-sm text-right">{{ _lang('Rate') }}</th>
                        <th class="input-sm text-right">{{ _lang('Premium') }}</th>
                        <th class="text-center"><i class="fas fa-minus-circle"></i></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="line-item">
                        <td class="input-lg align-top">
                            <div class="w-72">
                                <select name="product_id[]" class="selectize product_id auto-select">
                                    <option value="">Select Item</option>
                                    @foreach(\App\Models\Product::where('type', 'service')->get() as $product)
                                    <option value="{{ $product->id }}">
                                        {{ $product->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </td>
                        <td class="align-top input-md">
                            <input type="number" class="w-36 quantity" name="quantity[]">
                        </td>
                        <td class="align-top input-xs text-center">
                            <!-- select benefits -->
                            <div class="w-52">
                                <select name="benefits[]" class="selectize benefits auto-select">
                                    <option value="">Select One</option>
                                    @foreach(\App\Models\InsuranceBenefit::where('invoice_category', 'GPA')->get() as $benefit)
                                    <option value="{{ $benefit->name }}">
                                        {{ $benefit->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </td>
                        <td class="align-top input-sm">
                            <input type="text" class="w-36 text-right limits" name="limits[]">
                        </td>
                        <td class="align-top input-sm">
                            <input type="text" class="w-36 text-right unit_cost" name="unit_cost[]">
                        </td>
                        <td class="align-top input-sm">
                            <input type="text" class="w-36 text-right sub_total" name="sub_total[]" readonly>
                        </td>
                        <td class="align-top input-xxs text-center">
                            <button type="button" class="bg-red-600 btn-remove-row px-1 rounded-md">
                                <i class="ri-close-circle-line text-xl text-white"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            `);
        } else if ($('#invoice_category').val() == 'other') {
            $('#invoice-table').append(`
                <thead>
                    <tr>
                        <th class="input-lg">{{ _lang('Service') }}</th>
                        <th class="input-md">{{ _lang('Quantity') }}</th>
                        <th class="input-xs">{{ _lang('Benefits') }}</th>
                        <th class="input-sm text-right">{{ _lang('Sum Insured') }}</th>
                        <th class="input-sm text-right">{{ _lang('Rate') }}</th>
                        <th class="input-sm text-right">{{ _lang('Premium') }}</th>
                        <th class="text-center"><i class="fas fa-minus-circle"></i></th>
                    </tr>
                </thead>
                <tbody>
                    <tr class="line-item">
                        <td class="input-lg align-top">
                            <div class="w-72">
                                <select name="product_id[]" class="selectize product_id auto-select">
                                    <option value="">Select Item</option>
                                    @foreach(\App\Models\Product::where('type', 'service')->get() as $product)
                                    <option value="{{ $product->id }}">
                                        {{ $product->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </td>
                        <td class="align-top input-md">
                            <input type="number" class="w-36 quantity" name="quantity[]">
                        </td>
                        <td class="align-top input-xs text-center">
                            <!-- select benefits -->
                            <div class="w-52">
                                <select name="benefits[]" class="selectize benefits auto-select">
                                    <option value="">Select One</option>
                                    @foreach(\App\Models\InsuranceBenefit::where('invoice_category', 'Other')->get() as $benefit)
                                    <option value="{{ $benefit->name }}">
                                        {{ $benefit->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>
                        </td>
                        <td class="align-top input-sm">
                            <input type="text" class="w-36 text-right sum_insured" name="sum_insured[]">
                        </td>
                        <td class="align-top input-sm">
                            <input type="text" class="w-36 text-right unit_cost" name="unit_cost[]">
                        </td>
                        <td class="align-top input-sm">
                            <input type="text" class="w-36 text-right sub_total" name="sub_total[]" readonly>
                        </td>
                        <td class="align-top input-xxs text-center">
                            <button type="button" class="bg-red-600 btn-remove-row px-1 rounded-md">
                                <i class="ri-close-circle-line text-xl text-white"></i>
                            </button>
                        </td>
                    </tr>
                </tbody>
            `);
        }
        // initialize selectize
        $('.selectize').select2({
            placeholder: "Select an option",
            allowClear: true,
            width: '100%'
        });
    });

    $(document).on('click', '.remove-payment', function() {
        $(this).parent().parent().remove();
    });

    $(document).on('change', 'input[name="earnings[amount][]"]', function() {
        var total = 0;
        $('input[name="earnings[amount][]"]').each(function() {
            if ($(this).val() != '') {
                total += parseFloat($(this).val());
            }
        });
        total = total.toFixed(2);
        $('#total_amount').val(total);
        $('#total_amount_span').html(total)
    });

    $(document).on('click', '#add_new_row', function() {
        $('.selectize').select2('destroy');

        if ($('#invoice_category').val() == 'medical') {
            $("#invoice-table tbody").append($("#copy-line-medical").clone().removeAttr("id"));
        } else if ($('#invoice_category').val() == 'gpa') {
            $("#invoice-table tbody").append($("#copy-line-gpa").clone().removeAttr("id"));
        } else {
            $("#invoice-table tbody").append($("#copy-line-other").clone().removeAttr("id"));
        }

        // initialize selectize
        $('.selectize').select2({
            placeholder: "Select an option",
            allowClear: true,
            width: '100%'
        });
    });

    $(document).on('change', '.deffered_start, .deffered_end', function() {
        var startDate = $('.deffered_start').val();
        var endDate = $('.deffered_end').val();

        $('#active_days').val(0);
        if (startDate != '' && endDate != '') {
            var start_date = new Date(formatDate(startDate));
            var end_date = new Date(formatDate(endDate));
            var active_days = (end_date - start_date) / 1000 / 60 / 60 / 24 + 1;
            if (active_days > 0) {
                $('#active_days').val(active_days);
                $('#cost_per_day').val((parseFloat($('#sub_total').val()) / active_days).toFixed(4));
            }

            // generate earning schedule apend to earnings table
            var earning_start_date = new Date(start_date);
            var earning_end_date = new Date(end_date);
            var earning_days = (earning_end_date - earning_start_date) / 1000 / 60 / 60 / 24 + 1;
            var earning_amount = parseFloat($('#sub_total').val()) / earning_days;
            $('#earnings tbody').html('');

            // break down into months with exact days of each month
            var months = [];
            var month = earning_start_date.getMonth();
            var year = earning_start_date.getFullYear();
            var days = 0;
            while (earning_start_date <= earning_end_date) {
                if (earning_start_date.getMonth() != month) {
                    months.push({
                        month: month,
                        year: year,
                        days: days
                    });
                    days = 0;
                    month = earning_start_date.getMonth();
                    year = earning_start_date.getFullYear();
                }
                days++;
                earning_start_date.setDate(earning_start_date.getDate() + 1);
            }
            months.push({
                month: month,
                year: year,
                days: days
            });

            months.forEach(function(month) {
                var start_date = new Date(month.year, month.month, 1);
                var end_date = new Date(month.year, month.month, month.days);
                $('#earnings tbody').append(
                    `<tr>
                        <td>
                            <x-text-input type="text" class="flatpickr revenue_start_date" id="date" name="earnings[start_date][]" value="${start_date.getDate()}-${start_date.getMonth() + 1}-${start_date.getFullYear()}" placeholder="{{ _lang('Date') }}" />
                        </td>
                        <td>
                            <x-text-input type="text" class="flatpickr revenue_end_date" id="date" name="earnings[end_date][]" value="${end_date.getDate()}-${end_date.getMonth() + 1}-${end_date.getFullYear()}" placeholder="{{ _lang('Date') }}" />
                        </td>
                        <td>
                            <x-text-input type="number" id="days" name="earnings[days][]" value="${month.days}" placeholder="{{ _lang('Days') }}" readonly />
                        </td>
                        <td>
                            <x-text-input type="number" id="amount" step="any" name="earnings[amount][]" value="${earning_amount}" placeholder="{{ _lang('Amount') }}" readonly />
                        </td>
                    </tr>`
                );

                // revenue_start_date trigger change
                $('.revenue_start_date').trigger('change');
            });
        }

        // flatpickr
        flatpickr("#date", {
            dateFormat: "d-m-Y",
        });
    });


    $(document).on('change', '.revenue_start_date, .revenue_end_date', function() {
        var startDate = $(this).closest('tr').find('.revenue_start_date').val();
        var endDate = $(this).closest('tr').find('.revenue_end_date').val();
        if (startDate != '' && endDate != '') {
            var start_date = new Date(formatDate(startDate));
            var end_date = new Date(formatDate(endDate));
            var days = (end_date - start_date) / 1000 / 60 / 60 / 24 + 1;
            if (days > 0) {
                $(this).closest('tr').find('#days').val(days);
                $(this).closest('tr').find('#amount').val((parseFloat($('#cost_per_day').val()) * days).toFixed(4));
                $(this).closest('tr').find('#amount').trigger('change');
            }
        }
    });

    $(document).on("change", ".product_taxes", function(event) {
        var grandTotal = parseFloat($("#grand_total").val());

        if (grandTotal > 0 && $(".deffered_start").val() != "" && $(".deffered_end").val() != "") {
            $(".deffered_start").trigger("change");
            $(".deffered_end").trigger("change");
        }
    });

    $(document).on("keyup change", ".unit_cost", function() {
        var grandTotal = parseFloat($("#grand_total").val());

        if (grandTotal > 0 && $(".deffered_start").val() != "" && $(".deffered_end").val() != "") {
            $(".deffered_start").trigger("change");
            $(".deffered_end").trigger("change");
        }
    });

    $(document).on("keyup change", ".quantity", function() {
        var grandTotal = parseFloat($("#grand_total").val());

        if (grandTotal > 0 && $(".deffered_start").val() != "" && $(".deffered_end").val() != "") {
            $(".deffered_start").trigger("change");
            $(".deffered_end").trigger("change");
        }
    });

    function formatDate(date) {
        var parts = date.split('-'); // Split the date by '-'
        if (parts.length === 3) {
            return `${parts[1]}-${parts[0]}-${parts[2]}`; // Rearrange the parts to mm-dd-yyyy
        } else {
            return 'Invalid date'; // Handle invalid input
        }
    }

    function addAttachmentRow() {
		var table = document.getElementById("attachments-table");
		var row = table.insertRow();
		var cell1 = row.insertCell(0);
		var cell2 = row.insertCell(1);
		var cell3 = row.insertCell(2);
		var cell4 = row.insertCell(3);

		cell1.innerHTML = table.rows.length;
		cell2.innerHTML = "<input type='text' placeholder='Enter file name' name='attachments[file_name][]' class='w-full px-2 py-1 border rounded'>";
		cell3.innerHTML = "<input type='file' name='attachments[file][]' class='dropify' data-allowed-file-extensions='pdf jpg jpeg png doc docx xls xlsx' data-max-file-size='2M' data-height='50' />";
		cell4.innerHTML = "<button class='text-red-500 hover:text-red-700' type='button' onclick='removeAttachmentRow(this)'>Remove</button>";

		$('.dropify').dropify();
	}

	function removeAttachmentRow(row) {
		var i = row.parentNode.parentNode.rowIndex;
		document.getElementById("attachments-table").deleteRow(i);
	}
</script>

<style>
    .align-top {
        vertical-align: top;
    }

    td textarea,
    td input {
        border: none;
    }

    td .select2-container--default .select2-selection--single {
        border: none;
    }
</style>
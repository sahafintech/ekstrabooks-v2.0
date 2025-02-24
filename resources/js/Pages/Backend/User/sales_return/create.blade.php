<x-app-layout>
    <!-- Start::main-content -->
    <div class="main-content">
        <x-page-header title="Sales Return" page="user" subpage="create" />

        <form method="post" class="validate" autocomplete="off" action="{{ route('sales_returns.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="grid grid-cols-12 gap-x-5">
                <div class="xl:col-span-9 lg:col-span-8 col-span-12">
                    <div class="box invoice-form">
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
                                            <x-text-input class="text-xl font-bold" type="text" name="title" value="{{ get_business_option('sales_return_title', 'Sales Return') }}" placeholder="{{ _lang('Sales Return Title') }}" />
                                        </div>
                                        <div class="col-span-12">
                                            <select id="type" class="selectize auto-select" data-selected="{{ old('type', 'credit') }}" name="type">
                                                <option value="">{{ _lang('Select Type') }}</option>
                                                <option value="credit">Credit Sales Return</option>
                                                <option value="cash">Cash Sales Return</option>
                                            </select>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-span-12 my-8">
                                <hr>
                            </div>

                            <div class="grid grid-cols-12">
                                <div class="col-span-12 grid grid-cols-12 gap-x-2">
                                    <div class="md:col-span-4 col-span-12">
                                        <x-input-label>
                                            {{ _lang('Return Date') }}
                                            <span class='text-red-600'>*</span>
                                        </x-input-label>
                                        <x-text-input type="text" class="flatpickr-input" id="date" name="return_date" value="{{ old('return_date') }}" placeholder="Select Date" />
                                    </div>
                                    <div class="md:col-span-4 col-span-12 account hidden">
                                        <x-input-label value="{{ _lang('Credit account') }}" />
                                        <select class="w-full selectize auto-select" data-selected="{{ old('account_id') }}" name="account_id">
                                            <option value="">{{ _lang('Select Account') }}</option>
                                            @foreach(\App\Models\Account::where(function ($query) {
                                            $query->where('account_type', '=', 'Bank')
                                            ->orWhere('account_type', '=', 'Cash');
                                            })->where(function ($query) {
                                            $query->where('business_id', '=', request()->activeBusiness->id)
                                            ->orWhere('business_id', '=', null);
                                            })
                                            ->get() as $account)
                                            <option value="{{ $account->id }}">{{ $account->account_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="md:col-span-4 col-span-12 method hidden">
                                        <x-input-label value="{{ _lang('Payment Method') }}" />
                                        <select class="w-full selectize auto-select" data-selected="{{ old('method') }}" name="method">
                                            <option value="">{{ _lang('Select Method') }}</option>
                                            @foreach(\App\Models\TransactionMethod::where('business_id', request()->activeBusiness->id)->get() as $method)
                                            <option value="{{ $method->name }}">{{ $method->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="md:col-span-7 col-span-12 mt-5">
                                    <x-input-label>
                                        {{ _lang('Select Customer') }}
                                        <span id="required">
                                            <span class='text-red-600'>*</span>
                                        </span>
                                    </x-input-label>
                                    <div class="select-customer">
                                        <select class="w-full selectize" data-selected="{{ old('customer_id') }}" name="customer_id">
                                            <option value="">{{ _lang('Select Customer') }}</option>
                                            @foreach(\App\Models\Customer::where('business_id', request()->activeBusiness->id)->get() as $customer)
                                            <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-12 my-10 gap-x-2">
                                <div class="md:col-span-4 col-span-12">
                                    <x-input-label>
                                        {{ _lang('Select Product / Service') }}
                                        <span class='text-red-600'>*</span>
                                    </x-input-label>
                                    <select id="products" data-type="sell" class="w-full selectize">
                                        <option value="">Select Item</option>
                                        @foreach(\App\Models\Product::where('business_id', request()->activeBusiness->id)->get() as $product)
                                        <option value="{{ $product->id }}">
                                            {{ $product->name }}
                                        </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="md:col-span-4 col-span-12">
                                    <x-input-label>
                                        {{ _lang('Transaction Currency') }}
                                        <span class='text-red-600'>*</span>
                                    </x-input-label>
                                    <select class="w-full selectize auto-select" id="currency" data-selected="{{ old('currency') }}" name="currency">
                                        <option value="">{{ _lang('Select One') }}</option>
                                        {{ get_currency_list() }}
                                    </select>
                                </div>
                                <div class="md:col-span-4 col-span-12 hidden">
                                    <x-input-label value="{{ _lang('Exchange Rate') }}" />
                                    <x-text-input id="exchange_rate" step="any" type="number" name="exchange_rate" value="{{ old('exchange_rate') }}" readonly />
                                </div>
                            </div>

                            <div class="grid grid-cols-12 mt-3">
                                <div class="col-span-12">
                                    <div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
                                        <table id="invoice-table" class="ti-custom-table ti-custom-table-head whitespace-nowrap bg-gray-100">
                                            <thead>
                                                <tr>
                                                    <th class="input-lg">{{ _lang('Name') }}</th>
                                                    <th class="input-md">{{ _lang('Item Taxes') }}</th>
                                                    <th class="input-xs text-center">{{ _lang('Quantity') }}</th>
                                                    <th class="input-sm text-right">{{ _lang('Price') }}</th>
                                                    <th class="input-sm text-right">{{ _lang('Amount') }}</th>
                                                    <th class="text-center"><i class="fas fa-minus-circle"></i></th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @if(old('product_id') != null)
                                                @foreach(old('product_id') as $index => $product_id)
                                                <tr class="line-item">
                                                    <td class="input-lg align-top">
                                                        <input type="hidden" class="product_id" name="product_id[]" value="{{ $product_id }}">
                                                        <input type="hidden" class="product_type" name="product_type[]" value="{{ old('product_type')[$index] }}">
                                                        <input type="text" class="w-72 product_name" name="product_name[]" value="{{ old('product_name')[$index] }}"><br>
                                                        <textarea class="w-72 mt-2 description" name="description[]" placeholder="{{ _lang('Descriptions') }}">{{ old('description')[0] }}</textarea>
                                                    </td>
                                                    <td class="input-md align-top">
                                                        <div class="w-52">
                                                            <select name="taxes[{{ $product_id }}][]" class="multi-selector product_taxes auto-select selectize" data-selected="[{{ isset(old('taxes')[$product_id]) != null ? implode(',', old('taxes')[$product_id]) : '' }}]" data-placeholder="{{ _lang('Select Taxes') }}" multiple>
                                                                @foreach(\App\Models\Tax::all() as $tax)
                                                                <option value="{{ $tax->id }}" data-tax-rate="{{ $tax->rate }}" data-tax-name="{{ $tax->name }} {{ $tax->rate }} %">{{ $tax->name }} {{ $tax->rate }} %</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </td>
                                                    <td class="input-xs text-center align-top"><input type="number" class="w-36 quantity" name="quantity[]" value="{{ old('quantity')[0] }}" min="1"></td>
                                                    <td class="input-sm align-top"><input type="text" class="w-36 text-right unit_cost" name="unit_cost[]" value="{{ old('unit_cost')[0] }}"></td>
                                                    <td class="input-sm align-top"><input type="text" class="w-36 text-right sub_total" name="sub_total[]" value="{{ old('sub_total')[0] }}" readonly></td>
                                                    <td class="input-xxs text-center align-top">
                                                        <button type="button" class="bg-red-600 btn-remove-row px-1 rounded-md">
                                                            <i class="ri-close-circle-line text-xl text-white"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                @endforeach
                                                @endif
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            <div class="col-span-12 my-8">
                                <hr>
                            </div>

                            <div class="grid grid-cols-12 md:text-right">
                                <div class="xl:col-start-6 col-span-12 space-y-3">
                                    <div class="grid grid-cols-12 gap-x-5" id="before-tax">
                                        <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Sub Total') }}" />
                                        <div class="md:col-span-6 col-span-12">
                                            <x-text-input type="text" class="md:text-right border-none" name="sub_total" id="sub_total" value="{{ old('sub_total') }}" readonly />
                                        </div>
                                    </div>

                                    @if(old('tax_amount') != null)
                                    @foreach(old('tax_amount') as $index => $value)
                                    <div class="grid grid-cols-12 gap-x-5 old-tax" id="after-tax">
                                        <x-input-label class="md:col-span-6 col-span-12" value="{{ \App\Models\Tax::where('id', $index)->first()->name }} {{ \App\Models\Tax::where('id', $index)->first()->rate }}" />
                                        <div class="md:col-span-6 col-span-12">
                                            <x-text-input type="text" class="md:text-right border-none" name="tax[]" id="tax" value="{{ old('tax_amount')[$index] }}" readonly />
                                        </div>
                                    </div>
                                    @endforeach
                                    @endif

                                    <div class="grid grid-cols-12 gap-x-5" id="after-tax">
                                        <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Discount Amount') }}" />
                                        <div class="md:col-span-6 col-span-12">
                                            <x-text-input type="text" class="md:text-right border-none" name="discount_amount" id="discount_amount" value="{{ old('discount_amount') }}" readonly />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-12 gap-x-5">
                                        <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Grand Total') }}" />
                                        <div class="md:col-span-6 col-span-12">
                                            <x-text-input type="text" class="md:text-right border-none" name="grand_total" id="grand_total" value="{{ old('grand_total') }}" readonly />
                                        </div>
                                    </div>

                                    <div class="hidden grid-cols-12 gap-x-5">
                                        <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Converted Total') }}" />
                                        <div class="md:col-span-6 col-span-12">
                                            <x-text-input type="text" class="md:text-right border-none" name="converted_total" id="converted_total" value="{{ old ('converted_total') }}" readonly placeholder="0.00" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-span-12 my-8">
                                <hr>
                            </div>

                            <div class="grid grid-cols-12">
                                <div class="col-span-12">
                                    <x-input-label value="{{ _lang('Notes') }}" />
                                    <textarea class="w-full" name="note">{{ old('note') }}</textarea>
                                </div>

                                <div class="col-span-12">
                                    <x-input-label value="{{ _lang('Footer') }}" />
                                    <textarea class="w-full" name="footer">{!! xss_clean(get_business_option('sales_return_footer', old('footer'))) !!}</textarea>
                                </div>

                                <div class="col-span-12 mt-4">
                                    <x-primary-button type="submit" class="submit-btn">{{ _lang('Save Return') }}</x-primary-button>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <div class="xl:col-span-3 lg:col-span-4 col-span-12">
                    <div class="box">
                        <div class="box-body">
                            <div class="grid grid-cols-12">

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
                                    <x-primary-button type="submit" class="submit-btn">{{ _lang('Save Return') }}</x-primary-button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>


        <table class="ti-custom-table ti-custom-table-head whitespace-nowrap hidden">
            <tr class="line-item" id="copy-line">
                <td class="align-top">
                    <input type="hidden" class="product_id" name="product_id[]">
                    <input type="hidden" class="product_type" name="product_type[]">
                    <input type="text" class="w-72 product_name" name="product_name[]"><br>
                    <textarea class="w-72 mt-2 description" name="description[]" placeholder="{{ _lang('Descriptions') }}"></textarea>
                </td>
                <td class="input-md align-top">
                    <div class="w-52">
                        <select name="taxes[][]" class="multi-selector product_taxes w-full" data-placeholder="{{ _lang('Select Taxes') }}" multiple>

                        </select>
                    </div>
                </td>
                <td class="input-xs text-center align-top"><input type="number" class="w-36 quantity" name="quantity[]" min="1"></td>
                <td class="input-sm align-top"><input type="text" class="w-36 text-right unit_cost" name="unit_cost[]"></td>
                <td class="input-sm align-top"><input type="text" class="w-36 text-right sub_total" name="sub_total[]" readonly></td>
                <td class="input-xxs text-center align-top">
                    <button type="button" class="bg-red-600 btn-remove-row px-1 rounded-md">
                        <i class="ri-close-circle-line text-xl text-white"></i>
                    </button>
                </td>
            </tr>
        </table>
    </div>
</x-app-layout>

<script src="{{ asset('/backend/assets/js/invoice.js?v=1.2') }}"></script>

<script>
    $(document).on('change', '#type', function() {
        if ($(this).val() == 'credit') {
            $('#required').html(
                `<span class='text-red-600'>*</span>`
            );

            $('.account').addClass('hidden');
            $('.method').addClass('hidden');
        } else {
            $('#required').html('');
            $('.account').removeClass('hidden');
            $('.method').removeClass('hidden');
        }
    });
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
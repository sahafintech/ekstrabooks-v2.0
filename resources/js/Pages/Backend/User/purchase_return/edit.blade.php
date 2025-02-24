<x-app-layout>
    <!-- Start::main-content -->
    <div class="main-content">
        <x-page-header title="Purchase Return" page="user" subpage="edit" />

        <form method="post" class="validate" autocomplete="off" action="{{ route('purchase_returns.update', $id) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
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
                                            <x-text-input class="text-xl font-bold" type="text" name="title" value="{{ get_business_option('purchase_return_title', $return->title) }}" placeholder="{{ _lang('Purchase Return Title') }}" />
                                        </div>
                                        <!-- <div class="col-span-12">
                                            <select id="type" class="selectize auto-select" data-selected="{{ old('type', 'credit') }}" name="type">
                                                <option value="">{{ _lang('Select Type') }}</option>
                                                <option value="credit">Credit Sales Return</option>
                                                <option value="cash">Cash Sales Return</option>
                                            </select>
                                        </div> -->
                                    </div>
                                </div>
                            </div>

                            <div class="col-span-12 my-8">
                                <hr>
                            </div>

                            <div class="grid grid-cols-12">
                                <div class="md:col-span-7 col-span-12 space-y-3">
                                    <x-input-label>
                                        {{ _lang('Return Date') }}
                                        <span class='text-red-600'>*</span>
                                    </x-input-label>
                                    <x-text-input type="text" class="flatpickr-input" id="date" name="return_date" value="{{ \Carbon\Carbon::createFromFormat(get_date_format(), $return->return_date)->format('d-m-Y') }}" placeholder="Select Date" />
                                </div>
                                <div class="md:col-span-7 col-span-12 mt-5">
                                    <x-input-label>
                                        {{ _lang('Select Supplier') }}
                                        <span id="required">
                                            <span class='text-red-600'>*</span>
                                        </span>
                                    </x-input-label>
                                    <div class="select-vendor">
                                        <select class="w-full selectize auto-select" data-selected="{{ $return->vendor_id }}" name="vendor_id">
                                            <option value="">{{ _lang('Select Supplier') }}</option>
                                            @foreach(\App\Models\Vendor::where('business_id', request()->activeBusiness->id)->get() as $vendor)
                                            <option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
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
                                    <select id="products" data-type="purchase" class="w-full selectize">
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
                                    <select class="w-full selectize auto-select" id="currency" data-selected="{{ $return->currency }}" name="currency">
                                        <option value="">{{ _lang('Select One') }}</option>
                                        {{ get_currency_list() }}
                                    </select>
                                </div>
                                <div class="md:col-span-4 col-span-12 {{ $return->currency == request()->activeBusiness->currency ? 'hidden' : '' }}">
                                    <x-input-label value="{{ _lang('Exchange Rate') }}" />
                                    <x-text-input id="exchange_rate" step="any" type="number" name="exchange_rate" value="{{ $return->exchange_rate }}" readonly />
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
                                                @foreach($return->items as $item)
                                                <tr class="line-item">
                                                    <td class="input-lg align-top">
                                                        <input type="hidden" class="product_id" name="product_id[]" value="{{ $item->product_id }}">
                                                        <input type="hidden" class="product_type" name="product_type[]" value="{{ $item->product->type }}">
                                                        <input type="text" class="w-72 product_name" name="product_name[]" value="{{ $item->product_name }}"><br>
                                                        <textarea class="w-72 mt-2 description" name="description[]" placeholder="{{ _lang('Descriptions') }}">{{ $item->description }}</textarea>
                                                    </td>
                                                    <td class="input-md align-top">
                                                        <div class="w-52">
                                                            <select name="taxes[{{ $item->product_id }}][]" class="selectize auto-select product_taxes" multiple data-selected="{{ $item->taxes->pluck('tax_id') }}" data-placeholder="{{ _lang('Select Taxes') }}" multiple>
                                                                @foreach(\App\Models\Tax::all() as $tax)
                                                                <option value="{{ $tax->id }}" data-tax-rate="{{ $tax->rate }}" data-tax-name="{{ $tax->name }} {{ $tax->rate }} %">{{ $tax->name }} {{ $tax->rate }} %</option>
                                                                @endforeach
                                                            </select>
                                                        </div>
                                                    </td>
                                                    <td class="input-xs text-center align-top"><input type="number" class="w-36 quantity" name="quantity[]" value="{{ $item->quantity }}" min="1"></td>
                                                    <td class="input-sm align-top"><input type="text" class="w-36 text-right unit_cost" name="unit_cost[]" value="{{ $item->unit_cost }}"></td>
                                                    <td class="input-sm align-top"><input type="text" class="w-36 text-right sub_total" name="sub_total[]" value="{{ $item->sub_total }}" readonly></td>
                                                    <td class="input-xxs text-center align-top">
                                                        <button type="button" class="bg-red-600 btn-remove-row px-1 rounded-md">
                                                            <i class="ri-close-circle-line text-xl text-white"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                @endforeach
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
                                            <x-text-input type="text" class="md:text-right border-none" name="sub_total" id="sub_total" value="{{ $return->sub_total }}" readonly />
                                        </div>
                                    </div>

                                    @foreach($return->taxes as $tax)
                                    <div class="grid grid-cols-12 gap-x-2 old-tax {{ count($return->taxes) > 0 ? ''  : 'hidden'}}" id="after-tax">
                                        <x-input-label class="md:col-span-6 col-span-12" value="{{ $tax->name }}" />
                                        <div class="md:col-span-6 col-span-12">
                                            <x-text-input type="hidden" class="md:text-right border-none tax-input-field" name="tax_amount[{{ $tax->tax_id }}]" id="tax-{{ $tax->tax_id }}" value="0" readonly />
                                            <span class="md:text-right tax_span-{{ $tax->tax_id }}">
                                                {{ formatAmount($tax->amount) }}
                                            </span>
                                        </div>
                                    </div>
                                    @endforeach

                                    <div class="grid grid-cols-12 gap-x-5" id="after-tax">
                                        <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Discount Amount') }}" />
                                        <div class="md:col-span-6 col-span-12">
                                            <x-text-input type="text" class="md:text-right border-none" name="discount_amount" id="discount_amount" value="{{ $return->discount }}" readonly />
                                        </div>
                                    </div>

                                    <div class="grid grid-cols-12 gap-x-5">
                                        <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Grand Total') }}" />
                                        <div class="md:col-span-6 col-span-12">
                                            <x-text-input type="text" class="md:text-right border-none" name="grand_total" id="grand_total" value="{{ $return->grand_total }}" readonly />
                                        </div>
                                    </div>

                                    <div class="{{ $return->currency == request()->activeBusiness->currency ? 'hidden' : 'grid' }} grid-cols-12 gap-x-5">
                                        <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Converted Total') }}" />
                                        <div class="md:col-span-6 col-span-12">
                                            <x-text-input type="text" class="md:text-right border-none" name="converted_total" id="converted_total" value="{{ $return->converted_total }}" readonly placeholder="0.00" />
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
                                    <textarea class="w-full" name="note">{{ $return->note }}</textarea>
                                </div>

                                <div class="col-span-12">
                                    <x-input-label value="{{ _lang('Footer') }}" />
                                    <textarea class="w-full" name="footer">{!! xss_clean(get_business_option('purchase_return_footer', $return->note)) !!}</textarea>
                                </div>

                                <div class="col-span-12 mt-4">
                                    <x-primary-button type="submit" class="submit-btn">{{ _lang('Update Return') }}</x-primary-button>
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
                                            <select class="w-full discount_type auto-select" id="discount_type" name="discount_type" data-selected="{{ $return->discount_type }}">
                                                <option value="0">%</option>
                                                <option value="1">{{ currency_symbol(request()->activeBusiness->currency) }}</option>
                                            </select>
                                        </div>
                                        <input type="number" step="any" class="col-span-9" name="discount_value" id="discount_value" value="{{ $return->discount_value }}">
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

<script src="{{ asset('/backend/assets/js/invoice.js?v=1.2') }}"></script>
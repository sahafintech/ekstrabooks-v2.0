@if(session('success'))
<div id="dismiss-alert" class="hs-removing:translate-x-5 hs-removing:opacity-0 transition duration-300 bg-teal-50 border border-teal-200 alert mb-4" role="alert">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-4 w-4 text-teal-400 mt-0.5" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
            </svg>
        </div>
        <div class="ml-3">
            <div class="text-sm text-teal-800 font-medium">
                {{ session('success') }}
            </div>
        </div>
        <div class="pl-3 ml-auto">
            <div class="mx-1 my-auto">
                <button type="button" class="inline-flex bg-teal-50 rounded-sm text-teal-500 focus:outline-none focus:ring-0 focus:ring-offset-0 focus:ring-offset-teal-50 focus:ring-teal-600" data-hs-remove-element="#dismiss-alert">
                    <span class="sr-only">Dismiss</span>
                    <svg class="h-3 w-3" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M0.92524 0.687069C1.126 0.486219 1.39823 0.373377 1.68209 0.373377C1.96597 0.373377 2.2382 0.486219 2.43894 0.687069L8.10514 6.35813L13.7714 0.687069C13.8701 0.584748 13.9882 0.503105 14.1188 0.446962C14.2494 0.39082 14.3899 0.361248 14.5321 0.360026C14.6742 0.358783 14.8151 0.38589 14.9468 0.439762C15.0782 0.493633 15.1977 0.573197 15.2983 0.673783C15.3987 0.774389 15.4784 0.894026 15.5321 1.02568C15.5859 1.15736 15.6131 1.29845 15.6118 1.44071C15.6105 1.58297 15.5809 1.72357 15.5248 1.85428C15.4688 1.98499 15.3872 2.10324 15.2851 2.20206L9.61883 7.87312L15.2851 13.5441C15.4801 13.7462 15.588 14.0168 15.5854 14.2977C15.5831 14.5787 15.4705 14.8474 15.272 15.046C15.0735 15.2449 14.805 15.3574 14.5244 15.3599C14.2437 15.3623 13.9733 15.2543 13.7714 15.0591L8.10514 9.38812L2.43894 15.0591C2.23704 15.2543 1.96663 15.3623 1.68594 15.3599C1.40526 15.3574 1.13677 15.2449 0.938279 15.046C0.739807 14.8474 0.627232 14.5787 0.624791 14.2977C0.62235 14.0168 0.730236 13.7462 0.92524 13.5441L6.59144 7.87312L0.92524 2.20206C0.724562 2.00115 0.611816 1.72867 0.611816 1.44457C0.611816 1.16047 0.724562 0.887983 0.92524 0.687069Z" fill="currentColor" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@if(session('error'))
<div id="dismiss-alert" class="hs-removing:translate-x-5 hs-removing:opacity-0 transition duration-300 bg-red-50 border border-red-200 alert mb-4" role="alert">
    <div class="flex">
        <div class="flex-shrink-0">
            <svg class="h-4 w-4 text-red-400 mt-0.5" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
            </svg>
        </div>
        <div class="ml-3">
            <div class="text-sm text-red-800 font-medium">
                {{ session('error') }}
            </div>
        </div>
        <div class="pl-3 ml-auto">
            <div class="mx-1 my-auto">
                <button type="button" class="inline-flex bg-red-50 rounded-sm text-red-500 focus:outline-none focus:ring-0 focus:ring-offset-0 focus:ring-offset-red-50 focus:ring-red-600" data-hs-remove-element="#dismiss-alert">
                    <span class="sr-only">Dismiss</span>
                    <svg class="h-3 w-3" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M0.92524 0.687069C1.126 0.486219 1.39823 0.373377 1.68209 0.373377C1.96597 0.373377 2.2382 0.486219 2.43894 0.687069L8.10514 6.35813L13.7714 0.687069C13.8701 0.584748 13.9882 0.503105 14.1188 0.446962C14.2494 0.39082 14.3899 0.361248 14.5321 0.360026C14.6742 0.358783 14.8151 0.38589 14.9468 0.439762C15.0782 0.493633 15.1977 0.573197 15.2983 0.673783C15.3987 0.774389 15.4784 0.894026 15.5321 1.02568C15.5859 1.15736 15.6131 1.29845 15.6118 1.44071C15.6105 1.58297 15.5809 1.72357 15.5248 1.85428C15.4688 1.98499 15.3872 2.10324 15.2851 2.20206L9.61883 7.87312L15.2851 13.5441C15.4801 13.7462 15.588 14.0168 15.5854 14.2977C15.5831 14.5787 15.4705 14.8474 15.272 15.046C15.0735 15.2449 14.805 15.3574 14.5244 15.3599C14.2437 15.3623 13.9733 15.2543 13.7714 15.0591L8.10514 9.38812L2.43894 15.0591C2.23704 15.2543 1.96663 15.3623 1.68594 15.3599C1.40526 15.3574 1.13677 15.2449 0.938279 15.046C0.739807 14.8474 0.627232 14.5787 0.624791 14.2977C0.62235 14.0168 0.730236 13.7462 0.92524 13.5441L6.59144 7.87312L0.92524 2.20206C0.724562 2.00115 0.611816 1.72867 0.611816 1.44457C0.611816 1.16047 0.724562 0.887983 0.92524 0.687069Z" fill="currentColor" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@if($errors->any())
<div id="dismiss-alert" class="hs-removing:translate-x-5 hs-removing:opacity-0 transition duration-300 bg-red-50 border border-red-200 alert mb-4" role="alert">
    <div class="flex">
        <div class="space-y-1">
            @foreach ($errors->all() as $error)
            <div class="flex items-center space-x-1">
                <div class="flex-shrink-0">
                    <svg class="h-4 w-4 text-red-400 mt-0.5" xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16">
                        <path d="M16 8A8 8 0 1 1 0 8a8 8 0 0 1 16 0zm-3.97-3.03a.75.75 0 0 0-1.08.022L7.477 9.417 5.384 7.323a.75.75 0 0 0-1.06 1.06L6.97 11.03a.75.75 0 0 0 1.079-.02l3.992-4.99a.75.75 0 0 0-.01-1.05z" />
                    </svg>
                </div>
                <div class="ml-3">
                    <div class="text-sm text-red-800 font-medium">
                        {{ $error }}
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        <div class="pl-3 ml-auto">
            <div class="mx-1 my-auto">
                <button type="button" class="inline-flex bg-red-50 rounded-sm text-red-500 focus:outline-none focus:ring-0 focus:ring-offset-0 focus:ring-offset-red-50 focus:ring-red-600" data-hs-remove-element="#dismiss-alert">
                    <span class="sr-only">Dismiss</span>
                    <svg class="h-3 w-3" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                        <path d="M0.92524 0.687069C1.126 0.486219 1.39823 0.373377 1.68209 0.373377C1.96597 0.373377 2.2382 0.486219 2.43894 0.687069L8.10514 6.35813L13.7714 0.687069C13.8701 0.584748 13.9882 0.503105 14.1188 0.446962C14.2494 0.39082 14.3899 0.361248 14.5321 0.360026C14.6742 0.358783 14.8151 0.38589 14.9468 0.439762C15.0782 0.493633 15.1977 0.573197 15.2983 0.673783C15.3987 0.774389 15.4784 0.894026 15.5321 1.02568C15.5859 1.15736 15.6131 1.29845 15.6118 1.44071C15.6105 1.58297 15.5809 1.72357 15.5248 1.85428C15.4688 1.98499 15.3872 2.10324 15.2851 2.20206L9.61883 7.87312L15.2851 13.5441C15.4801 13.7462 15.588 14.0168 15.5854 14.2977C15.5831 14.5787 15.4705 14.8474 15.272 15.046C15.0735 15.2449 14.805 15.3574 14.5244 15.3599C14.2437 15.3623 13.9733 15.2543 13.7714 15.0591L8.10514 9.38812L2.43894 15.0591C2.23704 15.2543 1.96663 15.3623 1.68594 15.3599C1.40526 15.3574 1.13677 15.2449 0.938279 15.046C0.739807 14.8474 0.627232 14.5787 0.624791 14.2977C0.62235 14.0168 0.730236 13.7462 0.92524 13.5441L6.59144 7.87312L0.92524 2.20206C0.724562 2.00115 0.611816 1.72867 0.611816 1.44457C0.611816 1.16047 0.724562 0.887983 0.92524 0.687069Z" fill="currentColor" />
                    </svg>
                </button>
            </div>
        </div>
    </div>
</div>
@endif
<x-guest-layout>
    <div class="grid grid-cols-12 gap-x-2 h-screen">
        <div class="col-span-12 h-[10vh]">
            <div class="box h-full">
                <div class="box-body grid grid-cols-12 h-full">
                    <div class="md:col-span-10 col-span-12 flex items-center space-x-2">
                        <div class="relative w-full">
                            <x-text-input type="text" class="!pt-[4px] !pb-[4px]" id="search_term" name="product_name" placeholder="search product name / item code / scan bar code" />
                            <div class="absolute top-9 bg-rose-100 w-full z-50" id="product_list">
                            </div>
                        </div>
                        <!-- hold list button -->
                        <div class="w-full flex items-center space-x-2">
                            <x-secondary-button data-hs-overlay="#hold-list-modal" type="button">
                                Hold List
                            </x-secondary-button>
                            <x-secondary-button data-hs-overlay="#today-invoices-modal" type="button">
                                Today Invoices
                            </x-secondary-button>
                            @if(package()->medical_record == 1 && package()->prescription == 1)
                            <x-secondary-button data-hs-overlay="#appointment-modal" type="button" id="appointment_btn">
                                Appointment
                            </x-secondary-button>
                            @endif
                            @if(package()->medical_record == 1 && package()->prescription == 1)
                            <x-secondary-button data-hs-overlay="#prescription-product-modal" type="button" id="prescription_btn">
                                Prescriptions
                            </x-secondary-button>
                            @endif
                        </div>
                    </div>
                    <div class="md:col-span-2 col-span-12 flex items-center justify-end">
                        <a href="{{ route('dashboard.index') }}" class="flex items-center">
                            <i class="ri-arrow-left-s-line"></i>
                            Back
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <!-- <div class="md:col-span-5 col-span-12">
            <div class="box">
                <div class="box-body">
                    <div class="grid grid-cols-12 gap-x-2 mt-2">
                        <div class="grid grid-cols-12 md:col-span-6 col-span-12">
                            <div class="col-span-3">
                                <select class="w-full discount_type !pt-[4px] !pb-[4px]" id="discount_type" name="discount_type" value="{{ old('discount_type') }}">
                                    <option value="0">%</option>
                                    <option value="1">{{ currency_symbol(request()->activeBusiness->currency) }}</option>
                                </select>
                            </div>
                            <input type="number" class="col-span-9 !pt-[4px] !pb-[4px]" name="discount_value" id="discount_value" min="0" value="{{ old('discount_value',0) }}">
                        </div>

                        <div class="md:col-span-6 col-span-12">
                            <select name="taxes[][]" class="multi-selector selectize product_taxes" data-placeholder="{{ _lang('Select Taxes') }}" multiple>
                                @foreach(\App\Models\Tax::all() as $tax)
                                <option value="{{ $tax->id }}" data-tax-rate="{{ $tax->rate }}" data-tax-name="{{ $tax->name }}">{{ $tax->name }} ({{ $tax->rate }})</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>
        </div> -->
        <!-- cart -->
        <form method="POST" action="{{ route('receipts.pos_store') }}" id="invoice_form" class="md:col-span-5 col-span-12 grid grid-cols-12">
            @csrf
            <div class="col-span-12 grid grid-rows-12 h-[90vh] drop-shadow-xl">
                <div class="box row-span-7 whitespace-nowrap overflow-y-auto">
                    <div class="box-body">
                        <table id="invoice-table" class="ti-custom-table ti-custom-table-head whitespace-nowrap relative">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-right">Subtotal</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <div id="loader" class="absolute hidden top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 w-full h-full bg-black/20 items-center justify-center z-10">
                                    <div class="loader"></div>
                                </div>
                                @if(old('product_id') != null)
                                @foreach(old('product_id') as $key => $value)
                                <tr class="line-item">
                                    <td class="align-top">
                                        <input type="hidden" class="product_id" name="product_id[]" value="{{ old('product_id')[$key] }}">
                                        <input type="hidden" class="product_type" name="product_type[]" value="{{ old('product_type')[$key] }}">
                                        <div class="flex flex-col">
                                            <input type="text" readonly class="w-full product_name" name="product_name[]" value="{{ old('product_name')[$key] }}">
                                            <input type="text" class="w-full unit_cost" name="unit_cost[]" value="{{ old('unit_cost')[$key] }}">
                                            <input type="number" class="w-full quantity" name="quantity[]" value="{{ old('quantity')[$key] }}" min="1">
                                        </div>
                                    </td>
                                    <td class="input-sm align-top"><input type="text" class="w-full text-right sub_total" name="sub_total[]" value="{{ old('sub_total')[$key] }}" readonly></td>
                                    <td class="input-xxs text-center align-top">
                                        <button class="text-danger btn-remove-row" type="button">
                                            <i class="ri-delete-bin-2-line"></i>
                                        </button>
                                    </td>
                                </tr>
                                @endforeach
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="box row-span-3">
                    <div class="box-body">
                        <div class="grid grid-cols-12 md:text-right">
                            <div class="xl:col-start-6 col-span-12 space-y-2">
                                <div class="grid grid-cols-12 gap-x-2" id="before-tax">
                                    <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Sub Total') }}" />
                                    <div class="md:col-span-6 col-span-12">
                                        <x-text-input type="hidden" class="md:text-right border-none !p-0" name="sub_total" id="sub_total" value="{{ old('sub_total') }}" readonly />
                                        <div class="pos_sub_total_span md:text-right border-none">

                                        </div>
                                    </div>
                                </div>

                                <!-- hiddent input of transaction currency -->
                                <input type="hidden" name="currency" id="transaction_currency" value="{{ old('transaction_currency') }}">

                                <!-- hidden input of currency rate -->
                                <input type="hidden" name="exchange_rate" id="exchange_rate" value="{{ old('exchange_rate') }}">

                                <!-- hidden input account -->
                                <input type="hidden" name="account_id" id="account_id" value="{{ old('account_id') }}">

                                <!-- hidden input discount type -->
                                <input type="hidden" name="discount_type" id="pos_discount_type" value="{{ old('discount_type', 0) }}">

                                <!-- hidden input discount value -->
                                <input type="hidden" name="discount_value" id="pos_discount_value" value="{{ old('discount_value', 0) }}">

                                <!-- hidden input hold pos id -->
                                <input type="hidden" name="hold_pos_id" id="hold_pos_id" value="{{ old('hold_pos_id') }}">

                                <!-- hidden input customer id -->
                                <input type="hidden" name="customer_id" id="customer_id" value="{{ old('customer_id') }}">

                                <!-- hidden input client id -->
                                <input type="hidden" name="client_id" id="client_id" value="{{ old('client_id') }}">

                                <!-- hidden input credit_cash -->
                                <input type="hidden" name="credit_cash" id="credit_cash" value="{{ old('credit_cash') }}">

                                <!-- hidden input invoice_date -->
                                <input type="hidden" name="invoice_date" id="invoice_date" value="{{ old('invoice_date', date('Y-m-d')) }}">

                                <!-- hidden input due_date -->
                                <input type="hidden" name="due_date" id="due_date" value="{{ old('due_date', date('Y-m-d')) }}">

                                <!-- hidden input appointment -->
                                <input type="hidden" name="appointment" id="appointment" value="{{ old('appointment') }}">

                                <!-- hidden input prescription_products_id -->
                                <input type="hidden" name="prescription_products_id" id="prescription_products_id" value="{{ old('prescription_products_id') }}">

                                @if(old('tax_amount') != null)
                                @foreach(old('tax_amount') as $index => $value)
                                <div class="grid grid-cols-12 gap-x-2 old-tax" id="after-tax">
                                    <x-input-label class="md:col-span-6 col-span-12" value="{{ \App\Models\Tax::where('id', $index)->first()->name }} {{ \App\Models\Tax::where('id', $index)->first()->rate }}" />
                                    <div class="md:col-span-6 col-span-12">
                                        <x-text-input type="text" class="md:text-right border-none !p-0" name="tax[]" id="tax" value="{{ old('tax_amount')[$index] }}" readonly />
                                    </div>
                                </div>
                                @endforeach
                                @endif

                                <div class="grid grid-cols-12 gap-x-2" id="after-tax">
                                    <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Discount Amount') }}" />
                                    <div class="md:col-span-6 col-span-12">
                                        <x-text-input type="hidden" class="md:text-right border-none !p-0" name="discount_amount" id="discount_amount" value="{{ old('discount_amount') }}" readonly />
                                        <div class="pos_discount_span md:text-right border-none">

                                        </div>
                                    </div>
                                </div>

                                <div class="grid grid-cols-12 gap-x-2">
                                    <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Grand Total') }}" />
                                    <div class="md:col-span-6 col-span-12">
                                        <x-text-input type="hidden" class="md:text-right border-none !p-0" name="grand_total" id="grand_total" value="{{ old('grand_total') }}" readonly />
                                        <div class="pos_grand_total_span md:text-right border-none">

                                        </div>
                                    </div>
                                </div>

                                <div class="hidden grid-cols-12 gap-x-2">
                                    <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Converted Total') }}" />
                                    <div class="md:col-span-6 col-span-12">
                                        <x-text-input type="hidden" class="md:text-right border-none !p-0" name="converted_total" id="converted_total" value="{{ old ('converted_total') }}" readonly placeholder="0.00" />
                                        <div class="pos_converted_total_span md:text-right border-none">

                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-12 row-span-2 gap-1 mt-1">
                    <button class="w-full p-1 bg-[#77B180] text-white text-lg font-semibold col-span-4 rounded-sm drop-shadow-xl" data-hs-overlay="#pay-modal" id="pay" type="button">Pay</button>
                    <button class="w-full p-1 bg-[#F85F68] text-white text-lg font-semibold col-span-4 rounded-sm drop-shadow-xl" id="cancel" type="button">Cancel</button>
                    <button class="w-full p-1 bg-[#3097BF] text-white text-lg font-semibold col-span-4 rounded-sm drop-shadow-xl" data-hs-overlay="#discount-modal" type="button">Discount</button>
                    <button class="w-full p-1 bg-[#322831] text-white text-lg font-semibold col-span-4 rounded-sm drop-shadow-xl" data-hs-overlay="#change-modal" type="button">Change</button>
                    <button class="w-full p-1 bg-[#F0B99A] text-white text-lg font-semibold col-span-4 rounded-sm drop-shadow-xl" data-hs-overlay="#tax-modal" type="button">Tax</button>
                    <button class="w-full p-1 bg-[#BB5769] text-white text-lg font-semibold col-span-4 rounded-sm drop-shadow-xl" id="hold" type="button">Hold</button>
                </div>
            </div>
        </form>

        <!-- all products -->
        <div class="md:col-span-7 col-span-12 h-[90vh]">
            <!-- categories -->
            <div class="overflow-x-scroll whitespace-nowrap hide-scrollbar mb-2">
                <div class="flex items-center space-x-0.5">
                    <div class="text-center px-2 py-4 bg-white border border-gray-200 cursor-pointer select-none all-category">
                        <p>ALL</p>
                    </div>
                    @foreach(\App\Models\Category::all() as $category)
                    <div class="text-center px-3 py-4 bg-white border drop-shadow-2xl border-gray-200 cursor-pointer select-none category" data-id="{{$category->id}}">
                        <p>{{ $category->name }}</p>
                    </div>
                    @endforeach
                </div>
            </div>
            <div class="h-full overflow-y-scroll hide-scrollbar box">
                <div class="grid grid-cols-12 gap-2 box-body list-products" style="padding-bottom: 100px !important;">
                    <!-- products -->
                    @foreach(\App\Models\Product::where('type', 'product')->get() as $product)
                    <div class="col-span-4 xl:col-span-3 cursor-pointer product border border-gray-400 drop-shadow-lg p-2 rounded-md" data-id="{{ $product->id }}">
                        <div class="space-y-2 relative">
                            @if(get_business_option('pos_show_image') == 1)
                            <div class="w-20 h-20 rounded-md">
                                <img src="{{ asset('/uploads/media/'.$product->image) }}" class="w-full h-full object-contain">
                            </div>
                            @endif
                            <p class="text-sm font-bold">{{ $product->name }}</p>
                            @if(get_business_option('pos_show_image') == 1)
                            <p class="font-semibold absolute top-0 right-0 bg-green-100 px-1">
                                {{ formatAmount($product->selling_price, currency_symbol(request()->activeBusiness->currency), request()->activeBusiness->id) }}
                            </p>
                            @else
                            <p class="font-semibold">
                                <span class="bg-green-100 px-1">
                                    {{ formatAmount($product->selling_price, currency_symbol(request()->activeBusiness->currency), request()->activeBusiness->id) }}
                                </span>
                            </p>
                            @endif
                        </div>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <table class="hidden">
            <tr class="line-item" id="copy-line">
                <td class="align-top">
                    <input type="hidden" class="product_id" name="product_id[]">
                    <input type="hidden" class="product_type" name="product_type[]">
                    <div class="flex flex-col">
                        <input type="text" readonly class="w-full product_name" name="product_name[]">
                        <input type="text" class="w-full unit_cost" name="unit_cost[]">
                        <input type="number" class="w-full quantity" name="quantity[]" min="1">
                    </div>
                </td>
                <td class="input-sm align-top"><input type="text" class="w-full text-right sub_total" name="sub_total[]" readonly></td>
                <td class="input-xxs text-center align-top">
                    <button class="text-danger btn-remove-row" type="button">
                        <i class="ri-delete-bin-2-line"></i>
                    </button>
                </td>
            </tr>
        </table>
    </div>

    <div id="pay-modal" class="hs-overlay hidden ti-modal">
        <div class="hs-overlay-open:mt-7 ti-modal-box mt-0 ease-out lg:!max-w-4xl lg:w-full m-3 lg:!mx-auto">
            <div class="ti-modal-content">
                <div class="ti-modal-header">
                    <h3 class="ti-modal-title">
                        Payment Modal
                    </h3>
                    <button type="button" class="hs-dropdown-toggle ti-modal-close-btn" data-hs-overlay="#pay-modal">
                        <span class="sr-only">Close</span>
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>
                <div class="ti-modal-body">
                    <!-- select transaction currency -->
                    <!-- <div class="grid grid-cols-12 gap-x-2">
                            <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Transaction Currency') }}" />
                            <div class="md:col-span-6 col-span-12">
                                <select name="currency" id="currency" class="selectize">
                                    <option value="">{{ _lang('Select One') }}</option>
                                    {{ get_currency_list() }}
                                </select>
                            </div>
                        </div> -->
                    <!-- radio button for credit and cash -->
                    <div class="grid grid-cols-12 gap-x-2 mb-8">
                        <div class="md:col-span-6 col-span-12">
                            <div class="flex items-center space-x-2">
                                <label class="bg-gray-200 px-6 py-2 flex items-center space-x-1" for="cash">
                                    <input type="radio" id="cash" name="credit_cash" value="cash" checked>
                                    <span>{{ _lang('Cash') }}</span>
                                </label>
                                <label class="bg-gray-200 px-6 py-2 flex items-center space-x-1" for="credit">
                                    <input type="radio" id="credit" name="credit_cash" value="credit">
                                    <span>{{ _lang('Credit') }}</span>
                                </label>
                                <label class="bg-gray-200 px-6 py-2 flex items-center space-x-1" for="provider_btn">
                                    <input type="radio" id="provider_btn" name="credit_cash" value="provider">
                                    <span>{{ _lang('Provider') }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-12">
                        <div class="col-span-12 space-y-3">
                            <div class="grid grid-cols-12 gap-x-2" id="before-tax">
                                <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Sub Total') }}" />
                                <div class="md:col-span-6 col-span-12">
                                    <div class="sub_total_span md:text-right border-none">

                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-12 gap-x-2" id="after-tax">
                                <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Discount Amount') }}" />
                                <div class="md:col-span-6 col-span-12">
                                    <div class="discount_span md:text-right border-none">

                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-12 gap-x-2">
                                <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Grand Total') }}" />
                                <div class="md:col-span-6 col-span-12">
                                    <div class="grand_total_span md:text-right border-none">

                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-12 gap-x-2">
                                <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Converted Total') }}" />
                                <div class="md:col-span-6 col-span-12">
                                    <div class="converted_total_span md:text-right border-none">

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- select a client -->
                    <div class="grid grid-cols-12 gap-x-2 mt-2 items-center" id="client">
                        <x-input-label class="md:col-span-3 col-span-12" value="{{ _lang('Client') }}" />
                        <div class="md:col-span-9 col-span-12 w-full">
                            <select name="client" class="selectize client" data-placeholder="Select a client">
                                <option value="">Select a customer</option>
                                @foreach(\App\Models\Customer::all() as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }} - {{ $customer->mobile }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <!-- select customer provider -->
                    <div class="hidden grid-cols-12 gap-x-2 mt-2 items-center" id="provider">
                        <x-input-label class="md:col-span-3 col-span-12" value="{{ _lang('Provider') }}" />
                        <div class="md:col-span-9 col-span-12 w-full">
                            <select name="customer_id" class="w-full selectize provider">
                                <option value="">{{ _lang('Select One') }}</option>
                                @foreach(\App\Models\Customer::all() as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- select transaction method -->
                    <div class="grid grid-cols-12 gap-x-2 mt-2 items-center">
                        <x-input-label class="md:col-span-3 col-span-12" value="{{ _lang('Payment Method') }}" />
                        <div class="md:col-span-9 col-span-12 w-full">
                            <select name="payment_method" id="payment_method" class="w-full">
                                <option value="">{{ _lang('Select One') }}</option>
                                @foreach(\App\Models\TransactionMethod::all() as $payment_method)
                                <option value="{{ $payment_method->name }}">{{ $payment_method->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- select debit account -->
                    <div class="grid grid-cols-12 gap-x-2 mt-2 items-center debit_account">
                        <x-input-label class="md:col-span-3 col-span-12" value="{{ _lang('Debit Account') }}" />
                        <div class="md:col-span-9 col-span-12 w-full">
                            <select name="debit_account" id="debit_account" class="w-full selectize">
                                <option value="">{{ _lang('Select One') }}</option>
                                @foreach(\App\Models\Account::where(function ($query) {
                                $query->where('account_type', '=', 'Bank')
                                ->orWhere('account_type', '=', 'Cash');
                                })->where(function ($query) {
                                $query->where('business_id', '=', request()->activeBusiness->id);
                                })
                                ->get() as $account)
                                <option value="{{ $account->id }}">{{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- invoice date -->
                    <div class="grid grid-cols-12 gap-x-2 mt-2 items-center invoice_date">
                        <x-input-label class="md:col-span-3 col-span-12" value="{{ _lang('Invoice Date') }}" />
                        <div class="md:col-span-9 col-span-12 w-full">
                            <x-text-input name="invoice_date" type="text" id="date" class="flatpickr" value="{{ date('d-m-Y') }}" />
                        </div>
                    </div>

                    <!-- due date -->
                    <div class="hidden grid-cols-12 gap-x-2 mt-2 items-center due_date">
                        <x-input-label class="md:col-span-3 col-span-12" value="{{ _lang('Due Date') }}" />
                        <div class="md:col-span-9 col-span-12 w-full">
                            <x-text-input name="due_date" type="text" id="date" class="flatpickr" value="{{ date('d-m-Y') }}" />
                        </div>
                    </div>

                </div>
                <div class="ti-modal-footer">
                    <x-secondary-button data-hs-overlay="#pay-modal">
                        {{ __('Cancel') }}
                    </x-secondary-button>

                    <x-primary-button type="button" class="submit-btn" id="save_invoice">
                        {{ __('Save Invoice') }}
                    </x-primary-button>
                </div>
            </div>
        </div>
    </div>

    <div id="appointment-modal" class="hs-overlay hidden ti-modal">
        <div class="hs-overlay-open:mt-7 ti-modal-box mt-0 ease-out lg:!max-w-4xl lg:w-full m-3 lg:!mx-auto">
            <div class="ti-modal-content">
                <div class="ti-modal-header">
                    <h3 class="ti-modal-title">
                        Appointment Modal
                    </h3>
                    <button type="button" class="hs-dropdown-toggle ti-modal-close-btn" data-hs-overlay="#appointment-modal">
                        <span class="sr-only">Close</span>
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>
                <div class="ti-modal-body">
                    <!-- select transaction currency -->
                    <!-- <div class="grid grid-cols-12 gap-x-2">
                            <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Transaction Currency') }}" />
                            <div class="md:col-span-6 col-span-12">
                                <select name="currency" id="currency" class="selectize">
                                    <option value="">{{ _lang('Select One') }}</option>
                                    {{ get_currency_list() }}
                                </select>
                            </div>
                        </div> -->
                    <!-- radio button for credit and cash -->
                    <div class="grid grid-cols-12 gap-x-2 mb-8">
                        <div class="md:col-span-6 col-span-12">
                            <div class="flex items-center space-x-2">
                                <label class="bg-gray-200 px-6 py-2 flex items-center space-x-1" for="appointment_cash">
                                    <input type="radio" id="appointment_cash" name="appointment_credit_cash" value="cash" checked>
                                    <span>{{ _lang('Cash') }}</span>
                                </label>
                                <label class="bg-gray-200 px-6 py-2 flex items-center space-x-1" for="appointment_credit">
                                    <input type="radio" id="appointment_credit" name="appointment_credit_cash" value="credit">
                                    <span>{{ _lang('Credit') }}</span>
                                </label>
                                <label class="bg-gray-200 px-6 py-2 flex items-center space-x-1" for="appointment_provider_btn">
                                    <input type="radio" id="appointment_provider_btn" name="appointment_credit_cash" value="provider">
                                    <span>{{ _lang('Provider') }}</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-12">
                        <div class="col-span-12 space-y-3">
                            <div class="grid grid-cols-12 gap-x-2" id="before-tax">
                                <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Sub Total') }}" />
                                <div class="md:col-span-6 col-span-12">
                                    <div class="sub_total_span md:text-right border-none">

                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-12 gap-x-2" id="after-tax">
                                <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Discount Amount') }}" />
                                <div class="md:col-span-6 col-span-12">
                                    <div class="discount_span md:text-right border-none">

                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-12 gap-x-2">
                                <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Grand Total') }}" />
                                <div class="md:col-span-6 col-span-12">
                                    <div class="grand_total_span md:text-right border-none">

                                    </div>
                                </div>
                            </div>

                            <div class="grid grid-cols-12 gap-x-2">
                                <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Converted Total') }}" />
                                <div class="md:col-span-6 col-span-12">
                                    <div class="converted_total_span md:text-right border-none">

                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- select a client -->
                    <div class="grid grid-cols-12 gap-x-2 mt-2 items-center" id="client">
                        <x-input-label class="md:col-span-3 col-span-12" value="{{ _lang('Client') }}" />
                        <div class="md:col-span-9 col-span-12 w-full">
                            <select name="client" class="selectize client" data-placeholder="Select a client">
                                <option value="">Select a customer</option>
                                @foreach(\App\Models\Customer::all() as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }} - {{ $customer->mobile }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                    <!-- select customer provider -->
                    <div class="hidden grid-cols-12 gap-x-2 mt-2 items-center" id="appointment_provider">
                        <x-input-label class="md:col-span-3 col-span-12" value="{{ _lang('Provider') }}" />
                        <div class="md:col-span-9 col-span-12 w-full">
                            <select name="customer_id" class="w-full selectize appointment_provider">
                                <option value="">{{ _lang('Select One') }}</option>
                                @foreach(\App\Models\Customer::all() as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- select transaction method -->
                    <div class="grid grid-cols-12 gap-x-2 mt-2 items-center">
                        <x-input-label class="md:col-span-3 col-span-12" value="{{ _lang('Payment Method') }}" />
                        <div class="md:col-span-9 col-span-12 w-full">
                            <select name="payment_method" id="payment_method" class="w-full">
                                <option value="">{{ _lang('Select One') }}</option>
                                @foreach(\App\Models\TransactionMethod::all() as $payment_method)
                                <option value="{{ $payment_method->name }}">{{ $payment_method->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- select debit account -->
                    <div class="grid grid-cols-12 gap-x-2 mt-2 items-center debit_account">
                        <x-input-label class="md:col-span-3 col-span-12" value="{{ _lang('Debit Account') }}" />
                        <div class="md:col-span-9 col-span-12 w-full">
                            <select name="debit_account" id="debit_account" class="w-full selectize">
                                <option value="">{{ _lang('Select One') }}</option>
                                @foreach(\App\Models\Account::where(function ($query) {
                                $query->where('account_type', '=', 'Bank')
                                ->orWhere('account_type', '=', 'Cash');
                                })->where(function ($query) {
                                $query->where('business_id', '=', request()->activeBusiness->id);
                                })
                                ->get() as $account)
                                <option value="{{ $account->id }}">{{ $account->account_name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- invoice date -->
                    <div class="grid grid-cols-12 gap-x-2 mt-2 items-center invoice_date">
                        <x-input-label class="md:col-span-3 col-span-12" value="{{ _lang('Invoice Date') }}" />
                        <div class="md:col-span-9 col-span-12 w-full">
                            <x-text-input name="invoice_date" type="text" id="date" class="flatpickr" value="{{ date('d-m-Y') }}" />
                        </div>
                    </div>

                    <!-- due date -->
                    <div class="hidden grid-cols-12 gap-x-2 mt-2 items-center due_date">
                        <x-input-label class="md:col-span-3 col-span-12" value="{{ _lang('Due Date') }}" />
                        <div class="md:col-span-9 col-span-12 w-full">
                            <x-text-input name="due_date" type="text" id="date" class="flatpickr" value="{{ date('d-m-Y') }}" />
                        </div>
                    </div>
                </div>
                <div class="ti-modal-footer">
                    <x-secondary-button data-hs-overlay="#appointment-modal">
                        {{ __('Cancel') }}
                    </x-secondary-button>

                    <x-primary-button type="button" class="submit-btn" id="save_appointment">
                        {{ __('Save Appointment') }}
                    </x-primary-button>
                </div>
            </div>
        </div>
    </div>

    <div id="prescription-product-modal" class="hs-overlay hidden ti-modal">
        <div class="hs-overlay-open:mt-7 ti-modal-box mt-0 ease-out lg:!max-w-4xl lg:w-full m-3 lg:!mx-auto">
            <div class="ti-modal-content">
                <div class="ti-modal-header">
                    <h3 class="ti-modal-title">
                        Prescription Modal
                    </h3>
                    <button type="button" class="hs-dropdown-toggle ti-modal-close-btn" data-hs-overlay="#prescription-product-modal">
                        <span class="sr-only">Close</span>
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>
                <div class="ti-modal-body">
                    <div class="box">
                        <div class="box-body">
                            <table class="ti-custom-table ti-custom-table-head">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Customer Name</th>
                                        <th>Phone</th>
                                        <th>Number Of Products</th>
                                        <th>Grand Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(\App\Models\PrescriptionProduct::where("status", 0)->get() as $pp)
                                    <tr>
                                        <td>{{ $pp->prescription?->date }}</td>
                                        <td>{{ $pp->customer?->name }}</td>
                                        <td>{{ $pp->customer?->mobile }}</td>
                                        <td>{{ $pp->items->count() }}</td>
                                        <td>{{ formatAmount($pp->grand_total, currency_symbol(request()->activeBusiness->currency), request()->activeBusiness->id) }}</td>
                                        <td>
                                            <button class="text-success btn-prescription-product" data-id="{{ $pp->id }}" type="button">
                                                <i class="ri-check-line text-xl"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div id="discount-modal" class="hs-overlay hidden ti-modal">
        <div class="hs-overlay-open:mt-7 ti-modal-box mt-0 ease-out lg:!max-w-3xl lg:w-full m-3 lg:!mx-auto">
            <div class="ti-modal-content">
                <form method="post">
                    {{ csrf_field() }}
                    <input name="_method" type="hidden" value="DELETE">
                    <div class="ti-modal-header">
                        <h3 class="ti-modal-title">
                            Discount Modal
                        </h3>
                        <button type="button" class="hs-dropdown-toggle ti-modal-close-btn" data-hs-overlay="#discount-modal">
                            <span class="sr-only">Close</span>
                            <i class="ri-close-line text-xl"></i>
                        </button>
                    </div>
                    <div class="ti-modal-body">
                        <!-- discount -->
                        <div class="grid grid-cols-12 md:col-span-6 col-span-12">
                            <div class="col-span-3">
                                <select class="w-full discount_type !pt-[4px] !pb-[4px] auto-select" id="discount_type" name="discount_type" data-selected="{{ old('discount_type', 0) }}">
                                    <option value="0">%</option>
                                    <option value="1">{{ currency_symbol(request()->activeBusiness->currency) }}</option>
                                </select>
                            </div>
                            <input type="number" class="col-span-9 !pt-[4px] !pb-[4px]" name="discount_value" id="discount_value" min="0" value="{{ old('discount_value',0) }}">
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="change-modal" class="hs-overlay hidden ti-modal">
        <div class="hs-overlay-open:mt-7 ti-modal-box mt-0 ease-out lg:!max-w-3xl lg:w-full m-3 lg:!mx-auto">
            <div class="ti-modal-content">
                <form method="post">
                    {{ csrf_field() }}
                    <input name="_method" type="hidden" value="DELETE">
                    <div class="ti-modal-header">
                        <h3 class="ti-modal-title">
                            Change Modal
                        </h3>
                        <button type="button" class="hs-dropdown-toggle ti-modal-close-btn" data-hs-overlay="#change-modal">
                            <span class="sr-only">Close</span>
                            <i class="ri-close-line text-xl"></i>
                        </button>
                    </div>
                    <div class="ti-modal-body">
                        <!-- select transaction currency -->
                        <div class="hidden">
                            <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Transaction Currency') }}" />
                            <div class="md:col-span-6 col-span-12">
                                <select name="currency" id="currency" class="selectize">
                                    <option value="">{{ _lang('Select One') }}</option>
                                    {{ get_currency_list() }}
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-12 mt-4 gap-x-2">
                            <!-- usd button -->
                            <div class="col-span-6">
                                <button type="button" class="w-full h-44 p-2 bg-blue-400 text-white text-xl font-semibold rounded-sm" id="usd">USD</button>
                            </div>
                            <!-- slsh button -->
                            <div class="col-span-6">
                                <button type="button" class="w-full h-44 p-2 bg-blue-400 text-white text-xl font-semibold rounded-sm" id="slsh">SLSH</button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div id="tax-modal" class="hs-overlay hidden ti-modal">
        <div class="hs-overlay-open:mt-7 ti-modal-box mt-0 ease-out lg:!max-w-3xl lg:w-full m-3 lg:!mx-auto">
            <div class="ti-modal-content">
                <form method="post">
                    {{ csrf_field() }}
                    <input name="_method" type="hidden" value="DELETE">
                    <div class="ti-modal-header">
                        <h3 class="ti-modal-title">
                            Tax Modal
                        </h3>
                        <button type="button" class="hs-dropdown-toggle ti-modal-close-btn" data-hs-overlay="#tax-modal">
                            <span class="sr-only">Close</span>
                            <i class="ri-close-line text-xl"></i>
                        </button>
                    </div>
                    <div class="ti-modal-body">
                        <!-- select tax -->
                        <div class="grid grid-cols-12 gap-x-2">
                            <x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Select Taxes') }}" />
                            <div class="md:col-span-6 col-span-12">
                                <select name="taxes[][]" class="multi-selector selectize product_taxes" data-placeholder="{{ _lang('Select Taxes') }}" multiple>
                                    @foreach(\App\Models\Tax::all() as $tax)
                                    <option value="{{ $tax->id }}" data-tax-rate="{{ $tax->rate }}" data-tax-name="{{ $tax->name }}">{{ $tax->name }} ({{ $tax->rate }})</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- hold list modal -->
    <div id="hold-list-modal" class="hs-overlay hidden ti-modal">
        <div class="hs-overlay-open:mt-7 ti-modal-box mt-0 ease-out lg:!max-w-4xl lg:w-full m-3 lg:!mx-auto">
            <div class="ti-modal-content">
                <div class="ti-modal-header">
                    <h3 class="ti-modal-title">
                        Hold List
                    </h3>
                    <button type="button" class="hs-dropdown-toggle ti-modal-close-btn" data-hs-overlay="#hold-list-modal">
                        <span class="sr-only">Close</span>
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>
                <div class="ti-modal-body">
                    <div class="box">
                        <div class="box-body">
                            <table class="ti-custom-table ti-custom-table-head">
                                <thead>
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Customer</th>
                                        <th>Grand Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(\App\Models\HoldPosInvoice::all() as $invoice)
                                    <tr>
                                        <td>{{ $invoice->receipt_number }}</td>
                                        <td>{{ $invoice->customer?->name }}</td>
                                        <td>{{ formatAmount($invoice->grand_total, currency_symbol(request()->activeBusiness->currency), request()->activeBusiness->id) }}</td>
                                        <td>
                                            <button class="text-success btn-hold-invoice" data-id="{{ $invoice->id }}" type="button">
                                                <i class="ri-check-line text-xl"></i>
                                            </button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- today invoices modal -->
    <div id="today-invoices-modal" class="hs-overlay hidden ti-modal">
        <div class="hs-overlay-open:mt-7 ti-modal-box mt-0 ease-out lg:!max-w-4xl lg:w-full m-3 lg:!mx-auto">
            <div class="ti-modal-content">
                <div class="ti-modal-header">
                    <h3 class="ti-modal-title">
                        Today Invoices
                    </h3>
                    <button type="button" class="hs-dropdown-toggle ti-modal-close-btn" data-hs-overlay="#today-invoices-modal">
                        <span class="sr-only">Close</span>
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>
                <div class="ti-modal-body">
                    <div class="box">
                        <div class="box-body">
                            <table class="ti-custom-table ti-custom-table-head">
                                <thead>
                                    <tr>
                                        <th>Invoice</th>
                                        <th>Customer</th>
                                        <th>Grand Total</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach(\App\Models\Receipt::where('business_id', request()->activeBusiness->id)->whereDate('created_at', today())->get() as $invoice)
                                    <tr>
                                        <td>{{ $invoice->receipt_number }}</td>
                                        <td>{{ $invoice->customer?->name }}</td>
                                        <td>{{ formatAmount($invoice->grand_total, currency_symbol(request()->activeBusiness->currency), request()->activeBusiness->id) }}</td>
                                        <td>
                                            <!-- print invoice -->
                                            <a href="{{ route('receipts.invoice_pos', $invoice->id) }}" target="_blank" class="text-info">
                                                <i class="ri-printer-line text-xl"></i>
                                            </a>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

</x-guest-layout>

<style>
    .box-body {
        padding: 1rem !important;
    }

    .content {
        margin-top: 0 !important;
    }

    .box {
        margin-bottom: 0.1rem !important;
    }

    .hide-scrollbar::-webkit-scrollbar {
        display: none;
        /* for Chrome, Safari, and Opera */
    }

    .hide-scrollbar {
        -ms-overflow-style: none;
        /* for Internet Explorer and Edge */
        scrollbar-width: none;
        /* for Firefox */
    }

    table tr td [type='text'] {
        padding: 0px !important;
        border: none !important;
        font-size: 13px !important;
    }

    table tr td [type='number'] {
        padding: 0px !important;
        border: none !important;
        font-size: 13px !important;
    }

    table tr td [type='text']:focus {
        outline: none !important;
    }

    .select2-container--default .select2-selection--multiple {
        padding: 3px !important;
    }

    .select2-selection.select2-selection--single {
        padding: 15px;
    }

    .ti-custom-table th {
        font-size: 10px !important;
    }

    /* HTML: <div class="loader"></div> */
    .loader {
        width: 40px;
        aspect-ratio: 1;
        border-radius: 50%;
        border: 3px solid #514b82;
        animation:
            l20-1 0.8s infinite linear alternate,
            l20-2 1.6s infinite linear;
    }

    @keyframes l20-1 {
        0% {
            clip-path: polygon(50% 50%, 0 0, 50% 0%, 50% 0%, 50% 0%, 50% 0%, 50% 0%)
        }

        12.5% {
            clip-path: polygon(50% 50%, 0 0, 50% 0%, 100% 0%, 100% 0%, 100% 0%, 100% 0%)
        }

        25% {
            clip-path: polygon(50% 50%, 0 0, 50% 0%, 100% 0%, 100% 100%, 100% 100%, 100% 100%)
        }

        50% {
            clip-path: polygon(50% 50%, 0 0, 50% 0%, 100% 0%, 100% 100%, 50% 100%, 0% 100%)
        }

        62.5% {
            clip-path: polygon(50% 50%, 100% 0, 100% 0%, 100% 0%, 100% 100%, 50% 100%, 0% 100%)
        }

        75% {
            clip-path: polygon(50% 50%, 100% 100%, 100% 100%, 100% 100%, 100% 100%, 50% 100%, 0% 100%)
        }

        100% {
            clip-path: polygon(50% 50%, 50% 100%, 50% 100%, 50% 100%, 50% 100%, 50% 100%, 0% 100%)
        }
    }

    @keyframes l20-2 {
        0% {
            transform: scaleY(1) rotate(0deg)
        }

        49.99% {
            transform: scaleY(1) rotate(135deg)
        }

        50% {
            transform: scaleY(-1) rotate(0deg)
        }

        100% {
            transform: scaleY(-1) rotate(-135deg)
        }
    }

    .align-top {
        vertical-align: top;
    }
</style>

<script>
    (function($) {
        "use strict";

        var decimalPlace = 2; //Default Decimal Places

        $(document).on("click", ".product", function(e) {
            if (navigator.onLine) {
                $(document).find("#loader").addClass("flex");
                $(document).find("#loader").removeClass("hidden");

                var productId = $(this).data('id');
                var selectType = "sell";

                if (productId == null) {
                    return;
                }

                $.ajax({
                    url: "/user/products/findProduct/" + productId,
                    success: function(data) {

                        // if product already exists in the cart
                        var product_id = data.product.id;
                        var product_exists = false;
                        $("#invoice-table > tbody > tr").each(function(index, elem) {
                            if ($(elem).find(".product_id").val() == product_id) {
                                product_exists = true;
                                var quantity = parseFloat($(elem).find(".quantity").val()) + 1;
                                $(elem).find(".quantity").val(quantity);
                                $(elem).find(".sub_total").val(parseFloat(quantity * parseFloat($(elem).find(".unit_cost").val())).toFixed(decimalPlace));
                            }
                        });

                        if (!product_exists) {
                            var json = JSON.parse(JSON.stringify(data));
                            var row = $("#copy-line").clone().removeAttr("id");

                            // Check Stock is not available
                            if (selectType == "sell") {
                                if (
                                    json["product"]["type"] == "product" &&
                                    json["product"]["stock_management"] == "1"
                                ) {
                                    var available_quantity = parseFloat(
                                        json["product"]["stock"]
                                    );
                                    var invoice_quantity = 0;

                                    $("#invoice-table > tbody > tr").each(function(
                                        index,
                                        elem
                                    ) {
                                        if ($(elem).find(".product_id").val() == product_id) {
                                            invoice_quantity += parseFloat($(elem).find(".quantity").val());
                                        }
                                    });

                                    if (invoice_quantity >= available_quantity) {
                                        alert(
                                            "Stock is not available for this product. Available Stock: " +
                                            available_quantity
                                        );

                                        $(document).find("#loader").removeClass("flex");
                                        $(document).find("#loader").addClass("hidden");

                                        $("#products").val(null).trigger("change");
                                        return;
                                    }
                                    $(row)
                                        .find(".quantity")
                                        .prop("max", available_quantity);
                                    if (available_quantity == 1) {
                                        $(row).find(".quantity").prop("readonly", true);
                                    }
                                }
                            }


                            $(document)
                                .find(".product_taxes")
                                .attr("name", `taxes[${json["product"]["id"]}][]`);

                            if (selectType == "sell") {
                                var sellingPrice = json["product"]["selling_price"]; // Selling Price
                            } else {
                                var sellingPrice = json["product"]["purchase_cost"]; // Purchase Price
                            }
                            decimalPlace = json["decimal_place"];

                            $(row).find(".product_id").val(json["product"]["id"]);
                            $(row).find(".product_type").val(json["product"]["type"]);
                            $(row).find(".product_name").val(json["product"]["name"]);
                            $(row)
                                .find(".description")
                                .text(json["product"]["descriptions"]);
                            $(row).find(".quantity").val(1);
                            $(row)
                                .find(".unit_cost")
                                .val(parseFloat(sellingPrice).toFixed(decimalPlace));
                            $(row)
                                .find(".sub_total")
                                .val(parseFloat(sellingPrice * 1).toFixed(decimalPlace));

                            //Append Row
                            $("#invoice-table tbody").append(row);

                            if ($(document).find(".product_taxes").val() != "") {
                                var element = $(document).find(".product_taxes");
                                $("#invoice-table > tbody > tr").each(function(index, elem) {
                                    $.each(
                                        $(element).val(),
                                        function(index, value) {
                                            var tax_name = $(element)
                                                .find('option[value="' + value + '"]')
                                                .data("tax-name");
                                            var tax_rate = $(element)
                                                .find('option[value="' + value + '"]')
                                                .data("tax-rate");

                                            if (!$("#tax-" + value).length) {
                                                $("#after-tax")
                                                    .before(`
                            <div class="grid grid-cols-12 gap-x-2 tax-field">
								<label class="md:col-span-6 col-span-12">
                                    ${tax_name}
                                </label>
                                <input type="text" class="md:text-right border-none tax-input-field !p-0 md:col-span-6 col-span-12" name="tax_amount[${value}]" id="tax-${value}" value="0" readonly />
							</div>
                            </div>`);
                                            }
                                        }
                                    );
                                });
                            }
                        }

                        $.fn.calculateTotal();

                        calculateExchangeRate();

                        $("#products").val(null).trigger("change");

                        $(document).find("#loader").removeClass("flex");
                        $(document).find("#loader").addClass("hidden");
                    },
                });
            } else {
                let productId = $(this).data('id');
                initIndexedDB().then(db => {
                    let transaction = db.transaction(['products'], 'readonly');
                    let objectStore = transaction.objectStore('products');
                    let request = objectStore.get(productId);

                    request.onsuccess = function(event) {
                        let product = event.target.result;
                        if (product) {
                            // if product already exists in the cart
                            var product_id = product.id;
                            var product_exists = false;
                            $("#invoice-table > tbody > tr").each(function(index, elem) {
                                if ($(elem).find(".product_id").val() == product_id) {
                                    product_exists = true;
                                    var quantity = parseFloat($(elem).find(".quantity").val()) + 1;
                                    $(elem).find(".quantity").val(quantity);
                                    $(elem).find(".sub_total").val(parseFloat(quantity * parseFloat($(elem).find(".unit_cost").val())).toFixed(decimalPlace));
                                }
                            });

                            if (!product_exists) {
                                // check product quantity
                                if (product.type == "product" && product.stock_management == "1") {
                                    let available_quantity = parseFloat(product.stock);
                                    let invoice_quantity = 0;

                                    $("#invoice-table > tbody > tr").each(function(index, elem) {
                                        invoice_quantity += parseFloat($(elem).find(".quantity").val());
                                    });

                                    if (invoice_quantity >= available_quantity) {
                                        alert("Stock is not available for this product. Available Stock: " + available_quantity);
                                        return;
                                    }
                                }
                                let row = $("#copy-line").clone().removeAttr("id");

                                $(row).find(".product_id").val(product.id);
                                $(row).find(".product_type").val(product.type);
                                $(row).find(".product_name").val(product.name);
                                $(row).find(".unit_cost").val(parseFloat(product.selling_price).toFixed(decimalPlace));
                                $(row).find(".quantity").val(1);
                                $(row).find(".sub_total").val(parseFloat(product.selling_price * 1).toFixed(decimalPlace));

                                $("#invoice-table tbody").append(row);
                            }

                            $.fn.calculateTotal();
                            calculateExchangeRate();
                        }
                    };
                }).catch(error => console.error('IndexedDB Error:', error));
            }
        });

        $(document).on("keyup change", ".unit_cost", function() {
            // if ($(this).val() == "") {
            //     $(this).val(0);
            // }
            if (typeof parseFloat($(this).val()) == "number") {
                $.fn.calculateTotal();
            }
        });

        $(document).on("keyup change", ".quantity", function() {
            // if ($(this).val() == "") {
            //     $(this).val(1);
            // }
            if (typeof parseFloat($(this).val()) == "number") {
                $.fn.calculateTotal();
                calculateExchangeRate();
            }
        });

        $(document).on("paste", ".quantity", function(e) {
            e.preventDefault();
        });

        //Select Tax
        $(document).on("change", ".product_taxes", function(event) {
            $('.old-tax').css('display', 'none');

            var element = $(this);
            $(".tax-field").remove();

            $("#invoice-table > tbody > tr").each(function(index, elem) {
                $.each(
                    $(element).val(),
                    function(index, value) {
                        var tax_name = $(element)
                            .find('option[value="' + value + '"]')
                            .data("tax-name");
                        var tax_rate = $(element)
                            .find('option[value="' + value + '"]')
                            .data("tax-rate");

                        if (!$("#tax-" + value).length) {
                            $("#after-tax")
                                .before(`
                            <div class="grid grid-cols-12 gap-x-2 tax-field">
								<label class="md:col-span-6 col-span-12">
                                    ${tax_name}
                                </label>
                                <input type="text" class="md:text-right border-none tax-input-field !p-0 md:col-span-6 col-span-12" name="tax_amount[${value}]" id="tax-${value}" value="0" readonly />
							</div>
                    </div>`);
                        }
                    }
                );
            });

            $.fn.calculateTotal();
            calculateExchangeRate();
        });

        $(document).on("keyup", "#discount_value", function() {
            // if ($(this).val() == "") {
            //     $(this).val(0);
            // }
            if (typeof parseFloat($(this).val()) == "number") {
                $.fn.calculateTotal();
                calculateExchangeRate();
            }

            $('#pos_discount_value').val($(this).val());
        });

        $(document).on("change", "#discount_type", function() {
            $.fn.calculateTotal();

            $('#pos_discount_type').val($(this).val());
        });

        $(document).on("click", ".btn-remove-row", function() {
            $(this)
                .closest("tr")
                .fadeOut("normal", function() {
                    $(this).remove();
                    $(".product_taxes").trigger("change");
                    $.fn.calculateTotal();
                    calculateExchangeRate();
                });
        });

        $.fn.calculateTotal = function() {
            var subTotal = 0;
            var taxAmount = 0;
            var discountAmount = 0;
            var grandTotal = 0;

            $(".tax-input-field").val(0);

            $("#invoice-table > tbody > tr").each(function(index, elem) {
                //Calculate Sub Total
                var line_qnt = parseFloat($(elem).find(".quantity").val());
                var line_unit_cost = parseFloat($(elem).find(".unit_cost").val());
                var line_total = parseFloat(line_qnt * line_unit_cost);
                $(elem).find(".sub_total").val(line_total.toFixed(decimalPlace));

                //Show Sub Total
                subTotal = parseFloat(subTotal + line_total);

                //Calculate Taxes
                $.each(
                    $(document).find(".product_taxes").val(),
                    function(index, value) {
                        var tax_rate = $(document).find(".product_taxes")
                            .find('option[value="' + value + '"]')
                            .data("tax-rate");
                        var product_tax = (line_total / 100) * tax_rate;

                        //Find Tax Field
                        if ($("#tax-" + value).length) {
                            var existingTaxAmount = parseFloat(
                                $("#tax-" + value).val()
                            );
                            var newTaxAmount = existingTaxAmount + product_tax;
                            $("#tax-" + value).val(
                                newTaxAmount.toFixed(decimalPlace)
                            );
                            taxAmount += newTaxAmount;
                        }
                    }
                );

                //Calculate Discount
                if ($("#discount_type").val() == "0") {
                    discountAmount =
                        (subTotal / 100) * parseFloat($("#discount_value").val());
                } else if ($("#discount_type").val() == "1") {
                    discountAmount = parseFloat($("#discount_value").val());
                }

                //Calculate Grand Total
                grandTotal = subTotal + taxAmount - discountAmount;
            });

            $("#sub_total").val(subTotal.toFixed(decimalPlace));
            $("#discount_amount").val(
                parseFloat(discountAmount).toFixed(decimalPlace)
            );
            $("#grand_total").val(parseFloat(grandTotal).toFixed(decimalPlace));

            var base_currency = '{{ currency_symbol(request()->activeBusiness->currency) }}';

            var sub_total = base_currency + parseFloat(subTotal).toLocaleString();
            var grand_total = base_currency + parseFloat(grandTotal).toLocaleString();
            var discount = base_currency + parseFloat(discountAmount).toLocaleString();

            $('.pos_sub_total_span').text(sub_total);
            $('.pos_grand_total_span').text(grand_total);
            $('.pos_discount_span').text(discount);
        };

        $(document).on("change", "#exchange_rate", function(e) {
            calculateExchangeRate();
        });

        $(document).on('change', '#debit_account', function() {
            $('#account_id').val($(this).val());
        })

        $(document).on("change", "#currency", function(e) {
            if (navigator.onLine) {
                console.log('online');
                $.ajax({
                    url: "/user/find_currency/" +
                        $("#currency").find(":selected").val(),
                    async: false,
                    success: function(data) {
                        if ($("#currency").find(":selected").val() != "") {
                            if (data.base_currency == 1) {
                                $("#converted_total")
                                    .parent()
                                    .closest("div")
                                    .parent()
                                    .closest("div")
                                    .addClass("hidden");
                                $("#converted_total")
                                    .parent()
                                    .closest("div")
                                    .parent()
                                    .closest("div")
                                    .removeClass("grid");

                                $("#exchange_rate").val(data.exchange_rate);
                                $("#converted_total").val(
                                    (
                                        $("#grand_total").val() * data.exchange_rate
                                    ).toFixed(decimalPlace)
                                );
                            } else {
                                $("#converted_total")
                                    .parent()
                                    .closest("div")
                                    .parent()
                                    .closest("div")
                                    .removeClass("hidden");
                                $("#converted_total")
                                    .parent()
                                    .closest("div")
                                    .parent()
                                    .closest("div")
                                    .addClass("grid");

                                $("#exchange_rate").val(data.exchange_rate);
                                $("#converted_total").val(
                                    (
                                        $("#grand_total").val() * data.exchange_rate
                                    ).toFixed(decimalPlace)
                                );

                                // pos converted total span
                                var transaction_currency = $("#currency").val();
                                var converted_total = $("#converted_total").val();
                                $('.pos_converted_total_span').text(transaction_currency + parseFloat(converted_total).toLocaleString())
                            }
                        }
                    },
                });

                // transaction currency
                $('#transaction_currency').val($("#currency").find(":selected").val());

                if ($("#currency").find(":selected").val() == "") {
                    $("#exchange_rate").closest("div").addClass("hidden");
                    $("#converted_total")
                        .parent()
                        .closest("div")
                        .parent()
                        .closest("div")
                        .addClass("hidden");
                    $("#converted_total")
                        .parent()
                        .closest("div")
                        .parent()
                        .closest("div")
                        .removeClass("grid");

                    $("#exchange_rate").val(1);
                    $("#converted_total").val($("#grand_total").val());
                }
            } else {
                // get currency from indexedDB
                let currency = $(this).val();
                console.log(currency);
                console.log('offline');
                initIndexedDB().then(db => {
                    let transaction = db.transaction(['currency'], 'readonly');
                    let objectStore = transaction.objectStore('currency');
                    let request = objectStore.get(currency);

                    request.onsuccess = function(event) {
                        let currency = event.target.result;
                        if (currency) {
                            if (currency.base_currency == 1) {
                                $("#converted_total")
                                    .parent()
                                    .closest("div")
                                    .parent()
                                    .closest("div")
                                    .addClass("hidden");
                                $("#converted_total")
                                    .parent()
                                    .closest("div")
                                    .parent()
                                    .closest("div")
                                    .removeClass("grid");

                                $("#exchange_rate").val(currency.exchange_rate);
                                $("#converted_total").val(
                                    (
                                        $("#grand_total").val() * currency.exchange_rate
                                    ).toFixed(decimalPlace)
                                );
                            } else {
                                $("#converted_total")
                                    .parent()
                                    .closest("div")
                                    .parent()
                                    .closest("div")
                                    .removeClass("hidden");
                                $("#converted_total")
                                    .parent()
                                    .closest("div")
                                    .parent()
                                    .closest("div")
                                    .addClass("grid");

                                $("#exchange_rate").val(currency.exchange_rate);
                                $("#converted_total").val(
                                    (
                                        $("#grand_total").val() * currency.exchange_rate
                                    ).toFixed(decimalPlace)
                                );

                                // pos converted total span
                                var transaction_currency = $("#currency").val();
                                var converted_total = $("#converted_total").val();
                                $('.pos_converted_total_span').text(transaction_currency + parseFloat(converted_total).toLocaleString())
                            }
                        }
                    };
                }).catch(error => console.error('IndexedDB Error:', error));
            }
        });

        function calculateExchangeRate() {
            var currency = $("#currency").val();
            var exchange_rate = $("#exchange_rate").val();
            var grand_total = $("#grand_total").val();

            if (currency != "") {
                if (exchange_rate == 1) {
                    $("#converted_total").val(grand_total);
                } else {
                    var converted_total = grand_total * exchange_rate;
                    $("#converted_total").val(
                        converted_total.toFixed(decimalPlace)
                    );
                    $('.pos_converted_total_span').text(currency + parseFloat(converted_total).toLocaleString())
                }
            }
        }

        $(document).on('click', '#pay', function() {
            // if currency is empty
            if ($("#currency").val() == "") {
                // select base currency
                $("#currency").val("{{ request()->activeBusiness->currency }}");
                $("#currency").trigger("change");
            }

            $("#cash").prop("checked", true);

            $('#credit_cash').val('cash');
            $('#provider').removeClass('grid');
            $('#provider').addClass('hidden');

            $('.due_date').removeClass('grid');
            $('.due_date').addClass('hidden');

            var sub_total = $("#sub_total").val();
            var grand_total = $("#grand_total").val();
            var converted_total = $("#converted_total").val();
            var discount = $("#discount_amount").val();
            var tax_amount = $("#tax").val();

            var transaction_currency = $("#currency").val();
            var base_currency = '{{ currency_symbol(request()->activeBusiness->currency) }}';

            sub_total = base_currency + parseFloat(sub_total).toLocaleString();
            grand_total = base_currency + parseFloat(grand_total).toLocaleString();
            converted_total = transaction_currency + parseFloat(converted_total).toLocaleString();
            discount = base_currency + parseFloat(discount).toLocaleString();
            tax_amount = base_currency + parseFloat(tax_amount).toLocaleString();

            $('.sub_total_span').text(sub_total);
            $('.grand_total_span').text(grand_total);
            $('.converted_total_span').text(converted_total);
            $('.discount_span').text(discount);
            $('.tax_span').text(tax_amount);

            if (sub_total == "" && grand_total == "" && converted_total == "") {
                alert("Please add product to cart");
                return false;
            }
        });

        $(document).on('click', '#appointment_btn', function() {
            // if currency is empty
            if ($("#currency").val() == "") {
                // select base currency
                $("#currency").val("{{ request()->activeBusiness->currency }}");
                $("#currency").trigger("change");
            }

            $("#appointment_cash").prop("checked", true);

            $('#credit_cash').val('cash');
            $('#appointment_provider').removeClass('grid');
            $('#appointment_provider').addClass('hidden');

            $('.due_date').removeClass('grid');
            $('.due_date').addClass('hidden');

            var sub_total = $("#sub_total").val();
            var grand_total = $("#grand_total").val();
            var converted_total = $("#converted_total").val();
            var discount = $("#discount_amount").val();
            var tax_amount = $("#tax").val();

            var transaction_currency = $("#currency").val();
            var base_currency = '{{ currency_symbol(request()->activeBusiness->currency) }}';

            sub_total = base_currency + parseFloat(sub_total).toLocaleString();
            grand_total = base_currency + parseFloat(grand_total).toLocaleString();
            converted_total = transaction_currency + parseFloat(converted_total).toLocaleString();
            discount = base_currency + parseFloat(discount).toLocaleString();
            tax_amount = base_currency + parseFloat(tax_amount).toLocaleString();

            $('.sub_total_span').text(sub_total);
            $('.grand_total_span').text(grand_total);
            $('.converted_total_span').text(converted_total);
            $('.discount_span').text(discount);
            $('.tax_span').text(tax_amount);

            if (sub_total == "" && grand_total == "" && converted_total == "") {
                alert("Please add product to cart");
                return false;
            }
        });

        $(document).ready(function() {
            var lastTime = 0;
            var typingTimer;
            var barcodeScanTimeThreshold = 10; // milliseconds, adjust as needed
            var barcodeBuffer = '';

            $('#search_term').on('keyup', function(e) {
                var currentTime = new Date().getTime();
                var timeDifference = currentTime - lastTime;
                lastTime = currentTime;
                var search_term = $(this).val();

                if (timeDifference < barcodeScanTimeThreshold) {
                    barcodeBuffer += String.fromCharCode(e.which);
                } else {
                    barcodeBuffer = String.fromCharCode(e.which);
                }

                console.log("Current barcodeBuffer: " + barcodeBuffer);

                clearTimeout(typingTimer);
                typingTimer = setTimeout(function() {
                    if (navigator.onLine) {
                        console.log("Online: barcodeBuffer length is " + barcodeBuffer.length);
                        if (e.keyCode == 13) {
                            handleBarcodeInput(search_term);
                        } else {
                            handleKeyboardInput(search_term);
                        }
                    } else {
                        console.log("Offline: barcodeBuffer length is " + barcodeBuffer.length);
                        if (e.keyCode == 13) {
                            initIndexedDB().then(db => {
                                let transaction = db.transaction(['products'], 'readonly');
                                let objectStore = transaction.objectStore('products');
                                let request = objectStore.getAll();

                                request.onsuccess = function(event) {
                                    let products = event.target.result;

                                    console.log('scanned');
                                    handleOfflineBarcodeInput(products, search_term);
                                };
                            }).catch(error => console.error('IndexedDB Error:', error));
                        } else {
                            initIndexedDB().then(db => {
                                let transaction = db.transaction(['products'], 'readonly');
                                let objectStore = transaction.objectStore('products');
                                let request = objectStore.getAll();

                                request.onsuccess = function(event) {
                                    let products = event.target.result;
                                    handleOfflineKeyboardInput(products, search_term);
                                };
                            }).catch(error => console.error('IndexedDB Error:', error));
                        }
                    }
                    barcodeBuffer = '';
                }, barcodeScanTimeThreshold + 10); // slightly more than threshold
            });

            function handleBarcodeInput(search_term) {
                if (search_term != "") {
                    $.ajax({
                        url: "/user/products/getProductByBarcode/" + search_term,
                        success: function(data) {
                            // Process the barcode data
                            processProductData(data);
                        }
                    });
                }
            }

            function handleKeyboardInput(search_term) {
                if (search_term != "") {
                    $.ajax({
                        url: "/user/products/search_product/" + search_term,
                        success: function(data) {
                            var json = JSON.parse(JSON.stringify(data));
                            var product_list = "";

                            $.each(json, function(index, value) {
                                product_list += `
                        <div class="border-t border-gray-400 p-1 cursor-pointer product" data-id="${value.id}">
                            <p>${value.name}</p>
                        </div>`;
                            });

                            $("#product_list").html(product_list);
                        }
                    });
                } else {
                    $("#product_list").html("");
                }
            }

            function handleOfflineBarcodeInput(products, search_term) {
                let product = products.find(product => product.code == search_term);
                if (product) {
                    // Process the barcode data
                    processProductData(product);
                }
            }

            function handleOfflineKeyboardInput(products, search_term) {
                if (search_term != "") {
                    let search_term = $('#search_term').val();
                    let filteredProducts = products.filter(product => product.name.toLowerCase().includes(search_term.toLowerCase()));
                    let product_list = '';

                    filteredProducts.forEach(product => {
                        product_list += `
                <div class="border-t border-gray-400 p-1 cursor-pointer product" data-id="${product.id}">
                    <p>${product.name}</p>
                </div>`;
                    });

                    $("#product_list").html(product_list);
                } else {
                    $("#product_list").html("");
                }
            }

            function processProductData(productData) {
                if(productData.id == null) {
                    alert('Product not found');
                    return;
                }
                var product_id = productData.id;
                var product_exists = false;
                $("#invoice-table > tbody > tr").each(function(index, elem) {
                    if ($(elem).find(".product_id").val() == product_id) {
                        product_exists = true;
                        var quantity = parseFloat($(elem).find(".quantity").val()) + 1;
                        $(elem).find(".quantity").val(quantity);
                        $(elem).find(".sub_total").val(parseFloat(quantity * parseFloat($(elem).find(".unit_cost").val())).toFixed(decimalPlace));
                    }
                });

                if (!product_exists) {
                    var row = $("#copy-line").clone().removeAttr("id");

                    $(row).find(".product_id").val(productData.id);
                    $(row).find(".product_type").val(productData.type);
                    $(row).find(".product_name").val(productData.name);
                    $(row).find(".unit_cost").val(parseFloat(productData.selling_price).toFixed(decimalPlace));
                    $(row).find(".quantity").val(1);
                    $(row).find(".sub_total").val(parseFloat(productData.selling_price * 1).toFixed(decimalPlace));

                    $("#invoice-table tbody").append(row);
                }

                $('#search_term').val('');
                $.fn.calculateTotal();
                calculateExchangeRate();
            }

            // credit_cash make cash
            $('#credit_cash').val('cash');
        });

        // click outside or select product hide searched products
        $(document).on('click', function(e) {
            if (!$(e.target).closest('.product_list').length) {
                $("#product_list").html("");
            }
        });

        $(document).on('click', '#save_invoice', function() {
            $("#appointment").val(0);

            // if currency is empty
            if ($("#currency").val() == "") {
                // select base currency
                $("#currency").val("{{ request()->activeBusiness->currency }}");
                $("#currency").trigger("change");
            }

            if ($("#credit_cash").val() == 'credit' && $("#client_id").val() == '') {
                alert('Please select a customer/client');
                return false;
            }

            if ($("#credit_cash").val() == 'provider' && ($("#customer_id").val() == '' || $("#client_id").val() == '')) {
                alert('Please select a provider and a client');
                return false;
            }

            if($("#credit_cash").val() == 'cash' && $('#account_id').val() == '') {
                alert('Please select a debit account');
                return false;
            }

            saveInvoice();
        });

        $(document).on('click', '#save_appointment', function() {
            $("#appointment").val(1);

            // if currency is empty
            if ($("#currency").val() == "") {
                // select base currency
                $("#currency").val("{{ request()->activeBusiness->currency }}");
                $("#currency").trigger("change");
            }

            if ($("#credit_cash").val() == 'credit' && $("#client_id").val() == '') {
                alert('Please select a customer/client');
                return false;
            }

            if ($("#credit_cash").val() == 'provider' && ($("#customer_id").val() == '' || $("#client_id").val() == '')) {
                alert('Please select a provider and a client');
                return false;
            }

            if($("#credit_cash").val() == 'cash' && $('#account_id').val() == '') {
                alert('Please select a debit account');
                return false;
            }

            saveInvoice();
        });

        function saveInvoice() {
            // submit form
            if (navigator.onLine) {
                $("#invoice_form").submit();
            } else {
                // save to indexedDB
                let invoice = {
                    product_id: [],
                    product_type: [],
                    product_name: [],
                    unit_cost: [],
                    quantity: [],
                    sub_total: '',
                    currency: '',
                    exchange_rate: '',
                    account_id: '',
                    discount_type: '',
                    discount_value: '',
                    hold_pos_id: '',
                    customer_id: '',
                    discount_amount: '',
                    grand_total: '',
                    converted_total: '',
                    client_id: '',
                    payment_method: '',
                    customer_id: '',
                    invoice_date: '',
                    credit_cash: '',
                    due_date: '',
                };

                $("#invoice-table > tbody > tr").each(function(index, elem) {
                    invoice.product_id.push($(elem).find(".product_id").val());
                    invoice.product_type.push($(elem).find(".product_type").val());
                    invoice.product_name.push($(elem).find(".product_name").val());
                    invoice.unit_cost.push($(elem).find(".unit_cost").val());
                    invoice.quantity.push($(elem).find(".quantity").val());
                    invoice.sub_total = $("#sub_total").val();
                    invoice.currency = $("#currency").val();
                    invoice.exchange_rate = $("#exchange_rate").val();
                    invoice.account_id = $("#account_id").val();
                    invoice.discount_type = $("#discount_type").val();
                    invoice.discount_value = $("#discount_value").val();
                    invoice.customer_id = $("#customer_id").val();
                    invoice.discount_amount = $("#discount_amount").val();
                    invoice.grand_total = $("#grand_total").val();
                    invoice.converted_total = $("#converted_total").val();
                });

                invoice.client_id = $("#client_id").val();
                invoice.customer_id = $("#customer_id").val();
                invoice.payment_method = $("#payment_method").val();
                invoice.invoice_date = $("#invoice_date").val();
                invoice.credit_cash = $("#credit_cash").val();
                invoice.due_date = $("#due_date").val();
                invoice.credit_cash = $("#credit_cash").val();
                invoice.appointment = $("#appointment").val();

                initIndexedDB().then(db => {
                    let transaction = db.transaction(['invoices'], 'readwrite');
                    let objectStore = transaction.objectStore('invoices');
                    let request = objectStore.add(invoice);

                    request.onsuccess = function(event) {
                        console.log('Invoice saved to IndexedDB');
                        resetForm();
                    };
                }).catch(error => console.error('IndexedDB Error:', error));

                // show success message
                alert('Invoice saved successfully');
            }
        }

        $(document).on('click', '#hold', function() {
            // if currency is empty
            if ($("#currency").val() == "") {
                // select base currency
                $("#currency").val("{{ request()->activeBusiness->currency }}");
                $("#currency").trigger("change");
            }
            // submit form
            $("#invoice_form").attr('action', '/user/hold_pos_invoices').submit();
        });

        $(document).on('click', '#cancel', function() {
            resetForm();
        });

        // click usd change currency to usd and trigger change event
        $(document).on('click', '#usd', function() {
            $('#currency').val('USD').trigger('change');
        });

        // reset form
        function resetForm() {
            $("#invoice-table tbody").html("");
            $("#sub_total").val("");
            $("#discount_amount").val("");
            $("#grand_total").val("");
            $("#converted_total").val("");
            $("#discount_value").val("");
            $("#discount_type").val("");
            $("#payment_method").val("");
            $("#currency").val("");
            $("#exchange_rate").val("");
            $("#tax").val("");
            $("#products").val("").trigger("change");
            $("#product_list").html("");
            $(".tax-field").remove();
            $('.sub_total_span').text('');
            $('.grand_total_span').text('');
            $('.converted_total_span').text('');
            $('.discount_span').text('');
            $('.tax_span').text('');
            $('.pos_sub_total_span').text('');
            $('.pos_grand_total_span').text('');
            $('.pos_converted_total_span').text('');
            $('.pos_discount_span').text('');
            $('#search_term').val('');
        }

        // click slsh change currency to slsh and trigger change event
        $(document).on('click', '#slsh', function() {
            $('#currency').val('SLSH').trigger('change');
        });

        // hold invoice
        $(document).on('click', '.btn-hold-invoice', function() {
            var invoice_id = $(this).data('id');
            $('#hold_pos_id').val(invoice_id);
            $.ajax({
                url: "/user/hold_pos_invoices/" + invoice_id,
                success: function(data) {
                    var json = JSON.parse(JSON.stringify(data));
                    var product_list = "";

                    $.each(json.items, function(index, value) {
                        product_list += `
                        <tr class="line-item">
                            <td class="align-top">
                                <input type="hidden" class="product_id" name="product_id[]" value="${value.product_id}">
                                <input type="hidden" class="product_type" name="product_type[]" value="${value.product_type}">
                                <div class="flex flex-col">
                                    <input type="text" readonly class="w-full product_name" name="product_name[]" value="${value.product_name}">
                                    <input type="text" class="w-full unit_cost" name="unit_cost[]" value="${parseFloat(value.unit_cost).toFixed(2)}">
                                    <input type="number" class="w-full quantity" name="quantity[]" min="1" value="${parseFloat(value.quantity).toFixed(2)}">
                                </div>
                            </td>
                            <td class="input-sm align-top"><input type="text" class="w-full text-right sub_total" name="sub_total[]" readonly value="${parseFloat(value.sub_total).toFixed(2)}"></td>
                            <td class="input-xxs text-center align-top">
                                <button class="text-danger btn-remove-row" type="button">
                                    <i class="ri-delete-bin-2-line"></i>
                                </button>
                            </td>`;
                    });

                    $("#invoice-table tbody").html(product_list);
                    $("#sub_total").val(parseFloat(json.sub_total).toFixed(2));
                    $("#discount_amount").val(parseFloat(json.discount).toFixed(2));
                    $("#grand_total").val(parseFloat(json.grand_total).toFixed(2));
                    $("#converted_total").val(parseFloat(json.converted_total).toFixed(2));
                    $("#discount_value").val(json.discount_value);
                    $("#discount_type").val(json.discount_type);
                    $("#payment_method").val(json.payment_method);
                    $("#currency").val(json.currency).trigger('change');
                    $("#exchange_rate").val(json.exchange_rate);
                    $("#products").val("").trigger("change");
                    // for each product tax get id and set value to product taxes trigger change event
                    $.each(json.taxes, function(index, value) {
                        $('.product_taxes').val(value.tax_id).trigger('change');
                    });
                    $("#product_list").html("");
                    $('.sub_total_span').text(parseFloat(json.sub_total).toFixed(2));
                    $('.grand_total_span').text(parseFloat(json.grand_total).toFixed(2));
                    $('.converted_total_span').text(parseFloat(json.converted_total).toFixed(2));
                    $('.discount_span').text(parseFloat(json.discount).toFixed(2));
                    $('.tax_span').text(parseFloat(json.tax).toFixed(2));

                    var base_currency = '{{ currency_symbol(request()->activeBusiness->currency) }}';

                    var sub_total = base_currency + parseFloat(json.sub_total).toLocaleString();
                    var grand_total = base_currency + parseFloat(json.grand_total).toLocaleString();
                    var discount = base_currency + parseFloat(json.discount).toLocaleString();
                    var converted_total = json.currency + parseFloat(json.converted_total).toLocaleString();

                    $('.pos_sub_total_span').text(sub_total);
                    $('.pos_grand_total_span').text(grand_total);
                    $('.pos_discount_span').text(discount);
                    $('.pos_converted_total_span').text(converted_total);
                }
            });
        });

        // prescription products
        $(document).on('click', '.btn-prescription-product', function() {
            var pp_id = $(this).data('id');
            $('#prescription_products_id').val(pp_id);
            $.ajax({
                url: "/user/prescription_products/" + pp_id,
                success: function(data) {
                    var json = JSON.parse(JSON.stringify(data));
                    var product_list = "";

                    $.each(json.items, function(index, value) {
                        product_list += `
                        <tr class="line-item">
                            <td class="align-top">
                                <input type="hidden" class="product_id" name="product_id[]" value="${value.product_id}">
                                <input type="hidden" class="product_type" name="product_type[]" value="${value.product_type}">
                                <div class="flex flex-col">
                                    <input type="text" readonly class="w-full product_name" name="product_name[]" value="${value.product_name}">
                                    <input type="text" class="w-full unit_cost" name="unit_cost[]" value="${parseFloat(value.unit_cost).toFixed(2)}">
                                    <input type="number" class="w-full quantity" name="quantity[]" min="1" value="${parseFloat(value.quantity).toFixed(2)}">
                                </div>
                            </td>
                            <td class="input-sm align-top"><input type="text" class="w-full text-right sub_total" name="sub_total[]" readonly value="${parseFloat(value.sub_total).toFixed(2)}"></td>
                            <td class="input-xxs text-center align-top">
                                <button class="text-danger btn-remove-row" type="button">
                                    <i class="ri-delete-bin-2-line"></i>
                                </button>
                            </td>`;
                    });

                    $("#invoice-table tbody").html(product_list);
                    $("#sub_total").val(parseFloat(json.sub_total).toFixed(2));
                    $("#discount_amount").val(parseFloat(0).toFixed(2));
                    $("#grand_total").val(parseFloat(json.grand_total).toFixed(2));
                    $("#converted_total").val(parseFloat(json.grand_total).toFixed(2));
                    $("#currency").val(json.currency).trigger('change');
                    $("#exchange_rate").val(json.exchange_rate);
                    $("#products").val("").trigger("change");
                    $("#client_id").val(json.customer.id);

                    // select a client
                    $('.client').val(json.customer.id).trigger('change');

                    $("#product_list").html("");
                    $('.sub_total_span').text(parseFloat(json.sub_total).toFixed(2));
                    $('.grand_total_span').text(parseFloat(json.grand_total).toFixed(2));
                    $('.converted_total_span').text(parseFloat(json.grand_total).toFixed(2));
                    $('.discount_span').text(parseFloat(0).toFixed(2));
                    $('.tax_span').text(parseFloat(0).toFixed(2));

                    var base_currency = '{{ currency_symbol(request()->activeBusiness->currency) }}';

                    var sub_total = base_currency + parseFloat(json.sub_total).toLocaleString();
                    var grand_total = base_currency + parseFloat(json.grand_total).toLocaleString();
                    var discount = base_currency + parseFloat(0).toLocaleString();
                    var converted_total = json.currency + parseFloat(json.grand_total).toLocaleString();

                    $('.pos_sub_total_span').text(sub_total);
                    $('.pos_grand_total_span').text(grand_total);
                    $('.pos_discount_span').text(discount);
                    $('.pos_converted_total_span').text(converted_total);
                }
            });
        });

        // on change customer
        $(document).on('change', '.client', function() {
            $('#client_id').val($(this).val());
        });

        // on change customer
        $(document).on('change', '.provider', function() {
            $('#customer_id').val($(this).val());
        });


        // register service worker
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('/service-worker.js')
                .then(function(registration) {
                    console.log('Service Worker registered with scope:', registration.scope);
                })
                .catch(function(error) {
                    console.log('Service Worker registration failed:', error);
                });
        }

        // Initialize IndexedDB
        function initIndexedDB() {
            return new Promise((resolve, reject) => {
                let request = indexedDB.open('POSDatabase', 1);

                request.onupgradeneeded = function(event) {
                    let db = event.target.result;
                    if (!db.objectStoreNames.contains('products')) {
                        db.createObjectStore('products', {
                            keyPath: 'id'
                        });
                    }

                    if (!db.objectStoreNames.contains('currency')) {
                        db.createObjectStore('currency', {
                            keyPath: 'name'
                        });
                    }

                    if (!db.objectStoreNames.contains('tax')) {
                        db.createObjectStore('tax', {
                            keyPath: 'id'
                        });
                    }

                    if (!db.objectStoreNames.contains('invoices')) {
                        db.createObjectStore('invoices', {
                            keyPath: 'id',
                            autoIncrement: true
                        });
                    }
                };

                request.onsuccess = function(event) {
                    resolve(event.target.result);
                };

                request.onerror = function(event) {
                    reject(event.target.error);
                };
            });
        }

        // Add products to IndexedDB
        function addProductsToIndexedDB(products) {
            initIndexedDB().then(db => {
                let transaction = db.transaction(['products'], 'readwrite');
                let objectStore = transaction.objectStore('products');
                products.forEach(product => objectStore.put(product));
            }).catch(error => console.error('IndexedDB Error:', error));
        }

        // Fetch products from the server and store them in IndexedDB
        function fetchAndStoreProducts() {
            $.ajax({
                url: '/user/pos/products',
                method: 'GET',
                success: function(data) {
                    addProductsToIndexedDB(data);
                },
                error: function(error) {
                    console.error('Error fetching products:', error);
                }
            });
        }

        // Add currency to IndexedDB
        function addCurrencyToIndexedDB(currencies) {
            initIndexedDB().then(db => {
                let transaction = db.transaction(['currency'], 'readwrite');
                let objectStore = transaction.objectStore('currency');
                currencies.forEach(currency => objectStore.put(currency));
            }).catch(error => console.error('IndexedDB Error:', error));
        }

        // Fetch currency from the server and store them in IndexedDB
        function fetchAndStoreCurrency() {
            $.ajax({
                url: '/user/pos/currency',
                method: 'GET',
                success: function(data) {
                    addCurrencyToIndexedDB(data);
                },
                error: function(error) {
                    console.error('Error fetching currencies:', error);
                }
            });
        }

        // Add tax to IndexedDB
        function addTaxToIndexedDB(taxes) {
            initIndexedDB().then(db => {
                let transaction = db.transaction(['tax'], 'readwrite');
                let objectStore = transaction.objectStore('tax');
                taxes.forEach(tax => objectStore.put(tax));
            }).catch(error => console.error('IndexedDB Error:', error));
        }

        // Fetch tax from the server and store them in IndexedDB
        function fetchAndStoreTax() {
            $.ajax({
                url: '/user/pos/tax',
                method: 'GET',
                success: function(data) {
                    addTaxToIndexedDB(data);
                },
                error: function(error) {
                    console.error('Error fetching taxes:', error);
                }
            });
        }

        $(document).ready(function() {
            fetchAndStoreProducts();
            fetchAndStoreCurrency();
            fetchAndStoreTax();
        });

        // Function to sync form data with the server
        function syncFormDataWithServer(data) {
            $.ajax({
                url: "/user/pos/store",
                method: "POST",
                data: JSON.stringify(data),
                contentType: "application/json",
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    console.log("Form data synced with server:", response);
                },
                error: function(xhr, status, error) {
                    console.error("Error syncing form data with server:", error);
                },
            });
        }

        // Function to sync all stored form data from IndexedDB when online
        function syncAllDataFromIndexedDB() {
            initIndexedDB().then((db) => {
                var transaction = db.transaction(["invoices"], "readonly");
                var objectStore = transaction.objectStore("invoices");
                var request = objectStore.getAll();

                request.onsuccess = function(event) {
                    var allData = event.target.result;
                    allData.forEach((data) => {
                        syncFormDataWithServer(data);
                    });

                    // Clear all data from IndexedDB after syncing
                    clearIndexedDB();
                };

                request.onerror = function(event) {
                    console.error(
                        "Error retrieving data from IndexedDB:",
                        event.target.error
                    );
                };
            });
        }

        // Function to clear all data from IndexedDB
        function clearIndexedDB() {
            initIndexedDB().then((db) => {
                var transaction = db.transaction(["invoices"], "readwrite");
                var objectStore = transaction.objectStore("invoices");
                var request = objectStore.clear();

                request.onsuccess = function(event) {
                    console.log("IndexedDB cleared");
                };

                request.onerror = function(event) {
                    console.error("Error clearing IndexedDB:", event.target.error);
                };
            });
        }

        // Listen for network status changes to sync data
        window.addEventListener("online", function() {
            console.log("Network connection restored, syncing data from IndexedDB...");
            syncAllDataFromIndexedDB();
        });

        $(document).on('click', '.category', function() {
            var category_id = $(this).data('id');
            $.ajax({
                url: "/user/pos/products/category/" + category_id,
                success: function(data) {
                    var json = JSON.parse(JSON.stringify(data));
                    var list_products = "";

                    $.each(json, function(index, value) {
                        list_products += `
                        <div class="col-span-4 xl:col-span-3 cursor-pointer product border border-gray-400 drop-shadow-lg p-2 rounded-md" data-id="${value.id}">
                            <div class="space-y-2 relative">
                                @if(get_business_option('pos_show_image') == 1)
                                <div class="w-20 h-20 rounded-md">
                                    <img src="/uploads/media/${value.image}" class="w-full h-full object-contain">
                                </div>
                                @endif
                                <p class="text-sm font-bold">${value.name}</p>
                                @if(get_business_option('pos_show_image') == 1)
                                <p class="font-semibold absolute top-0 right-0 bg-green-100 px-1">
                                    <span class="bg-green-100 px-1">
                                        ${parseFloat(value.selling_price).toFixed(2)}
                                    </span>
                                </p>
                                @else
                                <p class="font-semibold">
                                    <span class="bg-green-100 px-1">
                                        ${parseFloat(value.selling_price).toFixed(2)}
                                    </span>
                                </p>
                                @endif
                            </div>
                        </div>`;
                    });

                    $(".list-products").html(list_products);
                }
            });
        });

        $(document).on('click', '.all-category', function() {
            $.ajax({
                url: "/user/pos/products",
                success: function(data) {
                    var json = JSON.parse(JSON.stringify(data));
                    var list_products = "";

                    $.each(json, function(index, value) {
                        list_products += `<div class="col-span-4 xl:col-span-3 cursor-pointer product border border-gray-400 drop-shadow-lg p-2 rounded-md" data-id="${value.id}">
                        <div class="space-y-2 relative">
                            @if(get_business_option('pos_show_image') == 1)
                            <div class="w-20 h-20 rounded-md">
                                <img src="/uploads/media/${value.image}" class="w-full h-full object-contain">
                            </div>
                            @endif
                            <p class="text-sm font-bold">${value.name}</p>
                            @if(get_business_option('pos_show_image') == 1)
                            <p class="font-semibold absolute top-0 right-0 bg-green-100 px-1">
                                <span class="bg-green-100 px-1">
                                    ${parseFloat(value.selling_price).toFixed(2)}
                                </span>
                            </p>
                            @else
                            <p class="font-semibold">
                                <span class="bg-green-100 px-1">
                                    ${parseFloat(value.selling_price).toFixed(2)}
                                </span>
                            </p>
                            @endif
                        </div>
                    </div>`;
                    });

                    $(".list-products").html(list_products);
                }
            });
        });

        $(document).on('click', '#cash', function() {
            $('#credit_cash').val('cash');
            $('#provider').removeClass('grid');
            $('#provider').addClass('hidden');

            $('.due_date').removeClass('grid');
            $('.due_date').addClass('hidden');

            $(".debit_account").removeClass('hidden');
            $(".debit_account").addClass('grid');
        })

        $(document).on('click', '#credit', function() {
            $('#credit_cash').val('credit');
            $('#provider').addClass('hidden');
            $('#provider').removeClass('grid');

            $('.due_date').removeClass('hidden');
            $('.due_date').addClass('grid');

            $(".debit_account").addClass('hidden');
            $(".debit_account").removeClass('grid');
        })

        $(document).on('click', '#provider_btn', function() {
            $('#credit_cash').val('provider');
            $('#provider').removeClass('hidden');
            $('#provider').addClass('grid');

            $('.due_date').removeClass('hidden');
            $('.due_date').addClass('grid');

            $(".debit_account").addClass('hidden');
            $(".debit_account").removeClass('grid');
        })

        $(document).on('click', '#appointment_cash', function() {
            $('#credit_cash').val('cash');
            $('#appointment_provider').removeClass('grid');
            $('#appointment_provider').addClass('hidden');

            $('.due_date').removeClass('grid');
            $('.due_date').addClass('hidden');

            $(".debit_account").removeClass('hidden');
            $(".debit_account").addClass('grid');
        })

        $(document).on('click', '#appointment_credit', function() {
            $('#credit_cash').val('credit');
            $('#appointment_provider').addClass('hidden');
            $('#appointment_provider').removeClass('grid');

            $('.due_date').removeClass('hidden');
            $('.due_date').addClass('grid');

            $(".debit_account").addClass('hidden');
            $(".debit_account").removeClass('grid');
        })

        $(document).on('click', '#appointment_provider_btn', function() {
            $('#credit_cash').val('provider');
            $('#appointment_provider').removeClass('hidden');
            $('#appointment_provider').addClass('grid');

            $('.due_date').removeClass('hidden');
            $('.due_date').addClass('grid');

            $(".debit_account").addClass('hidden');
            $(".debit_account").removeClass('grid');
        })

        $(document).on('change', '.invoice_date', function() {
            $('#invoice_date').val($(this).val());
        })

        $(document).on('change', '.due_date', function() {
            $('#due_date').val($(this).val());
        })

        $(document).on('change', '.appointment_provider', function() {
            $('#customer_id').val($(this).val());
        })
    })(jQuery);

    $(document).on('submit', 'form', function() {
        $('.submit-btn').attr('disabled', true);
        $('.submit-btn').html('<div class="ti-spinner text-white" role="status" aria-label="loading"> <span class="sr-only">Loading...</span> </div> {{ _lang('Processing') }}');
    });
</script>

<style>
    .ti-modal {
        width: 100% !important;
    }
</style>
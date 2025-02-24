<x-app-layout>
    <!-- Start::main-content -->
    <div class="main-content">
        <x-page-header title="Prescription" page="user" subpage="create" />

        <div class="box">
            <div class="box-header text-center">
                <h5>{{ _lang('Edit Prescription') }}</h5>
            </div>
            <div class="box-body">
                <form method="post" class="validate" autocomplete="off" action="{{ route('prescriptions.update', $id) }}" enctype="multipart/form-data">
                    {{ csrf_field() }}
                    <input name="_method" type="hidden" value="PATCH">
                    <div class="grid grid-cols-12 gap-x-2">
                        <div class="col-span-12">
                            <div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
                                <table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
                                    <tr>
                                        <td>Customer</td>
                                        <td>
                                            <select name="customer_id" class="selectize auto-select" data-selected="{{ $prescription->customer_id }}">
                                                <option value="">{{ _lang('Select One') }}</option>
                                                @foreach(\App\Models\Customer::all() as $customer)
                                                <option value="{{ $customer->id }}">{{ $customer->name }} - {{ $customer->mobile }}</option>
                                                @endforeach
                                            </select>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Date</td>
                                        <td>
                                            <x-text-input type="text" name="date" id="date" class="flatpickr" value="{{ \Carbon\Carbon::createFromFormat(get_date_format(), $prescription->date)->format('d-m-Y') }}" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>Result Date</td>
                                        <td>
                                            <x-text-input type="text" name="result_date" id="date" class="flatpickr" value="{{ \Carbon\Carbon::createFromFormat(get_date_format(), $prescription->result_date)->format('d-m-Y') }}" />
                                        </td>
                                    </tr>
                                </table>
                            </div>
                            <div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
                                <table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
                                    <thead>
                                        <tr>
                                            <th>RX</th>
                                            <th colspan="4">Right Eye</th>
                                            <th colspan="4">Left Eye</th>
                                        </tr>
                                        <tr>
                                            <th></th>
                                            <th>Sph</th>
                                            <th>Cyl</th>
                                            <th>Axis</th>
                                            <th>VA</th>
                                            <th>Sph</th>
                                            <th>Cyl</th>
                                            <th>Axis</th>
                                            <th>VA</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>Dist</td>
                                            <td>
                                                <x-text-input type="text" name="dist_sph_re" value="{{ $prescription->dist_sph_re }}" />
                                            </td>
                                            <td>
                                                <x-text-input type="text" name="dist_cyl_re" value="{{ $prescription->dist_cyl_re }}" />
                                            </td>
                                            <td>
                                                <x-text-input type="text" name="dist_axis_re" value="{{ $prescription->dist_axis_re }}" />
                                            </td>
                                            <td>
                                                <x-text-input type="text" name="dist_va_re" value="{{ $prescription->dist_va_re }}" />
                                            </td>
                                            <td>
                                                <x-text-input type="text" name="dist_sph_le" value="{{ $prescription->dist_sph_le }}" />
                                            </td>
                                            <td>
                                                <x-text-input type="text" name="dist_cyl_le" value="{{ $prescription->dist_cyl_le }}" />
                                            </td>
                                            <td>
                                                <x-text-input type="text" name="dist_axis_le" value="{{ $prescription->dist_axis_le }}" />
                                            </td>
                                            <td>
                                                <x-text-input type="text" name="dist_va_le" value="{{ $prescription->dist_va_le }}" />
                                            </td>
                                        </tr>
                                        <tr>
                                            <td>Near</td>
                                            <td>
                                                <x-text-input type="text" name="near_sph_re" value="{{ $prescription->near_sph_re }}" />
                                            </td>
                                            <td>
                                                <x-text-input type="text" name="near_cyl_re" value="{{ $prescription->near_cyl_re }}" />
                                            </td>
                                            <td>
                                                <x-text-input type="text" name="near_axis_re" value="{{ $prescription->near_axis_re }}" />
                                            </td>
                                            <td>
                                                <x-text-input type="text" name="near_va_re" value="{{ $prescription->near_va_re }}" />
                                            </td>
                                            <td>
                                                <x-text-input type="text" name="near_sph_le" value="{{ $prescription->near_sph_le }}" />
                                            </td>
                                            <td>
                                                <x-text-input type="text" name="near_cyl_le" value="{{ $prescription->near_cyl_le }}" />
                                            </td>
                                            <td>
                                                <x-text-input type="text" name="near_axis_le" value="{{ $prescription->near_axis_le }}" />
                                            </td>
                                            <td>
                                                <x-text-input type="text" name="near_va_le" value="{{ $prescription->near_va_le }}" />
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
                                <table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
                                    <tr>
                                        <td>IPD</td>
                                        <td colspan="3">
                                            <x-text-input type="text" name="ipd" value="{{ old('ipd') }}" />
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="flex items-center">
                                                <input type="checkbox" name="glasses" id="glasses" class="input-switch-toggle" value="1" {{ $prescription->glasses == 1 ? 'checked' : '' }}>
                                                <label for="glasses" class="ml-2">{{ _lang('Glasses') }}</label>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items-center">
                                                <input type="checkbox" name="photochromatic_lenses" id="photochromatic_lenses" class="input-switch-toggle" value="1" {{ $prescription->photochromatic_lenses == 1 ? 'checked' : '' }}>
                                                <label for="photochromatic_lenses" class="ml-2">{{ _lang('Photochromatic Lenses') }}</label>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items-center">
                                                <input type="checkbox" name="high_index_lenses" id="high_index_lenses" class="input-switch-toggle" value="1" {{ $prescription->high_index_lenses == 1 ? 'checked' : '' }}>
                                                <label for="high_index_lenses" class="ml-2">{{ _lang('High Index Lenses') }}</label>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="flex items-center">
                                                <input type="checkbox" name="polycarbonate" id="polycarbonate" class="input-switch-toggle" value="1" {{ $prescription->polycarbonate == 1 ? 'checked' : '' }}>
                                                <label for="polycarbonate" class="ml-2">{{ _lang('Polycarbonate') }}</label>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items-center">
                                                <input type="checkbox" name="contact_lenses" id="contact_lenses" class="input-switch-toggle" value="1" {{ $prescription->contact_lenses == 1 ? 'checked' : '' }}>
                                                <label for="contact_lenses" class="ml-2">{{ _lang('Contact Lenses') }}</label>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items-center">
                                                <input type="checkbox" name="bi_focal_lenses" id="bi_focal_lenses" class="input-switch-toggle" value="1" {{ $prescription->bi_focal_lenses == 1 ? 'checked' : '' }}>
                                                <label for="bi_focal_lenses" class="ml-2">{{ _lang('Bi Focal Lenses') }}</label>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="flex items-center">
                                                <input type="checkbox" name="progressive_lenses" id="progressive_lenses" class="input-switch-toggle" value="1" {{ $prescription->progressive_lenses == 1 ? 'checked' : '' }}>
                                                <label for="progressive_lenses" class="ml-2">{{ _lang('Progressive Lenses') }}</label>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items-center">
                                                <input type="checkbox" name="anti_reflection_coating" id="anti_reflection_coating" class="input-switch-toggle" value="1" {{ $prescription->anti_reflection_coating == 1 ? 'checked' : '' }}>
                                                <label for="anti_reflection_coating" class="ml-2">{{ _lang('Anti Reflection Coating') }}</label>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items-center">
                                                <input type="checkbox" name="single_vision" id="single_vision" class="input-switch-toggle" value="1" {{ $prescription->single_vision == 1 ? 'checked' : '' }}>
                                                <label for="single_vision" class="ml-2">{{ _lang('Single Vision') }}</label>
                                            </div>
                                        </td>
                                    </tr>
                                    <tr>
                                        <td>
                                            <div class="flex items-center">
                                                <input type="checkbox" name="plastic" id="plastic" class="input-switch-toggle" value="1" {{ $prescription->plastic == 1 ? 'checked' : '' }}>
                                                <label for="plastic" class="ml-2">{{ _lang('Plastic') }}</label>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items-center">
                                                <input type="checkbox" name="white_lenses" id="white_lenses" class="input-switch-toggle" value="1" {{ $prescription->white_lenses == 1 ? 'checked' : '' }}>
                                                <label for="white_lenses" class="ml-2">{{ _lang('White Lenses') }}</label>
                                            </div>
                                        </td>
                                        <td>
                                            <div class="flex items-center">
                                                <input type="checkbox" name="blue_cut" id="blue_cut" class="input-switch-toggle" value="1" {{ $prescription->blue_cut == 1 ? 'checked' : '' }}>
                                                <label for="blue_cut" class="ml-2">{{ _lang('Blue Cut') }}</label>
                                            </div>
                                        </td>
                                    </tr>
                                </table>
                            </div>
                        </div>

                        <div class="grid grid-cols-12 mt-5 col-span-12">
                            <div class="col-span-12">
                                <x-input-label value="Description" />
                                <textarea name="prescription_description" class="w-full" cols="10" rows="5">{{ $prescription->description }}</textarea>
                            </div>
                        </div>

                        <div class="grid grid-cols-12 mt-5 col-span-12">
                            <div class="md:col-span-4 col-span-12 mt-5">
                                <x-input-label value="Select Product" />
                                <select id="products" data-type="sell" class="w-full selectize">
                                    <option value="">Select Item</option>
                                    @foreach(\App\Models\Product::where('business_id', request()->activeBusiness->id)->get() as $product)
                                    <option value="{{ $product->id }}">
                                        {{ $product->name }}
                                    </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-span-12 mt-4">
                                <div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
                                    <table id="invoice-table" class="ti-custom-table ti-custom-table-head whitespace-nowrap bg-gray-100">
                                        <thead>
                                            <tr>
                                                <th class="input-lg">{{ _lang('Name') }}</th>
                                                <th class="input-xs text-center">{{ _lang('Quantity') }}</th>
                                                <th class="input-sm text-right">{{ _lang('Price') }}</th>
                                                <th class="input-sm text-right">{{ _lang('Amount') }}</th>
                                                <th class="text-center"><i class="fas fa-minus-circle"></i></th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($prescription->items as $item)
                                            <tr class="line-item">
                                                <td class="input-lg align-top">
                                                    <input type="hidden" class="product_id" name="product_id[]" value="{{ $item->product_id }}">
                                                    <input type="hidden" class="product_type" name="product_type[]" value="{{ $item->product->type }}">
                                                    <input type="text" class="w-full product_name" name="product_name[]" value="{{ $item->product_name }}"><br>
                                                    <textarea class="w-full mt-2 description" name="description[]" placeholder="{{ _lang('Descriptions') }}">{{ $item->description }}</textarea>
                                                </td>
                                                <td class="input-xs text-center align-top"><input type="number" class="w-full quantity" name="quantity[]" value="{{ $item->quantity }}" min="1" {{ $item->product->type == 'product' && $item->product->stock_management == 1 ? 'max=' . $item->product->stock + $item->quantity : '' }}></td>
                                                <td class="input-sm align-top"><input type="text" class="w-full text-right unit_cost" name="unit_cost[]" value="{{ $item->unit_cost }}"></td>
                                                <td class="input-sm align-top"><input type="text" class="w-full text-right sub_total" name="sub_total[]" value="{{ $item->sub_total }}" readonly></td>
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
                    </div>

                    <div class="col-span-12 mt-4">
                        <x-primary-button type="submit" class="submit-btn">{{ _lang('Save Changes') }}</x-primary-button>
                    </div>
            </div>
            </form>
        </div>
    </div>

    <table class="ti-custom-table ti-custom-table-head whitespace-nowrap hidden">
        <tr class="line-item" id="copy-line">
            <td class="align-top">
                <input type="hidden" class="product_id" name="product_id[]">
                <input type="hidden" class="product_type" name="product_type[]">
                <input type="text" class="w-full product_name" name="product_name[]"><br>
                <textarea class="w-full mt-2 description" name="description[]" placeholder="{{ _lang('Descriptions') }}"></textarea>
            </td>
            <td class="input-xs text-center align-top"><input type="number" class="w-full quantity" name="quantity[]" min="1"></td>
            <td class="input-sm align-top"><input type="text" class="w-full text-right unit_cost" name="unit_cost[]"></td>
            <td class="input-sm align-top"><input type="text" class="w-full text-right sub_total" name="sub_total[]" readonly></td>
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
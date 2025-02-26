<form method="post" class="ajax-screen-submit" autocomplete="off" action="{{ route('inventory_adjustments.update', $id) }}" enctype="multipart/form-data">
    {{ csrf_field() }}
    <input name="_method" type="hidden" value="PATCH">
    <div class="grid grid-cols-12 gap-2 mt-5">
        <div class="md:col-span-6 col-span-12">
            <x-input-label value="{{ _lang('Select Account') }}" />
            <select class="w-full select2 auto-select" data-selected="{{ $adjustment->account_id }}" name="account_id" data-placeholder="Select Account" required>
                <option value="">Select Account</option>
                @foreach(\App\Models\Account::where('dr_cr', 'dr')->get() as $account)
                <option value="{{ $account->id }}">{{ $account->account_name }}</option>
                @endforeach
            </select>
        </div>

        <div class="md:col-span-6 col-span-12">
            <x-input-label value="{{ _lang('Adjustment Date') }}" />
            <x-text-input type="text" name="adjustment_date" class="flatpickr" id="date" value="{{ \Carbon\Carbon::createFromFormat(get_date_format(), $adjustment->adjustment_date)->format('d-m-Y') }}" />
        </div>

        <div class="md:col-span-6 col-span-12">
            <x-input-label value="{{ _lang('Select Product') }}" />
            <select class="w-full select2 auto-select" data-selected="{{ $adjustment->product_id }}" name="product_id" data-placeholder="Select Product" id="products" required>
                <option value="">Select Product</option>
                @foreach(\App\Models\Product::all() as $product)
                <option value="{{ $product->id }}">{{ $product->name }}</option>
                @endforeach
            </select>
        </div>

        <div class="md:col-span-6 col-span-12">
            <x-input-label value="{{ _lang('Quantity On Hand') }}" />
            <x-text-input type="text" name="quantity_on_hand" value="{{ $adjustment->quantity_on_hand }}" id="qty_on_hand" readonly/>
        </div>

        <div class="md:col-span-6 col-span-12">
            <x-input-label value="{{ _lang('New Quantity') }}" />
            <x-text-input type="text" name="new_quantity" id="new_qty" value="{{ $adjustment->new_quantity_on_hand }}" />
        </div>

        <div class="md:col-span-6 col-span-12">
            <x-input-label value="{{ _lang('Adjusted Quantity') }}" />
            <x-text-input type="text" name="adjusted_quantity" id="adjusted_qty" value="{{ $adjustment->adjusted_quantity }}" readonly />
        </div>

        <div class="md:col-span-6 col-span-12 mt-3">
            <x-input-label value="{{ _lang('Description') }}" />
            <textarea class="w-full" name="description">{{ $adjustment->description }}</textarea>
        </div>

        <div class="col-span-12">
            <x-primary-button type="submit" class="submit-btn">
                Update Adjustment
            </x-primary-button>
        </div>
    </div>
</form>

<!-- Flatpickr JS -->
<script src="/assets/libs/flatpickr/flatpickr.min.js"></script>
<script src="/assets/js/flatpickr.js"></script>

<script>
	$('.select2').select2({
		placeholder: "Select an option",
		allowClear: true,
		width: '100%'
	});
</script>

<script>
    // $(document).on("change", "#products", function (e) {
    //     var productId = $(this).val();

    //     if (productId == null) {
    //         return;
    //     }

    //     $.ajax({
    //         url: "/user/products/findProduct/" + productId,
    //         success: function (data) {
    //             $("#qty_on_hand").val(data.product.stock);
    //         },
    //     });
    // });

    $(document).on("change", "#new_qty", function (e) {
        var newQty = $(this).val();
        var qtyOnHand = $("#qty_on_hand").val();

        if (newQty == null || newQty == "") {
            $("#adjusted_qty").val("");
            return;
        }

        var adjustedQty = newQty - qtyOnHand;
        $("#adjusted_qty").val(adjustedQty);
    });
</script>
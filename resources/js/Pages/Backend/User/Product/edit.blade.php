<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Products" page="user" subpage="edit" />

		<div class="box">
			<div class="box-header">
				<h5>{{ _lang('Update Product') }}</h5>
			</div>
			<div class="box-body">
				<form method="post" class="validate" autocomplete="off" action="{{ route('products.update', $id) }}" enctype="multipart/form-data">
					{{ csrf_field() }}
					<input name="_method" type="hidden" value="PATCH">
					<div class="grid grid-cols-12 gap-x-2">
						<div class="col-span-12">
							<x-input-label>
								{{ _lang('Name') }}
								<span class='text-red-600'>*</span>
							</x-input-label>
							<x-text-input type="text" name="name" value="{{ $product->name }}" required />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label>
								{{ _lang('Type') }}
								<span class='text-red-600'>*</span>
							</x-input-label>
							<select class="w-full auto-select" data-selected="{{ $product->type }}" name="type" required>
								<option value="product">{{ _lang('Product') }}</option>
								<option value="service">{{ _lang('Service') }}</option>
							</select>
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Product Unit') }}" />
							<select class="w-full selectize auto-select" data-selected="{{ $product->product_unit_id }}" name="product_unit_id">
								<option value="">{{ _lang('Select One') }}</option>
								@foreach(\App\Models\ProductUnit::all() as $productUnit)
								<option value="{{ $productUnit->id }}">{{ $productUnit->unit }}</option>
								@endforeach
							</select>
						</div>

						<hr class="mt-8 mb-3 border border-gray-300 col-span-12">

						<div class="col-span-12 grid grid-cols-12 gap-x-2">
							<div class="md:col-span-6 col-span-12 mt-3">
								<x-input-label value="{{ _lang('Allow For Selling') }} ?" />
								<select class="w-full c-select auto-select" data-selected="{{ $product->allow_for_selling }}" id="allow_for_selling" data-show="income-account" data-condition="1" name="allow_for_selling">
									<option value="1">{{ _lang('Yes') }}</option>
									<option value="0">{{ _lang('No') }}</option>
								</select>
							</div>

							<div class="md:col-span-6 col-span-12"></div>

							<div class="md:col-span-6 col-span-12 mt-3 {{ $product->allow_for_selling != 1 ? 'hidden' : '' }} income-account">
								<x-input-label value="{{ _lang('Selling Price').' ('.currency_symbol(request()->activeBusiness->currency).')' }}" />
								<x-text-input type="text" name="selling_price" value="{{ $product->selling_price }}" required />
							</div>

							<div class="md:col-span-6 col-span-12 mt-3 {{ $product->allow_for_selling != 1 ? 'hidden' : '' }} income-account">
								<x-input-label value="{{ _lang('Income Account') }}" />
								<select class="w-full auto-select selectize" data-selected="{{ $product->income_account_id }}" name="income_account_id">
									<option value="">{{ _lang('Select Account') }}</option>
									@foreach(\App\Models\Account::where(function ($query) {
									$query->where('account_type', '=', 'Sales')
									->orWhere('account_type', '=', 'Other Income');
									})->where(function ($query) {
									$query->where('business_id', '=', request()->activeBusiness->id)
									->orWhere('business_id', '=', null);
									})
									->get() as $account)
									<option value="{{ $account->id }}">{{ $account->account_name }}</option>
									@endforeach
								</select>
							</div>
						</div>

						<hr class="mt-8 mb-3 border border-gray-300 col-span-12">

						<div class="col-span-12 grid grid-cols-12 gap-x-2">
							<div class="md:col-span-6 col-span-12 mt-3">
								<x-input-label value="{{ _lang('Allow For Purchasing') }} ?" />
								<select class="w-full c-select auto-select" data-selected="{{ $product->allow_for_purchasing }}" id="allow_for_purchasing" data-show="expense-account" data-condition="1" name="allow_for_purchasing">
									<option value="1">{{ _lang('Yes') }}</option>
									<option value="0">{{ _lang('No') }}</option>
								</select>
							</div>

							<div class="md:col-span-6 col-span-12"></div>

							<div class="md:col-span-6 col-span-12 mt-3 {{ $product->allow_for_purchasing != 1 ? 'hidden' : '' }} expense-account">
								<x-input-label value="{{ _lang('Purchase Cost').' ('.currency_symbol(request()->activeBusiness->currency).')' }}" />
								<x-text-input type="text" class="form-control float-field no-msg" name="purchase_cost" value="{{ $product->purchase_cost }}" />
							</div>

							<div class="md:col-span-6 col-span-12 mt-3 {{ $product->allow_for_purchasing != 1 ? 'hidden' : '' }} expense-account">
								<x-input-label value="{{ _lang('Expense Account') }}" />
								<select class="w-full auto-select selectize" data-selected="{{ $product->expense_account_id }}" name="expense_account_id">
									<option value="">{{ _lang('Select Account') }}</option>
									@foreach(\App\Models\Account::where(function ($query) {
									$query->where('account_type', '=', 'Cost Of Sale');
									})->where(function ($query) {
									$query->where('business_id', '=', request()->activeBusiness->id)
									->orWhere('business_id', '=', null);
									})
									->get() as $account)
									<option value="{{ $account->id }}">{{ $account->account_name }}</option>
									@endforeach
								</select>
							</div>
						</div>

						<div class="col-span-12 grid grid-cols-12 gap-x-2">
							<div class="md:col-span-6 col-span-12 mt-3">
								<x-input-label value="{{ _lang('Category') }}" />
								<select class="w-full c-select auto-select selectize" data-selected="{{ $product->category_id }}" name="category_id">
									@foreach(\App\Models\Category::all() as $category)
									<option value="{{ $category->id }}">{{ $category->name }}</option>
									@endforeach
								</select>
							</div>

							<div class="md:col-span-6 col-span-12 mt-3">
								<x-input-label value="{{ _lang('Brand') }}" />
								<select class="w-full auto-select selectize" data-selected="{{ $product->brand_id }}" name="brand_id">
									@foreach(\App\Models\Brands::all() as $brand)
									<option value="{{ $brand->id }}">{{ $brand->name }}</option>
									@endforeach
								</select>
							</div>
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Image') }}" />
							<input type="file" name="image" class="dropify" data-allowed-file-extensions="png jpg jpeg" data-default-file="{{ asset('/uploads/media/'.$product->image) }}" />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Descriptions') }}" />
							<textarea class="w-full" name="descriptions">{{ $product->descriptions }}</textarea>
						</div>

						<!-- expiry date -->
						<div class="md:col-span-4 col-span-12 mt-3">
							<x-input-label>
								{{ _lang('Expiry Date') }}
							</x-input-label>
							<x-text-input type="text" class="flatpickr" id="date" name="expiry_date" value="{{ $product->expiry_date ? \Carbon\Carbon::parse($product->expiry_date)->format('d-m-Y') : '' }}" />
						</div>

						<!-- code -->
						<div class="md:col-span-4 col-span-12 mt-3">
							<x-input-label>
								{{ _lang('Code') }}
							</x-input-label>
							<x-text-input type="text" name="code" value="{{ $product->code }}" />
						</div>

						<!-- reorder point -->
						<div class="md:col-span-4 col-span-12 mt-3">
							<x-input-label>
								{{ _lang('Reorder Point') }}
							</x-input-label>
							<x-text-input type="number" name="reorder_point" value="{{ $product->reorder_point }}" />
						</div>

						<div class="md:col-span-4 col-span-12 mt-3">
							<x-input-label>
								{{ _lang('Status') }}
								<span class='text-red-600'>*</span>
							</x-input-label>
							<select class="w-full" data-selected="{{ $product->status }}" name="status" required>
								<option value="1">{{ _lang('Active') }}</option>
								<option value="0">{{ _lang('Disabled') }}</option>
							</select>
						</div>

						<div class="md:col-span-4 col-span-12 mt-3">
							<x-input-label>
								{{ _lang('Stock Management') }}
								<span class='text-red-600'>*</span>
							</x-input-label>
							<select class="w-full auto-select" data-selected="{{ $product->stock_management }}" name="stock_management" required id="stock_management">
								<option value="1">{{ _lang('Yes') }}</option>
								<option value="0">{{ _lang('No') }}</option>
							</select>
							<small class="text-danger"><i class="fas fa-exclamation-circle mr-1"></i>{{ _lang('Works for product only!') }}</small>
						</div>

						@if($product_sales == 0)
						<div class="md:col-span-4 col-span-12 mt-3">
							<x-input-label>
								{{ _lang('Initial Stock') }}
							</x-input-label>
							<x-text-input type="text" name="initial_stock" value="{{ $product->initial_stock }}" />
						</div>
						@else
						<div class="md:col-span-4 col-span-12 mt-3">
							<x-input-label>
								{{ _lang('Initial Stock') }}
							</x-input-label>
							<x-text-input type="text" name="initial_stock" value="{{ $product->initial_stock }}" readonly />
						</div>
						@endif
					</div>
					<div class="col-span-12 mt-4">
						<x-primary-button type="submit" class="submit-btn">{{ _lang('Save Changes') }}</x-primary-button>
					</div>
				</form>
			</div>
		</div>
	</div>
</x-app-layout>

<script>
	$(document).on('change', '#stock_management', function() {
		if ($(this).val() == 1) {
			$('#allow_for_selling').val(1).trigger('change');
			$('#allow_for_purchasing').val(1).trigger('change');
			$('#allow_for_selling').attr('readonly', true);
			$('#allow_for_purchasing').attr('readonly', true);
		} else {
			$('#allow_for_selling').attr('readonly', false);
			$('#allow_for_purchasing').attr('readonly', false);
		}
	});
</script>
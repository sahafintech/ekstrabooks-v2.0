<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Bill Invoice" page="user" subpage="update" />

		<form method="post" class="validate" autocomplete="off" action="{{ route('cash_purchases.update', $id) }}" enctype="multipart/form-data">
			@csrf
			<input name="_method" type="hidden" value="PATCH">
			<div class="grid grid-cols-12 gap-x-2">
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
											<x-text-input class="text-xl font-bold" type="text" name="title" value="{{ $bill->title }}" placeholder="{{ _lang('Bill Title') }}" required />
										</div>

										<div class="col-span-12">
											<x-text-input type="text" name="po_so_number" value="{{ $bill->po_so_number }}" placeholder="{{ _lang('PO / SO Number') }}" />
										</div>
									</div>
								</div>
							</div>

							<div class="col-span-12 my-8">
								<hr>
							</div>

							<div class="grid grid-cols-12">
								<div class="md:col-span-5 col-span-12 md:mr-2">
									<x-input-label class="xl:col-span-2 col-span-12" value="{{ _lang('Bill Date') }}" />
									<x-text-input type="text" class="flatpickr-input" id="date" name="purchase_date" value="{{ \Carbon\Carbon::createFromFormat(get_business_option('date_format', request()->activeBusiness->id),$bill->purchase_date)->format('d-m-Y') }}" required placeholder="Select Date" />
								</div>

								<div class="md:col-span-7 col-span-12">
									<x-input-label class="xl:col-span-2 col-span-12" value="{{ _lang('Select Supplier') }}" />
									<div class="select-vendor">
										<select class="w-full selectize auto-select" data-selected="{{ $bill->vendor_id }}" name="vendor_id">
											<option value="">{{ _lang('Select Supplier') }}</option>
											@foreach(\App\Models\Vendor::where('business_id', request()->activeBusiness->id)->get() as $vendor)
											<option value="{{ $vendor->id }}">{{ $vendor->name }}</option>
											@endforeach
										</select>
									</div>
								</div>

								<div class="col-span-12 mt-5">
									<x-input-label value="{{ _lang('Benificiary') }}" />
									<textarea class="w-full" name="benificiary">{{ $bill->benificiary }}</textarea>
								</div>
							</div>

							<div class="grid grid-cols-12 my-10 gap-x-2">
								<div class="md:col-span-4 col-span-12">
									<x-input-label value="{{ _lang('Select Credit Account') }}" />
									<select class="w-full selectize auto-select" id="credit_account_id" data-selected="{{ $credit_account?->account_id }}" name="credit_account_id">
										<option value="">{{ _lang('Select Account') }}</option>
										@foreach(\App\Models\Account::all() as $account)
										<option value="{{ $account->id }}">{{ $account->account_name }}</option>
										@endforeach
									</select>
								</div>

								<div class="md:col-span-4 col-span-12">
									<x-input-label value="Select Product" />
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
									<x-input-label value="{{ _lang('Transaction Currency') }}" />
									<select class="w-full auto-select selectize" id="currency" data-selected="{{ $bill->currency }}" name="currency" required>
										<option value="">{{ _lang('Select One') }}</option>
										{{ get_currency_list() }}
									</select>
								</div>
								<div class="md:col-span-4 col-span-12 {{ $bill->currency == request()->activeBusiness->currency ? 'hidden' : '' }}">
									<x-input-label value="{{ _lang('Exchange Rate') }}" />
									<x-text-input id="exchange_rate" step="any" type="number" name="exchange_rate" value="{{ $bill->exchange_rate }}" readonly />
								</div>
							</div>

							<div class="grid grid-cols-12 mt-3">
								<div class="col-span-12">
									<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
										<table id="invoice-table" class="ti-custom-table ti-custom-table-head whitespace-nowrap bg-gray-100">
											<thead>
												<tr>
													<th class="input-lg">{{ _lang('Select Account') }}</th>
													<th class="input-lg">{{ _lang('Product/Service') }}</th>
													<th class="input-md">{{ _lang('Item Taxes') }}</th>
													<th class="input-xs text-center">{{ _lang('Quantity') }}</th>
													<th class="input-sm text-right">{{ _lang('Price') }}</th>
													<th class="input-sm text-right">{{ _lang('Amount') }}</th>
													<th class="text-center"><i class="fas fa-minus-circle"></i></th>
												</tr>
											</thead>
											<tbody>
												@foreach($bill->items as $item)
												<tr class="line-item">
													<!-- select account -->
													<td class="align-top input-lg">
														@if($item->account_id != get_account('Inventory')->id)
														<div class="w-72">
															<select name="account_id[]" class="w-full selectize account_id auto-select" data-selected="{{ $item->account_id }}" data-placeholder="{{ _lang('Select Account') }}">
																<option value="">{{ _lang('Select Account') }}</option>
																@foreach(\App\Models\Account::all() as $account)
																<option value="{{ $account->id }}">{{ $account->account_name }}</option>
																@endforeach
															</select>
														</div>
														@else
														<div class="w-72">
															<select class="w-full selectize auto-select" data-selected="{{ $item->account_id }}" data-placeholder="{{ _lang('Select Account') }}" readonly data-selected="{{ $item->account_id }}">
																<option value="">{{ _lang('Select Account') }}</option>
																@foreach(\App\Models\Account::all() as $account)
																<option value="{{ $account->id }}">{{ $account->account_name }}</option>
																@endforeach
															</select>
														</div>
														<input type="hidden" class="account_id" name="account_id[]" value="{{ $item->account_id }}">
														@endif
													</td>
													<td class="align-top input-lg">
														<input type="hidden" class="product_id" name="product_id[]" value="{{ $item->product_id }}">
														<input type="hidden" class="product_type" name="product_type[]" value="{{ $item->product->type }}">
														<textarea name="product_name[]" class="w-72 product_name" name="product_name[]">{{ $item->product_name }}</textarea>
													</td>
													<td class="input-md align-top">
														<div class="w-52">
															<select name="taxes[{{ $item->product_name }}][]" class="selectize auto-select product_taxes" multiple data-selected="{{ $item->taxes->pluck('tax_id') }}" data-placeholder="{{ _lang('Select Taxes') }}" multiple>
																@foreach(\App\Models\Tax::all() as $tax)
																<option value="{{ $tax->id }}" data-tax-rate="{{ $tax->rate }}" data-tax-name="{{ $tax->name }} {{ $tax->rate }} %">{{ $tax->name }} {{ $tax->rate }} %</option>
																@endforeach
															</select>
														</div>
													</td>
													<td class="align-top input-xs text-center">
														<input type="number" class="w-36 quantity" name="quantity[]" value="{{ $item->quantity }}" min="1">
													</td>
													<td class="align-top input-sm">
														<input type="text" class="w-36 text-right unit_cost" name="unit_cost[]" value="{{ $item->unit_cost }}">
													</td>
													<td class="align-top input-sm">
														<input type="text" class="w-36 text-right sub_total" name="sub_total[]" value="{{ $item->sub_total }}" readonly>
													</td>
													<td class="align-top input-xxs text-center">
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
											<x-text-input type="hidden" class="md:text-right border-none" name="sub_total" id="sub_total" value="{{ $bill->sub_total }}" readonly />
											<span class="md:text-right sub_total_span">
												{{ $bill->sub_total }}
											</span>
										</div>
									</div>

									<div class="grid grid-cols-12 gap-x-2">
										<div class="md:col-span-6 col-span-12">
											<div class="custom-control custom-checkbox">
												<input type="checkbox" class="custom-control-input" id="withholding_tax" name="withholding_tax" value="1" {{ $bill->withholding_tax == 1 ? 'checked' : '' }}>
												<label class="custom-control-label" for="withholding_tax"></label>
											</div>
										</div>
										<x-input-label class="md:col-span-3 col-span-12" value="{{ _lang('Withholding Tax') }}" />
									</div>

									@foreach($bill->taxes as $tax)
									<div class="grid grid-cols-12 gap-x-2 old-tax {{ count($bill->taxes) > 0 ? ''  : 'hidden'}}" id="after-tax">
										<x-input-label class="md:col-span-6 col-span-12" value="{{ $tax->name }}" />
										<div class="md:col-span-6 col-span-12">
											<x-text-input type="hidden" class="md:text-right border-none tax-input-field" name="tax_amount[{{ $tax->tax_id }}]" id="tax-{{ $tax->tax_id }}" value="0" readonly />
											<span class="md:text-right tax_span-{{ $tax->tax_id }}">
												{{ formatAmount($tax->amount) }}
											</span>
										</div>
									</div>
									@endforeach

									<div class="grid grid-cols-12 gap-x-2" id="after-tax">
										<x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Discount Amount') }}" />
										<div class="md:col-span-6 col-span-12">
											<x-text-input type="hidden" class="md:text-right border-none" name="discount_amount" id="discount_amount" value="{{ $bill->discount }}" readonly />
											<span class="md:text-right discount_span">
												{{ $bill->discount }}
											</span>
										</div>
									</div>

									<div class="grid grid-cols-12 gap-x-2">
										<x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Grand Total') }}" />
										<div class="md:col-span-6 col-span-12">
											<x-text-input type="hidden" class="md:text-right border-none" name="grand_total" id="grand_total" value="{{ $bill->grand_total }}" readonly />
											<span class="md:text-right grand_total_span">
												{{ $bill->grand_total }}
											</span>
										</div>
									</div>

									<div class="{{ $bill->currency == request()->activeBusiness->currency ? 'hidden' : 'grid' }} grid-cols-12 gap-x-2">
										<x-input-label class="md:col-span-6 col-span-12" value="{{ _lang('Converted Total') }}" />
										<div class="md:col-span-6 col-span-12">
											<x-text-input type="hidden" class="md:text-right border-none" name="converted_total" id="converted_total" value="{{ $bill->converted_total }}" readonly />
											<span class="md:text-right converted_total_span">
												{{ $bill->converted_total }}
											</span>
										</div>
									</div>
								</div>
							</div>

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
												@if($attachments->count() > 0)
												@foreach($attachments as $attachment)
												<td class="border border-gray-300 px-4 py-2">1</td>
													<td class="border border-gray-300 px-4 py-2">
														<input type="text" name="attachments[file_name][]" placeholder="Enter file name" class="w-full px-2 py-1 border rounded" value="{{ $attachment->file_name }}">
													</td>
													<td class="border border-gray-300 px-4 py-2">
														<input type="file" name="attachments[file][]" class="dropify" data-allowed-file-extensions="pdf jpg jpeg png doc docx xls xlsx" data-max-file-size="2M" data-default-file="{{ $attachment->path }}" data-height="50" />
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
									<textarea class="w-full" name="note">{{ $bill->note }}</textarea>
								</div>

								<div class="col-span-12">
									<x-input-label value="{{ _lang('Footer') }}" />
									<textarea class="w-full" name="footer">{!! xss_clean(get_business_option('invoice_footer', $bill->footer)) !!}</textarea>
								</div>

								<div class="col-span-12 mt-4">
									<x-primary-button type="submit" class="submit-btn">{{ _lang('Update Bill') }}</x-primary-button>
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
											<select class="w-full discount_type auto-select" id="discount_type" name="discount_type" data-selected="{{ $bill->discount_type }}">
												<option value="0">%</option>
												<option value="1">{{ currency_symbol(request()->activeBusiness->currency) }}</option>
											</select>
										</div>
										<input type="number" step="any" class="col-span-9" name="discount_value" id="discount_value" value="{{ $bill->discount_value }}">
									</div>
								</div>

								<div class="col-span-12 mt-4">
									<x-primary-button type="submit" class="submit-btn">{{ _lang('Update Bill') }}</x-primary-button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</form>

		<table class="hidden">
			<tr class="line-item" id="copy-line">
				<td class="align-top input-lg">
					<div class="w-52">
						<select class="selectize w-full auto-select" data-placeholder="{{ _lang('Select Account') }}" data-selected="{{ get_account('Inventory')->id }}">
							<option value="">{{ _lang('Select Account') }}</option>
							@foreach(\App\Models\Account::all() as $account)
							<option value="{{ $account->id }}">{{ $account->account_name }}</option>
							@endforeach
						</select>
					</div>
					<input type="hidden" class="account_id" name="account_id[]" value="{{ get_account('Inventory')->id }}">
				</td>
				<td class="align-top">
					<input type="hidden" class="product_id" name="product_id[]">
					<input type="hidden" class="product_type" name="product_type[]">
					<textarea type="text" class="w-72 product_name" name="product_name[]" readonly></textarea>
				</td>
				<td class="align-top input-md">
					<div class="w-52">
						<select name="taxes[][]" class="multi-selector product_taxes w-full" data-placeholder="{{ _lang('Select Taxes') }}" multiple>

						</select>
					</div>
				</td>
				<td class="align-top input-xs text-center"><input type="number" class="w-36 quantity" name="quantity[]" min="1"></td>
				<td class="align-top input-sm"><input type="text" class="w-36 text-right unit_cost" name="unit_cost[]"></td>
				<td class="align-top input-sm"><input type="text" class="w-36 text-right sub_total" name="sub_total[]" readonly></td>
				<td class="align-top input-xxs text-center">
					<button type="button" class="bg-red-600 btn-remove-row px-1 rounded-md">
						<i class="ri-close-circle-line text-xl text-white"></i>
					</button>
				</td>
			</tr>
		</table>
		
		<table class="hidden">
			<tbody id="invoice-table-template">
				<tr class="line-item" id="copy-template">
					<!-- select account -->
					<td class="align-top input-lg">
						<div class="w-72">
							<select name="account_id[]" class="selectize account_id">
								<option value="">{{ _lang('Select Account') }}</option>
								@foreach(\App\Models\Account::all() as $account)
								<option value="{{ $account->id }}">{{ $account->account_name }}</option>
								@endforeach
							</select>
						</div>
					</td>
					<td class="align-top input-lg">
						<textarea name="product_name[]" id="" class="w-72 product_name"></textarea>
					</td>
					<td class="input-md align-top">
						<div class="w-52">
							<select name="taxes[][]" class="multi-selector product_taxes selectize w-full" data-placeholder="{{ _lang('Select Taxes') }}" multiple>

							</select>
						</div>
					</td>
					<td class="align-top input-xs text-center"><input type="number" class="w-36 quantity" name="quantity[]" value="1" min="1"></td>
					<td class="align-top input-sm"><input type="text" class="w-36 text-right unit_cost" name="unit_cost[]"></td>
					<td class="align-top input-sm"><input type="text" class="w-36 text-right sub_total" name="sub_total[]" readonly></td>
					<td class="align-top input-xxs text-center">
						<button type="button" class="bg-red-600 btn-remove-row px-1 rounded-md">
							<i class="ri-close-circle-line text-xl text-white"></i>
						</button>
					</td>
				</tr>
			</tbody>
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

<script>
	$(document).on('click', '#add_new_row', function() {

		// destroy selectize
		$('.selectize').select2('destroy');

		var theRow = $("#copy-template").clone().removeAttr("id");

		$("#invoice-table tbody").append(theRow);


		$.ajax({
			url: "/user/find_taxes/",
			success: function(data) {
				var taxes = JSON.parse(JSON.stringify(data));
				$.each(taxes, function(index, value) {
					$(theRow)
						.find(".product_taxes")
						.append(
							`<option value="${value.id}" data-tax-rate="${value.rate}" data-tax-name="${value.name}">${value.name} (${value.rate}%)</option>`
						);
				});
			},
		});

		// initialize selectize
		$('.selectize').select2({
			placeholder: "Select an option",
			allowClear: true,
			width: '100%'
		});
	});

	$(document).on('keyup', '.product_name', function() {
		var product_name = $(this).val();
		$(this).closest('tr').find('.product_taxes').attr('name', 'taxes[' + product_name + '][]');
	});

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

<script src="{{ asset('/backend/assets/js/invoice.js?v=1.2') }}"></script>
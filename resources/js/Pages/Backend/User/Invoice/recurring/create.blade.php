@extends('layouts.app')

@section('content')
<form method="post" class="validate" autocomplete="off" action="{{ route('recurring_invoices.store') }}" enctype="multipart/form-data">
	@csrf
	<div class="row">
		<div class="col-xl-9 col-lg-8">
			<div class="card invoice-form">
				<div class="card-header d-flex align-items-center">
					<span class="panel-title">{{ _lang('New Recurring Invoice') }}</span>
				</div>
				<div class="card-body">
					<div class="row">
						<div class="col-lg-4">
							<div class="invoice-logo">
								<img src="{{ asset('public/uploads/media/' . request()->activeBusiness->logo) }}" alt="logo">
							</div>
						</div>

						<div class="col-lg-4 offset-lg-4">
							<div class="row">
								<div class="col-md-12">
									<div class="form-group">
										<input type="text" class="form-control form-control-lg" name="title" value="{{ get_business_option('invoice_title', 'Invoice') }}" placeholder="{{ _lang('Invoice Title') }}" required>
									</div>
								</div>

								<div class="col-md-12">
									<div class="form-group">
										<input type="text" class="form-control" name="order_number" value="{{ old('order_number') }}" placeholder="{{ _lang('Sales Order No') }}">
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="row my-4">
						<div class="col-12">
							<div class="divider"></div>
						</div>
					</div>

					<div class="row">
						<div class="col-lg-4">
							<div class="form-group select-customer">
								<select class="form-control" data-selected="{{ old('customer_id') }}" name="customer_id" data-value="id" data-display="name"
										data-href="{{ route('customers.create') }}" data-title="{{ _lang('Create New Customer') }}" data-table="customers"
										data-where="3" data-placeholder="{{ _lang('Choose Customer') }}" required>
									<option value="">{{ _lang('Select Customer') }}</option>
								</select>
							</div>
						</div>

						<div class="col-lg-6 offset-lg-2">
							<div class="form-group row">
								<label class="col-xl-4 col-form-label">{{ _lang('Recurring Start') }}</label>
								<div class="col-xl-8">
									<input type="text" class="form-control datepicker no-msg" name="recurring_start" value="{{ old('recurring_start') }}" required>
								</div>
							</div>

							<div class="form-group row">
								<label class="col-xl-4 col-form-label">{{ _lang('Recurring End') }}</label>
								<div class="col-xl-8">
									<input type="text" class="form-control datepicker no-msg" name="recurring_end" value="{{ old('recurring_end') }}" required>
								</div>
							</div>

							<div class="form-group row">
								<label class="col-xl-4 col-form-label">{{ _lang('Recurring Every') }}</label>
								<div class="col-xl-8">
									<div class="input-group">
										<input type="number" class="form-control no-msg" name="recurring_value" min="1" value="{{ old('recurring_value', 1) }}" required>
										<div class="input-group-append">
											<select class="form-control recurring_type" name="recurring_type">
												<option value="days">{{ _lang('Days') }}</option>
												<option value="weeks">{{ _lang('Weeks') }}</option>
												<option value="month">{{ _lang('Month') }}</option>
												<option value="year">{{ _lang('Year') }}</option>
											</select>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>

					<div class="row mt-2">
						<div class="col-12">
							<div class="form-group">
								<select class="form-control" id="products" data-type="sell" data-value="id" data-display="name" data-placeholder="{{ _lang('Select an Item') }}" data-modal="ajax-modal"
									data-href="{{ route('products.create') }}?type=sell" data-title="{{ _lang('Add New Item') }}"
									data-table="products" data-where="6">
								</select>
							</div>
						</div>
					</div>

					<div class="row mt-2">
						<div class="col-12">
							<div class="table-responsive">
								<table class="table" id="invoice-table">
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
											<td class="input-lg">
												<input type="hidden" class="product_id" name="product_id[]" value="{{ $product_id }}">
												<input type="hidden" class="product_type" name="product_type[]" value="{{ old('product_type')[$index] }}">
												<input type="text" class="form-control product_name" name="product_name[]" value="{{ old('product_name')[$index] }}"><br>
												<textarea class="form-control description" name="description[]" placeholder="{{ _lang('Descriptions') }}">{{ old('description')[0] }}</textarea>
											</td>
											<td class="input-md">
												<select name="taxes[{{ $product_id }}][]" class="multi-selector product_taxes auto-multiple-select" data-selected="[{{ isset(old('taxes')[$product_id]) != null ? implode(',', old('taxes')[$product_id]) : '' }}]" data-placeholder="{{ _lang('Select Taxes') }}" multiple>
													@foreach(\App\Models\Tax::all() as $tax)
													<option value="{{ $tax->id }}" data-tax-rate="{{ $tax->rate }}" data-tax-name="{{ $tax->name }} {{ $tax->rate }} %">{{ $tax->name }} {{ $tax->rate }} %</option>
													@endforeach
												</select>
											</td>
											<td class="input-xs text-center"><input type="number" class="form-control quantity" name="quantity[]" value="{{ old('quantity')[0] }}" min="1"></td>
											<td class="input-sm"><input type="text" class="form-control text-right unit_cost" name="unit_cost[]" value="{{ old('unit_cost')[0] }}"></td>
											<td class="input-sm"><input type="text" class="form-control text-right sub_total" name="sub_total[]" value="{{ old('sub_total')[0] }}" readonly></td>
											<td class="input-xxs text-center"><button type="button" class="btn btn-outline-danger btn-xs mt-1 btn-remove-row"><i class="fas fa-minus-circle"></i></button></td>
										</tr>
										@endforeach
										@endif
									</tbody>
								</table>
							</div>
						</div>
					</div>

					<div class="row my-4">
						<div class="col-12">
							<div class="divider"></div>
						</div>
					</div>

					<div class="row text-md-right">
						<div class="col-xl-6 offset-xl-6">
							<div class="form-group row" id="before-tax">
								<label class="col-md-6 col-form-label">{{ _lang('Sub Total') }}</label>
								<div class="col-md-6">
									<input type="text" class="form-control text-md-right" name="sub_total" id="sub_total" value="{{ old('sub_total') }}" readonly>
								</div>
							</div>

							<div class="form-group row" id="after-tax">
								<label class="col-md-6 col-form-label">{{ _lang('Discount Amount') }}</label>
								<div class="col-md-6">
									<input type="text" class="form-control text-md-right" name="discount" id="discount" value="{{ old('discount') }}" readonly>
								</div>
							</div>

							<div class="form-group row">
								<label class="col-md-6 col-form-label">{{ _lang('Grand Total') }}</label>
								<div class="col-md-6">
									<input type="text" class="form-control text-md-right" name="grand_total" id="grand_total" value="{{ old('grand_total') }}" readonly>
								</div>
							</div>
						</div>
					</div>

					<div class="row my-4">
						<div class="col-12">
							<div class="divider"></div>
						</div>
					</div>

					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label class="control-label">{{ _lang('Notes') }}</label>
								<textarea class="form-control" name="note">{{ old('note') }}</textarea>
							</div>
						</div>

						<div class="col-md-12">
							<div class="form-group">
								<label class="control-label">{{ _lang('Footer') }}</label>
								<textarea class="form-control" name="footer">{!! xss_clean(get_business_option('invoice_footer', old('footer'))) !!}</textarea>
							</div>
						</div>

						<div class="col-md-12 mt-4">
							<div class="form-group">
								<button type="submit" class="btn btn-primary"><i class="ti-check-box mr-1"></i>{{ _lang('Save Invoice') }}</button>
							</div>
						</div>
					</div>

				</div>
			</div>
		</div>

		<div class="col-xl-3 col-lg-4">
			<div class="card sticky-card">
				<div class="card-body">
					<div class="row">
						<div class="col-md-12">
							<div class="form-group">
								<label class="control-label">{{ _lang('Invoice Template') }}</label>
								<select class="form-control auto-select" data-selected="{{ old('template', 'default') }}" name="template" required>
									<option value="default">{{ _lang('Default') }}</option>
								</select>
							</div>
						</div>

						<div class="col-md-12">
							<div class="form-group">
								<label class="control-label">{{ _lang('Due Date') }}</label>
								<select class="form-control auto-select" data-selected="{{ old('recurring_due_date', '+0 day') }}" name="recurring_due_date" required>
									<option value="+0 day">{{ _lang('On Receipt') }}</option>
									<option value="+1 week">{{ _lang('Within 1 Week') }}</option>
									<option value="+2 week">{{ _lang('Within 2 Week') }}</option>
									<option value="+1 month">{{ _lang('Within 1 Month') }}</option>
									<option value="+45 days">{{ _lang('Within 45 Days') }}</option>
									<option value="+2 month">{{ _lang('Within 2 Month') }}</option>
									<option value="+3 month">{{ _lang('Within 3 Month') }}</option>
								</select>
							</div>
						</div>

						<div class="col-md-12">
							<div class="form-group">
								<label class="control-label">{{ _lang('Discount Value') }}</label>
								<div class="input-group">
									<div class="input-group-prepend">
										<select class="form-control discount_type" id="discount_type" name="discount_type" value="{{ old('discount_type') }}">
											<option value="0">%</option>
											<option value="1">{{ currency_symbol(request()->activeBusiness->currency) }}</option>
										</select>
									</div>
									<input type="number" class="form-control" name="discount_value" id="discount_value" min="0" value="{{ old('discount_value',0) }}">
								</div>
							</div>
						</div>

						<div class="col">
							<div class="form-group">
								<button type="submit" class="btn btn-primary btn-block"><i class="ti-check-box mr-1"></i>{{ _lang('Save Invoice') }}</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</form>


<table class="d-none">
	<tr class="line-item" id="copy-line">
		<td class="input-lg">
			<input type="hidden" class="product_id" name="product_id[]">
			<input type="hidden" class="product_type" name="product_type[]">
			<input type="text" class="form-control product_name" name="product_name[]"><br>
			<textarea class="form-control description" name="description[]" placeholder="{{ _lang('Descriptions') }}"></textarea>
		</td>
		<td class="input-md">
			<select name="taxes[][]" class="multi-selector product_taxes" data-placeholder="{{ _lang('Select Taxes') }}" multiple>
				@foreach(\App\Models\Tax::all() as $tax)
				<option value="{{ $tax->id }}" data-tax-rate="{{ $tax->rate }}" data-tax-name="{{ $tax->name }} {{ $tax->rate }} %">{{ $tax->name }} {{ $tax->rate }} %</option>
				@endforeach
			</select>
		</td>
		<td class="input-xs text-center"><input type="number" class="form-control quantity" name="quantity[]" min="1"></td>
		<td class="input-sm"><input type="text" class="form-control text-right unit_cost" name="unit_cost[]"></td>
		<td class="input-sm"><input type="text" class="form-control text-right sub_total" name="sub_total[]" readonly></td>
		<td class="input-xxs text-center"><button type="button" class="btn btn-outline-danger btn-xs mt-1 btn-remove-row"><i class="fas fa-minus-circle"></i></button></td>
	</tr>
</table>
@endsection

@section('js-script')
<script src="{{ asset('public/backend/assets/js/invoice.js?v=1.0') }}"></script>
@endsection



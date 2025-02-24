<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Product" page="user" subpage="view" />

		<div class="box">
			<div class="box-header">
				<h5>{{ _lang('Product Details') }}</h5>
			</div>

			<div class="box-body">
				<div class="grid grid-cols-12 gap-4">
					<!-- Product Details -->
					<div class="col-span-12 lg:col-span-6">
						<h6 class="font-medium mb-4">{{ _lang('Basic Information') }}</h6>
						<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
							<table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
								<tr>
									<td colspan="2" class="text-center"><img class="w-14" src="{{ asset('/uploads/media/'.$product->image) }}"></td>
								</tr>
								<tr>
									<td>{{ _lang('Name') }}</td>
									<td>{{ $product->name }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Type') }}</td>
									<td>{{ ucwords($product->type) }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Product Unit') }}</td>
									<td>{{ $product->product_unit->unit }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Purchase Cost') }}</td>
									<td>{{ formatAmount($product->purchase_cost, currency_symbol(request()->activeBusiness->currency)) }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Selling Price') }}</td>
									<td>{{ formatAmount($product->selling_price, currency_symbol(request()->activeBusiness->currency)) }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Descriptions') }}</td>
									<td>{{ $product->descriptions }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Stock') }}</td>
									<td>{{ $product->stock }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Allow For Selling') }}</td>
									<td>{!! $product->allow_for_selling == 1 ? xss_clean(show_status(_lang('Yes'), 'success')) : xss_clean(show_status(_lang('No'), 'danger')) !!}</td>
								</tr>
								<tr>
									<td>{{ _lang('Allow For Purchasing') }}</td>
									<td>{!! $product->allow_for_purchasing == 1 ? xss_clean(show_status(_lang('Yes'), 'success')) : xss_clean(show_status(_lang('No'), 'danger')) !!}</td>
								</tr>
								<tr>
									<td>{{ _lang('Income Account') }}</td>
									<td>{{ $product->income_account->account_name }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Expense Account') }}</td>
									<td>{{ $product->expense_account->account_name }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Status') }}</td>
									<td>{!! xss_clean(status($product->status)) !!}</td>
								</tr>
							</table>
						</div>
					</div>

					<!-- Sales Statistics -->
					<div class="col-span-12 lg:col-span-6">
						<h6 class="font-medium mb-4">{{ _lang('Sales Statistics') }}</h6>
						<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
							<table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
								<tr>
									<td>{{ _lang('Total Sales Quantity') }}</td>
									<td>{{ $product->invoice_items_sum_quantity ?? 0 }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Total Sales Amount') }}</td>
									<td>{{ formatAmount($product->invoice_items_sum_sub_total ?? 0, currency_symbol(request()->activeBusiness->currency)) }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Total Purchase Quantity') }}</td>
									<td>{{ $product->purchase_items_sum_quantity ?? 0 }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Total Purchase Amount') }}</td>
									<td>{{ formatAmount($product->purchase_items_sum_sub_total ?? 0, currency_symbol(request()->activeBusiness->currency)) }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Sales Returns') }}</td>
									<td>{{ $product->sales_return_items_sum_quantity ?? 0 }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Purchase Returns') }}</td>
									<td>{{ $product->purchase_return_items_sum_quantity ?? 0 }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Average Sale Price') }}</td>
									<td>{{ $product->invoice_items_sum_quantity > 0 ? formatAmount(($product->invoice_items_sum_sub_total / $product->invoice_items_sum_quantity), currency_symbol(request()->activeBusiness->currency)) : formatAmount(0, currency_symbol(request()->activeBusiness->currency)) }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Profit Margin') }}</td>
									<td>{{ $product->selling_price > 0 ? number_format((($product->selling_price - $product->purchase_cost) / $product->selling_price) * 100, 2) : 0 }}%</td>
								</tr>
							</table>
						</div>
					</div>

					<!-- Recent Transactions -->
					<div class="col-span-12">
						<h6 class="font-medium mb-4">{{ _lang('Recent Transactions') }}</h6>
						<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
							<table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
								<thead>
									<tr>
										<th>{{ _lang('Date') }}</th>
										<th>{{ _lang('Type') }}</th>
										<th>{{ _lang('Reference') }}</th>
										<th>{{ _lang('Customer/Supplier') }}</th>
										<th class="text-right">{{ _lang('Quantity') }}</th>
										<th class="text-right">{{ _lang('Unit Price') }}</th>
										<th class="text-right">{{ _lang('Total') }}</th>
									</tr>
								</thead>
								<tbody>
									@forelse($product->getAllTransactions() as $transaction)
									<tr>
										<td>{{ format_date($transaction->date) }}</td>
										<td>
											@if($transaction->type == 'sale')
												<span class="badge bg-primary">{{ _lang('Sale') }}</span>
											@elseif($transaction->type == 'purchase')
												<span class="badge bg-info">{{ _lang('Purchase') }}</span>
											@elseif($transaction->type == 'sale_return')
												<span class="badge bg-warning">{{ _lang('Sale Return') }}</span>
											@else
												<span class="badge bg-danger">{{ _lang('Purchase Return') }}</span>
											@endif
										</td>
										<td>
											<a href="{{ $transaction->reference_url }}" class="text-primary">{{ $transaction->reference }}</a>
										</td>
										<td>{{ $transaction->party_name }}</td>
										<td class="text-right">{{ $transaction->quantity }}</td>
										<td class="text-right">{{ formatAmount($transaction->unit_price, currency_symbol(request()->activeBusiness->currency)) }}</td>
										<td class="text-right">{{ formatAmount($transaction->total, currency_symbol(request()->activeBusiness->currency)) }}</td>
									</tr>
									@empty
									<tr>
										<td colspan="7" class="text-center">{{ _lang('No Transactions Found') }}</td>
									</tr>
									@endforelse
								</tbody>
							</table>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>
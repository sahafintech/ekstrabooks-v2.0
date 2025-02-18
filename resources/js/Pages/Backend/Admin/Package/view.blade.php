<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Packages" page="home" subpage="package details" />

		<div class="box">
			<div class="box-header">
				<h5>{{ _lang('Package Details') }}</h5>
			</div>

			<div class="box-body">
				<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
					<table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
						<tr>
							<td>{{ _lang('Pasckage Name') }}</td>
							<td>{{ $package->name }}</td>
						</tr>
						<tr>
							<td>{{ _lang('Package Type') }}</td>
							<td>{{ ucwords($package->package_type) }}</td>
						</tr>
						<tr>
							<td>{{ _lang('Cost') }}</td>
							<td>{{ decimalPlace($package->cost, currency_symbol()) }}</td>
						</tr>
						<tr>
							<td>{{ _lang('Status') }}</td>
							<td>
								@if($package->status == 1)
								<span class="badge bg-success rounded-md text-white">{{ _lang('Active') }}</span>
								@else
								<span class="badge bg-danger rounded-md text-white">{{ _lang('Disabled') }}</span>
								@endif
							</td>
						</tr>
						<tr>
							<td>{{ _lang('Is Popular') }}</td>
							<td>
								@if($package->is_popular == 1)
								<span class="badge bg-success rounded-md text-white">{{ _lang('Yes') }}</span>
								@else
								<span class="badge bg-danger rounded-md text-white">{{ _lang('No') }}</span>
								@endif
							</td>
						</tr>
						<tr>
							<td>{{ _lang('Discount') }}</td>
							<td>{{ $package->discount }} %</td>
						</tr>
						<tr>
							<td>{{ _lang('Trial Days') }}</td>
							<td>{{ $package->trial_days }}</td>
						</tr>
						<tr>
							<td>{{ _lang('User Limit') }}</td>
							<td>{{ $package->user_limit != '-1' ? $package->user_limit : _lang('Unlimited') }}</td>
						</tr>
						<tr>
							<td>{{ _lang('Invoice Limit') }}</td>
							<td>{{ $package->invoice_limit != '-1' ? $package->invoice_limit : _lang('Unlimited') }}</td>
						</tr>
						<tr>
							<td>{{ _lang('Quotation Limit') }}</td>
							<td>{{ $package->quotation_limit != '-1' ? $package->quotation_limit : _lang('Unlimited') }}</td>
						</tr>
						<tr>
							<td>{{ _lang('Recurring Invoice') }}</td>
							<td>
								@if($package->recurring_invoice == 1)
								<span class="badge bg-success rounded-md text-white">{{ _lang('Yes') }}</span>
								@else
								<span class="badge bg-danger rounded-md text-white">{{ _lang('No') }}</span>
								@endif
							</td>
						</tr>
						<tr>
							<td>{{ _lang('Customer Limit') }}</td>
							<td>{{ $package->customer_limit != '-1' ? $package->customer_limit : _lang('Unlimited') }}</td>
						</tr>
						<tr>
							<td>{{ _lang('Business Limit') }}</td>
							<td>{{ $package->business_limit != '-1' ? $package->business_limit : _lang('Unlimited') }}</td>
						</tr>
						<tr>
							<td>{{ _lang('Invoice Template Maker') }}</td>
							<td>
								@if($package->invoice_builder == 1)
								<span class="badge bg-success rounded-md text-white">{{ _lang('Yes') }}</span>
								@else
								<span class="badge bg-danger rounded-md text-white">{{ _lang('No') }}</span>
								@endif
							</td>
						</tr>
						<tr>
							<td>{{ _lang('Online Invoice Payment') }}</td>
							<td>
								@if($package->online_invoice_payment == 1)
								<span class="badge bg-success rounded-md text-white">{{ _lang('Yes') }}</span>
								@else
								<span class="badge bg-danger rounded-md text-white">{{ _lang('No') }}</span>
								@endif
							</td>
						</tr>
						<tr>
							<td>{{ _lang('HR & Payroll Module') }}</td>
							<td>
								@if($package->payroll_module == 1)
								<span class="badge bg-success rounded-md text-white">{{ _lang('Yes') }}</span>
								@else
								<span class="badge bg-danger rounded-md text-white">{{ _lang('No') }}</span>
								@endif
							</td>
						</tr>
					</table>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>
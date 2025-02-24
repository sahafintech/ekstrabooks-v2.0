<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Supplier" page="user" subpage="view" />

		<div class="grid grid-cols-12 gap-x-5">
			<div class="lg:col-span-3 col-span-12">
				<div class="box">
					<div class="box-body">
						<div class="max-w-xs flex flex-col">
							<a class="ti-list-group gap-x-3.5 ti-icon-link focus:ring-primary dark:border-white/10 {{ isset($_GET['tab']) ? '' : 'text-primary'  }}" href="{{ route('vendors.show', $vendor->id) }}">
								<i class="ri-tools-line text-xl"></i>
								{{ _lang('Overview') }}
							</a>
							<a class="ti-list-group gap-x-3.5 ti-icon-link focus:ring-primary dark:border-white/10 {{ isset($_GET['tab']) && $_GET['tab'] == 'purchases' ? 'text-primary' : ''  }}" href="{{ route('vendors.show', $vendor->id) }}?tab=purchases">
								<i class="ri-article-line text-xl"></i>
								{{ _lang('Purchases') }}
							</a>
							<a class="ti-list-group gap-x-3.5 ti-icon-link focus:ring-primary dark:border-white/10 {{ isset($_GET['tab']) && $_GET['tab'] == 'transactions' ? 'text-primary' : ''  }}" href="{{ route('vendors.show', $vendor->id) }}?tab=transactions">
								<i class="ri-bank-line text-xl"></i>
								{{ _lang('Transactions') }}
							</a>
							<a class="ti-list-group gap-x-3.5 ti-icon-link focus:ring-primary dark:border-white/10" href="{{ route('vendors.edit', $vendor->id) }}">
								<i class="ri-edit-box-line text-xl"></i>
								{{ _lang('Edit Details') }}
							</a>
						</div>
					</div>
				</div>
			</div>


			@if(! isset($_GET['tab']))
			<div class="lg:col-span-9 col-span-12">
				<div class="box">
					<div class="box-body">
						<div class="grid grid-cols-12 gap-x-5">
							<div class="md:col-span-6 col-span-12">
								<div class="box overflow-hidden">
									<div class="box-body">
										<div class="flex">
											<div class="flex space-x-3 rtl:space-x-reverse">
												<div class="avatar p-2 rounded-sm bg-primary/10">
													<i class="ri-file-list-3-line text-3xl text-rose-400"></i>
												</div>
												<h6 class="text-lg font-medium text-gray-800 mb-2 dark:text-white my-auto">
													{{ _lang('Total Bill') }}
												</h6>
											</div>
										</div>
										<div class="mt-2">
											<h2 class="text-2xl font-semibold text-gray-800 dark:text-white">
												{{ $purchase->total_bill }}
											</h2>
										</div>
									</div>
								</div>
							</div>

							<div class="md:col-span-6 col-span-12">
								<div class="box overflow-hidden">
									<div class="box-body">
										<div class="flex">
											<div class="flex space-x-3 rtl:space-x-reverse">
												<div class="avatar p-2 rounded-sm bg-primary/10">
													<i class="ri-exchange-dollar-line text-3xl text-rose-400"></i>
												</div>
												<h6 class="text-lg font-medium text-gray-800 mb-2 dark:text-white my-auto">
													{{ _lang('Total Amount') }}
												</h6>
											</div>
										</div>
										<div class="mt-2">
											<h2 class="text-2xl font-semibold text-gray-800 dark:text-white">
												{{ formatAmount($purchase->total_amount, currency_symbol(request()->activeBusiness->currency)) }}
											</h2>
										</div>
									</div>
								</div>
							</div>

							<div class="md:col-span-6 col-span-12">
								<div class="box overflow-hidden">
									<div class="box-body">
										<div class="flex">
											<div class="flex space-x-3 rtl:space-x-reverse">
												<div class="avatar p-2 rounded-sm bg-primary/10">
													<i class="ri-exchange-dollar-line text-3xl text-rose-400"></i>
												</div>
												<h6 class="text-lg font-medium text-gray-800 mb-2 dark:text-white my-auto">
													{{ _lang('Total Paid') }}
												</h6>
											</div>
										</div>
										<div class="mt-2">
											<h2 class="text-2xl font-semibold text-gray-800 dark:text-white">
												{{ formatAmount($purchase->total_paid, currency_symbol(request()->activeBusiness->currency)) }}
											</h2>
										</div>
									</div>
								</div>
							</div>

							<div class="md:col-span-6 col-span-12">
								<div class="box overflow-hidden">
									<div class="box-body">
										<div class="flex">
											<div class="flex space-x-3 rtl:space-x-reverse">
												<div class="avatar p-2 rounded-sm bg-primary/10">
													<i class="ri-exchange-dollar-line text-3xl text-rose-400"></i>
												</div>
												<h6 class="text-lg font-medium text-gray-800 mb-2 dark:text-white my-auto">
													{{ _lang('Due Amount') }}
												</h6>
											</div>
										</div>
										<div class="mt-2">
											<h2 class="text-2xl font-semibold text-gray-800 dark:text-white">
												{{ formatAmount($purchase->total_amount - $purchase->total_paid, currency_symbol(request()->activeBusiness->currency)) }}
											</h2>
										</div>
									</div>
								</div>
							</div>
						</div>

						<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
							<table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
								<tr>
									<td>{{ _lang('Name') }}</td>
									<td>{{ $vendor->name }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Company Name') }}</td>
									<td>{{ $vendor->company_name }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Email') }}</td>
									<td>{{ $vendor->email }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Tax Registration No') }}</td>
									<td>{{ $vendor->registration_no }}</td>
								</tr>
								<tr>
									<td>{{ _lang('VAT ID') }}</td>
									<td>{{ $vendor->vat_id }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Mobile') }}</td>
									<td>{{ $vendor->mobile }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Country') }}</td>
									<td>{{ $vendor->country }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Currency') }}</td>
									<td>{{ $vendor->currency }}</td>
								</tr>
								<tr>
									<td>{{ _lang('City') }}</td>
									<td>{{ $vendor->city }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Contract No') }}</td>
									<td>{{ $vendor->contract_no }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Zip') }}</td>
									<td>{{ $vendor->zip }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Address') }}</td>
									<td>{{ $vendor->address }}</td>
								</tr>
							</table>
						</div>
					</div>
				</div>
				@else
				<div class="lg:col-span-9 col-span-12">
					@include('backend.user.vendor.tabs.'.$_GET['tab'])
				</div>
				@endif

			</div>
		</div>
	</div>
</x-app-layout>
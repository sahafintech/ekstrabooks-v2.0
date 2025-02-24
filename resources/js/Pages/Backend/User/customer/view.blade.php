<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Customers" page="user" subpage="view" />

		<div class="grid grid-cols-12 gap-x-5">
			<div class="lg:col-span-3 col-span-12">
				<div class="box">
					<div class="box-body">
						<div class="max-w-xs flex flex-col">
							<a class="ti-list-group gap-x-3.5 ti-icon-link focus:ring-primary dark:border-white/10 {{ isset($_GET['tab']) ? '' : 'text-primary'  }}" href="{{ route('customers.show', $customer->id) }}">
								<i class="ri-tools-line text-xl"></i>
								{{ _lang('Overview') }}
							</a>
							<a class="ti-list-group gap-x-3.5 ti-icon-link focus:ring-primary dark:border-white/10 {{ isset($_GET['tab']) && $_GET['tab'] == 'invoices' ? 'text-primary' : ''  }}" href="{{ route('customers.show', $customer->id) }}?tab=invoices">
								<i class="ri-article-line text-xl"></i>
								{{ _lang('Credit Invoices') }}
							</a>
							<a class="ti-list-group gap-x-3.5 ti-icon-link focus:ring-primary dark:border-white/10 {{ isset($_GET['tab']) && $_GET['tab'] == 'receipts' ? 'text-primary' : ''  }}" href="{{ route('customers.show', $customer->id) }}?tab=receipts">
								<i class="ri-article-line text-xl"></i>
								{{ _lang('Cash Invoices') }}
							</a>
							<a class="ti-list-group gap-x-3.5 ti-icon-link focus:ring-primary dark:border-white/10 {{ isset($_GET['tab']) && $_GET['tab'] == 'quotations' ? 'text-primary' : ''  }}" href="{{ route('customers.show', $customer->id) }}?tab=quotations">
								<i class="ri-article-line text-xl"></i>
								{{ _lang('Quotations') }}
							</a>
							<a class="ti-list-group gap-x-3.5 ti-icon-link focus:ring-primary dark:border-white/10 {{ isset($_GET['tab']) && $_GET['tab'] == 'transactions' ? 'text-primary' : ''  }}" href="{{ route('customers.show', $customer->id) }}?tab=transactions">
								<i class="ri-bank-line text-xl"></i>
								{{ _lang('Transactions') }}
							</a>
							<a class="ti-list-group gap-x-3.5 ti-icon-link focus:ring-primary dark:border-white/10" href="{{ route('customers.edit', $customer->id) }}">
								<i class="ri-edit-box-line text-xl"></i>
								{{ _lang('Edit Details') }}
							</a>
						</div>
					</div>
				</div>
			</div>


			<div class="lg:col-span-9 col-span-12">
				@if(! isset($_GET['tab']))
				<div class="box">
					<div class="box-body">
						<div class="grid grid-cols-12 gap-x-5">
							<div class="md:col-span-6 col-span-12">
								<div class="box overflow-hidden bg-gray-50">
									<div class="box-body">
										<div class="flex">
											<div class="flex space-x-3 rtl:space-x-reverse">
												<div class="avatar p-2 rounded-sm bg-primary/10">
													<i class="ri-file-list-3-line text-3xl text-rose-400"></i>
												</div>
												<h6 class="text-lg font-medium text-gray-800 mb-2 dark:text-white my-auto">
													{{ _lang('Total Invoices') }}
												</h6>
											</div>
										</div>
										<div class="mt-2">
											<h2 class="text-2xl font-semibold text-gray-800 dark:text-white">
												{{ $invoice->total_invoice }}
											</h2>
										</div>
									</div>
								</div>
							</div>

							<div class="md:col-span-6 col-span-12">
								<div class="box overflow-hidden bg-gray-50">
									<div class="box-body">
										<div class="flex">
											<div class="flex space-x-3 rtl:space-x-reverse">
												<div class="avatar p-2 rounded-sm bg-primary/10">
													<i class="ri-exchange-dollar-line text-3xl text-rose-400"></i>
												</div>
												<h6 class="text-lg font-medium text-gray-800 mb-2 dark:text-white my-auto">
													{{ _lang('Total Invoice Amount') }}
												</h6>
											</div>
										</div>
										<div class="mt-2">
											<h2 class="text-2xl font-semibold text-gray-800 dark:text-white">
												{{ formatAmount($invoice->total_amount, currency_symbol(request()->activeBusiness->currency)) }}
											</h2>
										</div>
									</div>
								</div>
							</div>

							<div class="md:col-span-6 col-span-12">
								<div class="box overflow-hidden bg-gray-50">
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
												{{ formatAmount($invoice->total_paid, currency_symbol(request()->activeBusiness->currency)) }}
											</h2>
										</div>
									</div>
								</div>
							</div>

							<div class="md:col-span-6 col-span-12">
								<div class="box overflow-hidden bg-gray-50">
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
												{{ formatAmount($invoice->total_amount - $invoice->total_paid, currency_symbol(request()->activeBusiness->currency)) }}
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
									<td>{{ $customer->name }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Company Name') }}</td>
									<td>{{ $customer->company_name }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Email') }}</td>
									<td>{{ $customer->email }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Mobile') }}</td>
									<td>{{ $customer->mobile }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Age') }}</td>
									<td>{{ $customer->age }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Gender') }}</td>
									<td>{{ ucwords($customer->gender) }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Country') }}</td>
									<td>{{ $customer->country }}</td>
								</tr>
								<tr>
									<td>{{ _lang('VAT ID') }}</td>
									<td>{{ $customer->vat_id }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Tax Reg No') }}</td>
									<td>{{ $customer->reg_no }}</td>
								</tr>
								<tr>
									<td>{{ _lang('City') }}</td>
									<td>{{ $customer->city }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Contract No') }}</td>
									<td>{{ $customer->contract_no }}</td>
								</tr>
								<tr>
									<td>{{ _lang('Zip') }}</td>
									<td>{{ $customer->zip }}</td>
								<tr>
									<td>{{ _lang('Address') }}</td>
									<td>{{ $customer->address }}</td>
								</tr>
							</table>
						</div>
					</div>
				</div>
				@else
				<div class="lg:col-span-9 col-span-12">
					@include('backend.user.customer.tabs.'.$_GET['tab'])
				</div>
				@endif
			</div>
		</div>
	</div>
</x-app-layout>
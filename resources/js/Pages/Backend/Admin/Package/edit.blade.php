<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Packages" page="home" subpage="create" />

		<div class="w-full">
			<div class="box">
				<div class="box-header">
					<div>{{ _lang('Add New Package') }}</div>
				</div>
				<div class="box-body">
					<form method="post" class="validate" autocomplete="off" action="{{ route('packages.update', $id) }}" enctype="multipart/form-data">
						{{ csrf_field() }}
						<input name="_method" type="hidden" value="PATCH">
						<div>
							<div class="grid grid-cols-12 gap-x-5 items-center">
								<x-input-label class="xl:col-span-3 col-span-12" value="{{ _lang('Package Name') }}" />
								<div class="xl:col-span-9 col-span-12 my-2">
									<x-text-input type="text" name="name" value="{{ $package->name }}" required />
								</div>
							</div>

							<div class="grid grid-cols-12 gap-x-5 items-center">
								<x-input-label class="xl:col-span-3 col-span-12" value="{{ _lang('Package Type') }}" />
								<div class="xl:col-span-9 col-span-12 my-2">
									<select class="w-full auto-select" data-selected="{{ $package->package_type }}" name="package_type" required>
										<option value="">{{ _lang('Select One') }}</option>
										<option value="monthly">{{ _lang('Monthly') }}</option>
										<option value="yearly">{{ _lang('Yearly') }}</option>
										<option value="lifetime">{{ _lang('Lifetime') }}</option>
									</select>
								</div>
							</div>

							<div class="grid grid-cols-12 gap-x-5 items-center">
								<x-input-label class="xl:col-span-3 col-span-12" value="{{ _lang('Cost') }} ({{ currency_symbol() }})" />
								<div class="xl:col-span-9 col-span-12 my-2">
									<x-text-input type="text" name="cost" value="{{ $package->discount }}" required />
								</div>
							</div>

							<div class="grid grid-cols-12 gap-x-5 items-center">
								<x-input-label class="xl:col-span-3 col-span-12" value="{{ _lang('Discount') }} (%)" />
								<div class="xl:col-span-9 col-span-12 my-2">
									<x-text-input type="number" name="discount" value="{{ $package->discount }}" required />
								</div>
							</div>

							<div class="grid grid-cols-12 gap-x-5 items-center">
								<x-input-label class="xl:col-span-3 col-span-12" value="{{ _lang('Trial Days') }}" />
								<div class="xl:col-span-9 col-span-12 my-2">
									<x-text-input type="number" name="trial_days" value="{{ $package->trial_days }}" required />
								</div>
							</div>

							<div class="grid grid-cols-12 gap-x-5 items-center">
								<x-input-label class="xl:col-span-3 col-span-12" value="{{ _lang('Status') }}" />
								<div class="xl:col-span-9 col-span-12 my-2">
									<select class="w-full auto-select" data-selected="{{ $package->status }}" name="status" required>
										<option value="1">{{ _lang('Active') }}</option>
										<option value="0">{{ _lang('Disabled') }}</option>
									</select>
								</div>
							</div>

							<div class="grid grid-cols-12 gap-x-5 items-center">
								<x-input-label class="xl:col-span-3 col-span-12" value="{{ _lang('Is Popular') }}" />
								<div class="xl:col-span-9 col-span-12 my-2">
									<select class="w-full auto-select" data-selected="{{ $package->is_popular }}" name="is_popular" required>
										<option value="0">{{ _lang('No') }}</option>
										<option value="1">{{ _lang('Yes') }}</option>
									</select>
								</div>
							</div>

							<hr>
							<div class="py-4">
								<h5><strong>{{ _lang('Manage Package Features') }}</strong></h5>
							</div>
							<hr>

							<div class="grid grid-cols-12 gap-x-5 items-center">
								<x-input-label class="xl:col-span-3 col-span-12" value="{{ _lang('System User Limit') }}" />
								<div class="xl:col-span-7 col-span-12 my-2">
									<x-text-input type="number" name="user_limit" value="{{ $package->user_limit != '-1' ? $package->user_limit : '' }}" placeholder="5" />
								</div>

								<div class="xl:col-span-2">
									<div>
										<label class="text-danger flex items-center">
											<input class="mr-2" type="checkbox" name="user_limit" value="-1" {{ $package->user_limit == '-1' ? 'checked' : '' }}>{{ _lang('UNLIMITED') }}
										</label>
									</div>
								</div>
							</div>

							<div class="grid grid-cols-12 gap-x-5 items-center">
								<x-input-label class="xl:col-span-3 col-span-12" value="{{ _lang('Invoice Limit') }}" />
								<div class="xl:col-span-7 col-span-12 my-2">
									<x-text-input type="number" name="invoice_limit" value="{{ $package->invoice_limit != '-1' ? $package->invoice_limit : '' }}" placeholder="100" />
								</div>

								<div class="xl:col-span-2">
									<div>
										<label class="text-danger flex items-center">
											<input class="mr-2" type="checkbox" name="invoice_limit" value="-1" {{ $package->invoice_limit == '-1' ? 'checked' : '' }}>{{ _lang('UNLIMITED') }}
										</label>
									</div>
								</div>
							</div>

							<div class="grid grid-cols-12 gap-x-5 items-center">
								<x-input-label class="xl:col-span-3 col-span-12" value="{{ _lang('Quotation Limit') }}" />
								<div class="xl:col-span-7 col-span-12 my-2">
									<x-text-input type="number" name="quotation_limit" value="{{ $package->quotation_limit != '-1' ? $package->quotation_limit : '' }}" placeholder="150" />
								</div>

								<div class="xl:col-span-2">
									<div>
										<label class="text-danger flex items-center">
											<input class="mr-2" type="checkbox" name="quotation_limit" value="-1" {{ $package->quotation_limit == '-1' ? 'checked' : '' }}>{{ _lang('UNLIMITED') }}
										</label>
									</div>
								</div>
							</div>

							<div class="grid grid-cols-12 gap-x-5 items-center">
								<x-input-label class="xl:col-span-3 col-span-12" value="{{ _lang('Recurring Limit') }}" />
								<div class="xl:col-span-7 col-span-12 my-2">
									<select class="w-full auto-select" data-selected="{{ $package->recurring_invoice }}" name="recurring_invoice" required>
										<option value="0">{{ _lang('No') }}</option>
										<option value="1">{{ _lang('Yes') }}</option>
									</select>
								</div>
							</div>

							<div class="grid grid-cols-12 gap-x-5 items-center">
								<x-input-label class="xl:col-span-3 col-span-12" value="{{ _lang('Deffered Limit') }}" />
								<div class="xl:col-span-7 col-span-12 my-2">
									<select class="w-full auto-select" data-selected="{{ $package->deffered_invoice }}" name="deffered_invoice" required>
										<option value="0">{{ _lang('No') }}</option>
										<option value="1">{{ _lang('Yes') }}</option>
									</select>
								</div>
							</div>

							<div class="grid grid-cols-12 gap-x-5 items-center">
								<x-input-label class="xl:col-span-3 col-span-12" value="{{ _lang('Customer  Limit') }}" />
								<div class="xl:col-span-7 col-span-12 my-2">
									<x-text-input type="number" name="customer_limit" value="{{ $package->customer_limit != '-1' ? $package->customer_limit : '' }}" placeholder="100" />
								</div>
								<div class="xl:col-span-2">
									<div>
										<label class="text-danger flex items-center">
											<input class="mr-2" type="checkbox" name="customer_limit" value="-1" {{ $package->customer_limit == '-1' ? 'checked' : '' }}>{{ _lang('UNLIMITED') }}
										</label>
									</div>
								</div>
							</div>

							<div class="grid grid-cols-12 gap-x-5 items-center">
								<x-input-label class="xl:col-span-3 col-span-12" value="{{ _lang('Business Limit') }}" />
								<div class="xl:col-span-7 col-span-12 my-2">
									<x-text-input type="number" name="business_limit" value="{{ $package->business_limit }}" placeholder="10" />
								</div>
								<div class="xl:col-span-2">
									<div>
										<label class="text-danger flex items-center">
											<input class="mr-2" type="checkbox" name="business_limit" value="-1" {{ $package->business_limit == '-1' ? 'checked' : '' }}>{{ _lang('UNLIMITED') }}
										</label>
									</div>
								</div>
							</div>

							<div class="grid grid-cols-12 gap-x-5 items-center">
								<x-input-label class="xl:col-span-3 col-span-12" value="{{ _lang('Invoice Template Maker') }}" />
								<div class="xl:col-span-7 col-span-12 my-2">
									<select class="w-full auto-select" data-selected="{{ $package->invoice_builder }}" name="invoice_builder" required>
										<option value="">{{ _lang('Select One') }}</option>
										<option value="0">{{ _lang('No') }}</option>
										<option value="1">{{ _lang('Yes') }}</option>
									</select>
								</div>
							</div>

							<div class="grid grid-cols-12 gap-x-5 items-center">
								<x-input-label class="xl:col-span-3 col-span-12" value="{{ _lang('Online Invoice Payment') }}" />
								<div class="xl:col-span-7 col-span-12 my-2">
									<select class="w-full auto-select" data-selected="{{ $package->online_invoice_payment }}" name="online_invoice_payment" required>
										<option value="">{{ _lang('Select One') }}</option>
										<option value="0">{{ _lang('No') }}</option>
										<option value="1">{{ _lang('Yes') }}</option>
									</select>
								</div>
							</div>

							<div class="grid grid-cols-12 gap-x-5 items-center">
								<x-input-label class="xl:col-span-3 col-span-12" value="{{ _lang('POS') }}" />
								<div class="xl:col-span-7 col-span-12 my-2">
									<select class="w-full ti-form-select auto-select" data-selected="{{ $package->pos }}" name="pos" required>
										<option value="">{{ _lang('Select One') }}</option>
										<option value="0">{{ _lang('No') }}</option>
										<option value="1">{{ _lang('Yes') }}</option>
									</select>
								</div>
							</div>

							<div class="grid grid-cols-12 gap-x-5 items-center">
								<x-input-label class="xl:col-span-3 col-span-12" value="{{ _lang('HR and Payroll Module') }}" />
								<div class="xl:col-span-7 col-span-12 my-2">
									<select class="w-full auto-select" data-selected="{{ $package->payroll_module }}" name="payroll_module" required>
										<option value="">{{ _lang('Select One') }}</option>
										<option value="0">{{ _lang('No') }}</option>
										<option value="1">{{ _lang('Yes') }}</option>
									</select>
								</div>
							</div>

							<div class="mt-2">
								<x-primary-button type="submit" class="submit-btn">
									{{ _lang('Save Changes') }}
								</x-primary-button>
							</div>
						</div>
					</form>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>
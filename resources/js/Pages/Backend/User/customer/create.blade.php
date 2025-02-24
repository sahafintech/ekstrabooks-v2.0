<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Customers" page="user" subpage="create" />

		<div class="box">
			<div class="box-header text-center">
				<h5>{{ _lang('Add New Customer') }}</h5>
			</div>
			<div class="box-body">
				<form method="post" class="validate" autocomplete="off" action="{{ route('customers.store') }}" enctype="multipart/form-data">
					{{ csrf_field() }}
					<div class="grid grid-cols-12 gap-x-2">
						<div class="md:col-span-6 col-span-12">
							<x-input-label>
								{{ _lang('Name') }}
								<span class='text-red-600'>*</span>
							</x-input-label>
							<x-text-input type="text" name="name" value="{{ old('name') }}" required />
						</div>

						<div class="md:col-span-6 col-span-12">
							<x-input-label value="{{ _lang('Company Name') }}" />
							<x-text-input type="text" name="company_name" value="{{ old('company_name') }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Email') }}" />
							<x-text-input type="text" name="email" value="{{ old('email') }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label>
								{{ _lang('Mobile') }}
								<span class='text-red-600'>*</span>
							</x-input-label>
							<x-text-input type="text" name="mobile" value="{{ old('mobile') }}" />
						</div>

						<!-- age -->
						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label>
								{{ _lang('Age') }}
							</x-input-label>
							<x-text-input type="number" name="age" value="{{ old('age') }}" />
						</div>

						<!-- gender -->
						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label>
								{{ _lang('Gender') }}
							</x-input-label>
							<select class="w-full selectize auto-select" name="gender" data-selected="{{ old('gender') }}">
								<option value="">{{ _lang('Select One') }}</option>
								<option value="male">Male</option>
								<option value="female">Female</option>
							</select>
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label>
								{{ _lang('Country') }}
								<span class="text-red-600">*</span>
							</x-input-label>
							<select class="w-full selectize auto-select" data-selected="{{ old('country') }}" name="country">
								<option value="">{{ _lang('Select One') }}</option>
								{{ get_country_list() }}
							</select>
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('VAT ID') }}" />
							<x-text-input type="text" name="vat_id" value="{{ old('vat_id') }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Tax Reg No') }}" />
							<x-text-input type="text" name="reg_no" value="{{ old('reg_no') }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('City') }}" />
							<x-text-input type="text" name="city" value="{{ old('city') }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Contract No') }}" />
							<x-text-input type="text" name="contract_no" value="{{ old('contract_no') }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Zip') }}" />
							<x-text-input type="text" name="zip" value="{{ old('zip') }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Address') }}" />
							<textarea class="w-full" name="address">{{ old('address') }}</textarea>
						</div>

						<div class="col-span-12 mt-4">
							<x-primary-button type="submit" class="submit-btn">{{ _lang('Save Changes') }}</x-primary-button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</x-app-layout>
<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Vendors" page="user" subpage="create" />

		<div class="box">
			<div class="box-header">
				<h5>{{ _lang('Add New Vendor') }}</h5>
			</div>
			<div class="box-body">
				<form method="post" class="validate" autocomplete="off" action="{{ route('vendors.store') }}" enctype="multipart/form-data">
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
							<x-text-input type="email" name="email" value="{{ old('email') }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('VAT ID') }}" />
							<x-text-input type="text" name="vat_id" value="{{ old('vat_id') }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Tax Registration No') }}" />
							<x-text-input type="text" name="registration_no" value="{{ old('registration_no') }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Mobile') }}" />
							<x-text-input type="text" name="mobile" value="{{ old('mobile') }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Country') }}" />
							<select class="w-full selectize auto-select" data-selected="{{ old('country') }}" name="country">
								<option value="">{{ _lang('Select One') }}</option>
								{{ get_country_list() }}
							</select>
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
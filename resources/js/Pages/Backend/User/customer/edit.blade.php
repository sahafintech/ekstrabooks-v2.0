<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Customers" page="user" subpage="create" />

		<div class="box">
			<div class="box-header text-center">
				<h5>{{ _lang('Update Customer') }}</h5>
			</div>
			<div class="box-body">
				<form method="post" class="validate" autocomplete="off" action="{{ route('customers.update', $id) }}" enctype="multipart/form-data">
					{{ csrf_field() }}
					<input name="_method" type="hidden" value="PATCH">
					<div class="grid grid-cols-12 gap-x-2">
						<div class="md:col-span-6 col-span-12">
							<x-input-label value="{{ _lang('Name') }}" />
							<x-text-input type="text" name="name" value="{{ $customer->name }}" required />
						</div>

						<div class="md:col-span-6 col-span-12">
							<x-input-label value="{{ _lang('Company Name') }}" />
							<x-text-input type="text" name="company_name" value="{{ $customer->company_name }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Email') }}" />
							<x-text-input type="text" name="email" value="{{ $customer->email }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Mobile') }}" />
							<x-text-input type="text" name="mobile" value="{{ $customer->mobile }}" />
						</div>

						<!-- age -->
						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Age') }}" />
							<x-text-input type="number" name="age" value="{{ $customer->age }}" />
						</div>

						<!-- gender -->
						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label>
								{{ _lang('Gender') }}
							</x-input-label>
							<select class="w-full selectize auto-select" name="gender" data-selected="{{ $customer->gender }}">
								<option value="">{{ _lang('Select One') }}</option>
								<option value="male">Male</option>
								<option value="female">Female</option>
							</select>
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Country') }}" />
							<select class="w-full selectize auto-select" data-selected="{{ $customer->country }}" name="country">
								<option value="">{{ _lang('Select One') }}</option>
								{{ get_country_list() }}
							</select>
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('VAT ID') }}" />
							<x-text-input type="text" name="vat_id" value="{{ $customer->vat_id }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Tax Reg No') }}" />
							<x-text-input type="text" name="reg_no" value="{{ $customer->reg_no }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('City') }}" />
							<x-text-input type="text" name="city" value="{{ $customer->city }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Contract No') }}" />
							<x-text-input type="text" name="contract_no" value="{{ $customer->contaract_no }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Zip') }}" />
							<x-text-input type="text" name="zip" value="{{ $customer->zip }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Address') }}" />
							<textarea class="w-full" name="address">{{ $customer->address }}</textarea>
						</div>

						<div class="col-span-12 mt-4">
							<x-primary-button type="submit" class="submit-btn">{{ _lang('Update') }}</x-primary-button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</x-app-layout>
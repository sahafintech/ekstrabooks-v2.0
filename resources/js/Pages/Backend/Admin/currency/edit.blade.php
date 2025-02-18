<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Currency" page="admin" subpage="create" />

		<div class="box">
			<div class="box-header">
				<h5>{{ _lang('Add New Currency') }}</h5>
			</div>
			<div class="box-body">
				<form method="post" class="validate" autocomplete="off"  action="{{ route('currency.update', $id) }}" enctype="multipart/form-data">
					{{ csrf_field() }}
					<div class="grid grid-cols-12 gap-x-5">
						<div class="md:col-span-6 col-span-12">
							<x-input-label value="{{ _lang('Name') }}" />
							<x-text-input type="text" name="name" value="{{ $currency->name }}" required />
						</div>

						<div class="md:col-span-6 col-span-12">
							<x-input-label value="{{ _lang('Exchange Rate') }}" />
							<x-text-input type="text" name="exchange_rate" value="{{ $currency->exchange_rate }}" required />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Base Currency') }}" />
							<select class="w-full" data-selected="{{ $currency->base_currency }}" name="base_currency" required>
								<option value="">{{ _lang('Select One') }}</option>
								<option value="0">{{ _lang('No') }}</option>
								<option value="1">{{ _lang('Yes') }}</option>
							</select>
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Status') }}" />
							<select class="w-full" data-selected="{{ $currency->status }}" name="status" required>
								<option value="">{{ _lang('Select One') }}</option>
								<option value="1">{{ _lang('Active') }}</option>
								<option value="0">{{ _lang('Deactivate') }}</option>
							</select>
						</div>

						<div class="col-span-12 mt-4">
							<x-primary-button type="submit" class="submit-btn">{{ _lang('Save') }}</x-primary-button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</x-app-layout>
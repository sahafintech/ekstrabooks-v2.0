<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Business Type" page="home" subpage="update" />
		<div class="box">
			<div class="box-header">
				<h5>{{ _lang('Update Business Type') }}</h5>
			</div>
			<div class="box-body">
				<form method="post" class="validate" autocomplete="off" action="{{ route('business_types.store') }}" enctype="multipart/form-data">
					@csrf
					<div class="grid grid-cols-12 mb-4">
						<x-input-label class="xl:col-span-3 col-span-12" value="{{ _lang('Name') }}" />
						<x-text-input type="text" class="sm:col-span-9 col-span-12" name="name" value="{{ $businesstype->name }}" required />
					</div>

					<div class="grid grid-cols-12 mb-4">
						<x-input-label class="xl:col-span-3 col-span-12" value="{{ _lang('Status') }}" />
						<div class="xl:col-span-9 col-span-12">
							<select class="w-full selectize" data-selected="{{ $businesstype->status }}" name="status" required>
								<option value="1">{{ _lang('Active') }}</option>
								<option value="0">{{ _lang('Disabled') }}</option>
							</select>
						</div>
					</div>

					<div class="col-span-12 mt-4">
						<x-primary-button type="submit" class="submit-btn">{{ _lang('Save') }}</x-primary-button>
					</div>
				</form>
			</div>
		</div>
	</div>
</x-app-layout>
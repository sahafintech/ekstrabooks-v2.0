<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Account Type" page="admin" subpage="create" />
		<div class="box">
			<div class="box-header">
				<h5>{{ _lang('Update Account Type') }}</h5>
			</div>
			<div class="box-body">
				<form method="post" class="validate" autocomplete="off" action="{{ route('account_types.update', $id) }}" enctype="multipart/form-data">
					@csrf
                    @method('PUT')
					<div class="grid grid-cols-12 mb-4">
						<x-input-label class="xl:col-span-3 col-span-12" value="{{ _lang('Main') }}" />
						<x-text-input type="text" class="sm:col-span-9 col-span-12" name="main" value="{{ $account_type->main }}" required />
					</div>

					<div class="grid grid-cols-12 mb-4">
						<x-input-label class="xl:col-span-3 col-span-12" value="{{ _lang('Types') }}" />
						<x-text-input type="text" class="sm:col-span-9 col-span-12" name="type" value="{{ $account_type->type }}" required />
					</div>

					<div class="col-span-12 mt-4">
						<x-primary-button type="submit" class="submit-btn">{{ _lang('Save') }}</x-primary-button>
					</div>
				</form>
			</div>
		</div>
	</div>
</x-app-layout>
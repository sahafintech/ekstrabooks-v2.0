<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Languages" page="home" subpage="create" />

		<div class="box">
			<div class="box-header">
				<h5>{{ _lang('Create New Language') }}</h5>
			</div>
			<div class="box-body">
				<form method="post" class="validate" autocomplete="off" action="{{ route('languages.store') }}" enctype="multipart/form-data">
					@csrf
					<div class="grid grid-cols-12 mb-4">
						<x-input-label class="sm:col-span-3 col-span-12" value="{{ _lang('Language Name') }}" />
						<x-text-input type="text" class="sm:col-span-9 col-span-12" name="language_name" value="{{ old('language_name') }}" required />
					</div>

					<div class="grid grid-cols-12 mb-4">
						<x-input-label class="sm:col-span-3 col-span-12" value="{{ _lang('Country') }}" />
						<div class="sm:col-span-9 col-span-12">
							@include('backend.admin.administration.language.flag')
						</div>
					</div>

					<div class="col-span-12 mt-4">
						<x-primary-button type="submit" class="submit-btn">{{ _lang('Submit') }}</x-primary-button>
					</div>
				</form>
			</div>
		</div>
	</div>
</x-app-layout>
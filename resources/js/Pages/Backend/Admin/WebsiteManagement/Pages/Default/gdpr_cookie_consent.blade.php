<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Website" page="GDPR Cookie Consent" subpage="edit" />

		<form method="post" class="validate" autocomplete="off" action="{{ route('pages.default_pages.store', 'gdpr_cookie_consent') }}" enctype="multipart/form-data">
			<div class="box">
				<div class="box-header flex items-center justify-between">
					<h5>{{ _lang('GDPR Cookie Consent') }}</h5>
					<x-secondary-button>
						<a href="{{ route('pages.default_pages') }}"><i class="ri-arrow-left-line mr-1"></i></i>{{ _lang('Back') }}</a>
					</x-secondary-button>
				</div>
				<div class="box-body">
					{{ csrf_field() }}
					<div class="grid grid-cols-12 gap-x-6">
						<div class="md:col-span-6 col-span-12">
							<x-input-label value="{{ _lang('Cookie Consent') }}" />
							<select class="w-full" name="gdpr_cookie_consent_page[cookie_consent_status]" data-selected="{{ isset($pageData->cookie_consent_status) ? $pageData->cookie_consent_status : 0 }}" required>
								<option value="0">{{ _lang('Disabled') }}</option>
								<option value="1">{{ _lang('Active') }}</option>
							</select>
						</div>

						<div class="md:col-span-6 col-span-12">
							<x-input-label value="{{ _lang('Language') }}" />
							<select class="w-full" name="model_language" required>
								{{ load_language(get_language()) }}
							</select>
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Cookie Message') }}" />
							<textarea class="w-full" name="gdpr_cookie_consent_page[cookie_message]">{{ isset($pageData->cookie_message) ? $pageData->cookie_message : '' }}</textarea>
						</div>

						<div class="col-span-12 mt-4">
							<x-primary-button type="submit" class="submit-btn">{{ _lang('Save Changes') }}</x-primary-button>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</x-app-layout>
<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Website" page="default pages" subpage="faq" />

		<form method="post" class="validate" autocomplete="off" action="{{ route('pages.default_pages.store', 'home') }}" enctype="multipart/form-data">
			<div class="box">
				<div class="box-header flex items-center justify-between">
					<span>{{ _lang('Update FAQ Page') }}</span>
					<a href="{{ route('pages.default_pages') }}">
						<x-secondary-button>
							<i class="ri-arrow-left-line mr-1"></i>
							{{ _lang('Back') }}
						</x-secondary-button>
					</a>
				</div>
				<div class="box-body">
					{{ csrf_field() }}
					<div class="grid grid-cols-12 gap-x-5">
						<div class="md:col-span-6 col-span-12">
							<x-input-label value="{{ _lang('Title') }}" />
							<x-text-input type="text" name="faq_page[title]" value="{{ isset($pageData->title) ? $pageData->title : '' }}" required />
						</div>

						<div class="md:col-span-6 col-span-12">
							<x-input-label value="{{ _lang('Language') }}" />
							<select class="w-full" name="model_language" required>
								{{ load_language(get_language()) }}
							</select>
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('FAQ Heading') }}" />
							<x-text-input type="text" name="faq_page[faq_heading]" value="{{ isset($pageData->faq_heading) ? $pageData->faq_heading : '' }}" required />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('FAQ Sub Heading') }}" />
							<x-text-input type="text" name="faq_page[faq_sub_heading]" value="{{ isset($pageData->faq_sub_heading) ? $pageData->faq_sub_heading : '' }}" required />
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
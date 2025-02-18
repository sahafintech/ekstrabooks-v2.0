<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Website" page="default pages" subpage="about" />

		<form method="post" class="validate" autocomplete="off" action="{{ route('pages.default_pages.store', 'home') }}" enctype="multipart/form-data">
			<div class="box">
				<div class="box-header flex items-center justify-between">
					<h5>{{ _lang('Update About Page') }}</h5>

					<a href="{{ route('pages.default_pages') }}">
						<x-secondary-button>
							<i class="ri-arrow-left-line mr-1"></i>
							{{ _lang('Back') }}
						</x-secondary-button>
					</a>
				</div>
				<div class="box-body">
					{{ csrf_field() }}
					<div class="grid grid-cols-12 gap-x-2">
						<div class="md:col-span-6 col-span-12">
							<x-input-label value="{{ _lang('Title') }}" />
							<x-text-input type="text" name="about_page[title]" value="{{ isset($pageData->title) ? $pageData->title : '' }}" required />
						</div>

						<div class="md:col-span-6 col-span-12">
							<x-input-label value="{{ _lang('Language') }}" />
							<select class="w-full" name="model_language" required>
								{{ load_language(get_language()) }}
							</select>
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Section 1 Heading') }}" />
							<x-text-input type="text" name="about_page[title]" value="{{ isset($pageData->title) ? $pageData->title : '' }}" required />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Section 1 Content') }}" />
							<textarea class="w-full summernote" name="about_page[section_1_content]">{{ isset($pageData->section_1_content) ? $pageData->section_1_content : '' }}</textarea>
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('About Image') }}" />
							<x-text-input type="file" class="dropify" name="about_page_media[about_image]" data-default-file="{{ isset($pageMedia->about_image) ? asset('/uploads/media/'.$pageMedia->about_image) : '' }}" />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Section 2 Heading') }}" />
							<x-text-input type="text" name="about_page[section_2_heading]" value="{{ isset($pageData->section_2_heading) ? $pageData->section_2_heading : '' }}" required />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Section 2 Content') }}" />
							<textarea class="w-full summernote" name="about_page[section_2_content]">{{ isset($pageData->section_2_content) ? $pageData->section_2_content : '' }}</textarea>
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Section 3 Heading') }}" />
							<x-text-input type="text" name="about_page[section_3_heading]" value="{{ isset($pageData->section_3_heading) ? $pageData->section_3_heading : '' }}" required />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Section 3 Content') }}" />
							<textarea class="w-full summernote" name="about_page[section_3_content]">{{ isset($pageData->section_3_content) ? $pageData->section_3_content : '' }}</textarea>
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Team Heading') }}" />
							<x-text-input type="text" name="about_page[team_heading]" value="{{ isset($pageData->team_heading) ? $pageData->team_heading : '' }}" required />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Team Sub Heading') }}" />
							<x-text-input type="text" name="about_page[team_sub_heading]" value="{{ isset($pageData->team_sub_heading) ? $pageData->team_sub_heading : '' }}" />
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
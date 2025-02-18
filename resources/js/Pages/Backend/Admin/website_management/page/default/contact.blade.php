<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Website" page="default pages" subpage="contact" />

		<form method="post" class="validate" autocomplete="off" action="{{ route('pages.default_pages.store', 'home') }}" enctype="multipart/form-data">
			<div class="box">
				<div class="box-header flex items-center justify-between">
					<h5>{{ _lang('Update Contact Page') }}</h5>
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
							<x-text-input type="text" name="contact_page[title]" value="{{ isset($pageData->title) ? $pageData->title : '' }}" required />
						</div>

						<div class="md:col-span-6 col-span-12">
							<x-input-label value="{{ _lang('Language') }}" />
							<select class="w-full" name="model_language" required>
								{{ load_language(get_language()) }}
							</select>
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Contact Form Heading') }}" />
							<x-text-input type="text" name="contact_page[contact_form_heading]" value="{{ isset($pageData->contact_form_heading) ? $pageData->contact_form_heading : '' }}" required />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Contact Form Sub Heading') }}" />
							<x-text-input type="text" name="contact_page[contact_form_sub_heading]" value="{{ isset($pageData->contact_form_sub_heading) ? $pageData->contact_form_sub_heading : '' }}" required />
						</div>

						<div class="col-span-12 my-4 flex items-center justify-between">
							<h5><b>{{ _lang('Contact Informations') }}</b></h5>
							<button type="button" id="add-row" class="btn btn-outline-primary btn-xs">{{ _lang('Add Row') }}</button>
						</div>

						<div class="col-span-12 mt-3" id="contact-informations">
							@if(isset($pageData->contact_info_heading))
							@foreach($pageData->contact_info_heading as $contact_info_heading)
							<div class="grid grid-cols-12">
								<div class="col-span-12">
									<label class="flex mb-2">{{ _lang('Heading') }} <button type="button" class="remove-row bg-red-500 text-white border border-danger px-2 rounded ml-auto order-3"><i class="ri-subtract-line"></i></button></label>
									<x-text-input type="text" name="contact_page[contact_info_heading][]" value="{{ $contact_info_heading }}" required />
								</div>
							</div>

							<div class="col-span-12 mt-3">
								<x-input-label value="{{ _lang('Content') }}" />
								<textarea class="w-full" name="contact_page[contact_info_content][]" required>{{ isset($pageData->contact_info_content[$loop->index]) ? $pageData->contact_info_content[$loop->index] : '' }}</textarea>
							</div>
						</div>
						@endforeach
						@endif
					</div>
					<div class="box">
						<div class="box-body">
							<div class="grid grid-cols-12 gap-x-5">
								<div class="col-span-12 my-4">
									<h5><b>{{ _lang('Social Links') }}</b></h5>
								</div>

								<div class="md:col-span-6 col-span-12">
									<x-input-label value="{{ _lang('Facebook Link') }}" />
									<x-text-input type="text" name="contact_page[facebook_link]" value="{{ isset($pageData->facebook_link) ? $pageData->facebook_link : '' }}" />
								</div>

								<div class="md:col-span-6 col-span-12">
									<x-input-label value="{{ _lang('Linkedin Link') }}" />
									<x-text-input type="text" name="contact_page[linkedin_link]" value="{{ isset($pageData->linkedin_link) ? $pageData->linkedin_link : '' }}" />
								</div>

								<div class="md:col-span-6 col-span-12">
									<x-input-label value="{{ _lang('Twitter Link') }}" />
									<x-text-input type="text" name="contact_page[twitter_link]" value="{{ isset($pageData->twitter_link) ? $pageData->twitter_link : '' }}" />
								</div>

								<div class="md:col-span-6 col-span-12">
									<x-input-label value="{{ _lang('Youtube Link') }}" />
									<x-text-input type="text" name="contact_page[youtube_link]" value="{{ isset($pageData->youtube_link) ? $pageData->youtube_link : '' }}" />
								</div>

								<div class="col-span-12 mt-4">
									<x-primary-button type="submit" class="submit-btn">{{ _lang('Save Changes') }}</x-primary-button>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</form>
	</div>
</x-app-layout>

<script>
	(function($) {
		"use strict";
		$(document).on('click', '#add-row', function() {
			$('#contact-informations').append(`<div class="row grid grid-cols-12">
			<div class="col-span-12">
				<label class="flex justify-between mb-2">{{ _lang('Heading') }} <button type="button" class="bg-red-500 text-white border border-danger px-2 rounded"><i class="ri-subtract-line"></i></button></label>
				<x-text-input type="text" class="form-control" name="contact_page[contact_info_heading][]" value="" required />
			</div>

			<div class="col-span-12">
				<x-input-label value="{{ _lang('Content') }}" />
				<textarea class="w-full" name="contact_page[contact_info_content][]" required></textarea>
			</div>
		</div>`);
		});

		$(document).on('click', '.remove-row', function() {
			$(this).closest('.row').remove();
		});

	})(jQuery);
</script>
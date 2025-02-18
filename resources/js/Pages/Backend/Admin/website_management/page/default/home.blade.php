<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Website" page="default pages" subpage="home" />

		<form method="post" class="validate" autocomplete="off" action="{{ route('pages.default_pages.store', 'home') }}" enctype="multipart/form-data">
			<div class="box">
				<div class="box-header flex items-center justify-between">
					<h5>{{ _lang('Update Home Page') }}</h5>
					<a href="{{ route('pages.default_pages') }}">
						<x-secondary-button>
							<i class="ri-arrow-left-line mr-1"></i>
							{{ _lang('Back') }}
						</x-secondary-button>
					</a>
				</div>
				<div class="box-body">
					@csrf
					<div class="grid grid-cols-12 gap-x-5">
						<div class="md:col-span-6 col-span-12">
							<x-input-label value="{{ _lang('Title') }}" />
							<x-text-input type="text" name="home_page[title]" value="{{ isset($pageData->title) ? $pageData->title : '' }}" required />
						</div>

						<div class="md:col-span-6 col-span-12">
							<x-input-label value="{{ _lang('Language') }}" />
							<select class="w-full" name="model_language" required>
								{{ load_language(get_language()) }}
							</select>
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Hero Heading') }}" />
							<x-text-input type="text" name="home_page[hero_heading]" value="{{ isset($pageData->hero_heading) ? $pageData->hero_heading : '' }}" required />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Hero Sub Heading') }}" />
							<x-text-input type="text" name="home_page[hero_sub_heading]" value="{{ isset($pageData->hero_sub_heading) ? $pageData->hero_sub_heading : '' }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Get Started Text') }}" />
							<x-text-input type="text" name="home_page[get_started_text]" value="{{ isset($pageData->get_started_text) ? $pageData->get_started_text : '' }}" required />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Get Started Link') }}" />
							<x-text-input type="text" name="home_page[get_started_link]" value="{{ isset($pageData->get_started_link) ? $pageData->get_started_link : '' }}" required />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Hero Image') }}" />
							<input type="file" class="dropify" name="home_page_media[hero_bg_image]" data-default-file="{{ isset($pageMedia->hero_bg_image) ? asset('/uploads/media/'.$pageMedia->hero_bg_image) : '' }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Features Section') }}" />
							<select class="w-full" data-selected="{{ isset($pageData->features_status) ? $pageData->features_status : '' }}" name="home_page[features_status]" required>
								<option value="1">{{ _lang('Active') }}</option>
								<option value="0">{{ _lang('Disabled') }}</option>
							</select>
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Features Heading') }}" />
							<x-text-input type="text" name="home_page[features_heading]" value="{{ isset($pageData->features_heading) ? $pageData->features_heading : '' }}" required />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Features Sub Heading') }}" />
							<x-text-input type="text" name="home_page[features_sub_heading]" value="{{ isset($pageData->features_sub_heading) ? $pageData->features_sub_heading : '' }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Pricing Section') }}" />
							<select class="w-full" data-selected="{{ isset($pageData->pricing_status) ? $pageData->pricing_status : '' }}" name="home_page[pricing_status]" required>
								<option value="1">{{ _lang('Active') }}</option>
								<option value="0">{{ _lang('Disabled') }}</option>
							</select>
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Pricing Heading') }}" />
							<x-text-input type="text" name="home_page[pricing_heading]" value="{{ isset($pageData->pricing_heading) ? $pageData->pricing_heading : '' }}" required />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Pricing Sub Heading') }}" />
							<x-text-input type="text" name="home_page[pricing_sub_heading]" value="{{ isset($pageData->pricing_sub_heading) ? $pageData->pricing_sub_heading : '' }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Blog Section') }}" />
							<select class="w-full" data-selected="{{ isset($pageData->blog_status) ? $pageData->blog_status : '' }}" name="home_page[blog_status]" required>
								<option value="1">{{ _lang('Active') }}</option>
								<option value="0">{{ _lang('Disabled') }}</option>
							</select>
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Blog Heading') }}" />
							<x-text-input type="text" name="home_page[blog_heading]" value="{{ isset($pageData->blog_heading) ? $pageData->blog_heading : '' }}" required />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Blog Sub Heading') }}" />
							<x-text-input type="text" name="home_page[blog_sub_heading]" value="{{ isset($pageData->blog_sub_heading) ? $pageData->blog_sub_heading : '' }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Testimonials Section') }}" />
							<select class="w-full" data-selected="{{ isset($pageData->testimonials_status) ? $pageData->testimonials_status : '' }}" name="home_page[testimonials_status]" required>
								<option value="1">{{ _lang('Active') }}</option>
								<option value="0">{{ _lang('Disabled') }}</option>
							</select>
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Testimonials Heading') }}" />
							<x-text-input type="text" name="home_page[testimonials_heading]" value="{{ isset($pageData->testimonials_heading) ? $pageData->testimonials_heading : '' }}" required />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Testimonials Sub Heading') }}" />
							<x-text-input type="text" name="home_page[testimonials_sub_heading]" value="{{ isset($pageData->testimonials_sub_heading) ? $pageData->testimonials_sub_heading : '' }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Newsletter Section') }}" />
							<select class="w-full" data-selected="{{ isset($pageData->newsletter_status) ? $pageData->newsletter_status : '' }}" name="home_page[newsletter_status]" required>
								<option value="1">{{ _lang('Active') }}</option>
								<option value="0">{{ _lang('Disabled') }}</option>
							</select>
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Newsletter Heading') }}" />
							<x-text-input type="text" name="home_page[newsletter_heading]" value="{{ isset($pageData->newsletter_heading) ? $pageData->newsletter_heading : '' }}" required />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Newsletter Sub Heading') }}" />
							<x-text-input type="text" name="home_page[newsletter_sub_heading]" value="{{ isset($pageData->newsletter_sub_heading) ? $pageData->newsletter_sub_heading : '' }}" />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Newsletter Image') }}" />
							<input type="file" class="dropify" name="home_page_media[newsletter_image]" data-default-file="{{ isset($pageMedia->newsletter_image) ? asset('/uploads/media/'.$pageMedia->newsletter_image) : '' }}" />
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
<!-- End::main-content -->
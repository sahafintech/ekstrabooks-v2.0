<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Website" page="default pages" subpage="header & footer" />

		<form method="post" class="validate" autocomplete="off" action="{{ route('pages.default_pages.store', 'header_footer') }}" enctype="multipart/form-data">
			<div class="box">
				<div class="box-header text-center">
					<h5>{{ _lang('Header & Footer Settings') }}</h5>
				</div>
				<div class="box-body">
					{{ csrf_field() }}
					<div class="grid grid-cols-12 gap-x-5">
						<div class="md:col-span-6 col-span-12">
							<x-input-label value="{{ _lang('Top Header Color') }}" />
							<x-text-input type="text" name="header_footer_page[top_header_color]" value="{{ isset($pageData->top_header_color) ? $pageData->top_header_color : '#5034fc' }}" placeholder="#5034fc" required />
						</div>

						<div class="md:col-span-6 col-span-12">
							<x-input-label value="{{ _lang('Footer Color') }}" />
							<x-text-input type="text" name="header_footer_page[footer_color]" value="{{ isset($pageData->footer_color) ? $pageData->footer_color : '#061E5C' }}" placeholder="#061E5C" required />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Widget 1 Heading') }}" />
							<x-text-input type="text" name="header_footer_page[widget_1_heading]" value="{{ isset($pageData->widget_1_heading) ? $pageData->widget_1_heading : '' }}" required />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Widget 1 Content') }}" />
							<textarea class="w-full" name="header_footer_page[widget_1_content]">{{ isset($pageData->widget_1_content) ? $pageData->widget_1_content : '' }}</textarea>
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Widget 2 Heading') }}" />
							<x-text-input type="text" name="header_footer_page[widget_2_heading]" value="{{ isset($pageData->widget_2_heading) ? $pageData->widget_2_heading : '' }}" required />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Widget 2 Content') }}" />
							<select class="w-full selectize auto-select" multiple data-placeholder="{{ _lang('Select Pages') }}" name="header_footer_page[widget_2_menus][]" data-selected="{{ isset($pageData->widget_2_menus) ? json_encode($pageData->widget_2_menus) : '' }}" multiple>
								<option value="home">{{ _lang('Home') }}</option>
								<option value="about">{{ _lang('About') }}</option>
								<option value="features">{{ _lang('Features') }}</option>
								<option value="pricing">{{ _lang('Pricing') }}</option>
								<option value="blogs">{{ _lang('Blogs') }}</option>
								<option value="faq">{{ _lang('FAQ') }}</option>
								<option value="contact">{{ _lang('Contact') }}</option>
								@foreach(\App\Models\Page::active()->get() as $page)
								<option value="{{ $page->slug }}">{{ $page->translation->title }} ({{ _lang('Custom') }})</option>
								@endforeach
							</select>
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Widget 3 Heading') }}" />
							<x-text-input type="text" name="header_footer_page[widget_3_heading]" value="{{ isset($pageData->widget_3_heading) ? $pageData->widget_3_heading : '' }}" required />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Widget 3 Menus') }}" />
							<select class="w-full selectize auto-select" multiple data-placeholder="{{ _lang('Select Pages') }}" name="header_footer_page[widget_3_menus][]" data-selected="{{ isset($pageData->widget_3_menus) ? json_encode($pageData->widget_3_menus) : '' }}" multiple>
								<option value="home">{{ _lang('Home') }}</option>
								<option value="about">{{ _lang('About') }}</option>
								<option value="features">{{ _lang('Features') }}</option>
								<option value="pricing">{{ _lang('Pricing') }}</option>
								<option value="blogs">{{ _lang('Blogs') }}</option>
								<option value="faq">{{ _lang('FAQ') }}</option>
								<option value="contact">{{ _lang('Contact') }}</option>
								@foreach(\App\Models\Page::active()->get() as $page)
								<option value="{{ $page->slug }}">{{ $page->translation->title }} ({{ _lang('Custom') }})</option>
								@endforeach
							</select>
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Language') }}" />
							<select class="w-full auto-select" name="model_language" required>
								{{ load_language(get_language()) }}
							</select>
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Copyright Text') }}" />
							<x-text-input type="text" class="form-control" name="header_footer_page[copyright_text]" value="{{ isset($pageData->copyright_text) ? $pageData->copyright_text : '' }}" required />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Payment Gateway Image') }}" />
							<input type="file" class="form-control dropify" name="payment_gateway_image" data-allowed-file-extensions="png jpg jpeg PNG JPG JPEG" data-default-file="{{ isset($pageData->payment_gateway_image) ? asset('storage/payment_gateway/' . $pageData->payment_gateway_image) : '' }}" />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Custom CSS') }}" />
							<textarea class="w-full" rows="8" name="header_footer_page[custom_css]">{{ isset($pageData->custom_css) ? $pageData->custom_css : '' }}</textarea>
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Custom JS') }}" />
							<textarea class="w-full" rows="8" name="header_footer_page[custom_js]" placeholder="Write Code Without <script> tag">{{ isset($pageData->custom_js) ? $pageData->custom_js : '' }}</textarea>
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
<div id="edit-testimonial-modal" class="hs-overlay hidden ti-modal">
	<div class="hs-overlay-open:mt-7 ti-modal-box mt-0 ease-out md:!max-w-2xl md:w-full m-3 md:mx-auto">
		<div class="ti-modal-content">
			<div class="ti-modal-header">
				<h3 class="ti-modal-title">
					Update Testimonial
				</h3>
				<button type="button" class="hs-dropdown-toggle ti-modal-close-btn" data-hs-overlay="#edit-testimonial-modal">
					<span class="sr-only">Close</span>
					<i class="ri-close-line text-xl"></i>
				</button>
			</div>
			<form method="post" autocomplete="off" action="{{ route('testimonials.update', 3) }}" enctype="multipart/form-data">
				{{ csrf_field() }}
				<input name="_method" type="hidden" value="PATCH">
				<div class="ti-modal-body">
					<p class="mt-1 text-gray-800">
					<div class="grid grid-cols-12 gap-x-12 px-2">
						<div class="col-span-12">
							<x-input-label value="{{ _lang('Customer Name') }}" />
							<x-text-input type="text" name="trans[name]" value="{{ $testimonial->translation->name }}" required />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Testimonial') }}" />
							<textarea class="w-full" name="trans[testimonial]" required>{{ $testimonial->translation->testimonial }}</textarea>
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Image') }}" />
							<input type="file" class="fdropify" name="image" data-default-file="{{ $testimonial->image != '' ? asset('public/uploads/media/'.$testimonial->image) : '' }}" />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Language') }}" />
							<select class="w-full" name="model_language" required>
								{{ load_language(get_language()) }}
							</select>
						</div>
					</div>
				</div>
				<div class="ti-modal-footer">
					<x-secondary-button type="button" data-hs-overlay="#edit-testimonial-modal">
						Close
					</x-secondary-button>
					<x-primary-button type="submit" class="submit-btn">
						Save changes
					</x-primary-button>
				</div>
			</form>
		</div>
	</div>
</div>
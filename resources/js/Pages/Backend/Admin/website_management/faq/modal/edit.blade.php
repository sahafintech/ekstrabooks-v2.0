<div id="edit-faq-modal" class="hs-overlay hidden ti-modal">
	<div class="hs-overlay-open:mt-7 ti-modal-box mt-0 ease-out md:!max-w-2xl md:w-full m-3 md:mx-auto">
		<div class="ti-modal-content">
			<div class="ti-modal-header">
				<h3 class="ti-modal-title">
					Update FAQ
				</h3>
				<button type="button" class="hs-dropdown-toggle ti-modal-close-btn" data-hs-overlay="#edit-faq-modal">
					<span class="sr-only">Close</span>
					<i class="ri-close-line text-xl"></i>
				</button>
			</div>
			<form method="post" autocomplete="off" action="{{ route('faqs.update', <?php echo '<script> document.write(editedId) </script>' ?>) }}" enctype="multipart/form-data">
				<div class="ti-modal-body">
					<p class="mt-1 text-gray-800 dark:text-white/70">
						{{ csrf_field() }}
					<div class="grid grid-cols-12 gap-x-5">
						<div class="col-span-12">
							<x-input-label value="{{ _lang('Question') }}" />
							<x-text-input type="text" class="form-control" name="trans[question]" value="{{ $faq->translation->question }}" required />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Answer') }}" />
							<textarea class="w-full" rows="6" name="trans[answer]" required>{{ $faq->translation->answer }}</textarea>
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Status') }}" />
							<select class="w-full" data-selected="{{ $faq->status }}" name="status" required>
								<option value="1">{{ _lang('Active') }}</option>
								<option value="0">{{ _lang('Deactivate') }}</option>
							</select>
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Language') }}" />
							<select class="w-full" name="model_language" required>
								{{ load_language(get_language()) }}
							</select>
						</div>
					</div>
				</div>
				<div class="ti-modal-footer">
					<x-secondary-button type="button" data-hs-overlay="#edit-faq-modal">
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
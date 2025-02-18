<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Website" page="page" subpage="create" />

		<form method="post" class="validate" autocomplete="off" action="{{ route('pages.update', $id) }}" enctype="multipart/form-data">

			<div class="box">
				<div class="box-header">
					<h5>{{ _lang('Update Page') }}</h5>
				</div>
				<div class="box-body">
					{{ csrf_field() }}
					<div class="grid grid-cols-12 gap-x-5">
						<div class="col-span-12">
							<x-input-label value="{{ _lang('Title') }}" />
							<x-text-input type="text" name="trans[title]" value="{{ $page->translation->title }}" required />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Body') }}" />
							<textarea class="w-full summernote" name="trans[body]">{{ $page->translation->body }}</textarea>
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Status') }}" />
							<select class="w-full" data-selected="{{ $page->status }}" name="status" required>
								<option value="1">{{ _lang('Publish') }}</option>
								<option value="0">{{ _lang('Draft') }}</option>
							</select>
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Language') }}" />
							<select class="w-full" name="model_language" required>
								{{ load_language(get_language()) }}
							</select>
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
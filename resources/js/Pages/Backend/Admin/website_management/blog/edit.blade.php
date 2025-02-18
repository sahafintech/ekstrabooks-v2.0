<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Website" page="blog posts" subpage="update" />

		<div class="box">
			<div class="box-header">
				<h5>{{ _lang('Update Post') }}</h5>
			</div>
			<div class="box-body">
				<form method="post" class="validate" autocomplete="off" action="{{ route('posts.update', $id) }}" enctype="multipart/form-data">
					{{ csrf_field() }}
					<div class="grid grid-cols-12 gap-x-5">
						<div class="col-span-12">
							<x-input-label value="{{ _lang('Title') }}" />
							<x-text-input type="text" name="trans[title]" value="{{ $post->translation->title }}" required />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Short Description') }}" />
							<textarea class="w-full" name="trans[short_description]" required>{{ $post->translation->short_description }}</textarea>
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Content') }}" />
							<textarea class="w-full summernote" name="trans[content]">{{ $post->translation->content }}</textarea>
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Image') }}" />
							<x-text-input type="file" class="dropify" name="image" data-default-file="{{ $post->image != '' ? asset('/uploads/media/'.$post->image) : '' }}" />
						</div>

						<div class="md:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Status') }}" />
							<select class="w-full" data-selected="{{ $post->status }}" name="status" required>
								<option value="1">{{ _lang('Published') }}</option>
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
				</form>
			</div>
		</div>
	</div>
</x-app-layout>
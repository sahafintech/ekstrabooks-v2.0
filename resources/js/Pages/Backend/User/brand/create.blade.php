<form method="post" class="ajax-screen-submit" autocomplete="off" action="{{ route('brands.store') }}" enctype="multipart/form-data">
	{{ csrf_field() }}
	<div class="ti-modal-header">
		<h3 class="ti-modal-title">
			Create New Brand
		</h3>
		<button type="button" class="hs-dropdown-toggle ti-modal-close-btn" data-hs-overlay="#category-modal">
			<span class="sr-only">Close</span>
			<i class="ri-close-line text-xl"></i>
		</button>
	</div>
	<div class="ti-modal-body grid grid-cols-12 gap-x-5">
		<div class="col-span-12">
			<x-input-label>
				{{ _lang('Name') }}
				<span class='text-red-600'>*</span>
			</x-input-label>
			<x-text-input type="text" name="name" value="{{ old('name') }}" required />
		</div>
	</div>
	<div class="ti-modal-footer">
		<x-secondary-button type="button" data-hs-overlay="#category-modal">
			Close
		</x-secondary-button>
		<x-primary-button type="submit" class="submit-btn">
			Save Category
		</x-primary-button>
	</div>
</form>
@php
$title = _lang('Create Unit');
@endphp

<form method="post" class="ajax-screen-submit" autocomplete="off" action="{{ route('product_units.store') }}" enctype="multipart/form-data">
	{{ csrf_field() }}
	<div class="ti-modal-header">
		<h3 class="ti-modal-title">
			Create Unit
		</h3>
		<button type="button" class="hs-dropdown-toggle ti-modal-close-btn" data-hs-overlay="#unit-modal">
			<span class="sr-only">Close</span>
			<i class="ri-close-line text-xl"></i>
		</button>
	</div>
	<div class="ti-modal-body">
		<div class="gid grid-cols-12 px-3">
			<x-input-label value="{{ _lang('Unit Name') }}" />
			<div class="md:col-span-9 col-span-12">
				<x-text-input type="text" name="unit" value="{{ old('unit') }}" required />
			</div>
		</div>
	</div>
	<div class="ti-modal-footer">
		<x-secondary-button type="button" data-hs-overlay="#unit-modal">
			Close
		</x-secondary-button>
		<x-primary-button type="submit" class="submit-btn">
			{{ _lang('Create Unit') }}
		</x-primary-button>
	</div>
</form>
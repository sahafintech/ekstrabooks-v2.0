<form method="post" class="ajax-screen-submit" autocomplete="off" action="{{ route('product_units.update', $id) }}" enctype="multipart/form-data">
	{{ csrf_field()}}
	<input name="_method" type="hidden" value="PATCH">
	<div class="ti-modal-header">
		<h3 class="ti-modal-title">
			Update Unit
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
				<x-text-input type="text" name="unit" value="{{ $productunit->unit }}" required />
			</div>
		</div>
	</div>
	<div class="ti-modal-footer">
		<x-secondary-button type="button" data-hs-overlay="#unit-modal">
			Close
		</x-secondary-button>
		<x-primary-button type="submit" class="submit-btn">
			{{ _lang('Update Unit') }}
		</x-primary-button>
	</div>
</form>
<form method="post" class="ajax-screen-submit" autocomplete="off" action="{{ route('prescriptions.change_status', $id) }}" enctype="multipart/form-data">
	{{ csrf_field() }}
	<div class="ti-modal-header">
		<h3 class="ti-modal-title">
			Update Status
		</h3>
		<button type="button" class="hs-dropdown-toggle ti-modal-close-btn" data-hs-overlay="#prescription-modal">
			<span class="sr-only">Close</span>
			<i class="ri-close-line text-xl"></i>
		</button>
	</div>
	<div class="ti-modal-body grid grid-cols-12 gap-x-5">
		<div class="col-span-12">
			<x-input-label>
				{{ _lang('Status') }}
				<span class='text-red-600'>*</span>
			</x-input-label>
            <!-- select status -->
            <select name="status" class="selectize w-full" data-placeholder="Select Status" required data-selected="{{ $prescription->status }}">
                <option value="0">Not Started</option>
                <option value="1">Working Progress</option>
                <option value="2">Completed</option>
                <option value="3">Delivered</option>
            </select>
		</div>
	</div>
	<div class="ti-modal-footer">
		<x-secondary-button type="button" data-hs-overlay="#prescription-modal">
			Close
		</x-secondary-button>
		<x-primary-button type="submit" class="submit-btn">
			Save Status
		</x-primary-button>
	</div>
</form>
<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Prescriptions" page="user" subpage="list" />

		<div class="box">
			<div class="box-header flex items-center justify-between">
				<h5>{{ _lang('Prescriptions') }}</h5>
				<div>
					<x-primary-button>
						<a href="{{ route('prescriptions.create') }}"><i class="ri-add-line mr-1"></i>{{ _lang('Add New Prescription') }}</a>
					</x-primary-button>
				</div>
			</div>
			<div class="box-body">
				<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
					<table id="datatable" class="ti-custom-table ti-custom-table-head whitespace-nowrap">
						<thead>
							<tr>
								<th>{{ _lang('Date') }}</th>
								<th>{{ _lang('Result Date') }}</th>
								<th>{{ _lang('Name') }}</th>
								<th>{{ _lang('Age') }}</th>
								<th>{{ _lang('Phone') }}</th>
								<th>{{ _lang('Status') }}</th>
								<th>{{ _lang('Action') }}</th>
							</tr>
						</thead>
						<tbody>
							@foreach($prescriptions as $prescription)
							<tr data-id="{{ $prescription->id }}">
								<td>{{ $prescription->date }}</td>
								<td>{{ $prescription->result_date }}</td>
								<td>{{ $prescription->customer?->name }}</td>
								<td>{{ $prescription->customer?->age }}</td>
								<td>{{ $prescription->customer?->mobile }}</td>
								<td>
									@if($prescription->status == 0)
									<span class="text-danger">{{ _lang('Not Started') }}</span>
									@elseif($prescription->status == 1)
									<span class="text-warning">{{ _lang('Working Progress') }}</span>
									@elseif($prescription->status == 2)
									<span class="text-success">{{ _lang('Completed') }}</span>
									@elseif($prescription->status == 3)
									<span class="text-secondary">{{ _lang('Delivered') }}</span>
									@endif
								<td>
									<div class="hs-dropdown ti-dropdown">
										<button id="hs-dropdown-with-icons" type="button" class="hs-dropdown-toggle ti-dropdown-toggle">
											Actions
											<svg class="hs-dropdown-open:rotate-180 ti-dropdown-caret" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path d="M2 5L8.16086 10.6869C8.35239 10.8637 8.64761 10.8637 8.83914 10.6869L15 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
											</svg>
										</button>

										<div class="hs-dropdown-menu ti-dropdown-menu divide-y divide-gray-200" aria-labelledby="hs-dropdown-with-icons">
											<div class="ti-dropdown-divider">
												<a class="ti-dropdown-item" href="{{ route('prescriptions.edit', $prescription['id']) }}">
													<i class="ri-edit-box-line text-lg"></i>
													Edit
												</a>
												<a class="ti-dropdown-item" href="{{ route('prescriptions.show', $prescription['id']) }}">
													<i class="ri-eye-line text-lg"></i>
													View
												</a>
												<!-- change status -->
												<a class="ti-dropdown-item ajax-modal" href="{{ route('prescriptions.edit_status', $prescription['id']) }}" data-hs-overlay="#prescription-modal">
													<i class="ri-settings-3-line text-lg"></i>
													Change Status
												</a>
												<a class="ti-dropdown-item" href="javascript:void(0);" data-hs-overlay="#delete-modal" data-id="{{ $prescription['id'] }}" id="delete">
													<i class="ri-delete-bin-line text-lg"></i>
													Delete
												</a>
											</div>
										</div>
									</div>
								</td>
							</tr>
							@endforeach
						</tbody>
					</table>
				</div>
			</div>
		</div>
		<x-modal>
			<form method="post">
				<div class="ti-modal-header">
					<h3 class="ti-modal-title">
						Delete Modal
					</h3>
					<button type="button" class="hs-dropdown-toggle ti-modal-close-btn" data-hs-overlay="#delete-modal">
						<span class="sr-only">Close</span>
						<i class="ri-close-line text-xl"></i>
					</button>
				</div>
				<div class="ti-modal-body">
					{{ csrf_field() }}

					<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
						{{ __('Are you sure you want to delete the prescription?') }}
					</h2>

					<input name="_method" type="hidden" value="DELETE">
				</div>
				<div class="ti-modal-footer">
					<x-secondary-button data-hs-overlay="#delete-modal">
						{{ __('Cancel') }}
					</x-secondary-button>

					<x-danger-button class="ml-3 submit-btn" type="submit">
						{{ __('Delete Prescription') }}
					</x-danger-button>
				</div>
			</form>
		</x-modal>

		<div id="prescription-modal" class="hs-overlay hidden ti-modal">
			<div class="hs-overlay-open:mt-7 ti-modal-box mt-0 ease-out lg:!max-w-4xl lg:w-full m-3 lg:!mx-auto">
				<div class="ti-modal-content">
					<div class="ti-modal-body hidden" id="modal_spinner">
						<div class="text-center spinner">
							<div class="ti-spinner text-primary" role="status" aria-label="loading"> <span class="sr-only">Loading...</span> </div>
						</div>
					</div>
					<div id="main-modal">

					</div>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>

<script>
	$(document).on('click', '#delete', function() {
		var id = $(this).data('id');
		$('#delete-modal form').attr('action', '/user/prescriptions/' + id);
	});
</script>
<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Customers" page="user" subpage="list" />

		<div class="box">
			<div class="box-header flex items-center justify-between">
				<h5>{{ _lang('Customers') }}</h5>
				<div>
					<x-primary-button>
						<a href="{{ route('customers.create') }}"><i class="ri-add-line mr-1"></i>{{ _lang('Add New') }}</a>
					</x-primary-button>
					<x-secondary-button data-hs-overlay="#import-modal">
						<i class="ri-upload-cloud-2-line ml-1"></i>
						Import Customers
					</x-secondary-button>
					<a href="{{ route('customers.export') }}">
						<x-secondary-button>
							<i class="ri-download-2-line mr-1"></i>
							Export Customers
						</x-secondary-button>
					</a>
				</div>
			</div>
			<div class="box-body">
				<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
					<table id="datatable" class="ti-custom-table ti-custom-table-head whitespace-nowrap">
						<thead>
							<tr>
								<th>{{ _lang('Name') }}</th>
								<th>{{ _lang('Company Name') }}</th>
								<th>{{ _lang('Email') }}</th>
								<th>{{ _lang('Mobile') }}</th>
								<th>{{ _lang('Action') }}</th>
							</tr>
						</thead>
						<tbody>
							@foreach($customers as $customer)
							<tr data-id="{{ $customer->id }}">
								<td>{{ $customer->name }}</td>
								<td>{{ $customer->company_name }}</td>
								<td>{{ $customer->email }}</td>
								<td>{{ $customer->mobile }}</td>
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
												<a class="ti-dropdown-item" href="{{ route('customers.edit', $customer['id']) }}">
													<i class="ri-edit-box-line text-lg"></i>
													Edit
												</a>
												<a class="ti-dropdown-item" href="{{ route('customers.show', $customer['id']) }}">
													<i class="ri-eye-line text-lg"></i>
													View
												</a>
												<a class="ti-dropdown-item" href="javascript:void(0);" data-hs-overlay="#delete-modal" data-id="{{ $customer['id'] }}" id="delete">
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
						{{ __('Are you sure you want to delete the customer?') }}
					</h2>

					<input name="_method" type="hidden" value="DELETE">
				</div>
				<div class="ti-modal-footer">
					<x-secondary-button data-hs-overlay="#delete-modal">
						{{ __('Cancel') }}
					</x-secondary-button>

					<x-danger-button class="ml-3 submit-btn" type="submit">
						{{ __('Delete Customer') }}
					</x-danger-button>
				</div>
			</form>
		</x-modal>

		<div id="import-modal" class="hs-overlay hidden ti-modal">
			<div class="hs-overlay-open:mt-7 ti-modal-box mt-0 ease-out lg:!max-w-4xl lg:w-full m-3 lg:!mx-auto">
				<div class="ti-modal-content">
					<div class="ti-modal-body hidden" id="modal_spinner">
						<div class="text-center spinner">
							<div class="ti-spinner text-primary" role="status" aria-label="loading"> <span class="sr-only">Loading...</span> </div>
						</div>
					</div>
					<div class="modal-body">
						<div class="ti-modal-header">
							<h3 class="text-lg font-bold">{{ _lang('Import customers') }}</h3>
							<button class="ti-modal-close" data-hs-overlay="#import-modal">
								<span class="sr-only">Close</span>
								<i class="ri-close-line text-xl"></i>
							</button>
						</div>
						<form action="{{ route('customers.import') }}" method="post" id="import_customers" enctype="multipart/form-data">
							@csrf
							<div class="ti-modal-body grid grid-cols-12">
								<div class="col-span-12">
									<div class="flex items-center justify-between">
										<x-input-label value="{{ _lang('Customers File') }}" />
										<a href="/uploads/media/default/sample_customers.xlsx" download>
											<x-secondary-button type="button" class="mb-3">
												Use This Sample File
											</x-secondary-button>
										</a>
									</div>
									<input type="file" class="w-full dropify" name="customers_file" class="w-full" required />
								</div>
								<div class="col-span-12 mt-4">
									<ul class="space-y-3 text-sm">
										<li class="flex space-x-3">
											<i class="ri-check-line text-primary bg-primary/20 rounded-full px-1"></i>
											<span class="text-gray-800 dark:text-white/70">
												Maximum File Size: 1 MB
											</span>
										</li>

										<li class="flex space-x-3">
											<i class="ri-check-line text-primary bg-primary/20 rounded-full px-1"></i>
											<span class="text-gray-800 dark:text-white/70">
												File format Supported: CSV, TSV, XLS
											</span>
										</li>

										<li class="flex space-x-3">
											<i class="ri-check-line text-primary bg-primary/20 rounded-full px-1"></i>
											<span class="text-gray-800 dark:text-white/70">
												Make sure the format of the import file matches our sample file by comparing them.
											</span>
										</li>
									</ul>
								</div>
							</div>
							<div class="ti-modal-footer">
								<x-secondary-button type="button" data-hs-overlay="#import-modal">
									Close
								</x-secondary-button>
								<x-primary-button type="submit" class="submit-btn">
									Import Customers
								</x-primary-button>
							</div>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>

<script>
	$(document).on('click', '#delete', function() {
		var id = $(this).data('id');
		$('#delete-modal form').attr('action', '/user/customers/' + id);
	});
</script>
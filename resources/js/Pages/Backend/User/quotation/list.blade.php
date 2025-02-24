<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Quotations" page="user" subpage="list" />

		<div class="box">
			<div class="box-header flex items-center justify-between">
				<span>{{ _lang('Quotations') }}</span>
				<x-primary-button>
					<a href="{{ route('quotations.create') }}"><i class="ri-add-line mr-1"></i>{{ _lang('New Quotation') }}</a>
				</x-primary-button>
			</div>
			<div class="box-body">
				<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
					<table id="datatable" class="ti-custom-table ti-custom-table-head whitespace-nowrap">
						<thead>
							<tr>
								<th>{{ _lang('Date') }}</th>
								<th>{{ _lang('Expired At') }}</th>
								<th>{{ _lang('Quotation Number') }}</th>
								<th>{{ _lang('Customer') }}</th>
								<th>{{ _lang('Status') }}</th>
								<th class="text-right">{{ _lang('Grand Total') }}</th>
								<th class="text-center">{{ _lang('Action') }}</th>
							</tr>
						</thead>
						<tbody>
							@foreach($quotations as $quotation)
							<tr data-id="row_{{ $quotation->id }}">
								<td>{{ $quotation->quotation_date }}</td>
								<td>{{ $quotation->expired_date }}</td>
								<td>{{ $quotation->quotation_number }}</td>
								<td>{{ $quotation->customer->name }}</td>
								<td>
									@if($quotation->getRawOriginal('expired_date') > date('Y-m-d'))
									<span class="text-success">{{ _lang('Active') }}</span>
									@else
									<span class="text-danger">{{ _lang('Expired') }}</span>
									@endif
								</td>
								<td>{{ $quotation->grand_total }}</td>
								<td class="text-center">
									<div class="hs-dropdown ti-dropdown">
										<button id="hs-dropdown-with-icons" type="button" class="hs-dropdown-toggle ti-dropdown-toggle">
											Actions
											<svg class="hs-dropdown-open:rotate-180 ti-dropdown-caret" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
												<path d="M2 5L8.16086 10.6869C8.35239 10.8637 8.64761 10.8637 8.83914 10.6869L15 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
											</svg>
										</button>

										<div class="hs-dropdown-menu ti-dropdown-menu divide-y divide-gray-200" aria-labelledby="hs-dropdown-with-icons">
											<div class="ti-dropdown-divider">
												<a class="ti-dropdown-item" href="{{ route('quotations.edit', $quotation['id']) }}">
													<i class="ri-edit-box-line text-lg"></i>
													Edit
												</a>
												<a class="ti-dropdown-item" href="{{ route('quotations.show', $quotation['id']) }}">
													<i class="ri-eye-line text-lg"></i>
													View
												</a>
												<a class="ti-dropdown-item" href="javascript:void(0);" data-id="{{ $quotation['id'] }}" id="delete">
													<i class="ri-delete-bin-line text-lg"></i>
													Delete
													</form>
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
				{{ csrf_field() }}
				<input name="_method" type="hidden" value="DELETE">
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

					<h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
						{{ __('Are you sure you want to delete the quotation?') }}
					</h2>

				</div>
				<div class="ti-modal-footer">
					<x-secondary-button data-hs-overlay="#delete-modal">
						{{ __('Cancel') }}
					</x-secondary-button>

					<x-danger-button class="ml-3 submit-btn" type="submit">
						{{ __('Delete Credit Invoice') }}
					</x-danger-button>
				</div>
			</form>
		</x-modal>
	</div>
</x-app-layout>

<script>
	$(document).on('click', '#delete', function() {
		var id = $(this).data('id');
		$('#delete-modal form').attr('action', '/user/quotations/' + id);
	});
</script>
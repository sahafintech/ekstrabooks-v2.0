<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Languages" page="home" subpage="list" />

		<div class="box">
			<div class="box-header flex items-center justify-between">
				<h5>{{ _lang('Languages') }}</h5>
				<x-primary-button>
					<a href="{{ route('languages.create') }}"><i class="ri-add-line mr-1"></i>{{ _lang('Add New') }}</a>
				</x-primary-button>
			</div>

			<div class="box-body">
				<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
					<table id="datatable" class="ti-custom-table ti-custom-table-head whitespace-nowrap">
						<thead>
							<tr>
								<th class="pl-3">{{ _lang('Flag') }}</th>
								<th>{{ _lang('Language Name') }}</th>
								<th class="text-center">{{ _lang('Action') }}</th>
							</tr>
						</thead>
						<tbody>
							@foreach(get_language_list() as $language)
							<tr>
								<td class="pl-3"><img class="avatar avatar-xss avatar-circle me-2" src="{{ asset('/backend/plugins/flag-icon-css/flags/1x1/'.explode('---', $language)[1].'.svg') }}" /></td>
								<td>{{ explode('---', $language)[0] }}</td>
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
												<a class="ti-dropdown-item" href="route('languages.edit', $language) }}">
													<i class="ri-edit-box-line text-lg"></i>
													Edit
												</a>
												<a class="ti-dropdown-item" href="javascript:void(0);">
													<i class="ri-delete-bin-line text-lg"></i>
													<form action="{{ route('languages.destroy', $language) }}" method="post">
														{{ csrf_field() }}
														<input name="_method" type="hidden" value="DELETE">
														<button type="submit">Delete</button>
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
	</div>
</x-app-layout>
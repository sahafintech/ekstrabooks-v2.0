<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Database Backup" page="admin" subpage="list" />

		<div class="box">
			<div class="box-header flex items-center justify-between">
				<h5>{{ _lang('Database Backups') }}</h5>
				<x-primary-button>
					<a href="{{ route('database_backups.create') }}"><i class="ri-add-line mr-1"></i>{{ _lang('Create New Backup') }}</a>
				</x-primary-button>
			</div>
			<div class="box-body">
				<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
					<table id="datatable" class="ti-custom-table ti-custom-table-head whitespace-nowrap">
						<thead>
							<tr>
								<th>{{ _lang('Created At') }}</th>
								<th>{{ _lang('File') }}</th>
								<th>{{ _lang('Created By') }}</th>
								<th class="text-center">{{ _lang('Action') }}</th>
							</tr>
						</thead>
						<tbody>
							@foreach($databasebackups as $databasebackup)
							<tr data-id="row_{{ $databasebackup->id }}">
								<td class='created_at'>{{ $databasebackup->created_at }}</td>
								<td class='file'>{{ $databasebackup->file }}</td>
								<td class='user_id'>{{ $databasebackup->created_by->name }}</td>
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
												<a class="ti-dropdown-item" href="{{ route('database_backups.download', $databasebackup['id']) }}">
													<i class="ri-edit-box-line text-lg"></i>
													Download
												</a>
												<a class="ti-dropdown-item" href="javascript:void(0);">
													<i class="ri-delete-bin-line text-lg"></i>
													<form action="{{ route('database_backups.destroy', $databasebackup['id']) }}" method="post">
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
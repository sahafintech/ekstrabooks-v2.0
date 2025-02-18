<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Notofication Template" page="admin" subpage="list" />

		<div class="box">
			<div class="box-header">
				<h5>{{ _lang('Notification Templates') }}</h5>
			</div>
			<div class="box-body">
				<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
					<table id="datatable" class="ti-custom-table ti-custom-table-head whitespace-nowrap">
						<thead>
							<tr>
								<th>{{ _lang('Name') }}</th>
								<th>{{ _lang('Allowed Channels') }}</th>
								<th class="text-center">{{ _lang('Action') }}</th>
							</tr>
						</thead>
						<tbody>
							@foreach($emailtemplates as $emailtemplate)
							<tr id="row_{{ $emailtemplate->id }}">
								<td class='name'>{{ ucwords(str_replace('_',' ',$emailtemplate->name)) }}</td>
								<td class='status'>
									@if($emailtemplate->email_status == 1)
									<span class="badge bg-success rounded-md text-white">{{ _lang('Email') }}</span>
									@endif

									@if($emailtemplate->sms_status == 1)
									<span class="badge bg-success rounded-md text-white">{{ _lang('SMS') }}</span>
									@endif

									@if($emailtemplate->notification_status == 1)
									<span class="badge bg-success rounded-md text-white">{{ _lang('App') }}</span>
									@endif

									@if($emailtemplate->email_status == 0 && $emailtemplate->sms_status == 0 && $emailtemplate->notification_status == 0)
									<span class="badge bg-secondary rounded-md text-white">{{ _lang('N/A') }}</span>
									@endif
								</td>
								<td>
									<a href="{{ route('notification_templates.edit', $emailtemplate->id) }}">
										<x-secondary-button>
											<i class="ri-edit-box-line mr-1"></i>
											{{ _lang('Edit') }}
										</x-secondary-button>
									</a>
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
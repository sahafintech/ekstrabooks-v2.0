<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Currency" page="admin" subpage="list" />

		<div class="col-lg-12">
			<div class="bg-yellow-100 border border-gray-200 alert">
				<div class="flex">
					<div class="flex-shrink-0">
						<i class="ri-question-line"></i>
					</div>
					<p class="text-sm text-gray-700">
						{{ _lang('Base Currency exchange rate always 1.00') }}
					</p>
				</div>
			</div>
			<div class="box">
				<div class="box-header flex items-center justify-between">
					<h5>{{ _lang('Currency List') }}</h5>
					<x-primary-button>
						<a href="{{ route('currency.create') }}"><i class="ri-add-line mr-1"></i>{{ _lang('Add New') }}</a>
					</x-primary-button>
				</div>
				<div class="box-body">
					<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
						<table id="datatable" class="ti-custom-table ti-custom-table-head whitespace-nowrap">
							<thead>
								<tr>
									<th>{{ _lang('Name') }}</th>
									<th>{{ _lang('Exchange Rate') }}</th>
									<th>{{ _lang('Base Currency') }}</th>
									<th>{{ _lang('Status') }}</th>
									<th class="text-center">{{ _lang('Action') }}</th>
								</tr>
							</thead>
							<tbody>
								@foreach($currencys as $currency)
								<tr data-id="row_{{ $currency->id }}">
									<td class='name'>{{ $currency->name }}</td>
									<td class='exchange_rate'>{{ $currency->exchange_rate }}</td>
									<td class='base_currency'>
										@if($currency->base_currency == 1)
										<span class="badge bg-success rounded-md text-white">{{ _lang('Yes') }}</span>
										@else
										<span class="badge bg-danger rounded-md text-white">{{ _lang('No') }}</span>
										@endif
									</td>
									<td class='status'>
										@if($currency->status == 1)
										<span class="badge bg-success rounded-md text-white">{{ _lang('Active') }}</span>
										@else
										<span class="badge bg-danger rounded-md text-white">{{ _lang('Disabled') }}</span>
										@endif
									</td>

									<td>
										@if($currency->base_currency == 0 )
										<div class="hs-dropdown ti-dropdown">
											<button id="hs-dropdown-with-icons" type="button" class="hs-dropdown-toggle ti-dropdown-toggle">
												Actions
												<svg class="hs-dropdown-open:rotate-180 ti-dropdown-caret" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
													<path d="M2 5L8.16086 10.6869C8.35239 10.8637 8.64761 10.8637 8.83914 10.6869L15 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
												</svg>
											</button>
											<div class="hs-dropdown-menu ti-dropdown-menu divide-y divide-gray-200" aria-labelledby="hs-dropdown-with-icons">
												<div class="ti-dropdown-divider">
													<a class="ti-dropdown-item" href="{{ route('currency.edit', $currency->id) }}">
														<i class="ri-edit-box-line text-lg"></i>
														Edit
													</a>
													<a class="ti-dropdown-item" href="{{ route('currency.show', $currency->id) }}">
														<i class="ri-eye-line text-lg"></i>
														View
													</a>
													<a class="ti-dropdown-item" href="javascript:void(0);">
														<i class="ri-delete-bin-line text-lg"></i>
														<form action="{{ route('currency.destroy', $currency->id) }}" method="post">
															{{ csrf_field() }}
															<input name="_method" type="hidden" value="DELETE">
															<button type="submit">Delete</button>
														</form>
													</a>
												</div>
											</div>
										</div>
										@else
										<div class="hs-dropdown ti-dropdown">
											<button id="hs-dropdown-with-icons" type="button" class="hs-dropdown-toggle ti-dropdown-toggle">
												Actions
												<svg class="hs-dropdown-open:rotate-180 ti-dropdown-caret" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
													<path d="M2 5L8.16086 10.6869C8.35239 10.8637 8.64761 10.8637 8.83914 10.6869L15 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
												</svg>
											</button>
											<div class="hs-dropdown-menu ti-dropdown-menu divide-y divide-gray-200" aria-labelledby="hs-dropdown-with-icons">
												<div class="ti-dropdown-divider">
													<a class="ti-dropdown-item" href="{{ route('currency.edit', $currency->id) }}">
														<i class="ri-edit-box-line text-lg"></i>
														Edit
													</a>
													<a class="ti-dropdown-item" href="{{ route('currency.show', $currency->id) }}">
														<i class="ri-eye-line text-lg"></i>
														View
													</a>
												</div>
											</div>
										</div>
										@endif
									</td>
								</tr>
								@endforeach
							</tbody>
						</table>
					</div>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>
<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Payments" page="payment gateways" subpage="list" />

		<div class="box">
			<div class="box-header">
				<h5>{{ _lang('Payment Gateways') }}</h5>
			</div>
			<div class="box-body">
				<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
					<table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
						<thead>
							<th class="pl-3">{{ _lang('Name') }}</th>
							<th class="text-center">{{ _lang('Status') }}</th>
							<th class="text-center">{{ _lang('Action') }}</th>
						</thead>
						<tbody>
							@foreach($paymentgateways as $paymentgateway)
							<tr>
								<td class="pl-3">
									<div class="flex space-x-3 w-full">
										<img class="avatar avatar-sm rounded-sm" src="{{ asset('/backend/images/gateways/'.$paymentgateway->image) }}" alt="Image Description" />
										<div class="flex w-full">
											<div class="block my-auto">
												<p class="block text-sm font-semibold text-gray-800 hover:text-gray-900 my-auto dark:text-white dark:hover:text-gray-200">
												{{ $paymentgateway->name }}
												</p>
											</div>
										</div>
									</div>
								</td>
								<td>
									@if($paymentgateway->status == 1)
									<span class="text-success">{{ _lang('Active') }}</span>
									@else
									<span class="text-danger">{{ _lang('Inactive') }}</span>
									@endif
								</td>
								<td>
									<x-secondary-button>
										<a href="{{ route('payment_gateways.edit', $paymentgateway->id) }}" class="flex items-center space-x-2" >
											
											<div>{{ _lang('Config') }}</div>
										</a>
									</x-secondary-button>
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
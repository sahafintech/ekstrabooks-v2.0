<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Payments" page="payment gateway" subpage="edit" />

		<div class="box">
			<div class="box-header">
				<h5>{{ _lang('Update Payment Gateway') }}</h5>
			</div>
			<div class="box-body">
				<form method="post" class="validate" autocomplete="off" action="{{ route('payment_gateways.update', $id) }}" enctype="multipart/form-data">
					{{ csrf_field()}}
					<input name="_method" type="hidden" value="PATCH">

					<div class="grid grid-cols-12">
						<x-input-label class="xl:col-span-3 col-span-12" value="{{ _lang('Name') }}" />
						<x-text-input type="text" class="xl:col-span-9 col-span-12" value="{{ $paymentgateway->name }}" readonly />
					</div>

					<div class="grid grid-cols-12 mt-3">
						<x-input-label class="xl:col-span-3 col-span-12" value="{{ _lang('Image') }}" />
						<select class="xl:col-span-9 col-span-12" data-selected="{{ $paymentgateway->status }}" name="status" id="gateway_status" required>
							<option value="0">{{ _lang('Disable') }}</option>
							<option value="1">{{ _lang('Enable') }}</option>
						</select>
					</div>

					<div class="grid grid-cols-12">
						<div class="md:col-span-12 col-span-12">
							@foreach($paymentgateway->parameters as $key => $value)
							@if($key != 'environment')
							<div class="grid grid-cols-12 mt-3">
								<x-input-label class="xl:col-span-3 col-span-12" value="{{ strtoupper(str_replace('_',' ',$key)) }}" />
								<x-text-input type="text" class="xl:col-span-9 col-span-12" value="{{ $value }}" name="parameter_value[{{$key}}]" />
							</div>
							@else
							<div class="grid grid-cols-12 mt-3">
								<x-input-label class="xl:col-span-3 col-span-12" value="{{ strtoupper(str_replace('_',' ',$key)) }}" />
								<select class="xl:col-span-9 col-span-12" data-selected="{{ $value }}" name="parameter_value[{{$key}}]">
									<option value="sandbox">{{ _lang('Sandbox') }}</option>
									<option value="live">{{ _lang('Live') }}</option>
								</select>
							</div>
							@endif
							@endforeach

							<div class="col-span-12 mt-4">
								<div class="col-span-12 mt-4">
									<x-primary-button type="submit" class="submit-btn">{{ _lang('Save Changes') }}</x-primary-button>
								</div>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</x-app-layout>

<script>
	(function($) {
		"use strict";

		@if($paymentgateway-> is_crypto == 0)

		$('#gateway_currency').val() != '' ? $(".gateway_currency_preview").html($('#gateway_currency').val()) : '';

		$(document).on('change', '#gateway_currency', function() {
			$(".gateway_currency_preview").html($(this).val());
		});

		@endif

	})(jQuery);
</script>
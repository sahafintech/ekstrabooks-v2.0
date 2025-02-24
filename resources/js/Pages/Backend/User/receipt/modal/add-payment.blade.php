<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Receive Payment" page="user" subpage="create" />

		<form method="post" class="ajax-screen-submit" autocomplete="off" action="{{ route('invoices.receive_payment') }}" enctype="multipart/form-data">
			{{ csrf_field() }}
			<div class="grid grid-cols-12 gap-x-5">
				<div class="md:col-span-6 col-span-12 mt-3">
					<x-input-label value="{{ _lang('Customer') }}" />
					<select id="customers" class="w-full auto-select selectize" data-selected="{{ old('customer_id') }}" name="customer_id" required>
						<option value="">{{ _lang('Select Customer') }}</option>
						@foreach(\App\Models\Customer::all() as $customer)
						<option value="{{ $customer->id }}">{{ $customer->name }}</option>
						@endforeach
					</select>
				</div>

				<div class="md:col-span-6 col-span-12 mt-3">
					<x-input-label value="{{ _lang('Date') }}" />
					<x-text-input type="text" class="flatpickr" id="datetime" name="trans_date" value="{{ \Carbon\Carbon::parse(now())->format('m-d-Y h:i') }}" required />
				</div>

				<div class="md:col-span-6 col-span-12 mt-3">
					<x-input-label value="{{ _lang('Credit Account') }}" />
					<select class="w-full auto-select selectize" data-selected="{{ old('account_id') }}" name="account_id" required>
						<option value="">{{ _lang('Select Account') }}</option>
						@foreach(\App\Models\Account::where('account_type', 'Bank')
						->orWhere('account_type', 'Cash')
						->get() as $account)
						<option value="{{ $account->id }}">{{ $account->account_name }}</option>
						@endforeach
					</select>
				</div>

				<div class="md:col-span-6 col-span-12 mt-3">
					<x-input-label value="{{ _lang('Payment Method') }}" />
					<select class="w-full auto-select selectize" data-selected="{{ old('method') }}" name="method" required>
						<option value="">{{ _lang('Select Payment Method') }}</option>
						@foreach(\App\Models\TransactionMethod::all() as $method)
						<option value="{{ $method->name }}">{{ $method->name }}</option>
						@endforeach
					</select>
				</div>

				<div class="md:col-span-6 col-span-12 mt-3">
					<x-input-label value="{{ _lang('Reference') }}" />
					<x-text-input type="text" name="reference" value="{{ old('reference') }}" />
				</div>

				<div class="col-span-12 mt-3">
					<h5 class="text-md">Standing Invoices</h5>
					<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto mt-3 bg-white">
						<table id="invoices-table" class="ti-custom-table ti-custom-table-head whitespace-nowrap">
							<thead>
								<tr>
									<th></th>
									<th>{{ _lang('Description') }}</th>
									<th>{{ _lang('Due Date') }}</th>
									<th>{{ _lang('Grand Total') }}</th>
									<th>{{ _lang('Due Amount') }}</th>
									<th>{{ _lang('Amount') }}</th>
								</tr>
							</thead>
							<tbody>
							</tbody>
						</table>
					</div>
				</div>

				<div class="col-span-12 mt-3">
					<x-input-label value="{{ _lang('Attachment') }}" />
					<input type="file" class="dropify" name="attachment">
				</div>

				<div class="col-span-12 my-4">
					<x-primary-button type="submit">{{ _lang('Submit') }}</x-primary-button>
				</div>
			</div>
		</form>
	</div>
</x-app-layout>
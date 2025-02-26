<div class="box">
    <div class="box-body">
        <form method="post" class="ajax-screen-submit" autocomplete="off" action="{{ route('deffered_receive_payments.store') }}" enctype="multipart/form-data">
            {{ csrf_field() }}
            <div class="grid grid-cols-12 gap-x-2">
                <div class="md:col-span-6 col-span-12 mt-3">
                    <x-input-label value="{{ _lang('Customer') }}" />
                    <select class="w-full auto-select selectize customers" data-selected="{{ old('customer_id') }}" name="customer_id" required>
                        <option value="">{{ _lang('Select Customer') }}</option>
                        @foreach(\App\Models\Customer::all() as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-6 col-span-12 mt-3">
                    <x-input-label value="{{ _lang('Date') }}" />
                    <x-text-input type="text" class="flatpickr" id="date" name="trans_date" value="{{ \Carbon\Carbon::now()->format('d-m-Y') }}" required />
                </div>

                <div class="md:col-span-6 col-span-12 mt-3">
                    <x-input-label value="{{ _lang('Debit Account') }}" />
                    <select class="w-full auto-select selectize" data-selected="{{ old('account_id') }}" name="account_id" required>
                        <option value="">{{ _lang('Select Account') }}</option>
                        @foreach(\App\Models\Account::where(function ($query) {
                        $query->where('account_type', '=', 'Bank')
                        ->orWhere('account_type', '=', 'Cash');
                        })->where(function ($query) {
                        $query->where('business_id', '=', request()->activeBusiness->id)
                        ->orWhere('business_id', '=', null);
                        })
                        ->get() as $account)
                        <option value="{{ $account->id }}">{{ $account->account_name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="md:col-span-6 col-span-12 mt-3">
                    <x-input-label value="{{ _lang('Payment Method') }}" />
                    <select class="w-full auto-select selectize" data-selected="{{ old('method') }}" name="method">
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
                    <h5 class="text-md">Standing Payments</h5>
                    <div class="table-bordered rounded-sm ti-custom-table-head overflow-auto mt-3 bg-white">
                        <table id="invoices-table" class="ti-custom-table ti-custom-table-head whitespace-nowrap">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>{{ _lang('Description') }}</th>
                                    <th>{{ _lang('Due Date') }}</th>
                                    <th>{{ _lang('Amount') }}</th>
                                    <th>{{ _lang('Due Amount') }}</th>
                                    <th>{{ _lang('Paid Amount') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="col-span-12 my-4">
                    <x-primary-button type="submit" class="submit-btn">{{ _lang('Submit') }}</x-primary-button>
                </div>
            </div>
        </form>
    </div>
</div>
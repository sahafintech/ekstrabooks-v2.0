<x-app-layout>
    <!-- Start::main-content -->
    <div class="main-content">
        <x-page-header title="Edit Payment" page="user" subpage="edit" />

        <div class="grid grid-cols-12 gap-x-3">
            <div class="col-span-12">
                <div class="box">
                    <div class="box-body">
                        <div class="flex flex-col">
                            <a class="ti-list-group gap-x-2 ti-icon-link focus:ring-primary dark:border-white/10 {{ isset($_GET['tab']) ? '' : 'text-primary'  }}" href="{{ route('deffered_invoices.index') }}">
                                <i class="ri-article-line text-xl"></i>
                                {{ _lang('Invoice List') }}
                            </a>
                            <a class="ti-list-group gap-x-2 ti-icon-link focus:ring-primary dark:border-white/10 {{ isset($_GET['tab']) && $_GET['tab'] == 'received_payments' ? 'text-primary' : ''  }}" href="{{ route('deffered_invoices.index') }}?tab=received_payments">
                                <i class="ri-wallet-3-line text-xl"></i>
                                {{ _lang('Received Payments') }}
                            </a>
                            <a class="ti-list-group gap-x-2 ti-icon-link focus:ring-primary dark:border-white/10 {{ isset($_GET['tab']) && $_GET['tab'] == 'create_payments' ? 'text-primary' : ''  }}" href="{{ route('deffered_invoices.index') }}?tab=create_payments">
                                <i class="ri-money-dollar-box-line text-xl"></i>
                                {{ _lang('Create Payments') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-span-12">
                <div class="box">
                    <div class="box-header">
                        <h5>Edit Payment</h5>
                    </div>
                    <div class="box-body">
                        <form method="post" class="ajax-screen-submit" autocomplete="off" action="{{ route('deffered_receive_payments.update', $id) }}" enctype="multipart/form-data">
                            {{ csrf_field() }}
                            <input name="_method" type="hidden" value="PATCH">
                            <div class="grid grid-cols-12 gap-x-5">
                                <div class="md:col-span-6 col-span-12 mt-3">
                                    <x-input-label value="{{ _lang('Customer') }}" />
                                    <select id="customers" class="w-full auto-select selectize" data-selected="{{ $payment->customer_id }}" name="customer_id" required>
                                        <option value="">{{ _lang('Select Customer') }}</option>
                                        @foreach(\App\Models\Customer::all() as $customer)
                                        <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="md:col-span-6 col-span-12 mt-3">
                                    <x-input-label value="{{ _lang('Date') }}" />
                                    <x-text-input type="text" class="flatpickr" id="date" name="trans_date" value="{{ \Carbon\Carbon::createFromFormat(get_date_format(),$payment->date)->format('d-m-Y') }}" required />
                                </div>

                                <div class="md:col-span-6 col-span-12 mt-3">
                                    <x-input-label value="{{ _lang('Debit Account') }}" />
                                    <select class="w-full auto-select selectize" data-selected="{{ $payment->account_id }}" name="account_id" required>
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
                                    <select class="w-full auto-select selectize" data-selected="{{ $payment->payment_method }}" name="method">
                                        <option value="">{{ _lang('Select Payment Method') }}</option>
                                        @foreach(\App\Models\TransactionMethod::all() as $method)
                                        <option value="{{ $method->name }}">{{ $method->name }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div class="md:col-span-6 col-span-12 mt-3">
                                    <x-input-label value="{{ _lang('Reference') }}" />
                                    <x-text-input type="text" name="reference" value="{{ $payment->reference }}" />
                                </div>

                                <div class="col-span-12 mt-3">
                                    <h5 class="text-md">Standing Invoices</h5>
                                    <div class="table-bordered rounded-sm ti-custom-table-head overflow-auto mt-3 bg-white">
                                        <table id="invoices-table" class="ti-custom-table ti-custom-table-head whitespace-nowrap">
                                            <thead>
                                                <tr>
                                                    <th>{{ _lang('Description') }}</th>
                                                    <th>{{ _lang('Due Date') }}</th>
                                                    <th>{{ _lang('Grand Total') }}</th>
                                                    <th>{{ _lang('Due Amount') }}</th>
                                                    <th>{{ _lang('Amount') }}</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach($payment->defferedReceivePayment as $receive_payment)
                                                <tr>
                                                    <input hidden name="payments[]" value="{{ $receive_payment->id }}">
                                                    <input type="hidden" name="invoices[]" value="{{ $receive_payment->invoice_id }}">
                                                    <td>
                                                        Deffered Payment #{{ $receive_payment->id }}
                                                    </td>
                                                    <td>{{ $receive_payment->due_date }}</td>
                                                    <td>{{ $receive_payment->amount }}</td>
                                                    <td>{{ $receive_payment->amount }}</td>
                                                    <td>
                                                        <input type="number" step="any" name="amount[{{ $receive_payment->id }}]" value="{{ $receive_payment->pivot->amount }}" required>
                                                    </td>
                                                </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <div class="col-span-12 mt-3">
                                    <x-input-label value="{{ _lang('Attachment') }}" />
                                    <input type="file" class="dropify" name="attachment">
                                </div>

                                <div class="col-span-12 my-4">
                                    <x-primary-button type="submit" class="submit-btn">{{ _lang('Submit') }}</x-primary-button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
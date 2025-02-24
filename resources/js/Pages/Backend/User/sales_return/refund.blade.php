<form method="post" class="ajax-screen-submit" autocomplete="off" action="{{ route('sales_returns.refund.store', $salesReturn->id) }}" enctype="multipart/form-data">
    {{ csrf_field() }}
    <div class="ti-modal-header flex items-center justify-between">
        <h3 class="ti-modal-title">
            Create New Refund
        </h3>
        <button type="button" class="hs-dropdown-toggle ti-modal-close-btn" data-hs-overlay="#refund-modal">
            <span class="sr-only">Close</span>
            <i class="ri-close-line text-xl"></i>
        </button>
    </div>
    @if($salesReturn->grand_total - $salesReturn->paid > 0)
    <div class="ti-modal-body grid grid-cols-12 gap-x-5">
        <h1 class="col-span-12 text-xl font-bold">
            Balance: {{ formatAmount($salesReturn->grand_total - $salesReturn->paid, currency_symbol(request()->activeBusiness->currency), $salesReturn->business_id) }}
        </h1>

        <div class="md:col-span-6 col-span-12 mt-3">
            <x-input-label value="{{ _lang('Date') }}" />
            <x-text-input type="text" class="flatpickr" id="date" name="refund_date" value="{{ \Carbon\Carbon::parse(old('refund_date'))->format('d-m-Y') }}" />
        </div>

        <div class="md:col-span-6 col-span-12 mt-3">
            <x-input-label value="{{ _lang('Amount') }}" />
            <x-text-input type="number" step="any" name="amount" value="{{ old('amount') }}" />
        </div>

        <div class="md:col-span-6 col-span-12 mt-3">
            <x-input-label value="{{ _lang('Credit Account') }}" />
            <select class="w-full auto-select selectize" data-selected="{{ old('account_id') }}" name="account_id">
                <option value="">{{ _lang('Select Account') }}</option>
                @foreach(\App\Models\Account::where(function ($query) {
                $query->where('account_type', '=', 'Bank')
                ->orWhere('account_type', '=', 'Cash');
                })->where('business_id', '=', request()->activeBusiness->id)
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
            <x-input-label value="{{ _lang('Attachment') }}" />
            <x-text-input type="file" name="attachment" class="dropify" data-allowed-file-extensions="pdf jpg png jpeg" data-default-file="{{ old('attachment') }}"  />
        </div>
    </div>
    @else
    <div class="ti-modal-body">
        <h1 class="text-center text-xl font-bold">
            No Balance to Refund
        </h1>
    </div>
    @endif
    <div class="ti-modal-footer">
        <x-secondary-button type="button" data-hs-overlay="#refund-modal">
            Close
        </x-secondary-button>
        @if($salesReturn->grand_total - $salesReturn->paid > 0)
        <x-primary-button type="submit" class="submit-btn">
            Save
        </x-primary-button>
        @endif
    </div>
</form>

<!-- Flatpickr Css -->
<link rel="stylesheet" href="/assets/libs/flatpickr/flatpickr.min.css">

<!-- Flatpickr JS -->
<script src="/assets/libs/flatpickr/flatpickr.min.js"></script>
<script src="/assets/js/flatpickr.js"></script>
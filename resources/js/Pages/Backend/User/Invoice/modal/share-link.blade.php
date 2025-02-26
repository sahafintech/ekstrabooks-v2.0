<div class="ti-modal-header">
	<h3 class="ti-modal-title">
		Share Invoice
	</h3>
	<button type="button" class="hs-dropdown-toggle ti-modal-close-btn" data-hs-overlay="#modal">
		<span class="sr-only">Close</span>
		<i class="ri-close-line text-xl"></i>
	</button>
</div>
<div class="ti-modal-body grid grid-cols-12 p-2 text-center">
	<div class="col-span-12">
		<p><strong>{{ _lang('Copy the following link and share with your customer') }}</strong></p>
	</div>

	<div class="col-span-12 mt-2">
		<div id="link" class="border border-primary inline-block rounded-md py-2 px-3 bg-white font-bold">{{ route('invoices.show_public_invoice', $invoice->short_code) }}</div>
	</div>
</div>
<div class="ti-modal-footer">
	<x-secondary-button type="button" data-hs-overlay="#modal">
		Close
	</x-secondary-button>
	<x-primary-button type="button" class="copy-link" data-message="{{ _lang('Link Copied') }}" data-copy-text="{{ route('invoices.show_public_invoice', $invoice->short_code) }}">
		Copy Link
	</x-primary-button>
</div>
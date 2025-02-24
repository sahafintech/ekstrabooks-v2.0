<div class="row p-2 text-center">
	<div class="col-12">
		<p><strong>{{ _lang('Copy the following link and share with your customer') }}</strong></p>
	</div>

	<div class="col-12 mt-2">
		<div id="link" class="border border-primary d-inline-block rounded-lg py-2 px-3 bg-light font-weight-bold">{{ route('invoices.show_public_invoice', $invoice->short_code) }}</div>
	</div>

	<div class="col-12 mt-4">
		<button type="button" class="btn btn-primary copy-link" data-message="{{ _lang('Link Copied') }}" data-copy-text="{{ route('invoices.show_public_invoice', $invoice->short_code) }}"><i class="far fa-copy mr-2"></i>{{ _lang('Copy Link') }}</button>
	</div>
</div>


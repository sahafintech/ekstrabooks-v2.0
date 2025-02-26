<form method="post" class="ajax-screen-submit" autocomplete="off" action="{{ route('invoices.send_email', $invoice->id) }}" enctype="multipart/form-data">
	{{ csrf_field() }}
	<div class="ti-modal-header">
		<h3 class="ti-modal-title">
			Send Invoie Email
		</h3>
		<button type="button" class="hs-dropdown-toggle ti-modal-close-btn" data-hs-overlay="#modal">
			<span class="sr-only">Close</span>
			<i class="ri-close-line text-xl"></i>
		</button>
	</div>
	<div class="ti-modal-body grid grid-cols-12 gap-x-5">
		<div class="md:col-span-6 col-span-12">
			<x-input-label value="{{ _lang('Customer Email') }}" />
			<x-text-input class="form-control" name="email" value="{{ old('email', $invoice->customer->email) }}" required />
		</div>

		<div class="md:col-span-6 col-span-12">
			<x-input-label value="{{ _lang('Email Template') }}" />
			<select class="auto-select w-full" data-selected="{{ old('template') }}" id="template" name="template">
				<option value="">{{ _lang('None') }}</option>
				@foreach($email_templates as $email_template)
				<option value="{{ $email_template->slug }}" data-email-body="{{ $email_template->email_body }}" data-subject="{{ $email_template->subject }}" data-shortcode="{{ $email_template->shortcode }}">{{ $email_template->name }}</option>
				@endforeach
			</select>
		</div>

		<div class="col-span-12 mt-3">
			<x-input-label value="{{ _lang('Subject') }}" />
			<x-text-input type="text" name="subject" id="subject" value="{{ old('subject') }}" required />
		</div>

		<div class="col-span-12 mt-3">
			<x-input-label value="{{ _lang('Shortcode') }}" />
			<pre class="border py-2 px-2" id="shortcode">@php echo "{{customerName}} {{invoiceNumber}} {{invoiceDate}} {{DueDate}} {{dueAmount}} {{invoiceLink}}" @endphp</pre>
		</div>

		<div class="col-span-12 mt-3">
			<x-input-label value="{{ _lang('Message') }}" />
			<textarea class="w-full" name="message" id="message">{{ old('message') }}</textarea>
		</div>
	</div>
	<div class="ti-modal-footer">
		<x-secondary-button type="button" data-hs-overlay="#modal">
			Close
		</x-secondary-button>
		<x-primary-button type="submit" class="submit-btn">
			Send Email
		</x-primary-button>
	</div>
</form>


<script>
	$('#message').summernote({
		tabsize: 4,
		height: 250
	});

	$(document).on('change', '#template', function() {
		var template = $(this).val();
		if (template != '') {
			$('#message').summernote('code', $(this).find(':selected').data('email-body'));
			$("#subject").val($(this).find(':selected').data('subject'));
			$("#shortcode").html($(this).find(':selected').data('shortcode'));
		} else {
			$('#message').summernote('code', '');
			$("#subject").val('');
			$("#shortcode").html('@php echo "{{customerName}} {{invoiceNumber}} {{invoiceDate}} {{dueDate}} {{dueAmount}} {{invoiceLink}}" @endphp');
		}
	});
</script>
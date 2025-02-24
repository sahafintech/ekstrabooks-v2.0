<form method="post" class="ajax-screen-submit" autocomplete="off" action="{{ route('quotations.send_email', $quotation->id) }}" enctype="multipart/form-data">
	{{ csrf_field() }}
	<div class="row px-2">
		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label">{{ _lang('Customer Email') }}</label>
				<input type="email" class="form-control" name="email" value="{{ old('email', $quotation->customer->email) }}" required>
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label">{{ _lang('Email Template') }}</label>
				<select class="form-control auto-select" data-selected="{{ old('template') }}" id="template" name="template">
					<option value="">{{ _lang('None') }}</option>
					@foreach($email_templates as $email_template)
					<option value="{{ $email_template->slug }}" data-email-body="{{ $email_template->email_body }}" data-subject="{{ $email_template->subject }}" data-shortcode="{{ $email_template->shortcode }}">{{ $email_template->name }}</option>
					@endforeach
				</select>
			</div>
		</div>

		<div class="col-md-12">
			<div class="form-group">
				<label class="control-label">{{ _lang('Subject') }}</label>
				<input type="text" class="form-control" name="subject" id="subject" value="{{ old('subject') }}" required>
			</div>
		</div>

		<div class="col-md-12">
			<div class="form-group">
				<label class="control-label">{{ _lang('Short Code') }}</label>
				<pre class="border py-2 px-2" id="shortcode">@php echo "{{customerName}} {{quotationNumber}} {{quotationDate}} {{expiryDate}} {{amount}} {{quotationLink}}" @endphp</pre>
			</div>
		</div>

		<div class="col-md-12">
			<div class="form-group">
				<label class="control-label">{{ _lang('Message') }}</label>
				<textarea class="form-control" name="message" id="message">{{ old('message') }}</textarea>
			</div>
		</div>

		<div class="col-md-12 mt-2">
		    <div class="form-group">
			    <button type="submit" class="btn btn-primary"><i class="far fa-paper-plane mr-2"></i>{{ _lang('Send') }}</button>
		    </div>
		</div>
	</div>
</form>


<script>
$('#message').summernote({
	tabsize: 4,
	height: 250
});

$(document).on('change','#template', function(){
	var template = $(this).val();
	if(template != ''){
		$('#message').summernote('code', $(this).find(':selected').data('email-body'));
		$("#subject").val($(this).find(':selected').data('subject'));
		$("#shortcode").html($(this).find(':selected').data('shortcode'));
	}else{
		$('#message').summernote('code', '');
		$("#subject").val('');
		$("#shortcode").html('@php echo "{{customerName}} {{quotationNumber}} {{quotationDate}} {{expiryDate}} {{amount}} {{quotationLink}}" @endphp');
	}
});
</script>

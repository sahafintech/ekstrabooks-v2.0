<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Notification Template" page="admin" subpage="edit" />

		<div class="box">
			<div class="box-header flex items-center">
				<h5>{{ _lang('Update Notification Template') }}</h5>
			</div>
			<div class="box-body">
				<form method="post" class="validate" autocomplete="off" action="{{ route('notification_templates.update', $id) }}" enctype="multipart/form-data">
					@csrf
					<input name="_method" type="hidden" value="PATCH">
					<div class="col-span-12">
						<x-input-label value="{{ _lang('Name') }}" />
						<x-text-input type="text" class="form-control" name="name" value="{{ $emailtemplate->name }}" readonly />
					</div>

					<div class="col-span-12 mt-3">
						<x-input-label value="{{ _lang('Short Code') }}" />
						<pre class="border py-2 px-2">{{ $emailtemplate->shortcode }}</pre>
					</div>

					<div class="col-span-12 mt-4">
						@if($emailtemplate->template_mode == 1 || $emailtemplate->template_mode == 0)
						<div class="border rounded py-2 px-3 mb-4">
							<div class="form-check my-2">
								<input type="hidden" name="email_status" value="0">
								<input type="checkbox" class="form-check-input" id="Check1" name="email_status" value="1" id="hs-basic-collapse" data-hs-collapse="#hs-basic-collapse-heading" {{ $emailtemplate->email_status == 1 ? 'checked' : '' }}>
								<label class="form-check-label" for="Check1">{{ _lang('Email Notification') }}</label>
							</div>
							<div id="hs-basic-collapse-heading" class="hs-collapse {{ $emailtemplate->email_status == 0 ? 'hidden' : '' }} w-full overflow-hidden transition-[height] duration-300" aria-labelledby="hs-basic-collapse">
								<div class="grid grid-cols-12">
									<div class="col-span-12">
										<x-input-label value="{{ _lang('Subject') }}" />
										<x-text-input type="text" name="subject" value="{{ $emailtemplate->subject }}" required />
									</div>

									<div class="col-span-12 mt-3">
										<x-input-label value="{{ _lang('Email Body') }}" />
										<textarea class="w-full summernote" rows="6" name="email_body">{{ $emailtemplate->email_body }}</textarea>
									</div>
								</div>
							</div>
						</div>
						@endif

						@if($emailtemplate->template_mode == 2 || $emailtemplate->template_mode == 0)
						<div class="accordion border rounded py-2 px-3 mb-4" id="sms_templates">
							<div class="form-check my-2">
								<input type="hidden" name="sms_status" value="0">
								<input type="checkbox" class="form-check-input" id="Check2" name="sms_status" value="1" data-toggle="collapse" data-target="#collapseTwo" aria-expanded="true" aria-controls="collapseTwo" {{ $emailtemplate->sms_status == 1 ? 'checked' : '' }}>
								<label class="form-check-label" for="Check2">{{ _lang('SMS Notification') }}</label>
							</div>
							<div id="collapseTwo" class="collapse {{ $emailtemplate->sms_status == 1 ? 'show' : '' }}" aria-labelledby="headingTwo" data-parent="#sms_templates">
								<div class="grid grid-cols-12">
									<div class="col-span-12">
										<textarea class="w-full" name="sms_body">{{ $emailtemplate->sms_body }}</textarea>
									</div>
								</div>
							</div>
						</div>
						@endif

						@if($emailtemplate->template_mode == 3 || $emailtemplate->template_mode == 0)
						<div class="accordion border rounded py-2 px-3" id="notification_templates">
							<div class="form-check my-2">
								<input type="hidden" name="notification_status" value="0">
								<input type="checkbox" class="form-check-input" id="Check3" name="notification_status" value="1" data-toggle="collapse" data-target="#collapseThree" aria-expanded="true" aria-controls="collapseThree" {{ $emailtemplate->notification_status == 1 ? 'checked' : '' }}>
								<label class="form-check-label" for="Check3">{{ _lang('Local Notification') }}</label>
							</div>

							<div id="collapseThree" class="{{ $emailtemplate->notification_status == 1 ? 'show' : '' }}" aria-labelledby="headingThree" data-parent="#notification_templates">
								<div class="grid grid-cols-12">
									<div class="col-span-12">
										<textarea class="w-full" name="notification_body">{{ $emailtemplate->notification_body }}</textarea>
									</div>
								</div>
							</div>
						</div>
						@endif
					</div>

					<div class="col-span-12 mt-4">
						<x-primary-button type="submit" class="submit-btn">{{ _lang('Update') }}</x-primary-button>
					</div>
				</form>
			</div>
		</div>
	</div>
</x-app-layout>
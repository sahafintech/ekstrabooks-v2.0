<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Email Subscribers" page="admin" subpage="send email" />

		<div class="box">
			<div class="box-header text-center">
				<span class="panel-title">{{ _lang('Send email to all Subscriber') }}</span>
			</div>
			<div class="box-body">
				<form method="post" class="validate" autocomplete="off" action="{{ route('email_subscribers.send_email') }}" enctype="multipart/form-data">
					{{ csrf_field() }}
					<div class="grid grid-cols-12">
						<div class="col-span-12">
							<x-input-label value="{{ _lang('Subject') }}" />
							<x-text-input type="text" value="{{ old('subject') }}" name="subject" />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Message') }}" />
							<textarea class="w-full summernote" name="message">{{ old('message') }}</textarea>
						</div>

						<div class="col-span-12 mt-4">
							<x-primary-button type="submit" class="submit-btn">{{ _lang('Send Email') }}</x-primary-button>
							<x-secondary-button>
								<a href="{{ url()->previous() }}" class="btn btn-dark"><i class="ri-arrow-left-line mr-1"></i>{{ _lang('Back') }}</a>
							</x-secondary-button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</x-app-layout>
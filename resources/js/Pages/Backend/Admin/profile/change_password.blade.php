<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Admin" page="home" subpage="change password" />

		<div class="box">
			<div class="box-header">
				{{ _lang('Change Password') }}
			</div>

			<div class="box-body">
				<form action="{{ route('profile.update_password') }}" class="form-horizontal form-groups-bordered validate" enctype="multipart/form-data" method="post" accept-charset="utf-8">
					@csrf
					<div class="grid grid-cols-12 gap-x-5">
						<div class="xl:col-span-6 col-span-12">
							<x-input-label value="{{ _lang('Old Password') }}" />
							<x-text-input type="password" name="oldpassword" required />
						</div>

						<div class="xl:col-span-6 col-span-12">
							<x-input-label value="{{ _lang('New Password') }}" />
							<x-text-input type="password" name="password" required />
						</div>

						<div class="xl:col-span-6 col-span-12 mt-3">
							<x-input-label value="{{ _lang('Confirm Password') }}" />
							<x-text-input type="password" name="password_confirmation" required />
						</div>

						<div class="col-span-12 mt-4">
							<x-primary-button type="submit" class="submit-btn">{{ _lang('Update Password') }}</x-primary-button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</x-app-layout>
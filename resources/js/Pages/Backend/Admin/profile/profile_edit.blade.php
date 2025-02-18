<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Profile Settings" page="profile" subpage="edit" />

		<div class="box">
			<div class="box-header text-center">
				{{ _lang('Profile Settings') }}
			</div>
			<div class="box-body">
				<form action="{{ route('profile.update') }}" autocomplete="off" class="form-horizontal form-groups-bordered validate" enctype="multipart/form-data" method="post">
					@csrf
					<div class="grid grid-cols-12 gap-x-5">
						<div class="col-span-12">
							<x-input-label value="{{ _lang('Name') }}" />
							<x-text-input type="text" name="name" value="{{ $profile->name }}" required />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Email') }}" />
							<x-text-input type="text" name="email" value="{{ $profile->email }}" required />
						</div>

						<div class="col-span-12 mt-3">
							<x-input-label value="{{ _lang('Image') }} (300 X 300)" />
							<x-text-input type="file" class="dropify" data-default-file="{{ $profile->profile_picture != "" ? asset('public/uploads/profile/'.$profile->profile_picture) : '' }}" name="profile_picture" data-allowed-file-extensions="png jpg jpeg PNG JPG JPEG" />
						</div>

						<div class="col-span-12 mt-4">
							<x-primary-button type="submit" class="submit-btn">{{ _lang('Update Profile') }}</x-primary-button>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</x-app-layout>
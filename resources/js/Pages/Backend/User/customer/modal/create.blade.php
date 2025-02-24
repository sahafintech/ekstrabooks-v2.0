<form method="post" class="ajax-screen-submit" autocomplete="off" action="{{ route('customers.store') }}" enctype="multipart/form-data">
	@csrf
	<div class="row px-2">
		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label">{{ _lang('Name') }}</label>						
				<input type="text" class="form-control" name="name" value="{{ old('name') }}" required>
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label">{{ _lang('Company Name') }}</label>						
				<input type="text" class="form-control" name="company_name" value="{{ old('company_name') }}">
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label">{{ _lang('Email') }}</label>						
				<input type="text" class="form-control" name="email" value="{{ old('email') }}">
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label">{{ _lang('Mobile') }}</label>						
				<input type="text" class="form-control" name="mobile" value="{{ old('mobile') }}">
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label">{{ _lang('Country') }}</label>						
				<select class="form-control auto-select select2" data-selected="{{ old('country') }}" name="country">
					<option value="">{{ _lang('Select One') }}</option>
					{{ get_country_list() }}
				</select>
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label">{{ _lang('Currency') }}</label>						
				<select class="form-control auto-select select2" data-selected="{{ old('currency') }}" name="currency" required>
					<option value="">{{ _lang('Select One') }}</option>
					{{ get_currency_list() }}
				</select>
				<small class="text-danger"><i class="fas fa-exclamation-circle mr-1"></i>{{ _lang('You will be not able to change this currency !') }}</small>
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label">{{ _lang('Vat ID') }}</label>						
				<input type="text" class="form-control" name="vat_id" value="{{ old('vat_id') }}">
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label">{{ _lang('Reg No') }}</label>						
				<input type="text" class="form-control" name="reg_no" value="{{ old('reg_no') }}">
			</div>
		</div>

		<div class="col-md-12">
			<div class="form-group">
				<label class="control-label">{{ _lang('City') }}</label>						
				<input type="text" class="form-control" name="city" value="{{ old('city') }}">
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label">{{ _lang('State') }}</label>						
				<input type="text" class="form-control" name="state" value="{{ old('state') }}">
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label">{{ _lang('ZIP') }}</label>						
				<input type="text" class="form-control" name="zip" value="{{ old('zip') }}">
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label">{{ _lang('Address') }}</label>						
				<textarea class="form-control" name="address">{{ old('address') }}</textarea>
			</div>
		</div>

		<div class="col-md-6">
			<div class="form-group">
				<label class="control-label">{{ _lang('Remarks') }}</label>						
				<textarea class="form-control" name="remarks">{{ old('remarks') }}</textarea>
			</div>
		</div>

		<div class="col-md-12">
			<div class="form-group">
				<label class="control-label">{{ _lang('Profile Picture') }}</label>						
				<input type="file" class="form-control dropify" name="profile_picture" >
			</div>
		</div>
			
		<div class="col-md-12 mt-2">
			<div class="form-group">
				<button type="submit" class="btn btn-primary"><i class="ti-check-box mr-2"></i>{{ _lang('Save Changes') }}</button>
			</div>
		</div>
	</div>
</form>

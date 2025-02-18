<form method="post" class="ajax-screen-submit" autocomplete="off" action="{{ route('business_types.update', $id) }}" enctype="multipart/form-data">
	{{ csrf_field()}}
	<input name="_method" type="hidden" value="PATCH">
	<div class="row px-2">
		<div class="col-md-12">
		<div class="form-group">
		   <label class="control-label">{{ _lang('Name') }}</label>						
		   <input type="text" class="form-control" name="name" value="{{ $businesstype->name }}" required>
		</div>
	</div>

	<div class="col-md-12">
		<div class="form-group">
			<label class="control-label">{{ _lang('Status') }}</label>						
			<select class="form-control auto-select" data-selected="{{ $businesstype->status }}" name="status"  required>
				<option value="">{{ _lang('Select One') }}</option>
				<option value="1">{{ _lang('Active') }}</option>
<option value="0">{{ _lang('Disabled') }}</option>
			</select>
		</div>
	</div>

	
		<div class="col-md-12">
			<div class="form-group">    
			    <button type="submit" class="btn btn-primary"><i class="ti-check-box mr-2"></i>{{ _lang('Update') }}</button>
		    </div>
		</div>
	</div>
</form>


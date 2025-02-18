@extends('layouts.app')

@section('content')
<div class="row">
	<div class="col-12">
		<div class="card">
			<div class="card-header d-flex align-items-center justify-content-between">
				<span class="panel-title">{{ $title }}</span>
			</div>
			<div class="card-body">
				<form method="post" class="validate" autocomplete="off" action="{{ route('languages.update', $id) }}">
					@csrf
					<input name="_method" type="hidden" value="PATCH">
					<div class="row">
						@foreach( $language as $key => $lang )
						<div class="col-md-6">
							<div class="form-group">
								<label class="control-label">{{ $key }}</label>
								<input type="text" class="form-control" name="language[{{ str_replace(' ','_',$key) }}]" value="{{ $lang }}" required>
							</div>
						</div>
						@endforeach

						<div class="col-md-12 mt-2">
							<div class="form-group">
								<button type="submit" class="btn btn-primary"><i class="ti-check-box mr-2"></i>{{ _lang('Save Translation') }}</button>
							</div>
						</div>
					</div>
				</form>
			</div>
		</div>
	</div>
</div>
@endsection

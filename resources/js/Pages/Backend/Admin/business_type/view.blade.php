@extends('layouts.app')

@section('content')
<div class="row">
	<div class="col-lg-12">
		<div class="card">
		    <div class="card-header">
				<span class="panel-title">{{ _lang('Business Types') }}</span>
			</div>
			
			<div class="card-body">
			    <table class="table table-bordered">
				    <tr><td>{{ _lang('Name') }}</td><td>{{ $businesstype->name }}</td></tr>
					<tr><td>{{ _lang('Status') }}</td><td>{{ $businesstype->status }}</td></tr>
			    </table>
			</div>
	    </div>
	</div>
</div>
@endsection



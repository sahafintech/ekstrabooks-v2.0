@extends('layouts.app')

@section('content')

<div class="row">
	<div class="col-lg-12">
		<div class="card">
			<div class="card-header d-md-flex align-items-center justify-content-between">
				<span class="panel-title flex-grow-1">{{ _lang('Recurring Invoices') }}</span>

				<div class="row flex-grow-1 align-items-center">				
					<div class="col-lg-5 col-md-4 mb-2">
						<select class="form-control select2 select-filter" name="customer_id">
							<option value="">{{ _lang('All Customers') }}</option>
							@foreach(\App\Models\Customer::all() as $customer)
							<option value="{{ $customer->id }}">{{ $customer->name }}</option>
							@endforeach
						</select>
					</div>	
					
					<div class="col-md-4 mb-2">
						<select class="form-control multi-selector select-filter" data-placeholder="{{ _lang('All Statuses') }}" name="status" multiple>
							<option value="0">{{ _lang('Draft') }}</option>
							<option value="1">{{ _lang('Active') }}</option>
							<option value="2">{{ _lang('Ended') }}</option>
						</select>
					</div>	

					<div class="col-lg-3 col-md-4 mb-2">
						<a class="btn btn-primary btn-block" href="{{ route('recurring_invoices.create') }}"><i class="ti-plus mr-1"></i>{{ _lang('New Invoice') }}</a>
					</div>
				</div>

				
			</div>
			<div class="card-body">
				<table id="invoices_table" class="table">
					<thead>
					    <tr>
						    <th>{{ _lang('Customer') }}</th>
						    <th>{{ _lang('Recurring') }}</th>
						    <th>{{ _lang('Recurring Start') }}</th>
						    <th>{{ _lang('Next Invoice') }}</th>
							<th class="text-center">{{ _lang('Status') }}</th>
							<th class="text-right">{{ _lang('Grand Total') }}</th>	
							<th class="text-center">{{ _lang('Action') }}</th>
					    </tr>
					</thead>
					<tbody>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>
@endsection

@section('js-script')
<script>
(function ($) {
	"use strict";
	var invoice_table = $('#invoices_table').DataTable({
		processing: true,
		serverSide: true,
		ajax: ({
			url: '{{ url('user/recurring_invoices/get_table_data') }}',
			method: "POST",
			data: function (d) {

				d._token =  $('meta[name="csrf-token"]').attr('content');
				
				if($('select[name=customer_id]').val() != ''){
					d.customer_id = $('select[name=customer_id]').val();
				}
				
				if($('select[name=status]').val().length > 0){
					d.status = JSON.stringify($('select[name=status]').val());
				}

			},
			error: function (request, status, error) {
				console.log(request.responseText);
			}
		}),
		"columns" : [
			{ data : 'customer.name', name : 'customer.customer', orderable: false },
			{ data : 'recurring_schedule', name : 'recurring_schedule', orderable: false },
			{ data : 'recurring_start', name : 'recurring_start', orderable: false },
			{ data : 'recurring_invoice_date', name : 'recurring_invoice_date', orderable: false },
			{ data : 'status', name : 'status', orderable: false },
			{ data : 'grand_total', name : 'grand_total', orderable: false },
			{ data : 'action', name : 'action', orderable: false },
		],
		responsive: true,
		"bStateSave": true,
		"bAutoWidth":false,
		"ordering": false,
		"language": {
		   "decimal":        "",
		   "emptyTable":     "{{ _lang('No Data Found') }}",
		   "info":           "{{ _lang('Showing') }} _START_ {{ _lang('to') }} _END_ {{ _lang('of') }} _TOTAL_ {{ _lang('Entries') }}",
		   "infoEmpty":      "{{ _lang('Showing 0 To 0 Of 0 Entries') }}",
		   "infoFiltered":   "(filtered from _MAX_ total entries)",
		   "infoPostFix":    "",
		   "thousands":      ",",
		   "lengthMenu":     "{{ _lang('Show') }} _MENU_ {{ _lang('Entries') }}",
		   "loadingRecords": "{{ _lang('Loading...') }}",
		   "processing":     "{{ _lang('Processing...') }}",
		   "search":         "{{ _lang('Search') }}",
		   "zeroRecords":    "{{ _lang('No matching records found') }}",
		   "paginate": {
			  "first":      "{{ _lang('First') }}",
			  "last":       "{{ _lang('Last') }}",
			  "previous":   "<i class='fas fa-angle-left'></i>",
			  "next":       "<i class='fas fa-angle-right'></i>"
		  }
		}
	});
	
	$('.select-filter').on('change', function(e) {
		invoice_table.draw();
	});


})(jQuery);
</script>
@endsection
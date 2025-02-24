<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Credit Invoices" page="user" subpage="view" />

		<!-- <link rel="stylesheet" href="{{ asset('/backend/assets/css/invoice.css?v=1.0') }}"> -->
		<div class="grid grid-cols-12 content-center">
			<div class="col-span-12">
				@include('backend.user.quotation.action')
				@include('backend.user.quotation.template.loader')
			</div>
		</div>
	</div>
</x-app-layout>
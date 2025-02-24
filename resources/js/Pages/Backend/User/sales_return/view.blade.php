<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Sales Returns" page="user" subpage="view" />

		<!-- <link rel="stylesheet" href="{{ asset('/backend/assets/css/invoice.css?v=1.0') }}"> -->
		<div class="grid grid-cols-12 content-center">
			<div class="col-span-12">
				@include('backend.user.sales_return.action')
				@include('backend.user.sales_return.template.loader')
			</div>
		</div>
	</div>
</x-app-layout>
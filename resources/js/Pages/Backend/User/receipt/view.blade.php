@php $type = isset($type) ? $type : 'preview'; @endphp

<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Cash Invoice" page="user" subpage="view" />

		<div class="grid grid-cols-12 content-center">
			<div class="col-span-12">
				@include('backend.user.receipt.action')
				@include('backend.user.receipt.template.default', ['type' => $type])
			</div>
		</div>
	</div>
</x-app-layout>
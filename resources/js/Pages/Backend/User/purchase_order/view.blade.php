<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Purchases" page="user" subpage="view" />

		<!-- <link rel="stylesheet" href="{{ asset('public/backend/assets/css/invoice.css?v=1.0') }}"> -->
		<div class="grid grid-cols-12 content-center">
			<div class="col-span-12">
				<div>
					<nav class="relative z-0 sm:flex border rounded-sm divide-y sm:divide-y-0 sm:divide-x rtl:divide-x-reverse divide-gray-200 overflow-hidden dark:border-white/10 dark:divide-white/10" aria-label="Tabs" role="tablist">
						<a class="justify-center items-center sm:flex inline-flex w-full sm:w-auto hs-tab-active:!border-b-2 hs-tab-active:!border-b-primary hs-tab-active:text-gray-900 dark:hs-tab-active:text-white group relative min-w-0 flex-1 bg-white py-4 px-4 text-gray-500 hover:text-gray-700 text-sm font-medium text-center overflow-hidden dark:text-white/70 hover:bg-gray-50 focus:z-10 dark:bg-bgdark dark:hover:bg-bodybg dark:hover:text-white active" href="javascript:void(0);" id="bar-item-1" data-hs-tab="#bar-1" aria-controls="bar-1">
							Invoice
						</a>
						<a class="justify-center items-center sm:flex inline-flex w-full sm:w-auto hs-tab-active:!border-b-2 hs-tab-active:!border-b-primary hs-tab-active:text-gray-900 dark:hs-tab-active:text-white group relative min-w-0 flex-1 bg-white py-4 px-4 text-gray-500 hover:text-gray-700 text-sm font-medium text-center overflow-hidden dark:text-white/70 hover:bg-gray-50 focus:z-10 dark:bg-bgdark dark:hover:bg-bodybg dark:hover:text-white" href="javascript:void(0);" id="bar-item-2" data-hs-tab="#bar-2" aria-controls="bar-2">
							Attachments
						</a>
					</nav>
					<div class="mt-3">
						<div id="bar-1" role="tabpanel" aria-labelledby="bar-item-1" class="">
							@include('backend.user.purchase_order.action')
							@include('backend.user.purchase_order.template.loader')
						</div>
						<div id="bar-2" class="hidden" role="tabpanel" aria-labelledby="bar-item-2">
							@include('backend.user.purchase_order.attachments')
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>
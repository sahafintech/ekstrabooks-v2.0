<x-app-layout>
	<!-- Start::main-content -->
	<div class="main-content">
		<x-page-header title="Website" page="default pages" subpage="list" />

		<div class="box">
			<div class="box-header flex items-center">
				<h5>{{ _lang('Default Pages') }}</h5>
			</div>
			<div class="box-body">
				<div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
					<table class="ti-custom-table ti-custom-table-head whitespace-nowrap">
						<thead>
							<tr>
								<th class="pl-3">{{ _lang('Page') }}</th>
								<th>{{ _lang('Action') }}</th>
							</tr>
						</thead>
						<tbody>
							<tr>
								<td class="pl-3">{{ _lang('Home Page') }}</td>
								<td>
									<a href="{{ route('pages.default_pages', 'home') }}">
										<x-secondary-button>
											<i class="ri-edit-box-line mr-1"></i>
											{{ _lang('Edit') }}
										</x-secondary-button>
									</a>
								</td>
							</tr>
							<tr>
								<td class="pl-3">{{ _lang('About Page') }}</td>
								<td>
									<a href="{{ route('pages.default_pages', 'about') }}">
										<x-secondary-button>
											<i class="ri-edit-box-line mr-1"></i>
											{{ _lang('Edit') }}
										</x-secondary-button>
									</a>
								</td>
							</tr>
							<tr>
								<td class="pl-3">{{ _lang('Feature Page') }}</td>
								<td>
									<a href="{{ route('pages.default_pages', 'features') }}">
										<x-secondary-button>
											<i class="ri-edit-box-line mr-1"></i>
											{{ _lang('Edit') }}
										</x-secondary-button>
									</a>
								</td>
							</tr>
							<tr>
								<td class="pl-3">{{ _lang('Pricing Page') }}</td>
								<td>
									<a href="{{ route('pages.default_pages', 'pricing') }}">
										<x-secondary-button>
											<i class="ri-edit-box-line mr-1"></i>
											{{ _lang('Edit') }}
										</x-secondary-button>
									</a>
								</td>
							</tr>
							<tr>
								<td class="pl-3">{{ _lang('Blog Page') }}</td>
								<td>
									<a href="{{ route('pages.default_pages', 'blogs') }}">
										<x-secondary-button>
											<i class="ri-edit-box-line mr-1"></i>
											{{ _lang('Edit') }}
										</x-secondary-button>
									</a>
								</td>
							</tr>
							<tr>
								<td class="pl-3">{{ _lang('FAQ Page') }}</td>
								<td>
									<a href="{{ route('pages.default_pages', 'faq') }}">
										<x-secondary-button>
											<i class="ri-edit-box-line mr-1"></i>
											{{ _lang('Edit') }}
										</x-secondary-button>
									</a>
								</td>
							</tr>
							<tr>
								<td class="pl-3">{{ _lang('Contact Page') }}</td>
								<td>
									<a href="{{ route('pages.default_pages', 'contact') }}">
										<x-secondary-button>
											<i class="ri-edit-box-line mr-1"></i>
											{{ _lang('Edit') }}
										</x-secondary-button>
									</a>
								</td>
							</tr>
						</tbody>
					</table>
				</div>
			</div>
		</div>
	</div>
</x-app-layout>
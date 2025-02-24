<x-app-layout>
    <!-- Start::main-content -->
    <div class="main-content">
        <x-page-header title="Category" page="user" subpage="list" />

        <div class="box">
            <div class="box-header flex items-center justify-between">
                <h5>{{ _lang('Categories') }}</h5>
                <a href="{{ route('categories.create') }}" class="ajax-modal" data-hs-overlay="#category-modal">
                    <x-primary-button>
                        <i class="ri-add-line mr-1"></i>
                        {{ _lang('Add New Category') }}
                    </x-primary-button>
                </a>
            </div>
            <div class="box-body">
                <div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
                    <table id="datatable" class="ti-custom-table ti-custom-table-head whitespace-nowrap">
                        <thead>
                            <tr>
                                <th>{{ _lang('Image') }}</th>
                                <th>{{ _lang('Name') }}</th>
                                <th>{{ _lang('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($categories as $category)
                            <tr>
                                <td><img src="{{ asset('/uploads/media/'.$category->image) }}" class="w-14" /></td>
                                <td>{{ $category->name }}</td>
                                <td>
                                    <div class="hs-dropdown ti-dropdown">
                                        <button id="hs-dropdown-with-icons" type="button" class="hs-dropdown-toggle ti-dropdown-toggle">
                                            Actions
                                            <svg class="hs-dropdown-open:rotate-180 ti-dropdown-caret" width="16" height="16" viewBox="0 0 16 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                                                <path d="M2 5L8.16086 10.6869C8.35239 10.8637 8.64761 10.8637 8.83914 10.6869L15 5" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                            </svg>
                                        </button>
                                        <div class="hs-dropdown-menu ti-dropdown-menu divide-y divide-gray-200" aria-labelledby="hs-dropdown-with-icons">
                                            <div class="ti-dropdown-divider">
                                                <a class="ti-dropdown-item ajax-modal" href="{{ route('categories.edit', $category['id']) }}" data-hs-overlay="#category-modal">
                                                    <i class="ri-edit-box-line text-lg"></i>
                                                    Edit
                                                </a>
                                                <a class="ti-dropdown-item" href="javascript:void(0);" data-hs-overlay="#delete-modal" id="delete" data-id="{{ $category['id'] }}">
                                                    <i class="ri-delete-bin-line text-lg"></i>
                                                    Delete
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div id="category-modal" class="hs-overlay hidden ti-modal">
            <div class="hs-overlay-open:mt-7 ti-modal-box mt-0 ease-out lg:!max-w-4xl lg:w-full m-3 lg:!mx-auto">
                <div class="ti-modal-content">
                    <div class="ti-modal-body hidden" id="modal_spinner">
                        <div class="text-center spinner">
                            <div class="ti-spinner text-primary" role="status" aria-label="loading"> <span class="sr-only">Loading...</span> </div>
                        </div>
                    </div>
                    <div id="main-modal">

                    </div>
                </div>
            </div>
        </div>
        <x-modal>
            <form method="post">
                {{ csrf_field() }}
                <input name="_method" type="hidden" value="DELETE">
                <div class="ti-modal-header">
                    <h3 class="ti-modal-title">
                        Delete Modal
                    </h3>
                    <button type="button" class="hs-dropdown-toggle ti-modal-close-btn" data-hs-overlay="#delete-modal">
                        <span class="sr-only">Close</span>
                        <i class="ri-close-line text-xl"></i>
                    </button>
                </div>
                <div class="ti-modal-body">

                    <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                        {{ __('Are you sure you want to delete the category?') }}
                    </h2>

                </div>
                <div class="ti-modal-footer">
                    <x-secondary-button data-hs-overlay="#delete-modal">
                        {{ __('Cancel') }}
                    </x-secondary-button>

                    <x-danger-button class="ml-3 submit-btn" type="submit">
                        {{ __('Delete Category') }}
                    </x-danger-button>
                </div>
            </form>
        </x-modal>
    </div>
</x-app-layout>

<script>
    $(document).on('click', '#delete', function() {
        var id = $(this).data('id');
        $('#delete-modal form').attr('action', '/user/categories/' + id);
    });
</script>
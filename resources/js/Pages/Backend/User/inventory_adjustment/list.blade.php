<x-app-layout>
    <!-- Start::main-content -->
    <div class="main-content">
        <x-page-header title="Inventory Adjustments" page="user" subpage="list" />

        <div class="box">
            <div class="box-header flex items-center justify-between">
                <h5>{{ _lang('Adjustments') }}</h5>
                <div class="flex gap-2">
                    <a href="/uploads/media/default/sample_adjustments.xlsx" download>
                        <x-secondary-button>
                            <i class="ri-download-line mr-1"></i>
                            {{ _lang('Download Sample') }}
                        </x-secondary-button>
                    </a>
                    <a href="{{ route('inventory_adjustment.import') }}">
                        <x-secondary-button>
                            <i class="ri-file-upload-line mr-1"></i>
                            {{ _lang('Import') }}
                        </x-secondary-button>
                    </a>
                    <a href="{{ route('inventory_adjustments.create') }}" class="ajax-modal" data-hs-overlay="#adjustment-modal">
                        <x-primary-button>
                            <i class="ri-add-line mr-1"></i>
                            {{ _lang('Add New') }}
                        </x-primary-button>
                    </a>
                </div>
            </div>
            <div class="box-body">
                <div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
                    <table id="datatable" class="ti-custom-table ti-custom-table-head whitespace-nowrap">
                        <thead>
                            <tr>
                                <th>{{ _lang('Date') }}</th>
                                <th>{{ _lang('Product Name') }}</th>
                                <th>{{ _lang('Qty On Hand') }}</th>
                                <th>{{ _lang('Qty Adjusted') }}</th>
                                <th>{{ _lang('Adjustment Type') }}</th>
                                <th>{{ _lang('New Qty') }}</th>
                                <th>{{ _lang('Action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($adjustments as $adjustment)
                            <tr>
                                <td>{{ $adjustment->adjustment_date }}</td>
                                <td>{{ $adjustment->product->name }}</td>
                                <td>{{ $adjustment->quantity_on_hand }}</td>
                                <td>{{ $adjustment->adjusted_quantity }}</td>
                                <td>
                                    @if($adjustment->adjustment_type == 'adds')
                                    <span class="text-success">{{ _lang('Addition') }}</span>
                                    @else
                                    <span class="text-danger">{{ _lang('Deduction') }}</span>
                                    @endif
                                </td>
                                <td>{{ $adjustment->new_quantity_on_hand }}</td>
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
                                                <a class="ti-dropdown-item ajax-modal" href="{{ route('inventory_adjustments.edit', $adjustment['id']) }}" data-hs-overlay="#adjustment-modal">
                                                    <i class="ri-edit-box-line text-lg"></i>
                                                    Edit
                                                </a>
                                                <a class="ti-dropdown-item" href="javascript:void(0);" data-hs-overlay="#delete-modal" data-id="{{ $adjustment['id'] }}" id="delete">
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
                        {{ __('Are you sure you want to delete the adjustment?') }}
                    </h2>

                </div>
                <div class="ti-modal-footer">
                    <x-secondary-button data-hs-overlay="#delete-modal">
                        {{ __('Cancel') }}
                    </x-secondary-button>

                    <x-danger-button class="ml-3 submit-btn" type="submit">
                        {{ __('Delete Adjustment') }}
                    </x-danger-button>
                </div>
            </form>
        </x-modal>

        <div id="adjustment-modal" class="hs-overlay hidden overflow-y-auto ti-offcanvas ti-offcanvas-right px-10" tabindex="-1">
            <div class="ti-offcanvas-header">
                <h3 class="ti-offcanvas-title">
                    Adjustment Modal
                </h3>
                <button type="button" class="ti-btn flex-shrink-0 h-8 w-8 p-0 transition-none text-gray-500 hover:text-gray-700 focus:ring-gray-400 focus:ring-offset-white dark:text-white/70 dark:hover:text-white/80 dark:focus:ring-white/10 dark:focus:ring-offset-white/10" data-hs-overlay="#adjustment-modal">
                    <span class="sr-only">Close modal</span>
                    <svg class="w-3.5 h-3.5" width="8" height="8" viewBox="0 0 8 8" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M0.258206 1.00652C0.351976 0.912791 0.479126 0.860131 0.611706 0.860131C0.744296 0.860131 0.871447 0.912791 0.965207 1.00652L3.61171 3.65302L6.25822 1.00652C6.30432 0.958771 6.35952 0.920671 6.42052 0.894471C6.48152 0.868271 6.54712 0.854471 6.61352 0.853901C6.67992 0.853321 6.74572 0.865971 6.80722 0.891111C6.86862 0.916251 6.92442 0.953381 6.97142 1.00032C7.01832 1.04727 7.05552 1.1031 7.08062 1.16454C7.10572 1.22599 7.11842 1.29183 7.11782 1.35822C7.11722 1.42461 7.10342 1.49022 7.07722 1.55122C7.05102 1.61222 7.01292 1.6674 6.96522 1.71352L4.31871 4.36002L6.96522 7.00648C7.05632 7.10078 7.10672 7.22708 7.10552 7.35818C7.10442 7.48928 7.05182 7.61468 6.95912 7.70738C6.86642 7.80018 6.74102 7.85268 6.60992 7.85388C6.47882 7.85498 6.35252 7.80458 6.25822 7.71348L3.61171 5.06702L0.965207 7.71348C0.870907 7.80458 0.744606 7.85498 0.613506 7.85388C0.482406 7.85268 0.357007 7.80018 0.264297 7.70738C0.171597 7.61468 0.119017 7.48928 0.117877 7.35818C0.116737 7.22708 0.167126 7.10078 0.258206 7.00648L2.90471 4.36002L0.258206 1.71352C0.164476 1.61976 0.111816 1.4926 0.111816 1.36002C0.111816 1.22744 0.164476 1.10028 0.258206 1.00652Z" fill="currentColor" />
                    </svg>
                </button>
            </div>
            <div class="ti-modal-body hidden mt-10" id="canvas_spinner">
                <div class="text-center spinner">
                    <div class="ti-spinner text-primary w-16 h-16" role="status" aria-label="loading"> <span class="sr-only">Loading...</span> </div>
                </div>
            </div>
            <div id="main-canvas">

            </div>
        </div>
    </div>
</x-app-layout>

<script>
    $(document).on('click', '#delete', function() {
        var id = $(this).data('id');
        $('#delete-modal form').attr('action', '/user/inventory_adjustments/' + id);
    });
</script>
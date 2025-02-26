<x-app-layout>
    <!-- Start::main-content -->
    <div class="main-content">
        <x-page-header title="Import Inventory Adjustment" page="user" subpage="edit" />

        <div class="box">
            <div class="box-header">
                <h5>{{ _lang('Import Inventory Adjustment') }}</h5>
            </div>
            <div class="box-body">
                <form method="post" class="validate" autocomplete="off" action="{{ route('inventory_adjustment.import.store') }}" enctype="multipart/form-data">
                    @csrf
                    <div class="grid grid-cols-12 gap-x-2 items-center">
                        <div class="lg:col-span-6 col-span-12">
                            <div class="form-group">
                                <label class="form-label">{{ _lang('Choose Excel File') }}</label>
                                <input type="file" class="dropify" name="import_file" id="import_file" data-allowed-file-extensions="xlsx xls" required>
                                <small class="text-muted">{{ _lang('Supported formats: xls, xlsx. Max file size 2MB') }}</small>
                            </div>
                        </div>

                        <div class="col-span-12">
                            <div class="form-group">
                                <label class="form-label">{{ _lang('File Contains Header Row?') }}</label>
                                <div class="flex items-center">
                                    <input type="checkbox" id="heading" name="heading" value="1" checked>
                                    <label for="heading" class="ml-2">{{ _lang('Yes, file contains header row') }}</label>
                                </div>
                            </div>
                        </div>

                        <div class="col-span-12 mt-4">
                            <div class="flex items-center space-x-2">
                                <x-primary-button type="submit" class="flex items-center">
                                    <i class="ri-upload-2-line mr-1"></i>
                                    {{ _lang('Upload and Continue') }}
                                </x-primary-button>

                                <a href="{{ route('inventory_adjustments.index') }}">
                                    <x-secondary-button>
                                        <i class="ri-arrow-left-line mr-1"></i>
                                        {{ _lang('Back to List') }}
                                    </x-secondary-button>
                                </a>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>

<script>
    $(document).ready(function() {
        // Initialize dropify
        $('.dropify').dropify({
            messages: {
                default: '{{ _lang("Drag and drop a file here or click") }}',
                replace: '{{ _lang("Drag and drop or click to replace") }}',
                remove: '{{ _lang("Remove") }}',
                error: '{{ _lang("Error occurred") }}'
            }
        });

        // Form submission handling
        $('form').on('submit', function(e) {
            e.preventDefault();
            var form = $(this);
            var submitButton = form.find('button[type="submit"]');

            $.ajax({
                url: form.attr('action'),
                method: 'POST',
                data: new FormData(this),
                processData: false,
                contentType: false,
                beforeSend: function() {
                    submitButton.prop('disabled', true).html('<i class="ri-loader-4-line animate-spin mr-1"></i>{{ _lang("Processing") }}');
                },
                success: function(response) {
                    if (response.success) {
                        window.location.href = '{{ route("inventory_adjustment.import.progress") }}';
                    } else {
                        alert(response.message || '{{ _lang("An error occurred") }}');
                        submitButton.prop('disabled', false).html('<i class="ri-upload-2-line mr-1"></i>{{ _lang("Upload and Continue") }}');
                    }
                },
                error: function(xhr) {
                    var errorMessage = xhr.responseJSON?.message || '{{ _lang("An error occurred") }}';
                    alert(errorMessage);
                    submitButton.prop('disabled', false).html('<i class="ri-upload-2-line mr-1"></i>{{ _lang("Upload and Continue") }}');
                },
                complete: function() {
                    submitButton.prop('disabled', false).html('<i class="ri-upload-2-line mr-1"></i>{{ _lang("Upload and Continue") }}');
                }
            });
        });
    });

    (function() {
        'use strict'
        
        // Initialize DataTables with sorting
        $('#datatables').DataTable({
            "order": [[0, "desc"]], // Sort by first column (Transaction Date) in descending order
            "pageLength": 25,
            "language": {
                "lengthMenu": "{{ _lang('Show') }} _MENU_ {{ _lang('entries') }}",
                "info": "{{ _lang('Showing') }} _START_ {{ _lang('to') }} _END_ {{ _lang('of') }} _TOTAL_ {{ _lang('entries') }}",
                "search": "{{ _lang('Search') }}",
                "paginate": {
                    "first": "{{ _lang('First') }}",
                    "last": "{{ _lang('Last') }}",
                    "next": "{{ _lang('Next') }}",
                    "previous": "{{ _lang('Previous') }}"
                }
            }
        });

        //To choose date
        flatpickr("#date2", {
            dateFormat: "d-m-Y",
            maxDate: "today",
        });
        flatpickr("#date1", {
            dateFormat: "d-m-Y",
        });
        //close
    })();
</script>

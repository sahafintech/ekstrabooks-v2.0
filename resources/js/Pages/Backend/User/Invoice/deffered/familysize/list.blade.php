<div class="ti-offcanvas-body grid grid-cols-12">
    <div class="col-span-12">
        <div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
            <table id="datatable" class="ti-custom-table ti-custom-table-head">
                <thead>
                    <tr>
                        <th>{{ _lang('Size') }}</th>
                        <th>{{ _lang('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="family-sizes-list">
                    @foreach($sizes as $size)
                    <tr data-id="{{ $size->id }}">
                        <td class="editable" data-field="size">{{ $size->size }}</td>
                        <td>
                            <button class="save-size hidden" title="Save">
                                <i class="ri-save-line text-success text-lg"></i>
                            </button>
                            <button class="cancel-size hidden" title="Cancel">
                                <i class="ri-close-line text-warning text-lg"></i>
                            </button>
                            <button class="delete-size" title="Delete">
                                <i class="ri-delete-bin-line text-danger text-lg"></i>
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="mb-4">
    <button id="add-size" class="btn btn-primary">
        <i class="ri-add-line"></i> Add New Size
    </button>
</div>

<script>
$(document).ready(function() {
    // Store original content when editing starts
    let originalContent = null;

    // Make cells editable on click
    $(document).on('click', '.editable', function() {
        const cell = $(this);
        if (!cell.hasClass('editing')) {
            // Save original content
            originalContent = cell.text().trim();
            
            // Make cell editable
            cell.addClass('editing')
                .attr('contenteditable', 'true')
                .focus();
            
            // Show save/cancel buttons
            cell.closest('tr').find('.save-size, .cancel-size').removeClass('hidden');
        }
    });

    // Updated save button handler
    $(document).on('click', '.save-size', function() {
        const row = $(this).closest('tr');
        const id = row.data('id');
        
        // Validate field before saving
        const size = row.find('[data-field="size"]').text().trim();
        
        if (!size) {
            alert('Size is required');
            return;
        }
        
        const data = {
            id: id,
            size: size,
            _token: '{{ csrf_token() }}'
        };

        // Disable save button while processing
        $(this).prop('disabled', true);

        $.ajax({
            url: '{{ route("insurance_family_sizes.update", ":id") }}'.replace(':id', id),
            type: 'PUT',
            data: data,
            success: function(response) {
                // Remove editing state
                row.find('.editable').removeClass('editing').removeAttr('contenteditable');
                row.find('.save-size, .cancel-size').addClass('hidden');
                
                // Show success feedback
                row.addClass('bg-green-50');
                setTimeout(() => row.removeClass('bg-green-50'), 1000);
            },
            error: function(xhr) {
                console.error(xhr);
                alert('Error saving changes. Please try again.');
            },
            complete: function() {
                // Re-enable save button
                row.find('.save-size').prop('disabled', false);
            }
        });
    });

    // Handle cancel button
    $(document).on('click', '.cancel-size', function() {
        const row = $(this).closest('tr');
        
        // If this is a new row, remove it
        if (row.hasClass('new-size')) {
            row.remove();
            return;
        }

        // Restore original content
        row.find('.editing').text(originalContent)
            .removeClass('editing')
            .removeAttr('contenteditable');
        
        // Hide save/cancel buttons
        row.find('.save-size, .cancel-size').addClass('hidden');
    });

    // Add new row
    $('#add-size').off('click').on('click', function() {
        const newRow = `
            <tr class="new-size">
                <td class="editable" data-field="size" contenteditable="true"></td>
                <td>
                    <button class="save-new-size" title="Save">
                        <i class="ri-save-line text-success text-lg"></i>
                    </button>
                    <button class="cancel-size" title="Cancel">
                        <i class="ri-close-line text-warning text-lg"></i>
                    </button>
                </td>
            </tr>
        `;
        
        $('tbody.family-sizes-list').append(newRow);
        $('.new-size').find('[data-field="size"]').focus();
    });

    // Updated save new row handler
    $(document).on('click', '.save-new-size', function() {
        const row = $(this).closest('tr');
        
        // Validate field before saving
        const size = row.find('[data-field="size"]').text().trim();
        
        if (!size) {
            alert('Size is required');
            return;
        }
        
        const data = {
            size: size,
            _token: '{{ csrf_token() }}'
        };

        // Disable save button while processing
        $(this).prop('disabled', true);

        $.ajax({
            url: '{{ route("insurance_family_sizes.store") }}',
            type: 'POST',
            data: data,
            success: function(response) {
                // Update row with new ID and remove new-size class
                row.attr('data-id', response.id)
                   .removeClass('new-size');
                
                // Update buttons
                const actionButtons = `
                    <button class="save-size hidden" title="Save">
                        <i class="ri-save-line text-success text-lg"></i>
                    </button>
                    <button class="cancel-size hidden" title="Cancel">
                        <i class="ri-close-line text-warning text-lg"></i>
                    </button>
                    <button class="delete-size" title="Delete">
                        <i class="ri-delete-bin-line text-danger text-lg"></i>
                    </button>
                `;
                row.find('td:last').html(actionButtons);
                
                // Remove editing state
                row.find('.editable').removeClass('editing').removeAttr('contenteditable');
                
                // Show success feedback
                row.addClass('bg-green-50');
                setTimeout(() => row.removeClass('bg-green-50'), 1000);
            },
            error: function(xhr) {
                console.error(xhr);
                alert('Error saving new size. Please try again.');
            },
            complete: function() {
                // Re-enable save button
                row.find('.save-new-size').prop('disabled', false);
            }
        });
    });

    // Press Enter to save
    $(document).on('keypress', '.editable', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $(this).closest('tr').find('.save-size, .save-new-size').click();
        }
    });

    // Press Escape to cancel
    $(document).on('keyup', '.editable', function(e) {
        if (e.key === "Escape") {
            $(this).closest('tr').find('.cancel-size').click();
        }
    });

    // Handle delete button
    $(document).on('click', '.delete-size', function(e) {
        // Prevent default behavior
        e.preventDefault();
        
        const row = $(this).closest('tr');
        const id = row.data('id');

        if (confirm('Are you sure you want to delete this size?')) {
            $.ajax({
                url: '{{ route("insurance_family_sizes.destroy", ":id") }}'.replace(':id', id),
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                },
                success: function(response) {
                    row.remove();
                },
                error: function(xhr) {
                    console.error(xhr);
                    alert('Error deleting size. Please try again.');
                }
            });
        }
    });
});
</script>

<style>
.editable {
    cursor: pointer;
    white-space: normal;  /* Allow text wrapping */
    word-break: break-word;  /* Break long words if needed */
    text-align: left;  /* Align text to the left */
}
.editing {
    background-color: #fff;
    border: 1px solid #ddd;
    padding: 5px;
    outline: none;
}
</style>
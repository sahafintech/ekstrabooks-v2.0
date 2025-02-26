<div class="ti-offcanvas-body grid grid-cols-12">
    <div class="col-span-12">
        <div class="table-bordered rounded-sm ti-custom-table-head overflow-auto">
            <table id="datatable" class="ti-custom-table ti-custom-table-head">
                <thead>
                    <tr>
                        <th>{{ _lang('Invoice Category') }}</th>
                        <th>{{ _lang('Name') }}</th>
                        <th>{{ _lang('Action') }}</th>
                    </tr>
                </thead>
                <tbody class="benefits-list">
                    @foreach($benefits as $benefit)
                    <tr data-id="{{ $benefit->id }}">
                        <td class="editable" data-field="invoice_category">{{ $benefit->invoice_category }}</td>
                        <td class="editable" data-field="name">{{ $benefit->name }}</td>
                        <td>
                            <button class="save-row hidden" title="Save">
                                <i class="ri-save-line text-success text-lg"></i>
                            </button>
                            <button class="cancel-row hidden" title="Cancel">
                                <i class="ri-close-line text-warning text-lg"></i>
                            </button>
                            <button class="delete-row" title="Delete">
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
    <button id="add-row" class="btn btn-primary">
        <i class="ri-add-line"></i> Add New Benefit
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
            cell.closest('tr').find('.save-row, .cancel-row').removeClass('hidden');
        }
    });

    // Handle save button
    $(document).on('click', '.save-row', function() {
        const row = $(this).closest('tr');
        const id = row.data('id');
        
        // Validate fields before saving
        const invoiceCategory = row.find('[data-field="invoice_category"]').text().trim();
        const name = row.find('[data-field="name"]').text().trim();
        
        if (!invoiceCategory || !name) {
            alert('Both Invoice Category and Name are required');
            return;
        }
        
        const data = {
            id: id,
            invoice_category: invoiceCategory,
            name: name,
            _token: '{{ csrf_token() }}'
        };

        // Disable save button while processing
        $(this).prop('disabled', true);

        $.ajax({
            url: '{{ route("insurance_benefits.update", ":id") }}'.replace(':id', id),
            type: 'PUT',
            data: data,
            success: function(response) {
                // Remove editing state
                row.find('.editable').removeClass('editing').removeAttr('contenteditable');
                row.find('.save-row, .cancel-row').addClass('hidden');
                
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
                row.find('.save-row').prop('disabled', false);
            }
        });
    });

    // Handle cancel button
    $(document).on('click', '.cancel-row', function() {
        const row = $(this).closest('tr');
        
        // If this is a new row, remove it
        if (row.hasClass('new-row')) {
            row.remove();
            return;
        }

        // Restore original content
        row.find('.editing').text(originalContent)
            .removeClass('editing')
            .removeAttr('contenteditable');
        
        // Hide save/cancel buttons
        row.find('.save-row, .cancel-row').addClass('hidden');
    });

    // Add new row
    $('#add-row').off('click').on('click', function() {
        const newRow = `
            <tr class="new-row">
                <td class="editable" data-field="invoice_category" contenteditable="true"></td>
                <td class="editable" data-field="name" contenteditable="true"></td>
                <td>
                    <button class="save-new-row" title="Save">
                        <i class="ri-save-line text-success text-lg"></i>
                    </button>
                    <button class="cancel-row" title="Cancel">
                        <i class="ri-close-line text-warning text-lg"></i>
                    </button>
                </td>
            </tr>
        `;
        
        $('tbody.benefits-list').append(newRow);
        $('.new-row').find('[data-field="invoice_category"]').focus();
    });

    // Handle save new row
    $(document).on('click', '.save-new-row', function() {
        const row = $(this).closest('tr');
        
        // Validate fields before saving
        const invoiceCategory = row.find('[data-field="invoice_category"]').text().trim();
        const name = row.find('[data-field="name"]').text().trim();
        
        if (!invoiceCategory || !name) {
            alert('Both Invoice Category and Name are required');
            return;
        }
        
        const data = {
            invoice_category: invoiceCategory,
            name: name,
            _token: '{{ csrf_token() }}'
        };

        // Disable save button while processing
        $(this).prop('disabled', true);

        $.ajax({
            url: '{{ route("insurance_benefits.store") }}',
            type: 'POST',
            data: data,
            success: function(response) {
                // Update row with new ID and remove new-row class
                row.attr('data-id', response.id)
                   .removeClass('new-row');
                
                // Update buttons
                const actionButtons = `
                    <button class="save-row hidden" title="Save">
                        <i class="ri-save-line text-success text-lg"></i>
                    </button>
                    <button class="cancel-row hidden" title="Cancel">
                        <i class="ri-close-line text-warning text-lg"></i>
                    </button>
                    <button class="delete-row" title="Delete">
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
                alert('Error saving new benefit. Please try again.');
            },
            complete: function() {
                // Re-enable save button
                row.find('.save-new-row').prop('disabled', false);
            }
        });
    });

    // Press Enter to save
    $(document).on('keypress', '.editable', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $(this).closest('tr').find('.save-row, .save-new-row').click();
        }
    });

    // Press Escape to cancel
    $(document).on('keyup', '.editable', function(e) {
        if (e.key === "Escape") {
            $(this).closest('tr').find('.cancel-row').click();
        }
    });

    // Handle delete button
    $(document).on('click', '.delete-row', function(e) {
        // Prevent default behavior
        e.preventDefault();
        
        const row = $(this).closest('tr');
        const id = row.data('id');

        if (confirm('Are you sure you want to delete this benefit?')) {
            $.ajax({
                url: '{{ route("insurance_benefits.destroy", ":id") }}'.replace(':id', id),
                type: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    row.remove();
                },
                error: function(xhr) {
                    console.error(xhr);
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
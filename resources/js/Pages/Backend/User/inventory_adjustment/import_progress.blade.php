<x-app-layout>
@include('components.process-form', [
    'headingTitle' => 'Import Excel Inventory Adjustment',
    'processRoute' => route('inventory_adjustment.import.process'),
    'backRoute' => route('inventory_adjustments.index'),
    'backButtonText' => _lang('Back to Inventory Adjustment'),
    'queueName' => 'inventory-adjustment'
])
</x-app-layout>

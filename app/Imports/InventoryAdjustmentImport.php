<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class InventoryAdjustmentImport implements ToCollection, WithHeadingRow, WithValidation
{
    public static function fields(): array
    {
        return [
            ['key' => 'adjustment_date', 'name' => 'Adjustment Date', 'required' => true],
            ['key' => 'product_name', 'name' => 'Product Name', 'required' => true],
            ['key' => 'account_name', 'name' => 'Account Name', 'required' => true],
            ['key' => 'quantity_on_hand', 'name' => 'Quantity on Hand', 'required' => true],
            ['key' => 'adjusted_quantity', 'name' => 'Adjusted Quantity', 'required' => true],
            ['key' => 'description', 'name' => 'Description', 'required' => false],
        ];
    }

    public function collection(Collection $rows)
    {
        return $rows;
    }

    public function rules(): array
    {
        return [
            'adjustment_date' => ['required', 'date'],
            'product_name' => ['required', 'exists:products,name'],
            'account_name' => ['required', 'exists:accounts,account_name'],
            'quantity_on_hand' => ['required', 'numeric'],
            'adjusted_quantity' => ['required', 'numeric'],
            'description' => ['nullable', 'string'],
        ];
    }
}

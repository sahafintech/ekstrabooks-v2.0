<?php

namespace App\Imports;

use App\Models\Vendor;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class SupplierImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
        foreach($rows as $row) {
            $customer = new Vendor();
            $customer->name = trim($row['name']);
            $customer->company_name = trim($row['company_name']);
            $customer->email = trim($row['email']);
            $customer->mobile = trim($row['mobile']);
            $customer->country = trim($row['country']);
            $customer->vat_id = trim($row['vat_id']);
            $customer->registration_no = trim($row['registration_no']);
            $customer->city = trim($row['city']);
            $customer->contract_no = trim($row['contract_no']);
            $customer->address = trim($row['address']);
            $customer->profile_picture = $row['profile_picture'] ?? 'default.png';
            $customer->save();
        }
    }

    public function rules(): array
    {
        return [
            '*.name' => 'required',
            '*.email' => 'nullable|email|unique:vendors,email',
        ];
    }
}

<?php

namespace App\Imports;

use App\Models\Customer;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class CustomerImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
        foreach($rows as $row) {
            $customer = new Customer();
            $customer->name = $row['name'];
            $customer->company_name = $row['company_name'];
            $customer->email = $row['email'];
            $customer->mobile = $row['mobile'];
            $customer->country = $row['country'];
            $customer->vat_id = $row['vat_id'];
            $customer->reg_no = $row['reg_no'];
            $customer->city = $row['city'];
            $customer->contract_no = $row['contract_no'];
            $customer->address = $row['address'];
            $customer->profile_picture = $row['profile_picture'] ?? 'default.png';
            $customer->save();
        }
    }

    public function rules(): array
    {
        return [
            '*.name' => 'required',
            '*.email' => 'nullable|email|unique:customers,email',
        ];
    }
}

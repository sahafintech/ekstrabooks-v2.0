<?php

namespace App\Imports;

use App\Models\CostCode;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Auth;

class CostCodeImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
        foreach($rows as $row) {
            $cost_code = new CostCode();
            $cost_code->code = $row['code'];
            $cost_code->description = $row['description'];
            $cost_code->created_by = Auth::id();
            $cost_code->save();
        }
    }

    public function rules(): array
    {
        return [
            '*.code' => 'required',
            '*.description' => 'nullable',
        ];
    }
}

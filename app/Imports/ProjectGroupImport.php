<?php

namespace App\Imports;

use App\Models\ProjectGroup;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Illuminate\Support\Facades\Auth;

class ProjectGroupImport implements ToCollection, WithHeadingRow, WithValidation, SkipsEmptyRows
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $rows)
    {
        foreach($rows as $row) {
            $project_group = new ProjectGroup();
            $project_group->group_name = $row['group_name'];
            $project_group->description = $row['description'];
            $project_group->created_by = Auth::id();
            $project_group->save();
        }
    }

    public function rules(): array
    {
        return [
            '*.group_name' => 'required',
            '*.description' => 'nullable',
        ];
    }
}

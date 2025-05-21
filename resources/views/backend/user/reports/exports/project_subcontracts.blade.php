<table>
    <tr>
        <td style="background-color: lightgray; font-size: 12px">project_code</td>
        <td style="background-color: lightgray; font-size: 12px">supplier_name</td>
        <td style="background-color: lightgray; font-size: 12px">subcontract_no</td>
        <td style="background-color: lightgray; font-size: 12px">start_date</td>
        <td style="background-color: lightgray; font-size: 12px">end_date</td>
        <td style="background-color: lightgray; font-size: 12px">currency</td>
        <td style="background-color: lightgray; font-size: 12px">discount_type</td>
        <td style="background-color: lightgray; font-size: 12px">discount_value</td>
        <td style="background-color: lightgray; font-size: 12px">description</td>
        <td style="background-color: lightgray; font-size: 12px">project_task_code</td>
        <td style="background-color: lightgray; font-size: 12px">cost_code</td>
        <td style="background-color: lightgray; font-size: 12px">uom</td>
        <td style="background-color: lightgray; font-size: 12px">quantity</td>
        <td style="background-color: lightgray; font-size: 12px">unit_cost</td>
        <td style="background-color: lightgray; font-size: 12px">account_name</td>
    </tr>
    <tbody>
        @foreach($project_subcontracts as $project_subcontract)
        @foreach($project_subcontract->tasks as $task)
        <tr>
            <td>{{ $project_subcontract->project->project_code }}</td>
            <td>{{ $project_subcontract->vendor->name }}</td>
            <td>{{ $project_subcontract->subcontract_no }}</td>
            <td>{{ $project_subcontract->start_date }}</td>
            <td>{{ $project_subcontract->end_date }}</td>
            <td>{{ $project_subcontract->currency }}</td>
            <td>{{ $project_subcontract->discount_type }}</td>
            <td>{{ $project_subcontract->discount_value }}</td>
            <td>{{ $project_subcontract->description }}</td>
            <td>{{ $task->task->task_code }}</td>
            <td>{{ $task->cost_code->code }}</td>
            <td>{{ $task->uom }}</td>
            <td>{{ $task->quantity }}</td>
            <td>{{ $task->unit_cost }}</td>
            <td>{{ $task->account->account_name }}</td>
        </tr>
        @endforeach
        @endforeach
    </tbody>
</table>
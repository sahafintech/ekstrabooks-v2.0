<table>
    <tr>
        <td style="background-color: lightgray; font-size: 12px">project_code</td>
        <td style="background-color: lightgray; font-size: 12px">task_code</td>
        <td style="background-color: lightgray; font-size: 12px">cost_code</td>
        <td style="background-color: lightgray; font-size: 12px">description</td>
        <td style="background-color: lightgray; font-size: 12px">uom</td>
        <td style="background-color: lightgray; font-size: 12px">unit_rate</td>
        <td style="background-color: lightgray; font-size: 12px">original_budgeted_quantity</td>
        <td style="background-color: lightgray; font-size: 12px">original_budgeted_amount</td>
        <td style="background-color: lightgray; font-size: 12px">committed_budget_quantity</td>
        <td style="background-color: lightgray; font-size: 12px">committed_budget_amount</td>
        <td style="background-color: lightgray; font-size: 12px">received_budget_quantity</td>
        <td style="background-color: lightgray; font-size: 12px">received_budget_amount</td>
        <td style="background-color: lightgray; font-size: 12px">actual_budget_quantity</td>
        <td style="background-color: lightgray; font-size: 12px">actual_budget_amount</td>
    </tr>
    <tbody>
        @foreach($project_budgets as $project_budget)
        <tr>
            <td>{{ $project_budget->project->project_code }}</td>
            <td>{{ $project_budget->tasks->task_code }}</td>
            <td>{{ $project_budget->cost_codes->code }}</td>
            <td>{{ $project_budget->description }}</td>
            <td>{{ $project_budget->uom }}</td>
            <td>{{ $project_budget->unit_rate }}</td>
            <td>{{ $project_budget->original_budgeted_quantity }}</td>
            <td>{{ $project_budget->original_budgeted_amount }}</td>
            <td>{{ $project_budget->committed_budget_quantity }}</td>
            <td>{{ $project_budget->committed_budget_amount }}</td>
            <td>{{ $project_budget->received_budget_quantity }}</td>
            <td>{{ $project_budget->received_budget_amount }}</td>
            <td>{{ $project_budget->actual_budget_quantity }}</td>
            <td>{{ $project_budget->actual_budget_amount }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
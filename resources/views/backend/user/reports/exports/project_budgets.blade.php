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
        <td style="background-color: lightgray; font-size: 12px">revised_budgeted_quantity</td>
        <td style="background-color: lightgray; font-size: 12px">revised_budgeted_amount</td>
        <td style="background-color: lightgray; font-size: 12px">revised_committed_quantity</td>
        <td style="background-color: lightgray; font-size: 12px">revised_committed_amount</td>
        <td style="background-color: lightgray; font-size: 12px">committed_open_quantity</td>
        <td style="background-color: lightgray; font-size: 12px">committed_open_amount</td>
        <td style="background-color: lightgray; font-size: 12px">committed_received_quantity</td>
        <td style="background-color: lightgray; font-size: 12px">committed_invoiced_quantity</td>
        <td style="background-color: lightgray; font-size: 12px">committed_invoiced_amount</td>
        <td style="background-color: lightgray; font-size: 12px">actual_quantity</td>
        <td style="background-color: lightgray; font-size: 12px">actual_amount</td>
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
            <td>{{ $project_budget->revised_budgeted_quantity }}</td>
            <td>{{ $project_budget->revised_budgeted_amount }}</td>
            <td>{{ $project_budget->revised_committed_quantity }}</td>
            <td>{{ $project_budget->revised_committed_amount }}</td>
            <td>{{ $project_budget->committed_open_quantity }}</td>
            <td>{{ $project_budget->committed_open_amount }}</td>
            <td>{{ $project_budget->committed_received_quantity }}</td>
            <td>{{ $project_budget->committed_invoiced_quantity }}</td>
            <td>{{ $project_budget->committed_invoiced_amount }}</td>
            <td>{{ $project_budget->actual_quantity }}</td>
            <td>{{ $project_budget->actual_amount }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
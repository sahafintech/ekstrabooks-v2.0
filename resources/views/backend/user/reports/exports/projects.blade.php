<table>
    <tr>
        <td style="background-color: lightgray; font-size: 12px">project_code</td>
        <td style="background-color: lightgray; font-size: 12px">project_name</td>
        <td style="background-color: lightgray; font-size: 12px">project_group</td>
        <td style="background-color: lightgray; font-size: 12px">customer</td>
        <td style="background-color: lightgray; font-size: 12px">project_manager</td>
        <td style="background-color: lightgray; font-size: 12px">start_date</td>
        <td style="background-color: lightgray; font-size: 12px">end_date</td>
        <td style="background-color: lightgray; font-size: 12px">status</td>
        <td style="background-color: lightgray; font-size: 12px">priority</td>
        <td style="background-color: lightgray; font-size: 12px">completion_date</td>
        <td style="background-color: lightgray; font-size: 12px">project_currency</td>
        
    </tr>
    <tbody>
        @foreach($projects as $project)
        <tr>
            <td>{{ $project->project_code }}</td>
            <td>{{ $project->project_name }}</td>
            <td>{{ $project->project_group->group_name }}</td>
            <td>{{ $project->customer->name }}</td>
            <td>{{ $project->manager->name }}</td>
            <td>{{ $project->start_date }}</td>
            <td>{{ $project->end_date }}</td>
            <td>{{ $project->status }}</td>
            <td>{{ $project->priority }}</td>
            <td>{{ $project->completion_date }}</td>
            <td>{{ $project->project_currency }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
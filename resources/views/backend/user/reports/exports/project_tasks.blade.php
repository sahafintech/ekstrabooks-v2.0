<table>
    <tr>
        <td style="background-color: lightgray; font-size: 12px">task code</td>
        <td style="background-color: lightgray; font-size: 12px">description</td>
        <td style="background-color: lightgray; font-size: 12px">status</td>
        <td style="background-color: lightgray; font-size: 12px">completed_percent</td>
    </tr>
    <tbody>
        @foreach($project_tasks as $project_task)
        <tr>
            <td>{{ $project_task->task_code }}</td>
            <td>{{ $project_task->description }}</td>
            <td>{{ $project_task->status }}</td>
            <td>{{ $project_task->completed_percent }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
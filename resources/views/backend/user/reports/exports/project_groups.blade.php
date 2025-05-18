<table>
    <tr>
        <td style="background-color: lightgray; font-size: 12px">group_name</td>
        <td style="background-color: lightgray; font-size: 12px">description</td>
    </tr>
    <tbody>
        @foreach($project_groups as $project_group)
        <tr>
            <td>{{ $project_group->group_name }}</td>
            <td>{{ $project_group->description }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
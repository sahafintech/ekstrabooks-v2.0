<table>
    <tr>
        <td style="background-color: lightgray; font-size: 12px">code</td>
        <td style="background-color: lightgray; font-size: 12px">description</td>
    </tr>
    <tbody>
        @foreach($cost_codes as $cost_code)
        <tr>
            <td>{{ $cost_code->code }}</td>
            <td>{{ $cost_code->description }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
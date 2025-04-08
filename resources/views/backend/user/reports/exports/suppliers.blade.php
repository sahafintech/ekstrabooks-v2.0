<table>
    <tr>
        <td style="background-color: lightgray; font-size: 12px">name</td>
        <td style="background-color: lightgray; font-size: 12px">company_name</td>
        <td style="background-color: lightgray; font-size: 12px">email</td>
        <td style="background-color: lightgray; font-size: 12px">mobile</td>
        <td style="background-color: lightgray; font-size: 12px">country</td>
        <td style="background-color: lightgray; font-size: 12px">vat_id</td>
        <td style="background-color: lightgray; font-size: 12px">registration_no</td>
        <td style="background-color: lightgray; font-size: 12px">city</td>
        <td style="background-color: lightgray; font-size: 12px">contract_no</td>
        <td style="background-color: lightgray; font-size: 12px">address</td>
    </tr>
    <tbody>
        @foreach($vendors as $vendor)
        <tr>
            <td>{{ $vendor->name }}</td>
            <td>{{ $vendor->company_name }}</td>
            <td>{{ $vendor->email }}</td>
            <td>{{ $vendor->mobile }}</td>
            <td>{{ $vendor->country }}</td>
            <td>{{ $vendor->vat_id }}</td>
            <td>{{ $vendor->registration_no }}</td>
            <td>{{ $vendor->city }}</td>
            <td>{{ $vendor->contract_no }}</td>
            <td>{{ $vendor->address }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
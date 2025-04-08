<table>
    <tr>
        <td style="background-color: lightgray; font-size: 12px">name</td>
        <td style="background-color: lightgray; font-size: 12px">company_name</td>
        <td style="background-color: lightgray; font-size: 12px">email</td>
        <td style="background-color: lightgray; font-size: 12px">mobile</td>
        <td style="background-color: lightgray; font-size: 12px">country</td>
        <td style="background-color: lightgray; font-size: 12px">vat_id</td>
        <td style="background-color: lightgray; font-size: 12px">reg_no</td>
        <td style="background-color: lightgray; font-size: 12px">city</td>
        <td style="background-color: lightgray; font-size: 12px">contract_no</td>
        <td style="background-color: lightgray; font-size: 12px">address</td>
    </tr>
    <tbody>
        @foreach($customers as $customer)
        <tr>
            <td>{{ $customer->name }}</td>
            <td>{{ $customer->company_name }}</td>
            <td>{{ $customer->email }}</td>
            <td>{{ $customer->mobile }}</td>
            <td>{{ $customer->country }}</td>
            <td>{{ $customer->vat_id }}</td>
            <td>{{ $customer->reg_no }}</td>
            <td>{{ $customer->city }}</td>
            <td>{{ $customer->contract_no }}</td>
            <td>{{ $customer->address }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
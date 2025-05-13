<table>
    <tr>
        <td style="background-color: lightgray; font-size: 12px">adjustment_date</td>
        <td style="background-color: lightgray; font-size: 12px">product_name</td>
        <td style="background-color: lightgray; font-size: 12px">account_name</td>
        <td style="background-color: lightgray; font-size: 12px">quantity_on_hand</td>
        <td style="background-color: lightgray; font-size: 12px">adjusted_quantity</td>
        <td style="background-color: lightgray; font-size: 12px">new_quantity_on_hand</td>
        <td style="background-color: lightgray; font-size: 12px">description</td>
    </tr>
    <tbody>
        @foreach($adjustments as $adjustment)
        <tr>
            <td>{{ $adjustment->adjustment_date }}</td>
            <td>{{ $adjustment->product->name }}</td>
            <td>{{ $adjustment->account->account_name }}</td>
            <td>{{ $adjustment->quantity_on_hand }}</td>
            <td>{{ $adjustment->adjusted_quantity }}</td>
            <td>{{ $adjustment->new_quantity_on_hand }}</td>
            <td>{{ $adjustment->description }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
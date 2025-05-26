<table>
    <tr>
        <td style="background-color: lightgray; font-size: 12px">vendor_name</td>
        <td style="background-color: lightgray; font-size: 12px">title</td>
        <td style="background-color: lightgray; font-size: 12px">purchase_number</td>
        <td style="background-color: lightgray; font-size: 12px">purchase_date</td>
        <td style="background-color: lightgray; font-size: 12px">transaction_currency</td>
        <td style="background-color: lightgray; font-size: 12px">discount_type</td>
        <td style="background-color: lightgray; font-size: 12px">discount_value</td>
        <td style="background-color: lightgray; font-size: 12px">note</td>
        <td style="background-color: lightgray; font-size: 12px">product_name</td>
        <td style="background-color: lightgray; font-size: 12px">quantity</td>
        <td style="background-color: lightgray; font-size: 12px">unit_cost</td>
        <td style="background-color: lightgray; font-size: 12px">subtotal</td>
        <td style="background-color: lightgray; font-size: 12px">Approval Status</td>
    </tr>
    <tbody>
        @foreach($purchases as $purchase)
        @foreach($purchase->items as $item)
        <tr>
            <td>{{ $purchase->vendor->name }}</td>
            <td>{{ $purchase->title }}</td>
            <td>{{ $purchase->bill_no }}</td>
            <td>{{ $purchase->purchase_date }}</td>
            <td>{{ $purchase->currency }}</td>
            <td>{{ $purchase->discount_type }}</td>
            <td>{{ $purchase->discount_value }}</td>
            <td>{{ $purchase->note }}</td>
            <td>{{ $item->product_name }}</td>
            <td>{{ $item->quantity }}</td>
            <td>{{ $item->unit_cost }}</td>
            <td>{{ $item->quantity * $item->unit_cost }}</td>
            <td>{{ $purchase->approval_status == 1 ? 'Approved' : 'Pending' }}</td>
        </tr>
        @endforeach
        @endforeach
    </tbody>
</table>
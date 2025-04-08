<table>
    <tr>
        <td style="background-color: lightgray; font-size: 12px">supplier_name</td>
        <td style="background-color: lightgray; font-size: 12px">title</td>
        <td style="background-color: lightgray; font-size: 12px">order_number</td>
        <td style="background-color: lightgray; font-size: 12px">invoice_date</td>
        <td style="background-color: lightgray; font-size: 12px">due_date</td>
        <td style="background-color: lightgray; font-size: 12px">transaction_currency</td>
        <td style="background-color: lightgray; font-size: 12px">discount_type</td>
        <td style="background-color: lightgray; font-size: 12px">discount_value</td>
        <td style="background-color: lightgray; font-size: 12px">note</td>
        <td style="background-color: lightgray; font-size: 12px">product_name</td>
        <td style="background-color: lightgray; font-size: 12px">quantity</td>
        <td style="background-color: lightgray; font-size: 12px">unit_cost</td>
    </tr>
    <tbody>
        @foreach($purchases as $purchase)
        @foreach($purchase->items as $item)
        <tr>
            <td>{{ $purchase->vendor->name }}</td>
            <td>{{ $purchase->title }}</td>
            <td>{{ $purchase->po_so_number }}</td>
            <td>{{ $purchase->purchase_date }}</td>
            <td>{{ $purchase->due_date }}</td>
            <td>{{ $purchase->transaction_currency }}</td>
            <td>{{ $purchase->discount_type }}</td>
            <td>{{ $purchase->discount_value }}</td>
            <td>{{ $purchase->note }}</td>
            <td>{{ $item->product->name }}</td>
            <td>{{ $item->quantity }}</td>
            <td>{{ $item->unit_cost }}</td>
        </tr>
        @endforeach
        @endforeach
    </tbody>
</table>
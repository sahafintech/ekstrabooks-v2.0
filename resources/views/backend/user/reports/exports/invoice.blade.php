<table>
    <tr>
        <td style="background-color: lightgray; font-size: 12px">customer_name</td>
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
        @foreach($invoices as $invoice)
        @foreach($invoice->items as $item)
        <tr>
            <td>{{ $invoice->customer->name }}</td>
            <td>{{ $invoice->title }}</td>
            <td>{{ $invoice->order_number }}</td>
            <td>{{ $invoice->invoice_date }}</td>
            <td>{{ $invoice->due_date }}</td>
            <td>{{ $invoice->transaction_currency }}</td>
            <td>{{ $invoice->discount_type }}</td>
            <td>{{ $invoice->discount_value }}</td>
            <td>{{ $invoice->note }}</td>
            <td>{{ $item->product->name }}</td>
            <td>{{ $item->quantity }}</td>
            <td>{{ $item->unit_cost }}</td>
        </tr>
        @endforeach
        @endforeach
    </tbody>
</table>
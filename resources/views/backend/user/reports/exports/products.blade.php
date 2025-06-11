<table>
    <tr>
        <td style="background-color: lightgray; font-size: 12px">id</td>
        <td style="background-color: lightgray; font-size: 12px">name</td>
        <td style="background-color: lightgray; font-size: 12px">sub_category_id</td>
        <td style="background-color: lightgray; font-size: 12px">sub_category</td>
        <td style="background-color: lightgray; font-size: 12px">brand_id</td>
        <td style="background-color: lightgray; font-size: 12px">brand</td>
        <td style="background-color: lightgray; font-size: 12px">type</td>
        <td style="background-color: lightgray; font-size: 12px">unit</td>
        <td style="background-color: lightgray; font-size: 12px">purchase_cost</td>
        <td style="background-color: lightgray; font-size: 12px">selling_price</td>
        <td style="background-color: lightgray; font-size: 12px">descriptions</td>
        <td style="background-color: lightgray; font-size: 12px">expiry_date</td>
        <td style="background-color: lightgray; font-size: 12px">code</td>
        <td style="background-color: lightgray; font-size: 12px">reorder_point</td>
        <td style="background-color: lightgray; font-size: 12px">stock_management</td>
        <td style="background-color: lightgray; font-size: 12px">initial_stock</td>
        <td style="background-color: lightgray; font-size: 12px">stock_in</td>
        <td style="background-color: lightgray; font-size: 12px">stock_out</td>
        <td style="background-color: lightgray; font-size: 12px">stock_adjustment_added</td>
        <td style="background-color: lightgray; font-size: 12px">stock_adjustment_deducted</td>
        <td style="background-color: lightgray; font-size: 12px">stock_balance</td>
        <td style="background-color: lightgray; font-size: 12px">total_stock_cost</td>
        <td style="background-color: lightgray; font-size: 12px">income_account_name</td>
        <td style="background-color: lightgray; font-size: 12px">allow_for_selling</td>
        <td style="background-color: lightgray; font-size: 12px">expense_account_name</td>
        <td style="background-color: lightgray; font-size: 12px">allow_for_purchasing</td>
        <td style="background-color: lightgray; font-size: 12px">status</td>
    </tr>
    <tbody>
        @foreach($products as $product)
        <tr>
            <td>{{ $product->id }}</td>
            <td>{{ $product->name }}</td>
            <td>{{ $product->sub_category_id }}</td>
            <td>{{ $product->sub_category_name }}</td>
            <td>{{ $product->brand_id }}</td>
            <td>{{ $product->brand_name }}</td>
            <td>{{ $product->type }}</td>
            <td>{{ $product->product_unit_name }}</td>
            <td>{{ $product->purchase_cost }}</td>
            <td>{{ $product->selling_price }}</td>
            <td>{{ $product->descriptions }}</td>
            <td>{{ $product->expiry_date }}</td>
            <td>{{ $product->code }}</td>
            <td>{{ $product->reorder_point }}</td>
            <td>{{ $product->stock_management }}</td>
            <td>{{ $product->initial_stock }}</td>
            <td>{{ $product->total_stock_in }}</td>
            <td>{{ $product->total_sold_invoices + $product->total_sold_receipts }}</td>
            <td>{{ $product->total_stock_adjustment_added }}</td>
            <td>{{ $product->total_stock_adjustment_deducted }}</td>
            <td>{{ $product->stock }}</td>
            <td>{{ $product->stock * $product->purchase_cost }}</td>
            <td>{{ $product->income_account_name }}</td>
            <td>{{ $product->allow_for_selling }}</td>
            <td>{{ $product->expense_account_name }}</td>
            <td>{{ $product->allow_for_purchasing }}</td>
            <td>{{ $product->status }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
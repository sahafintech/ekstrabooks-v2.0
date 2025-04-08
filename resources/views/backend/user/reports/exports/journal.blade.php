<table>
    <tr>
        <td style="background-color: lightgray; font-size: 12px">date</td>
        <td style="background-color: lightgray; font-size: 12px">account_name</td>
        <td style="background-color: lightgray; font-size: 12px">description</td>
        <td style="background-color: lightgray; font-size: 12px">customer_name</td>
        <td style="background-color: lightgray; font-size: 12px">supplier_name</td>
        <td style="background-color: lightgray; font-size: 12px">debit</td>
        <td style="background-color: lightgray; font-size: 12px">credit</td>
    </tr>
    <tbody>
        @foreach($transactions as $transaction)
        <tr>
            <td>{{ $transaction->trans_date }}</td>
            <td>{{ $transaction->account->account_name }}</td>
            <td>{{ $transaction->description }}</td>
            <td>{{ $transaction->customer->name ?? '' }}</td>
            <td>{{ $transaction->vendor->name ?? '' }}</td>
            <td>{{ $transaction->dr_cr == 'dr' ? $transaction->transaction_amount : '' }}</td>
            <td>{{ $transaction->dr_cr == 'cr' ? $transaction->transaction_amount : '' }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
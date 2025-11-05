@if(isset($report_data))
@php

$currency = request()->activeBusiness->currency;
$businessName = request()->activeBusiness->name;

$salesAndIncome = $report_data['sales_and_income'] ?? collect();
$netSales = $salesAndIncome->sum('cr_amount') - $salesAndIncome->sum('dr_amount');

$costOfSale = $report_data['cost_of_sale'] ?? collect();
$netCostOfSale = $costOfSale->sum('dr_amount') - $costOfSale->sum('cr_amount');

$directExpenses = $report_data['direct_expenses'] ?? collect();
$otherExpenses = $report_data['other_expenses'] ?? collect();
$taxExpenses = $report_data['tax_expenses'] ?? collect();

$totalDirectExpenses = $directExpenses->sum('dr_amount') - $directExpenses->sum('cr_amount');
$totalOtherExpenses = $otherExpenses->sum('dr_amount') - $otherExpenses->sum('cr_amount');
$totalTaxExpenses = $taxExpenses->sum('dr_amount') - $taxExpenses->sum('cr_amount');
$totalExpenses = $totalDirectExpenses + $totalOtherExpenses;

$grossIncome = $netSales - $netCostOfSale;
$netIncomeBeforeTax = $grossIncome - $totalExpenses;
$netIncomeAfterTax = $netIncomeBeforeTax - $totalTaxExpenses;

function calculateAccountAmount($account, $isDiscount = false) {
$drAmount = $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount');
$crAmount = $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount');
return $isDiscount ? $drAmount - $crAmount : $crAmount - $drAmount;
}
@endphp

{{-- Header Section --}}
<table>
    <tr>
        <td></td>
        <td style="text-align: center; font-size: 12px;">
            <b>{{ $businessName }}</b>
        </td>
    </tr>
    <tr>
        <td></td>
        <td style="text-align: center; font-size: 12px;">
            <b>Income Statement Report</b>
        </td>
    </tr>
    <tr>
        <td></td>
        <td style="text-align: center; font-size: 12px;">
            <b>{{ $date1->format(get_date_format()) }} - {{ $date2->format(get_date_format()) }}</b>
        </td>
    </tr>
    <tr>
        <td style="background-color: lightgray; font-size: 12px;">Account Name</td>
        <td style="background-color: lightgray; font-size: 12px;">Base Currency</td>
        <td style="background-color: lightgray; font-size: 12px;">Base Currency Amount</td>
    </tr>
</table>

{{-- Sales and Income Section --}}
@if($salesAndIncome->count() > 0)
<table>
    <tr>
        <td><b>Sales And Income</b></td>
    </tr>
    <tbody>
        @foreach($salesAndIncome as $account)
        <tr>
            <td>{{ $account->account_name }}</td>
            <td>{{ $currency }}</td>
            <td>
                @php
                $isSalesDiscount = $account->account_name == 'Sales Discount Allowed';
                $amount = calculateAccountAmount($account, $isSalesDiscount);
                @endphp
                {{ $amount }}
            </td>
        </tr>
        @endforeach

        <tr>
            <td style="background-color: lightgray; font-size: 12px;"><b>Net Sales</b></td>
            <td style="background-color: lightgray; font-size: 12px;">{{ $currency }}</td>
            <td style="background-color: lightgray;"><b>{{ $netSales }}</b></td>
        </tr>

        {{-- Show Gross Income if no Cost of Sale --}}
        @if($costOfSale->count() == 0)
        <tr>
            <td style="background-color: lightgray; font-size: 12px;"><b>Gross Income</b></td>
            <td style="background-color: lightgray; font-size: 12px;">{{ $currency }}</td>
            <td style="background-color: lightgray;"><b>{{ $grossIncome }}</b></td>
        </tr>
        @endif
    </tbody>
</table>
@endif

{{-- Cost of Sale Section --}}
@if($costOfSale->count() > 0)
<table>
    <tr>
        <td><b>Cost Of Sale</b></td>
    </tr>
    <tbody>
        @foreach($costOfSale as $account)
        <tr>
            <td>{{ $account->account_name }}</td>
            <td>{{ $currency }}</td>
            <td>
                @php
                $isPurchaseDiscount = $account->account_name == 'Purchase Discount Allowed';
                $amount = calculateAccountAmount($account, !$isPurchaseDiscount);
                @endphp
                {{ $amount }}
            </td>
        </tr>
        @endforeach

        <tr>
            <td style="background-color: lightgray; font-size: 12px;"><b>Net Cost Of Sale</b></td>
            <td style="background-color: lightgray;">{{ $currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;"><b>{{ $netCostOfSale }}</b></td>
        </tr>

        <tr>
            <td style="background-color: lightgray; font-size: 12px;"><b>Gross Income</b></td>
            <td style="background-color: lightgray;">{{ $currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;"><b>{{ $grossIncome }}</b></td>
        </tr>
    </tbody>
</table>
@endif

{{-- Direct Expenses Section --}}
@if($directExpenses->count() > 0)
<table>
    <tbody>
        <tr>
            <td><b>Direct Expenses</b></td>
        </tr>
        @foreach($directExpenses as $account)
        <tr>
            <td>{{ $account->account_name }}</td>
            <td>{{ $currency }}</td>
            <td>
                @php
                $amount = calculateAccountAmount($account, true);
                @endphp
                {{ $amount }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Other Expenses Section --}}
@if($otherExpenses->count() > 0)
<table>
    <tbody>
        <tr>
            <td><b>Other Expenses</b></td>
        </tr>
        @foreach($otherExpenses as $account)
        <tr>
            <td>{{ $account->account_name }}</td>
            <td>{{ $currency }}</td>
            <td>
                @php
                $amount = calculateAccountAmount($account, true);
                @endphp
                {{ $amount }}
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Total Expenses Section --}}
@if($directExpenses->count() > 0 || $otherExpenses->count() > 0)
<table>
    <tbody>
        <tr>
            <td style="background-color: lightgray; font-size: 12px;"><b>Total Expenses</b></td>
            <td style="background-color: lightgray;">{{ $currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;"><b>{{ $totalExpenses }}</b></td>
        </tr>
    </tbody>
</table>
@endif

<table>
    <tbody>
        <tr>
            <td style="background-color: lightgray; font-size: 12px;"><b>Net Income Before Tax</b></td>
            <td style="background-color: lightgray;">{{ $currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;"><b>{{ $netIncomeBeforeTax }}</b></td>
        </tr>
    </tbody>
</table>


{{-- Tax Expenses Section --}}
@if($taxExpenses->count() > 0)
<table>
    <tbody>
        <tr>
            <td><b>Tax Expenses</b></td>
        </tr>
        @foreach($taxExpenses as $account)
        <tr>
            <td>{{ $account->account_name }}</td>
            <td>{{ $currency }}</td>
            <td>{{ calculateAccountAmount($account, true) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>
@endif

{{-- Tax Expenses Section --}}
@if($taxExpenses->count() > 0)
<table>
    <tbody>
        <tr>
            <td style="background-color: lightgray; font-size: 12px;"><b>Total Tax Expenses</b></td>
            <td style="background-color: lightgray;">{{ $currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;"><b>{{ $totalTaxExpenses }}</b></td>
        </tr>
    </tbody>
</table>
@endif

{{-- Net Income Section --}}
@if($directExpenses->count() > 0 || $otherExpenses->count() > 0 || ($grossIncome != 0 && $costOfSale->count() == 0))
<table>
    <tbody>
        <tr>
            <td style="background-color: lightgray; font-size: 12px;"><b>Net Income After Tax</b></td>
            <td style="background-color: lightgray;">{{ $currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;"><b>{{ $netIncomeAfterTax }}</b></td>
        </tr>
    </tbody>
</table>
@endif
@endif
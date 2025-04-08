@if(isset($report_data))
<table>
    <tr>
        <td></td>
        <td style="text-align: center; font-size: 12px;">
            <b>{{ request()->activeBusiness->name }}</b>
        </td>
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
        <td style="background-color: lightgray; font-size: 12px;">
            Base Currency Amount
        </td>
    </tr>
</table>
@if(count($report_data['sales_and_income']) > 0)
<table>
    <tr>
        <td>
            <b>Sales And Income</b>
        </td>
    </tr>
    <tbody>
        @foreach($report_data['sales_and_income'] as $index => $account)
        <tr>
            <td>{{ $account->account_name }}</td>
            <td>{{ request()->activeBusiness->currency }}</td>
            <td>
                @if($account->account_name == 'Sales Discount Allowed')
                {{ $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount') - $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount') }}
                @else
                {{ $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount') - $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount') }}
                @endif
            </td>
        </tr>
        @endforeach

        @if(count($report_data['cost_of_sale']) == 0)
        <tr>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>Gross Income</b>
            </td>
            <td style="background-color: lightgray; font-size: 12px;">{{ request()->activeBusiness->currency }}</td>
            <td style="background-color: lightgray;">
                <b>{{ $report_data['sales_and_income']->sum('cr_amount') - $report_data['sales_and_income']->sum('dr_amount')) - ($report_data['cost_of_sale']->sum('dr_amount') - $report_data['cost_of_sale']->sum('cr_amount') }}</b>
            </td>
        </tr>
        <tr>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>Net Income</b>
            </td>
            <td style="background-color: lightgray;">{{ request()->activeBusiness->currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ ($report_data['sales_and_income']->sum('cr_amount') - $report_data['sales_and_income']->sum('dr_amount')) - ($report_data['cost_of_sale']->sum('dr_amount') - $report_data['cost_of_sale']->sum('cr_amount'))) - ((($report_data['direct_expenses']->sum('dr_amount') - $report_data['direct_expenses']->sum('cr_amount'))) + (($report_data['other_expenses']->sum('dr_amount') - $report_data['other_expenses']->sum('cr_amount'))) }}</b>
            </td>
        </tr>
        @endif
    </tbody>
</table>
@endif
@if(count($report_data['cost_of_sale']) > 0)
<table>
    <tr>
        <td>
            <b>Cost Of Sale</b>
        </td>
    </tr>
    <tbody>
        @foreach($report_data['cost_of_sale'] as $index => $account)
        <tr>
            <td>{{ $account->account_name }}</td>
            <td>{{ request()->activeBusiness->currency }}</td>
            <td>
                @if($account->account_name == 'Purchase Discount Allowed')
                {{ $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount') - $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount') }}
                @else
                {{ $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount') - $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount') }}
                @endif
            </td>
        </tr>
        @endforeach
        <tr>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>Gross Income</b>
            </td>
            <td style="background-color: lightgray;">{{ request()->activeBusiness->currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ ($report_data['sales_and_income']->sum('cr_amount') - $report_data['sales_and_income']->sum('dr_amount')) - ($report_data['cost_of_sale']->sum('dr_amount') - $report_data['cost_of_sale']->sum('cr_amount')) }}</b>
            </td>
        </tr>

        @if(count($report_data['direct_expenses']) == 0)
        <tr>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>Net Income</b>
            </td>
            <td style="background-color: lightgray;">{{ request()->activeBusiness->currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ (($report_data['sales_and_income']->sum('cr_amount') - $report_data['sales_and_income']->sum('dr_amount')) - ($report_data['cost_of_sale']->sum('dr_amount') - $report_data['cost_of_sale']->sum('cr_amount'))) - ((($report_data['direct_expenses']->sum('dr_amount') - $report_data['direct_expenses']->sum('cr_amount'))) + (($report_data['other_expenses']->sum('dr_amount') - $report_data['other_expenses']->sum('cr_amount')))) }}</b>
            </td>
        </tr>
        @endif
    </tbody>
</table>
@endif
@if(count($report_data['direct_expenses']) > 0)
<table>
    <tbody>
        <tr>
            <td>
                <b>Direct Expenses</b>
            </td>
        </tr>
        @foreach($report_data['direct_expenses'] as $index => $account)
        <tr>
            <td>{{ $account->account_name }}</td>
            <td>{{ request()->activeBusiness->currency }}</td>
            <td>
                {{ $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount') - $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount') }}
            </td>
        </tr>
        @endforeach

        @if(count($report_data['other_expenses']) == 0)
        <tr>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>Net Income</b>
            </td>
            <td style="background-color: lightgray;">{{ request()->activeBusiness->currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ (($report_data['sales_and_income']->sum('cr_amount') - $report_data['sales_and_income']->sum('dr_amount')) - ($report_data['cost_of_sale']->sum('dr_amount') - $report_data['cost_of_sale']->sum('cr_amount'))) - ((($report_data['direct_expenses']->sum('dr_amount') - $report_data['direct_expenses']->sum('cr_amount'))) + (($report_data['other_expenses']->sum('dr_amount') - $report_data['other_expenses']->sum('cr_amount')))) }}</b>
            </td>
        </tr>
        @endif
    </tbody>
</table>
@endif
@if(count($report_data['other_expenses']) > 0)
<table>
    <tbody>
        <tr>
            <td>
                <b>Other Expenses</b>
            </td>
        </tr>
        @foreach($report_data['other_expenses'] as $index => $account)
        <tr>
            <td>{{ $account->account_name }}</td>
            <td>{{ request()->activeBusiness->currency }}</td>
            <td>
                {{ $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount') - $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount') }}
            </td>
        </tr>
        @endforeach
        <tr>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>Net Income</b>
            </td>
            <td style="background-color: lightgray;">{{ request()->activeBusiness->currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ (($report_data['sales_and_income']->sum('cr_amount') - $report_data['sales_and_income']->sum('dr_amount')) - ($report_data['cost_of_sale']->sum('dr_amount') - $report_data['cost_of_sale']->sum('cr_amount'))) - ((($report_data['direct_expenses']->sum('dr_amount') - $report_data['direct_expenses']->sum('cr_amount'))) + (($report_data['other_expenses']->sum('dr_amount') - $report_data['other_expenses']->sum('cr_amount')))) }}</b>
            </td>
        </tr>
    </tbody>
</table>
@endif
@endif
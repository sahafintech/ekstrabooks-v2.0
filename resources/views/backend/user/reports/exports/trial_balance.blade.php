@if(isset($report_data))
<table>
    <tr>
        <td></td>
        <td></td>
        <td style="text-align: center; font-size: 12px;">
            <b>{{ request()->activeBusiness->name }}</b>
        </td>
    <tr>
        <td></td>
        <td></td>
        <td style="text-align: center; font-size: 12px;">
            <b>Trial Balance Report</b>
        </td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td style="text-align: center; font-size: 12px;">
            <b>{{ $date1->format(get_date_format()) }} - {{ $date2->format(get_date_format()) }}</b>
        </td>
    </tr>
    <tr>
        <td style="background-color: lightgray; font-size: 12px;">Account #</td>
        <td style="background-color: lightgray; font-size: 12px;">Account Name</td>
        <td style="background-color: lightgray; font-size: 12px;">Base Currency</td>
        <td style="background-color: lightgray; font-size: 12px;">Debit</td>
        <td style="background-color: lightgray; font-size: 12px;">Credit</td>
        <td style="background-color: lightgray; font-size: 12px;">
            Base Currency Amount
        </td>
    </tr>
</table>
@if(count($report_data['fixed_asset']) > 0)
<table>
    <tr>
        <td></td>
        <td>
            <b>Fixed Assets</b>
        </td>
    </tr>
    <tbody>
        @foreach($report_data['fixed_asset'] as $index => $account)
        <tr>
            <td>{{ $account->account_code }}</td>
            <td>{{ $account->account_name }}</td>
            <td>{{ request()->activeBusiness->currency }}</td>
            <td>
                {{ $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount') }}
            </td>
            <td>
                {{ $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount') }}
            </td>
            <td>
                {{ $account->dr_amount - $account->cr_amount }}
            </td>
        </tr>
        @endforeach

        @if(count($report_data['current_asset']) == 0)
        <tr>
            <td style="background-color: lightgray; font-size: 12px;"></td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>Grand Total</b>
            </td>
            <td style="background-color: lightgray;">{{ request()->activeBusiness->currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_debit }}</b>
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_credit }}</b>
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_debit - $total_credit }}</b>
            </td>
        </tr>
        @endif
    </tbody>
</table>
@endif
@if(count($report_data['current_asset']) > 0)
<table>
    <tr>
        <td></td>
        <td>
            <b>Current Assets</b>
        </td>
    </tr>
    <tbody>
        @foreach($report_data['current_asset'] as $index => $account)
        <tr>
            <td>{{ $account->account_code }}</td>
            <td>{{ $account->account_name }}</td>
            <td>{{ request()->activeBusiness->currency }}</td>
            <td>
                {{ $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount') }}
            </td>
            <td>
                {{ $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount') }}
            </td>
            <td>
                {{ $account->dr_amount - $account->cr_amount }}
            </td>
        </tr>
        @endforeach

        @if(count($report_data['current_liability']) == 0)
        <tr>
            <td style="background-color: lightgray; font-size: 12px;"></td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>Grand Total</b>
            </td>
            <td style="background-color: lightgray;">{{ request()->activeBusiness->currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_debit }}</b>
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_credit }}</b>
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_debit - $total_credit }}</b>
            </td>
        </tr>
        @endif
    </tbody>
</table>
@endif
@if(count($report_data['current_liability']) > 0)
<table>
    <tr>
        <td></td>
        <td>
            <b>Current Liability</b>
        </td>
    </tr>
    <tbody>
        @foreach($report_data['current_liability'] as $index => $account)
        <tr>
            <td>{{ $account->account_code }}</td>
            <td>{{ $account->account_name }}</td>
            <td>{{ request()->activeBusiness->currency }}</td>
            <td>
                {{ $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount') }}
            </td>
            <td>
                {{ $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount') }}
            </td>
            <td>
                @if($account->account_name == 'Purchase Tax Payable')
                {{ $account->dr_amount - $account->cr_amount }}
                @else
                {{ $account->cr_amount - $account->dr_amount }}
                @endif
            </td>
        </tr>
        @endforeach

        @if(count($report_data['long_term_liability']) == 0 & count($report_data['equity']) == 0 & count($report_data['sales_and_income']) == 0 & count($report_data['cost_of_sale']) == 0 & count($report_data['direct_expenses']) == 0)
        <tr>
            <td style="background-color: lightgray; font-size: 12px;"></td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>Grand Total</b>
            </td>
            <td style="background-color: lightgray;">{{ request()->activeBusiness->currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_debit }}</b>
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_credit }}</b>
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_debit - $total_credit }}</b>
            </td>
        </tr>
        @endif
    </tbody>
</table>
@endif
@if(count($report_data['long_term_liability']) > 0)
<table>
    <tr>
        <td></td>
        <td>
            <b>Long Term Liability</b>
        </td>
    </tr>
    <tbody>
        @foreach($report_data['long_term_liability'] as $index => $account)
        <tr>
            <td>{{ $account->account_code }}</td>
            <td>{{ $account->account_name }}</td>
            <td>{{ request()->activeBusiness->currency }}</td>
            <td>
                {{ $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount') }}
            </td>
            <td>
                {{ $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount') }}
            </td>
            <td>
                {{ $account->cr_amount - $account->dr_amount }}
            </td>
        </tr>
        @endforeach

        @if(count($report_data['equity']) == 0 & count($report_data['sales_and_income']) == 0 & count($report_data['cost_of_sale']) == 0 & count($report_data['direct_expenses']) == 0)
        <tr>
            <td style="background-color: lightgray; font-size: 12px;"></td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>Grand Total</b>
            </td>
            <td style="background-color: lightgray;">{{ request()->activeBusiness->currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_debit }}</b>
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_credit }}</b>
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_debit - $total_credit }}</b>
            </td>
        </tr>
        @endif
    </tbody>
</table>
@endif
@if(count($report_data['equity']) > 0)
<table>
    <tr>
        <td></td>
        <td>
            <b>Equity</b>
        </td>
    </tr>
    <tbody>
        @foreach($report_data['equity'] as $index => $account)
        <tr>
            <td>{{ $account->account_code }}</td>
            <td>{{ $account->account_name }}</td>
            <td>{{ request()->activeBusiness->currency }}</td>
            <td>
                {{ $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount') }}
            </td>
            <td>
                {{ $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount') }}
            </td>
            <td>
                {{ $account->cr_amount - $account->dr_amount }}
            </td>
        </tr>
        @endforeach

        @if(count($report_data['sales_and_income']) == 0 & count($report_data['cost_of_sale']) == 0 & count($report_data['direct_expenses']) == 0)
        <tr>
            <td style="background-color: lightgray; font-size: 12px;"></td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>Grand Total</b>
            </td>
            <td style="background-color: lightgray;">{{ request()->activeBusiness->currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_debit }}</b>
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_credit }}</b>
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_debit - $total_credit }}</b>
            </td>
        </tr>
        @endif
    </tbody>
</table>
@endif
@if(count($report_data['sales_and_income']) > 0)
<table>
    <tr>
        <td></td>
        <td>
            <b>Sales And Income</b>
        </td>
    </tr>
    <tbody>
        @foreach($report_data['sales_and_income'] as $index => $account)
        <tr>
            <td>{{ $account->account_code }}</td>
            <td>{{ $account->account_name }}</td>
            <td>{{ request()->activeBusiness->currency }}</td>
            <td>
                {{ $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount') }}
            </td>
            <td>
                {{ $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount') }}
            </td>
            <td>
                @if($account->account_name == 'Sales Discount Allowed')
                {{ $account->dr_amount - $account->cr_amount }}
                @else
                {{ $account->cr_amount - $account->dr_amount }}
                @endif
            </td>
        </tr>
        @endforeach

        @if(count($report_data['cost_of_sale']) == 0 & count($report_data['direct_expenses']) == 0)
        <tr>
            <td style="background-color: lightgray; font-size: 12px;"></td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>Grand Total</b>
            </td>
            <td style="background-color: lightgray;">{{ request()->activeBusiness->currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_debit }}</b>
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_credit }}</b>
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_debit - $total_credit }}</b>
            </td>
        </tr>
        @endif
    </tbody>
</table>
@endif
@if(count($report_data['cost_of_sale']) > 0)
<table>
    <tr>
        <td></td>
        <td>
            <b>Cost Of Sale</b>
        </td>
    </tr>
    <tbody>
        @foreach($report_data['cost_of_sale'] as $index => $account)
        <tr>
            <td>{{ $account->account_code }}</td>
            <td>{{ $account->account_name }}</td>
            <td>{{ request()->activeBusiness->currency }}</td>
            <td>
                {{ $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount') }}
            </td>
            <td>
                {{ $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount') }}
            </td>
            <td>
                @if($account->account_name == 'Purchase Discount Allowed')
                {{ $account->cr_amount - $account->dr_amount }}
                @else
                {{ $account->dr_amount - $account->cr_amount }}
                @endif
            </td>
        </tr>
        @endforeach

        @if(count($report_data['direct_expenses']) == 0 & count($report_data['other_expenses']) == 0)
        <tr>
            <td style="background-color: lightgray; font-size: 12px;"></td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>Grand Total</b>
            </td>
            <td style="background-color: lightgray;">{{ request()->activeBusiness->currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_debit }}</b>
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_credit }}</b>
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_debit - $total_credit }}</b>
            </td>
        </tr>
        @endif
    </tbody>
</table>
@endif
@if(count($report_data['direct_expenses']) > 0)
<table>
    <tr>
        <td></td>
        <td>
            <b>Direct Expenses</b>
        </td>
    </tr>
    <tbody>
        @foreach($report_data['direct_expenses'] as $index => $account)
        <tr>
            <td>{{ $account->account_code }}</td>
            <td>{{ $account->account_name }}</td>
            <td>{{ request()->activeBusiness->currency }}</td>
            <td>
                {{ $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount') }}
            </td>
            <td>
                {{ $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount') }}
            </td>
            <td>
                {{ $account->dr_amount - $account->cr_amount }}
            </td>
        </tr>
        @endforeach

        @if(count($report_data['other_expenses']) == 0)
        <tr>
            <td style="background-color: lightgray; font-size: 12px;"></td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>Grand Total</b>
            </td>
            <td style="background-color: lightgray;">{{ request()->activeBusiness->currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_debit }}</b>
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_credit }}</b>
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_debit - $total_credit }}</b>
            </td>
        </tr>
        @endif
    </tbody>
</table>
@endif
@if(count($report_data['other_expenses']) > 0)
<table>
    <tr>
        <td></td>
        <td>
            <b>Other Expenses</b>
        </td>
    </tr>
    <tbody>
        @foreach($report_data['other_expenses'] as $index => $account)
        <tr>
            <td>{{ $account->account_code }}</td>
            <td>{{ $account->account_name }}</td>
            <td>{{ request()->activeBusiness->currency }}</td>
            <td>
                {{ $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount') }}
            </td>
            <td>
                {{ $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount') }}
            </td>
            <td>
                {{ $account->dr_amount - $account->cr_amount }}
            </td>
        </tr>
        @endforeach

        <tr>
            <td style="background-color: lightgray; font-size: 12px;"></td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>Grand Total</b>
            </td>
            <td style="background-color: lightgray;">{{ request()->activeBusiness->currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_debit }}</b>
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_credit }}</b>
            </td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_debit - $total_credit }}</b>
            </td>
        </tr>
    </tbody>
</table>
@endif
@endif
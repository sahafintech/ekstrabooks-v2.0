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
            <b>Balance Sheet Report</b>
        </td>
    </tr>
    <tr>
        <td></td>
        <td style="text-align: center; font-size: 12px;">
            <b>{{ _lang('As of') . $date2->format(get_date_format()) }}</b>
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
@if(count($report_data['fixed_asset']) > 0)
<table>
    <tr>
        <td>
            <b>Fixed Assets</b>
        </td>
    </tr>
    <tbody>
        @foreach($report_data['fixed_asset'] as $index => $account)
        <tr>
            <td>{{ $account->account_name }}</td>
            <td>{{ request()->activeBusiness->currency }}</td>
            <td>
                {{ $account->dr_amount - $account->cr_amount }}
            </td>
        </tr>
        @endforeach

        <tr>
            <td style="background-color: lightgray; font-size: 12px;"><b>{{ _lang('Total Of Fixed Assets') }}</b></td>
            <td style="background-color: lightgray;">{{ request()->activeBusiness->currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $report_data['fixed_asset']->sum('dr_amount') - $report_data['fixed_asset']->sum('cr_amount') }}</b>
            </td>
        </tr>

        @if(count($report_data['current_asset']) == 0)
        <tr>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>Total Asset</b>
            </td>
            <td style="background-color: lightgray;">{{ $request()->activeBusiness->currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_debit_asset - $total_credit_asset }}</b>
            </td>
        </tr>
        @endif
    </tbody>
</table>
@endif
@if(count($report_data['current_asset']) > 0)
<table>
    <tr>
        <td>
            <b>Current Assets</b>
        </td>
    </tr>
    <tbody>
        @foreach($report_data['current_asset'] as $index => $account)
        <tr>
            <td>{{ $account->account_name }}</td>
            <td>{{ request()->activeBusiness->currency }}</td>
            <td>
                {{ $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount') - $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount') }}
            </td>
        </tr>
        @endforeach

        <tr>
            <td style="background-color: lightgray; font-size: 12px;"><b>{{ _lang('Total Of Current Assets') }}</b></td>
            <td style="background-color: lightgray;">{{ request()->activeBusiness->currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $report_data['current_asset']->sum('dr_amount') - $report_data['current_asset']->sum('cr_amount') }}</b>
            </td>
        </tr>

        <tr>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ _lang('Total Of Assets') }}</b>
            </td>
            <td style="background-color: lightgray;">{{ request()->activeBusiness->currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $total_debit_asset - $total_credit_asset }}</b>
            </td>
        </tr>
    </tbody>
</table>
@endif
@if(count($report_data['current_liability']) > 0)
<table>
    <tr>
        <td>
            <b>Current Liability</b>
        </td>
    </tr>
    <tbody>
        @foreach($report_data['current_liability'] as $index => $account)
        <tr>
            <td>{{ $account->account_name }}</td>
            <td>{{ request()->activeBusiness->currency }}</td>
            <td>
                @if($account->account_name == 'Purchase Tax Payable')
                {{ $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount') - $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount') }}
                @else
                {{ $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount') - $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount') }}
                @endif
            </td>
        </tr>
        @endforeach

        <tr>
            <td style="background-color: lightgray; font-size: 12px;"><b>{{ _lang('Total Of Current Liability') }}</b></td>
            <td style="background-color: lightgray;">{{ request()->activeBusiness->currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $report_data['current_liability']->sum('cr_amount') - $report_data['current_liability']->sum('dr_amount') }}</b>
            </td>
        </tr>

        @if(count($report_data['equity']) == 0 & count($report_data['long_term_liability']) == 0)
        <tr>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>Total of Liability & Equity</b>
            </td>
            <td style="background-color: lightgray;">{{ request()->activeBusiness->currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ ($total_credit_liability - $total_debit_liability) + ($total_credit_equity - $total_debit_equity) + $income_statement }}</b>
            </td>
        </tr>
        @endif
    </tbody>
</table>
@endif
@if(count($report_data['long_term_liability']) > 0)
<table>
    <tr>
        <td>
            <b>Long Term Liability</b>
        </td>
    </tr>
    <tbody>
        @foreach($report_data['long_term_liability'] as $index => $account)
        <tr>
            <td>{{ $account->account_name }}</td>
            <td>{{ request()->activeBusiness->currency }}</td>
            <td>
                {{ $account->transactions->where('dr_cr', 'cr')->sum('base_currency_amount') - $account->transactions->where('dr_cr', 'dr')->sum('base_currency_amount') }}
            </td>
        </tr>
        @endforeach

        <tr>
            <td style="background-color: lightgray; font-size: 12px;"><b>{{ _lang('Total Of Long Term Liability') }}</b></td>
            <td style="background-color: lightgray;">{{ request()->activeBusiness->currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $report_data['long_term_liability']->sum('cr_amount') - $report_data['long_term_liability']->sum('dr_amount') }}</b>
            </td>
        </tr>

        @if(count($report_data['equity']) == 0)
        <tr>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>Total of Liability & Equity</b>
            </td>
            <td style="background-color: lightgray;">{{ request()->activeBusiness->currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ ($total_credit_liability - $total_debit_liability) + ($total_credit_equity - $total_debit_equity) + $income_statement }}</b>
            </td>
        </tr>
        @endif
    </tbody>
</table>
@endif
@if(count($report_data['equity']) > 0)
<table>
    <tr>
        <td>
            <b>Equity</b>
        </td>
    </tr>
    <tbody>
        @foreach($report_data['equity'] as $index => $account)
        <tr>
            <td>{{ $account->account_name }}</td>
            <td>{{ request()->activeBusiness->currency }}</td>
            <td>
                {{ $account->cr_amount - $account->dr_amount }}
            </td>
        </tr>
        @endforeach

        <tr>
            <td style="background-color: lightgray; font-size: 12px;"><b>{{ _lang('Total Of Equity') }}</b></td>
            <td style="background-color: lightgray;">{{ request()->activeBusiness->currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ $report_data['equity']->sum('cr_amount') - $report_data['equity']->sum('dr_amount') }}</b>
            </td>
        </tr>

        <tr>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ _lang('Total of Liability & Equity') }}</b>
            </td>
            <td style="background-color: lightgray;">{{ request()->activeBusiness->currency }}</td>
            <td style="background-color: lightgray; font-size: 12px;">
                <b>{{ ($total_credit_liability - $total_debit_liability) + ($total_credit_equity - $total_debit_equity) + $income_statement }}</b>
            </td>
        </tr>
    </tbody>
</table>
@endif
@endif
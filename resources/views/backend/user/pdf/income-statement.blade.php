@extends('backend.user.pdf.layout')

@section('content')
<div class="max-w-4xl mx-auto p-8">
    {{-- Header Section --}}
    <div class="text-center mb-6">
        <h1 class="text-lg font-bold">{{ $business_name }}</h1>
        <h2 class="text-base font-bold">Income Statement</h2>
        <p class="text-sm">From {{ $date1->format(get_date_format()) }} To {{ $date2->format(get_date_format()) }}</p>
    </div>

    <table class="w-full">
        <tbody>
        @php
            // Calculate totals
            $totalIncome = 0;
            if(isset($report_data['sales_and_income']) && count($report_data['sales_and_income']) > 0) {
                foreach($report_data['sales_and_income'] as $account) {
                    $totalIncome += ($account->cr_amount - $account->dr_amount);
                }
            }

            $totalCostOfSales = 0;
            if(isset($report_data['cost_of_sale']) && count($report_data['cost_of_sale']) > 0) {
                foreach($report_data['cost_of_sale'] as $account) {
                    $totalCostOfSales += ($account->dr_amount - $account->cr_amount);
                }
            }

            $grossProfit = $totalIncome - $totalCostOfSales;

            $totalDirectExpenses = 0;
            if(isset($report_data['direct_expenses']) && count($report_data['direct_expenses']) > 0) {
                foreach($report_data['direct_expenses'] as $account) {
                    $totalDirectExpenses += ($account->dr_amount - $account->cr_amount);
                }
            }

            $totalOtherExpenses = 0;
            if(isset($report_data['other_expenses']) && count($report_data['other_expenses']) > 0) {
                foreach($report_data['other_expenses'] as $account) {
                    $totalOtherExpenses += ($account->dr_amount - $account->cr_amount);
                }
            }

            $totalExpenses = $totalDirectExpenses + $totalOtherExpenses;
            $netProfitBeforeTax = $grossProfit - $totalExpenses;

            $taxExpenses = 0;
            if(isset($report_data['tax_expenses']) && count($report_data['tax_expenses']) > 0) {
                foreach($report_data['tax_expenses'] as $account) {
                    $taxExpenses += ($account->dr_amount - $account->cr_amount);
                }
            }

            $netProfitAfterTax = $netProfitBeforeTax - $taxExpenses;
        @endphp

        {{-- SALES AND INCOME SECTION --}}
        <tr>
            <td colspan="2" class="text-xs font-bold underline uppercase py-2">
                SALES AND INCOME
            </td>
        </tr>

        @if(isset($report_data['sales_and_income']) && count($report_data['sales_and_income']) > 0)
            @foreach($report_data['sales_and_income'] as $account)
                <tr>
                    <td class="text-[10px] py-0.5">{{ $account->account_name }}</td>
                    <td class="text-[10px] text-right py-0.5">{{ formatCurrency($account->cr_amount - $account->dr_amount, $currency) }}</td>
                </tr>
            @endforeach
        @endif

        <tr>
            <td class="text-[10px] font-bold text-right py-1 border-t border-black" style="border-bottom: 3px double black;">
                Net Sales
            </td>
            <td class="text-[10px] font-bold text-right py-1 border-t border-black" style="border-bottom: 3px double black;">
                {{ formatCurrency($totalIncome, $currency) }}
            </td>
        </tr>

        {{-- COST OF SALE SECTION --}}
        <tr>
            <td colspan="2" class="text-xs font-bold underline uppercase py-2">
                COST OF SALE
            </td>
        </tr>

        @if(isset($report_data['cost_of_sale']) && count($report_data['cost_of_sale']) > 0)
            @foreach($report_data['cost_of_sale'] as $account)
                <tr>
                    <td class="text-[10px] py-0.5">{{ $account->account_name }}</td>
                    <td class="text-[10px] text-right py-0.5">{{ formatCurrency($account->dr_amount - $account->cr_amount, $currency) }}</td>
                </tr>
            @endforeach
        @endif

        <tr>
            <td class="text-[10px] font-bold text-right py-1 border-t border-black" style="border-bottom: 3px double black;">
                Net Cost of Sale
            </td>
            <td class="text-[10px] font-bold text-right py-1 border-t border-black" style="border-bottom: 3px double black;">
                {{ formatCurrency($totalCostOfSales, $currency) }}
            </td>
        </tr>

        {{-- Spacer --}}
        <tr>
            <td colspan="2" class="py-2"></td>
        </tr>

        {{-- GROSS PROFIT --}}
        <tr>
            <td class="text-[10px] font-bold text-right py-1 border-t border-black" style="border-bottom: 3px double black;">
                Gross Profit
            </td>
            <td class="text-[10px] font-bold text-right py-1 border-t border-black" style="border-bottom: 3px double black;">
                {{ formatCurrency($grossProfit, $currency) }}
            </td>
        </tr>

        {{-- OTHER EXPENSES SECTION --}}
        <tr>
            <td colspan="2" class="text-xs font-bold underline uppercase py-2">
                OTHER EXPENSES
            </td>
        </tr>

        @if(isset($report_data['other_expenses']) && count($report_data['other_expenses']) > 0)
            @foreach($report_data['other_expenses'] as $account)
                <tr>
                    <td class="text-[10px] py-0.5">{{ $account->account_name }}</td>
                    <td class="text-[10px] text-right py-0.5">{{ formatCurrency($account->dr_amount - $account->cr_amount, $currency) }}</td>
                </tr>
            @endforeach
        @endif

        {{-- DIRECT EXPENSES SECTION (if needed) --}}
        @if(isset($report_data['direct_expenses']) && count($report_data['direct_expenses']) > 0)
            <tr>
                <td colspan="2" class="text-xs font-bold underline uppercase py-2">
                    DIRECT EXPENSES
                </td>
            </tr>

            @foreach($report_data['direct_expenses'] as $account)
                <tr>
                    <td class="text-[10px] py-0.5">{{ $account->account_name }}</td>
                    <td class="text-[10px] text-right py-0.5">{{ formatCurrency($account->dr_amount - $account->cr_amount, $currency) }}</td>
                </tr>
            @endforeach
        @endif

        <tr>
            <td class="text-[10px] font-bold text-right py-1 border-t border-black" style="border-bottom: 3px double black;">
                Total Expenses
            </td>
            <td class="text-[10px] font-bold text-right py-1 border-t border-black" style="border-bottom: 3px double black;">
                {{ formatCurrency($totalExpenses, $currency) }}
            </td>
        </tr>

        {{-- Spacer --}}
        <tr>
            <td colspan="2" class="py-2"></td>
        </tr>

        {{-- NET INCOME BEFORE TAX --}}
        <tr>
            <td class="text-[10px] font-bold text-right py-1 border-t border-black" style="border-bottom: 3px double black;">
                Net Income Before Tax
            </td>
            <td class="text-[10px] font-bold text-right py-1 border-t border-black" style="border-bottom: 3px double black;">
                {{ formatCurrency($netProfitBeforeTax, $currency) }}
            </td>
        </tr>

        {{-- TAX EXPENSES SECTION (if needed) --}}
        @if(isset($report_data['tax_expenses']) && count($report_data['tax_expenses']) > 0)
            <tr>
                <td colspan="2" class="text-xs font-bold underline uppercase py-2">
                    TAX EXPENSES
                </td>
            </tr>

            @foreach($report_data['tax_expenses'] as $account)
                <tr>
                    <td class="text-[10px] py-0.5">{{ $account->account_name }}</td>
                    <td class="text-[10px] text-right py-0.5">{{ formatCurrency($account->dr_amount - $account->cr_amount, $currency) }}</td>
                </tr>
            @endforeach
        @endif

        <tr>
            <td class="text-[10px] font-bold text-right py-1 border-t border-black" style="border-bottom: 3px double black;">
                Total Tax Expenses
            </td>
            <td class="text-[10px] font-bold text-right py-1 border-t border-black" style="border-bottom: 3px double black;">
                {{ formatCurrency($taxExpenses, $currency) }}
            </td>
        </tr>

        {{-- Spacer --}}
        <tr>
            <td colspan="2" class="py-2"></td>
        </tr>

        {{-- NET INCOME AFTER TAX --}}
        <tr>
            <td class="text-[10px] font-bold text-right py-1 border-t border-black" style="border-bottom: 3px double black;">
                Net Income After Tax
            </td>
            <td class="text-[10px] font-bold text-right py-1 border-t border-black" style="border-bottom: 3px double black;">
                {{ formatCurrency($netProfitAfterTax, $currency) }}
            </td>
        </tr>
    </tbody>
</table>
</div>
@endsection


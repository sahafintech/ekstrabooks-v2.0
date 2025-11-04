<table>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td style="text-align: center; font-size: 14px;" colspan="2">
            <b>{{ $business_name }}</b>
        </td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td style="text-align: center; font-size: 13px;" colspan="2">
            <b>General Ledger</b>
        </td>
    </tr>
    <tr>
        <td></td>
        <td></td>
        <td></td>
        <td style="text-align: center; font-size: 12px;" colspan="2">
            <b>For the Period From {{ $date1->format(get_date_format()) }} to {{ $date2->format(get_date_format()) }}</b>
        </td>
    </tr>
    <tr>
        <td></td>
    </tr>
    <tr>
        <td style="background-color: #d1d5db; font-size: 11px; font-weight: bold; border: 1px solid #9ca3af;">{{ _lang('Account Name') }}</td>
        <td style="background-color: #d1d5db; font-size: 11px; font-weight: bold; border: 1px solid #9ca3af;">{{ _lang('Date') }}</td>
        <td style="background-color: #d1d5db; font-size: 11px; font-weight: bold; border: 1px solid #9ca3af;">{{ _lang('Reference') }}</td>
        <td style="background-color: #d1d5db; font-size: 11px; font-weight: bold; border: 1px solid #9ca3af;">{{ _lang('Type') }}</td>
        <td style="background-color: #d1d5db; font-size: 11px; font-weight: bold; border: 1px solid #9ca3af;">{{ _lang('Description') }}</td>
        <td style="background-color: #d1d5db; font-size: 11px; font-weight: bold; border: 1px solid #9ca3af; text-align: right;">{{ _lang('Debit Amount') }} ({{ $currency }})</td>
        <td style="background-color: #d1d5db; font-size: 11px; font-weight: bold; border: 1px solid #9ca3af; text-align: right;">{{ _lang('Credit Amount') }} ({{ $currency }})</td>
        <td style="background-color: #d1d5db; font-size: 11px; font-weight: bold; border: 1px solid #9ca3af; text-align: right;">{{ _lang('Balance') }} ({{ $currency }})</td>
    </tr>
    <tbody>
        @if(isset($report_data) && count($report_data) > 0)
            @foreach($report_data as $account)
                @php
                    // Calculate beginning balance
                    $beginning_balance = $account['dr_cr'] === 'dr' 
                        ? ($account['debit_amount'] - $account['credit_amount'])
                        : ($account['credit_amount'] - $account['debit_amount']);
                    
                    // Track running balance
                    $running_balance = $beginning_balance;
                @endphp
                
                {{-- Beginning Balance Row --}}
                <tr>
                    <td style="font-size: 10px; font-weight: 600; border: 1px solid #e5e7eb;">{{ $account['account_number'] }} - {{ $account['account_name'] }}</td>
                    <td style="font-size: 10px; border: 1px solid #e5e7eb;" colspan="4">Beginning Balance</td>
                    <td style="font-size: 10px; border: 1px solid #e5e7eb;"></td>
                    <td style="font-size: 10px; border: 1px solid #e5e7eb;"></td>
                    <td style="font-size: 10px; border: 1px solid #e5e7eb; text-align: right; font-weight: 500;">{{ number_format($beginning_balance, 2) }}</td>
                </tr>
                
                {{-- Transaction Rows --}}
                @foreach($account['transactions'] as $transaction)
                    @php
                        // Update running balance
                        if ($account['dr_cr'] === 'dr') {
                            $running_balance += $transaction->dr_cr === 'dr' ? $transaction->base_currency_amount : -$transaction->base_currency_amount;
                        } else {
                            $running_balance += $transaction->dr_cr === 'cr' ? $transaction->base_currency_amount : -$transaction->base_currency_amount;
                        }
                    @endphp
                    <tr>
                        <td style="font-size: 10px; border: 1px solid #e5e7eb;"></td>
                        <td style="font-size: 10px; border: 1px solid #e5e7eb;">{{ $transaction->trans_date }}</td>
                        <td style="font-size: 10px; border: 1px solid #e5e7eb;">{{ $transaction->ref_id ?? 'N/A' }}</td>
                        <td style="font-size: 10px; border: 1px solid #e5e7eb; text-transform: uppercase;">{{ $transaction->ref_type ?? 'N/A' }}</td>
                        <td style="font-size: 10px; border: 1px solid #e5e7eb;">{{ $transaction->description }}</td>
                        <td style="font-size: 10px; border: 1px solid #e5e7eb; text-align: right;">
                            @if($transaction->dr_cr == 'dr')
                                {{ number_format($transaction->base_currency_amount, 2) }}
                            @endif
                        </td>
                        <td style="font-size: 10px; border: 1px solid #e5e7eb; text-align: right;">
                            @if($transaction->dr_cr == 'cr')
                                {{ number_format($transaction->base_currency_amount, 2) }}
                            @endif
                        </td>
                        <td style="font-size: 10px; border: 1px solid #e5e7eb; text-align: right;">{{ number_format($running_balance, 2) }}</td>
                    </tr>
                @endforeach
                
                {{-- Account Total Row --}}
                <tr>
                    <td style="background-color: #f9fafb; font-size: 10px; border: 1px solid #e5e7eb;"></td>
                    <td style="background-color: #f9fafb; font-size: 10px; border: 1px solid #e5e7eb; font-weight: 500;" colspan="4">Total for {{ $account['account_number'] }} - {{ $account['account_name'] }}</td>
                    <td style="background-color: #f9fafb; font-size: 10px; border: 1px solid #e5e7eb; text-align: right; font-weight: 500;">{{ number_format($account['debit_amount'], 2) }}</td>
                    <td style="background-color: #f9fafb; font-size: 10px; border: 1px solid #e5e7eb; text-align: right; font-weight: 500;">{{ number_format($account['credit_amount'], 2) }}</td>
                    <td style="background-color: #f9fafb; font-size: 10px; border: 1px solid #e5e7eb; text-align: right; font-weight: 500;">{{ number_format($account['balance'], 2) }}</td>
                </tr>
            @endforeach
            
            {{-- Grand Total Row --}}
            <tr>
                <td style="background-color: #d1d5db; font-size: 10px; border: 2px solid #6b7280;"></td>
                <td style="background-color: #d1d5db; font-size: 10px; border: 2px solid #6b7280; font-weight: bold;" colspan="4">Grand Total</td>
                <td style="background-color: #d1d5db; font-size: 10px; border: 2px solid #6b7280; text-align: right; font-weight: bold;">{{ number_format($grand_total_debit, 2) }}</td>
                <td style="background-color: #d1d5db; font-size: 10px; border: 2px solid #6b7280; text-align: right; font-weight: bold;">{{ number_format($grand_total_credit, 2) }}</td>
                <td style="background-color: #d1d5db; font-size: 10px; border: 2px solid #6b7280; text-align: right; font-weight: bold;">{{ number_format($grand_total_balance, 2) }}</td>
            </tr>
        @else
            <tr>
                <td colspan="8" style="text-align: center; font-size: 11px; padding: 20px;">No data found.</td>
            </tr>
        @endif
    </tbody>
</table>
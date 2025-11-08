@extends('backend.user.pdf.layout')

@section('content')
<div class="max-w-full mx-auto p-6 bg-white">
    <!-- Header -->
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-2">Receivables Report</h1>
        <div class="text-sm text-gray-600">
            <p><strong>Business:</strong> {{ $business_name ?? 'N/A' }}</p>
            <p><strong>Period:</strong> {{ $date1 ?? 'N/A' }} to {{ $date2 ?? 'N/A' }}</p>
            <p><strong>Generated:</strong> {{ now()->format('M d, Y H:i:s') }}</p>
        </div>
    </div>

    <!-- Table -->
    <div class="border border-gray-200 rounded-lg overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Invoice Date
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Customer (Provider)
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Client
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Invoice Number
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Invoice Amount ({{ $currency ?? 'USD' }})
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Paid Amount ({{ $currency ?? 'USD' }})
                    </th>
                    <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Due Amount ({{ $currency ?? 'USD' }})
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Due Date
                    </th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                        Status
                    </th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @if(isset($report_data) && count($report_data) > 0)
                    @foreach($report_data as $item)
                    <tr class="hover:bg-gray-50">
                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ $item['invoice_date'] ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ $item['customer_name'] ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ $item['client_name'] ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ $item['invoice_number'] ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right">
                            {{ number_format($item['grand_total'] ?? 0, 2) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right">
                            {{ number_format($item['paid_amount'] ?? 0, 2) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900 text-right">
                            {{ number_format($item['due_amount'] ?? 0, 2) }}
                        </td>
                        <td class="px-4 py-3 text-sm text-gray-900">
                            {{ $item['due_date'] ?? 'N/A' }}
                        </td>
                        <td class="px-4 py-3 text-sm">
                            @php
                                $status = $item['status'] ?? 0;
                                $statusConfig = [
                                    0 => ['label' => 'Draft', 'class' => 'text-gray-500 bg-gray-100'],
                                    1 => ['label' => 'Unpaid', 'class' => 'text-red-700 bg-red-100'],
                                    2 => ['label' => 'Paid', 'class' => 'text-green-700 bg-green-100'],
                                    3 => ['label' => 'Partial Paid', 'class' => 'text-blue-700 bg-blue-100'],
                                ];
                                $currentStatus = $statusConfig[$status] ?? $statusConfig[0];
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $currentStatus['class'] }}">
                                {{ $currentStatus['label'] }}
                            </span>
                        </td>
                    </tr>
                    @endforeach
                    
                    <!-- Totals Row -->
                    <tr class="bg-gray-100 font-semibold border-t-2 border-gray-300">
                        <td class="px-4 py-4 text-sm text-gray-900 font-bold">
                            Total
                        </td>
                        <td class="px-4 py-4"></td>
                        <td class="px-4 py-4"></td>
                        <td class="px-4 py-4"></td>
                        <td class="px-4 py-4 text-sm text-gray-900 text-right font-bold">
                            {{ number_format($grand_total ?? 0, 2) }}
                        </td>
                        <td class="px-4 py-4 text-sm text-gray-900 text-right font-bold">
                            {{ number_format($paid_amount ?? 0, 2) }}
                        </td>
                        <td class="px-4 py-4 text-sm text-gray-900 text-right font-bold">
                            {{ number_format($due_amount ?? 0, 2) }}
                        </td>
                        <td class="px-4 py-4"></td>
                        <td class="px-4 py-4"></td>
                    </tr>
                @else
                    <tr>
                        <td colspan="9" class="px-4 py-8 text-center text-sm text-gray-500">
                            No data found for the selected period.
                        </td>
                    </tr>
                @endif
            </tbody>
        </table>
    </div>

    <!-- Footer -->
    <div class="mt-8 pt-4 border-t border-gray-200">
        <div class="flex justify-between items-center text-sm text-gray-600">
            <div>
                <p>Report generated on {{ now()->format('F j, Y \a\t g:i A') }}</p>
                @if(isset($report_data) && count($report_data) > 0)
                    <p>Total Records: {{ count($report_data) }}</p>
                @endif
            </div>
            <div class="text-right">
                <p>{{ $business_name ?? config('app.name') }}</p>
                <p>Receivables Report</p>
            </div>
        </div>
    </div>
</div>

@push('styles')
<style>
    @media print {
        body {
            margin: 0;
            padding: 0;
        }
        
        .max-w-full {
            max-width: none;
        }
        
        table {
            page-break-inside: auto;
        }
        
        tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }
        
        thead {
            display: table-header-group;
        }
        
        tfoot {
            display: table-footer-group;
        }
        
        .grid {
            display: block;
        }
        
        .grid > div {
            display: inline-block;
            width: 32%;
            margin-right: 1%;
        }
    }
    
    /* Ensure proper spacing and readability */
    .table-auto {
        table-layout: auto;
    }
    
    /* Status badge styling */
    .status-badge {
        display: inline-flex;
        align-items: center;
        padding: 0.25rem 0.625rem;
        border-radius: 9999px;
        font-size: 0.75rem;
        font-weight: 500;
        line-height: 1;
    }
</style>
@endpush
@endsection

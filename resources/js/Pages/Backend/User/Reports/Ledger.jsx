import React, { useState } from "react";
import { router, useForm } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { toast } from "sonner";
import {
    ReportTable,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/shared/ReportTable";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import { Input } from "@/Components/ui/input";
import { Toaster } from "@/Components/ui/toaster";
import PageHeader from "@/Components/PageHeader";
import { formatAmount, parseDateObject } from "@/lib/utils";
import DateTimePicker from "@/Components/DateTimePicker";

export default function Ledger({
    report_data,
    date1,
    date2,
    meta = {},
    filters = {},
    business_name,
    currency,
    grand_total_debit,
    grand_total_credit,
    grand_total_balance
}) {
    const [search, setSearch] = useState(filters.search || "");
    const [perPage, setPerPage] = useState(50);
    const [currentPage, setCurrentPage] = useState(meta.current_page || 1);

    const { data, setData, post, processing } = useForm({
        date1: parseDateObject(date1),
        date2: parseDateObject(date2)
    });

    const handleSearch = (e) => {
        e.preventDefault();
        const value = e.target.value;
        setSearch(value);

        router.get(
            route("reports.ledger"),
            { 
                search: value, 
                page: 1, 
                per_page: perPage,
                date1: data.date1,
                date2: data.date2
            },
            { preserveState: true }
        );
    };

    const handleGenerate = (e) => {
        e.preventDefault();
        post(route("reports.ledger"), {
            date1: data.date1,
            date2: data.date2,
            search: search,
            per_page: perPage,
            page: 1,
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.success("Report Generated successfully");
                setCurrentPage(1);
            },
        });
    };

    const handlePerPageChange = (value) => {
        setPerPage(value);
        router.get(
            route("reports.ledger"),
            { 
                search, 
                page: 1, 
                per_page: value,
                date1: data.date1,
                date2: data.date2
            },
            { preserveState: true }
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("reports.ledger"),
            { 
                search, 
                page, 
                per_page: perPage,
                date1: data.date1,
                date2: data.date2
            },
            { preserveState: true }
        );
    };

    // Flatten transactions for display with pre-calculated running balances
    const flattenTransactions = () => {
        const flattened = [];
        
        report_data.forEach(account => {
            // Calculate beginning balance
            const beginningBalance = account.dr_cr === 'dr' ? 
                (account.debit_amount - account.credit_amount) : 
                (account.credit_amount - account.debit_amount);

            // Add beginning balance row
            flattened.push({
                type: 'beginning_balance',
                account_id: account.id,
                account_number: account.account_number,
                account_name: account.account_name,
                account_dr_cr: account.dr_cr,
                account_debit_amount: account.debit_amount,
                account_credit_amount: account.credit_amount,
                account_balance: account.balance,
                beginning_balance: beginningBalance,
                show_account_name: true
            });

            // Track running balance for transactions
            let runningBalance = beginningBalance;

            // Add transactions with running balance
            account.transactions.forEach((transaction, index) => {
                // Update running balance based on transaction type
                if (account.dr_cr === 'dr') {
                    runningBalance += transaction.dr_cr === 'dr' ? transaction.base_currency_amount : -transaction.base_currency_amount;
                } else {
                    runningBalance += transaction.dr_cr === 'cr' ? transaction.base_currency_amount : -transaction.base_currency_amount;
                }

                flattened.push({
                    type: 'transaction',
                    account_id: account.id,
                    account_number: account.account_number,
                    account_name: account.account_name,
                    account_dr_cr: account.dr_cr,
                    account_debit_amount: account.debit_amount,
                    account_credit_amount: account.credit_amount,
                    running_balance: runningBalance,
                    show_account_name: false,
                    ...transaction
                });
            });

            // Add account total row
            flattened.push({
                type: 'account_total',
                account_id: account.id,
                account_number: account.account_number,
                account_name: account.account_name,
                account_debit_amount: account.debit_amount,
                account_credit_amount: account.credit_amount,
                account_balance: account.balance,
            });
        });

        return flattened;
    };

    const flatTransactions = flattenTransactions();

    const renderPageNumbers = () => {
        const totalPages = meta.last_page;
        const pages = [];
        const maxPagesToShow = 5;

        let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
        let endPage = startPage + maxPagesToShow - 1;

        if (endPage > totalPages) {
            endPage = totalPages;
            startPage = Math.max(1, endPage - maxPagesToShow + 1);
        }

        for (let i = startPage; i <= endPage; i++) {
            pages.push(
                <Button
                    key={i}
                    variant={i === currentPage ? "default" : "outline"}
                    size="sm"
                    onClick={() => handlePageChange(i)}
                    className="mx-1"
                >
                    {i}
                </Button>
            );
        }

        return pages;
    };

    const handlePrint = () => {
        // Create a new window for printing
        const printWindow = window.open('', '_blank', 'width=800,height=600');

        // Generate CSS for the print window
        const style = `
            <style>
                body { font-family: Arial, sans-serif; padding: 20px; }
                table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 10px; }
                th { background-color: #f2f2f2; font-weight: bold; }
                h2, h1 { text-align: center; margin-bottom: 10px; }
                .text-right { text-align: right; }
                .total-row { font-weight: bold; background-color: #f9f9f9; }
                .account-name { font-weight: 600; }
                .grand-total { 
                    font-weight: bold; 
                    background-color: #d1d5db; 
                    border-top: 2px solid #000;
                }
                .empty-cell { width: 180px; }
            </style>
        `;

        // Start building the HTML content for the print window
        let printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>General Ledger Report</title>
                ${style}
            </head>
            <body>
                <h1>${business_name}</h1>
                <h2>General Ledger</h2>
                <p style="text-align: center;">For the Period From ${data.date1} to ${data.date2}</p>
                <table>
                    <thead>
                        <tr>
                            <th class="empty-cell">Account Name</th>
                            <th>Date</th>
                            <th>Reference</th>
                            <th>Type</th>
                            <th>Description</th>
                            <th class="text-right">Debit Amount (${currency})</th>
                            <th class="text-right">Credit Amount (${currency})</th>
                            <th class="text-right">Balance (${currency})</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        // Add all flattened transactions
        if (flatTransactions.length > 0) {
            flatTransactions.forEach(row => {
                if (row.type === 'beginning_balance') {
                    printContent += `
                        <tr>
                            <td class="account-name">${row.account_number} - ${row.account_name}</td>
                            <td colspan="4">Beginning Balance</td>
                            <td class="text-right"></td>
                            <td class="text-right"></td>
                            <td class="text-right">${formatAmount(row.beginning_balance)}</td>
                        </tr>
                    `;
                } else if (row.type === 'transaction') {
                    printContent += `
                        <tr>
                            <td></td>
                            <td>${row.trans_date}</td>
                            <td>${row.ref_id || 'N/A'}</td>
                            <td style="text-transform: uppercase;">${row.ref_type || 'N/A'}</td>
                            <td>${row.description}</td>
                            <td class="text-right">${row.dr_cr === 'dr' ? formatAmount(row.base_currency_amount) : ''}</td>
                            <td class="text-right">${row.dr_cr === 'cr' ? formatAmount(row.base_currency_amount) : ''}</td>
                            <td class="text-right">${formatAmount(row.running_balance)}</td>
                        </tr>
                    `;
                } else if (row.type === 'account_total') {
                    printContent += `
                        <tr class="total-row">
                            <td></td>
                            <td colspan="4">Total for ${row.account_number} - ${row.account_name}</td>
                            <td class="text-right">${formatAmount(row.account_debit_amount)}</td>
                            <td class="text-right">${formatAmount(row.account_credit_amount)}</td>
                            <td class="text-right">${formatAmount(row.account_balance)}</td>
                        </tr>
                    `;
                }
            });

            // Add grand totals
            printContent += `
                <tr class="grand-total">
                    <td></td>
                    <td colspan="4">Grand Total</td>
                    <td class="text-right">${grand_total_debit}</td>
                    <td class="text-right">${grand_total_credit}</td>
                    <td class="text-right">${grand_total_balance}</td>
                </tr>
            `;
        } else {
            printContent += `
                <tr>
                    <td colspan="8" style="text-align: center;">No data found.</td>
                </tr>
            `;
        }

        printContent += `
                    </tbody>
                </table>
            </body>
            </html>
        `;

        // Write the content to the print window and trigger print
        printWindow.document.open();
        printWindow.document.write(printContent);
        printWindow.document.close();

        // Wait for content to load before printing
        setTimeout(() => {
            printWindow.print();
            // Close the window after printing
            printWindow.onafterprint = function () {
                printWindow.close();
            };
        }, 300);
    };

    const handleExport = () => {
        window.location.href = route('reports.ledger_export')
    }

    return (
        <AuthenticatedLayout>
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Ledger"
                        subpage="Report"
                        url="reports.ledger"
                    />
                    <div className="p-4">
                        <div className="flex flex-col justify-between items-start mb-6 gap-4">
                            <div className="flex flex-col md:flex-row gap-4">
                                <form onSubmit={handleGenerate} className="flex flex-col md:flex-row gap-4 w-full">
                                    <div className="flex items-center gap-2">
                                        <DateTimePicker
                                            value={data.date1}
                                            onChange={(date) => setData("date1", date)}
                                            className="md:w-1/2 w-full"
                                            required
                                        />

                                        <DateTimePicker
                                            value={data.date2}
                                            onChange={(date) => setData("date2", date)}
                                            className="md:w-1/2 w-full"
                                            required
                                        />
                                        <Button type="submit" disabled={processing}>{processing ? 'Generating...' : 'Generate'}</Button>
                                    </div>
                                </form>
                            </div>
                            <div className="flex flex-col md:flex-row gap-4 md:items-center">
                                <Input
                                    placeholder="Search account name or number..."
                                    value={search}
                                    onChange={(e) => handleSearch(e)}
                                    className="w-full md:w-80"
                                />
                            </div>
                        </div>

                        <div className="mb-4 flex flex-col md:flex-row gap-4 justify-between">
                            <div className="flex items-center gap-2 print-buttons">
                                <Button variant="outline" onClick={handlePrint}>
                                    Print
                                </Button>
                                <Button variant="outline" onClick={handleExport}>Export</Button>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="text-sm text-gray-500">Show</span>
                                <Select value={perPage.toString()} onValueChange={handlePerPageChange}>
                                    <SelectTrigger className="w-[80px]">
                                        <SelectValue placeholder="10" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="10">10</SelectItem>
                                        <SelectItem value="25">25</SelectItem>
                                        <SelectItem value="50">50</SelectItem>
                                        <SelectItem value="100">100</SelectItem>
                                    </SelectContent>
                                </Select>
                                <span className="text-sm text-gray-500">entries</span>
                            </div>
                        </div>

                        {report_data.length > 0 ? (
                            <div className="overflow-x-auto">
                                <ReportTable>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="!text-[10px] font-semibold w-[180px]">Account Name</TableHead>
                                            <TableHead className="!text-[10px] font-semibold">Date</TableHead>
                                            <TableHead className="!text-[10px] font-semibold">Reference</TableHead>
                                            <TableHead className="!text-[10px] font-semibold">Type</TableHead>
                                            <TableHead className="!text-[10px] font-semibold">Description</TableHead>
                                            <TableHead className="text-right !text-[10px] font-semibold">Debit Amount ({currency})</TableHead>
                                            <TableHead className="text-right !text-[10px] font-semibold">Credit Amount ({currency})</TableHead>
                                            <TableHead className="text-right !text-[10px] font-semibold">Balance ({currency})</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {flatTransactions.map((row) => {
                                            if (row.type === 'beginning_balance') {
                                                return (
                                                    <TableRow key={`beginning-${row.account_id}`}>
                                                        <TableCell className="!text-[10px] font-medium">
                                                            {row.account_number} - {row.account_name}
                                                        </TableCell>
                                                        <TableCell className="!text-[10px]" colSpan={4}>Beginning Balance</TableCell>
                                                        <TableCell className="!text-[10px]"></TableCell>
                                                        <TableCell className="!text-[10px]"></TableCell>
                                                        <TableCell className="text-right !text-[10px] font-medium">
                                                            {formatAmount(row.beginning_balance)}
                                                        </TableCell>
                                                    </TableRow>
                                                );
                                            } else if (row.type === 'transaction') {
                                                return (
                                                    <TableRow key={`transaction-${row.id}`}>
                                                        <TableCell className="!text-[10px]"></TableCell>
                                                        <TableCell className="!text-[10px]">{row.trans_date}</TableCell>
                                                        <TableCell className="!text-[10px]">{row.ref_id || 'N/A'}</TableCell>
                                                        <TableCell className="!text-[10px] uppercase">{row.ref_type || 'N/A'}</TableCell>
                                                        <TableCell className="!text-[10px]">{row.description}</TableCell>
                                                        <TableCell className="text-right !text-[10px]">
                                                            {row.dr_cr === 'dr' ? formatAmount(row.base_currency_amount) : ''}
                                                        </TableCell>
                                                        <TableCell className="text-right !text-[10px]">
                                                            {row.dr_cr === 'cr' ? formatAmount(row.base_currency_amount) : ''}
                                                        </TableCell>
                                                        <TableCell className="text-right !text-[10px]">
                                                            {formatAmount(row.running_balance)}
                                                        </TableCell>
                                                    </TableRow>
                                                );
                                            } else if (row.type === 'account_total') {
                                                return (
                                                    <TableRow key={`total-${row.account_id}`} className="bg-gray-50 font-medium">
                                                        <TableCell className="!text-[10px]"></TableCell>
                                                        <TableCell className="!text-[10px]" colSpan={4}>
                                                            Total for {row.account_number} - {row.account_name}
                                                        </TableCell>
                                                        <TableCell className="text-right !text-[10px]">{formatAmount(row.account_debit_amount)}</TableCell>
                                                        <TableCell className="text-right !text-[10px]">{formatAmount(row.account_credit_amount)}</TableCell>
                                                        <TableCell className="text-right !text-[10px]">{formatAmount(row.account_balance)}</TableCell>
                                                    </TableRow>
                                                );
                                            }
                                            return null;
                                        })}

                                        {/* Grand Total Row */}
                                        <TableRow className="bg-muted/50 font-bold border-t-2">
                                            <TableCell className="!text-[10px]"></TableCell>
                                            <TableCell className="!text-[10px]" colSpan={4}>Grand Total</TableCell>
                                            <TableCell className="text-right !text-[10px]">{grand_total_debit}</TableCell>
                                            <TableCell className="text-right !text-[10px]">{grand_total_credit}</TableCell>
                                            <TableCell className="text-right !text-[10px]">{grand_total_balance}</TableCell>
                                        </TableRow>
                                    </TableBody>
                                </ReportTable>
                            </div>
                        ) : (
                            <div className="rounded-md border">
                                <div className="py-24 text-center text-muted-foreground">
                                    No data found. Please adjust your search criteria.
                                </div>
                            </div>
                        )}

                        {report_data.length > 0 && meta.total > 0 && (
                            <div className="flex items-center justify-between mt-4">
                                <div className="text-sm text-gray-500">
                                    Showing {(currentPage - 1) * perPage + 1} to {Math.min(currentPage * perPage, meta.total)} of {meta.total} entries
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handlePageChange(1)}
                                        disabled={currentPage === 1}
                                    >
                                        First
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handlePageChange(currentPage - 1)}
                                        disabled={currentPage === 1}
                                    >
                                        Previous
                                    </Button>
                                    {renderPageNumbers()}
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handlePageChange(currentPage + 1)}
                                        disabled={currentPage === meta.last_page}
                                    >
                                        Next
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handlePageChange(meta.last_page)}
                                        disabled={currentPage === meta.last_page}
                                    >
                                        Last
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}

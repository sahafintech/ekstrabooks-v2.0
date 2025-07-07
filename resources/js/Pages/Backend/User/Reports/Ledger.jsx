import React, { useState } from "react";
import { Head, router, useForm } from "@inertiajs/react";
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
import { ChevronDown, ChevronRight } from "lucide-react";

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
    const [currentPage, setCurrentPage] = useState(1);
    const [expandedAccounts, setExpandedAccounts] = useState(new Set());

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

    const toggleAccountExpansion = (accountId) => {
        const newExpanded = new Set(expandedAccounts);
        if (newExpanded.has(accountId)) {
            newExpanded.delete(accountId);
        } else {
            newExpanded.add(accountId);
        }
        setExpandedAccounts(newExpanded);
    };

    const isAccountExpanded = (accountId) => {
        return expandedAccounts.has(accountId);
    };

    const expandAllAccounts = () => {
        const allAccountIds = report_data.map(account => account.id);
        setExpandedAccounts(new Set(allAccountIds));
    };

    const collapseAllAccounts = () => {
        setExpandedAccounts(new Set());
    };

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
                body { font-family: Arial, sans-serif; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                h2, h1 { text-align: center; margin-bottom: 20px; }
                .text-right { text-align: right; }
                .total-row { font-weight: bold; background-color: #f9f9f9; }
                .account-type { margin-top: 20px; font-weight: bold; font-size: 16px; }
                .account { margin-left: 20px; margin-top: 10px; }
                .transaction { margin-left: 40px; }
            </style>
        `;

        // Start building the HTML content for the print window
        let printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Ledger Report</title>
                ${style}
            </head>
            <body>
                <h1>${business_name}</h1>
                <h2>Ledger Report (${data.date1} - ${data.date2})</h2>
        `;

        // Add table for each account type
        if (report_data.length > 0) {
            printContent += `
                <table>
                    <thead>
                        <tr>
                            <th>Account</th>
                            <th class="text-right">Debit (${currency})</th>
                            <th class="text-right">Credit (${currency})</th>
                            <th class="text-right">Balance (${currency})</th>
                        </tr>
                    </thead>
                    <tbody>
            `;

            // Add accounts
            report_data.forEach(account => {
                printContent += `
                    <tr>
                        <td>${account.account_number} - ${account.account_name}</td>
                        <td class="text-right">${account.debit_amount}</td>
                        <td class="text-right">${account.credit_amount}</td>
                        <td class="text-right">${account.balance}</td>
                    </tr>
                `;

                // Add transactions
                if (account.transactions.length > 0) {
                    printContent += `
                            <tr>
                                <td colspan="4">
                                    <table class="transaction" style="width: 100%;">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Description</th>
                                                <th>Reference</th>
                                                <th class="text-right">Debit</th>
                                                <th class="text-right">Credit</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                        `;

                    account.transactions.forEach(transaction => {
                        printContent += `
                                <tr>
                                    <td>${transaction.trans_date}</td>
                                    <td>${transaction.description}</td>
                                    <td>${transaction.reference || 'N/A'}</td>
                                    <td class="text-right">${transaction.debit_amount}</td>
                                    <td class="text-right">${transaction.credit_amount}</td>
                                </tr>
                            `;
                    });

                    printContent += `
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        `;
                }
            });

            // Add account type totals
            printContent += `
                    <tr class="total-row">
                        <td>Total for ${accountType.type}</td>
                        <td class="text-right">${accountType.total_debit_formatted}</td>
                        <td class="text-right">${accountType.total_credit_formatted}</td>
                        <td class="text-right">${accountType.balance}</td>
                    </tr>
                `;

            printContent += `
                            </tbody>
                        </table>
                    </div>
                `;
            // Add grand totals row for the main table
            printContent += `
                <tr class="total-row">
                    <td>Grand Total</td>
                    <td class="text-right">${grand_total_debit}</td>
                    <td class="text-right">${grand_total_credit}</td>
                    <td class="text-right">${grand_total_balance}</td>
                </tr>
                </tbody>
                </table>
            `;
        } else {
            printContent += `
                <p style="text-align: center;">No data found.</p>
            `;
        }

        // Complete the HTML content
        printContent += `
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
                                <a download href={route('reports.ledger_export')}>
                                    <Button variant="outline">Export</Button>
                                </a>
                                {report_data.length > 0 && (
                                    <>
                                        <Button variant="outline" onClick={expandAllAccounts}>
                                            Expand All
                                        </Button>
                                        <Button variant="outline" onClick={collapseAllAccounts}>
                                            Collapse All
                                        </Button>
                                    </>
                                )}
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
                            <div className="p-4">
                                <ReportTable>
                                    <TableBody>
                                        {report_data.map((account) => (
                                            <React.Fragment key={account.id}>
                                                <TableRow className="bg-gray-200 !text-[10px]">
                                                    <TableCell>Account</TableCell>
                                                    <TableCell className="text-right !text-[10px]">Debit ({currency})</TableCell>
                                                    <TableCell className="text-right !text-[10px]">Credit ({currency})</TableCell>
                                                    <TableCell className="text-right !text-[10px]">Balance ({currency})</TableCell>
                                                </TableRow>
                                                <TableRow>
                                                    <TableCell className="bg-gray-100 !text-[10px]">
                                                        <div className="flex items-center gap-2">
                                                            {account.transactions.length > 0 && (
                                                                <Button
                                                                    variant="ghost"
                                                                    size="sm"
                                                                    onClick={() => toggleAccountExpansion(account.id)}
                                                                    className="h-6 w-6 p-0 hover:bg-gray-200"
                                                                >
                                                                    {isAccountExpanded(account.id) ? (
                                                                        <ChevronDown className="h-4 w-4" />
                                                                    ) : (
                                                                        <ChevronRight className="h-4 w-4" />
                                                                    )}
                                                                </Button>
                                                            )}
                                                            <div className="font-medium">
                                                                {account.account_number} - {account.account_name}
                                                                {account.transactions.length > 0 && (
                                                                    <span className="ml-2 text-xs text-gray-500">
                                                                        ({account.transactions.length} transactions)
                                                                    </span>
                                                                )}
                                                            </div>
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="text-right bg-gray-100 !text-[10px]">{formatAmount(account.debit_amount)}</TableCell>
                                                    <TableCell className="text-right bg-gray-100 !text-[10px]">{formatAmount(account.credit_amount)}</TableCell>
                                                    <TableCell className="text-right bg-gray-100 !text-[10px]">{formatAmount(account.balance)}</TableCell>
                                                </TableRow>

                                                {account.transactions.length > 0 && isAccountExpanded(account.id) && (
                                                    <TableRow>
                                                        <TableCell colSpan={4} className="p-0">
                                                            <div className="pl-8 pb-4">
                                                                <ReportTable>
                                                                    <TableHeader>
                                                                        <TableRow>
                                                                            <TableHead className="!text-[10px]">Date</TableHead>
                                                                            <TableHead className="!text-[10px]">Description</TableHead>
                                                                            <TableHead className="!text-[10px]">Type</TableHead>
                                                                            <TableHead className="!text-[10px]">Name</TableHead>
                                                                            <TableHead className="!text-[10px]">Transaction Currency</TableHead>
                                                                            <TableHead className="!text-[10px]">Transaction Currency[Debit]</TableHead>
                                                                            <TableHead className="!text-[10px]">Transaction Currency[Credit]</TableHead>
                                                                            <TableHead className="!text-[10px]">Currency Rate</TableHead>
                                                                            <TableHead className="!text-[10px]">Rate</TableHead>
                                                                            <TableHead className="!text-[10px]">Base Currency</TableHead>
                                                                            <TableHead className="!text-[10px]">Base Currency[Debit]</TableHead>
                                                                            <TableHead className="!text-[10px]">Base Currency[Credit]</TableHead>
                                                                        </TableRow>
                                                                    </TableHeader>
                                                                    <TableBody>
                                                                        {account.transactions.map((transaction) => (
                                                                            <TableRow key={transaction.id}>
                                                                                <TableCell className="!text-[10px]">{transaction.trans_date}</TableCell>
                                                                                <TableCell className="!text-[10px]">{transaction.description}</TableCell>
                                                                                <TableCell className="!text-[10px]">{transaction.ref_type === 'receipt' ? 'Cash Invoice' : transaction.ref_type}</TableCell>
                                                                                <TableCell className="!text-[10px]">{transaction.payee_name || 'N/A'}</TableCell>
                                                                                <TableCell className="!text-[10px]">{transaction.transaction_currency}</TableCell>
                                                                                <TableCell className="text-right !text-[10px]">{transaction.dr_cr === 'dr' ? formatAmount(transaction.transaction_amount) : 0}</TableCell>
                                                                                <TableCell className="text-right !text-[10px]">{transaction.dr_cr === 'cr' ? formatAmount(transaction.transaction_amount) : 0}</TableCell>
                                                                                <TableCell className="text-right !text-[10px]">{transaction.transaction_currency}</TableCell>
                                                                                <TableCell className="text-right !text-[10px]">{formatAmount(transaction.currency_rate)}</TableCell>
                                                                                <TableCell className="!text-[10px]">{currency}</TableCell>
                                                                                <TableCell className="text-right !text-[10px]">{transaction.dr_cr === 'dr' ? formatAmount(transaction.base_currency_amount) : 0}</TableCell>
                                                                                <TableCell className="text-right !text-[10px]">{transaction.dr_cr === 'cr' ? formatAmount(transaction.base_currency_amount) : 0}</TableCell>
                                                                            </TableRow>
                                                                        ))}
                                                                    </TableBody>
                                                                </ReportTable>
                                                            </div>
                                                        </TableCell>
                                                    </TableRow>
                                                )}
                                            </React.Fragment>
                                        ))}

                                        <TableRow className="bg-muted/50 font-medium">
                                            <TableCell className="!text-[10px]">Grand Total</TableCell>
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

import React, { useState } from "react";
import { Head, router, useForm } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { toast } from "sonner";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
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
import { parseDateObject } from "@/lib/utils";
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
    const [perPage, setPerPage] = useState(10);
    const [currentPage, setCurrentPage] = useState(1);



    const { data, setData, post, processing, errors, reset } = useForm({
        date1: parseDateObject(date1),
        date2: parseDateObject(date2)
    });

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(
            route("reports.ledger"),
            {
                search: search,
                per_page: perPage,
                page: 1
            },
            { preserveState: true }
        );
        setCurrentPage(1);
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
            { search, page: 1, per_page: value },
            { preserveState: true }
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("reports.ledger"),
            { search, page, per_page: perPage },
            { preserveState: true }
        );
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
                        <td class="text-right">${account.debit_amount_formatted}</td>
                        <td class="text-right">${account.credit_amount_formatted}</td>
                        <td class="text-right">${account.balance_formatted}</td>
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
                                    <td class="text-right">${transaction.debit_amount_formatted}</td>
                                    <td class="text-right">${transaction.credit_amount_formatted}</td>
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
                        <td class="text-right">${accountType.balance_formatted}</td>
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
            <Head title="Ledger Report" />
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
                                <form onSubmit={handleSearch} className="flex gap-2">
                                    <Input
                                        placeholder="Search account name or number..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="w-full md:w-80"
                                    />
                                    <Button type="submit">Search</Button>
                                </form>
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
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Account</TableHead>
                                            <TableHead className="text-right">Debit ({currency})</TableHead>
                                            <TableHead className="text-right">Credit ({currency})</TableHead>
                                            <TableHead className="text-right">Balance ({currency})</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {report_data.map((account) => (
                                            <React.Fragment key={account.id}>
                                                <TableRow>
                                                    <TableCell>
                                                        <div className="font-medium">
                                                            {account.account_number} - {account.account_name}
                                                        </div>
                                                    </TableCell>
                                                    <TableCell className="text-right">{account.debit_amount_formatted}</TableCell>
                                                    <TableCell className="text-right">{account.credit_amount_formatted}</TableCell>
                                                    <TableCell className="text-right">{account.balance_formatted}</TableCell>
                                                </TableRow>

                                                {account.transactions.length > 0 && (
                                                    <TableRow>
                                                        <TableCell colSpan={4} className="p-0">
                                                            <div className="pl-8 pb-4">
                                                                <Table>
                                                                    <TableHeader>
                                                                        <TableRow>
                                                                            <TableHead>Date</TableHead>
                                                                            <TableHead>Description</TableHead>
                                                                            <TableHead>Type</TableHead>
                                                                            <TableHead>Name</TableHead>
                                                                            <TableHead>Transaction Currency</TableHead>
                                                                            <TableHead className="text-right">Transaction Currency[Debit]</TableHead>
                                                                            <TableHead className="text-right">Transaction Currency[Credit]</TableHead>
                                                                            <TableHead>Currency Rate</TableHead>
                                                                            <TableHead>Rate</TableHead>
                                                                            <TableHead>Base Currency</TableHead>
                                                                            <TableHead className="text-right">Base Currency[Debit]</TableHead>
                                                                            <TableHead className="text-right">Base Currency[Credit]</TableHead>
                                                                        </TableRow>
                                                                    </TableHeader>
                                                                    <TableBody>
                                                                        {account.transactions.map((transaction) => (
                                                                            <TableRow key={transaction.id}>
                                                                                <TableCell>{transaction.trans_date}</TableCell>
                                                                                <TableCell>{transaction.description}</TableCell>
                                                                                <TableCell>{transaction.ref_type === 'receipt' ? 'Cash Invoice' : transaction.ref_type}</TableCell>
                                                                                <TableCell className="text-right">{transaction.payee_name || 'N/A'}</TableCell>
                                                                                <TableCell>{transaction.transaction_currency}</TableCell>
                                                                                <TableCell className="text-right">{transaction.dr_cr === 'dr' ? transaction.transaction_amount_formatted : 0}</TableCell>
                                                                                <TableCell className="text-right">{transaction.dr_cr === 'cr' ? transaction.transaction_amount_formatted : 0}</TableCell>
                                                                                <TableCell className="text-right">{transaction.currency}</TableCell>
                                                                                <TableCell className="text-right">{transaction.currency_rate}</TableCell>
                                                                                <TableCell>{currency}</TableCell>
                                                                                <TableCell className="text-right">{transaction.dr_cr === 'dr' ? transaction.base_currency_amount_formatted : 0}</TableCell>
                                                                                <TableCell className="text-right">{transaction.dr_cr === 'cr' ? transaction.base_currency_amount_formatted : 0}</TableCell>
                                                                            </TableRow>
                                                                        ))}
                                                                    </TableBody>
                                                                </Table>
                                                            </div>
                                                        </TableCell>
                                                    </TableRow>
                                                )}
                                            </React.Fragment>
                                        ))}

                                        <TableRow className="bg-muted/50 font-medium">
                                            <TableCell>Grand Total</TableCell>
                                            <TableCell className="text-right">{grand_total_debit}</TableCell>
                                            <TableCell className="text-right">{grand_total_credit}</TableCell>
                                            <TableCell className="text-right">{grand_total_balance}</TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
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

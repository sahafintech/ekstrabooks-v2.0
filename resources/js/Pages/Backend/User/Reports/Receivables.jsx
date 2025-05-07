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
import { formatCurrency, parseDateObject } from "@/lib/utils";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import DateTimePicker from "@/Components/DateTimePicker";

export default function Receivables({ report_data, date1, date2, meta = {}, filters = {}, business_name, currency, grand_total, paid_amount, due_amount, customers = [], customer_id = '' }) {
    const [search, setSearch] = useState(filters.search || "");
    const [perPage, setPerPage] = useState(10);
    const [currentPage, setCurrentPage] = useState(1);

    const { data, setData, post, processing } = useForm({
        date1: parseDateObject(date1),
        date2: parseDateObject(date2),
        customer_id: customer_id,
    });

    const handleGenerate = (e) => {
        e.preventDefault();
        post(route("reports.receivables"), {
            date1: data.date1,
            date2: data.date2,
            customer_id: data.customer_id,
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
            route("reports.receivables"),
            { search, page: 1, per_page: value },
            { preserveState: true }
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("reports.receivables"),
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

    const ItemStatusBadge = ({ status }) => {
        const statusMap = {
            0: { label: "Draft", className: "text-gray-500" },
            1: { label: "Unpaid", className: "text-red-500" },
            2: { label: "Paid", className: "text-green-500" },
            3: { label: "Partial Paid", className: "text-blue-500" },
        };

        return (
            <span className={statusMap[status].className}>
                {statusMap[status].label}
            </span>
        );
    };

    const handlePrint = () => {
        // Create a new window for printing
        const printWindow = window.open('', '_blank', 'width=800,height=600');

        // Generate CSS for the print window
        const style = `
            <style>
                body { font-family: Arial, sans-serif; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                h2, h1 { text-align: center; margin-bottom: 20px; }
                .text-right { text-align: right; }
                .total-row { font-weight: bold; background-color: #f9f9f9; }
            </style>
        `;

        // Start building the HTML content for the print window
        let printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Receivables</title>
                ${style}
            </head>
            <body>
                <h1>${business_name}</h1>
                <h2>Receivables (${data.date1} - ${data.date2})</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Customer</th>
                            <th class="text-right">Invoice Amount (${currency})</th>
                            <th class="text-right">Paid Amount (${currency})</th>
                            <th class="text-right">Due Amount (${currency})</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        // Add table rows from report_data
        if (report_data.length > 0) {
            report_data.forEach(item => {
                printContent += `
                    <tr>
                        <td>${item.customer_name || 'N/A'}</td>
                        <td class="text-right">${formatCurrency(item.total_income)}</td>
                        <td class="text-right">${formatCurrency(item.total_paid)}</td>
                        <td class="text-right">${formatCurrency(item.total_due)}</td>
                    </tr>
                `;
            });

            // Add totals row
            printContent += `
                <tr class="total-row">
                    <td>Total</td>
                    <td class="text-right">${formatCurrency(grand_total)}</td>
                    <td class="text-right">${formatCurrency(paid_amount)}</td>
                    <td class="text-right">${formatCurrency(due_amount)}</td>
                </tr>
            `;
        } else {
            printContent += `
                <tr>
                    <td colspan="4" style="text-align: center;">No data found.</td>
                </tr>
            `;
        }

        // Complete the HTML content
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

    return (
        <AuthenticatedLayout>
            <Head title="Receivables Report" />
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Receivables"
                        subpage="Report"
                        url="reports.receivables"
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
                                    </div>

                                    <div className="flex items-center gap-2 w-full md:w-72">
                                        <SearchableCombobox
                                            options={[
                                                { id: '', name: 'All Customers' },
                                                ...customers.map(customer => ({
                                                    id: customer.id.toString(),
                                                    name: customer.name,
                                                    details: customer.mobile || customer.email
                                                }))
                                            ]}
                                            value={data.customer_id}
                                            onChange={(value) => setData('customer_id', value)}
                                            className="w-full"
                                            placeholder="Select Customer"
                                        />
                                        <Button type="submit" disabled={processing}>{processing ? 'Generating...' : 'Generate'}</Button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div className="mb-4 flex flex-col md:flex-row gap-4 justify-between">
                            <div className="flex items-center gap-2 print-buttons">
                                <Button variant="outline" onClick={handlePrint}>
                                    Print
                                </Button>
                                <a download href="">
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

                        <div className="rounded-md border printable-table">
                            <ReportTable>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Invoice Date</TableHead>
                                        <TableHead>Customer(Provider)</TableHead>
                                        <TableHead>Client</TableHead>
                                        <TableHead>Invoice Number</TableHead>
                                        <TableHead className="text-right">Invoice Amount ({currency})</TableHead>
                                        <TableHead className="text-right">Paid Amount ({currency})</TableHead>
                                        <TableHead className="text-right">Due Amount ({currency})</TableHead>
                                        <TableHead>Due Date</TableHead>
                                        <TableHead>Status</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {report_data.length > 0 ? (
                                        <>
                                            {report_data.map((item) => (
                                                <TableRow key={item.customer_id}>
                                                    <TableCell>{item.invoice_date}</TableCell>
                                                    <TableCell>{item.customer_name || 'N/A'}</TableCell>
                                                    <TableCell>{item.client_name || 'N/A'}</TableCell>
                                                    <TableCell>{item.invoice_number || 'N/A'}</TableCell>
                                                    <TableCell className="text-right">{formatCurrency(item.grand_total)}</TableCell>
                                                    <TableCell className="text-right">{formatCurrency(item.paid_amount)}</TableCell>
                                                    <TableCell className="text-right">{formatCurrency(item.due_amount)}</TableCell>
                                                    <TableCell>{item.due_date}</TableCell>
                                                    <TableCell>{<ItemStatusBadge status={item.status} />}</TableCell>
                                                </TableRow>
                                            ))}
                                            <TableRow className="bg-muted/50 font-medium">
                                                <TableCell>Total</TableCell>
                                                <TableCell></TableCell>
                                                <TableCell></TableCell>
                                                <TableCell></TableCell>
                                                <TableCell className="text-right">{formatCurrency(grand_total)}</TableCell>
                                                <TableCell className="text-right">{formatCurrency(paid_amount)}</TableCell>
                                                <TableCell className="text-right">{formatCurrency(due_amount)}</TableCell>
                                                <TableCell></TableCell>
                                                <TableCell></TableCell>
                                            </TableRow>
                                        </>
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={9} className="h-24 text-center">
                                                No data found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </ReportTable>
                        </div>

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

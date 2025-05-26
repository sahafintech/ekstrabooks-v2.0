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
import { Toaster } from "@/Components/ui/toaster";
import PageHeader from "@/Components/PageHeader";
import { formatCurrency, parseDateObject } from "@/lib/utils";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import DateTimePicker from "@/Components/DateTimePicker";
import { ChevronUp, ChevronDown } from "lucide-react";
import { format } from 'date-fns';

export default function Payables({ auth, report_data, vendors, currency, grand_total, paid_amount, due_amount, business_name, date1, date2, vendor_id, meta, filters }) {
    const [search, setSearch] = useState(filters.search || '');
    const [sorting, setSorting] = useState(filters.sorting || { column: 'purchase_date', direction: 'desc' });

    const { data, setData, post, processing } = useForm({
        date1: parseDateObject(date1),
        date2: parseDateObject(date2),
        vendor_id: vendor_id,
    });

    const handleGenerate = (e) => {
        e.preventDefault();
        post(route('reports.payables'), {
            date1: data.date1,
            date2: data.date2,
            vendor_id: data.vendor_id,
            search,
            sorting,
            per_page: meta.per_page,
            preserveScroll: true,
            preserveState: true,
        });
    };

    const handlePerPageChange = (value) => {
        router.post(route('reports.payables'), {
            date1: data.date1,
            date2: data.date2,
            vendor_id: data.vendor_id,
            search,
            sorting,
            per_page: value,
        });
    };

    const handlePageChange = (page) => {
        router.post(route('reports.payables'), {
            date1: data.date1,
            date2: data.date2,
            vendor_id: data.vendor_id,
            search,
            sorting,
            per_page: meta.per_page,
            page,
        });
    };

    const exportPayables = () => {
        window.location.href = route('reports.payables_export');
    };

    const PayableStatusBadge = ({ status }) => {
        const statusMap = {
            0: {
                label: "Active",
                className: "text-blue-600 bg-blue-200 px-3 py-1 rounded text-xs",
            },
            1: {
                label: "Partial Paid",
                className:
                    "text-yellow-600 bg-yellow-200 px-3 py-1 rounded text-xs",
            },
            2: {
                label: "Paid",
                className: "text-green-600 bg-green-200 px-3 py-1 rounded text-xs",
            },
        };
    
        return (
            <span className={statusMap[status].className}>
                {statusMap[status].label}
            </span>
        );
    };

    const handleSort = (column) => {
        const newDirection = sorting.column === column && sorting.direction === 'asc' ? 'desc' : 'asc';
        setSorting({ column, direction: newDirection });
        router.post(route('reports.payables'), {
            date1: data.date1,
            date2: data.date2,
            vendor_id: data.vendor_id,
            search,
            sorting: { column, direction: newDirection },
            per_page: meta.per_page,
        });
    };

    const renderSortIcon = (column) => {
        if (sorting.column !== column) return null;
        return sorting.direction === 'asc' ? <ChevronUp className="h-4 w-4" /> : <ChevronDown className="h-4 w-4" />;
    };

    const renderPageNumbers = () => {
        const pages = [];
        for (let i = 1; i <= meta.last_page; i++) {
            pages.push(
                <Button
                    key={i}
                    variant={meta.current_page === i ? 'default' : 'outline'}
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
        const printWindow = window.open('', '_blank');
        const content = `
            <html>
                <head>
                    <title>Payables Report</title>
                    <style>
                        body { font-family: Arial, sans-serif; }
                        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f5f5f5; }
                        .header { text-align: center; margin-bottom: 20px; }
                        .totals { margin-top: 20px; }
                        .status-badge {
                            padding: 4px 8px;
                            border-radius: 12px;
                            font-size: 12px;
                            font-weight: 500;
                        }
                        .status-unpaid { background-color: #fee2e2; color: #991b1b; }
                        .status-paid { background-color: #dcfce7; color: #166534; }
                        .status-partial { background-color: #fef9c3; color: #854d0e; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <h1>${business_name}</h1>
                        <h2>Payables Report</h2>
                        <p>Period: ${format(new Date(date1), 'dd/MM/yyyy')} - ${format(new Date(date2), 'dd/MM/yyyy')}</p>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Purchase #</th>
                                <th>Vendor</th>
                                <th>Date</th>
                                <th>Due Date</th>
                                <th>Total</th>
                                <th>Paid</th>
                                <th>Due</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            ${report_data.map(item => `
                                <tr>
                                    <td>${item.purchase_number}</td>
                                    <td>${item.vendor_name}</td>
                                    <td>${format(new Date(item.purchase_date), 'dd/MM/yyyy')}</td>
                                    <td>${format(new Date(item.due_date), 'dd/MM/yyyy')}</td>
                                    <td>${currency} ${item.grand_total.toFixed(2)}</td>
                                    <td>${currency} ${item.paid_amount.toFixed(2)}</td>
                                    <td>${currency} ${item.due_amount.toFixed(2)}</td>
                                    <td>
                                        <span class="status-badge status-${item.status.toLowerCase().replace(' ', '-')}">
                                            ${item.status}
                                        </span>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                    <div class="totals">
                        <p><strong>Grand Total:</strong> ${currency} ${grand_total.toFixed(2)}</p>
                        <p><strong>Total Paid:</strong> ${currency} ${paid_amount.toFixed(2)}</p>
                        <p><strong>Total Due:</strong> ${currency} ${due_amount.toFixed(2)}</p>
                    </div>
                </body>
            </html>
        `;
        printWindow.document.write(content);
        printWindow.document.close();
        printWindow.print();
    };

    return (
        <AuthenticatedLayout
            user={auth.user}
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Payables Report</h2>}
        >
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Payables"
                        subpage="Report"
                        url="reports.payables"
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
                                                { id: '', name: 'All Vendors' },
                                                ...vendors.map(vendor => ({
                                                    id: vendor.id.toString(),
                                                    name: vendor.name,
                                                    details: vendor.mobile || vendor.email
                                                }))
                                            ]}
                                            value={data.vendor_id}
                                            onChange={(value) => setData('vendor_id', value)}
                                            className="w-full"
                                            placeholder="Select Vendor"
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
                                <Button variant="outline" onClick={exportPayables}>
                                    Export
                                </Button>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="text-sm text-gray-500">Show</span>
                                <Select value={meta.per_page.toString()} onValueChange={handlePerPageChange}>
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
                                        <TableHead onClick={() => handleSort("purchase_date")} className="cursor-pointer">
                                            Purchase Date {renderSortIcon("purchase_date")}
                                        </TableHead>
                                        <TableHead onClick={() => handleSort("vendor_name")} className="cursor-pointer">
                                            Vendor {renderSortIcon("vendor_name")}
                                        </TableHead>
                                        <TableHead onClick={() => handleSort("purchase_number")} className="cursor-pointer">
                                            Purchase Number {renderSortIcon("purchase_number")}
                                        </TableHead>
                                        <TableHead onClick={() => handleSort("grand_total")} className="cursor-pointer text-right">
                                            Purchase Amount ({currency}) {renderSortIcon("grand_total")}
                                        </TableHead>
                                        <TableHead onClick={() => handleSort("paid_amount")} className="cursor-pointer text-right">
                                            Paid Amount ({currency}) {renderSortIcon("paid_amount")}
                                        </TableHead>
                                        <TableHead onClick={() => handleSort("due_amount")} className="cursor-pointer text-right">
                                            Due Amount ({currency}) {renderSortIcon("due_amount")}
                                        </TableHead>
                                        <TableHead onClick={() => handleSort("due_date")} className="cursor-pointer">
                                            Due Date {renderSortIcon("due_date")}
                                        </TableHead>
                                        <TableHead onClick={() => handleSort("status")} className="cursor-pointer">
                                            Status {renderSortIcon("status")}
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {report_data.length > 0 ? (
                                        <>
                                            {report_data.map((item) => (
                                                <TableRow key={item.vendor_id}>
                                                    <TableCell>{item.purchase_date}</TableCell>
                                                    <TableCell>{item.vendor_name || 'N/A'}</TableCell>
                                                    <TableCell>{item.purchase_number || 'N/A'}</TableCell>
                                                    <TableCell className="text-right">{formatCurrency(item.grand_total)}</TableCell>
                                                    <TableCell className="text-right">{formatCurrency(item.paid_amount)}</TableCell>
                                                    <TableCell className="text-right">{formatCurrency(item.due_amount)}</TableCell>
                                                    <TableCell>{item.due_date}</TableCell>
                                                    <TableCell>{<PayableStatusBadge status={item.status} />}</TableCell>
                                                </TableRow>
                                            ))}
                                            <TableRow className="bg-muted/50 font-medium">
                                                <TableCell>Total</TableCell>
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
                                            <TableCell colSpan={8} className="h-24 text-center">
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
                                    Showing {(meta.current_page - 1) * meta.per_page + 1} to {Math.min(meta.current_page * meta.per_page, meta.total)} of {meta.total} entries
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handlePageChange(1)}
                                        disabled={meta.current_page === 1}
                                    >
                                        First
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handlePageChange(meta.current_page - 1)}
                                        disabled={meta.current_page === 1}
                                    >
                                        Previous
                                    </Button>
                                    {renderPageNumbers()}
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handlePageChange(meta.current_page + 1)}
                                        disabled={meta.current_page === meta.last_page}
                                    >
                                        Next
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handlePageChange(meta.last_page)}
                                        disabled={meta.current_page === meta.last_page}
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

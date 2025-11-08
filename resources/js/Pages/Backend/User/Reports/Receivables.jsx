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
import { Toaster } from "@/Components/ui/toaster";
import PageHeader from "@/Components/PageHeader";
import { formatCurrency, parseDateObject } from "@/lib/utils";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import DateTimePicker from "@/Components/DateTimePicker";
import { ChevronUp, ChevronDown } from "lucide-react";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";

export default function Receivables({ report_data, date1, date2, meta = {}, filters = {}, currency, grand_total, paid_amount, due_amount, customers = [], customer_id = '' }) {
    const [perPage, setPerPage] = useState(50);
    const [currentPage, setCurrentPage] = useState(1);
    const [sorting, setSorting] = useState(filters.sorting || { column: 'invoice_date', direction: 'desc' });

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
            per_page: perPage,
            page: 1,
            sorting: sorting,
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
            { page: 1, per_page: value, sorting: sorting },
            { preserveState: true }
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("reports.receivables"),
            { page, per_page: perPage, sorting: sorting },
            { preserveState: true }
        );
    };

    const handleSort = (column) => {
        let direction = "asc";
        if (sorting.column === column && sorting.direction === "asc") {
            direction = "desc";
        }
        setSorting({ column, direction });
        router.get(
            route("reports.receivables"),
            { ...filters, sorting: { column, direction } },
            { preserveState: true }
        );
    };

    const renderSortIcon = (column) => {
        const isActive = sorting.column === column;
        return (
            <span className="inline-flex flex-col ml-1">
                <ChevronUp
                    className={`w-3 h-3 ${isActive && sorting.direction === "asc" ? "text-gray-800" : "text-gray-300"}`}
                />
                <ChevronDown
                    className={`w-3 h-3 -mt-1 ${isActive && sorting.direction === "desc" ? "text-gray-800" : "text-gray-300"}`}
                />
            </span>
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

    const printStyles = `
        @media print {
                body * {
                    visibility: hidden;
                }

                #printable-area, #printable-area * {
                    visibility: visible;
                }

                #printable-area {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    margin: 0;
                    padding: 0;
                    border: none;
                    height: 100%;
                }

                .group.peer.hidden.text-sidebar-foreground {
                    display: none !important;
                }

                @page {
                    size: auto;
                    margin: 10mm;
                }

                body {
                    margin: 0;
                    padding: 0;
                }
            }
        `;

    const handleExport = (type) => {
        if (type === 'excel') {
            window.location.href = route("reports.receivables_export");
        } else if (type === 'pdf') {
            window.location.href = route("reports.receivables_pdf");
        }
    };

    const handlePrint = () => {
        window.print();
    };

    return (
        <AuthenticatedLayout>
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <style dangerouslySetInnerHTML={{ __html: printStyles }} />
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
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="outline">
                                            Export
                                            <ChevronDown className="ml-2 h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent>
                                        <DropdownMenuItem onClick={() => handleExport('excel')}>
                                            Excel
                                        </DropdownMenuItem>
                                        <DropdownMenuItem onClick={() => handleExport('pdf')}>
                                            PDF
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
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

                        <div id="printable-area" className="rounded-md border">
                            <ReportTable>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("invoice_date")}>
                                            Invoice Date {renderSortIcon("invoice_date")}
                                        </TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("customer_name")}>
                                            Customer(Provider) {renderSortIcon("customer_name")}
                                        </TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("client_name")}>
                                            Client {renderSortIcon("client_name")}
                                        </TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("invoice_number")}>
                                            Invoice Number {renderSortIcon("invoice_number")}
                                        </TableHead>
                                        <TableHead className="cursor-pointer text-right" onClick={() => handleSort("grand_total")}>
                                            Invoice Amount ({currency}) {renderSortIcon("grand_total")}
                                        </TableHead>
                                        <TableHead className="cursor-pointer text-right" onClick={() => handleSort("paid_amount")}>
                                            Paid Amount ({currency}) {renderSortIcon("paid_amount")}
                                        </TableHead>
                                        <TableHead className="cursor-pointer text-right" onClick={() => handleSort("due_amount")}>
                                            Due Amount ({currency}) {renderSortIcon("due_amount")}
                                        </TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("due_date")}>
                                            Due Date {renderSortIcon("due_date")}
                                        </TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("status")}>
                                            Status {renderSortIcon("status")}
                                        </TableHead>
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

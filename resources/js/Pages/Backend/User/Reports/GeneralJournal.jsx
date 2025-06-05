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
import { formatAmount, formatCurrency, parseDateObject } from "@/lib/utils";
import DateTimePicker from "@/Components/DateTimePicker";
import { ChevronUp, ChevronDown } from "lucide-react";

export default function GeneralJournal({
    transactions,
    date1,
    date2,
    meta = {},
    base_currency,
    business_name,
    filters = {},
    total_debit,
    total_credit,
}) {
    const [search, setSearch] = useState(filters.search || "");
    const [perPage, setPerPage] = useState(50);
    const [currentPage, setCurrentPage] = useState(1);
    const [sorting, setSorting] = useState(
        filters.sorting || { column: "trans_date", direction: "desc" }
    );

    const { data, setData, post, processing } = useForm({
        date1: parseDateObject(date1),
        date2: parseDateObject(date2),
    });

    const handleExport = () => {
        window.location.href = route("reports.gen_journal_export");
    };

    const handleSearch = (e) => {
        e.preventDefault();
        const value = e.target.value;
        setSearch(value);

        router.get(
            route("reports.journal"),
            { search: value, page: 1, per_page: perPage, sorting },
            { preserveState: true }
        );
    };

    const handleGenerate = (e) => {
        e.preventDefault();
        post(route("reports.journal"), {
            date1: data.date1,
            date2: data.date2,
            search: search,
            per_page: perPage,
            page: 1,
            sorting,
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
            route("reports.journal"),
            { search, page: 1, per_page: value, sorting },
            { preserveState: true }
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("reports.journal"),
            { search, page, per_page: perPage, sorting },
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
            route("reports.journal"),
            { ...filters, sorting: { column, direction } },
            { preserveState: true }
        );
    };

    const renderSortIcon = (column) => {
        const isActive = sorting.column === column;
        return (
            <span className="inline-flex flex-col ml-1">
                <ChevronUp
                    className={`w-3 h-3 ${
                        isActive && sorting.direction === "asc"
                            ? "text-gray-800"
                            : "text-gray-300"
                    }`}
                />
                <ChevronDown
                    className={`w-3 h-3 -mt-1 ${
                        isActive && sorting.direction === "desc"
                            ? "text-gray-800"
                            : "text-gray-300"
                    }`}
                />
            </span>
        );
    };

    const getRefType = (ref_type) => {
        if (ref_type == "receipt") {
            return "cash invoice";
        } else if (ref_type == "s return") {
            return "sales return";
        } else if (ref_type == "s refund") {
            return "sales return refund";
        } else {
            return ref_type;
        }
    };

    const renderPageNumbers = () => {
        const totalPages = meta.last_page;
        const pages = [];
        const maxPagesToShow = 5;

        let startPage = Math.max(
            1,
            currentPage - Math.floor(maxPagesToShow / 2)
        );
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
        const printWindow = window.open("", "_blank", "width=800,height=600");

        // Generate CSS for the print window
        const style = `
            <style>
                body { font-family: Arial, sans-serif; }
                table { width: 100%; border-collapse: collapse; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                h2, h1 { text-align: center; margin-bottom: 20px; }
                text-right { text-align: right; }
                .total-row { font-weight: bold; background-color: #f9f9f9; }
            </style>
        `;

        // Start building the HTML content for the print window
        let printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>General Journal</title>
                ${style}
            </head>
            <body>
                <h1>${business_name}</h1>
                <h2>General Journal (${data.date1} - ${data.date2})</h2>
                <table>
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Account</th>
                            <th>Description</th>
                            <th>Type</th>
                            <th>Name</th>
                            <th>Transaction Currency</th>
                            <th>Transaction Amount[Debit]</th>
                            <th>Transaction Amount[Credit]</th>
                            <th>Currency Rate</th>
                            <th>Base Currency</th>
                            <th>Base Amount[Debit]</th>
                            <th>Base Amount[Credit]</th>
                        </tr>
                    </thead>
                    <tbody>
        `;

        // Add table rows from transactions data
        if (transactions.length > 0) {
            transactions.forEach((transaction) => {
                printContent += `
                    <tr>
                        <td>${transaction.trans_date || "N/A"}</td>
                        <td>${transaction.account.account_name || "N/A"}</td>
                        <td>${transaction.description || "N/A"}</td>
                        <td>${
                            transaction.ref_type === "receipt"
                                ? "cash invoice"
                                : transaction.ref_type || "N/A"
                        }</td>
                        <td>${transaction.payee_name || "N/A"}</td>
                        <td>${transaction.transaction_currency || "N/A"}</td>
                        <td>${
                            transaction.dr_cr === "dr"
                                ? transaction.transaction_amount
                                : 0
                        }</td>
                        <td>${
                            transaction.dr_cr === "cr"
                                ? transaction.transaction_amount
                                : 0
                        }</td>
                        <td>${transaction.currency_rate || "N/A"}</td>
                        <td>${base_currency}</td>
                        <td>${
                            transaction.dr_cr === "dr"
                                ? transaction.base_currency_amount
                                : 0
                        }</td>
                        <td>${
                            transaction.dr_cr === "cr"
                                ? transaction.base_currency_amount
                                : 0
                        }</td>
                    </tr>
                `;
            });
        } else {
            printContent += `
                <tr>
                    <td colspan="12" style="text-align: center;">No transactions found.</td>
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
            // Close the window after printing (optional, can be commented out if you want to keep it open)
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
                        page="General Journal"
                        subpage="List"
                        url="reports.journal"
                    />
                    <div className="p-4">
                        <div className="flex flex-col justify-between items-start mb-6 gap-4">
                            <div className="flex flex-col md:flex-row gap-4">
                                <form
                                    onSubmit={handleGenerate}
                                    className="flex gap-2"
                                >
                                    <div className="flex items-center gap-2">
                                        <DateTimePicker
                                            value={data.date1}
                                            onChange={(date) =>
                                                setData("date1", date)
                                            }
                                            className="md:w-1/2 w-full"
                                            required
                                        />

                                        <DateTimePicker
                                            value={data.date2}
                                            onChange={(date) =>
                                                setData("date2", date)
                                            }
                                            className="md:w-1/2 w-full"
                                            required
                                        />

                                        <Button
                                            type="submit"
                                            disabled={processing}
                                        >
                                            {processing
                                                ? "Generating..."
                                                : "Generate"}
                                        </Button>
                                    </div>
                                </form>
                            </div>
                            <div className="flex flex-col md:flex-row gap-4 md:items-center">
                                <Input
                                    placeholder="Search..."
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
                                <Button
                                    variant="outline"
                                    onClick={handleExport}
                                >
                                    Export
                                </Button>
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="text-sm text-gray-500">
                                    Show
                                </span>
                                <Select
                                    value={perPage.toString()}
                                    onValueChange={handlePerPageChange}
                                >
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
                                <span className="text-sm text-gray-500">
                                    entries
                                </span>
                            </div>
                        </div>

                        <div className="rounded-md border printable-table">
                            <ReportTable>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead
                                            className="!text-[10px] cursor-pointer"
                                            onClick={() =>
                                                handleSort("trans_date")
                                            }
                                        >
                                            Date {renderSortIcon("trans_date")}
                                        </TableHead>
                                        <TableHead
                                            className="!text-[10px] cursor-pointer"
                                            onClick={() =>
                                                handleSort(
                                                    "account.account_name"
                                                )
                                            }
                                        >
                                            Account{" "}
                                            {renderSortIcon(
                                                "account.account_name"
                                            )}
                                        </TableHead>
                                        <TableHead
                                            className="!text-[10px] cursor-pointer"
                                            onClick={() =>
                                                handleSort("description")
                                            }
                                        >
                                            Description{" "}
                                            {renderSortIcon("description")}
                                        </TableHead>
                                        <TableHead
                                            className="!text-[10px] cursor-pointer"
                                            onClick={() =>
                                                handleSort("ref_type")
                                            }
                                        >
                                            Type {renderSortIcon("ref_type")}
                                        </TableHead>
                                        <TableHead
                                            className="!text-[10px]">
                                            Name
                                        </TableHead>
                                        <TableHead
                                            className="!text-[10px] cursor-pointer"
                                            onClick={() =>
                                                handleSort(
                                                    "transaction_currency"
                                                )
                                            }
                                        >
                                            Transaction Currency{" "}
                                            {renderSortIcon(
                                                "transaction_currency"
                                            )}
                                        </TableHead>
                                        <TableHead
                                            className="!text-[10px] cursor-pointer"
                                            onClick={() =>
                                                handleSort("transaction_amount")
                                            }
                                        >
                                            Transaction Amount[Debit]{" "}
                                            {renderSortIcon(
                                                "transaction_amount"
                                            )}
                                        </TableHead>
                                        <TableHead
                                            className="!text-[10px] cursor-pointer"
                                            onClick={() =>
                                                handleSort("transaction_amount")
                                            }
                                        >
                                            Transaction Amount[Credit]{" "}
                                            {renderSortIcon(
                                                "transaction_amount"
                                            )}
                                        </TableHead>
                                        <TableHead
                                            className="!text-[10px] cursor-pointer"
                                            onClick={() =>
                                                handleSort("currency_rate")
                                            }
                                        >
                                            Rate{" "}
                                            {renderSortIcon("currency_rate")}
                                        </TableHead>
                                        <TableHead className="!text-[10px]">
                                            Base Currency
                                        </TableHead>
                                        <TableHead
                                            className="!text-[10px] cursor-pointer"
                                            onClick={() =>
                                                handleSort(
                                                    "base_currency_amount"
                                                )
                                            }
                                        >
                                            Base Amount[Debit]{" "}
                                            {renderSortIcon(
                                                "base_currency_amount"
                                            )}
                                        </TableHead>
                                        <TableHead
                                            className="!text-[10px] cursor-pointer"
                                            onClick={() =>
                                                handleSort(
                                                    "base_currency_amount"
                                                )
                                            }
                                        >
                                            Base Amount[Credit]{" "}
                                            {renderSortIcon(
                                                "base_currency_amount"
                                            )}
                                        </TableHead>
                                        <TableHead className="!text-[10px]">
                                            Difference
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow>
                                        <TableCell colSpan={10}></TableCell>
                                        <TableCell>
                                            {formatCurrency({
                                                amount: total_debit,
                                            })}
                                        </TableCell>
                                        <TableCell>
                                            {formatCurrency({
                                                amount: total_credit,
                                            })}
                                        </TableCell>
                                        <TableCell>
                                            {total_debit - total_credit !== 0 ? (
                                            <span className="text-red-500">
                                                {formatCurrency({
                                                    amount:
                                                        total_debit -
                                                        total_credit,
                                                    })}
                                                </span>
                                            ) : (
                                                <span className="text-green-500">
                                                    {formatCurrency({
                                                        amount:
                                                            total_debit -
                                                            total_credit,
                                                    })}
                                                </span>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                    {transactions.length > 0 ? (
                                        transactions.map((transaction) => (
                                            <TableRow key={transaction.id}>
                                                <TableCell className="!text-[10px]">
                                                    {transaction.trans_date ||
                                                        "N/A"}
                                                </TableCell>
                                                <TableCell className="!text-[10px]">
                                                    {transaction.account
                                                        .account_name || "N/A"}
                                                </TableCell>
                                                <TableCell className="!text-[10px]">
                                                    {transaction.description ||
                                                        "N/A"}
                                                </TableCell>
                                                <TableCell className="!text-[10px]">
                                                    {getRefType(
                                                        transaction.ref_type
                                                    ) || "N/A"}
                                                </TableCell>
                                                <TableCell className="!text-[10px]">
                                                    {transaction.payee_name ||
                                                        "N/A"}
                                                </TableCell>
                                                <TableCell className="text-right !text-[10px]">
                                                    {transaction.transaction_currency ||
                                                        "N/A"}
                                                </TableCell>
                                                <TableCell className="text-right !text-[10px]">
                                                    {transaction.dr_cr === "dr"
                                                        ? formatAmount(
                                                              transaction.transaction_amount
                                                          )
                                                        : 0}
                                                </TableCell>
                                                <TableCell className="text-right !text-[10px]">
                                                    {transaction.dr_cr === "cr"
                                                        ? formatAmount(
                                                              transaction.transaction_amount
                                                          )
                                                        : 0}
                                                </TableCell>
                                                <TableCell className="text-right !text-[10px]">
                                                    {formatAmount(
                                                        transaction.currency_rate
                                                    ) || "N/A"}
                                                </TableCell>
                                                <TableCell className="!text-[10px]">
                                                    {base_currency}
                                                </TableCell>
                                                <TableCell className="text-right !text-[10px]">
                                                    {transaction.dr_cr === "dr"
                                                        ? formatAmount(
                                                              transaction.base_currency_amount
                                                          )
                                                        : 0}
                                                </TableCell>
                                                <TableCell className="text-right !text-[10px]">
                                                    {transaction.dr_cr === "cr"
                                                        ? formatAmount(
                                                              transaction.base_currency_amount
                                                          )
                                                        : 0}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell
                                                colSpan={13}
                                                className="h-24 text-center"
                                            >
                                                No transactions found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </ReportTable>
                        </div>

                        {transactions.length > 0 && meta.total > 0 && (
                            <div className="flex items-center justify-between mt-4">
                                <div className="text-sm text-gray-500">
                                    Showing {(currentPage - 1) * perPage + 1} to{" "}
                                    {Math.min(
                                        currentPage * perPage,
                                        meta.total
                                    )}{" "}
                                    of {meta.total} entries
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
                                        onClick={() =>
                                            handlePageChange(currentPage - 1)
                                        }
                                        disabled={currentPage === 1}
                                    >
                                        Previous
                                    </Button>
                                    {renderPageNumbers()}
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            handlePageChange(currentPage + 1)
                                        }
                                        disabled={
                                            currentPage === meta.last_page
                                        }
                                    >
                                        Next
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() =>
                                            handlePageChange(meta.last_page)
                                        }
                                        disabled={
                                            currentPage === meta.last_page
                                        }
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

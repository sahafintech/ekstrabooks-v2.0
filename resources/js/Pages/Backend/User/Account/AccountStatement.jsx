import React, { useState } from "react";
import { router, useForm } from "@inertiajs/react";
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
import { formatCurrency } from "@/lib/utils";
import { Badge } from "@/Components/ui/badge";
import DateTimePicker from "@/Components/DateTimePicker";

export default function AccountStatement({
    transactions,
    account,
    date1,
    date2,
    meta = {},
    currency,
    business_name,
    balances,
    currenct_balance,
}) {
    const [search, setSearch] = useState("");
    const [perPage, setPerPage] = useState(10);
    const [currentPage, setCurrentPage] = useState(1);

    const { data, setData, post, processing } = useForm({
        date1: date1,
        date2: date2,
    });

    const handleExport = () => {
        window.location.href = route(
            "accounts.export_account_statement",
            account.id
        );
    };

    const handleSearch = (e) => {
        e.preventDefault();
        const value = e.target.value;
        setSearch(value);
        router.get(
          route("accounts.account_statement", account.id),
          { 
            search: value, 
            page: 1, 
            per_page: perPage, 
            date1: data.date1,
            date2: data.date2,
        }, { preserveState: true });
    };

    const handleGenerate = (e) => {
        e.preventDefault();
        post(route("accounts.account_statement", account.id), {
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
            route("accounts.account_statement", account.id),
            { search, page: 1, per_page: value },
            { preserveState: true }
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("accounts.account_statement", account.id),
            { search, page, per_page: perPage },
            { preserveState: true }
        );
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
                        <td>${account.account_name || "N/A"}</td>
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
                        page="Accounts"
                        subpage={account?.account_name}
                        url="accounts.index"
                    />
                    <div className="p-4">
                        <div className="print:shadow-none print:border-none">
                            <div className="flex flex-col gap-1">
                                <div className="text-sm font-medium text-muted-foreground">
                                    Account Code:{" "}
                                    <span className="ml-2 text-primary">
                                        {account?.account_code}
                                    </span>
                                </div>
                                <div className="text-sm font-medium text-muted-foreground">
                                    Currency:{" "}
                                    <span className="ml-2 text-primary">
                                        {account?.currency}
                                    </span>
                                </div>
                                <div className="text-sm font-medium text-muted-foreground">
                                    Account Type:{" "}
                                    <span className="ml-2 text-primary">
                                        {account?.account_type}
                                    </span>
                                </div>
                                <div className="text-sm font-medium text-muted-foreground">
                                    Balance:{" "}
                                    <span className="ml-2 text-primary">
                                        {formatCurrency({
                                            amount: currenct_balance,
                                            currency: currency,
                                        })}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div className="flex flex-col justify-between items-start my-6 gap-4">
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
                                <form
                                    onSubmit={handleSearch}
                                    className="flex gap-2"
                                >
                                    <Input
                                        placeholder="Search..."
                                        value={search}
                                        onChange={(e) => handleSearch(e)}
                                        className="w-full md:w-80"
                                    />
                                </form>
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
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Reference</TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead className="text-right">
                                            Debit
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Credit
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Balance
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {transactions.length > 0 ? (
                                        transactions.map(
                                            (transaction, index) => (
                                                <TableRow key={transaction.id}>
                                                    <TableCell>
                                                        {transaction.trans_date ||
                                                            "N/A"}
                                                    </TableCell>
                                                    <TableCell>
                                                        {transaction.ref_id &&
                                                            (transaction.ref_type ? (
                                                                <Badge
                                                                    variant="outline"
                                                                    className="font-normal"
                                                                >
                                                                    {transaction.ref_type.toUpperCase()}{" "}
                                                                    #
                                                                    {
                                                                        transaction.ref_id
                                                                    }
                                                                </Badge>
                                                            ) : (
                                                                transaction.ref_id
                                                            ))}
                                                    </TableCell>
                                                    <TableCell>
                                                        {transaction.description ||
                                                            "N/A"}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        {transaction.dr_cr ===
                                                        "dr"
                                                            ? formatCurrency({
                                                                  amount: transaction.transaction_amount,
                                                                  currency,
                                                              })
                                                            : formatCurrency({
                                                                  amount: 0,
                                                                  currency,
                                                              })}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        {transaction.dr_cr ===
                                                        "cr"
                                                            ? formatCurrency({
                                                                  amount: transaction.transaction_amount,
                                                                  currency,
                                                              })
                                                            : formatCurrency({
                                                                  amount: 0,
                                                                  currency,
                                                              })}
                                                    </TableCell>
                                                    <TableCell className="text-right">
                                                        {formatCurrency({
                                                            amount: balances
                                                                ?.running[
                                                                index
                                                            ],
                                                            currency,
                                                        })}
                                                    </TableCell>
                                                </TableRow>
                                            )
                                        )
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
                            </Table>
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

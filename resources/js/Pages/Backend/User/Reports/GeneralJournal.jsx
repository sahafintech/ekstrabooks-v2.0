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
import { Calendar } from "@/Components/ui/calendar";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/Components/ui/popover";
import { CalendarIcon } from "lucide-react";
import { format } from "date-fns";
import { cn } from "@/lib/utils";

export default function GeneralJournal({ transactions, date1, date2, meta = {}, filters = {}, base_currency, business_name }) {
    const [search, setSearch] = useState("");
    const [perPage, setPerPage] = useState(10);
    const [currentPage, setCurrentPage] = useState(1);
    


    const { data, setData, post, processing, errors, reset } = useForm({
        date1: date1,
        date2: date2,
    });

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(
            route("reports.journal"),
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
        post(route("reports.journal"), {
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
            route("reports.journal"),
            { search, page: 1, per_page: value },
            { preserveState: true }
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("reports.journal"),
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
            transactions.forEach(transaction => {
                printContent += `
                    <tr>
                        <td>${transaction.trans_date || 'N/A'}</td>
                        <td>${transaction.account.account_name || 'N/A'}</td>
                        <td>${transaction.description || 'N/A'}</td>
                        <td>${transaction.ref_type === 'receipt' ? 'cash invoice' : transaction.ref_type || 'N/A'}</td>
                        <td>${transaction.payee_name || 'N/A'}</td>
                        <td>${transaction.transaction_currency || 'N/A'}</td>
                        <td>${transaction.dr_cr === 'dr' ? transaction.transaction_amount : 0}</td>
                        <td>${transaction.dr_cr === 'cr' ? transaction.transaction_amount : 0}</td>
                        <td>${transaction.currency_rate || 'N/A'}</td>
                        <td>${base_currency}</td>
                        <td>${transaction.dr_cr === 'dr' ? transaction.base_currency_amount : 0}</td>
                        <td>${transaction.dr_cr === 'cr' ? transaction.base_currency_amount : 0}</td>
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
            printWindow.onafterprint = function() {
                printWindow.close();
            };
        }, 300);
    };

    return (
        <AuthenticatedLayout>
            <Head title="Reports" />
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
                                <form onSubmit={handleGenerate} className="flex gap-2">
                                    <div className="flex items-center gap-2">
                                        <Popover>
                                            <PopoverTrigger asChild>
                                                <Button
                                                    variant="outline"
                                                    className={cn(
                                                        "w-full md:w-auto justify-start text-left font-normal",
                                                        !data.date1 && "text-muted-foreground"
                                                    )}
                                                >
                                                    <CalendarIcon className="mr-2 h-4 w-4" />
                                                    {data.date1 ? format(new Date(data.date1), "PPP") : <span>From date</span>}
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent className="w-auto p-0" align="start">
                                                <Calendar
                                                    mode="single"
                                                    selected={data.date1 ? new Date(data.date1) : undefined}
                                                    onSelect={(date) => setData('date1', date ? format(date, "yyyy-MM-dd") : '')}
                                                    initialFocus
                                                />
                                            </PopoverContent>
                                        </Popover>

                                        <Popover>
                                            <PopoverTrigger asChild>
                                                <Button
                                                    variant="outline"
                                                    className={cn(
                                                        "w-full md:w-auto justify-start text-left font-normal",
                                                        !data.date2 && "text-muted-foreground"
                                                    )}
                                                >
                                                    <CalendarIcon className="mr-2 h-4 w-4" />
                                                    {data.date2 ? format(new Date(data.date2), "PPP") : <span>To date</span>}
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent className="w-auto p-0" align="start">
                                                <Calendar
                                                    mode="single"
                                                    selected={data.date2 ? new Date(data.date2) : undefined}
                                                    onSelect={(date) => setData('date2', date ? format(date, "yyyy-MM-dd") : '')}
                                                    initialFocus
                                                />
                                            </PopoverContent>
                                        </Popover>

                                        <Button type="submit" disabled={processing}>{processing ? 'Generating...' : 'Generate'}</Button>
                                    </div>
                                </form>
                            </div>
                            <div className="flex flex-col md:flex-row gap-4 md:items-center">
                                <form onSubmit={handleSearch} className="flex gap-2">
                                    <Input
                                        placeholder="Search..."
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
                                <a download href={route("reports.gen_journal_export")}>
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
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Account</TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Transaction Currency</TableHead>
                                        <TableHead>Transaction Amount[Debit]</TableHead>
                                        <TableHead>Transaction Amount[Credit]</TableHead>
                                        <TableHead>Currency Rate</TableHead>
                                        <TableHead>Rate</TableHead>
                                        <TableHead>Base Currency</TableHead>
                                        <TableHead>Base Amount[Debit]</TableHead>
                                        <TableHead>Base Amount[Credit]</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {transactions.length > 0 ? (
                                        transactions.map((transaction) => (
                                            <TableRow key={transaction.id}>
                                                <TableCell>{transaction.trans_date || 'N/A'}</TableCell>
                                                <TableCell>{transaction.account.account_name || 'N/A'}</TableCell>
                                                <TableCell>{transaction.description || 'N/A'}</TableCell>
                                                <TableCell>{transaction.ref_type === 'receipt' ? 'cash invoice' : transaction.ref_type || 'N/A'}</TableCell>
                                                <TableCell>{transaction.payee_name || 'N/A'}</TableCell>
                                                <TableCell>{transaction.transaction_currency || 'N/A'}</TableCell>
                                                <TableCell>{transaction.dr_cr === 'dr' ? transaction.transaction_amount : 0}</TableCell>
                                                <TableCell>{transaction.dr_cr === 'cr' ? transaction.transaction_amount : 0}</TableCell>
                                                <TableCell>{transaction.transaction_currency || 'N/A'}</TableCell>
                                                <TableCell>{transaction.currency_rate || 'N/A'}</TableCell>
                                                <TableCell>{base_currency}</TableCell>
                                                <TableCell>{transaction.dr_cr === 'dr' ? transaction.base_currency_amount : 0}</TableCell>
                                                <TableCell>{transaction.dr_cr === 'cr' ? transaction.base_currency_amount : 0}</TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={13} className="h-24 text-center">
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

import React, { useState, useEffect, useMemo } from "react";
import { Link, router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { Checkbox } from "@/Components/ui/checkbox";
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
import { ChevronUp, ChevronDown } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import PageHeader from "@/Components/PageHeader";
import { formatCurrency } from "@/lib/utils";

export default function List({ transactions = [], meta = {}, filters = {} }) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [search, setSearch] = useState(filters.search || "");
    const [perPage, setPerPage] = useState(meta.per_page || 50);
    const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
    const [sorting, setSorting] = useState(
        filters.sorting || { column: "id", direction: "desc" }
    );

    // Client-side filtering, sorting and pagination
    const filteredAndSortedTransactions = useMemo(() => {
        let result = [...transactions];

        // Apply search filter
        if (search) {
            const searchLower = search.toLowerCase();
            result = result.filter(
                (transaction) =>
                    transaction.id.toString().includes(searchLower) ||
                    (transaction.account?.account_name &&
                        transaction.account.account_name
                            .toLowerCase()
                            .includes(searchLower)) ||
                    (transaction.description &&
                        transaction.description
                            .toLowerCase()
                            .includes(searchLower)) ||
                    (transaction.ref_type &&
                        transaction.ref_type
                            .toLowerCase()
                            .includes(searchLower)) ||
                    (transaction.payee_name &&
                        transaction.payee_name
                            .toLowerCase()
                            .includes(searchLower)) ||
                    (transaction.transaction_currency &&
                        transaction.transaction_currency
                            .toLowerCase()
                            .includes(searchLower))
            );
        }

        // Apply sorting
        result.sort((a, b) => {
            const { column, direction } = sorting;
            let aValue, bValue;

            // Handle nested properties
            if (column.includes(".")) {
                const [parent, child] = column.split(".");
                aValue = a[parent]?.[child];
                bValue = b[parent]?.[child];
            } else {
                aValue = a[column];
                bValue = b[column];
            }

            // Handle null/undefined values
            if (aValue === null || aValue === undefined) aValue = "";
            if (bValue === null || bValue === undefined) bValue = "";

            if (direction === "asc") {
                return aValue > bValue ? 1 : -1;
            } else {
                return aValue < bValue ? 1 : -1;
            }
        });

        return result;
    }, [transactions, search, sorting]);

    // Calculate pagination
    const paginatedTransactions = useMemo(() => {
        const startIndex = (currentPage - 1) * perPage;
        return filteredAndSortedTransactions.slice(
            startIndex,
            startIndex + perPage
        );
    }, [filteredAndSortedTransactions, currentPage, perPage]);

    // Calculate pagination metadata
    const paginationMeta = useMemo(
        () => ({
            total: filteredAndSortedTransactions.length,
            per_page: perPage,
            current_page: currentPage,
            last_page: Math.ceil(
                filteredAndSortedTransactions.length / perPage
            ),
        }),
        [filteredAndSortedTransactions.length, perPage, currentPage]
    );

    useEffect(() => {
        if (flash && flash.success) {
            toast({
                title: "Success",
                description: flash.success,
            });
        }

        if (flash && flash.error) {
            toast({
                variant: "destructive",
                title: "Error",
                description: flash.error,
            });
        }
    }, [flash, toast]);

    const handleSearch = (e) => {
        const value = e.target.value;
        setSearch(value);
        setCurrentPage(1); // Reset to first page on search
    };

    const handlePerPageChange = (value) => {
        setPerPage(Number(value));
        setCurrentPage(1); // Reset to first page when changing items per page
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
    };

    const handleSort = (column) => {
        let direction = "asc";
        if (sorting.column === column && sorting.direction === "asc") {
            direction = "desc";
        }
        setSorting({ column, direction });
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

    const renderPageNumbers = () => {
        const totalPages = paginationMeta.last_page;
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

    return (
        <div className="main-content">
            <div className="p-4">
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                    <div></div>
                    <div className="flex flex-col md:flex-row gap-4 md:items-center">
                        <Input
                            placeholder="search transactions..."
                            value={search}
                            onChange={(e) => handleSearch(e)}
                            className="w-full md:w-80"
                        />
                    </div>
                </div>

                <div className="mb-4 flex flex-col md:flex-row gap-4 justify-between">
                    <div></div>
                    <div className="flex items-center gap-2">
                        <span className="text-sm text-gray-500">Show</span>
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
                        <span className="text-sm text-gray-500">entries</span>
                    </div>
                </div>

                <div className="rounded-md border">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead
                                    className="!text-[10px] cursor-pointer"
                                    onClick={() => handleSort("trans_date")}
                                >
                                    Date {renderSortIcon("trans_date")}
                                </TableHead>
                                <TableHead
                                    className="!text-[10px] cursor-pointer"
                                    onClick={() =>
                                        handleSort("account.account_name")
                                    }
                                >
                                    Account{" "}
                                    {renderSortIcon("account.account_name")}
                                </TableHead>
                                <TableHead
                                    className="!text-[10px] cursor-pointer"
                                    onClick={() => handleSort("description")}
                                >
                                    Description {renderSortIcon("description")}
                                </TableHead>
                                <TableHead
                                    className="!text-[10px] cursor-pointer"
                                    onClick={() => handleSort("ref_type")}
                                >
                                    Type {renderSortIcon("ref_type")}
                                </TableHead>
                                <TableHead className="!text-[10px]">
                                    Name
                                </TableHead>
                                <TableHead
                                    className="!text-[10px] cursor-pointer"
                                    onClick={() =>
                                        handleSort("transaction_currency")
                                    }
                                >
                                    Transaction Currency{" "}
                                    {renderSortIcon("transaction_currency")}
                                </TableHead>
                                <TableHead
                                    className="!text-[10px] cursor-pointer"
                                    onClick={() =>
                                        handleSort("transaction_amount")
                                    }
                                >
                                    Transaction Amount[Debit]{" "}
                                    {renderSortIcon("transaction_amount")}
                                </TableHead>
                                <TableHead
                                    className="!text-[10px] cursor-pointer"
                                    onClick={() =>
                                        handleSort("transaction_amount")
                                    }
                                >
                                    Transaction Amount[Credit]{" "}
                                    {renderSortIcon("transaction_amount")}
                                </TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {paginatedTransactions.length > 0 ? (
                                paginatedTransactions.map((transaction) => (
                                    <TableRow key={transaction.id}>
                                        <TableCell className="!text-[10px]">
                                            {transaction.trans_date || "N/A"}
                                        </TableCell>
                                        <TableCell className="!text-[10px]">
                                            {transaction.account.account_name ||
                                                "N/A"}
                                        </TableCell>
                                        <TableCell className="!text-[10px]">
                                            {transaction.description || "N/A"}
                                        </TableCell>
                                        <TableCell className="!text-[10px]">
                                            {transaction.ref_type || "N/A"}
                                        </TableCell>
                                        <TableCell className="!text-[10px]">
                                            {transaction.payee_name || "N/A"}
                                        </TableCell>
                                        <TableCell className="text-right !text-[10px]">
                                            {transaction.transaction_currency ||
                                                "N/A"}
                                        </TableCell>
                                        <TableCell className="text-right !text-[10px]">
                                            {transaction.dr_cr === "dr"
                                                ? formatCurrency({
                                                      amount: transaction.transaction_amount,
                                                  })
                                                : 0}
                                        </TableCell>
                                        <TableCell className="text-right !text-[10px]">
                                            {transaction.dr_cr === "cr"
                                                ? formatCurrency({
                                                      amount: transaction.transaction_amount,
                                                  })
                                                : 0}
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell
                                        colSpan={8}
                                        className="h-24 text-center"
                                    >
                                        No Transactions found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                {paginatedTransactions.length > 0 &&
                    paginationMeta.total > 0 && (
                        <div className="flex items-center justify-between mt-4">
                            <div className="text-sm text-gray-500">
                                Showing {(currentPage - 1) * perPage + 1} to{" "}
                                {Math.min(
                                    currentPage * perPage,
                                    paginationMeta.total
                                )}{" "}
                                of {paginationMeta.total} entries
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
                                        currentPage === paginationMeta.last_page
                                    }
                                >
                                    Next
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        handlePageChange(
                                            paginationMeta.last_page
                                        )
                                    }
                                    disabled={
                                        currentPage === paginationMeta.last_page
                                    }
                                >
                                    Last
                                </Button>
                            </div>
                        </div>
                    )}
            </div>
        </div>
    );
}

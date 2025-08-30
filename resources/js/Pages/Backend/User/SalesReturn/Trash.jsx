import React, { useState, useEffect } from "react";
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
import { Plus, Trash2, ChevronUp, ChevronDown, Trash, RotateCcw } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { formatCurrency } from "@/lib/utils";
import DateTimePicker from "@/Components/DateTimePicker";

const DeleteSalesReturnModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete this sales return?
            </h2>
            <div className="mt-6 flex justify-end">
                <Button
                    type="button"
                    variant="secondary"
                    onClick={onClose}
                    className="mr-3"
                >
                    Cancel
                </Button>
                <Button
                    type="submit"
                    variant="destructive"
                    disabled={processing}
                >
                    Delete Sales Return
                </Button>
            </div>
        </form>
    </Modal>
);

const DeleteAllSalesReturnModal = ({ show, onClose, onConfirm, processing, count }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete {count} selected sales return{count !== 1 ? 's' : ''}?
            </h2>
            <div className="mt-6 flex justify-end">
                <Button
                    type="button"
                    variant="secondary"
                    onClick={onClose}
                    className="mr-3"
                >
                    Cancel
                </Button>
                <Button
                    type="submit"
                    variant="destructive"
                    disabled={processing}
                >
                    Delete Selected
                </Button>
            </div>
        </form>
    </Modal>
);

const RestoreSalesReturnModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to restore this sales return?
            </h2>
            <div className="mt-6 flex justify-end">
                <Button
                    type="button"
                    variant="secondary"
                    onClick={onClose}
                    className="mr-3"
                >
                    Cancel
                </Button>
                <Button
                    type="submit"
                    variant="default"
                    disabled={processing}
                >
                    Restore Sales Return
                </Button>
            </div>
        </form>
    </Modal>
);

const RestoreAllSalesReturnModal = ({ show, onClose, onConfirm, processing, count }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to restore {count} selected sales return{count !== 1 ? 's' : ''}?
            </h2>
            <div className="mt-6 flex justify-end">
                <Button
                    type="button"
                    variant="secondary"
                    onClick={onClose}
                    className="mr-3"
                >
                    Cancel
                </Button>
                <Button
                    type="submit"
                    variant="default"
                    disabled={processing}
                >
                    Restore Selected
                </Button>
            </div>
        </form>
    </Modal>
);

const SalesReturnStatusBadge = ({ status }) => {
    const statusMap = {
        0: { label: "Active", className: "text-blue-600" },
        1: { label: "Refunded", className: "text-green-600" },
        2: { label: "Partially Refunded", className: "text-yellow-600" },
    };

    return (
        <span className={statusMap[status].className}>
            {statusMap[status].label}
        </span>
    );
};

export default function TrashList({ returns = [], meta = {}, filters = {}, accounts = [], errors = [], customers = [] }) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [selectedSalesReturns, setSelectedSalesReturns] = useState([]);
    const [isAllSelected, setIsAllSelected] = useState(false);
    const [search, setSearch] = useState(filters.search || "");
    const [perPage, setPerPage] = useState(meta.per_page || 10);
    const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
    const [bulkAction, setBulkAction] = useState("");
    const [sorting, setSorting] = useState(filters.sorting || { column: "id", direction: "desc" });
    const [selectedCustomer, setSelectedCustomer] = useState(filters.customer_id || "");
    const [dateRange, setDateRange] = useState(filters.date_range || null);
    const [selectedStatus, setSelectedStatus] = useState(filters.status || "");

    // Delete confirmation modal states
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [salesReturnToDelete, setSalesReturnToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);

    // Restore confirmation modal states
    const [showRestoreModal, setShowRestoreModal] = useState(false);
    const [showRestoreAllModal, setShowRestoreAllModal] = useState(false);
    const [salesReturnToRestore, setSalesReturnToRestore] = useState(null);

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

    const toggleSelectAll = () => {
        if (isAllSelected) {
            setSelectedSalesReturns([]);
        } else {
            setSelectedSalesReturns(returns.map((sales_return) => sales_return.id));
        }
        setIsAllSelected(!isAllSelected);
    };

    const toggleSelectSalesReturns = (id) => {
        if (selectedSalesReturns.includes(id)) {
            setSelectedSalesReturns(selectedSalesReturns.filter((sales_returnId) => sales_returnId !== id));
            setIsAllSelected(false);
        } else {
            setSelectedSalesReturns([...selectedSalesReturns, id]);
            if (selectedSalesReturns.length + 1 === returns.length) {
                setIsAllSelected(true);
            }
        }
    };

    const handleSearch = (e) => {
        e.preventDefault();
        const value = e.target.value;
        setSearch(value);

        router.get(
            route("sales_returns.index"),
            { search: value, page: 1, per_page: perPage },
            { preserveState: true }
        );
    };

    const handlePerPageChange = (value) => {
        setPerPage(value);
        router.get(
            route("sales_returns.index"),
            { search, page: 1, per_page: value },
            { preserveState: true }
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("sales_returns.index"),
            { 
                search, 
                page, 
                per_page: perPage,
                sorting,
                customer_id: selectedCustomer,
                date_range: dateRange,
                status: selectedStatus
            },
            { preserveState: true }
        );
    };

    const handleBulkAction = () => {
        if (bulkAction === "") return;

        if (selectedSalesReturns.length === 0) {
            toast({
                variant: "destructive",
                title: "Error",
                description: "Please select at least one sales return",
            });
            return;
        }

        if (bulkAction === "delete") {
            setShowDeleteAllModal(true);
        }else if (bulkAction === "restore") {
            setShowRestoreAllModal(true);
        }
    };

    const handleDeleteConfirm = (id) => {
        setSalesReturnToDelete(id);
        setShowDeleteModal(true);
    };

    const handleRestoreConfirm = (id) => {
        setSalesReturnToRestore(id);
        setShowRestoreModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.delete(route('sales_returns.permanent_destroy', salesReturnToDelete), {
            onSuccess: () => {
                setShowDeleteModal(false);
                setSalesReturnToDelete(null);
                setProcessing(false);
            },
            onError: () => {
                setProcessing(false);
            }
        });
    };

    const handleDeleteAll = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(route('sales_returns.bulk_permanent_destroy'),
            {
                ids: selectedSalesReturns
            },
            {
                onSuccess: () => {
                    setShowDeleteAllModal(false);
                    setSelectedSalesReturns([]);
                    setIsAllSelected(false);
                    setProcessing(false);
                    setBulkAction("");
                },
                onError: () => {
                    setProcessing(false);
                }
            }
        );
    };

    const handleRestore = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(route('sales_returns.restore', salesReturnToRestore), {
            onSuccess: () => {
                setShowRestoreModal(false);
                setSalesReturnToRestore(null);
                setProcessing(false);
            },
            onError: () => {
                setProcessing(false);
            }
        });
    };

    const handleRestoreAll = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(route('sales_returns.bulk_restore'),
            {
                ids: selectedSalesReturns
            },
            {
                onSuccess: () => {
                    setShowRestoreAllModal(false);
                    setSelectedSalesReturns([]);
                    setIsAllSelected(false);
                    setProcessing(false);
                    setBulkAction("");
                },
                onError: () => {
                    setProcessing(false);
                }
            }
        );
    };

    const handleSort = (column) => {
        let direction = "asc";
        if (sorting.column === column && sorting.direction === "asc") {
            direction = "desc";
        }
        setSorting({ column, direction });
        router.get(
            route("sales_returns.index"),
            { ...filters, sorting: { column, direction } },
            { preserveState: true }
        );
    };

    const handleCustomerChange = (value) => {
        setSelectedCustomer(value);
        router.get(
            route("sales_returns.index"),
            { 
                search, 
                page: 1, 
                per_page: perPage, 
                sorting,
                customer_id: value,
                date_range: dateRange,
                status: selectedStatus
            },
            { preserveState: true }
        );
    };

    const handleDateRangeChange = (dates) => {
        setDateRange(dates);
        router.get(
            route("sales_returns.index"),
            { 
                search, 
                page: 1, 
                per_page: perPage, 
                sorting,
                customer_id: selectedCustomer,
                date_range: dates,
                status: selectedStatus
            },
            { preserveState: true }
        );
    };

    const handleStatusChange = (value) => {
        setSelectedStatus(value);
        router.get(
            route("sales_returns.index"),
            { 
                search, 
                page: 1, 
                per_page: perPage, 
                sorting,
                customer_id: selectedCustomer,
                date_range: dateRange,
                status: value
            },
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

    const exportSalesReturns = () => {
        router.get(route("sales_returns.export"));
    };

    return (
        <AuthenticatedLayout>
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Sales Returns"
                        subpage="List"
                        url="sales_returns.index"
                    />
                    <div className="p-4">
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div className="flex flex-col md:flex-row gap-2">
                                <div>
                                    <div className="text-red-500">
                                        Total trashed sales returns: {meta.total}
                                    </div>
                                </div>
                            </div>
                            <div className="flex flex-col md:flex-row gap-4 md:items-center">
                                <Input
                                    placeholder="Search sales returns..."
                                    value={search}
                                    onChange={(e) => handleSearch(e)}
                                    className="w-full md:w-80"
                                />
                            </div>
                        </div>

                        <div className="mb-4 flex flex-col md:flex-row gap-4 justify-between">
                            <div className="flex items-center gap-2">
                                <Select value={bulkAction} onValueChange={setBulkAction}>
                                    <SelectTrigger className="w-[180px]">
                                        <SelectValue placeholder="Bulk actions" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="delete">Permanently Delete Selected</SelectItem>
                                        <SelectItem value="restore">Restore Selected</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button onClick={handleBulkAction} variant="outline">
                                    Apply
                                </Button>
                                <SearchableCombobox
                                    options={customers.map(customer => ({
                                        id: customer.id,
                                        name: customer.name
                                    }))}
                                    value={selectedCustomer}
                                    onChange={handleCustomerChange}
                                    placeholder="Select customer"
                                    className="w-[200px]"
                                />
                                <DateTimePicker
                                    value={dateRange}
                                    onChange={handleDateRangeChange}
                                    isRange={true}
                                    className="w-[200px]"
                                    placeholder="Select date range"
                                />
                                <SearchableCombobox
                                    options={[
                                        { id: "", name: "All Status" },
                                        { id: "0", name: "Active" },
                                        { id: "1", name: "Refunded" },
                                        { id: "2", name: "Partially Refunded" }
                                    ]}
                                    value={selectedStatus}
                                    onChange={handleStatusChange}
                                    placeholder="Select status"
                                    className="w-[150px]"
                                />
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

                        <div className="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-[50px]">
                                            <Checkbox
                                                checked={isAllSelected}
                                                onCheckedChange={toggleSelectAll}
                                            />
                                        </TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("return_number")}>
                                            Return # {renderSortIcon("return_number")}
                                        </TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("customer.name")}>
                                            Customer {renderSortIcon("customer.name")}
                                        </TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("return_date")}>
                                            Date {renderSortIcon("return_date")}
                                        </TableHead>
                                        <TableHead className="cursor-pointer text-right" onClick={() => handleSort("grand_total")}>
                                            Total {renderSortIcon("grand_total")}
                                        </TableHead>
                                        <TableHead className="cursor-pointer text-right" onClick={() => handleSort("paid")}>
                                            Paid {renderSortIcon("paid")}
                                        </TableHead>
                                        <TableHead className="cursor-pointer text-right" onClick={() => handleSort("grand_total")}>
                                            Due {renderSortIcon("grand_total")}
                                        </TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("status")}>
                                            Status {renderSortIcon("status")}
                                        </TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {returns.length > 0 ? (
                                        returns.map((sales_return) => (
                                            <TableRow key={sales_return.id}>
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selectedSalesReturns.includes(sales_return.id)}
                                                        onCheckedChange={() => toggleSelectSalesReturns(sales_return.id)}
                                                    />
                                                </TableCell>
                                                <TableCell>{sales_return.return_number}</TableCell>
                                                <TableCell>{sales_return.customer ? sales_return.customer.name : "-"}</TableCell>
                                                <TableCell>{sales_return.return_date}</TableCell>
                                                <TableCell className="text-right">{formatCurrency(sales_return.grand_total)}</TableCell>
                                                <TableCell className="text-right">{formatCurrency(sales_return.paid)}</TableCell>
                                                <TableCell className="text-right">{formatCurrency(sales_return.grand_total - sales_return.paid)}</TableCell>
                                                <TableCell>
                                                    <SalesReturnStatusBadge status={sales_return.status} />
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <TableActions
                                                        actions={[
                                                        {
                                                            label: "Restore",
                                                            icon: <RotateCcw className="h-4 w-4" />,
                                                            onClick: () => handleRestoreConfirm(sales_return.id)
                                                        },
                                                        {
                                                            label: "Permanently Delete",
                                                            icon: <Trash className="h-4 w-4" />,
                                                            onClick: () => handleDeleteConfirm(sales_return.id),
                                                            destructive: true,
                                                        },
                                                        ]}
                                                    />
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={10} className="h-24 text-center">
                                                No sales returns found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {returns.length > 0 && meta.total > 0 && (
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

            <DeleteSalesReturnModal
                show={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                onConfirm={handleDelete}
                processing={processing}
            />

            <DeleteAllSalesReturnModal
                show={showDeleteAllModal}
                onClose={() => setShowDeleteAllModal(false)}
                onConfirm={handleDeleteAll}
                processing={processing}
                count={selectedSalesReturns.length}
            />

            <RestoreSalesReturnModal
                show={showRestoreModal}
                onClose={() => setShowRestoreModal(false)}
                onConfirm={handleRestore}
                processing={processing}
            />

            <RestoreAllSalesReturnModal
                show={showRestoreAllModal}
                onClose={() => setShowRestoreAllModal(false)}
                onConfirm={handleRestoreAll}
                processing={processing}
                count={selectedSalesReturns.length}
            />
        </AuthenticatedLayout>
    );
}

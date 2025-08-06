import React, { useState, useEffect } from "react";
import { router, usePage } from "@inertiajs/react";
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
import {
    ChevronUp,
    ChevronDown,
    RotateCcw,
    Trash,
} from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { formatCurrency } from "@/lib/utils";
import DateTimePicker from "@/Components/DateTimePicker";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";

const DeleteBillModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to permanently delete this bill?
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
                    Permanently Delete Bill
                </Button>
            </div>
        </form>
    </Modal>
);

const DeleteAllBillsModal = ({
    show,
    onClose,
    onConfirm,
    processing,
    count,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to permanently delete {count} selected bill
                {count !== 1 ? "s" : ""}?
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
                    Permanently Delete Selected
                </Button>
            </div>
        </form>
    </Modal>
);

const RestoreBillModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to restore this bill?
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
                    Restore Bill
                </Button>
            </div>
        </form>
    </Modal>
);

const RestoreAllBillsModal = ({
    show,
    onClose,
    onConfirm,
    processing,
    count,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to restore {count} selected bill
                {count !== 1 ? "s" : ""}?
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

const BillApprovalStatusBadge = ({ status }) => {
    const statusMap = {
        0: {
            label: "Pending",
            className: "text-gray-600 bg-gray-200 px-3 py-1 rounded text-xs",
        },
        1: {
            label: "Approved",
            className: "text-green-400 bg-green-200 px-3 py-1 rounded text-xs",
        },
    };

    return (
        <span className={statusMap[status].className}>
            {statusMap[status].label}
        </span>
    );
};

const BillStatusBadge = ({ status }) => {
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

export default function TrashList({
    bills = [],
    meta = {},
    filters = {},
    vendors = [],
}) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [selectedBills, setSelectedBills] = useState([]);
    const [isAllSelected, setIsAllSelected] = useState(false);
    const [search, setSearch] = useState(filters.search || "");
    const [perPage, setPerPage] = useState(filters.per_page || 50);
    const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
    const [bulkAction, setBulkAction] = useState("");
    const [sorting, setSorting] = useState(
        filters.sorting || { column: "id", direction: "desc" }
    );
    const [selectedVendor, setSelectedVendor] = useState(
        filters.vendor_id || ""
    );
    const [dateRange, setDateRange] = useState(filters.date_range || null);
    const [selectedApprovalStatus, setSelectedApprovalStatus] = useState(
        filters.approval_status || ""
    );
    const [selectedBillStatus, setSelectedBillStatus] = useState(
        filters.status || ""
    );

    // Delete confirmation modal states
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [billToDelete, setBillToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);
    const [showRestoreAllModal, setShowRestoreAllModal] = useState(false);
    const [billToRestore, setBillToRestore] = useState(null);
    const [showRestoreModal, setShowRestoreModal] = useState(false);

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

    const toggleSelectAll = (checked) => {
        setIsAllSelected(checked);
        setSelectedBills(checked ? bills.map((bill) => bill.id) : []);
    };

    const toggleSelectBill = (billId) => {
        setSelectedBills((prev) => {
            if (prev.includes(billId)) {
                return prev.filter((id) => id !== billId);
            } else {
                return [...prev, billId];
            }
        });
    };

    const handleDeleteConfirm = (billId) => {
        setBillToDelete(billId);
        setShowDeleteModal(true);
    };

    const handleRestoreConfirm = (billId) => {
        setBillToRestore(billId);
        setShowRestoreModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.delete(route("bill_invoices.permanent_destroy", billToDelete), {
            onSuccess: () => {
                setShowDeleteModal(false);
                setBillToDelete(null);
                setProcessing(false);
                setSelectedBills((prev) =>
                    prev.filter((id) => id !== billToDelete)
                );
                toast({
                    title: "Bill Deleted",
                    description: "bill has been deleted successfully.",
                });
            },
            onError: () => {
                setProcessing(false);
                toast({
                    variant: "destructive",
                    title: "Error",
                    description: "There was an error deleting the bill.",
                });
            },
        });
    };

    const handleDeleteAll = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(
            route("bill_invoices.bulk_permanent_destroy"),
            {
                ids: selectedBills,
            },
            {
                onSuccess: () => {
                    setShowDeleteAllModal(false);
                    setProcessing(false);
                    setSelectedBills([]);
                    setIsAllSelected(false);
                },
                onError: () => {
                    setProcessing(false);
                },
            }
        );
    };

    const handleRestore = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(route("bill_invoices.restore", billToRestore), {
            onSuccess: () => {
                setShowRestoreModal(false);
                setBillToRestore(null);
                setProcessing(false);
                setSelectedBills((prev) =>
                    prev.filter((id) => id !== billToDelete)
                );
                toast({
                    title: "Bill Restored",
                    description: "bill has been restored successfully.",
                });
            },
            onError: () => {
                setProcessing(false);
                toast({
                    variant: "destructive",
                    title: "Error",
                    description: "There was an error restoring the bill.",
                });
            },
        });
    };

    const handleRestoreAll = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(
            route("bill_invoices.bulk_restore"),
            {
                ids: selectedBills,
            },
            {
                onSuccess: () => {
                    setShowRestoreAllModal(false);
                    setProcessing(false);
                    setSelectedBills([]);
                    setIsAllSelected(false);
                },
                onError: () => {
                    setProcessing(false);
                },
            }
        );
    };

    const handleSearch = (e) => {
        e.preventDefault();
        const value = e.target.value;
        setSearch(value);

        router.get(
            route("bill_invoices.index"),
            {
                search: value,
                page: 1,
                per_page: perPage,
                vendor_id: selectedVendor,
                date_range: dateRange,
                approval_status: selectedApprovalStatus,
                status: selectedBillStatus,
            },
            { preserveState: true }
        );
    };

    const handlePerPageChange = (value) => {
        setPerPage(parseInt(value));
        router.get(
            route("bill_invoices.index"),
            {
                search,
                page: 1,
                per_page: value,
                vendor_id: selectedVendor,
                date_range: dateRange,
                approval_status: selectedApprovalStatus,
                status: selectedBillStatus,
            },
            { preserveState: true }
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("bill_invoices.index"),
            {
                search,
                page,
                per_page: perPage,
                vendor_id: selectedVendor,
                date_range: dateRange,
                approval_status: selectedApprovalStatus,
                status: selectedBillStatus,
                sorting: sorting
            },
            { preserveState: true }
        );
    };

    const handleVendorChange = (value) => {
        setSelectedVendor(value);
        router.get(
            route("bill_invoices.index"),
            {
                search,
                page: 1,
                per_page: perPage,
                vendor_id: value,
                date_range: dateRange,
                approval_status: selectedApprovalStatus,
                status: selectedBillStatus,
            },
            { preserveState: true }
        );
    };

    const handleDateRangeChange = (dates) => {
        setDateRange(dates);
        router.get(
            route("bill_invoices.index"),
            {
                search,
                page: 1,
                per_page: perPage,
                vendor_id: selectedVendor,
                date_range: dates,
                approval_status: selectedApprovalStatus,
                status: selectedBillStatus,
            },
            { preserveState: true }
        );
    };

    const handleBillStatusChange = (value) => {
        setSelectedBillStatus(value);
        router.get(
            route("bill_invoices.index"),
            { 
                search, 
                page: 1, 
                per_page: perPage,
                vendor_id: selectedVendor,
                date_range: dateRange,
                approval_status: selectedApprovalStatus,
                status: value || null
            },
            { preserveState: true }
        );
    };

    const handleApprovalStatusChange = (value) => {
        setSelectedApprovalStatus(value);
        router.get(
            route("bill_invoices.index"),
            { 
                search, 
                page: 1, 
                per_page: perPage,
                vendor_id: selectedVendor,
                date_range: dateRange,
                approval_status: value || null,
                status: selectedBillStatus
            },
            { preserveState: true }
        );
    };

    const handleBulkAction = () => {
        if (bulkAction === "delete" && selectedBills.length > 0) {
            setShowDeleteAllModal(true);
        }else if (bulkAction === "restore" && selectedBills.length > 0) {
            setShowRestoreAllModal(true);
        }
    };

    const handleExport = () => {
        window.location.href = route("bill_invoices.export");
    };

    const handleSort = (column) => {
        let direction = "asc";
        if (sorting.column === column && sorting.direction === "asc") {
            direction = "desc";
        }
        setSorting({ column, direction });
        router.get(
            route("bill_invoices.index"),
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

    const renderPageNumbers = () => {
        const totalPages = meta.last_page || 1;
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
        <AuthenticatedLayout>
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Credit Purchase"
                        subpage="List"
                        url="bill_invoices.index"
                    />
                    <div className="p-4">
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div>
                                <div className="text-red-500">
                                    Total trashed credit purchases: {meta.total}
                                </div>
                            </div>
                            <div className="flex flex-col md:flex-row gap-4 md:items-center">
                                <Input
                                    placeholder="Search trashed credit purchases..."
                                    value={search}
                                    onChange={(e) => handleSearch(e)}
                                    className="w-full md:w-80"
                                />
                            </div>
                        </div>

                        <div className="mb-4 flex flex-col md:flex-row gap-4 justify-between">
                            <div className="flex flex-wrap items-center gap-2">
                                <Select
                                    value={bulkAction}
                                    onValueChange={setBulkAction}
                                >
                                    <SelectTrigger className="w-[180px]">
                                        <SelectValue placeholder="Bulk actions" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="delete">
                                            Permanently Delete Selected
                                        </SelectItem>
                                        <SelectItem value="restore">
                                            Restore Selected
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button
                                    onClick={handleBulkAction}
                                    variant="outline"
                                >
                                    Apply
                                </Button>

                                <SearchableCombobox
                                    options={vendors.map((vendor) => ({
                                        id: vendor.id,
                                        name: vendor.name,
                                    }))}
                                    value={selectedVendor}
                                    onChange={handleVendorChange}
                                    placeholder="Select vendor"
                                    className="w-[200px]"
                                />

                                <DateTimePicker
                                    value={dateRange}
                                    onChange={handleDateRangeChange}
                                    className="w-[200px]"
                                    isRange={true}
                                    placeholder="Select date range"
                                />

                                <SearchableCombobox
                                    options={[
                                        { id: "", name: "All" },
                                        { id: "0", name: "Pending" },
                                        { id: "1", name: "Approved" },
                                    ]}
                                    value={selectedApprovalStatus}
                                    onChange={handleApprovalStatusChange}
                                    placeholder="Select approval status"
                                    className="w-[200px]"
                                />

                                <SearchableCombobox
                                    options={[
                                        { id: "", name: "All" },
                                        { id: "0", name: "Active" },
                                        { id: "1", name: "Partial Paid" },
                                        { id: "2", name: "Paid" },
                                    ]}
                                    value={selectedBillStatus}
                                    onChange={handleBillStatusChange}
                                    placeholder="Select purchase status"
                                    className="w-[200px]"
                                />
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

                        <div className="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-[50px]">
                                            <Checkbox
                                                checked={isAllSelected}
                                                onCheckedChange={
                                                    toggleSelectAll
                                                }
                                            />
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() =>
                                                handleSort("bill_no")
                                            }
                                        >
                                            Bill Number{" "}
                                            {renderSortIcon("bill_no")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() =>
                                                handleSort("vendor.name")
                                            }
                                        >
                                            Vendor{" "}
                                            {renderSortIcon("vendor.name")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() =>
                                                handleSort("purchase_date")
                                            }
                                        >
                                            Date{" "}
                                            {renderSortIcon("purchase_date")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() =>
                                                handleSort("due_date")
                                            }
                                        >
                                            Due Date{" "}
                                            {renderSortIcon("due_date")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer text-right"
                                            onClick={() =>
                                                handleSort("grand_total")
                                            }
                                        >
                                            Grand Total{" "}
                                            {renderSortIcon("grand_total")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer text-right"
                                            onClick={() => handleSort("paid")}
                                        >
                                            Paid {renderSortIcon("paid")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer text-right"
                                        >
                                            Due
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() =>
                                                handleSort("approval_status")
                                            }
                                        >
                                            Approval Status{" "}
                                            {renderSortIcon("approval_status")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() => handleSort("status")}
                                        >
                                            Bill Status{" "}
                                            {renderSortIcon("status")}
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {bills.length > 0 ? (
                                        bills.map((bill) => (
                                            <TableRow key={bill.id}>
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selectedBills.includes(
                                                            bill.id
                                                        )}
                                                        onCheckedChange={() =>
                                                            toggleSelectBill(
                                                                bill.id
                                                            )
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    {bill.bill_no}
                                                </TableCell>
                                                <TableCell>
                                                    {bill.vendor
                                                        ? bill.vendor.name
                                                        : "-"}
                                                </TableCell>
                                                <TableCell>
                                                    {bill.purchase_date}
                                                </TableCell>
                                                <TableCell>
                                                    {bill.due_date}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                {bill.grand_total !== bill.converted_total ? (
                                                    <span>
                                                    {formatCurrency({ amount: bill.grand_total, currency: bill.business.currency })} ({formatCurrency({ amount: bill.converted_total, currency: bill.currency })})
                                                    </span>
                                                ) : (
                                                    <span>
                                                    {formatCurrency({ amount: bill.grand_total, currency: bill.business.currency })}
                                                    </span>
                                                )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency({
                                                        amount: bill.paid,
                                                    })}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                {bill.grand_total !== bill.converted_total ? (
                                                    <span>
                                                    {formatCurrency({ amount: bill.grand_total - bill.paid, currency: bill.business.currency })} ({formatCurrency({ amount: bill.converted_total - bill.paid, currency: bill.currency })})
                                                    </span>
                                                ) : (
                                                    <span>
                                                    {formatCurrency({ amount: bill.grand_total - bill.paid, currency: bill.business.currency })}
                                                    </span>
                                                )}
                                                </TableCell>
                                                <TableCell>
                                                    <BillApprovalStatusBadge
                                                        status={
                                                            bill.approval_status
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <BillStatusBadge
                                                        status={bill.status}
                                                    />
                                                </TableCell>
                                                <TableCell className="text-right">
                                                <TableActions
                                                    actions={[
                                                    {
                                                        label: "Restore",
                                                        icon: <RotateCcw className="h-4 w-4" />,
                                                        onClick: () => handleRestoreConfirm(bill.id)
                                                    },
                                                    {
                                                        label: "Permanently Delete",
                                                        icon: <Trash className="h-4 w-4" />,
                                                        onClick: () => handleDeleteConfirm(bill.id),
                                                        destructive: true,
                                                    },
                                                    ]}
                                                />
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell
                                                colSpan={11}
                                                className="h-24 text-center"
                                            >
                                                No bills found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {bills.length > 0 && meta.total > 0 && (
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

            <DeleteBillModal
                show={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                onConfirm={handleDelete}
                processing={processing}
            />

            <DeleteAllBillsModal
                show={showDeleteAllModal}
                onClose={() => setShowDeleteAllModal(false)}
                onConfirm={handleDeleteAll}
                processing={processing}
                count={selectedBills.length}
            />

            <RestoreBillModal
                show={showRestoreModal}
                onClose={() => setShowRestoreModal(false)}
                onConfirm={handleRestore}
                processing={processing}
            />

            <RestoreAllBillsModal
                show={showRestoreAllModal}
                onClose={() => setShowRestoreAllModal(false)}
                onConfirm={handleRestoreAll}
                processing={processing}
                count={selectedBills.length}
            />
        </AuthenticatedLayout>
    );
}

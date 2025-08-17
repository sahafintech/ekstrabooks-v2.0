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

const DeletePurchaseReturnModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to permanently delete this purchase return?
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
                    Permanently Delete Purchase Return
                </Button>
            </div>
        </form>
    </Modal>
);

const DeleteAllPurchaseReturnsModal = ({
    show,
    onClose,
    onConfirm,
    processing,
    count,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to permanently delete {count} selected purchase return
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

const RestorePurchaseReturnModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to restore this purchase return?
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
                    Restore Purchase Return
                </Button>
            </div>
        </form>
    </Modal>
);

const RestoreAllPurchaseReturnsModal = ({
    show,
    onClose,
    onConfirm,
    processing,
    count,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to restore {count} selected purchase return
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

const PurchaseReturnApprovalStatusBadge = ({ status }) => {
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

const PurchaseReturnStatusBadge = ({ status }) => {
    const statusMap = {
        0: {
            label: "Active",
            className: "text-blue-600 bg-blue-200 px-3 py-1 rounded text-xs",
        },
        1: {
            label: "Refunded",
            className: "text-green-600 bg-green-200 px-3 py-1 rounded text-xs",
        },
        2: {
            label: "Partially Refunded",
            className: "text-yellow-600 bg-yellow-200 px-3 py-1 rounded text-xs",
        },
    };

    return (
        <span className={statusMap[status].className}>
            {statusMap[status].label}
        </span>
    );
};

export default function TrashList({
    purchaseReturns = [],
    meta = {},
    filters = {},
    vendors = [],
}) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [selectedPurchaseReturns, setSelectedPurchaseReturns] = useState([]);
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
    const [selectedStatus, setSelectedStatus] = useState(
        filters.status || ""
    );

    // Delete confirmation modal states
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [purchaseReturnToDelete, setPurchaseReturnToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);
    const [showRestoreAllModal, setShowRestoreAllModal] = useState(false);
    const [purchaseReturnToRestore, setPurchaseReturnToRestore] = useState(null);
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
        setSelectedPurchaseReturns(checked ? purchaseReturns.map((purchaseReturn) => purchaseReturn.id) : []);
    };

    const toggleSelectPurchaseReturn = (purchaseReturnId) => {
        setSelectedPurchaseReturns((prev) => {
            if (prev.includes(purchaseReturnId)) {
                return prev.filter((id) => id !== purchaseReturnId);
            } else {
                return [...prev, purchaseReturnId];
            }
        });
    };

    const handleDeleteConfirm = (purchaseReturnId) => {
        setPurchaseReturnToDelete(purchaseReturnId);
        setShowDeleteModal(true);
    };

    const handleRestoreConfirm = (purchaseReturnId) => {
        setPurchaseReturnToRestore(purchaseReturnId);
        setShowRestoreModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.delete(route("purchase_returns.permanent_destroy", purchaseReturnToDelete), {
            onSuccess: () => {
                setShowDeleteModal(false);
                setPurchaseReturnToDelete(null);
                setProcessing(false);
                setSelectedPurchaseReturns((prev) =>
                    prev.filter((id) => id !== purchaseReturnToDelete)
                );
                toast({
                    title: "Purchase Return Deleted",
                    description: "Purchase return has been deleted successfully.",
                });
            },
            onError: () => {
                setProcessing(false);
                toast({
                    variant: "destructive",
                    title: "Error",
                    description: "There was an error deleting the purchase return.",
                });
            },
        });
    };

    const handleDeleteAll = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(
            route("purchase_returns.bulk_permanent_destroy"),
            {
                ids: selectedPurchaseReturns,
            },
            {
                onSuccess: () => {
                    setShowDeleteAllModal(false);
                    setProcessing(false);
                    setSelectedPurchaseReturns([]);
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

        router.post(route("purchase_returns.restore", purchaseReturnToRestore), {
            onSuccess: () => {
                setShowRestoreModal(false);
                setPurchaseReturnToRestore(null);
                setProcessing(false);
                setSelectedPurchaseReturns((prev) =>
                    prev.filter((id) => id !== purchaseReturnToRestore)
                );
                toast({
                    title: "Purchase Return Restored",
                    description: "Purchase return has been restored successfully.",
                });
            },
            onError: () => {
                setProcessing(false);
                toast({
                    variant: "destructive",
                    title: "Error",
                    description: "There was an error restoring the purchase return.",
                });
            },
        });
    };

    const handleRestoreAll = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(
            route("purchase_returns.bulk_restore"),
            {
                ids: selectedPurchaseReturns,
            },
            {
                onSuccess: () => {
                    setShowRestoreAllModal(false);
                    setProcessing(false);
                    setSelectedPurchaseReturns([]);
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
            route("purchase_returns.trash"),
            {
                search: value,
                page: 1,
                per_page: perPage,
                vendor_id: selectedVendor,
                date_range: dateRange,
                approval_status: selectedApprovalStatus,
                status: selectedStatus,
            },
            { preserveState: true }
        );
    };

    const handlePerPageChange = (value) => {
        setPerPage(parseInt(value));
        router.get(
            route("purchase_returns.trash"),
            {
                search,
                page: 1,
                per_page: value,
                vendor_id: selectedVendor,
                date_range: dateRange,
                approval_status: selectedApprovalStatus,
                status: selectedStatus,
            },
            { preserveState: true }
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("purchase_returns.trash"),
            {
                search,
                page,
                per_page: perPage,
                vendor_id: selectedVendor,
                date_range: dateRange,
                approval_status: selectedApprovalStatus,
                status: selectedStatus,
                sorting: sorting
            },
            { preserveState: true }
        );
    };

    const handleVendorChange = (value) => {
        setSelectedVendor(value);
        router.get(
            route("purchase_returns.trash"),
            {
                search,
                page: 1,
                per_page: perPage,
                vendor_id: value,
                date_range: dateRange,
                approval_status: selectedApprovalStatus,
                status: selectedStatus,
            },
            { preserveState: true }
        );
    };

    const handleDateRangeChange = (dates) => {
        setDateRange(dates);
        router.get(
            route("purchase_returns.trash"),
            {
                search,
                page: 1,
                per_page: perPage,
                vendor_id: selectedVendor,
                date_range: dates,
                approval_status: selectedApprovalStatus,
                status: selectedStatus,
            },
            { preserveState: true }
        );
    };

    const handleStatusChange = (value) => {
        setSelectedStatus(value);
        router.get(
            route("purchase_returns.trash"),
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
            route("purchase_returns.trash"),
            { 
                search, 
                page: 1, 
                per_page: perPage,
                vendor_id: selectedVendor,
                date_range: dateRange,
                approval_status: value || null,
                status: selectedStatus
            },
            { preserveState: true }
        );
    };

    const handleBulkAction = () => {
        if (bulkAction === "delete" && selectedPurchaseReturns.length > 0) {
            setShowDeleteAllModal(true);
        }else if (bulkAction === "restore" && selectedPurchaseReturns.length > 0) {
            setShowRestoreAllModal(true);
        }
    };

    const handleExport = () => {
        window.location.href = route("purchase_returns.export");
    };

    const handleSort = (column) => {
        let direction = "asc";
        if (sorting.column === column && sorting.direction === "asc") {
            direction = "desc";
        }
        setSorting({ column, direction });
        router.get(
            route("purchase_returns.trash"),
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
                        page="Purchase Returns"
                        subpage="Trash"
                        url="purchase_returns.index"
                    />
                    <div className="p-4">
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div>
                                <div className="text-red-500">
                                    Total trashed purchase returns: {meta.total}
                                </div>
                            </div>
                            <div className="flex flex-col md:flex-row gap-4 md:items-center">
                                <Input
                                    placeholder="Search trashed purchase returns..."
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
                                        { id: "1", name: "Refunded" },
                                        { id: "2", name: "Partially Refunded" },
                                    ]}
                                    value={selectedStatus}
                                    onChange={handleStatusChange}
                                    placeholder="Select return status"
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
                                                handleSort("return_number")
                                            }
                                        >
                                            Return Number{" "}
                                            {renderSortIcon("return_number")}
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
                                                handleSort("return_date")
                                            }
                                        >
                                            Date{" "}
                                            {renderSortIcon("return_date")}
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
                                            Return Status{" "}
                                            {renderSortIcon("status")}
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {purchaseReturns.length > 0 ? (
                                        purchaseReturns.map((purchaseReturn) => (
                                            <TableRow key={purchaseReturn.id}>
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selectedPurchaseReturns.includes(
                                                            purchaseReturn.id
                                                        )}
                                                        onCheckedChange={() =>
                                                            toggleSelectPurchaseReturn(
                                                                purchaseReturn.id
                                                            )
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    {purchaseReturn.return_number}
                                                </TableCell>
                                                <TableCell>
                                                    {purchaseReturn.vendor
                                                        ? purchaseReturn.vendor.name
                                                        : "-"}
                                                </TableCell>
                                                <TableCell>
                                                    {purchaseReturn.return_date}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                {purchaseReturn.grand_total !== purchaseReturn.converted_total ? (
                                                    <span>
                                                    {formatCurrency({ amount: purchaseReturn.grand_total, currency: purchaseReturn.business.currency })} ({formatCurrency({ amount: purchaseReturn.converted_total, currency: purchaseReturn.currency })})
                                                    </span>
                                                ) : (
                                                    <span>
                                                    {formatCurrency({ amount: purchaseReturn.grand_total, currency: purchaseReturn.business.currency })}
                                                    </span>
                                                )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency({
                                                        amount: purchaseReturn.paid,
                                                    })}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                {purchaseReturn.grand_total !== purchaseReturn.converted_total ? (
                                                    <span>
                                                    {formatCurrency({ amount: purchaseReturn.grand_total - purchaseReturn.paid, currency: purchaseReturn.business.currency })} ({formatCurrency({ amount: purchaseReturn.converted_total - purchaseReturn.paid, currency: purchaseReturn.currency })})
                                                    </span>
                                                ) : (
                                                    <span>
                                                    {formatCurrency({ amount: purchaseReturn.grand_total - purchaseReturn.paid, currency: purchaseReturn.business.currency })}
                                                    </span>
                                                )}
                                                </TableCell>
                                                <TableCell>
                                                    <PurchaseReturnApprovalStatusBadge
                                                        status={
                                                            purchaseReturn.approval_status
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <PurchaseReturnStatusBadge
                                                        status={purchaseReturn.status}
                                                    />
                                                </TableCell>
                                                <TableCell className="text-right">
                                                <TableActions
                                                    actions={[
                                                    {
                                                        label: "Restore",
                                                        icon: <RotateCcw className="h-4 w-4" />,
                                                        onClick: () => handleRestoreConfirm(purchaseReturn.id)
                                                    },
                                                    {
                                                        label: "Permanently Delete",
                                                        icon: <Trash className="h-4 w-4" />,
                                                        onClick: () => handleDeleteConfirm(purchaseReturn.id),
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
                                                No purchase returns found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {purchaseReturns.length > 0 && meta.total > 0 && (
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

            <DeletePurchaseReturnModal
                show={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                onConfirm={handleDelete}
                processing={processing}
            />

            <DeleteAllPurchaseReturnsModal
                show={showDeleteAllModal}
                onClose={() => setShowDeleteAllModal(false)}
                onConfirm={handleDeleteAll}
                processing={processing}
                count={selectedPurchaseReturns.length}
            />

            <RestorePurchaseReturnModal
                show={showRestoreModal}
                onClose={() => setShowRestoreModal(false)}
                onConfirm={handleRestore}
                processing={processing}
            />

            <RestoreAllPurchaseReturnsModal
                show={showRestoreAllModal}
                onClose={() => setShowRestoreAllModal(false)}
                onConfirm={handleRestoreAll}
                processing={processing}
                count={selectedPurchaseReturns.length}
            />
        </AuthenticatedLayout>
    );
}

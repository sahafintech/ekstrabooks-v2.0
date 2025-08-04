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
    Trash
} from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { formatCurrency } from "@/lib/utils";
import DateTimePicker from "@/Components/DateTimePicker";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";

const DeletePurchaseOrderModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to permanently delete this purchase order?
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
                    Permanently Delete Purchase Order
                </Button>
            </div>
        </form>
    </Modal>
);

const DeleteAllPurchaseOrdersModal = ({
    show,
    onClose,
    onConfirm,
    processing,
    count,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete {count} selected purchase order
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
                    Delete Selected
                </Button>
            </div>
        </form>
    </Modal>
);

const RestorePurchaseOrderModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to restore this purchase order?
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
                    Restore Purchase Order
                </Button>
            </div>
        </form>
    </Modal>
);

const RestoreAllPurchaseOrdersModal = ({
    show,
    onClose,
    onConfirm,
    processing,
    count,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to restore {count} selected purchase order
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

const PurchaseOrderStatusBadge = ({ status }) => {
    const statusMap = {
        0: { label: "Active", className: "text-blue-500" },
        1: { label: "Converted", className: "text-green-500" },
    };

    return (
        <span className={statusMap[status].className}>
            {statusMap[status].label}
        </span>
    );
};

export default function TrashList({
    orders = [],
    meta = {},
    filters = {},
    vendors = [],
}) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [selectedPurchaseOrders, setSelectedPurchaseOrders] = useState([]);
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
    const [dateRange, setDateRange] = useState(filters.date_range || "");

    // Delete confirmation modal states
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [purchaseOrderToDelete, setPurchaseOrderToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);
    const [showRestoreModal, setShowRestoreModal] = useState(false);
    const [purchaseOrderToRestore, setPurchaseOrderToRestore] = useState(null);
    const [showRestoreAllModal, setShowRestoreAllModal] = useState(false);

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
        setSelectedPurchaseOrders(
            checked ? orders.map((order) => order.id) : []
        );
    };

    const toggleSelectPurchaseOrder = (purchaseOrderId) => {
        setSelectedPurchaseOrders((prev) => {
            if (prev.includes(purchaseOrderId)) {
                return prev.filter((id) => id !== purchaseOrderId);
            } else {
                return [...prev, purchaseOrderId];
            }
        });
    };

    const handleDeleteConfirm = (purchaseOrderId) => {
        setPurchaseOrderToDelete(purchaseOrderId);
        setShowDeleteModal(true);
    };

    const handleRestoreConfirm = (purchaseOrderId) => {
        setPurchaseOrderToRestore(purchaseOrderId);
        setShowRestoreModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.delete(route("purchase_orders.permanent_destroy", purchaseOrderToDelete), {
            onSuccess: () => {
                setShowDeleteModal(false);
                setPurchaseOrderToDelete(null);
                setProcessing(false);
                setSelectedPurchaseOrders((prev) =>
                    prev.filter((id) => id !== purchaseOrderToDelete)
                );
                toast({
                    title: "Purchase Order Deleted",
                    description:
                        "purchase order has been deleted successfully.",
                });
            },
            onError: () => {
                setProcessing(false);
                toast({
                    variant: "destructive",
                    title: "Error",
                    description:
                        "There was an error deleting the purchase order.",
                });
            },
        });
    };

    const handleDeleteAll = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(
            route("purchase_orders.bulk_permanent_destroy"),
            {
                ids: selectedPurchaseOrders,
            },
            {
                onSuccess: () => {
                    setShowDeleteAllModal(false);
                    setProcessing(false);
                    setSelectedPurchaseOrders([]);
                    setIsAllSelected(false);
                    toast({
                        title: "Purchase Orders Deleted",
                        description:
                            "Selected purchase orders have been deleted successfully.",
                    });
                },
                onError: () => {
                    setProcessing(false);
                    toast({
                        variant: "destructive",
                        title: "Error",
                        description:
                            "There was an error deleting the selected purchase orders.",
                    });
                },
            }
        );
    };

    const handleRestore = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(route("purchase_orders.restore", purchaseOrderToRestore), {
            onSuccess: () => {
                setShowRestoreModal(false);
                setPurchaseOrderToRestore(null);
                setProcessing(false);
                setSelectedPurchaseOrders((prev) =>
                    prev.filter((id) => id !== purchaseOrderToRestore)
                );
                toast({
                    title: "Purchase Order Restored",
                    description:
                        "purchase order has been restored successfully.",
                });
            },
            onError: () => {
                setProcessing(false);
                toast({
                    variant: "destructive",
                    title: "Error",
                    description:
                        "There was an error restoring the purchase order.",
                });
            },
        });
    };

    const handleRestoreAll = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(
            route("purchase_orders.bulk_restore"),
            {
                ids: selectedPurchaseOrders,
            },
            {
                onSuccess: () => {
                    setShowRestoreAllModal(false);
                    setProcessing(false);
                    setSelectedPurchaseOrders([]);
                    setIsAllSelected(false);
                    toast({
                        title: "Purchase Orders Restored",
                        description:
                            "Selected purchase orders have been restored successfully.",
                    });
                },
                onError: () => {
                    setProcessing(false);
                    toast({
                        variant: "destructive",
                        title: "Error",
                        description:
                            "There was an error restoring the selected purchase orders.",
                    });
                },
            }
        );
    };

    const handleSearch = (e) => {
        e.preventDefault();
        const value = e.target.value;
        setSearch(value);

        router.get(
            route("purchase_orders.trash"),
            { search: value, page: 1, per_page: perPage },
            { preserveState: true }
        );
    };

    const handlePerPageChange = (value) => {
        setPerPage(parseInt(value));
        router.get(
            route("purchase_orders.trash"),
            { search, page: 1, per_page: value },
            { preserveState: true }
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("purchase_orders.trash"),
            { 
                search,
                page,
                per_page: perPage,
                vendor_id: selectedVendor,
                date_range: dateRange,
                sorting: sorting
            },
            { preserveState: true }
        );
    };

    const handleBulkAction = () => {
        if (bulkAction === "delete" && selectedPurchaseOrders.length > 0) {
            setShowDeleteAllModal(true);
        }else if (bulkAction === "restore" && selectedPurchaseOrders.length > 0) {
            setShowRestoreAllModal(true);
        }
    };

    const handleSort = (column) => {
        let direction = "asc";
        if (sorting.column === column && sorting.direction === "asc") {
            direction = "desc";
        }
        setSorting({ column, direction });
        router.get(
            route("purchase_orders.trash"),
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

    const handleVendorChange = (value) => {
        setSelectedVendor(value);
        router.get(
            route("purchase_orders.trash"),
            { ...filters, vendor_id: value },
            { preserveState: true }
        );
    };

    const handleDateRangeChange = (value) => {
        setDateRange(value);
        router.get(
            route("purchase_orders.trash"),
            { ...filters, date_range: value },
            { preserveState: true }
        );
    };

    return (
        <AuthenticatedLayout>
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Purchase Orders"
                        subpage="List"
                        url="purchase_orders.index"
                    />
                    <div className="p-4">
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div>
                                <div className="text-red-500">
                                    Total trashed purchase orders: {meta.total}
                                </div>
                            </div>
                            <div className="flex flex-col md:flex-row gap-4 md:items-center">
                                <Input
                                    placeholder="Search trashed purchase orders..."
                                    value={search}
                                    onChange={(e) => handleSearch(e)}
                                    className="w-full md:w-80"
                                />
                            </div>
                        </div>

                        <div className="mb-4 flex flex-col md:flex-row gap-4 justify-between">
                            <div className="flex flex-col md:flex-row md:items-center gap-2">
                                <div className="flex items-center gap-2">
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
                                </div>
                                <SearchableCombobox
                                    options={[
                                        { id: "", name: "All Vendors" },
                                        ...vendors.map((vendor) => ({
                                            id: vendor.id.toString(),
                                            name: vendor.name,
                                        })),
                                    ]}
                                    value={selectedVendor}
                                    onChange={handleVendorChange}
                                    className="w-[200px]"
                                    placeholder="Select Vendor"
                                />
                                <DateTimePicker
                                    value={dateRange}
                                    onChange={handleDateRangeChange}
                                    className="w-[200px]"
                                    isRange={true}
                                    placeholder="Select date range"
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
                                                handleSort("order_number")
                                            }
                                        >
                                            Order Number{" "}
                                            {renderSortIcon("order_number")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() =>
                                                handleSort("vendor.name")
                                            }
                                        >
                                            Supplier{" "}
                                            {renderSortIcon("vendor.name")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() =>
                                                handleSort("order_date")
                                            }
                                        >
                                            Order Date{" "}
                                            {renderSortIcon("order_date")}
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
                                            onClick={() => handleSort("status")}
                                        >
                                            Status {renderSortIcon("status")}
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {orders.length > 0 ? (
                                        orders.map((order) => (
                                            <TableRow key={order.id}>
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selectedPurchaseOrders.includes(
                                                            order.id
                                                        )}
                                                        onCheckedChange={() =>
                                                            toggleSelectPurchaseOrder(
                                                                order.id
                                                            )
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    {order.order_number}
                                                </TableCell>
                                                <TableCell>
                                                    {order.vendor
                                                        ? order.vendor.name
                                                        : "-"}
                                                </TableCell>
                                                <TableCell>
                                                    {order.order_date}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {order.grand_total !== order.converted_total ? (
                                                        <span>
                                                        {formatCurrency({ amount: order.grand_total, currency: order.business.currency })} ({formatCurrency({ amount: order.converted_total, currency: order.currency })})
                                                        </span>
                                                    ) : (
                                                        <span>
                                                        {formatCurrency({ amount: order.grand_total, currency: order.business.currency })}
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <PurchaseOrderStatusBadge
                                                        status={order.status}
                                                    />
                                                </TableCell>
                                                <TableCell className="text-right">
                                                <TableActions
                                                    actions={[
                                                    {
                                                        label: "Restore",
                                                        icon: <RotateCcw className="h-4 w-4" />,
                                                        onClick: () => handleRestoreConfirm(order.id)
                                                    },
                                                    {
                                                        label: "Permanently Delete",
                                                        icon: <Trash className="h-4 w-4" />,
                                                        onClick: () => handleDeleteConfirm(order.id),
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
                                                colSpan={8}
                                                className="h-24 text-center"
                                            >
                                                No purchase orders found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {orders.length > 0 && meta.total > 0 && (
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

            <DeletePurchaseOrderModal
                show={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                onConfirm={handleDelete}
                processing={processing}
            />

            <DeleteAllPurchaseOrdersModal
                show={showDeleteAllModal}
                onClose={() => setShowDeleteAllModal(false)}
                onConfirm={handleDeleteAll}
                processing={processing}
                count={selectedPurchaseOrders.length}
            />

            <RestorePurchaseOrderModal
                show={showRestoreModal}
                onClose={() => setShowRestoreModal(false)}
                onConfirm={handleRestore}
                processing={processing}
            />

            <RestoreAllPurchaseOrdersModal
                show={showRestoreAllModal}
                onClose={() => setShowRestoreAllModal(false)}
                onConfirm={handleRestoreAll}
                processing={processing}
                count={selectedPurchaseOrders.length}
            />
        </AuthenticatedLayout>
    );
}

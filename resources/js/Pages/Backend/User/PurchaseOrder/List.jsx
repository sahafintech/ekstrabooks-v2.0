import React, { useState, useEffect } from "react";
import { Head, Link, router, usePage } from "@inertiajs/react";
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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import { Input } from "@/Components/ui/input";
import {
    MoreVertical,
    FileUp,
    FileDown,
    Plus,
    Eye,
    Trash2,
    Edit,
    Check,
    X,
    ChevronUp,
    ChevronDown,
    ShoppingCart,
    DollarSign,
    FileText,
    CreditCard,
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
                Are you sure you want to delete this purchase order?
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
                    Delete Purchase Order
                </Button>
            </div>
        </form>
    </Modal>
);

const ConvertToBillModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to convert this purchase order to a bill?
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
                <Button type="submit" variant="default" disabled={processing}>
                    Convert to Bill
                </Button>
            </div>
        </form>
    </Modal>
);

const ConvertToCashPurchaseModal = ({
    show,
    onClose,
    onConfirm,
    processing,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to convert this purchase order to a cash
                purchase?
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
                <Button type="submit" variant="default" disabled={processing}>
                    Convert to Cash Purchase
                </Button>
            </div>
        </form>
    </Modal>
);

const ImportPurchaseOrdersModal = ({ show, onClose, onSubmit, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onSubmit} className="p-6">
            <div className="ti-modal-header">
                <h3 className="text-lg font-bold">Import Purchase Orders</h3>
            </div>
            <div className="ti-modal-body grid grid-cols-12">
                <div className="col-span-12">
                    <div className="flex items-center justify-between">
                        <label className="block font-medium text-sm text-gray-700">
                            Purchase Orders File
                        </label>
                        <Link href="/uploads/media/default/sample_purchase_orders.xlsx">
                            <Button variant="secondary" size="sm">
                                Use This Sample File
                            </Button>
                        </Link>
                    </div>
                    <input
                        type="file"
                        className="w-full dropify"
                        name="purchase_orders_file"
                        required
                    />
                </div>
                <div className="col-span-12 mt-4">
                    <ul className="space-y-3 text-sm">
                        <li className="flex space-x-3">
                            <span className="text-primary bg-primary/20 rounded-full px-1">
                                ✓
                            </span>
                            <span className="text-gray-800 dark:text-white/70">
                                Maximum File Size: 1 MB
                            </span>
                        </li>
                        <li className="flex space-x-3">
                            <span className="text-primary bg-primary/20 rounded-full px-1">
                                ✓
                            </span>
                            <span className="text-gray-800 dark:text-white/70">
                                File format Supported: CSV, TSV, XLS
                            </span>
                        </li>
                        <li className="flex space-x-3">
                            <span className="text-primary bg-primary/20 rounded-full px-1">
                                ✓
                            </span>
                            <span className="text-gray-800 dark:text-white/70">
                                Make sure the format of the import file matches
                                our sample file by comparing them.
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
            <div className="ti-modal-footer flex justify-end mt-4">
                <Button
                    type="button"
                    variant="secondary"
                    onClick={onClose}
                    className="mr-3"
                >
                    Close
                </Button>
                <Button type="submit" disabled={processing}>
                    Import Purchase Orders
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

const SummaryCards = ({ summary = {} }) => {
    const cards = [
        {
            title: "Total Orders",
            value: summary.total_orders || 0,
            description: "Total purchase orders",
            icon: ShoppingCart,
            iconColor: "text-blue-500",
        },
        {
            title: "Grand Total",
            value: formatCurrency({
                amount: summary.grand_total || 0,
                currency: "USD", // This will be overridden by the actual currency in the component
            }),
            description: "Total amount of all orders",
            icon: DollarSign,
            iconColor: "text-green-500",
        },
        {
            title: "Total Converted",
            value: summary.total_converted || 0,
            description: "Orders converted to bills",
            icon: FileText,
            iconColor: "text-purple-500",
        },
        {
            title: "Converted Grand Total",
            value: formatCurrency({
                amount: summary.converted_grand_total || 0,
                currency: "USD", // This will be overridden by the actual currency in the component
            }),
            description: "Total amount of converted orders",
            icon: CreditCard,
            iconColor: "text-orange-500",
        },
    ];

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {cards.map((card, index) => (
                <div key={index} className="bg-white rounded-lg shadow-sm p-4">
                    <div className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <h3 className="text-sm font-medium">{card.title}</h3>
                        <card.icon className={`h-4 w-4 ${card.iconColor}`} />
                    </div>
                    <div className="text-2xl font-bold">
                        {card.value}
                        <p className="text-xs text-muted-foreground">
                            {card.description}
                        </p>
                    </div>
                </div>
            ))}
        </div>
    );
};

export default function List({
    orders = [],
    meta = {},
    filters = {},
    vendors = [],
    summary = {},
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
    const [showImportModal, setShowImportModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [purchaseOrderToDelete, setPurchaseOrderToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);
    const [showConvertToBillModal, setShowConvertToBillModal] = useState(false);
    const [showConvertToCashPurchaseModal, setShowConvertToCashPurchaseModal] =
        useState(false);

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

    const handleConvertToBillConfirm = (purchaseOrderId) => {
        setPurchaseOrderToDelete(purchaseOrderId);
        setShowConvertToBillModal(true);
    };

    const handleConvertToCashPurchaseConfirm = (purchaseOrderId) => {
        setPurchaseOrderToDelete(purchaseOrderId);
        setShowConvertToCashPurchaseModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.delete(route("purchase_orders.destroy", purchaseOrderToDelete), {
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

    const handleConvertToBill = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(
            route("purchase_orders.convert_to_bill", purchaseOrderToDelete),
            {
                onSuccess: () => {
                    setShowConvertToBillModal(false);
                    setPurchaseOrderToDelete(null);
                    setProcessing(false);
                    setSelectedPurchaseOrders((prev) =>
                        prev.filter((id) => id !== purchaseOrderToDelete)
                    );
                },
                onError: () => {
                    setProcessing(false);
                },
            }
        );
    };

    const handleConvertToCashPurchase = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(
            route(
                "purchase_orders.convert_to_cash_purchase",
                purchaseOrderToDelete
            ),
            {
                onSuccess: () => {
                    setShowConvertToCashPurchaseModal(false);
                    setPurchaseOrderToDelete(null);
                    setProcessing(false);
                    setSelectedPurchaseOrders((prev) =>
                        prev.filter((id) => id !== purchaseOrderToDelete)
                    );
                    toast({
                        title: "Purchase Order Converted to Cash Purchase",
                        description:
                            "purchase order has been converted to cash purchase successfully.",
                    });
                },
                onError: () => {
                    setProcessing(false);
                    toast({
                        variant: "destructive",
                        title: "Error",
                        description:
                            "There was an error converting the purchase order to cash purchase.",
                    });
                },
            }
        );
    };

    const handleDeleteAll = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(
            route("purchase_orders.bulk_destroy"),
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

    const handleImport = (e) => {
        e.preventDefault();
        setProcessing(true);

        const formData = new FormData(e.target);

        router.post(route("purchase_orders.import"), formData, {
            onSuccess: () => {
                setShowImportModal(false);
                setProcessing(false);
                toast({
                    title: "Import Successful",
                    description:
                        "Purchase orders have been imported successfully.",
                });
            },
            onError: (errors) => {
                setProcessing(false);
                toast({
                    variant: "destructive",
                    title: "Import Failed",
                    description: Object.values(errors).flat().join(", "),
                });
            },
        });
    };

    const handleSearch = (e) => {
        e.preventDefault();
        const value = e.target.value;
        setSearch(value);

        router.get(
            route("purchase_orders.index"),
            { search: value, page: 1, per_page: perPage },
            { preserveState: true }
        );
    };

    const handlePerPageChange = (value) => {
        setPerPage(parseInt(value));
        router.get(
            route("purchase_orders.index"),
            { search, page: 1, per_page: value },
            { preserveState: true }
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("purchase_orders.index"),
            { search, page, per_page: perPage },
            { preserveState: true }
        );
    };

    const handleBulkAction = () => {
        if (bulkAction === "delete" && selectedPurchaseOrders.length > 0) {
            setShowDeleteAllModal(true);
        }
    };

    const handleSort = (column) => {
        let direction = "asc";
        if (sorting.column === column && sorting.direction === "asc") {
            direction = "desc";
        }
        setSorting({ column, direction });
        router.get(
            route("purchase_orders.index"),
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
            route("purchase_orders.index"),
            { ...filters, vendor_id: value },
            { preserveState: true }
        );
    };

    const handleDateRangeChange = (value) => {
        setDateRange(value);
        router.get(
            route("purchase_orders.index"),
            { ...filters, date_range: value },
            { preserveState: true }
        );
    };

    return (
        <AuthenticatedLayout>
            <Head title="Purchase Orders" />
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Purchase Orders"
                        subpage="List"
                        url="purchase_orders.index"
                    />
                    <div className="p-4">
                        <SummaryCards summary={summary} />
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div className="flex flex-col md:flex-row gap-4">
                                <Link href={route("purchase_orders.create")}>
                                    <Button>
                                        <Plus className="h-4 w-4 mr-2" />
                                        Add Purchase Order
                                    </Button>
                                </Link>
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="secondary">
                                            <MoreVertical className="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem
                                            onClick={() =>
                                                setShowImportModal(true)
                                            }
                                        >
                                            <FileUp className="mr-2 h-4 w-4" />{" "}
                                            Import
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            onClick={() =>
                                                (window.location.href = route(
                                                    "purchase_orders.export"
                                                ))
                                            }
                                        >
                                            <FileDown className="mr-2 h-4 w-4" />{" "}
                                            Export
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                            <div className="flex flex-col md:flex-row gap-4 md:items-center">
                                <Input
                                    placeholder="Search purchase orders..."
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
                                                Delete Selected
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
                                                    {formatCurrency({
                                                        amount: order.grand_total,
                                                        currency:
                                                            order.currency,
                                                    })}
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
                                                                label: "View",
                                                                icon: (
                                                                    <Eye className="h-4 w-4" />
                                                                ),
                                                                href: route(
                                                                    "purchase_orders.show",
                                                                    order.id
                                                                ),
                                                            },
                                                            {
                                                                label: "Convert to bill",
                                                                icon: (
                                                                    <FileUp className="h-4 w-4" />
                                                                ),
                                                                onClick: () =>
                                                                    handleConvertToBillConfirm(
                                                                        order.id
                                                                    ),
                                                            },
                                                            {
                                                                label: "Convert to Cash Purchase",
                                                                icon: (
                                                                    <FileUp className="h-4 w-4" />
                                                                ),
                                                                onClick: () =>
                                                                    handleConvertToCashPurchaseConfirm(
                                                                        order.id
                                                                    ),
                                                            },
                                                            {
                                                                label: "Edit",
                                                                icon: (
                                                                    <Edit className="h-4 w-4" />
                                                                ),
                                                                href: route(
                                                                    "purchase_orders.edit",
                                                                    order.id
                                                                ),
                                                            },
                                                            {
                                                                label: "Delete",
                                                                icon: (
                                                                    <Trash2 className="h-4 w-4" />
                                                                ),
                                                                onClick: () =>
                                                                    handleDeleteConfirm(
                                                                        order.id
                                                                    ),
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

            <ConvertToBillModal
                show={showConvertToBillModal}
                onClose={() => setShowConvertToBillModal(false)}
                onConfirm={handleConvertToBill}
                processing={processing}
            />

            <ConvertToCashPurchaseModal
                show={showConvertToCashPurchaseModal}
                onClose={() => setShowConvertToCashPurchaseModal(false)}
                onConfirm={handleConvertToCashPurchase}
                processing={processing}
            />

            <DeleteAllPurchaseOrdersModal
                show={showDeleteAllModal}
                onClose={() => setShowDeleteAllModal(false)}
                onConfirm={handleDeleteAll}
                processing={processing}
                count={selectedPurchaseOrders.length}
            />

            <ImportPurchaseOrdersModal
                show={showImportModal}
                onClose={() => setShowImportModal(false)}
                onSubmit={handleImport}
                processing={processing}
            />
        </AuthenticatedLayout>
    );
}

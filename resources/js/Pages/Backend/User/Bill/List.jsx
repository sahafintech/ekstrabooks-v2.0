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
    ChevronUp,
    ChevronDown,
    Receipt,
    DollarSign,
    CreditCard,
    AlertCircle,
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
                Are you sure you want to delete this bill?
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
                    Delete Bill
                </Button>
            </div>
        </form>
    </Modal>
);

const ImportBillsModal = ({ show, onClose, onSubmit, processing }) => (
    <Modal show={show} onClose={onClose} maxWidth="3xl">
        <form onSubmit={onSubmit}>
            <div className="ti-modal-header">
                <h3 className="text-lg font-bold">Import Bills</h3>
            </div>
            <div className="ti-modal-body grid grid-cols-12">
                <div className="col-span-12">
                    <div className="flex items-center justify-between">
                        <label className="block font-medium text-sm text-gray-700">
                            Bills File
                        </label>
                        <a
                            href="/uploads/media/default/sample_bills.xlsx"
                            download
                        >
                            <Button variant="secondary" size="sm" type="button">
                                Use This Sample File
                            </Button>
                        </a>
                    </div>
                    <input
                        type="file"
                        className="w-full dropify"
                        name="bills_file"
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
                    Import Bills
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
                Are you sure you want to delete {count} selected bill
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

const ApproveAllBillsModal = ({
    show,
    onClose,
    onConfirm,
    processing,
    count,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to approve {count} selected bill
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
                <Button type="submit" variant="default" disabled={processing}>
                    Approve Selected
                </Button>
            </div>
        </form>
    </Modal>
);

const RejectAllBillsModal = ({
    show,
    onClose,
    onConfirm,
    processing,
    count,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to reject {count} selected bill
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
                    Reject Selected
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

const SummaryCards = ({ summary = {} }) => {
    const cards = [
        {
            title: "Total Bills",
            value: summary.total_bills || 0,
            description: "Total number of bills",
            icon: Receipt,
            iconColor: "text-blue-500"
        },
        {
            title: "Grand Total",
            value: formatCurrency({ amount: summary.total_amount || 0 }),
            description: "Total amount of all bills",
            icon: DollarSign,
            iconColor: "text-green-500"
        },
        {
            title: "Total Paid",
            value: formatCurrency({ amount: summary.total_paid || 0 }),
            description: "Total amount paid",
            icon: CreditCard,
            iconColor: "text-purple-500"
        },
        {
            title: "Total Due",
            value: formatCurrency({ amount: (summary.total_amount || 0) - (summary.total_paid || 0) }),
            description: "Total amount due",
            icon: AlertCircle,
            iconColor: "text-orange-500"
        }
    ];

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {cards.map((card, index) => (
                <div key={index} className="bg-gray-100 rounded-lg shadow-sm p-4">
                    <div className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <h3 className="text-lg font-medium">{card.title}</h3>
                        <card.icon className={`h-8 w-8 ${card.iconColor}`} />
                    </div>
                    <div className="text-2xl font-bold">{card.value}
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
    bills = [],
    meta = {},
    filters = {},
    vendors = [],
    summary = {},
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
    const [showImportModal, setShowImportModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [showApproveAllModal, setShowApproveAllModal] = useState(false);
    const [showRejectAllModal, setShowRejectAllModal] = useState(false);
    const [billToDelete, setBillToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);

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

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.delete(route("bill_invoices.destroy", billToDelete), {
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
            route("bill_invoices.bulk_destroy"),
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

    const handleApproveAll = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(
            route("bill_invoices.bulk_approve"),
            {
                ids: selectedBills,
            },
            {
                onSuccess: () => {
                    setShowApproveAllModal(false);
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

    const handleRejectAll = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(
            route("bill_invoices.bulk_reject"),
            {
                ids: selectedBills,
            },
            {
                onSuccess: () => {
                    setShowRejectAllModal(false);
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

    const handleImport = (e) => {
        e.preventDefault();
        setProcessing(true);

        const formData = new FormData(e.target);

        router.post(route("bill_invoices.import"), formData, {
            onSuccess: () => {
                setShowImportModal(false);
                setProcessing(false);
                toast({
                    title: "Import Successful",
                    description: "Bill have been imported successfully.",
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
        }
        if (bulkAction === "approve" && selectedBills.length > 0) {
            setShowApproveAllModal(true);
        }
        if (bulkAction === "reject" && selectedBills.length > 0) {
            setShowRejectAllModal(true);
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
                        <SummaryCards summary={summary} />
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div className="flex flex-col md:flex-row gap-2">
                                <Link href={route("bill_invoices.create")}>
                                    <Button>
                                        <Plus className="h-4 w-4 mr-2" />
                                        Add Credit Purchase
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
                                            onClick={handleExport}
                                        >
                                            <FileDown className="mr-2 h-4 w-4" />{" "}
                                            Export
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                            <div className="flex flex-col md:flex-row gap-4 md:items-center">
                                <Input
                                    placeholder="Search bills..."
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
                                            Delete Selected
                                        </SelectItem>
                                        <SelectItem value="approve">
                                            Approve Selected
                                        </SelectItem>
                                        <SelectItem value="reject">
                                            Reject Selected
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
                                                    {formatCurrency({
                                                        amount: bill.grand_total,
                                                    })}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency({
                                                        amount: bill.paid,
                                                    })}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency({
                                                        amount:
                                                            bill.grand_total -
                                                            bill.paid,
                                                    })}
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
                                                                label: "View",
                                                                icon: (
                                                                    <Eye className="h-4 w-4" />
                                                                ),
                                                                href: route(
                                                                    "bill_invoices.show",
                                                                    bill.id
                                                                ),
                                                            },
                                                            {
                                                                label: "Edit",
                                                                icon: (
                                                                    <Edit className="h-4 w-4" />
                                                                ),
                                                                href: route(
                                                                    "bill_invoices.edit",
                                                                    bill.id
                                                                ),
                                                            },
                                                            {
                                                                label: "Delete",
                                                                icon: (
                                                                    <Trash2 className="h-4 w-4" />
                                                                ),
                                                                onClick: () =>
                                                                    handleDeleteConfirm(
                                                                        bill.id
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

            <ApproveAllBillsModal
                show={showApproveAllModal}
                onClose={() => setShowApproveAllModal(false)}
                onConfirm={handleApproveAll}
                processing={processing}
                count={selectedBills.length}
            />

            <RejectAllBillsModal
                show={showRejectAllModal}
                onClose={() => setShowRejectAllModal(false)}
                onConfirm={handleRejectAll}
                processing={processing}
                count={selectedBills.length}
            />

            <ImportBillsModal
                show={showImportModal}
                onClose={() => setShowImportModal(false)}
                onSubmit={handleImport}
                processing={processing}
            />
        </AuthenticatedLayout>
    );
}

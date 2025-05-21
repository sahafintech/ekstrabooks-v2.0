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

const DeleteSubcontractModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete this subcontract?
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
                    Delete Subcontract
                </Button>
            </div>
        </form>
    </Modal>
);

const ImportSubcontractsModal = ({ show, onClose, onSubmit, processing }) => (
    <Modal show={show} onClose={onClose} maxWidth="3xl">
        <form onSubmit={onSubmit}>
            <div className="ti-modal-header">
                <h3 className="text-lg font-bold">Import Subcontracts</h3>
            </div>
            <div className="ti-modal-body grid grid-cols-12">
                <div className="col-span-12">
                    <div className="flex items-center justify-between">
                        <label className="block font-medium text-sm text-gray-700">
                            Subcontracts File
                        </label>
                        <a
                            href="/uploads/media/default/sample_project_subcontracts.xlsx"
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
                        name="subcontracts_file"
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
                    Import Subcontracts
                </Button>
            </div>
        </form>
    </Modal>
);

const DeleteAllSubcontractsModal = ({
    show,
    onClose,
    onConfirm,
    processing,
    count,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete {count} selected subcontract
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

const SubcontractStatusBadge = ({ status }) => {
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

const ContractStatusBadge = ({ status }) => {
    const statusMap = {
        0: {
            label: "Draft",
            className: "text-gray-600 bg-gray-200 px-3 py-1 rounded text-xs",
        },
        1: {
            label: "Active",
            className: "text-green-400 bg-green-200 px-3 py-1 rounded text-xs",
        },
        2: {
            label: "Completed",
            className: "text-blue-400 bg-blue-200 px-3 py-1 rounded text-xs",
        },
        3: {
            label: "Terminated",
            className: "text-red-400 bg-red-200 px-3 py-1 rounded text-xs",
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
            title: "Total Subcontracts",
            value: summary.total_subcontracts || 0,
            description: "Total number of subcontracts",
            icon: Receipt,
            iconColor: "text-blue-500"
        },
        {
            title: "Grand Total",
            value: formatCurrency({ amount: summary.total_amount || 0 }),
            description: "Total amount of all subcontracts",
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
                <div key={index} className="bg-white rounded-lg shadow-sm p-4">
                    <div className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <h3 className="text-sm font-medium">{card.title}</h3>
                        <card.icon className={`h-4 w-4 ${card.iconColor}`} />
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
    subcontracts = [],
    meta = {},
    filters = {},
    vendors = [],
    summary = {},
}) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [selectedSubcontracts, setSelectedSubcontracts] = useState([]);
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
    const [selectedContractStatus, setSelectedContractStatus] = useState(
        filters.contract_status || ""
    );
    const [selectedSubcontractStatus, setSelectedSubcontractStatus] = useState(
        filters.status || ""
    );

    // Delete confirmation modal states
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showImportModal, setShowImportModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [subcontractToDelete, setSubcontractToDelete] = useState(null);
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
        setSelectedSubcontracts(checked ? subcontracts.map((subcontract) => subcontract.id) : []);
    };

    const toggleSelectSubcontract = (subcontractId) => {
        setSelectedSubcontracts((prev) => {
            if (prev.includes(subcontractId)) {
                return prev.filter((id) => id !== subcontractId);
            } else {
                return [...prev, subcontractId];
            }
        });
    };

    const handleDeleteConfirm = (subcontractId) => {
        setSubcontractToDelete(subcontractId);
        setShowDeleteModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.delete(route("project_subcontracts.destroy", subcontractToDelete), {
            onSuccess: () => {
                setShowDeleteModal(false);
                setSubcontractToDelete(null);
                setProcessing(false);
                setSelectedSubcontracts((prev) =>
                    prev.filter((id) => id !== subcontractToDelete)
                );
                toast({
                    title: "Subcontract Deleted",
                    description: "Subcontract has been deleted successfully.",
                });
            },
            onError: () => {
                setProcessing(false);
                toast({
                    variant: "destructive",
                    title: "Error",
                    description: "There was an error deleting the subcontract.",
                });
            },
        });
    };

    const handleDeleteAll = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(
            route("project_subcontracts.bulk_destroy"),
            {
                ids: selectedSubcontracts,
            },
            {
                onSuccess: () => {
                    setShowDeleteAllModal(false);
                    setProcessing(false);
                    setSelectedSubcontracts([]);
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

        router.post(route("project_subcontracts.import"), formData, {
            onSuccess: () => {
                setShowImportModal(false);
                setProcessing(false);
                toast({
                    title: "Import Successful",
                    description: "Subcontracts have been imported successfully.",
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
            route("project_subcontracts.index"),
            {
                search: value,
                page: 1,
                per_page: perPage,
                vendor_id: selectedVendor,
                date_range: dateRange,
                contract_status: selectedContractStatus,
                status: selectedSubcontractStatus,
            },
            { preserveState: true }
        );
    };

    const handlePerPageChange = (value) => {
        setPerPage(parseInt(value));
        router.get(
            route("project_subcontracts.index"),
            {
                search,
                page: 1,
                per_page: value,
                vendor_id: selectedVendor,
                date_range: dateRange,
                contract_status: selectedContractStatus,
                status: selectedSubcontractStatus,
            },
            { preserveState: true }
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("project_subcontracts.index"),
            {
                search,
                page,
                per_page: perPage,
                vendor_id: selectedVendor,
                date_range: dateRange,
                contract_status: selectedContractStatus,
                status: selectedSubcontractStatus,
            },
            { preserveState: true }
        );
    };

    const handleVendorChange = (value) => {
        setSelectedVendor(value);
        router.get(
            route("project_subcontracts.index"),
            {
                search,
                page: 1,
                per_page: perPage,
                vendor_id: value,
                date_range: dateRange,
                contract_status: selectedContractStatus,
                status: selectedSubcontractStatus,
            },
            { preserveState: true }
        );
    };

    const handleDateRangeChange = (dates) => {
        setDateRange(dates);
        router.get(
            route("project_subcontracts.index"),
            {
                search,
                page: 1,
                per_page: perPage,
                vendor_id: selectedVendor,
                date_range: dates,
                contract_status: selectedContractStatus,
                status: selectedSubcontractStatus,
            },
            { preserveState: true }
        );
    };

    const handleSubcontractStatusChange = (value) => {
        setSelectedSubcontractStatus(value);
        router.get(
            route("project_subcontracts.index"),
            { 
                search, 
                page: 1, 
                per_page: perPage,
                vendor_id: selectedVendor,
                date_range: dateRange,
                contract_status: selectedContractStatus,
                status: value || null
            },
            { preserveState: true }
        );
    };

    const handleContractStatusChange = (value) => {
        setSelectedContractStatus(value);
        router.get(
            route("project_subcontracts.index"),
            { 
                search, 
                page: 1, 
                per_page: perPage,
                vendor_id: selectedVendor,
                date_range: dateRange,
                contract_status: value || null,
                status: selectedSubcontractStatus
            },
            { preserveState: true }
        );
    };

    const handleBulkAction = () => {
        if (bulkAction === "delete" && selectedSubcontracts.length > 0) {
            setShowDeleteAllModal(true);
        }
    };

    const handleExport = () => {
        window.location.href = route("project_subcontracts.export");
    };

    const handleSort = (column) => {
        let direction = "asc";
        if (sorting.column === column && sorting.direction === "asc") {
            direction = "desc";
        }
        setSorting({ column, direction });
        router.get(
            route("project_subcontracts.index"),
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
            <Head title="Project Subcontracts" />
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Project Subcontracts"
                        subpage="List"
                        url="project_subcontracts.index"
                    />
                    <div className="p-4">
                        <SummaryCards summary={summary} />
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div className="flex flex-col md:flex-row gap-2">
                                <Link href={route("project_subcontracts.create")}>
                                    <Button>
                                        <Plus className="h-4 w-4 mr-2" />
                                        Add Subcontract
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
                                    placeholder="Search subcontracts..."
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
                                        { id: "0", name: "Draft" },
                                        { id: "1", name: "Active" },
                                        { id: "2", name: "Completed" },
                                        { id: "3", name: "Terminated" },
                                    ]}
                                    value={selectedContractStatus}
                                    onChange={handleContractStatusChange}
                                    placeholder="Select contract status"
                                    className="w-[200px]"
                                />

                                <SearchableCombobox
                                    options={[
                                        { id: "", name: "All" },
                                        { id: "0", name: "Active" },
                                        { id: "1", name: "Partial Paid" },
                                        { id: "2", name: "Paid" },
                                    ]}
                                    value={selectedSubcontractStatus}
                                    onChange={handleSubcontractStatusChange}
                                    placeholder="Select payment status"
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
                                                handleSort("subcontract_no")
                                            }
                                        >
                                            Contract No{" "}
                                            {renderSortIcon("subcontract_no")}
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
                                                handleSort("project.name")
                                            }
                                        >
                                            Project{" "}
                                            {renderSortIcon("project.name")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() =>
                                                handleSort("start_date")
                                            }
                                        >
                                            Start Date{" "}
                                            {renderSortIcon("start_date")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() =>
                                                handleSort("end_date")
                                            }
                                        >
                                            End Date{" "}
                                            {renderSortIcon("end_date")}
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
                                            onClick={() => handleSort("due")}
                                        >
                                            Due {renderSortIcon("due")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() =>
                                                handleSort("contract_status")
                                            }
                                        >
                                            Contract Status{" "}
                                            {renderSortIcon("contract_status")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() => handleSort("status")}
                                        >
                                            Payment Status{" "}
                                            {renderSortIcon("status")}
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {subcontracts.length > 0 ? (
                                        subcontracts.map((subcontract) => (
                                            <TableRow key={subcontract.id}>
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selectedSubcontracts.includes(
                                                            subcontract.id
                                                        )}
                                                        onCheckedChange={() =>
                                                            toggleSelectSubcontract(
                                                                subcontract.id
                                                            )
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <Link href={route("project_subcontracts.show", subcontract.id)} className="text-blue-500 underline">
                                                        {subcontract.subcontract_no}
                                                    </Link>
                                                </TableCell>
                                                <TableCell>
                                                    {subcontract.vendor
                                                        ? subcontract.vendor.name
                                                        : "-"}
                                                </TableCell>
                                                <TableCell>
                                                    {subcontract.project
                                                        ? subcontract.project.project_name
                                                        : "-"}
                                                </TableCell>
                                                <TableCell>
                                                    {subcontract.start_date}
                                                </TableCell>
                                                <TableCell>
                                                    {subcontract.end_date}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency({
                                                        amount: subcontract.grand_total,
                                                    })}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency({
                                                        amount: subcontract.paid,
                                                    })}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency({
                                                        amount:
                                                            subcontract.grand_total -
                                                            subcontract.paid,
                                                    })}
                                                </TableCell>
                                                <TableCell>
                                                    <ContractStatusBadge
                                                        status={
                                                            subcontract.contract_status
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <SubcontractStatusBadge
                                                        status={subcontract.status}
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
                                                                    "project_subcontracts.show",
                                                                    subcontract.id
                                                                ),
                                                            },
                                                            {
                                                                label: "Edit",
                                                                icon: (
                                                                    <Edit className="h-4 w-4" />
                                                                ),
                                                                href: route(
                                                                    "project_subcontracts.edit",
                                                                    subcontract.id
                                                                ),
                                                            },
                                                            {
                                                                label: "Delete",
                                                                icon: (
                                                                    <Trash2 className="h-4 w-4" />
                                                                ),
                                                                onClick: () =>
                                                                    handleDeleteConfirm(
                                                                        subcontract.id
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
                                                colSpan={12}
                                                className="h-24 text-center"
                                            >
                                                No subcontracts found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {subcontracts.length > 0 && meta.total > 0 && (
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

            <DeleteSubcontractModal
                show={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                onConfirm={handleDelete}
                processing={processing}
            />

            <DeleteAllSubcontractsModal
                show={showDeleteAllModal}
                onClose={() => setShowDeleteAllModal(false)}
                onConfirm={handleDeleteAll}
                processing={processing}
                count={selectedSubcontracts.length}
            />

            <ImportSubcontractsModal
                show={showImportModal}
                onClose={() => setShowImportModal(false)}
                onSubmit={handleImport}
                processing={processing}
            />
        </AuthenticatedLayout>
    );
}

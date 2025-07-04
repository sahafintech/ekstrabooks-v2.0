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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import { Input } from "@/Components/ui/input";
import { MoreVertical, FileUp, FileDown, Plus, Eye, Trash2, Edit, RefreshCcw, CalendarIcon, ChevronUp, ChevronDown, ShoppingCart, DollarSign, FileText, CreditCard } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { Label } from "@/Components/ui/label";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { Calendar } from "@/Components/ui/calendar";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/Components/ui/popover";
import { cn, formatCurrency } from "@/lib/utils";
import InputError from "@/Components/InputError";
import { format } from "date-fns";
import DateTimePicker from "@/Components/DateTimePicker";

const DeletePurchaseReturnModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete this purchase return?
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
                    Delete Purchase Return
                </Button>
            </div>
        </form>
    </Modal>
);

const RefundPurchaseReturnModal = ({ show, onClose, onConfirm, processing, accounts, refundDate, setRefundDate, refundAmount, setRefundAmount, paymentAccount, setPaymentAccount, errors }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                <div>
                    <div className="grid grid-cols-12 mt-2">
                        <Label htmlFor="refund_date" className="md:col-span-3 col-span-12">
                            Refund Date *
                        </Label>
                        <div className="md:col-span-9 col-span-12 md:mt-0 mt-2">
                            <Popover>
                                <PopoverTrigger asChild>
                                    <Button
                                        variant={"outline"}
                                        className={cn(
                                            "w-full justify-start text-left font-normal",
                                            !refundDate && "text-muted-foreground"
                                        )}
                                    >
                                        <CalendarIcon className="mr-2 h-4 w-4" />
                                        {refundDate ? (
                                            format(new Date(refundDate), "PPP")
                                        ) : (
                                            <span>Pick a date</span>
                                        )}
                                    </Button>
                                </PopoverTrigger>
                                <PopoverContent className="w-auto p-0" align="start">
                                    <Calendar
                                        mode="single"
                                        selected={refundDate ? new Date(refundDate) : undefined}
                                        onSelect={(date) =>
                                            setRefundDate(date ? format(date, "yyyy-MM-dd") : "")
                                        }
                                        initialFocus
                                    />
                                </PopoverContent>
                            </Popover>
                            <InputError message={errors.refund_date} className="text-sm" />
                        </div>
                    </div>

                    <div className="grid grid-cols-12 mt-2">
                        <Label htmlFor="amount" className="md:col-span-3 col-span-12">
                            Amount *
                        </Label>
                        <div className="md:col-span-9 col-span-12 md:mt-0 mt-2">
                            <Input
                                id="amount"
                                type="text"
                                value={refundAmount}
                                onChange={(e) => setRefundAmount(e.target.value)}
                                required
                            />
                            <InputError message={errors.refund_amount} className="text-sm" />
                        </div>
                    </div>

                    <div className="grid grid-cols-12 mt-2">
                        <Label htmlFor="account_id" className="md:col-span-3 col-span-12">
                            Payment Account *
                        </Label>
                        <div className="md:col-span-9 col-span-12 md:mt-0 mt-2">
                            <SearchableCombobox
                                options={accounts.map(account => ({
                                    id: account.id,
                                    name: account.account_name
                                }))}
                                value={paymentAccount}
                                onChange={setPaymentAccount}
                                placeholder="Select payment account"
                                required
                            />
                            <InputError message={errors.account_id} className="text-sm" />
                        </div>
                    </div>
                </div>
            </div>
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
                    Refund Purchase Return
                </Button>
            </div>
        </form>
    </Modal>
);

const ImportPurchaseReturnModal = ({ show, onClose, onSubmit, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onSubmit} className="p-6">
            <div className="ti-modal-header">
                <h3 className="text-lg font-bold">Import Purchase Returns</h3>
            </div>
            <div className="ti-modal-body grid grid-cols-12">
                <div className="col-span-12">
                    <div className="flex items-center justify-between">
                        <label className="block font-medium text-sm text-gray-700">
                            Purchase Returns File
                        </label>
                        <Link href="/uploads/media/default/sample_purchase_returns.xlsx">
                            <Button variant="secondary" size="sm">
                                Use This Sample File
                            </Button>
                        </Link>
                    </div>
                    <input type="file" className="w-full dropify" name="purchase_return_file" required />
                </div>
                <div className="col-span-12 mt-4">
                    <ul className="space-y-3 text-sm">
                        <li className="flex space-x-3">
                            <span className="text-primary bg-primary/20 rounded-full px-1">✓</span>
                            <span className="text-gray-800 dark:text-white/70">
                                Maximum File Size: 1 MB
                            </span>
                        </li>
                        <li className="flex space-x-3">
                            <span className="text-primary bg-primary/20 rounded-full px-1">✓</span>
                            <span className="text-gray-800 dark:text-white/70">
                                File format Supported: CSV, TSV, XLS
                            </span>
                        </li>
                        <li className="flex space-x-3">
                            <span className="text-primary bg-primary/20 rounded-full px-1">✓</span>
                            <span className="text-gray-800 dark:text-white/70">
                                Make sure the format of the import file matches our sample file by comparing them.
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
            <div className="mt-6 flex justify-end">
                <Button
                    type="button"
                    variant="secondary"
                    onClick={onClose}
                    className="mr-3"
                >
                    Close
                </Button>
                <Button
                    type="submit"
                    disabled={processing}
                >
                    Import Purchase Returns
                </Button>
            </div>
        </form>
    </Modal>
);

const DeleteAllPurchaseReturnModal = ({ show, onClose, onConfirm, processing, count }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete {count} selected purchase return{count !== 1 ? 's' : ''}?
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

const PurchaseReturnStatusBadge = ({ status }) => {
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

const SummaryCards = ({ summary = {} }) => {
    const cards = [
        {
            title: "Total Returns",
            value: summary.total_returns || 0,
            description: "Total purchase returns",
            icon: ShoppingCart,
            iconColor: "text-blue-500"
        },
        {
            title: "Grand Total",
            value: formatCurrency({ amount: summary.grand_total || 0, currency: 'USD' }),
            description: "Total amount of all returns",
            icon: DollarSign,
            iconColor: "text-green-500"
        },
        {
            title: "Total Refunded",
            value: summary.total_refunded || 0,
            description: "Returns that have been refunded",
            icon: FileText,
            iconColor: "text-purple-500"
        },
        {
            title: "Total Due",
            value: formatCurrency({ amount: summary.total_due || 0, currency: 'USD' }),
            description: "Total amount due for returns",
            icon: CreditCard,
            iconColor: "text-orange-500"
        }
    ];

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {cards.map((card, index) => (
                <div key={index} className="bg-gray-100 rounded-lg shadow-sm p-4">
                    <div className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <h3 className="text-lg font-medium">
                            {card.title}
                        </h3>
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

export default function List({ returns = [], meta = {}, filters = {}, accounts = [], errors = {}, vendors = [], summary = {} }) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [selectedPurchaseReturns, setSelectedPurchaseReturns] = useState([]);
    const [isAllSelected, setIsAllSelected] = useState(false);
    const [search, setSearch] = useState(filters.search || "");
    const [perPage, setPerPage] = useState(meta.per_page || 50);
    const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
    const [bulkAction, setBulkAction] = useState("");
    const [sorting, setSorting] = useState(filters.sorting || { column: "id", direction: "desc" });

    // Delete confirmation modal states
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showImportModal, setShowImportModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [purchaseReturnToDelete, setPurchaseReturnToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);

    // Refund confirmation modal states
    const [showRefundModal, setShowRefundModal] = useState(false);
    const [purchaseReturnToRefund, setPurchaseReturnToRefund] = useState(null);

    const [refundDate, setRefundDate] = useState(format(new Date(), "yyyy-MM-dd"));
    const [refundAmount, setRefundAmount] = useState("");
    const [paymentAccount, setPaymentAccount] = useState("");

    const [selectedVendor, setSelectedVendor] = useState(filters.vendor_id || "");
    const [dateRange, setDateRange] = useState(filters.date_range || null);
    const [selectedStatus, setSelectedStatus] = useState(filters.status || "");

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
            setSelectedPurchaseReturns([]);
        } else {
            setSelectedPurchaseReturns(purchase_returns.map((purchase_return) => purchase_return.id));
        }
        setIsAllSelected(!isAllSelected);
    };

    const toggleSelectPurchaseReturns = (id) => {
        if (selectedPurchaseReturns.includes(id)) {
            setSelectedPurchaseReturns(selectedPurchaseReturns.filter((purchase_returnId) => purchase_returnId !== id));
            setIsAllSelected(false);
        } else {
            setSelectedPurchaseReturns([...selectedPurchaseReturns, id]);
            if (selectedPurchaseReturns.length + 1 === returns.length) {
                setIsAllSelected(true);
            }
        }
    };

    const handleSearch = (e) => {
        e.preventDefault();
        const value = e.target.value;
        setSearch(value);

        router.get(
            route("purchase_returns.index"),
            { search: value, page: 1, per_page: perPage },
            { preserveState: true }
        );
    };

    const handlePerPageChange = (value) => {
        setPerPage(value);
        router.get(
            route("purchase_returns.index"),
            { search, page: 1, per_page: value },
            { preserveState: true }
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("purchase_returns.index"),
            { 
                search,
                page,
                per_page: perPage,
                vendor_id: selectedVendor,
                date_range: dateRange,
                status: selectedStatus,
                sorting: sorting
            },
            { preserveState: true }
        );
    };

    const handleBulkAction = () => {
        if (bulkAction === "") return;

        if (selectedPurchaseReturns.length === 0) {
            toast({
                variant: "destructive",
                title: "Error",
                description: "Please select at least one purchase return",
            });
            return;
        }

        if (bulkAction === "delete") {
            setShowDeleteAllModal(true);
        }
    };

    const handleDeleteConfirm = (id) => {
        setPurchaseReturnToDelete(id);
        setShowDeleteModal(true);
    };

    const handleRefundConfirm = (id) => {
        setPurchaseReturnToRefund(id);
        setShowRefundModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.delete(route('purchase_returns.destroy', purchaseReturnToDelete), {
            onSuccess: () => {
                setShowDeleteModal(false);
                setPurchaseReturnToDelete(null);
                setProcessing(false);
            },
            onError: () => {
                setProcessing(false);
            }
        });
    };

    const handleRefund = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(route('purchase_returns.refund.store', purchaseReturnToRefund), {
            refund_date: refundDate,
            amount: refundAmount,
            account_id: paymentAccount,
            onSuccess: () => {
                setShowRefundModal(false);
                setPurchaseReturnToRefund(null);
                setProcessing(false);
                toast.success('Refund created successfully');
                setRefundDate('');
                setRefundAmount('');
                setPaymentAccount('');
            },
            onError: () => {
                setProcessing(false);
            }
        });
    };

    const handleDeleteAll = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(route('purchase_returns.bulk_destroy'),
            {
                ids: selectedPurchaseReturns
            },
            {
                onSuccess: () => {
                    setShowDeleteAllModal(false);
                    setSelectedPurchaseReturns([]);
                    setIsAllSelected(false);
                    setProcessing(false);
                },
                onError: () => {
                    setProcessing(false);
                }
            }
        );
    };

    const handleImport = (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        setProcessing(true);

        router.post(route('purchase_returns.import'), formData, {
            onSuccess: () => {
                setShowImportModal(false);
                setProcessing(false);
            },
            onError: () => {
                setProcessing(false);
            }
        });
    };

    const handleSort = (column) => {
        let direction = "asc";
        if (sorting.column === column && sorting.direction === "asc") {
            direction = "desc";
        }
        setSorting({ column, direction });
        router.get(
            route("purchase_returns.index"),
            { ...filters, sorting: { column, direction } },
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

    const exportPurchaseReturns = () => {
        router.get(route("purchase_returns.export"));
    };

    const handleVendorChange = (value) => {
        setSelectedVendor(value);
        router.get(
            route("purchase_returns.index"),
            {
                search,
                page: 1,
                per_page: perPage,
                vendor_id: value,
                date_range: dateRange,
                status: selectedStatus,
            },
            { preserveState: true }
        );
    };

    const handleDateRangeChange = (dates) => {
        setDateRange(dates);
        router.get(
            route("purchase_returns.index"),
            {
                search,
                page: 1,
                per_page: perPage,
                vendor_id: selectedVendor,
                date_range: dates,
                status: selectedStatus,
            },
            { preserveState: true }
        );
    };

    const handleStatusChange = (value) => {
        setSelectedStatus(value);
        router.get(
            route("purchase_returns.index"),
            {
                search,
                page: 1,
                per_page: perPage,
                vendor_id: selectedVendor,
                date_range: dateRange,
                status: value,
            },
            { preserveState: true }
        );
    };

    return (
        <AuthenticatedLayout>
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Purchase Returns"
                        subpage="List"
                        url="purchase_returns.index"
                    />
                    <div className="p-4">
                        <SummaryCards summary={summary} />
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div className="flex flex-col md:flex-row gap-2">
                                <Link href={route("purchase_returns.create")}>
                                    <Button>
                                        <Plus className="w-4 h-4 mr-2" />
                                        Add Purchase Return
                                    </Button>
                                </Link>
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="secondary">
                                            <MoreVertical className="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem onClick={() => setShowImportModal(true)}>
                                            <FileUp className="mr-2 h-4 w-4" /> Import
                                        </DropdownMenuItem>
                                        <DropdownMenuItem onClick={exportPurchaseReturns}>
                                            <FileDown className="mr-2 h-4 w-4" /> Export
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                            <div className="flex flex-col md:flex-row gap-4 md:items-center">
                                <Input
                                    placeholder="Search purchase returns..."
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
                                        <SelectItem value="delete">Delete Selected</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button onClick={handleBulkAction} variant="outline">
                                    Apply
                                </Button>
                                <SearchableCombobox
                                    options={vendors.map(vendor => ({
                                        id: vendor.id,
                                        name: vendor.name
                                    }))}
                                    value={selectedVendor}
                                    onChange={handleVendorChange}
                                    placeholder="Select vendor"
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
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("vendor.name")}>
                                            Vendor {renderSortIcon("vendor.name")}
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
                                        returns.map((purchase_return) => (
                                            <TableRow key={purchase_return.id}>
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selectedPurchaseReturns.includes(purchase_return.id)}
                                                        onCheckedChange={() => toggleSelectPurchaseReturns(purchase_return.id)}
                                                    />
                                                </TableCell>
                                                <TableCell>{purchase_return.return_number}</TableCell>
                                                <TableCell>{purchase_return.vendor ? purchase_return.vendor.name : "-"}</TableCell>
                                                <TableCell>{purchase_return.return_date}</TableCell>
                                                <TableCell className="text-right">{formatCurrency(purchase_return.grand_total)}</TableCell>
                                                <TableCell className="text-right">{formatCurrency(purchase_return.paid)}</TableCell>
                                                <TableCell className="text-right">{formatCurrency(purchase_return.grand_total - purchase_return.paid)}</TableCell>
                                                <TableCell>
                                                    <PurchaseReturnStatusBadge status={purchase_return.status} />
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <TableActions
                                                        actions={[
                                                            {
                                                                label: "View",
                                                                icon: <Eye className="h-4 w-4" />,
                                                                href: route("purchase_returns.show", purchase_return.id),
                                                            },
                                                            {
                                                                label: "Edit",
                                                                icon: <Edit className="h-4 w-4" />,
                                                                href: route("purchase_returns.edit", purchase_return.id),
                                                            },
                                                            {
                                                                label: "Refund",
                                                                icon: <RefreshCcw className="h-4 w-4" />,
                                                                onClick: () => handleRefundConfirm(purchase_return.id),
                                                            },
                                                            {
                                                                label: "Delete",
                                                                icon: <Trash2 className="h-4 w-4" />,
                                                                onClick: () => handleDeleteConfirm(purchase_return.id),
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
                                                No purchase returns found.
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

            <DeletePurchaseReturnModal
                show={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                onConfirm={handleDelete}
                processing={processing}
            />

            <RefundPurchaseReturnModal
                show={showRefundModal}
                onClose={() => setShowRefundModal(false)}
                onConfirm={handleRefund}
                processing={processing}
                accounts={accounts}
                refundDate={refundDate}
                setRefundDate={setRefundDate}
                refundAmount={refundAmount}
                setRefundAmount={setRefundAmount}
                paymentAccount={paymentAccount}
                setPaymentAccount={setPaymentAccount}
                errors={errors}
            />

            <DeleteAllPurchaseReturnModal
                show={showDeleteAllModal}
                onClose={() => setShowDeleteAllModal(false)}
                onConfirm={handleDeleteAll}
                processing={processing}
                count={selectedPurchaseReturns.length}
            />

            <ImportPurchaseReturnModal
                show={showImportModal}
                onClose={() => setShowImportModal(false)}
                onSubmit={handleImport}
                processing={processing}
            />
        </AuthenticatedLayout>
    );
}

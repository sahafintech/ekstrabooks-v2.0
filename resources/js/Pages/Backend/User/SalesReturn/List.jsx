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
import { MoreVertical, FileUp, FileDown, Plus, Eye, Trash2, Edit, RefreshCcw, CalendarIcon, ChevronUp, ChevronDown } from "lucide-react";
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

const RefundSalesReturnModal = ({ show, onClose, onConfirm, processing, accounts, refundDate, setRefundDate, refundAmount, setRefundAmount, paymentAccount, setPaymentAccount, errors }) => (
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
                    Refund Sales Return
                </Button>
            </div>
        </form>
    </Modal>
);

const ImportSalesReturnModal = ({ show, onClose, onSubmit, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onSubmit} className="p-6">
            <div className="ti-modal-header">
                <h3 className="text-lg font-bold">Import Sales Returns</h3>
            </div>
            <div className="ti-modal-body grid grid-cols-12">
                <div className="col-span-12">
                    <div className="flex items-center justify-between">
                        <label className="block font-medium text-sm text-gray-700">
                            Sales Returns File
                        </label>
                        <Link href="/uploads/media/default/sample_sales_returns.xlsx">
                            <Button variant="secondary" size="sm">
                                Use This Sample File
                            </Button>
                        </Link>
                    </div>
                    <input type="file" className="w-full dropify" name="sales_return_file" required />
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
                    Import Sales Returns
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

export default function List({ returns = [], meta = {}, filters = {}, accounts = [], errors = {} }) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [selectedSalesReturns, setSelectedSalesReturns] = useState([]);
    const [isAllSelected, setIsAllSelected] = useState(false);
    const [search, setSearch] = useState(filters.search || "");
    const [perPage, setPerPage] = useState(meta.per_page || 10);
    const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
    const [bulkAction, setBulkAction] = useState("");

    // Delete confirmation modal states
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showImportModal, setShowImportModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [salesReturnToDelete, setSalesReturnToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);

    // Refund confirmation modal states
    const [showRefundModal, setShowRefundModal] = useState(false);
    const [salesReturnToRefund, setSalesReturnToRefund] = useState(null);

    const [refundDate, setRefundDate] = useState(format(new Date(), "yyyy-MM-dd"));
    const [refundAmount, setRefundAmount] = useState("");
    const [paymentAccount, setPaymentAccount] = useState("");

    const [sorting, setSorting] = useState(filters.sorting || { column: "id", direction: "desc" });

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
            { search, page, per_page: perPage },
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
        }
    };

    const handleDeleteConfirm = (id) => {
        setSalesReturnToDelete(id);
        setShowDeleteModal(true);
    };

    const handleRefundConfirm = (id) => {
        setSalesReturnToRefund(id);
        setShowRefundModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.delete(route('sales_returns.destroy', salesReturnToDelete), {
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

    const handleRefund = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(route('sales_returns.refund.store', salesReturnToRefund), {
            refund_date: refundDate,
            amount: refundAmount,
            account_id: paymentAccount,
            onSuccess: () => {
                setShowRefundModal(false);
                setSalesReturnToRefund(null);
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

        router.post(route('sales_returns.bulk_destroy'),
            {
                ids: selectedSalesReturns
            },
            {
                onSuccess: () => {
                    setShowDeleteAllModal(false);
                    setSelectedSalesReturns([]);
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

        router.post(route('sales_returns.import'), formData, {
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
            route("sales_returns.index"),
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

    const exportSalesReturns = () => {
        router.get(route("sales_returns.export"));
    };

    return (
        <AuthenticatedLayout>
            <Head title="Sales Returns" />
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
                            <div className="flex flex-col md:flex-row gap-4">
                                <Link href={route("sales_returns.create")}>
                                    <Button>
                                        <Plus className="w-4 h-4 mr-2" />
                                        Add Sales Return
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
                                        <DropdownMenuItem onClick={exportSalesReturns}>
                                            <FileDown className="mr-2 h-4 w-4" /> Export
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
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
                                        <SelectItem value="delete">Delete Selected</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button onClick={handleBulkAction} variant="outline">
                                    Apply
                                </Button>
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
                                                                label: "View",
                                                                icon: <Eye className="h-4 w-4" />,
                                                                href: route("sales_returns.show", sales_return.id),
                                                            },
                                                            {
                                                                label: "Edit",
                                                                icon: <Edit className="h-4 w-4" />,
                                                                href: route("sales_returns.edit", sales_return.id),
                                                            },
                                                            {
                                                                label: "Refund",
                                                                icon: <RefreshCcw className="h-4 w-4" />,
                                                                onClick: () => handleRefundConfirm(sales_return.id),
                                                            },
                                                            {
                                                                label: "Delete",
                                                                icon: <Trash2 className="h-4 w-4" />,
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

            <RefundSalesReturnModal
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

            <DeleteAllSalesReturnModal
                show={showDeleteAllModal}
                onClose={() => setShowDeleteAllModal(false)}
                onConfirm={handleDeleteAll}
                processing={processing}
                count={selectedSalesReturns.length}
            />

            <ImportSalesReturnModal
                show={showImportModal}
                onClose={() => setShowImportModal(false)}
                onSubmit={handleImport}
                processing={processing}
            />
        </AuthenticatedLayout>
    );
}

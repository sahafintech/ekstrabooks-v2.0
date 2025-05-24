import React, { useState, useEffect, useMemo } from "react";
import { Head, router, usePage } from "@inertiajs/react";
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
import { Label } from "@/Components/ui/label";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
} from "@/Components/ui/dialog";
import { Edit, Plus, Trash, ChevronUp, ChevronDown, Eye } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import Modal from "@/Components/Modal";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import DateTimePicker from "@/Components/DateTimePicker";
import { formatCurrency } from "@/lib/utils";

// Delete Confirmation Modal Component
const DeletePaymentModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete this payment?
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
                    Delete
                </Button>
            </div>
        </form>
    </Modal>
);

// Bulk Delete Confirmation Modal Component
const DeleteAllPaymentsModal = ({
    show,
    onClose,
    onConfirm,
    processing,
    count,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete {count} selected payment
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

// Create Payment Dialog Component
const CreatePaymentDialog = ({
    show,
    onClose,
    onSubmit,
    processing,
    form,
    setForm,
    errors,
    methods,
    accounts,
    projectSubcontract,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onSubmit}>
            <div className="flex items-center justify-between">
                <h3 className="text-lg font-bold">Add Payment</h3>
                <h3 className="text-lg font-bold">
                    Balance:{" "}
                    {formatCurrency({
                        amount:
                            projectSubcontract.grand_total -
                            projectSubcontract.paid,
                        currency: projectSubcontract.currency,
                    })}
                </h3>
            </div>
            {errors.payment_amount && (
                <p className="text-sm text-red-500">{errors.payment_amount}</p>
            )}
            <div className="grid grid-cols-12">
                <div className="col-span-12">
                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="date">Date *</Label>
                            <DateTimePicker
                                value={form.date}
                                onChange={(date) => setForm("date", date)}
                            />
                            {errors.date && (
                                <p className="text-sm text-red-500">
                                    {errors.date}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="account_id">
                                Payment Account *
                            </Label>
                            <SearchableCombobox
                                id="account_id"
                                options={accounts.map((account) => ({
                                    id: account.id,
                                    name: account.account_name,
                                }))}
                                name="account_id"
                                value={form.account_id}
                                placeholder="Select account"
                                onChange={(value) =>
                                    setForm({ ...form, account_id: value })
                                }
                            />
                            {errors.account_id && (
                                <p className="text-sm text-red-500">
                                    {errors.account_id}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="method">Payment Method</Label>
                            <SearchableCombobox
                                id="method"
                                options={methods.map((method) => ({
                                    id: method.name,
                                    name: method.name,
                                }))}
                                name="method"
                                value={form.method}
                                placeholder="Select payment method"
                                onChange={(value) =>
                                    setForm({ ...form, method: value })
                                }
                            />
                            {errors.method && (
                                <p className="text-sm text-red-500">
                                    {errors.method}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="amount">Amount *</Label>
                            <Input
                                id="amount"
                                type="number"
                                name="amount"
                                value={form.amount}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        amount: e.target.value,
                                    })
                                }
                                placeholder="Enter amount"
                            />
                            {errors.amount && (
                                <p className="text-sm text-red-500">
                                    {errors.amount}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="reference">Reference</Label>
                            <Input
                                id="reference"
                                type="text"
                                name="reference"
                                value={form.reference}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        reference: e.target.value,
                                    })
                                }
                                placeholder="Enter reference"
                            />
                            {errors.reference && (
                                <p className="text-sm text-red-500">
                                    {errors.reference}
                                </p>
                            )}
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
                        <Button type="submit" disabled={processing}>
                            Save
                        </Button>
                    </div>
                </div>
            </div>
        </form>
    </Modal>
);

// Edit Payment Dialog Component
const EditPaymentDialog = ({
    show,
    onClose,
    onSubmit,
    processing,
    form,
    setForm,
    errors,
    methods,
    accounts,
    projectSubcontract,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onSubmit}>
            <div className="flex items-center justify-between">
                <h3 className="text-lg font-bold">Edit Payment</h3>
                <h3 className="text-lg font-bold">
                    Balance:{" "}
                    {formatCurrency({
                        amount:
                            projectSubcontract.grand_total -
                            projectSubcontract.paid,
                        currency: projectSubcontract.currency,
                    })}
                </h3>
            </div>
            {errors.payment_amount && (
                <p className="text-sm text-red-500">{errors.payment_amount}</p>
            )}
            <div className="col-span-12">
                <div className="grid gap-4 py-4">
                    <div className="grid gap-2">
                        <Label htmlFor="date">Date *</Label>
                        <DateTimePicker
                            value={form.date}
                            onChange={(date) => setForm("date", date)}
                        />
                        {errors.date && (
                            <p className="text-sm text-red-500">
                                {errors.date}
                            </p>
                        )}
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="method">Payment Method</Label>
                        <SearchableCombobox
                            id="method"
                            options={methods.map((method) => ({
                                id: method.name,
                                name: method.name,
                            }))}
                            name="method"
                            value={form.method}
                            placeholder="Select payment method"
                            onChange={(value) =>
                                setForm({ ...form, method: value })
                            }
                        />
                        {errors.method && (
                            <p className="text-sm text-red-500">
                                {errors.method}
                            </p>
                        )}
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="account_id">Payment Account *</Label>
                        <SearchableCombobox
                            id="account_id"
                            options={accounts.map((account) => ({
                                id: account.id,
                                name: account.account_name,
                            }))}
                            name="account_id"
                            value={form.account_id}
                            onChange={(value) =>
                                setForm({ ...form, account_id: value })
                            }
                        />
                        {errors.account_id && (
                            <p className="text-sm text-red-500">
                                {errors.account_id}
                            </p>
                        )}
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="amount">Amount *</Label>
                        <Input
                            id="amount"
                            type="number"
                            name="amount"
                            value={form.amount}
                            onChange={(e) =>
                                setForm({
                                    ...form,
                                    amount: e.target.value,
                                })
                            }
                            placeholder="Enter amount"
                        />
                        {errors.amount && (
                            <p className="text-sm text-red-500">
                                {errors.amount}
                            </p>
                        )}
                    </div>
                    <div className="grid gap-2">
                        <Label htmlFor="reference">Reference</Label>
                        <Input
                            id="reference"
                            type="text"
                            name="reference"
                            value={form.reference}
                            onChange={(e) =>
                                setForm({
                                    ...form,
                                    reference: e.target.value,
                                })
                            }
                            placeholder="Enter reference"
                        />
                        {errors.reference && (
                            <p className="text-sm text-red-500">
                                {errors.reference}
                            </p>
                        )}
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
                    <Button type="submit" disabled={processing}>
                        Save
                    </Button>
                </div>
            </div>
        </form>
    </Modal>
);

export default function PaymentList({ projectSubcontract, methods, accounts }) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [selectedPayments, setSelectedPayments] = useState([]);
    const [isAllSelected, setIsAllSelected] = useState(false);
    const [search, setSearch] = useState("");
    const [perPage, setPerPage] = useState(50);
    const [currentPage, setCurrentPage] = useState(1);
    const [bulkAction, setBulkAction] = useState("");
    const [sorting, setSorting] = useState({ column: "id", direction: "desc" });

    // Form state for Create/Edit dialogs
    const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
    const [isEditDialogOpen, setIsEditDialogOpen] = useState(false);
    const [editingPayment, setEditingPayment] = useState(null);
    const [form, setForm] = useState({
        date: new Date(),
        vendor_id: projectSubcontract.vendor_id,
        method: "",
        amount: "",
        account_id: "",
        reference: "",
        project_subcontract_id: projectSubcontract.id,
    });

    // Form errors
    const [errors, setErrors] = useState({});

    // Delete confirmation modal states
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [paymentToDelete, setPaymentToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);

    // Client-side filtering, sorting and pagination
    const filteredAndSortedPayments = useMemo(() => {
        let result = [...projectSubcontract.payments];

        // Apply search filter
        if (search) {
            const searchLower = search.toLowerCase();
            result = result.filter(
                (payment) =>
                    payment.id.toString().includes(searchLower) ||
                    payment.vendor.name.toLowerCase().includes(searchLower) ||
                    payment.method.toLowerCase().includes(searchLower) ||
                    payment.amount.toString().includes(searchLower) ||
                    payment.date.includes(searchLower)
            );
        }

        // Apply sorting
        result.sort((a, b) => {
            const { column, direction } = sorting;
            const aValue = a[column];
            const bValue = b[column];

            if (direction === "asc") {
                return aValue > bValue ? 1 : -1;
            } else {
                return aValue < bValue ? 1 : -1;
            }
        });

        return result;
    }, [projectSubcontract.payments, search, sorting]);

    // Calculate pagination
    const paginatedPayments = useMemo(() => {
        const startIndex = (currentPage - 1) * perPage;
        return filteredAndSortedPayments.slice(
            startIndex,
            startIndex + perPage
        );
    }, [filteredAndSortedPayments, currentPage, perPage]);

    // Calculate pagination metadata
    const paginationMeta = useMemo(
        () => ({
            total: filteredAndSortedPayments.length,
            per_page: perPage,
            current_page: currentPage,
            last_page: Math.ceil(filteredAndSortedPayments.length / perPage),
        }),
        [filteredAndSortedPayments.length, perPage, currentPage]
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

    const toggleSelectAll = () => {
        if (isAllSelected) {
            setSelectedPayments([]);
        } else {
            setSelectedPayments(paginatedPayments.map((payment) => payment.id));
        }
        setIsAllSelected(!isAllSelected);
    };

    const toggleSelectPayment = (id) => {
        if (selectedPayments.includes(id)) {
            setSelectedPayments(
                selectedPayments.filter((paymentId) => paymentId !== id)
            );
            setIsAllSelected(false);
        } else {
            setSelectedPayments([...selectedPayments, id]);
            if (selectedPayments.length + 1 === paginatedPayments.length) {
                setIsAllSelected(true);
            }
        }
    };

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

    const handleBulkAction = () => {
        if (bulkAction === "") return;

        if (selectedPayments.length === 0) {
            toast({
                variant: "destructive",
                title: "Error",
                description: "Please select at least one payment",
            });
            return;
        }

        if (bulkAction === "delete") {
            setShowDeleteAllModal(true);
        }
    };

    const handleDeleteConfirm = (id) => {
        setPaymentToDelete(id);
        setShowDeleteModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.delete(route("project_subcontract_payments.destroy", paymentToDelete), {
            onSuccess: () => {
                setShowDeleteModal(false);
                setPaymentToDelete(null);
                setProcessing(false);
            },
            onError: () => {
                setProcessing(false);
            },
        });
    };

    const handleDeleteAll = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(
            route("project_subcontract_payments.bulk_destroy"),
            {
                ids: selectedPayments,
            },
            {
                onSuccess: () => {
                    setShowDeleteAllModal(false);
                    setSelectedPayments([]);
                    setIsAllSelected(false);
                    setProcessing(false);
                },
                onError: () => {
                    setProcessing(false);
                },
            }
        );
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

    // Create form handlers
    const openCreateDialog = () => {
        setForm({
            date: new Date(),
            vendor_id: projectSubcontract.vendor_id,
            method: "",
            amount: "",
            account_id: "",
            reference: "",
            project_subcontract_id: projectSubcontract.id,
        });
        setErrors({});
        setIsCreateDialogOpen(true);
    };

    const handleCreateSubmit = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(route("project_subcontract_payments.store"), form, {
            preserveState: true,
            onSuccess: () => {
                setIsCreateDialogOpen(false);
                setForm({
                    date: new Date(),
                    vendor_id: projectSubcontract.vendor_id,
                    method: "",
                    amount: "",
                    account_id: "",
                    reference: "",
                    project_subcontract_id: projectSubcontract.id,
                });
                setProcessing(false);
            },
            onError: (errors) => {
                setErrors(errors);
                setProcessing(false);
            },
        });
    };

    // Edit form handlers
    const openEditDialog = (payment) => {
        setEditingPayment(payment);
        setForm({
            date: payment.date,
            vendor_id: payment.vendor_id,
            method: payment.method,
            amount: payment.amount,
            account_id: payment.account_id,
            project_subcontract_id: projectSubcontract.id,
        });
        setErrors({});
        setIsEditDialogOpen(true);
    };

    const handleEditSubmit = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.put(
            route("project_subcontract_payments.update", editingPayment.id),
            form,
            {
                preserveState: true,
                onSuccess: () => {
                    setIsEditDialogOpen(false);
                    setEditingPayment(null);
                    setProcessing(false);
                },
                onError: (errors) => {
                    setErrors(errors);
                    setProcessing(false);
                },
            }
        );
    };

    return (
        <div>
            <Head title="Payments" />
            <Toaster />
            <div className="main-content">
                <div className="p-4">
                    <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                        <div className="flex flex-col md:flex-row gap-2">
                            <Button onClick={openCreateDialog}>
                                <Plus className="w-4 h-4 mr-2" />
                                Add Payment
                            </Button>
                        </div>
                        <div className="flex flex-col md:flex-row gap-4 md:items-center">
                            <Input
                                placeholder="search payments..."
                                value={search}
                                onChange={(e) => handleSearch(e)}
                                className="w-full md:w-80"
                            />
                        </div>
                    </div>

                    <div className="mb-4 flex flex-col md:flex-row gap-4 justify-between">
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
                                            onCheckedChange={toggleSelectAll}
                                        />
                                    </TableHead>
                                    <TableHead
                                        className="cursor-pointer"
                                        onClick={() => handleSort("date")}
                                    >
                                        Date {renderSortIcon("date")}
                                    </TableHead>
                                    <TableHead
                                        className="cursor-pointer"
                                        onClick={() => handleSort("vendor_id")}
                                    >
                                        Vendor {renderSortIcon("vendor_id")}
                                    </TableHead>
                                    <TableHead
                                        className="cursor-pointer"
                                        onClick={() => handleSort("method")}
                                    >
                                        Method {renderSortIcon("method")}
                                    </TableHead>
                                    <TableHead
                                        className="cursor-pointer"
                                        onClick={() => handleSort("amount")}
                                    >
                                        Amount {renderSortIcon("amount")}
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {projectSubcontract.payments.length > 0 ? (
                                    projectSubcontract.payments.map(
                                        (payment) => (
                                            <TableRow key={payment.id}>
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selectedPayments.includes(
                                                            payment.id
                                                        )}
                                                        onCheckedChange={() =>
                                                            toggleSelectPayment(
                                                                payment.id
                                                            )
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    {payment.date}
                                                </TableCell>
                                                <TableCell>
                                                    {payment.vendor.name}
                                                </TableCell>
                                                <TableCell>
                                                    {payment.method}
                                                </TableCell>
                                                <TableCell>
                                                    {formatCurrency({
                                                        amount: payment.amount,
                                                        currency:
                                                            projectSubcontract.currency,
                                                    })}
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
                                                                    "project_subcontract_payments.show",
                                                                    payment.id
                                                                ),
                                                            },
                                                            {
                                                                label: "Edit",
                                                                icon: (
                                                                    <Edit className="h-4 w-4" />
                                                                ),
                                                                onClick: () =>
                                                                    openEditDialog(
                                                                        payment
                                                                    ),
                                                            },
                                                            {
                                                                label: "Delete",
                                                                icon: (
                                                                    <Trash className="h-4 w-4" />
                                                                ),
                                                                onClick: () =>
                                                                    handleDeleteConfirm(
                                                                        payment.id
                                                                    ),
                                                                destructive: true,
                                                            },
                                                        ]}
                                                    />
                                                </TableCell>
                                            </TableRow>
                                        )
                                    )
                                ) : (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="h-24 text-center"
                                        >
                                            No payments found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {paginatedPayments.length > 0 &&
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
                                            currentPage ===
                                            paginationMeta.last_page
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
                                            currentPage ===
                                            paginationMeta.last_page
                                        }
                                    >
                                        Last
                                    </Button>
                                </div>
                            </div>
                        )}
                </div>
            </div>

            <DeletePaymentModal
                show={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                onConfirm={handleDelete}
                processing={processing}
            />

            <DeleteAllPaymentsModal
                show={showDeleteAllModal}
                onClose={() => setShowDeleteAllModal(false)}
                onConfirm={handleDeleteAll}
                processing={processing}
                count={selectedPayments.length}
            />

            {/* Create Dialog */}
            <CreatePaymentDialog
                show={isCreateDialogOpen}
                onClose={() => setIsCreateDialogOpen(false)}
                onSubmit={handleCreateSubmit}
                processing={processing}
                form={form}
                setForm={setForm}
                errors={errors}
                methods={methods}
                accounts={accounts}
                projectSubcontract={projectSubcontract}
            />

            {/* Edit Dialog */}
            <EditPaymentDialog
                show={isEditDialogOpen}
                onClose={() => setIsEditDialogOpen(false)}
                onSubmit={handleEditSubmit}
                processing={processing}
                form={form}
                setForm={setForm}
                errors={errors}
                methods={methods}
                accounts={accounts}
                projectSubcontract={projectSubcontract}
            />
        </div>
    );
}

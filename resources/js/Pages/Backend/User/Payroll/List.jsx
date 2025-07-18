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
import {
    Edit,
    EyeIcon,
    FileDown,
    MoreVertical,
    Plus,
    Trash,
    ChevronUp,
    ChevronDown,
} from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { formatAmount, formatCurrency } from "@/lib/utils";
import { Label } from "@/Components/ui/label";
import DateTimePicker from "@/Components/DateTimePicker";

// Delete Confirmation Modal Component
const DeleteConfirmationModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete this payslip?
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
const BulkDeleteConfirmationModal = ({
    show,
    onClose,
    onConfirm,
    processing,
    count,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete {count} selected{" "}
                {count !== 1 ? "payslips" : "payslip"}?
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

const BulkApproveConfirmationModal = ({
    show,
    onClose,
    onConfirm,
    processing,
    count,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to approve {count} selected{" "}
                {count !== 1 ? "payslips" : "payslip"}?
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

const BulkRejectConfirmationModal = ({
    show,
    onClose,
    onConfirm,
    processing,
    count,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to reject {count} selected{" "}
                {count !== 1 ? "payslips" : "payslip"}?
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
                    Reject Selected
                </Button>
            </div>
        </form>
    </Modal>
);

const BulkAccrueConfirmationModal = ({
    show,
    onClose,
    onConfirm,
    processing,
    count,
    accounts,
    liabilityAccountId,
    expenseAccountId,
    setLiabilityAccountId,
    setExpenseAccountId,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to accrue {count} selected{" "}
                {count !== 1 ? "payslips" : "payslip"}?
            </h2>
            <div className="flex flex-1 flex-col gap-4 p-4 mt-4">
                <div className="grid grid-cols-12 mt-2">
                    <Label
                        htmlFor="liability_account_id"
                        className="md:col-span-3 col-span-12"
                    >
                        Liablity Account
                    </Label>
                    <div className="md:col-span-9 col-span-12 md:mt-0 mt-2">
                        <SearchableCombobox
                            options={accounts.map((account) => ({
                                id: account.id,
                                name: account.account_name,
                            }))}
                            value={liabilityAccountId}
                            onChange={(value) => setLiabilityAccountId(value)}
                            placeholder="Select account"
                        />
                    </div>
                </div>

                <div className="grid grid-cols-12 mt-2">
                    <Label
                        htmlFor="expense_account_id"
                        className="md:col-span-3 col-span-12"
                    >
                        Expense Account
                    </Label>
                    <div className="md:col-span-9 col-span-12 md:mt-0 mt-2">
                        <SearchableCombobox
                            options={accounts.map((account) => ({
                                id: account.id,
                                name: account.account_name,
                            }))}
                            value={expenseAccountId}
                            onChange={(value) => setExpenseAccountId(value)}
                            placeholder="Select account"
                        />
                    </div>
                </div>

                <div className="flex flex-row justify-end">
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
                        Accrue Selected
                    </Button>
                </div>
            </div>
        </form>
    </Modal>
);

const BulkPaymentConfirmationModal = ({
    show,
    onClose,
    onConfirm,
    processing,
    count,
    accounts,
    methods,
    debitAccountId,
    creditAccountId,
    setDebitAccountId,
    setCreditAccountId,
    advanceAccountId,
    setAdvanceAccountId,
    paymentMethod,
    setPaymentMethod,
    paymentDate,
    setPaymentDate,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to pay {count} selected{" "}
                {count !== 1 ? "payslips" : "payslip"}?
            </h2>
            <div className="flex flex-1 flex-col gap-4 p-4 mt-4">
                <div className="grid grid-cols-12 mt-2">
                    <Label
                        htmlFor="credit_account_id"
                        className="md:col-span-3 col-span-12"
                    >
                        Payment Date *
                    </Label>
                    <div className="md:col-span-9 col-span-12 md:mt-0 mt-2">
                        <DateTimePicker
                            value={paymentDate}
                            onChange={(value) => setPaymentDate(value)}
                            required
                        />
                    </div>
                </div>

                <div className="grid grid-cols-12 mt-2">
                    <Label
                        htmlFor="credit_account_id"
                        className="md:col-span-3 col-span-12"
                    >
                        Credit Account *
                    </Label>
                    <div className="md:col-span-9 col-span-12 md:mt-0 mt-2">
                        <SearchableCombobox
                            options={accounts.map((account) => ({
                                id: account.id,
                                name: account.account_name,
                            }))}
                            value={creditAccountId}
                            onChange={(value) => setCreditAccountId(value)}
                            placeholder="Select account"
                        />
                    </div>
                </div>

                <div className="grid grid-cols-12 mt-2">
                    <Label
                        htmlFor="debit_account_id"
                        className="md:col-span-3 col-span-12"
                    >
                        Debit Account *
                    </Label>
                    <div className="md:col-span-9 col-span-12 md:mt-0 mt-2">
                        <SearchableCombobox
                            options={accounts.map((account) => ({
                                id: account.id,
                                name: account.account_name,
                            }))}
                            value={debitAccountId}
                            onChange={(value) => setDebitAccountId(value)}
                            placeholder="Select account"
                        />
                    </div>
                </div>

                <div className="grid grid-cols-12 mt-2">
                    <Label
                        htmlFor="advance_account_id"
                        className="md:col-span-3 col-span-12"
                    >
                        Advance Account *
                    </Label>
                    <div className="md:col-span-9 col-span-12 md:mt-0 mt-2">
                        <SearchableCombobox
                            options={accounts.map((account) => ({
                                id: account.id,
                                name: account.account_name,
                            }))}
                            value={advanceAccountId}
                            onChange={(value) => setAdvanceAccountId(value)}
                            placeholder="Select account"
                        />
                    </div>
                </div>

                <div className="grid grid-cols-12 mt-2">
                    <Label
                        htmlFor="method"
                        className="md:col-span-3 col-span-12"
                    >
                        Payment Method
                    </Label>
                    <div className="md:col-span-9 col-span-12 md:mt-0 mt-2">
                        <SearchableCombobox
                            options={methods.map((method) => ({
                                id: method.name,
                                name: method.name,
                            }))}
                            value={paymentMethod}
                            onChange={(value) => setPaymentMethod(value)}
                            placeholder="Select payment method"
                        />
                    </div>
                </div>

                <div className="flex flex-row justify-end">
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
                        Pay Selected
                    </Button>
                </div>
            </div>
        </form>
    </Modal>
);

export default function List({
    payrolls = [],
    meta = {},
    filters = {},
    years,
    year,
    month,
    accounts,
    methods,
}) {
    const { flash = {}, errors = {}, userPackage } = usePage().props;
    const { toast } = useToast();
    const [selectedPaylips, setSelectedPayslips] = useState([]);
    const [isAllSelected, setIsAllSelected] = useState(false);
    const [search, setSearch] = useState(filters.search || "");
    const [perPage, setPerPage] = useState(meta.per_page || 50);
    const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
    const [bulkAction, setBulkAction] = useState("");
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showBulkDeleteModal, setShowBulkDeleteModal] = useState(false);
    const [showBulkApproveModal, setShowBulkApproveModal] = useState(false);
    const [showBulkRejectModal, setShowBulkRejectModal] = useState(false);
    const [showBulkAccrueModal, setShowBulkAccrueModal] = useState(false);
    const [payslipsToDelete, setPayslipsToDelete] = useState(null);
    const [isProcessing, setIsProcessing] = useState(false);
    const [selectedMonth, setSelectedMonth] = useState(month);
    const [selectedYear, setSelectedYear] = useState(year);
    const [liabilityAccountId, setLiabilityAccountId] = useState(null);
    const [expenseAccountId, setExpenseAccountId] = useState(null);
    const [showBulkPaymentModal, setShowBulkPaymentModal] = useState(false);
    const [debitAccountId, setDebitAccountId] = useState(null);
    const [creditAccountId, setCreditAccountId] = useState(null);
    const [advanceAccountId, setAdvanceAccountId] = useState(null);
    const [paymentMethod, setPaymentMethod] = useState(null);
    const [paymentDate, setPaymentDate] = useState(null);
    const [sorting, setSorting] = useState(
        filters.sorting || { column: "id", direction: "desc" }
    );
    const [paymentAmounts, setPaymentAmounts] = useState({});

    useEffect(() => {
        // Initialize paymentAmounts with the correct due amounts
        const initialPaymentAmounts = {};
        payrolls.forEach((payroll) => {
            initialPaymentAmounts[payroll.id] =
                payroll.net_salary - payroll.paid;
        });
        setPaymentAmounts(initialPaymentAmounts);
    }, [payrolls]);

    useEffect(() => {
        if (flash && flash.success) {
            toast({
                title: "Success",
                description: flash.success,
            });
        }

        if (errors && errors.error) {
            toast({
                variant: "destructive",
                title: "Error",
                description: errors.error,
            });
        }
    }, [flash, toast, errors]);

    const toggleSelectAll = () => {
        if (isAllSelected) {
            setSelectedPayslips([]);
        } else {
            setSelectedPayslips(payrolls.map((payroll) => payroll.id));
        }
        setIsAllSelected(!isAllSelected);
    };

    const toggleSelectPayroll = (id) => {
        if (selectedPaylips.includes(id)) {
            setSelectedPayslips(
                selectedPaylips.filter((payrollId) => payrollId !== id)
            );
            setIsAllSelected(false);
        } else {
            setSelectedPayslips([...selectedPaylips, id]);
            if (selectedPaylips.length + 1 === payrolls.length) {
                setIsAllSelected(true);
            }
        }
    };

    useEffect(() => {
        if (
            selectedMonth &&
            selectedYear &&
            (selectedMonth !== month || selectedYear !== year)
        ) {
            router.get(
                route("payslips.index"),
                {
                    search,
                    page: 1,
                    per_page: perPage,
                    month: selectedMonth,
                    year: selectedYear,
                },
                { preserveState: true }
            );
        }
    }, [selectedMonth, selectedYear, search, perPage]);

    const handleSearch = (e) => {
        setSearch(e.target.value);
        router.get(
            route("payslips.index"),
            {
                search: e.target.value,
                page: 1,
                per_page: perPage,
                month: selectedMonth,
                year: selectedYear,
            },
            { preserveState: true }
        );
    };

    const handlePerPageChange = (value) => {
        setPerPage(value);
        router.get(
            route("payslips.index"),
            {
                search,
                page: 1,
                per_page: value,
                month: selectedMonth,
                year: selectedYear,
            },
            { preserveState: true }
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("payslips.index"),
            {
                search,
                page,
                per_page: perPage,
                month: selectedMonth,
                year: selectedYear,
            },
            { preserveState: true }
        );
    };

    const handleBulkAction = () => {
        if (bulkAction === "") return;

        if (selectedPaylips.length === 0) {
            toast({
                variant: "destructive",
                title: "Error",
                description: "Please select at least one payroll",
            });
            return;
        }

        if (bulkAction === "delete") {
            setShowBulkDeleteModal(true);
        }

        if (bulkAction === "approve") {
            setShowBulkApproveModal(true);
        }

        if (bulkAction === "reject") {
            setShowBulkRejectModal(true);
        }

        if (bulkAction === "accrue") {
            setShowBulkAccrueModal(true);
        }

        if (bulkAction === "pay") {
            setShowBulkPaymentModal(true);
        }
    };

    const handleDeleteConfirm = (id) => {
        setPayslipsToDelete(id);
        setShowDeleteModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setIsProcessing(true);

        router.delete(route("payslips.destroy", payslipsToDelete), {
            preserveState: true,
            onSuccess: () => {
                setShowDeleteModal(false);
                setPayslipsToDelete(null);
                setIsProcessing(false);
            },
            onError: () => {
                setIsProcessing(false);
            },
        });
    };

    const handleBulkDeleteConfirm = (e) => {
        e.preventDefault();
        setIsProcessing(true);

        router.post(
            route("payslips.bulk_delete"),
            {
                ids: selectedPaylips,
            },
            {
                preserveState: true,
                onSuccess: () => {
                    setSelectedPayslips([]);
                    setIsAllSelected(false);
                    setBulkAction("");
                    setShowBulkDeleteModal(false);
                    setIsProcessing(false);
                },
                onError: () => {
                    setIsProcessing(false);
                },
            }
        );
    };

    const handleBulkApproveConfirm = (e) => {
        e.preventDefault();
        setIsProcessing(true);

        router.post(
            route("payslips.bulk_approve"),
            {
                ids: selectedPaylips,
            },
            {
                preserveState: true,
                onSuccess: () => {
                    setSelectedPayslips([]);
                    setIsAllSelected(false);
                    setBulkAction("");
                    setShowBulkApproveModal(false);
                    setIsProcessing(false);
                },
                onError: () => {
                    setIsProcessing(false);
                },
            }
        );
    };

    const handleBulkRejectConfirm = (e) => {
        e.preventDefault();
        setIsProcessing(true);

        router.post(
            route("payslips.bulk_reject"),
            {
                ids: selectedPaylips,
            },
            {
                preserveState: true,
                onSuccess: () => {
                    setSelectedPayslips([]);
                    setIsAllSelected(false);
                    setBulkAction("");
                    setShowBulkRejectModal(false);
                    setIsProcessing(false);
                },
                onError: () => {
                    setIsProcessing(false);
                },
            }
        );
    };

    const handleBulkAccrueConfirm = (e) => {
        e.preventDefault();
        setIsProcessing(true);

        router.post(
            route("payslips.bulk_accrue"),
            {
                ids: selectedPaylips,
                liability_account_id: liabilityAccountId,
                expense_account_id: expenseAccountId,
            },
            {
                preserveState: true,
                onSuccess: () => {
                    setSelectedPayslips([]);
                    setIsAllSelected(false);
                    setBulkAction("");
                    setShowBulkAccrueModal(false);
                    setIsProcessing(false);
                },
                onError: () => {
                    setIsProcessing(false);
                },
            }
        );
    };

    const handlePaymentAmountChange = (payrollId, amount) => {
        setPaymentAmounts((prev) => ({
            ...prev,
            [payrollId]: amount,
        }));
    };

    const handleBulkPaymentConfirm = (e) => {
        e.preventDefault();
        setIsProcessing(true);

        // Create an array of payment data including the payment amounts
        const paymentData = selectedPaylips.map((id) => ({
            id,
            amount:
                paymentAmounts[id] ||
                payrolls.find((p) => p.id === id)?.net_salary,
        }));

        router.post(
            route("payslips.bulk_payment"),
            {
                payments: paymentData,
                credit_account_id: creditAccountId,
                debit_account_id: debitAccountId,
                advance_account_id: advanceAccountId,
                method: paymentMethod,
                payment_date: paymentDate,
            },
            {
                preserveState: true,
                onSuccess: () => {
                    setSelectedPayslips([]);
                    setIsAllSelected(false);
                    setBulkAction("");
                    setShowBulkPaymentModal(false);
                    setIsProcessing(false);
                    setPaymentAmounts({});
                },
                onError: () => {
                    setIsProcessing(false);
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
        router.get(
            route("payslips.index"),
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
        const totalPages = meta.last_page;
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

    const PayrollStatusBadge = ({ status }) => {
        const statusMap = {
            0: { label: "Draft", className: "text-gray-600" },
            1: { label: "Approved", className: "text-blue-600" },
            2: { label: "Accrued", className: "text-green-600" },
            3: { label: "Partially Paid", className: "text-yellow-600" },
            4: { label: "Paid", className: "text-green-600" },
        };

        return (
            <span className={statusMap[status].className}>
                {statusMap[status].label}
            </span>
        );
    };

    const exportPayrolls = () => {
        window.location.href = route("payslips.export");
    };

    return (
        <AuthenticatedLayout>
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Payroll Management"
                        subpage="List"
                        url="payslips.index"
                    />
                    <div className="p-4">
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div className="flex flex-col md:flex-row gap-2">
                                <Link href={route("payslips.create")}>
                                    <Button>
                                        <Plus className="w-4 h-4 mr-2" />
                                        Generate Payroll
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
                                            onClick={exportPayrolls}
                                        >
                                            <FileDown className="mr-2 h-4 w-4" />{" "}
                                            Export
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                                {/* months */}
                                <SearchableCombobox
                                    options={[
                                        { name: "January", id: "01" },
                                        { name: "February", id: "02" },
                                        { name: "March", id: "03" },
                                        { name: "April", id: "04" },
                                        { name: "May", id: "05" },
                                        { name: "June", id: "06" },
                                        { name: "July", id: "07" },
                                        { name: "August", id: "08" },
                                        { name: "September", id: "09" },
                                        { name: "October", id: "10" },
                                        { name: "November", id: "11" },
                                        { name: "December", id: "12" },
                                    ]}
                                    value={selectedMonth}
                                    onChange={(value) =>
                                        setSelectedMonth(value)
                                    }
                                    className="w-full md:w-44"
                                    placeholder="Select month"
                                />
                                {/* year */}
                                <SearchableCombobox
                                    options={years.map((year) => ({
                                        id: year,
                                        name: year,
                                    }))}
                                    value={selectedYear}
                                    onChange={(value) => setSelectedYear(value)}
                                    className="w-full md:w-44"
                                    placeholder="Select year"
                                />
                            </div>
                            <div className="flex flex-col md:flex-row gap-4 md:items-center">
                                <Input
                                    placeholder="Search payrolls..."
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
                                        <SelectItem value="approve">
                                            Approve Selected
                                        </SelectItem>
                                        <SelectItem value="reject">
                                            Reject Selected
                                        </SelectItem>
                                        <SelectItem value="accrue">
                                            Accrue Selected
                                        </SelectItem>
                                        <SelectItem value="pay">
                                            Pay Selected
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
                                            className="w-[80px] cursor-pointer whitespace-nowrap"
                                            onClick={() => handleSort("id")}
                                        >
                                            NO {renderSortIcon("id")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer whitespace-nowrap"
                                            onClick={() =>
                                                handleSort("employee_id")
                                            }
                                        >
                                            Employee ID{" "}
                                            {renderSortIcon("employee_id")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer whitespace-nowrap"
                                            onClick={() =>
                                                handleSort("staff.name")
                                            }
                                        >
                                            Name {renderSortIcon("staff.name")}
                                        </TableHead>
                                        {userPackage.time_sheet_module ===
                                            1 && (
                                            <>
                                                <TableHead
                                                    className="cursor-pointer whitespace-nowrap"
                                                    onClick={() =>
                                                        handleSort(
                                                            "required_working_days"
                                                        )
                                                    }
                                                >
                                                    Required Working Days{" "}
                                                    {renderSortIcon(
                                                        "required_working_days"
                                                    )}
                                                </TableHead>
                                                <TableHead
                                                    className="cursor-pointer whitespace-nowrap"
                                                    onClick={() =>
                                                        handleSort(
                                                            "public_holiday"
                                                        )
                                                    }
                                                >
                                                    Public Holiday{" "}
                                                    {renderSortIcon(
                                                        "public_holiday"
                                                    )}
                                                </TableHead>
                                                <TableHead
                                                    className="cursor-pointer whitespace-nowrap"
                                                    onClick={() =>
                                                        handleSort("weekend")
                                                    }
                                                >
                                                    Weekend{" "}
                                                    {renderSortIcon("weekend")}
                                                </TableHead>
                                                <TableHead
                                                    className="cursor-pointer whitespace-nowrap"
                                                    onClick={() =>
                                                        handleSort(
                                                            "required_working_hours"
                                                        )
                                                    }
                                                >
                                                    Required Working Hours{" "}
                                                    {renderSortIcon(
                                                        "required_working_hours"
                                                    )}
                                                </TableHead>
                                                <TableHead
                                                    className="cursor-pointer whitespace-nowrap"
                                                    onClick={() =>
                                                        handleSort(
                                                            "overtime_hours"
                                                        )
                                                    }
                                                >
                                                    Overtime Hours{" "}
                                                    {renderSortIcon(
                                                        "overtime_hours"
                                                    )}
                                                </TableHead>
                                                <TableHead
                                                    className="cursor-pointer whitespace-nowrap"
                                                    onClick={() =>
                                                        handleSort(
                                                            "cost_overtime_hours"
                                                        )
                                                    }
                                                >
                                                    Cost of Overtime Hours{" "}
                                                    {renderSortIcon(
                                                        "cost_overtime_hours"
                                                    )}
                                                </TableHead>
                                                <TableHead
                                                    className="cursor-pointer whitespace-nowrap"
                                                    onClick={() =>
                                                        handleSort(
                                                            "cost_public_holiday"
                                                        )
                                                    }
                                                >
                                                    Cost of Public Holiday{" "}
                                                    {renderSortIcon(
                                                        "cost_public_holiday"
                                                    )}
                                                </TableHead>
                                                <TableHead
                                                    className="cursor-pointer whitespace-nowrap"
                                                    onClick={() =>
                                                        handleSort(
                                                            "cost_weekend"
                                                        )
                                                    }
                                                >
                                                    Cost of Weekend{" "}
                                                    {renderSortIcon(
                                                        "cost_weekend"
                                                    )}
                                                </TableHead>
                                                <TableHead
                                                    className="cursor-pointer whitespace-nowrap"
                                                    onClick={() =>
                                                        handleSort(
                                                            "total_cost_normal_hours"
                                                        )
                                                    }
                                                >
                                                    Total Cost of Normal Hours{" "}
                                                    {renderSortIcon(
                                                        "total_cost_normal_hours"
                                                    )}
                                                </TableHead>
                                                <TableHead
                                                    className="cursor-pointer whitespace-nowrap"
                                                    onClick={() =>
                                                        handleSort(
                                                            "total_cost_overtime_hours"
                                                        )
                                                    }
                                                >
                                                    Total Cost of Overtime Hours{" "}
                                                    {renderSortIcon(
                                                        "total_cost_overtime_hours"
                                                    )}
                                                </TableHead>
                                                <TableHead
                                                    className="cursor-pointer whitespace-nowrap"
                                                    onClick={() =>
                                                        handleSort(
                                                            "total_cost_public_holiday"
                                                        )
                                                    }
                                                >
                                                    Total Cost of Public
                                                    Holidays{" "}
                                                    {renderSortIcon(
                                                        "total_cost_public_holiday"
                                                    )}
                                                </TableHead>
                                                <TableHead
                                                    className="cursor-pointer whitespace-nowrap"
                                                    onClick={() =>
                                                        handleSort(
                                                            "total_cost_weekend"
                                                        )
                                                    }
                                                >
                                                    Total Cost of Weekends{" "}
                                                    {renderSortIcon(
                                                        "total_cost_weekend"
                                                    )}
                                                </TableHead>
                                            </>
                                        )}
                                        <TableHead
                                            className="cursor-pointer whitespace-nowrap"
                                            onClick={() =>
                                                handleSort("current_salary")
                                            }
                                        >
                                            Basic Salary{" "}
                                            {renderSortIcon("current_salary")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer whitespace-nowrap"
                                            onClick={() =>
                                                handleSort("total_allowance")
                                            }
                                        >
                                            Additions{" "}
                                            {renderSortIcon("total_allowance")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer whitespace-nowrap"
                                            onClick={() =>
                                                handleSort("tax_amount")
                                            }
                                        >
                                            Payroll Tax{" "}
                                            {renderSortIcon("tax_amount")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer whitespace-nowrap"
                                            onClick={() =>
                                                handleSort("total_deduction")
                                            }
                                        >
                                            Other Deductions{" "}
                                            {renderSortIcon("total_deduction")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer whitespace-nowrap"
                                            onClick={() =>
                                                handleSort("advance")
                                            }
                                        >
                                            Salary Advance Deductions{" "}
                                            {renderSortIcon("advance")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer whitespace-nowrap"
                                            onClick={() =>
                                                handleSort("net_salary")
                                            }
                                        >
                                            Net Salary{" "}
                                            {renderSortIcon("net_salary")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer whitespace-nowrap"
                                            onClick={() => handleSort("paid")}
                                        >
                                            Paid {renderSortIcon("paid")}
                                        </TableHead>
                                        <TableHead>Due Amount</TableHead>
                                        <TableHead>Amount</TableHead>
                                        <TableHead
                                            className="cursor-pointer"
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
                                    <TableRow>
                                        <TableCell colSpan={4}></TableCell>
                                        {userPackage.time_sheet_module ===
                                            1 && (
                                            <>
                                                <TableCell>
                                                    <span className="text-blue-600">
                                                        {formatAmount(
                                                            payrolls.reduce(
                                                                (
                                                                    acc,
                                                                    payroll
                                                                ) =>
                                                                    acc +
                                                                    Number(
                                                                        payroll.required_working_days ||
                                                                            0
                                                                    ),
                                                                0
                                                            )
                                                        )}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-blue-600">
                                                        {formatAmount(
                                                            payrolls.reduce(
                                                                (
                                                                    acc,
                                                                    payroll
                                                                ) =>
                                                                    acc +
                                                                    Number(
                                                                        payroll.public_holiday ||
                                                                            0
                                                                    ),
                                                                0
                                                            )
                                                        )}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-blue-600">
                                                        {formatAmount(
                                                            payrolls.reduce(
                                                                (
                                                                    acc,
                                                                    payroll
                                                                ) =>
                                                                    acc +
                                                                    Number(
                                                                        payroll.weekend ||
                                                                            0
                                                                    ),
                                                                0
                                                            )
                                                        )}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-blue-600">
                                                        {formatAmount(
                                                            payrolls.reduce(
                                                                (
                                                                    acc,
                                                                    payroll
                                                                ) =>
                                                                    acc +
                                                                    Number(
                                                                        payroll.required_working_hours ||
                                                                            0
                                                                    ),
                                                                0
                                                            )
                                                        )}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-blue-600">
                                                        {formatAmount(
                                                            payrolls.reduce(
                                                                (
                                                                    acc,
                                                                    payroll
                                                                ) =>
                                                                    acc +
                                                                    Number(
                                                                        payroll.overtime_hours ||
                                                                            0
                                                                    ),
                                                                0
                                                            )
                                                        )}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-blue-600">
                                                        {formatCurrency({
                                                            amount: payrolls.reduce(
                                                                (
                                                                    acc,
                                                                    payroll
                                                                ) =>
                                                                    acc +
                                                                    Number(
                                                                        payroll.cost_overtime_hours ||
                                                                            0
                                                                    ),
                                                                0
                                                            ),
                                                        })}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-blue-600">
                                                        {formatCurrency({
                                                            amount: payrolls.reduce(
                                                                (
                                                                    acc,
                                                                    payroll
                                                                ) =>
                                                                    acc +
                                                                    Number(
                                                                        payroll.cost_public_holiday ||
                                                                            0
                                                                    ),
                                                                0
                                                            ),
                                                        })}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-blue-600">
                                                        {formatCurrency({
                                                            amount: payrolls.reduce(
                                                                (
                                                                    acc,
                                                                    payroll
                                                                ) =>
                                                                    acc +
                                                                    Number(
                                                                        payroll.cost_weekend ||
                                                                            0
                                                                    ),
                                                                0
                                                            ),
                                                        })}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-blue-600">
                                                        {formatCurrency({
                                                            amount: payrolls.reduce(
                                                                (
                                                                    acc,
                                                                    payroll
                                                                ) =>
                                                                    acc +
                                                                    Number(
                                                                        payroll.total_cost_normal_hours ||
                                                                            0
                                                                    ),
                                                                0
                                                            ),
                                                        })}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-blue-600">
                                                        {formatCurrency({
                                                            amount: payrolls.reduce(
                                                                (
                                                                    acc,
                                                                    payroll
                                                                ) =>
                                                                    acc +
                                                                    Number(
                                                                        payroll.total_cost_overtime_hours ||
                                                                            0
                                                                    ),
                                                                0
                                                            ),
                                                        })}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-blue-600">
                                                        {formatCurrency({
                                                            amount: payrolls.reduce(
                                                                (
                                                                    acc,
                                                                    payroll
                                                                ) =>
                                                                    acc +
                                                                    Number(
                                                                        payroll.total_cost_public_holiday ||
                                                                            0
                                                                    ),
                                                                0
                                                            ),
                                                        })}
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-blue-600">
                                                        {formatCurrency({
                                                            amount: payrolls.reduce(
                                                                (
                                                                    acc,
                                                                    payroll
                                                                ) =>
                                                                    acc +
                                                                    Number(
                                                                        payroll.total_cost_weekend ||
                                                                            0
                                                                    ),
                                                                0
                                                            ),
                                                        })}
                                                    </span>
                                                </TableCell>
                                            </>
                                        )}
                                        <TableCell>
                                            <span className="text-blue-600">
                                                {formatCurrency({
                                                    amount: payrolls.reduce(
                                                        (acc, payroll) =>
                                                            acc +
                                                            Number(
                                                                payroll.current_salary ||
                                                                    0
                                                            ),
                                                        0
                                                    ),
                                                })}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <span className="text-green-600">
                                                {formatCurrency({
                                                    amount: payrolls.reduce(
                                                        (acc, payroll) =>
                                                            acc +
                                                            Number(
                                                                payroll.total_allowance ||
                                                                    0
                                                            ),
                                                        0
                                                    ),
                                                })}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <span className="text-red-600">
                                                {formatCurrency({
                                                    amount: payrolls.reduce(
                                                        (acc, payroll) =>
                                                            acc +
                                                            Number(
                                                                payroll.total_tax ||
                                                                    0
                                                            ),
                                                        0
                                                    ),
                                                })}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <span className="text-red-600">
                                                {formatCurrency({
                                                    amount: payrolls.reduce(
                                                        (acc, payroll) =>
                                                            acc +
                                                            Number(
                                                                payroll.total_deduction ||
                                                                    0
                                                            ),
                                                        0
                                                    ),
                                                })}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <span className="text-red-600">
                                                {formatCurrency({
                                                    amount: payrolls.reduce(
                                                        (acc, payroll) =>
                                                            acc +
                                                            Number(
                                                                payroll.advance ||
                                                                    0
                                                            ),
                                                        0
                                                    ),
                                                })}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <span className="text-green-600">
                                                {formatCurrency({
                                                    amount: payrolls.reduce(
                                                        (acc, payroll) =>
                                                            acc +
                                                            Number(
                                                                payroll.net_salary ||
                                                                    0
                                                            ),
                                                        0
                                                    ),
                                                })}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <span className="text-green-600">
                                                {formatCurrency({
                                                    amount: payrolls.reduce(
                                                        (acc, payroll) =>
                                                            acc +
                                                            Number(
                                                                payroll.paid ||
                                                                    0
                                                            ),
                                                        0
                                                    ),
                                                })}
                                            </span>
                                        </TableCell>
                                        <TableCell>
                                            <span className="text-red-600">
                                                {formatCurrency({
                                                    amount: payrolls.reduce(
                                                        (acc, payroll) =>
                                                            acc +
                                                            (Number(
                                                                payroll.net_salary ||
                                                                    0
                                                            ) -
                                                                Number(
                                                                    payroll.paid ||
                                                                        0
                                                                )),
                                                        0
                                                    ),
                                                })}
                                            </span>
                                        </TableCell>
                                        <TableCell></TableCell>
                                        <TableCell></TableCell>
                                        <TableCell></TableCell>
                                    </TableRow>
                                    {payrolls.length > 0 ? (
                                        payrolls.map((payroll, index) => (
                                            <TableRow key={payroll.id}>
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selectedPaylips.includes(
                                                            payroll.id
                                                        )}
                                                        onCheckedChange={() =>
                                                            toggleSelectPayroll(
                                                                payroll.id
                                                            )
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    {index + 1}
                                                </TableCell>
                                                <TableCell>
                                                    <Link
                                                        href={route(
                                                            "payslips.show",
                                                            payroll.id
                                                        )}
                                                        className="text-blue-600 underline"
                                                    >
                                                        {
                                                            payroll.staff
                                                                .employee_id
                                                        }
                                                    </Link>
                                                </TableCell>
                                                <TableCell className="whitespace-nowrap">
                                                    {payroll.staff.name}
                                                </TableCell>
                                                {userPackage.time_sheet_module ===
                                                    1 && (
                                                    <>
                                                        <TableCell className="whitespace-nowrap">
                                                            {formatAmount(
                                                                payroll.required_working_days
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {formatAmount(
                                                                payroll.public_holiday
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {formatAmount(
                                                                payroll.weekend
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {formatAmount(
                                                                payroll.required_working_hours
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {formatAmount(
                                                                payroll.overtime_hours
                                                            )}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {formatCurrency({
                                                                amount: payroll.cost_overtime_hours,
                                                            })}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {formatCurrency({
                                                                amount: payroll.cost_public_holiday,
                                                            })}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {formatCurrency({
                                                                amount: payroll.cost_weekend,
                                                            })}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {formatCurrency({
                                                                amount: payroll.total_cost_normal_hours,
                                                            })}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {formatCurrency({
                                                                amount: payroll.total_cost_overtime_hours,
                                                            })}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {formatCurrency({
                                                                amount: payroll.total_cost_public_holiday,
                                                            })}
                                                        </TableCell>
                                                        <TableCell className="whitespace-nowrap">
                                                            {formatCurrency({
                                                                amount: payroll.total_cost_weekend,
                                                            })}
                                                        </TableCell>
                                                    </>
                                                )}
                                                <TableCell className="whitespace-nowrap">
                                                    {formatCurrency({
                                                        amount: payroll.current_salary,
                                                    })}
                                                </TableCell>
                                                <TableCell className="whitespace-nowrap">
                                                    {formatCurrency({
                                                        amount: payroll.total_allowance,
                                                    })}
                                                </TableCell>
                                                <TableCell className="whitespace-nowrap">
                                                    {formatCurrency({
                                                        amount: payroll.total_tax,
                                                    })}
                                                </TableCell>
                                                <TableCell className="whitespace-nowrap">
                                                    {formatCurrency({
                                                        amount: payroll.total_deduction,
                                                    })}
                                                </TableCell>
                                                <TableCell className="whitespace-nowrap">
                                                    {formatCurrency({
                                                        amount: payroll.advance,
                                                    })}
                                                </TableCell>
                                                <TableCell className="whitespace-nowrap">
                                                    {formatCurrency({
                                                        amount: payroll.net_salary,
                                                    })}
                                                </TableCell>
                                                <TableCell className="whitespace-nowrap">
                                                    {formatCurrency({
                                                        amount: payroll.paid,
                                                    })}
                                                </TableCell>
                                                <TableCell className="whitespace-nowrap">
                                                    {formatCurrency({
                                                        amount:
                                                            payroll.net_salary -
                                                            payroll.paid,
                                                    })}
                                                </TableCell>
                                                <TableCell>
                                                    <Input
                                                        type="number"
                                                        value={
                                                            paymentAmounts[
                                                                payroll.id
                                                            ] ||
                                                            payroll.net_salary -
                                                                payroll.paid
                                                        }
                                                        onChange={(e) =>
                                                            handlePaymentAmountChange(
                                                                payroll.id,
                                                                parseFloat(
                                                                    e.target
                                                                        .value
                                                                )
                                                            )
                                                        }
                                                        className="w-32"
                                                        min="0"
                                                        max={payroll.net_salary}
                                                        step="0.01"
                                                        disabled={
                                                            payroll.status === 4
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <PayrollStatusBadge
                                                        status={payroll.status}
                                                    />
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <TableActions
                                                        actions={[
                                                            {
                                                                label: "View",
                                                                icon: (
                                                                    <EyeIcon className="h-4 w-4" />
                                                                ),
                                                                href: route(
                                                                    "payslips.show",
                                                                    payroll.id
                                                                ),
                                                            },
                                                            {
                                                                label: "Edit",
                                                                icon: (
                                                                    <Edit className="h-4 w-4" />
                                                                ),
                                                                href: route(
                                                                    "payslips.edit",
                                                                    payroll.id
                                                                ),
                                                            },
                                                            {
                                                                label: "Delete",
                                                                icon: (
                                                                    <Trash className="h-4 w-4" />
                                                                ),
                                                                onClick: () =>
                                                                    handleDeleteConfirm(
                                                                        payroll.id
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
                                                colSpan={15}
                                                className="h-24 text-center"
                                            >
                                                No payrolls found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {meta.last_page > 1 && (
                            <div className="flex justify-between items-center mt-4">
                                <div className="text-sm text-gray-500">
                                    Showing {meta.from || 0} to {meta.to || 0}{" "}
                                    of {meta.total} entries
                                </div>
                                <div className="flex gap-1">
                                    {renderPageNumbers()}
                                </div>
                            </div>
                        )}
                    </div>

                    {/* Delete Confirmation Modal */}
                    <DeleteConfirmationModal
                        show={showDeleteModal}
                        onClose={() => setShowDeleteModal(false)}
                        onConfirm={handleDelete}
                        processing={isProcessing}
                    />

                    {/* Bulk Delete Confirmation Modal */}
                    <BulkDeleteConfirmationModal
                        show={showBulkDeleteModal}
                        onClose={() => setShowBulkDeleteModal(false)}
                        onConfirm={handleBulkDeleteConfirm}
                        processing={isProcessing}
                        count={selectedPaylips.length}
                    />

                    <BulkApproveConfirmationModal
                        show={showBulkApproveModal}
                        onClose={() => setShowBulkApproveModal(false)}
                        onConfirm={handleBulkApproveConfirm}
                        processing={isProcessing}
                        count={selectedPaylips.length}
                    />

                    <BulkRejectConfirmationModal
                        show={showBulkRejectModal}
                        onClose={() => setShowBulkRejectModal(false)}
                        onConfirm={handleBulkRejectConfirm}
                        processing={isProcessing}
                        count={selectedPaylips.length}
                    />

                    <BulkAccrueConfirmationModal
                        show={showBulkAccrueModal}
                        onClose={() => setShowBulkAccrueModal(false)}
                        onConfirm={handleBulkAccrueConfirm}
                        processing={isProcessing}
                        count={selectedPaylips.length}
                        accounts={accounts}
                        liabilityAccountId={liabilityAccountId}
                        expenseAccountId={expenseAccountId}
                        setLiabilityAccountId={setLiabilityAccountId}
                        setExpenseAccountId={setExpenseAccountId}
                    />

                    <BulkPaymentConfirmationModal
                        show={showBulkPaymentModal}
                        onClose={() => setShowBulkPaymentModal(false)}
                        onConfirm={handleBulkPaymentConfirm}
                        processing={isProcessing}
                        count={selectedPaylips.length}
                        accounts={accounts}
                        creditAccountId={creditAccountId}
                        debitAccountId={debitAccountId}
                        advanceAccountId={advanceAccountId}
                        methods={methods}
                        setCreditAccountId={setCreditAccountId}
                        setDebitAccountId={setDebitAccountId}
                        setAdvanceAccountId={setAdvanceAccountId}
                        setPaymentMethod={setPaymentMethod}
                        setPaymentDate={setPaymentDate}
                        paymentDate={paymentDate}
                    />
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}

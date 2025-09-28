import React, { useState, useEffect } from "react";
import { Link, router, useForm, usePage } from "@inertiajs/react";
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
    Edit,
    EyeIcon,
    Plus,
    Trash,
    Trash2,
    ChevronUp,
    ChevronDown,
} from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { DrawerComponent } from "@/Components/DrawerComponent";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { Label } from "@/Components/ui/label";
import InputError from "@/Components/InputError";
import DateTimePicker from "@/Components/DateTimePicker";
import { formatCurrency, parseDateObject } from "@/lib/utils";

// Delete Confirmation Modal Component
const DeleteSalaryAdvancesModal = ({
    show,
    onClose,
    onConfirm,
    processing,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete this salary advance?
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
const DeleteAllSalaryAdvancesModal = ({
    show,
    onClose,
    onConfirm,
    processing,
    count,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete {count} selected salary advance
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

export default function List({
    salaryAdvances = [],
    meta = {},
    filters = {},
    trashed_salaryAdvances = 0,
    employees = [],
    accounts = [],
    years = [],
    currencies = [],
}) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [selectedSalaryAdvance, setSelectedSalaryAdvance] = useState([]);
    const [isAllSelected, setIsAllSelected] = useState(false);
    const [search, setSearch] = useState(filters.search || "");
    const [perPage, setPerPage] = useState(meta.per_page || 50);
    const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
    const [bulkAction, setBulkAction] = useState("");
    const [sorting, setSorting] = useState(
        filters.sorting || { column: "id", direction: "desc" }
    );

    // Delete confirmation modal states
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [salaryAdvanceToDelete, setSalaryAdvanceToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);
    const [drawerOpen, setDrawerOpen] = useState(false);
    const [editDrawerOpen, setEditDrawerOpen] = useState(false);
    const [editingSalaryAdvance, setEditingSalaryAdvance] = useState(null);

    const { data, setData, post, errors, reset } = useForm({
        employee_id: "",
        currency: "",
        amount: "",
        date: "",
        payment_account_id: "",
        advance_account_id: "",
        payroll_month: "",
        payroll_year: "",
        notes: "",
    });

    const { data: editData, setData: setEditData, put, errors: editErrors, reset: resetEdit } = useForm({
        employee_id: "",
        currency: "",
        amount: "",
        date: "",
        payment_account_id: "",
        advance_account_id: "",
        payroll_month: "",
        payroll_year: "",
        notes: "",
    });

    const handleSubmitSalaryAdvance = (e) => {
        e.preventDefault();
        setProcessing(true);
        post(route("salary_advances.store"), {
            preserveScroll: true,
            onSuccess: () => {
                reset();
                setProcessing(false);
                setDrawerOpen(false);
            },
            onError: () => {
                setProcessing(false);
            },
        });
    };

    const handleEditSalaryAdvance = (salaryAdvance) => {
        setEditingSalaryAdvance(salaryAdvance);
        setEditData({
            employee_id: salaryAdvance.employee_id,
            currency: salaryAdvance.currency,
            amount: salaryAdvance.amount,
            date: parseDateObject(salaryAdvance.date),
            payment_account_id: salaryAdvance.payment_account_id,
            advance_account_id: salaryAdvance.advance_account_id,
            payroll_month: salaryAdvance.payroll_month,
            payroll_year: salaryAdvance.payroll_year,
            notes: salaryAdvance.notes || "",
        });
        setEditDrawerOpen(true);
    };

    const handleUpdateSalaryAdvance = (e) => {
        e.preventDefault();
        setProcessing(true);
        put(route("salary_advances.update", editingSalaryAdvance.id), {
            preserveScroll: true,
            onSuccess: () => {
                resetEdit();
                setProcessing(false);
                setEditDrawerOpen(false);
                setEditingSalaryAdvance(null);
            },
            onError: () => {
                setProcessing(false);
            },
        });
    };

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
            setSelectedSalaryAdvance([]);
        } else {
            setSelectedSalaryAdvance(
                salaryAdvances.map((salaryAdvance) => salaryAdvance.id)
            );
        }
        setIsAllSelected(!isAllSelected);
    };

    const toggleSelectSalaryAdvance = (id) => {
        if (selectedSalaryAdvance.includes(id)) {
            setSelectedSalaryAdvance(
                selectedSalaryAdvance.filter(
                    (salaryAdvanceId) => salaryAdvanceId !== id
                )
            );
            setIsAllSelected(false);
        } else {
            setSelectedSalaryAdvance([...selectedSalaryAdvance, id]);
            if (selectedSalaryAdvance.length + 1 === salaryAdvances.length) {
                setIsAllSelected(true);
            }
        }
    };

    const handleSearch = (e) => {
        e.preventDefault();
        const value = e.target.value;
        setSearch(value);

        router.get(
            route("salary_advances.index"),
            { search: value, page: 1, per_page: perPage },
            { preserveState: true }
        );
    };

    const handlePerPageChange = (value) => {
        setPerPage(value);
        router.get(
            route("salary_advances.index"),
            { search, page: 1, per_page: value },
            { preserveState: true }
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("salary_advances.index"),
            { search, page, per_page: perPage },
            { preserveState: true }
        );
    };

    const handleBulkAction = () => {
        if (bulkAction === "") return;

        if (selectedSalaryAdvance.length === 0) {
            toast({
                variant: "destructive",
                title: "Error",
                description: "Please select at least one salary advance",
            });
            return;
        }

        if (bulkAction === "delete") {
            setShowDeleteAllModal(true);
        }
    };

    const handleDeleteConfirm = (id) => {
        setSalaryAdvanceToDelete(id);
        setShowDeleteModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.delete(route("salary_advances.destroy", salaryAdvanceToDelete), {
            onSuccess: () => {
                setShowDeleteModal(false);
                setSalaryAdvanceToDelete(null);
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
            route("salary_advances.bulk_destroy"),
            {
                ids: selectedSalaryAdvance,
            },
            {
                onSuccess: () => {
                    setShowDeleteAllModal(false);
                    setSelectedSalaryAdvance([]);
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
        router.get(
            route("salary_advances.index"),
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

    return (
        <AuthenticatedLayout>
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Salary Advances"
                        subpage="List"
                        url="salary_advances.index"
                    />
                    <div className="p-4">
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div className="flex flex-col md:flex-row gap-2">
                                <DrawerComponent
                                    position="right"
                                    width="w-4/5"
                                    title="Create Salary Advance"
                                    open={drawerOpen}
                                    onOpenChange={setDrawerOpen}
                                    trigger={
                                        <Button>
                                            <Plus className="w-4 h-4 mr-2" />
                                            Add Salary Advance
                                        </Button>
                                    }
                                >
                                    <form onSubmit={handleSubmitSalaryAdvance}>
                                        <div className="grid grid-cols-12 mt-2">
                                            <Label
                                                htmlFor="employee_id"
                                                className="md:col-span-2 col-span-12"
                                            >
                                                Employee *
                                            </Label>
                                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                                <div className="md:w-1/2 w-full">
                                                    <SearchableCombobox
                                                        options={employees.map(
                                                            (employee) => ({
                                                                id: employee.id,
                                                                name: employee.name,
                                                            })
                                                        )}
                                                        value={data.employee_id}
                                                        onChange={(value) =>
                                                            setData(
                                                                "employee_id",
                                                                value
                                                            )
                                                        }
                                                        placeholder="Select employee"
                                                    />
                                                </div>
                                                <InputError
                                                    message={errors.employee_id}
                                                    className="text-sm"
                                                />
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-12 mt-2">
                                            <Label
                                                htmlFor="date"
                                                className="md:col-span-2 col-span-12"
                                            >
                                                Date *
                                            </Label>
                                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                                <DateTimePicker
                                                    value={data.date}
                                                    onChange={(date) =>
                                                        setData("date", date)
                                                    }
                                                    className="md:w-1/2 w-full"
                                                    required
                                                />
                                                <InputError
                                                    message={errors.date}
                                                    className="text-sm"
                                                />
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-12 mt-2">
                                            <Label
                                                htmlFor="currency"
                                                className="md:col-span-2 col-span-12"
                                            >
                                                Currency *
                                            </Label>
                                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                                <div className="md:w-1/2 w-full">
                                                    <SearchableCombobox
                                                        className="mt-1"
                                                        options={currencies.map(
                                                            (currency) => ({
                                                                id: currency.name,
                                                                value: currency.name,
                                                                label: currency.name,
                                                                name: `${currency.name} - ${currency.description} (${currency.exchange_rate})`,
                                                            })
                                                        )}
                                                        value={data.currency}
                                                        onChange={(
                                                            selectedValue
                                                        ) => {
                                                            console.log(
                                                                "Currency selected:",
                                                                selectedValue
                                                            );
                                                            setData(
                                                                "currency",
                                                                selectedValue
                                                            );
                                                        }}
                                                        placeholder="Select currency"
                                                    />
                                                </div>
                                                <InputError
                                                    message={errors.currency}
                                                    className="text-sm"
                                                />
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-12 mt-2">
                                            <Label
                                                htmlFor="amount"
                                                className="md:col-span-2 col-span-12"
                                            >
                                                Amount *
                                            </Label>
                                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                                <Input
                                                    id="amount"
                                                    type="text"
                                                    value={data.amount}
                                                    onChange={(e) =>
                                                        setData(
                                                            "amount",
                                                            e.target.value
                                                        )
                                                    }
                                                    className="md:w-1/2 w-full"
                                                />
                                                <InputError
                                                    message={errors.amount}
                                                    className="text-sm"
                                                />
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-12 mt-2">
                                            <Label
                                                htmlFor="payment_account_id"
                                                className="md:col-span-2 col-span-12"
                                            >
                                                Payment Account *
                                            </Label>
                                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                                <div className="md:w-1/2 w-full">
                                                    <SearchableCombobox
                                                        options={accounts.map(
                                                            (account) => ({
                                                                id: account.id,
                                                                name: account.account_name,
                                                            })
                                                        )}
                                                        value={
                                                            data.payment_account_id
                                                        }
                                                        onChange={(value) =>
                                                            setData(
                                                                "payment_account_id",
                                                                value
                                                            )
                                                        }
                                                        placeholder="Select account"
                                                    />
                                                </div>
                                                <InputError
                                                    message={
                                                        errors.payment_account_id
                                                    }
                                                    className="text-sm"
                                                />
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-12 mt-2">
                                            <Label
                                                htmlFor="advance_account_id"
                                                className="md:col-span-2 col-span-12"
                                            >
                                                Advance Account *
                                            </Label>
                                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                                <div className="md:w-1/2 w-full">
                                                    <SearchableCombobox
                                                        options={accounts.map(
                                                            (account) => ({
                                                                id: account.id,
                                                                name: account.account_name,
                                                            })
                                                        )}
                                                        value={
                                                            data.advance_account_id
                                                        }
                                                        onChange={(value) =>
                                                            setData(
                                                                "advance_account_id",
                                                                value
                                                            )
                                                        }
                                                        placeholder="Select account"
                                                    />
                                                </div>
                                                <InputError
                                                    message={
                                                        errors.advance_account_id
                                                    }
                                                    className="text-sm"
                                                />
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-12 mt-2">
                                            <Label
                                                htmlFor="payroll_month"
                                                className="md:col-span-2 col-span-12"
                                            >
                                                Payroll Month *
                                            </Label>
                                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                                <div className="md:w-1/2 w-full">
                                                    <SearchableCombobox
                                                        options={[
                                                            {
                                                                name: "January",
                                                                id: "01",
                                                            },
                                                            {
                                                                name: "February",
                                                                id: "02",
                                                            },
                                                            {
                                                                name: "March",
                                                                id: "03",
                                                            },
                                                            {
                                                                name: "April",
                                                                id: "04",
                                                            },
                                                            {
                                                                name: "May",
                                                                id: "05",
                                                            },
                                                            {
                                                                name: "June",
                                                                id: "06",
                                                            },
                                                            {
                                                                name: "July",
                                                                id: "07",
                                                            },
                                                            {
                                                                name: "August",
                                                                id: "08",
                                                            },
                                                            {
                                                                name: "September",
                                                                id: "09",
                                                            },
                                                            {
                                                                name: "October",
                                                                id: "10",
                                                            },
                                                            {
                                                                name: "November",
                                                                id: "11",
                                                            },
                                                            {
                                                                name: "December",
                                                                id: "12",
                                                            },
                                                        ]}
                                                        value={
                                                            data.payroll_month
                                                        }
                                                        onChange={(value) =>
                                                            setData(
                                                                "payroll_month",
                                                                value
                                                            )
                                                        }
                                                        placeholder="Select month"
                                                    />
                                                </div>
                                                <InputError
                                                    message={
                                                        errors.payroll_month
                                                    }
                                                    className="text-sm"
                                                />
                                            </div>
                                        </div>
                                        <div className="grid grid-cols-12 mt-2">
                                            <Label
                                                htmlFor="payroll_year"
                                                className="md:col-span-2 col-span-12"
                                            >
                                                Payroll Year *
                                            </Label>
                                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                                <div className="md:w-1/2 w-full">
                                                    <SearchableCombobox
                                                        options={years.map(
                                                            (year) => ({
                                                                id: year,
                                                                name: year,
                                                            })
                                                        )}
                                                        value={
                                                            data.payroll_year
                                                        }
                                                        onChange={(value) =>
                                                            setData(
                                                                "payroll_year",
                                                                value
                                                            )
                                                        }
                                                        placeholder="Select year"
                                                    />
                                                </div>
                                                <InputError
                                                    message={
                                                        errors.payroll_year
                                                    }
                                                    className="text-sm"
                                                />
                                            </div>
                                        </div>

                                        {/* submit button */}
                                        <div className="mt-4">
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                            >
                                                {processing
                                                    ? "Submitting..."
                                                    : "Add Salary Advance"}
                                            </Button>
                                        </div>
                                    </form>
                                </DrawerComponent>
                                
                                <DrawerComponent
                                    position="right"
                                    width="w-4/5"
                                    title="Edit Salary Advance"
                                    open={editDrawerOpen}
                                    onOpenChange={setEditDrawerOpen}
                                >
                                    <form onSubmit={handleUpdateSalaryAdvance}>
                                        <div className="grid grid-cols-12 mt-2">
                                            <Label
                                                htmlFor="edit_employee_id"
                                                className="md:col-span-2 col-span-12"
                                            >
                                                Employee *
                                            </Label>
                                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                                <div className="md:w-1/2 w-full">
                                                    <SearchableCombobox
                                                        options={employees.map(
                                                            (employee) => ({
                                                                id: employee.id,
                                                                name: employee.name,
                                                            })
                                                        )}
                                                        value={editData.employee_id}
                                                        onChange={(value) =>
                                                            setEditData(
                                                                "employee_id",
                                                                value
                                                            )
                                                        }
                                                        placeholder="Select employee"
                                                    />
                                                </div>
                                                <InputError
                                                    message={editErrors.employee_id}
                                                    className="text-sm"
                                                />
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-12 mt-2">
                                            <Label
                                                htmlFor="edit_date"
                                                className="md:col-span-2 col-span-12"
                                            >
                                                Date *
                                            </Label>
                                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                                <DateTimePicker
                                                    value={editData.date}
                                                    onChange={(date) =>
                                                        setEditData("date", date)
                                                    }
                                                    className="md:w-1/2 w-full"
                                                    required
                                                />
                                                <InputError
                                                    message={editErrors.date}
                                                    className="text-sm"
                                                />
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-12 mt-2">
                                            <Label
                                                htmlFor="edit_currency"
                                                className="md:col-span-2 col-span-12"
                                            >
                                                Currency *
                                            </Label>
                                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                                <div className="md:w-1/2 w-full">
                                                    <SearchableCombobox
                                                        className="mt-1"
                                                        options={currencies.map(
                                                            (currency) => ({
                                                                id: currency.name,
                                                                value: currency.name,
                                                                label: currency.name,
                                                                name: `${currency.name} - ${currency.description} (${currency.exchange_rate})`,
                                                            })
                                                        )}
                                                        value={editData.currency}
                                                        onChange={(
                                                            selectedValue
                                                        ) => {
                                                            setEditData(
                                                                "currency",
                                                                selectedValue
                                                            );
                                                        }}
                                                        placeholder="Select currency"
                                                    />
                                                </div>
                                                <InputError
                                                    message={editErrors.currency}
                                                    className="text-sm"
                                                />
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-12 mt-2">
                                            <Label
                                                htmlFor="edit_amount"
                                                className="md:col-span-2 col-span-12"
                                            >
                                                Amount *
                                            </Label>
                                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                                <Input
                                                    id="edit_amount"
                                                    type="text"
                                                    value={editData.amount}
                                                    onChange={(e) =>
                                                        setEditData(
                                                            "amount",
                                                            e.target.value
                                                        )
                                                    }
                                                    className="md:w-1/2 w-full"
                                                />
                                                <InputError
                                                    message={editErrors.amount}
                                                    className="text-sm"
                                                />
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-12 mt-2">
                                            <Label
                                                htmlFor="edit_payment_account_id"
                                                className="md:col-span-2 col-span-12"
                                            >
                                                Payment Account *
                                            </Label>
                                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                                <div className="md:w-1/2 w-full">
                                                    <SearchableCombobox
                                                        options={accounts.map(
                                                            (account) => ({
                                                                id: account.id,
                                                                name: account.account_name,
                                                            })
                                                        )}
                                                        value={
                                                            editData.payment_account_id
                                                        }
                                                        onChange={(value) =>
                                                            setEditData(
                                                                "payment_account_id",
                                                                value
                                                            )
                                                        }
                                                        placeholder="Select account"
                                                    />
                                                </div>
                                                <InputError
                                                    message={
                                                        editErrors.payment_account_id
                                                    }
                                                    className="text-sm"
                                                />
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-12 mt-2">
                                            <Label
                                                htmlFor="edit_advance_account_id"
                                                className="md:col-span-2 col-span-12"
                                            >
                                                Advance Account *
                                            </Label>
                                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                                <div className="md:w-1/2 w-full">
                                                    <SearchableCombobox
                                                        options={accounts.map(
                                                            (account) => ({
                                                                id: account.id,
                                                                name: account.account_name,
                                                            })
                                                        )}
                                                        value={
                                                            editData.advance_account_id
                                                        }
                                                        onChange={(value) =>
                                                            setEditData(
                                                                "advance_account_id",
                                                                value
                                                            )
                                                        }
                                                        placeholder="Select account"
                                                    />
                                                </div>
                                                <InputError
                                                    message={
                                                        editErrors.advance_account_id
                                                    }
                                                    className="text-sm"
                                                />
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-12 mt-2">
                                            <Label
                                                htmlFor="edit_payroll_month"
                                                className="md:col-span-2 col-span-12"
                                            >
                                                Payroll Month *
                                            </Label>
                                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                                <div className="md:w-1/2 w-full">
                                                    <SearchableCombobox
                                                        options={[
                                                            {
                                                                name: "January",
                                                                id: "01",
                                                            },
                                                            {
                                                                name: "February",
                                                                id: "02",
                                                            },
                                                            {
                                                                name: "March",
                                                                id: "03",
                                                            },
                                                            {
                                                                name: "April",
                                                                id: "04",
                                                            },
                                                            {
                                                                name: "May",
                                                                id: "05",
                                                            },
                                                            {
                                                                name: "June",
                                                                id: "06",
                                                            },
                                                            {
                                                                name: "July",
                                                                id: "07",
                                                            },
                                                            {
                                                                name: "August",
                                                                id: "08",
                                                            },
                                                            {
                                                                name: "September",
                                                                id: "09",
                                                            },
                                                            {
                                                                name: "October",
                                                                id: "10",
                                                            },
                                                            {
                                                                name: "November",
                                                                id: "11",
                                                            },
                                                            {
                                                                name: "December",
                                                                id: "12",
                                                            },
                                                        ]}
                                                        value={
                                                            editData.payroll_month
                                                        }
                                                        onChange={(value) =>
                                                            setEditData(
                                                                "payroll_month",
                                                                value
                                                            )
                                                        }
                                                        placeholder="Select month"
                                                    />
                                                </div>
                                                <InputError
                                                    message={
                                                        editErrors.payroll_month
                                                    }
                                                    className="text-sm"
                                                />
                                            </div>
                                        </div>
                                        <div className="grid grid-cols-12 mt-2">
                                            <Label
                                                htmlFor="edit_payroll_year"
                                                className="md:col-span-2 col-span-12"
                                            >
                                                Payroll Year *
                                            </Label>
                                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                                <div className="md:w-1/2 w-full">
                                                    <SearchableCombobox
                                                        options={years.map(
                                                            (year) => ({
                                                                id: year,
                                                                name: year,
                                                            })
                                                        )}
                                                        value={
                                                            editData.payroll_year
                                                        }
                                                        onChange={(value) =>
                                                            setEditData(
                                                                "payroll_year",
                                                                value
                                                            )
                                                        }
                                                        placeholder="Select year"
                                                    />
                                                </div>
                                                <InputError
                                                    message={
                                                        editErrors.payroll_year
                                                    }
                                                    className="text-sm"
                                                />
                                            </div>
                                        </div>

                                        <div className="grid grid-cols-12 mt-2">
                                            <Label
                                                htmlFor="edit_notes"
                                                className="md:col-span-2 col-span-12"
                                            >
                                                Notes
                                            </Label>
                                            <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                                                <Input
                                                    id="edit_notes"
                                                    type="text"
                                                    value={editData.notes}
                                                    onChange={(e) =>
                                                        setEditData(
                                                            "notes",
                                                            e.target.value
                                                        )
                                                    }
                                                    className="md:w-1/2 w-full"
                                                />
                                                <InputError
                                                    message={editErrors.notes}
                                                    className="text-sm"
                                                />
                                            </div>
                                        </div>

                                        {/* submit button */}
                                        <div className="mt-4">
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                            >
                                                {processing
                                                    ? "Updating..."
                                                    : "Update Salary Advance"}
                                            </Button>
                                        </div>
                                    </form>
                                </DrawerComponent>
                                
                                <Link href={route("salary_advances.trash")}>
                                    <Button
                                        variant="outline"
                                        className="relative"
                                    >
                                        <Trash2 className="h-8 w-8" />
                                        {trashed_salaryAdvances > 0 && (
                                            <span className="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-6 w-6 flex items-center justify-center font-bold">
                                                {trashed_salaryAdvances}
                                            </span>
                                        )}
                                    </Button>
                                </Link>
                            </div>
                            <div className="flex flex-col md:flex-row gap-4 md:items-center">
                                <Input
                                    placeholder="search salary advances..."
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
                                            onClick={() => handleSort("id")}
                                        >
                                            ID {renderSortIcon("id")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() => handleSort("name")}
                                        >
                                            Employee{" "}
                                            {renderSortIcon("employee")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() => handleSort("amount")}
                                        >
                                            Amount {renderSortIcon("amount")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() => handleSort("date")}
                                        >
                                            Date {renderSortIcon("date")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() =>
                                                handleSort("payroll_month")
                                            }
                                        >
                                            Payroll Month{" "}
                                            {renderSortIcon("payroll_month")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() =>
                                                handleSort("payroll_year")
                                            }
                                        >
                                            Payroll Year{" "}
                                            {renderSortIcon("payroll_year")}
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {salaryAdvances.length > 0 ? (
                                        salaryAdvances.map((salaryAdvance) => (
                                            <TableRow key={salaryAdvance.id}>
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selectedSalaryAdvance.includes(
                                                            salaryAdvance.id
                                                        )}
                                                        onCheckedChange={() =>
                                                            toggleSelectSalaryAdvance(
                                                                salaryAdvance.id
                                                            )
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    {salaryAdvance.id}
                                                </TableCell>
                                                <TableCell>
                                                    {
                                                        salaryAdvance.employee
                                                            .name
                                                    }
                                                </TableCell>
                                                <TableCell>
                                                    {formatCurrency(salaryAdvance.amount, salaryAdvance.currency)}
                                                </TableCell>
                                                <TableCell>
                                                    {salaryAdvance.date}
                                                </TableCell>
                                                <TableCell>
                                                    {
                                                        salaryAdvance.payroll_month
                                                    }
                                                </TableCell>
                                                <TableCell>
                                                    {salaryAdvance.payroll_year}
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
                                                                    "salary_advances.show",
                                                                    salaryAdvance.id
                                                                ),
                                                            },
                                                            {
                                                                label: "Edit",
                                                                icon: (
                                                                    <Edit className="h-4 w-4" />
                                                                ),
                                                                onClick: () =>
                                                                    handleEditSalaryAdvance(salaryAdvance),
                                                            },
                                                            {
                                                                label: "Delete",
                                                                icon: (
                                                                    <Trash className="h-4 w-4" />
                                                                ),
                                                                onClick: () =>
                                                                    handleDeleteConfirm(
                                                                        salaryAdvance.id
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
                                                No salary advances found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {salaryAdvances.length > 0 && meta.total > 0 && (
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

            <DeleteSalaryAdvancesModal
                show={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                onConfirm={handleDelete}
                processing={processing}
            />

            <DeleteAllSalaryAdvancesModal
                show={showDeleteAllModal}
                onClose={() => setShowDeleteAllModal(false)}
                onConfirm={handleDeleteAll}
                processing={processing}
                count={selectedSalaryAdvance.length}
            />
        </AuthenticatedLayout>
    );
}

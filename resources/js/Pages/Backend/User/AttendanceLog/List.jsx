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
import { Input } from "@/Components/ui/input";
import {
    Plus,
    Trash2,
    ChevronUp,
    ChevronDown,
    Clock,
    CheckCircle,
    XCircle,
    Edit,
    RefreshCcw,
} from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import DateTimePicker from "@/Components/DateTimePicker";
import { Label } from "@/Components/ui/label";
import { parseDateObject } from "@/lib/utils";

const DeleteAttendanceLogModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete this attendance log?
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
                    Delete Attendance Log
                </Button>
            </div>
        </form>
    </Modal>
);

const DeleteAllAttendanceLogsModal = ({
    show,
    onClose,
    onConfirm,
    processing,
    count,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete {count} selected attendance log
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

const fetchAttendanceLogs = () => {
    window.location.href = route("attendance_logs.fetch");
};

const SummaryCards = ({ summary = {} }) => {
    const cards = [
        {
            title: "Total Logs",
            value: summary.total_logs || 0,
            description: "Total number of attendance logs",
            icon: Clock,
            iconColor: "text-blue-500",
        },
        {
            title: "Check-ins",
            value: summary.check_ins || 0,
            description: "Number of check-ins",
            icon: CheckCircle,
            iconColor: "text-green-500",
        },
        {
            title: "Check-outs",
            value: summary.check_outs || 0,
            description: "Number of check-outs",
            icon: XCircle,
            iconColor: "text-purple-500",
        },
    ];

    return (
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            {cards.map((card, index) => (
                <div key={index} className="bg-gray-100 rounded-lg shadow-sm p-4">
                    <div className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <h3 className="text-lg font-medium">{card.title}</h3>
                        <card.icon className={`h-8 w-8 ${card.iconColor}`} />
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
    attendanceLogs = [],
    meta = {},
    filters = {},
    employees = [],
    summary = {},
}) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [selectedLogs, setSelectedLogs] = useState([]);
    const [isAllSelected, setIsAllSelected] = useState(false);
    const [search, setSearch] = useState(filters.search || "");
    const [perPage, setPerPage] = useState(meta.per_page || 50);
    const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
    const [bulkAction, setBulkAction] = useState("");
    const [sorting, setSorting] = useState(
        filters.sorting || { column: "id", direction: "desc" }
    );
    const [selectedEmployee, setSelectedEmployee] = useState(
        filters.employee_id || ""
    );
    const [dateRange, setDateRange] = useState(filters.date_range || null);

    // Delete confirmation modal states
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [logToDelete, setLogToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);

    // Form state for Create/Edit dialogs
    const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
    const [isEditDialogOpen, setIsEditDialogOpen] = useState(false);
    const [editingLog, setEditingLog] = useState(null);
    const [form, setForm] = useState({
        employee_id: "",
        state: "0",
        timestamp: "",
    });

    const AttendanceStateBadge = ({ status }) => {
        const statusMap = {
            0: {
                label: "Check-In",
                className: "text-blue-600 bg-blue-200 px-3 py-1 rounded text-xs",
            },
            1: {
                label: "Check-Out",
                className:
                    "text-yellow-600 bg-yellow-200 px-3 py-1 rounded text-xs",
            },
            2: {
                label: "Break-Out",
                className: "text-green-600 bg-green-200 px-3 py-1 rounded text-xs",
            },
            3: {
                label: "Break-In",
                className: "text-green-600 bg-green-200 px-3 py-1 rounded text-xs",
            },
            4: {
                label: "Overtime-In",
                className: "text-green-600 bg-green-200 px-3 py-1 rounded text-xs",
            },
            5: {
                label: "Overtime-Out",
                className: "text-green-600 bg-green-200 px-3 py-1 rounded text-xs",
            },
            15: {
                label: "Undefined",
                className: "text-gray-600 bg-gray-200 px-3 py-1 rounded text-xs",
            },
        };
    
        return (
            <span className={statusMap[status].className}>
                {statusMap[status].label}
            </span>
        );
    };

    // Form errors
    const [errors, setErrors] = useState({});

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
            setSelectedLogs([]);
        } else {
            setSelectedLogs(attendanceLogs.map((log) => log.id));
        }
        setIsAllSelected(!isAllSelected);
    };

    const toggleSelectLog = (id) => {
        if (selectedLogs.includes(id)) {
            setSelectedLogs(
                selectedLogs.filter((logId) => logId !== id)
            );
            setIsAllSelected(false);
        } else {
            setSelectedLogs([...selectedLogs, id]);
            if (selectedLogs.length + 1 === attendanceLogs.length) {
                setIsAllSelected(true);
            }
        }
    };

    const handleSort = (column) => {
        let direction = "asc";
        if (sorting.column === column && sorting.direction === "asc") {
            direction = "desc";
        }
        setSorting({ column, direction });
        router.get(
            route("attendance_logs.index"),
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

    const handleSearch = (e) => {
        e.preventDefault();
        const value = e.target.value;
        setSearch(value);
        router.get(
            route("attendance_logs.index"),
            {
                search: value,
                page: 1,
                per_page: perPage,
                sorting,
                employee_id: selectedEmployee,
                date_range: dateRange,
            },
            { preserveState: true }
        );
    };

    const handlePerPageChange = (value) => {
        setPerPage(value);
        router.get(
            route("attendance_logs.index"),
            {
                search,
                page: 1,
                per_page: value,
                sorting,
                employee_id: selectedEmployee,
                date_range: dateRange,
            },
            { preserveState: true }
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("attendance_logs.index"),
            {
                search,
                page,
                per_page: perPage,
                sorting,
                employee_id: selectedEmployee,
                date_range: dateRange,
            },
            { preserveState: true }
        );
    };

    const handleEmployeeChange = (value) => {
        setSelectedEmployee(value);
        router.get(
            route("attendance_logs.index"),
            {
                search,
                page: 1,
                per_page: perPage,
                sorting,
                employee_id: value,
                date_range: dateRange,
            },
            { preserveState: true }
        );
    };

    const handleDateRangeChange = (dates) => {
        setDateRange(dates);
        router.get(
            route("attendance_logs.index"),
            {
                search,
                page: 1,
                per_page: perPage,
                sorting,
                employee_id: selectedEmployee,
                date_range: dates,
            },
            { preserveState: true }
        );
    };

    const handleBulkAction = () => {
        if (bulkAction === "") return;

        if (selectedLogs.length === 0) {
            toast({
                variant: "destructive",
                title: "Error",
                description: "Please select at least one attendance log",
            });
            return;
        }

        if (bulkAction === "delete") {
            setShowDeleteAllModal(true);
        }
    };

    const handleDeleteConfirm = (id) => {
        setLogToDelete(id);
        setShowDeleteModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.delete(route("attendance_logs.destroy", logToDelete), {
            onSuccess: () => {
                setShowDeleteModal(false);
                setLogToDelete(null);
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
            route("attendance_logs.bulk_destroy"),
            {
                ids: selectedLogs,
            },
            {
                onSuccess: () => {
                    setShowDeleteAllModal(false);
                    setSelectedLogs([]);
                    setIsAllSelected(false);
                    setProcessing(false);
                },
                onError: () => {
                    setProcessing(false);
                },
            }
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

    // Create form handlers
    const openCreateDialog = () => {
        setForm({
            employee_id: "",
            state: "0",
            timestamp: "",
        });
        setErrors({});
        setIsCreateDialogOpen(true);
    };

    const handleCreateSubmit = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(route("attendance_logs.store"), form, {
            preserveState: true,
            onSuccess: () => {
                setIsCreateDialogOpen(false);
                setForm({
                    employee_id: "",
                    state: "0",
                    timestamp: "",
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
    const openEditDialog = (log) => {
        setEditingLog(log);
        setForm({
            employee_id: log.employee_id,
            state: log.state,
            timestamp: parseDateObject(log.timestamp),
        });
        setErrors({});
        setIsEditDialogOpen(true);
    };

    const handleEditSubmit = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.put(route("attendance_logs.update", editingLog.id), form, {
            preserveState: true,
            onSuccess: () => {
                setIsEditDialogOpen(false);
                setEditingLog(null);
                setProcessing(false);
            },
            onError: (errors) => {
                setErrors(errors);
                setProcessing(false);
            },
        });
    };

    // Form input change handler
    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setForm({
            ...form,
            [name]: value,
        });
    };

    const handleTimestampChange = (value) => {
        setForm({
            ...form,
            timestamp: value,
        });
    };

    const handleStateChange = (value) => {
        setForm({
            ...form,
            state: value,
        });
    };

    const handleFormEmployeeChange = (value) => {
        setForm({
            ...form,
            employee_id: value,
        });
    };

    return (
        <AuthenticatedLayout>
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Attendance Logs"
                        subpage="List"
                        url="attendance_logs.index"
                    />
                    <div className="p-4">
                        <SummaryCards summary={summary} />
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div className="flex flex-col md:flex-row gap-2">
                                <Button onClick={openCreateDialog}>
                                    <Plus className="w-4 h-4 mr-2" />
                                    Add Attendance Log
                                </Button>
                                <Button onClick={fetchAttendanceLogs} variant="outline">
                                    <RefreshCcw className="w-4 h-4 mr-2" />
                                    Fetch from the device
                                </Button>
                            </div>
                            <div className="flex flex-col md:flex-row gap-4 md:items-center">
                                <Input
                                    placeholder="search attendance logs..."
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
                                <SearchableCombobox
                                    options={employees.map((employee) => ({
                                        id: employee.id,
                                        name: employee.name,
                                    }))}
                                    value={selectedEmployee}
                                    onChange={handleEmployeeChange}
                                    placeholder="Select employee"
                                    className="w-[200px]"
                                />
                                <DateTimePicker
                                    value={dateRange}
                                    onChange={handleDateRangeChange}
                                    isRange={true}
                                    className="w-[200px]"
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
                                                handleSort("employee.name")
                                            }
                                        >
                                            Employee{" "}
                                            {renderSortIcon("staff.name")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() =>
                                                handleSort("state")
                                            }
                                        >
                                            State {renderSortIcon("state")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() => handleSort("timestamp")}
                                        >
                                            Timestamp {renderSortIcon("timestamp")}
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {attendanceLogs.length > 0 ? (
                                        attendanceLogs.map((log) => (
                                            <TableRow key={log.id}>
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selectedLogs.includes(
                                                            log.id
                                                        )}
                                                        onCheckedChange={() =>
                                                            toggleSelectLog(
                                                                log.id
                                                            )
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    {log.staff
                                                        ? log.staff.name
                                                        : "-"}
                                                </TableCell>
                                                <TableCell>
                                                    <AttendanceStateBadge status={log.state} />
                                                </TableCell>
                                                <TableCell>
                                                    {log.timestamp}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <TableActions
                                                        actions={[
                                                            {
                                                                label: "Edit",
                                                                icon: (
                                                                    <Edit className="h-4 w-4" />
                                                                ),
                                                                onClick: () =>
                                                                    openEditDialog(
                                                                        log
                                                                    ),
                                                            },
                                                            {
                                                                label: "Delete",
                                                                icon: (
                                                                    <Trash2 className="h-4 w-4" />
                                                                ),
                                                                onClick: () =>
                                                                    handleDeleteConfirm(
                                                                        log.id
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
                                                colSpan={5}
                                                className="h-24 text-center"
                                            >
                                                No attendance logs found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {attendanceLogs.length > 0 && meta.total > 0 && (
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

            <DeleteAttendanceLogModal
                show={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                onConfirm={handleDelete}
                processing={processing}
            />

            <DeleteAllAttendanceLogsModal
                show={showDeleteAllModal}
                onClose={() => setShowDeleteAllModal(false)}
                onConfirm={handleDeleteAll}
                processing={processing}
                count={selectedLogs.length}
            />

            {/* Create Modal */}
            <Modal
                show={isCreateDialogOpen}
                onClose={() => setIsCreateDialogOpen(false)}
                maxWidth="2xl"
            >
                <form onSubmit={handleCreateSubmit}>
                    <div className="ti-modal-header">
                        <h3 className="text-lg font-bold">Add Attendance Log</h3>
                    </div>
                    <div className="ti-modal-body">
                        <div className="grid gap-4 mt-4">
                            <div className="grid gap-2">
                                <Label htmlFor="employee_id">
                                    Employee{" "}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <SearchableCombobox
                                    options={employees.map((employee) => ({
                                        id: employee.id,
                                        name: employee.name,
                                    }))}
                                    value={form.employee_id}
                                    onChange={handleFormEmployeeChange}
                                    placeholder="Select employee"
                                />
                                {errors.employee_id && (
                                    <p className="text-sm text-red-500">
                                        {errors.employee_id}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="state">
                                    State <span className="text-red-500">*</span>
                                </Label>
                                <SearchableCombobox
                                    options={[
                                        { id: "0", name: "Check In" },
                                        { id: "1", name: "Check Out" },
                                        { id: "2", name: "Break-Out" },
                                        { id: "3", name: "Break-In" },
                                        { id: "4", name: "Overtime-In" },
                                        { id: "5", name: "Overtime-Out" },
                                        { id: "15", name: "Undefined" },
                                    ]}
                                    value={form.state}
                                    onChange={handleStateChange}
                                    placeholder="Select state"
                                />
                                {errors.state && (
                                    <p className="text-sm text-red-500">
                                        {errors.state}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="timestamp">
                                    Timestamp{" "}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <DateTimePicker
                                    value={form.timestamp}
                                    onChange={handleTimestampChange}
                                />
                                {errors.timestamp && (
                                    <p className="text-sm text-red-500">
                                        {errors.timestamp}
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                    <div className="flex justify-end gap-2 mt-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setIsCreateDialogOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            Save
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Edit Modal */}
            <Modal
                show={isEditDialogOpen}
                onClose={() => setIsEditDialogOpen(false)}
                maxWidth="2xl"
            >
                <form onSubmit={handleEditSubmit}>
                    <div className="ti-modal-header">
                        <h3 className="text-lg font-bold">Edit Attendance Log</h3>
                    </div>
                    <div className="ti-modal-body">
                        <div className="grid gap-4 mt-4">
                            <div className="grid gap-2">
                                <Label htmlFor="employee_id">
                                    Employee{" "}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <SearchableCombobox
                                    options={employees.map((employee) => ({
                                        id: employee.id,
                                        name: employee.name,
                                    }))}
                                    value={form.employee_id}
                                    onChange={handleFormEmployeeChange}
                                    placeholder="Select employee"
                                />
                                {errors.employee_id && (
                                    <p className="text-sm text-red-500">
                                        {errors.employee_id}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="state">
                                    State <span className="text-red-500">*</span>
                                </Label>
                                <SearchableCombobox
                                    options={[
                                        { id: "check-in", name: "Check In" },
                                        { id: "check-out", name: "Check Out" },
                                    ]}
                                    value={form.state}
                                    onChange={handleStateChange}
                                    placeholder="Select state"
                                />
                                {errors.state && (
                                    <p className="text-sm text-red-500">
                                        {errors.state}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="timestamp">
                                    Timestamp{" "}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <DateTimePicker
                                    value={form.timestamp}
                                    onChange={handleTimestampChange}
                                />
                                {errors.timestamp && (
                                    <p className="text-sm text-red-500">
                                        {errors.timestamp}
                                    </p>
                                )}
                            </div>
                        </div>
                    </div>
                    <div className="flex justify-end gap-2 mt-4">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={() => setIsEditDialogOpen(false)}
                        >
                            Cancel
                        </Button>
                        <Button type="submit" disabled={processing}>
                            Update
                        </Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}

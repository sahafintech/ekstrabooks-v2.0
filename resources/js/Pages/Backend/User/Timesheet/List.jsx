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
    Trash2,
    ChevronUp,
    ChevronDown,
    Clock,
    Calendar,
    CheckCircle,
    XCircle,
    Edit,
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

const DeleteTimesheetModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete this timesheet?
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
                    Delete Timesheet
                </Button>
            </div>
        </form>
    </Modal>
);

const ImportTimesheetsModal = ({ show, onClose, onSubmit, processing }) => (
    <Modal show={show} onClose={onClose} maxWidth="3xl">
        <form onSubmit={onSubmit}>
            <div className="ti-modal-header">
                <h3 className="text-lg font-bold">Import Timesheets</h3>
            </div>
            <div className="ti-modal-body grid grid-cols-12">
                <div className="col-span-12">
                    <div className="flex items-center justify-between">
                        <label className="block font-medium text-sm text-gray-700">
                            Timesheets File
                        </label>
                        <a
                            href="/uploads/media/default/sample_timesheets.xlsx"
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
                        name="timesheets_file"
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
                    Import Timesheets
                </Button>
            </div>
        </form>
    </Modal>
);

const DeleteAllTimesheetsModal = ({
    show,
    onClose,
    onConfirm,
    processing,
    count,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete {count} selected timesheet
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

const SummaryCards = ({ summary = {} }) => {
    const cards = [
        {
            title: "Total Timesheets",
            value: summary.total_timesheets || 0,
            description: "Total number of timesheets",
            icon: Clock,
            iconColor: "text-blue-500",
        },
        {
            title: "Total Hours",
            value: summary.total_hours || 0,
            description: "Total hours logged",
            icon: Calendar,
            iconColor: "text-green-500",
        },
        {
            title: "Approved",
            value: summary.approved_timesheets || 0,
            description: "Number of approved timesheets",
            icon: CheckCircle,
            iconColor: "text-purple-500",
        },
        {
            title: "Pending",
            value: summary.pending_timesheets || 0,
            description: "Number of pending timesheets",
            icon: XCircle,
            iconColor: "text-orange-500",
        },
    ];

    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
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
    timesheets = [],
    meta = {},
    filters = {},
    employees = [],
    projects = [],
    summary = {},
}) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [selectedTimesheets, setSelectedTimesheets] = useState([]);
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
    const [selectedProject, setSelectedProject] = useState(
        filters.project_id || ""
    );
    const [dateRange, setDateRange] = useState(filters.date_range || null);

    // Delete confirmation modal states
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showImportModal, setShowImportModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [timesheetToDelete, setTimesheetToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);

    // Form state for Create/Edit dialogs
    const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
    const [isEditDialogOpen, setIsEditDialogOpen] = useState(false);
    const [editingTimesheet, setEditingTimesheet] = useState(null);
    const [form, setForm] = useState({
        employee_id: "",
        project_id: "",
        date: "",
        check_in: "",
        check_out: "",
        working_day_type: "Normal",
        working_hours: "",
    });

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
            setSelectedTimesheets([]);
        } else {
            setSelectedTimesheets(timesheets.map((timesheet) => timesheet.id));
        }
        setIsAllSelected(!isAllSelected);
    };

    const toggleSelectTimesheet = (id) => {
        if (selectedTimesheets.includes(id)) {
            setSelectedTimesheets(
                selectedTimesheets.filter((timesheetId) => timesheetId !== id)
            );
            setIsAllSelected(false);
        } else {
            setSelectedTimesheets([...selectedTimesheets, id]);
            if (selectedTimesheets.length + 1 === timesheets.length) {
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
            route("timesheets.index"),
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
            route("timesheets.index"),
            {
                search: value,
                page: 1,
                per_page: perPage,
                sorting,
                employee_id: selectedEmployee,
                project_id: selectedProject,
                date_range: dateRange,
            },
            { preserveState: true }
        );
    };

    const handlePerPageChange = (value) => {
        setPerPage(value);
        router.get(
            route("timesheets.index"),
            {
                search,
                page: 1,
                per_page: value,
                sorting,
                employee_id: selectedEmployee,
                date_range: dateRange,
                project_id: selectedProject,
            },
            { preserveState: true }
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("timesheets.index"),
            {
                search,
                page,
                per_page: perPage,
                sorting,
                employee_id: selectedEmployee,
                date_range: dateRange,
                project_id: selectedProject,
            },
            { preserveState: true }
        );
    };

    const handleEmployeeChange = (value) => {
        setSelectedEmployee(value);
        router.get(
            route("timesheets.index"),
            {
                search,
                page: 1,
                per_page: perPage,
                sorting,
                employee_id: value,
                date_range: dateRange,
                project_id: selectedProject,
            },
            { preserveState: true }
        );
    };

    const handleDateRangeChange = (dates) => {
        setDateRange(dates);
        router.get(
            route("timesheets.index"),
            {
                search,
                page: 1,
                per_page: perPage,
                sorting,
                employee_id: selectedEmployee,
                project_id: selectedProject,
                date_range: dates,
            },
            { preserveState: true }
        );
    };

    const handleProjectChange = (value) => {
        setSelectedProject(value);
        router.get(
            route("timesheets.index"),
            {
                search,
                page: 1,
                per_page: perPage,
                sorting,
                employee_id: selectedEmployee,
                date_range: dateRange,
                project_id: value,
            },
            { preserveState: true }
        );
    };

    const handleBulkAction = () => {
        if (bulkAction === "") return;

        if (selectedTimesheets.length === 0) {
            toast({
                variant: "destructive",
                title: "Error",
                description: "Please select at least one timesheet",
            });
            return;
        }

        if (bulkAction === "delete") {
            setShowDeleteAllModal(true);
        }
    };

    const handleDeleteConfirm = (id) => {
        setTimesheetToDelete(id);
        setShowDeleteModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.delete(route("timesheets.destroy", timesheetToDelete), {
            onSuccess: () => {
                setShowDeleteModal(false);
                setTimesheetToDelete(null);
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
            route("timesheets.bulk_destroy"),
            {
                ids: selectedTimesheets,
            },
            {
                onSuccess: () => {
                    setShowDeleteAllModal(false);
                    setSelectedTimesheets([]);
                    setIsAllSelected(false);
                    setProcessing(false);
                },
                onError: () => {
                    setProcessing(false);
                },
            }
        );
    };

    const handleImport = (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        setProcessing(true);

        router.post(route("timesheets.import"), formData, {
            onSuccess: () => {
                setShowImportModal(false);
                setProcessing(false);
            },
            onError: () => {
                setProcessing(false);
            },
        });
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

    const exportTimesheets = () => {
        window.location.href = route("timesheets.export");
    };

    // Create form handlers
    const openCreateDialog = () => {
        setForm({
            employee_id: "",
            project_id: "",
            date: "",
            check_in: "",
            check_out: "",
            working_day_type: "Normal",
            working_hours: "",
        });
        setErrors({});
        setIsCreateDialogOpen(true);
    };

    const handleCreateSubmit = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(route("timesheets.store"), form, {
            preserveState: true,
            onSuccess: () => {
                setIsCreateDialogOpen(false);
                setForm({
                    employee_id: "",
                    project_id: "",
                    date: "",
                    check_in: "",
                    check_out: "",
                    working_day_type: "Normal",
                    working_hours: "",
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
    const openEditDialog = (timesheet) => {
        setEditingTimesheet(timesheet);
        setForm({
            employee_id: timesheet.employee_id,
            project_id: timesheet.project_id,
            date: parseDateObject(timesheet.date),
            check_in: timesheet.check_in,
            check_out: timesheet.check_out,
            working_day_type: timesheet.working_day_type,
            working_hours: timesheet.working_hours,
        });
        setErrors({});
        setIsEditDialogOpen(true);
    };

    const handleEditSubmit = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.put(route("timesheets.update", editingTimesheet.id), form, {
            preserveState: true,
            onSuccess: () => {
                setIsEditDialogOpen(false);
                setEditingTimesheet(null);
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

    const handleDateChange = (value) => {
        setForm({
            ...form,
            date: value,
        });
    };

    const handleWorkingDayTypeChange = (value) => {
        setForm({
            ...form,
            working_day_type: value,
        });
    };

    const handleFormEmployeeChange = (value) => {
        setForm({
            ...form,
            employee_id: value,
        });
    };

    const handleFormProjectChange = (value) => {
        setForm({
            ...form,
            project_id: value,
        });
    };

    return (
        <AuthenticatedLayout>
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Timesheets"
                        subpage="List"
                        url="timesheets.index"
                    />
                    <div className="p-4">
                        <SummaryCards summary={summary} />
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div className="flex flex-col md:flex-row gap-2">
                                <Button onClick={openCreateDialog}>
                                    <Plus className="w-4 h-4 mr-2" />
                                    Add Timesheet
                                </Button>
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
                                            onClick={exportTimesheets}
                                        >
                                            <FileDown className="mr-2 h-4 w-4" />{" "}
                                            Export
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>
                            <div className="flex flex-col md:flex-row gap-4 md:items-center">
                                <Input
                                    placeholder="search timesheets..."
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
                                <SearchableCombobox
                                    options={projects?.map((project) => ({
                                        id: project.id,
                                        name:
                                            project?.project_code +
                                            " - " +
                                            project?.project_name,
                                    }))}
                                    value={selectedProject}
                                    onChange={handleProjectChange}
                                    placeholder="Select project"
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
                                            onClick={() => handleSort("date")}
                                        >
                                            Date {renderSortIcon("date")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() =>
                                                handleSort("employee.name")
                                            }
                                        >
                                            Employee{" "}
                                            {renderSortIcon("employee.name")}
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
                                                handleSort("working_day_type")
                                            }
                                        >
                                            Working Day Type{" "}
                                            {renderSortIcon("working_day_type")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() =>
                                                handleSort("working_hours")
                                            }
                                        >
                                            Working Hours{" "}
                                            {renderSortIcon("working_hours")}
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {timesheets.length > 0 ? (
                                        timesheets.map((timesheet) => (
                                            <TableRow key={timesheet.id}>
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selectedTimesheets.includes(
                                                            timesheet.id
                                                        )}
                                                        onCheckedChange={() =>
                                                            toggleSelectTimesheet(
                                                                timesheet.id
                                                            )
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    {timesheet.date}
                                                </TableCell>
                                                <TableCell>
                                                    {timesheet.employee
                                                        ? timesheet.employee
                                                              .name
                                                        : "-"}
                                                </TableCell>
                                                <TableCell>
                                                    {timesheet.project
                                                        ? timesheet.project
                                                              .project_code +
                                                          " - " +
                                                          timesheet.project
                                                              .project_name
                                                        : ""}
                                                </TableCell>
                                                <TableCell>
                                                    {timesheet.working_day_type}
                                                </TableCell>
                                                <TableCell>
                                                    {timesheet.working_hours}
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
                                                                        timesheet
                                                                    ),
                                                            },
                                                            {
                                                                label: "Delete",
                                                                icon: (
                                                                    <Trash2 className="h-4 w-4" />
                                                                ),
                                                                onClick: () =>
                                                                    handleDeleteConfirm(
                                                                        timesheet.id
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
                                                No timesheets found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {timesheets.length > 0 && meta.total > 0 && (
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

            <DeleteTimesheetModal
                show={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                onConfirm={handleDelete}
                processing={processing}
            />

            <DeleteAllTimesheetsModal
                show={showDeleteAllModal}
                onClose={() => setShowDeleteAllModal(false)}
                onConfirm={handleDeleteAll}
                processing={processing}
                count={selectedTimesheets.length}
            />

            <ImportTimesheetsModal
                show={showImportModal}
                onClose={() => setShowImportModal(false)}
                onSubmit={handleImport}
                processing={processing}
            />

            {/* Create Modal */}
            <Modal
                show={isCreateDialogOpen}
                onClose={() => setIsCreateDialogOpen(false)}
                maxWidth="2xl"
            >
                <form onSubmit={handleCreateSubmit}>
                    <div className="ti-modal-header">
                        <h3 className="text-lg font-bold">Add Timesheet</h3>
                    </div>
                    <div className="ti-modal-body">
                        <div className="grid gap-4">
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
                                <Label htmlFor="project_id">
                                    Project{" "}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <SearchableCombobox
                                    options={projects.map((project) => ({
                                        id: project.id,
                                        name:
                                            project.project_code +
                                            " - " +
                                            project.project_name,
                                    }))}
                                    value={form.project_id}
                                    onChange={handleFormProjectChange}
                                    placeholder="Select project"
                                />
                                {errors.project_id && (
                                    <p className="text-sm text-red-500">
                                        {errors.project_id}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="date">
                                    Date <span className="text-red-500">*</span>
                                </Label>
                                <DateTimePicker
                                    value={form.date}
                                    onChange={handleDateChange}
                                />
                                {errors.date && (
                                    <p className="text-sm text-red-500">
                                        {errors.date}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="check_in">Check In</Label>
                                <Input
                                    type="time"
                                    id="check_in"
                                    name="check_in"
                                    value={form.check_in}
                                    onChange={handleInputChange}
                                />
                                {errors.check_in && (
                                    <p className="text-sm text-red-500">
                                        {errors.check_in}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="check_out">Check Out</Label>
                                <Input
                                    type="time"
                                    id="check_out"
                                    name="check_out"
                                    value={form.check_out}
                                    onChange={handleInputChange}
                                />
                                {errors.check_out && (
                                    <p className="text-sm text-red-500">
                                        {errors.check_out}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="working_day_type">
                                    Working Day Type
                                </Label>
                                <SearchableCombobox
                                    options={[
                                        { id: "Normal", name: "Normal" },
                                        { id: "Overtime", name: "Overtime" },
                                        { id: "Holiday", name: "Holiday" },
                                        { id: "Weekend", name: "Weekend" },
                                        { id: "Leave", name: "Leave" },
                                    ]}
                                    value={form.working_day_type}
                                    onChange={handleWorkingDayTypeChange}
                                    placeholder="Select working day type"
                                />
                                {errors.working_day_type && (
                                    <p className="text-sm text-red-500">
                                        {errors.working_day_type}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="working_hours">
                                    Working Hours
                                </Label>
                                <Input
                                    type="number"
                                    id="working_hours"
                                    name="working_hours"
                                    value={form.working_hours}
                                    onChange={handleInputChange}
                                    placeholder="Enter working hours"
                                />
                                {errors.working_hours && (
                                    <p className="text-sm text-red-500">
                                        {errors.working_hours}
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
                        <h3 className="text-lg font-bold">Edit Timesheet</h3>
                    </div>
                    <div className="ti-modal-body">
                        <div className="grid gap-4">
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
                                <Label htmlFor="project_id">
                                    Project{" "}
                                    <span className="text-red-500">*</span>
                                </Label>
                                <SearchableCombobox
                                    options={projects.map((project) => ({
                                        id: project.id,
                                        name:
                                            project.project_code +
                                            " - " +
                                            project.project_name,
                                    }))}
                                    value={form.project_id}
                                    onChange={handleFormProjectChange}
                                    placeholder="Select project"
                                />
                                {errors.project_id && (
                                    <p className="text-sm text-red-500">
                                        {errors.project_id}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="date">
                                    Date <span className="text-red-500">*</span>
                                </Label>
                                <DateTimePicker
                                    value={form.date}
                                    onChange={handleDateChange}
                                />
                                {errors.date && (
                                    <p className="text-sm text-red-500">
                                        {errors.date}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="check_in">Check In</Label>
                                <Input
                                    type="time"
                                    id="check_in"
                                    name="check_in"
                                    value={form.check_in}
                                    onChange={handleInputChange}
                                />
                                {errors.check_in && (
                                    <p className="text-sm text-red-500">
                                        {errors.check_in}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="check_out">Check Out</Label>
                                <Input
                                    type="time"
                                    id="check_out"
                                    name="check_out"
                                    value={form.check_out}
                                    onChange={handleInputChange}
                                />
                                {errors.check_out && (
                                    <p className="text-sm text-red-500">
                                        {errors.check_out}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="working_day_type">
                                    Working Day Type
                                </Label>
                                <SearchableCombobox
                                    options={[
                                        { id: "Normal", name: "Normal" },
                                        { id: "Overtime", name: "Overtime" },
                                        { id: "Holiday", name: "Holiday" },
                                        { id: "Weekend", name: "Weekend" },
                                        { id: "Leave", name: "Leave" },
                                    ]}
                                    value={form.working_day_type}
                                    onChange={handleWorkingDayTypeChange}
                                    placeholder="Select working day type"
                                />
                                {errors.working_day_type && (
                                    <p className="text-sm text-red-500">
                                        {errors.working_day_type}
                                    </p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="working_hours">
                                    Working Hours
                                </Label>
                                <Input
                                    type="number"
                                    id="working_hours"
                                    name="working_hours"
                                    value={form.working_hours}
                                    onChange={handleInputChange}
                                    placeholder="Enter working hours"
                                />
                                {errors.working_hours && (
                                    <p className="text-sm text-red-500">
                                        {errors.working_hours}
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

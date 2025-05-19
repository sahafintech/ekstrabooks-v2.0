import React, { useState, useEffect, useMemo } from "react";
import { Head, Link, router, usePage } from "@inertiajs/react";
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
import { Label } from "@/Components/ui/label";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/Components/ui/dialog";
import {
    Edit,
    EyeIcon,
    FileDown,
    FileUp,
    MoreVertical,
    Plus,
    Trash,
    ChevronUp,
    ChevronDown,
} from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import Modal from "@/Components/Modal";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { Textarea } from "@/Components/ui/textarea";

// Delete Confirmation Modal Component
const DeleteTaskModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete this task?
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
const DeleteAllTasksModal = ({
    show,
    onClose,
    onConfirm,
    processing,
    count,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete {count} selected task
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

// Import Tasks Modal Component
const ImportTasksModal = ({ show, onClose, onSubmit, processing, errors, project }) => (
    <Modal show={show} onClose={onClose} maxWidth="3xl">
        <form onSubmit={onSubmit}>
            <div>
                <h3 className="text-lg font-bold">Import Tasks</h3>
            </div>
            <div className="grid grid-cols-12">
                <div className="col-span-12">
                    <div className="flex items-center justify-between">
                        <label className="block font-medium text-sm text-gray-700">
                            Tasks File
                        </label>
                        <a
                            href="/uploads/media/default/sample_project_tasks.xlsx"
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
                        name="project_tasks_file"
                        required
                    />
                    <input
                        type="hidden"
                        name="project_id"
                        value={project.id}
                    />
                    {errors.project_tasks_file && (
                        <p className="text-sm text-red-500">
                            {errors.project_tasks_file}
                        </p>
                    )}
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
                    Import
                </Button>
            </div>
        </form>
    </Modal>
);

// Create Task Dialog Component
const CreateTaskDialog = ({
    show,
    onClose,
    onSubmit,
    processing,
    form,
    setForm,
    errors,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onSubmit}>
            <div>
                <h3 className="text-lg font-bold">Add Task</h3>
            </div>
            <div className="grid grid-cols-12">
                <div className="col-span-12">
                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="task_code">Task Code</Label>
                            <Input
                                id="task_code"
                                name="task_code"
                                value={form.task_code}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        task_code: e.target.value,
                                    })
                                }
                                placeholder="Enter task code"
                            />
                            {errors.task_code && (
                                <p className="text-sm text-red-500">
                                    {errors.task_code}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="description">Description</Label>
                            <Textarea
                                id="description"
                                name="description"
                                value={form.description}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        description: e.target.value,
                                    })
                                }
                                placeholder="Enter task description"
                            />
                            {errors.description && (
                                <p className="text-sm text-red-500">
                                    {errors.description}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="status">Status</Label>
                            <SearchableCombobox
                                id="status"
                                options={[
                                    { id: "Pending", name: "Pending" },
                                    { id: "Completed", name: "Completed" },
                                    { id: "Cancelled", name: "Cancelled" },
                                    { id: "On Hold", name: "On Hold" },
                                    { id: "In Progress", name: "In Progress" },
                                ]}
                                name="status"
                                value={form.status}
                                onChange={(value) =>
                                    setForm({ ...form, status: value })
                                }
                            />
                            {errors.status && (
                                <p className="text-sm text-red-500">
                                    {errors.status}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="completed_percent">
                                Completed Percent
                            </Label>
                            <Input
                                id="completed_percent"
                                type="number"
                                name="completed_percent"
                                value={form.completed_percent}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        completed_percent: e.target.value,
                                    })
                                }
                            />
                            {errors.completed_percent && (
                                <p className="text-sm text-red-500">
                                    {errors.completed_percent}
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

// Edit Task Dialog Component
const EditTaskDialog = ({
    show,
    onClose,
    onSubmit,
    processing,
    form,
    setForm,
    errors,
}) => (
    <Dialog open={show} onOpenChange={onClose}>
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Edit Task</DialogTitle>
            </DialogHeader>
            <form onSubmit={onSubmit}>
                <div className="col-span-12">
                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="task_code">Task Code</Label>
                            <Input
                                id="task_code"
                                name="task_code"
                                value={form.task_code}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        task_code: e.target.value,
                                    })
                                }
                                placeholder="Enter task code"
                            />
                            {errors.task_code && (
                                <p className="text-sm text-red-500">
                                    {errors.task_code}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="description">Description</Label>
                            <Textarea
                                id="description"
                                name="description"
                                value={form.description}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        description: e.target.value,
                                    })
                                }
                                placeholder="Enter task description"
                            />
                            {errors.description && (
                                <p className="text-sm text-red-500">
                                    {errors.description}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="status">Status</Label>
                            <SearchableCombobox
                                id="status"
                                options={[
                                    { id: "Pending", name: "Pending" },
                                    { id: "Completed", name: "Completed" },
                                    { id: "Cancelled", name: "Cancelled" },
                                    { id: "On Hold", name: "On Hold" },
                                    { id: "In Progress", name: "In Progress" },
                                ]}
                                name="status"
                                value={form.status}
                                onChange={(value) =>
                                    setForm({ ...form, status: value })
                                }
                            />
                            {errors.status && (
                                <p className="text-sm text-red-500">
                                    {errors.status}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="completed_percent">
                                Completed Percent
                            </Label>
                            <Input
                                id="completed_percent"
                                type="number"
                                name="completed_percent"
                                value={form.completed_percent}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        completed_percent: e.target.value,
                                    })
                                }
                            />
                            {errors.completed_percent && (
                                <p className="text-sm text-red-500">
                                    {errors.completed_percent}
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
        </DialogContent>
    </Dialog>
);

export default function TaskList({
    tasks = [],
    meta = {},
    filters = {},
    project,
}) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [selectedTasks, setSelectedTasks] = useState([]);
    const [isAllSelected, setIsAllSelected] = useState(false);
    const [search, setSearch] = useState(filters.search || "");
    const [perPage, setPerPage] = useState(meta.per_page || 50);
    const [currentPage, setCurrentPage] = useState(1);
    const [bulkAction, setBulkAction] = useState("");
    const [sorting, setSorting] = useState(
        filters.sorting || { column: "id", direction: "desc" }
    );

    // Form state for Create/Edit dialogs
    const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
    const [isEditDialogOpen, setIsEditDialogOpen] = useState(false);
    const [editingTask, setEditingTask] = useState(null);
    const [form, setForm] = useState({
        task_code: "",
        description: "",
        status: "Pending",
        completed_percent: 0,
        project_id: project.id,
    });

    // Form errors
    const [errors, setErrors] = useState({});

    // Delete confirmation modal states
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [showImportModal, setShowImportModal] = useState(false);
    const [taskToDelete, setTaskToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);

    // Client-side filtering, sorting and pagination
    const filteredAndSortedTasks = useMemo(() => {
        let result = [...tasks];

        // Apply search filter
        if (search) {
            const searchLower = search.toLowerCase();
            result = result.filter(
                (task) =>
                    task.id.toString().includes(searchLower) ||
                    task.task_code.toLowerCase().includes(searchLower) ||
                    task.status.toLowerCase().includes(searchLower) ||
                    task.completed_percent.toString().includes(searchLower) ||
                    (task.description &&
                        task.description.toLowerCase().includes(searchLower))
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
    }, [tasks, search, sorting]);

    // Calculate pagination
    const paginatedTasks = useMemo(() => {
        const startIndex = (currentPage - 1) * perPage;
        return filteredAndSortedTasks.slice(startIndex, startIndex + perPage);
    }, [filteredAndSortedTasks, currentPage, perPage]);

    // Calculate pagination metadata
    const paginationMeta = useMemo(
        () => ({
            total: filteredAndSortedTasks.length,
            per_page: perPage,
            current_page: currentPage,
            last_page: Math.ceil(filteredAndSortedTasks.length / perPage),
        }),
        [filteredAndSortedTasks.length, perPage, currentPage]
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
            setSelectedTasks([]);
        } else {
            setSelectedTasks(paginatedTasks.map((task) => task.id));
        }
        setIsAllSelected(!isAllSelected);
    };

    const toggleSelectTask = (id) => {
        if (selectedTasks.includes(id)) {
            setSelectedTasks(selectedTasks.filter((taskId) => taskId !== id));
            setIsAllSelected(false);
        } else {
            setSelectedTasks([...selectedTasks, id]);
            if (selectedTasks.length + 1 === paginatedTasks.length) {
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

        if (selectedTasks.length === 0) {
            toast({
                variant: "destructive",
                title: "Error",
                description: "Please select at least one task",
            });
            return;
        }

        if (bulkAction === "delete") {
            setShowDeleteAllModal(true);
        }
    };

    const handleDeleteConfirm = (id) => {
        setTaskToDelete(id);
        setShowDeleteModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.delete(route("project_tasks.destroy", taskToDelete), {
            onSuccess: () => {
                setShowDeleteModal(false);
                setTaskToDelete(null);
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
            route("project_tasks.bulk_destroy"),
            {
                ids: selectedTasks,
            },
            {
                onSuccess: () => {
                    setShowDeleteAllModal(false);
                    setSelectedTasks([]);
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

        router.post(route("project_tasks.import"), formData, {
            onSuccess: () => {
                setShowImportModal(false);
                setProcessing(false);
            },
            onError: () => {
                setProcessing(false);
            },
        });
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

    const exportTasks = () => {
        window.location.href = route("project_tasks.export");
    };

    // Create form handlers
    const openCreateDialog = () => {
        setForm({
            task_code: "",
            description: "",
            status: "Pending",
            project_id: project.id,
            completed_percent: 0,
        });
        setErrors({});
        setIsCreateDialogOpen(true);
    };

    const handleCreateSubmit = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(route("project_tasks.store"), form, {
            preserveState: true,
            onSuccess: () => {
                setIsCreateDialogOpen(false);
                setForm({
                    task_code: "",
                    description: "",
                    status: "Pending",
                    project_id: project.id,
                    completed_percent: 0,
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
    const openEditDialog = (task) => {
        setEditingTask(task);
        setForm({
            task_code: task.task_code,
            description: task.description,
            status: task.status,
            completed_percent: task.completed_percent,
            project_id: project.id,
        });
        setErrors({});
        setIsEditDialogOpen(true);
    };

    const handleEditSubmit = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.put(route("project_tasks.update", editingTask.id), form, {
            preserveState: true,
            onSuccess: () => {
                setIsEditDialogOpen(false);
                setEditingTask(null);
                setProcessing(false);
            },
            onError: (errors) => {
                setErrors(errors);
                setProcessing(false);
            },
        });
    };

    const TaskStatusBadge = ({ status }) => {
        const statusMap = {
            Pending: {
                label: "Pending",
                className:
                    "text-gray-600 bg-gray-200 px-3 py-1 rounded text-xs",
            },
            "In Progress": {
                label: "In Progress",
                className:
                    "text-green-400 bg-green-200 px-3 py-1 rounded text-xs",
            },
            Completed: {
                label: "Completed",
                className:
                    "text-green-400 bg-green-200 px-3 py-1 rounded text-xs",
            },
            "On Hold": {
                label: "On Hold",
                className:
                    "text-yellow-400 bg-yellow-200 px-3 py-1 rounded text-xs",
            },
            Cancelled: {
                label: "Cancelled",
                className: "text-red-400 bg-red-200 px-3 py-1 rounded text-xs",
            },
        };

        return (
            <span className={statusMap[status].className}>
                {statusMap[status].label}
            </span>
        );
    };

    return (
        <div>
            <Head title="Tasks" />
            <Toaster />
            <div className="main-content">
                <div className="p-4">
                    <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                        <div className="flex flex-col md:flex-row gap-2">
                            <Button onClick={openCreateDialog}>
                                <Plus className="w-4 h-4 mr-2" />
                                Add Task
                            </Button>
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="secondary">
                                        <MoreVertical className="h-4 w-4" />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent align="end">
                                    <DropdownMenuItem
                                        onClick={() => setShowImportModal(true)}
                                    >
                                        <FileUp className="mr-2 h-4 w-4" />{" "}
                                        Import
                                    </DropdownMenuItem>
                                    <DropdownMenuItem onClick={exportTasks}>
                                        <FileDown className="mr-2 h-4 w-4" />{" "}
                                        Export
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                        <div className="flex flex-col md:flex-row gap-4 md:items-center">
                            <Input
                                placeholder="search tasks..."
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
                                        onClick={() => handleSort("id")}
                                    >
                                        ID {renderSortIcon("id")}
                                    </TableHead>
                                    <TableHead
                                        className="cursor-pointer"
                                        onClick={() => handleSort("task_code")}
                                    >
                                        Task Code {renderSortIcon("task_code")}
                                    </TableHead>
                                    <TableHead
                                        className="cursor-pointer"
                                        onClick={() =>
                                            handleSort("description")
                                        }
                                    >
                                        Description{" "}
                                        {renderSortIcon("description")}
                                    </TableHead>
                                    <TableHead
                                        className="cursor-pointer"
                                        onClick={() => handleSort("status")}
                                    >
                                        Status {renderSortIcon("status")}
                                    </TableHead>
                                    <TableHead
                                        className="cursor-pointer"
                                        onClick={() =>
                                            handleSort("completed_percent")
                                        }
                                    >
                                        Completed Percent{" "}
                                        {renderSortIcon("completed_percent")}
                                    </TableHead>
                                    <TableHead className="text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {paginatedTasks.length > 0 ? (
                                    paginatedTasks.map((task) => (
                                        <TableRow key={task.id}>
                                            <TableCell>
                                                <Checkbox
                                                    checked={selectedTasks.includes(
                                                        task.id
                                                    )}
                                                    onCheckedChange={() =>
                                                        toggleSelectTask(
                                                            task.id
                                                        )
                                                    }
                                                />
                                            </TableCell>
                                            <TableCell>{task.id}</TableCell>
                                            <TableCell>
                                                {task.task_code}
                                            </TableCell>
                                            <TableCell>
                                                {task.description || "-"}
                                            </TableCell>
                                            <TableCell>
                                                <TaskStatusBadge
                                                    status={task.status}
                                                />
                                            </TableCell>
                                            <TableCell>
                                                <div className="flex items-center gap-2">
                                                    <div className="w-full bg-gray-200 rounded-full h-2.5">
                                                        <div
                                                            className="bg-primary h-2.5 rounded-full"
                                                            style={{
                                                                width: `${task.completed_percent}%`,
                                                            }}
                                                        ></div>
                                                    </div>
                                                    <span className="min-w-[3rem] text-right">
                                                        {task.completed_percent}%
                                                    </span>
                                                </div>
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
                                                                    task
                                                                ),
                                                        },
                                                        {
                                                            label: "Delete",
                                                            icon: (
                                                                <Trash className="h-4 w-4" />
                                                            ),
                                                            onClick: () =>
                                                                handleDeleteConfirm(
                                                                    task.id
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
                                            No tasks found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {paginatedTasks.length > 0 && paginationMeta.total > 0 && (
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
                                        currentPage === paginationMeta.last_page
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
                                        currentPage === paginationMeta.last_page
                                    }
                                >
                                    Last
                                </Button>
                            </div>
                        </div>
                    )}
                </div>
            </div>

            <DeleteTaskModal
                show={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                onConfirm={handleDelete}
                processing={processing}
            />

            <DeleteAllTasksModal
                show={showDeleteAllModal}
                onClose={() => setShowDeleteAllModal(false)}
                onConfirm={handleDeleteAll}
                processing={processing}
                count={selectedTasks.length}
            />

            <ImportTasksModal
                show={showImportModal}
                onClose={() => setShowImportModal(false)}
                onSubmit={handleImport}
                processing={processing}
                errors={errors}
                project={project}
            />

            {/* Create Dialog */}
            <CreateTaskDialog
                show={isCreateDialogOpen}
                onClose={() => setIsCreateDialogOpen(false)}
                onSubmit={handleCreateSubmit}
                processing={processing}
                form={form}
                setForm={setForm}
                errors={errors}
            />

            {/* Edit Dialog */}
            <EditTaskDialog
                show={isEditDialogOpen}
                onClose={() => setIsEditDialogOpen(false)}
                onSubmit={handleEditSubmit}
                processing={processing}
                form={form}
                setForm={setForm}
                errors={errors}
            />
        </div>
    );
}

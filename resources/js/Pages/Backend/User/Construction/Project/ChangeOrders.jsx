import React, { useState, useEffect, useMemo } from "react";
import { router, usePage } from "@inertiajs/react";
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
import { Edit, Plus, Trash, ChevronUp, ChevronDown } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import Modal from "@/Components/Modal";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { Textarea } from "@/Components/ui/textarea";
import DateTimePicker from "@/Components/DateTimePicker";
import { formatCurrency } from "@/lib/utils";

// Delete Confirmation Modal Component
const DeleteChangeOrderModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete this change order?
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
const DeleteAllChangeOrdersModal = ({
    show,
    onClose,
    onConfirm,
    processing,
    count,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete {count} selected change order
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

// Create Change Order Dialog Component
const CreateChangeOrderDialog = ({
    show,
    onClose,
    onSubmit,
    processing,
    form,
    setForm,
    errors,
    project,
    cost_codes,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onSubmit}>
            <div>
                <h3 className="text-lg font-bold">Add Change Order</h3>
            </div>
            <div className="grid grid-cols-12">
                <div className="col-span-12">
                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="project_task_id">Task</Label>
                            <SearchableCombobox
                                id="project_task_id"
                                options={project.tasks.map((task) => ({
                                    id: task.id,
                                    name:
                                        task.task_code +
                                        " - " +
                                        task.description,
                                }))}
                                name="project_task_id"
                                value={form.project_task_id}
                                placeholder="Select Task"
                                onChange={(value) =>
                                    setForm({ ...form, project_task_id: value })
                                }
                            />
                            {errors.project_task_id && (
                                <p className="text-sm text-red-500">
                                    {errors.project_task_id}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="cost_code_id">Cost Code</Label>
                            <SearchableCombobox
                                id="cost_code_id"
                                options={cost_codes.map((code) => ({
                                    id: code.id,
                                    name: code.code + " - " + code.description,
                                }))}
                                name="cost_code_id"
                                value={form.cost_code_id}
                                placeholder="Select Cost Code"
                                onChange={(value) =>
                                    setForm({ ...form, cost_code_id: value })
                                }
                            />
                            {errors.cost_code_id && (
                                <p className="text-sm text-red-500">
                                    {errors.cost_code_id}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="change_order_date">Date</Label>
                            <DateTimePicker
                                value={form.change_order_date}
                                onChange={(date) =>
                                    setForm({
                                        ...form,
                                        change_order_date: date,
                                    })
                                }
                                required
                            />
                            {errors.change_order_date && (
                                <p className="text-sm text-red-500">
                                    {errors.change_order_date}
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
                                placeholder="Enter change order description"
                            />
                            {errors.description && (
                                <p className="text-sm text-red-500">
                                    {errors.description}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="quantity">Quantity</Label>
                            <Input
                                id="quantity"
                                type="number"
                                step="0.01"
                                name="quantity"
                                value={form.quantity}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        quantity: e.target.value,
                                    })
                                }
                            />
                            {errors.quantity && (
                                <p className="text-sm text-red-500">
                                    {errors.quantity}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="unit_cost">Unit Cost</Label>
                            <Input
                                id="unit_cost"
                                type="number"
                                step="0.01"
                                name="unit_cost"
                                value={form.unit_cost}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        unit_cost: e.target.value,
                                    })
                                }
                            />
                            {errors.unit_cost && (
                                <p className="text-sm text-red-500">
                                    {errors.unit_cost}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="total_amount">Total Amount</Label>
                            <Input
                                id="total_amount"
                                type="number"
                                step="0.01"
                                name="total_amount"
                                value={form.total_amount}
                                readOnly
                            />
                            {errors.total_amount && (
                                <p className="text-sm text-red-500">
                                    {errors.total_amount}
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

// Edit Change Order Dialog Component
const EditChangeOrderDialog = ({
    show,
    onClose,
    onSubmit,
    processing,
    form,
    setForm,
    errors,
    project,
    cost_codes,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onSubmit}>
            <div>
                <h3 className="text-lg font-bold">Edit Change Order</h3>
            </div>
            <div className="grid grid-cols-12">
                <div className="col-span-12">
                    <div className="grid gap-4 py-4">
                        <div className="grid gap-2">
                            <Label htmlFor="project_task_id">Task</Label>
                            <SearchableCombobox
                                id="project_task_id"
                                options={project.tasks.map((task) => ({
                                    id: task.id,
                                    name:
                                        task.task_code +
                                        " - " +
                                        task.description,
                                }))}
                                name="project_task_id"
                                value={form.project_task_id}
                                placeholder="Select Task"
                                onChange={(value) =>
                                    setForm({ ...form, project_task_id: value })
                                }
                            />
                            {errors.project_task_id && (
                                <p className="text-sm text-red-500">
                                    {errors.project_task_id}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="cost_code_id">Cost Code</Label>
                            <SearchableCombobox
                                id="cost_code_id"
                                options={cost_codes.map((code) => ({
                                    id: code.id,
                                    name: code.code + " - " + code.description,
                                }))}
                                name="cost_code_id"
                                value={form.cost_code_id}
                                placeholder="Select Cost Code"
                                onChange={(value) =>
                                    setForm({ ...form, cost_code_id: value })
                                }
                            />
                            {errors.cost_code_id && (
                                <p className="text-sm text-red-500">
                                    {errors.cost_code_id}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="change_order_date">Date</Label>
                            <DateTimePicker
                                value={form.change_order_date}
                                onChange={(date) =>
                                    setForm({
                                        ...form,
                                        change_order_date: date,
                                    })
                                }
                                required
                            />
                            {errors.change_order_date && (
                                <p className="text-sm text-red-500">
                                    {errors.change_order_date}
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
                                placeholder="Enter change order description"
                            />
                            {errors.description && (
                                <p className="text-sm text-red-500">
                                    {errors.description}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="quantity">Quantity</Label>
                            <Input
                                id="quantity"
                                type="number"
                                step="0.01"
                                name="quantity"
                                value={form.quantity}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        quantity: e.target.value,
                                    })
                                }
                            />
                            {errors.quantity && (
                                <p className="text-sm text-red-500">
                                    {errors.quantity}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="unit_cost">Unit Cost</Label>
                            <Input
                                id="unit_cost"
                                type="number"
                                step="0.01"
                                name="unit_cost"
                                value={form.unit_cost}
                                onChange={(e) =>
                                    setForm({
                                        ...form,
                                        unit_cost: e.target.value,
                                    })
                                }
                            />
                            {errors.unit_cost && (
                                <p className="text-sm text-red-500">
                                    {errors.unit_cost}
                                </p>
                            )}
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor="total_amount">Total Amount</Label>
                            <Input
                                id="total_amount"
                                type="number"
                                step="0.01"
                                name="total_amount"
                                value={form.total_amount}
                                readOnly
                            />
                            {errors.total_amount && (
                                <p className="text-sm text-red-500">
                                    {errors.total_amount}
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
                            Save Changes
                        </Button>
                    </div>
                </div>
            </div>
        </form>
    </Modal>
);

export default function ChangeOrders({
    project,
    change_orders,
    cost_codes,
}) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [selectedOrders, setSelectedOrders] = useState([]);
    const [isAllSelected, setIsAllSelected] = useState(false);
    const [search, setSearch] = useState("");
    const [perPage, setPerPage] = useState(50);
    const [currentPage, setCurrentPage] = useState(1);
    const [bulkAction, setBulkAction] = useState("");
    const [sorting, setSorting] = useState({ column: "id", direction: "desc" });

    // Form state for Create/Edit dialogs
    const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
    const [isEditDialogOpen, setIsEditDialogOpen] = useState(false);
    const [editingOrder, setEditingOrder] = useState(null);
    const [form, setForm] = useState({
        project_id: project.id,
        project_task_id: "",
        cost_code_id: "",
        account_id: "",
        change_order_date: "",
        description: "",
        quantity: 0,
        unit_cost: 0,
        total_amount: 0,
        status: 0,
    });

    // Calculate total amount whenever quantity or unit_cost changes
    useEffect(() => {
        const quantity = parseFloat(form.quantity) || 0;
        const unitCost = parseFloat(form.unit_cost) || 0;
        const totalAmount = quantity * unitCost;
        
        setForm(prevForm => ({
            ...prevForm,
            total_amount: totalAmount
        }));
    }, [form.quantity, form.unit_cost]);

    // Form errors
    const [errors, setErrors] = useState({});

    // Delete confirmation modal states
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [orderToDelete, setOrderToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);

    // Client-side filtering, sorting and pagination
    const filteredAndSortedOrders = useMemo(() => {
        let result = [...change_orders];

        // Apply search filter
        if (search) {
            const searchLower = search.toLowerCase();
            result = result.filter(
                (order) =>
                    order.id.toString().includes(searchLower) ||
                    order.description.toLowerCase().includes(searchLower) ||
                    order.status.toLowerCase().includes(searchLower)
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
    }, [change_orders, search, sorting]);

    // Calculate pagination
    const paginatedOrders = useMemo(() => {
        const startIndex = (currentPage - 1) * perPage;
        return filteredAndSortedOrders.slice(startIndex, startIndex + perPage);
    }, [filteredAndSortedOrders, currentPage, perPage]);

    // Calculate pagination metadata
    const paginationMeta = useMemo(
        () => ({
            total: filteredAndSortedOrders.length,
            per_page: perPage,
            current_page: currentPage,
            last_page: Math.ceil(filteredAndSortedOrders.length / perPage),
        }),
        [filteredAndSortedOrders.length, perPage, currentPage]
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
            setSelectedOrders([]);
        } else {
            setSelectedOrders(paginatedOrders.map((order) => order.id));
        }
        setIsAllSelected(!isAllSelected);
    };

    const toggleSelectOrder = (id) => {
        if (selectedOrders.includes(id)) {
            setSelectedOrders(
                selectedOrders.filter((orderId) => orderId !== id)
            );
            setIsAllSelected(false);
        } else {
            setSelectedOrders([...selectedOrders, id]);
            if (selectedOrders.length + 1 === paginatedOrders.length) {
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

        if (selectedOrders.length === 0) {
            toast({
                variant: "destructive",
                title: "Error",
                description: "Please select at least one change order",
            });
            return;
        }

        if (bulkAction === "delete") {
            setShowDeleteAllModal(true);
        }
    };

    const handleDeleteConfirm = (id) => {
        setOrderToDelete(id);
        setShowDeleteModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.delete(route("change_orders.destroy", orderToDelete), {
            onSuccess: () => {
                setShowDeleteModal(false);
                setOrderToDelete(null);
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
            route("change_orders.bulk_destroy"),
            {
                ids: selectedOrders,
            },
            {
                onSuccess: () => {
                    setShowDeleteAllModal(false);
                    setSelectedOrders([]);
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
            project_id: project.id,
            project_task_id: "",
            cost_code_id: "",
            account_id: "",
            change_order_date: "",
            description: "",
            quantity: 0,
            unit_cost: 0,
            total_amount: 0,
            status: 0,
        });
        setErrors({});
        setIsCreateDialogOpen(true);
    };

    const handleCreateSubmit = (e) => {
        e.preventDefault();
        setProcessing(true);

        const formData = {
            project_id: form.project_id,
            project_task_id: form.project_task_id,
            cost_code_id: form.cost_code_id,
            account_id: form.account_id,
            change_order_date: form.change_order_date,
            description: form.description,
            quantity: form.quantity,
            unit_cost: form.unit_cost,
            total_amount: form.total_amount,
            status: form.status,
        };

        router.post(route("change_orders.store"), formData, {
            preserveState: true,
            onSuccess: () => {
                setIsCreateDialogOpen(false);
                setForm({
                    project_id: project.id,
                    project_task_id: "",
                    cost_code_id: "",
                    account_id: "",
                    change_order_date: "",
                    description: "",
                    quantity: 0,
                    unit_cost: 0,
                    total_amount: 0,
                    status: 0,
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
    const openEditDialog = (order) => {
        setEditingOrder(order);
        setForm({
            project_id: project.id,
            project_task_id: order.project_task_id,
            cost_code_id: order.cost_code_id,
            account_id: order.account_id,
            change_order_date: order.change_order_date,
            description: order.description,
            quantity: order.quantity,
            unit_cost: order.unit_cost,
            total_amount: order.total_amount,
            status: order.status,
        });
        setErrors({});
        setIsEditDialogOpen(true);
    };

    const handleEditSubmit = (e) => {
        e.preventDefault();
        setProcessing(true);

        const formData = {
            project_id: form.project_id,
            project_task_id: form.project_task_id,
            cost_code_id: form.cost_code_id,
            account_id: form.account_id,
            change_order_date: form.change_order_date,
            description: form.description,
            quantity: form.quantity,
            unit_cost: form.unit_cost,
            total_amount: form.total_amount,
            status: form.status,
        };

        router.put(route("change_orders.update", editingOrder.id), formData, {
            preserveState: true,
            onSuccess: () => {
                setIsEditDialogOpen(false);
                setEditingOrder(null);
                setForm({
                    project_id: project.id,
                    project_task_id: "",
                    cost_code_id: "",
                    account_id: "",
                    change_order_date: "",
                    description: "",
                    quantity: 0,
                    unit_cost: 0,
                    total_amount: 0,
                    status: 0,
                });
                setProcessing(false);
            },
            onError: (errors) => {
                setErrors(errors);
                setProcessing(false);
            },
        });
    };

    const OrderStatusBadge = ({ status }) => {
        const statusMap = {
            0: {
                label: "Pending",
                className:
                    "text-gray-600 bg-gray-200 px-3 py-1 rounded text-xs",
            },
            1: {
                label: "Approved",
                className:
                    "text-green-400 bg-green-200 px-3 py-1 rounded text-xs",
            },
            2: {
                label: "Rejected",
                className: "text-red-400 bg-red-200 px-3 py-1 rounded text-xs",
            },
            3: {
                label: "On Hold",
                className:
                    "text-yellow-400 bg-yellow-200 px-3 py-1 rounded text-xs",
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
            <Toaster />
            <div className="main-content">
                <div className="p-4">
                    <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                        <div className="flex flex-col md:flex-row gap-2">
                            <Button onClick={openCreateDialog}>
                                <Plus className="w-4 h-4 mr-2" />
                                Add Change Order
                            </Button>
                        </div>
                        <div className="flex flex-col md:flex-row gap-4 md:items-center">
                            <Input
                                placeholder="search change orders..."
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
                                        onClick={() =>
                                            handleSort("change_order_date")
                                        }
                                    >
                                        Date{" "}
                                        {renderSortIcon("change_order_date")}
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
                                        onClick={() => handleSort("quantity")}
                                    >
                                        Quantity {renderSortIcon("quantity")}
                                    </TableHead>
                                    <TableHead
                                        className="cursor-pointer"
                                        onClick={() => handleSort("unit_cost")}
                                    >
                                        Unit Cost {renderSortIcon("unit_cost")}
                                    </TableHead>
                                    <TableHead
                                        className="cursor-pointer"
                                        onClick={() =>
                                            handleSort("total_amount")
                                        }
                                    >
                                        Total Amount{" "}
                                        {renderSortIcon("total_amount")}
                                    </TableHead>
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
                                {change_orders.length > 0 ? (
                                    change_orders.map((order) => (
                                        <TableRow key={order.id}>
                                            <TableCell>
                                                <Checkbox
                                                    checked={selectedOrders.includes(
                                                        order.id
                                                    )}
                                                    onCheckedChange={() =>
                                                        toggleSelectOrder(
                                                            order.id
                                                        )
                                                    }
                                                />
                                            </TableCell>
                                            <TableCell>{order.id}</TableCell>
                                            <TableCell>
                                                {order.change_order_date}
                                            </TableCell>
                                            <TableCell>
                                                {order.description}
                                            </TableCell>
                                            <TableCell>
                                                {order.quantity}
                                            </TableCell>
                                            <TableCell>
                                                {formatCurrency({ amount: order.unit_cost, currency: project.currency })}
                                            </TableCell>
                                            <TableCell>
                                                {formatCurrency({ amount: order.total_amount, currency: project.currency })}
                                            </TableCell>
                                            <TableCell>
                                                <OrderStatusBadge
                                                    status={order.status}
                                                />
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
                                                                    order
                                                                ),
                                                        },
                                                        {
                                                            label: "Delete",
                                                            icon: (
                                                                <Trash className="h-4 w-4" />
                                                            ),
                                                            onClick: () =>
                                                                handleDeleteConfirm(
                                                                    order.id
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
                                            colSpan={9}
                                            className="h-24 text-center"
                                        >
                                            No change orders found.
                                        </TableCell>
                                    </TableRow>
                                )}
                            </TableBody>
                        </Table>
                    </div>

                    {paginatedOrders.length > 0 && paginationMeta.total > 0 && (
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

            {/* Create Dialog */}
            <CreateChangeOrderDialog
                show={isCreateDialogOpen}
                onClose={() => setIsCreateDialogOpen(false)}
                onSubmit={handleCreateSubmit}
                processing={processing}
                form={form}
                setForm={setForm}
                errors={errors}
                project={project}
                cost_codes={cost_codes}
            />

            {/* Edit Dialog */}
            <EditChangeOrderDialog
                show={isEditDialogOpen}
                onClose={() => {
                    setIsEditDialogOpen(false);
                    setEditingOrder(null);
                }}
                onSubmit={handleEditSubmit}
                processing={processing}
                form={form}
                setForm={setForm}
                errors={errors}
                project={project}
                cost_codes={cost_codes}
            />

            {/* Delete Modal */}
            <DeleteChangeOrderModal
                show={showDeleteModal}
                onClose={() => {
                    setShowDeleteModal(false);
                    setOrderToDelete(null);
                }}
                onConfirm={handleDelete}
                processing={processing}
            />

            {/* Bulk Delete Modal */}
            <DeleteAllChangeOrdersModal
                show={showDeleteAllModal}
                onClose={() => setShowDeleteAllModal(false)}
                onConfirm={handleDeleteAll}
                processing={processing}
                count={selectedOrders.length}
            />
        </div>
    );
}

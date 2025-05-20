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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
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
import InputError from "@/Components/InputError";
import { formatCurrency } from "@/lib/utils";

// Delete Confirmation Modal Component
const DeleteBudgetModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete this budget?
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
const DeleteAllBudgetsModal = ({
    show,
    onClose,
    onConfirm,
    processing,
    count,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete {count} selected budget
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

// Import Budgets Modal Component
const ImportBudgetsModal = ({
    show,
    onClose,
    onSubmit,
    processing,
    errors,
    project,
}) => (
    <Modal show={show} onClose={onClose} maxWidth="3xl">
        <form onSubmit={onSubmit}>
            <div>
                <h3 className="text-lg font-bold">Import Budgets</h3>
            </div>
            <div className="grid grid-cols-12">
                <div className="col-span-12">
                    <div className="flex items-center justify-between">
                        <label className="block font-medium text-sm text-gray-700">
                            Budgets File
                        </label>
                        <a
                            href="/uploads/media/default/sample_project_budgets.xlsx"
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
                        name="project_budgets_file"
                        required
                    />
                    <input type="hidden" name="project_id" value={project.id} />
                    {errors.project_budgets_file && (
                        <p className="text-sm text-red-500">
                            {errors.project_budgets_file}
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

// Create Budget Dialog Component
const CreateBudgetDialog = ({
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
    <Modal show={show} onClose={onClose} maxWidth="4xl">
        <form onSubmit={onSubmit}>
            <div>
                <h3 className="text-lg font-bold">Add Budget</h3>
            </div>
            <div className="grid grid-cols-12">
                <div className="col-span-12">
                    <div className="grid gap-4 py-4">
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="project_task_id"
                                className="md:col-span-4 col-span-12"
                            >
                                Project Task *
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
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
                                    placeholder="Select Project Task"
                                    onChange={(value) =>
                                        setForm({
                                            ...form,
                                            project_task_id: value,
                                        })
                                    }
                                />
                                <InputError
                                    message={errors.project_task_id}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="cost_code_id"
                                className="md:col-span-4 col-span-12"
                            >
                                Cost Code *
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <SearchableCombobox
                                    id="cost_code_id"
                                    options={cost_codes.map((code) => ({
                                        id: code.id,
                                        name:
                                            "(" +
                                            code.code +
                                            ") " +
                                            code.description,
                                    }))}
                                    name="cost_code_id"
                                    value={form.cost_code_id}
                                    placeholder="Select Cost Code"
                                    onChange={(value) =>
                                        setForm({
                                            ...form,
                                            cost_code_id: value,
                                        })
                                    }
                                />
                                <InputError
                                    message={errors.cost_code_id}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="description"
                                className="md:col-span-4 col-span-12"
                            >
                                Description
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
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
                                    placeholder="Enter description"
                                />
                                <InputError
                                    message={errors.description}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="uom"
                                className="md:col-span-4 col-span-12"
                            >
                                Unit of Measure
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="uom"
                                    name="uom"
                                    value={form.uom}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            uom: e.target.value,
                                        })
                                    }
                                    placeholder="Enter unit of measure"
                                />
                                <InputError
                                    message={errors.uom}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="unit_rate"
                                className="md:col-span-4 col-span-12"
                            >
                                Unit Rate
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="unit_rate"
                                    type="number"
                                    step="0.01"
                                    name="unit_rate"
                                    value={form.unit_rate}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            unit_rate: e.target.value,
                                        })
                                    }
                                />
                                <InputError
                                    message={errors.unit_rate}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="original_budgeted_quantity"
                                className="md:col-span-4 col-span-12"
                            >
                                Original Budgeted Quantity
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="original_budgeted_quantity"
                                    type="number"
                                    step="0.01"
                                    name="original_budgeted_quantity"
                                    value={form.original_budgeted_quantity}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            original_budgeted_quantity:
                                                e.target.value,
                                        })
                                    }
                                />
                                <InputError
                                    message={errors.original_budgeted_quantity}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="original_budgeted_amount"
                                className="md:col-span-4 col-span-12"
                            >
                                Original Budgeted Amount
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <p>
                                    {formatCurrency(
                                        form.original_budgeted_amount
                                    )}
                                </p>
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="revised_budgeted_quantity"
                                className="md:col-span-4 col-span-12"
                            >
                                Revised Budgeted Quantity
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="revised_budgeted_quantity"
                                    type="number"
                                    step="0.01"
                                    name="revised_budgeted_quantity"
                                    value={form.revised_budgeted_quantity}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            revised_budgeted_quantity:
                                                e.target.value,
                                        })
                                    }
                                />
                                <InputError
                                    message={errors.revised_budgeted_quantity}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="revised_budgeted_amount"
                                className="md:col-span-4 col-span-12"
                            >
                                Revised Budgeted Amount
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <p>
                                    {formatCurrency(
                                        form.revised_budgeted_amount
                                    )}
                                </p>
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="revised_committed_quantity"
                                className="md:col-span-4 col-span-12"
                            >
                                Revised Committed Quantity
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="revised_committed_quantity"
                                    type="number"
                                    step="0.01"
                                    name="revised_committed_quantity"
                                    value={form.revised_committed_quantity}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            revised_committed_quantity:
                                                e.target.value,
                                        })
                                    }
                                />
                                <InputError
                                    message={errors.revised_committed_quantity}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="revised_committed_amount"
                                className="md:col-span-4 col-span-12"
                            >
                                Revised Committed Amount
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <p>
                                    {formatCurrency(
                                        form.revised_committed_amount
                                    )}
                                </p>
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="committed_open_quantity"
                                className="md:col-span-4 col-span-12"
                            >
                                Committed Open Quantity
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="committed_open_quantity"
                                    type="number"
                                    step="0.01"
                                    name="committed_open_quantity"
                                    value={form.committed_open_quantity}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            committed_open_quantity:
                                                e.target.value,
                                        })
                                    }
                                />
                                <InputError
                                    message={errors.committed_open_quantity}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="committed_open_amount"
                                className="md:col-span-4 col-span-12"
                            >
                                Committed Open Amount
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <p>
                                    {formatCurrency(form.committed_open_amount)}
                                </p>
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="committed_received_quantity"
                                className="md:col-span-4 col-span-12"
                            >
                                Committed Received Quantity
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="committed_received_quantity"
                                    type="number"
                                    step="0.01"
                                    name="committed_received_quantity"
                                    value={form.committed_received_quantity}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            committed_received_quantity:
                                                e.target.value,
                                        })
                                    }
                                />
                                <InputError
                                    message={errors.committed_received_quantity}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="committed_invoiced_quantity"
                                className="md:col-span-4 col-span-12"
                            >
                                Committed Invoiced Quantity
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="committed_invoiced_quantity"
                                    type="number"
                                    step="0.01"
                                    name="committed_invoiced_quantity"
                                    value={form.committed_invoiced_quantity}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            committed_invoiced_quantity:
                                                e.target.value,
                                        })
                                    }
                                />
                                <InputError
                                    message={errors.committed_invoiced_quantity}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="committed_invoiced_amount"
                                className="md:col-span-4 col-span-12"
                            >
                                Committed Invoiced Amount
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <p>
                                    {formatCurrency(
                                        form.committed_invoiced_amount
                                    )}
                                </p>
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="actual_quantity"
                                className="md:col-span-4 col-span-12"
                            >
                                Actual Quantity
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="actual_quantity"
                                    type="number"
                                    step="0.01"
                                    name="actual_quantity"
                                    value={form.actual_quantity}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            actual_quantity: e.target.value,
                                        })
                                    }
                                />
                                <InputError
                                    message={errors.actual_quantity}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="actual_amount"
                                className="md:col-span-4 col-span-12"
                            >
                                Actual Amount
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <p>{formatCurrency(form.actual_amount)}</p>
                            </div>
                        </div>
                    </div>
                    <div className="mt-6">
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

// Edit Budget Dialog Component
const EditBudgetDialog = ({
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
    <Modal show={show} onClose={onClose} maxWidth="4xl">
        <form onSubmit={onSubmit}>
            <div>
                <h3 className="text-lg font-bold">Edit Budget</h3>
            </div>
            <div className="grid grid-cols-12">
                <div className="col-span-12">
                    <div className="grid gap-4 py-4">
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="project_task_id"
                                className="md:col-span-4 col-span-12"
                            >
                                Project Task *
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
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
                                    placeholder="Select Project Task"
                                    onChange={(value) =>
                                        setForm({
                                            ...form,
                                            project_task_id: value,
                                        })
                                    }
                                />
                                <InputError
                                    message={errors.project_task_id}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="cost_code_id"
                                className="md:col-span-4 col-span-12"
                            >
                                Cost Code *
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <SearchableCombobox
                                    id="cost_code_id"
                                    options={cost_codes.map((code) => ({
                                        id: code.id,
                                        name:
                                            "(" +
                                            code.code +
                                            ") " +
                                            code.description,
                                    }))}
                                    name="cost_code_id"
                                    value={form.cost_code_id}
                                    placeholder="Select Cost Code"
                                    onChange={(value) =>
                                        setForm({
                                            ...form,
                                            cost_code_id: value,
                                        })
                                    }
                                />
                                <InputError
                                    message={errors.cost_code_id}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="description"
                                className="md:col-span-4 col-span-12"
                            >
                                Description
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
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
                                    placeholder="Enter description"
                                />
                                <InputError
                                    message={errors.description}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="uom"
                                className="md:col-span-4 col-span-12"
                            >
                                Unit of Measure
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="uom"
                                    name="uom"
                                    value={form.uom}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            uom: e.target.value,
                                        })
                                    }
                                    placeholder="Enter unit of measure"
                                />
                                <InputError
                                    message={errors.uom}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="unit_rate"
                                className="md:col-span-4 col-span-12"
                            >
                                Unit Rate
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="unit_rate"
                                    type="number"
                                    step="0.01"
                                    name="unit_rate"
                                    value={form.unit_rate}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            unit_rate: e.target.value,
                                        })
                                    }
                                />
                                <InputError
                                    message={errors.unit_rate}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="original_budgeted_quantity"
                                className="md:col-span-4 col-span-12"
                            >
                                Original Budgeted Quantity
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="original_budgeted_quantity"
                                    type="number"
                                    step="0.01"
                                    name="original_budgeted_quantity"
                                    value={form.original_budgeted_quantity}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            original_budgeted_quantity:
                                                e.target.value,
                                        })
                                    }
                                />
                                <InputError
                                    message={errors.original_budgeted_quantity}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="original_budgeted_amount"
                                className="md:col-span-4 col-span-12"
                            >
                                Original Budgeted Amount
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <p>
                                    {formatCurrency(
                                        form.original_budgeted_amount
                                    )}
                                </p>
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="revised_budgeted_quantity"
                                className="md:col-span-4 col-span-12"
                            >
                                Revised Budgeted Quantity
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="revised_budgeted_quantity"
                                    type="number"
                                    step="0.01"
                                    name="revised_budgeted_quantity"
                                    value={form.revised_budgeted_quantity}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            revised_budgeted_quantity:
                                                e.target.value,
                                        })
                                    }
                                />
                                <InputError
                                    message={errors.revised_budgeted_quantity}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="revised_budgeted_amount"
                                className="md:col-span-4 col-span-12"
                            >
                                Revised Budgeted Amount
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <p>
                                    {formatCurrency(
                                        form.revised_budgeted_amount
                                    )}
                                </p>
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="revised_committed_quantity"
                                className="md:col-span-4 col-span-12"
                            >
                                Revised Committed Quantity
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="revised_committed_quantity"
                                    type="number"
                                    step="0.01"
                                    name="revised_committed_quantity"
                                    value={form.revised_committed_quantity}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            revised_committed_quantity:
                                                e.target.value,
                                        })
                                    }
                                />
                                <InputError
                                    message={errors.revised_committed_quantity}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="revised_committed_amount"
                                className="md:col-span-4 col-span-12"
                            >
                                Revised Committed Amount
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <p>
                                    {formatCurrency(
                                        form.revised_committed_amount
                                    )}
                                </p>
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="committed_open_quantity"
                                className="md:col-span-4 col-span-12"
                            >
                                Committed Open Quantity
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="committed_open_quantity"
                                    type="number"
                                    step="0.01"
                                    name="committed_open_quantity"
                                    value={form.committed_open_quantity}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            committed_open_quantity:
                                                e.target.value,
                                        })
                                    }
                                />
                                <InputError
                                    message={errors.committed_open_quantity}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="committed_open_amount"
                                className="md:col-span-4 col-span-12"
                            >
                                Committed Open Amount
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <p>
                                    {formatCurrency(form.committed_open_amount)}
                                </p>
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="committed_received_quantity"
                                className="md:col-span-4 col-span-12"
                            >
                                Committed Received Quantity
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="committed_received_quantity"
                                    type="number"
                                    step="0.01"
                                    name="committed_received_quantity"
                                    value={form.committed_received_quantity}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            committed_received_quantity:
                                                e.target.value,
                                        })
                                    }
                                />
                                <InputError
                                    message={errors.committed_received_quantity}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="committed_invoiced_quantity"
                                className="md:col-span-4 col-span-12"
                            >
                                Committed Invoiced Quantity
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="committed_invoiced_quantity"
                                    type="number"
                                    step="0.01"
                                    name="committed_invoiced_quantity"
                                    value={form.committed_invoiced_quantity}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            committed_invoiced_quantity:
                                                e.target.value,
                                        })
                                    }
                                />
                                <InputError
                                    message={errors.committed_invoiced_quantity}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="committed_invoiced_amount"
                                className="md:col-span-4 col-span-12"
                            >
                                Committed Invoiced Amount
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <p>
                                    {formatCurrency(
                                        form.committed_invoiced_amount
                                    )}
                                </p>
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="actual_quantity"
                                className="md:col-span-4 col-span-12"
                            >
                                Actual Quantity
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <Input
                                    id="actual_quantity"
                                    type="number"
                                    step="0.01"
                                    name="actual_quantity"
                                    value={form.actual_quantity}
                                    onChange={(e) =>
                                        setForm({
                                            ...form,
                                            actual_quantity: e.target.value,
                                        })
                                    }
                                />
                                <InputError
                                    message={errors.actual_quantity}
                                    className="text-sm"
                                />
                            </div>
                        </div>
                        <div className="grid grid-cols-12">
                            <Label
                                htmlFor="actual_amount"
                                className="md:col-span-4 col-span-12"
                            >
                                Actual Amount
                            </Label>
                            <div className="md:col-span-8 col-span-12 md:mt-0 mt-2">
                                <p>{formatCurrency(form.actual_amount)}</p>
                            </div>
                        </div>
                    </div>
                    <div className="mt-6">
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

export default function BudgetList({ cost_codes = [], project }) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [selectedBudgets, setSelectedBudgets] = useState([]);
    const [isAllSelected, setIsAllSelected] = useState(false);
    const [search, setSearch] = useState("");
    const [perPage, setPerPage] = useState(50);
    const [currentPage, setCurrentPage] = useState(1);
    const [bulkAction, setBulkAction] = useState("");
    const [sorting, setSorting] = useState({ column: "id", direction: "desc" });

    // Form state for Create/Edit dialogs
    const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
    const [isEditDialogOpen, setIsEditDialogOpen] = useState(false);
    const [editingBudget, setEditingBudget] = useState(null);
    const [form, setForm] = useState({
        project_task_id: "",
        cost_code_id: "",
        description: "",
        uom: "",
        original_budgeted_quantity: 0,
        unit_rate: 0,
        original_budgeted_amount: 0,
        revised_budgeted_quantity: 0,
        revised_budgeted_amount: 0,
        revised_committed_quantity: 0,
        revised_committed_amount: 0,
        committed_open_quantity: 0,
        committed_open_amount: 0,
        committed_received_quantity: 0,
        committed_invoiced_quantity: 0,
        committed_invoiced_amount: 0,
        actual_quantity: 0,
        actual_amount: 0,
        project_id: project.id,
    });

    // Calculate amounts based on unit rate and quantities
    useEffect(() => {
        const calculateAmount = (quantity) => {
            return (
                (parseFloat(quantity) || 0) * (parseFloat(form.unit_rate) || 0)
            );
        };

        setForm((prev) => ({
            ...prev,
            original_budgeted_amount: calculateAmount(
                prev.original_budgeted_quantity
            ),
            revised_budgeted_amount: calculateAmount(
                prev.revised_budgeted_quantity
            ),
            revised_committed_amount: calculateAmount(
                prev.revised_committed_quantity
            ),
            committed_open_amount: calculateAmount(
                prev.committed_open_quantity
            ),
            committed_invoiced_amount: calculateAmount(
                prev.committed_invoiced_quantity
            ),
            actual_amount: calculateAmount(prev.actual_quantity),
        }));
    }, [
        form.unit_rate,
        form.original_budgeted_quantity,
        form.revised_budgeted_quantity,
        form.revised_committed_quantity,
        form.committed_open_quantity,
        form.committed_invoiced_quantity,
        form.actual_quantity,
    ]);

    // Form errors
    const [errors, setErrors] = useState({});

    // Delete confirmation modal states
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [showImportModal, setShowImportModal] = useState(false);
    const [budgetToDelete, setBudgetToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);

    // Client-side filtering, sorting and pagination
    const filteredAndSortedBudgets = useMemo(() => {
        let result = [...project.budgets];

        // Apply search filter
        if (search) {
            const searchLower = search.toLowerCase();
            result = result.filter(
                (budget) =>
                    budget.id.toString().includes(searchLower) ||
                    budget.tasks?.task_code
                        .toLowerCase()
                        .includes(searchLower) ||
                    budget.cost_codes?.code
                        .toLowerCase()
                        .includes(searchLower) ||
                    budget.description.toLowerCase().includes(searchLower) ||
                    budget.uom.toLowerCase().includes(searchLower) ||
                    budget.unit_rate.toString().includes(searchLower) ||
                    budget.original_budgeted_quantity
                        .toString()
                        .includes(searchLower) ||
                    budget.original_budgeted_amount
                        .toString()
                        .includes(searchLower) ||
                    budget.revised_budgeted_quantity
                        .toString()
                        .includes(searchLower) ||
                    budget.revised_budgeted_amount
                        .toString()
                        .includes(searchLower) ||
                    budget.revised_committed_quantity
                        .toString()
                        .includes(searchLower) ||
                    budget.revised_committed_amount
                        .toString()
                        .includes(searchLower) ||
                    budget.committed_open_quantity
                        .toString()
                        .includes(searchLower) ||
                    budget.committed_open_amount
                        .toString()
                        .includes(searchLower) ||
                    budget.committed_invoiced_quantity
                        .toString()
                        .includes(searchLower) ||
                    budget.committed_invoiced_amount
                        .toString()
                        .includes(searchLower) ||
                    budget.actual_quantity.toString().includes(searchLower) ||
                    budget.actual_amount.toString().includes(searchLower)
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
    }, [project.budgets, search, sorting]);

    // Calculate pagination
    const paginatedBudgets = useMemo(() => {
        const startIndex = (currentPage - 1) * perPage;
        return filteredAndSortedBudgets.slice(startIndex, startIndex + perPage);
    }, [filteredAndSortedBudgets, currentPage, perPage]);

    // Calculate pagination metadata
    const paginationMeta = useMemo(
        () => ({
            total: filteredAndSortedBudgets.length,
            per_page: perPage,
            current_page: currentPage,
            last_page: Math.ceil(filteredAndSortedBudgets.length / perPage),
        }),
        [filteredAndSortedBudgets.length, perPage, currentPage]
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
            setSelectedBudgets([]);
        } else {
            setSelectedBudgets(paginatedBudgets.map((budget) => budget.id));
        }
        setIsAllSelected(!isAllSelected);
    };

    const toggleSelectBudget = (id) => {
        if (selectedBudgets.includes(id)) {
            setSelectedBudgets(
                selectedBudgets.filter((budgetId) => budgetId !== id)
            );
            setIsAllSelected(false);
        } else {
            setSelectedBudgets([...selectedBudgets, id]);
            if (selectedBudgets.length + 1 === paginatedBudgets.length) {
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

        if (selectedBudgets.length === 0) {
            toast({
                variant: "destructive",
                title: "Error",
                description: "Please select at least one budget",
            });
            return;
        }

        if (bulkAction === "delete") {
            setShowDeleteAllModal(true);
        }
    };

    const handleDeleteConfirm = (id) => {
        setBudgetToDelete(id);
        setShowDeleteModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.delete(route("project_budgets.destroy", budgetToDelete), {
            onSuccess: () => {
                setShowDeleteModal(false);
                setBudgetToDelete(null);
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
            route("project_budgets.bulk_destroy"),
            {
                ids: selectedBudgets,
            },
            {
                onSuccess: () => {
                    setShowDeleteAllModal(false);
                    setSelectedBudgets([]);
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

        router.post(route("project_budgets.import"), formData, {
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

    const exportBudgets = () => {
        window.location.href = route("project_budgets.export");
    };

    // Create form handlers
    const openCreateDialog = () => {
        setForm({
            project_task_id: "",
            cost_code_id: "",
            description: "",
            uom: "",
            original_budgeted_quantity: "",
            unit_rate: "",
            original_budgeted_amount: "",
            revised_budgeted_quantity: "",
            revised_budgeted_amount: "",
            revised_committed_quantity: "",
            revised_committed_amount: "",
            committed_open_quantity: "",
            committed_open_amount: "",
            committed_received_quantity: "",
            committed_invoiced_quantity: "",
            committed_invoiced_amount: "",
            actual_quantity: "",
            actual_amount: "",
            project_id: project.id,
        });
        setErrors({});
        setIsCreateDialogOpen(true);
    };

    const handleCreateSubmit = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(route("project_budgets.store"), form, {
            preserveState: true,
            onSuccess: () => {
                setIsCreateDialogOpen(false);
                setForm({
                    project_task_id: "",
                    cost_code_id: "",
                    description: "",
                    uom: "",
                    original_budgeted_quantity: "",
                    unit_rate: "",
                    original_budgeted_amount: "",
                    revised_budgeted_quantity: "",
                    revised_budgeted_amount: "",
                    revised_committed_quantity: "",
                    revised_committed_amount: "",
                    committed_open_quantity: 0,
                    committed_open_amount: 0,
                    committed_received_quantity: "",
                    committed_invoiced_quantity: "",
                    committed_invoiced_amount: "",
                    actual_quantity: "",
                    actual_amount: "",
                    project_id: project.id,
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
    const openEditDialog = (budget) => {
        setEditingBudget(budget);
        setForm({
            project_task_id: budget.project_task_id,
            cost_code_id: budget.cost_code_id,
            description: budget.description,
            uom: budget.uom,
            original_budgeted_quantity: budget.original_budgeted_quantity,
            unit_rate: budget.unit_rate,
            original_budgeted_amount: budget.original_budgeted_amount,
            revised_budgeted_quantity: budget.revised_budgeted_quantity,
            revised_budgeted_amount: budget.revised_budgeted_amount,
            revised_committed_quantity: budget.revised_committed_quantity,
            revised_committed_amount: budget.revised_committed_amount,
            committed_open_quantity: budget.committed_open_quantity,
            committed_open_amount: budget.committed_open_amount,
            committed_received_quantity: budget.committed_received_quantity,
            committed_invoiced_quantity: budget.committed_invoiced_quantity,
            committed_invoiced_amount: budget.committed_invoiced_amount,
            actual_quantity: budget.actual_quantity,
            actual_amount: budget.actual_amount,
            project_id: budget.project_id,
        });
        setErrors({});
        setIsEditDialogOpen(true);
    };

    const handleEditSubmit = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.put(route("project_budgets.update", editingBudget.id), form, {
            preserveState: true,
            onSuccess: () => {
                setIsEditDialogOpen(false);
                setEditingBudget(null);
                setProcessing(false);
            },
            onError: (errors) => {
                setErrors(errors);
                setProcessing(false);
            },
        });
    };

    return (
        <div>
            <Head title="Budgets" />
            <Toaster />
            <div className="p-4">
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                    <div className="flex flex-col md:flex-row gap-2">
                        <Button onClick={openCreateDialog}>
                            <Plus className="w-4 h-4 mr-2" />
                            Add Budget
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
                                    <FileUp className="mr-2 h-4 w-4" /> Import
                                </DropdownMenuItem>
                                <DropdownMenuItem onClick={exportBudgets}>
                                    <FileDown className="mr-2 h-4 w-4" /> Export
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>
                    <div className="flex flex-col md:flex-row gap-4 md:items-center">
                        <Input
                            placeholder="search budgets..."
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
                        <Button onClick={handleBulkAction} variant="outline">
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
                        <span className="text-sm text-gray-500">entries</span>
                    </div>
                </div>

                <div className="rounded-md border overflow-y-auto">
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
                                    onClick={() => handleSort("task_code")}
                                >
                                    Task Code {renderSortIcon("task_code")}
                                </TableHead>
                                <TableHead
                                    className="cursor-pointer"
                                    onClick={() => handleSort("cost_code")}
                                >
                                    Cost Code {renderSortIcon("cost_code")}
                                </TableHead>
                                <TableHead
                                    className="cursor-pointer"
                                    onClick={() => handleSort("status")}
                                >
                                    Description {renderSortIcon("description")}
                                </TableHead>
                                <TableHead
                                    className="cursor-pointer"
                                    onClick={() =>
                                        handleSort("completed_percent")
                                    }
                                >
                                    UOM {renderSortIcon("uom")}
                                </TableHead>
                                <TableHead
                                    className="cursor-pointer"
                                    onClick={() => handleSort("unit_rate")}
                                >
                                    Unit Rate {renderSortIcon("unit_rate")}
                                </TableHead>
                                <TableHead
                                    className="cursor-pointer"
                                    onClick={() =>
                                        handleSort("original_budgeted_quantity")
                                    }
                                >
                                    Original Budgeted Quantity{" "}
                                    {renderSortIcon(
                                        "original_budgeted_quantity"
                                    )}
                                </TableHead>
                                <TableHead
                                    className="cursor-pointer"
                                    onClick={() =>
                                        handleSort("original_budgeted_amount")
                                    }
                                >
                                    Original Budgeted Amount{" "}
                                    {renderSortIcon("original_budgeted_amount")}
                                </TableHead>
                                <TableHead
                                    className="cursor-pointer"
                                    onClick={() =>
                                        handleSort("revised_budgeted_quantity")
                                    }
                                >
                                    Revised Budgeted Quantity{" "}
                                    {renderSortIcon(
                                        "revised_budgeted_quantity"
                                    )}
                                </TableHead>
                                <TableHead
                                    className="cursor-pointer"
                                    onClick={() =>
                                        handleSort("revised_budgeted_amount")
                                    }
                                >
                                    Revised Budgeted Amount{" "}
                                    {renderSortIcon("revised_budgeted_amount")}
                                </TableHead>
                                <TableHead
                                    className="cursor-pointer"
                                    onClick={() =>
                                        handleSort("revised_committed_quantity")
                                    }
                                >
                                    Revised Committed Quantity{" "}
                                    {renderSortIcon(
                                        "revised_committed_quantity"
                                    )}
                                </TableHead>
                                <TableHead
                                    className="cursor-pointer"
                                    onClick={() =>
                                        handleSort("revised_committed_amount")
                                    }
                                >
                                    Revised Committed Amount{" "}
                                    {renderSortIcon("revised_committed_amount")}
                                </TableHead>
                                <TableHead
                                    className="cursor-pointer"
                                    onClick={() =>
                                        handleSort("committed_open_quantity")
                                    }
                                >
                                    Committed Open Quantity{" "}
                                    {renderSortIcon("committed_open_quantity")}
                                </TableHead>
                                <TableHead
                                    className="cursor-pointer"
                                    onClick={() =>
                                        handleSort("committed_open_amount")
                                    }
                                >
                                    Committed Open Amount{" "}
                                    {renderSortIcon("committed_open_amount")}
                                </TableHead>
                                <TableHead
                                    className="cursor-pointer"
                                    onClick={() =>
                                        handleSort(
                                            "committed_received_quantity"
                                        )
                                    }
                                >
                                    Committed Received Quantity{" "}
                                    {renderSortIcon(
                                        "committed_received_quantity"
                                    )}
                                </TableHead>
                                <TableHead
                                    className="cursor-pointer"
                                    onClick={() =>
                                        handleSort("committed_received_amount")
                                    }
                                >
                                    Committed Received Amount{" "}
                                    {renderSortIcon(
                                        "committed_received_amount"
                                    )}
                                </TableHead>
                                <TableHead
                                    className="cursor-pointer"
                                    onClick={() =>
                                        handleSort("committed_received_amount")
                                    }
                                >
                                    Committed Invoiced Quantity{" "}
                                    {renderSortIcon(
                                        "committed_invoiced_quantity"
                                    )}
                                </TableHead>
                                <TableHead
                                    className="cursor-pointer"
                                    onClick={() =>
                                        handleSort("committed_invoiced_amount")
                                    }
                                >
                                    Committed Invoiced Amount{" "}
                                    {renderSortIcon(
                                        "committed_invoiced_amount"
                                    )}
                                </TableHead>
                                <TableHead
                                    className="cursor-pointer"
                                    onClick={() =>
                                        handleSort("committed_invoiced_amount")
                                    }
                                >
                                    Actual Quantity{" "}
                                    {renderSortIcon("actual_quantity")}
                                </TableHead>
                                <TableHead
                                    className="cursor-pointer"
                                    onClick={() => handleSort("actual_amount")}
                                >
                                    Actual Amount{" "}
                                    {renderSortIcon("actual_amount")}
                                </TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {paginatedBudgets.length > 0 ? (
                                paginatedBudgets.map((budget) => (
                                    <TableRow key={budget.id}>
                                        <TableCell>
                                            <Checkbox
                                                checked={selectedBudgets.includes(
                                                    budget.id
                                                )}
                                                onCheckedChange={() =>
                                                    toggleSelectBudget(
                                                        budget.id
                                                    )
                                                }
                                            />
                                        </TableCell>
                                        <TableCell>
                                            {budget.tasks?.task_code}
                                        </TableCell>
                                        <TableCell>
                                            {budget.cost_codes?.code}
                                        </TableCell>
                                        <TableCell>
                                            {budget.description}
                                        </TableCell>
                                        <TableCell>{budget.uom}</TableCell>
                                        <TableCell className="text-right">
                                            {budget.original_budgeted_quantity}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {formatCurrency(budget.unit_rate)}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {formatCurrency(
                                                budget.original_budgeted_amount
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {budget.revised_budgeted_quantity}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {formatCurrency(
                                                budget.revised_budgeted_amount
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {budget.revised_committed_quantity}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {formatCurrency(
                                                budget.revised_committed_amount
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {budget.committed_open_quantity}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {formatCurrency(
                                                budget.committed_open_amount
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {budget.committed_received_quantity}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {formatCurrency(
                                                budget.committed_received_amount
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {budget.committed_invoiced_quantity}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {formatCurrency(
                                                budget.committed_invoiced_amount
                                            )}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {budget.actual_quantity}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {formatCurrency(
                                                budget.actual_amount
                                            )}
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
                                                                budget
                                                            ),
                                                    },
                                                    {
                                                        label: "Delete",
                                                        icon: (
                                                            <Trash className="h-4 w-4" />
                                                        ),
                                                        onClick: () =>
                                                            handleDeleteConfirm(
                                                                budget.id
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
                                        colSpan={21}
                                        className="h-24 text-center"
                                    >
                                        No budgets found.
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>

                {paginatedBudgets.length > 0 && paginationMeta.total > 0 && (
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
                                    handlePageChange(paginationMeta.last_page)
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

            <DeleteBudgetModal
                show={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                onConfirm={handleDelete}
                processing={processing}
            />

            <DeleteAllBudgetsModal
                show={showDeleteAllModal}
                onClose={() => setShowDeleteAllModal(false)}
                onConfirm={handleDeleteAll}
                processing={processing}
                count={selectedBudgets.length}
            />

            <ImportBudgetsModal
                show={showImportModal}
                onClose={() => setShowImportModal(false)}
                onSubmit={handleImport}
                processing={processing}
                errors={errors}
                project={project}
            />

            {/* Create Dialog */}
            <CreateBudgetDialog
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
            <EditBudgetDialog
                show={isEditDialogOpen}
                onClose={() => setIsEditDialogOpen(false)}
                onSubmit={handleEditSubmit}
                processing={processing}
                form={form}
                setForm={setForm}
                errors={errors}
                project={project}
                cost_codes={cost_codes}
            />
        </div>
    );
}
import React, { useState, useEffect } from "react";
import { Head, router, usePage } from "@inertiajs/react";
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
import { Label } from "@/Components/ui/label";
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogFooter,
} from "@/Components/ui/dialog";
import { Plus, Edit, Trash, Search } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";

// Delete Confirmation Modal Component
const DeleteConfirmationModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete this tax?
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
const BulkDeleteConfirmationModal = ({ show, onClose, onConfirm, processing, count }) => (
    <Modal show={show} onClose={onClose}>
        <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete {count} selected tax{count !== 1 ? 'es' : ''}?
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

export default function List({ taxs = [], meta = {}, filters = {}, accounts = [] }) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [selectedMethods, setSelectedMethods] = useState([]);
    const [isAllSelected, setIsAllSelected] = useState(false);
    const [search, setSearch] = useState(filters.search || "");
    const [perPage, setPerPage] = useState(meta.per_page || 10);
    const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
    const [bulkAction, setBulkAction] = useState("");

    // Form state for Create/Edit dialogs
    const [isCreateDialogOpen, setIsCreateDialogOpen] = useState(false);
    const [isEditDialogOpen, setIsEditDialogOpen] = useState(false);
    const [editingTax, setEditingTax] = useState(null);
    const [form, setForm] = useState({
        name: "",
        rate: "",
        tax_number: "",
        account_id: ""
    });

    // Form errors
    const [errors, setErrors] = useState({});

    // Delete confirmation modal states
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showBulkDeleteModal, setShowBulkDeleteModal] = useState(false);
    const [taxToDelete, setTaxToDelete] = useState(null);
    const [isProcessing, setIsProcessing] = useState(false);

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
            setSelectedMethods([]);
        } else {
            setSelectedMethods(taxs.map((tax) => tax.id));
        }
        setIsAllSelected(!isAllSelected);
    };

    const toggleSelectTax = (id) => {
        if (selectedMethods.includes(id)) {
            setSelectedMethods(selectedMethods.filter((taxId) => taxId !== id));
            setIsAllSelected(false);
        } else {
            setSelectedMethods([...selectedMethods, id]);
            if (selectedMethods.length + 1 === taxs.length) {
                setIsAllSelected(true);
            }
        }
    };

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(
            route("taxes.index"),
            { search, page: 1, per_page: perPage },
            { preserveState: true }
        );
    };

    const handlePerPageChange = (value) => {
        setPerPage(value);
        router.get(
            route("taxes.index"),
            { search, page: 1, per_page: value },
            { preserveState: true }
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("taxes.index"),
            { search, page, per_page: perPage },
            { preserveState: true }
        );
    };

    const handleBulkAction = () => {
        if (bulkAction === "") return;

        if (selectedMethods.length === 0) {
            toast({
                variant: "destructive",
                title: "Error",
                description: "Please select at least one transaction method",
            });
            return;
        }

        if (bulkAction === "delete") {
            setShowBulkDeleteModal(true);
        }
    };

    const handleDeleteConfirm = (id) => {
        setTaxToDelete(id);
        setShowDeleteModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setIsProcessing(true);

        router.delete(route("taxes.destroy", taxToDelete), {
            preserveState: true,
            onSuccess: () => {
                setShowDeleteModal(false);
                setTaxToDelete(null);
                setIsProcessing(false);
            },
            onError: () => {
                setIsProcessing(false);
            }
        });
    };

    const handleBulkDeleteConfirm = (e) => {
        e.preventDefault();
        setIsProcessing(true);

        router.post(route("taxes.bulk_delete"), {
            ids: selectedMethods
        }, {
            preserveState: true,
            onSuccess: () => {
                setSelectedMethods([]);
                setIsAllSelected(false);
                setBulkAction("");
                setShowBulkDeleteModal(false);
                setIsProcessing(false);
            },
            onError: () => {
                setIsProcessing(false);
            }
        });
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

    // Create form handlers
    const openCreateDialog = () => {
        setForm({
            name: "",
            rate: "",
            tax_number: "",
            account_id: ""
        });
        setErrors({});
        setIsCreateDialogOpen(true);
    };

    const handleCreateSubmit = (e) => {
        e.preventDefault();

        router.post(route("taxes.store"), form, {
            preserveState: true,
            onSuccess: () => {
                setIsCreateDialogOpen(false);
                setForm({
                    name: "",
                    rate: "",
                    tax_number: "",
                    account_id: ""
                });
            },
            onError: (errors) => {
                setErrors(errors);
            }
        });
    };

    // Edit form handlers
    const openEditDialog = (tax) => {
        setEditingTax(tax);
        setForm({
            name: tax.name,
            rate: tax.rate.toString(),
            tax_number: tax.tax_number,
            account_id: tax.account_id
        });
        setErrors({});
        setIsEditDialogOpen(true);
    };

    const handleEditSubmit = (e) => {
        e.preventDefault();

        router.put(route("taxes.update", editingTax.id), form, {
            preserveState: true,
            onSuccess: () => {
                setIsEditDialogOpen(false);
                setEditingTax(null);
            },
            onError: (errors) => {
                setErrors(errors);
            }
        });
    };

    // Form input change handler
    const handleInputChange = (e) => {
        const { name, value } = e.target;
        setForm({
            ...form,
            [name]: value
        });
    };
    return (
        <AuthenticatedLayout>
            <Head title="Tax" />
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Tax"
                        subpage="List"
                        url="taxes.index"
                    />
                    <div className="p-4">
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div className="flex flex-col md:flex-row gap-4">
                                <Button onClick={openCreateDialog}>
                                    <Plus className="w-4 h-4 mr-2" />
                                    Add Tax
                                </Button>
                            </div>
                            <div className="flex flex-col md:flex-row gap-4 md:items-center">
                                <form onSubmit={handleSearch} className="flex gap-2">
                                    <Input
                                        placeholder="Search tax..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="w-full md:w-80"
                                    />
                                    <Button type="submit">
                                        <Search className="w-4 h-4 mr-2" />
                                        Search
                                    </Button>
                                </form>
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
                                        <TableHead className="w-[50px]">ID</TableHead>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Tax rate</TableHead>
                                        <TableHead>Tax Number</TableHead>
                                        <TableHead>Account</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {taxs.length > 0 ? (
                                        taxs.map((tax) => (
                                            <TableRow key={tax.id}>
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selectedMethods.includes(tax.id)}
                                                        onCheckedChange={() => toggleSelectMethod(tax.id)}
                                                    />
                                                </TableCell>
                                                <TableCell>{tax.id}</TableCell>
                                                <TableCell>{tax.name}</TableCell>
                                                <TableCell>{tax.rate}</TableCell>
                                                <TableCell>{tax.tax_number}</TableCell>
                                                <TableCell>{tax.account?.account_name}</TableCell>
                                                <TableCell className="text-right">
                                                    <TableActions
                                                        actions={[
                                                            {
                                                                label: "Edit",
                                                                icon: <Edit className="h-4 w-4" />,
                                                                onClick: () => openEditDialog(tax),
                                                            },
                                                            {
                                                                label: "Delete",
                                                                icon: <Trash className="h-4 w-4" />,
                                                                onClick: () => handleDeleteConfirm(tax.id),
                                                                destructive: true,
                                                            },
                                                        ]}
                                                    />
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={5} className="h-24 text-center">
                                                No tax found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {meta.last_page > 1 && (
                            <div className="flex justify-between items-center mt-4">
                                <div className="text-sm text-gray-500">
                                    Showing {meta.from || 0} to {meta.to || 0} of {meta.total} entries
                                </div>
                                <div className="flex gap-1">{renderPageNumbers()}</div>
                            </div>
                        )}
                    </div>

                    {/* Create Dialog */}
                    <Dialog open={isCreateDialogOpen} onOpenChange={setIsCreateDialogOpen}>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Add Tax</DialogTitle>
                            </DialogHeader>
                            <form onSubmit={handleCreateSubmit}>
                                <div className="grid gap-4 py-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Name</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            value={form.name}
                                            onChange={handleInputChange}
                                            placeholder="Enter transaction method name"
                                        />
                                        {errors.name && (
                                            <p className="text-sm text-red-500">{errors.name}</p>
                                        )}
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="rate">Tax rate</Label>
                                        <Input
                                            id="rate"
                                            name="rate"
                                            value={form.rate}
                                            onChange={handleInputChange}
                                            placeholder="Enter tax rate"
                                        />
                                        {errors.rate && (
                                            <p className="text-sm text-red-500">{errors.rate}</p>
                                        )}
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="tax_number">Tax number</Label>
                                        <Input
                                            id="tax_number"
                                            name="tax_number"
                                            value={form.tax_number}
                                            onChange={handleInputChange}
                                            placeholder="Enter tax number"
                                        />
                                        {errors.tax_number && (
                                            <p className="text-sm text-red-500">{errors.tax_number}</p>
                                        )}
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="account_id">Account</Label>
                                        <SearchableCombobox
                                            placeholder="Select Account"
                                            value={form.account_id}
                                            onChange={(value) => setForm({ ...form, account_id: value })}
                                            options={accounts}
                                            optionLabel="account_name"
                                            optionValue="id"
                                        />
                                        {errors.account_id && (
                                            <p className="text-sm text-red-500">{errors.account_id}</p>
                                        )}
                                    </div>
                                </div>
                                <DialogFooter>
                                    <Button type="button" variant="outline" onClick={() => setIsCreateDialogOpen(false)}>
                                        Cancel
                                    </Button>
                                    <Button type="submit">Save</Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>

                    {/* Edit Dialog */}
                    <Dialog open={isEditDialogOpen} onOpenChange={setIsEditDialogOpen}>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Edit Tax</DialogTitle>
                            </DialogHeader>
                            <form onSubmit={handleEditSubmit}>
                                <div className="grid gap-4 py-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="name">Name</Label>
                                        <Input
                                            id="name"
                                            name="name"
                                            value={form.name}
                                            onChange={handleInputChange}
                                            placeholder="Enter transaction method name"
                                        />
                                        {errors.name && (
                                            <p className="text-sm text-red-500">{errors.name}</p>
                                        )}
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="rate">Tax rate</Label>
                                        <Input
                                            id="rate"
                                            name="rate"
                                            value={form.rate}
                                            onChange={handleInputChange}
                                            placeholder="Enter tax rate"
                                        />
                                        {errors.rate && (
                                            <p className="text-sm text-red-500">{errors.rate}</p>
                                        )}
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="tax_number">Tax number</Label>
                                        <Input
                                            id="tax_number"
                                            name="tax_number"
                                            value={form.tax_number}
                                            onChange={handleInputChange}
                                            placeholder="Enter tax number"
                                        />
                                        {errors.tax_number && (
                                            <p className="text-sm text-red-500">{errors.tax_number}</p>
                                        )}
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="account_id">Account</Label>
                                        <SearchableCombobox
                                            placeholder="Select Account"
                                            value={form.account_id}
                                            onChange={(value) => setForm({ ...form, account_id: value })}
                                            options={accounts}
                                            optionLabel="account_name"
                                            optionValue="id"
                                        />
                                        {errors.account_id && (
                                            <p className="text-sm text-red-500">{errors.account_id}</p>
                                        )}
                                    </div>
                                </div>
                                <DialogFooter>
                                    <Button type="button" variant="outline" onClick={() => setIsEditDialogOpen(false)}>
                                        Cancel
                                    </Button>
                                    <Button type="submit">Update</Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>

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
                        count={selectedMethods.length}
                    />
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}

import React, { useState, useCallback, useEffect } from "react";
import { router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Badge } from "@/Components/ui/badge";
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
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import { DrawerComponent } from "@/Components/DrawerComponent";
import Modal from "@/Components/Modal";
import { Edit, Trash, Plus, ChevronUp, ChevronDown } from "lucide-react";
import { Label } from "@/Components/ui/label";

// Delete Confirmation Modal Component
const DeleteRoleModal = ({ show, onClose, onConfirm, processing, roleName }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete the role "{roleName}"?
            </h2>
            <p className="text-sm text-muted-foreground mt-2">
                This action cannot be undone.
            </p>
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
                    {processing ? "Deleting..." : "Delete"}
                </Button>
            </div>
        </form>
    </Modal>
);

// Bulk Delete Confirmation Modal Component
const DeleteAllRolesModal = ({ show, onClose, onConfirm, processing, count }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete {count} selected role{count !== 1 ? 's' : ''}?
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

export default function Roles({ roles = [], permissions = {} }) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();

    // Selection state
    const [selectedRoles, setSelectedRoles] = useState([]);
    const [isAllSelected, setIsAllSelected] = useState(false);
    const [search, setSearch] = useState("");
    const [perPage, setPerPage] = useState(10);
    const [currentPage, setCurrentPage] = useState(1);
    const [bulkAction, setBulkAction] = useState("");
    const [sorting, setSorting] = useState({ column: "name", direction: "asc" });

    // Drawer state
    const [isDrawerOpen, setIsDrawerOpen] = useState(false);
    const [editingRole, setEditingRole] = useState(null);
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Delete dialog state
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [deletingRole, setDeletingRole] = useState(null);
    const [processing, setProcessing] = useState(false);

    // Form state
    const [formData, setFormData] = useState({ name: "" });
    const [selectedPermissions, setSelectedPermissions] = useState([]);
    const [formErrors, setFormErrors] = useState({});

    // Show flash messages
    useEffect(() => {
        if (flash.success) {
            toast({
                title: "Success",
                description: flash.success,
            });
        }
        if (flash.error) {
            toast({
                title: "Error",
                description: flash.error,
                variant: "destructive",
            });
        }
    }, [flash, toast]);

    // Filter and sort roles
    const filteredAndSortedRoles = React.useMemo(() => {
        let filtered = roles.filter(role =>
            role.name.toLowerCase().includes(search.toLowerCase())
        );

        // Sort
        filtered.sort((a, b) => {
            let aVal = a[sorting.column];
            let bVal = b[sorting.column];

            if (sorting.column === "permissions_count" || sorting.column === "users_count") {
                aVal = Number(aVal) || 0;
                bVal = Number(bVal) || 0;
            }

            if (aVal < bVal) return sorting.direction === "asc" ? -1 : 1;
            if (aVal > bVal) return sorting.direction === "asc" ? 1 : -1;
            return 0;
        });

        return filtered;
    }, [roles, search, sorting]);

    // Paginate
    const paginatedRoles = React.useMemo(() => {
        const startIndex = (currentPage - 1) * perPage;
        return filteredAndSortedRoles.slice(startIndex, startIndex + perPage);
    }, [filteredAndSortedRoles, currentPage, perPage]);

    const totalPages = Math.ceil(filteredAndSortedRoles.length / perPage);

    // Selection handlers
    const toggleSelectAll = () => {
        if (isAllSelected) {
            setSelectedRoles([]);
        } else {
            setSelectedRoles(paginatedRoles.map((role) => role.id));
        }
        setIsAllSelected(!isAllSelected);
    };

    const toggleSelectRole = (id) => {
        if (selectedRoles.includes(id)) {
            setSelectedRoles(selectedRoles.filter((roleId) => roleId !== id));
            setIsAllSelected(false);
        } else {
            setSelectedRoles([...selectedRoles, id]);
            if (selectedRoles.length + 1 === paginatedRoles.length) {
                setIsAllSelected(true);
            }
        }
    };

    // Search handler
    const handleSearch = (e) => {
        setSearch(e.target.value);
        setCurrentPage(1);
    };

    // Pagination handlers
    const handlePerPageChange = (value) => {
        setPerPage(Number(value));
        setCurrentPage(1);
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
    };

    // Sorting handler
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
                    className={`w-3 h-3 ${isActive && sorting.direction === "asc" ? "text-gray-800" : "text-gray-300"}`}
                />
                <ChevronDown
                    className={`w-3 h-3 -mt-1 ${isActive && sorting.direction === "desc" ? "text-gray-800" : "text-gray-300"}`}
                />
            </span>
        );
    };

    // Bulk action handler
    const handleBulkAction = () => {
        if (bulkAction === "") return;

        if (selectedRoles.length === 0) {
            toast({
                variant: "destructive",
                title: "Error",
                description: "Please select at least one role",
            });
            return;
        }

        if (bulkAction === "delete") {
            setShowDeleteAllModal(true);
        }
    };

    // Drawer handlers
    const handleOpenDrawer = useCallback((role) => {
        setEditingRole(role ?? null);
        setFormData({
            name: role?.name ?? ""
        });
        setSelectedPermissions(role?.permissions ?? []);
        setFormErrors({});
        setIsDrawerOpen(true);
    }, []);

    const handleCloseDrawer = useCallback(() => {
        setIsDrawerOpen(false);
        setTimeout(() => {
            setEditingRole(null);
            setFormData({ name: "" });
            setSelectedPermissions([]);
            setFormErrors({});
        }, 300);
    }, []);

    const handleSubmit = useCallback((e) => {
        e.preventDefault();

        const url = editingRole
            ? `/business/roles/${editingRole.id}`
            : "/business/roles";

        const method = editingRole ? "put" : "post";

        router[method](url, {
            ...formData,
            permissions: selectedPermissions
        }, {
            preserveScroll: true,
            onStart: () => setIsSubmitting(true),
            onSuccess: () => {
                handleCloseDrawer();
            },
            onError: (errors) => {
                setFormErrors(errors);
            },
            onFinish: () => {
                setIsSubmitting(false);
            }
        });
    }, [editingRole, formData, selectedPermissions, handleCloseDrawer]);

    // Delete handlers
    const handleDeleteConfirm = (role) => {
        setDeletingRole(role);
        setShowDeleteModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.delete(`/business/roles/${deletingRole.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                setShowDeleteModal(false);
                setDeletingRole(null);
                setProcessing(false);
            },
            onError: () => {
                setProcessing(false);
            }
        });
    };

    const handleDeleteAll = (e) => {
        e.preventDefault();
        setProcessing(true);

        // For now, delete each role individually
        // You can create a bulk delete endpoint in the backend if needed
        Promise.all(
            selectedRoles.map(id =>
                new Promise((resolve) => {
                    router.delete(`/business/roles/${id}`, {
                        preserveScroll: true,
                        onSuccess: resolve,
                        onError: resolve,
                    });
                })
            )
        ).then(() => {
            setShowDeleteAllModal(false);
            setSelectedRoles([]);
            setIsAllSelected(false);
            setProcessing(false);
        });
    };

    const handleTogglePermission = useCallback((permissionName) => {
        setSelectedPermissions(prev => {
            if (prev.includes(permissionName)) {
                return prev.filter(p => p !== permissionName);
            } else {
                return [...prev, permissionName];
            }
        });
    }, []);

    // Pagination render
    const renderPageNumbers = () => {
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

    return (
        <AuthenticatedLayout>
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Settings"
                        subpage="Roles"
                        url="business.roles"
                    />
                    <div className="p-4">
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div className="flex flex-col md:flex-row gap-2">
                                <Button onClick={() => handleOpenDrawer()}>
                                    <Plus className="w-4 h-4 mr-2" />
                                    Create Role
                                </Button>
                            </div>
                            <div className="flex flex-col md:flex-row gap-4 md:items-center">
                                <Input
                                    placeholder="Search roles..."
                                    value={search}
                                    onChange={handleSearch}
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
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("name")}>
                                            Role Name {renderSortIcon("name")}
                                        </TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("permissions_count")}>
                                            Permissions {renderSortIcon("permissions_count")}
                                        </TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("users_count")}>
                                            Users {renderSortIcon("users_count")}
                                        </TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {paginatedRoles.length > 0 ? (
                                        paginatedRoles.map((role) => (
                                            <TableRow key={role.id}>
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selectedRoles.includes(role.id)}
                                                        onCheckedChange={() => toggleSelectRole(role.id)}
                                                        disabled={role.name === "Owner"}
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <div className="font-medium">{role.name}</div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="secondary">
                                                        {role.permissions_count} permissions
                                                    </Badge>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-muted-foreground">
                                                        {role.users_count} users
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <TableActions
                                                        actions={[
                                                            {
                                                                label: "Edit",
                                                                icon: <Edit className="h-4 w-4" />,
                                                                onClick: () => handleOpenDrawer(role),
                                                            },
                                                            {
                                                                label: "Delete",
                                                                icon: <Trash className="h-4 w-4" />,
                                                                onClick: () => handleDeleteConfirm(role),
                                                                destructive: true,
                                                                disabled: role.name === "Owner",
                                                            },
                                                        ]}
                                                    />
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={5} className="h-24 text-center">
                                                No roles found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {paginatedRoles.length > 0 && filteredAndSortedRoles.length > 0 && (
                            <div className="flex items-center justify-between mt-4">
                                <div className="text-sm text-gray-500">
                                    Showing {(currentPage - 1) * perPage + 1} to {Math.min(currentPage * perPage, filteredAndSortedRoles.length)} of {filteredAndSortedRoles.length} entries
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
                                        disabled={currentPage === totalPages}
                                    >
                                        Next
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handlePageChange(totalPages)}
                                        disabled={currentPage === totalPages}
                                    >
                                        Last
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </SidebarInset>

            {/* Create/Edit Drawer */}
            <DrawerComponent
                open={isDrawerOpen}
                onOpenChange={(open) => {
                    if (!open) {
                        handleCloseDrawer();
                    }
                }}
                title={editingRole ? "Edit Role" : "Create Role"}
                position="right"
                width="w-[600px]"
            >
                <div className="flex flex-col h-full">
                    <form onSubmit={handleSubmit} className="flex flex-col h-full">
                        {/* Form Content */}
                        <div className="flex-1 overflow-y-auto p-4 space-y-6">
                            <div>
                                <Label htmlFor="name" className="text-sm font-medium">
                                    Role Name <span className="text-destructive">*</span>
                                </Label>
                                <Input
                                    id="name"
                                    name="name"
                                    placeholder="Enter role name"
                                    value={formData.name}
                                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                    disabled={isSubmitting}
                                    className={`mt-2 ${formErrors.name ? 'border-destructive focus:ring-destructive' : ''}`}
                                />
                                {formErrors.name && (
                                    <p className="text-destructive text-sm mt-1">{formErrors.name}</p>
                                )}
                            </div>

                            <div>
                                <Label className="text-sm font-medium mb-3 block">
                                    Permissions
                                </Label>
                                <div className="space-y-4">
                                    {Object.entries(permissions).map(([group, perms]) => (
                                        <div key={group} className="space-y-2">
                                            <h4 className="font-semibold text-sm capitalize">
                                                {group}
                                            </h4>
                                            <div className="space-y-2 pl-2">
                                                {perms.map((perm) => (
                                                    <label
                                                        key={perm.id}
                                                        className="flex items-center gap-2 cursor-pointer hover:bg-muted p-2 rounded"
                                                    >
                                                        <Checkbox
                                                            checked={selectedPermissions.includes(perm.name)}
                                                            onCheckedChange={() => handleTogglePermission(perm.name)}
                                                            disabled={isSubmitting}
                                                        />
                                                        <span className="text-sm">
                                                            {perm.name.replace(`${group}.`, "")}
                                                        </span>
                                                    </label>
                                                ))}
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </div>

                        {/* Footer */}
                        <div className="sticky bottom-0 bg-background border-t p-4 flex gap-2 justify-end">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={handleCloseDrawer}
                                disabled={isSubmitting}
                            >
                                Cancel
                            </Button>
                            <Button
                                type="submit"
                                disabled={isSubmitting}
                            >
                                {isSubmitting ? "Saving..." : (editingRole ? "Update Role" : "Create Role")}
                            </Button>
                        </div>
                    </form>
                </div>
            </DrawerComponent>

            {/* Delete Confirmation Modal */}
            <DeleteRoleModal
                show={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                onConfirm={handleDelete}
                processing={processing}
                roleName={deletingRole?.name}
            />

            {/* Bulk Delete Confirmation Modal */}
            <DeleteAllRolesModal
                show={showDeleteAllModal}
                onClose={() => setShowDeleteAllModal(false)}
                onConfirm={handleDeleteAll}
                processing={processing}
                count={selectedRoles.length}
            />
        </AuthenticatedLayout>
    );
}

import { useState, useEffect, useCallback, useMemo } from "react";
import { router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Button } from "@/Components/ui/button";
import { Input } from "@/Components/ui/input";
import { Checkbox } from "@/Components/ui/checkbox";
import { Badge } from "@/Components/ui/badge";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import { DrawerComponent, DrawerFooter } from "@/Components/DrawerComponent";
import Modal from "@/Components/Modal";
import { Edit, Trash, Plus } from "lucide-react";
import { Label } from "@/Components/ui/label";

export default function Roles({ roles = [], permissions = {} }) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();

    // Drawer state
    const [isDrawerOpen, setIsDrawerOpen] = useState(false);
    const [editingRole, setEditingRole] = useState(null);
    const [isSubmitting, setIsSubmitting] = useState(false);

    // Delete modal state
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [deletingRole, setDeletingRole] = useState(null);
    const [isDeleting, setIsDeleting] = useState(false);

    // Form state
    const [formData, setFormData] = useState({ name: "" });
    const [selectedPermissions, setSelectedPermissions] = useState([]);
    const [formErrors, setFormErrors] = useState({});

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

    // Drawer handlers
    const handleOpenDrawer = useCallback((role = null) => {
        setEditingRole(role);
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
            ? route("business.roles.update", editingRole.id)
            : route("business.roles.store");
        
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
    const handleOpenDeleteModal = useCallback((role) => {
        setDeletingRole(role);
        setShowDeleteModal(true);
    }, []);

    const handleCloseDeleteModal = useCallback(() => {
        setShowDeleteModal(false);
        setTimeout(() => {
            setDeletingRole(null);
        }, 300);
    }, []);

    const handleConfirmDelete = useCallback((e) => {
        e.preventDefault();
        if (!deletingRole) return;

        setIsDeleting(true);
        router.delete(route("business.roles.destroy", deletingRole.id), {
            preserveScroll: true,
            onFinish: () => {
                setIsDeleting(false);
                handleCloseDeleteModal();
            }
        });
    }, [deletingRole, handleCloseDeleteModal]);

    const handleTogglePermission = useCallback((permissionName) => {
        setSelectedPermissions(prev => {
            if (prev.includes(permissionName)) {
                return prev.filter(p => p !== permissionName);
            } else {
                return [...prev, permissionName];
            }
        });
    }, []);

    // Toggle all permissions in a group
    const handleToggleGroup = useCallback((group, perms) => {
        const permNames = perms.map(p => p.name);
        const allSelected = permNames.every(name => selectedPermissions.includes(name));
        
        setSelectedPermissions(prev => {
            if (allSelected) {
                return prev.filter(p => !permNames.includes(p));
            } else {
                return [...new Set([...prev, ...permNames])];
            }
        });
    }, [selectedPermissions]);

    return (
        <AuthenticatedLayout>
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Settings"
                        subpage="Roles"
                        url="settings.roles"
                    />
                    <div className="p-4">
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div className="flex flex-col md:flex-row gap-4">
                                <Button onClick={() => handleOpenDrawer()}>
                                    <Plus className="w-4 h-4 mr-2" />
                                    Create Role
                                </Button>
                            </div>
                        </div>

                        <div className="rounded-md border">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Role Name</TableHead>
                                        <TableHead>Permissions</TableHead>
                                        <TableHead>Users</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {roles.length > 0 ? (
                                        roles.map((role) => (
                                            <TableRow key={role.id}>
                                                <TableCell>
                                                    <span className="font-medium">{role.name}</span>
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
                                                            ...(role.name !== 'Owner' ? [{
                                                                label: "Delete",
                                                                icon: <Trash className="h-4 w-4" />,
                                                                onClick: () => handleOpenDeleteModal(role),
                                                                destructive: true,
                                                            }] : []),
                                                        ]}
                                                    />
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={4} className="h-24 text-center">
                                                No roles found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                </div>
            </SidebarInset>

            {/* Create/Edit Role Drawer */}
            <DrawerComponent
                open={isDrawerOpen}
                onOpenChange={handleCloseDrawer}
                title={editingRole ? "Edit Role" : "Create Role"}
                position="right"
                width="w-[500px]"
            >
                <form id="role-form" onSubmit={handleSubmit} className="space-y-6 p-4">
                    <div>
                        <Label className="block font-medium text-sm mb-2">
                            Role Name <span className="text-destructive">*</span>
                        </Label>
                        <Input
                            type="text"
                            name="name"
                            placeholder="Enter role name"
                            value={formData.name}
                            onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                            disabled={isSubmitting || editingRole?.name === 'Owner'}
                            className={formErrors.name ? 'border-destructive' : ''}
                        />
                        {formErrors.name && (
                            <p className="text-destructive text-sm mt-1">{formErrors.name}</p>
                        )}
                    </div>

                    <div>
                        <Label className="block font-medium text-sm mb-3">
                            Permissions
                        </Label>
                        <div className="space-y-4 max-h-[500px] overflow-y-auto">
                            {Object.entries(permissions).map(([group, perms]) => {
                                const permNames = perms.map(p => p.name);
                                const allSelected = permNames.every(name => selectedPermissions.includes(name));
                                const someSelected = permNames.some(name => selectedPermissions.includes(name));
                                
                                return (
                                    <div key={group} className="space-y-2">
                                        <div className="flex items-center gap-2">
                                            <Checkbox
                                                checked={allSelected}
                                                onCheckedChange={() => handleToggleGroup(group, perms)}
                                                disabled={isSubmitting}
                                                className={someSelected && !allSelected ? 'opacity-50' : ''}
                                            />
                                            <h4 className="font-semibold text-sm capitalize cursor-pointer" onClick={() => handleToggleGroup(group, perms)}>
                                                {group}
                                            </h4>
                                        </div>
                                        <div className="space-y-2 pl-6">
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
                                );
                            })}
                        </div>
                    </div>
                </form>
                <DrawerFooter className="flex gap-2 justify-end">
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
                        form="role-form"
                        disabled={isSubmitting}
                    >
                        {isSubmitting ? "Processing..." : (editingRole ? "Update Role" : "Create Role")}
                    </Button>
                </DrawerFooter>
            </DrawerComponent>

            {/* Delete Confirmation Modal */}
            <Modal show={showDeleteModal} onClose={handleCloseDeleteModal}>
                <form onSubmit={handleConfirmDelete}>
                    <h2 className="text-lg font-medium">
                        Are you sure you want to delete the role "{deletingRole?.name}"?
                    </h2>
                    <p className="text-sm text-muted-foreground mt-2">
                        This action cannot be undone.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={handleCloseDeleteModal}
                            disabled={isDeleting}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            variant="destructive"
                            disabled={isDeleting}
                        >
                            {isDeleting ? "Deleting..." : "Delete"}
                        </Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}

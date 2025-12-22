import React, { useState, useEffect, useCallback, useMemo } from "react";
import { router, usePage, Link } from "@inertiajs/react";
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
import { Edit, Trash, Plus, ChevronUp, ChevronDown, Building, Mail } from "lucide-react";
import { Label } from "@/Components/ui/label";
import { RadioGroup, RadioGroupItem } from "@/Components/ui/radio-group";
import { SearchableMultiSelectCombobox } from "@/Components/ui/searchable-multiple-combobox";
import { Textarea } from "@/Components/ui/textarea";

// Delete Confirmation Modal Component
const DeleteUserModal = ({ show, onClose, onConfirm, processing, userName }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete {userName}?
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
const DeleteAllUsersModal = ({ show, onClose, onConfirm, processing, count }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete {count} selected user{count !== 1 ? 's' : ''}?
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

export default function UserManagement({ users = [], roles = [], permissions = {}, ownerBusinesses = [], pendingInvitationsCount = 0, meta = {}, filters = {} }) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();

    const [selectedUsers, setSelectedUsers] = useState([]);
    const [isAllSelected, setIsAllSelected] = useState(false);
    const [search, setSearch] = useState(filters.search || "");
    const [perPage, setPerPage] = useState(meta.per_page || 50);
    const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
    const [bulkAction, setBulkAction] = useState("");
    const [sorting, setSorting] = useState(filters.sorting || { column: "created_at", direction: "desc" });

    // Delete modal states
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [userToDelete, setUserToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);

    // Assign Business modal state
    const [showAssignBusinessModal, setShowAssignBusinessModal] = useState(false);
    const [assignBusinessUser, setAssignBusinessUser] = useState(null);
    const [selectedBusinessIds, setSelectedBusinessIds] = useState([]);
    const [isAssigning, setIsAssigning] = useState(false);

    // Edit drawer state (Manage Roles & Permissions)
    const [isEditDrawerOpen, setIsEditDrawerOpen] = useState(false);
    const [editingUser, setEditingUser] = useState(null);
    const [selectedRole, setSelectedRole] = useState("");
    const [selectedPermissions, setSelectedPermissions] = useState([]);
    const [isSaving, setIsSaving] = useState(false);

    // Invite drawer state
    const [isCreateDrawerOpen, setIsCreateDrawerOpen] = useState(false);
    const [isCreating, setIsCreating] = useState(false);
    const [createFormData, setCreateFormData] = useState({
        email: "",
        message: ""
    });
    const [createFormErrors, setCreateFormErrors] = useState({});
    const [selectedCreateRole, setSelectedCreateRole] = useState("");
    const [selectedInviteBusinessIds, setSelectedInviteBusinessIds] = useState([]);

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

    // Count super admins for protection
    const ownerCount = useMemo(() => {
        return users.filter(u => u.is_owner).length;
    }, [users]);

    // Selection handlers
    const toggleSelectAll = () => {
        if (isAllSelected) {
            setSelectedUsers([]);
        } else {
            setSelectedUsers(users.map((user) => user.id));
        }
        setIsAllSelected(!isAllSelected);
    };

    const toggleSelectUser = (id) => {
        if (selectedUsers.includes(id)) {
            setSelectedUsers(selectedUsers.filter((userId) => userId !== id));
            setIsAllSelected(false);
        } else {
            setSelectedUsers([...selectedUsers, id]);
            if (selectedUsers.length + 1 === users.length) {
                setIsAllSelected(true);
            }
        }
    };

    // Search handler
    const handleSearch = (e) => {
        const value = e.target.value;
        setSearch(value);

        router.get(
            route("business.user-management"),
            { search: value, page: 1, per_page: perPage, sorting },
            { preserveState: true }
        );
    };

    // Pagination handlers
    const handlePerPageChange = (value) => {
        setPerPage(value);
        router.get(
            route("business.user-management"),
            { search, page: 1, per_page: value, sorting },
            { preserveState: true }
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("business.user-management"),
            { search, page, per_page: perPage, sorting },
            { preserveState: true }
        );
    };

    // Sorting handler
    const handleSort = (column) => {
        let direction = "asc";
        if (sorting.column === column && sorting.direction === "asc") {
            direction = "desc";
        }
        setSorting({ column, direction });
        router.get(
            route("business.user-management"),
            { search, page: 1, per_page: perPage, sorting: { column, direction } },
            { preserveState: true }
        );
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

        if (selectedUsers.length === 0) {
            toast({
                variant: "destructive",
                title: "Error",
                description: "Please select at least one user",
            });
            return;
        }

        if (bulkAction === "delete") {
            setShowDeleteAllModal(true);
        }
    };

    // Delete handlers
    const handleDeleteConfirm = (user) => {
        setUserToDelete(user);
        setShowDeleteModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.delete(route('business.users.destroy', userToDelete.id), {
            onSuccess: () => {
                setShowDeleteModal(false);
                setUserToDelete(null);
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

        router.post(route('business.users.bulk-destroy'),
            {
                ids: selectedUsers
            },
            {
                onSuccess: () => {
                    setShowDeleteAllModal(false);
                    setSelectedUsers([]);
                    setIsAllSelected(false);
                    setProcessing(false);
                },
                onError: () => {
                    setProcessing(false);
                }
            }
        );
    };

    // Assign Business handlers
    const handleOpenAssignBusinessModal = useCallback((user) => {
        setAssignBusinessUser(user);
        // Pre-select businesses the user already has access to
        setSelectedBusinessIds(user.businesses?.map(b => String(b.id)) || []);
        setShowAssignBusinessModal(true);
    }, []);

    const handleCloseAssignBusinessModal = useCallback(() => {
        setShowAssignBusinessModal(false);
        setTimeout(() => {
            setAssignBusinessUser(null);
            setSelectedBusinessIds([]);
        }, 300);
    }, []);

    const handleAssignBusinesses = (e) => {
        e.preventDefault();
        setIsAssigning(true);

        router.post(route('business.users.assign-businesses', assignBusinessUser.id),
            {
                business_ids: selectedBusinessIds.map(id => parseInt(id))
            },
            {
                onSuccess: () => {
                    handleCloseAssignBusinessModal();
                    setIsAssigning(false);
                },
                onError: () => {
                    setIsAssigning(false);
                }
            }
        );
    };

    // Edit drawer handlers
    const handleOpenEditDrawer = useCallback((user) => {
        setEditingUser(user);
        setSelectedRole(user.roles?.[0] || "");
        setSelectedPermissions(user.permissions || []);
        setIsEditDrawerOpen(true);
    }, []);

    const handleCloseEditDrawer = useCallback(() => {
        setIsEditDrawerOpen(false);
        setTimeout(() => {
            setEditingUser(null);
            setSelectedRole("");
            setSelectedPermissions([]);
        }, 300);
    }, []);

    const handleSaveRolesAndPermissions = useCallback(() => {
        if (!editingUser || isSaving) return;

        setIsSaving(true);

        router.post(
            route("business.users.roles-and-permissions", editingUser.id),
            {
                roles: selectedRole ? [selectedRole] : [],
                permissions: selectedPermissions,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    handleCloseEditDrawer();
                },
                onFinish: () => {
                    setIsSaving(false);
                },
            }
        );
    }, [editingUser, selectedRole, selectedPermissions, handleCloseEditDrawer, isSaving]);

    // Invite drawer handlers
    const handleOpenCreateDrawer = useCallback(() => {
        setCreateFormData({
            email: "",
            message: ""
        });
        setCreateFormErrors({});
        setSelectedCreateRole("");
        setSelectedInviteBusinessIds([]);
        setIsCreateDrawerOpen(true);
    }, []);

    const handleCloseCreateDrawer = useCallback(() => {
        setIsCreateDrawerOpen(false);
        setTimeout(() => {
            setCreateFormData({
                email: "",
                message: ""
            });
            setCreateFormErrors({});
            setSelectedCreateRole("");
            setSelectedInviteBusinessIds([]);
        }, 300);
    }, []);

    const handleSendInvitation = useCallback((e) => {
        e.preventDefault();

        router.post(route("business.users.send-invitation"), {
            email: createFormData.email,
            message: createFormData.message,
            role: selectedCreateRole,
            business_ids: selectedInviteBusinessIds.map(id => parseInt(id)),
        }, {
            preserveScroll: true,
            onStart: () => setIsCreating(true),
            onSuccess: () => {
                handleCloseCreateDrawer();
            },
            onError: (errors) => {
                setCreateFormErrors(errors);
            },
            onFinish: () => {
                setIsCreating(false);
            }
        });
    }, [createFormData, selectedCreateRole, selectedInviteBusinessIds, handleCloseCreateDrawer]);

    // Handle role change for edit drawer
    const handleRoleChange = useCallback((roleName) => {
        // Get permissions from the new role
        const role = roles.find(r => r.name === roleName);
        const rolePermissions = role?.permissions || [];
        
        // Set the new role and its permissions
        setSelectedRole(roleName);
        setSelectedPermissions(rolePermissions);
    }, [roles]);

    // Handle role change for create drawer
    const handleCreateRoleChange = useCallback((roleName) => {
        setSelectedCreateRole(roleName);
    }, []);

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
        const totalPages = meta.last_page || 1;
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
                        page="Business"
                        subpage="User Management"
                        url="business.user-management"
                    />
                    <div className="p-4">
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div className="flex flex-col md:flex-row gap-2">
                                <Button onClick={handleOpenCreateDrawer}>
                                    <Plus className="w-4 h-4 mr-2" />
                                    Invite User
                                </Button>
                                <Link href={route('business.invitations')}>
                                    <Button variant="outline" className="relative">
                                        <Mail className="w-4 h-4 mr-2" />
                                        Invitations
                                        {pendingInvitationsCount > 0 && (
                                            <Badge 
                                                variant="destructive" 
                                                className="absolute -top-2 -right-2 h-5 w-5 flex items-center justify-center p-0 text-xs"
                                            >
                                                {pendingInvitationsCount}
                                            </Badge>
                                        )}
                                    </Button>
                                </Link>
                            </div>
                            <div className="flex flex-col md:flex-row gap-4 md:items-center">
                                <Input
                                    placeholder="Search users..."
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
                                            Name {renderSortIcon("name")}
                                        </TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("email")}>
                                            Email {renderSortIcon("email")}
                                        </TableHead>
                                        <TableHead>Roles</TableHead>
                                        <TableHead>Businesses</TableHead>
                                        <TableHead>Permissions</TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("created_at")}>
                                            Created {renderSortIcon("created_at")}
                                        </TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {users.length > 0 ? (
                                        users.map((user) => (
                                            <TableRow key={user.id}>
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selectedUsers.includes(user.id)}
                                                        onCheckedChange={() => toggleSelectUser(user.id)}
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <div className="font-medium">{user.name}</div>
                                                </TableCell>
                                                <TableCell>{user.email}</TableCell>
                                                <TableCell>
                                                    {user.roles.length === 0 ? (
                                                        <span className="text-muted-foreground">-</span>
                                                    ) : (
                                                        <div className="flex flex-wrap gap-1">
                                                            {user.roles.map((role) => (
                                                                <Badge key={role} variant="secondary">
                                                                    {role}
                                                                </Badge>
                                                            ))}
                                                        </div>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {(!user.businesses || user.businesses.length === 0) ? (
                                                        <span className="text-muted-foreground">-</span>
                                                    ) : (
                                                        <div className="flex flex-wrap gap-1">
                                                            {user.businesses.slice(0, 2).map((business) => (
                                                                <Badge key={business.id} variant="outline">
                                                                    {business.name}
                                                                </Badge>
                                                            ))}
                                                            {user.businesses.length > 2 && (
                                                                <Badge variant="outline">
                                                                    +{user.businesses.length - 2} more
                                                                </Badge>
                                                            )}
                                                        </div>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-muted-foreground">
                                                        {user.permissions.length} permissions
                                                    </span>
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-sm text-muted-foreground">
                                                        {user.created_at || '-'}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <TableActions
                                                        actions={[
                                                            {
                                                                label: "Manage Roles & Permissions",
                                                                icon: <Edit className="h-4 w-4" />,
                                                                onClick: () => handleOpenEditDrawer(user),
                                                            },
                                                            {
                                                                label: "Assign Business",
                                                                icon: <Building className="h-4 w-4" />,
                                                                onClick: () => handleOpenAssignBusinessModal(user),
                                                            },
                                                            {
                                                                label: "Delete",
                                                                icon: <Trash className="h-4 w-4" />,
                                                                onClick: () => handleDeleteConfirm(user),
                                                                destructive: true,
                                                                disabled: user.owner && ownerCount <= 1,
                                                            },
                                                        ]}
                                                    />
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={7} className="h-24 text-center">
                                                No users found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {users.length > 0 && meta.total > 0 && (
                            <div className="flex items-center justify-between mt-4">
                                <div className="text-sm text-gray-500">
                                    Showing {(currentPage - 1) * perPage + 1} to {Math.min(currentPage * perPage, meta.total)} of {meta.total} entries
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
                                        disabled={currentPage === (meta.last_page || 1)}
                                    >
                                        Next
                                    </Button>
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        onClick={() => handlePageChange(meta.last_page || 1)}
                                        disabled={currentPage === (meta.last_page || 1)}
                                    >
                                        Last
                                    </Button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </SidebarInset>

            {/* Manage Roles & Permissions Drawer */}
            <DrawerComponent
                open={isEditDrawerOpen}
                onOpenChange={handleCloseEditDrawer}
                title="Manage Roles & Permissions"
                position="right"
                width="w-[500px]"
            >
                <div className="flex flex-col h-full">
                    <div className="flex-1 overflow-y-auto p-4 space-y-6">
                        {/* User Info */}
                        <div className="bg-muted p-4 rounded-lg">
                            <div className="font-semibold">{editingUser?.name}</div>
                            <div className="text-sm text-muted-foreground">{editingUser?.email}</div>
                        </div>

                        {/* Roles Section */}
                        <div>
                            <Label className="block font-medium text-sm mb-3">
                                Role
                            </Label>
                            <RadioGroup
                                value={selectedRole}
                                onValueChange={handleRoleChange}
                                disabled={isSaving}
                                className="space-y-2"
                            >
                                {roles.filter(role => role.name.toLowerCase() !== 'contact').map((role) => {
                                    const isOwnerRole = role.name === 'Owner';
                                    const isEditingOwner = editingUser?.owner;
                                    const isDisabled = isSaving || (isOwnerRole && isEditingOwner && ownerCount <= 1 && selectedRole === 'Owner');

                                    return (
                                        <label
                                            key={role.id}
                                            className="flex items-center gap-2 cursor-pointer hover:bg-muted p-2 rounded"
                                        >
                                            <RadioGroupItem
                                                value={role.name}
                                                disabled={isDisabled}
                                            />
                                            <span className="text-sm">{role.name}</span>
                                        </label>
                                    );
                                })}
                            </RadioGroup>
                        </div>

                        {/* Direct Permissions Section */}
                        <div>
                            <Label className="block font-medium text-sm mb-3">
                                Direct Permissions
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
                                                        disabled={isSaving}
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
                    <div className="sticky bottom-0 bg-background border-t p-4 flex gap-2 justify-end">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={handleCloseEditDrawer}
                            disabled={isSaving}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="button"
                            onClick={handleSaveRolesAndPermissions}
                            disabled={isSaving}
                        >
                            {isSaving ? "Saving..." : "Save Changes"}
                        </Button>
                    </div>
                </div>
            </DrawerComponent>

            {/* Invite User Drawer */}
            <DrawerComponent
                open={isCreateDrawerOpen}
                onOpenChange={handleCloseCreateDrawer}
                title="Invite User"
                position="right"
                width="w-[500px]"
            >
                <div className="flex flex-col h-full">
                    <form id="invite-user-form" onSubmit={handleSendInvitation} className="flex-1 overflow-y-auto p-4 space-y-4">
                        <div>
                            <Label className="block font-medium text-sm mb-2">
                                Email Address <span className="text-destructive">*</span>
                            </Label>
                            <Input
                                type="email"
                                name="email"
                                placeholder="Enter email address"
                                value={createFormData.email}
                                onChange={(e) => setCreateFormData({ ...createFormData, email: e.target.value })}
                                disabled={isCreating}
                                className={createFormErrors.email ? 'border-destructive' : ''}
                            />
                            {createFormErrors.email && (
                                <p className="text-destructive text-sm mt-1">{createFormErrors.email}</p>
                            )}
                        </div>

                        {/* Businesses Section */}
                        <div>
                            <Label className="block font-medium text-sm mb-2">
                                Businesses <span className="text-destructive">*</span>
                            </Label>
                            <SearchableMultiSelectCombobox
                                options={ownerBusinesses}
                                value={selectedInviteBusinessIds}
                                onChange={setSelectedInviteBusinessIds}
                                placeholder="Select businesses..."
                                emptyMessage="No businesses found."
                            />
                            {createFormErrors.business_ids && (
                                <p className="text-destructive text-sm mt-1">{createFormErrors.business_ids}</p>
                            )}
                        </div>

                        {/* Role Section */}
                        <div>
                            <Label className="block font-medium text-sm mb-3">
                                Role <span className="text-destructive">*</span>
                            </Label>
                            <RadioGroup
                                value={selectedCreateRole}
                                onValueChange={handleCreateRoleChange}
                                disabled={isCreating}
                                className="space-y-2"
                            >
                                {roles.filter(role => role.name.toLowerCase() !== 'contact').map((role) => (
                                    <label
                                        key={role.id}
                                        className="flex items-center gap-2 cursor-pointer hover:bg-muted p-2 rounded"
                                    >
                                        <RadioGroupItem
                                            value={role.name}
                                            disabled={isCreating}
                                        />
                                        <span className="text-sm">{role.name}</span>
                                    </label>
                                ))}
                            </RadioGroup>
                            {createFormErrors.role && (
                                <p className="text-destructive text-sm mt-1">{createFormErrors.role}</p>
                            )}
                        </div>

                        {/* Message Section */}
                        <div>
                            <Label className="block font-medium text-sm mb-2">
                                Message (Optional)
                            </Label>
                            <Textarea
                                name="message"
                                placeholder="Add a personal message to the invitation email..."
                                value={createFormData.message}
                                onChange={(e) => setCreateFormData({ ...createFormData, message: e.target.value })}
                                disabled={isCreating}
                            />
                            {createFormErrors.message && (
                                <p className="text-destructive text-sm mt-1">{createFormErrors.message}</p>
                            )}
                        </div>
                    </form>
                    <div className="sticky bottom-0 bg-background border-t p-4 flex gap-2 justify-end">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={handleCloseCreateDrawer}
                            disabled={isCreating}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            form="invite-user-form"
                            disabled={isCreating}
                        >
                            {isCreating ? "Sending..." : "Send Invitation"}
                        </Button>
                    </div>
                </div>
            </DrawerComponent>

            <DeleteUserModal
                show={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                onConfirm={handleDelete}
                processing={processing}
                userName={userToDelete?.name}
            />

            <DeleteAllUsersModal
                show={showDeleteAllModal}
                onClose={() => setShowDeleteAllModal(false)}
                onConfirm={handleDeleteAll}
                processing={processing}
                count={selectedUsers.length}
            />

            {/* Assign Business Modal */}
            <Modal show={showAssignBusinessModal} onClose={handleCloseAssignBusinessModal}>
                <form onSubmit={handleAssignBusinesses}>
                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">
                        Assign Business Access
                    </h2>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                        Select businesses that <strong>{assignBusinessUser?.name}</strong> should have access to.
                    </p>
                    <div className="mb-4">
                        <Label className="block font-medium text-sm mb-2">
                            Businesses
                        </Label>
                        <SearchableMultiSelectCombobox
                            options={ownerBusinesses}
                            value={selectedBusinessIds}
                            onChange={setSelectedBusinessIds}
                            placeholder="Select businesses..."
                            emptyMessage="No businesses found."
                        />
                    </div>
                    <div className="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="outline"
                            onClick={handleCloseAssignBusinessModal}
                            disabled={isAssigning}
                        >
                            Cancel
                        </Button>
                        <Button
                            type="submit"
                            disabled={isAssigning}
                        >
                            {isAssigning ? "Saving..." : "Save Changes"}
                        </Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}

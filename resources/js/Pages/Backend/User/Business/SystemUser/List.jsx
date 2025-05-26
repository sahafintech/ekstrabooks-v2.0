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
import { Edit, Plus, Trash } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { Label } from "@/Components/ui/label";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { SearchableMultiSelectCombobox } from "@/Components/ui/searchable-multiple-combobox";

// Delete Confirmation Modal Component
const DeleteUserModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete this user?
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
const DeleteAllUsersModal = ({
    show,
    onClose,
    onConfirm,
    processing,
    count,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete {count} selected user
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

// change role modal
const ChangeRoleModal = ({ show, onClose, onConfirm, processing, user, roles, businesses }) => {
    const [data, setData] = useState({
        role_id: user?.pivot?.role_id,
        business_id: [user?.pivot?.business_id],
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        onConfirm(data);
    };

    if (!user) return null;

    return (
        <Modal show={show} onClose={onClose}>
            <form onSubmit={handleSubmit}>
                <h2 className="text-lg font-medium">Change Role for {user.name}</h2>
                
                <div className="grid grid-cols-12 mt-6">
                    <Label
                        htmlFor="role_id"
                        className="md:col-span-2 col-span-12"
                    >
                        Role *
                    </Label>
                    <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                        <SearchableCombobox
                            id="role_id"
                            label="Role"
                            options={[
                                { id: "admin", name: "Admin" },
                                ...roles.map((role) => ({
                                    id: role.id,
                                    name: role.name,
                                })),
                            ]}
                            value={data.role_id}
                            onChange={(value) =>
                                setData({ ...data, role_id: value })
                            }
                            required
                            placeholder="Select Role"
                        />
                    </div>
                </div>

                <div className="grid grid-cols-12 mt-2">
                    <Label
                        htmlFor="business_id"
                        className="md:col-span-2 col-span-12"
                    >
                        Business *
                    </Label>
                    <div className="md:col-span-10 col-span-12 md:mt-0 mt-2">
                        <SearchableMultiSelectCombobox
                            id="business_id"
                            label="Business"
                            options={businesses.map((business) => ({
                                id: business.id,
                                name: business.name,
                            }))}
                            value={data.business_id}
                            onChange={(value) =>
                                setData({ ...data, business_id: value })
                            }
                            required
                            placeholder="Select Business"
                        />
                    </div>
                </div>

                <div className="mt-6 flex justify-end">
                    <Button
                        type="button"
                        variant="secondary"
                        onClick={onClose}
                        className="mr-3"
                    >
                        Cancel
                    </Button>
                    <Button type="submit" variant="default" disabled={processing}>
                        Change Role
                    </Button>
                </div>
            </form>
        </Modal>
    );
};

export default function List({
    business = {},
    users = {},
    meta = {},
    filters = {},
    roles = [],
    businesses = [],
}) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [selectedUsers, setSelectedUsers] = useState([]);
    const [isAllSelected, setIsAllSelected] = useState(false);
    const [search, setSearch] = useState(filters.search || "");
    const [perPage, setPerPage] = useState(meta.per_page || 50);
    const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
    const [bulkAction, setBulkAction] = useState("");

    // Delete confirmation modal states
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [roleToDelete, setRoleToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);

    const [showChangeRoleModal, setShowChangeRoleModal] = useState(false);
    const [selectedUser, setSelectedUser] = useState(null);

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

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(
            route("business.users", business.id),
            { search, page: 1, per_page: perPage },
            { preserveState: true }
        );
    };

    const handlePerPageChange = (value) => {
        setPerPage(value);
        router.get(
            route("business.users", business.id),
            { search, page: 1, per_page: value },
            { preserveState: true }
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("business.users", business.id),
            { search, page, per_page: perPage },
            { preserveState: true }
        );
    };

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

    const handleDeleteConfirm = (id) => {
        setRoleToDelete(id);
        setShowDeleteModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.delete(route("system_users.destroy", roleToDelete), {
            onSuccess: () => {
                setShowDeleteModal(false);
                setRoleToDelete(null);
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
            route("user.bulk_action"),
            {
                delete_users: selectedUsers.join(","),
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
                },
            }
        );
    };

    const handleChangeRole = (user) => {
        setSelectedUser(user);
        setShowChangeRoleModal(true);
    };

    const handleRoleChange = (data) => {
        setProcessing(true);
        router.post(
            route("system_users.change_role", [selectedUser.id, business.id]),
            data,
            {
                onSuccess: () => {
                    setShowChangeRoleModal(false);
                    setSelectedUser(null);
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

    return (
        <AuthenticatedLayout>
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Users"
                        subpage="List"
                        url="business.users"
                        params={business.id}
                    />
                    <div className="p-4">
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div className="flex flex-col md:flex-row gap-4">
                                <Link
                                    href={route(
                                        "system_users.invite",
                                        business.id
                                    )}
                                >
                                    <Button>
                                        <Plus className="w-4 h-4 mr-2" />
                                        Invite User
                                    </Button>
                                </Link>
                            </div>
                            <div className="flex flex-col md:flex-row gap-4 md:items-center">
                                <form
                                    onSubmit={handleSearch}
                                    className="flex gap-2"
                                >
                                    <Input
                                        placeholder="Search users..."
                                        value={search}
                                        onChange={(e) =>
                                            setSearch(e.target.value)
                                        }
                                        className="w-full md:w-80"
                                    />
                                    <Button type="submit">Search</Button>
                                </form>
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
                                        <TableHead>Name</TableHead>
                                        <TableHead>Business</TableHead>
                                        <TableHead>Role</TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {users.length > 0 ? (
                                        users.map((user) => (
                                            <TableRow key={user.id}>
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selectedUsers.includes(
                                                            user.id
                                                        )}
                                                        onCheckedChange={() =>
                                                            toggleSelectUser(
                                                                user.id
                                                            )
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    {user.name}
                                                </TableCell>
                                                <TableCell>
                                                    {business.name}
                                                </TableCell>
                                                <TableCell>
                                                    {user.pivot.role_id === null
                                                        ? "Admin"
                                                        : user.roles.find(
                                                              (role) =>
                                                                  role.id ===
                                                                  user.pivot
                                                                      .role_id
                                                          ).name}
                                                </TableCell>
                                                {user.pivot.role_id !==
                                                    null && (
                                                    <TableCell className="text-right">
                                                        <TableActions
                                                            actions={[
                                                                {
                                                                    label: "Change Role",
                                                                    icon: (
                                                                        <Edit className="h-4 w-4" />
                                                                    ),
                                                                    onClick: () => handleChangeRole(user),
                                                                },
                                                                {
                                                                    label: "Delete",
                                                                    icon: (
                                                                        <Trash className="h-4 w-4" />
                                                                    ),
                                                                    onClick:
                                                                        () =>
                                                                            handleDeleteConfirm(
                                                                                user.id
                                                                            ),
                                                                    destructive: true,
                                                                },
                                                            ]}
                                                        />
                                                    </TableCell>
                                                )}
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell
                                                colSpan={8}
                                                className="h-24 text-center"
                                            >
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

            <DeleteUserModal
                show={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                onConfirm={handleDelete}
                processing={processing}
            />

            <DeleteAllUsersModal
                show={showDeleteAllModal}
                onClose={() => setShowDeleteAllModal(false)}
                onConfirm={handleDeleteAll}
                processing={processing}
                count={selectedUsers.length}
            />

            <ChangeRoleModal
                show={showChangeRoleModal}
                onClose={() => setShowChangeRoleModal(false)}
                onConfirm={handleRoleChange}
                processing={processing}
                user={selectedUser}
                roles={roles}
                businesses={businesses}
            />
        </AuthenticatedLayout>
    );
}

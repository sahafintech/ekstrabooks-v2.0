import { Button } from "@/Components/ui/button";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Head, Link, router, usePage } from "@inertiajs/react";
import PageHeader from "@/Components/PageHeader";
import { Pencil, Eye, Trash2, LogInIcon, Plus, ChevronUp, ChevronDown } from "lucide-react";
import { useState, useEffect } from "react";
import TableActions from "@/Components/shared/TableActions";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import { Input } from "@/Components/ui/input";
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
import Modal from "@/Components/Modal";

const DeleteUserModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete this user?
            </h2>
            <p className="mt-1 text-sm text-gray-600">
                Once this user is deleted, all of its resources and data will be permanently deleted.
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
                    Delete User
                </Button>
            </div>
        </form>
    </Modal>
);

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

export default function List({ users = [], meta = {}, filters = {} }) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [selectedUsers, setSelectedUsers] = useState([]);
    const [isAllSelected, setIsAllSelected] = useState(false);
    const [search, setSearch] = useState(filters.search || "");
    const [perPage, setPerPage] = useState(meta.per_page || 50);
    const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
    const [bulkAction, setBulkAction] = useState("");
    const [sorting, setSorting] = useState(filters.sorting || { column: "id", direction: "desc" });

    // Delete confirmation modal states
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [showImportModal, setShowImportModal] = useState(false);
    const [userToDelete, setUserToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);

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
        const value = e.target.value;
        setSearch(value);

        router.get(
            route("users.index"),
            { search: value, page: 1, per_page: perPage },
            { preserveState: true }
        );
    };

    const handlePerPageChange = (value) => {
        setPerPage(value);
        router.get(
            route("users.index"),
            { search, page: 1, per_page: value },
            { preserveState: true }
        );
    };

    const UserStatusBadge = ({ status }) => {
        const statusMap = {
            0: {
                label: "Disabled",
                className: "text-red-600 bg-red-200 px-3 py-1 rounded text-xs",
            },
            1: {
                label: "Active",
                className:
                    "text-green-600 bg-green-200 px-3 py-1 rounded text-xs",
            },
        };
    
        return (
            <span className={statusMap[status].className}>
                {statusMap[status].label}
            </span>
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("users.index"),
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
        setUserToDelete(id);
        setShowDeleteModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.delete(route('users.destroy', userToDelete), {
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

        router.post(route('users.bulk_destroy'),
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

    const handleImport = (e) => {
        e.preventDefault();
        const formData = new FormData(e.target);
        setProcessing(true);

        router.post(route('users.import'), formData, {
            onSuccess: () => {
                setShowImportModal(false);
                setProcessing(false);
            },
            onError: () => {
                setProcessing(false);
            }
        });
    };

    const handleSort = (column) => {
        let direction = "asc";
        if (sorting.column === column && sorting.direction === "asc") {
            direction = "desc";
        }
        setSorting({ column, direction });
        router.get(
            route("users.index"),
            { ...filters, sorting: { column, direction } },
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

    return (
        <AuthenticatedLayout>
            <Head title="Users" />
            <Toaster />
            <div className="flex flex-1 flex-col">
                <PageHeader page="Users" subpage="List" url="users.index" />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                        <div className="flex flex-col md:flex-row gap-2">
                            <Link href={route("users.create")}>
                                <Button>
                                    <Plus className="w-4 h-4 mr-2" />
                                    Add User
                                </Button>
                            </Link>
                        </div>
                        <div className="flex flex-col md:flex-row gap-4 md:items-center">
                            <Input
                                placeholder="Search users..."
                                value={search}
                                onChange={(e) => handleSearch(e)}
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
                                    <TableHead className="cursor-pointer" onClick={() => handleSort("id")}>
                                        ID {renderSortIcon("id")}
                                    </TableHead>
                                    <TableHead className="cursor-pointer" onClick={() => handleSort("name")}>
                                        Name {renderSortIcon("name")}
                                    </TableHead>
                                    <TableHead className="cursor-pointer" onClick={() => handleSort("package.name")}>
                                        Package {renderSortIcon("package.name")}
                                    </TableHead>
                                    <TableHead className="cursor-pointer" onClick={() => handleSort("membership_type")}>
                                        Membership {renderSortIcon("membership_type")}
                                    </TableHead>
                                    <TableHead className="cursor-pointer" onClick={() => handleSort("status")}>
                                        Status {renderSortIcon("status")}
                                    </TableHead>
                                    <TableHead className="cursor-pointer" onClick={() => handleSort("valid_to")}>
                                        Valid Until {renderSortIcon("valid_to")}
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
                                            <TableCell>{user.id}</TableCell>
                                            <TableCell>{user.name}</TableCell>
                                            <TableCell>{user.package?.name || "-"}</TableCell>
                                            <TableCell>{user.package?.membership_type || "-"}</TableCell>
                                            <TableCell>
                                                <UserStatusBadge status={user.status} />
                                            </TableCell>
                                            <TableCell>{user.package?.valid_to || "-"}</TableCell>
                                            <TableCell className="text-right">
                                                <TableActions
                                                    actions={[
                                                        {
                                                            label: "View",
                                                            icon: <Eye className="h-4 w-4" />,
                                                            href: route("users.show", user.id),
                                                        },
                                                        {
                                                            label: "Edit",
                                                            icon: <Pencil className="h-4 w-4" />,
                                                            href: route("users.edit", user.id),
                                                        },
                                                        {
                                                            label: "Delete",
                                                            icon: <Trash2 className="h-4 w-4" />,
                                                            onClick: () => handleDeleteConfirm(user.id),
                                                            destructive: true,
                                                        },
                                                        {
                                                            label: "Login As User",
                                                            icon: <LogInIcon className="h-4 w-4" />,
                                                            href: route("users.login_as_user", user.id),
                                                        },
                                                    ]}
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))
                                ) : (
                                    <TableRow>
                                        <TableCell colSpan={8} className="h-24 text-center">
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
                                    disabled={currentPage === meta.last_page}
                                >
                                    Next
                                </Button>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => handlePageChange(meta.last_page)}
                                    disabled={currentPage === meta.last_page}
                                >
                                    Last
                                </Button>
                            </div>
                        </div>
                    )}
                </div>

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
            </div>
        </AuthenticatedLayout>
    );
}

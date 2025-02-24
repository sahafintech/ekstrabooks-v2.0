import { Button } from "@/Components/ui/button";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Link } from "@inertiajs/react";
import PageHeader from "@/Components/PageHeader";
import { MoreVertical, FileUp, FileDown, Pencil, Eye, Trash2, LogInIcon } from "lucide-react";
import Modal from "@/Components/Modal";
import { useState } from "react";
import { useFormSubmit } from "@/hooks/useFormSubmit";
import TableActions from "@/Components/shared/TableActions";
import TableWrapper from "@/Components/shared/TableWrapper";
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";

const DeleteUserModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm} className="p-6">
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

const UserStatusBadge = ({ status }) => (
    <span className={`inline-flex items-center rounded-full px-2 py-1 text-xs font-medium ${
        status === "active" 
            ? "bg-green-50 text-green-700" 
            : "bg-red-50 text-red-700"
    }`}>
        {status}
    </span>
);

export default function List({ users }) {
    const [confirmingUserDeletion, setConfirmingUserDeletion] = useState(false);
    const [selectedUserId, setSelectedUserId] = useState(null);

    const {
        processing,
        handleSubmit: submitForm,
        reset,
        clearErrors,
        errors
    } = useFormSubmit(
        {},
        null, 
        {
            onSuccess: () => closeModal(),
            resetOnSuccess: true,
        }
    );

    const confirmUserDeletion = (id) => {
        setSelectedUserId(id);
        setConfirmingUserDeletion(true);
    };

    const closeModal = () => {
        setConfirmingUserDeletion(false);
        setSelectedUserId(null);
        clearErrors();
        reset();
    };

    const handleDelete = (e) => {
        e.preventDefault();
        if (!selectedUserId) return;
        
        submitForm(e, "delete", route("users.destroy", { user: selectedUserId }));
    };

    const getRowActions = (user) => [
        {
            label: "View",
            icon: Eye,
            onClick: () => window.location = route("users.show", user.id)
        },
        {
            label: "Edit",
            icon: Pencil,
            onClick: () => window.location = route("users.edit", user.id)
        },
        {
            label: "Delete",
            icon: Trash2,
            onClick: () => confirmUserDeletion(user.id),
            className: "text-destructive focus:text-destructive"
        },
        {
            label: "Login As User",
            icon: LogInIcon,
            onClick: () => window.location = route("users.login_as_user", user.id)
        }
    ];

    return (
        <AuthenticatedLayout>
            <div className="flex flex-1 flex-col">
                <PageHeader page="Users" subpage="List" url="users.index" />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <div className="flex justify-between">
                        <Link href={route("users.create")}>
                            <Button>Add New User</Button>
                        </Link>
                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="secondary">
                                    <MoreVertical className="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem>
                                    <FileUp className="mr-2 h-4 w-4" /> Import
                                </DropdownMenuItem>
                                <DropdownMenuItem>
                                    <FileDown className="mr-2 h-4 w-4" /> Export
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <TableWrapper>
                        <Table>
                            <TableCaption>A list of your recent users.</TableCaption>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Name</TableHead>
                                    <TableHead>Package</TableHead>
                                    <TableHead>Membership</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Valid Until</TableHead>
                                    <TableHead className="w-[100px]">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {users.length === 0 ? (
                                    <TableRow>
                                        <TableCell colSpan={6} className="text-center py-8">
                                            No users found
                                        </TableCell>
                                    </TableRow>
                                ) : (
                                    users.map((user) => (
                                        <TableRow key={user.id}>
                                            <TableCell>{user.name}</TableCell>
                                            <TableCell>{user.package?.name || "-"}</TableCell>
                                            <TableCell>{user.membership_type || "-"}</TableCell>
                                            <TableCell>
                                                <UserStatusBadge status={user.status} />
                                            </TableCell>
                                            <TableCell>{user.valid_until || "-"}</TableCell>
                                            <TableCell>
                                                <TableActions actions={getRowActions(user)} />
                                            </TableCell>
                                        </TableRow>
                                    ))
                                )}
                            </TableBody>
                        </Table>
                    </TableWrapper>
                </div>

                <DeleteUserModal 
                    show={confirmingUserDeletion}
                    onClose={closeModal}
                    onConfirm={handleDelete}
                    processing={processing}
                />
            </div>
        </AuthenticatedLayout>
    );
}

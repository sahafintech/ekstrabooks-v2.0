import { Button } from "@/Components/ui/button";
import { SidebarInset } from "@/Components/ui/sidebar";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Link } from "@inertiajs/react";
import PageHeader from "@/Components/PageHeader";
import { MoreVertical } from "lucide-react";
import Modal from "@/Components/Modal";
import { useState } from "react";
import { useForm } from "@inertiajs/react";
import {
    Table,
    TableBody,
    TableCaption,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";

import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuGroup,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/components/ui/dropdown-menu";

export default function List({ users }) {
    const [confirmingUserDeletion, setConfirmingUserDeletion] = useState(false);

    const [user_id, setUserId] = useState(null);

    const confirmUserDeletion = (id) => {
        setConfirmingUserDeletion(true);

        setUserId(id);
    };

    const { delete: destroy, processing, reset, clearErrors } = useForm({});

    const deleteUser = (e) => {
        e.preventDefault();

        destroy(route("users.destroy", user_id), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        setConfirmingUserDeletion(false);

        clearErrors();
        reset();
    };
    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader page="Users" subpage="List" url="users.index" />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <div className="flex">
                        <div>
                            <Link href={route("users.create")}>
                                <Button>Add New User</Button>
                            </Link>
                        </div>
                        <div className="ms-2">
                            <DropdownMenu>
                                <DropdownMenuTrigger asChild>
                                    <Button variant="secondary">
                                        <MoreVertical />
                                    </Button>
                                </DropdownMenuTrigger>
                                <DropdownMenuContent className="w-14">
                                    <DropdownMenuItem>
                                        Import
                                    </DropdownMenuItem>
                                    <DropdownMenuItem>
                                        Export
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </div>
                    </div>
                    <div>
                        <Table>
                            <TableCaption>
                                A list of your recent users.
                            </TableCaption>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="w-[100px]">
                                        Name
                                    </TableHead>
                                    <TableHead>Package</TableHead>
                                    <TableHead>Membership</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Valid Until</TableHead>
                                    <TableHead>Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {users.map((user) => (
                                    <TableRow key={user.id}>
                                        <TableCell>{user.name}</TableCell>
                                        <TableCell>
                                            {user.package?.name || "-"}
                                        </TableCell>
                                        <TableCell>
                                            {user.package?.membership_type ||
                                                "-"}
                                        </TableCell>
                                        <TableCell>{user.status}</TableCell>
                                        <TableCell>{user.valid_to}</TableCell>
                                        <TableCell className="text-right">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger>
                                                    <Button variant="secondary">
                                                        <MoreVertical />
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent>
                                                    <DropdownMenuGroup>
                                                        <Link
                                                            href={route(
                                                                "users.show",
                                                                user.id
                                                            )}
                                                        >
                                                            <DropdownMenuItem>
                                                                View
                                                            </DropdownMenuItem>
                                                        </Link>
                                                        <Link
                                                            href={route(
                                                                "users.edit",
                                                                user.id
                                                            )}
                                                        >
                                                            <DropdownMenuItem>
                                                                Edit
                                                            </DropdownMenuItem>
                                                        </Link>
                                                        <DropdownMenuItem
                                                            onClick={() =>
                                                                confirmUserDeletion(user.id)
                                                            }
                                                        >
                                                            Delete
                                                        </DropdownMenuItem>
                                                    </DropdownMenuGroup>
                                                </DropdownMenuContent>
                                            </DropdownMenu>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>
                </div>
            </SidebarInset>
            <Modal
                show={confirmingUserDeletion}
                onClose={closeModal}
                maxWidth="sm"
            >
                <form onSubmit={deleteUser} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Are you sure you want to delete the user?
                    </h2>

                    <div className="mt-6 flex justify-end">
                        <Button type="button" variant="secondary" onClick={closeModal}>
                            Cancel
                        </Button>

                        <Button
                            variant="destructive"
                            className="ms-3"
                            disabled={processing}
                        >
                            Delete User
                        </Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}

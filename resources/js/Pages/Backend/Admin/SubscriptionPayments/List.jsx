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

import TableWrapper from "@/Components/shared/TableWrapper";
import TableActions from "@/Components/shared/TableActions";
import { Pencil, Trash2 } from "lucide-react";

export default function List({ subscription_payments }) {
    const [confirmingPaymentDeletion, setConfirmingPaymentDeletion] =
        useState(false);
    const [paymentId, setPaymentId] = useState(null);

    const confirmPaymentDeletion = (id) => {
        setConfirmingPaymentDeletion(true);
        setPaymentId(id);
    };

    const { delete: destroy, processing, reset, clearErrors } = useForm({});

    const deletePayment = (e) => {
        e.preventDefault();

        destroy(route("subscription_payments.destroy", paymentId), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        setConfirmingPaymentDeletion(false);
        clearErrors();
        reset();
    };

    const getRowActions = (payment) => [
        {
            label: "Edit",
            icon: Pencil,
            onClick: () =>
                (window.location = route(
                    "subscription_payments.edit",
                    payment.id
                )),
        },
        {
            label: "Delete",
            icon: Trash2,
            onClick: () => confirmPaymentDeletion(payment.id),
            className: "text-destructive focus:text-destructive",
        },
    ];

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader
                    page="Payments"
                    subpage="List"
                    url="subscription_payments.index"
                />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <div className="flex">
                        <div>
                            <Link href={route("subscription_payments.create")}>
                                <Button>Add New Payment</Button>
                            </Link>
                        </div>
                    </div>
                    <div>
                        <TableWrapper>
                            <Table>
                                <TableCaption>
                                    A list of your recent payments.
                                </TableCaption>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>User</TableHead>
                                        <TableHead>Order ID</TableHead>
                                        <TableHead>Method</TableHead>
                                        <TableHead>Package</TableHead>
                                        <TableHead>Amount</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Created By</TableHead>
                                        <TableHead>Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {subscription_payments.map((payment) => (
                                        <TableRow key={payment.id}>
                                            <TableCell>
                                                {payment.user.name}
                                            </TableCell>
                                            <TableCell>
                                                {payment.order_id}
                                            </TableCell>
                                            <TableCell>
                                                {payment.method}
                                            </TableCell>
                                            <TableCell>
                                                {payment.package.name}
                                            </TableCell>
                                            <TableCell>
                                                {payment.amount}
                                            </TableCell>
                                            <TableCell>
                                                {payment.status == 1 ? (
                                                    <span className="text-success">
                                                        Completed
                                                    </span>
                                                ) : (
                                                    <span className="text-danger">
                                                        Pending
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {payment.created_by.name}
                                            </TableCell>
                                            <TableCell>
                                                <TableActions
                                                    actions={getRowActions(
                                                        payment
                                                    )}
                                                />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </TableWrapper>
                    </div>
                </div>
            </SidebarInset>
            <Modal
                show={confirmingPaymentDeletion}
                onClose={closeModal}
                maxWidth="sm"
            >
                <form onSubmit={deletePayment} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Are you sure you want to delete this payment?
                    </h2>

                    <div className="mt-6 flex justify-end">
                        <Button
                            type="button"
                            variant="secondary"
                            onClick={closeModal}
                        >
                            Cancel
                        </Button>

                        <Button
                            variant="destructive"
                            className="ms-3"
                            disabled={processing}
                        >
                            Delete Payment
                        </Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}

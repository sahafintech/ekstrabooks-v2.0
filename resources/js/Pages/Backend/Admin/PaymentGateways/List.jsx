import { Button } from "@/Components/ui/button";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { Link } from "@inertiajs/react";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { useState } from "react";
import { useForm } from "@inertiajs/react";
import { Avatar, AvatarFallback, AvatarImage } from "@/components/ui/avatar";
import { Settings } from "lucide-react";

import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/components/ui/table";
import { SidebarInset } from "@/Components/ui/sidebar";
import TableWrapper from "@/Components/shared/TableWrapper";
import TableActions from "@/Components/shared/TableActions";

export default function List({ paymentgateways }) {
    const [confirmingDeletion, setConfirmingDeletion] = useState(false);
    const [gatewayId] = useState(null);

    const { delete: destroy, processing, reset, clearErrors } = useForm({});

    const deleteGateway = (e) => {
        e.preventDefault();

        destroy(route("payment_gateways.destroy", gatewayId), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        setConfirmingDeletion(false);
        clearErrors();
        reset();
    };

    const getRowActions = (gateway) => [
        {
            label: "Configure",
            icon: Settings,
            onClick: () => window.location = route("payment_gateways.edit", gateway.id)
        }
    ];

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader
                    page="Payments"
                    subpage="Payment Gateways"
                    url="payment_gateways.index"
                />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <div>
                        <TableWrapper>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {paymentgateways.map((gateway) => (
                                        <TableRow key={gateway.id}>
                                            <TableCell>
                                                <div className="flex space-x-3 w-full">
                                                    <Avatar>
                                                        <AvatarImage
                                                            src={`/backend/images/gateways/${gateway.image}`}
                                                        />
                                                        <AvatarFallback>
                                                            IMG
                                                        </AvatarFallback>
                                                    </Avatar>
                                                    <div className="flex w-full">
                                                        <div className="block my-auto">
                                                            <p className="block text-sm font-semibold text-gray-800 hover:text-gray-900 my-auto dark:text-white dark:hover:text-gray-200">
                                                                {gateway.name}
                                                            </p>
                                                        </div>
                                                    </div>
                                                </div>
                                            </TableCell>
                                            <TableCell>
                                                {gateway.status == 1 ? (
                                                    <span className="text-success">
                                                        Active
                                                    </span>
                                                ) : (
                                                    <span className="text-danger">
                                                        Inactive
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <TableActions actions={getRowActions(gateway)} />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </TableWrapper>
                    </div>
                </div>

                <Modal
                    show={confirmingDeletion}
                    onClose={closeModal}
                    maxWidth="sm"
                >
                    <form onSubmit={deleteGateway} className="p-6">
                        <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Are you sure you want to delete this gateway?
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
                                Delete Gateway
                            </Button>
                        </div>
                    </form>
                </Modal>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}

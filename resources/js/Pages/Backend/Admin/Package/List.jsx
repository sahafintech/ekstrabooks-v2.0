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
import { Eye, Pencil, Trash2 } from "lucide-react";
import TableActions from "@/Components/shared/TableActions";
import TableWrapper from "@/Components/shared/TableWrapper";

export default function List({ packages }) {
    const [confirmingPackageDeletion, setConfirmingPackageDeletion] =
        useState(false);
    const [packageId, setPackageId] = useState(null);

    const confirmPackageDeletion = (id) => {
        setConfirmingPackageDeletion(true);
        setPackageId(id);
    };

    const { delete: destroy, processing, reset, clearErrors } = useForm({});

    const deletePackage = (e) => {
        e.preventDefault();

        destroy(route("packages.destroy", packageId), {
            preserveScroll: true,
            onSuccess: () => closeModal(),
            onFinish: () => reset(),
        });
    };

    const closeModal = () => {
        setConfirmingPackageDeletion(false);
        clearErrors();
        reset();
    };

    const getRowActions = (pkg) => [
        {
            label: "View",
            icon: Eye,
            onClick: () => window.location = route("packages.show", pkg.id)
        },
        {
            label: "Edit",
            icon: Pencil,
            onClick: () => window.location = route("packages.edit", pkg.id)
        },
        {
            label: "Delete",
            icon: Trash2,
            onClick: () => confirmPackageDeletion(pkg.id),
            className: "text-destructive focus:text-destructive"
        }
    ];

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader
                    page="Packages"
                    subpage="List"
                    url="packages.index"
                />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    <div className="flex">
                        <div>
                            <Link href={route("packages.create")}>
                                <Button>Add New Package</Button>
                            </Link>
                        </div>
                    </div>
                    <div>
                        <TableWrapper>
                            <Table>
                                <TableCaption>
                                    A list of your packages.
                                </TableCaption>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Name</TableHead>
                                        <TableHead>Cost</TableHead>
                                        <TableHead>Package Type</TableHead>
                                        <TableHead>Discount</TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead>Popular</TableHead>
                                        <TableHead>Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {packages.map((pkg) => (
                                        <TableRow key={pkg.id}>
                                            <TableCell>{pkg.name}</TableCell>
                                            <TableCell>{pkg.cost}</TableCell>
                                            <TableCell>
                                                {pkg.package_type}
                                            </TableCell>
                                            <TableCell>{pkg.discount}%</TableCell>
                                            <TableCell>
                                                {pkg.status == 1 ? (
                                                    <span className="text-success">
                                                        Active
                                                    </span>
                                                ) : (
                                                    <span className="text-danger">
                                                        Disabled
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                {pkg.is_popular == 1 ? (
                                                    <span className="text-success">
                                                        Yes
                                                    </span>
                                                ) : (
                                                    <span className="text-danger">
                                                        No
                                                    </span>
                                                )}
                                            </TableCell>
                                            <TableCell>
                                                <TableActions actions={getRowActions(pkg)} />
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
                show={confirmingPackageDeletion}
                onClose={closeModal}
                maxWidth="sm"
            >
                <form onSubmit={deletePackage} className="p-6">
                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Are you sure you want to delete this package?
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
                            Delete Package
                        </Button>
                    </div>
                </form>
            </Modal>
        </AuthenticatedLayout>
    );
}

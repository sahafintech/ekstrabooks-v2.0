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
import { Edit, EyeIcon, Plus, Trash } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";

// Delete Confirmation Modal Component
const DeletePaymentModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete this payment?
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
const DeleteAllPaymentsModal = ({ show, onClose, onConfirm, processing, count }) => (
    <Modal show={show} onClose={onClose}>
        <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete {count} selected payments{count !== 1 ? 's' : ''}?
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

export default function List({ payments = [], meta = {}, filters = {} }) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [selectedPayments, setSelectedPayments] = useState([]);
    const [isAllSelected, setIsAllSelected] = useState(false);
    const [search, setSearch] = useState(filters.search || "");
    const [perPage, setPerPage] = useState(meta.per_page || 10);
    const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
    const [bulkAction, setBulkAction] = useState("");

    // Delete confirmation modal states
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [paymentToDelete, setPaymentToDelete] = useState(null);
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
            setSelectedPayments([]);
        } else {
            setSelectedPayments(payments.map((payment) => payment.id));
        }
        setIsAllSelected(!isAllSelected);
    };

    const toggleSelectPayment = (id) => {
        if (selectedPayments.includes(id)) {
            setSelectedPayments(selectedPayments.filter((paymentId) => paymentId !== id));
            setIsAllSelected(false);
        } else {
            setSelectedPayments([...selectedPayments, id]);
            if (selectedPayments.length + 1 === payments.length) {
                setIsAllSelected(true);
            }
        }
    };

    const handleSearch = (e) => {
        e.preventDefault();
        router.get(
            route("receive_payments.index"),
            { search, page: 1, per_page: perPage },
            { preserveState: true }
        );
    };

    const handlePerPageChange = (value) => {
        setPerPage(value);
        router.get(
            route("receive_payments.index"),
            { search, page: 1, per_page: value },
            { preserveState: true }
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("receive_payments.index"),
            { search, page, per_page: perPage },
            { preserveState: true }
        );
    };

    const handleBulkAction = () => {
        if (bulkAction === "") return;

        if (selectedPayments.length === 0) {
            toast({
                variant: "destructive",
                title: "Error",
                description: "Please select at least one payment",
            });
            return;
        }

        if (bulkAction === "delete") {
            setShowDeleteAllModal(true);
        }
    };

    const handleDeleteConfirm = (id) => {
        setPaymentToDelete(id);
        setShowDeleteModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.delete(route('receive_payments.destroy', paymentToDelete), {
            onSuccess: () => {
                setShowDeleteModal(false);
                setPaymentToDelete(null);
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

        router.post(route('receive_payments.bulk_action'), {
            delete_payments: selectedPayments.join(',')
        }, {
            onSuccess: () => {
                setShowDeleteAllModal(false);
                setSelectedPayments([]);
                setIsAllSelected(false);
                setProcessing(false);
            },
            onError: () => {
                setProcessing(false);
            }
        });
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
            <Head title="Payments" />
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Payments"
                        subpage="List"
                        url="receive_payments.index"
                    />
                    <div className="p-4">
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <Link href={route("receive_payments.create")}>
                                <Button>
                                    <Plus className="w-4 h-4 mr-2" />
                                    Add Payment
                                </Button>
                            </Link>
                            <div className="flex flex-col md:flex-row gap-4 md:items-center">
                                <form onSubmit={handleSearch} className="flex gap-2">
                                    <Input
                                        placeholder="Search payments..."
                                        value={search}
                                        onChange={(e) => setSearch(e.target.value)}
                                        className="w-full md:w-80"
                                    />
                                    <Button type="submit">Search</Button>
                                </form>
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
                                        <TableHead className="w-[80px]">ID</TableHead>
                                        <TableHead>Date</TableHead>
                                        <TableHead>Customer</TableHead>
                                        <TableHead>Invoice</TableHead>
                                        <TableHead>Method</TableHead>
                                        <TableHead>Type</TableHead>
                                        <TableHead>Amount</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {payments.length > 0 ? (
                                        payments.map((payment) => (
                                            <TableRow key={payment.id}>
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selectedPayments.includes(payment.id)}
                                                        onCheckedChange={() => toggleSelectPayment(payment.id)}
                                                    />
                                                </TableCell>
                                                <TableCell>{payment.id}</TableCell>
                                                <TableCell>{payment.date}</TableCell>
                                                <TableCell>{payment.customer.name || "-"}</TableCell>
                                                <TableCell>{payment.invoices.length + " " + "Invoices"}</TableCell>
                                                <TableCell>{payment.method || "-"}</TableCell>
                                                <TableCell>{payment.type || "-"}</TableCell>
                                                <TableCell className="text-right">{payment.amount}</TableCell>
                                                <TableCell className="text-right">
                                                    <TableActions
                                                        actions={[
                                                            {
                                                                label: "View",
                                                                icon: <EyeIcon className="h-4 w-4" />,
                                                                href: route("receive_payments.show", payment.id),
                                                            },
                                                            {
                                                                label: "Edit",
                                                                icon: <Edit className="h-4 w-4" />,
                                                                href: route("receive_payments.edit", payment.id),
                                                            },
                                                            {
                                                                label: "Delete",
                                                                icon: <Trash className="h-4 w-4" />,
                                                                onClick: () => handleDeleteConfirm(payment.id),
                                                                destructive: true,
                                                            },
                                                        ]}
                                                    />
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={9} className="h-24 text-center">
                                                No payments found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {payments.length > 0 && meta.total > 0 && (
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
                </div>
            </SidebarInset>

            <DeletePaymentModal
                show={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                onConfirm={handleDelete}
                processing={processing}
            />

            <DeleteAllPaymentsModal
                show={showDeleteAllModal}
                onClose={() => setShowDeleteAllModal(false)}
                onConfirm={handleDeleteAll}
                processing={processing}
                count={selectedPayments.length}
            />
        </AuthenticatedLayout>
    );
}

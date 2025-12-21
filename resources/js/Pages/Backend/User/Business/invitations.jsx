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
import Modal from "@/Components/Modal";
import { ArrowLeft, RefreshCw, Trash, Clock, CheckCircle, ChevronUp, ChevronDown } from "lucide-react";

// Cancel Confirmation Modal Component
const CancelInvitationModal = ({ show, onClose, onConfirm, processing, email }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                Cancel Invitation
            </h2>
            <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Are you sure you want to cancel the invitation to <strong>{email}</strong>? This action cannot be undone.
            </p>
            <div className="mt-6 flex justify-end gap-3">
                <Button
                    type="button"
                    variant="outline"
                    onClick={onClose}
                    disabled={processing}
                >
                    Keep Invitation
                </Button>
                <Button
                    type="submit"
                    variant="destructive"
                    disabled={processing}
                >
                    Cancel Invitation
                </Button>
            </div>
        </form>
    </Modal>
);

// Bulk Cancel Modal Component
const BulkCancelModal = ({ show, onClose, onConfirm, processing, count }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                Cancel Selected Invitations
            </h2>
            <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Are you sure you want to cancel {count} selected invitation(s)? This action cannot be undone.
            </p>
            <div className="mt-6 flex justify-end gap-3">
                <Button
                    type="button"
                    variant="outline"
                    onClick={onClose}
                    disabled={processing}
                >
                    Keep Invitations
                </Button>
                <Button
                    type="submit"
                    variant="destructive"
                    disabled={processing}
                >
                    Cancel Selected
                </Button>
            </div>
        </form>
    </Modal>
);

export default function Invitations({ invitations = [], meta = {}, filters = {} }) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();

    const [selectedInvitations, setSelectedInvitations] = useState([]);
    const [isAllSelected, setIsAllSelected] = useState(false);
    const [search, setSearch] = useState(filters.search || "");
    const [perPage, setPerPage] = useState(meta.per_page || 50);
    const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
    const [bulkAction, setBulkAction] = useState("");
    const [sorting, setSorting] = useState(filters.sorting || { column: "created_at", direction: "desc" });

    const [showCancelModal, setShowCancelModal] = useState(false);
    const [showBulkCancelModal, setShowBulkCancelModal] = useState(false);
    const [invitationToCancel, setInvitationToCancel] = useState(null);
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
    }, [flash]);

    // Pending invitations for bulk actions
    const pendingInvitations = useMemo(() => 
        invitations.filter(inv => inv.status === 1), 
        [invitations]
    );

    // Selection handlers
    const toggleSelectAll = useCallback(() => {
        if (isAllSelected) {
            setSelectedInvitations([]);
            setIsAllSelected(false);
        } else {
            setSelectedInvitations(pendingInvitations.map(inv => inv.id));
            setIsAllSelected(true);
        }
    }, [isAllSelected, pendingInvitations]);

    const toggleSelectInvitation = useCallback((invitationId) => {
        setSelectedInvitations(prev => {
            if (prev.includes(invitationId)) {
                return prev.filter(id => id !== invitationId);
            } else {
                return [...prev, invitationId];
            }
        });
    }, []);

    // Search handler
    const handleSearch = useCallback((e) => {
        const value = e.target.value;
        setSearch(value);
        
        const timeoutId = setTimeout(() => {
            router.get(route("business.invitations"), {
                search: value,
                per_page: perPage,
                sorting: sorting,
            }, {
                preserveState: true,
                preserveScroll: true,
            });
        }, 500);

        return () => clearTimeout(timeoutId);
    }, [perPage, sorting]);

    // Sorting
    const handleSort = useCallback((column) => {
        const newDirection = sorting.column === column && sorting.direction === "asc" ? "desc" : "asc";
        const newSorting = { column, direction: newDirection };
        setSorting(newSorting);
        
        router.get(route("business.invitations"), {
            search,
            per_page: perPage,
            sorting: newSorting,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, [search, perPage, sorting]);

    const renderSortIcon = (column) => {
        if (sorting.column !== column) return null;
        return sorting.direction === "asc" 
            ? <ChevronUp className="w-4 h-4 inline-block ml-1" />
            : <ChevronDown className="w-4 h-4 inline-block ml-1" />;
    };

    // Pagination handlers
    const handlePageChange = useCallback((page) => {
        setCurrentPage(page);
        router.get(route("business.invitations"), {
            page,
            search,
            per_page: perPage,
            sorting,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, [search, perPage, sorting]);

    const handlePerPageChange = useCallback((value) => {
        setPerPage(parseInt(value));
        setCurrentPage(1);
        router.get(route("business.invitations"), {
            page: 1,
            search,
            per_page: parseInt(value),
            sorting,
        }, {
            preserveState: true,
            preserveScroll: true,
        });
    }, [search, sorting]);

    // Bulk action handler
    const handleBulkAction = useCallback(() => {
        if (bulkAction === "cancel" && selectedInvitations.length > 0) {
            setShowBulkCancelModal(true);
        }
    }, [bulkAction, selectedInvitations]);

    const handleResendInvitation = (invitation) => {
        setProcessing(true);
        router.post(route('business.invitations.resend', invitation.id), {}, {
            onSuccess: () => {
                setProcessing(false);
            },
            onError: () => {
                setProcessing(false);
            }
        });
    };

    const handleCancelConfirm = (invitation) => {
        setInvitationToCancel(invitation);
        setShowCancelModal(true);
    };

    const handleCancel = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.delete(route('business.invitations.cancel', invitationToCancel.id), {
            onSuccess: () => {
                setShowCancelModal(false);
                setInvitationToCancel(null);
                setProcessing(false);
            },
            onError: () => {
                setProcessing(false);
            }
        });
    };

    const handleBulkCancel = (e) => {
        e.preventDefault();
        setProcessing(true);

        // Cancel invitations one by one
        const promises = selectedInvitations.map(id => 
            new Promise((resolve) => {
                router.delete(route('business.invitations.cancel', id), {
                    preserveScroll: true,
                    onSuccess: resolve,
                    onError: resolve,
                });
            })
        );

        Promise.all(promises).then(() => {
            setShowBulkCancelModal(false);
            setSelectedInvitations([]);
            setIsAllSelected(false);
            setProcessing(false);
        });
    };

    const getStatusBadge = (status) => {
        if (status === 1) {
            return (
                <Badge variant="secondary" className="gap-1">
                    <Clock className="w-3 h-3" />
                    Pending
                </Badge>
            );
        }
        return (
            <Badge variant="outline" className="gap-1 text-green-600 border-green-600">
                <CheckCircle className="w-3 h-3" />
                Accepted
            </Badge>
        );
    };

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
                    variant={currentPage === i ? "default" : "outline"}
                    size="sm"
                    onClick={() => handlePageChange(i)}
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
                        subpage="Invitations"
                        url="business.invitations"
                    />
                    <div className="p-4">
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div className="flex flex-col md:flex-row gap-2">
                                <Link href={route('business.user-management')}>
                                    <Button variant="outline">
                                        <ArrowLeft className="w-4 h-4 mr-2" />
                                        Back to Users
                                    </Button>
                                </Link>
                            </div>
                            <div className="flex flex-col md:flex-row gap-4 md:items-center">
                                <Input
                                    placeholder="Search by email..."
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
                                        <SelectItem value="cancel">Cancel Selected</SelectItem>
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
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("email")}>
                                            Email {renderSortIcon("email")}
                                        </TableHead>
                                        <TableHead>Role</TableHead>
                                        <TableHead>Businesses</TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("status")}>
                                            Status {renderSortIcon("status")}
                                        </TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("created_at")}>
                                            Sent At {renderSortIcon("created_at")}
                                        </TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {invitations.length > 0 ? (
                                        invitations.map((invitation) => (
                                            <TableRow key={invitation.id}>
                                                <TableCell>
                                                    {invitation.status === 1 && (
                                                        <Checkbox
                                                            checked={selectedInvitations.includes(invitation.id)}
                                                            onCheckedChange={() => toggleSelectInvitation(invitation.id)}
                                                        />
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    <div className="font-medium">{invitation.email}</div>
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant="secondary">{invitation.role}</Badge>
                                                </TableCell>
                                                <TableCell>
                                                    {(!invitation.businesses || invitation.businesses.length === 0) ? (
                                                        <span className="text-muted-foreground">-</span>
                                                    ) : (
                                                        <div className="flex flex-wrap gap-1">
                                                            {invitation.businesses.slice(0, 2).map((business) => (
                                                                <Badge key={business.id} variant="outline">
                                                                    {business.name}
                                                                </Badge>
                                                            ))}
                                                            {invitation.businesses.length > 2 && (
                                                                <Badge variant="outline">
                                                                    +{invitation.businesses.length - 2} more
                                                                </Badge>
                                                            )}
                                                        </div>
                                                    )}
                                                </TableCell>
                                                <TableCell>
                                                    {getStatusBadge(invitation.status)}
                                                </TableCell>
                                                <TableCell>
                                                    <span className="text-sm text-muted-foreground">
                                                        {invitation.created_at}
                                                    </span>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {invitation.status === 1 ? (
                                                        <TableActions
                                                            actions={[
                                                                {
                                                                    label: "Resend Invitation",
                                                                    icon: <RefreshCw className="h-4 w-4" />,
                                                                    onClick: () => handleResendInvitation(invitation),
                                                                },
                                                                {
                                                                    label: "Cancel Invitation",
                                                                    icon: <Trash className="h-4 w-4" />,
                                                                    onClick: () => handleCancelConfirm(invitation),
                                                                    destructive: true,
                                                                },
                                                            ]}
                                                        />
                                                    ) : (
                                                        <span className="text-muted-foreground text-sm">-</span>
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={7} className="h-24 text-center">
                                                No invitations found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {invitations.length > 0 && meta.total > 0 && (
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

            <CancelInvitationModal
                show={showCancelModal}
                onClose={() => setShowCancelModal(false)}
                onConfirm={handleCancel}
                processing={processing}
                email={invitationToCancel?.email}
            />

            <BulkCancelModal
                show={showBulkCancelModal}
                onClose={() => setShowBulkCancelModal(false)}
                onConfirm={handleBulkCancel}
                processing={processing}
                count={selectedInvitations.length}
            />
        </AuthenticatedLayout>
    );
}

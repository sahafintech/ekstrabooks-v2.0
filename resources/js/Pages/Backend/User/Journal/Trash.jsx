import React, { useState, useEffect } from "react";
import { router, usePage } from "@inertiajs/react";
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
import {
    ChevronUp,
    ChevronDown,
    RotateCcw,
    Trash,
} from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { formatCurrency } from "@/lib/utils";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";

const DeleteJournalModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to permanently delete this journal?
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
                    Permanently Delete Journal
                </Button>
            </div>
        </form>
    </Modal>
);

const DeleteAllJournalsModal = ({
    show,
    onClose,
    onConfirm,
    processing,
    count,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to permanently delete {count} selected journal
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
                    Permanently Delete Selected
                </Button>
            </div>
        </form>
    </Modal>
);

const RestoreJournalModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to restore this journal?
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
                    variant="default"
                    disabled={processing}
                >
                    Restore Journal
                </Button>
            </div>
        </form>
    </Modal>
);

const RestoreAllJournalsModal = ({
    show,
    onClose,
    onConfirm,
    processing,
    count,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to restore {count} selected journal
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
                    variant="default"
                    disabled={processing}
                >
                    Restore Selected
                </Button>
            </div>
        </form>
    </Modal>
);

const JournalStatusBadge = ({ status }) => {
    const statusMap = {
        0: { label: "Pending", className: "text-yellow-500" },
        1: { label: "Approved", className: "text-green-500" },
        2: { label: "Rejected", className: "text-red-500" },
    };

    return (
        <span className={statusMap[status].className}>
            {statusMap[status].label}
        </span>
    );
};

export default function TrashList({
    journals = [],
    meta = {},
    filters = {},
}) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [selectedJournals, setSelectedJournals] = useState([]);
    const [isAllSelected, setIsAllSelected] = useState(false);
    const [search, setSearch] = useState(filters.search || "");
    const [perPage, setPerPage] = useState(filters.per_page || 50);
    const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
    const [bulkAction, setBulkAction] = useState("");
    const [sorting, setSorting] = useState(
        filters.sorting || { column: "id", direction: "desc" }
    );
    const [selectedStatus, setSelectedStatus] = useState(filters.status || "");

    // Delete confirmation modal states
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [journalToDelete, setJournalToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);
    const [showRestoreAllModal, setShowRestoreAllModal] = useState(false);
    const [journalToRestore, setJournalToRestore] = useState(null);
    const [showRestoreModal, setShowRestoreModal] = useState(false);

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

    const toggleSelectAll = (checked) => {
        setIsAllSelected(checked);
        setSelectedJournals(checked ? journals.map((journal) => journal.id) : []);
    };

    const toggleSelectJournal = (journalId) => {
        setSelectedJournals((prev) => {
            if (prev.includes(journalId)) {
                return prev.filter((id) => id !== journalId);
            } else {
                return [...prev, journalId];
            }
        });
    };

    const handleDeleteConfirm = (journalId) => {
        setJournalToDelete(journalId);
        setShowDeleteModal(true);
    };

    const handleRestoreConfirm = (journalId) => {
        setJournalToRestore(journalId);
        setShowRestoreModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.delete(route("journals.permanent_destroy", journalToDelete), {
            onSuccess: () => {
                setShowDeleteModal(false);
                setJournalToDelete(null);
                setProcessing(false);
                setSelectedJournals((prev) =>
                    prev.filter((id) => id !== journalToDelete)
                );
                toast({
                    title: "Journal Deleted",
                    description: "Journal has been deleted successfully.",
                });
            },
            onError: () => {
                setProcessing(false);
                toast({
                    variant: "destructive",
                    title: "Error",
                    description: "There was an error deleting the journal.",
                });
            },
        });
    };

    const handleDeleteAll = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(
            route("journals.bulk_permanent_destroy"),
            {
                ids: selectedJournals,
            },
            {
                onSuccess: () => {
                    setShowDeleteAllModal(false);
                    setProcessing(false);
                    setSelectedJournals([]);
                    setIsAllSelected(false);
                },
                onError: () => {
                    setProcessing(false);
                },
            }
        );
    };

    const handleRestore = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(route("journals.restore", journalToRestore), {
            onSuccess: () => {
                setShowRestoreModal(false);
                setJournalToRestore(null);
                setProcessing(false);
                setSelectedJournals((prev) =>
                    prev.filter((id) => id !== journalToRestore)
                );
                toast({
                    title: "Journal Restored",
                    description: "Journal has been restored successfully.",
                });
            },
            onError: () => {
                setProcessing(false);
                toast({
                    variant: "destructive",
                    title: "Error",
                    description: "There was an error restoring the journal.",
                });
            },
        });
    };

    const handleRestoreAll = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(
            route("journals.bulk_restore"),
            {
                ids: selectedJournals,
            },
            {
                onSuccess: () => {
                    setShowRestoreAllModal(false);
                    setProcessing(false);
                    setSelectedJournals([]);
                    setIsAllSelected(false);
                },
                onError: () => {
                    setProcessing(false);
                },
            }
        );
    };

    const handleSearch = (e) => {
        e.preventDefault();
        const value = e.target.value;
        setSearch(value);

        router.get(
            route("journals.trash"),
            {
                search: value,
                page: 1,
                per_page: perPage,
                status: selectedStatus,
            },
            { preserveState: true }
        );
    };

    const handlePerPageChange = (value) => {
        setPerPage(parseInt(value));
        router.get(
            route("journals.trash"),
            {
                search,
                page: 1,
                per_page: value,
                status: selectedStatus,
            },
            { preserveState: true }
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("journals.trash"),
            {
                search,
                page,
                per_page: perPage,
                status: selectedStatus,
                sorting: sorting
            },
            { preserveState: true }
        );
    };

    const handleStatusChange = (value) => {
        setSelectedStatus(value);
        router.get(
            route("journals.trash"),
            { 
                search, 
                page: 1, 
                per_page: perPage,
                status: value || null
            },
            { preserveState: true }
        );
    };

    const handleBulkAction = () => {
        if (bulkAction === "delete" && selectedJournals.length > 0) {
            setShowDeleteAllModal(true);
        }else if (bulkAction === "restore" && selectedJournals.length > 0) {
            setShowRestoreAllModal(true);
        }
    };

    const handleSort = (column) => {
        let direction = "asc";
        if (sorting.column === column && sorting.direction === "asc") {
            direction = "desc";
        }
        setSorting({ column, direction });
        router.get(
            route("journals.trash"),
            { ...filters, sorting: { column, direction } },
            { preserveState: true }
        );
    };

    const renderSortIcon = (column) => {
        const isActive = sorting.column === column;
        return (
            <span className="inline-flex flex-col ml-1">
                <ChevronUp
                    className={`w-3 h-3 ${
                        isActive && sorting.direction === "asc"
                            ? "text-gray-800"
                            : "text-gray-300"
                    }`}
                />
                <ChevronDown
                    className={`w-3 h-3 -mt-1 ${
                        isActive && sorting.direction === "desc"
                            ? "text-gray-800"
                            : "text-gray-300"
                    }`}
                />
            </span>
        );
    };

    const renderPageNumbers = () => {
        const totalPages = meta.last_page || 1;
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
                        page="Journals"
                        subpage="Trash"
                        url="journals.index"
                    />
                    <div className="p-4">
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div>
                                <div className="text-red-500">
                                    Total trashed journals: {meta.total}
                                </div>
                            </div>
                            <div className="flex flex-col md:flex-row gap-4 md:items-center">
                                <Input
                                    placeholder="Search trashed journals..."
                                    value={search}
                                    onChange={(e) => handleSearch(e)}
                                    className="w-full md:w-80"
                                />
                            </div>
                        </div>

                        <div className="mb-4 flex flex-col md:flex-row gap-4 justify-between">
                            <div className="flex flex-wrap items-center gap-2">
                                <Select
                                    value={bulkAction}
                                    onValueChange={setBulkAction}
                                >
                                    <SelectTrigger className="w-[180px]">
                                        <SelectValue placeholder="Bulk actions" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="delete">
                                            Permanently Delete Selected
                                        </SelectItem>
                                        <SelectItem value="restore">
                                            Restore Selected
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button
                                    onClick={handleBulkAction}
                                    variant="outline"
                                >
                                    Apply
                                </Button>

                                <SearchableCombobox
                                    options={[
                                        { id: "", name: "All Status" },
                                        { id: "0", name: "Pending" },
                                        { id: "1", name: "Approved" },
                                        { id: "2", name: "Rejected" },
                                    ]}
                                    value={selectedStatus}
                                    onChange={handleStatusChange}
                                    placeholder="Select status"
                                    className="w-[150px]"
                                />
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
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() => handleSort("date")}
                                        >
                                            Date {renderSortIcon("date")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() =>
                                                handleSort("journal_number")
                                            }
                                        >
                                            Journal #{" "}
                                            {renderSortIcon("journal_number")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer text-right"
                                            onClick={() =>
                                                handleSort("transaction_amount")
                                            }
                                        >
                                            Transaction Amount{" "}
                                            {renderSortIcon(
                                                "transaction_amount"
                                            )}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer text-right"
                                            onClick={() =>
                                                handleSort("currency_rate")
                                            }
                                        >
                                            Currency Rate{" "}
                                            {renderSortIcon("currency_rate")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer text-right"
                                            onClick={() =>
                                                handleSort(
                                                    "base_currency_amount"
                                                )
                                            }
                                        >
                                            Base Currency Amount{" "}
                                            {renderSortIcon(
                                                "base_currency_amount"
                                            )}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() => handleSort("status")}
                                        >
                                            Status {renderSortIcon("status")}
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {journals.length > 0 ? (
                                        journals.map((journal) => (
                                            <TableRow key={journal.id}>
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selectedJournals.includes(
                                                            journal.id
                                                        )}
                                                        onCheckedChange={() =>
                                                            toggleSelectJournal(
                                                                journal.id
                                                            )
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    {journal.date}
                                                </TableCell>
                                                <TableCell>
                                                    {journal.journal_number}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency({
                                                        amount: journal.transaction_amount,
                                                        currency:
                                                            journal.transaction_currency,
                                                    })}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency({
                                                        amount: journal.currency_rate,
                                                        currency:
                                                            journal.transaction_currency,
                                                    })}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency({
                                                        amount: journal.base_currency_amount,
                                                    })}
                                                </TableCell>
                                                <TableCell className="text-center">
                                                    <JournalStatusBadge
                                                        status={journal.status}
                                                    />
                                                </TableCell>
                                                <TableCell className="text-right">
                                                <TableActions
                                                    actions={[
                                                    {
                                                        label: "Restore",
                                                        icon: <RotateCcw className="h-4 w-4" />,
                                                        onClick: () => handleRestoreConfirm(journal.id)
                                                    },
                                                    {
                                                        label: "Permanently Delete",
                                                        icon: <Trash className="h-4 w-4" />,
                                                        onClick: () => handleDeleteConfirm(journal.id),
                                                        destructive: true,
                                                    },
                                                    ]}
                                                />
                                                </TableCell>
                                            </TableRow>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell
                                                colSpan={8}
                                                className="h-24 text-center"
                                            >
                                                No journals found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {journals.length > 0 && meta.total > 0 && (
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

            <DeleteJournalModal
                show={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                onConfirm={handleDelete}
                processing={processing}
            />

            <DeleteAllJournalsModal
                show={showDeleteAllModal}
                onClose={() => setShowDeleteAllModal(false)}
                onConfirm={handleDeleteAll}
                processing={processing}
                count={selectedJournals.length}
            />

            <RestoreJournalModal
                show={showRestoreModal}
                onClose={() => setShowRestoreModal(false)}
                onConfirm={handleRestore}
                processing={processing}
            />

            <RestoreAllJournalsModal
                show={showRestoreAllModal}
                onClose={() => setShowRestoreAllModal(false)}
                onConfirm={handleRestoreAll}
                processing={processing}
                count={selectedJournals.length}
            />
        </AuthenticatedLayout>
    );
}

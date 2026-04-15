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
import { Badge } from "@/Components/ui/badge";
import { ChevronUp, ChevronDown, RotateCcw, Trash, Package } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import DateTimePicker from "@/Components/DateTimePicker";

const DeleteTransferModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to permanently delete this transfer?
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
                    Permanently Delete Transfer
                </Button>
            </div>
        </form>
    </Modal>
);

const DeleteAllTransfersModal = ({ show, onClose, onConfirm, processing, count }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to permanently delete {count} selected transfer{count !== 1 ? 's' : ''}?
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

const RestoreTransferModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to restore this transfer?
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
                    Restore Transfer
                </Button>
            </div>
        </form>
    </Modal>
);

const RestoreAllTransfersModal = ({ show, onClose, onConfirm, processing, count }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to restore {count} selected transfer{count !== 1 ? 's' : ''}?
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

const TransferStatusBadge = ({ status }) => {
    const statusMap = {
        draft: { label: "Draft", className: "text-gray-600 bg-gray-200 px-3 py-1 rounded text-sm" },
        sent: { label: "Sent", className: "text-blue-600 bg-blue-200 px-3 py-1 rounded text-sm" },
        received: { label: "Received", className: "text-green-600 bg-green-200 px-3 py-1 rounded text-sm" },
        rejected: { label: "Rejected", className: "text-red-600 bg-red-200 px-3 py-1 rounded text-sm" },
        cancelled: { label: "Cancelled", className: "text-gray-600 bg-gray-200 px-3 py-1 rounded text-sm" }
    };

    return (
        <span className={statusMap[status].className}>
            {statusMap[status].label}
        </span>
    );
};

export default function TrashList({ transfers = [], meta = {}, filters = {}, businesses = [] }) {
    const { flash = {}, auth = {} } = usePage().props;
    const { toast } = useToast();
    const [selectedTransfers, setSelectedTransfers] = useState([]);
    const [isAllSelected, setIsAllSelected] = useState(false);
    const [search, setSearch] = useState(filters.search || "");
    const [perPage, setPerPage] = useState(meta.per_page || 50);
    const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
    const [bulkAction, setBulkAction] = useState("");
    const [sorting, setSorting] = useState(filters.sorting || { column: "id", direction: "desc" });
    const [selectedBusiness, setSelectedBusiness] = useState(filters.business_id || "");
    const [dateRange, setDateRange] = useState(filters.date_range || null);
    const [selectedStatus, setSelectedStatus] = useState(filters.status || "");
    const [selectedDirection, setSelectedDirection] = useState(filters.direction || "");

    // Modal states
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [showRestoreModal, setShowRestoreModal] = useState(false);
    const [showRestoreAllModal, setShowRestoreAllModal] = useState(false);
    const [transferToDelete, setTransferToDelete] = useState(null);
    const [transferToRestore, setTransferToRestore] = useState(null);
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
            setSelectedTransfers([]);
        } else {
            setSelectedTransfers(transfers.map((transfer) => transfer.id));
        }
        setIsAllSelected(!isAllSelected);
    };

    const toggleSelectTransfer = (id) => {
        if (selectedTransfers.includes(id)) {
            setSelectedTransfers(selectedTransfers.filter((transferId) => transferId !== id));
            setIsAllSelected(false);
        } else {
            setSelectedTransfers([...selectedTransfers, id]);
            if (selectedTransfers.length + 1 === transfers.length) {
                setIsAllSelected(true);
            }
        }
    };

    const handleSort = (column) => {
        let direction = "asc";
        if (sorting.column === column && sorting.direction === "asc") {
            direction = "desc";
        }
        setSorting({ column, direction });
        router.get(
            route("inventory_transfers.trash"),
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

    const handleSearch = (e) => {
        const value = e.target.value;
        setSearch(value);
        router.get(
            route("inventory_transfers.trash"),
            { 
                search: value, 
                page: 1, 
                per_page: perPage, 
                sorting,
                business_id: selectedBusiness,
                date_range: dateRange,
                status: selectedStatus,
                direction: selectedDirection
            },
            { preserveState: true }
        );
    };

    const handlePerPageChange = (value) => {
        setPerPage(value);
        router.get(
            route("inventory_transfers.trash"),
            { 
                search, 
                page: 1, 
                per_page: value, 
                sorting,
                business_id: selectedBusiness,
                date_range: dateRange,
                status: selectedStatus,
                direction: selectedDirection
            },
            { preserveState: true }
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("inventory_transfers.trash"),
            { 
                search, 
                page, 
                per_page: perPage, 
                sorting,
                business_id: selectedBusiness,
                date_range: dateRange,
                status: selectedStatus,
                direction: selectedDirection
            },
            { preserveState: true }
        );
    };

    const handleBusinessChange = (value) => {
        setSelectedBusiness(value);
        router.get(
            route("inventory_transfers.trash"),
            { 
                search, 
                page: 1, 
                per_page: perPage, 
                sorting,
                business_id: value,
                date_range: dateRange,
                status: selectedStatus,
                direction: selectedDirection
            },
            { preserveState: true }
        );
    };

    const handleDateRangeChange = (dates) => {
        setDateRange(dates);
        router.get(
            route("inventory_transfers.trash"),
            { 
                search, 
                page: 1, 
                per_page: perPage, 
                sorting,
                business_id: selectedBusiness,
                date_range: dates,
                status: selectedStatus,
                direction: selectedDirection
            },
            { preserveState: true }
        );
    };

    const handleStatusChange = (value) => {
        setSelectedStatus(value);
        router.get(
            route("inventory_transfers.trash"),
            { 
                search, 
                page: 1, 
                per_page: perPage, 
                sorting,
                business_id: selectedBusiness,
                date_range: dateRange,
                status: value,
                direction: selectedDirection
            },
            { preserveState: true }
        );
    };

    const handleDirectionChange = (value) => {
        setSelectedDirection(value);
        router.get(
            route("inventory_transfers.trash"),
            {
                search,
                page: 1, 
                per_page: perPage, 
                sorting,
                business_id: selectedBusiness,
                date_range: dateRange,
                status: selectedStatus,
                direction: value
            },
            { preserveState: true }
        );
    };

    const handleBulkAction = () => {
        if (bulkAction === "") return;

        if (selectedTransfers.length === 0) {
            toast({
                variant: "destructive",
                title: "Error",
                description: "Please select at least one transfer",
            });
            return;
        }

        if (bulkAction === "delete") {
            setShowDeleteAllModal(true);
        } else if (bulkAction === "restore") {
            setShowRestoreAllModal(true);
        }
    };

    const handleDeleteConfirm = (id) => {
        setTransferToDelete(id);
        setShowDeleteModal(true);
    };

    const handleRestoreConfirm = (id) => {
        setTransferToRestore(id);
        setShowRestoreModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.delete(route('inventory_transfers.permanent_destroy', transferToDelete), {
            onSuccess: () => {
                setShowDeleteModal(false);
                setTransferToDelete(null);
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

        router.post(route('inventory_transfers.bulk_permanent_destroy'),
            {
                ids: selectedTransfers
            },
            {
                onSuccess: () => {
                    setShowDeleteAllModal(false);
                    setSelectedTransfers([]);
                    setIsAllSelected(false);
                    setProcessing(false);
                },
                onError: () => {
                    setProcessing(false);
                }
            }
        );
    };

    const handleRestore = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(route('inventory_transfers.restore', transferToRestore), {
            onSuccess: () => {
                setShowRestoreModal(false);
                setTransferToRestore(null);
                setProcessing(false);
            },
            onError: () => {
                setProcessing(false);
            }
        });
    };

    const handleRestoreAll = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(route('inventory_transfers.bulk_restore'),
            {
                ids: selectedTransfers
            },
            {
                onSuccess: () => {
                    setShowRestoreAllModal(false);
                    setSelectedTransfers([]);
                    setIsAllSelected(false);
                    setProcessing(false);
                },
                onError: () => {
                    setProcessing(false);
                }
            }
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

    const formatDate = (date) => {
        return new Date(date).toLocaleDateString();
    };

    const currentBusinessId = auth.user?.business_ids?.[0];

    return (
        <AuthenticatedLayout>
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Inventory Transfers"
                        subpage="Trash"
                        url="inventory_transfers.index"
                    />
                    <div className="p-4">
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div>
                                <div className="text-red-500">
                                    Total trashed transfers: {meta.total}
                                </div>
                            </div>
                            <div className="flex flex-col md:flex-row gap-4 md:items-center">
                                <Input
                                    placeholder="Search transfers..."
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
                                        <SelectItem value="delete">Permanently Delete Selected</SelectItem>
                                        <SelectItem value="restore">Restore Selected</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button onClick={handleBulkAction} variant="outline">
                                    Apply
                                </Button>
                                <SearchableCombobox
                                    options={businesses.map(business => ({
                                        id: business.id,
                                        name: business.name
                                    }))}
                                    value={selectedBusiness}
                                    onChange={handleBusinessChange}
                                    placeholder="Select business"
                                    className="w-[200px]"
                                />
                                <DateTimePicker
                                    value={dateRange}
                                    onChange={handleDateRangeChange}
                                    isRange={true}
                                    className="w-[200px]"
                                    placeholder="Select date range"
                                />
                                <SearchableCombobox
                                    options={[
                                        { id: "", name: "All Status" },
                                        { id: "draft", name: "Draft" },
                                        { id: "sent", name: "Sent" },
                                        { id: "received", name: "Received" },
                                        { id: "rejected", name: "Rejected" },
                                        { id: "cancelled", name: "Cancelled" }
                                    ]}
                                    value={selectedStatus}
                                    onChange={handleStatusChange}
                                    placeholder="Select status"
                                    className="w-[150px]"
                                />
                                <SearchableCombobox
                                    options={[
                                        { id: "", name: "All Directions" },
                                        { id: "outgoing", name: "Outgoing" },
                                        { id: "incoming", name: "Incoming" }
                                    ]}
                                    value={selectedDirection}
                                    onChange={handleDirectionChange}
                                    placeholder="Select direction"
                                    className="w-[150px]"
                                />
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
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("transfer_number")}>Transfer # {renderSortIcon("transfer_number")}</TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("transfer_date")}>Date {renderSortIcon("transfer_date")}</TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("from_entity.name")}>From {renderSortIcon("from_entity.name")}</TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("to_entity.name")}>To {renderSortIcon("to_entity.name")}</TableHead>
                                        <TableHead>Items</TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("status")}>Status {renderSortIcon("status")}</TableHead>
                                        <TableHead>Direction</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {transfers.length > 0 ? (
                                        transfers.map((transfer) => (
                                            <TableRow key={transfer.id}>
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selectedTransfers.includes(transfer.id)}
                                                        onCheckedChange={() => toggleSelectTransfer(transfer.id)}
                                                    />
                                                </TableCell>
                                                <TableCell>{transfer.transfer_number}</TableCell>
                                                <TableCell>{formatDate(transfer.transfer_date)}</TableCell>
                                                <TableCell>{transfer.from_entity?.name || "N/A"}</TableCell>
                                                <TableCell>{transfer.to_entity?.name || "N/A"}</TableCell>
                                                <TableCell>
                                                    <div className="flex items-center">
                                                        <Package className="h-4 w-4 mr-1" />
                                                        {transfer.items?.length || 0}
                                                    </div>
                                                </TableCell>
                                                <TableCell>
                                                    <TransferStatusBadge status={transfer.status} />
                                                </TableCell>
                                                <TableCell>
                                                    <Badge variant={transfer.from_entity_id === currentBusinessId ? "outline" : "default"}>
                                                        {transfer.from_entity_id === currentBusinessId ? "Outgoing" : "Incoming"}
                                                    </Badge>
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <TableActions
                                                        actions={[
                                                            {
                                                                label: "Restore",
                                                                icon: <RotateCcw className="h-4 w-4" />,
                                                                onClick: () => handleRestoreConfirm(transfer.id)
                                                            },
                                                            {
                                                                label: "Permanently Delete",
                                                                icon: <Trash className="h-4 w-4" />,
                                                                onClick: () => handleDeleteConfirm(transfer.id),
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
                                                No transfers found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {transfers.length > 0 && meta.total > 0 && (
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

            <DeleteTransferModal
                show={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                onConfirm={handleDelete}
                processing={processing}
            />

            <DeleteAllTransfersModal
                show={showDeleteAllModal}
                onClose={() => setShowDeleteAllModal(false)}
                onConfirm={handleDeleteAll}
                processing={processing}
                count={selectedTransfers.length}
            />

            <RestoreTransferModal
                show={showRestoreModal}
                onClose={() => setShowRestoreModal(false)}
                onConfirm={handleRestore}
                processing={processing}
            />

            <RestoreAllTransfersModal
                show={showRestoreAllModal}
                onClose={() => setShowRestoreAllModal(false)}
                onConfirm={handleRestoreAll}
                processing={processing}
                count={selectedTransfers.length}
            />
        </AuthenticatedLayout>
    );
}

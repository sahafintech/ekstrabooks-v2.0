import React, { useState, useEffect } from "react";
import { Link, router, usePage } from "@inertiajs/react";
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
import { Plus, Eye, Trash2, Edit, ChevronUp, ChevronDown, FileDown, Clock, CheckCircle, DollarSign, FileText, CircleOff } from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { formatCurrency, parseDateObject } from "@/lib/utils";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import DateTimePicker from "@/Components/DateTimePicker";
import { Badge } from "@/Components/ui/badge";

const DeleteModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">Are you sure you want to delete this underwriting quote?</h2>
            <div className="mt-6 flex justify-end">
                <Button type="button" variant="secondary" onClick={onClose} className="mr-3">Cancel</Button>
                <Button type="submit" variant="destructive" disabled={processing}>Delete Quote</Button>
            </div>
        </form>
    </Modal>
);

const DeleteAllModal = ({ show, onClose, onConfirm, processing, count }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">Are you sure you want to delete {count} selected quote{count !== 1 ? "s" : ""}?</h2>
            <div className="mt-6 flex justify-end">
                <Button type="button" variant="secondary" onClick={onClose} className="mr-3">Cancel</Button>
                <Button type="submit" variant="destructive" disabled={processing}>Delete Selected</Button>
            </div>
        </form>
    </Modal>
);

const ActionConfirmationModal = ({ show, onClose, onConfirm, processing, title, confirmLabel, confirmVariant = "default" }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">{title}</h2>
            <div className="mt-6 flex justify-end">
                <Button type="button" variant="secondary" onClick={onClose} className="mr-3">Cancel</Button>
                <Button type="submit" variant={confirmVariant} disabled={processing}>{confirmLabel}</Button>
            </div>
        </form>
    </Modal>
);

const StatusBadge = ({ quotation }) => {
    const statusMap = {
        0: { label: "Active",     className: "gap-1 text-blue-600 border-blue-600" },
        1: { label: "Expired",    className: "gap-1 text-red-600 border-red-600" },
        2: { label: "Accepted",   className: "gap-1 text-green-600 border-green-600" },
        3: { label: "Rejected",   className: "gap-1 text-red-600 border-red-600" },
        4: { label: "Converted",  className: "gap-1 text-teal-600 border-teal-600" },
    };
    const parsedExpirationDate = parseDateObject(quotation.expired_date);
    const expirationDate = parsedExpirationDate ? new Date(parsedExpirationDate) : null;
    const quotationStatus = Number(quotation.status ?? 0);
    if (expirationDate) expirationDate.setHours(23, 59, 59, 999);
    const isActive = expirationDate ? expirationDate >= new Date() : false;
    const displayStatus = quotationStatus === 3 ? 4 : quotationStatus === 1 ? 2 : quotationStatus === 2 ? 3 : isActive ? 0 : 1;
    return (
        <Badge variant="outline" className={statusMap[displayStatus].className}>
            {statusMap[displayStatus].label}
        </Badge>
    );
};

const categoryLabelMap = {
    medical: { label: "Medical", className: "gap-1 text-blue-600 border-blue-600" },
    gpa: { label: "GPA", className: "gap-1 text-green-600 border-green-600" },
    other: { label: "General", className: "gap-1 text-orange-600 border-orange-600" },
};

const CategoryBadge = ({ quotation }) => {
    const category = quotation.insurance_category;
    const type = quotation.invoice_category;
    const info = categoryLabelMap[type] ?? { label: type ?? "-", className: "gap-1 text-gray-600 border-gray-400" };
    return <Badge variant="outline" className={info.className}>{category?.name ?? info.label}</Badge>;
};

const SummaryCards = ({ summary = {} }) => {
    const cards = [
        { title: "Total Quotes", value: summary.total_quotations || 0, description: "Total underwriting quotes", icon: FileText, iconColor: "text-blue-500" },
        { title: "Grand Total", value: formatCurrency({ amount: summary.grand_total || 0, currency: "USD" }), description: "Total amount of all quotes", icon: DollarSign, iconColor: "text-green-500" },
        { title: "Active Quotes", value: summary.active_quotations || 0, description: "Quotes that are still valid", icon: CheckCircle, iconColor: "text-purple-500" },
        { title: "Expired Quotes", value: summary.expired_quotations || 0, description: "Quotes that have expired", icon: Clock, iconColor: "text-orange-500" },
    ];
    return (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            {cards.map((card, index) => (
                <div key={index} className="bg-gray-100 rounded-lg shadow-sm p-4">
                    <div className="flex flex-row items-center justify-between space-y-0 pb-2">
                        <h3 className="text-lg font-medium">{card.title}</h3>
                        <card.icon className={`h-8 w-8 ${card.iconColor}`} />
                    </div>
                    <div className="text-2xl font-bold">{card.value}
                        <p className="text-xs text-muted-foreground">{card.description}</p>
                    </div>
                </div>
            ))}
        </div>
    );
};

export default function List({ quotations = [], meta = {}, filters = {}, customers = [], insuranceCategories = [], summary = {}, trashed_quotations = 0 }) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [selectedQuotations, setSelectedQuotations] = useState([]);
    const [isAllSelected, setIsAllSelected] = useState(false);
    const [search, setSearch] = useState(filters.search || "");
    const [perPage, setPerPage] = useState(meta.per_page || 50);
    const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
    const [bulkAction, setBulkAction] = useState("");
    const [sorting, setSorting] = useState(filters.sorting || { column: "id", direction: "desc" });
    const [selectedCustomer, setSelectedCustomer] = useState(filters.customer_id || "");
    const [selectedCategory, setSelectedCategory] = useState(filters.insurance_category_id || "");
    const [dateRange, setDateRange] = useState(filters.date_range || null);
    const [selectedStatus, setSelectedStatus] = useState(filters.status || "");

    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [showAcceptModal, setShowAcceptModal] = useState(false);
    const [showConvertModal, setShowConvertModal] = useState(false);
    const [showRejectModal, setShowRejectModal] = useState(false);
    const [quoteToDelete, setQuoteToDelete] = useState(null);
    const [quoteToAccept, setQuoteToAccept] = useState(null);
    const [quoteToConvert, setQuoteToConvert] = useState(null);
    const [quoteToReject, setQuoteToReject] = useState(null);
    const [processing, setProcessing] = useState(false);
    const categoryFilterOptions = [
        { id: "", name: "All Categories" },
        ...insuranceCategories.map((category) => ({
            id: String(category.id),
            name: category.name,
        })),
    ];

    useEffect(() => {
        if (flash?.success) toast({ title: "Success", description: flash.success });
        if (flash?.error) toast({ variant: "destructive", title: "Error", description: flash.error });
    }, [flash, toast]);

    const toggleSelectAll = () => {
        if (isAllSelected) {
            setSelectedQuotations([]);
        } else {
            setSelectedQuotations(quotations.map((q) => q.id));
        }
        setIsAllSelected(!isAllSelected);
    };

    const toggleSelectQuotation = (id) => {
        if (selectedQuotations.includes(id)) {
            setSelectedQuotations(selectedQuotations.filter((qid) => qid !== id));
            setIsAllSelected(false);
        } else {
            const next = [...selectedQuotations, id];
            setSelectedQuotations(next);
            if (next.length === quotations.length) setIsAllSelected(true);
        }
    };

    const buildParams = (overrides = {}) => ({
        search,
        page: 1,
        per_page: perPage,
        sorting,
        customer_id: selectedCustomer,
        insurance_category_id: selectedCategory,
        date_range: dateRange,
        status: selectedStatus,
        ...overrides,
    });

    const handleSort = (column) => {
        const direction = sorting.column === column && sorting.direction === "asc" ? "desc" : "asc";
        setSorting({ column, direction });
        router.get(route("underwriting_quotes.index"), buildParams({ sorting: { column, direction } }), { preserveState: true });
    };

    const renderSortIcon = (column) => {
        const isActive = sorting.column === column;
        return (
            <span className="inline-flex flex-col ml-1">
                <ChevronUp className={`w-3 h-3 ${isActive && sorting.direction === "asc" ? "text-gray-800" : "text-gray-300"}`} />
                <ChevronDown className={`w-3 h-3 -mt-1 ${isActive && sorting.direction === "desc" ? "text-gray-800" : "text-gray-300"}`} />
            </span>
        );
    };

    const handleSearch = (e) => {
        const value = e.target.value;
        setSearch(value);
        router.get(route("underwriting_quotes.index"), buildParams({ search: value }), { preserveState: true });
    };

    const handlePerPageChange = (value) => {
        setPerPage(value);
        router.get(route("underwriting_quotes.index"), buildParams({ per_page: value }), { preserveState: true });
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(route("underwriting_quotes.index"), buildParams({ page }), { preserveState: true });
    };

    const handleCustomerChange = (value) => {
        setSelectedCustomer(value);
        router.get(route("underwriting_quotes.index"), buildParams({ customer_id: value }), { preserveState: true });
    };

    const handleCategoryChange = (value) => {
        setSelectedCategory(value);
        router.get(route("underwriting_quotes.index"), buildParams({ insurance_category_id: value }), { preserveState: true });
    };

    const handleDateRangeChange = (dates) => {
        setDateRange(dates);
        router.get(route("underwriting_quotes.index"), buildParams({ date_range: dates }), { preserveState: true });
    };

    const handleStatusChange = (value) => {
        setSelectedStatus(value);
        router.get(route("underwriting_quotes.index"), buildParams({ status: value }), { preserveState: true });
    };

    const handleBulkAction = () => {
        if (bulkAction === "") return;
        if (selectedQuotations.length === 0) {
            toast({ variant: "destructive", title: "Error", description: "Please select at least one quote" });
            return;
        }
        if (bulkAction === "delete") setShowDeleteAllModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);
        router.delete(route("underwriting_quotes.destroy", quoteToDelete), {
            onSuccess: () => { setShowDeleteModal(false); setQuoteToDelete(null); setProcessing(false); },
            onError: () => setProcessing(false),
        });
    };

    const handleDeleteAll = (e) => {
        e.preventDefault();
        setProcessing(true);
        router.post(route("underwriting_quotes.bulk_destroy"), { ids: selectedQuotations }, {
            onSuccess: () => {
                setShowDeleteAllModal(false);
                setSelectedQuotations([]);
                setIsAllSelected(false);
                setProcessing(false);
            },
            onError: () => setProcessing(false),
        });
    };

    const handleAcceptQuotation = (e) => {
        e.preventDefault();
        if (!quoteToAccept) return;
        setProcessing(true);
        router.post(route("underwriting_quotes.accept", quoteToAccept), {}, {
            preserveScroll: true,
            onSuccess: () => { setShowAcceptModal(false); setQuoteToAccept(null); },
            onFinish: () => setProcessing(false),
        });
    };

    const handleConvertQuotation = (e) => {
        e.preventDefault();
        if (!quoteToConvert) return;
        setProcessing(true);
        router.post(route("underwriting_quotes.convert_to_invoice", quoteToConvert), {}, {
            preserveScroll: true,
            onSuccess: () => { setShowConvertModal(false); setQuoteToConvert(null); },
            onFinish: () => setProcessing(false),
        });
    };

    const handleRejectQuotation = (e) => {
        e.preventDefault();
        if (!quoteToReject) return;
        setProcessing(true);
        router.post(route("underwriting_quotes.reject", quoteToReject), {}, {
            preserveScroll: true,
            onSuccess: () => { setShowRejectModal(false); setQuoteToReject(null); },
            onFinish: () => setProcessing(false),
        });
    };

    const renderPageNumbers = () => {
        const totalPages = meta.last_page;
        const pages = [];
        const maxPagesToShow = 5;
        let startPage = Math.max(1, currentPage - Math.floor(maxPagesToShow / 2));
        let endPage = startPage + maxPagesToShow - 1;
        if (endPage > totalPages) { endPage = totalPages; startPage = Math.max(1, endPage - maxPagesToShow + 1); }
        for (let i = startPage; i <= endPage; i++) {
            pages.push(
                <Button key={i} variant={i === currentPage ? "default" : "outline"} size="sm" onClick={() => handlePageChange(i)} className="mx-1">{i}</Button>
            );
        }
        return pages;
    };

    return (
        <AuthenticatedLayout>
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader page="Underwriting Quotes" subpage="List" url="underwriting_quotes.index" />
                    <div className="p-4">
                        <SummaryCards summary={summary} />

                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div className="flex flex-col md:flex-row gap-2">
                                <Link href={route("underwriting_quotes.create")}>
                                    <Button>
                                        <Plus className="w-4 h-4 mr-2" />Add Underwriting Quote
                                    </Button>
                                </Link>
                                <Link href={route("underwriting_quotes.trash")}>
                                    <Button variant="outline" className="relative">
                                        <Trash2 className="h-8 w-8" />
                                        {trashed_quotations > 0 && (
                                            <span className="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-6 w-6 flex items-center justify-center font-bold">
                                                {trashed_quotations}
                                            </span>
                                        )}
                                    </Button>
                                </Link>
                            </div>
                            <div className="flex flex-col md:flex-row gap-4 md:items-center">
                                <Input placeholder="Search underwriting quotes..." value={search} onChange={handleSearch} className="w-full md:w-80" />
                            </div>
                        </div>

                        <div className="mb-4 flex flex-col md:flex-row gap-4 justify-between">
                            <div className="flex flex-wrap items-center gap-2">
                                <Select value={bulkAction} onValueChange={setBulkAction}>
                                    <SelectTrigger className="w-[180px]">
                                        <SelectValue placeholder="Bulk actions" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="delete">Delete Selected</SelectItem>
                                    </SelectContent>
                                </Select>
                                <Button onClick={handleBulkAction} variant="outline">Apply</Button>
                                <SearchableCombobox
                                    options={customers.map((c) => ({ id: c.id, name: c.name }))}
                                    value={selectedCustomer}
                                    onChange={handleCustomerChange}
                                    placeholder="Select customer"
                                    className="w-[200px]"
                                />
                                <SearchableCombobox
                                    options={categoryFilterOptions}
                                    value={selectedCategory}
                                    onChange={handleCategoryChange}
                                    placeholder="Select category"
                                    className="w-[160px]"
                                />
                                <DateTimePicker value={dateRange} onChange={handleDateRangeChange} isRange={true} className="w-[200px]" placeholder="Select date range" />
                                <SearchableCombobox
                                    options={[
                                        { id: "", name: "All Status" },
                                        { id: "0", name: "Active" },
                                        { id: "1", name: "Expired" },
                                        { id: "2", name: "Accepted" },
                                        { id: "3", name: "Rejected" },
                                        { id: "4", name: "Converted" },
                                    ]}
                                    value={selectedStatus}
                                    onChange={handleStatusChange}
                                    placeholder="Select status"
                                    className="w-[150px]"
                                />
                            </div>
                            <div className="flex items-center gap-2">
                                <span className="text-sm text-gray-500">Show</span>
                                <Select value={perPage.toString()} onValueChange={handlePerPageChange}>
                                    <SelectTrigger className="w-[80px]">
                                        <SelectValue placeholder="50" />
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
                                            <Checkbox checked={isAllSelected} onCheckedChange={toggleSelectAll} />
                                        </TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("quotation_number")}>
                                            Quote Number {renderSortIcon("quotation_number")}
                                        </TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("insuranceCategory.name")}>
                                            Category {renderSortIcon("insuranceCategory.name")}
                                        </TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("customer.name")}>
                                            Customer {renderSortIcon("customer.name")}
                                        </TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("quotation_date")}>
                                            Date {renderSortIcon("quotation_date")}
                                        </TableHead>
                                        <TableHead className="cursor-pointer" onClick={() => handleSort("expired_date")}>
                                            Expire Date {renderSortIcon("expired_date")}
                                        </TableHead>
                                        <TableHead className="cursor-pointer text-right" onClick={() => handleSort("grand_total")}>
                                            Grand Total {renderSortIcon("grand_total")}
                                        </TableHead>
                                        <TableHead>Status</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {quotations.length > 0 ? (
                                        quotations.map((quotation) => {
                                            const isPending  = Number(quotation.status ?? 0) === 0;
                                            const isAccepted = Number(quotation.status ?? 0) === 1;
                                            return (
                                                <TableRow key={quotation.id}>
                                                    <TableCell>
                                                        <Checkbox checked={selectedQuotations.includes(quotation.id)} onCheckedChange={() => toggleSelectQuotation(quotation.id)} />
                                                    </TableCell>
                                                    <TableCell>{quotation.quotation_number}</TableCell>
                                                    <TableCell><CategoryBadge quotation={quotation} /></TableCell>
                                                    <TableCell>{quotation.customer ? quotation.customer.name : "-"}</TableCell>
                                                    <TableCell>{quotation.quotation_date}</TableCell>
                                                    <TableCell>{quotation.expired_date}</TableCell>
                                                    <TableCell className="text-right">{formatCurrency(quotation.grand_total)}</TableCell>
                                                    <TableCell><StatusBadge quotation={quotation} /></TableCell>
                                                    <TableCell className="text-right">
                                                        <TableActions
                                                            actions={[
                                                                ...(isPending
                                                                    ? [
                                                                          { label: "Accept", icon: <CheckCircle className="h-4 w-4" />, onClick: () => { setQuoteToAccept(quotation.id); setShowAcceptModal(true); } },
                                                                          { label: "Reject", icon: <CircleOff className="h-4 w-4" />, onClick: () => { setQuoteToReject(quotation.id); setShowRejectModal(true); }, destructive: true },
                                                                      ]
                                                                    : []),
                                                                ...(isAccepted
                                                                    ? [
                                                                          { label: "Convert", icon: <FileDown className="h-4 w-4" />, onClick: () => { setQuoteToConvert(quotation.id); setShowConvertModal(true); } },
                                                                      ]
                                                                    : []),
                                                                { label: "View", icon: <Eye className="h-4 w-4" />, href: route("underwriting_quotes.show", quotation.id) },
                                                                { label: "Edit", icon: <Edit className="h-4 w-4" />, href: route("underwriting_quotes.edit", quotation.id) },
                                                                { label: "Delete", icon: <Trash2 className="h-4 w-4" />, onClick: () => { setQuoteToDelete(quotation.id); setShowDeleteModal(true); }, destructive: true },
                                                            ]}
                                                        />
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={9} className="h-24 text-center">No underwriting quotes found.</TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {quotations.length > 0 && meta.total > 0 && (
                            <div className="flex items-center justify-between mt-4">
                                <div className="text-sm text-gray-500">
                                    Showing {(currentPage - 1) * perPage + 1} to {Math.min(currentPage * perPage, meta.total)} of {meta.total} entries
                                </div>
                                <div className="flex items-center space-x-2">
                                    <Button variant="outline" size="sm" onClick={() => handlePageChange(1)} disabled={currentPage === 1}>First</Button>
                                    <Button variant="outline" size="sm" onClick={() => handlePageChange(currentPage - 1)} disabled={currentPage === 1}>Previous</Button>
                                    {renderPageNumbers()}
                                    <Button variant="outline" size="sm" onClick={() => handlePageChange(currentPage + 1)} disabled={currentPage === meta.last_page}>Next</Button>
                                    <Button variant="outline" size="sm" onClick={() => handlePageChange(meta.last_page)} disabled={currentPage === meta.last_page}>Last</Button>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </SidebarInset>

            <DeleteModal show={showDeleteModal} onClose={() => setShowDeleteModal(false)} onConfirm={handleDelete} processing={processing} />
            <DeleteAllModal show={showDeleteAllModal} onClose={() => setShowDeleteAllModal(false)} onConfirm={handleDeleteAll} processing={processing} count={selectedQuotations.length} />
            <ActionConfirmationModal
                show={showAcceptModal}
                onClose={() => { setShowAcceptModal(false); setQuoteToAccept(null); }}
                onConfirm={handleAcceptQuotation}
                processing={processing}
                title="Are you sure you want to accept this underwriting quote?"
                confirmLabel="Accept Quote"
            />
            <ActionConfirmationModal
                show={showConvertModal}
                onClose={() => { setShowConvertModal(false); setQuoteToConvert(null); }}
                onConfirm={handleConvertQuotation}
                processing={processing}
                title="Are you sure you want to convert this accepted quote to a deferred invoice?"
                confirmLabel="Convert to Invoice"
            />
            <ActionConfirmationModal
                show={showRejectModal}
                onClose={() => { setShowRejectModal(false); setQuoteToReject(null); }}
                onConfirm={handleRejectQuotation}
                processing={processing}
                title="Are you sure you want to reject this underwriting quote?"
                confirmLabel="Reject Quote"
                confirmVariant="destructive"
            />
        </AuthenticatedLayout>
    );
}

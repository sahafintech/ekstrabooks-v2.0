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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import InputError from "@/Components/InputError";
import RichTextEditor from "@/Components/RichTextEditor";
import {
    MoreVertical,
    FileUp,
    FileDown,
    Plus,
    Mail,
    Eye,
    Trash2,
    Edit,
    ChevronUp,
    ChevronDown,
    Receipt,
    DollarSign,
    CheckCircle,
    Clock,
    AlertTriangle,
    CheckCheck,
    XCircle,
    ShieldCheck,
} from "lucide-react";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import TableActions from "@/Components/shared/TableActions";
import PageHeader from "@/Components/PageHeader";
import Modal from "@/Components/Modal";
import { formatCurrency } from "@/lib/utils";
import DateTimePicker from "@/Components/DateTimePicker";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { Badge } from "@/Components/ui/badge";

const DeleteBillModal = ({ show, onClose, onConfirm, processing }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete this bill?
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
                    Delete Bill
                </Button>
            </div>
        </form>
    </Modal>
);

const ImportBillsModal = ({ show, onClose, onSubmit, processing }) => (
    <Modal show={show} onClose={onClose} maxWidth="3xl">
        <form onSubmit={onSubmit}>
            <div className="ti-modal-header">
                <h3 className="text-lg font-bold">Import Bills</h3>
            </div>
            <div className="ti-modal-body grid grid-cols-12">
                <div className="col-span-12">
                    <div className="flex items-center justify-between">
                        <label className="block font-medium text-sm text-gray-700">
                            Bills File
                        </label>
                        <a
                            href="/uploads/media/default/sample_bills.xlsx"
                            download
                        >
                            <Button variant="secondary" size="sm" type="button">
                                Use This Sample File
                            </Button>
                        </a>
                    </div>
                    <input
                        type="file"
                        className="w-full dropify"
                        name="bills_file"
                        required
                    />
                </div>
                <div className="col-span-12 mt-4">
                    <ul className="space-y-3 text-sm">
                        <li className="flex space-x-3">
                            <span className="text-primary bg-primary/20 rounded-full px-1">
                                ✓
                            </span>
                            <span className="text-gray-800 dark:text-white/70">
                                Maximum File Size: 1 MB
                            </span>
                        </li>
                        <li className="flex space-x-3">
                            <span className="text-primary bg-primary/20 rounded-full px-1">
                                ✓
                            </span>
                            <span className="text-gray-800 dark:text-white/70">
                                File format Supported: CSV, TSV, XLS
                            </span>
                        </li>
                        <li className="flex space-x-3">
                            <span className="text-primary bg-primary/20 rounded-full px-1">
                                ✓
                            </span>
                            <span className="text-gray-800 dark:text-white/70">
                                Make sure the format of the import file matches
                                our sample file by comparing them.
                            </span>
                        </li>
                    </ul>
                </div>
            </div>
            <div className="ti-modal-footer flex justify-end mt-4">
                <Button
                    type="button"
                    variant="secondary"
                    onClick={onClose}
                    className="mr-3"
                >
                    Close
                </Button>
                <Button type="submit" disabled={processing}>
                    Import Bills
                </Button>
            </div>
        </form>
    </Modal>
);

const DeleteAllBillsModal = ({
    show,
    onClose,
    onConfirm,
    processing,
    count,
}) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <h2 className="text-lg font-medium">
                Are you sure you want to delete {count} selected bill
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
                    Delete Selected
                </Button>
            </div>
        </form>
    </Modal>
);

const BulkApproveModal = ({ show, onClose, onConfirm, processing, count }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <div className="flex items-center gap-3 mb-4">
                <div className="p-3 bg-green-100 rounded-full">
                    <CheckCheck className="h-6 w-6 text-green-600" />
                </div>
                <h2 className="text-lg font-medium">
                    Confirm Bulk Approval
                </h2>
            </div>
            <p className="text-gray-600 mb-6">
                Are you sure you want to approve {count} selected bill{count !== 1 ? "s" : ""}?
                This will update your approval status for these bills.
            </p>
            <div className="flex justify-end gap-3">
                <Button
                    type="button"
                    variant="secondary"
                    onClick={onClose}
                >
                    Cancel
                </Button>
                <Button
                    type="submit"
                    className="bg-green-600 hover:bg-green-700"
                    disabled={processing}
                >
                    {processing ? "Approving..." : "Approve Selected"}
                </Button>
            </div>
        </form>
    </Modal>
);

const BulkRejectModal = ({ show, onClose, onConfirm, processing, count }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <div className="flex items-center gap-3 mb-4">
                <div className="p-3 bg-red-100 rounded-full">
                    <XCircle className="h-6 w-6 text-red-600" />
                </div>
                <h2 className="text-lg font-medium">
                    Confirm Bulk Rejection
                </h2>
            </div>
            <p className="text-gray-600 mb-6">
                Are you sure you want to reject {count} selected bill{count !== 1 ? "s" : ""}?
                This will update your rejection status for these bills.
            </p>
            <div className="flex justify-end gap-3">
                <Button
                    type="button"
                    variant="secondary"
                    onClick={onClose}
                >
                    Cancel
                </Button>
                <Button
                    type="submit"
                    variant="destructive"
                    disabled={processing}
                >
                    {processing ? "Rejecting..." : "Reject Selected"}
                </Button>
            </div>
        </form>
    </Modal>
);

const BulkVerifyModal = ({ show, onClose, onConfirm, processing, count }) => (
    <Modal show={show} onClose={onClose}>
        <form onSubmit={onConfirm}>
            <div className="flex items-center gap-3 mb-4">
                <div className="p-3 bg-blue-100 rounded-full">
                    <ShieldCheck className="h-6 w-6 text-blue-600" />
                </div>
                <h2 className="text-lg font-medium">
                    Confirm Bulk Verification
                </h2>
            </div>
            <p className="text-gray-600 mb-6">
                Are you sure you want to verify {count} selected bill{count !== 1 ? "s" : ""}?
                This will update your verification status for these bills.
            </p>
            <div className="flex justify-end gap-3">
                <Button
                    type="button"
                    variant="secondary"
                    onClick={onClose}
                >
                    Cancel
                </Button>
                <Button
                    type="submit"
                    className="bg-blue-600 hover:bg-blue-700"
                    disabled={processing}
                >
                    {processing ? "Verifying..." : "Verify Selected"}
                </Button>
            </div>
        </form>
    </Modal>
);

const ShareSelectedModal = ({
    show,
    onClose,
    onSubmit,
    processing,
    approvers,
    emailData,
    onRecipientChange,
    onSubjectChange,
    onMessageChange,
    errors,
    selectionCount,
}) => (
    <Modal show={show} onClose={onClose} maxWidth="4xl">
        <form onSubmit={onSubmit} className="space-y-4">
            <h2 className="text-lg font-medium">Share Selected Bills via Email</h2>
            <p className="text-sm text-gray-600">
                {selectionCount} bill{selectionCount !== 1 ? "s" : ""} will be inserted into the email body.
            </p>

            <div className="grid gap-3">
                <div className="grid gap-2">
                    <Label>Recipient</Label>
                    <Select value={emailData.recipient_id} onValueChange={onRecipientChange}>
                        <SelectTrigger>
                            <SelectValue placeholder="Select recipient" />
                        </SelectTrigger>
                        <SelectContent>
                            {approvers.map((user) => (
                                <SelectItem key={user.id} value={user.id.toString()}>
                                    {user.name} ({user.email})
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                    <InputError message={errors?.recipient_id} className="text-sm" />
                </div>

                <div className="grid gap-2">
                    <Label>Subject</Label>
                    <Input value={emailData.subject} onChange={(e) => onSubjectChange(e.target.value)} />
                    <InputError message={errors?.subject} className="text-sm" />
                </div>

                <div className="grid gap-2">
                    <Label>Message</Label>
                    <RichTextEditor value={emailData.message} onChange={onMessageChange} height={260} />
                    <InputError message={errors?.message} className="text-sm" />
                </div>
            </div>

            <div className="flex justify-end gap-3 pt-2">
                <Button type="button" variant="outline" onClick={onClose}>
                    Cancel
                </Button>
                <Button type="submit" disabled={processing}>
                    {processing ? "Sending..." : "Send"}
                </Button>
            </div>
        </form>
    </Modal>
);

const DEFAULT_BILL_TEMPLATE = `<div style="font-family: Arial, sans-serif; font-size: 14px; color: #333333;"><h2 style="color: #333333;">Purchase Approval Required</h2><p>Dear {{approverName}},</p><p>We hope this email finds you well. The following purchase requests have been submitted and are awaiting your review and approval.</p><p>Please review the details below and take the necessary action at your earliest convenience.</p><table style="border-collapse: collapse; width: 100%; margin-top: 15px;"><thead><tr style="background-color: #f2f2f2;"><th style="border: 1px solid #dddddd; padding: 8px; text-align: left;">Purchase No</th><th style="border: 1px solid #dddddd; padding: 8px; text-align: left;">Supplier</th><th style="border: 1px solid #dddddd; padding: 8px; text-align: left;">Purchase Date</th><th style="border: 1px solid #dddddd; padding: 8px; text-align: right;">Total Amount</th><th style="border: 1px solid #dddddd; padding: 8px; text-align: left;">Status</th></tr></thead><tbody>{{#each purchases}}<tr><td style="border: 1px solid #dddddd; padding: 8px;"><a href="{{purchaseUrl}}" style="color:#1a73e8; text-decoration:none;">{{purchaseNumber}}</a></td><td style="border: 1px solid #dddddd; padding: 8px;">{{supplier}}</td><td style="border: 1px solid #dddddd; padding: 8px;">{{purchaseDate}}</td><td style="border: 1px solid #dddddd; padding: 8px; text-align: right;">{{totalAmount}}</td><td style="border: 1px solid #dddddd; padding: 8px;">{{status}}</td></tr>{{/each}}</tbody></table><p style="margin-top: 15px;">Status values may include <strong>Pending</strong>, <strong>Approved</strong>, or <strong>Verified</strong>.</p><p>If you have already reviewed these purchases, please disregard this email. Otherwise, we kindly request you to complete the approval process to avoid any delays.</p><p>If you have any questions or require further information, please do not hesitate to contact us.</p><p>Thank you for your time and cooperation.</p><p>Best regards,<br />{{companyName}}</p></div>`;
const BillApprovalStatusBadge = ({ status }) => {
    const statusMap = {
        0: {
            label: "Pending",
            className: "gap-1 text-gray-600 border-gray-400",
        },
        2: {
            label: "Rejected",
            className: "gap-1 text-red-600 border-red-600",
        },
        1: {
            label: "Approved",
            className: "gap-1 text-green-600 border-green-600",
        },
        4: {
            label: "Verified",
            className: "gap-1 text-blue-600 border-blue-600",
        }
    };
    const statusConfig = statusMap[status] || statusMap[0];

    return (
        <Badge variant="outline" className={statusConfig.className}>
            {statusConfig.label}
        </Badge>
    );
};

const BillStatusBadge = ({ status }) => {
    const statusMap = {
        0: {
            label: "Active",
            className: "gap-1 text-blue-600 border-blue-600",
        },
        1: {
            label: "Partial Paid",
            className: "gap-1 text-yellow-600 border-yellow-600",
        },
        2: {
            label: "Paid",
            className: "gap-1 text-green-600 border-green-600",
        },
    };

    return (
        <Badge variant="outline" className={statusMap[status].className}>
            {statusMap[status].label}
        </Badge>
    );
};

const SummaryCards = ({ summary = {} }) => {
    const cards = [
        {
            title: "Total Bills",
            value: summary.total_bills || 0,
            description: "Total number of bills",
            icon: Receipt,
            iconColor: "text-blue-500"
        },
        {
            title: "Grand Total",
            value: formatCurrency({ amount: summary.total_amount || 0 }),
            description: "Total amount of all bills",
            icon: DollarSign,
            iconColor: "text-green-500"
        },
        {
            title: "Total Approved",
            value: summary.total_approved || 0,
            description: "Approved bills",
            icon: CheckCircle,
            iconColor: "text-purple-500"
        },
        {
            title: "Total Pending",
            value: summary.total_pending || 0,
            description: "Pending bills",
            icon: Clock,
            iconColor: "text-orange-500"
        }
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
                        <p className="text-xs text-muted-foreground">
                            {card.description}
                        </p>
                    </div>
                </div>
            ))}
        </div>
    );
};

export default function List({
    bills = [],
    meta = {},
    filters = {},
    vendors = [],
    summary = {},
    trashed_bills = 0,
    hasConfiguredApprovers = false,
    hasConfiguredCheckers = false,
    approvers = [],
    emailTemplate = null,
    appUrl = "",
}) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [selectedBills, setSelectedBills] = useState([]);
    const [isAllSelected, setIsAllSelected] = useState(false);
    const [search, setSearch] = useState(filters.search || "");
    const [perPage, setPerPage] = useState(filters.per_page || 50);
    const [currentPage, setCurrentPage] = useState(meta.current_page || 1);
    const [bulkAction, setBulkAction] = useState("");
    const [sorting, setSorting] = useState(
        filters.sorting || { column: "id", direction: "desc" }
    );
    const [selectedVendor, setSelectedVendor] = useState(
        filters.vendor_id || ""
    );
    const [dateRange, setDateRange] = useState(filters.date_range || null);
    const [selectedApprovalStatus, setSelectedApprovalStatus] = useState(
        filters.approval_status || ""
    );
    const [selectedBillStatus, setSelectedBillStatus] = useState(
        filters.status || ""
    );

    // Delete confirmation modal states
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const [showImportModal, setShowImportModal] = useState(false);
    const [showDeleteAllModal, setShowDeleteAllModal] = useState(false);
    const [showBulkApproveModal, setShowBulkApproveModal] = useState(false);
    const [showBulkRejectModal, setShowBulkRejectModal] = useState(false);
    const [showBulkVerifyModal, setShowBulkVerifyModal] = useState(false);
    const [showShareModal, setShowShareModal] = useState(false);
    const [billToDelete, setBillToDelete] = useState(null);
    const [processing, setProcessing] = useState(false);
    const [shareErrors, setShareErrors] = useState({});
    const [emailData, setEmailData] = useState({
        recipient_id: approvers[0]?.id?.toString() || "",
        subject: emailTemplate?.subject || "Purchase Approval Required",
        message: "",
    });

    const approvalStatusLabel = (status) => {
        if (status === 1) return "Approved";
        if (status === 4) return "Verified";
        return "Pending";
    };

    const buildEmailBody = (recipientId) => {
        const baseUrl = appUrl || (typeof window !== "undefined" ? window.location.origin : "");
        const recipient = approvers.find(
            (u) => u.id.toString() === (recipientId || "").toString()
        );
        const companyName =
            bills[0]?.business?.business_name || bills[0]?.business?.name || "";
        const selected = bills.filter((b) => selectedBills.includes(b.id));

        const templateRaw = emailTemplate?.email_body || DEFAULT_BILL_TEMPLATE;
        const hasLoop = templateRaw.includes("{{#each purchases}}");
        const baseTemplate = hasLoop ? templateRaw : DEFAULT_BILL_TEMPLATE;

        const rowPattern = /{{#each purchases}}([\s\S]*?){{\/each}}/;
        const rowMatch = baseTemplate.match(rowPattern);
        const fallbackRowTemplate =
            '<tr><td style="border: 1px solid #dddddd; padding: 8px;"><a href="{{purchaseUrl}}" style="color:#1a73e8; text-decoration:none;">{{purchaseNumber}}</a></td><td style="border: 1px solid #dddddd; padding: 8px;">{{supplier}}</td><td style="border: 1px solid #dddddd; padding: 8px;">{{purchaseDate}}</td><td style="border: 1px solid #dddddd; padding: 8px; text-align: right;">{{totalAmount}}</td><td style="border: 1px solid #dddddd; padding: 8px;">{{status}}</td></tr>';
        const rowTemplate = rowMatch ? rowMatch[1] : fallbackRowTemplate;

        const rows = selected
            .map((b) => {
                let row = rowTemplate;
                row = row.replace(/{{purchaseUrl}}/g, `${baseUrl}/user/bill_invoices/${b.bill_no}`);
                row = row.replace(/{{purchaseNumber}}/g, b.bill_no);
                row = row.replace(/{{supplier}}/g, b.vendor?.name || "-");
                row = row.replace(/{{purchaseDate}}/g, b.purchase_date);
                row = row.replace(
                    /{{totalAmount}}/g,
                    formatCurrency({ amount: b.grand_total, currency: b.business?.currency })
                );
                row = row.replace(/{{status}}/g, approvalStatusLabel(b.approval_status));
                return row;
            })
            .join("");

        let body = baseTemplate;
        if (rowMatch) {
            body = body.replace(rowPattern, rows);
        } else if (body.includes("<tbody>")) {
            body = body.replace("<tbody>", "<tbody>" + rows);
        } else {
            body += rows;
        }

        body = body.replace(/{{approverName}}/g, recipient?.name || "");
        body = body.replace(/{{companyName}}/g, companyName);
        return body;
    };

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
        setSelectedBills(checked ? bills.map((bill) => bill.id) : []);
    };

    const toggleSelectBill = (billId) => {
        setSelectedBills((prev) => {
            if (prev.includes(billId)) {
                return prev.filter((id) => id !== billId);
            } else {
                return [...prev, billId];
            }
        });
    };

    const handleDeleteConfirm = (billId) => {
        setBillToDelete(billId);
        setShowDeleteModal(true);
    };

    const handleDelete = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.delete(route("bill_invoices.destroy", billToDelete), {
            onSuccess: () => {
                setShowDeleteModal(false);
                setBillToDelete(null);
                setProcessing(false);
                setSelectedBills((prev) =>
                    prev.filter((id) => id !== billToDelete)
                );
                toast({
                    title: "Bill Deleted",
                    description: "bill has been deleted successfully.",
                });
            },
            onError: () => {
                setProcessing(false);
                toast({
                    variant: "destructive",
                    title: "Error",
                    description: "There was an error deleting the bill.",
                });
            },
        });
    };

    const handleDeleteAll = (e) => {
        e.preventDefault();
        setProcessing(true);

        router.post(
            route("bill_invoices.bulk_destroy"),
            {
                ids: selectedBills,
            },
            {
                onSuccess: () => {
                    setShowDeleteAllModal(false);
                    setProcessing(false);
                    setSelectedBills([]);
                    setIsAllSelected(false);
                },
                onError: () => {
                    setProcessing(false);
                },
            }
        );
    };


    const handleImport = (e) => {
        e.preventDefault();
        setProcessing(true);

        const formData = new FormData(e.target);

        router.post(route("bill_invoices.import"), formData, {
            onSuccess: () => {
                setShowImportModal(false);
                setProcessing(false);
                toast({
                    title: "Import Successful",
                    description: "Bill have been imported successfully.",
                });
            },
            onError: (errors) => {
                setProcessing(false);
                toast({
                    variant: "destructive",
                    title: "Import Failed",
                    description: Object.values(errors).flat().join(", "),
                });
            },
        });
    };

    const handleSearch = (e) => {
        e.preventDefault();
        const value = e.target.value;
        setSearch(value);

        router.get(
            route("bill_invoices.index"),
            {
                search: value,
                page: 1,
                per_page: perPage,
                vendor_id: selectedVendor,
                date_range: dateRange,
                approval_status: selectedApprovalStatus,
                status: selectedBillStatus,
            },
            { preserveState: true }
        );
    };

    const handlePerPageChange = (value) => {
        setPerPage(parseInt(value));
        router.get(
            route("bill_invoices.index"),
            {
                search,
                page: 1,
                per_page: value,
                vendor_id: selectedVendor,
                date_range: dateRange,
                approval_status: selectedApprovalStatus,
                status: selectedBillStatus,
            },
            { preserveState: true }
        );
    };

    const handlePageChange = (page) => {
        setCurrentPage(page);
        router.get(
            route("bill_invoices.index"),
            {
                search,
                page,
                per_page: perPage,
                vendor_id: selectedVendor,
                date_range: dateRange,
                approval_status: selectedApprovalStatus,
                status: selectedBillStatus,
                sorting: sorting
            },
            { preserveState: true }
        );
    };

    const handleVendorChange = (value) => {
        setSelectedVendor(value);
        router.get(
            route("bill_invoices.index"),
            {
                search,
                page: 1,
                per_page: perPage,
                vendor_id: value,
                date_range: dateRange,
                approval_status: selectedApprovalStatus,
                status: selectedBillStatus,
            },
            { preserveState: true }
        );
    };

    const handleDateRangeChange = (dates) => {
        setDateRange(dates);
        router.get(
            route("bill_invoices.index"),
            {
                search,
                page: 1,
                per_page: perPage,
                vendor_id: selectedVendor,
                date_range: dates,
                approval_status: selectedApprovalStatus,
                status: selectedBillStatus,
            },
            { preserveState: true }
        );
    };

    const handleBillStatusChange = (value) => {
        setSelectedBillStatus(value);
        router.get(
            route("bill_invoices.index"),
            {
                search,
                page: 1,
                per_page: perPage,
                vendor_id: selectedVendor,
                date_range: dateRange,
                approval_status: selectedApprovalStatus,
                status: value || null
            },
            { preserveState: true }
        );
    };

    const handleApprovalStatusChange = (value) => {
        setSelectedApprovalStatus(value);
        router.get(
            route("bill_invoices.index"),
            {
                search,
                page: 1,
                per_page: perPage,
                vendor_id: selectedVendor,
                date_range: dateRange,
                approval_status: value || null,
                status: selectedBillStatus
            },
            { preserveState: true }
        );
    };

    const handleBulkAction = () => {
        if (bulkAction === "delete" && selectedBills.length > 0) {
            setShowDeleteAllModal(true);
        } else if (bulkAction === "approve" && selectedBills.length > 0) {
            setShowBulkApproveModal(true);
        } else if (bulkAction === "reject" && selectedBills.length > 0) {
            setShowBulkRejectModal(true);
        } else if (bulkAction === "verify" && selectedBills.length > 0) {
            setShowBulkVerifyModal(true);
        } else if (bulkAction === "share_email" && selectedBills.length > 0) {
            const recipientId = emailData.recipient_id || approvers[0]?.id?.toString() || "";
            setEmailData((prev) => ({
                ...prev,
                recipient_id: recipientId,
                subject: emailTemplate?.subject || "Purchase Approval Required",
                message: buildEmailBody(recipientId),
            }));
            setShareErrors({});
            setShowShareModal(true);
        }
    };

    const handleBulkApprove = (e) => {
        e.preventDefault();
        setProcessing(true);
        router.post(
            route("bill_invoices.bulk_approve"),
            { ids: selectedBills },
            {
                onSuccess: () => {
                    setProcessing(false);
                    setSelectedBills([]);
                    setIsAllSelected(false);
                    setBulkAction("");
                    setShowBulkApproveModal(false);
                },
                onError: () => {
                    setProcessing(false);
                }
            }
        );
    };

    const handleBulkReject = (e) => {
        e.preventDefault();
        setProcessing(true);
        router.post(
            route("bill_invoices.bulk_reject"),
            { ids: selectedBills },
            {
                onSuccess: () => {
                    setProcessing(false);
                    setSelectedBills([]);
                    setIsAllSelected(false);
                    setBulkAction("");
                    setShowBulkRejectModal(false);
                },
                onError: () => {
                    setProcessing(false);
                }
            }
        );
    };

    const handleBulkVerify = (e) => {
        e.preventDefault();
        setProcessing(true);
        router.post(
            route("bill_invoices.bulk_verify"),
            { ids: selectedBills },
            {
                onSuccess: () => {
                    setProcessing(false);
                    setSelectedBills([]);
                    setIsAllSelected(false);
                    setBulkAction("");
                    setShowBulkVerifyModal(false);
                },
                onError: () => {
                    setProcessing(false);
                }
            }
        );
    };

    const handleRecipientChange = (value) => {
        setEmailData((prev) => ({
            ...prev,
            recipient_id: value,
            message: buildEmailBody(value),
        }));
    };

    const handleSubjectChange = (value) => {
        setEmailData((prev) => ({ ...prev, subject: value }));
    };

    const handleMessageChange = (value) => {
        setEmailData((prev) => ({ ...prev, message: value }));
    };

    const handleShareSubmit = (e) => {
        e.preventDefault();
        setProcessing(true);
        setShareErrors({});
        router.post(
            route("bill_invoices.bulk_email"),
            {
                ids: selectedBills,
                recipient_id: emailData.recipient_id,
                subject: emailData.subject,
                message: emailData.message,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setProcessing(false);
                    setShowShareModal(false);
                    setSelectedBills([]);
                    setIsAllSelected(false);
                    setBulkAction("");
                },
                onError: (errors) => {
                    setProcessing(false);
                    setShareErrors(errors);
                },
            }
        );
    };

    const handleExport = () => {
        window.location.href = route("bill_invoices.export");
    };

    const handleSort = (column) => {
        let direction = "asc";
        if (sorting.column === column && sorting.direction === "asc") {
            direction = "desc";
        }
        setSorting({ column, direction });
        router.get(
            route("bill_invoices.index"),
            { ...filters, sorting: { column, direction } },
            { preserveState: true }
        );
    };

    const renderSortIcon = (column) => {
        const isActive = sorting.column === column;
        return (
            <span className="inline-flex flex-col ml-1">
                <ChevronUp
                    className={`w-3 h-3 ${isActive && sorting.direction === "asc"
                        ? "text-gray-800"
                        : "text-gray-300"
                        }`}
                />
                <ChevronDown
                    className={`w-3 h-3 -mt-1 ${isActive && sorting.direction === "desc"
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
                        page="Credit Purchase"
                        subpage="List"
                        url="bill_invoices.index"
                    />
                    <div className="p-4">
                        <SummaryCards summary={summary} />
                        <div className="flex flex-col md:flex-row justify-between items-start md:items-center mb-6 gap-4">
                            <div className="flex flex-col md:flex-row gap-2">
                                <Link href={route("bill_invoices.create")}>
                                    <Button>
                                        <Plus className="h-4 w-4 mr-2" />
                                        Add Credit Purchase
                                    </Button>
                                </Link>
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="secondary">
                                            <MoreVertical className="h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent align="end">
                                        <DropdownMenuItem
                                            onClick={() =>
                                                setShowImportModal(true)
                                            }
                                        >
                                            <FileUp className="mr-2 h-4 w-4" />{" "}
                                            Import
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            onClick={handleExport}
                                        >
                                            <FileDown className="mr-2 h-4 w-4" />{" "}
                                            Export
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                                <Link href={route("bill_invoices.trash")}>
                                    <Button variant="outline" className="relative">
                                        <Trash2 className="h-8 w-8" />
                                        {trashed_bills > 0 && (
                                            <span className="absolute -top-2 -right-2 bg-red-500 text-white text-xs rounded-full h-6 w-6 flex items-center justify-center font-bold">
                                                {trashed_bills}
                                            </span>
                                        )}
                                    </Button>
                                </Link>
                            </div>
                            <div className="flex flex-col md:flex-row gap-4 md:items-center">
                                <Input
                                    placeholder="Search bills..."
                                    value={search}
                                    onChange={(e) => handleSearch(e)}
                                    className="w-full md:w-80"
                                />
                            </div>
                        </div>

                        {/* Warning banner if no approvers configured */}
                        {!hasConfiguredApprovers && (
                            <div className="mb-4 p-4 bg-yellow-50 border border-yellow-200 rounded-lg flex items-center gap-3">
                                <AlertTriangle className="h-5 w-5 text-yellow-600" />
                                <div>
                                    <p className="text-sm font-medium text-yellow-800">No Approvers Configured</p>
                                    <p className="text-xs text-yellow-600">Configure purchase approval users in business settings to enable the approval workflow.</p>
                                </div>
                            </div>
                        )}

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
                                        {approvers.length > 0 && (
                                            <SelectItem value="share_email">
                                                <span className="flex items-center gap-2">
                                                    <Mail className="h-4 w-4 text-sky-600" />
                                                    Share Selected via Email
                                                </span>
                                            </SelectItem>
                                        )}
                                        <SelectItem value="delete">
                                            <span className="flex items-center gap-2">
                                                <Trash2 className="h-4 w-4 text-red-600" />
                                                Delete Selected
                                            </span>
                                        </SelectItem>
                                        {hasConfiguredCheckers && (
                                            <SelectItem value="verify">
                                                <span className="flex items-center gap-2">
                                                    <ShieldCheck className="h-4 w-4 text-blue-600" />
                                                    Verify Selected
                                                </span>
                                            </SelectItem>
                                        )}
                                        {hasConfiguredApprovers && (
                                            <>
                                                <SelectItem value="approve">
                                                    <span className="flex items-center gap-2">
                                                        <CheckCheck className="h-4 w-4 text-green-600" />
                                                        Approve Selected
                                                    </span>
                                                </SelectItem>
                                                <SelectItem value="reject">
                                                    <span className="flex items-center gap-2">
                                                        <XCircle className="h-4 w-4 text-red-600" />
                                                        Reject Selected
                                                    </span>
                                                </SelectItem>
                                            </>
                                        )}
                                    </SelectContent>
                                </Select>
                                <Button
                                    onClick={handleBulkAction}
                                    variant="outline"
                                >
                                    Apply
                                </Button>

                                <SearchableCombobox
                                    options={vendors.map((vendor) => ({
                                        id: vendor.id,
                                        name: vendor.name,
                                    }))}
                                    value={selectedVendor}
                                    onChange={handleVendorChange}
                                    placeholder="Select vendor"
                                    className="w-[200px]"
                                />

                                <DateTimePicker
                                    value={dateRange}
                                    onChange={handleDateRangeChange}
                                    className="w-[200px]"
                                    isRange={true}
                                    placeholder="Select date range"
                                />

                                <SearchableCombobox
                                    options={[
                                        { id: "", name: "All" },
                                        { id: "0", name: "Pending" },
                                        { id: "1", name: "Approved" },
                                        { id: "4", name: "Verified" }
                                    ]}
                                    value={selectedApprovalStatus}
                                    onChange={handleApprovalStatusChange}
                                    placeholder="Select approval status"
                                    className="w-[200px]"
                                />

                                <SearchableCombobox
                                    options={[
                                        { id: "", name: "All" },
                                        { id: "0", name: "Active" },
                                        { id: "1", name: "Partial Paid" },
                                        { id: "2", name: "Paid" },
                                    ]}
                                    value={selectedBillStatus}
                                    onChange={handleBillStatusChange}
                                    placeholder="Select purchase status"
                                    className="w-[200px]"
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
                                            onClick={() =>
                                                handleSort("bill_no")
                                            }
                                        >
                                            Bill Number{" "}
                                            {renderSortIcon("bill_no")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() =>
                                                handleSort("vendor.name")
                                            }
                                        >
                                            Vendor{" "}
                                            {renderSortIcon("vendor.name")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() =>
                                                handleSort("purchase_date")
                                            }
                                        >
                                            Date{" "}
                                            {renderSortIcon("purchase_date")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() =>
                                                handleSort("due_date")
                                            }
                                        >
                                            Due Date{" "}
                                            {renderSortIcon("due_date")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer text-right"
                                            onClick={() =>
                                                handleSort("grand_total")
                                            }
                                        >
                                            Grand Total{" "}
                                            {renderSortIcon("grand_total")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer text-right"
                                            onClick={() => handleSort("paid")}
                                        >
                                            Paid {renderSortIcon("paid")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer text-right"
                                        >
                                            Due
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer text-right"
                                            onClick={() =>
                                                handleSort("approval_status")
                                            }
                                        >
                                            Approval Status{" "}
                                            {renderSortIcon("approval_status")}
                                        </TableHead>
                                        <TableHead
                                            className="cursor-pointer"
                                            onClick={() => handleSort("status")}
                                        >
                                            Bill Status{" "}
                                            {renderSortIcon("status")}
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Actions
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {bills.length > 0 ? (
                                        bills.map((bill) => (
                                            <TableRow key={bill.id}>
                                                <TableCell>
                                                    <Checkbox
                                                        checked={selectedBills.includes(
                                                            bill.id
                                                        )}
                                                        onCheckedChange={() =>
                                                            toggleSelectBill(
                                                                bill.id
                                                            )
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <Link
                                                        href={route(
                                                            "bill_invoices.show",
                                                            bill.id
                                                        )}
                                                        className="text-blue-500 underline"
                                                    >
                                                        {bill.bill_no}
                                                    </Link>
                                                </TableCell>
                                                <TableCell>
                                                    {bill.vendor
                                                        ? bill.vendor.name
                                                        : "-"}
                                                </TableCell>
                                                <TableCell>
                                                    {bill.purchase_date}
                                                </TableCell>
                                                <TableCell>
                                                    {bill.due_date}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {bill.grand_total !== bill.converted_total ? (
                                                        <span>
                                                            {formatCurrency({ amount: bill.grand_total, currency: bill.business.currency })} ({formatCurrency({ amount: bill.converted_total, currency: bill.currency })})
                                                        </span>
                                                    ) : (
                                                        <span>
                                                            {formatCurrency({ amount: bill.grand_total, currency: bill.business.currency })}
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency({
                                                        amount: bill.paid,
                                                    })}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {bill.grand_total !== bill.converted_total ? (
                                                        <span>
                                                            {formatCurrency({ amount: bill.grand_total - bill.paid, currency: bill.business.currency })} ({formatCurrency({ amount: bill.converted_total - bill.paid, currency: bill.currency })})
                                                        </span>
                                                    ) : (
                                                        <span>
                                                            {formatCurrency({ amount: bill.grand_total - bill.paid, currency: bill.business.currency })}
                                                        </span>
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <BillApprovalStatusBadge
                                                        status={
                                                            bill.approval_status
                                                        }
                                                    />
                                                </TableCell>
                                                <TableCell>
                                                    <BillStatusBadge
                                                        status={bill.status}
                                                    />
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    <TableActions
                                                        actions={[
                                                            {
                                                                label: "View",
                                                                icon: (
                                                                    <Eye className="h-4 w-4" />
                                                                ),
                                                                href: route(
                                                                    "bill_invoices.show",
                                                                    bill.id
                                                                ),
                                                            },
                                                            {
                                                                label: "Edit",
                                                                icon: (
                                                                    <Edit className="h-4 w-4" />
                                                                ),
                                                                href: route(
                                                                    "bill_invoices.edit",
                                                                    bill.id
                                                                ),
                                                            },
                                                            {
                                                                label: "Delete",
                                                                icon: (
                                                                    <Trash2 className="h-4 w-4" />
                                                                ),
                                                                onClick: () =>
                                                                    handleDeleteConfirm(
                                                                        bill.id
                                                                    ),
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
                                                colSpan={11}
                                                className="h-24 text-center"
                                            >
                                                No bills found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {bills.length > 0 && meta.total > 0 && (
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

            <DeleteBillModal
                show={showDeleteModal}
                onClose={() => setShowDeleteModal(false)}
                onConfirm={handleDelete}
                processing={processing}
            />

            <DeleteAllBillsModal
                show={showDeleteAllModal}
                onClose={() => setShowDeleteAllModal(false)}
                onConfirm={handleDeleteAll}
                processing={processing}
                count={selectedBills.length}
            />


            <ImportBillsModal
                show={showImportModal}
                onClose={() => setShowImportModal(false)}
                onSubmit={handleImport}
                processing={processing}
            />

            <BulkApproveModal
                show={showBulkApproveModal}
                onClose={() => setShowBulkApproveModal(false)}
                onConfirm={handleBulkApprove}
                processing={processing}
                count={selectedBills.length}
            />

            <BulkRejectModal
                show={showBulkRejectModal}
                onClose={() => setShowBulkRejectModal(false)}
                onConfirm={handleBulkReject}
                processing={processing}
                count={selectedBills.length}
            />

            <BulkVerifyModal
                show={showBulkVerifyModal}
                onClose={() => setShowBulkVerifyModal(false)}
                onConfirm={handleBulkVerify}
                processing={processing}
                count={selectedBills.length}
            />

            <ShareSelectedModal
                show={showShareModal}
                onClose={() => setShowShareModal(false)}
                onSubmit={handleShareSubmit}
                processing={processing}
                approvers={approvers}
                emailData={emailData}
                onRecipientChange={handleRecipientChange}
                onSubjectChange={handleSubjectChange}
                onMessageChange={handleMessageChange}
                errors={shareErrors}
                selectionCount={selectedBills.length}
            />
        </AuthenticatedLayout>
    );
}

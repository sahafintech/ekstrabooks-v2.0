import { Link, useForm, usePage, router } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset, SidebarSeparator } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { formatCurrency } from "@/lib/utils";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger
} from "@/Components/ui/dropdown-menu";
import PageHeader from "@/Components/PageHeader";
import {
    PrinterIcon,
    MailIcon,
    DownloadIcon,
    MoreVertical,
    ShareIcon,
    Edit,
    Facebook,
    MessageCircle,
    Copy,
    PaperclipIcon,
    CheckCircle,
    XCircle,
    Clock,
    Users,
    ShieldCheck
} from "lucide-react";
import { useState, useEffect } from "react";
import { Table, TableBody, TableHeader, TableRow, TableHead, TableCell } from "@/Components/ui/table";
import Modal from "@/Components/Modal";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import InputError from "@/Components/InputError";
import RichTextEditor from "@/Components/RichTextEditor";
import { QRCodeSVG } from 'qrcode.react';
import { Badge } from "@/Components/ui/badge";
import DrawerComponent from "@/Components/DrawerComponent";
import { Textarea } from "@/Components/ui/textarea";
import { toast } from "sonner";

const printStyles = `
  @media print {
        body * {
            visibility: hidden;
        }

        #printable-area, #printable-area * {
            visibility: visible;
        }

        #printable-area {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            margin: 0;
            padding: 0;
            border: none;
            height: 100%;
        }

        .group.peer.hidden.text-sidebar-foreground {
            display: none !important;
        }

        @page {
            size: auto;
            margin: 10mm;
        }

        body {
            margin: 0;
            padding: 0;
        }
    }
`;

export default function View({ bill, attachments, decimalPlace, email_templates, approvalUsersCount, hasConfiguredApprovers, checkerUsersCount, hasConfiguredCheckers }) {
    const { flash = {} } = usePage().props;
    const { toast: toastHook } = useToast();
    const [isLoading, setIsLoading] = useState({
        print: false,
        email: false,
        pdf: false
    });
    const [isEmailModalOpen, setIsEmailModalOpen] = useState(false);
    const [isShareModalOpen, setIsShareModalOpen] = useState(false);
    const [isAttachmentsModalOpen, setIsAttachmentsModalOpen] = useState(false);
    const [isApprovalsDrawerOpen, setIsApprovalsDrawerOpen] = useState(false);
    const [isCheckersDrawerOpen, setIsCheckersDrawerOpen] = useState(false);
    const [isApproveRejectModalOpen, setIsApproveRejectModalOpen] = useState(false);
    const [isVerifyModalOpen, setIsVerifyModalOpen] = useState(false);
    const [approvalAction, setApprovalAction] = useState(null); // 'approve' or 'reject'
    const [approvalComment, setApprovalComment] = useState('');
    const [verifyComment, setVerifyComment] = useState('');
    const [isSubmittingApproval, setIsSubmittingApproval] = useState(false);
    const [isSubmittingVerify, setIsSubmittingVerify] = useState(false);
    const [shareLink, setShareLink] = useState('');

    const { data, setData, post, processing, errors, reset } = useForm({
        email: bill?.vendor?.email || "",
        subject: "",
        message: "",
        template: "",
    });

    const handleApprovalAction = (action) => {
        setApprovalAction(action);
        setApprovalComment('');
        setIsApproveRejectModalOpen(true);
    };

    const handleVerifyAction = () => {
        setVerifyComment('');
        setIsVerifyModalOpen(true);
    };

    const submitApprovalAction = () => {
        if (approvalAction === 'reject' && !approvalComment.trim()) {
            toast.error("Comment is required when rejecting");
            return;
        }

        setIsSubmittingApproval(true);

        router.post(route(`bill_invoices.${approvalAction}`, bill.id), {
            comment: approvalComment
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setIsApproveRejectModalOpen(false);
                setApprovalComment('');
                setIsSubmittingApproval(false);
                toast.success(`Bill ${approvalAction}d successfully`);
            },
            onError: (errors) => {
                setIsSubmittingApproval(false);
                if (errors.comment) {
                    toast.error(errors.comment);
                } else {
                    toast.error(`Failed to ${approvalAction} bill`);
                }
            }
        });
    };

    const submitVerifyAction = () => {
        setIsSubmittingVerify(true);

        router.post(route('bill_invoices.verify', bill.id), {
            comment: verifyComment
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setIsVerifyModalOpen(false);
                setVerifyComment('');
                setIsSubmittingVerify(false);
                toast.success('Bill verified successfully');
            },
            onError: (errors) => {
                setIsSubmittingVerify(false);
                if (errors.comment) {
                    toast.error(errors.comment);
                } else {
                    toast.error('Failed to verify bill');
                }
            }
        });
    };

    // Get current user's approval status
    const userApproval = bill.approvals?.find(approval => approval.action_user?.id === usePage().props.auth.user.id);
    // User can only take approval actions if:
    // 1. There are configured approvers in the system
    // 2. AND the user is one of the assigned approvers
    const canApprove = hasConfiguredApprovers && userApproval !== undefined;
    const currentStatus = userApproval?.status || null;
    // Check individual user's approval status:
    // - status 0 (pending): Show both Approve and Reject buttons
    // - status 1 (approved): Show only Reject button (to change vote)
    // - status 2 (rejected): Show only Approve button (to change vote)
    const hasAlreadyApproved = currentStatus === 1;
    const hasAlreadyRejected = currentStatus === 2;

    // Get current user's checker status
    const userChecker = bill.checkers?.find(checker => checker.action_user?.id === usePage().props.auth.user.id);
    // User can only take verify actions if:
    // 1. There are configured checkers in the system
    // 2. AND the user is one of the assigned checkers
    const canVerify = hasConfiguredCheckers && userChecker !== undefined;
    const currentVerifyStatus = userChecker?.status || null;
    // Check individual user's checker status:
    // - status 0 (pending): Show Verify button
    // - status 1 (verified): Already verified
    // - status 2 (rejected): Show Verify button (to change vote)
    const hasAlreadyVerified = currentVerifyStatus === 1;

    const handlePrint = () => {
        setIsLoading(prev => ({ ...prev, print: true }));
        setTimeout(() => {
            window.print();
            setIsLoading(prev => ({ ...prev, print: false }));
        }, 300);
    };

    useEffect(() => {
        if (flash && flash.success) {
            toastHook({
                title: "Success",
                description: flash.success,
            });
        }

        if (flash && flash.error) {
            toastHook({
                variant: "destructive",
                title: "Error",
                description: flash.error,
            });
        }
    }, [flash, toastHook]);

    const BillStatusBadge = ({ status }) => {
        const statusMap = {
            0: {
                label: "Active",
                className: "text-blue-600",
            },
            1: {
                label: "Partial Paid",
                className: "text-yellow-600",
            },
            2: {
                label: "Paid",
                className: "text-green-600",
            },
        };

        return (
            <Badge variant="outline" className="gap-1 text-green-600 border-green-600 mt-2">
                {statusMap[status].label}: (Paid {formatCurrency({ amount: bill.paid })})
            </Badge>
        );
    };

    const handleEmailInvoice = () => {
        setIsEmailModalOpen(true);
    };

    const handleEmailSubmit = (e) => {
        e.preventDefault();
        post(route("bill_invoices.send_email", bill.id), {
            preserveScroll: true,
            onSuccess: () => {
                setIsEmailModalOpen(false);
                reset();
            },
        });
    };

    const handleTemplateChange = (templateSlug) => {
        const template = email_templates.find(t => t.slug === templateSlug);
        if (template) {
            setData("template", templateSlug);
            setData("subject", template.subject);
            setData("message", template.email_body);
        }
    };

    const handleDownloadPDF = () => {
        window.open(route('bill_invoices.pdf', bill.id), '_blank');
    };

    const handleShareLink = () => {
        const link = route('bill_invoices.show_public_bill_invoice', bill.short_code);
        setShareLink(link);
        setIsShareModalOpen(true);
    };

    const handleCopyLink = () => {
        navigator.clipboard.writeText(shareLink);
        toastHook({
            title: "Success",
            description: "Link copied to clipboard",
        });
    };

    const handleWhatsAppShare = () => {
        const text = `Check out this bill: ${shareLink}`;
        window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
    };

    const handleFacebookShare = () => {
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareLink)}`, '_blank');
    };

    return (
        <AuthenticatedLayout>
            <Toaster />

            <SidebarInset>
                <style dangerouslySetInnerHTML={{ __html: printStyles }} />
                <div className="space-y-4">
                    <PageHeader
                        page="Credit Purchase"
                        subpage={`Credit Purchase #${bill.bill_no}`}
                        url="bill_invoices.index"
                    />

                    <div className="flex items-center justify-end space-x-2 mb-4">
                        {checkerUsersCount > 0 && (
                            <Button
                                variant="outline"
                                onClick={() => setIsCheckersDrawerOpen(true)}
                                className="flex items-center"
                            >
                                <ShieldCheck className="mr-2 h-4 w-4" />
                                Checkers
                                <Badge variant="secondary" className="ml-2">
                                    {checkerUsersCount}
                                </Badge>
                            </Button>
                        )}
                        {approvalUsersCount > 0 && (
                            <Button
                                variant="outline"
                                onClick={() => setIsApprovalsDrawerOpen(true)}
                                className="flex items-center"
                            >
                                <Users className="mr-2 h-4 w-4" />
                                Approvals
                                <Badge variant="secondary" className="ml-2">
                                    {approvalUsersCount}
                                </Badge>
                            </Button>
                        )}
                        {attachments && attachments.length > 0 && (
                            <Button
                                variant="outline"
                                onClick={() => setIsAttachmentsModalOpen(true)}
                                className="flex items-center"
                            >
                                <PaperclipIcon className="mr-2 h-4 w-4" />
                                Attachments ({attachments.length})
                            </Button>
                        )}
                        <Button
                            variant="outline"
                            onClick={handlePrint}
                            disabled={isLoading.print}
                            className="flex items-center"
                        >
                            <PrinterIcon className="mr-2 h-4 w-4" />
                            {isLoading.print ? "Printing..." : "Print"}
                        </Button>

                        <Button
                            variant="outline"
                            onClick={handleEmailInvoice}
                            disabled={isLoading.email}
                            className="flex items-center"
                        >
                            <MailIcon className="mr-2 h-4 w-4" />
                            {isLoading.email ? "Sending..." : "Email"}
                        </Button>

                        <Button
                            variant="outline"
                            onClick={handleDownloadPDF}
                            className="flex items-center"
                        >
                            <DownloadIcon className="mr-2 h-4 w-4" />
                            Download PDF
                        </Button>

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline" size="icon">
                                    <MoreVertical className="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem onClick={handleShareLink}>
                                    <ShareIcon className="mr-2 h-4 w-4" />
                                    <span>Share Link</span>
                                </DropdownMenuItem>
                                {canVerify && !hasAlreadyVerified && (
                                    <DropdownMenuItem onClick={() => handleVerifyAction()}>
                                        <ShieldCheck className="mr-2 h-4 w-4 text-blue-600" />
                                        <span>Verify</span>
                                    </DropdownMenuItem>
                                )}
                                {canApprove && (
                                    <>
                                        {!hasAlreadyApproved && (
                                            <DropdownMenuItem onClick={() => handleApprovalAction('approve')}>
                                                <CheckCircle className="mr-2 h-4 w-4 text-green-600" />
                                                <span>Approve</span>
                                            </DropdownMenuItem>
                                        )}
                                        {!hasAlreadyRejected && (
                                            <DropdownMenuItem onClick={() => handleApprovalAction('reject')}>
                                                <XCircle className="mr-2 h-4 w-4 text-red-600" />
                                                <span>Reject</span>
                                            </DropdownMenuItem>
                                        )}
                                    </>
                                )}
                                <DropdownMenuItem asChild>
                                    <Link href={route("bill_invoices.edit", bill.id)} className="flex items-center">
                                        <Edit className="mr-2 h-4 w-4" />
                                        <span>Edit Credit Purchase</span>
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    {/* Warning banner when no approvers are configured */}
                    {!hasConfiguredApprovers && bill.approval_status === 0 && (
                        <div className="p-4 rounded-lg border border-yellow-400 bg-yellow-50 dark:bg-yellow-900/20 dark:border-yellow-600 print:hidden">
                            <div className="flex items-start gap-3">
                                <div className="flex-shrink-0 mt-0.5">
                                    <svg className="h-5 w-5 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 className="text-sm font-medium text-yellow-800 dark:text-yellow-200">No Approvers Assigned</h3>
                                    <p className="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                        This bill cannot be approved or rejected until approvers are configured.
                                        Go to <span className="font-medium">Settings → Business Settings → Approval Workflows</span> to assign approvers.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Warning banner when no checkers are configured */}
                    {!hasConfiguredCheckers && bill.approval_status === 0 && (
                        <div className="p-4 rounded-lg border border-orange-400 bg-orange-50 dark:bg-orange-900/20 dark:border-orange-600 print:hidden">
                            <div className="flex items-start gap-3">
                                <div className="flex-shrink-0 mt-0.5">
                                    <ShieldCheck className="h-5 w-5 text-orange-600 dark:text-orange-400" />
                                </div>
                                <div>
                                    <h3 className="text-sm font-medium text-orange-800 dark:text-orange-200">No Checkers Assigned</h3>
                                    <p className="text-sm text-orange-700 dark:text-orange-300 mt-1">
                                        This bill cannot be verified until checkers are configured.
                                        Go to <span className="font-medium">Settings → Business Settings → Checker Workflows</span> to assign checkers.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Email Modal */}
                    <Modal
                        show={isEmailModalOpen}
                        onClose={() => setIsEmailModalOpen(false)}
                        maxWidth="3xl"
                    >
                        <div className="mb-6">
                            <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Send Bill Email
                            </h2>
                        </div>

                        <form
                            onSubmit={handleEmailSubmit}
                            className="space-y-4"
                        >
                            <div className="grid gap-4">
                                <div className="grid gap-2">
                                    <Label htmlFor="email">
                                        Recipient Email
                                    </Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        value={data.email}
                                        onChange={(e) =>
                                            setData("email", e.target.value)
                                        }
                                        required
                                    />
                                    <InputError
                                        message={errors.email}
                                        className="text-sm"
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="template">
                                        Email Template
                                    </Label>
                                    <SearchableCombobox
                                        options={email_templates?.map(
                                            (template) => ({
                                                id: template.slug,
                                                name: template.name,
                                            })
                                        )}
                                        value={data.template}
                                        onChange={handleTemplateChange}
                                        placeholder="Select a template"
                                        emptyMessage="No templates found"
                                    />
                                    <InputError
                                        message={errors.template}
                                        className="text-sm"
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="subject">Subject</Label>
                                    <Input
                                        id="subject"
                                        value={data.subject}
                                        onChange={(e) =>
                                            setData("subject", e.target.value)
                                        }
                                        required
                                    />
                                    <InputError
                                        message={errors.subject}
                                        className="text-sm"
                                    />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="message">Message</Label>
                                    <RichTextEditor
                                        value={data.message}
                                        onChange={(content) => setData("message", content)}
                                        height={250}
                                    />
                                    <InputError
                                        message={errors.message}
                                        className="text-sm"
                                    />
                                </div>
                            </div>

                            <div className="mt-6 flex justify-end space-x-3">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setIsEmailModalOpen(false)}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={processing}>
                                    {processing ? "Sending..." : "Send Email"}
                                </Button>
                            </div>
                        </form>
                    </Modal>

                    {/* Attachments Modal */}
                    <Modal
                        show={isAttachmentsModalOpen}
                        onClose={() => setIsAttachmentsModalOpen(false)}
                        maxWidth="4xl"
                    >
                        <div className="mb-6">
                            <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Bill Attachments
                            </h2>
                            <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                View and download files attached to this bill
                            </p>
                        </div>

                        <div className="overflow-hidden border rounded-md">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>File Name</TableHead>
                                        <TableHead>Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {attachments.map((attachment, index) => (
                                        <TableRow key={index}>
                                            <TableCell className="font-medium">
                                                {attachment.file_name}
                                            </TableCell>
                                            <TableCell>
                                                <a
                                                    href={`${attachment.path}`}
                                                    target="_blank"
                                                    className="text-blue-600 hover:text-blue-800 hover:underline flex items-center"
                                                    download
                                                >
                                                    <DownloadIcon className="h-4 w-4 mr-1" />
                                                    Download
                                                </a>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </div>

                        <div className="mt-6 flex justify-end">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsAttachmentsModalOpen(false)}
                            >
                                Close
                            </Button>
                        </div>
                    </Modal>

                    {/* Share Modal */}
                    <Modal
                        show={isShareModalOpen}
                        onClose={() => setIsShareModalOpen(false)}
                        maxWidth="2xl"
                    >
                        <div className="mb-6">
                            <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Share Bill
                            </h2>
                        </div>

                        <div className="space-y-4">
                            <div className="flex items-center space-x-2">
                                <Input
                                    value={shareLink}
                                    readOnly
                                    className="flex-1"
                                />
                                <Button
                                    variant="outline"
                                    onClick={handleCopyLink}
                                    className="flex items-center"
                                >
                                    <Copy className="mr-2 h-4 w-4" />
                                    Copy
                                </Button>
                            </div>

                            <div className="flex justify-center space-x-4 pt-4">
                                <Button
                                    variant="outline"
                                    onClick={handleWhatsAppShare}
                                    className="flex items-center"
                                >
                                    <MessageCircle className="mr-2 h-4 w-4" />
                                    WhatsApp
                                </Button>
                                <Button
                                    variant="outline"
                                    onClick={handleFacebookShare}
                                    className="flex items-center"
                                >
                                    <Facebook className="mr-2 h-4 w-4" />
                                    Facebook
                                </Button>
                            </div>
                        </div>

                        <div className="mt-6 flex justify-end">
                            <Button
                                type="button"
                                variant="outline"
                                onClick={() => setIsShareModalOpen(false)}
                            >
                                Close
                            </Button>
                        </div>
                    </Modal>

                    <div id="printable-area" className="lg:w-[210mm] min-h-[297mm] mx-auto rounded-md border p-4">
                        <div className="p-6 sm:p-8">
                            {/* Invoice Header */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
                                <div>
                                    {bill.business.logo && (
                                        <div className="mb-3">
                                            <img
                                                src={`/uploads/media/${bill.business.logo}`}
                                                alt="Business Logo"
                                                className="max-h-32 object-contain"
                                            />
                                        </div>
                                    )}
                                    <h2 className="text-2xl font-bold text-primary">
                                        {bill.business.business_name}
                                    </h2>
                                    <div className="mt-2 text-sm">
                                        <p>{bill.business.address}</p>
                                        <p>{bill.business.business_email}</p>
                                        <p>{bill.business.phone}</p>
                                    </div>
                                </div>
                                <div className="sm:text-right">
                                    <h1 className="text-2xl font-bold">{bill.title}</h1>
                                    <div className="mt-2 text-sm">
                                        <p><span className="font-medium">Credit Purchase #:</span> {bill.bill_no}</p>
                                        <p><span className="font-medium">Credit Purchase Date:</span> {bill.purchase_date}</p>
                                        <p><span className="font-medium">Due Date:</span> {bill.due_date}</p>
                                        {bill.order_number && (
                                            <p><span className="font-medium">Order Number:</span> {bill.order_number}</p>
                                        )}
                                        <BillStatusBadge status={bill.status} />
                                    </div>
                                    <div className="mt-4 sm:flex sm:justify-end">
                                        <QRCodeSVG
                                            value={route('bill_invoices.show_public_bill_invoice', bill.short_code)}
                                            size={100}
                                            level="H"
                                            includeMargin={true}
                                            className="print:block"
                                        />
                                    </div>
                                </div>
                            </div>

                            <SidebarSeparator className="my-6" />

                            {/* Customer Information */}
                            <div className="mb-8">
                                <h3 className="font-medium text-lg mb-2">Bill To:</h3>
                                <div className="text-sm">
                                    <p className="font-medium">{bill.vendor?.name}</p>
                                    {bill.vendor?.company_name && <p>{bill.vendor?.company_name}</p>}
                                    <p>{bill.vendor?.address}</p>
                                    <p>{bill.vendor?.email}</p>
                                    <p>{bill.vendor?.mobile}</p>
                                </div>
                            </div>

                            {/* Invoice Items */}
                            <div className="mb-8">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead className="print:hidden">Account</TableHead>
                                            <TableHead>Item</TableHead>
                                            <TableHead>Description</TableHead>
                                            <TableHead className="text-right">Quantity</TableHead>
                                            <TableHead className="text-right">Unit Cost</TableHead>
                                            <TableHead className="text-right">Total</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {bill.items.map((item, index) => (
                                            <TableRow key={index}>
                                                <TableCell className="print:hidden">
                                                    {item.account?.account_name ? (
                                                        <Badge variant="outline" className="gap-1 text-green-600 border-green-600">{item.account.account_name}</Badge>
                                                    ) : '-'}
                                                </TableCell>
                                                <TableCell className="font-medium">{item.product_name}</TableCell>
                                                <TableCell>{item.description}</TableCell>
                                                <TableCell className="text-right">{item.quantity}</TableCell>
                                                <TableCell className="text-right">{formatCurrency(item.unit_cost, bill.currency, decimalPlace)}</TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency(item.quantity * item.unit_cost, bill.currency, decimalPlace)}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>

                            {/* Invoice Summary */}
                            <div className="flex justify-end">
                                <div className="w-full md:w-1/2 lg:w-1/3 space-y-2">
                                    <div className="flex justify-between py-2 border-t">
                                        <span className="font-medium">Subtotal:</span>
                                        <span>{formatCurrency(bill.sub_total, bill.business.currency, decimalPlace)}</span>
                                    </div>

                                    {/* Tax details */}
                                    {bill.taxes.map((tax, index) => (
                                        <div key={index} className="flex justify-between py-2">
                                            <span>
                                                {tax.name}:
                                            </span>
                                            <span>{formatCurrency(tax.amount, bill.business.currency, decimalPlace)}</span>
                                        </div>
                                    ))}

                                    {/* Discount */}
                                    {bill.discount > 0 && (
                                        <div className="flex justify-between py-2">
                                            <span>
                                                Discount
                                            </span>
                                            <span>-{formatCurrency(bill.discount, bill.business.currency, decimalPlace)}</span>
                                        </div>
                                    )}

                                    {/* Total */}
                                    <div className="flex justify-between py-3 border-t border-b font-bold text-lg">
                                        <span>Total:</span>
                                        <span>{formatCurrency(bill.grand_total, bill.business.currency, decimalPlace)}</span>
                                    </div>

                                    {/* Base currency equivalent if different currency */}
                                    {bill.currency !== bill.business.currency && (
                                        <div className="flex justify-between py-2 text-gray-500 text-sm">
                                            <span>Exchange Rate:</span>
                                            <span>
                                                1 {bill.business.currency} = {formatCurrency(bill.exchange_rate, bill.currency, decimalPlace)}
                                            </span>
                                        </div>
                                    )}

                                    {/* Base currency equivalent total */}
                                    {bill.currency !== bill.business.currency && (
                                        <div className="flex justify-between py-2 text-sm text-gray-600">
                                            <span>Equivalent to:</span>
                                            <span>
                                                {formatCurrency(
                                                    bill.converted_total,
                                                    bill.currency,
                                                    decimalPlace
                                                )}
                                            </span>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Notes & Terms */}
                            {(bill.note || bill.footer) && (
                                <div className="mt-8 space-y-4">
                                    {bill.note && (
                                        <div>
                                            <h3 className="font-medium mb-1">Notes:</h3>
                                            <p className="text-sm">{bill.note}</p>
                                        </div>
                                    )}

                                    {bill.footer && (
                                        <div>
                                            <h3 className="font-medium mb-1">Terms & Conditions:</h3>
                                            <p className="text-sm">{bill.footer}</p>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </SidebarInset>

            {/* Approvals Drawer */}
            <DrawerComponent
                open={isApprovalsDrawerOpen}
                onOpenChange={setIsApprovalsDrawerOpen}
                title="Bill Approvals"
                position="right"
                width="w-4xl"
            >
                <div className="p-4">
                    <div className="mb-4">
                        <p className="text-sm text-gray-600">
                            View approval status from all assigned approvers
                        </p>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Comment</TableHead>
                                <TableHead>Date</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {bill.approvals && bill.approvals.length > 0 ? (
                                bill.approvals.map((approval, index) => (
                                    <TableRow key={index}>
                                        <TableCell className="font-medium">
                                            {approval.action_user?.name || 'N/A'}
                                        </TableCell>
                                        <TableCell>
                                            {approval.action_user?.email || 'N/A'}
                                        </TableCell>
                                        <TableCell>
                                            {approval.status === 0 && (
                                                <Badge variant="outline" className="flex items-center w-fit">
                                                    <Clock className="h-3 w-3 mr-1" />
                                                    Pending
                                                </Badge>
                                            )}
                                            {approval.status === 1 && (
                                                <Badge variant="outline" className="flex items-center w-fit text-green-600 border-green-600">
                                                    <CheckCircle className="h-3 w-3 mr-1" />
                                                    Approved
                                                </Badge>
                                            )}
                                            {approval.status === 2 && (
                                                <Badge variant="outline" className="flex items-center w-fit text-red-600 border-red-600">
                                                    <XCircle className="h-3 w-3 mr-1" />
                                                    Rejected
                                                </Badge>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {approval.comment || '-'}
                                        </TableCell>
                                        <TableCell>
                                            {approval.action_date ? new Date(approval.action_date).toLocaleDateString() : '-'}
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell colSpan={5} className="text-center py-8">
                                        <div className="flex flex-col items-center">
                                            <Users className="h-10 w-10 text-yellow-500 mb-3" />
                                            <p className="font-medium text-yellow-700 dark:text-yellow-400 mb-2">No Approvers Assigned</p>
                                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                                This bill cannot be approved until approvers are configured.
                                            </p>
                                            <p className="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                                Go to <span className="font-medium">Settings → Business Settings → Approval Workflows</span> to assign approvers.
                                            </p>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
            </DrawerComponent>

            {/* Approve/Reject Modal */}
            <Modal
                show={isApproveRejectModalOpen}
                onClose={() => setIsApproveRejectModalOpen(false)}
                maxWidth="md"
            >
                <div className="mb-6">
                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center">
                        {approvalAction === 'approve' ? (
                            <>
                                <CheckCircle className="h-5 w-5 mr-2 text-green-600" />
                                Approve Bill
                            </>
                        ) : (
                            <>
                                <XCircle className="h-5 w-5 mr-2 text-red-600" />
                                Reject Bill
                            </>
                        )}
                    </h2>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {approvalAction === 'approve'
                            ? 'Add an optional comment for this approval'
                            : 'Please provide a reason for rejecting this bill'}
                    </p>
                </div>

                <div className="mb-6">
                    <label className="block text-sm font-medium mb-2">
                        Comment {approvalAction === 'reject' && <span className="text-red-600">*</span>}
                    </label>
                    <Textarea
                        value={approvalComment}
                        onChange={(e) => setApprovalComment(e.target.value)}
                        placeholder={approvalAction === 'approve' ? "Add a comment (optional)" : "Explain why you're rejecting this bill"}
                        rows={4}
                        className="w-full"
                    />
                </div>

                <div className="flex justify-end space-x-2">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => setIsApproveRejectModalOpen(false)}
                        disabled={isSubmittingApproval}
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        onClick={submitApprovalAction}
                        disabled={isSubmittingApproval}
                        className={approvalAction === 'approve' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'}
                    >
                        {isSubmittingApproval
                            ? (approvalAction === 'approve' ? 'Approving...' : 'Rejecting...')
                            : (approvalAction === 'approve' ? 'Approve' : 'Reject')
                        }
                    </Button>
                </div>
            </Modal>

            {/* Checkers Drawer */}
            <DrawerComponent
                open={isCheckersDrawerOpen}
                onOpenChange={setIsCheckersDrawerOpen}
                title="Bill Verification"
                position="right"
                width="w-4xl"
            >
                <div className="p-4">
                    <div className="mb-4">
                        <p className="text-sm text-gray-600">
                            View verification status from all assigned checkers
                        </p>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Name</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Comment</TableHead>
                                <TableHead>Date</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {bill.checkers && bill.checkers.length > 0 ? (
                                bill.checkers.map((checker, index) => (
                                    <TableRow key={index}>
                                        <TableCell className="font-medium">
                                            {checker.action_user?.name || 'N/A'}
                                        </TableCell>
                                        <TableCell>
                                            {checker.action_user?.email || 'N/A'}
                                        </TableCell>
                                        <TableCell>
                                            {checker.status === 0 && (
                                                <Badge variant="outline" className="flex items-center w-fit">
                                                    <Clock className="h-3 w-3 mr-1" />
                                                    Pending
                                                </Badge>
                                            )}
                                            {checker.status === 1 && (
                                                <Badge variant="outline" className="flex items-center w-fit text-blue-600 border-blue-600">
                                                    <ShieldCheck className="h-3 w-3 mr-1" />
                                                    Verified
                                                </Badge>
                                            )}
                                            {checker.status === 2 && (
                                                <Badge variant="outline" className="flex items-center w-fit text-red-600 border-red-600">
                                                    <XCircle className="h-3 w-3 mr-1" />
                                                    Rejected
                                                </Badge>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {checker.comment || '-'}
                                        </TableCell>
                                        <TableCell>
                                            {checker.action_date ? new Date(checker.action_date).toLocaleDateString() : '-'}
                                        </TableCell>
                                    </TableRow>
                                ))
                            ) : (
                                <TableRow>
                                    <TableCell colSpan={5} className="text-center py-8">
                                        <div className="flex flex-col items-center">
                                            <ShieldCheck className="h-10 w-10 text-orange-500 mb-3" />
                                            <p className="font-medium text-orange-700 dark:text-orange-400 mb-2">No Checkers Assigned</p>
                                            <p className="text-sm text-gray-500 dark:text-gray-400">
                                                This bill cannot be verified until checkers are configured.
                                            </p>
                                            <p className="text-xs text-gray-400 dark:text-gray-500 mt-1">
                                                Go to <span className="font-medium">Settings → Business Settings → Checker Workflows</span> to assign checkers.
                                            </p>
                                        </div>
                                    </TableCell>
                                </TableRow>
                            )}
                        </TableBody>
                    </Table>
                </div>
            </DrawerComponent>

            {/* Verify Modal */}
            <Modal
                show={isVerifyModalOpen}
                onClose={() => setIsVerifyModalOpen(false)}
                maxWidth="md"
            >
                <div className="mb-6">
                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center">
                        <ShieldCheck className="h-5 w-5 mr-2 text-blue-600" />
                        Verify Bill
                    </h2>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Add an optional comment for this verification
                    </p>
                </div>

                <div className="mb-6">
                    <label className="block text-sm font-medium mb-2">
                        Comment
                    </label>
                    <Textarea
                        value={verifyComment}
                        onChange={(e) => setVerifyComment(e.target.value)}
                        placeholder="Add a comment (optional)"
                        rows={4}
                        className="w-full"
                    />
                </div>

                <div className="flex justify-end space-x-2">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => setIsVerifyModalOpen(false)}
                        disabled={isSubmittingVerify}
                    >
                        Cancel
                    </Button>
                    <Button
                        type="button"
                        onClick={submitVerifyAction}
                        disabled={isSubmittingVerify}
                        className="bg-blue-600 hover:bg-blue-700"
                    >
                        {isSubmittingVerify ? 'Verifying...' : 'Verify'}
                    </Button>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}

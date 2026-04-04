import { Link, useForm, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { formatCurrency } from "@/lib/utils";
import { Toaster } from "@/Components/ui/toaster";
import { useToast } from "@/hooks/use-toast";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
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
    PaperclipIcon
} from "lucide-react";
import { useState, useEffect } from "react";
import {
    Table,
    TableBody,
    TableHeader,
    TableRow,
    TableHead,
    TableCell,
} from "@/Components/ui/table";
import Modal from "@/Components/Modal";
import { Input } from "@/Components/ui/input";
import { Label } from "@/Components/ui/label";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import InputError from "@/Components/InputError";
import RichTextEditor from "@/Components/RichTextEditor";

const printStyles = `
@media print {
      body * {
          visibility: hidden;
          -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
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
          size: A4 portrait;
          margin: 8mm;
      }

      body {
          margin: 0;
          padding: 0;
          background: white;
      }
  }
`;

const sectionTitleStyle = (primaryColor, textColor) => ({
    backgroundColor: primaryColor,
    color: textColor,
    letterSpacing: "0.12em",
});
export default function View({
    invoice,
    attachments = [],
    decimalPlaces,
    email_templates = [],
}) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [isLoading, setIsLoading] = useState({
        print: false,
        email: false,
        pdf: false,
    });
    const [isEmailModalOpen, setIsEmailModalOpen] = useState(false);
    const [isShareModalOpen, setIsShareModalOpen] = useState(false);
    const [isAttachmentsModalOpen, setIsAttachmentsModalOpen] = useState(false);
    const [shareLink, setShareLink] = useState("");

    const { data, setData, post, processing, errors, reset } = useForm({
        email: invoice?.customer?.email || "",
        subject: "",
        message: "",
        template: "",
    });

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

    const getBusinessSettingValue = (name, fallback) =>
        invoice?.business?.system_settings?.find((setting) => setting.name === name)?.value ||
        fallback;

    const primaryColor = getBusinessSettingValue("invoice_primary_color", "#6d0e47");
    const textColor = getBusinessSettingValue("invoice_text_color", "#ffffff");
    const businessName =
        invoice?.business?.business_name || invoice?.business?.name || "Business";
    const businessEmail =
        invoice?.business?.business_email || invoice?.business?.email || "";
    const businessPhone = invoice?.business?.phone || invoice?.business?.mobile || "";
    const businessWebsite = invoice?.business?.website || "";
    const quantityLabel =
        invoice?.invoice_category === "medical" ? "Members" : "Quantity";
    const preparedBy = invoice?.created_by?.name || businessName;
    const items = invoice?.items || [];
    const bankAccounts = invoice?.business?.bank_accounts || [];
    const fillerRows = Array.from({
        length: Math.max(0, 10 - items.length),
    });
    const paidAmount = Number(invoice?.paid || 0);
    const outstandingAmount = Math.max(
        0,
        Number(invoice?.grand_total || 0) - paidAmount
    );
    const policyPeriod = [invoice?.deffered_start, invoice?.deffered_end]
        .filter(Boolean)
        .join(" - ");

    const formatMoney = (amount, currency = invoice?.currency) =>
        formatCurrency({
            amount,
            currency,
            decimalPlaces,
        });

    const handlePrint = () => {
        setIsLoading((prev) => ({ ...prev, print: true }));
        setTimeout(() => {
            window.print();
            setIsLoading((prev) => ({ ...prev, print: false }));
        }, 300);
    };

    const handleEmailInvoice = () => {
        setIsEmailModalOpen(true);
    };

    const handleEmailSubmit = (e) => {
        e.preventDefault();
        post(route("deffered_invoices.send_email", invoice.id), {
            preserveScroll: true,
            onSuccess: () => {
                setIsEmailModalOpen(false);
                reset();
            },
        });
    };

    const handleTemplateChange = (templateSlug) => {
        const template = email_templates.find((t) => t.slug === templateSlug);
        if (template) {
            setData("template", templateSlug);
            setData("subject", template.subject);
            setData("message", template.email_body);
        }
    };

    const handleDownloadPDF = () => {
        window.open(route('deffered_invoices.pdf', invoice.id), '_blank');
    };

    const handleShareLink = () => {
        const link = route(
            "deffered_invoices.show_public_deffered_invoice",
            invoice.short_code
        );
        setShareLink(link);
        setIsShareModalOpen(true);
    };

    const handleCopyLink = () => {
        navigator.clipboard.writeText(shareLink);
        toast({
            title: "Success",
            description: "Link copied to clipboard",
        });
    };

    const handleWhatsAppShare = () => {
        const text = `Check out this invoice: ${shareLink}`;
        window.open(
            `https://wa.me/?text=${encodeURIComponent(text)}`,
            "_blank"
        );
    };

    const handleFacebookShare = () => {
        window.open(
            `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(
                shareLink
            )}`,
            "_blank"
        );
    };

    return (
        <AuthenticatedLayout>
            <Toaster />

            <SidebarInset>
                <style dangerouslySetInnerHTML={{ __html: printStyles }} />
                <div className="space-y-4">
                    <PageHeader
                        page="Deffered Invoices"
                        subpage={`Invoice #${invoice.invoice_number}`}
                        url="deffered_invoices.index"
                    />

                    <div className="flex items-center justify-end space-x-2 mb-4">
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
                                <DropdownMenuItem asChild>
                                    <Link
                                        href={route(
                                            "deffered_invoices.edit",
                                            invoice.id
                                        )}
                                        className="flex items-center"
                                    >
                                        <Edit className="mr-2 h-4 w-4" />
                                        <span>Edit Invoice</span>
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    {/* Email Modal */}
                    <Modal
                        show={isEmailModalOpen}
                        onClose={() => setIsEmailModalOpen(false)}
                        maxWidth="3xl"
                    >
                        <div className="mb-6">
                            <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Send Invoice Email
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
                                        onChange={(content) =>
                                            setData("message", content)
                                        }
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
                                Deffered Invoice Attachments
                            </h2>
                            <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                View and download files attached to this deffered invoice
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
                                                    href={attachment.download_url || attachment.path}
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
                                Share Invoice
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

                    <div className="overflow-x-auto pb-4">
                        <div
                            id="printable-area"
                            className="mx-auto min-h-[297mm] w-[210mm] max-w-full bg-white"
                        >
                            <div className="border-[10px] p-3" style={{ borderColor: primaryColor }}>
                                <div className="mb-4 h-4" style={{ backgroundColor: primaryColor }} />

                                <div className="mb-4 flex items-start justify-between gap-4">
                                    <div className="max-w-[260px]">
                                        {invoice?.business?.logo ? (
                                            <img
                                                src={`/uploads/media/${invoice.business.logo}`}
                                                alt="Business Logo"
                                                className="max-h-24 object-contain"
                                            />
                                        ) : (
                                            <div
                                                className="text-3xl font-bold uppercase"
                                                style={{ color: primaryColor }}
                                            >
                                                {businessName}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 gap-0 border border-slate-900 text-sm">
                                    <div className="col-span-7 border-r border-slate-900 px-3 py-3">
                                        <div className="space-y-1.5">
                                            <div>
                                                <span className="font-semibold uppercase">
                                                    Invoice To:
                                                </span>{" "}
                                                {invoice?.customer?.name || "-"}
                                            </div>
                                            <div>
                                                <span className="font-semibold uppercase">
                                                    Policy No:
                                                </span>{" "}
                                                {invoice?.order_number || "-"}
                                            </div>
                                            {policyPeriod && (
                                                <div>
                                                    <span className="font-semibold uppercase">
                                                        Policy Period:
                                                    </span>{" "}
                                                    {policyPeriod}
                                                </div>
                                            )}
                                            {invoice?.invoice_category && (
                                                <div>
                                                    <span className="font-semibold uppercase">
                                                        Insurance Class:
                                                    </span>{" "}
                                                    {invoice.invoice_category.toUpperCase()}
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    <div className="col-span-5 px-3 py-3">
                                        <div className="space-y-1.5">
                                            <div className="flex justify-between gap-4">
                                                <span className="font-semibold uppercase">
                                                    Invoice Number:
                                                </span>
                                                <span>{invoice?.invoice_number || "-"}</span>
                                            </div>
                                            <div className="flex justify-between gap-4">
                                                <span className="font-semibold uppercase">
                                                    Invoice Date:
                                                </span>
                                                <span>{invoice?.invoice_date || "-"}</span>
                                            </div>
                                            <div className="flex justify-between gap-4">
                                                <span className="font-semibold uppercase">
                                                    Due Date:
                                                </span>
                                                <span>{invoice?.due_date || "-"}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div
                                    className="mt-4 px-2 py-1 text-center text-2xl font-bold uppercase leading-none"
                                    style={sectionTitleStyle(primaryColor, textColor)}
                                >
                                    Invoice
                                </div>

                                <div className="border-x border-b border-slate-900">
                                    <table className="w-full border-collapse text-xs">
                                        <thead>
                                            <tr>
                                                <th
                                                    className="border border-slate-900 px-2 py-1 text-left"
                                                    style={sectionTitleStyle(primaryColor, textColor)}
                                                >
                                                    Class
                                                </th>
                                                <th
                                                    className="border border-slate-900 px-2 py-1 text-left"
                                                    style={sectionTitleStyle(primaryColor, textColor)}
                                                >
                                                    Description
                                                </th>
                                                <th
                                                    className="border border-slate-900 px-2 py-1 text-right"
                                                    style={sectionTitleStyle(primaryColor, textColor)}
                                                >
                                                    {quantityLabel}
                                                </th>
                                                <th
                                                    className="border border-slate-900 px-2 py-1 text-right"
                                                    style={sectionTitleStyle(primaryColor, textColor)}
                                                >
                                                    Rate
                                                </th>
                                                <th
                                                    className="border border-slate-900 px-2 py-1 text-right"
                                                    style={sectionTitleStyle(primaryColor, textColor)}
                                                >
                                                    Amount
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {items.map((item, index) => (
                                                <tr key={index}>
                                                    <td className="border border-slate-900 px-2 py-2 align-top">
                                                        {item.product_name || "-"}
                                                    </td>
                                                    <td className="border border-slate-900 px-2 py-2 align-top">
                                                        <div className="font-medium text-slate-900">
                                                            {item.description || "-"}
                                                        </div>
                                                        <div className="mt-1 space-y-0.5 text-[11px] text-slate-700">
                                                            {item.benefits && (
                                                                <div>
                                                                    Benefits: {item.benefits}
                                                                </div>
                                                            )}
                                                            {invoice?.invoice_category === "medical" &&
                                                                item.family_size && (
                                                                    <div>
                                                                        Family Size: {item.family_size}
                                                                    </div>
                                                                )}
                                                            {invoice?.invoice_category === "other" &&
                                                                Number(item.sum_insured) > 0 && (
                                                                    <div>
                                                                        Sum Insured: {formatMoney(item.sum_insured)}
                                                                    </div>
                                                                )}
                                                        </div>
                                                    </td>
                                                    <td className="border border-slate-900 px-2 py-2 text-right align-top">
                                                        {item.quantity}
                                                    </td>
                                                    <td className="border border-slate-900 px-2 py-2 text-right align-top">
                                                        {formatMoney(item.unit_cost)}
                                                    </td>
                                                    <td className="border border-slate-900 px-2 py-2 text-right align-top">
                                                        {formatMoney(
                                                            Number(item.quantity || 0) *
                                                                Number(item.unit_cost || 0)
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}

                                            {fillerRows.map((_, index) => (
                                                <tr key={`filler-${index}`} className="h-8">
                                                    <td className="border border-slate-900 px-2 py-2" />
                                                    <td className="border border-slate-900 px-2 py-2" />
                                                    <td className="border border-slate-900 px-2 py-2" />
                                                    <td className="border border-slate-900 px-2 py-2" />
                                                    <td className="border border-slate-900 px-2 py-2" />
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                <div className="grid grid-cols-12 gap-0 border border-slate-900 border-t-0">
                                    <div className="col-span-7 border-r border-slate-900">
                                        <div
                                            className="px-2 py-1 text-xs font-bold uppercase"
                                            style={sectionTitleStyle(primaryColor, textColor)}
                                        >
                                            Bank Details
                                        </div>

                                        <table className="w-full border-collapse text-xs">
                                            <tbody>
                                                {bankAccounts.length > 0 ? (
                                                    bankAccounts.map((bank) => (
                                                        <tr key={bank.id}>
                                                            <td className="w-40 border-b border-slate-900 px-2 py-2 font-semibold align-top">
                                                                {bank.bank_name}
                                                            </td>
                                                            <td className="border-b border-slate-900 px-2 py-2 align-top">
                                                                <div>{bank.account_number || "-"}</div>
                                                                {bank.account_name && (
                                                                    <div className="text-[11px] text-slate-600">
                                                                        {bank.account_name}
                                                                    </div>
                                                                )}
                                                                {bank.swift_code && (
                                                                    <div className="text-[11px] text-slate-600">
                                                                        Swift: {bank.swift_code}
                                                                    </div>
                                                                )}
                                                            </td>
                                                        </tr>
                                                    ))
                                                ) : (
                                                    <tr>
                                                        <td
                                                            colSpan={2}
                                                            className="px-2 py-4 text-center text-slate-500"
                                                        >
                                                            No bank details available.
                                                        </td>
                                                    </tr>
                                                )}
                                            </tbody>
                                        </table>

                                        {(invoice?.note || invoice?.footer) && (
                                            <div className="border-t border-slate-900 text-xs">
                                                {invoice?.note && (
                                                    <div className="px-3 py-2">
                                                        <span className="font-semibold uppercase">
                                                            Notes:
                                                        </span>{" "}
                                                        {invoice.note}
                                                    </div>
                                                )}
                                                {invoice?.footer && (
                                                    <div
                                                        className={`px-3 py-2 ${
                                                            invoice.note
                                                                ? "border-t border-slate-900"
                                                                : ""
                                                        }`}
                                                    >
                                                        <span className="font-semibold uppercase">
                                                            Terms:
                                                        </span>{" "}
                                                        {invoice.footer}
                                                    </div>
                                                )}
                                            </div>
                                        )}
                                    </div>

                                    <div className="col-span-5">
                                        <div
                                            className="px-2 py-1 text-xs font-bold uppercase"
                                            style={sectionTitleStyle(primaryColor, textColor)}
                                        >
                                            Invoice Summary
                                        </div>

                                        <table className="w-full border-collapse text-sm">
                                            <tbody>
                                                <tr>
                                                    <td className="border-b border-slate-900 px-3 py-2 font-semibold">
                                                        Subtotal
                                                    </td>
                                                    <td className="border-b border-slate-900 px-3 py-2 text-right">
                                                        {formatMoney(invoice?.sub_total)}
                                                    </td>
                                                </tr>

                                                {(invoice?.taxes || []).map((tax, index) => (
                                                    <tr key={index}>
                                                        <td className="border-b border-slate-900 px-3 py-2 font-semibold">
                                                            {tax.name}
                                                        </td>
                                                        <td className="border-b border-slate-900 px-3 py-2 text-right">
                                                            {formatMoney(tax.amount)}
                                                        </td>
                                                    </tr>
                                                ))}

                                                {Number(invoice?.discount) > 0 && (
                                                    <tr>
                                                        <td className="border-b border-slate-900 px-3 py-2 font-semibold">
                                                            Discount
                                                        </td>
                                                        <td className="border-b border-slate-900 px-3 py-2 text-right">
                                                            -{formatMoney(invoice.discount)}
                                                        </td>
                                                    </tr>
                                                )}

                                                <tr>
                                                    <td className="border-b border-slate-900 px-3 py-2 font-semibold">
                                                        Total Invoice Amount
                                                    </td>
                                                    <td className="border-b border-slate-900 px-3 py-2 text-right">
                                                        {formatMoney(invoice?.grand_total)}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td className="border-b border-slate-900 px-3 py-2 font-semibold">
                                                        Credit Applied
                                                    </td>
                                                    <td className="border-b border-slate-900 px-3 py-2 text-right">
                                                        {formatMoney(paidAmount)}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td className="px-3 py-2 font-bold uppercase">
                                                        Net Amount
                                                    </td>
                                                    <td className="px-3 py-2 text-right font-bold">
                                                        {formatMoney(outstandingAmount)}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>

                                        {invoice?.currency !== invoice?.business?.currency && (
                                            <div className="border-t border-slate-900 px-3 py-2 text-[11px] text-slate-700">
                                                <div>
                                                    Exchange Rate: 1 {invoice.business.currency} ={" "}
                                                    {formatMoney(
                                                        invoice.exchange_rate,
                                                        invoice.currency
                                                    )}
                                                </div>
                                                <div>
                                                    Equivalent Total:{" "}
                                                    {formatMoney(
                                                        invoice.converted_total,
                                                        invoice.currency
                                                    )}
                                                </div>
                                            </div>
                                        )}
                                    </div>
                                </div>

                                <div className="grid grid-cols-12 gap-0 border-x border-b border-slate-900">
                                    <div className="col-span-7 px-4 py-6 text-sm">
                                        <div className="font-semibold">
                                            Prepared By: {preparedBy}
                                        </div>
                                        <div className="mt-3 font-semibold">Signature:</div>
                                        <div className="mt-6 w-40 border-b border-slate-900" />
                                    </div>

                                    <div className="col-span-5 px-4 py-6 text-xs text-slate-700">
                                        {invoice?.invoice_category && (
                                            <div className="mb-1">
                                                <span className="font-semibold uppercase">
                                                    Category:
                                                </span>{" "}
                                                {invoice.invoice_category.toUpperCase()}
                                            </div>
                                        )}
                                        {policyPeriod && (
                                            <div className="mb-1">
                                                <span className="font-semibold uppercase">
                                                    Policy Period:
                                                </span>{" "}
                                                {policyPeriod}
                                            </div>
                                        )}
                                        {invoice?.currency && (
                                            <div>
                                                <span className="font-semibold uppercase">
                                                    Currency:
                                                </span>{" "}
                                                {invoice.currency}
                                            </div>
                                        )}
                                    </div>
                                </div>

                                <div
                                    className="mt-4 px-4 py-4 text-center text-sm font-semibold"
                                    style={{
                                        backgroundColor: primaryColor,
                                        color: textColor,
                                    }}
                                >
                                    {[businessPhone, businessEmail, businessWebsite]
                                        .filter(Boolean)
                                        .join(" | ")}
                                </div>
                            </div>
                        </div>
                    </div>

                    <div className="print:hidden">
                        <div className="border bg-white p-4">
                            <h3 className="mb-4 font-medium">
                                Deferred Earnings Schedule
                            </h3>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Earning Start Date</TableHead>
                                            <TableHead>Earning End Date</TableHead>
                                            <TableHead>No Of Days</TableHead>
                                            <TableHead>Earning Recognized</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {invoice.deffered_earnings &&
                                        invoice.deffered_earnings.length > 0 ? (
                                            <>
                                                {invoice.deffered_earnings.map((earning, idx) => (
                                                    <TableRow
                                                        key={idx}
                                                        className={earning.status === 1 ? "bg-gray-100" : ""}
                                                    >
                                                        <TableCell>{earning.start_date}</TableCell>
                                                        <TableCell>{earning.end_date}</TableCell>
                                                        <TableCell>{earning.days}</TableCell>
                                                        <TableCell className="text-right">
                                                            {formatMoney(earning.transaction_amount)}
                                                            {earning.status === 1 && (
                                                                <span className="ml-2 text-xs text-green-600">
                                                                    (Earned)
                                                                </span>
                                                            )}
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                                <TableRow>
                                                    <TableCell className="font-bold">TOTAL:</TableCell>
                                                    <TableCell colSpan={2}></TableCell>
                                                    <TableCell className="text-right font-bold">
                                                        {formatMoney(
                                                            invoice.deffered_earnings.reduce(
                                                                (sum, earning) =>
                                                                    sum +
                                                                    (parseFloat(
                                                                        earning.transaction_amount
                                                                    ) || 0),
                                                                0
                                                            )
                                                        )}
                                                    </TableCell>
                                                </TableRow>
                                            </>
                                        ) : (
                                            <TableRow>
                                                <TableCell colSpan={4} className="h-24 text-center">
                                                    No deferred earnings found.
                                                </TableCell>
                                            </TableRow>
                                        )}
                                    </TableBody>
                                </Table>
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}

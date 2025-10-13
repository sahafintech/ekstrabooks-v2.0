import { Link, router, useForm, usePage } from "@inertiajs/react";
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
import { QRCodeSVG } from "qrcode.react";

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
          size: auto;
          margin: 10mm;
      }

      body {
          margin: 0;
          padding: 0;
      }
  }
`;



export default function View({
    invoice,
    attachments,
    decimalPlace,
    email_templates,
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

                    <div id="printable-area" className="lg:w-[210mm] min-h-[297mm] mx-auto rounded-md border p-4">
                        <div className="p-6 sm:p-8">
                            {/* Invoice Header */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
                                <div>
                                    {invoice.business.logo && (
                                        <div className="mb-3">
                                            <img
                                                src={`/uploads/media/${invoice.business.logo}`}
                                                alt="Business Logo"
                                                className="max-h-32 object-contain"
                                            />
                                        </div>
                                    )}
                                    <h2 className="text-2xl font-bold text-primary">
                                        {invoice.business.business_name}
                                    </h2>
                                    <div className="mt-2 text-sm">
                                        <p>{invoice.business.address}</p>
                                        <p>{invoice.business.business_email}</p>
                                        <p>{invoice.business.phone}</p>
                                    </div>
                                </div>
                                <div className="sm:text-right">
                                    <h1 className="text-2xl font-bold">
                                        {invoice.title}
                                    </h1>
                                    <div className="mt-2 text-sm">
                                        <p>
                                            <span className="font-medium">
                                                Invoice #:
                                            </span>{" "}
                                            {invoice.invoice_number}
                                        </p>
                                        {invoice.order_number && (
                                            <p>
                                                <span className="font-medium">
                                                    Policy Number:
                                                </span>{" "}
                                                {invoice.order_number}
                                            </p>
                                        )}
                                        <p>
                                            <span className="font-medium">
                                                Invoice Date:
                                            </span>{" "}
                                            {invoice.invoice_date}
                                        </p>
                                        <p>
                                            <span className="font-medium">
                                                Due Date:
                                            </span>{" "}
                                            {invoice.due_date}
                                        </p>
                                    </div>
                                    </div>
                            </div>
                            <div className="mt-4 grid grid-cols-2 sm:grid-cols-2 gap-4">
                                {/* bank details */}
                                <div>
                                    <h3 className="font-medium text-lg mb-2">
                                        Bank Details:
                                    </h3>
                                    {invoice.business.bank_accounts.map((bank) => (
                                        <div key={bank.id} className="text-sm bg-gray-100 p-2 rounded-md m-1">
                                            <p>Bank Name: <strong>{bank.bank_name}</strong></p>
                                            <p>Account Number: <strong>{bank.account_number}</strong></p>
                                            <p>Account Name: <strong>{bank.account_name}</strong></p>
                                        </div>
                                    ))}
                                </div>
                                <div className="flex justify-end">
                                    <QRCodeSVG
                                    value={route(
                                        "deffered_invoices.show_public_deffered_invoice",
                                        invoice.short_code
                                    )}
                                    size={100}
                                    level="H"
                                    includeMargin={true}
                                        margin={10}
                                        className="print:block"
                                    />
                                </div>
                            </div>

                            <SidebarSeparator className="my-6" />

                            {/* Customer Information */}
                            <div className="mb-8">
                                <h3 className="font-medium text-lg mb-2">
                                    Bill To:
                                </h3>
                                <div className="text-sm">
                                    <p className="font-medium">
                                        {invoice.customer?.name}
                                    </p>
                                    {invoice.customer?.company_name && (
                                        <p>{invoice.customer?.company_name}</p>
                                    )}
                                    <p>{invoice.customer?.address}</p>
                                    <p>{invoice.customer?.email}</p>
                                    <p>{invoice.customer?.mobile}</p>
                                </div>
                            </div>

                            {/* Invoice Items */}
                            <div className="mb-8">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Item</TableHead>
                                            <TableHead>Benefit</TableHead>
                                            {invoice.invoice_category ==
                                                "other" && (
                                                <TableHead className="text-right">
                                                    Sum Insured
                                                </TableHead>
                                            )}
                                            {invoice.invoice_category ===
                                                "medical" && (
                                                <TableHead>
                                                    Family Size
                                                </TableHead>
                                            )}
                                            {invoice.invoice_category ===
                                            "medical" ? (
                                                <TableHead className="text-right">
                                                    Members
                                                </TableHead>
                                            ) : (
                                                <TableHead className="text-right">
                                                    Quantity
                                                </TableHead>
                                            )}
                                            <TableHead className="text-right">
                                                Unit Cost
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Total
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {invoice.items.map((item, index) => (
                                            <TableRow key={index}>
                                                <TableCell className="font-medium">
                                                    {item.product_name}
                                                </TableCell>
                                                <TableCell>
                                                    {item.benefits}
                                                </TableCell>
                                                {invoice.invoice_category ==
                                                    "other" && (
                                                    <TableCell className="text-right">
                                                        {formatCurrency(
                                                            item.sum_insured,
                                                            invoice.currency,
                                                            decimalPlace
                                                        )}
                                                    </TableCell>
                                                )}
                                                {invoice.invoice_category ===
                                                    "medical" && (
                                                    <TableCell>
                                                        {item.family_size}
                                                    </TableCell>
                                                )}
                                                <TableCell className="text-right">
                                                    {item.quantity}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency(
                                                        item.unit_cost,
                                                        invoice.currency,
                                                        decimalPlace
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency(
                                                        item.quantity *
                                                            item.unit_cost,
                                                        invoice.currency,
                                                        decimalPlace
                                                    )}
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
                                        <span className="font-medium">
                                            Subtotal:
                                        </span>
                                        <span>
                                            {formatCurrency(
                                                invoice.sub_total,
                                                invoice.business.currency,
                                                decimalPlace
                                            )}
                                        </span>
                                    </div>

                                    {/* Tax details */}
                                    {invoice.taxes.map((tax, index) => (
                                        <div
                                            key={index}
                                            className="flex justify-between py-2"
                                        >
                                            <span>{tax.name}:</span>
                                            <span>
                                                {formatCurrency(
                                                    tax.amount,
                                                    invoice.currency,
                                                    decimalPlace
                                                )}
                                            </span>
                                        </div>
                                    ))}

                                    {/* Discount */}
                                    {invoice.discount > 0 && (
                                        <div className="flex justify-between py-2">
                                            <span>Discount</span>
                                            <span>
                                                -
                                                {formatCurrency(
                                                    invoice.discount,
                                                    invoice.business.currency,
                                                    decimalPlace
                                                )}
                                            </span>
                                        </div>
                                    )}

                                    {/* Total */}
                                    <div className="flex justify-between py-3 border-t border-b font-bold text-lg">
                                        <span>Total:</span>
                                        <span>
                                            {formatCurrency(
                                                invoice.grand_total,
                                                invoice.business.currency,
                                                decimalPlace
                                            )}
                                        </span>
                                    </div>

                                    {/* Base currency equivalent if different currency */}
                                    {invoice.currency !==
                                        invoice.business.currency && (
                                        <div className="flex justify-between py-2 text-gray-500 text-sm">
                                            <span>Exchange Rate:</span>
                                            <span>
                                                1 {invoice.business.currency} ={" "}
                                                {formatCurrency(
                                                    invoice.exchange_rate,
                                                    invoice.currency,
                                                    decimalPlace
                                                )}
                                            </span>
                                        </div>
                                    )}

                                    {/* Base currency equivalent total */}
                                    {invoice.currency !==
                                        invoice.business.currency && (
                                        <div className="flex justify-between py-2 text-sm text-gray-600">
                                            <span>Equivalent to:</span>
                                            <span>
                                                {formatCurrency(
                                                    invoice.converted_total,
                                                    invoice.currency,
                                                    decimalPlace
                                                )}
                                            </span>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Notes & Terms */}
                            {(invoice.note || invoice.footer) && (
                                <div className="mt-8 space-y-4">
                                    {invoice.note && (
                                        <div>
                                            <h3 className="font-medium mb-1">
                                                Notes:
                                            </h3>
                                            <p className="text-sm">
                                                {invoice.note}
                                            </p>
                                        </div>
                                    )}

                                    {invoice.footer && (
                                        <div>
                                            <h3 className="font-medium mb-1">
                                                Terms & Conditions:
                                            </h3>
                                            <p className="text-sm">
                                                {invoice.footer}
                                            </p>
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Deferred Earnings Schedule - hidden when printing */}
                            <div className="mt-8 print:hidden">
                                <h3 className="font-medium mb-4">Deferred Earnings Schedule</h3>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Earning Start Date</TableHead>
                                            <TableHead>Earning End Date</TableHead>
                                            <TableHead>No Of Days</TableHead>
                                            <TableHead>Earning recognized</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {invoice.deffered_earnings && invoice.deffered_earnings.length > 0 ? (
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
                                                            {formatCurrency(earning.transaction_amount, invoice.currency, decimalPlace)}
                                                            {earning.status === 1 && (
                                                                <span className="ml-2 text-xs text-green-600">(Earned)</span>
                                                            )}
                                                        </TableCell>
                                                    </TableRow>
                                                ))}
                                                <TableRow>
                                                    <TableCell className="font-bold">TOTAL:</TableCell>
                                                    <TableCell colSpan={2}></TableCell>
                                                    <TableCell className="text-right font-bold">
                                                        {formatCurrency(
                                                            invoice.deffered_earnings.reduce((sum, e) => sum + (parseFloat(e.transaction_amount) || 0), 0),
                                                            invoice.currency,
                                                            decimalPlace
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
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}

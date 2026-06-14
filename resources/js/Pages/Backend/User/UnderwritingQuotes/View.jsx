import { Link, router } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { formatCurrency } from "@/lib/utils";
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
} from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import Modal from "@/Components/Modal";
import { Input } from "@/Components/ui/input";
import { Textarea } from "@/Components/ui/textarea";
import { QRCodeSVG } from "qrcode.react";
import QuotationSections from "@/Components/shared/QuotationSections";

const buildPrintStyles = () => `
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

export default function View({ quotation, decimalPlace, email_templates = [] }) {
    const [isLoading, setIsLoading]               = useState({ print: false });
    const [isShareModalOpen, setIsShareModalOpen] = useState(false);
    const [shareLink, setShareLink]               = useState("");
    const [showEmailModal, setShowEmailModal]     = useState(false);
    const [emailProcessing, setEmailProcessing]   = useState(false);
    const [emailForm, setEmailForm]               = useState({ email: "", subject: "", message: "", template: "" });

    const getBusinessSettingValue = (name, fallback) =>
        quotation?.business?.system_settings?.find((s) => s.name === name)?.value || fallback;

    const primaryColor      = getBusinessSettingValue("invoice_primary_color", "#6d0e47");
    const textColor         = getBusinessSettingValue("invoice_text_color", "#ffffff");
    const businessName      = quotation?.business?.business_name || quotation?.business?.name || "Business";
    const businessEmail     = quotation?.business?.business_email || quotation?.business?.email || "";
    const quotationSections = quotation?.sections || [];
    const sectionStyle      = sectionTitleStyle(primaryColor, textColor);

    const formatMoney = (amount, currency = quotation?.currency) =>
        formatCurrency({ amount, currency, decimalPlaces: decimalPlace });

    const formatItemRate = (item) =>
        item.calculation_type === "percentage_of_amount"
            ? `${Number(item.rate_value ?? 0)}%`
            : formatMoney(item.unit_cost);

    const handlePrint = () => {
        setIsLoading((prev) => ({ ...prev, print: true }));
        setTimeout(() => { window.print(); setIsLoading((prev) => ({ ...prev, print: false })); }, 300);
    };

    const handleEmailInvoice = () => {
        setEmailForm({
            email:    quotation?.customer?.email || "",
            subject:  `Quotation #${quotation?.quotation_number}`,
            message:  "",
            template: email_templates[0]?.slug || "",
        });
        setShowEmailModal(true);
    };

    const handleSendEmail = (e) => {
        e.preventDefault();
        setEmailProcessing(true);
        router.post(route("underwriting_quotes.send_email", quotation.id), emailForm, {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Email sent successfully");
                setShowEmailModal(false);
            },
            onError: () => toast.error("Failed to send email"),
            onFinish: () => setEmailProcessing(false),
        });
    };

    const handleDownloadPDF = () => {
        window.open(route("underwriting_quotes.pdf", quotation.id), "_blank");
    };

    const handleShareLink = () => {
        const link = route("quotations.show_public_quotation", quotation.short_code);
        setShareLink(link);
        setIsShareModalOpen(true);
    };

    const handleCopyLink = () => {
        navigator.clipboard.writeText(shareLink);
        toast.success("Link copied to clipboard");
    };

    const handleWhatsAppShare = () => {
        window.open(`https://wa.me/?text=${encodeURIComponent(`Check out this quotation: ${shareLink}`)}`, "_blank");
    };

    const handleFacebookShare = () => {
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareLink)}`, "_blank");
    };

    const infoRows = [
        { label: "Quotation Date",     value: quotation?.quotation_date },
        { label: "Currency Type",      value: quotation?.currency },
        { label: "Quotation No",       value: quotation?.quotation_number },
        { label: "Quotation Validity", value: quotation?.expired_date },
    ];

    const quoteToRows = [
        { label: "Quote To",    value: quotation?.customer?.name || "-" },
        { label: "Valid Until", value: quotation?.expired_date || "-" },
    ];

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <style dangerouslySetInnerHTML={{ __html: buildPrintStyles() }} />

                <div className="space-y-4 p-4">
                    <PageHeader page="Underwriting Quotes" subpage={`Quote #${quotation.id}`} url="underwriting_quotes.index" />

                    <div className="mb-4 flex items-center justify-end space-x-2 print:hidden">
                        <Button variant="outline" onClick={handlePrint} disabled={isLoading.print}>
                            <PrinterIcon className="mr-2 h-4 w-4" />
                            {isLoading.print ? "Printing..." : "Print"}
                        </Button>

                        <Button variant="outline" onClick={handleEmailInvoice}>
                            <MailIcon className="mr-2 h-4 w-4" />Email
                        </Button>

                        <Button variant="outline" onClick={handleDownloadPDF}>
                            <DownloadIcon className="mr-2 h-4 w-4" />Download PDF
                        </Button>

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline" size="icon">
                                    <MoreVertical className="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem onClick={handleShareLink}>
                                    <ShareIcon className="mr-2 h-4 w-4" /><span>Share Link</span>
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    <Link href={route("underwriting_quotes.edit", quotation.id)} className="flex items-center">
                                        <Edit className="mr-2 h-4 w-4" /><span>Edit Quote</span>
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <div className="overflow-x-auto pb-4">
                        <div id="printable-area" className="mx-auto bg-white flex flex-col min-h-[297mm] w-[210mm]">
                            <div className="flex-1 flex flex-col border-[10px] p-3" style={{ borderColor: primaryColor }}>
                                <div className="mb-4 h-4" style={{ backgroundColor: primaryColor }} />

                                <div className="grid grid-cols-12 gap-6">
                                    <div className="col-span-7">
                                        <div className="mb-6 flex items-start justify-between gap-4">
                                            <div className="max-w-[260px]">
                                                {quotation?.business?.logo ? (
                                                    <img src={`/uploads/media/${quotation.business.logo}`} alt="Business Logo" className="max-h-24 object-contain" />
                                                ) : (
                                                    <div className="text-3xl font-bold uppercase" style={{ color: primaryColor }}>{businessName}</div>
                                                )}
                                            </div>
                                            <QRCodeSVG
                                                value={route("quotations.show_public_quotation", quotation.short_code)}
                                                size={100}
                                                level="H"
                                                includeMargin={true}
                                                margin={10}
                                                className="shrink-0 print:block"
                                            />
                                        </div>

                                        <table className="w-full text-sm">
                                            <tbody>
                                                {quoteToRows.map((row) => (
                                                    <tr key={row.label}>
                                                        <td className="w-40 py-0.5 font-semibold text-slate-900">{row.label}:</td>
                                                        <td className="py-0.5 text-slate-700">{row.value}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>

                                    <div className="col-span-5">
                                        <div className="border border-slate-900">
                                            <div className="border-b-2 px-4 py-2 text-center text-2xl font-bold uppercase" style={{ color: primaryColor, borderColor: primaryColor }}>
                                                Quotation
                                            </div>
                                            <div className="px-4 py-2">
                                                {infoRows.map((row) => (
                                                    <div key={row.label} className="flex items-start justify-between gap-4 py-0.5 text-sm">
                                                        <span className="font-semibold text-slate-900">{row.label}:</span>
                                                        <span className="text-slate-700">{row.value}</span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div className="mt-5 border border-slate-900">
                                    <div className="border-b border-slate-900 px-2 py-1 text-xs font-bold uppercase" style={sectionStyle}>
                                        Quote Items
                                    </div>

                                    <table className="w-full border-collapse text-xs">
                                        <thead>
                                            <tr>
                                                <th className="border border-slate-900 px-2 py-1 text-left" style={sectionStyle}>Item</th>
                                                <th className="border border-slate-900 px-2 py-1 text-left" style={sectionStyle}>Description</th>
                                                <th className="border border-slate-900 px-2 py-1 text-right" style={sectionStyle}>Qty</th>
                                                <th className="border border-slate-900 px-2 py-1 text-right" style={sectionStyle}>Rate</th>
                                                <th className="border border-slate-900 px-2 py-1 text-right" style={sectionStyle}>Premium</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {(quotation?.items || []).map((item, index) => (
                                                <tr key={index}>
                                                    <td className="border border-slate-900 px-2 py-2 align-top">{item.product_name}</td>
                                                    <td className="border border-slate-900 px-2 py-2 align-top">{item.description}</td>
                                                    <td className="border border-slate-900 px-2 py-2 text-right align-top">{item.quantity}</td>
                                                    <td className="border border-slate-900 px-2 py-2 text-right align-top">{formatItemRate(item)}</td>
                                                    <td className="border border-slate-900 px-2 py-2 text-right align-top">{formatMoney(item.sub_total)}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                <QuotationSections sections={quotationSections} style={sectionStyle} />

                                <div className="mt-5 border border-slate-900">
                                    <div className="px-2 py-1 text-xs font-bold uppercase" style={sectionStyle}>Premium Summary</div>
                                    <table className="w-full border-collapse text-sm">
                                        <tbody>
                                            <tr>
                                                <td className="border-b border-slate-900 px-3 py-2 font-semibold">Sub-Total</td>
                                                <td className="border-b border-slate-900 px-3 py-2 text-right">{formatMoney(quotation?.sub_total)}</td>
                                            </tr>
                                            {(quotation?.taxes || []).map((tax, index) => (
                                                <tr key={index}>
                                                    <td className="border-b border-slate-900 px-3 py-2 font-semibold">{tax.name}</td>
                                                    <td className="border-b border-slate-900 px-3 py-2 text-right">{formatMoney(tax.amount)}</td>
                                                </tr>
                                            ))}
                                            {Number(quotation?.discount) > 0 && (
                                                <tr>
                                                    <td className="border-b border-slate-900 px-3 py-2 font-semibold">Discount</td>
                                                    <td className="border-b border-slate-900 px-3 py-2 text-right">-{formatMoney(quotation.discount)}</td>
                                                </tr>
                                            )}
                                            <tr>
                                                <td className="px-3 py-2 font-bold uppercase">Total Premium</td>
                                                <td className="px-3 py-2 text-right font-bold">{formatMoney(quotation?.grand_total)}</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div className="mt-auto border-x border-b border-slate-900 px-4 py-3 text-center text-sm text-slate-800">
                                    We trust you will find our quotation competitive and await your placement instructions.
                                </div>

                                <div className="px-4 py-4 text-center text-sm font-semibold" style={{ backgroundColor: primaryColor, color: textColor }}>
                                    {[quotation?.business?.phone, businessEmail, quotation?.business?.website].filter(Boolean).join(" | ")}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </SidebarInset>

            {/* Email Modal */}
            <Modal show={showEmailModal} onClose={() => setShowEmailModal(false)} maxWidth="xl">
                <form onSubmit={handleSendEmail} className="p-4">
                    <h2 className="text-lg font-medium text-gray-900 mb-4">Send Quotation via Email</h2>
                    <div className="space-y-4">
                        {email_templates.length > 0 && (
                            <div>
                                <label className="block text-sm font-medium text-gray-700 mb-1">Template</label>
                                <select
                                    value={emailForm.template}
                                    onChange={(e) => setEmailForm((p) => ({ ...p, template: e.target.value }))}
                                    className="w-full rounded-md border border-input bg-background px-3 py-2 text-sm"
                                >
                                    <option value="">No template</option>
                                    {email_templates.map((t) => (
                                        <option key={t.slug} value={t.slug}>{t.name}</option>
                                    ))}
                                </select>
                            </div>
                        )}
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">To *</label>
                            <Input
                                type="email"
                                value={emailForm.email}
                                onChange={(e) => setEmailForm((p) => ({ ...p, email: e.target.value }))}
                                required
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Subject *</label>
                            <Input
                                value={emailForm.subject}
                                onChange={(e) => setEmailForm((p) => ({ ...p, subject: e.target.value }))}
                                required
                            />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-gray-700 mb-1">Message *</label>
                            <Textarea
                                value={emailForm.message}
                                onChange={(e) => setEmailForm((p) => ({ ...p, message: e.target.value }))}
                                rows={4}
                                required
                            />
                        </div>
                    </div>
                    <div className="flex justify-end gap-2 mt-6">
                        <Button type="button" variant="outline" onClick={() => setShowEmailModal(false)}>Cancel</Button>
                        <Button type="submit" disabled={emailProcessing}>
                            {emailProcessing ? "Sending..." : "Send Email"}
                        </Button>
                    </div>
                </form>
            </Modal>

            {/* Share Modal */}
            <Modal show={isShareModalOpen} onClose={() => setIsShareModalOpen(false)} maxWidth="2xl">
                <div className="mb-6">
                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">Share Underwriting Quote</h2>
                </div>
                <div className="space-y-4">
                    <div className="flex items-center space-x-2">
                        <Input value={shareLink} readOnly className="flex-1" />
                        <Button variant="outline" onClick={handleCopyLink} className="flex items-center">
                            <Copy className="mr-2 h-4 w-4" />Copy
                        </Button>
                    </div>
                    <div className="flex justify-center space-x-4 pt-4">
                        <Button variant="outline" onClick={handleWhatsAppShare} className="flex items-center">
                            <MessageCircle className="mr-2 h-4 w-4" />WhatsApp
                        </Button>
                        <Button variant="outline" onClick={handleFacebookShare} className="flex items-center">
                            <Facebook className="mr-2 h-4 w-4" />Facebook
                        </Button>
                    </div>
                </div>
                <div className="mt-6 flex justify-end">
                    <Button type="button" variant="outline" onClick={() => setIsShareModalOpen(false)}>Close</Button>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}

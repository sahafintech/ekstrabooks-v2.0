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
      size: A4 landscape;
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

export default function View({ quotation, decimalPlace }) {
    const [isLoading, setIsLoading] = useState({
        print: false,
        email: false,
    });
    const [isShareModalOpen, setIsShareModalOpen] = useState(false);
    const [shareLink, setShareLink] = useState("");

    const isDeferredQuotation = Number(quotation?.is_deffered) === 1;
    const quantityLabel =
        isDeferredQuotation && quotation?.invoice_category === "medical"
            ? "Members"
            : "Quantity";

    const getBusinessSettingValue = (name, fallback) =>
        quotation?.business?.system_settings?.find((setting) => setting.name === name)?.value ||
        fallback;

    const primaryColor = getBusinessSettingValue("invoice_primary_color", "#6d0e47");
    const textColor = getBusinessSettingValue("invoice_text_color", "#ffffff");
    const businessName =
        quotation?.business?.business_name || quotation?.business?.name || "Business";
    const businessEmail =
        quotation?.business?.business_email || quotation?.business?.email || "";

    const formatMoney = (amount, currency = quotation?.currency) =>
        formatCurrency({
            amount,
            currency,
            decimalPlaces: decimalPlace,
        });

    const handlePrint = () => {
        setIsLoading((prev) => ({ ...prev, print: true }));
        setTimeout(() => {
            window.print();
            setIsLoading((prev) => ({ ...prev, print: false }));
        }, 300);
    };

    const handleEmailInvoice = () => {
        setIsLoading((prev) => ({ ...prev, email: true }));
        router.visit(route("quotations.send_email", quotation.id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success("Email form opened successfully");
                setIsLoading((prev) => ({ ...prev, email: false }));
            },
            onError: () => {
                toast.error("Failed to open email form");
                setIsLoading((prev) => ({ ...prev, email: false }));
            },
        });
    };

    const handleDownloadPDF = () => {
        window.open(route("quotations.pdf", quotation.id), "_blank");
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
        const text = `Check out this quotation: ${shareLink}`;
        window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, "_blank");
    };

    const handleFacebookShare = () => {
        window.open(
            `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareLink)}`,
            "_blank"
        );
    };

    const infoRows = [
        { label: "Quotation Date", value: quotation?.quotation_date },
        { label: "Currency Type", value: quotation?.currency },
        { label: "Quotation No", value: quotation?.quotation_number },
        { label: "Quotation Validity", value: quotation?.expired_date },
    ];

    const quoteToRows = [
        { label: "Quote To", value: quotation?.customer?.name || "-" },
        { label: "Quotation Type", value: isDeferredQuotation ? "Deferred" : "Normal" },
        {
            label: isDeferredQuotation ? "Policy Number" : "Order Number",
            value: quotation?.po_so_number || "-",
        },
        ...(isDeferredQuotation && quotation?.invoice_category
            ? [{ label: "Category", value: quotation.invoice_category.toUpperCase() }]
            : []),
        { label: "Valid Until", value: quotation?.expired_date || "-" },
    ];

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <style dangerouslySetInnerHTML={{ __html: printStyles }} />

                <div className="space-y-4">
                    <PageHeader
                        page="Quotations"
                        subpage={`Quotation #${quotation.id}`}
                        url="quotations.index"
                    />

                    <div className="mb-4 flex items-center justify-end space-x-2 print:hidden">
                        <Button variant="outline" onClick={handlePrint} disabled={isLoading.print}>
                            <PrinterIcon className="mr-2 h-4 w-4" />
                            {isLoading.print ? "Printing..." : "Print"}
                        </Button>

                        <Button variant="outline" onClick={handleEmailInvoice} disabled={isLoading.email}>
                            <MailIcon className="mr-2 h-4 w-4" />
                            {isLoading.email ? "Sending..." : "Email"}
                        </Button>

                        <Button variant="outline" onClick={handleDownloadPDF}>
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
                                    <Link href={route("quotations.edit", quotation.id)} className="flex items-center">
                                        <Edit className="mr-2 h-4 w-4" />
                                        <span>Edit Quotation</span>
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <div className="overflow-x-auto pb-4">
                        <div id="printable-area" className="mx-auto min-h-[210mm] w-[297mm] max-w-full bg-white">
                            <div className="border-[10px] p-3" style={{ borderColor: primaryColor }}>
                                <div className="mb-4 h-4" style={{ backgroundColor: primaryColor }} />

                                <div className="grid grid-cols-12 gap-6">
                                    <div className="col-span-7">
                                        {quotation?.business?.logo && (
                                            <div className="mb-6 max-w-[260px]">
                                                <img
                                                    src={`/uploads/media/${quotation.business.logo}`}
                                                    alt="Business Logo"
                                                    className="max-h-24 object-contain"
                                                />
                                            </div>
                                        )}

                                        <table className="w-full text-sm">
                                            <tbody>
                                                {quoteToRows.map((row) => (
                                                    <tr key={row.label}>
                                                        <td className="w-40 py-0.5 font-semibold text-slate-900">
                                                            {row.label}:
                                                        </td>
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
                                    <div
                                        className="border-b border-slate-900 px-2 py-1 text-xs font-bold uppercase"
                                        style={sectionTitleStyle(primaryColor, textColor)}
                                    >
                                        {quotation?.invoice_category
                                            ? `${quotation.invoice_category.toUpperCase()} Quote`
                                            : "Quotation Items"}
                                    </div>

                                    <table className="w-full border-collapse text-xs">
                                        <thead>
                                            <tr>
                                                <th className="border border-slate-900 px-2 py-1 text-left" style={sectionTitleStyle(primaryColor, textColor)}>Coverage</th>
                                                <th className="border border-slate-900 px-2 py-1 text-left" style={sectionTitleStyle(primaryColor, textColor)}>Item</th>
                                                <th className="border border-slate-900 px-2 py-1 text-left" style={sectionTitleStyle(primaryColor, textColor)}>Description</th>
                                                {isDeferredQuotation && (
                                                    <th className="border border-slate-900 px-2 py-1 text-left" style={sectionTitleStyle(primaryColor, textColor)}>Benefits</th>
                                                )}
                                                {isDeferredQuotation && quotation?.invoice_category === "medical" && (
                                                    <th className="border border-slate-900 px-2 py-1 text-left" style={sectionTitleStyle(primaryColor, textColor)}>Family Size</th>
                                                )}
                                                {isDeferredQuotation && quotation?.invoice_category === "other" && (
                                                    <th className="border border-slate-900 px-2 py-1 text-right" style={sectionTitleStyle(primaryColor, textColor)}>Sum Insured</th>
                                                )}
                                                <th className="border border-slate-900 px-2 py-1 text-right" style={sectionTitleStyle(primaryColor, textColor)}>{quantityLabel}</th>
                                                <th className="border border-slate-900 px-2 py-1 text-right" style={sectionTitleStyle(primaryColor, textColor)}>{isDeferredQuotation ? "Rate" : "Unit Cost"}</th>
                                                <th className="border border-slate-900 px-2 py-1 text-right" style={sectionTitleStyle(primaryColor, textColor)}>Premium</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {(quotation?.items || []).map((item, index) => (
                                                <tr key={index}>
                                                    <td className="border border-slate-900 px-2 py-2 align-top">{item.product_name}</td>
                                                    <td className="border border-slate-900 px-2 py-2 align-top">{item.product_name}</td>
                                                    <td className="border border-slate-900 px-2 py-2 align-top">{item.description}</td>
                                                    {isDeferredQuotation && (
                                                        <td className="border border-slate-900 px-2 py-2 align-top">{item.benefits}</td>
                                                    )}
                                                    {isDeferredQuotation && quotation?.invoice_category === "medical" && (
                                                        <td className="border border-slate-900 px-2 py-2 align-top">{item.family_size}</td>
                                                    )}
                                                    {isDeferredQuotation && quotation?.invoice_category === "other" && (
                                                        <td className="border border-slate-900 px-2 py-2 text-right align-top">{formatMoney(item.sum_insured)}</td>
                                                    )}
                                                    <td className="border border-slate-900 px-2 py-2 text-right align-top">{item.quantity}</td>
                                                    <td className="border border-slate-900 px-2 py-2 text-right align-top">{formatMoney(item.unit_cost)}</td>
                                                    <td className="border border-slate-900 px-2 py-2 text-right align-top">{formatMoney(item.quantity * item.unit_cost)}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                <div className="mt-5 grid grid-cols-12 gap-0 border border-slate-900">
                                    <div className="col-span-4 border-r border-slate-900">
                                        <div className="px-2 py-1 text-xs font-bold uppercase" style={sectionTitleStyle(primaryColor, textColor)}>
                                            Coverage Summary
                                        </div>
                                        <div className="min-h-[150px] p-3 text-sm leading-6 text-slate-800">
                                            {quotation?.note || "No additional coverage summary provided."}
                                        </div>
                                    </div>

                                    <div className="col-span-4 border-r border-slate-900">
                                        <div className="px-2 py-1 text-xs font-bold uppercase" style={sectionTitleStyle(primaryColor, textColor)}>
                                            Exclusions and Remarks
                                        </div>
                                        <div className="min-h-[150px] p-3 text-sm leading-6 text-slate-800">
                                            {quotation?.footer || "No additional exclusions or remarks provided."}
                                        </div>
                                    </div>

                                    <div className="col-span-4">
                                        <div className="px-2 py-1 text-xs font-bold uppercase" style={sectionTitleStyle(primaryColor, textColor)}>
                                            Premium Summary
                                        </div>
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
                                </div>

                                <div className="border-x border-b border-slate-900 px-4 py-3 text-center text-sm text-slate-800">
                                    We trust you will find our quotation competitive and await your placement instructions.
                                </div>

                                <div className="mt-4 px-4 py-4 text-center text-sm font-semibold" style={{ backgroundColor: primaryColor, color: textColor }}>
                                    {[quotation?.business?.phone, businessEmail, quotation?.business?.website]
                                        .filter(Boolean)
                                        .join(" | ")}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </SidebarInset>

            <Modal show={isShareModalOpen} onClose={() => setIsShareModalOpen(false)} maxWidth="2xl">
                <div className="mb-6">
                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Share Quotation
                    </h2>
                </div>

                <div className="space-y-4">
                    <div className="flex items-center space-x-2">
                        <Input value={shareLink} readOnly className="flex-1" />
                        <Button variant="outline" onClick={handleCopyLink} className="flex items-center">
                            <Copy className="mr-2 h-4 w-4" />
                            Copy
                        </Button>
                    </div>

                    <div className="flex justify-center space-x-4 pt-4">
                        <Button variant="outline" onClick={handleWhatsAppShare} className="flex items-center">
                            <MessageCircle className="mr-2 h-4 w-4" />
                            WhatsApp
                        </Button>
                        <Button variant="outline" onClick={handleFacebookShare} className="flex items-center">
                            <Facebook className="mr-2 h-4 w-4" />
                            Facebook
                        </Button>
                    </div>
                </div>

                <div className="mt-6 flex justify-end">
                    <Button type="button" variant="outline" onClick={() => setIsShareModalOpen(false)}>
                        Close
                    </Button>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}

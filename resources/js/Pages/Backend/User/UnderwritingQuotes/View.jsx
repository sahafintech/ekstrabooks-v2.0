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
import { Fragment, useState } from "react";
import { toast } from "sonner";
import Modal from "@/Components/Modal";
import { Input } from "@/Components/ui/input";
import { QRCodeSVG } from "qrcode.react";

const buildPrintStyles = (isLandscape) => `
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
      size: A4 ${isLandscape ? "landscape" : "portrait"};
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

const MEDICAL_COVERAGE_SECTIONS = [
    { key: "inpatient", label: "INPATIENT" },
    { key: "outpatient", label: "OUTPATIENT" },
    { key: "maternity", label: "MATERNITY" },
    { key: "dental", label: "DENTAL" },
    { key: "optical", label: "OPTICAL" },
    { key: "telemedicine", label: "TELEMEDICINE" },
];

const MEDICAL_TABLE_COLUMN_WIDTHS = [
    "5%",
    "6%",
    "8%",
    ...Array(MEDICAL_COVERAGE_SECTIONS.length * 2).fill("6%"),
    "9%",
];

const MEDICAL_TABLE_CLASS_NAME = "w-full table-fixed border-collapse text-[7px] leading-tight";
const MEDICAL_TABLE_HEADER_CELL_CLASS_NAME = "border border-slate-900 px-1 py-0.5 text-[7px] leading-tight";
const MEDICAL_TABLE_BODY_CELL_CLASS_NAME = "border border-slate-900 px-1 py-1 text-[7px] leading-tight";
const MEDICAL_TABLE_NUMERIC_CELL_CLASS_NAME = `${MEDICAL_TABLE_BODY_CELL_CLASS_NAME} text-right whitespace-nowrap`;

const parseFamilySizeValue = (familySize) => {
    if (!familySize) return null;
    const n = String(familySize).trim().toUpperCase();
    const m = n.match(/^M\s*\+\s*(\d+(?:\.\d+)?)$/);
    if (m) return 1 + Number(m[1]);
    if (n === "M") return 1;
    if (/^\d+(?:\.\d+)?$/.test(n)) return Number(n);
    const c = n.match(/^(\d+(?:\.\d+)?)\s*\+\s*(\d+(?:\.\d+)?)$/);
    if (c) return Number(c[1]) + Number(c[2]);
    return null;
};

const calculateMembersAndFamilyValue = (members, familySize) => {
    const fsv = parseFamilySizeValue(familySize);
    if (fsv === null) return 0;
    return (Number(members) || 0) * fsv;
};

const getFamilySizeSortValue = (familySize) => {
    const n = String(familySize || "").trim().toUpperCase();
    if (n === "M") return 0;
    const m = n.match(/^M\s*\+\s*(\d+(?:\.\d+)?)$/);
    if (m) return Number(m[1]);
    return Number.MAX_SAFE_INTEGER;
};

const sortMedicalQuotationItems = (items = []) =>
    [...items].sort((a, b) => {
        const av = getFamilySizeSortValue(a.family_size);
        const bv = getFamilySizeSortValue(b.family_size);
        if (av !== bv) return av - bv;
        return String(a.family_size || "").localeCompare(String(b.family_size || ""));
    });

const buildMedicalSectionTotals = (items = []) =>
    MEDICAL_COVERAGE_SECTIONS.reduce((totals, section) => {
        totals[section.key] = items.reduce(
            (sum, item) => sum + (Number(item?.[`${section.key}_total_contribution`]) || 0),
            0
        );
        return totals;
    }, {});

const allocateAmountAcrossSections = (amount, sectionTotals, decimalPlaces) => {
    const factor = 10 ** decimalPlaces;
    const totalAmountUnits = Math.round((Number(amount) || 0) * factor);
    const grandSectionTotal = MEDICAL_COVERAGE_SECTIONS.reduce(
        (sum, section) => sum + (Number(sectionTotals[section.key]) || 0),
        0
    );

    if (grandSectionTotal === 0) {
        return MEDICAL_COVERAGE_SECTIONS.reduce((alloc, section) => {
            alloc[section.key] = 0;
            return alloc;
        }, {});
    }

    let allocatedUnits = 0;
    return MEDICAL_COVERAGE_SECTIONS.reduce((alloc, section, idx) => {
        if (idx === MEDICAL_COVERAGE_SECTIONS.length - 1) {
            alloc[section.key] = (totalAmountUnits - allocatedUnits) / factor;
            return alloc;
        }
        const sectionUnits = Math.round(
            totalAmountUnits * ((Number(sectionTotals[section.key]) || 0) / grandSectionTotal)
        );
        allocatedUnits += sectionUnits;
        alloc[section.key] = sectionUnits / factor;
        return alloc;
    }, {});
};

export default function View({ quotation, decimalPlace }) {
    const [isLoading, setIsLoading] = useState({ print: false, email: false });
    const [isShareModalOpen, setIsShareModalOpen] = useState(false);
    const [shareLink, setShareLink] = useState("");

    const isMedical = quotation?.invoice_category === "medical";
    const isOther   = quotation?.invoice_category === "other";
    const quantityLabel = isMedical ? "Members" : "Quantity";

    const getBusinessSettingValue = (name, fallback) =>
        quotation?.business?.system_settings?.find((s) => s.name === name)?.value || fallback;

    const primaryColor = getBusinessSettingValue("invoice_primary_color", "#6d0e47");
    const textColor    = getBusinessSettingValue("invoice_text_color", "#ffffff");
    const businessName = quotation?.business?.business_name || quotation?.business?.name || "Business";
    const businessEmail = quotation?.business?.business_email || quotation?.business?.email || "";
    const coverageSummary    = quotation?.coverage_summary    || quotation?.note   || "";
    const exclusionsRemarks  = quotation?.exclusions_remarks  || quotation?.footer || "";

    const formatMoney = (amount, currency = quotation?.currency) =>
        formatCurrency({ amount, currency, decimalPlaces: decimalPlace });

    const formatOptionalMoney = (amount) =>
        amount === null || amount === undefined || amount === "" ? "-" : formatMoney(amount);

    const formatPlainNumber = (amount) =>
        amount === null || amount === undefined || amount === ""
            ? "-"
            : new Intl.NumberFormat(undefined, {
                  minimumFractionDigits: 0,
                  maximumFractionDigits: decimalPlace,
              }).format(Number(amount) || 0);

    const hasCoverageValue = (value) => value !== null && value !== undefined && value !== "";

    const getMedicalCoverageConfigEntries = (item) =>
        MEDICAL_COVERAGE_SECTIONS.map((section) => ({
            ...section,
            limitPerFamily: item?.[`${section.key}_limit_per_family`],
            contributionPerFamily: item?.[`${section.key}_contribution_per_family`],
            totalContribution: item?.[`${section.key}_total_contribution`],
        })).filter(
            (s) => hasCoverageValue(s.limitPerFamily) || hasCoverageValue(s.contributionPerFamily) || hasCoverageValue(s.totalContribution)
        );

    const medicalItems   = isMedical ? sortMedicalQuotationItems(quotation?.items || []) : [];
    const medicalTotals  = isMedical ? buildMedicalSectionTotals(medicalItems) : {};
    const medicalTotalMembers = medicalItems.reduce((s, i) => s + (Number(i.quantity) || 0), 0);
    const medicalTotalMembersAndFamily = medicalItems.reduce(
        (s, i) => s + calculateMembersAndFamilyValue(i.quantity, i.family_size),
        0
    );
    const medicalTaxAllocations = (quotation?.taxes || []).map((tax) => ({
        ...tax,
        allocations: allocateAmountAcrossSections(tax.amount, medicalTotals, decimalPlace),
    }));
    const medicalDiscountAllocations =
        Number(quotation?.discount) > 0
            ? allocateAmountAcrossSections(quotation.discount, medicalTotals, decimalPlace)
            : null;
    const medicalGrossTotals = MEDICAL_COVERAGE_SECTIONS.reduce((totals, section) => {
        const taxTotal = medicalTaxAllocations.reduce((s, tax) => s + (Number(tax.allocations?.[section.key]) || 0), 0);
        const discountAlloc = Number(medicalDiscountAllocations?.[section.key]) || 0;
        totals[section.key] = (Number(medicalTotals[section.key]) || 0) + taxTotal - discountAlloc;
        return totals;
    }, {});
    const getMedicalSectionLimit = (key) =>
        medicalItems.find((i) => hasCoverageValue(i?.[`${key}_limit_per_family`]))?.[`${key}_limit_per_family`];

    const handlePrint = () => {
        setIsLoading((prev) => ({ ...prev, print: true }));
        setTimeout(() => { window.print(); setIsLoading((prev) => ({ ...prev, print: false })); }, 300);
    };

    const handleEmailInvoice = () => {
        setIsLoading((prev) => ({ ...prev, email: true }));
        router.visit(route("underwriting_quotes.send_email", quotation.id), {
            preserveScroll: true,
            onSuccess: () => { toast.success("Email form opened successfully"); setIsLoading((prev) => ({ ...prev, email: false })); },
            onError: () => { toast.error("Failed to open email form"); setIsLoading((prev) => ({ ...prev, email: false })); },
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
        { label: "Quote To",      value: quotation?.customer?.name || "-" },
        { label: "Policy Number", value: quotation?.po_so_number || "-" },
        { label: "Valid Until",   value: quotation?.expired_date || "-" },
    ];

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <style dangerouslySetInnerHTML={{ __html: buildPrintStyles(isMedical) }} />

                <div className="space-y-4 p-4">
                    <PageHeader page="Underwriting Quotes" subpage={`Quote #${quotation.id}`} url="underwriting_quotes.index" />

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
                        <div
                            id="printable-area"
                            className={`mx-auto bg-white flex flex-col ${isMedical ? "min-h-[210mm] w-[281mm]" : "min-h-[297mm] w-[210mm]"}`}
                        >
                            <div
                                className={`flex-1 flex flex-col ${isMedical ? "border-[8px] p-2" : "border-[10px] p-3"}`}
                                style={{ borderColor: primaryColor }}
                            >
                                <div className="mb-4 h-4" style={{ backgroundColor: primaryColor }} />

                                <div className={`grid grid-cols-12 ${isMedical ? "gap-4" : "gap-6"}`}>
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
                                    <div className="border-b border-slate-900 px-2 py-1 text-xs font-bold uppercase" style={sectionTitleStyle(primaryColor, textColor)}>
                                        {quotation?.invoice_category ? `${quotation.invoice_category.toUpperCase()} Quote` : "Quote Items"}
                                    </div>

                                    {isMedical ? (
                                        <div>
                                            <table className={MEDICAL_TABLE_CLASS_NAME}>
                                                <colgroup>
                                                    {MEDICAL_TABLE_COLUMN_WIDTHS.map((width, index) => (
                                                        <col key={`col-${index}`} style={{ width }} />
                                                    ))}
                                                </colgroup>
                                                <thead>
                                                    <tr>
                                                        <th className={MEDICAL_TABLE_HEADER_CELL_CLASS_NAME} colSpan={3}></th>
                                                        {MEDICAL_COVERAGE_SECTIONS.map((section) => (
                                                            <th key={section.key} className={`${MEDICAL_TABLE_HEADER_CELL_CLASS_NAME} text-center font-bold uppercase`} colSpan={2} style={sectionTitleStyle(primaryColor, textColor)}>
                                                                {section.label}
                                                            </th>
                                                        ))}
                                                        <th className={`${MEDICAL_TABLE_HEADER_CELL_CLASS_NAME} text-center font-bold uppercase`} style={sectionTitleStyle(primaryColor, textColor)}>
                                                            Total
                                                        </th>
                                                    </tr>
                                                    <tr className="bg-slate-50">
                                                        <th className={MEDICAL_TABLE_HEADER_CELL_CLASS_NAME} colSpan={3}></th>
                                                        {MEDICAL_COVERAGE_SECTIONS.map((section) => (
                                                            <th key={`${section.key}-limit`} className={`${MEDICAL_TABLE_HEADER_CELL_CLASS_NAME} text-center font-semibold`} colSpan={2}>
                                                                Limit: {formatPlainNumber(getMedicalSectionLimit(section.key))} / Family
                                                            </th>
                                                        ))}
                                                        <th className={MEDICAL_TABLE_HEADER_CELL_CLASS_NAME}></th>
                                                    </tr>
                                                    <tr className="bg-slate-100 font-semibold uppercase">
                                                        <th className={`${MEDICAL_TABLE_HEADER_CELL_CLASS_NAME} text-left`}>Family Size</th>
                                                        <th className={`${MEDICAL_TABLE_HEADER_CELL_CLASS_NAME} text-right`}>Staff</th>
                                                        <th className={`${MEDICAL_TABLE_HEADER_CELL_CLASS_NAME} text-right`}>Staff + Family</th>
                                                        {MEDICAL_COVERAGE_SECTIONS.map((section) => (
                                                            <Fragment key={`${section.key}-subhead`}>
                                                                <th className={`${MEDICAL_TABLE_HEADER_CELL_CLASS_NAME} text-right`}>Contrib./Family</th>
                                                                <th className={`${MEDICAL_TABLE_HEADER_CELL_CLASS_NAME} text-right`}>Total Contrib.</th>
                                                            </Fragment>
                                                        ))}
                                                        <th className={`${MEDICAL_TABLE_HEADER_CELL_CLASS_NAME} text-right`}>Total</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    {medicalItems.map((item, index) => (
                                                        <tr key={index}>
                                                            <td className={`${MEDICAL_TABLE_BODY_CELL_CLASS_NAME} font-semibold whitespace-nowrap`}>{item.family_size}</td>
                                                            <td className={MEDICAL_TABLE_NUMERIC_CELL_CLASS_NAME}>{formatPlainNumber(item.quantity)}</td>
                                                            <td className={MEDICAL_TABLE_NUMERIC_CELL_CLASS_NAME}>{formatPlainNumber(calculateMembersAndFamilyValue(item.quantity, item.family_size))}</td>
                                                            {MEDICAL_COVERAGE_SECTIONS.map((section) => (
                                                                <Fragment key={`${item.id || index}-${section.key}`}>
                                                                    <td className={MEDICAL_TABLE_NUMERIC_CELL_CLASS_NAME}>{formatPlainNumber(item?.[`${section.key}_contribution_per_family`])}</td>
                                                                    <td className={MEDICAL_TABLE_NUMERIC_CELL_CLASS_NAME}>{formatPlainNumber(item?.[`${section.key}_total_contribution`])}</td>
                                                                </Fragment>
                                                            ))}
                                                            <td className={`${MEDICAL_TABLE_NUMERIC_CELL_CLASS_NAME} font-semibold`}>{formatPlainNumber(item.sub_total)}</td>
                                                        </tr>
                                                    ))}
                                                </tbody>
                                            </table>
                                        </div>
                                    ) : (
                                        <table className="w-full border-collapse text-xs">
                                            <thead>
                                                <tr>
                                                    <th className="border border-slate-900 px-2 py-1 text-left" style={sectionTitleStyle(primaryColor, textColor)}>Item</th>
                                                    <th className="border border-slate-900 px-2 py-1 text-left" style={sectionTitleStyle(primaryColor, textColor)}>Description</th>
                                                    {isOther && <th className="border border-slate-900 px-2 py-1 text-right" style={sectionTitleStyle(primaryColor, textColor)}>Sum Insured</th>}
                                                    {!isOther && <th className="border border-slate-900 px-2 py-1 text-right" style={sectionTitleStyle(primaryColor, textColor)}>{quantityLabel}</th>}
                                                    <th className="border border-slate-900 px-2 py-1 text-right" style={sectionTitleStyle(primaryColor, textColor)}>{isOther ? "Rate (%)" : "Rate"}</th>
                                                    <th className="border border-slate-900 px-2 py-1 text-right" style={sectionTitleStyle(primaryColor, textColor)}>Premium</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {(quotation?.items || []).map((item, index) => (
                                                    <tr key={index}>
                                                        <td className="border border-slate-900 px-2 py-2 align-top">{item.product_name}</td>
                                                        <td className="border border-slate-900 px-2 py-2 align-top">{item.description}</td>
                                                        {isOther && <td className="border border-slate-900 px-2 py-2 text-right align-top">{formatMoney(item.sum_insured)}</td>}
                                                        {!isOther && <td className="border border-slate-900 px-2 py-2 text-right align-top">{item.quantity}</td>}
                                                        <td className="border border-slate-900 px-2 py-2 text-right align-top">{isOther ? `${item.unit_cost}%` : formatMoney(item.unit_cost)}</td>
                                                        <td className="border border-slate-900 px-2 py-2 text-right align-top">{formatMoney(item.sub_total)}</td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    )}
                                </div>

                                <div className="mt-5 grid grid-cols-12 gap-0 border border-slate-900">
                                    <div className="col-span-4 border-r border-slate-900">
                                        <div className="px-2 py-1 text-xs font-bold uppercase" style={sectionTitleStyle(primaryColor, textColor)}>Coverage Summary</div>
                                        <div className="min-h-[150px] whitespace-pre-line p-3 text-sm leading-6 text-slate-800">
                                            {coverageSummary || "No additional coverage summary provided."}
                                        </div>
                                    </div>

                                    <div className="col-span-4 border-r border-slate-900">
                                        <div className="px-2 py-1 text-xs font-bold uppercase" style={sectionTitleStyle(primaryColor, textColor)}>Exclusions and Remarks</div>
                                        <div className="min-h-[150px] whitespace-pre-line p-3 text-sm leading-6 text-slate-800">
                                            {exclusionsRemarks || "No additional exclusions or remarks provided."}
                                        </div>
                                    </div>

                                    <div className="col-span-4">
                                        <div className="px-2 py-1 text-xs font-bold uppercase" style={sectionTitleStyle(primaryColor, textColor)}>Premium Summary</div>
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

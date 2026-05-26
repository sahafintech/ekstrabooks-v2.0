import { usePage } from "@inertiajs/react";
import GuestLayout from "@/Layouts/GuestLayout";
import { Button } from "@/Components/ui/button";
import { formatCurrency } from "@/lib/utils";
import { useToast } from "@/hooks/use-toast";
import { PrinterIcon, DownloadIcon } from "lucide-react";
import { Fragment, useEffect, useState } from "react";
import { QRCodeSVG } from "qrcode.react";

const MEDICAL_COVERAGE_SECTIONS = [
    { key: "inpatient",    label: "INPATIENT" },
    { key: "outpatient",   label: "OUTPATIENT" },
    { key: "maternity",    label: "MATERNITY" },
    { key: "dental",       label: "DENTAL" },
    { key: "optical",      label: "OPTICAL" },
    { key: "telemedicine", label: "TELEMEDICINE" },
];

const MEDICAL_TABLE_COLUMN_WIDTHS = [
    "5%", "6%", "8%",
    ...Array(MEDICAL_COVERAGE_SECTIONS.length * 2).fill("6%"),
    "9%",
];

const MEDICAL_TABLE_CLASS_NAME = "w-full table-fixed border-collapse text-[7px] leading-tight";
const MEDICAL_TABLE_HEADER_CELL_CLASS_NAME = "border border-slate-900 px-1 py-0.5 text-[7px] leading-tight";
const MEDICAL_TABLE_BODY_CELL_CLASS_NAME   = "border border-slate-900 px-1 py-1 text-[7px] leading-tight";
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

export default function PublicView({ quotation }) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [isLoading, setIsLoading] = useState({ print: false, pdf: false });

    const isMedical = quotation?.invoice_category === "medical";
    const isOther   = quotation?.invoice_category === "other";

    const decimalPlaces = Number(
        quotation?.business?.system_settings?.find((s) => s.name === "decimal_place")?.value ?? 2
    );

    const primaryColor = quotation?.business?.system_settings?.find((s) => s.name === "invoice_primary_color")?.value || "#6d0e47";
    const textColor    = quotation?.business?.system_settings?.find((s) => s.name === "invoice_text_color")?.value  || "#ffffff";

    const coverageSummary   = quotation?.coverage_summary   || quotation?.note   || "";
    const exclusionsRemarks = quotation?.exclusions_remarks || quotation?.footer || "";

    const printStyles = `
        @media print {
            @page {
                size: A4 ${isMedical ? "landscape" : "portrait"};
                margin: 8mm;
            }
            body {
                margin: 0; padding: 0; background: white;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    `;

    const formatMoney = (amount) =>
        formatCurrency({ amount, currency: quotation?.currency, decimalPlaces });

    const formatPlainNumber = (amount) =>
        amount === null || amount === undefined || amount === ""
            ? "-"
            : new Intl.NumberFormat(undefined, {
                  minimumFractionDigits: 0,
                  maximumFractionDigits: decimalPlaces,
              }).format(Number(amount) || 0);

    const medicalItems  = isMedical ? sortMedicalQuotationItems(quotation?.items || []) : [];
    const medicalTotals = isMedical ? buildMedicalSectionTotals(medicalItems) : {};
    const medicalTaxAllocations = (quotation?.taxes || []).map((tax) => ({
        ...tax,
        allocations: allocateAmountAcrossSections(tax.amount, medicalTotals, decimalPlaces),
    }));
    const medicalDiscountAllocations =
        Number(quotation?.discount) > 0
            ? allocateAmountAcrossSections(quotation.discount, medicalTotals, decimalPlaces)
            : null;
    const getMedicalSectionLimit = (key) =>
        medicalItems.find((i) => i?.[`${key}_limit_per_family`] != null && i?.[`${key}_limit_per_family`] !== "")
            ?.[`${key}_limit_per_family`];

    useEffect(() => {
        if (flash?.success) toast({ title: "Success", description: flash.success });
        if (flash?.error)   toast({ variant: "destructive", title: "Error", description: flash.error });
    }, [flash, toast]);

    const handlePrint = () => {
        setIsLoading((p) => ({ ...p, print: true }));
        window.print();
        setIsLoading((p) => ({ ...p, print: false }));
    };

    const handleDownloadPDF = async () => {
        setIsLoading((p) => ({ ...p, pdf: true }));
        try {
            const html2canvas = (await import("html2canvas")).default;
            const { jsPDF } = await import("jspdf");
            const content = document.querySelector(".print-container");
            if (!content) throw new Error("Content element not found");

            const canvas = await html2canvas(content, { scale: 2, useCORS: true, logging: false });
            const pdf = new jsPDF({ orientation: isMedical ? "landscape" : "portrait", unit: "mm", format: "a4" });
            const imgWidth = isMedical ? 297 : 210;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            pdf.addImage(canvas.toDataURL("image/png"), "PNG", 0, 0, imgWidth, imgHeight);
            pdf.save(`UW-Quote-${quotation.id}.pdf`);
        } catch (error) {
            console.error("Error generating PDF:", error);
            toast({ variant: "destructive", title: "Error", description: "Failed to generate PDF. Please try again." });
        } finally {
            setIsLoading((p) => ({ ...p, pdf: false }));
        }
    };

    const sectionStyle = { backgroundColor: primaryColor, color: textColor };

    return (
        <GuestLayout>
            <style dangerouslySetInnerHTML={{ __html: printStyles }} />
            <div className="container mx-auto px-4 py-8">
                <div className={`flex justify-end gap-4 mb-6 w-full mx-auto print:hidden ${isMedical ? "md:w-[281mm]" : "md:w-[210mm]"}`}>
                    <Button variant="outline" onClick={handlePrint} disabled={isLoading.print}>
                        <PrinterIcon className="h-4 w-4 mr-2" />Print
                    </Button>
                    <Button variant="outline" onClick={handleDownloadPDF} disabled={isLoading.pdf}>
                        <DownloadIcon className="h-4 w-4 mr-2" />Download PDF
                    </Button>
                </div>

                <div
                    className={`bg-white mx-auto w-full print-container ${
                        isMedical
                            ? "p-3 md:w-[281mm] min-h-[210mm] md:p-4"
                            : "p-4 md:w-[210mm] min-h-[297mm] md:p-8"
                    } print:w-full print:p-0 print:shadow-none print:rounded-none`}
                >
                    <div className={`border-[10px]`} style={{ borderColor: primaryColor }}>
                        <div className="mb-4 h-4" style={{ backgroundColor: primaryColor }} />

                        <div className={`px-4 pb-4 ${isMedical ? "p-2" : "p-4"}`}>
                            {/* Header */}
                            <div className="grid grid-cols-12 gap-4 mb-6">
                                <div className="col-span-7">
                                    <div className="mb-4 flex items-start justify-between gap-4">
                                        <div className="max-w-[260px]">
                                            {quotation?.business?.logo ? (
                                                <img src={`/uploads/media/${quotation.business.logo}`} alt="Business Logo" className="max-h-24 object-contain" />
                                            ) : (
                                                <div className="text-2xl font-bold uppercase" style={{ color: primaryColor }}>
                                                    {quotation?.business?.business_name || quotation?.business?.name}
                                                </div>
                                            )}
                                        </div>
                                        <QRCodeSVG
                                            value={route("quotations.show_public_quotation", quotation.short_code)}
                                            size={90}
                                            level="H"
                                            includeMargin
                                            className="shrink-0 print:block"
                                        />
                                    </div>
                                    <table className="w-full text-sm">
                                        <tbody>
                                            <tr><td className="w-36 py-0.5 font-semibold">Quote To:</td><td className="py-0.5">{quotation?.customer?.name || "-"}</td></tr>
                                            <tr><td className="py-0.5 font-semibold">Policy Number:</td><td className="py-0.5">{quotation?.po_so_number || "-"}</td></tr>
                                            <tr><td className="py-0.5 font-semibold">Valid Until:</td><td className="py-0.5">{quotation?.expired_date || "-"}</td></tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div className="col-span-5">
                                    <div className="border border-slate-900">
                                        <div className="border-b-2 px-4 py-2 text-center text-xl font-bold uppercase" style={{ color: primaryColor, borderColor: primaryColor }}>
                                            Quotation
                                        </div>
                                        <div className="px-4 py-2 text-sm space-y-1">
                                            <div className="flex justify-between"><span className="font-semibold">Quotation Date:</span><span>{quotation?.quotation_date}</span></div>
                                            <div className="flex justify-between"><span className="font-semibold">Currency:</span><span>{quotation?.currency}</span></div>
                                            <div className="flex justify-between"><span className="font-semibold">Quotation No:</span><span>{quotation?.quotation_number}</span></div>
                                            <div className="flex justify-between"><span className="font-semibold">Validity:</span><span>{quotation?.expired_date}</span></div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Items section */}
                            <div className="border border-slate-900">
                                <div className="border-b border-slate-900 px-2 py-1 text-xs font-bold uppercase" style={sectionStyle}>
                                    {quotation?.invoice_category ? `${quotation.invoice_category.toUpperCase()} Quote` : "Quote Items"}
                                </div>

                                {isMedical ? (
                                    <table className={MEDICAL_TABLE_CLASS_NAME}>
                                        <colgroup>
                                            {MEDICAL_TABLE_COLUMN_WIDTHS.map((w, i) => <col key={i} style={{ width: w }} />)}
                                        </colgroup>
                                        <thead>
                                            <tr>
                                                <th className={MEDICAL_TABLE_HEADER_CELL_CLASS_NAME} colSpan={3}></th>
                                                {MEDICAL_COVERAGE_SECTIONS.map((section) => (
                                                    <th key={section.key} className={`${MEDICAL_TABLE_HEADER_CELL_CLASS_NAME} text-center font-bold uppercase`} colSpan={2} style={sectionStyle}>
                                                        {section.label}
                                                    </th>
                                                ))}
                                                <th className={`${MEDICAL_TABLE_HEADER_CELL_CLASS_NAME} text-center font-bold uppercase`} style={sectionStyle}>Total</th>
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
                                ) : (
                                    <table className="w-full border-collapse text-xs">
                                        <thead>
                                            <tr>
                                                <th className="border border-slate-900 px-2 py-1 text-left" style={sectionStyle}>Item</th>
                                                <th className="border border-slate-900 px-2 py-1 text-left" style={sectionStyle}>Description</th>
                                                {isOther && <th className="border border-slate-900 px-2 py-1 text-right" style={sectionStyle}>Sum Insured</th>}
                                                {!isOther && <th className="border border-slate-900 px-2 py-1 text-right" style={sectionStyle}>Quantity</th>}
                                                <th className="border border-slate-900 px-2 py-1 text-right" style={sectionStyle}>{isOther ? "Rate (%)" : "Rate"}</th>
                                                <th className="border border-slate-900 px-2 py-1 text-right" style={sectionStyle}>Premium</th>
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

                            {/* Footer: summary + premium */}
                            <div className="mt-5 grid grid-cols-12 gap-0 border border-slate-900">
                                <div className="col-span-4 border-r border-slate-900">
                                    <div className="px-2 py-1 text-xs font-bold uppercase" style={sectionStyle}>Coverage Summary</div>
                                    <div className="min-h-[120px] whitespace-pre-line p-3 text-sm leading-6 text-slate-800">
                                        {coverageSummary || "No additional coverage summary provided."}
                                    </div>
                                </div>
                                <div className="col-span-4 border-r border-slate-900">
                                    <div className="px-2 py-1 text-xs font-bold uppercase" style={sectionStyle}>Exclusions and Remarks</div>
                                    <div className="min-h-[120px] whitespace-pre-line p-3 text-sm leading-6 text-slate-800">
                                        {exclusionsRemarks || "No additional exclusions or remarks provided."}
                                    </div>
                                </div>
                                <div className="col-span-4">
                                    <div className="px-2 py-1 text-xs font-bold uppercase" style={sectionStyle}>Premium Summary</div>
                                    <table className="w-full border-collapse text-sm">
                                        <tbody>
                                            <tr>
                                                <td className="border-b border-slate-900 px-3 py-2 font-semibold">Sub-Total</td>
                                                <td className="border-b border-slate-900 px-3 py-2 text-right">{formatMoney(quotation?.sub_total)}</td>
                                            </tr>
                                            {(quotation?.taxes || []).map((tax, i) => (
                                                <tr key={i}>
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
                                {[quotation?.business?.phone, quotation?.business?.business_email || quotation?.business?.email, quotation?.business?.website].filter(Boolean).join(" | ")}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </GuestLayout>
    );
}

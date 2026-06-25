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

const formatDateTime = (value) => {
    if (!value) return "-";

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) return value;

    return date.toLocaleString(undefined, {
        year:   "numeric",
        month:  "short",
        day:    "2-digit",
        hour:   "2-digit",
        minute: "2-digit",
    });
};

const QuotationSections = ({ sections = [], sectionStyle }) => {
    if (!sections.length) return null;

    return (
        <div className="mt-5 grid grid-cols-1 gap-3 md:grid-cols-2 print:grid-cols-2">
            {sections.map((section, index) => {
                const data = section.data_json ?? {};
                const fields = data.fields ?? [];
                const columns = data.columns ?? [];
                const rows = data.rows ?? [];
                const isLastOddSection = sections.length % 2 === 1 && index === sections.length - 1;

                return (
                    <div
                        key={section.id ?? index}
                        className={`border border-slate-900 ${isLastOddSection ? "md:col-span-2 print:col-span-2" : ""}`}
                    >
                        <div className="px-2 py-1 text-xs font-bold uppercase" style={sectionStyle}>{section.title}</div>

                        {section.type === "fields" && (
                            <table className="w-full border-collapse text-sm">
                                <tbody>
                                    {fields.map((field, fieldIndex) => (
                                        <tr key={fieldIndex}>
                                            <td className="w-1/3 border-t border-slate-900 px-3 py-2 font-semibold">{field.label}</td>
                                            <td className="border-t border-slate-900 px-3 py-2">{field.value || "-"}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        )}

                        {section.type === "table" && (
                            <table className="w-full border-collapse text-sm">
                                <thead>
                                    <tr>{columns.map((column, columnIndex) => <th key={columnIndex} className="border-t border-slate-900 px-3 py-2 text-left">{column}</th>)}</tr>
                                </thead>
                                <tbody>
                                    {rows.map((row, rowIndex) => (
                                        <tr key={rowIndex}>{row.map((cell, cellIndex) => <td key={cellIndex} className="border-t border-slate-900 px-3 py-2">{cell || "-"}</td>)}</tr>
                                    ))}
                                </tbody>
                            </table>
                        )}

                        {section.type !== "fields" && section.type !== "table" && (
                            <div className="min-h-[80px] whitespace-pre-line p-3 text-sm leading-6 text-slate-800">{section.content || "-"}</div>
                        )}
                    </div>
                );
            })}
        </div>
    );
};


export default function PublicView({ quotation }) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [isLoading, setIsLoading] = useState({ print: false, pdf: false });

    const isMedical = quotation?.invoice_category === "medical";
    const isOther   = quotation?.invoice_category === "other";

    const decimalPlaces = Number(
        quotation?.business?.system_settings?.find((s) => s.name === "decimal_places")?.value ?? 2
    );

    const primaryColor = quotation?.business?.system_settings?.find((s) => s.name === "invoice_primary_color")?.value || "#6d0e47";
    const textColor    = quotation?.business?.system_settings?.find((s) => s.name === "invoice_text_color")?.value  || "#ffffff";

    const quotationSections = quotation?.sections || [];

    const printStyles = `
        @media print {
            @page {
                size: A4 landscape;
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

    const formatItemRate = (item) =>
        item.calculation_type === "percentage_of_amount"
            ? `${Number(item.rate_value ?? 0)}%`
            : formatMoney(item.unit_cost);

    const formatPlainNumber = (amount) =>
        amount === null || amount === undefined || amount === ""
            ? "-"
            : new Intl.NumberFormat(undefined, {
                  minimumFractionDigits: 0,
                  maximumFractionDigits: decimalPlaces,
              }).format(Number(amount) || 0);

    const medicalItems  = isMedical ? sortMedicalQuotationItems(quotation?.items || []) : [];
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
            const pdf = new jsPDF({ orientation: "landscape", unit: "mm", format: "a4" });
            const imgWidth = 297;
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
    const createdByName = quotation?.created_by?.name || quotation?.createdBy?.name || "-";
    const infoRows = [
        { label: "Quotation Date", value: quotation?.quotation_date || "-" },
        { label: "Currency",       value: quotation?.currency || "-" },
        { label: "Quotation No",   value: quotation?.quotation_number || "-" },
        { label: "Validity",       value: quotation?.expired_date || "-" },
        { label: "Created By",     value: createdByName },
        { label: "Created Date",   value: formatDateTime(quotation?.created_at) },
        { label: "Last Updated",   value: formatDateTime(quotation?.updated_at) },
    ];

    return (
        <GuestLayout>
            <style dangerouslySetInnerHTML={{ __html: printStyles }} />
            <div className="container mx-auto px-4 py-8">
                <div className="flex justify-end gap-4 mb-6 w-full mx-auto print:hidden md:w-[281mm]">
                    <Button variant="outline" onClick={handlePrint} disabled={isLoading.print}>
                        <PrinterIcon className="h-4 w-4 mr-2" />Print
                    </Button>
                    <Button variant="outline" onClick={handleDownloadPDF} disabled={isLoading.pdf}>
                        <DownloadIcon className="h-4 w-4 mr-2" />Download PDF
                    </Button>
                </div>

                <div
                    className="bg-white mx-auto w-full print-container p-3 md:w-[281mm] min-h-[190mm] md:p-4 print:w-full print:p-0 print:shadow-none print:rounded-none"
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
                                            {infoRows.map((row) => (
                                                <div key={row.label} className="flex justify-between gap-4">
                                                    <span className="font-semibold">{row.label}:</span>
                                                    <span className="text-right">{row.value}</span>
                                                </div>
                                            ))}
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
                                                    {isOther && <td className="border border-slate-900 px-2 py-2 text-right align-top">{formatMoney(item.basis_amount ?? item.sum_insured)}</td>}
                                                    {!isOther && <td className="border border-slate-900 px-2 py-2 text-right align-top">{item.quantity}</td>}
                                                    <td className="border border-slate-900 px-2 py-2 text-right align-top">{formatItemRate(item)}</td>
                                                    <td className="border border-slate-900 px-2 py-2 text-right align-top">{formatMoney(item.sub_total)}</td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                )}
                            </div>

                            <QuotationSections sections={quotationSections} sectionStyle={sectionStyle} />

                            <div className="mt-5 border border-slate-900">
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

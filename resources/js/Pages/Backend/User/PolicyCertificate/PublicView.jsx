import GuestLayout from "@/Layouts/GuestLayout";
import { Button } from "@/Components/ui/button";
import { useToast } from "@/hooks/use-toast";
import { PrinterIcon, DownloadIcon } from "lucide-react";
import { useState } from "react";
import { QRCodeSVG } from "qrcode.react";

const printStyles = `
@media print {
    body * {
        visibility: hidden;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .print-container, .print-container * {
        visibility: visible;
    }

    .print-container {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        margin: 0;
        padding: 0;
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

// ── Section renderers ────────────────────────────────────────────────────────

function SectionHeader({ title, primaryColor, textColor, filled = false }) {
    if (filled) {
        return (
            <div
                className="text-xs font-bold uppercase px-3 py-1 tracking-widest mb-1"
                style={{ backgroundColor: primaryColor, color: textColor }}
            >
                {title}
            </div>
        );
    }
    return (
        <div
            className="text-xs font-bold uppercase px-1 py-1 tracking-widest mb-1 border-b-2"
            style={{ borderColor: primaryColor, color: primaryColor }}
        >
            {title}
        </div>
    );
}

function FieldsSection({ section, primaryColor, textColor }) {
    return (
        <div className="mb-4">
            <SectionHeader title={section.title} primaryColor={primaryColor} textColor={textColor} filled={false} />
            <div className="border border-gray-300 px-3 py-2">
                <table className="w-full text-sm">
                    <tbody>
                        {section.fields.map((field, i) => (
                            <tr key={i}>
                                <td className="text-gray-600 pr-2 py-0.5 whitespace-nowrap w-1/3 align-top">
                                    {field.label}:
                                </td>
                                <td className="font-medium py-0.5 align-top">{field.value || "—"}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>
        </div>
    );
}

function TableSection({ section, primaryColor, textColor }) {
    return (
        <div className="mb-4">
            <SectionHeader title={section.title} primaryColor={primaryColor} textColor={textColor} filled={true} />
            <table className="w-full text-sm border-collapse border border-gray-300">
                <thead>
                    <tr style={{ backgroundColor: primaryColor, color: textColor }}>
                        {section.columns.map((col, i) => (
                            <th key={i} className="border border-gray-400 px-2 py-1 text-left font-semibold text-xs">
                                {col}
                            </th>
                        ))}
                    </tr>
                </thead>
                <tbody>
                    {section.rows.map((row, ri) => (
                        <tr key={ri} className={ri % 2 === 0 ? "bg-white" : "bg-gray-50"}>
                            {row.map((cell, ci) => (
                                <td key={ci} className="border border-gray-300 px-2 py-1 text-xs">
                                    {cell || "—"}
                                </td>
                            ))}
                        </tr>
                    ))}
                </tbody>
            </table>
        </div>
    );
}

function ContentSection({ section, primaryColor, textColor }) {
    return (
        <div className="mb-4">
            <SectionHeader title={section.title} primaryColor={primaryColor} textColor={textColor} />
            <div className="border border-gray-300 px-3 py-2 text-xs text-gray-700 whitespace-pre-wrap leading-relaxed">
                {section.content || "—"}
            </div>
        </div>
    );
}

function SignatureSection({ section, primaryColor, textColor }) {
    return (
        <div className="mb-4">
            <SectionHeader title={section.title} primaryColor={primaryColor} textColor={textColor} />
            <div className="border border-gray-300 px-3 py-4 text-xs text-gray-700">
                {section.content && <p className="mb-4 whitespace-pre-wrap">{section.content}</p>}
                <div className="flex justify-between mt-6">
                    <div className="text-center">
                        <div className="border-t border-gray-500 pt-1 w-40">Authorized Signatory</div>
                    </div>
                    <div className="text-center">
                        <div className="border-t border-gray-500 pt-1 w-40">Underwriter / Branch Manager</div>
                    </div>
                </div>
            </div>
        </div>
    );
}

function renderSection(section, i, primaryColor, textColor) {
    const props = { key: i, section, primaryColor, textColor };
    switch (section.type) {
        case "fields":    return <FieldsSection {...props} />;
        case "table":     return <TableSection {...props} />;
        case "signature": return <SignatureSection {...props} />;
        default:          return <ContentSection {...props} />;
    }
}

// ── Main component ───────────────────────────────────────────────────────────

export default function PublicView({ certificate, sections = [], business = {} }) {
    const { toast } = useToast();
    const [isLoading, setIsLoading] = useState({ print: false, pdf: false });

    const getSetting = (name, fallback) =>
        (business.system_settings ?? []).find((s) => s.name === name)?.value || fallback;

    const primaryColor = getSetting("invoice_primary_color", "#6b1f2a");
    const textColor    = getSetting("invoice_text_color", "#ffffff");

    const contactLine = [business.phone, business.email, business.website]
        .filter(Boolean)
        .join(" | ");

    const handlePrint = () => {
        setIsLoading((prev) => ({ ...prev, print: true }));
        setTimeout(() => {
            window.print();
            setIsLoading((prev) => ({ ...prev, print: false }));
        }, 300);
    };

    const handleDownloadPDF = async () => {
        setIsLoading((prev) => ({ ...prev, pdf: true }));
        try {
            const html2canvas = (await import("html2canvas")).default;
            const { jsPDF } = await import("jspdf");

            const content = document.querySelector(".print-container");
            if (!content) throw new Error("Content element not found");

            const canvas = await html2canvas(content, {
                scale: 2,
                useCORS: true,
                logging: false,
            });

            const pdf = new jsPDF({ orientation: "portrait", unit: "mm", format: "a4" });
            const imgWidth = 210;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;

            pdf.addImage(canvas.toDataURL("image/png"), "PNG", 0, 0, imgWidth, imgHeight);
            pdf.save(`certificate-${certificate.certificate_number}.pdf`);
        } catch (error) {
            console.error("Error generating PDF:", error);
            toast({
                variant: "destructive",
                title: "Error",
                description: "Failed to generate PDF. Please try again.",
            });
        } finally {
            setIsLoading((prev) => ({ ...prev, pdf: false }));
        }
    };

    return (
        <GuestLayout>
            <style dangerouslySetInnerHTML={{ __html: printStyles }} />
            <div className="container mx-auto px-4 py-8">

                {/* Action Buttons */}
                <div className="flex justify-end gap-4 mb-6 w-full md:w-[210mm] mx-auto print:hidden">
                    <Button variant="outline" onClick={handlePrint} disabled={isLoading.print}>
                        <PrinterIcon className="h-4 w-4 mr-2" />
                        {isLoading.print ? "Printing..." : "Print"}
                    </Button>
                    <Button variant="outline" onClick={handleDownloadPDF} disabled={isLoading.pdf}>
                        <DownloadIcon className="h-4 w-4 mr-2" />
                        {isLoading.pdf ? "Generating..." : "Download PDF"}
                    </Button>
                </div>

                {/* Certificate Document */}
                <div className="mx-auto min-h-[297mm] w-[210mm] max-w-full bg-white print-container">
                    <div
                        className="border-[10px] p-4"
                        style={{ borderColor: primaryColor }}
                    >
                        {/* Top color bar */}
                        <div className="mb-4 h-3" style={{ backgroundColor: primaryColor }} />

                        {/* ── Header ───────────────────────────────── */}
                        <div className="flex items-start justify-between mb-4 pb-3 border-b-2" style={{ borderColor: primaryColor }}>
                            <div>
                                {business.logo ? (
                                    <img
                                        src={`/uploads/media/${business.logo}`}
                                        alt="Logo"
                                        className="max-h-16 max-w-[140px] object-contain mb-1"
                                    />
                                ) : (
                                    <div className="text-lg font-bold uppercase tracking-wide" style={{ color: primaryColor }}>
                                        {business.name}
                                    </div>
                                )}
                                <div className="text-sm font-semibold text-gray-600 mt-0.5">
                                    Certificate of {certificate.certificate_type} Insurance
                                </div>
                                {certificate.customer_name && (
                                    <div className="text-xs text-gray-500 mt-1">
                                        <span className="font-semibold text-gray-700">Insured:</span>{" "}
                                        {certificate.customer_name}
                                    </div>
                                )}
                            </div>
                            <div className="text-right flex flex-col items-end gap-1">
                                {business.logo && (
                                    <div className="text-base font-bold uppercase tracking-wide mb-1" style={{ color: primaryColor }}>
                                        {business.name}
                                    </div>
                                )}
                                <QRCodeSVG
                                    value={window.location.href}
                                    size={80}
                                    level="H"
                                    marginSize={2}
                                    className="shrink-0 print:block"
                                />
                                <div className="text-xs text-gray-500">
                                    <span className="font-semibold text-gray-700">Cert No:</span>{" "}
                                    <span className="font-mono">{certificate.certificate_number}</span>
                                </div>
                                {certificate.policy_number && (
                                    <div className="text-xs text-gray-500">
                                        <span className="font-semibold text-gray-700">Policy No:</span>{" "}
                                        <span className="font-mono">{certificate.policy_number}</span>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Policy dates row */}
                        {(certificate.policy_start_date || certificate.policy_end_date) && (
                            <div className="flex gap-6 mb-4 text-xs text-gray-600">
                                {certificate.policy_start_date && (
                                    <span>
                                        <span className="font-semibold">Start Date:</span>{" "}
                                        {new Date(certificate.policy_start_date + "T00:00:00").toLocaleDateString("en-GB", { day: "2-digit", month: "long", year: "numeric" })}
                                    </span>
                                )}
                                {certificate.policy_end_date && (
                                    <span>
                                        <span className="font-semibold">End Date:</span>{" "}
                                        {new Date(certificate.policy_end_date + "T00:00:00").toLocaleDateString("en-GB", { day: "2-digit", month: "long", year: "numeric" })}
                                    </span>
                                )}
                            </div>
                        )}

                        {/* ── Sections ─────────────────────────────── */}
                        {sections.map((section, i) => renderSection(section, i, primaryColor, textColor))}

                        {/* ── Default footer signatures if no signature section ─── */}
                        {!sections.some((s) => s.type === "signature") && (
                            <div className="flex justify-between mt-10 pt-4 border-t border-gray-300 text-xs text-gray-600">
                                <div className="text-center">
                                    <div className="border-t border-gray-500 pt-1 w-40">Authorized Signatory</div>
                                </div>
                                <div className="text-center">
                                    <div className="border-t border-gray-500 pt-1 w-40">Underwriter / Branch Manager</div>
                                </div>
                            </div>
                        )}

                        {/* ── Footer bar ─────────────────────────────── */}
                        {contactLine && (
                            <div
                                className="mt-6 px-4 py-3 text-center text-xs font-semibold"
                                style={{ backgroundColor: primaryColor, color: textColor }}
                            >
                                {contactLine}
                            </div>
                        )}

                        {/* ── Footer disclaimer ─────────────────────── */}
                        <div className="mt-3 text-[10px] text-gray-400 italic text-center">
                            This certificate is issued subject to the terms and conditions of the policy.{" "}
                            {business.name} is regulated by the relevant authority.
                        </div>
                    </div>
                </div>
            </div>
        </GuestLayout>
    );
}

import { usePage } from "@inertiajs/react";
import GuestLayout from "@/Layouts/GuestLayout";
import { Button } from "@/Components/ui/button";
import { formatCurrency } from "@/lib/utils";
import { useToast } from "@/hooks/use-toast";
import { PrinterIcon, DownloadIcon } from "lucide-react";
import { Fragment, useEffect, useState, useRef } from "react";
import {
    Table,
    TableBody,
    TableHeader,
    TableRow,
    TableHead,
    TableCell,
} from "@/Components/ui/table";
import { SidebarSeparator } from "@/Components/ui/sidebar";
import { QRCodeSVG } from "qrcode.react";

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
const MEDICAL_TABLE_HEADER_CELL_CLASS_NAME =
    "border border-slate-900 px-1 py-0.5 text-[7px] leading-tight";
const MEDICAL_TABLE_BODY_CELL_CLASS_NAME =
    "border border-slate-900 px-1 py-1 text-[7px] leading-tight";
const MEDICAL_TABLE_NUMERIC_CELL_CLASS_NAME =
    `${MEDICAL_TABLE_BODY_CELL_CLASS_NAME} text-right whitespace-nowrap`;

const parseFamilySizeValue = (familySize) => {
    if (!familySize) {
        return null;
    }

    const normalizedFamilySize = String(familySize).trim().toUpperCase();
    const memberFormatMatch = normalizedFamilySize.match(/^M\s*\+\s*(\d+(?:\.\d+)?)$/);

    if (memberFormatMatch) {
        return 1 + Number(memberFormatMatch[1]);
    }

    if (normalizedFamilySize === "M") {
        return 1;
    }

    if (/^\d+(?:\.\d+)?$/.test(normalizedFamilySize)) {
        return Number(normalizedFamilySize);
    }

    const combinedFamilySizeMatch = normalizedFamilySize.match(/^(\d+(?:\.\d+)?)\s*\+\s*(\d+(?:\.\d+)?)$/);

    if (combinedFamilySizeMatch) {
        return Number(combinedFamilySizeMatch[1]) + Number(combinedFamilySizeMatch[2]);
    }

    return null;
};

const calculateMembersAndFamilyValue = (members, familySize) => {
    const familySizeValue = parseFamilySizeValue(familySize);

    if (familySizeValue === null) {
        return 0;
    }

    return (Number(members) || 0) * familySizeValue;
};

const getFamilySizeSortValue = (familySize) => {
    const normalizedFamilySize = String(familySize || "").trim().toUpperCase();

    if (normalizedFamilySize === "M") {
        return 0;
    }

    const memberFormatMatch = normalizedFamilySize.match(/^M\s*\+\s*(\d+(?:\.\d+)?)$/);

    if (memberFormatMatch) {
        return Number(memberFormatMatch[1]);
    }

    return Number.MAX_SAFE_INTEGER;
};

const sortMedicalQuotationItems = (items = []) =>
    [...items].sort((firstItem, secondItem) => {
        const firstSortValue = getFamilySizeSortValue(firstItem.family_size);
        const secondSortValue = getFamilySizeSortValue(secondItem.family_size);

        if (firstSortValue !== secondSortValue) {
            return firstSortValue - secondSortValue;
        }

        return String(firstItem.family_size || "").localeCompare(String(secondItem.family_size || ""));
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
        return MEDICAL_COVERAGE_SECTIONS.reduce((allocations, section) => {
            allocations[section.key] = 0;
            return allocations;
        }, {});
    }

    let allocatedUnits = 0;

    return MEDICAL_COVERAGE_SECTIONS.reduce((allocations, section, sectionIndex) => {
        if (sectionIndex === MEDICAL_COVERAGE_SECTIONS.length - 1) {
            allocations[section.key] = (totalAmountUnits - allocatedUnits) / factor;
            return allocations;
        }

        const sectionUnits = Math.round(
            totalAmountUnits * ((Number(sectionTotals[section.key]) || 0) / grandSectionTotal)
        );

        allocatedUnits += sectionUnits;
        allocations[section.key] = sectionUnits / factor;
        return allocations;
    }, {});
};

export default function PublicView({ quotation }) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [isLoading, setIsLoading] = useState({
        print: false,
        pdf: false,
    });
    const contentRef = useRef(null);
    const isDeferredQuotation = Number(quotation?.is_deffered) === 1;
    const isMedicalDeferredQuotation =
        isDeferredQuotation && quotation?.invoice_category === "medical";
    const quantityLabel = isMedicalDeferredQuotation ? "Members" : "Quantity";
    const coverageSummary = quotation?.coverage_summary || quotation?.note || "";
    const exclusionsRemarks = quotation?.exclusions_remarks || quotation?.footer || "";
    const decimalPlaces = Number(
        quotation?.business?.system_settings?.find((setting) => setting.name === "decimal_place")
            ?.value ?? 2
    );
    const printStyles = `
        @media print {
            @page {
                size: A4 ${isMedicalDeferredQuotation ? "landscape" : "portrait"};
                margin: 8mm;
            }

            body {
                margin: 0;
                padding: 0;
                background: white;
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    `;

    const formatPlainNumber = (amount) =>
        amount === null || amount === undefined || amount === ""
            ? "-"
            : new Intl.NumberFormat(undefined, {
                minimumFractionDigits: 0,
                maximumFractionDigits: decimalPlaces,
            }).format(Number(amount) || 0);

    const medicalQuotationItems = isMedicalDeferredQuotation
        ? sortMedicalQuotationItems(quotation?.items || [])
        : [];
    const medicalSectionTotals = isMedicalDeferredQuotation
        ? buildMedicalSectionTotals(medicalQuotationItems)
        : {};
    const medicalTotalMembers = medicalQuotationItems.reduce(
        (sum, item) => sum + (Number(item.quantity) || 0),
        0
    );
    const medicalTotalMembersAndFamily = medicalQuotationItems.reduce(
        (sum, item) => sum + calculateMembersAndFamilyValue(item.quantity, item.family_size),
        0
    );
    const medicalTaxAllocations = (quotation?.taxes || []).map((tax) => ({
        ...tax,
        allocations: allocateAmountAcrossSections(tax.amount, medicalSectionTotals, decimalPlaces),
    }));
    const medicalDiscountAllocations =
        Number(quotation?.discount) > 0
            ? allocateAmountAcrossSections(quotation.discount, medicalSectionTotals, decimalPlaces)
            : null;
    const medicalGrossTotals = MEDICAL_COVERAGE_SECTIONS.reduce((totals, section) => {
        const taxAllocationTotal = medicalTaxAllocations.reduce(
            (sum, tax) => sum + (Number(tax.allocations?.[section.key]) || 0),
            0
        );
        const discountAllocation = Number(medicalDiscountAllocations?.[section.key]) || 0;

        totals[section.key] =
            (Number(medicalSectionTotals[section.key]) || 0) + taxAllocationTotal - discountAllocation;

        return totals;
    }, {});
    const getMedicalSectionLimit = (sectionKey) =>
        medicalQuotationItems.find((item) => item?.[`${sectionKey}_limit_per_family`] !== null && item?.[`${sectionKey}_limit_per_family`] !== undefined && item?.[`${sectionKey}_limit_per_family`] !== "")?.[
            `${sectionKey}_limit_per_family`
        ];

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
        window.print();
        setIsLoading((prev) => ({ ...prev, print: false }));
    };

    const handleDownloadPDF = async () => {
        setIsLoading((prev) => ({ ...prev, pdf: true }));
        try {
            // Dynamically import the required libraries
            const html2canvas = (await import("html2canvas")).default;
            const { jsPDF } = await import("jspdf");

            // Get the content element
            const content = document.querySelector(".print-container");
            if (!content) {
                throw new Error("Content element not found");
            }

            // Create canvas from content
            const canvas = await html2canvas(content, {
                scale: 2,
                useCORS: true,
                logging: false,
            });

            // Create PDF
            const pdf = new jsPDF({
                orientation: isMedicalDeferredQuotation ? "landscape" : "portrait",
                unit: "mm",
                format: "a4",
            });

            // Calculate dimensions
            const imgWidth = isMedicalDeferredQuotation ? 297 : 210;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;

            // Add image to PDF
            pdf.addImage(
                canvas.toDataURL("image/png"),
                "PNG",
                0,
                0,
                imgWidth,
                imgHeight
            );

            // Save PDF
            pdf.save(`Quotation-${quotation.id}.pdf`);
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
                <div
                    className={`flex justify-end gap-4 mb-6 w-full mx-auto print:hidden ${
                        isMedicalDeferredQuotation ? "md:w-[281mm]" : "md:w-[210mm]"
                    }`}
                >
                    <Button
                        variant="outline"
                        onClick={handlePrint}
                        disabled={isLoading.print}
                    >
                        <PrinterIcon className="h-4 w-4 mr-2" />
                        Print
                    </Button>
                    <Button
                        variant="outline"
                        onClick={handleDownloadPDF}
                        disabled={isLoading.pdf}
                    >
                        <DownloadIcon className="h-4 w-4 mr-2" />
                        Download PDF
                    </Button>
                </div>

                {/* Cash Purchase Content */}
                <div
                    ref={contentRef}
                    className={`bg-white mx-auto w-full ${
                        isMedicalDeferredQuotation ? "p-3 md:w-[281mm] md:p-4" : "p-4 md:w-[210mm] md:p-8"
                    } print:w-full print:p-0 print:shadow-none print:rounded-none print-container`}
                >
                    <div className={isMedicalDeferredQuotation ? "p-4 sm:p-5 print:p-3" : "p-6 sm:p-8"}>
                        {/* Invoice Header */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div>
                                <div className="mb-3 flex items-start justify-between gap-4">
                                    <div>
                                        {quotation.business.logo ? (
                                            <img
                                                src={`/uploads/media/${quotation.business.logo}`}
                                                alt="Business Logo"
                                                className="max-h-32 object-contain"
                                            />
                                        ) : (
                                            <h2 className="text-2xl font-bold text-primary">
                                                {quotation.business.business_name}
                                            </h2>
                                        )}
                                    </div>
                                    <QRCodeSVG
                                        value={route(
                                            "quotations.show_public_quotation",
                                            quotation.short_code
                                        )}
                                        size={100}
                                        level="H"
                                        includeMargin={true}
                                        margin={10}
                                        className="shrink-0 print:block"
                                    />
                                </div>
                                {quotation.business.logo && (
                                    <h2 className="text-2xl font-bold text-primary">
                                        {quotation.business.business_name}
                                    </h2>
                                )}
                                <div className="mt-2 text-sm">
                                    <p>{quotation.business.address}</p>
                                    <p>{quotation.business.email}</p>
                                    <p>{quotation.business.phone}</p>
                                </div>
                            </div>
                            <div className="md:text-right">
                                <h1 className="text-2xl font-bold">
                                    {quotation.title}
                                </h1>
                                <div className="mt-2 text-sm">
                                    <p>
                                        <span className="font-medium">
                                            Quotation #:
                                        </span>{" "}
                                        {quotation.quotation_number}
                                    </p>
                                    <p>
                                        <span className="font-medium">
                                            Quotation Date:
                                        </span>{" "}
                                        {quotation.quotation_date}
                                    </p>
                                    {quotation.po_so_number && (
                                        <p>
                                            <span className="font-medium">
                                                {isDeferredQuotation ? "Policy Number:" : "Order Number:"}
                                            </span>{" "}
                                            {quotation.po_so_number}
                                        </p>
                                    )}
                                    <p>
                                        <span className="font-medium">Quotation Type:</span>{" "}
                                        {isDeferredQuotation ? "Deferred" : "Normal"}
                                    </p>
                                    {isDeferredQuotation && quotation.invoice_category && (
                                        <p>
                                            <span className="font-medium">Deferred Category:</span>{" "}
                                            {quotation.invoice_category.toUpperCase()}
                                        </p>
                                    )}
                                </div>
                            </div>
                        </div>

                        <SidebarSeparator className="my-6" />

                        {/* Customer Information */}
                        <div className="mb-8">
                            <h3 className="font-medium text-lg mb-2">
                                Quotation To:
                            </h3>
                            <div className="text-sm">
                                <p className="font-medium">
                                    {quotation.customer?.name}
                                </p>
                                {quotation.customer?.company_name && (
                                    <p>{quotation.customer?.company_name}</p>
                                )}
                                <p>{quotation.customer?.address}</p>
                                <p>{quotation.customer?.email}</p>
                                <p>{quotation.customer?.mobile}</p>
                            </div>
                        </div>

                        {/* Invoice Items */}
                        <div className="mb-8">
                            {isMedicalDeferredQuotation ? (
                                <div>
                                    <table className={MEDICAL_TABLE_CLASS_NAME}>
                                        <colgroup>
                                            {MEDICAL_TABLE_COLUMN_WIDTHS.map((width, index) => (
                                                <col key={`medical-column-${index}`} style={{ width }} />
                                            ))}
                                        </colgroup>
                                        <thead>
                                            <tr className="bg-[#6d0e47] text-white">
                                                <th className={MEDICAL_TABLE_HEADER_CELL_CLASS_NAME} colSpan={3}></th>
                                                {MEDICAL_COVERAGE_SECTIONS.map((section) => (
                                                    <th
                                                        key={section.key}
                                                        className={`${MEDICAL_TABLE_HEADER_CELL_CLASS_NAME} text-center font-bold uppercase`}
                                                        colSpan={2}
                                                    >
                                                        {section.label}
                                                    </th>
                                                ))}
                                                <th className={`${MEDICAL_TABLE_HEADER_CELL_CLASS_NAME} text-center font-bold uppercase`}>
                                                    Total
                                                </th>
                                            </tr>
                                            <tr className="bg-slate-50">
                                                <th className={MEDICAL_TABLE_HEADER_CELL_CLASS_NAME} colSpan={3}></th>
                                                {MEDICAL_COVERAGE_SECTIONS.map((section) => (
                                                    <th
                                                        key={`${section.key}-limit`}
                                                        className={`${MEDICAL_TABLE_HEADER_CELL_CLASS_NAME} text-center font-semibold`}
                                                        colSpan={2}
                                                    >
                                                        Limit: {formatPlainNumber(getMedicalSectionLimit(section.key))} / Family
                                                    </th>
                                                ))}
                                                <th className={MEDICAL_TABLE_HEADER_CELL_CLASS_NAME}></th>
                                            </tr>
                                            <tr className="bg-slate-100 font-semibold uppercase">
                                                <th className={`${MEDICAL_TABLE_HEADER_CELL_CLASS_NAME} text-left`}>
                                                    Family Size
                                                </th>
                                                <th className={`${MEDICAL_TABLE_HEADER_CELL_CLASS_NAME} text-right`}>
                                                    Staff
                                                </th>
                                                <th className={`${MEDICAL_TABLE_HEADER_CELL_CLASS_NAME} text-right`}>
                                                    Staff + Family
                                                </th>
                                                {MEDICAL_COVERAGE_SECTIONS.map((section) => (
                                                    <Fragment key={`${section.key}-subhead`}>
                                                        <th className={`${MEDICAL_TABLE_HEADER_CELL_CLASS_NAME} text-right`}>
                                                            Contrib./Family
                                                        </th>
                                                        <th className={`${MEDICAL_TABLE_HEADER_CELL_CLASS_NAME} text-right`}>
                                                            Total Contrib.
                                                        </th>
                                                    </Fragment>
                                                ))}
                                                <th className={`${MEDICAL_TABLE_HEADER_CELL_CLASS_NAME} text-right`}>
                                                    Total
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {medicalQuotationItems.map((item, index) => (
                                                <tr key={index}>
                                                    <td className={`${MEDICAL_TABLE_BODY_CELL_CLASS_NAME} font-semibold whitespace-nowrap`}>
                                                        {item.family_size}
                                                    </td>
                                                    <td className={MEDICAL_TABLE_NUMERIC_CELL_CLASS_NAME}>
                                                        {formatPlainNumber(item.quantity)}
                                                    </td>
                                                    <td className={MEDICAL_TABLE_NUMERIC_CELL_CLASS_NAME}>
                                                        {formatPlainNumber(
                                                            calculateMembersAndFamilyValue(
                                                                item.quantity,
                                                                item.family_size
                                                            )
                                                        )}
                                                    </td>
                                                    {MEDICAL_COVERAGE_SECTIONS.map((section) => (
                                                        <Fragment key={`${item.id || index}-${section.key}`}>
                                                            <td className={MEDICAL_TABLE_NUMERIC_CELL_CLASS_NAME}>
                                                                {formatPlainNumber(
                                                                    item?.[
                                                                        `${section.key}_contribution_per_family`
                                                                    ]
                                                                )}
                                                            </td>
                                                            <td className={MEDICAL_TABLE_NUMERIC_CELL_CLASS_NAME}>
                                                                {formatPlainNumber(
                                                                    item?.[`${section.key}_total_contribution`]
                                                                )}
                                                            </td>
                                                        </Fragment>
                                                    ))}
                                                    <td className={`${MEDICAL_TABLE_NUMERIC_CELL_CLASS_NAME} font-semibold`}>
                                                        {formatPlainNumber(item.sub_total)}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            ) : (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Item</TableHead>
                                            <TableHead>Description</TableHead>
                                            {isDeferredQuotation && quotation.invoice_category === "other" && (
                                                <TableHead className="text-right">Sum Insured</TableHead>
                                            )}
                                            {isDeferredQuotation && quotation.invoice_category === "medical" && (
                                                <TableHead>Family Size</TableHead>
                                            )}
                                            <TableHead className="text-right">
                                                {quantityLabel}
                                            </TableHead>
                                            <TableHead className="text-right">
                                                {isDeferredQuotation ? "Rate" : "Unit Cost"}
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Total
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {quotation.items.map((item, index) => (
                                            <TableRow key={index}>
                                                <TableCell className="font-medium">
                                                    {item.product_name}
                                                </TableCell>
                                                <TableCell>
                                                    {item.description}
                                                </TableCell>
                                                {isDeferredQuotation && quotation.invoice_category === "other" && (
                                                    <TableCell className="text-right">
                                                        {formatCurrency(item.sum_insured, quotation.currency)}
                                                    </TableCell>
                                                )}
                                                {isDeferredQuotation && quotation.invoice_category === "medical" && (
                                                    <TableCell>{item.family_size}</TableCell>
                                                )}
                                                <TableCell className="text-right">
                                                    {item.quantity}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency(
                                                        item.unit_cost,
                                                        quotation.currency
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency({
                                                        amount: item.sub_total,
                                                        currency: quotation.currency,
                                                    })}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            )}
                        </div>

                        {!isMedicalDeferredQuotation && (
                            <div className="flex justify-end">
                                <div className="w-full md:w-1/2 lg:w-1/3 space-y-2">
                                    <div className="flex justify-between py-2 border-t">
                                        <span className="font-medium">
                                            Subtotal:
                                        </span>
                                        <span>
                                            {formatCurrency({
                                                amount: quotation.sub_total,
                                                currency: quotation.currency,
                                            })}
                                        </span>
                                    </div>

                                    {quotation.taxes.map((tax, index) => (
                                        <div
                                            key={index}
                                            className="flex justify-between py-2"
                                        >
                                            <span>{tax.name}:</span>
                                            <span>
                                                {formatCurrency({
                                                    amount: tax.amount,
                                                    currency: quotation.currency,
                                                })}
                                            </span>
                                        </div>
                                    ))}

                                    {quotation.discount > 0 && (
                                        <div className="flex justify-between py-2">
                                            <span>Discount</span>
                                            <span>
                                                -
                                                {formatCurrency({
                                                    amount: quotation.discount,
                                                    currency: quotation.currency,
                                                })}
                                            </span>
                                        </div>
                                    )}

                                    <div className="flex justify-between py-3 border-t border-b font-bold text-lg">
                                        <span>Total:</span>
                                        <span>
                                            {formatCurrency({
                                                amount: quotation.grand_total,
                                                currency: quotation.currency,
                                            })}
                                        </span>
                                    </div>

                                    {quotation.currency !==
                                        quotation.business.currency && (
                                        <div className="flex justify-between py-2 text-gray-500 text-sm">
                                            <span>Exchange Rate:</span>
                                            <span>
                                                1 {quotation.business.currency} ={" "}
                                                {formatCurrency(
                                                    quotation.exchange_rate,
                                                    quotation.currency
                                                )}
                                            </span>
                                        </div>
                                    )}

                                    {quotation.currency !==
                                        quotation.business.currency && (
                                        <div className="flex justify-between py-2 text-sm text-gray-600">
                                            <span>Equivalent to:</span>
                                            <span>
                                                {formatCurrency(
                                                    quotation.converted_total,
                                                    quotation.currency
                                                )}
                                            </span>
                                        </div>
                                    )}
                                </div>
                            </div>
                        )}

                        {/* Coverage Summary & Exclusions */}
                        {(coverageSummary || exclusionsRemarks) && (
                            <div className="mt-8 grid grid-cols-1 gap-6 md:grid-cols-2">
                                <div>
                                    <h3 className="mb-1 text-lg font-medium">
                                        Coverage Summary
                                    </h3>
                                    <p className="whitespace-pre-line text-sm">
                                        {coverageSummary || "No additional coverage summary provided."}
                                    </p>
                                </div>

                                <div>
                                    <h3 className="mb-1 text-lg font-medium">
                                        Exclusions and Remarks
                                    </h3>
                                    <p className="whitespace-pre-line text-sm">
                                        {exclusionsRemarks || "No additional exclusions or remarks provided."}
                                    </p>
                                </div>
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </GuestLayout>
    );
}

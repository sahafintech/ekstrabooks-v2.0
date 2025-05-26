import { usePage } from "@inertiajs/react";
import GuestLayout from "@/Layouts/GuestLayout";
import { Button } from "@/Components/ui/button";
import { formatCurrency } from "@/lib/utils";
import { useToast } from "@/hooks/use-toast";
import { PrinterIcon, DownloadIcon } from "lucide-react";
import { useEffect, useState, useRef } from "react";
import {
    Table,
    TableBody,
    TableHeader,
    TableRow,
    TableHead,
    TableCell,
} from "@/Components/ui/table";
import { SidebarSeparator } from "@/Components/ui/sidebar";

export default function PublicView({ quotation }) {
    const { flash = {} } = usePage().props;
    const { toast } = useToast();
    const [isLoading, setIsLoading] = useState({
        print: false,
        pdf: false,
    });
    const contentRef = useRef(null);

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
                orientation: "portrait",
                unit: "mm",
                format: "a4",
            });

            // Calculate dimensions
            const imgWidth = 210; // A4 width in mm
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
            <div className="container mx-auto px-4 py-8">
                {/* Action Buttons */}
                <div className="flex justify-end gap-4 mb-6 w-full md:w-[210mm] mx-auto print:hidden">
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
                    className="bg-white p-4 md:p-8 mx-auto w-full md:w-[210mm] print:shadow-none print:rounded-none print-container"
                >
                    <div className="p-6 sm:p-8">
                        {/* Invoice Header */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div>
                                {quotation.business.logo && (
                                    <div className="mb-3">
                                        <img
                                            src={`/uploads/media/${quotation.business.logo}`}
                                            alt="Business Logo"
                                            className="max-h-32 object-contain"
                                        />
                                    </div>
                                )}
                                <h2 className="text-2xl font-bold text-primary">
                                    {quotation.business.business_name}
                                </h2>
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
                                    {quotation.order_number && (
                                        <p>
                                            <span className="font-medium">
                                                Order Number:
                                            </span>{" "}
                                            {quotation.order_number}
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
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Item</TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead className="text-right">
                                            Quantity
                                        </TableHead>
                                        <TableHead className="text-right">
                                            Unit Cost
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
                                                    amount:
                                                        item.quantity *
                                                        item.unit_cost,
                                                    currency:
                                                        quotation.currency,
                                                })}
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
                                        {formatCurrency({
                                            amount: quotation.sub_total,
                                            currency: quotation.currency,
                                        })}
                                    </span>
                                </div>

                                {/* Tax details */}
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

                                {/* Discount */}
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

                                {/* Total */}
                                <div className="flex justify-between py-3 border-t border-b font-bold text-lg">
                                    <span>Total:</span>
                                    <span>
                                        {formatCurrency({
                                            amount: quotation.grand_total,
                                            currency: quotation.currency,
                                        })}
                                    </span>
                                </div>

                                {/* Base currency equivalent if different currency */}
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

                                {/* Base currency equivalent total */}
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

                        {/* Notes & Terms */}
                        {(quotation.note || quotation.footer) && (
                            <div className="mt-8 space-y-4">
                                {quotation.note && (
                                    <div>
                                        <h3 className="font-medium mb-1">
                                            Notes:
                                        </h3>
                                        <p className="text-sm">
                                            {quotation.note}
                                        </p>
                                    </div>
                                )}

                                {quotation.footer && (
                                    <div>
                                        <h3 className="font-medium mb-1">
                                            Terms & Conditions:
                                        </h3>
                                        <p className="text-sm">
                                            {quotation.footer}
                                        </p>
                                    </div>
                                )}
                            </div>
                        )}
                    </div>
                </div>
            </div>
        </GuestLayout>
    );
}

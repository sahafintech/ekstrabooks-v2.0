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

export default function PublicView({ purchase_return }) {
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
            pdf.save(`purchase-return-${purchase_return.return_number}.pdf`);
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
                        {/* Purchase Return Header */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div>
                                {purchase_return.business.logo && (
                                    <div className="mb-3">
                                        <img
                                            src={`/uploads/media/${purchase_return.business.logo}`}
                                            alt="Business Logo"
                                            className="max-h-32 object-contain"
                                        />
                                    </div>
                                )}
                                <h2 className="text-2xl font-bold text-primary">
                                    {purchase_return.business.business_name}
                                </h2>
                                <div className="mt-2 text-sm">
                                    <p>{purchase_return.business.address}</p>
                                    <p>{purchase_return.business.email}</p>
                                    <p>{purchase_return.business.phone}</p>
                                </div>
                            </div>
                            <div className="md:text-right">
                                <h1 className="text-2xl font-bold">
                                    {purchase_return.title}
                                </h1>
                                <div className="mt-2 text-sm">
                                    <p>
                                        <span className="font-medium">
                                            Purchase Return #:
                                        </span>{" "}
                                        {purchase_return.return_number}
                                    </p>
                                    <p>
                                        <span className="font-medium">
                                            Purchase Return Date:
                                        </span>{" "}
                                        {purchase_return.return_date}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <SidebarSeparator className="my-6" />

                        {/* Customer Information */}
                        <div className="mb-8">
                            <h3 className="font-medium text-lg mb-2">
                                Returned To:
                            </h3>
                            <div className="text-sm">
                                <p className="font-medium">
                                    {purchase_return.vendor?.name}
                                </p>
                                {purchase_return.vendor?.company_name && (
                                    <p>
                                        {purchase_return.vendor?.company_name}
                                    </p>
                                )}
                                <p>{purchase_return.vendor?.address}</p>
                                <p>{purchase_return.vendor?.email}</p>
                                <p>{purchase_return.vendor?.mobile}</p>
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
                                    {purchase_return.items.map(
                                        (item, index) => (
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
                                                        purchase_return.currency,
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency({
                                                        amount:
                                                            item.quantity *
                                                            item.unit_cost,
                                                        currency:
                                                            purchase_return.currency,
                                                    })}
                                                </TableCell>
                                            </TableRow>
                                        )
                                    )}
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
                                            amount: purchase_return.sub_total,
                                            currency: purchase_return.currency,
                                        })}
                                    </span>
                                </div>

                                {/* Tax details */}
                                {purchase_return.taxes.map((tax, index) => (
                                    <div
                                        key={index}
                                        className="flex justify-between py-2"
                                    >
                                        <span>{tax.name}:</span>
                                        <span>
                                            {formatCurrency({
                                                amount: tax.amount,
                                                currency:
                                                    purchase_return.currency,
                                            })}
                                        </span>
                                    </div>
                                ))}

                                {/* Discount */}
                                {purchase_return.discount > 0 && (
                                    <div className="flex justify-between py-2">
                                        <span>Discount</span>
                                        <span>
                                            -
                                            {formatCurrency({
                                                amount: purchase_return.discount,
                                                currency:
                                                    purchase_return.currency,
                                            })}
                                        </span>
                                    </div>
                                )}

                                {/* Total */}
                                <div className="flex justify-between py-3 border-t border-b font-bold text-lg">
                                    <span>Total:</span>
                                    <span>
                                        {formatCurrency({
                                            amount: purchase_return.grand_total,
                                            currency: purchase_return.currency,
                                        })}
                                    </span>
                                </div>

                                {/* Base currency equivalent if different currency */}
                                {purchase_return.currency !==
                                    purchase_return.business.currency && (
                                    <div className="flex justify-between py-2 text-gray-500 text-sm">
                                        <span>Exchange Rate:</span>
                                        <span>
                                            1{" "}
                                            {purchase_return.business.currency}{" "}
                                            ={" "}
                                            {formatCurrency(
                                                purchase_return.exchange_rate,
                                                purchase_return.currency
                                            )}
                                        </span>
                                    </div>
                                )}

                                {/* Base currency equivalent total */}
                                {purchase_return.currency !==
                                    purchase_return.business.currency && (
                                    <div className="flex justify-between py-2 text-sm text-gray-600">
                                        <span>Equivalent to:</span>
                                        <span>
                                            {formatCurrency(
                                                purchase_return.converted_total,
                                                purchase_return.currency,
                                            )}
                                        </span>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Notes & Terms */}
                        {(purchase_return.note || purchase_return.footer) && (
                            <div className="mt-8 space-y-4">
                                {purchase_return.note && (
                                    <div>
                                        <h3 className="font-medium mb-1">
                                            Notes:
                                        </h3>
                                        <p className="text-sm">
                                            {purchase_return.note}
                                        </p>
                                    </div>
                                )}

                                {purchase_return.footer && (
                                    <div>
                                        <h3 className="font-medium mb-1">
                                            Terms & Conditions:
                                        </h3>
                                        <p className="text-sm">
                                            {purchase_return.footer}
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

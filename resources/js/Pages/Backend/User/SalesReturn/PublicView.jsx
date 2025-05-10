import { Head, usePage } from "@inertiajs/react";
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

export default function PublicView({ sales_return }) {
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
            pdf.save(`Sales-return-${sales_return.id}.pdf`);
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
            <Head title={`Sales Return - ${sales_return.return_number}`} />

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
                        {/* Sales Return Header */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div>
                                {sales_return.business.logo && (
                                    <div className="mb-3">
                                        <img
                                            src={`/uploads/media/${sales_return.business.logo}`}
                                            alt="Business Logo"
                                            className="max-h-32 object-contain"
                                        />
                                    </div>
                                )}
                                <h2 className="text-2xl font-bold text-primary">
                                    {sales_return.business.business_name}
                                </h2>
                                <div className="mt-2 text-sm">
                                    <p>{sales_return.business.address}</p>
                                    <p>{sales_return.business.email}</p>
                                    <p>{sales_return.business.phone}</p>
                                </div>
                            </div>
                            <div className="md:text-right">
                                <h1 className="text-2xl font-bold">
                                    {sales_return.title}
                                </h1>
                                <div className="mt-2 text-sm">
                                    <p>
                                        <span className="font-medium">
                                            Sales Return #:
                                        </span>{" "}
                                        {sales_return.return_number}
                                    </p>
                                    <p>
                                        <span className="font-medium">
                                            Sales Return Date:
                                        </span>{" "}
                                        {sales_return.return_date}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <SidebarSeparator className="my-6" />

                        {/* Customer Information */}
                        <div className="mb-8">
                            <h3 className="font-medium text-lg mb-2">
                                Returned From:
                            </h3>
                            <div className="text-sm">
                                <p className="font-medium">
                                    {sales_return.customer?.name}
                                </p>
                                {sales_return.customer?.company_name && (
                                    <p>{sales_return.customer?.company_name}</p>
                                )}
                                <p>{sales_return.customer?.address}</p>
                                <p>{sales_return.customer?.email}</p>
                                <p>{sales_return.customer?.mobile}</p>
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
                                    {sales_return.items.map((item, index) => (
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
                                                    sales_return.currency
                                                )}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {formatCurrency({
                                                    amount:
                                                        item.quantity *
                                                        item.unit_cost,
                                                    currency:
                                                        sales_return.currency,
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
                                            amount: sales_return.sub_total,
                                            currency: sales_return.currency,
                                        })}
                                    </span>
                                </div>

                                {/* Tax details */}
                                {sales_return.taxes.map((tax, index) => (
                                    <div
                                        key={index}
                                        className="flex justify-between py-2"
                                    >
                                        <span>{tax.name}:</span>
                                        <span>
                                            {formatCurrency({
                                                amount: tax.amount,
                                                currency: sales_return.currency,
                                            })}
                                        </span>
                                    </div>
                                ))}

                                {/* Discount */}
                                {sales_return.discount > 0 && (
                                    <div className="flex justify-between py-2">
                                        <span>Discount</span>
                                        <span>
                                            -
                                            {formatCurrency({
                                                amount: sales_return.discount,
                                                currency: sales_return.currency,
                                            })}
                                        </span>
                                    </div>
                                )}

                                {/* Total */}
                                <div className="flex justify-between py-3 border-t border-b font-bold text-lg">
                                    <span>Total:</span>
                                    <span>
                                        {formatCurrency({
                                            amount: sales_return.grand_total,
                                            currency: sales_return.currency,
                                        })}
                                    </span>
                                </div>

                                {/* Base currency equivalent if different currency */}
                                {sales_return.currency !==
                                    sales_return.business.currency && (
                                    <div className="flex justify-between py-2 text-gray-500 text-sm">
                                        <span>Exchange Rate:</span>
                                        <span>
                                            1 {sales_return.business.currency} ={" "}
                                            {formatCurrency(
                                                sales_return.exchange_rate,
                                                sales_return.currency
                                            )}
                                        </span>
                                    </div>
                                )}

                                {/* Base currency equivalent total */}
                                {sales_return.currency !==
                                    sales_return.business.currency && (
                                    <div className="flex justify-between py-2 text-sm text-gray-600">
                                        <span>Equivalent to:</span>
                                        <span>
                                            {formatCurrency(
                                                sales_return.converted_total,
                                                sales_return.currency
                                            )}
                                        </span>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Notes & Terms */}
                        {(sales_return.note || sales_return.footer) && (
                            <div className="mt-8 space-y-4">
                                {sales_return.note && (
                                    <div>
                                        <h3 className="font-medium mb-1">
                                            Notes:
                                        </h3>
                                        <p className="text-sm">
                                            {sales_return.note}
                                        </p>
                                    </div>
                                )}

                                {sales_return.footer && (
                                    <div>
                                        <h3 className="font-medium mb-1">
                                            Terms & Conditions:
                                        </h3>
                                        <p className="text-sm">
                                            {sales_return.footer}
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

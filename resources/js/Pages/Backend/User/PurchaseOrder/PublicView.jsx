import { usePage } from "@inertiajs/react";
import GuestLayout from "@/Layouts/GuestLayout";
import { Button } from "@/Components/ui/button";
import { formatCurrency } from "@/lib/utils";
import { Toaster } from "@/Components/ui/toaster";
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

export default function PublicView({ purchase_order, attachments }) {
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

            // Create a canvas from the content
            const canvas = await html2canvas(content, {
                scale: 4,
                useCORS: true, // Enable CORS for images
                logging: false,
                windowWidth: content.scrollWidth,
                windowHeight: content.scrollHeight,
                allowTaint: true,
                backgroundColor: "#ffffff",
            });

            // Calculate dimensions
            const imgWidth = 210; // A4 width in mm
            const pageHeight = 297; // A4 height in mm
            const imgHeight = (canvas.height * imgWidth) / canvas.width;

            // Create PDF with higher quality
            const pdf = new jsPDF("p", "mm", "a4");
            let heightLeft = imgHeight;
            let position = 0;
            let pageData = canvas.toDataURL("image/jpeg", 1.0);

            // Add first page
            pdf.addImage(pageData, "JPEG", 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;

            // Add subsequent pages if content is longer than one page
            while (heightLeft >= 0) {
                position = heightLeft - imgHeight;
                pdf.addPage();
                pdf.addImage(
                    pageData,
                    "JPEG",
                    0,
                    position,
                    imgWidth,
                    imgHeight
                );
                heightLeft -= pageHeight;
            }

            // Save the PDF
            pdf.save(`Purchase_Order_${purchase_order.order_number}.pdf`);
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
            <div className="container mx-auto py-6 px-4 md:px-6">
                {/* Header with Actions */}
                <div className="flex justify-end items-center mb-6 w-full md:w-[210mm] mx-auto print:hidden">
                    <div className="flex items-center space-x-2">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handlePrint}
                            disabled={isLoading.print}
                        >
                            <PrinterIcon className="h-4 w-4 mr-2" />
                            Print
                        </Button>

                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleDownloadPDF}
                            disabled={isLoading.pdf}
                        >
                            <DownloadIcon className="h-4 w-4 mr-2" />
                            Download PDF
                        </Button>
                    </div>
                </div>

                {/* Purchase Order Content */}
                <div
                    ref={contentRef}
                    className="bg-white p-4 md:p-8 mx-auto w-full md:w-[210mm] print:shadow-none print:rounded-none print-container"
                >
                    {/* Header */}
                    <div className="flex justify-between items-start mb-8">
                        <div>
                            {purchase_order.business.logo && (
                                <div className="mb-3">
                                    <img
                                        src={`/uploads/media/${purchase_order.business.logo}`}
                                        alt="Business Logo"
                                        className="max-h-16 object-contain"
                                    />
                                </div>
                            )}
                            <h2 className="text-2xl font-bold text-primary">
                                {purchase_order.business.business_name}
                            </h2>
                            <div className="mt-2 text-sm">
                                <p>{purchase_order.business.address}</p>
                                <p>{purchase_order.business.email}</p>
                                <p>{purchase_order.business.phone}</p>
                            </div>
                        </div>

                        <div className="md:text-right">
                            <h1 className="text-2xl font-bold">
                                {purchase_order.title}
                            </h1>
                            <div className="mt-2 text-sm">
                                <p>
                                    <span className="font-medium">
                                        Purchase Order #:
                                    </span>{" "}
                                    {purchase_order.order_number}
                                </p>
                                <p>
                                    <span className="font-medium">
                                        Purchase Order Date:
                                    </span>{" "}
                                    {purchase_order.order_date}
                                </p>
                            </div>
                        </div>
                    </div>

                    <hr className="my-6" />

                    {/* Vendor Information */}
                    <div className="mb-8">
                        <h3 className="font-medium text-lg mb-2">Order To:</h3>
                        <div className="text-sm">
                            <p className="font-medium">
                                {purchase_order.vendor?.name}
                            </p>
                            {purchase_order.vendor?.company_name && (
                                <p>{purchase_order.vendor?.company_name}</p>
                            )}
                            <p>{purchase_order.vendor?.address}</p>
                            <p>{purchase_order.vendor?.email}</p>
                            <p>{purchase_order.vendor?.mobile}</p>
                        </div>
                    </div>

                    {/* Purchase Order Items */}
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
                                {purchase_order.items.map((item, index) => (
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
                                            {formatCurrency({
                                                amount: item.unit_cost,
                                                currency:
                                                    purchase_order.currency,
                                            })}
                                        </TableCell>
                                        <TableCell className="text-right">
                                            {formatCurrency({
                                                amount:
                                                    item.quantity *
                                                    item.unit_cost,
                                                currency:
                                                    purchase_order.currency,
                                            })}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>

                    {/* Purchase Order Summary */}
                    <div className="flex justify-end">
                        <div className="w-full md:w-1/2 lg:w-1/3 space-y-2">
                            <div className="flex justify-between py-2 border-t">
                                <span className="font-medium">Subtotal:</span>
                                <span>
                                    {formatCurrency({
                                        amount: purchase_order.sub_total,
                                        currency: purchase_order.currency,
                                    })}
                                </span>
                            </div>

                            {/* Tax details */}
                            {purchase_order.taxes.map((tax, index) => (
                                <div
                                    key={index}
                                    className="flex justify-between py-2"
                                >
                                    <span>{tax.name}:</span>
                                    <span>
                                        {formatCurrency({
                                            amount: tax.amount,
                                            currency: purchase_order.currency,
                                        })}
                                    </span>
                                </div>
                            ))}

                            {/* Discount */}
                            {purchase_order.discount > 0 && (
                                <div className="flex justify-between py-2">
                                    <span>Discount</span>
                                    <span>
                                        -
                                        {formatCurrency({
                                            amount: purchase_order.discount,
                                            currency: purchase_order.currency,
                                        })}
                                    </span>
                                </div>
                            )}

                            {/* Total */}
                            <div className="flex justify-between py-3 border-t border-b font-bold text-lg">
                                <span>Total:</span>
                                <span>
                                    {formatCurrency({
                                        amount: purchase_order.grand_total,
                                        currency: purchase_order.currency,
                                    })}
                                </span>
                            </div>

                            {/* Base currency equivalent if different currency */}
                            {purchase_order.currency !==
                                purchase_order.business.currency && (
                                <div className="flex justify-between py-2 text-gray-500 text-sm">
                                    <span>Exchange Rate:</span>
                                    <span>
                                        1 {purchase_order.business.currency} ={" "}
                                        {formatCurrency({
                                            amount: purchase_order.exchange_rate,
                                            currency: purchase_order.currency,
                                        })}
                                    </span>
                                </div>
                            )}

                            {/* Base currency equivalent total */}
                            {purchase_order.currency !==
                                purchase_order.business.currency && (
                                <div className="flex justify-between py-2 text-sm text-gray-600">
                                    <span>Equivalent to:</span>
                                    <span>
                                        {formatCurrency({
                                            amount: purchase_order.converted_total,
                                            currency: purchase_order.currency,
                                        })}
                                    </span>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Notes & Terms */}
                    {(purchase_order.note || purchase_order.footer) && (
                        <div className="mt-8 space-y-4">
                            {purchase_order.note && (
                                <div>
                                    <h3 className="font-medium mb-1">Notes:</h3>
                                    <p className="text-sm">
                                        {purchase_order.note}
                                    </p>
                                </div>
                            )}

                            {purchase_order.footer && (
                                <div>
                                    <h3 className="font-medium mb-1">
                                        Terms & Conditions:
                                    </h3>
                                    <p className="text-sm">
                                        {purchase_order.footer}
                                    </p>
                                </div>
                            )}
                        </div>
                    )}

                    {/* Attachments */}
                    {attachments && attachments.length > 0 && (
                        <div className="mt-8">
                            <h3 className="font-medium mb-2">Attachments:</h3>
                            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                {attachments.map((attachment, index) => (
                                    <div
                                        key={index}
                                        className="border rounded p-4"
                                    >
                                        <a
                                            href={attachment.path}
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            className="text-blue-600 hover:underline"
                                        >
                                            {attachment.file_name}
                                        </a>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            <Toaster />
        </GuestLayout>
    );
}

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
import { QRCodeSVG } from "qrcode.react";

export default function PublicView({ projectSubcontract }) {
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
            pdf.save(`invoice-${invoice.invoice_number}.pdf`);
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
            <Head
                title={`Subcontract Agreement - ${projectSubcontract.subcontract_no}`}
            />

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
                        {/* Subcontract Header */}
                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                            <div>
                                {projectSubcontract.business?.logo && (
                                    <div className="mb-3">
                                        <img
                                            src={`/uploads/media/${projectSubcontract.business.logo}`}
                                            alt="Business Logo"
                                            className="max-h-32 object-contain"
                                        />
                                    </div>
                                )}
                                <h2 className="text-2xl font-bold text-primary">
                                    {projectSubcontract.business?.business_name}
                                </h2>
                                <div className="mt-2 text-sm">
                                    <p>
                                        {projectSubcontract.business?.address}
                                    </p>
                                    <p>{projectSubcontract.business?.email}</p>
                                    <p>{projectSubcontract.business?.phone}</p>
                                </div>
                            </div>
                            <div className="md:text-right">
                                <h1 className="text-2xl font-bold">
                                    Subcontract Agreement
                                </h1>
                                <div className="mt-2 text-sm">
                                    <p>
                                        <span className="font-medium">
                                            Subcontract #:
                                        </span>{" "}
                                        {projectSubcontract.subcontract_no}
                                    </p>
                                    <p>
                                        <span className="font-medium">
                                            Start Date:
                                        </span>{" "}
                                        {projectSubcontract.start_date}
                                    </p>
                                    <p>
                                        <span className="font-medium">
                                            End Date:
                                        </span>{" "}
                                        {projectSubcontract.end_date}
                                    </p>
                                    <p>
                                        <span className="font-medium">
                                            Project:
                                        </span>{" "}
                                        {
                                            projectSubcontract.project
                                                ?.project_name
                                        }
                                    </p>
                                </div>
                                <div className="mt-4 md:flex md:justify-end">
                                    <QRCodeSVG
                                        value={route(
                                            "project_subcontracts.show_public_project_subcontract",
                                            projectSubcontract.short_code
                                        )}
                                        size={100}
                                        level="H"
                                        includeMargin={true}
                                        className="print:block"
                                    />
                                </div>
                            </div>
                        </div>

                        <SidebarSeparator className="my-6" />

                        {/* Vendor Information */}
                        <div className="mb-8">
                            <h3 className="font-medium text-lg mb-2">
                                Vendor Information:
                            </h3>
                            <div className="text-sm">
                                <p className="font-medium">
                                    {projectSubcontract.vendor?.name}
                                </p>
                                {projectSubcontract.vendor?.company_name && (
                                    <p>
                                        {
                                            projectSubcontract.vendor
                                                ?.company_name
                                        }
                                    </p>
                                )}
                                <p>{projectSubcontract.vendor?.address}</p>
                                <p>{projectSubcontract.vendor?.email}</p>
                                <p>{projectSubcontract.vendor?.mobile}</p>
                            </div>
                        </div>

                        <p className="text-sm">
                            {projectSubcontract.description}
                        </p>

                        <SidebarSeparator className="my-6" />

                        {/* Contract Items */}
                        <div className="mb-8">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Task</TableHead>
                                        <TableHead>Description</TableHead>
                                        <TableHead>Cost Code</TableHead>
                                        <TableHead>UOM</TableHead>
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
                                    {projectSubcontract.tasks?.map(
                                        (item, index) => (
                                            <TableRow key={index}>
                                                <TableCell className="font-medium">
                                                    {item.task?.task_code}
                                                </TableCell>
                                                <TableCell>
                                                    {item.task?.description}
                                                </TableCell>
                                                <TableCell>
                                                    {item.cost_code?.code}
                                                </TableCell>
                                                <TableCell>
                                                    {item.uom}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {item.quantity}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency(
                                                        item.unit_cost,
                                                        projectSubcontract.currency,
                                                    )}
                                                </TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency(
                                                        item.quantity *
                                                            item.unit_cost,
                                                        projectSubcontract.currency
                                                    )}
                                                </TableCell>
                                            </TableRow>
                                        )
                                    )}
                                </TableBody>
                            </Table>
                        </div>

                        {/* Subcontract Summary */}
                        <div className="flex justify-end">
                            <div className="w-full md:w-1/2 lg:w-1/3 space-y-2">
                                <div className="flex justify-between py-2 border-t">
                                    <span className="font-medium">
                                        Subtotal:
                                    </span>
                                    <span>
                                        {formatCurrency(
                                            projectSubcontract.sub_total,
                                            projectSubcontract.currency
                                        )}
                                    </span>
                                </div>

                                {/* Tax details */}
                                {projectSubcontract.taxes?.map((tax, index) => (
                                    <div
                                        key={index}
                                        className="flex justify-between py-2"
                                    >
                                        <span>{tax.name}:</span>
                                        <span>
                                            {formatCurrency(
                                                tax.amount,
                                                projectSubcontract.currency
                                            )}
                                        </span>
                                    </div>
                                ))}

                                {/* Discount */}
                                {projectSubcontract.discount > 0 && (
                                    <div className="flex justify-between py-2">
                                        <span>Discount</span>
                                        <span>
                                            -
                                            {formatCurrency(
                                                projectSubcontract.discount,
                                                projectSubcontract.currency
                                            )}
                                        </span>
                                    </div>
                                )}

                                {/* Total */}
                                <div className="flex justify-between py-3 border-t border-b font-bold text-lg">
                                    <span>Total:</span>
                                    <span>
                                        {formatCurrency(
                                            projectSubcontract.grand_total,
                                            projectSubcontract.currency
                                        )}
                                    </span>
                                </div>

                                {/* Base currency equivalent if different currency */}
                                {projectSubcontract.currency !==
                                    projectSubcontract.business?.currency && (
                                    <div className="flex justify-between py-2 text-gray-500 text-sm">
                                        <span>Exchange Rate:</span>
                                        <span>
                                            1{" "}
                                            {
                                                projectSubcontract.business
                                                    ?.currency
                                            }{" "}
                                            ={" "}
                                            {formatCurrency(
                                                projectSubcontract.exchange_rate,
                                                projectSubcontract.currency
                                            )}
                                        </span>
                                    </div>
                                )}

                                {/* Base currency equivalent total */}
                                {projectSubcontract.currency !==
                                    projectSubcontract.business?.currency && (
                                    <div className="flex justify-between py-2 text-sm text-gray-600">
                                        <span>Equivalent to:</span>
                                        <span>
                                            {formatCurrency(
                                                projectSubcontract.converted_total,
                                                projectSubcontract.currency
                                            )}
                                        </span>
                                    </div>
                                )}
                            </div>
                        </div>

                        {/* Notes & Terms */}
                        {(projectSubcontract.note ||
                            projectSubcontract.terms) && (
                            <div className="mt-8 space-y-4">
                                {projectSubcontract.note && (
                                    <div>
                                        <h3 className="font-medium mb-1">
                                            Notes:
                                        </h3>
                                        <p className="text-sm">
                                            {projectSubcontract.note}
                                        </p>
                                    </div>
                                )}

                                {projectSubcontract.terms && (
                                    <div>
                                        <h3 className="font-medium mb-1">
                                            Terms & Conditions:
                                        </h3>
                                        <p className="text-sm">
                                            {projectSubcontract.terms}
                                        </p>
                                    </div>
                                )}
                            </div>
                        )}

                        {/* Signatures */}
                        <div className="mt-12 grid grid-cols-2 gap-8">
                            <div>
                                <div className="border-t border-gray-300 w-48 mb-2"></div>
                                <p className="font-medium">Project Manager</p>
                                <p className="text-sm text-gray-600">
                                    {projectSubcontract.project?.project_manager
                                        ?.name || "________________"}
                                </p>
                            </div>
                            <div>
                                <div className="border-t border-gray-300 w-48 mb-2"></div>
                                <p className="font-medium">Supplier</p>
                                <p className="text-sm text-gray-600">
                                    {projectSubcontract.vendor?.name ||
                                        "________________"}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </GuestLayout>
    );
}

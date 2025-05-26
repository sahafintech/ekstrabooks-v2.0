import { Head } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { formatCurrency } from "@/lib/utils";
import {
    DropdownMenu,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import PageHeader from "@/Components/PageHeader";
import { PrinterIcon, DownloadIcon, MoreVertical } from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import {
    Table,
    TableBody,
    TableHeader,
    TableRow,
    TableHead,
    TableCell,
} from "@/Components/ui/table";

export default function PaymentVoucher({ payment }) {
    const [isLoading, setIsLoading] = useState({
        print: false,
        pdf: false,
    });

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

            const canvas = await html2canvas(content, {
                scale: 4,
                useCORS: true,
                logging: false,
                windowWidth: content.scrollWidth,
                windowHeight: content.scrollHeight,
                allowTaint: true,
                backgroundColor: "#ffffff",
            });

            const imgWidth = 210;
            const pageHeight = 297;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;

            const pdf = new jsPDF("p", "mm", "a4");
            let heightLeft = imgHeight;
            let position = 0;
            let pageData = canvas.toDataURL("image/jpeg", 1.0);

            pdf.addImage(pageData, "JPEG", 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;

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

            pdf.save(`Subcontract_Payment_${payment.id}.pdf`);
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
        <AuthenticatedLayout>
            <SidebarInset>
                <div className="space-y-4">
                    <PageHeader
                        page="Subcontract Payments"
                        subpage={`Payment #${payment.id}`}
                        url="project_subcontracts.index"
                    />

                    <div className="flex items-center justify-end space-x-2 mb-4">
                        <Button
                            variant="outline"
                            onClick={handlePrint}
                            disabled={isLoading.print}
                            className="flex items-center"
                        >
                            <PrinterIcon className="mr-2 h-4 w-4" />
                            {isLoading.print ? "Printing..." : "Print"}
                        </Button>

                        <Button
                            variant="outline"
                            onClick={handleDownloadPDF}
                            disabled={isLoading.pdf}
                            className="flex items-center"
                        >
                            <DownloadIcon className="mr-2 h-4 w-4" />
                            {isLoading.pdf ? "Downloading..." : "Download PDF"}
                        </Button>

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline" size="icon">
                                    <MoreVertical className="h-4 w-4" />
                                </Button>
                            </DropdownMenuTrigger>
                        </DropdownMenu>
                    </div>

                    <div className="print-container">
                        <div className="p-6 sm:p-8">
                            {/* Payment Voucher Header */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <div>
                                    {payment.business.logo && (
                                        <div className="mb-3">
                                            <img
                                                src={`/uploads/media/${payment.business.logo}`}
                                                alt="Business Logo"
                                                className="h-16 w-auto"
                                            />
                                        </div>
                                    )}
                                    <h1 className="text-2xl font-bold text-gray-900 dark:text-gray-100">
                                        Payment Voucher
                                    </h1>
                                    <p className="text-gray-600 dark:text-gray-400">
                                        #{payment.id}
                                    </p>
                                </div>
                                <div className="text-right">
                                    <p className="text-gray-600 dark:text-gray-400">
                                        Date: {payment.date}
                                    </p>
                                </div>
                            </div>

                            {/* Payment Details */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <div>
                                    <h2 className="text-lg font-semibold mb-2">
                                        Subcontractor Details
                                    </h2>
                                    <p className="text-gray-600 dark:text-gray-400">
                                        {payment.vendor.name}
                                    </p>
                                    <p className="text-gray-600 dark:text-gray-400">
                                        {payment.vendor.address}
                                    </p>
                                    <p className="text-gray-600 dark:text-gray-400">
                                        {payment.vendor.mobile}
                                    </p>
                                </div>
                                <div>
                                    <h2 className="text-lg font-semibold mb-2">
                                        Project Details
                                    </h2>
                                    <p className="text-gray-600 dark:text-gray-400">
                                        Project:{" "}
                                        {
                                            payment.project_subcontract.project
                                                .project_name
                                        }
                                    </p>
                                </div>
                            </div>

                            {/* Payment Items */}
                            <div className="mb-8">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>
                                                Project Subcontract
                                            </TableHead>
                                            <TableHead>Supplier</TableHead>
                                            <TableHead>
                                                Payment Account
                                            </TableHead>
                                            <TableHead className="text-right">
                                                Amount
                                            </TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        <TableRow>
                                            <TableCell>
                                                {
                                                    payment.project_subcontract
                                                        .project.project_name
                                                }
                                            </TableCell>
                                            <TableCell>
                                                {payment.vendor.name}
                                            </TableCell>
                                            <TableCell>
                                                {payment.account.account_name}
                                            </TableCell>
                                            <TableCell className="text-right">
                                                {formatCurrency(payment.amount)}
                                            </TableCell>
                                        </TableRow>
                                        <TableRow>
                                            <TableCell className="font-bold">
                                                Total
                                            </TableCell>
                                            <TableCell
                                                className="text-right font-bold"
                                                colSpan={3}
                                            >
                                                {formatCurrency(payment.amount)}
                                            </TableCell>
                                        </TableRow>
                                    </TableBody>
                                </Table>
                            </div>

                            {/* Payment Notes */}
                            {payment.notes && (
                                <div className="mb-8">
                                    <h2 className="text-lg font-semibold mb-2">
                                        Notes
                                    </h2>
                                    <p className="text-gray-600 dark:text-gray-400">
                                        {payment.notes}
                                    </p>
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}

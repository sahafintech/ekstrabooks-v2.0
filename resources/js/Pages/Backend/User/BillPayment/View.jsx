import { Head, Link, router } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset, SidebarSeparator } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { formatCurrency } from "@/lib/utils";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger
} from "@/Components/ui/dropdown-menu";
import PageHeader from "@/Components/PageHeader";
import {
    PrinterIcon,
    MailIcon,
    DownloadIcon,
    MoreVertical,
    ShareIcon,
    Edit
} from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import { Table, TableBody, TableHeader, TableRow, TableHead, TableCell } from "@/Components/ui/table";

export default function View({ payment, decimalPlace }) {
    const [isLoading, setIsLoading] = useState({
        print: false,
        pdf: false
    });

    const handlePrint = () => {
        setIsLoading(prev => ({ ...prev, print: true }));
        setTimeout(() => {
            window.print();
            setIsLoading(prev => ({ ...prev, print: false }));
        }, 300);
    };

    const handleDownloadPDF = async () => {
        setIsLoading(prev => ({ ...prev, pdf: true }));
        try {
            // Dynamically import the required libraries
            const html2canvas = (await import('html2canvas')).default;
            const { jsPDF } = await import('jspdf');

            // Get the content element
            const content = document.querySelector('.print-container');
            
            // Create a canvas from the content
            const canvas = await html2canvas(content, {
                scale: 4,
                useCORS: true, // Enable CORS for images
                logging: false,
                windowWidth: content.scrollWidth,
                windowHeight: content.scrollHeight,
                allowTaint: true,
                backgroundColor: '#ffffff'
            });

            // Calculate dimensions
            const imgWidth = 210; // A4 width in mm
            const pageHeight = 297; // A4 height in mm
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            
            // Create PDF with higher quality
            const pdf = new jsPDF('p', 'mm', 'a4');
            let heightLeft = imgHeight;
            let position = 0;
            let pageData = canvas.toDataURL('image/jpeg', 1.0);

            // Add first page
            pdf.addImage(pageData, 'JPEG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;

            // Add subsequent pages if content is longer than one page
            while (heightLeft >= 0) {
                position = heightLeft - imgHeight;
                pdf.addPage();
                pdf.addImage(pageData, 'JPEG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
            }

            // Save the PDF
            pdf.save(`Payment_${payment.id}.pdf`);
        } catch (error) {
            console.error('Error generating PDF:', error);
            toast({
                variant: "destructive",
                title: "Error",
                description: "Failed to generate PDF. Please try again.",
            });
        } finally {
            setIsLoading(prev => ({ ...prev, pdf: false }));
        }
    };

    const handleShareLink = () => {
        router.visit(route('invoices.link', invoice.id), {
            preserveScroll: true
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Bill Payment #${payment.id}`} />

            <SidebarInset>
                <div className="space-y-4">
                    <PageHeader
                        page="Bill Payments"
                        subpage={`Payment #${payment.id}`}
                        url="bill_payments.index"
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
                            <DropdownMenuContent align="end">
                                <DropdownMenuItem onClick={handleShareLink}>
                                    <ShareIcon className="mr-2 h-4 w-4" />
                                    <span>Share Link</span>
                                </DropdownMenuItem>
                                <DropdownMenuItem asChild>
                                    <Link href={route("bill_payments.edit", payment.id)} className="flex items-center">
                                        <Edit className="mr-2 h-4 w-4" />
                                        <span>Edit Payment</span>
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <div className="print-container">
                        <div className="p-6 sm:p-8">
                            {/* Invoice Header */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                                <div>
                                    {payment.business.logo && (
                                        <div className="mb-3">
                                            <img
                                                src={`/uploads/media/${payment.business.logo}`}
                                                alt="Business Logo"
                                                className="max-h-16 object-contain"
                                            />
                                        </div>
                                    )}
                                    <h2 className="text-2xl font-bold text-primary">
                                        {payment.business.business_name}
                                    </h2>
                                    <div className="mt-2 text-sm">
                                        <p>{payment.business.address}</p>
                                        <p>{payment.business.email}</p>
                                        <p>{payment.business.phone}</p>
                                    </div>
                                </div>
                                <div className="md:text-right">
                                    <h1 className="text-2xl font-bold">Payment Voucher</h1>
                                    <div className="mt-2 text-sm">
                                        <p><span className="font-medium">Payment #:</span> {payment.id}</p>
                                        <p><span className="font-medium">Payment Date:</span> {payment.date}</p>
                                        <p><span className="font-medium">Payment Method:</span> {payment.method}</p>
                                        <p><span className="font-medium">Payment Type:</span> {payment.type}</p>
                                    </div>
                                </div>
                            </div>

                            <SidebarSeparator className="my-6" />

                            {/* Customer Information */}
                            <div className="mb-8">
                                <h3 className="font-medium text-lg mb-2">Paid To:</h3>
                                <div className="text-sm">
                                    <p className="font-medium">{payment.vendor?.name}</p>
                                    {payment.vendor?.company_name && <p>{payment.vendor?.company_name}</p>}
                                    <p>{payment.vendor?.address}</p>
                                    <p>{payment.vendor?.email}</p>
                                    <p>{payment.vendor?.mobile}</p>
                                </div>
                            </div>

                            {/* Invoice Items */}
                            <div className="mb-8">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Bill Number</TableHead>
                                            <TableHead>Supplier</TableHead>
                                            <TableHead>Bill Date</TableHead>
                                            <TableHead className="text-right">Grand Total</TableHead>
                                            <TableHead className="text-right">Amount Paid</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {payment.purchases.map((invoice, index) => (
                                            <TableRow key={index}>
                                                <TableCell className="font-medium">{invoice.bill_no}</TableCell>
                                                <TableCell>{payment.vendor.name}</TableCell>
                                                <TableCell>{invoice.purchase_date}</TableCell>
                                                <TableCell className="text-right">{formatCurrency(invoice.grand_total, payment.business.currency, decimalPlace)}</TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency(invoice.paid, payment.business.currency, decimalPlace)}
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
                                        <span className="font-medium">Total Paid:</span>
                                        <span>{formatCurrency(payment.amount, payment.business.currency, decimalPlace)}</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </SidebarInset>

            {/* Print Styles */}
            <style jsx global>{`
        @media print {
          body * {
            visibility: hidden;
          }
          .print-container,
          .print-container * {
            visibility: visible;
          }
          .print-container {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
          }

          /* Hide action buttons when printing */
          button,
          .dropdown,
          .flex.space-x-2 {
            display: none !important;
          }
        }
      `}</style>
        </AuthenticatedLayout>
    );
}

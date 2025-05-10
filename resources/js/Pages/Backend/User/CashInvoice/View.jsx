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

export default function View({ receipt, attachments, decimalPlace }) {
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
            pdf.save(`Cash_Invoice_${receipt.receipt_number}.pdf`);
        } catch (error) {
            console.error('Error generating PDF:', error);
            toast.error('Failed to generate PDF. Please try again.');
        } finally {
            setIsLoading(prev => ({ ...prev, pdf: false }));
        }
    };

    const handleShareLink = () => {
        router.visit(route('receipts.link', receipt.id), {
            preserveScroll: true
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Cash Invoice #${receipt.receipt_number}`} />

            <SidebarInset>
                <div className="space-y-4">
                    <PageHeader
                        page="Cash Invoices"
                        subpage={`Cash Invoice #${receipt.receipt_number}`}
                        url="receipts.index"
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
                                    <Link href={route("receipts.edit", receipt.id)} className="flex items-center">
                                        <Edit className="mr-2 h-4 w-4" />
                                        <span>Edit Cash Invoice</span>
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
                                    {receipt.business.logo && (
                                        <div className="mb-3">
                                            <img 
                                                src={`/uploads/media/${receipt.business.logo}`} 
                                                alt="Business Logo" 
                                                className="max-h-16 object-contain"
                                            />
                                        </div>
                                    )}
                                    <h2 className="text-2xl font-bold text-primary">
                                        {receipt.business.business_name}
                                    </h2>
                                    <div className="mt-2 text-sm">
                                        <p>{receipt.business.address}</p>
                                        <p>{receipt.business.email}</p>
                                        <p>{receipt.business.phone}</p>
                                    </div>
                                </div>
                                <div className="md:text-right">
                                    <h1 className="text-2xl font-bold">{receipt.title}</h1>
                                    <div className="mt-2 text-sm">
                                        <p><span className="font-medium">Invoice #:</span> {receipt.receipt_number}</p>
                                        <p><span className="font-medium">Invoice Date:</span> {receipt.receipt_date}</p>
                                        {receipt.order_number && (
                                            <p><span className="font-medium">Order Number:</span> {receipt.order_number}</p>
                                        )}
                                    </div>
                                </div>
                            </div>

                            <SidebarSeparator className="my-6" />

                            {/* Customer Information */}
                            <div className="mb-8">
                                <h3 className="font-medium text-lg mb-2">Bill To:</h3>
                                <div className="text-sm">
                                    <p className="font-medium">{receipt.customer?.name}</p>
                                    {receipt.customer?.company_name && <p>{receipt.customer?.company_name}</p>}
                                    <p>{receipt.customer?.address}</p>
                                    <p>{receipt.customer?.email}</p>
                                    <p>{receipt.customer?.mobile}</p>
                                </div>
                            </div>

                            {/* Invoice Items */}
                            <div className="mb-8">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Item</TableHead>
                                            <TableHead>Description</TableHead>
                                            <TableHead className="text-right">Quantity</TableHead>
                                            <TableHead className="text-right">Unit Cost</TableHead>
                                            <TableHead className="text-right">Total</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {receipt.items.map((item, index) => (
                                            <TableRow key={index}>
                                                <TableCell className="font-medium">{item.product_name}</TableCell>
                                                <TableCell>{item.description}</TableCell>
                                                <TableCell className="text-right">{item.quantity}</TableCell>
                                                <TableCell className="text-right">{formatCurrency(item.unit_cost, receipt.currency, decimalPlace)}</TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency(item.quantity * item.unit_cost, receipt.currency, decimalPlace)}
                                                </TableCell>
                                            </TableRow>
                                        ))}
                                    </TableBody>
                                </Table>
                            </div>

                            {/* Cash Invoice Summary */}
                            <div className="flex justify-end">
                                <div className="w-full md:w-1/2 lg:w-1/3 space-y-2">
                                    <div className="flex justify-between py-2 border-t">
                                        <span className="font-medium">Subtotal:</span>
                                        <span>{formatCurrency(receipt.sub_total, receipt.business.currency, decimalPlace)}</span>
                                    </div>

                                    {/* Tax details */}
                                    {receipt.taxes.map((tax, index) => (
                                        <div key={index} className="flex justify-between py-2">
                                            <span>
                                                {tax.name}:
                                            </span>
                                            <span>{formatCurrency(tax.amount, receipt.currency, decimalPlace)}</span>
                                        </div>
                                    ))}

                                    {/* Discount */}
                                    {receipt.discount > 0 && (
                                        <div className="flex justify-between py-2">
                                            <span>
                                                Discount
                                            </span>
                                            <span>-{formatCurrency(receipt.discount, receipt.currency, decimalPlace)}</span>
                                        </div>
                                    )}

                                    {/* Total */}
                                    <div className="flex justify-between py-3 border-t border-b font-bold text-lg">
                                        <span>Total:</span>
                                        <span>{formatCurrency(receipt.grand_total, receipt.currency, decimalPlace)}</span>
                                    </div>

                                    {/* Base currency equivalent if different currency */}
                                    {receipt.currency !== receipt.business.currency && (
                                        <div className="flex justify-between py-2 text-gray-500 text-sm">
                                            <span>Exchange Rate:</span>
                                            <span>
                                                1 {receipt.business.currency} = {formatCurrency(receipt.exchange_rate, receipt.currency, decimalPlace)}
                                            </span>
                                        </div>
                                    )}
                                    
                                    {/* Base currency equivalent total */}
                                    {receipt.currency !== receipt.business.currency && (
                                        <div className="flex justify-between py-2 text-sm text-gray-600">
                                            <span>Equivalent to:</span>
                                            <span>
                                                {formatCurrency(
                                                    receipt.converted_total,
                                                    receipt.currency,
                                                    decimalPlace
                                                )}
                                            </span>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Notes & Terms */}
                            {(receipt.note || receipt.footer) && (
                                <div className="mt-8 space-y-4">
                                    {receipt.note && (
                                        <div>
                                            <h3 className="font-medium mb-1">Notes:</h3>
                                            <p className="text-sm">{receipt.note}</p>
                                        </div>
                                    )}

                                    {receipt.footer && (
                                        <div>
                                            <h3 className="font-medium mb-1">Terms & Conditions:</h3>
                                            <p className="text-sm">{receipt.footer}</p>
                                        </div>
                                    )}
                                </div>
                            )}

                            {/* Attachments */}
                            {attachments && attachments.length > 0 && (
                                <div className="mt-8">
                                    <h3 className="font-medium mb-2">Attachments:</h3>
                                    <ul className="list-disc pl-5">
                                        {attachments.map((attachment, index) => (
                                            <li key={index}>
                                                <a
                                                    href={`/storage/app/${attachment.file_path}`}
                                                    target="_blank"
                                                    className="text-blue-600 hover:underline"
                                                >
                                                    {attachment.file_name}
                                                </a>
                                            </li>
                                        ))}
                                    </ul>
                                </div>
                            )}
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

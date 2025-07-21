import { Head, Link, router, usePage } from "@inertiajs/react";
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
    DownloadIcon,
    MoreVertical,
    ShareIcon,
    Edit,
    Facebook,
    MessageCircle,
    Copy,
    PaperclipIcon
} from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import { Table, TableBody, TableHeader, TableRow, TableHead, TableCell } from "@/Components/ui/table";
import { QRCodeSVG } from 'qrcode.react';
import Modal from "@/Components/Modal";
import { Input } from "@/Components/ui/input";
import { Badge } from "@/Components/ui/badge";

const printStyles = `
  @media print {
        body * {
            visibility: hidden;
        }

        #printable-area, #printable-area * {
            visibility: visible;
        }

        #printable-area {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            margin: 0;
            padding: 0;
            border: none;
            height: 100%;
        }

        .group.peer.hidden.text-sidebar-foreground {
            display: none !important;
        }

        @page {
            size: auto;
            margin: 10mm;
        }

        body {
            margin: 0;
            padding: 0;
        }
    }
`;

export default function View({ bill, attachments, decimalPlace }) {
    const [isLoading, setIsLoading] = useState({
        print: false,
        pdf: false
    });
    const [isShareModalOpen, setIsShareModalOpen] = useState(false);
    const [isAttachmentsModalOpen, setIsAttachmentsModalOpen] = useState(false);
    const [shareLink, setShareLink] = useState('');

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
            const content = document.querySelector('#printable-area');
            
            // Create a canvas from the content
            const canvas = await html2canvas(content, {
                scale: 2, // Higher scale for better quality
                useCORS: true, // Enable CORS for images
                logging: false,
                windowWidth: content.scrollWidth,
                windowHeight: content.scrollHeight
            });

            // Calculate dimensions
            const imgWidth = 210; // A4 width in mm
            const pageHeight = 297; // A4 height in mm
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            
            // Create PDF
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
            pdf.save(`Cash_Purchase_${bill.bill_no}.pdf`);
        } catch (error) {
            console.error('Error generating PDF:', error);
            toast.error('Failed to generate PDF. Please try again.');
        } finally {
            setIsLoading(prev => ({ ...prev, pdf: false }));
        }
    };

    const handleShareLink = () => {
        const link = route('cash_purchases.show_public_cash_purchase', bill.short_code);
        setShareLink(link);
        setIsShareModalOpen(true);
    };

    const handleCopyLink = () => {
        navigator.clipboard.writeText(shareLink);
        toast.success("Link copied to clipboard");
    };

    const handleWhatsAppShare = () => {
        const text = `Check out this cash purchase: ${shareLink}`;
        window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
    };

    const handleFacebookShare = () => {
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareLink)}`, '_blank');
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <style dangerouslySetInnerHTML={{ __html: printStyles }} />
                <div className="space-y-4">
                    <PageHeader
                        page="Cash Purchases"
                        subpage={`Cash Purchase #${bill.bill_no}`}
                        url="cash_purchases.index"
                    />

                    <div className="flex items-center justify-end space-x-2 mb-4">
                        {attachments && attachments.length > 0 && (
                            <Button
                                variant="outline"
                                onClick={() => setIsAttachmentsModalOpen(true)}
                                className="flex items-center"
                            >
                                <PaperclipIcon className="mr-2 h-4 w-4" />
                                Attachments ({attachments.length})
                            </Button>
                        )}
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
                                    <Link href={route("cash_purchases.edit", bill.id)} className="flex items-center">
                                        <Edit className="mr-2 h-4 w-4" />
                                        <span>Edit Cash Purchase</span>
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    <div id="printable-area" className="lg:w-[210mm] min-h-[297mm] mx-auto rounded-md border p-4">
                        <div className="p-6 sm:p-8">
                            {/* Invoice Header */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
                                <div>
                                    {bill.business.logo && (
                                        <div className="mb-3">
                                            <img
                                                src={`/uploads/media/${bill.business.logo}`}
                                                alt="Business Logo"
                                                className="max-h-32 object-contain"
                                            />
                                        </div>
                                    )}
                                    <h2 className="text-2xl font-bold text-primary">
                                        {bill.business.business_name}
                                    </h2>
                                    <div className="mt-2 text-sm">
                                        <p>{bill.business.address}</p>
                                        <p>{bill.business.business_email}</p>
                                        <p>{bill.business.phone}</p>
                                    </div>
                                </div>
                                <div className="sm:text-right">
                                    <h1 className="text-2xl font-bold">{bill.title}</h1>
                                    <div className="mt-2 text-sm">
                                        <p><span className="font-medium">Cash Purchase #:</span> {bill.bill_no}</p>
                                        <p><span className="font-medium">Cash Purchase Date:</span> {bill.purchase_date}</p>
                                        <p><span className="font-medium">Due Date:</span> {bill.due_date}</p>
                                        {bill.order_number && (
                                            <p><span className="font-medium">Order Number:</span> {bill.order_number}</p>
                                        )}
                                        {bill.paid > 0 && (
                                            <Badge variant="outline" className="mt-2 text-green-600">
                                                Paid: {formatCurrency({ amount: bill.paid, currency: bill.currency })}
                                            </Badge>
                                        )}
                                    </div>
                                    <div className="mt-4 sm:flex sm:justify-end">
                                        <QRCodeSVG 
                                            value={route('cash_purchases.show_public_cash_purchase', bill.short_code)}
                                            size={100}
                                            level="H"
                                            includeMargin={true}
                                            className="print:block"
                                        />
                                    </div>
                                </div>
                            </div>

                            <SidebarSeparator className="my-6" />

                            {/* Customer Information */}
                            <div className="mb-8">
                                <h3 className="font-medium text-lg mb-2">Bill To:</h3>
                                <div className="text-sm">
                                    <p className="font-medium">{bill.vendor?.name}</p>
                                    {bill.vendor?.company_name && <p>{bill.vendor?.company_name}</p>}
                                    <p>{bill.vendor?.address}</p>
                                    <p>{bill.vendor?.email}</p>
                                    <p>{bill.vendor?.mobile}</p>
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
                                        {bill.items.map((item, index) => (
                                            <TableRow key={index}>
                                                <TableCell className="font-medium">{item.product_name}</TableCell>
                                                <TableCell>{item.description}</TableCell>
                                                <TableCell className="text-right">{item.quantity}</TableCell>
                                                <TableCell className="text-right">{formatCurrency(item.unit_cost, bill.currency, decimalPlace)}</TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency(item.quantity * item.unit_cost, bill.currency, decimalPlace)}
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
                                        <span className="font-medium">Subtotal:</span>
                                        <span>{formatCurrency(bill.sub_total, bill.business.currency, decimalPlace)}</span>
                                    </div>

                                    {/* Tax details */}
                                    {bill.taxes.map((tax, index) => (
                                        <div key={index} className="flex justify-between py-2">
                                            <span>
                                                {tax.name}:
                                            </span>
                                            <span>{formatCurrency(tax.amount, bill.business.currency, decimalPlace)}</span>
                                        </div>
                                    ))}

                                    {/* Discount */}
                                    {bill.discount > 0 && (
                                        <div className="flex justify-between py-2">
                                            <span>
                                                Discount
                                            </span>
                                            <span>-{formatCurrency(bill.discount, bill.business.currency, decimalPlace)}</span>
                                        </div>
                                    )}

                                    {/* Total */}
                                    <div className="flex justify-between py-3 border-t border-b font-bold text-lg">
                                        <span>Total:</span>
                                        <span>{formatCurrency(bill.grand_total, bill.business.currency, decimalPlace)}</span>
                                    </div>

                                    {/* Base currency equivalent if different currency */}
                                    {bill.currency !== bill.business.currency && (
                                        <div className="flex justify-between py-2 text-gray-500 text-sm">
                                            <span>Exchange Rate:</span>
                                            <span>
                                                1 {bill.business.currency} = {formatCurrency(bill.exchange_rate, bill.currency, decimalPlace)}
                                            </span>
                                        </div>
                                    )}

                                    {/* Base currency equivalent total */}
                                    {bill.currency !== bill.business.currency && (
                                        <div className="flex justify-between py-2 text-sm text-gray-600">
                                            <span>Equivalent to:</span>
                                            <span>
                                                {formatCurrency(
                                                    bill.converted_total,
                                                    bill.currency,
                                                    decimalPlace
                                                )}
                                            </span>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Notes & Terms */}
                            {(bill.note || bill.footer) && (
                                <div className="mt-8 space-y-4">
                                    {bill.note && (
                                        <div>
                                            <h3 className="font-medium mb-1">Notes:</h3>
                                            <p className="text-sm">{bill.note}</p>
                                        </div>
                                    )}

                                    {bill.footer && (
                                        <div>
                                            <h3 className="font-medium mb-1">Terms & Conditions:</h3>
                                            <p className="text-sm">{bill.footer}</p>
                                        </div>
                                    )}
                                </div>
                            )}
                        </div>
                    </div>
                </div>
            </SidebarInset>

            {/* Attachments Modal */}
            <Modal
                show={isAttachmentsModalOpen}
                onClose={() => setIsAttachmentsModalOpen(false)}
                maxWidth="4xl"
            >
                <div className="mb-6">
                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Cash Purchase Attachments
                    </h2>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        View and download files attached to this cash purchase
                    </p>
                </div>

                <div className="overflow-hidden border rounded-md">
                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>File Name</TableHead>
                                <TableHead>Actions</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {attachments.map((attachment, index) => (
                                <TableRow key={index}>
                                    <TableCell className="font-medium">
                                        {attachment.file_name}
                                    </TableCell>
                                    <TableCell>
                                        <a
                                            href={`${attachment.path}`}
                                            target="_blank"
                                            className="text-blue-600 hover:text-blue-800 hover:underline flex items-center"
                                            download
                                        >
                                            <DownloadIcon className="h-4 w-4 mr-1" />
                                            Download
                                        </a>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>

                <div className="mt-6 flex justify-end">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => setIsAttachmentsModalOpen(false)}
                    >
                        Close
                    </Button>
                </div>
            </Modal>

            {/* Share Modal */}
            <Modal
                show={isShareModalOpen}
                onClose={() => setIsShareModalOpen(false)}
                maxWidth="2xl"
            >
                <div className="mb-6">
                    <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                        Share Cash Purchase
                    </h2>
                </div>

                <div className="space-y-4">
                    <div className="flex items-center space-x-2">
                        <Input
                            value={shareLink}
                            readOnly
                            className="flex-1"
                        />
                        <Button
                            variant="outline"
                            onClick={handleCopyLink}
                            className="flex items-center"
                        >
                            <Copy className="mr-2 h-4 w-4" />
                            Copy
                        </Button>
                    </div>

                    <div className="flex justify-center space-x-4 pt-4">
                        <Button
                            variant="outline"
                            onClick={handleWhatsAppShare}
                            className="flex items-center"
                        >
                            <MessageCircle className="mr-2 h-4 w-4" />
                            WhatsApp
                        </Button>
                        <Button
                            variant="outline"
                            onClick={handleFacebookShare}
                            className="flex items-center"
                        >
                            <Facebook className="mr-2 h-4 w-4" />
                            Facebook
                        </Button>
                    </div>
                </div>

                <div className="mt-6 flex justify-end">
                    <Button
                        type="button"
                        variant="outline"
                        onClick={() => setIsShareModalOpen(false)}
                    >
                        Close
                    </Button>
                </div>
            </Modal>
        </AuthenticatedLayout>
    );
}

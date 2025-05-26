import { Link, router } from "@inertiajs/react";
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
    Edit,
    Facebook,
    MessageCircle,
    Copy
} from "lucide-react";
import { useState } from "react";
import { toast } from "sonner";
import { Table, TableBody, TableHeader, TableRow, TableHead, TableCell } from "@/Components/ui/table";
import { QRCodeSVG } from 'qrcode.react';
import Modal from "@/Components/Modal";
import { Input } from "@/Components/ui/input";

export default function View({ purchase_return, attachments, decimalPlace }) {
    const [isLoading, setIsLoading] = useState({
        print: false,
        pdf: false
    });
    const [isShareModalOpen, setIsShareModalOpen] = useState(false);
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
            pdf.save(`Purchase_Return_${purchase_return.return_number}.pdf`);
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
        const link = route('purchase_returns.show_public_purchase_return', purchase_return.short_code);
        setShareLink(link);
        setIsShareModalOpen(true);
    };

    const handleCopyLink = () => {
        navigator.clipboard.writeText(shareLink);
        toast({
            title: "Success",
            description: "Link copied to clipboard",
        });
    };

    const handleWhatsAppShare = () => {
        const text = `Check out this purchase return: ${shareLink}`;
        window.open(`https://wa.me/?text=${encodeURIComponent(text)}`, '_blank');
    };

    const handleFacebookShare = () => {
        window.open(`https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(shareLink)}`, '_blank');
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <div className="space-y-4">
                    <PageHeader
                        page="Purchase Returns"
                        subpage={`Purchase Return #${purchase_return.return_number}`}
                        url="purchase_returns.index"
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
                                    <Link href={route("purchase_returns.edit", purchase_return.id)} className="flex items-center">
                                        <Edit className="mr-2 h-4 w-4" />
                                        <span>Edit Purchase Return</span>
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    {/* Share Modal */}
                    <Modal
                        show={isShareModalOpen}
                        onClose={() => setIsShareModalOpen(false)}
                        maxWidth="2xl"
                    >
                        <div className="mb-6">
                            <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Share Purchase Return
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

                    <div className="print-container">
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
                                    <h1 className="text-2xl font-bold">{purchase_return.title}</h1>
                                    <div className="mt-2 text-sm">
                                        <p><span className="font-medium">Purchase Return #:</span> {purchase_return.return_number}</p>
                                        <p><span className="font-medium">Purchase Return Date:</span> {purchase_return.return_date}</p>
                                    </div>
                                    <div className="mt-4 md:flex md:justify-end">
                                        <QRCodeSVG 
                                            value={route('purchase_returns.show_public_purchase_return', purchase_return.short_code)}
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
                                <h3 className="font-medium text-lg mb-2">Returned To:</h3>
                                <div className="text-sm">
                                    <p className="font-medium">{purchase_return.vendor?.name}</p>
                                    {purchase_return.vendor?.company_name && <p>{purchase_return.vendor?.company_name}</p>}
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
                                            <TableHead className="text-right">Quantity</TableHead>
                                            <TableHead className="text-right">Unit Cost</TableHead>
                                            <TableHead className="text-right">Total</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {purchase_return.items.map((item, index) => (
                                            <TableRow key={index}>
                                                <TableCell className="font-medium">{item.product_name}</TableCell>
                                                <TableCell>{item.description}</TableCell>
                                                <TableCell className="text-right">{item.quantity}</TableCell>
                                                <TableCell className="text-right">{formatCurrency(item.unit_cost, purchase_return.currency, decimalPlace)}</TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency({ amount: item.quantity * item.unit_cost, currency: purchase_return.currency })}
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
                                        <span>{formatCurrency({ amount: purchase_return.sub_total, currency: purchase_return.currency })}</span>
                                    </div>

                                    {/* Tax details */}
                                    {purchase_return.taxes.map((tax, index) => (
                                        <div key={index} className="flex justify-between py-2">
                                            <span>
                                                {tax.name}:
                                            </span>
                                            <span>{formatCurrency({ amount: tax.amount, currency: purchase_return.currency })}</span>
                                        </div>
                                    ))}

                                    {/* Discount */}
                                    {purchase_return.discount > 0 && (
                                        <div className="flex justify-between py-2">
                                            <span>
                                                Discount
                                            </span>
                                            <span>-{formatCurrency({ amount: purchase_return.discount, currency: purchase_return.currency })}</span>
                                        </div>
                                    )}

                                    {/* Total */}
                                    <div className="flex justify-between py-3 border-t border-b font-bold text-lg">
                                        <span>Total:</span>
                                        <span>{formatCurrency({ amount: purchase_return.grand_total, currency: purchase_return.currency })}</span>
                                    </div>

                                    {/* Base currency equivalent if different currency */}
                                    {purchase_return.currency !== purchase_return.business.currency && (
                                        <div className="flex justify-between py-2 text-gray-500 text-sm">
                                            <span>Exchange Rate:</span>
                                            <span>
                                                1 {purchase_return.business.currency} = {formatCurrency(purchase_return.exchange_rate, purchase_return.currency)}
                                            </span>
                                        </div>
                                    )}

                                    {/* Base currency equivalent total */}
                                    {purchase_return.currency !== purchase_return.business.currency && (
                                        <div className="flex justify-between py-2 text-sm text-gray-600">
                                            <span>Equivalent to:</span>
                                            <span>
                                                {formatCurrency(
                                                    purchase_return.converted_total,
                                                    purchase_return.currency,
                                                    decimalPlace
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
                                            <h3 className="font-medium mb-1">Notes:</h3>
                                            <p className="text-sm">{purchase_return.note}</p>
                                        </div>
                                    )}

                                    {purchase_return.footer && (
                                        <div>
                                            <h3 className="font-medium mb-1">Terms & Conditions:</h3>
                                            <p className="text-sm">{purchase_return.footer}</p>
                                        </div>
                                    )}
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

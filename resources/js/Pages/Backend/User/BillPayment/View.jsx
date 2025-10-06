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

export default function View({ payment, decimalPlace, attachments }) {
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

    const handleDownloadPDF = () => {
        window.open(route('bill_payments.pdf', payment.id), '_blank');
    };

    const handleShareLink = () => {
        const link = route('bill_payments.show_public_bill_payment', payment.id);
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
        const text = `Check out this bill payment: ${shareLink}`;
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
                        page="Bill Payments"
                        subpage={`Payment #${payment.id}`}
                        url="bill_payments.index"
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
                            className="flex items-center"
                        >
                            <DownloadIcon className="mr-2 h-4 w-4" />
                            Download PDF
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

                    {/* Attachments Modal */}
                    <Modal
                        show={isAttachmentsModalOpen}
                        onClose={() => setIsAttachmentsModalOpen(false)}
                        maxWidth="4xl"
                    >
                        <div className="mb-6">
                            <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Bill Payment Attachments
                            </h2>
                            <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                                View and download files attached to this bill payment
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
                                Share Bill Payment
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

                    <div id="printable-area" className="lg:w-[210mm] min-h-[297mm] mx-auto rounded-md border p-4">
                        <div className="p-6 sm:p-8">
                            {/* Invoice Header */}
                            <div className="grid grid-cols-1 sm:grid-cols-2 gap-6 mb-8">
                                <div>
                                    {payment.business.logo && (
                                        <div className="mb-3">
                                            <img
                                                src={`/uploads/media/${payment.business.logo}`}
                                                alt="Business Logo"
                                                className="max-h-32 object-contain"
                                            />
                                        </div>
                                    )}
                                    <h2 className="text-2xl font-bold text-primary">
                                        {payment.business.business_name}
                                    </h2>
                                    <div className="mt-2 text-sm">
                                        <p>{payment.business.address}</p>
                                        <p>{payment.business.business_email}</p>
                                        <p>{payment.business.phone}</p>
                                    </div>
                                </div>
                                <div className="sm:text-right">
                                    <h1 className="text-2xl font-bold">Payment Voucher</h1>
                                    <div className="mt-2 text-sm">
                                        <p><span className="font-medium">Payment #:</span> {payment.id}</p>
                                        <p><span className="font-medium">Payment Date:</span> {payment.date}</p>
                                        <p><span className="font-medium">Payment Method:</span> {payment.method}</p>
                                        <p><span className="font-medium">Payment Type:</span> {payment.type}</p>
                                    </div>
                                    <div className="mt-4 sm:flex sm:justify-end">
                                        <QRCodeSVG 
                                            value={route('bill_payments.show_public_bill_payment', payment.id)}
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
        </AuthenticatedLayout>
    );
}

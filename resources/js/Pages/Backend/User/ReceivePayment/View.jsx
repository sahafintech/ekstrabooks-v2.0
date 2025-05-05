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
        email: false,
        pdf: false
    });

    const handlePrint = () => {
        setIsLoading(prev => ({ ...prev, print: true }));
        setTimeout(() => {
            window.print();
            setIsLoading(prev => ({ ...prev, print: false }));
        }, 300);
    };

    const handleEmailInvoice = () => {
        setIsLoading(prev => ({ ...prev, email: true }));
        router.visit(route('invoices.send_email', invoice.id), {
            preserveScroll: true,
            onSuccess: () => {
                toast.success('Email form opened successfully');
                setIsLoading(prev => ({ ...prev, email: false }));
            },
            onError: () => {
                toast.error('Failed to open email form');
                setIsLoading(prev => ({ ...prev, email: false }));
            }
        });
    };

    const handleDownloadPDF = () => {
        setIsLoading(prev => ({ ...prev, pdf: true }));
        window.open(route('invoices.pdf', invoice.id), '_blank');
        setTimeout(() => {
            setIsLoading(prev => ({ ...prev, pdf: false }));
        }, 1000);
    };

    const handleShareLink = () => {
        router.visit(route('invoices.link', invoice.id), {
            preserveScroll: true
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Payment #${payment.id}`} />

            <SidebarInset>
                <div className="space-y-4">
                    <PageHeader
                        page="Payments"
                        subpage={`Payment #${payment.id}`}
                        url="receive_payments.index"
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
                            onClick={handleEmailInvoice}
                            disabled={isLoading.email}
                            className="flex items-center"
                        >
                            <MailIcon className="mr-2 h-4 w-4" />
                            {isLoading.email ? "Sending..." : "Email"}
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
                                    <Link href={route("receive_payments.edit", payment.id)} className="flex items-center">
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
                                <h3 className="font-medium text-lg mb-2">Received From:</h3>
                                <div className="text-sm">
                                    <p className="font-medium">{payment.customer?.name}</p>
                                    {payment.customer?.company_name && <p>{payment.customer?.company_name}</p>}
                                    <p>{payment.customer?.address}</p>
                                    <p>{payment.customer?.email}</p>
                                    <p>{payment.customer?.mobile}</p>
                                </div>
                            </div>

                            {/* Invoice Items */}
                            <div className="mb-8">
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Invoice Number</TableHead>
                                            <TableHead>Customer</TableHead>
                                            <TableHead>Invoice Date</TableHead>
                                            <TableHead className="text-right">Grand Total</TableHead>
                                            <TableHead className="text-right">Amount Paid</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {payment.invoices.map((invoice, index) => (
                                            <TableRow key={index}>
                                                <TableCell className="font-medium">{invoice.invoice_number}</TableCell>
                                                <TableCell>{payment.customer.name}</TableCell>
                                                <TableCell>{invoice.invoice_date}</TableCell>
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

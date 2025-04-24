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

export default function View({ quotation, decimalPlace }) {
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
        router.visit(route('quotations.send_email', quotation.id), {
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
        window.open(route('quotations.pdf', quotation.id), '_blank');
        setTimeout(() => {
            setIsLoading(prev => ({ ...prev, pdf: false }));
        }, 1000);
    };

    const handleShareLink = () => {
        router.visit(route('quotations.link', quotation.id), {
            preserveScroll: true
        });
    };

    return (
        <AuthenticatedLayout>
            <Head title={`Quotation #${quotation.id}`} />

            <SidebarInset>
                <div className="space-y-4">
                    <PageHeader
                        page="Quotations"
                        subpage={`Quotation #${quotation.id}`}
                        url="quotations.index"
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
                                    <Link href={route("quotations.edit", quotation.id)} className="flex items-center">
                                        <Edit className="mr-2 h-4 w-4" />
                                        <span>Edit Quotation</span>
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
                                    <h2 className="text-2xl font-bold text-primary">
                                        {quotation.business.business_name}
                                    </h2>
                                    <div className="mt-2 text-sm">
                                        <p>{quotation.business.address}</p>
                                        <p>{quotation.business.email}</p>
                                        <p>{quotation.business.phone}</p>
                                    </div>
                                </div>
                                <div className="md:text-right">
                                    <h1 className="text-2xl font-bold">{quotation.title}</h1>
                                    <div className="mt-2 text-sm">
                                        <p><span className="font-medium">Quotation #:</span> {quotation.quotation_number}</p>
                                        <p><span className="font-medium">Quotation Date:</span> {quotation.quotation_date}</p>
                                        {quotation.order_number && (
                                            <p><span className="font-medium">Order Number:</span> {quotation.order_number}</p>
                                        )}
                                    </div>
                                </div>
                            </div>

                            <SidebarSeparator className="my-6" />

                            {/* Customer Information */}
                            <div className="mb-8">
                                <h3 className="font-medium text-lg mb-2">Quotation To:</h3>
                                <div className="text-sm">
                                    <p className="font-medium">{quotation.customer?.name}</p>
                                    {quotation.customer?.company_name && <p>{quotation.customer?.company_name}</p>}
                                    <p>{quotation.customer?.address}</p>
                                    <p>{quotation.customer?.email}</p>
                                    <p>{quotation.customer?.mobile}</p>
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
                                        {quotation.items.map((item, index) => (
                                            <TableRow key={index}>
                                                <TableCell className="font-medium">{item.product_name}</TableCell>
                                                <TableCell>{item.description}</TableCell>
                                                <TableCell className="text-right">{item.quantity}</TableCell>
                                                <TableCell className="text-right">{formatCurrency(item.unit_cost, quotation.currency, decimalPlace)}</TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency({ amount: item.quantity * item.unit_cost, currency: quotation.currency })}
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
                                        <span>{formatCurrency({ amount: quotation.sub_total, currency: quotation.currency })}</span>
                                    </div>

                                    {/* Tax details */}
                                    {quotation.taxes.map((tax, index) => (
                                        <div key={index} className="flex justify-between py-2">
                                            <span>
                                                {tax.name}:
                                            </span>
                                            <span>{formatCurrency({ amount: tax.amount, currency: quotation.currency })}</span>
                                        </div>
                                    ))}

                                    {/* Discount */}
                                    {quotation.discount > 0 && (
                                        <div className="flex justify-between py-2">
                                            <span>
                                                Discount
                                            </span>
                                            <span>-{formatCurrency({ amount: quotation.discount, currency: quotation.currency })}</span>
                                        </div>
                                    )}

                                    {/* Total */}
                                    <div className="flex justify-between py-3 border-t border-b font-bold text-lg">
                                        <span>Total:</span>
                                        <span>{formatCurrency({ amount: quotation.grand_total, currency: quotation.currency })}</span>
                                    </div>

                                    {/* Base currency equivalent if different currency */}
                                    {quotation.currency !== quotation.business.currency && (
                                        <div className="flex justify-between py-2 text-gray-500 text-sm">
                                            <span>Exchange Rate:</span>
                                            <span>
                                                1 {quotation.business.currency} = {formatCurrency(quotation.exchange_rate, quotation.currency, decimalPlace)}
                                            </span>
                                        </div>
                                    )}
                                    
                                    {/* Base currency equivalent total */}
                                    {quotation.currency !== quotation.business.currency && (
                                        <div className="flex justify-between py-2 text-sm text-gray-600">
                                            <span>Equivalent to:</span>
                                            <span>
                                                {formatCurrency(
                                                    quotation.converted_total,
                                                    quotation.currency,
                                                    decimalPlace
                                                )}
                                            </span>
                                        </div>
                                    )}
                                </div>
                            </div>

                            {/* Notes & Terms */}
                            {(quotation.note || quotation.footer) && (
                                <div className="mt-8 space-y-4">
                                    {quotation.note && (
                                        <div>
                                            <h3 className="font-medium mb-1">Notes:</h3>
                                            <p className="text-sm">{quotation.note}</p>
                                        </div>
                                    )}

                                    {quotation.footer && (
                                        <div>
                                            <h3 className="font-medium mb-1">Terms & Conditions:</h3>
                                            <p className="text-sm">{quotation.footer}</p>
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

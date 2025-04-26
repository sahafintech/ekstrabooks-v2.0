import React from "react";
import { Link } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
import { formatCurrency } from "@/lib/utils";

export default function InvoicePos({ business, receipt }) {
    const handlePrint = () => {
        // Get the content of the invoice
        const invoiceContent = document.getElementById('invoice-pos').innerHTML;
        
        // Create a new window for printing
        const printWindow = window.open('', '_blank', 'width=800,height=600');

        const style = `
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
                th, td { padding: 5px; text-align: left; }
                .!text-\[11px\] { font-size: 11px; }
                .text-\[11px\] { font-size: 11px; }
                .text-2xl { font-size: 20px; }
                .text-7xl { font-size: 64px; }
                .leading-4 { line-height: 1rem; }
                .mt-3 { margin-top: 12px; }
                .font-semibold { font-weight: 600; }
                .uppercase { text-transform: uppercase; }
                .underline { text-decoration: underline; }
                .relative { position: relative; }
                .absolute { position: absolute; }
                .right-0 { right: 0; }
                .top-0 { top: 0; }
                .text-right { text-align: right; }
                .whitespace-nowrap { white-space: nowrap; }
                @media print {
                    body { margin: 0; padding: 10px; }
                    .no-print { display: none; }
                }
            </style>
        `;

        const content = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Invoice</title>
                ${style}
            </head>
            <body>
                <div id="invoice-pos">${invoiceContent}</div>
                <script>
                    window.onload = function() {
                        setTimeout(function() {
                            window.print();
                            window.onafterprint = function() {
                                window.close();
                            };
                        }, 500);
                    };
                </script>
            </body>
            </html>
        `;

        printWindow.document.write(content);
        printWindow.document.close();
    };
    return (
        <div className="flex items-center justify-center max-w-lg mx-auto bg-white p-3">
            <div>
                {/* action buttons */}
                <div className="mb-2 grid grid-cols-12 gap-x-2">
                    <Button
                        variant="secondary"
                        className="col-span-6 print"
                        onClick={handlePrint}
                    >
                        Print
                    </Button>
                    <Link href={route('receipts.pos')}>
                        <Button variant="default" className="col-span-6">
                            Back
                        </Button>
                    </Link>
                </div>

                {/* printable invoice */}
                <div id="invoice-pos">
                    <div className="text-[11px] leading-4">
                        <div className="relative">
                            <h2 className="font-semibold text-2xl uppercase underline">
                                {business.name}
                            </h2>
                            {business.package?.medical_record === 1 &&
                                business.package?.prescription === 1 &&
                                receipt.queue_number > 0 && (
                                    <h2 className="text-7xl absolute right-0 top-0">
                                        {receipt.queue_number}
                                    </h2>
                                )}
                        </div>

                        <p>
                            <span>Date: {receipt.receipt_date}<br /></span>
                            <span>Invoice Number: {receipt.receipt_number}<br /></span>
                            <span>Address: {business.address}<br /></span>
                            <span>Email: {business.email}<br /></span>
                            <span>Phone: {business.phone}<br /></span>
                            <span>Customer: {receipt.customer?.name}<br /></span>
                        </p>
                    </div>

                    <table className="ti-custom-table ti-custom-table-head whitespace-nowrap mt-3">
                        <tbody>
                            {receipt.items.map((item, i) => (
                                <tr key={i}>
                                    <td colSpan={3} className="!text-[11px]">
                                        {item.product_name}
                                        <br />
                                        <span>
                                            {item.quantity} ×{" "}
                                            {formatCurrency({
                                                amount: item.unit_cost,
                                                currency: business.currency,
                                            })}
                                        </span>
                                    </td>
                                    <td className="text-right !text-[11px]">
                                        <b>
                                            {formatCurrency({
                                                amount: item.sub_total,
                                                currency: business.currency,
                                            })}
                                        </b>
                                    </td>
                                </tr>
                            ))}

                            {receipt.taxes.map((tax, i) => (
                                <tr key={`tax-${i}`} className="mt-10">
                                    <td colSpan={3} className="!text-[11px]">
                                        <b>{tax.name}</b>
                                    </td>
                                    <td className="text-right !text-[11px]">
                                        <b>
                                            {formatCurrency({
                                                amount: tax.amount,
                                                currency: business.currency,
                                            })}
                                        </b>
                                    </td>
                                </tr>
                            ))}

                            {receipt.discount > 0 && (
                                <tr className="mt-10">
                                    <td colSpan={3} className="!text-[11px]"><b>Discount</b></td>
                                    <td className="text-right !text-[11px]">
                                        <b>
                                            {formatCurrency({
                                                amount: receipt.discount,
                                                currency: business.currency,
                                            })}
                                        </b>
                                    </td>
                                </tr>
                            )}

                            <tr className="mt-10">
                                <td colSpan={3} className="!text-[11px]"><b>Grand Total</b></td>
                                <td className="text-right !text-[11px]">
                                    <b>
                                        {formatCurrency({
                                            amount: receipt.grand_total,
                                            currency: business.currency,
                                        })}
                                    </b>
                                </td>
                            </tr>

                            {receipt.grand_total !== receipt.converted_total && (
                                <tr className="mt-10">
                                    <td colSpan={3} className="!text-[11px]"><b>Converted Total</b></td>
                                    <td className="text-right !text-[11px]">
                                        <b>
                                            {formatCurrency({
                                                amount: receipt.converted_total,
                                                currency: receipt.currency,
                                            })}
                                        </b>
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>

                    <p className="!text-[11px] mt-3">
                        <strong>Thank You For Shopping With Us — Please Come Again</strong>
                    </p>
                </div>
            </div>
        </div>
    );
}

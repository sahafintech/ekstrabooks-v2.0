import React from 'react';
import { Link } from '@inertiajs/react';
import { Button } from '@/Components/ui/button';
import { formatCurrency } from '@/lib/utils';
import { QRCodeSVG } from 'qrcode.react';

export default function CreditInvoicePos({ business, invoice }) {
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
            <title>Credit Invoice</title>
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
          <Link href={route('receipts.pos')} className="col-span-6">
            <Button variant="default" className="w-full">
              Back
            </Button>
          </Link>
        </div>

        {/* printable invoice */}
        <div id="invoice-pos" style={{ width: 350, fontFamily: 'Arial, sans-serif', fontSize: 13 }}>
          <div style={{ textAlign: 'center', marginBottom: 8 }}>
            <h2 style={{ fontWeight: 700, fontSize: 22, margin: 0 }}>{business.name}</h2>
            <div style={{ fontSize: 12, margin: '4px 0' }}>{business.address}</div>
            <div style={{ fontSize: 12 }}>{business.email}</div>
            <div style={{ fontSize: 12 }}>{business.phone}</div>
          </div>
          <div style={{ textAlign: 'center', fontWeight: 600, fontSize: 16, margin: '8px 0' }}>Credit Invoice</div>
          <div style={{ fontSize: 12, marginBottom: 8 }}>
            <div style={{ display: 'flex', justifyContent: 'space-between' }}>
              <span>Date:</span>
              <span>{invoice.invoice_date}</span>
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between' }}>
              <span>Invoice No:</span>
              <span>{invoice.invoice_number}</span>
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between' }}>
              <span>Customer Name:</span>
              <span>{invoice.customer?.name || '-'}</span>
            </div>
            <div style={{ display: 'flex', justifyContent: 'space-between' }}>
              <span>Customer Phone:</span>
              <span>{invoice.customer?.phone || '-'}</span>
            </div>
            {invoice.customer?.address && (
              <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                <span>Customer Address:</span>
                <span>{invoice.customer.address}</span>
              </div>
            )}
            {invoice.client_id && invoice.client_id !== invoice.customer_id && (
              <>
                <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                  <span>Client:</span>
                  <span>{invoice.client?.name || '-'}</span>
                </div>
                <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                  <span>Contract No:</span>
                  <span>{invoice.client?.contract_no || '-'}</span>
                </div>
              </>
            )}
          </div>
          <table style={{ width: '100%', borderCollapse: 'collapse', fontSize: 12, marginBottom: 8 }}>
            <thead>
              <tr style={{ borderBottom: '1px solid #000' }}>
                <th style={{ textAlign: 'left', padding: '4px 0' }}>Item</th>
                <th style={{ textAlign: 'right', padding: '4px 0' }}>Qty</th>
                <th style={{ textAlign: 'right', padding: '4px 0' }}>Price</th>
                <th style={{ textAlign: 'right', padding: '4px 0' }}>Total</th>
              </tr>
            </thead>
            <tbody>
              {invoice.items.map((item, i) => (
                <tr key={i}>
                  <td style={{ padding: '2px 0' }}>{item.product_name}</td>
                  <td style={{ textAlign: 'right', padding: '2px 0' }}>{item.quantity}</td>
                  <td style={{ textAlign: 'right', padding: '2px 0' }}>{formatCurrency({ amount: item.unit_cost, currency: business.currency })}</td>
                  <td style={{ textAlign: 'right', padding: '2px 0' }}>{formatCurrency({ amount: item.sub_total, currency: business.currency })}</td>
                </tr>
              ))}
            </tbody>
          </table>
          <div style={{ fontSize: 12, marginBottom: 8 }}>
            {invoice.taxes.map((tax, i) => (
              <div key={i} style={{ display: 'flex', justifyContent: 'space-between' }}>
                <span>{tax.name}:</span>
                <span>{formatCurrency({ amount: tax.amount, currency: business.currency })}</span>
              </div>
            ))}
            {invoice.discount > 0 && (
              <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                <span>Discount:</span>
                <span>{formatCurrency({ amount: invoice.discount, currency: business.currency })}</span>
              </div>
            )}
            <div style={{ display: 'flex', justifyContent: 'space-between', fontWeight: 700, borderTop: '1px solid #000', marginTop: 4, paddingTop: 4 }}>
              <span>Total:</span>
              <span>{formatCurrency({ amount: invoice.grand_total, currency: business.currency })}</span>
            </div>
            {invoice.grand_total !== invoice.converted_total && (
              <div style={{ display: 'flex', justifyContent: 'space-between' }}>
                <span>Converted Total:</span>
                <span>{formatCurrency({ amount: invoice.converted_total, currency: invoice.currency })}</span>
              </div>
            )}
          </div>
          <div style={{ textAlign: 'center', margin: '12px 0' }}>
            <QRCodeSVG value={invoice.invoice_number} size={80} level="H" includeMargin={true} />
          </div>
          <div style={{ textAlign: 'center', fontSize: 12, marginTop: 8 }}>
            <strong>Thank You For Shopping With Us — Please Come Again</strong>
          </div>
        </div>
      </div>
    </div>
  );
}

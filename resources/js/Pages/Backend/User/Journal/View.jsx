import React, { useRef } from "react";
import { Link } from "@inertiajs/react";
import { Button } from "@/Components/ui/button";
import PageHeader from "@/Components/PageHeader";
import { SidebarInset } from "@/Components/ui/sidebar";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { format } from "date-fns";
import {
  Printer,
  ArrowLeft,
  FileText,
  Download,
  CheckCircle,
  XCircle
} from "lucide-react";
import { cn, formatCurrency } from "@/lib/utils";

export default function View({ journal, transactions, pending_transactions }) {
  const printRef = useRef();
  const allTransactions = journal.status === 1 ? transactions : pending_transactions;

  // Calculate totals
  const totalDebit = allTransactions
    .filter(t => t.dr_cr === 'dr')
    .reduce((sum, t) => sum + parseFloat(t.transaction_amount), 0);

  const totalCredit = allTransactions
    .filter(t => t.dr_cr === 'cr')
    .reduce((sum, t) => sum + parseFloat(t.transaction_amount), 0);

  // Handle print functionality
  const handlePrint = () => {
    const printWindow = window.open('', '_blank');
    const printContent = printRef.current.innerHTML;
    
    printWindow.document.write(`
      <html>
        <head>
          <title>Journal Entry - ${journal.journal_number}</title>
          <style>
            body { font-family: Arial, sans-serif; }
            table { width: 100%; border-collapse: collapse; }
            th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
            th { background-color: #f8f9fa; }
            .text-right { text-align: right; }
            .text-center { text-align: center; }
            .font-bold { font-weight: bold; }
            .mb-8 { margin-bottom: 2rem; }
            .mb-4 { margin-bottom: 1rem; }
            .mb-1 { margin-bottom: 0.25rem; }
            .text-sm { font-size: 0.875rem; }
            .text-lg { font-size: 1.125rem; }
            .text-2xl { font-size: 1.5rem; }
            .text-gray-600 { color: #4b5563; }
            .text-gray-700 { color: #374151; }
            .text-gray-500 { color: #6b7280; }
            .border-b { border-bottom: 1px solid #e5e7eb; }
            .border-t { border-top: 1px solid #e5e7eb; }
            .grid { display: grid; }
            .grid-cols-2 { grid-template-columns: repeat(2, minmax(0, 1fr)); }
            .gap-6 { gap: 1.5rem; }
            .pb-6 { padding-bottom: 1.5rem; }
            .pt-6 { padding-top: 1.5rem; }
            .watermark {
              position: fixed;
              top: 50%;
              left: 50%;
              transform: translate(-50%, -50%) rotate(-45deg);
              font-size: 6rem;
              color: #e5e7eb;
              opacity: 0.1;
              pointer-events: none;
              z-index: 1000;
            }
            @media print {
              .watermark { display: block; }
            }
          </style>
        </head>
        <body>
          ${printContent}
          <div class="watermark">
            ${journal.status === 1 ? "APPROVED" : journal.status === 2 ? "REJECTED" : "PENDING"}
          </div>
        </body>
      </html>
    `);
    
    printWindow.document.close();
    printWindow.focus();
    
    // Wait for images and styles to load
    printWindow.onload = function() {
      printWindow.print();
      // Close the window after printing
      printWindow.onafterprint = function() {
        printWindow.close();
      };
    };
  };

  return (
    <AuthenticatedLayout>
      <SidebarInset>
        <div className="main-content">
          <PageHeader
            page="Journal Entries"
            subpage="View"
            url="journals.index"
          />

          <div className="p-4">
            {/* Action Buttons */}
            <div className="flex justify-between items-center mb-6">
              <div className="space-x-2">
                <Button onClick={handlePrint}>
                  <Printer className="w-4 h-4 mr-2" />
                  Print
                </Button>
                <Button asChild variant="outline">
                  <button onClick={() => window.location.href = route("journals.export", journal.id)}>
                    <Download className="w-4 h-4 mr-2" />
                    Export
                  </button>
                </Button>
              </div>

              <div className="flex items-center gap-2">
                {journal.status === 1 ? (
                  <span className="flex items-center text-green-600">
                    <CheckCircle className="w-4 h-4 mr-1" />
                    Approved
                  </span>
                ) : journal.status === 2 ? (
                  <span className="flex items-center text-red-600">
                    <XCircle className="w-4 h-4 mr-1" />
                    Rejected
                  </span>
                ) : (
                  <span className="flex items-center text-yellow-600">
                    <FileText className="w-4 h-4 mr-1" />
                    Pending
                  </span>
                )}

                {/* Edit button if not approved */}
                {journal.status !== 1 && (
                  <Button asChild variant="outline">
                    <Link href={route("journals.edit", journal.id)}>
                      Edit
                    </Link>
                  </Button>
                )}
              </div>
            </div>

            {/* Journal Entry Paper Document */}
            <div
              ref={printRef}
              
            >
              {/* Header */}
              <div className="mb-8 text-center">
                <h1 className="text-2xl font-bold mb-1">Journal Entry</h1>
                <div className="text-lg font-semibold text-gray-700">
                  #{journal.journal_number}
                </div>
                <div className="print:block hidden text-sm text-gray-500 mt-2">
                  Printed on: {format(new Date(), "MMMM d, yyyy")}
                </div>
              </div>

              {/* Journal Details */}
              <div className="grid grid-cols-2 gap-6 mb-8 border-b pb-6">
                <div>
                  <div className="text-sm text-gray-600 mb-1">Date</div>
                  <div className="font-medium">{journal.date}</div>
                </div>
                <div>
                  <div className="text-sm text-gray-600 mb-1">Currency</div>
                  <div className="font-medium">{journal.transaction_currency}</div>
                </div>
                <div>
                  <div className="text-sm text-gray-600 mb-1">Amount</div>
                  <div className="font-medium">
                    {formatCurrency({ amount: journal.transaction_amount, currency: journal.transaction_currency })}
                  </div>
                </div>
                <div>
                  <div className="text-sm text-gray-600 mb-1">Status</div>
                  <div className={cn(
                    "font-medium",
                    journal.status === 1 ? "text-green-600" :
                      journal.status === 2 ? "text-red-600" :
                        "text-yellow-600"
                  )}>
                    {journal.status === 1 ? "Approved" :
                      journal.status === 2 ? "Rejected" :
                        "Pending"}
                  </div>
                </div>
              </div>

              {/* Transactions Table */}
              <div className="mb-8">
                <h2 className="text-lg font-semibold mb-4">Transactions</h2>
                <div className="overflow-x-auto">
                  <table className="w-full border-collapse">
                    <thead>
                      <tr className="bg-gray-50">
                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">
                          Account
                        </th>
                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">
                          Description
                        </th>
                        <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider border-b">
                          Reference
                        </th>
                        <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider border-b">
                          Debit
                        </th>
                        <th className="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider border-b">
                          Credit
                        </th>
                      </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-200">
                      {allTransactions.map((transaction, index) => (
                        <tr key={index} className="hover:bg-gray-50">
                          <td className="px-4 py-3 text-sm text-gray-900 border-b">
                            {transaction.account?.account_name}
                          </td>
                          <td className="px-4 py-3 text-sm text-gray-900 border-b">
                            {transaction.description}
                          </td>
                          <td className="px-4 py-3 text-sm text-gray-500 border-b">
                            {transaction.customer_id && (
                              <span className="text-blue-600">Customer: {transaction.customer?.name}</span>
                            )}
                            {transaction.vendor_id && (
                              <span className="text-green-600">Vendor: {transaction.vendor?.name}</span>
                            )}
                            {transaction.project_id && (
                              <div className="text-purple-600">Project: {transaction.project?.project_name}</div>
                            )}
                            {transaction.project_task_id && (
                              <div className="text-purple-600">Project Task: {transaction.project_task?.description}</div>
                            )}
                            {transaction.cost_code_id && (
                              <div className="text-purple-600">Cost Code: {transaction.cost_code?.description}</div>
                            )}
                          </td>
                          <td className="px-4 py-3 text-sm text-right font-medium border-b">
                            {transaction.dr_cr === 'dr'
                              ? formatCurrency({ amount: transaction.transaction_amount, currency: journal.transaction_currency })
                              : ''}
                          </td>
                          <td className="px-4 py-3 text-sm text-right font-medium border-b">
                            {transaction.dr_cr === 'cr'
                              ? formatCurrency({ amount: transaction.transaction_amount, currency: journal.transaction_currency })
                              : ''}
                          </td>
                        </tr>
                      ))}

                      {/* Totals Row */}
                      <tr className="bg-gray-50">
                        <td colSpan={3} className="px-4 py-3 text-sm font-bold text-right border-t-2 border-gray-300">
                          Total:
                        </td>
                        <td className="px-4 py-3 text-sm text-right font-bold border-t-2 border-gray-300">
                          {formatCurrency({ amount: totalDebit, currency: journal.transaction_currency })}
                        </td>
                        <td className="px-4 py-3 text-sm text-right font-bold border-t-2 border-gray-300">
                          {formatCurrency({ amount: totalCredit, currency: journal.transaction_currency })}
                        </td>
                      </tr>
                    </tbody>
                  </table>
                </div>
              </div>

              {/* Footer Information */}
              <div className="grid grid-cols-2 gap-6 border-t pt-6">
                <div>
                  <div className="text-sm text-gray-600 mb-1">Created By</div>
                  <div className="font-medium">{journal.created_user?.name}</div>
                  <div className="text-xs text-gray-500">
                    Created on: {format(new Date(journal.created_at), "MMMM d, yyyy")}
                  </div>
                </div>
                {journal.approved_by && (
                  <div>
                    <div className="text-sm text-gray-600 mb-1">Approved By</div>
                    <div className="font-medium">{journal.approved_user?.name}</div>
                    <div className="text-xs text-gray-500">
                      Approved on: {format(new Date(journal.updated_at), "MMMM d, yyyy")}
                    </div>
                  </div>
                )}
              </div>

              {/* Print Instructions - Hidden in print view */}
              <div className="print:hidden mt-6 text-sm text-gray-500 text-center max-w-4xl mx-auto">
                <p>This journal entry document is designed for printing. Click the Print button above to print a clean version.</p>
              </div>
            </div>
          </div>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}

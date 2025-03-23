import React, { useRef } from "react";
import { Head, Link } from "@inertiajs/react";
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
import { cn } from "@/lib/utils";

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
    const printContent = printRef.current;
    const originalContents = document.body.innerHTML;

    document.body.innerHTML = printContent.innerHTML;
    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload();
  };

  // Format currency values
  const formatCurrency = (amount, currency) => {
    return new Intl.NumberFormat('en-US', {
      style: 'currency',
      currency: currency || 'USD'
    }).format(amount);
  };

  return (
    <AuthenticatedLayout>
      <Head title="View Journal Entry" />

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
                  <Link href={route("journals.export", journal.id)} method="get">
                    <Download className="w-4 h-4 mr-2" />
                    Export
                  </Link>
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
                    {formatCurrency(journal.transaction_amount, journal.transaction_currency)}
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
                          </td>
                          <td className="px-4 py-3 text-sm text-right font-medium border-b">
                            {transaction.dr_cr === 'dr'
                              ? formatCurrency(transaction.transaction_amount, journal.transaction_currency)
                              : ''}
                          </td>
                          <td className="px-4 py-3 text-sm text-right font-medium border-b">
                            {transaction.dr_cr === 'cr'
                              ? formatCurrency(transaction.transaction_amount, journal.transaction_currency)
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
                          {formatCurrency(totalDebit, journal.transaction_currency)}
                        </td>
                        <td className="px-4 py-3 text-sm text-right font-bold border-t-2 border-gray-300">
                          {formatCurrency(totalCredit, journal.transaction_currency)}
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

              {/* Watermark for printing */}
              <div className="hidden print:block absolute top-1/2 left-1/2 transform -translate-x-1/2 -translate-y-1/2 text-gray-200 text-8xl opacity-10 font-bold rotate-45 pointer-events-none">
                {journal.status === 1 ? "APPROVED" :
                  journal.status === 2 ? "REJECTED" :
                    "PENDING"}
              </div>
            </div>

            {/* Print Instructions - Hidden in print view */}
            <div className="print:hidden mt-6 text-sm text-gray-500 text-center max-w-4xl mx-auto">
              <p>This journal entry document is designed for printing. Click the Print button above to print a clean version.</p>
            </div>
          </div>
        </div>
      </SidebarInset>
    </AuthenticatedLayout>
  );
}

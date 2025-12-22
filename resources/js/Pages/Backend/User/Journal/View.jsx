import React, { useRef, useState } from "react";
import { Link, router, usePage } from "@inertiajs/react";
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
  XCircle,
  Clock,
  Users,
  MoreVertical,
  Edit
} from "lucide-react";
import { cn, formatCurrency } from "@/lib/utils";
import { Badge } from "@/Components/ui/badge";
import DrawerComponent from "@/Components/DrawerComponent";
import Modal from "@/Components/Modal";
import { Textarea } from "@/Components/ui/textarea";
import { toast } from "sonner";
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from "@/Components/ui/table";
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";

export default function View({ journal, transactions, pending_transactions, approvalUsersCount = 0, hasConfiguredApprovers = false }) {
  const printRef = useRef();
  const allTransactions = journal.status === 1 ? transactions : pending_transactions;
  
  const [isApprovalsDrawerOpen, setIsApprovalsDrawerOpen] = useState(false);
  const [isApproveRejectModalOpen, setIsApproveRejectModalOpen] = useState(false);
  const [approvalAction, setApprovalAction] = useState(null); // 'approve' or 'reject'
  const [approvalComment, setApprovalComment] = useState('');
  const [isSubmittingApproval, setIsSubmittingApproval] = useState(false);

  // Calculate totals
  const totalDebit = allTransactions
    .filter(t => t.dr_cr === 'dr')
    .reduce((sum, t) => sum + parseFloat(t.transaction_amount), 0);

  const totalCredit = allTransactions
    .filter(t => t.dr_cr === 'cr')
    .reduce((sum, t) => sum + parseFloat(t.transaction_amount), 0);

  // Get current user's approval status
  const userApproval = journal.approvals?.find(approval => approval.action_user?.id === usePage().props.auth.user.id);
  // User can only take approval actions if:
  // 1. There are configured approvers in the system
  // 2. AND the user is one of the assigned approvers
  const canApprove = hasConfiguredApprovers && userApproval !== undefined;
  const currentStatus = userApproval?.status || null;
  // Check individual user's approval status:
  // - status 0 (pending): Show both Approve and Reject buttons
  // - status 1 (approved): Show only Reject button (to change vote)
  // - status 2 (rejected): Show only Approve button (to change vote)
  const hasAlreadyApproved = currentStatus === 1;
  const hasAlreadyRejected = currentStatus === 2;

  const handleApprovalAction = (action) => {
    setApprovalAction(action);
    setApprovalComment('');
    setIsApproveRejectModalOpen(true);
  };

  const submitApprovalAction = () => {
    if (approvalAction === 'reject' && !approvalComment.trim()) {
      toast.error("Comment is required when rejecting");
      return;
    }

    setIsSubmittingApproval(true);

    router.post(route(`journals.${approvalAction}`, journal.id), {
      comment: approvalComment
    }, {
      preserveScroll: true,
      onSuccess: () => {
        setIsApproveRejectModalOpen(false);
        setApprovalComment('');
        setIsSubmittingApproval(false);
        toast.success(`Journal ${approvalAction}d successfully`);
      },
      onError: (errors) => {
        setIsSubmittingApproval(false);
        if (errors.comment) {
          toast.error(errors.comment);
        } else {
          toast.error(`Failed to ${approvalAction} journal`);
        }
      }
    });
  };

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
            {/* Warning banner when no approvers are configured */}
            {!hasConfiguredApprovers && journal.status === 0 && (
              <div className="mb-4 p-4 rounded-lg border border-yellow-400 bg-yellow-50 dark:bg-yellow-900/20 dark:border-yellow-600 print:hidden">
                <div className="flex items-start gap-3">
                  <div className="flex-shrink-0 mt-0.5">
                    <svg className="h-5 w-5 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                      <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                    </svg>
                  </div>
                  <div>
                    <h3 className="text-sm font-medium text-yellow-800 dark:text-yellow-200">No Approvers Assigned</h3>
                    <p className="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                      This journal entry cannot be approved or rejected until approvers are configured.
                      Go to <span className="font-medium">Settings → Business Settings → Approval Workflows</span> to assign approvers.
                    </p>
                  </div>
                </div>
              </div>
            )}

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
                {approvalUsersCount > 0 && (
                  <Button
                    variant="outline"
                    onClick={() => setIsApprovalsDrawerOpen(true)}
                    className="flex items-center"
                  >
                    <Users className="mr-2 h-4 w-4" />
                    Approvals
                    <Badge variant="secondary" className="ml-2">
                      {approvalUsersCount}
                    </Badge>
                  </Button>
                )}
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

                <DropdownMenu>
                  <DropdownMenuTrigger asChild>
                    <Button variant="outline" size="icon">
                      <MoreVertical className="h-4 w-4" />
                    </Button>
                  </DropdownMenuTrigger>
                  <DropdownMenuContent align="end">
                    {canApprove && (
                      <>
                        {!hasAlreadyApproved && (
                          <DropdownMenuItem onClick={() => handleApprovalAction('approve')}>
                            <CheckCircle className="mr-2 h-4 w-4 text-green-600" />
                            <span>Approve</span>
                          </DropdownMenuItem>
                        )}
                        {!hasAlreadyRejected && (
                          <DropdownMenuItem onClick={() => handleApprovalAction('reject')}>
                            <XCircle className="mr-2 h-4 w-4 text-red-600" />
                            <span>Reject</span>
                          </DropdownMenuItem>
                        )}
                      </>
                    )}
                    {/* Edit button if not approved */}
                    {journal.status !== 1 && (
                      <DropdownMenuItem asChild>
                        <Link href={route("journals.edit", journal.id)} className="flex items-center">
                          <Edit className="mr-2 h-4 w-4" />
                          <span>Edit Journal</span>
                        </Link>
                      </DropdownMenuItem>
                    )}
                  </DropdownMenuContent>
                </DropdownMenu>
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

      {/* Approvals Drawer */}
      <DrawerComponent
        open={isApprovalsDrawerOpen}
        onOpenChange={setIsApprovalsDrawerOpen}
        title="Journal Approvals"
        position="right"
        width="w-4xl"
      >
        <div className="p-4">
          <div className="mb-4">
            <p className="text-sm text-gray-600">
              View approval status from all assigned approvers
            </p>
          </div>

          <Table>
            <TableHeader>
              <TableRow>
                <TableHead>Name</TableHead>
                <TableHead>Email</TableHead>
                <TableHead>Status</TableHead>
                <TableHead>Comment</TableHead>
                <TableHead>Date</TableHead>
              </TableRow>
            </TableHeader>
            <TableBody>
              {journal.approvals && journal.approvals.length > 0 ? (
                journal.approvals.map((approval, index) => (
                  <TableRow key={index}>
                    <TableCell className="font-medium">
                      {approval.action_user?.name || 'N/A'}
                    </TableCell>
                    <TableCell>
                      {approval.action_user?.email || 'N/A'}
                    </TableCell>
                    <TableCell>
                      {approval.status === 0 && (
                        <Badge variant="outline" className="flex items-center w-fit">
                          <Clock className="h-3 w-3 mr-1" />
                          Pending
                        </Badge>
                      )}
                      {approval.status === 1 && (
                        <Badge variant="outline" className="flex items-center w-fit text-green-600 border-green-600">
                          <CheckCircle className="h-3 w-3 mr-1" />
                          Approved
                        </Badge>
                      )}
                      {approval.status === 2 && (
                        <Badge variant="outline" className="flex items-center w-fit text-red-600 border-red-600">
                          <XCircle className="h-3 w-3 mr-1" />
                          Rejected
                        </Badge>
                      )}
                    </TableCell>
                    <TableCell>
                      {approval.comment || '-'}
                    </TableCell>
                    <TableCell>
                      {approval.action_date ? new Date(approval.action_date).toLocaleDateString() : '-'}
                    </TableCell>
                  </TableRow>
                ))
              ) : (
                <TableRow>
                  <TableCell colSpan={5} className="text-center py-8">
                    <div className="flex flex-col items-center">
                      <Users className="h-10 w-10 text-yellow-500 mb-3" />
                      <p className="font-medium text-yellow-700 dark:text-yellow-400 mb-2">No Approvers Assigned</p>
                      <p className="text-sm text-gray-500 dark:text-gray-400">
                        This journal entry cannot be approved until approvers are configured.
                      </p>
                      <p className="text-xs text-gray-400 dark:text-gray-500 mt-1">
                        Go to <span className="font-medium">Settings → Business Settings → Approval Workflows</span> to assign approvers.
                      </p>
                    </div>
                  </TableCell>
                </TableRow>
              )}
            </TableBody>
          </Table>
        </div>
      </DrawerComponent>

      {/* Approve/Reject Modal */}
      <Modal
        show={isApproveRejectModalOpen}
        onClose={() => setIsApproveRejectModalOpen(false)}
        maxWidth="md"
      >
        <div className="mb-6">
          <h2 className="text-lg font-medium text-gray-900 dark:text-gray-100 flex items-center">
            {approvalAction === 'approve' ? (
              <>
                <CheckCircle className="h-5 w-5 mr-2 text-green-600" />
                Approve Journal
              </>
            ) : (
              <>
                <XCircle className="h-5 w-5 mr-2 text-red-600" />
                Reject Journal
              </>
            )}
          </h2>
          <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
            {approvalAction === 'approve'
              ? 'Add an optional comment for this approval'
              : 'Please provide a reason for rejecting this journal'}
          </p>
        </div>

        <div className="mb-6">
          <label className="block text-sm font-medium mb-2">
            Comment {approvalAction === 'reject' && <span className="text-red-600">*</span>}
          </label>
          <Textarea
            value={approvalComment}
            onChange={(e) => setApprovalComment(e.target.value)}
            placeholder={approvalAction === 'approve' ? "Add a comment (optional)" : "Explain why you're rejecting this journal"}
            rows={4}
            className="w-full"
          />
        </div>

        <div className="flex justify-end space-x-2">
          <Button
            type="button"
            variant="outline"
            onClick={() => setIsApproveRejectModalOpen(false)}
            disabled={isSubmittingApproval}
          >
            Cancel
          </Button>
          <Button
            type="button"
            onClick={submitApprovalAction}
            disabled={isSubmittingApproval}
            className={approvalAction === 'approve' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700'}
          >
            {isSubmittingApproval 
              ? (approvalAction === 'approve' ? 'Approving...' : 'Rejecting...') 
              : (approvalAction === 'approve' ? 'Approve' : 'Reject')
            }
          </Button>
        </div>
      </Modal>
    </AuthenticatedLayout>
  );
}

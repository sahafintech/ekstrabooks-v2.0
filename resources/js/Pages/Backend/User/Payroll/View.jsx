import React, { useState } from "react";
import { Link, router, usePage } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Button } from "@/Components/ui/button";
import { Separator } from "@/Components/ui/separator";
import { formatCurrency } from "@/lib/utils";
import { Download, Edit, Printer, CheckCircle, XCircle, Clock, Users, MoreVertical } from "lucide-react";
import { Badge } from "@/Components/ui/badge";
import { toast } from "sonner";
import Modal from "@/Components/Modal";
import { Textarea } from "@/Components/ui/textarea";
import { Table, TableBody, TableHeader, TableRow, TableHead, TableCell } from "@/Components/ui/table";
import DrawerComponent from "@/Components/DrawerComponent";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger
} from "@/Components/ui/dropdown-menu";

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

export default function View({
    payroll,
    allowances,
    deductions,
    advance,
    actual_working_hours,
    approvalUsersCount,
    hasConfiguredApprovers
}) {
    const [isLoading, setIsLoading] = useState({
        print: false,
        pdf: false,
    });
    const [isApprovalsDrawerOpen, setIsApprovalsDrawerOpen] = useState(false);
    const [isApproveRejectModalOpen, setIsApproveRejectModalOpen] = useState(false);
    const [approvalAction, setApprovalAction] = useState(null); // 'approve' or 'reject'
    const [approvalComment, setApprovalComment] = useState('');
    const [isSubmittingApproval, setIsSubmittingApproval] = useState(false);

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

        router.post(route(`payslips.${approvalAction}`, payroll.id), {
            comment: approvalComment
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setIsApproveRejectModalOpen(false);
                setApprovalComment('');
                setIsSubmittingApproval(false);
                toast.success(`Payroll ${approvalAction}d successfully`);
            },
            onError: (errors) => {
                setIsSubmittingApproval(false);
                if (errors.comment) {
                    toast.error(errors.comment);
                } else {
                    toast.error(`Failed to ${approvalAction} payroll`);
                }
            }
        });
    };

    // Get current user's approval status
    const userApproval = payroll.approvals?.find(approval => approval.action_user?.id === usePage().props.auth.user.id);
    // User can only take approval actions if:
    // 1. There are configured approvers in the system
    // 2. AND the user is one of the assigned approvers
    // 3. AND the payroll is still in draft status (status = 0)
    const canApprove = hasConfiguredApprovers && userApproval !== undefined && payroll.status === 0;
    const currentStatus = userApproval?.status || null;
    // Check individual user's approval status:
    // - status 0 (pending): Show both Approve and Reject buttons
    // - status 1 (approved): Show only Reject button (to change vote)
    // - status 2 (rejected): Show only Approve button (to change vote)
    const hasAlreadyApproved = currentStatus === 1;
    const hasAlreadyRejected = currentStatus === 2;

    const calculateTotalAllowances = () => {
        if (!allowances || !Array.isArray(allowances)) return 0;
        return allowances.reduce(
            (sum, item) => sum + parseFloat(item.amount || 0),
            0
        );
    };

    const calculateTotalDeductions = () => {
        if (!deductions || !Array.isArray(deductions)) return 0;
        const deductionsTotal = deductions.reduce(
            (sum, item) => sum + parseFloat(item.amount || 0),
            0
        );
        return deductionsTotal + calculateTimesheetDeduction();
    };

    const calculateTimesheetDeduction = () => {
        const requiredHours = parseFloat(payroll?.required_working_hours || 0);
        const actualHours = parseFloat(actual_working_hours || 0);
        const hourlyRate = parseFloat(payroll?.cost_normal_hours || 0);

        if (actualHours >= requiredHours) return 0;
        
        const missingHours = requiredHours - actualHours;
        return missingHours * hourlyRate;
    };

    const handlePrint = () => {
        setIsLoading((prev) => ({ ...prev, print: true }));
        setTimeout(() => {
            window.print();
            setIsLoading((prev) => ({ ...prev, print: false }));
        }, 300);
    };

    const handleDownloadPDF = async () => {
        setIsLoading((prev) => ({ ...prev, pdf: true }));
        try {
            const html2canvas = (await import("html2canvas")).default;
            const { jsPDF } = await import("jspdf");

            const content = document.querySelector("#printable-area");
            if (!content) {
                throw new Error("Content element not found");
            }

            const canvas = await html2canvas(content, {
                scale: 4,
                useCORS: true,
                logging: false,
                windowWidth: content.scrollWidth,
                windowHeight: content.scrollHeight,
                allowTaint: true,
                backgroundColor: "#ffffff",
            });

            const imgWidth = 210;
            const pageHeight = 297;
            const imgHeight = (canvas.height * imgWidth) / canvas.width;

            const pdf = new jsPDF("p", "mm", "a4");
            let heightLeft = imgHeight;
            let position = 0;
            let pageData = canvas.toDataURL("image/jpeg", 1.0);

            pdf.addImage(pageData, "JPEG", 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;

            while (heightLeft >= 0) {
                position = heightLeft - imgHeight;
                pdf.addPage();
                pdf.addImage(
                    pageData,
                    "JPEG",
                    0,
                    position,
                    imgWidth,
                    imgHeight
                );
                heightLeft -= pageHeight;
            }

            pdf.save(
                `Payslip_${payroll?.staff?.name}_${payroll?.month}_${payroll?.year}.pdf`
            );
        } catch (error) {
            console.error("Error generating PDF:", error);
            toast.error("Failed to generate PDF. Please try again.");
        } finally {
            setIsLoading((prev) => ({ ...prev, pdf: false }));
        }
    };

    return (
        <AuthenticatedLayout>
            <style>{printStyles}</style>
            <SidebarInset>
                <PageHeader
                    page="Payrolls"
                    subpage="View Payslip"
                    url="payslips.index"
                />

                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    {/* Warning banner when no approvers are configured */}
                    {!hasConfiguredApprovers && payroll.status === 0 && (
                        <div className="p-4 rounded-lg border border-yellow-400 bg-yellow-50 dark:bg-yellow-900/20 dark:border-yellow-600 print:hidden">
                            <div className="flex items-start gap-3">
                                <div className="flex-shrink-0 mt-0.5">
                                    <svg className="h-5 w-5 text-yellow-600 dark:text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                        <path fillRule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clipRule="evenodd" />
                                    </svg>
                                </div>
                                <div>
                                    <h3 className="text-sm font-medium text-yellow-800 dark:text-yellow-200">No Approvers Assigned</h3>
                                    <p className="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                                        This payroll cannot be approved or rejected until approvers are configured.
                                        Go to <span className="font-medium">Settings → Business Settings → Approval Workflows</span> to assign approvers.
                                    </p>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Action Buttons */}
                    <div className="flex justify-end gap-2 no-print">
                        {approvalUsersCount > 0 && (
                            <Button
                                variant="outline"
                                size="sm"
                                onClick={() => setIsApprovalsDrawerOpen(true)}
                            >
                                <Users className="w-4 h-4 mr-2" />
                                Approvals
                                <Badge variant="secondary" className="ml-2">
                                    {approvalUsersCount}
                                </Badge>
                            </Button>
                        )}
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handlePrint}
                            disabled={isLoading.print}
                        >
                            <Printer className="w-4 h-4 mr-2" />
                            {isLoading.print ? "Printing..." : "Print Payslip"}
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleDownloadPDF}
                            disabled={isLoading.pdf}
                        >
                            <Download className="w-4 h-4 mr-2" />
                            {isLoading.pdf ? "Downloading..." : "Download PDF"}
                        </Button>

                        <DropdownMenu>
                            <DropdownMenuTrigger asChild>
                                <Button variant="outline" size="sm">
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
                                <DropdownMenuItem asChild>
                                    <Link href={route("payslips.edit", payroll.id)} className="flex items-center">
                                        <Edit className="mr-2 h-4 w-4" />
                                        <span>Edit Payslip</span>
                                    </Link>
                                </DropdownMenuItem>
                            </DropdownMenuContent>
                        </DropdownMenu>
                    </div>

                    {/* Payslip Card */}
                    <div id="printable-area" className="rounded-md border p-4 mt-4 w-full lg:w-[210mm] min-h-[297mm] mx-auto bg-white">
                        {/* Header */}
                        <div className="text-center p-4">
                            <h1 className="text-lg font-bold">PAYSLIP</h1>
                            <div className="flex items-center justify-center space-x-2 mt-2">
                                <span>Period:</span>
                                <span>
                                    {payroll?.month}/{payroll?.year}
                                </span>
                            </div>
                            <Badge
                                variant={
                                    payroll?.status === 4 ? "success" :
                                    payroll?.status === 3 ? "outline" :
                                    payroll?.status === 2 ? "secondary" :
                                    payroll?.status === 1 ? "default" :
                                    "warning"
                                }
                                className="mt-2"
                            >
                                {payroll?.status === 4 ? "Paid" :
                                 payroll?.status === 3 ? "Partially Paid" :
                                 payroll?.status === 2 ? "Accrued" :
                                 payroll?.status === 1 ? "Approved" :
                                 "Draft"}
                            </Badge>
                        </div>

                        <div className="p-4">
                            {/* Employee Information */}
                            <div className="mb-6">
                                <h3 className="text-left underline font-bold mb-2">
                                    EMPLOYEE INFORMATION
                                </h3>
                                <div className="grid grid-cols-2 gap-4">
                                    <div>
                                        <div className="text-xs text-muted-foreground">
                                            Employee ID
                                        </div>
                                        <div className="text-sm font-medium">
                                            {payroll?.staff?.employee_id}
                                        </div>
                                    </div>
                                    <div>
                                        <div className="text-xs text-muted-foreground">
                                            Employee Name
                                        </div>
                                        <div className="text-sm font-medium">
                                            {payroll?.staff?.name}
                                        </div>
                                    </div>
                                    <div>
                                        <div className="text-xs text-muted-foreground">
                                            Department
                                        </div>
                                        <div className="text-sm font-medium">
                                            {payroll?.staff?.department?.name ||
                                                "N/A"}
                                        </div>
                                    </div>
                                    <div>
                                        <div className="text-xs text-muted-foreground">
                                            Designation
                                        </div>
                                        <div className="text-sm font-medium">
                                            {payroll?.staff?.designation
                                                ?.name || "N/A"}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <Separator className="my-4" />

                            {/* Working Hours & Holidays */}
                            <div className="mb-6">
                                <h3 className="text-left underline font-bold mb-2">
                                    WORKING HOURS & HOLIDAYS
                                </h3>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="bg-slate-50 p-3 rounded-md">
                                        <h4 className="text-sm font-medium mb-2">
                                            Working Hours
                                        </h4>
                                        <div className="grid grid-cols-2 gap-2">
                                            <div>
                                                <div className="text-xs text-muted-foreground">
                                                    Required Hours
                                                </div>
                                                <div className="text-sm font-medium">
                                                    {payroll?.required_working_hours ||
                                                        0}
                                                </div>
                                            </div>
                                            <div className="flex flex-col gap-2">
                                                <div>
                                                    <div className="text-xs text-muted-foreground">
                                                        Actual Hours
                                                    </div>
                                                    <div className="text-sm font-medium">
                                                        {actual_working_hours ||
                                                            0}
                                                    </div>
                                                </div>
                                                <div>
                                                    <div className="text-xs text-muted-foreground">
                                                        Overtime Hours
                                                    </div>
                                                    <div className="text-sm font-medium">
                                                        {payroll?.overtime_hours ||
                                                            0}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="bg-slate-50 p-3 rounded-md">
                                        <h4 className="text-sm font-medium mb-2">
                                            Holidays & Weekends
                                        </h4>
                                        <div className="grid grid-cols-2 gap-2">
                                            <div>
                                                <div className="text-xs text-muted-foreground">
                                                    Public Holidays
                                                </div>
                                                <div className="text-sm font-medium">
                                                    {payroll?.public_holiday ||
                                                        0}
                                                </div>
                                            </div>
                                            <div>
                                                <div className="text-xs text-muted-foreground">
                                                    Weekends
                                                </div>
                                                <div className="text-sm font-medium">
                                                    {payroll?.weekend || 0}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Cost Details */}
                            <div className="mb-6">
                                <h3 className="text-left underline font-bold mb-2">
                                    COST DETAILS
                                </h3>
                                <div className="grid grid-cols-2 gap-4">
                                    <div className="bg-slate-50 p-3 rounded-md">
                                        <h4 className="text-sm font-medium mb-2">
                                            Hourly Rates
                                        </h4>
                                        <div className="grid grid-cols-2 gap-2">
                                            <div>
                                                <div className="text-xs text-muted-foreground">
                                                    Normal Hours
                                                </div>
                                                <div className="text-sm font-medium">
                                                    {formatCurrency({
                                                        amount:
                                                            payroll?.cost_normal_hours ||
                                                            0,
                                                    })}
                                                </div>
                                            </div>
                                            <div>
                                                <div className="text-xs text-muted-foreground">
                                                    Overtime Hours
                                                </div>
                                                <div className="text-sm font-medium">
                                                    {formatCurrency({
                                                        amount:
                                                            payroll?.cost_overtime_hours ||
                                                            0,
                                                    })}
                                                </div>
                                            </div>
                                            <div>
                                                <div className="text-xs text-muted-foreground">
                                                    Public Holiday
                                                </div>
                                                <div className="text-sm font-medium">
                                                    {formatCurrency({
                                                        amount:
                                                            payroll?.cost_public_holiday ||
                                                            0,
                                                    })}
                                                </div>
                                            </div>
                                            <div>
                                                <div className="text-xs text-muted-foreground">
                                                    Weekend
                                                </div>
                                                <div className="text-sm font-medium">
                                                    {formatCurrency({
                                                        amount:
                                                            payroll?.cost_weekend ||
                                                            0,
                                                    })}
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div className="bg-slate-50 p-3 rounded-md">
                                        <h4 className="text-sm font-medium mb-2">
                                            Total Costs
                                        </h4>
                                        <div className="grid grid-cols-2 gap-2">
                                            <div>
                                                <div className="text-xs text-muted-foreground">
                                                    Normal Hours
                                                </div>
                                                <div className="text-sm font-medium">
                                                    {formatCurrency({
                                                        amount:
                                                            payroll?.total_cost_normal_hours ||
                                                            0,
                                                    })}
                                                </div>
                                            </div>
                                            <div>
                                                <div className="text-xs text-muted-foreground">
                                                    Overtime Hours
                                                </div>
                                                <div className="text-sm font-medium">
                                                    {formatCurrency({
                                                        amount:
                                                            payroll?.total_cost_overtime_hours ||
                                                            0,
                                                    })}
                                                </div>
                                            </div>
                                            <div>
                                                <div className="text-xs text-muted-foreground">
                                                    Public Holiday
                                                </div>
                                                <div className="text-sm font-medium">
                                                    {formatCurrency({
                                                        amount:
                                                            payroll?.total_cost_public_holiday ||
                                                            0,
                                                    })}
                                                </div>
                                            </div>
                                            <div>
                                                <div className="text-xs text-muted-foreground">
                                                    Weekend
                                                </div>
                                                <div className="text-sm font-medium">
                                                    {formatCurrency({
                                                        amount:
                                                            payroll?.total_cost_weekend ||
                                                            0,
                                                    })}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <Separator className="my-4" />

                            {/* Earnings and Deductions Tables */}
                            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                {/* Earnings Table */}
                                <div>
                                    <h3 className="text-left underline font-bold mb-2">
                                        EARNINGS
                                    </h3>
                                    <table className="w-full !text-[12px]">
                                        <tbody>
                                            <tr>
                                                <td>Basic Salary</td>
                                                <td className="text-right">
                                                    {formatCurrency({
                                                        amount:
                                                            payroll?.current_salary ||
                                                            0,
                                                    })}
                                                </td>
                                            </tr>

                                            {allowances &&
                                                allowances.map(
                                                    (item, index) => (
                                                        <tr
                                                            key={`allowance-${index}`}
                                                        >
                                                            <td>
                                                                {item.description ||
                                                                    "Allowance"}
                                                            </td>
                                                            <td className="text-right">
                                                                {formatCurrency(
                                                                    {
                                                                        amount:
                                                                            item.amount ||
                                                                            0,
                                                                    }
                                                                )}
                                                            </td>
                                                        </tr>
                                                    )
                                                )}

                                            <tr>
                                                <td className="font-bold">
                                                    Total Earnings
                                                </td>
                                                <td className="text-right font-bold border-t border-black">
                                                    {formatCurrency({
                                                        amount:
                                                            parseFloat(
                                                                payroll?.current_salary ||
                                                                    0
                                                            ) +
                                                            calculateTotalAllowances(),
                                                    })}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                {/* Deductions Table */}
                                <div>
                                    <h3 className="text-left underline font-bold mb-2">
                                        DEDUCTIONS
                                    </h3>
                                    <table className="w-full !text-[12px]">
                                        <tbody>
                                            {deductions &&
                                                deductions.map(
                                                    (item, index) => (
                                                        <tr
                                                            key={`deduction-${index}`}
                                                        >
                                                            <td>
                                                                {item.description ||
                                                                    "Deduction"}
                                                            </td>
                                                            <td className="text-right">
                                                                {formatCurrency(
                                                                    {
                                                                        amount:
                                                                            item.amount ||
                                                                            0,
                                                                    }
                                                                )}
                                                            </td>
                                                        </tr>
                                                    )
                                                )}

                                            {calculateTimesheetDeduction() > 0 && (
                                                <tr>
                                                    <td>Timesheet Deduction</td>
                                                    <td className="text-right">
                                                        {formatCurrency({
                                                            amount: calculateTimesheetDeduction(),
                                                        })}
                                                    </td>
                                                </tr>
                                            )}

                                            {advance > 0 && (
                                                <tr>
                                                    <td>Advance</td>
                                                    <td className="text-right">
                                                        {formatCurrency({
                                                            amount: advance || 0,
                                                        })}
                                                    </td>
                                                </tr>
                                            )}

                                            <tr>
                                                <td className="font-bold">
                                                    Total Deductions
                                                </td>
                                                <td className="text-right font-bold border-t border-black">
                                                    {formatCurrency({
                                                        amount:
                                                            calculateTotalDeductions(),
                                                    })}
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <Separator className="my-6" />

                            {/* Net Salary Summary */}
                            <div className="bg-slate-50 p-4 rounded-md">
                                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <div className="text-sm font-medium">
                                            Summary
                                        </div>
                                        <div className="grid grid-cols-2 gap-2 mt-2">
                                            <div className="text-xs text-muted-foreground">
                                                Total Earnings
                                            </div>
                                            <div className="text-sm font-medium text-right">
                                                {formatCurrency({
                                                    amount:
                                                        parseFloat(
                                                            payroll?.current_salary ||
                                                                0
                                                        ) +
                                                        calculateTotalAllowances(),
                                                })}
                                            </div>

                                            <div className="text-xs text-muted-foreground">
                                                Total Deductions
                                            </div>
                                            <div className="text-sm font-medium text-right">
                                                {formatCurrency({
                                                    amount:
                                                        calculateTotalDeductions(),
                                                })}
                                            </div>
                                        </div>
                                    </div>

                                    <div>
                                        <div className="text-sm font-medium text-muted-foreground">
                                            Net Salary
                                        </div>
                                        <div className="text-2xl font-bold mt-2">
                                            {formatCurrency({
                                                amount: payroll?.net_salary || 0,
                                            })}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {/* Footer Note */}
                            <div className="mt-6 text-xs text-center text-muted-foreground">
                                This is a computer-generated payslip and does
                                not require a signature.
                            </div>
                        </div>
                    </div>
                </div>
            </SidebarInset>

            {/* Approvals Drawer */}
            <DrawerComponent
                open={isApprovalsDrawerOpen}
                onOpenChange={setIsApprovalsDrawerOpen}
                title="Payroll Approvals"
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
                            {payroll.approvals && payroll.approvals.length > 0 ? (
                                payroll.approvals.map((approval, index) => (
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
                                                This payroll cannot be approved until approvers are configured.
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
                                Approve Payroll
                            </>
                        ) : (
                            <>
                                <XCircle className="h-5 w-5 mr-2 text-red-600" />
                                Reject Payroll
                            </>
                        )}
                    </h2>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        {approvalAction === 'approve'
                            ? 'Add an optional comment for this approval'
                            : 'Please provide a reason for rejecting this payroll'}
                    </p>
                </div>

                <div className="mb-6">
                    <label className="block text-sm font-medium mb-2">
                        Comment {approvalAction === 'reject' && <span className="text-red-600">*</span>}
                    </label>
                    <Textarea
                        value={approvalComment}
                        onChange={(e) => setApprovalComment(e.target.value)}
                        placeholder={approvalAction === 'approve' ? "Add a comment (optional)" : "Explain why you're rejecting this payroll"}
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

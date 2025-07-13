import React, { useState } from "react";
import { Link } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Button } from "@/Components/ui/button";
import { Separator } from "@/Components/ui/separator";
import { formatCurrency } from "@/lib/utils";
import { Download, Edit, Printer } from "lucide-react";
import { Badge } from "@/Components/ui/badge";
import { toast } from "sonner";

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
    actual_working_hours
}) {
    const [isLoading, setIsLoading] = useState({
        print: false,
        pdf: false,
    });

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
                    {/* Action Buttons */}
                    <div className="flex justify-end gap-2 no-print">
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handlePrint}
                            disabled={isLoading.print}
                        >
                            <Printer className="w-4 h-4 mr-2" />
                            {isLoading.print ? "Printing..." : "Print Payslip"}
                        </Button>
                        <Link href={route("payslips.edit", payroll.id)}>
                            <Button variant="outline" size="sm">
                                <Edit className="w-4 h-4 mr-2" />
                                Edit
                            </Button>
                        </Link>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={handleDownloadPDF}
                            disabled={isLoading.pdf}
                        >
                            <Download className="w-4 h-4 mr-2" />
                            {isLoading.pdf ? "Downloading..." : "Download PDF"}
                        </Button>
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
                                    payroll?.status === 2
                                        ? "success"
                                        : "warning"
                                }
                                className="mt-2"
                            >
                                {payroll?.status === 2 ? "Paid" : "Unpaid"}
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
        </AuthenticatedLayout>
    );
}

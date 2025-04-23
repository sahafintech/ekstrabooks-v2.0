import React, { useState } from "react";
import { Link, router } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Button } from "@/Components/ui/button";
import { Card, CardContent, CardHeader, CardTitle } from "@/Components/ui/card";
import { Separator } from "@/Components/ui/separator";
import { formatCurrency } from "@/lib/utils";
import { format } from "date-fns";
import { Download, Edit, Printer } from "lucide-react";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/table";
import { Badge } from "@/Components/ui/badge";

export default function View({ payroll, working_days, absence, allowances, deductions, advance }) {
    const [isPrinting, setIsPrinting] = useState(false);

    const calculateTotalAllowances = () => {
        if (!allowances || !Array.isArray(allowances)) return 0;
        return allowances.reduce((sum, item) => sum + parseFloat(item.amount || 0), 0);
    };

    const calculateTotalDeductions = () => {
        if (!deductions || !Array.isArray(deductions)) return 0;
        return deductions.reduce((sum, item) => sum + parseFloat(item.amount || 0), 0);
    };

    const calculateNetSalary = () => {
        const basicSalary = parseFloat(payroll?.current_salary || 0);
        const totalAllowances = calculateTotalAllowances();
        const totalDeductions = calculateTotalDeductions();
        const advanceAmount = parseFloat(advance || 0);
        
        return basicSalary + totalAllowances - totalDeductions - advanceAmount;
    };

    const handlePrint = () => {
        setIsPrinting(true);
        const printWindow = window.open('', '_blank');
        
        // Create print content
        printWindow.document.write(`
            <html>
                <head>
                    <title>Payslip: ${payroll?.staff?.name} - ${payroll?.month}/${payroll?.year}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
                        .header { text-align: center; margin-bottom: 20px; }
                        .payslip-title { font-size: 24px; font-weight: bold; margin-bottom: 5px; }
                        .payslip-period { font-size: 14px; margin-bottom: 20px; }
                        .company-name { font-size: 18px; font-weight: bold; }
                        .section { margin-bottom: 20px; }
                        .section-title { font-size: 16px; font-weight: bold; border-bottom: 1px solid #ddd; padding-bottom: 5px; margin-bottom: 10px; }
                        .employee-info { display: flex; justify-content: space-between; margin-bottom: 20px; }
                        .info-group { margin-bottom: 15px; }
                        .info-label { font-weight: bold; font-size: 12px; color: #666; }
                        .info-value { font-size: 14px; }
                        table { width: 100%; border-collapse: collapse; }
                        th, td { padding: 8px; text-align: left; border-bottom: 1px solid #ddd; }
                        th { background-color: #f5f5f5; }
                        .amount { text-align: right; }
                        .total-row { font-weight: bold; }
                        .footer { margin-top: 30px; text-align: center; font-size: 12px; color: #666; }
                        .signature-section { margin-top: 50px; display: flex; justify-content: space-between; }
                        .signature-box { width: 45%; text-align: center; }
                        .signature-line { border-top: 1px solid #000; margin-top: 50px; }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <div class="company-name">Company Name</div>
                        <div class="payslip-title">PAYSLIP</div>
                        <div class="payslip-period">For the period: ${payroll?.month}/${payroll?.year}</div>
                    </div>
                    
                    <div class="employee-info">
                        <div>
                            <div class="info-group">
                                <div class="info-label">Employee ID</div>
                                <div class="info-value">${payroll?.staff?.employee_id || ''}</div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Employee Name</div>
                                <div class="info-value">${payroll?.staff?.name || ''}</div>
                            </div>
                        </div>
                        <div>
                            <div class="info-group">
                                <div class="info-label">Department</div>
                                <div class="info-value">${payroll?.staff?.department || ''}</div>
                            </div>
                            <div class="info-group">
                                <div class="info-label">Designation</div>
                                <div class="info-value">${payroll?.staff?.designation || ''}</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="section">
                        <div class="section-title">Earnings</div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="amount">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Basic Salary</td>
                                    <td class="amount">${formatCurrency({ amount: payroll?.current_salary || 0 })}</td>
                                </tr>
                                ${allowances?.map(item => `
                                <tr>
                                    <td>${item.description || ''}</td>
                                    <td class="amount">${formatCurrency({ amount: item.amount || 0 })}</td>
                                </tr>
                                `).join('') || ''}
                                <tr class="total-row">
                                    <td>Total Earnings</td>
                                    <td class="amount">${formatCurrency({ amount: parseFloat(payroll?.current_salary || 0) + calculateTotalAllowances() })}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="section">
                        <div class="section-title">Deductions</div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Description</th>
                                    <th class="amount">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                ${deductions?.map(item => `
                                <tr>
                                    <td>${item.description || ''}</td>
                                    <td class="amount">${formatCurrency({ amount: item.amount || 0 })}</td>
                                </tr>
                                `).join('') || ''}
                                ${advance > 0 ? `
                                <tr>
                                    <td>Advance</td>
                                    <td class="amount">${formatCurrency({ amount: advance || 0 })}</td>
                                </tr>
                                ` : ''}
                                <tr class="total-row">
                                    <td>Total Deductions</td>
                                    <td class="amount">${formatCurrency({ amount: calculateTotalDeductions() + parseFloat(advance || 0) })}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="section">
                        <div class="section-title">Summary</div>
                        <table>
                            <tbody>
                                <tr>
                                    <td>Total Earnings</td>
                                    <td class="amount">${formatCurrency({ amount: parseFloat(payroll?.current_salary || 0) + calculateTotalAllowances() })}</td>
                                </tr>
                                <tr>
                                    <td>Total Deductions</td>
                                    <td class="amount">${formatCurrency({ amount: calculateTotalDeductions() + parseFloat(advance || 0) })}</td>
                                </tr>
                                <tr class="total-row">
                                    <td>Net Salary</td>
                                    <td class="amount">${formatCurrency({ amount: calculateNetSalary() })}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="section">
                        <div class="section-title">Attendance Summary</div>
                        <table>
                            <tbody>
                                <tr>
                                    <td>Working Days</td>
                                    <td class="amount">${working_days || 0}</td>
                                </tr>
                                <tr>
                                    <td>Absent Days</td>
                                    <td class="amount">${absence || 0}</td>
                                </tr>
                                <tr>
                                    <td>Present Days</td>
                                    <td class="amount">${(working_days || 0) - (absence || 0)}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="signature-section">
                        <div class="signature-box">
                            <div class="signature-line"></div>
                            <p>Employee Signature</p>
                        </div>
                        <div class="signature-box">
                            <div class="signature-line"></div>
                            <p>Employer Signature</p>
                        </div>
                    </div>
                    
                    <div class="footer">
                        <p>This is a computer generated payslip and does not require a signature.</p>
                    </div>
                </body>
            </html>
        `);
        
        printWindow.document.close();
        printWindow.focus();
        
        // Print after window loads
        printWindow.onload = function() {
            printWindow.print();
            printWindow.onafterprint = function() {
                printWindow.close();
                setIsPrinting(false);
            };
        };
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader page="Payrolls" subpage="View Payslip" url="payslips.index" />
                
                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    {/* Action Buttons */}
                    <div className="flex justify-end gap-2">
                        <Button variant="outline" size="sm" onClick={handlePrint} disabled={isPrinting}>
                            <Printer className="w-4 h-4 mr-2" />
                            {isPrinting ? "Printing..." : "Print Payslip"}
                        </Button>
                        <Link href={route("payslips.edit", payroll.id)}>
                            <Button variant="outline" size="sm">
                                <Edit className="w-4 h-4 mr-2" />
                                Edit
                            </Button>
                        </Link>
                        <Button variant="outline" size="sm">
                            <Download className="w-4 h-4 mr-2" />
                            Download PDF
                        </Button>
                    </div>
                    
                    {/* Payslip Card */}
                    <Card className="shadow-sm">
                        <CardHeader className="pb-2">
                            <div className="flex justify-between items-center">
                                <CardTitle>Payslip</CardTitle>
                                <Badge variant={payroll?.status === 2 ? "success" : "warning"}>
                                    {payroll?.status === 2 ? "Paid" : "Unpaid"}
                                </Badge>
                            </div>
                            <div className="text-sm text-muted-foreground">
                                Period: {payroll?.month}/{payroll?.year}
                            </div>
                        </CardHeader>
                        <CardContent>
                            {/* Employee Information */}
                            <div className="grid grid-cols-1 md:grid-cols-12 gap-4 mb-6">
                                <div className="md:col-span-6 space-y-2">
                                    <h3 className="text-sm font-medium">Employee Information</h3>
                                    <div className="grid grid-cols-2 gap-2">
                                        <div>
                                            <div className="text-xs text-muted-foreground">Employee ID</div>
                                            <div className="text-sm font-medium">{payroll?.staff?.employee_id}</div>
                                        </div>
                                        <div>
                                            <div className="text-xs text-muted-foreground">Employee Name</div>
                                            <div className="text-sm font-medium">{payroll?.staff?.name}</div>
                                        </div>
                                        <div>
                                            <div className="text-xs text-muted-foreground">Department</div>
                                            <div className="text-sm font-medium">{payroll?.staff?.department || 'N/A'}</div>
                                        </div>
                                        <div>
                                            <div className="text-xs text-muted-foreground">Designation</div>
                                            <div className="text-sm font-medium">{payroll?.staff?.designation || 'N/A'}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <Separator className="my-4" />
                            
                            {/* Attendance Summary */}
                            <div className="mb-6">
                                <h3 className="text-sm font-medium mb-3">Attendance Summary</h3>
                                <div className="grid grid-cols-3 gap-4">
                                    <div className="bg-slate-50 p-3 rounded-md text-center">
                                        <div className="text-xs text-muted-foreground mb-1">Working Days</div>
                                        <div className="text-xl font-semibold">{working_days || 0}</div>
                                    </div>
                                    <div className="bg-slate-50 p-3 rounded-md text-center">
                                        <div className="text-xs text-muted-foreground mb-1">Absences</div>
                                        <div className="text-xl font-semibold">{absence || 0}</div>
                                    </div>
                                    <div className="bg-slate-50 p-3 rounded-md text-center">
                                        <div className="text-xs text-muted-foreground mb-1">Present Days</div>
                                        <div className="text-xl font-semibold">{(working_days || 0) - (absence || 0)}</div>
                                    </div>
                                </div>
                            </div>
                            
                            {/* Earnings and Deductions Tables */}
                            <div className="grid grid-cols-1 md:grid-cols-12 gap-6">
                                {/* Earnings Table */}
                                <div className="md:col-span-6">
                                    <h3 className="text-sm font-medium mb-3">Earnings</h3>
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Description</TableHead>
                                                <TableHead className="text-right">Amount</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            <TableRow>
                                                <TableCell>Basic Salary</TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency({ amount: payroll?.current_salary || 0 })}
                                                </TableCell>
                                            </TableRow>
                                            
                                            {allowances && allowances.map((item, index) => (
                                                <TableRow key={`allowance-${index}`}>
                                                    <TableCell>{item.description || 'Allowance'}</TableCell>
                                                    <TableCell className="text-right">
                                                        {formatCurrency({ amount: item.amount || 0 })}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                            
                                            <TableRow className="font-medium">
                                                <TableCell>Total Earnings</TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency({ 
                                                        amount: parseFloat(payroll?.current_salary || 0) + calculateTotalAllowances() 
                                                    })}
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                                
                                {/* Deductions Table */}
                                <div className="md:col-span-6">
                                    <h3 className="text-sm font-medium mb-3">Deductions</h3>
                                    <Table>
                                        <TableHeader>
                                            <TableRow>
                                                <TableHead>Description</TableHead>
                                                <TableHead className="text-right">Amount</TableHead>
                                            </TableRow>
                                        </TableHeader>
                                        <TableBody>
                                            {deductions && deductions.map((item, index) => (
                                                <TableRow key={`deduction-${index}`}>
                                                    <TableCell>{item.description || 'Deduction'}</TableCell>
                                                    <TableCell className="text-right">
                                                        {formatCurrency({ amount: item.amount || 0 })}
                                                    </TableCell>
                                                </TableRow>
                                            ))}
                                            
                                            {advance > 0 && (
                                                <TableRow>
                                                    <TableCell>Advance</TableCell>
                                                    <TableCell className="text-right">
                                                        {formatCurrency({ amount: advance || 0 })}
                                                    </TableCell>
                                                </TableRow>
                                            )}
                                            
                                            <TableRow className="font-medium">
                                                <TableCell>Total Deductions</TableCell>
                                                <TableCell className="text-right">
                                                    {formatCurrency({ 
                                                        amount: calculateTotalDeductions() + parseFloat(advance || 0) 
                                                    })}
                                                </TableCell>
                                            </TableRow>
                                        </TableBody>
                                    </Table>
                                </div>
                            </div>
                            
                            <Separator className="my-6" />
                            
                            {/* Net Salary Summary */}
                            <div className="bg-slate-50 p-4 rounded-md">
                                <div className="grid grid-cols-1 md:grid-cols-12 gap-4">
                                    <div className="md:col-span-6">
                                        <div className="text-sm font-medium">Summary</div>
                                        <div className="grid grid-cols-2 gap-2 mt-2">
                                            <div className="text-xs text-muted-foreground">Total Earnings</div>
                                            <div className="text-sm font-medium text-right">
                                                {formatCurrency({ 
                                                    amount: parseFloat(payroll?.current_salary || 0) + calculateTotalAllowances() 
                                                })}
                                            </div>
                                            
                                            <div className="text-xs text-muted-foreground">Total Deductions</div>
                                            <div className="text-sm font-medium text-right">
                                                {formatCurrency({ 
                                                    amount: calculateTotalDeductions() + parseFloat(advance || 0) 
                                                })}
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div className="md:col-span-6">
                                        <div className="text-sm font-medium text-muted-foreground">Net Salary</div>
                                        <div className="text-2xl font-bold mt-2">
                                            {formatCurrency({ amount: calculateNetSalary() })}
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            {/* Footer Note */}
                            <div className="mt-6 text-xs text-center text-muted-foreground">
                                This is a computer-generated payslip and does not require a signature.
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
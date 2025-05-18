import React, { useState } from "react";
import { Link, router } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import PageHeader from "@/Components/PageHeader";
import { Button } from "@/Components/ui/button";
import { Separator } from "@/Components/ui/separator";
import { formatCurrency } from "@/lib/utils";
import { Download, Edit, Printer } from "lucide-react";
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from "@/Components/ui/table";
import { Badge } from "@/Components/ui/badge";
import { toast } from "sonner";

export default function View({ payroll, working_days, absence, allowances, deductions, advance }) {
    const [isLoading, setIsLoading] = useState({
        print: false,
        pdf: false
    });

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
        setIsLoading(prev => ({ ...prev, print: true }));
        setTimeout(() => {
            window.print();
            setIsLoading(prev => ({ ...prev, print: false }));
        }, 300);
    };

    const handleDownloadPDF = async () => {
        setIsLoading(prev => ({ ...prev, pdf: true }));
        try {
            // Dynamically import the required libraries
            const html2canvas = (await import('html2canvas')).default;
            const { jsPDF } = await import('jspdf');

            // Get the content element
            const content = document.querySelector('.print-container');
            if (!content) {
                throw new Error("Content element not found");
            }
            
            // Create a canvas from the content
            const canvas = await html2canvas(content, {
                scale: 4,
                useCORS: true, // Enable CORS for images
                logging: false,
                windowWidth: content.scrollWidth,
                windowHeight: content.scrollHeight,
                allowTaint: true,
                backgroundColor: '#ffffff'
            });

            // Calculate dimensions
            const imgWidth = 210; // A4 width in mm
            const pageHeight = 297; // A4 height in mm
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            
            // Create PDF with higher quality
            const pdf = new jsPDF('p', 'mm', 'a4');
            let heightLeft = imgHeight;
            let position = 0;
            let pageData = canvas.toDataURL('image/jpeg', 1.0);

            // Add first page
            pdf.addImage(pageData, 'JPEG', 0, position, imgWidth, imgHeight);
            heightLeft -= pageHeight;

            // Add subsequent pages if content is longer than one page
            while (heightLeft >= 0) {
                position = heightLeft - imgHeight;
                pdf.addPage();
                pdf.addImage(pageData, 'JPEG', 0, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
            }

            // Save the PDF
            pdf.save(`Payslip_${payroll?.staff?.name}_${payroll?.month}_${payroll?.year}.pdf`);
        } catch (error) {
            console.error('Error generating PDF:', error);
            toast.error('Failed to generate PDF. Please try again.');
        } finally {
            setIsLoading(prev => ({ ...prev, pdf: false }));
        }
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <PageHeader page="Payrolls" subpage="View Payslip" url="payslips.index" />
                
                <div className="flex flex-1 flex-col gap-4 p-4 pt-0">
                    {/* Action Buttons */}
                    <div className="flex justify-end gap-2">
                        <Button variant="outline" size="sm" onClick={handlePrint} disabled={isLoading.print}>
                            <Printer className="w-4 h-4 mr-2" />
                            {isLoading.print ? "Printing..." : "Print Payslip"}
                        </Button>
                        <Link href={route("payslips.edit", payroll.id)}>
                            <Button variant="outline" size="sm">
                                <Edit className="w-4 h-4 mr-2" />
                                Edit
                            </Button>
                        </Link>
                        <Button variant="outline" size="sm" onClick={handleDownloadPDF} disabled={isLoading.pdf}>
                            <Download className="w-4 h-4 mr-2" />
                            {isLoading.pdf ? "Downloading..." : "Download PDF"}
                        </Button>
                    </div>
                    
                    {/* Payslip Card */}
                    <div className="shadow-sm p-3 print-container">
                        <div className="pb-2">
                            <div className="flex justify-between items-center">
                                <h3 className="text-sm font-medium">Payslip</h3>
                                <Badge variant={payroll?.status === 2 ? "success" : "warning"}>
                                    {payroll?.status === 2 ? "Paid" : "Unpaid"}
                                </Badge>
                            </div>
                            <div className="text-sm text-muted-foreground">
                                Period: {payroll?.month}/{payroll?.year}
                            </div>
                        </div>
                        <div className="p-4">
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
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}
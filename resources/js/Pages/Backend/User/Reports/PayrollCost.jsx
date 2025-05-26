import React from "react";
import { Head, useForm } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { toast } from "sonner";
import {
    ReportTable,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
    TableFooter,
} from "@/Components/shared/ReportTable";
import { Toaster } from "@/Components/ui/toaster";
import PageHeader from "@/Components/PageHeader";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import { formatCurrency } from "@/lib/utils";

export default function PayrollCost({ report_data, month, year, currency }) {
    const { data, setData, post, processing } = useForm({
        month: month || new Date().getMonth() + 1, // Month is 1-indexed in form
        year: year || new Date().getFullYear(),
    });

    const handleExport = () => {
        window.location.href = route("reports.payroll_cost_export");
    };

    const handleGenerate = (e) => {
        e.preventDefault();
        post(route("reports.payroll_cost"), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.success("Report Generated successfully");
            },
        });
    };

    const PayrollStatusBadge = ({ status }) => {
        const statusMap = {
            0: { label: "Draft", className: "text-gray-600" },
            1: { label: "Approved", className: "text-blue-600" },
            2: { label: "Accrued", className: "text-green-600" },
            3: { label: "Paid", className: "text-green-600" },
        };

        return (
            <span className={statusMap[status].className}>
                {statusMap[status].label}
            </span>
        );
    };

    const handlePrint = () => {
        // Create a new window for printing
        const printWindow = window.open('', '_blank', 'width=800,height=600');

        // Generate CSS for the print window
        const style = `
            <style>
                body { font-family: Arial, sans-serif; margin: 20px; }
                table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
                th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                th { background-color: #f2f2f2; }
                .report-header { text-align: center; margin-bottom: 20px; }
                .report-header h1 { margin: 0; font-size: 24px; }
                .report-header p { margin: 5px 0; }
                .summary-table { margin-top: 30px; }
                .status-accrued { color: #e11d48; }
                .status-approved { color: #2563eb; }
                .text-right { text-align: right; }
                .totals-row { font-weight: bold; background-color: #f8f9fa; }
                .report-footer { margin-top: 50px; font-size: 12px; text-align: center; color: #777; }
                @media print {
                    .no-print { display: none; }
                }
            </style>
        `;

        // Generate table rows for each employee
        const employeeRows = report_data?.payroll?.map((payroll, index) => {
            return `
                <tr>
                    <td>${index + 1}</td>
                    <td>${payroll.staff?.employee_id || 'N/A'}</td>
                    <td>${payroll.staff?.name || 'N/A'}</td>
                    <td class="text-right">${formatCurrency({ amount: payroll.current_salary || 0, currency })}</td>
                    <td class="text-right">${formatCurrency({ amount: payroll.total_allowance || 0, currency })}</td>
                    <td class="text-right">${formatCurrency({ amount: payroll.total_deduction || 0, currency })}</td>
                    <td class="text-right">${formatCurrency({ amount: payroll.tax_amount || 0, currency })}</td>
                    <td class="text-right">${formatCurrency({ amount: payroll.advance || 0, currency })}</td>
                    <td class="text-right">${formatCurrency({ amount: payroll.net_salary || 0, currency })}</td>
                    <td>${<PayrollStatusBadge status={payroll.status} />}</td>
                </tr>
            `;
        }).join('');

        // Generate the HTML content for the print window
        const content = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Payroll Cost Report</title>
                ${style}
            </head>
            <body>
                <div class="report-header">
                    <h1>Payroll Cost Report</h1>
                    <p>Month: ${getMonthName(data.month)} ${data.year}</p>
                    <p>Generated: ${new Date().toLocaleDateString()}</p>
                </div>

                <table class="summary-table">
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>EMPLOYEE ID</th>
                            <th>NAME</th>
                            <th>BASIC SALARY</th>
                            <th>ALLOWANCES</th>
                            <th>DEDUCTIONS</th>
                            <th>TOTAL TAX</th>
                            <th>SALARY ADVANCE</th>
                            <th>NET SALARY</th>
                            <th>STATUS</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${employeeRows}
                    </tbody>
                    <tfoot>
                        <tr class="totals-row">
                            <td colspan="3">Total</td>
                            <td class="text-right">${formatCurrency({ amount: report_data.total_basicsalary || 0, currency })}</td>
                            <td class="text-right">${formatCurrency({ amount: report_data.total_allowance || 0, currency })}</td>
                            <td class="text-right">${formatCurrency({ amount: report_data.total_deduction || 0, currency })}</td>
                            <td class="text-right">${formatCurrency({ amount: report_data.total_tax || 0, currency })}</td>
                            <td class="text-right">${formatCurrency({ amount: report_data.total_advance || 0, currency })}</td>
                            <td class="text-right">${formatCurrency({ amount: report_data.total_netsalary || 0, currency })}</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>

                <div class="report-footer">
                    <p>This is a computer-generated report and requires no signature.</p>
                </div>
            </body>
            </html>
        `;

        // Write the content to the print window
        printWindow.document.open();
        printWindow.document.write(content);
        printWindow.document.close();

        // Trigger the print dialog after the content is loaded
        printWindow.onload = function () {
            printWindow.print();
            // Close the window after printing
            printWindow.onafterprint = function () {
                printWindow.close();
            };
        };
    };

    // Helper function to convert month number to month name
    const getMonthName = (monthNumber) => {
        const months = [
            "January", "February", "March", "April", "May", "June",
            "July", "August", "September", "October", "November", "December"
        ];
        return months[monthNumber - 1];
    };

    // Generate an array of years from 10 years ago to 10 years from now
    const currentYear = new Date().getFullYear();
    const yearOptions = Array.from({ length: 21 }, (_, index) => currentYear - 10 + index);

    return (
        <AuthenticatedLayout>
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Payroll Cost"
                        subpage="List"
                        url="reports.payroll_cost"
                    />

                    <div className="p-4">
                        <form onSubmit={handleGenerate}>
                            <div className="flex items-end gap-2 flex-wrap">
                                <div>
                                    <label className="block text-sm font-medium pb-1">Month</label>
                                    <SearchableCombobox
                                        value={data.month.toString()}
                                        onChange={(value) => setData('month', parseInt(value))}
                                        options={Array.from({ length: 12 }, (_, i) => i + 1).map((m) => ({
                                            name: getMonthName(m),
                                            id: m.toString(),
                                        }))}
                                    />
                                </div>

                                <div>
                                    <label className="block text-sm font-medium pb-1">Year</label>
                                    <SearchableCombobox
                                        value={data.year.toString()}
                                        onChange={(value) => setData('year', parseInt(value))}
                                        options={yearOptions.map((year) => ({
                                            id: year,
                                            name: year.toString(),
                                        }))}
                                    />
                                </div>

                                <Button type="submit" disabled={processing}>{processing ? 'Generating...' : 'Generate'}</Button>
                            </div>
                        </form>

                        <div className="flex items-center gap-2 mt-6">
                            <Button variant="outline" onClick={handlePrint}>
                                Print
                            </Button>
                            <Button variant="outline" onClick={handleExport}>Export</Button>
                        </div>

                        <div className="printable-table mt-4">
                            <h2 className="text-xl font-bold mb-4">Payroll Cost - {getMonthName(data.month)} {data.year}</h2>

                            <ReportTable>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-12">NO</TableHead>
                                        <TableHead>EMPLOYEE ID</TableHead>
                                        <TableHead>NAME</TableHead>
                                        <TableHead>BASIC SALARY</TableHead>
                                        <TableHead>ALLOWANCES</TableHead>
                                        <TableHead>DEDUCTIONS</TableHead>
                                        <TableHead>TOTAL TAX</TableHead>
                                        <TableHead>SALARY ADVANCE</TableHead>
                                        <TableHead>NET SALARY</TableHead>
                                        <TableHead>STATUS</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {report_data?.payroll?.map((payroll, index) => (
                                        <TableRow key={payroll.id || index}>
                                            <TableCell>{index + 1}</TableCell>
                                            <TableCell>{payroll.staff?.employee_id || 'N/A'}</TableCell>
                                            <TableCell>{payroll.staff?.name || 'N/A'}</TableCell>
                                            <TableCell className="text-right">{formatCurrency({ amount: payroll.current_salary || 0, currency })}</TableCell>
                                            <TableCell className="text-right">{formatCurrency({ amount: payroll.total_allowance || 0, currency })}</TableCell>
                                            <TableCell className="text-right">{formatCurrency({ amount: payroll.total_deduction || 0, currency })}</TableCell>
                                            <TableCell className="text-right">{formatCurrency({ amount: payroll.tax_amount || 0, currency })}</TableCell>
                                            <TableCell className="text-right">{formatCurrency({ amount: payroll.advance || 0, currency })}</TableCell>
                                            <TableCell className="text-right">{formatCurrency({ amount: payroll.net_salary || 0, currency })}</TableCell>
                                            <TableCell>
                                                <PayrollStatusBadge status={payroll.status} />
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                                <TableFooter>
                                    <TableRow>
                                        <TableCell colSpan={3} className="font-bold">Total</TableCell>
                                        <TableCell className="text-right font-bold">{formatCurrency({ amount: report_data.total_basicsalary || 0, currency })}</TableCell>
                                        <TableCell className="text-right font-bold">{formatCurrency({ amount: report_data.total_allowance || 0, currency })}</TableCell>
                                        <TableCell className="text-right font-bold">{formatCurrency({ amount: report_data.total_deduction || 0, currency })}</TableCell>
                                        <TableCell className="text-right font-bold">{formatCurrency({ amount: report_data.total_tax || 0, currency })}</TableCell>
                                        <TableCell className="text-right font-bold">{formatCurrency({ amount: report_data.total_advance || 0, currency })}</TableCell>
                                        <TableCell className="text-right font-bold">{formatCurrency({ amount: report_data.total_netsalary || 0, currency })}</TableCell>
                                        <TableCell></TableCell>
                                    </TableRow>
                                </TableFooter>
                            </ReportTable>
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}

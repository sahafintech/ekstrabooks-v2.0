import React from "react";
import { Head, useForm } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { toast } from "sonner";
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from "@/Components/ui/table";
import { Toaster } from "@/Components/ui/toaster";
import PageHeader from "@/Components/PageHeader";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/Components/ui/select";
import { cn, formatCurrency } from "@/lib/utils";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";

export default function PayrollSummary({ report_data, month, year, currency }) {
    const { data, setData, post, processing } = useForm({
        month: month || new Date().getMonth() + 1, // Month is 1-indexed in form
        year: year || new Date().getFullYear(),
    });

    const handleExport = () => {
        window.location.href = route("reports.payroll_summary_export");
    };

    const handleGenerate = (e) => {
        e.preventDefault();
        post(route("reports.payroll_summary"), {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.success("Report Generated successfully");
            },
        });
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
                .centered { text-align: center; }
                .report-footer { margin-top: 50px; font-size: 12px; text-align: center; color: #777; }
                @media print {
                    .no-print { display: none; }
                }
            </style>
        `;

        // Generate the HTML content for the print window
        const content = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Payroll Summary Report</title>
                ${style}
            </head>
            <body>
                <div class="report-header">
                    <h1>Payroll Summary Report</h1>
                    <p>Month: ${getMonthName(data.month)} ${data.year}</p>
                    <p>Generated: ${new Date().toLocaleDateString()}</p>
                </div>

                <table class="summary-table">
                    <thead>
                        <tr>
                            <th>Pay Component</th>
                            <th>Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>Basic Salary</td>
                            <td>${formatCurrency({ amount: report_data.current_salary || 0, currency })}</td>
                        </tr>
                        <tr>
                            <td>Allowance</td>
                            <td>${formatCurrency({ amount: report_data.total_allowance || 0, currency })}</td>
                        </tr>
                        <tr>
                            <td>Deduction</td>
                            <td>${formatCurrency({ amount: report_data.total_deduction || 0, currency })}</td>
                        </tr>
                        <tr>
                            <td>Net Salary</td>
                            <td>${formatCurrency({ amount: report_data.net_salary || 0, currency })}</td>
                        </tr>
                        <tr>
                            <td>Total Tax</td>
                            <td>${formatCurrency({ amount: report_data.total_tax || 0, currency })}</td>
                        </tr>
                    </tbody>
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
            <Head title="Payroll Summary Report" />
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Payroll Summary"
                        subpage="List"
                        url="reports.payroll_summary"
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
                            <h2 className="text-xl font-bold mb-4">Payroll Summary - {getMonthName(data.month)} {data.year}</h2>

                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="w-1/2">Pay Component</TableHead>
                                        <TableHead className="w-1/2">Amount</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    <TableRow>
                                        <TableCell className="font-medium">Basic Salary</TableCell>
                                        <TableCell>{formatCurrency({ amount: report_data.current_salary || 0, currency })}</TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell className="font-medium">Allowance</TableCell>
                                        <TableCell>{formatCurrency({ amount: report_data.total_allowance || 0, currency })}</TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell className="font-medium">Deduction</TableCell>
                                        <TableCell>{formatCurrency({ amount: report_data.total_deduction || 0, currency })}</TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell className="font-medium">Net Salary</TableCell>
                                        <TableCell>{formatCurrency({ amount: report_data.net_salary || 0, currency })}</TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableCell className="font-medium">Total Tax</TableCell>
                                        <TableCell>{formatCurrency({ amount: report_data.total_tax || 0, currency })}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>

                            <div className="mt-8">
                                <h3 className="text-lg font-medium mb-2">Summary</h3>
                                <p>Total payroll cost for {getMonthName(data.month)} {data.year}:
                                    <span className="font-bold">{formatCurrency({ amount: report_data.net_salary || 0, currency })}</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}

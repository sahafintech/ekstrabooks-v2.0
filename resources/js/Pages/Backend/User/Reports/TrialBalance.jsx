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
import { Calendar } from "@/Components/ui/calendar";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/Components/ui/popover";
import { CalendarIcon } from "lucide-react";
import { format } from "date-fns";
import { cn, formatCurrency } from "@/lib/utils";

export default function TrialBalance({ report_data, date1, date2, business_name }) {
    // Function to render account tables by type
    const renderAccountTypeTable = (reportData, accountType, title, isDebitMinusCredit = true) => {
        if (!reportData[accountType] || reportData[accountType].length === 0) return null;

        return (
            <div className="mb-8">
                <h3 className="text-left underline font-bold mb-2">{title}</h3>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Account Code</TableHead>
                            <TableHead>Account Name</TableHead>
                            <TableHead className="text-right">Debit</TableHead>
                            <TableHead className="text-right">Credit</TableHead>
                            <TableHead className="text-right">Balance</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {reportData[accountType].map((account) => {
                            // Calculate balance based on account type
                            const balance = isDebitMinusCredit
                                ? account.dr_amount - account.cr_amount
                                : account.cr_amount - account.dr_amount;

                            return (
                                <TableRow key={account.id}>
                                    <TableCell>{account.account_code || 'N/A'}</TableCell>
                                    <TableCell>{account.account_name || 'N/A'}</TableCell>
                                    <TableCell className="text-right">{formatCurrency({ amount: account.dr_amount || 0 })}</TableCell>
                                    <TableCell className="text-right">{formatCurrency({ amount: account.cr_amount || 0 })}</TableCell>
                                    <TableCell className="text-right">{formatCurrency({ amount: balance || 0 })}</TableCell>
                                </TableRow>
                            );
                        })}
                    </TableBody>
                </Table>
            </div>
        );
    };

    const { data, setData, post, processing } = useForm({
        date1: date1,
        date2: date2,
    });

    const handleExport = () => {
        window.location.href = route("reports.trial_balance_export");
    };

    const handleGenerate = (e) => {
        e.preventDefault();
        post(route("reports.trial_balance"), {
            date1: data.date1,
            date2: data.date2,
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
                h2, h1 { text-align: center; margin-bottom: 20px; }
                h3 { margin-top: 20px; margin-bottom: 10px; font-size: 18px; text-decoration: underline; }
                .text-right { text-align: right; }
                .total-row { font-weight: bold; background-color: #f9f9f9; }
                .section { margin-bottom: 30px; }
                .grand-total { font-size: 16px; font-weight: bold; margin-top: 20px; }
            </style>
        `;

        // Start building the HTML content for the print window
        let printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Trial Balance</title>
                ${style}
            </head>
            <body>
                <h1>${business_name}</h1>
                <h2>Trial Balance (${data.date1} - ${data.date2})</h2>
        `;

        // Function to create a table for each account type
        const createAccountTable = (accounts, title) => {
            if (!accounts || accounts.length === 0) return '';

            let tableHtml = `
                <div class="section">
                    <h3>${title}</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Account Code</th>
                                <th>Account Name</th>
                                <th class="text-right">Debit</th>
                                <th class="text-right">Credit</th>
                                <th class="text-right">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            // Add rows for this account type
            accounts.forEach(account => {
                const formattedDr = formatCurrency(account.dr_amount || 0);
                const formattedCr = formatCurrency(account.cr_amount || 0);

                // Calculate balance based on account type
                let balance;
                if (['Fixed Asset', 'Current Asset', 'Cost Of Sale', 'Direct Expenses', 'Other Expenses'].includes(title)) {
                    balance = account.dr_amount - account.cr_amount;
                } else {
                    balance = account.cr_amount - account.dr_amount;
                }

                const formattedBalance = formatCurrency(balance || 0);

                tableHtml += `
                    <tr>
                        <td>${account.account_code || 'N/A'}</td>
                        <td>${account.account_name || 'N/A'}</td>
                        <td class="text-right">${formattedDr}</td>
                        <td class="text-right">${formattedCr}</td>
                        <td class="text-right">${formattedBalance}</td>
                    </tr>
                `;
            });

            tableHtml += `
                        </tbody>
                    </table>
                </div>
            `;

            return tableHtml;
        };

        // Add each account section
        if (report_data.fixed_asset && report_data.fixed_asset.length > 0) {
            printContent += createAccountTable(report_data.fixed_asset, 'Fixed Asset');
        }

        if (report_data.current_asset && report_data.current_asset.length > 0) {
            printContent += createAccountTable(report_data.current_asset, 'Current Asset');
        }

        if (report_data.long_term_liability && report_data.long_term_liability.length > 0) {
            printContent += createAccountTable(report_data.long_term_liability, 'Long Term Liability');
        }

        if (report_data.current_liability && report_data.current_liability.length > 0) {
            printContent += createAccountTable(report_data.current_liability, 'Current Liability');
        }

        if (report_data.equity && report_data.equity.length > 0) {
            printContent += createAccountTable(report_data.equity, 'Equity');
        }

        if (report_data.sales_and_income && report_data.sales_and_income.length > 0) {
            printContent += createAccountTable(report_data.sales_and_income, 'Sales and Income');
        }

        if (report_data.cost_of_sale && report_data.cost_of_sale.length > 0) {
            printContent += createAccountTable(report_data.cost_of_sale, 'Cost Of Sale');
        }

        if (report_data.direct_expenses && report_data.direct_expenses.length > 0) {
            printContent += createAccountTable(report_data.direct_expenses, 'Direct Expenses');
        }

        if (report_data.other_expenses && report_data.other_expenses.length > 0) {
            printContent += createAccountTable(report_data.other_expenses, 'Other Expenses');
        }

        // Add grand totals
        const formattedTotalDebit = formatCurrency(report_data.total_debit || 0);
        const formattedTotalCredit = formatCurrency(report_data.total_credit || 0);

        printContent += `
            <div class="grand-total">
                <p>Total Debit: ${formattedTotalDebit}</p>
                <p>Total Credit: ${formattedTotalCredit}</p>
            </div>
        `;

        // Complete the HTML content
        printContent += `
            </body>
            </html>
        `;

        // Write the content to the print window and trigger print
        printWindow.document.open();
        printWindow.document.write(printContent);
        printWindow.document.close();

        // Wait for content to load before printing
        setTimeout(() => {
            printWindow.print();
            // Close the window after printing (optional, can be commented out if you want to keep it open)
            printWindow.onafterprint = function () {
                printWindow.close();
            };
        }, 300);
    };

    return (
        <AuthenticatedLayout>
            <Toaster />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Trial Balance"
                        subpage="List"
                        url="reports.trial_balance"
                    />
                    <div className="p-4">
                        <div className="flex flex-col justify-between items-start mb-6 gap-4">
                            <div className="flex flex-col md:flex-row gap-4">
                                <form onSubmit={handleGenerate} className="flex gap-2">
                                    <div className="flex items-center gap-2">
                                        <Popover>
                                            <PopoverTrigger asChild>
                                                <Button
                                                    variant="outline"
                                                    className={cn(
                                                        "w-full md:w-auto justify-start text-left font-normal",
                                                        !data.date1 && "text-muted-foreground"
                                                    )}
                                                >
                                                    <CalendarIcon className="mr-2 h-4 w-4" />
                                                    {data.date1 ? format(new Date(data.date1), "PPP") : <span>From date</span>}
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent className="w-auto p-0" align="start">
                                                <Calendar
                                                    mode="single"
                                                    selected={data.date1 ? new Date(data.date1) : undefined}
                                                    onSelect={(date) => setData('date1', date ? format(date, "yyyy-MM-dd") : '')}
                                                    initialFocus
                                                />
                                            </PopoverContent>
                                        </Popover>

                                        <Popover>
                                            <PopoverTrigger asChild>
                                                <Button
                                                    variant="outline"
                                                    className={cn(
                                                        "w-full md:w-auto justify-start text-left font-normal",
                                                        !data.date2 && "text-muted-foreground"
                                                    )}
                                                >
                                                    <CalendarIcon className="mr-2 h-4 w-4" />
                                                    {data.date2 ? format(new Date(data.date2), "PPP") : <span>To date</span>}
                                                </Button>
                                            </PopoverTrigger>
                                            <PopoverContent className="w-auto p-0" align="start">
                                                <Calendar
                                                    mode="single"
                                                    selected={data.date2 ? new Date(data.date2) : undefined}
                                                    onSelect={(date) => setData('date2', date ? format(date, "yyyy-MM-dd") : '')}
                                                    initialFocus
                                                />
                                            </PopoverContent>
                                        </Popover>

                                        <Button type="submit" disabled={processing}>{processing ? 'Generating...' : 'Generate'}</Button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div className="flex items-center gap-2 print-buttons">
                            <Button variant="outline" onClick={handlePrint}>
                                Print
                            </Button>
                            <Button variant="outline" onClick={handleExport}>Export</Button>
                        </div>

                        <div className="rounded-md border printable-table p-4 mt-4 w-full lg:w-[210mm] min-h-[297mm] mx-auto bg-white">
                            <div className="text-center p-4">
                                <h1 className="text-lg">{business_name}</h1>
                                <h2 className="font-bold">Trial Balance</h2>
                                <h2 className="flex items-center justify-center space-x-2">
                                    <span>From</span>
                                    <span>{format(new Date(data.date1), "dd/MM/yyyy")}</span>
                                    <span>To</span>
                                    <span>{format(new Date(data.date2), "dd/MM/yyyy")}</span>
                                </h2>
                            </div>

                            {/* Reusable component for account type tables */}
                            {renderAccountTypeTable(report_data, 'fixed_asset', 'Fixed Asset', true)}
                            {renderAccountTypeTable(report_data, 'current_asset', 'Current Asset', true)}
                            {renderAccountTypeTable(report_data, 'long_term_liability', 'Long Term Liability', false)}
                            {renderAccountTypeTable(report_data, 'current_liability', 'Current Liability', false)}
                            {renderAccountTypeTable(report_data, 'equity', 'Equity', false)}
                            {renderAccountTypeTable(report_data, 'sales_and_income', 'Sales and Income', false)}
                            {renderAccountTypeTable(report_data, 'cost_of_sale', 'Cost Of Sale', true)}
                            {renderAccountTypeTable(report_data, 'direct_expenses', 'Direct Expenses', true)}
                            {renderAccountTypeTable(report_data, 'other_expenses', 'Other Expenses', true)}
                            
                            {/* Grand Total Row */}
                            <Table className="mt-8">
                                <TableBody>
                                    <TableRow className="font-bold bg-slate-100">
                                        <TableCell colSpan={2} className="text-right">Grand Total:</TableCell>
                                        <TableCell className="text-right">{formatCurrency({ amount: report_data.total_debit || 0 })}</TableCell>
                                        <TableCell className="text-right">{formatCurrency({ amount: report_data.total_credit || 0 })}</TableCell>
                                        <TableCell className="text-right">{formatCurrency({ amount: report_data.total_debit - report_data.total_credit || 0 })}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}

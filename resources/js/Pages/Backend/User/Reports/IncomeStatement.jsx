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
import PageHeader from "@/Components/PageHeader";
import { formatCurrency, parseDateObject } from "@/lib/utils";
import DateTimePicker from "@/Components/DateTimePicker";

export default function IncomeStatement({ report_data, date1, date2, business_name }) {
    // Calculate summary values
    const getTotalIncome = () => {
        if (!report_data.sales_and_income || report_data.sales_and_income.length === 0) return 0;
        return report_data.sales_and_income.reduce((total, account) => {
            return total + (account.cr_amount - account.dr_amount);
        }, 0);
    };

    const getTotalCostOfSales = () => {
        if (!report_data.cost_of_sale || report_data.cost_of_sale.length === 0) return 0;
        return report_data.cost_of_sale.reduce((total, account) => {
            return total + (account.dr_amount - account.cr_amount);
        }, 0);
    };

    const getTotalDirectExpenses = () => {
        if (!report_data.direct_expenses || report_data.direct_expenses.length === 0) return 0;
        return report_data.direct_expenses.reduce((total, account) => {
            return total + (account.dr_amount - account.cr_amount);
        }, 0);
    };

    const getTotalOtherExpenses = () => {
        if (!report_data.other_expenses || report_data.other_expenses.length === 0) return 0;
        return report_data.other_expenses.reduce((total, account) => {
            return total + (account.dr_amount - account.cr_amount);
        }, 0);
    };

    // Calculate critical financial metrics
    const totalIncome = getTotalIncome();
    const totalCostOfSales = getTotalCostOfSales();
    const grossProfit = totalIncome - totalCostOfSales;
    const totalDirectExpenses = getTotalDirectExpenses();
    const operatingProfit = grossProfit - totalDirectExpenses;
    const totalOtherExpenses = getTotalOtherExpenses();
    const netProfit = operatingProfit - totalOtherExpenses;

    // Function to render account tables by type
    const renderAccountTypeTable = (reportData, accountType, title, isDebitMinusCredit = true) => {
        if (!reportData[accountType] || reportData[accountType].length === 0) return null;

        return (
            <>
                <h1 className="text-lg font-bold p-3 underline">{title}</h1>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Account Code</TableHead>
                            <TableHead>Account Name</TableHead>
                            <TableHead>Debit</TableHead>
                            <TableHead>Credit</TableHead>
                            <TableHead>Balance</TableHead>
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
                                    <TableCell>{formatCurrency({ amount: account.dr_amount || 0 })}</TableCell>
                                    <TableCell>{formatCurrency({ amount: account.cr_amount || 0 })}</TableCell>
                                    <TableCell>{formatCurrency({ amount: balance || 0 })}</TableCell>
                                </TableRow>
                            );
                        })}
                    </TableBody>
                </Table>
            </>
        );
    };

    // Create summary row
    const SummaryRow = ({ label, value, isTotal = false, positive = true }) => (
        <TableRow className={isTotal ? "font-bold bg-slate-100" : ""}>
            <TableCell colSpan={4} className="text-right">{label}</TableCell>
            <TableCell className={positive ? "text-green-600" : "text-red-600"}>
                {formatCurrency({ amount: value || 0 })}
            </TableCell>
        </TableRow>
    );

    const { data, setData, post, processing } = useForm({
        date1: parseDateObject(date1),
        date2: parseDateObject(date2),
    });

    const handleExport = () => {
        window.location.href = route("reports.income_statement_export");
    };

    const handleGenerate = (e) => {
        e.preventDefault();
        post(route("reports.income_statement"), {
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
                .positive { color: green; }
                .negative { color: red; }
                .total-row { font-weight: bold; background-color: #f9f9f9; }
                .section { margin-bottom: 30px; }
                .summary { font-size: 16px; font-weight: bold; margin-top: 20px; }
            </style>
        `;

        // Start building the HTML content for the print window
        let printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Income Statement</title>
                ${style}
            </head>
            <body>
                <h1>${business_name}</h1>
                <h2>Income Statement (${data.date1} - ${data.date2})</h2>
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
                                <th>Debit</th>
                                <th>Credit</th>
                                <th>Balance</th>
                            </tr>
                        </thead>
                        <tbody>
            `;

            // Add rows for this account type
            accounts.forEach(account => {
                let balance;
                let balanceClass = "";

                if (title === 'Sales and Income') {
                    balance = account.cr_amount - account.dr_amount;
                    balanceClass = balance >= 0 ? "positive" : "negative";
                } else {
                    balance = account.dr_amount - account.cr_amount;
                    balanceClass = balance >= 0 ? "" : "positive";
                }

                const formattedDr = formatCurrency(account.dr_amount || 0);
                const formattedCr = formatCurrency(account.cr_amount || 0);
                const formattedBalance = formatCurrency(balance || 0);

                tableHtml += `
                    <tr>
                        <td>${account.account_code || 'N/A'}</td>
                        <td>${account.account_name || 'N/A'}</td>
                        <td class="text-right">${formattedDr}</td>
                        <td class="text-right">${formattedCr}</td>
                        <td class="text-right ${balanceClass}">${formattedBalance}</td>
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
        if (report_data.sales_and_income && report_data.sales_and_income.length > 0) {
            printContent += createAccountTable(report_data.sales_and_income, 'Sales and Income');
        }

        // Add total income summary
        printContent += `
            <div class="summary">
                <p class="text-right">Total Income: <span class="positive">${formatCurrency(totalIncome)}</span></p>
            </div>
        `;

        if (report_data.cost_of_sale && report_data.cost_of_sale.length > 0) {
            printContent += createAccountTable(report_data.cost_of_sale, 'Cost Of Sale');
        }

        // Add gross profit summary
        printContent += `
            <div class="summary">
                <p class="text-right">Less: Cost of Sales: <span>${formatCurrency(totalCostOfSales)}</span></p>
                <p class="text-right">Gross Profit: <span class="${grossProfit >= 0 ? 'positive' : 'negative'}">${formatCurrency(grossProfit)}</span></p>
            </div>
        `;

        if (report_data.direct_expenses && report_data.direct_expenses.length > 0) {
            printContent += createAccountTable(report_data.direct_expenses, 'Direct Expenses');
        }

        // Add operating profit summary
        printContent += `
            <div class="summary">
                <p class="text-right">Less: Direct Expenses: <span>${formatCurrency(totalDirectExpenses)}</span></p>
                <p class="text-right">Operating Profit: <span class="${operatingProfit >= 0 ? 'positive' : 'negative'}">${formatCurrency(operatingProfit)}</span></p>
            </div>
        `;

        if (report_data.other_expenses && report_data.other_expenses.length > 0) {
            printContent += createAccountTable(report_data.other_expenses, 'Other Expenses');
        }

        // Add net profit summary
        printContent += `
            <div class="summary total-row">
                <p class="text-right">Less: Other Expenses: <span>${formatCurrency(totalOtherExpenses)}</span></p>
                <p class="text-right">Net Profit: <span class="${netProfit >= 0 ? 'positive' : 'negative'}">${formatCurrency(netProfit)}</span></p>
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
            // Close the window after printing (optional)
            printWindow.onafterprint = function () {
                printWindow.close();
            };
        }, 500);
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Reports"
                        subpage="Income Statement"
                        url="reports.income_statement"
                    />
                    <div className="p-4">
                        <div className="mx-auto">
                            <div className="flex flex-col justify-between items-start mb-6 gap-4">
                                <div className="flex flex-col md:flex-row gap-4">
                                    <form onSubmit={handleGenerate} className="flex gap-2">
                                        <div className="flex items-center gap-2">
                                            <DateTimePicker
                                                value={data.date1}
                                                onChange={(date) => setData("date1", date)}
                                                className="md:w-1/2 w-full"
                                                required
                                            />

                                            <DateTimePicker
                                                value={data.date2}
                                                onChange={(date) => setData("date2", date)}
                                                className="md:w-1/2 w-full"
                                                required
                                            />
                                            <Button type="submit" disabled={processing}>{processing ? 'Generating...' : 'Generate'}</Button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <div className="flex items-center gap-2 print-buttons mt-6">
                                <Button variant="outline" onClick={handlePrint}>
                                    Print
                                </Button>
                                <Button variant="outline" onClick={handleExport}>Export</Button>
                            </div>

                            <div className="rounded-md border printable-table mt-4">
                                {/* Income Section */}
                                {renderAccountTypeTable(report_data, 'sales_and_income', 'Sales and Income', false)}

                                {/* Income Summary */}
                                <Table className="mt-2">
                                    <TableBody>
                                        <SummaryRow label="Total Income:" value={totalIncome} positive={true} />
                                    </TableBody>
                                </Table>

                                {/* Cost of Sales Section */}
                                {renderAccountTypeTable(report_data, 'cost_of_sale', 'Cost Of Sale', true)}

                                {/* Gross Profit Summary */}
                                <Table className="mt-2">
                                    <TableBody>
                                        <SummaryRow label="Less: Cost of Sales:" value={totalCostOfSales} positive={false} />
                                        <SummaryRow label="Gross Profit:" value={grossProfit} isTotal={true} positive={grossProfit >= 0} />
                                    </TableBody>
                                </Table>

                                {/* Direct Expenses Section */}
                                {renderAccountTypeTable(report_data, 'direct_expenses', 'Direct Expenses', true)}

                                {/* Operating Profit Summary */}
                                <Table className="mt-2">
                                    <TableBody>
                                        <SummaryRow label="Less: Direct Expenses:" value={totalDirectExpenses} positive={false} />
                                        <SummaryRow label="Operating Profit:" value={operatingProfit} isTotal={true} positive={operatingProfit >= 0} />
                                    </TableBody>
                                </Table>

                                {/* Other Expenses Section */}
                                {renderAccountTypeTable(report_data, 'other_expenses', 'Other Expenses', true)}

                                {/* Net Profit Summary - Final Result */}
                                <Table className="mt-8">
                                    <TableBody>
                                        <SummaryRow label="Less: Other Expenses:" value={totalOtherExpenses} positive={false} />
                                        <SummaryRow label="Net Profit:" value={netProfit} isTotal={true} positive={netProfit >= 0} />
                                    </TableBody>
                                </Table>
                            </div>
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}

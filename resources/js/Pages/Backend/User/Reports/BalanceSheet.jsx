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
import { cn, formatCurrency, parseDateObject } from "@/lib/utils";
import { Label } from "@/Components/ui/label";
import DateTimePicker from "@/Components/DateTimePicker";

export default function BalanceSheet({ report_data, date2, business_name }) {
    // Calculate summary values for assets
    const getTotalAssets = () => {
        let totalFixedAssets = 0;
        let totalCurrentAssets = 0;

        if (report_data.fixed_asset && report_data.fixed_asset.length > 0) {
            totalFixedAssets = report_data.fixed_asset.reduce((total, account) => {
                return total + (account.dr_amount - account.cr_amount);
            }, 0);
        }

        if (report_data.current_asset && report_data.current_asset.length > 0) {
            totalCurrentAssets = report_data.current_asset.reduce((total, account) => {
                return total + (account.dr_amount - account.cr_amount);
            }, 0);
        }

        return totalFixedAssets + totalCurrentAssets;
    };

    // Calculate summary values for liabilities
    const getTotalLiabilities = () => {
        let totalCurrentLiabilities = 0;
        let totalLongTermLiabilities = 0;

        if (report_data.current_liability && report_data.current_liability.length > 0) {
            totalCurrentLiabilities = report_data.current_liability.reduce((total, account) => {
                return total + (account.cr_amount - account.dr_amount);
            }, 0);
        }

        if (report_data.long_term_liability && report_data.long_term_liability.length > 0) {
            totalLongTermLiabilities = report_data.long_term_liability.reduce((total, account) => {
                return total + (account.cr_amount - account.dr_amount);
            }, 0);
        }

        return totalCurrentLiabilities + totalLongTermLiabilities;
    };

    // Calculate summary values for equity
    const getTotalEquity = () => {
        if (!report_data.equity || report_data.equity.length === 0) return 0;

        return report_data.equity.reduce((total, account) => {
            return total + (account.cr_amount - account.dr_amount);
        }, 0);
    };

    // Calculate critical balance sheet metrics
    const totalAssets = getTotalAssets();
    const totalLiabilities = getTotalLiabilities();
    const totalEquity = getTotalEquity();
    const liabilitiesPlusEquity = totalLiabilities + totalEquity;
    const isBalanced = Math.abs(totalAssets - liabilitiesPlusEquity) < 0.01; // Allow small rounding differences

    // Function to render account tables by type
    const renderAccountTypeTable = (reportData, accountType, title, isDebitMinusCredit = true) => {
        if (!reportData[accountType] || reportData[accountType].length === 0) return null;

        return (
            <div className="mb-6">
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
                                <TableRow key={account.id || account.account_name}>
                                    <TableCell>{account.account_code || 'N/A'}</TableCell>
                                    <TableCell>{account.account_name || 'N/A'}</TableCell>
                                    <TableCell>{formatCurrency({ amount: account.dr_amount || 0 })}</TableCell>
                                    <TableCell>{formatCurrency({ amount: account.cr_amount || 0 })}</TableCell>
                                    <TableCell>{formatCurrency({ amount: balance || 0 })}</TableCell>
                                </TableRow>
                            );
                        })}

                        {/* Section Total */}
                        <TableRow className="font-bold bg-slate-50">
                            <TableCell colSpan={4} className="text-right">Total {title}:</TableCell>
                            <TableCell>
                                {formatCurrency({
                                    amount: reportData[accountType].reduce((sum, account) => {
                                        const balance = isDebitMinusCredit
                                            ? account.dr_amount - account.cr_amount
                                            : account.cr_amount - account.dr_amount;
                                        return sum + balance;
                                    }, 0) || 0
                                })}
                            </TableCell>
                        </TableRow>
                    </TableBody>
                </Table>
            </div>
        );
    };

    // Create summary row
    const SummaryRow = ({ label, value, isTotal = false, isAsset = true }) => (
        <TableRow className={isTotal ? "font-bold bg-slate-100" : ""}>
            <TableCell colSpan={4} className="text-right">{label}</TableCell>
            <TableCell className={isAsset ? "" : value < 0 ? "text-red-600" : ""}>
                {formatCurrency({ amount: value || 0 })}
            </TableCell>
        </TableRow>
    );

    const { data, setData, post, processing } = useForm({
        date2: parseDateObject(date2),
    });

    const handleExport = () => {
        window.location.href = route("reports.balance_sheet_export");
    };

    const handleGenerate = (e) => {
        e.preventDefault();
        post(route("reports.balance_sheet"), {
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
                .negative { color: red; }
                .total-row { font-weight: bold; background-color: #f9f9f9; }
                .section { margin-bottom: 30px; }
                .summary { font-size: 16px; font-weight: bold; margin-top: 20px; }
                .section-title { font-size: 20px; font-weight: bold; margin-top: 30px; margin-bottom: 10px; }
                .balanced { color: green; }
                .unbalanced { color: red; }
            </style>
        `;

        // Start building the HTML content for the print window
        let printContent = `
            <!DOCTYPE html>
            <html>
            <head>
                <title>Balance Sheet</title>
                ${style}
            </head>
            <body>
                <h1>${business_name}</h1>
                <h2>Balance Sheet (as of ${format(new Date(data.date2), "PPP")})</h2>
        `;

        // Function to create a table for each account type
        const createAccountTable = (accounts, title, isDebitMinusCredit = true) => {
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
            let sectionTotal = 0;
            accounts.forEach(account => {
                const balance = isDebitMinusCredit
                    ? account.dr_amount - account.cr_amount
                    : account.cr_amount - account.dr_amount;

                sectionTotal += balance;

                const formattedDr = formatCurrency(account.dr_amount || 0);
                const formattedCr = formatCurrency(account.cr_amount || 0);
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

            // Add section total
            tableHtml += `
                <tr class="total-row">
                    <td colspan="4" class="text-right">Total ${title}:</td>
                    <td class="text-right">${formatCurrency(sectionTotal)}</td>
                </tr>
            `;

            tableHtml += `
                        </tbody>
                    </table>
                </div>
            `;

            return tableHtml;
        };

        // Add Assets section
        printContent += `<div class="section-title">ASSETS</div>`;

        if (report_data.fixed_asset && report_data.fixed_asset.length > 0) {
            printContent += createAccountTable(report_data.fixed_asset, 'Fixed Assets', true);
        }

        if (report_data.current_asset && report_data.current_asset.length > 0) {
            printContent += createAccountTable(report_data.current_asset, 'Current Assets', true);
        }

        // Add total assets summary
        printContent += `
            <div class="summary">
                <p class="text-right">TOTAL ASSETS: ${formatCurrency(totalAssets)}</p>
            </div>
        `;

        // Add Liabilities section
        printContent += `<div class="section-title">LIABILITIES</div>`;

        if (report_data.current_liability && report_data.current_liability.length > 0) {
            printContent += createAccountTable(report_data.current_liability, 'Current Liabilities', false);
        }

        if (report_data.long_term_liability && report_data.long_term_liability.length > 0) {
            printContent += createAccountTable(report_data.long_term_liability, 'Long Term Liabilities', false);
        }

        // Add total liabilities summary
        printContent += `
            <div class="summary">
                <p class="text-right">TOTAL LIABILITIES: ${formatCurrency(totalLiabilities)}</p>
            </div>
        `;

        // Add Equity section
        printContent += `<div class="section-title">EQUITY</div>`;

        if (report_data.equity && report_data.equity.length > 0) {
            printContent += createAccountTable(report_data.equity, 'Equity', false);
        }

        // Add total equity summary
        printContent += `
            <div class="summary">
                <p class="text-right">TOTAL EQUITY: ${formatCurrency(totalEquity)}</p>
            </div>
        `;

        // Add balance verification
        printContent += `
            <div class="summary">
                <p class="text-right">TOTAL LIABILITIES AND EQUITY: ${formatCurrency(liabilitiesPlusEquity)}</p>
                <p class="text-right ${isBalanced ? 'balanced' : 'unbalanced'}">BALANCE CHECK: ${isBalanced ? 'Balanced' : 'Not Balanced'}</p>
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
            <Head title="Balance Sheet" />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Reports"
                        subpage="Balance Sheet"
                        url="reports.balance_sheet"
                    />
                    <div className="p-4">
                        <div className="flex flex-col justify-between items-start mb-6 gap-4">
                            <div className="flex flex-col md:flex-row gap-4">
                                <Label>As of</Label>
                                <form onSubmit={handleGenerate}>
                                    <div className="flex items-center gap-2">
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
                            {/* ASSETS SECTION */}
                            <div className="p-3 bg-gray-100 font-bold text-lg border-b">ASSETS</div>

                            {/* Fixed Assets */}
                            {renderAccountTypeTable(report_data, 'fixed_asset', 'Fixed Assets', true)}

                            {/* Current Assets */}
                            {renderAccountTypeTable(report_data, 'current_asset', 'Current Assets', true)}

                            {/* Total Assets Summary */}
                            <Table className="mt-2 mb-6">
                                <TableBody>
                                    <SummaryRow label="TOTAL ASSETS:" value={totalAssets} isTotal={true} isAsset={true} />
                                </TableBody>
                            </Table>

                            {/* LIABILITIES SECTION */}
                            <div className="p-3 bg-gray-100 font-bold text-lg border-b border-t">LIABILITIES</div>

                            {/* Current Liabilities */}
                            {renderAccountTypeTable(report_data, 'current_liability', 'Current Liabilities', false)}

                            {/* Long Term Liabilities */}
                            {renderAccountTypeTable(report_data, 'long_term_liability', 'Long Term Liabilities', false)}

                            {/* Total Liabilities Summary */}
                            <Table className="mt-2 mb-6">
                                <TableBody>
                                    <SummaryRow label="TOTAL LIABILITIES:" value={totalLiabilities} isTotal={true} isAsset={false} />
                                </TableBody>
                            </Table>

                            {/* EQUITY SECTION */}
                            <div className="p-3 bg-gray-100 font-bold text-lg border-b border-t">EQUITY</div>

                            {/* Equity */}
                            {renderAccountTypeTable(report_data, 'equity', 'Equity', false)}

                            {/* Total Equity Summary */}
                            <Table className="mt-2">
                                <TableBody>
                                    <SummaryRow label="TOTAL EQUITY:" value={totalEquity} isTotal={true} isAsset={false} />
                                </TableBody>
                            </Table>

                            {/* Balance Verification */}
                            <Table className="mt-8 mb-4">
                                <TableBody>
                                    <SummaryRow label="TOTAL LIABILITIES AND EQUITY:" value={liabilitiesPlusEquity} isTotal={true} isAsset={false} />
                                    <TableRow className="font-bold">
                                        <TableCell colSpan={4} className="text-right">DIFFERENCE:</TableCell>
                                        <TableCell className={totalAssets - liabilitiesPlusEquity === 0 ? "text-green-600" : "text-red-600"}>
                                            {formatCurrency({ amount: totalAssets - liabilitiesPlusEquity })}
                                        </TableCell>
                                    </TableRow>
                                    <TableRow className="font-bold">
                                        <TableCell colSpan={4} className="text-right">BALANCE CHECK:</TableCell>
                                        <TableCell className={isBalanced ? "text-green-600" : "text-red-600"}>
                                            {isBalanced ? "Balanced" : "Not Balanced"}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout >
    );
}

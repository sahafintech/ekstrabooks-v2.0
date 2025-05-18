import React from "react";
import { useForm } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import { toast } from "sonner";
import PageHeader from "@/Components/PageHeader";
import { formatCurrency, parseDateObject } from "@/lib/utils";
import DateTimePicker from "@/Components/DateTimePicker";
import { format } from "date-fns";

const SectionHeading = ({ children }) => (
    <tr>
        <td colSpan="2" className="text-left font-bold text-sm uppercase py-1 underline">{children}</td>
    </tr>
);

const SectionTotal = ({ label, value, underlined = false, bold = false }) => (
    <tr>
        <td style={{ borderBottom: '3px double black' }} className={`text-right py-1 ${bold ? "font-bold" : ""} ${underlined ? "border-t border-black" : ""}`}>{label}</td>
        <td style={{ borderBottom: '3px double black' }} className={`text-right py-1 ${bold ? "font-bold" : ""} ${underlined ? "border-t border-black" : ""}`}>{value}</td>
    </tr>
);

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

    // Simplified rendering approach for the income statement based on the image format

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
                @page {
                    size: A4;
                    margin: 0;
                }
                body { 
                    font-family: Arial, sans-serif; 
                    margin: 0;
                    padding: 20px;
                    width: 210mm;
                    min-height: 297mm;
                    margin: 0 auto;
                }
                table { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
                td { padding: 4px 8px; text-align: left; }
                .text-right { text-align: right; }
                .uppercase { text-transform: uppercase; }
                .section-heading { font-weight: medium; text-decoration: underline; text-transform: uppercase; }
                .total-row { font-weight: bold; border-top: 1px solid #000; }
                h1, h2 { text-align: center; margin-bottom: 20px; }
                .currency { text-align: right; }
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
                <table>
                    <tbody>
                        <tr>
                            <td colspan="2" class="section-heading">SALES AND INCOME</td>
                        </tr>
        `;

        // Add Sales and Income rows
        if (report_data.sales_and_income && report_data.sales_and_income.length > 0) {
            report_data.sales_and_income.forEach(account => {
                const amount = account.cr_amount - account.dr_amount;
                printContent += `
                    <tr>
                        <td>${account.account_name}</td>
                        <td class="text-right">${formatCurrency({ amount })}</td>
                    </tr>
                `;
            });
        }

        // Add Cost of Sale section
        printContent += `
            <tr>
                <td colspan="2" class="section-heading">COST OF SALE</td>
            </tr>
        `;

        // Add Cost of Sale rows
        if (report_data.cost_of_sale && report_data.cost_of_sale.length > 0) {
            report_data.cost_of_sale.forEach(account => {
                const amount = account.dr_amount - account.cr_amount;
                printContent += `
                    <tr>
                        <td>${account.account_name}</td>
                        <td class="text-right">${formatCurrency({ amount })}</td>
                    </tr>
                `;
            });
        }

        // Add Gross Profit row
        printContent += `
            <tr class="total-row">
                <td class="text-right">Gross Profit</td>
                <td class="text-right">${formatCurrency({ amount: grossProfit })}</td>
            </tr>
        `;

        // Add Other Expenses section
        printContent += `
            <tr>
                <td colspan="2" class="section-heading">OTHER EXPENSES</td>
            </tr>
        `;

        // Add Other Expenses rows
        if (report_data.other_expenses && report_data.other_expenses.length > 0) {
            report_data.other_expenses.forEach(account => {
                const amount = account.dr_amount - account.cr_amount;
                printContent += `
                    <tr>
                        <td>${account.account_name}</td>
                        <td class="text-right">${formatCurrency({ amount })}</td>
                    </tr>
                `;
            });
        }

        // Add Direct Expenses if available
        if (report_data.direct_expenses && report_data.direct_expenses.length > 0) {
            printContent += `
                <tr>
                    <td colspan="2" class="section-heading">DIRECT EXPENSES</td>
                </tr>
            `;

            report_data.direct_expenses.forEach(account => {
                const amount = account.dr_amount - account.cr_amount;
                printContent += `
                    <tr>
                        <td>${account.account_name}</td>
                        <td class="text-right">${formatCurrency({ amount })}</td>
                    </tr>
                `;
            });
        }

        // Add Net Income row
        printContent += `
            <tr class="total-row">
                <td class="text-right">Net Income</td>
                <td class="text-right">${formatCurrency({ amount: netProfit })}</td>
            </tr>
            </tbody>
            </table>
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

                            <div className="flex items-center justify-center">
                                <div className="rounded-md border printable-table mt-4 p-4 w-full md:w-[210mm] min-h-[297mm] mx-auto bg-white">
                                    <div className="text-center p-4">
                                        <h1 className="text-lg">{business_name}</h1>
                                        <h2 className="font-bold">Income Statement</h2>
                                        <h2 className="flex items-center justify-center space-x-2">
                                            <span>From {format(new Date(data.date1), "dd/MM/yyyy")}</span>
                                            <span>To</span>
                                            <span>{format(new Date(data.date2), "dd/MM/yyyy")}</span>
                                        </h2>
                                    </div>
                                    <table className="w-full !text-[12px]">
                                        <tbody>
                                            {/* SALES AND INCOME SECTION */}
                                            <SectionHeading>SALES AND INCOME</SectionHeading>

                                            {report_data.sales_and_income && report_data.sales_and_income.map(account => (
                                                <tr key={account.id}>
                                                    <td className="py-1">{account.account_name}</td>
                                                    <td className="text-right py-1">{formatCurrency({ amount: account.cr_amount - account.dr_amount })}</td>
                                                </tr>
                                            ))}

                                            {/* COST OF SALE SECTION */}
                                            <SectionHeading>COST OF SALE</SectionHeading>

                                            {report_data.cost_of_sale && report_data.cost_of_sale.map(account => (
                                                <tr key={account.id}>
                                                    <td className="py-1">{account.account_name}</td>
                                                    <td className="text-right py-1">{formatCurrency({ amount: account.dr_amount - account.cr_amount })}</td>
                                                </tr>
                                            ))}

                                            <SectionTotal
                                                label="Gross Profit"
                                                value={formatCurrency({ amount: grossProfit })}
                                                underlined={true}
                                                bold={true}
                                            />

                                            {/* OTHER EXPENSES SECTION */}
                                            <SectionHeading>OTHER EXPENSES</SectionHeading>

                                            {report_data.other_expenses && report_data.other_expenses.map(account => (
                                                <tr key={account.id}>
                                                    <td className="py-1">{account.account_name}</td>
                                                    <td className="text-right py-1">{formatCurrency({ amount: account.dr_amount - account.cr_amount })}</td>
                                                </tr>
                                            ))}

                                            {/* DIRECT EXPENSES SECTION (if needed) */}
                                            {report_data.direct_expenses && report_data.direct_expenses.length > 0 && (
                                                <>
                                                    <SectionHeading>DIRECT EXPENSES</SectionHeading>
                                                    {report_data.direct_expenses.map(account => (
                                                        <tr key={account.id}>
                                                            <td className="py-1">{account.account_name}</td>
                                                            <td className="text-right py-1">{formatCurrency({ amount: account.dr_amount - account.cr_amount })}</td>
                                                        </tr>
                                                    ))}
                                                </>
                                            )}

                                            {/* NET INCOME */}
                                            <SectionTotal
                                                label="Net Income"
                                                value={formatCurrency({ amount: netProfit })}
                                                underlined={true}
                                                bold={true}
                                            />
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}

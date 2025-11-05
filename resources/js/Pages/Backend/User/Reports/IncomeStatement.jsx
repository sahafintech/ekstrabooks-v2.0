import React from "react";
import { useForm } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from "@/Components/ui/dropdown-menu";
import { toast } from "sonner";
import PageHeader from "@/Components/PageHeader";
import { formatCurrency, parseDateObject } from "@/lib/utils";
import DateTimePicker from "@/Components/DateTimePicker";
import { format } from "date-fns";
import { ChevronDown } from "lucide-react";

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

    const getTotalTaxExpenses = () => {
        if (!report_data.tax_expenses || report_data.tax_expenses.length === 0) return 0;
        return report_data.tax_expenses.reduce((total, account) => {
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
    const netProfitBeforeTax = operatingProfit - totalOtherExpenses;
    const taxExpenses = getTotalTaxExpenses();
    const netProfitAfterTax = netProfitBeforeTax - taxExpenses;

    // Simplified rendering approach for the income statement based on the image format

    const { data, setData, post, processing } = useForm({
        date1: parseDateObject(date1),
        date2: parseDateObject(date2),
    });

    const handleExport = (type) => {
        if (type === 'excel') {
            window.location.href = route("reports.income_statement_export");
        } else if (type === 'pdf') {
            window.location.href = route("reports.income_statement_pdf");
        }
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
        window.print();
    };

    return (
        <AuthenticatedLayout>
            <SidebarInset>
                <div className="main-content">
                    <style dangerouslySetInnerHTML={{ __html: printStyles }} />
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
                                <DropdownMenu>
                                    <DropdownMenuTrigger asChild>
                                        <Button variant="outline">
                                            Export
                                            <ChevronDown className="ml-2 h-4 w-4" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent>
                                        <DropdownMenuItem onClick={() => handleExport('excel')}>
                                            Excel
                                        </DropdownMenuItem>
                                        <DropdownMenuItem onClick={() => handleExport('pdf')}>
                                            PDF
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>

                            <div className="flex items-center justify-center">
                                <div id="printable-area" className="rounded-md border mt-4 p-4 w-full lg:w-[210mm] min-h-[297mm] mx-auto bg-white">
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

                                            <SectionTotal
                                                label="Net Sales"
                                                value={formatCurrency({ amount: totalIncome })}
                                                underlined={true}
                                                bold={true}
                                            />

                                            {/* COST OF SALE SECTION */}
                                            <SectionHeading>COST OF SALE</SectionHeading>

                                            {report_data.cost_of_sale && report_data.cost_of_sale.map(account => (
                                                <tr key={account.id}>
                                                    <td className="py-1">{account.account_name}</td>
                                                    <td className="text-right py-1">{formatCurrency({ amount: account.dr_amount - account.cr_amount })}</td>
                                                </tr>
                                            ))}

                                            <SectionTotal
                                                label="Net Cost of Sale"
                                                value={formatCurrency({ amount: totalCostOfSales })}
                                                underlined={true}
                                                bold={true}
                                            />
                                            
                                            <div className="mt-4"></div>

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

                                            <SectionTotal
                                                label="Total Expenses"
                                                value={formatCurrency({ amount: totalDirectExpenses + totalOtherExpenses })}
                                                underlined={true}
                                                bold={true}
                                            />

                                            <div className="mt-4"></div>

                                            {/* NET INCOME BEFORE TAX */}
                                            <SectionTotal
                                                label="Net Income Before Tax"
                                                value={formatCurrency({ amount: netProfitBeforeTax })}
                                                underlined={true}
                                                bold={true}
                                            />

                                            {/* TAX EXPENSES */}
                                            {report_data.tax_expenses && report_data.tax_expenses.length > 0 && (
                                                <>
                                                    <SectionHeading>TAX EXPENSES</SectionHeading>
                                                    {report_data.tax_expenses.map(account => (
                                                        <tr key={account.id}>
                                                            <td className="py-1">{account.account_name}</td>
                                                            <td className="text-right py-1">{formatCurrency({ amount: account.dr_amount - account.cr_amount })}</td>
                                                        </tr>
                                                    ))}
                                                </>
                                            )}

                                            <SectionTotal
                                                label="Total Tax Expenses"
                                                value={formatCurrency({ amount: taxExpenses })}
                                                underlined={true}
                                                bold={true}
                                            />

                                            <div className="mt-4"></div>

                                            <SectionTotal
                                                label="Net Income After Tax"
                                                value={formatCurrency({ amount: netProfitAfterTax })}
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

import React from "react";
import { useForm } from "@inertiajs/react";
import AuthenticatedLayout from "@/Layouts/AuthenticatedLayout";
import { SidebarInset } from "@/Components/ui/sidebar";
import { Button } from "@/Components/ui/button";
import PageHeader from "@/Components/PageHeader";
import { format } from "date-fns";
import { formatCurrency, parseDateObject } from "@/lib/utils";
import { Label } from "@/Components/ui/label";
import DateTimePicker from "@/Components/DateTimePicker";

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
                        subpage="Balance Sheet"
                        url="reports.balance_sheet"
                    />
                    <div className="p-4">
                        <div className="flex flex-col justify-between items-start mb-6 gap-4">
                            <div>
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

                        <div className="flex items-center gap-2 print-buttons">
                            <Button variant="outline" onClick={handlePrint}>
                                Print
                            </Button>
                            <Button variant="outline" onClick={handleExport}>Export</Button>
                        </div>

                        <div id="printable-area" className="rounded-md border p-4 mt-4 w-full lg:w-[210mm] min-h-[297mm] mx-auto bg-white">
                            {/* Simple 2-column grid layout */}
                            <div className="text-center p-4">
                                <h1 className="text-lg">{business_name}</h1>
                                <h2 className="font-bold">Balance Sheet</h2>
                                <h2 className="flex items-center justify-center space-x-2">
                                    <span>As of</span>
                                    <span>{format(new Date(data.date2), "dd/MM/yyyy")}</span>
                                </h2>
                            </div>
                            <div className="grid grid-cols-1 md:grid-cols-1 gap-8">
                                {/* Left Column - All Assets */}
                                <div>
                                    {/* FIXED ASSETS */}
                                    <div className="mb-8">
                                        <h3 className="text-left underline font-bold mb-2">FIXED ASSETS</h3>
                                        <table className="w-full !text-[12px]">
                                            <tbody>
                                                {report_data.fixed_asset && report_data.fixed_asset.map((asset, index) => (
                                                    <tr key={`fixed-${asset.id || index}`}>
                                                        <td>{asset.account_name}</td>
                                                        <td className="text-right">
                                                            {formatCurrency({ amount: asset.dr_amount - asset.cr_amount })}
                                                        </td>
                                                    </tr>
                                                ))}
                                                <tr>
                                                    <td className="font-bold">Total Of Fixed Assets</td>
                                                    <td className="text-right font-bold border-t border-black">
                                                        {formatCurrency({
                                                            amount: report_data.fixed_asset ? report_data.fixed_asset.reduce((sum, asset) =>
                                                                sum + (asset.dr_amount - asset.cr_amount), 0) : 0
                                                        })}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    {/* CURRENT ASSETS */}
                                    <div className="mb-8">
                                        <h3 className="text-left underline font-bold mb-2">CURRENT ASSETS</h3>
                                        <table className="w-full !text-[12px]">
                                            <tbody>
                                                {report_data.current_asset && report_data.current_asset.map((asset, index) => (
                                                    <tr key={`current-${asset.id || index}`}>
                                                        <td>{asset.account_name}</td>
                                                        <td className="text-right">
                                                            {formatCurrency({ amount: asset.dr_amount - asset.cr_amount })}
                                                        </td>
                                                    </tr>
                                                ))}
                                                <tr>
                                                    <td className="font-bold">Total Of Current Assets</td>
                                                    <td className="text-right font-bold border-t border-black">
                                                        {formatCurrency({
                                                            amount: report_data.current_asset ? report_data.current_asset.reduce((sum, asset) =>
                                                                sum + (asset.dr_amount - asset.cr_amount), 0) : 0
                                                        })}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    {/* TOTAL ASSETS */}
                                    <div>
                                        <table className="w-full !text-[12px]">
                                            <tbody>
                                                <tr>
                                                    <td className="font-bold">TOTAL OF ASSETS</td>
                                                    <td
                                                        className="text-right font-bold border-t border-black"
                                                        style={{ borderBottom: '3px double black' }}
                                                    >
                                                        {formatCurrency({ amount: totalAssets })}
                                                    </td>

                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                {/* Right Column - All Liabilities and Equity */}
                                <div>
                                    {/* CURRENT LIABILITY */}
                                    <div className="mb-8">
                                        <h3 className="text-left underline font-bold mb-2">CURRENT LIABILITY</h3>
                                        <table className="w-full !text-[12px]">
                                            <tbody>
                                                {report_data.current_liability && report_data.current_liability.map((liability, index) => (
                                                    <tr key={`liability-${liability.id || index}`}>
                                                        <td>{liability.account_name}</td>
                                                        <td className="text-right">
                                                            {formatCurrency({ amount: liability.cr_amount - liability.dr_amount })}
                                                        </td>
                                                    </tr>
                                                ))}
                                                <tr>
                                                    <td className="font-bold">Total Of Current Liability</td>
                                                    <td className="text-right font-bold border-t border-black">
                                                        {formatCurrency({
                                                            amount: report_data.current_liability ? report_data.current_liability.reduce((sum, liability) =>
                                                                sum + (liability.cr_amount - liability.dr_amount), 0) : 0
                                                        })}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    {/* EQUITY */}
                                    <div className="mb-8">
                                        <h3 className="text-left underline font-bold mb-2">EQUITY</h3>
                                        <table className="w-full !text-[12px]">
                                            <tbody>
                                                {report_data.equity && report_data.equity.map((equity, index) => (
                                                    <tr key={`equity-${equity.id || index}`}>
                                                        <td>{equity.account_name}</td>
                                                        <td className="text-right">
                                                            {formatCurrency({ amount: equity.cr_amount - equity.dr_amount })}
                                                        </td>
                                                    </tr>
                                                ))}
                                                <tr>
                                                    <td className="font-bold">Total Of Equity</td>
                                                    <td className="text-right font-bold border-t border-black">
                                                        {formatCurrency({
                                                            amount: report_data.equity ? report_data.equity.reduce((sum, equity) =>
                                                                sum + (equity.cr_amount - equity.dr_amount), 0) : 0
                                                        })}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    {/* TOTAL LIABILITY & EQUITY */}
                                    <div>
                                        <table className="w-full !text-[12px]">
                                            <tbody>
                                                <tr>
                                                    <td className="font-bold">TOTAL OF LIABILITY & EQUITY</td>
                                                    <td className="text-right font-bold border-t border-black"
                                                        style={{ borderBottom: '3px double black' }}>
                                                        {formatCurrency({ amount: liabilitiesPlusEquity })}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>

                            {/* BALANCE CHECK */}
                            <div className="mt-4">
                                <span className="mr-4 font-bold">DIFFERENCE:</span>
                                <span className={"font-bold " + (isBalanced ? "text-green-600" : "text-red-600")}>
                                    {formatCurrency({ amount: totalAssets - liabilitiesPlusEquity })}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout >
    );
}

import React from "react";
import { Head, router, useForm } from "@inertiajs/react";
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
import { Calendar } from "@/Components/ui/calendar";
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from "@/Components/ui/popover";
import { CalendarIcon } from "lucide-react";
import { format } from "date-fns";
import { cn, formatCurrency } from "@/lib/utils";

export default function InventorySummary({ categories, date1, date2, business_name, currency }) {
    const { data, setData, post, processing, errors, reset } = useForm({
        date1: date1,
        date2: date2,
    });

    const handleGenerate = (e) => {
        e.preventDefault();
        post(route("reports.inventory_summary"), {
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
        // Open new window
        const printWindow = window.open('', '_blank', 'width=800,height=600');

        // Shared CSS
        const style = `
          <style>
            body { font-family: Arial, sans-serif; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
            th { background-color: #f2f2f2; }
            .text-right { text-align: right; }
            .total-row { font-weight: bold; background-color: #f9f9f9; }
            h1, h2 { text-align: center; margin-bottom: 0.5em; }
          </style>
        `;

        // Build header & table start
        let html = `
          <!DOCTYPE html>
          <html><head><title>Inventory Details</title>${style}</head>
          <body>
            <h1>${business_name}</h1>
            <h2>Inventory Details (${data.date1} – ${data.date2})</h2>
            <table>
              <thead>
                <tr>
                  <th>Code</th>
                  <th>Name</th>
                  <th>Opening Stock</th>
                  <th>Stock In</th>
                  <th>Stock Out</th>
                  <th>Adj Added</th>
                  <th>Adj Deducted</th>
                  <th>Balance</th>
                  <th class="text-right">Total Cost (${currency})</th>
                </tr>
              </thead>
              <tbody>
        `;

        // Track grand totals
        let grandCost = 0;

        // Iterate categories → brands → products
        categories.forEach(cat => {
            const catOpen = getTotalInitialStock(cat);
            const catIn = getTotalStockIn(cat);
            const catOut = cat.total_sold;
            const catAdjAdd = getTotalStockAdjustmentAdded(cat);
            const catAdjDed = getTotalStockAdjustmentDeducted(cat);
            const catBal = getTotalStockBalance(cat);
            const catCost = getTotalStockCost(cat);
            grandCost += catCost;

            // Category row
            html += `
            <tr class="total-row">
              <td></td>
              <td><strong>${cat.category_name}</strong></td>
              <td>${catOpen}</td>
              <td>${catIn}</td>
              <td>${catOut}</td>
              <td>${catAdjAdd}</td>
              <td>${catAdjDed}</td>
              <td>${catBal}</td>
              <td class="text-right">${formatCurrency({ amount: catCost })}</td>
            </tr>
          `;

            // Brands
            cat.brands.forEach(brand => {
                const bOpen = getTotalInitialStock(brand);
                const bIn = getTotalStockIn(brand);
                const bOut = brand.total_sold;
                const bAdjAdd = getTotalStockAdjustmentAdded(brand);
                const bAdjDed = getTotalStockAdjustmentDeducted(brand);
                const bBal = getTotalStockBalance(brand);
                const bCost = getTotalStockCost(brand);

                html += `
              <tr>
                <td></td>
                <td style="padding-left:20px;"><strong>${brand.brand_name}</strong></td>
                <td>${bOpen}</td>
                <td>${bIn}</td>
                <td>${bOut}</td>
                <td>${bAdjAdd}</td>
                <td>${bAdjDed}</td>
                <td>${bBal}</td>
                <td class="text-right">${formatCurrency({ amount: bCost })}</td>
              </tr>
            `;

                // Products
                brand.products.forEach(prod => {
                    const pCost = prod.stock * prod.purchase_cost;
                    grandCost += pCost;
                    html += `
                <tr>
                  <td>${prod.code}</td>
                  <td style="padding-left:40px;">${prod.name}</td>
                  <td>${prod.initial_stock}</td>
                  <td>${prod.total_stock_in}</td>
                  <td>${prod.total_sold}</td>
                  <td>${prod.total_stock_adjustment_added}</td>
                  <td>${prod.total_stock_adjustment_deducted}</td>
                  <td>${prod.stock}</td>
                  <td class="text-right">${formatCurrency({ amount: pCost })}</td>
                </tr>
              `;
                });
            });
        });

        // Grand total row
        html += `
              <tr class="total-row">
                <td colspan="8">Grand Total</td>
                <td class="text-right">${formatCurrency({ amount: grandCost })}</td>
              </tr>
            </tbody>
          </table>
          </body>
          </html>
        `;

        // Write & print
        printWindow.document.open();
        printWindow.document.write(html);
        printWindow.document.close();
        setTimeout(() => {
            printWindow.print();
            printWindow.onafterprint = () => printWindow.close();
        }, 300);
    };


    function getTotalInitialStock(node) {
        // If it's a brand, sum its products
        if (Array.isArray(node.products)) {
            return node.products.reduce(
                (sum, prod) => sum + (Number(prod.initial_stock) || 0),
                0
            );
        }
        // If it's a category, sum each brand recursively
        if (Array.isArray(node.brands)) {
            return node.brands.reduce(
                (sum, brand) => sum + getTotalInitialStock(brand),
                0
            );
        }
        return 0;
    }

    function getTotalStockIn(node) {
        // If it's a brand, sum its products
        if (Array.isArray(node.products)) {
            return node.products.reduce(
                (sum, prod) => sum + (Number(prod.total_stock_in) || 0),
                0
            );
        }
        // If it's a category, sum each brand recursively
        if (Array.isArray(node.brands)) {
            return node.brands.reduce(
                (sum, brand) => sum + getTotalStockIn(brand),
                0
            );
        }
        return 0;
    }

    function getTotalStockAdjustmentAdded(node) {
        // If it's a brand, sum its products
        if (Array.isArray(node.products)) {
            return node.products.reduce(
                (sum, prod) => sum + (Number(prod.total_stock_adjustment_added) || 0),
                0
            );
        }
        // If it's a category, sum each brand recursively
        if (Array.isArray(node.brands)) {
            return node.brands.reduce(
                (sum, brand) => sum + getTotalStockAdjustmentAdded(brand),
                0
            );
        }
        return 0;
    }

    function getTotalStockAdjustmentDeducted(node) {
        // If it's a brand, sum its products
        if (Array.isArray(node.products)) {
            return node.products.reduce(
                (sum, prod) => sum + (Number(prod.total_stock_adjustment_deducted) || 0),
                0
            );
        }
        // If it's a category, sum each brand recursively
        if (Array.isArray(node.brands)) {
            return node.brands.reduce(
                (sum, brand) => sum + getTotalStockAdjustmentDeducted(brand),
                0
            );
        }
        return 0;
    }

    function getTotalStockBalance(node) {
        // If it's a brand, sum its products
        if (Array.isArray(node.products)) {
            return node.products.reduce(
                (sum, prod) => sum + (Number(prod.stock) || 0),
                0
            );
        }
        // If it's a category, sum each brand recursively
        if (Array.isArray(node.brands)) {
            return node.brands.reduce(
                (sum, brand) => sum + getTotalStockBalance(brand),
                0
            );
        }
        return 0;
    }

    function getTotalStockCost(node) {
        // If it's a brand, sum its products
        if (Array.isArray(node.products)) {
            return node.products.reduce(
                (sum, prod) => sum + (Number(prod.stock) * Number(prod.purchase_cost) || 0),
                0
            );
        }
        // If it's a category, sum each brand recursively
        if (Array.isArray(node.brands)) {
            return node.brands.reduce(
                (sum, brand) => sum + getTotalStockCost(brand),
                0
            );
        }
        return 0;
    }

    return (
        <AuthenticatedLayout>
            <Head title="Inventory Summary" />
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Inventory Summary"
                        subpage="Report"
                        url="reports.inventory_summary"
                    />
                    <div className="p-4">
                        <div className="flex flex-col justify-between items-start mb-6 gap-4">
                            <div className="flex flex-col md:flex-row gap-4">
                                <form onSubmit={handleGenerate} className="flex flex-col md:flex-row gap-4 w-full">
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
                            <a download href={route('reports.inventory_summary_export')}>
                                <Button variant="outline">Export</Button>
                            </a>
                        </div>

                        <div className="rounded-md border printable-table mt-4">
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableCell colSpan={9}><strong>Inventory Summary Report by Category</strong></TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableHead>Category Name</TableHead>
                                        <TableHead>Opening Stock</TableHead>
                                        <TableHead>Stock In</TableHead>
                                        <TableHead>Stock Out</TableHead>
                                        <TableHead>Stock Adjustment Added</TableHead>
                                        <TableHead>Stock Adjustment Deducted</TableHead>
                                        <TableHead>Stock Balance</TableHead>
                                        <TableHead className="text-right">Total Stock Cost</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {categories.length > 0 ? (
                                        categories.map((cat) => (
                                            <React.Fragment key={cat.id}>
                                                {/* Category row */}
                                                <TableRow>
                                                    <TableCell>
                                                        {cat.category_name}
                                                    </TableCell>
                                                    <TableCell>
                                                        {getTotalInitialStock(cat)}
                                                    </TableCell>
                                                    <TableCell>
                                                        {getTotalStockIn(cat)}
                                                    </TableCell>
                                                    <TableCell>
                                                        {cat.total_sold}
                                                    </TableCell>
                                                    <TableCell>
                                                        {getTotalStockAdjustmentAdded(cat)}
                                                    </TableCell>
                                                    <TableCell>
                                                        {getTotalStockAdjustmentDeducted(cat)}
                                                    </TableCell>
                                                    <TableCell>
                                                        {getTotalStockBalance(cat)}
                                                    </TableCell>
                                                    <TableCell>
                                                        {formatCurrency({ amount: getTotalStockCost(cat) })}
                                                    </TableCell>
                                                </TableRow>
                                            </React.Fragment>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={9} className="h-24 text-center">
                                                No data found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>

                            <Table className="mt-6">
                                <TableHeader>
                                    <TableRow>
                                        <TableCell colSpan={9}><strong>Inventory Summary Report by Brand</strong></TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableHead>Category Name</TableHead>
                                        <TableHead>Brand Name</TableHead>
                                        <TableHead>Opening Stock</TableHead>
                                        <TableHead>Stock In</TableHead>
                                        <TableHead>Stock Out</TableHead>
                                        <TableHead>Stock Adjustment Added</TableHead>
                                        <TableHead>Stock Adjustment Deducted</TableHead>
                                        <TableHead>Stock Balance</TableHead>
                                        <TableHead className="text-right">Total Stock Cost</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {categories.length > 0 ? (
                                        categories.map((cat) => (
                                            <React.Fragment key={cat.id}>
                                                {/* Brands */}
                                                {cat.brands.map((brand) => (
                                                    <React.Fragment key={brand.id}>
                                                        <TableRow>
                                                            <TableCell>
                                                                {cat.category_name}
                                                            </TableCell>
                                                            <TableCell>
                                                                {brand.brand_name}
                                                            </TableCell>
                                                            <TableCell>
                                                                {getTotalInitialStock(brand)}
                                                            </TableCell>
                                                            <TableCell>
                                                                {getTotalStockIn(brand)}
                                                            </TableCell>
                                                            <TableCell>
                                                                {brand.total_sold}
                                                            </TableCell>
                                                            <TableCell>
                                                                {getTotalStockAdjustmentAdded(brand)}
                                                            </TableCell>
                                                            <TableCell>
                                                                {getTotalStockAdjustmentDeducted(brand)}
                                                            </TableCell>
                                                            <TableCell>
                                                                {getTotalStockBalance(brand)}
                                                            </TableCell>
                                                            <TableCell>
                                                                {formatCurrency({ amount: getTotalStockCost(brand) })}
                                                            </TableCell>
                                                        </TableRow>
                                                    </React.Fragment>
                                                ))}
                                            </React.Fragment>
                                        ))
                                    ) : (
                                        <TableRow>
                                            <TableCell colSpan={9} className="h-24 text-center">
                                                No data found.
                                            </TableCell>
                                        </TableRow>
                                    )}
                                </TableBody>
                            </Table>
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}

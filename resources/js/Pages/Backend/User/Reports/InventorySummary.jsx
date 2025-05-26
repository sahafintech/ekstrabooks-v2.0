import React from "react";
import { Head, router, useForm } from "@inertiajs/react";
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
} from "@/Components/shared/ReportTable";
import PageHeader from "@/Components/PageHeader";
import { formatCurrency, parseDateObject } from "@/lib/utils";
import DateTimePicker from "@/Components/DateTimePicker";

export default function InventorySummary({ categories, date1, date2, business_name, currency }) {
    const { data, setData, post, processing } = useForm({
        date1: parseDateObject(date1),
        date2: parseDateObject(date2),
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
            body { font-family: Arial, sans-serif; padding: 20px; }
            h1, h2 { text-align: center; margin: 0.2em 0; }
            table { width: 100%; border-collapse: collapse; margin-top: 1em; }
            th, td { border: 1px solid #ddd; padding: 8px; }
            th { background-color: #f2f2f2; }
            .text-right { text-align: right; }
          </style>
        `;

        // Build header & tables
        let html = `
          <!DOCTYPE html>
          <html>
            <head>
              <title>Inventory Summary</title>
              ${style}
            </head>
            <body>
              <h1>${business_name}</h1>
              <h2>Inventory Summary (${data.date1} – ${data.date2})</h2>
      
              <!-- Category Summary Table -->
              <table>
                <thead>
                  <tr>
                    <th>Category Name</th>
                    <th class="text-right">Opening Stock</th>
                    <th class="text-right">Stock In</th>
                    <th class="text-right">Stock Out</th>
                    <th class="text-right">Adj. Added</th>
                    <th class="text-right">Adj. Deducted</th>
                    <th class="text-right">Balance</th>
                    <th class="text-right">Total Cost (${currency})</th>
                  </tr>
                </thead>
                <tbody>
        `;

        categories.forEach(cat => {
            html += `
            <tr>
              <td>${cat.category_name}</td>
              <td class="text-right">${getTotalInitialStock(cat)}</td>
              <td class="text-right">${getTotalStockIn(cat)}</td>
              <td class="text-right">${cat.total_sold}</td>
              <td class="text-right">${getTotalStockAdjustmentAdded(cat)}</td>
              <td class="text-right">${getTotalStockAdjustmentDeducted(cat)}</td>
              <td class="text-right">${getTotalStockBalance(cat)}</td>
              <td class="text-right">${formatCurrency({ amount: getTotalStockCost(cat) })}</td>
            </tr>
          `;
        });

        html += `
                </tbody>
              </table>
      
              <!-- Brand Summary Table -->
              <table>
                <thead>
                  <tr>
                    <th>Category Name</th>
                    <th>Brand Name</th>
                    <th class="text-right">Opening Stock</th>
                    <th class="text-right">Stock In</th>
                    <th class="text-right">Stock Out</th>
                    <th class="text-right">Adj. Added</th>
                    <th class="text-right">Adj. Deducted</th>
                    <th class="text-right">Balance</th>
                    <th class="text-right">Total Cost (${currency})</th>
                  </tr>
                </thead>
                <tbody>
        `;

        categories.forEach(cat => {
            cat.brands.forEach(brand => {
                html += `
              <tr>
                <td>${cat.category_name}</td>
                <td>${brand.brand_name}</td>
                <td class="text-right">${getTotalInitialStock(brand)}</td>
                <td class="text-right">${getTotalStockIn(brand)}</td>
                <td class="text-right">${brand.total_sold}</td>
                <td class="text-right">${getTotalStockAdjustmentAdded(brand)}</td>
                <td class="text-right">${getTotalStockAdjustmentDeducted(brand)}</td>
                <td class="text-right">${getTotalStockBalance(brand)}</td>
                <td class="text-right">${formatCurrency({ amount: getTotalStockCost(brand) })}</td>
              </tr>
            `;
            });
        });

        html += `
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

                        <div className="flex items-center gap-2 print-buttons">
                            <Button variant="outline" onClick={handlePrint}>
                                Print
                            </Button>
                            <a download href={route('reports.inventory_summary_export')}>
                                <Button variant="outline">Export</Button>
                            </a>
                        </div>

                        <div className="rounded-md border printable-table mt-4">
                            <ReportTable>
                                <TableHeader>
                                    <TableRow className="!text-[10px]">
                                        <TableCell colSpan={9}><strong>Inventory Summary Report by Category</strong></TableCell>
                                    </TableRow>
                                    <TableRow className="!text-[10px]">
                                        <TableHead className="!text-[10px]">Category Name</TableHead>
                                        <TableHead className="!text-[10px]">Opening Stock</TableHead>
                                        <TableHead className="!text-[10px]">Stock In</TableHead>
                                        <TableHead className="!text-[10px]">Stock Out</TableHead>
                                        <TableHead className="!text-[10px]">Stock Adjustment Added</TableHead>
                                        <TableHead className="!text-[10px]">Stock Adjustment Deducted</TableHead>
                                        <TableHead className="!text-[10px]">Stock Balance</TableHead>
                                        <TableHead className="!text-[10px]">Total Stock Cost</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {categories.length > 0 ? (
                                        categories.map((cat) => (
                                            <React.Fragment key={cat.id}>
                                                {/* Category row */}
                                                <TableRow>
                                                    <TableCell className="!text-[10px]">
                                                        {cat.category_name}
                                                    </TableCell>
                                                    <TableCell className="!text-[10px]">
                                                        {getTotalInitialStock(cat)}
                                                    </TableCell>
                                                    <TableCell className="!text-[10px]">
                                                        {getTotalStockIn(cat)}
                                                    </TableCell>
                                                    <TableCell className="!text-[10px]">
                                                        {cat.total_sold}
                                                    </TableCell>
                                                    <TableCell className="!text-[10px]">
                                                        {getTotalStockAdjustmentAdded(cat)}
                                                    </TableCell>
                                                    <TableCell className="!text-[10px]">
                                                        {getTotalStockAdjustmentDeducted(cat)}
                                                    </TableCell>
                                                    <TableCell className="!text-[10px]">
                                                        {getTotalStockBalance(cat)}
                                                    </TableCell>
                                                    <TableCell className="text-right !text-[10px]">
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
                            </ReportTable>

                            <ReportTable className="mt-6">
                                <TableHeader>
                                    <TableRow>
                                        <TableCell colSpan={9}><strong>Inventory Summary Report by Brand</strong></TableCell>
                                    </TableRow>
                                    <TableRow>
                                        <TableHead className="!text-[10px]">Category Name</TableHead>
                                        <TableHead className="!text-[10px]">Brand Name</TableHead>
                                        <TableHead className="!text-[10px]">Opening Stock</TableHead>
                                        <TableHead className="!text-[10px]">Stock In</TableHead>
                                        <TableHead className="!text-[10px]">Stock Out</TableHead>
                                        <TableHead className="!text-[10px]">Stock Adjustment Added</TableHead>
                                        <TableHead className="!text-[10px]">Stock Adjustment Deducted</TableHead>
                                        <TableHead className="!text-[10px]">Stock Balance</TableHead>
                                        <TableHead className="!text-[10px]">Total Stock Cost</TableHead>
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
                                                            <TableCell className="!text-[10px]">
                                                                {cat.category_name}
                                                            </TableCell>
                                                            <TableCell className="!text-[10px]">
                                                                {brand.brand_name}
                                                            </TableCell>
                                                            <TableCell className="!text-[10px]">
                                                                {getTotalInitialStock(brand)}
                                                            </TableCell>
                                                            <TableCell className="!text-[10px]">
                                                                {getTotalStockIn(brand)}
                                                            </TableCell>
                                                            <TableCell className="!text-[10px]">
                                                                {brand.total_sold}
                                                            </TableCell>
                                                            <TableCell className="!text-[10px]">
                                                                {getTotalStockAdjustmentAdded(brand)}
                                                            </TableCell>
                                                            <TableCell className="!text-[10px]">
                                                                {getTotalStockAdjustmentDeducted(brand)}
                                                            </TableCell>
                                                            <TableCell className="!text-[10px]">
                                                                {getTotalStockBalance(brand)}
                                                            </TableCell>
                                                            <TableCell className="!text-[10px]">
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
                            </ReportTable>
                        </div>
                    </div>
                </div>
            </SidebarInset>
        </AuthenticatedLayout>
    );
}

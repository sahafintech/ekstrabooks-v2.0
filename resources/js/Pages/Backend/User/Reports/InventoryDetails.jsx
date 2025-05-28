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
} from "@/Components/shared/ReportTable";
import PageHeader from "@/Components/PageHeader";
import { formatCurrency, parseDateObject } from "@/lib/utils";
import { SearchableCombobox } from "@/Components/ui/searchable-combobox";
import DateTimePicker from "@/Components/DateTimePicker";

export default function InventoryDetails({ categories, date1, date2, business_name, currency, subCategories = [], mainCategories = [], sub_category = '', main_category = '' }) {
    const { data, setData, post, processing } = useForm({
        date1: parseDateObject(date1),
        date2: parseDateObject(date2),
        sub_category: sub_category,
        main_category: main_category,
    });

    const handleGenerate = (e) => {
        e.preventDefault();
        post(route("reports.inventory_details"), {
            date1: data.date1,
            date2: data.date2,
            sub_category: data.sub_category,
            main_category: data.main_category,
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => {
                toast.success("Report Generated successfully");
            },
        });
    };

    const exportInventoryDetails = () => {
        window.location.href = route('reports.inventory_details_export')
    }

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
            <SidebarInset>
                <div className="main-content">
                    <PageHeader
                        page="Inventory Details"
                        subpage="Report"
                        url="reports.inventory_details"
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
                                    </div>

                                    <div className="flex items-center gap-2 w-full md:w-72">
                                        <SearchableCombobox
                                            options={[
                                                { id: 'all', name: 'All Sub Categories' },
                                                ...subCategories.map(cat => ({
                                                    id: cat.id.toString(),
                                                    name: cat.name,
                                                }))
                                            ]}
                                            value={data.sub_category}
                                            onChange={(value) => setData('sub_category', value)}
                                            className="w-full"
                                            placeholder="Select Sub Category"
                                        />
                                        <SearchableCombobox
                                            options={[
                                                { id: 'all', name: 'All Main Categories' },
                                                ...mainCategories.map(cat => ({
                                                    id: cat.id.toString(),
                                                    name: cat.name,
                                                }))
                                            ]}
                                            value={data.main_category}
                                            onChange={(value) => setData('main_category', value)}
                                            className="w-full"
                                            placeholder="Select Main Category"
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
                            <button onClick={exportInventoryDetails}>
                                <Button variant="outline">Export</Button>
                            </button>
                        </div>

                        <div className="rounded-md border printable-table mt-4">
                            <ReportTable>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead className="!text-[10px]">Code</TableHead>
                                        <TableHead className="!text-[10px]">Name</TableHead>
                                        <TableHead className="!text-[10px]">Opening Stock</TableHead>
                                        <TableHead className="!text-[10px]">Stock In</TableHead>
                                        <TableHead className="!text-[10px]">Stock Out</TableHead>
                                        <TableHead className="!text-[10px]">Stock Adjustment Added</TableHead>
                                        <TableHead className="!text-[10px]">Stock Adjustment Deducted</TableHead>
                                        <TableHead className="!text-[10px]">Stock Balance</TableHead>
                                        <TableHead className="text-right !text-[10px]">Total Stock Cost</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {categories.length > 0 ? (
                                        categories.map((cat) => (
                                            <React.Fragment key={cat.id}>
                                                {/* Category row */}
                                                <TableRow className="bg-gray-100">
                                                    <TableCell className="!text-[10px]"></TableCell>
                                                    <TableCell className="!text-[10px]">
                                                        <strong>{cat.category_name}</strong>
                                                    </TableCell>
                                                    <TableCell className="!text-[10px]">
                                                        <strong>{getTotalInitialStock(cat)}</strong>
                                                    </TableCell>
                                                    <TableCell className="!text-[10px]">
                                                        <strong>{getTotalStockIn(cat)}</strong>
                                                    </TableCell>
                                                    <TableCell className="!text-[10px]">
                                                        <strong>{cat.total_sold}</strong>
                                                    </TableCell>
                                                    <TableCell className="!text-[10px]">
                                                        <strong>{getTotalStockAdjustmentAdded(cat)}</strong>
                                                    </TableCell>
                                                    <TableCell className="!text-[10px]">
                                                        <strong>{getTotalStockAdjustmentDeducted(cat)}</strong>
                                                    </TableCell>
                                                    <TableCell className="!text-[10px]">
                                                        <strong>{getTotalStockBalance(cat)}</strong>
                                                    </TableCell>
                                                    <TableCell className="!text-[10px]">
                                                        <strong>{formatCurrency({ amount: getTotalStockCost(cat) })}</strong>
                                                    </TableCell>
                                                </TableRow>

                                                {/* Brands */}
                                                {cat.brands.map((brand) => (
                                                    <React.Fragment key={brand.id}>
                                                        <TableRow className="bg-gray-100 !text-[10px]">
                                                            <TableCell className="!text-[10px]"></TableCell>
                                                            <TableCell className="!text-[10px]">
                                                                <strong>{brand.brand_name}</strong>
                                                            </TableCell>
                                                            <TableCell className="!text-[10px]">
                                                                <strong>{getTotalInitialStock(brand)}</strong>
                                                            </TableCell>
                                                            <TableCell className="!text-[10px]">
                                                                <strong>{getTotalStockIn(brand)}</strong>
                                                            </TableCell>
                                                            <TableCell className="!text-[10px]">
                                                                <strong>{brand.total_sold}</strong>
                                                            </TableCell>
                                                            <TableCell className="!text-[10px]">
                                                                <strong>{getTotalStockAdjustmentAdded(brand)}</strong>
                                                            </TableCell>
                                                            <TableCell className="!text-[10px]">
                                                                <strong>{getTotalStockAdjustmentDeducted(brand)}</strong>
                                                            </TableCell>
                                                            <TableCell className="!text-[10px]">
                                                                <strong>{getTotalStockBalance(brand)}</strong>
                                                            </TableCell>
                                                            <TableCell className="!text-[10px]">
                                                                <strong>{formatCurrency({ amount: getTotalStockCost(brand) })}</strong>
                                                            </TableCell>
                                                        </TableRow>

                                                        {/* Products */}
                                                        {brand.products.map((prod) => (
                                                            <TableRow key={prod.id}>
                                                                <TableCell className="!text-[10px]">
                                                                    {prod.code}
                                                                </TableCell>
                                                                <TableCell className="!text-[10px]">
                                                                    {prod.name}
                                                                </TableCell>
                                                                <TableCell className="!text-[10px]">
                                                                    {prod.initial_stock}
                                                                </TableCell>
                                                                <TableCell className="!text-[10px]">
                                                                    {prod.total_stock_in}
                                                                </TableCell>
                                                                <TableCell className="!text-[10px]">
                                                                    {prod.total_sold}
                                                                </TableCell>
                                                                <TableCell className="!text-[10px]">
                                                                    {prod.total_stock_adjustment_added}
                                                                </TableCell>
                                                                <TableCell className="!text-[10px]">
                                                                    {prod.total_stock_adjustment_deducted}
                                                                </TableCell>
                                                                <TableCell className="!text-[10px]">
                                                                    {prod.stock}
                                                                </TableCell>
                                                                <TableCell className="text-right !text-[10px]">
                                                                    {formatCurrency({ amount: prod.stock * prod.purchase_cost })}
                                                                </TableCell>
                                                            </TableRow>
                                                        ))}
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

<?php

namespace App\Exports;

use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\ReceiptItem;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class SalesByProductExport implements FromView
{
    /**
     * @return \Illuminate\Support\Collection
     */

    public $date1;
    public $date2;
    public $category;

    public function __construct($date1, $date2, $category)
    {
        $this->date1 = $date1;
        $this->date2 = $date2;
        $this->category = $category;
    }

    public function view(): View
    {
        @ini_set('max_execution_time', 0);
        @set_time_limit(0);

        $data = array();

        $date1 = Carbon::parse($this->date1)->format('Y-m-d');
        $date2 = Carbon::parse($this->date2)->format('Y-m-d');

        if ($this->category == 'all') {
            // Step 1: Retrieve products with necessary joins and calculated fields
            $products = Product::select(
                'products.*',
                'categories.id as category_id',
                'categories.name as category_name',
                'product_brands.id as brand_id',
                'product_brands.name as brand_name'
            )
                ->join('categories', 'categories.id', '=', 'products.category_id')
                ->join('product_brands', 'product_brands.id', '=', 'products.brand_id')
                ->addSelect([
                    'total_sold_invoices' => InvoiceItem::selectRaw('SUM(quantity)')
                        ->whereColumn('product_id', 'products.id')
                        ->whereHas('invoice', function ($query) use ($date1, $date2) {
                            $query->whereDate('invoice_date', '>=', $date1)
                                ->whereDate('invoice_date', '<=', $date2);
                        }),

                    'total_sold_receipts' => ReceiptItem::selectRaw('SUM(quantity)')
                        ->whereColumn('product_id', 'products.id')
                        ->whereHas('receipt', function ($query) use ($date1, $date2) {
                            $query->whereDate('receipt_date', '>=', $date1)
                                ->whereDate('receipt_date', '<=', $date2);
                        }),

                    'sub_total_invoices' => InvoiceItem::selectRaw('SUM(sub_total)')
                        ->whereColumn('product_id', 'products.id')
                        ->whereHas('invoice', function ($query) use ($date1, $date2) {
                            $query->whereDate('invoice_date', '>=', $date1)
                                ->whereDate('invoice_date', '<=', $date2);
                        }),

                    'sub_total_receipts' => ReceiptItem::selectRaw('SUM(sub_total)')
                        ->whereColumn('product_id', 'products.id')
                        ->whereHas('receipt', function ($query) use ($date1, $date2) {
                            $query->whereDate('receipt_date', '>=', $date1)
                                ->whereDate('receipt_date', '<=', $date2);
                        }),
                ])
                ->get()
                ->map(function ($product) {
                    // Calculate the total sold by summing invoices and receipts
                    $product->total_sold = ($product->total_sold_invoices ?? 0) + ($product->total_sold_receipts ?? 0);

                    // Calculate total sales
                    $product->total_sales = ($product->sub_total_invoices ?? 0) + ($product->sub_total_receipts ?? 0);

                    // Calculate gross profit
                    $product->gross_profit = ($product->selling_price - $product->purchase_cost) * $product->total_sold;

                    // Calculate average price
                    $total_sub_total = ($product->sub_total_invoices ?? 0) + ($product->sub_total_receipts ?? 0);
                    $product->average_price = $product->total_sold > 0 ? $total_sub_total / $product->total_sold : 0;

                    // Calculate profit margin
                    // Corrected formula: (Total Sales - Total Cost) / Total Sales * 100
                    $total_cost = $product->purchase_cost * $product->total_sold;
                    $product->profit_margin = $product->total_sales > 0
                        ? (($product->total_sales - $total_cost) / $product->total_sales) * 100
                        : 0;

                    return $product;
                });

            // Step 2: Group products by category
            $groupedByCategory = $products->groupBy(function ($product) {
                return $product->category_id . '|' . $product->category_name;
            })->map(function ($categoryGroup, $categoryKey) {
                // Extract category ID and name
                [$categoryId, $categoryName] = explode('|', $categoryKey);

                // Step 3: Within each category, group by brand and calculate metrics
                $brands = $categoryGroup->groupBy(function ($product) {
                    return $product->brand_id . '|' . $product->brand_name;
                })->map(function ($brandGroup, $brandKey) {
                    // Extract brand ID and name
                    [$brandId, $brandName] = explode('|', $brandKey);

                    // Calculate total sold for the brand
                    $brandTotalSold = $brandGroup->sum('total_sold');

                    // Calculate total sales for the brand
                    $brandTotalSales = $brandGroup->sum('total_sales');

                    // Calculate gross profit for the brand
                    $brandGrossProfit = $brandGroup->sum('gross_profit');

                    // Calculate average price for the brand
                    $brandAveragePrice = $brandTotalSold > 0 ? $brandTotalSales / $brandTotalSold : 0;

                    // Calculate total cost for the brand correctly
                    $brandTotalCost = $brandGroup->sum(function ($product) {
                        return $product->purchase_cost * $product->total_sold;
                    });

                    // Calculate profit margin for the brand
                    $brandProfitMargin = $brandTotalSales > 0
                        ? (($brandTotalSales - $brandTotalCost) / $brandTotalSales) * 100
                        : 0;

                    return [
                        'brand_id' => $brandId,
                        'brand_name' => $brandName,
                        'total_sold' => $brandTotalSold,
                        'total_sales' => $brandTotalSales,
                        'gross_profit' => $brandGrossProfit,
                        'average_price' => $brandAveragePrice,
                        'profit_margin' => $brandProfitMargin,
                        'products' => $brandGroup->sortByDesc('total_sold')->values(),
                    ];
                });

                // Step 4: Sort brands within the category by total_sold descending
                $sortedBrands = $brands->sortByDesc('total_sold')->values();

                // Calculate total sold and sales for the category
                $categoryTotalSold = $categoryGroup->sum('total_sold');
                $categoryTotalSales = $categoryGroup->sum('total_sales');
                $categoryGrossProfit = $categoryGroup->sum('gross_profit');
                $categoryAveragePrice = $categoryTotalSold > 0 ? $categoryTotalSales / $categoryTotalSold : 0;

                // Calculate total cost for the category correctly
                $categoryTotalCost = $categoryGroup->sum(function ($product) {
                    return $product->purchase_cost * $product->total_sold;
                });

                // Calculate profit margin for the category
                $categoryProfitMargin = $categoryTotalSales > 0
                    ? (($categoryTotalSales - $categoryTotalCost) / $categoryTotalSales) * 100
                    : 0;

                return [
                    'category_id' => $categoryId,
                    'category_name' => $categoryName,
                    'total_sold' => $categoryTotalSold,
                    'total_sales' => $categoryTotalSales,
                    'gross_profit' => $categoryGrossProfit,
                    'average_price' => $categoryAveragePrice,
                    'profit_margin' => $categoryProfitMargin,
                    'brands' => $sortedBrands,
                ];
            });

            // Step 5: Optionally, sort categories as needed (e.g., by total_sold descending)
            $sortedCategories = $groupedByCategory->sortByDesc('total_sold')->values();

            // Assign to your data array
            $data['products'] = $sortedCategories;
        } else {
            // Step 1: Retrieve products with necessary joins and calculated fields
            $products = Product::select(
                'products.*',
                'categories.id as category_id',
                'categories.name as category_name',
                'product_brands.id as brand_id',
                'product_brands.name as brand_name'
            )
                ->join('categories', 'categories.id', '=', 'products.category_id')
                ->join('product_brands', 'product_brands.id', '=', 'products.brand_id')
                ->addSelect([
                    'total_sold_invoices' => InvoiceItem::selectRaw('SUM(quantity)')
                        ->whereColumn('product_id', 'products.id')
                        ->whereHas('invoice', function ($query) use ($date1, $date2) {
                            $query->whereDate('invoice_date', '>=', $date1)
                                ->whereDate('invoice_date', '<=', $date2);
                        }),

                    'total_sold_receipts' => ReceiptItem::selectRaw('SUM(quantity)')
                        ->whereColumn('product_id', 'products.id')
                        ->whereHas('receipt', function ($query) use ($date1, $date2) {
                            $query->whereDate('receipt_date', '>=', $date1)
                                ->whereDate('receipt_date', '<=', $date2);
                        }),

                    'sub_total_invoices' => InvoiceItem::selectRaw('SUM(sub_total)')
                        ->whereColumn('product_id', 'products.id')
                        ->whereHas('invoice', function ($query) use ($date1, $date2) {
                            $query->whereDate('invoice_date', '>=', $date1)
                                ->whereDate('invoice_date', '<=', $date2);
                        }),

                    'sub_total_receipts' => ReceiptItem::selectRaw('SUM(sub_total)')
                        ->whereColumn('product_id', 'products.id')
                        ->whereHas('receipt', function ($query) use ($date1, $date2) {
                            $query->whereDate('receipt_date', '>=', $date1)
                                ->whereDate('receipt_date', '<=', $date2);
                        }),
                ])
                ->where('categories.name', $this->category)
                ->get()
                ->map(function ($product) {
                    // Calculate the total sold by summing invoices and receipts
                    $product->total_sold = ($product->total_sold_invoices ?? 0) + ($product->total_sold_receipts ?? 0);

                    // Calculate total sales
                    $product->total_sales = ($product->sub_total_invoices ?? 0) + ($product->sub_total_receipts ?? 0);

                    // Calculate gross profit
                    $product->gross_profit = ($product->selling_price - $product->purchase_cost) * $product->total_sold;

                    // Calculate average price
                    $total_sub_total = ($product->sub_total_invoices ?? 0) + ($product->sub_total_receipts ?? 0);
                    $product->average_price = $product->total_sold > 0 ? $total_sub_total / $product->total_sold : 0;

                    // Calculate profit margin
                    // Corrected formula: (Total Sales - Total Cost) / Total Sales * 100
                    $total_cost = $product->purchase_cost * $product->total_sold;
                    $product->profit_margin = $product->total_sales > 0
                        ? (($product->total_sales - $total_cost) / $product->total_sales) * 100
                        : 0;

                    return $product;
                });

            // Step 2: Group products by category
            $groupedByCategory = $products->groupBy(function ($product) {
                return $product->category_id . '|' . $product->category_name;
            })->map(function ($categoryGroup, $categoryKey) {
                // Extract category ID and name
                [$categoryId, $categoryName] = explode('|', $categoryKey);

                // Step 3: Within each category, group by brand and calculate metrics
                $brands = $categoryGroup->groupBy(function ($product) {
                    return $product->brand_id . '|' . $product->brand_name;
                })->map(function ($brandGroup, $brandKey) {
                    // Extract brand ID and name
                    [$brandId, $brandName] = explode('|', $brandKey);

                    // Calculate total sold for the brand
                    $brandTotalSold = $brandGroup->sum('total_sold');

                    // Calculate total sales for the brand
                    $brandTotalSales = $brandGroup->sum('total_sales');

                    // Calculate gross profit for the brand
                    $brandGrossProfit = $brandGroup->sum('gross_profit');

                    // Calculate average price for the brand
                    $brandAveragePrice = $brandTotalSold > 0 ? $brandTotalSales / $brandTotalSold : 0;

                    // Calculate total cost for the brand correctly
                    $brandTotalCost = $brandGroup->sum(function ($product) {
                        return $product->purchase_cost * $product->total_sold;
                    });

                    // Calculate profit margin for the brand
                    $brandProfitMargin = $brandTotalSales > 0
                        ? (($brandTotalSales - $brandTotalCost) / $brandTotalSales) * 100
                        : 0;

                    return [
                        'brand_id' => $brandId,
                        'brand_name' => $brandName,
                        'total_sold' => $brandTotalSold,
                        'total_sales' => $brandTotalSales,
                        'gross_profit' => $brandGrossProfit,
                        'average_price' => $brandAveragePrice,
                        'profit_margin' => $brandProfitMargin,
                        'products' => $brandGroup->sortByDesc('total_sold')->values(),
                    ];
                });

                // Step 4: Sort brands within the category by total_sold descending
                $sortedBrands = $brands->sortByDesc('total_sold')->values();

                // Calculate total sold and sales for the category
                $categoryTotalSold = $categoryGroup->sum('total_sold');
                $categoryTotalSales = $categoryGroup->sum('total_sales');
                $categoryGrossProfit = $categoryGroup->sum('gross_profit');
                $categoryAveragePrice = $categoryTotalSold > 0 ? $categoryTotalSales / $categoryTotalSold : 0;

                // Calculate total cost for the category correctly
                $categoryTotalCost = $categoryGroup->sum(function ($product) {
                    return $product->purchase_cost * $product->total_sold;
                });

                // Calculate profit margin for the category
                $categoryProfitMargin = $categoryTotalSales > 0
                    ? (($categoryTotalSales - $categoryTotalCost) / $categoryTotalSales) * 100
                    : 0;

                return [
                    'category_id' => $categoryId,
                    'category_name' => $categoryName,
                    'total_sold' => $categoryTotalSold,
                    'total_sales' => $categoryTotalSales,
                    'gross_profit' => $categoryGrossProfit,
                    'average_price' => $categoryAveragePrice,
                    'profit_margin' => $categoryProfitMargin,
                    'brands' => $sortedBrands,
                ];
            });

            // Step 5: Optionally, sort categories as needed (e.g., by total_sold descending)
            $sortedCategories = $groupedByCategory->sortByDesc('total_sold')->values();

            // Assign to your data array
            $data['products'] = $sortedCategories;
        }

        $data['category'] = $this->category;
        $data['date1'] = Carbon::parse($date1);
        $data['date2'] = Carbon::parse($date2);

        return view('backend.user.reports.exports.sales_by_product', $data);
    }
}

<?php

namespace App\Exports;

use App\Models\InventoryAdjustment;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\ReceiptItem;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class InventorySummaryExport implements FromView
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function view() :View
    {
        $products = Product::select(
            'products.*',
            'sub_categories.id as category_id',
            'sub_categories.name as category_name',
            'product_brands.id as brand_id',
            'product_brands.name as brand_name'
        )
            ->join('sub_categories', 'sub_categories.id', '=', 'products.sub_category_id')
            ->join('product_brands', 'product_brands.id', '=', 'products.brand_id')
            ->addSelect([
                'total_sold_invoices' => InvoiceItem::selectRaw('IFNULL(SUM(quantity), 0)')
                    ->whereColumn('product_id', 'products.id'),
                'total_sold_receipts' => ReceiptItem::selectRaw('IFNULL(SUM(quantity), 0)')
                    ->whereColumn('product_id', 'products.id'),
                'total_stock_in' => PurchaseItem::selectRaw('IFNULL(SUM(quantity), 0)')
                    ->whereColumn('product_id', 'products.id'),
                'total_stock_adjustment_added' => InventoryAdjustment::selectRaw('IFNULL(SUM(adjusted_quantity), 0)')
                    ->whereColumn('product_id', 'products.id')
                    ->where('adjustment_type', 'adds'),
                'total_stock_adjustment_deducted' => InventoryAdjustment::selectRaw('IFNULL(SUM(adjusted_quantity), 0)')
                    ->whereColumn('product_id', 'products.id')
                    ->where('adjustment_type', 'deducts'),
            ])
            ->get()
            ->map(function ($product) {
                // Calculate the total sold by summing invoices and receipts
                $product->total_sold = $product->total_sold_invoices + $product->total_sold_receipts;
                $product->total_stock_cost = $product->stock * $product->purchase_cost;
                return $product;
            });

        // Step 2: Group products by category
        $groupedByCategory = $products->groupBy(function ($product) {
            return $product->category_id . '|' . $product->category_name;
        })->map(function ($categoryGroup, $categoryKey) {
            // Extract category ID and name
            [$categoryId, $categoryName] = explode('|', $categoryKey);

            // Step 3: Within each category, group by brand and calculate total_sold per brand
            $brands = $categoryGroup->groupBy(function ($product) {
                return $product->brand_id . '|' . $product->brand_name;
            })->map(function ($brandGroup, $brandKey) {
                // Extract brand ID and name
                [$brandId, $brandName] = explode('|', $brandKey);

                // Calculate total sold for the brand
                $brandTotalSold = $brandGroup->sum('total_sold');

                return [
                    'brand_id' => $brandId,
                    'brand_name' => $brandName,
                    'total_sold' => $brandTotalSold,
                    'products' => $brandGroup->sortByDesc('total_sold')->values(),
                ];
            });

            // Step 4: Sort brands within the category by total_sold descending
            $sortedBrands = $brands->sortByDesc('total_sold')->values();

            return [
                'category_id' => $categoryId,
                'category_name' => $categoryName,
                'total_sold' => $categoryGroup->sum('total_sold'),
                'brands' => $sortedBrands,
            ];
        });

        // Step 5: Optionally, sort categories as needed (e.g., by total_sold descending)
        $sortedCategories = $groupedByCategory->sortByDesc('total_sold')->values();

        // Assign to your data array
        $data['products'] = $sortedCategories;

        return view('backend.user.reports.exports.inventory_summary', $data);
    }
}

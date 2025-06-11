<?php

namespace App\Exports;

use App\Models\InventoryAdjustment;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\ReceiptItem;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class InventoryDetailsExport implements FromView
{
    protected $date1;
    protected $date2;
    protected $sub_category;

    public function __construct($date1, $date2, $sub_category)
    {
        $this->date1 = $date1;
        $this->date2 = $date2;
        $this->sub_category = $sub_category;
    }
    
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
            'product_brands.name as brand_name',
            'main_categories.id as main_category_id',
            'main_categories.name as main_category_name'
        )
            ->join('sub_categories', 'sub_categories.id', '=', 'products.sub_category_id')
            ->join('product_brands', 'product_brands.id', '=', 'products.brand_id')
            ->join('main_categories', 'main_categories.id', '=', 'sub_categories.main_category_id')
            ->where('products.stock_management', 1)
            ->addSelect([
                'total_sold_invoices' => InvoiceItem::selectRaw('IFNULL(SUM(quantity), 0)')
                    ->whereColumn('product_id', 'products.id')
                    ->whereHas('invoice', function ($query) {
                        $query->whereDate('invoice_date', '>=', $this->date1)
                            ->whereDate('invoice_date', '<=', $this->date2);
                    }),
                'total_sold_receipts' => ReceiptItem::selectRaw('IFNULL(SUM(quantity), 0)')
                    ->whereColumn('product_id', 'products.id')
                    ->whereHas('receipt', function ($query) {
                        $query->whereDate('receipt_date', '>=', $this->date1)
                            ->whereDate('receipt_date', '<=', $this->date2);
                    }),
                'total_stock_in' => PurchaseItem::selectRaw('IFNULL(SUM(quantity), 0)')
                    ->whereColumn('product_id', 'products.id')
                    ->whereHas('purchase', function ($query) {
                        $query->whereDate('purchase_date', '>=', $this->date1)
                            ->whereDate('purchase_date', '<=', $this->date2);
                    }),
                'total_stock_adjustment_added' => InventoryAdjustment::selectRaw('IFNULL(SUM(adjusted_quantity), 0)')
                    ->whereColumn('product_id', 'products.id')
                    ->where('adjustment_type', 'adds')
                    ->whereDate('adjustment_date', '>=', $this->date1)
                    ->whereDate('adjustment_date', '<=', $this->date2),
                'total_stock_adjustment_deducted' => InventoryAdjustment::selectRaw('IFNULL(SUM(adjusted_quantity), 0)')
                    ->whereColumn('product_id', 'products.id')
                    ->where('adjustment_type', 'deducts')
                    ->whereDate('adjustment_date', '>=', $this->date1)
                    ->whereDate('adjustment_date', '<=', $this->date2),
            ])
            ->get()
            ->map(function ($product) {
                // Calculate the total sold by summing invoices and receipts
                $product->total_sold = $product->total_sold_invoices + $product->total_sold_receipts;
                $product->total_stock_cost = $product->stock * $product->purchase_cost;
                return $product;
            });

        // Group products by category
        $groupedByCategory = $products->groupBy(function ($product) {
            return $product->category_id . '|' . $product->category_name;
        })->map(function ($categoryGroup, $categoryKey) {
            // Extract category ID and name
            [$categoryId, $categoryName] = explode('|', $categoryKey);

            // Within each category, group by brand and calculate total_sold per brand
            $brands = $categoryGroup->groupBy(function ($product) {
                return $product->brand_id . '|' . $product->brand_name;
            })->map(function ($brandGroup, $brandKey) {
                // Extract brand ID and name
                [$brandId, $brandName] = explode('|', $brandKey);

                return collect([
                    'brand_id' => $brandId,
                    'brand_name' => $brandName,
                    'total_sold' => $brandGroup->sum('total_sold'),
                    'total_stock_cost' => $brandGroup->sum('total_stock_cost'),
                    'products' => $brandGroup->sortByDesc('total_sold')->values(),
                ]);
            })->values();

            return collect([
                'category_id' => $categoryId,
                'category_name' => $categoryName,
                'total_sold' => $categoryGroup->sum('total_sold'),
                'total_stock_cost' => $categoryGroup->sum('total_stock_cost'),
                'brands' => $brands->sortByDesc('total_sold'),
            ]);
        })->values();

        // Sort categories by total_sold descending
        $data['products'] = $groupedByCategory->sortByDesc('total_sold');

        return view('backend.user.reports.exports.inventory_details', $data);
    }
}

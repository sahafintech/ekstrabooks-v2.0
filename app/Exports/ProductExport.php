<?php

namespace App\Exports;

use App\Models\InventoryAdjustment;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\PurchaseItem;
use App\Models\ReceiptItem;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ProductExport implements FromView
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function view(): View
    {
        $products = Product::addSelect([
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
            ->get();

        return view('backend.user.reports.exports.products', [
            'products' => $products
        ]);
    }
}

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
        $products = Product::select(
            'products.*',
            'sub_categories.id as sub_category_id',
            'sub_categories.name as sub_category_name',
            'product_brands.id as brand_id',
            'product_brands.name as brand_name',
            'main_categories.id as main_category_id',
            'main_categories.name as main_category_name',
            'product_units.unit as product_unit_name',
            'income_accounts.account_name as income_account_name',
            'expense_accounts.account_name as expense_account_name',
        )
            ->leftJoin('sub_categories', 'sub_categories.id', '=', 'products.sub_category_id')
            ->leftJoin('product_brands', 'product_brands.id', '=', 'products.brand_id')
            ->leftJoin('main_categories', 'main_categories.id', '=', 'sub_categories.main_category_id')
            ->leftJoin('product_units', 'product_units.id', '=', 'products.product_unit_id')
            ->leftJoin('accounts as income_accounts', 'income_accounts.id', '=', 'products.income_account_id')
            ->leftJoin('accounts as expense_accounts', 'expense_accounts.id', '=', 'products.expense_account_id')
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
            ->get();

        return view('backend.user.reports.exports.products', [
            'products' => $products
        ]);
    }
}

<?php

namespace App\Models;

use App\Models\ProductUnit;
use App\Models\InvoiceItem;
use App\Models\PurchaseItem;
use App\Models\SalesReturnItem;
use App\Models\PurchaseReturnItem;
use App\Models\Vendor;
use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Product extends Model {
    use MultiTenant;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'products';

    protected $fillable = ['name', 'type', 'product_unit_id', 'purchase_cost', 'selling_price', 'image', 'descriptions', 'stock_management', 'stock', 'allow_for_selling', 'allow_for_purchasing', 'status', 'income_account_id', 'sub_category_id', 'brand_id', 'expense_account_id', 'code', 'expiry_date', 'user_id', 'business_id', 'created_user_id', 'updated_user_id'];

    public function scopeActive($query) {
        return $query->where('status', 1);
    }

    public function product_unit() {
        return $this->belongsTo(ProductUnit::class)->withDefault();
    }

    public function income_account() {
        return $this->belongsTo(Account::class, 'income_account_id', 'id')->withDefault();
    }

    public function expense_account() {
        return $this->belongsTo(Account::class, 'expense_account_id', 'id')->withDefault();
    }

    protected function sellingPrice(): Attribute {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get:fn(string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    protected function purchaseCost(): Attribute {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get:fn(string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    public function category() {
        return $this->belongsTo(SubCategory::class, 'sub_category_id', 'id')->withDefault();
    }

    public function brand() {
        return $this->belongsTo(Brands::class)->withDefault();
    }

    public function invoice_items() {
        return $this->hasMany(InvoiceItem::class);
    }

    public function receipt_items() {
        return $this->hasMany(ReceiptItem::class);
    }

    public function purchase_items() {
        return $this->hasMany(PurchaseItem::class);
    }

    public function sales_return_items() {
        return $this->hasMany(SalesReturnItem::class);
    }

    public function purchase_return_items() {
        return $this->hasMany(PurchaseReturnItem::class);
    }

    /**
     * Get all transactions related to this product
     *
     * @return \Illuminate\Support\Collection
     */
    public function getAllTransactions() {
        $sales = $this->invoice_items()
            ->with('invoice')
            ->select(
                'invoice_date as date',
                'invoice_items.quantity',
                'invoice_items.unit_cost as unit_price',
                'invoice_items.sub_total as total',
                'invoices.invoice_number as reference'
            )
            ->join('invoices', 'invoices.id', '=', 'invoice_items.invoice_id')
            ->join('customers', 'customers.id', '=', 'invoices.customer_id')
            ->selectRaw("'Credit Invoice' as type")
            ->selectRaw('customers.name as party_name')
            ->selectRaw("CONCAT('/user/invoices/', invoices.id) as reference_url");

        $receipts = $this->receipt_items()
            ->with('receipt')
            ->select(
                'receipt_date as date',
                'receipt_items.quantity',
                'receipt_items.unit_cost as unit_price',
                'receipt_items.sub_total as total',
                'receipts.receipt_number as reference'
            )
            ->join('receipts', 'receipts.id', '=', 'receipt_items.receipt_id')
            ->join('customers', 'customers.id', '=', 'receipts.customer_id')
            ->selectRaw("'Cash Invoice' as type")
            ->selectRaw('customers.name as party_name')
            ->selectRaw("CONCAT('/user/receipts/', receipts.id) as reference_url");

        $purchases = $this->purchase_items()
            ->with('purchase')
            ->select(
                'purchase_date as date',
                'purchase_items.quantity',
                'purchase_items.unit_cost as unit_price',
                'purchase_items.sub_total as total',
                'purchases.bill_no as reference'
            )
            ->join('purchases', 'purchases.id', '=', 'purchase_items.purchase_id')
            ->join('vendors', 'vendors.id', '=', 'purchases.vendor_id')
            ->selectRaw("CASE WHEN purchases.cash = 1 THEN 'Cash Purchase' ELSE 'Bill Invoice' END as type")
            ->selectRaw('vendors.name as party_name')
            ->selectRaw("CASE WHEN purchases.cash = 1 THEN CONCAT('/user/cash_purchases/', purchases.id) ELSE CONCAT('/user/bill_invoices/', purchases.id) END as reference_url");

        $sale_returns = $this->sales_return_items()
            ->with('sales_return')
            ->select(
                'return_date as date',
                'sales_return_items.quantity',
                'sales_return_items.unit_cost as unit_price',
                'sales_return_items.sub_total as total',
                'sales_returns.return_number as reference'
            )
            ->join('sales_returns', 'sales_returns.id', '=', 'sales_return_items.sales_return_id')
            ->join('customers', 'customers.id', '=', 'sales_returns.customer_id')
            ->selectRaw("'Sales Return' as type")
            ->selectRaw('customers.name as party_name')
            ->selectRaw("CONCAT('/user/sales_returns/', sales_returns.id) as reference_url");

        $purchase_returns = $this->purchase_return_items()
            ->with('purchase_return')
            ->select(
                'return_date as date',
                'purchase_return_items.quantity',
                'purchase_return_items.unit_cost as unit_price',
                'purchase_return_items.sub_total as total',
                'purchase_returns.return_number as reference'
            )
            ->join('purchase_returns', 'purchase_returns.id', '=', 'purchase_return_items.purchase_return_id')
            ->join('vendors', 'vendors.id', '=', 'purchase_returns.vendor_id')
            ->selectRaw("'Purchase Return' as type")
            ->selectRaw('vendors.name as party_name')
            ->selectRaw("CONCAT('/user/purchase_returns/', purchase_returns.id) as reference_url");

        return $sales->union($purchases)
            ->union($receipts)
            ->union($sale_returns)
            ->union($purchase_returns)
            ->orderBy('date', 'desc')
            ->get();
    }
    
    /**
     * Get all vendors who supplied this product, along with their purchase metrics
     *
     * @return \Illuminate\Support\Collection
     */
    public function getSuppliers() {
        return Vendor::select(
                'vendors.id',
                'vendors.name',
                'vendors.company_name',
                'vendors.email',
                'vendors.mobile',
                'vendors.country'
            )
            ->selectRaw('COUNT(DISTINCT purchases.id) as purchase_count')
            ->selectRaw('SUM(purchase_items.quantity) as total_quantity')
            ->selectRaw('AVG(purchase_items.unit_cost) as avg_unit_cost')
            ->selectRaw('MAX(purchases.purchase_date) as last_purchase_date')
            ->join('purchases', 'purchases.vendor_id', '=', 'vendors.id')
            ->join('purchase_items', function($join) {
                $join->on('purchases.id', '=', 'purchase_items.purchase_id')
                    ->where('purchase_items.product_id', '=', $this->id);
            })
            ->groupBy(
                'vendors.id',
                'vendors.name',
                'vendors.company_name',
                'vendors.email',
                'vendors.mobile',
                'vendors.country'
            )
            ->orderBy('total_quantity', 'desc')
            ->get();
    }
}
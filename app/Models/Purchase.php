<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Purchase extends Model {
    use MultiTenant, SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'purchases';

    public function items() {
        return $this->hasMany(PurchaseItem::class, 'purchase_id')->withoutGlobalScopes();
    }

    public function taxes() {
        return $this->hasMany(PurchaseItemTax::class, "purchase_id")
            ->withoutGlobalScopes()
            ->selectRaw('purchase_item_taxes.*, sum(amount) as amount')
            ->groupBy('tax_id');
    }

    public function vendor() {
        return $this->belongsTo(Vendor::class, 'vendor_id')->withDefault()->withoutGlobalScopes();
    }

    public function business() {
        return $this->belongsTo(Business::class, 'business_id')->withDefault()->withoutGlobalScopes();
    }

    public function transactions() {
        return $this->hasMany(Transaction::class, 'ref_id')->where('ref_type', 'purchase')->withoutGlobalScopes();
    }

    public function approvals() {
        return $this->hasMany(Approvals::class, 'ref_id')->where('ref_name', 'purchase')->where('checker_type', 'approval')->withoutGlobalScopes();
    }

    /**
     * Get checker validation records for this purchase
     */
    public function checkers() {
        return $this->hasMany(Approvals::class, 'ref_id')->where('ref_name', 'purchase')->where('checker_type', 'checker')->withoutGlobalScopes();
    }

    /**
     * Get all approval and checker records for this purchase
     */
    public function allApprovalRecords() {
        return $this->hasMany(Approvals::class, 'ref_id')->where('ref_name', 'purchase')->withoutGlobalScopes();
    }

    /**
     * Get the user who checked this purchase
     */
    public function checkedByUser() {
        return $this->belongsTo(User::class, 'checked_by')->withDefault()->withoutGlobalScopes();
    }

    protected function subTotal(): Attribute {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get:fn(string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    protected function discount(): Attribute {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get:fn(string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    protected function grandTotal(): Attribute {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get:fn(string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    protected function convertedTotal(): Attribute {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get:fn(string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    protected function paid(): Attribute {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get:fn(string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    protected function purchaseDate(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn(string $value) => \Carbon\Carbon::parse($value)->format("$date_format"),
        );
    }

    protected function dueDate(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn(string $value) => \Carbon\Carbon::parse($value)->format("$date_format"),
        );
    }

}
<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseReturn extends Model
{
    use HasFactory;

    use MultiTenant;

    protected $table = 'purchase_returns';

    protected $fillable = [
        'return_date',
        'return_number',
        'vendor_id',
        'grand_total',
        'converted_total',
        'currency',
        'status',
        'paid',
        'note',
        'footer',
    ];

    public function items()
    {
        return $this->hasMany(PurchaseReturnItem::class, 'purchase_return_id')->withoutGlobalScopes();
    }

    public function taxes()
    {
        return $this->hasMany(PurchaseReturnItemTax::class, "purchase_return_id")
            ->withoutGlobalScopes()
            ->selectRaw('purchase_return_item_taxes.*, sum(amount) as amount')
            ->groupBy('tax_id');
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id')->withDefault()->withoutGlobalScopes();
    }

    public function transactions() {
        return $this->hasMany(Transaction::class, 'ref_id')->where('ref_type', 'p return')->withoutGlobalScopes();
    }

    public function business()
    {
        return $this->belongsTo(Business::class, 'business_id')->withDefault()->withoutGlobalScopes();
    }
    
    protected function subTotal(): Attribute
    {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get: fn (string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    protected function discount(): Attribute
    {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get: fn (string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    protected function grandTotal(): Attribute
    {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get: fn (string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    protected function convertedTotal(): Attribute
    {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get: fn (string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    protected function paid(): Attribute
    {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get: fn (string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    protected function returnDate(): Attribute
    {
        $date_format = get_date_format();

        return Attribute::make(
            get: fn (string $value) => \Carbon\Carbon::parse($value)->format("$date_format"),
        );
    }
}

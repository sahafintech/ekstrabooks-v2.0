<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Quotation extends Model {
    use MultiTenant;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'quotations';

    public function items() {
        return $this->hasMany(QuotationItem::class, 'quotation_id')->withoutGlobalScopes();
    }

    public function taxes() {
        return $this->hasMany(QuotationItemTax::class, "quotation_id")
            ->withoutGlobalScopes()
            ->selectRaw('quotation_item_taxes.*, sum(amount) as amount')
            ->groupBy('tax_id');
    }

    public function customer() {
        return $this->belongsTo(Customer::class, 'customer_id')->withDefault()->withoutGlobalScopes();
    }

    public function business() {
        return $this->belongsTo(Business::class, 'business_id')->withDefault()->withoutGlobalScopes();
    }

    public function invoice_template() {
        return $this->belongsTo(InvoiceTemplate::class, 'template')->withDefault()->withoutGlobalScopes();
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

    protected function quotationDate(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn(string $value) => \Carbon\Carbon::parse($value)->format("$date_format"),
        );
    }

    protected function expiredDate(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn(string $value) => \Carbon\Carbon::parse($value)->format("$date_format"),
        );
    }

}
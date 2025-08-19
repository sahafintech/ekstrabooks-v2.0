<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Invoice extends Model {
    
    use MultiTenant, SoftDeletes;
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'invoices';

    public function items() {
        return $this->hasMany(InvoiceItem::class, 'invoice_id')->withoutGlobalScopes();
    }

    public function taxes() {
        return $this->hasMany(InvoiceItemTax::class, "invoice_id")
            ->withoutGlobalScopes()
            ->selectRaw('invoice_item_taxes.*, sum(amount) as amount')
            ->groupBy('tax_id');
    }

    public function customer() {
        return $this->belongsTo(Customer::class, 'customer_id')->withDefault()->withoutGlobalScopes();
    }

    public function client() {
        return $this->belongsTo(Customer::class, 'client_id')->withDefault()->withoutGlobalScopes();
    }

    public function business() {
        return $this->belongsTo(Business::class, 'business_id')->withDefault()->withoutGlobalScopes();
    }

    public function transactions() {
        return $this->hasMany(Transaction::class, 'ref_id')->where('ref_type', 'invoice')->withoutGlobalScopes();
    }

    public function project() {
        return $this->belongsTo(Project::class, 'project_id')->withDefault()->withoutGlobalScopes();
    }

    public function receive_payments() {
        return $this->belongsToMany(ReceivePayment::class, 'invoice_payments', 'invoice_id', 'payment_id');
    }

    public function deffered_additions() {
        return $this->hasMany(DefferedAddition::class);
    }

    public function deffered_deductions() {
        return $this->hasMany(DefferedDeduction::class);
    }

    public function invoice_template() {
        return $this->belongsTo(InvoiceTemplate::class, 'template')->withDefault()->withoutGlobalScopes();
    }

    public function deffered_payments() {
        return $this->hasMany(DefferedPayment::class, 'invoice_id')->withoutGlobalScopes();
    }

    public function deffered_earnings() {
        return $this->hasMany(DefferedEarning::class, 'invoice_id')->withoutGlobalScopes();
    }

    public function payments() {
        return $this->hasMany(DefferedPayment::class, 'invoice_id')->withoutGlobalScopes();
    }

    public function createdBy() {
        return $this->belongsTo(User::class, 'created_by')->withDefault()->withoutGlobalScopes();
    }

    public function updatedBy() {
        return $this->belongsTo(User::class, 'updated_by')->withDefault()->withoutGlobalScopes();
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

    protected function invoiceDate(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn($value) => $value != null ? \Carbon\Carbon::parse($value)->format("$date_format") : null,
        );
    }

    protected function dueDate(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn($value) => $value != null ? \Carbon\Carbon::parse($value)->format("$date_format") : null,
        );
    }

    protected function defferedStart(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn($value) => $value != null ? \Carbon\Carbon::parse($value)->format("$date_format") : null,
        );
    }

    protected function defferedEnd(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn($value) => $value != null ? \Carbon\Carbon::parse($value)->format("$date_format") : null,
        );
    }

    protected function recurringStart(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn($value) => $value != null ? \Carbon\Carbon::parse($value)->format("$date_format") : null,
        );
    }

    protected function recurringInvoiceDate(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn($value) => $value != null ? \Carbon\Carbon::parse($value)->format("$date_format") : null,
        );
    }

    protected function recurringDueDate(): Attribute {
        $date_format = get_date_format();
        $recurring_due_date = date("Y-m-d", strtotime($this->getRawOriginal('recurring_invoice_date').' '.$this->getRawOriginal('recurring_due_date')));

        return Attribute::make(
            get:fn($value) => \Carbon\Carbon::parse($recurring_due_date)->format("$date_format"),
        );
    }

}
<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use MultiTenant;
    /**
     * The table associated with the model.
     *
     * @var string
     */

    protected $table = 'transactions';

    protected $fillable = ['trans_date', 'account_id', 'dr_cr', 'type', 'transaction_amount', 'currency_rate', 'transaction_currency', 'base_currency_amount', 'transaction_method', 'reference', 'description', 'attachment', 'ref_id', 'ref_type', 'customer_id', 'vendor_id', 'user_id', 'business_id', 'created_user_id', 'updated_user_id'];

    public function account()
    {
        return $this->belongsTo(Account::class, 'account_id')->withDefault([
            'account_name' => _lang('Not Specified'),
        ]);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class, 'ref_id')->withDefault();
    }

    public function vendor()
    {
        return $this->belongsTo(Vendor::class, 'vendor_id')->withDefault();
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id')->withDefault();
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class, 'employee_id')->withDefault();
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class, 'tax_id')->withDefault();
    }

    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'ref_id')->withDefault();
    }

    public function payslips()
    {
        return $this->hasMany(Payroll::class, 'transaction_id');
    }

    protected function transactionAmount(): Attribute
    {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get: fn(string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    protected function currencyRate(): Attribute
    {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get: fn(string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    protected function baseCurrencyAmount(): Attribute
    {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get: fn(string $value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    protected function convertedAmount(): Attribute
    {
        $decimal_place = get_business_option('decimal_places', 2);

        return Attribute::make(
            get: fn($value) => number_format($value, $decimal_place, '.', ''),
        );
    }

    public function created_by()
    {
        return $this->belongsTo(User::class, 'created_user_id')->withDefault();
    }

    public function updated_by()
    {
        return $this->belongsTo(User::class, 'updated_user_id')->withDefault(['name' => _lang('N/A')]);
    }

    protected function createdAt(): Attribute
    {
        $date_format = get_date_format();
        $time_format = get_time_format();

        return Attribute::make(
            get: fn(string $value) => \Carbon\Carbon::parse($value)->format("$date_format $time_format"),
        );
    }

    protected function updatedAt(): Attribute
    {
        $date_format = get_date_format();
        $time_format = get_time_format();

        return Attribute::make(
            get: fn(string $value) => \Carbon\Carbon::parse($value)->format("$date_format $time_format"),
        );
    }

    protected function transDate(): Attribute
    {
        $date_format = get_date_format();

        return Attribute::make(
            get: fn(string $value) => \Carbon\Carbon::parse($value)->format("$date_format"),
        );
    }

    public function getPayeeNameAttribute()
    {
        if ($this->customer_id !== null) {
            return $this->customer->name;
        } else if ($this->vendor_id !== null) {
            return $this->vendor->name;
        } else if ($this->employee_id !== null) {
            return $this->employee->name;
        } else if ($this->tax_id !== null) {
            return $this->nameWIthTax();
        } else {
            return '';
        }
    }

    protected static function booted(): void
    {
        // static::saving(function (Transaction $transaction) {
        //     $businessCurrency = Currency::where('name', request()->activeBusiness->currency)->first();
        //     $currency         = Currency::where('name', $transaction->account->currency)->first();

        //     if (!$currency) {
        //         $transaction->currency_rate = 1.00;
        //     } else {
        //         if ($currency->name == $businessCurrency->name) {
        //             $transaction->currency_rate = 1.00;
        //         } else {
        //             $transaction->currency_rate = $currency->exchange_rate / $businessCurrency->exchange_rate;
        //         }
        //     }
        // });

        // static::updating(function (Transaction $transaction) {
        //     $businessCurrency = Currency::where('name', request()->activeBusiness->currency)->first();
        //     $currency         = Currency::where('name', $transaction->account->currency)->first();

        //     if (!$currency) {
        //         $transaction->currency_rate = 1.00;
        //     } else {
        //         if ($currency->name == $businessCurrency->name) {
        //             $transaction->currency_rate = 1.00;
        //         } else {
        //             $transaction->currency_rate = $currency->exchange_rate / $businessCurrency->exchange_rate;
        //         }
        //     }
        // });

        static::deleting(function (Transaction $transaction) {
            if ($transaction->ref_type == 'invoice') {
                $invoice       = Invoice::find($transaction->ref_id);
                $invoice->paid = $invoice->paid - $transaction->ref_amount;

                if ($invoice->transactions->count() == 0) {
                    $invoice->paid = 0;
                }
                if ($invoice->paid == 0) {
                    $invoice->status = 1; //Unpaid
                }
                if ($invoice->paid > 0) {
                    $invoice->status = 3; //Partially Paid
                }
                if ($invoice->paid >= $invoice->grand_total) {
                    $invoice->status = 2; //Paid
                }
                $invoice->save();
            }

            if ($transaction->ref_type == 'purchase') {
                $purcahse       = Purchase::find($transaction->ref_id);
                $purcahse->paid = $purcahse->paid - $transaction->ref_amount;

                if ($purcahse->transactions->count() == 0) {
                    $purcahse->paid = 0;
                }
                if ($purcahse->paid == 0) {
                    $purcahse->status = 0; //Unpaid
                }
                if ($purcahse->paid > 0) {
                    $purcahse->status = 1; //Partially Paid
                }
                if ($purcahse->paid >= $purcahse->grand_total) {
                    $purcahse->status = 2; //Paid
                }
                $purcahse->save();
            }

            if ($transaction->payslips->count() > 0) {
                $transaction->payslips()->update(['status' => 0]);
            }
        });
    }

    public function nameWIthTax() {
        if($this->ref_type === 'invoice tax' || $this->ref_type === 'd invoice tax') {
            return $this->tax->name . ' (' . Invoice::find($this->ref_id)->customer->name . ')';
        }else if($this->ref_type === 'receipt tax') {
            return $this->tax->name . ' (' . Receipt::find($this->ref_id)->customer->name . ')';
        }
        
        return $this->tax->name;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;

class HospitalPurchase extends Purchase
{
    public const ALLOWED_ACCOUNT_TYPES = ['Cost Of Sale'];

    protected static function booted(): void
    {
        parent::booted();

        static::addGlobalScope('hospital_purchase', function (Builder $builder) {
            $builder->where($builder->qualifyColumn('hospital_purchase'), 1);
        });

        static::creating(function (self $purchase) {
            $purchase->hospital_purchase = 1;
        });
    }
}

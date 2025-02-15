<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;

trait MultiTenant {

    public static function bootMultiTenant() {
        $table = (new self())->getTable();

        if (auth()->check()) {
            $user  = auth()->user();

            if(request()->has('activeBusiness')){
                $activeBusiness = request()->activeBusiness;
            }else{
                $business = $user->business();
                $activeBusiness = $business->withoutGlobalScopes()->wherePivot('is_active', 1)->with('user.package')->first();
            }

            static::saving(function ($model) use ($activeBusiness, $user) {
                if (Schema::hasColumn($model->table, 'business_id')) {
                    $model->business_id = $activeBusiness->id;
                }
                if (Schema::hasColumn($model->table, 'user_id')) {
                    $model->user_id = $activeBusiness->user_id;
                }
                if (Schema::hasColumn($model->table, 'created_user_id')) {
                    if (!$model->exists) {
                        $model->created_user_id = $user->id;
                    }
                }
                if (Schema::hasColumn($model->table, 'updated_user_id')) {
                    if ($model->exists) {
                        $model->updated_user_id = $user->id;
                    }
                }
            });

            static::updating(function ($model) use ($activeBusiness, $user) {
                if (Schema::hasColumn($model->table, 'business_id')) {
                    $model->business_id = $activeBusiness->id;
                }
                if (Schema::hasColumn($model->table, 'user_id')) {
                    $model->user_id = $activeBusiness->user_id;
                }
                if (Schema::hasColumn($model->table, 'updated_user_id')) {
                    $model->updated_user_id = $user->id;
                }
            });

            static::addGlobalScope('business_id', function (Builder $builder) use ($activeBusiness, $table, $user) {
                if ($user->user_type != 'admin') {                 
                    if (Schema::hasColumn($table, 'business_id')) {
                        return $builder->where($table . '.business_id', $activeBusiness->id);
                    }

                    if (Schema::hasColumn($table, 'user_id')) {
                        return $builder->where($table . '.user_id', $user->id);
                    }
                }
            });

        }

    }

}
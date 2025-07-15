<?php

namespace App\Models;

use App\Traits\MultiTenant;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PendingTransaction extends Model
{
    use HasFactory, SoftDeletes, MultiTenant;

    /**
     * The table associated with the model.
     *
     * @var string
     */

    protected $table = 'pending_transactions';

    protected $fillable = ['trans_date', 'account_id', 'dr_cr', 'type', 'transaction_amount', 'currency_rate', 'transaction_currency', 'base_currency_amount', 'transaction_method', 'reference', 'description', 'attachment', 'ref_id', 'ref_type', 'customer_id', 'vendor_id', 'user_id', 'business_id', 'created_user_id', 'updated_user_id', 'deleted_user_id'];

    public function account()   
    {
        return $this->belongsTo(Account::class, 'account_id')->withDefault([
            'account_name' => _lang('Not Specified'),
        ]);
    }

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id')->withDefault();
    }

    public function project_task()
    {
        return $this->belongsTo(ProjectTask::class, 'project_task_id')->withDefault();
    }

    public function cost_code()
    {
        return $this->belongsTo(CostCode::class, 'cost_code_id')->withDefault();
    }
    
    protected function transDate(): Attribute{
        $date_format = get_date_format();

        return Attribute::make(
            get: fn(string $value) => \Carbon\Carbon::parse($value)->format("$date_format"),
        );
    }
}

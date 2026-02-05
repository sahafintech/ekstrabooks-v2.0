<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Approvals extends Model
{
    protected $table = 'approvals';

    protected $fillable = [
        'ref_id',
        'ref_name',
        'checker_type',
        'status',
        'comment',
        'action_date',
        'action_user',
    ];

    protected $casts = [
        'action_date' => 'datetime',
    ];

    /**
     * Get the user who actually performed the approval/rejection action
     * This is who clicked approve or reject
     */
    public function actionUser()
    {
        return $this->belongsTo(User::class, 'action_user');
    }

    /**
     * Get the purchase that this approval belongs to
     */
    public function purchase()
    {
        return $this->belongsTo(Purchase::class, 'ref_id');
    }

    /**
     * Check if approval is pending
     */
    public function isPending()
    {
        return $this->status === 0;
    }

    /**
     * Check if approval is approved
     */
    public function isApproved()
    {
        return $this->status === 1;
    }

    /**
     * Check if approval is rejected
     */
    public function isRejected()
    {
        return $this->status === 2;
    }
}

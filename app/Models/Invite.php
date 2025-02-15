<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class Invite extends Model {
	use Notifiable;
	/**
	 * The table associated with the model.
	 *
	 * @var string
	 */
	protected $table = 'user_invitations';

	/**
	 * The attributes that are mass assignable.
	 *
	 * @var array
	 */
	protected $fillable = [
		'email', 'sender_id', 'business_id', 'role_id', 'user_id', 'status',
	];

	public function scopeActive($query) {
		return $query->where('status', 1);
	}

	public function sender() {
		return $this->belongsTo('App\Models\User', 'sender_id');
	}

	public function business() {
		return $this->belongsTo('App\Models\Business', 'business_id');
	}

	public function role() {
		return $this->belongsTo('App\Models\Role', 'role_id');
	}
}
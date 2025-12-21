<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Models\Role;

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

	protected $casts = [
		'business_id' => 'array'
	];

	public function scopeActive($query) {
		return $query->where('status', 1);
	}

	public function sender() {
		return $this->belongsTo(User::class, 'sender_id');
	}

	public function businesses() {
		return $this->belongsToMany(Business::class, null, 'business_id', 'id')
			->whereIn('business.id', $this->business_id ?? []);
	}

	public function role() {
		return $this->belongsTo(Role::class, 'role_id');
	}
}
<?php

namespace App\Models;

use App\Models\Package;
use App\Models\Business;
use App\Utilities\Overrider;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail {
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'phone', 'password', 'user_type', 'status', 'package_id', 'profile_picture',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected $appends = ['business_ids', 'role'];

    public function scopeActive($query) {
        return $query->where('status', 1);
    }

    public function scopeStaff($query) {
        return $query->whereHas('business', function (Builder $query) {
            $query->where('owner_id', auth()->id())
                ->where('role_id', '!=', null);
        });
    }

    public function package() {
        return $this->hasOne(UserPackage::class);
    }

    public function subscriptionPayments() {
        return $this->hasMany(SubscriptionPayment::class, 'user_id');
    }

    public function business() {
        return $this->belongsToMany(Business::class, 'business_users')->withPivot('owner_id', 'is_active', 'role_id');
    }

    public function getBusinessIdsAttribute(){
        return BusinessUser::where('user_id', $this->id)->pluck('business_id')->toArray() ?? [];
    }

    public function getRoleAttribute(){
        return BusinessUser::where('user_id', $this->id)->first()->role_id ?? null;
    }

    protected function createdAt(): Attribute {
        $date_format = get_date_format();
        $time_format = get_time_format();

        return Attribute::make(
            get:fn($value) => \Carbon\Carbon::parse($value)->format("$date_format $time_format"),
        );
    }

    protected function subscriptionDate(): Attribute {
        $date_format = get_date_format();

        return Attribute::make(
            get:fn($value) => $value != null ? \Carbon\Carbon::parse($value)->format("$date_format") : null,
        );
    }

    public function sendEmailVerificationNotification()
    {
        if(get_option('email_verification') == 0) {
            return;
        }
        Overrider::load("Settings");
        $this->notify(new VerifyEmail);
    }

}

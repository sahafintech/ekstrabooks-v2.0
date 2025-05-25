<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\Business;
use App\Models\User;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $businesses = [];
        $activeBusiness = null;

        if ($user && $user->user_type !== 'admin') {
            $business = $user->business();
            $businesses = $business->withoutGlobalScopes()->get();
            $activeBusiness = $business
                ->withoutGlobalScopes()
                ->wherePivot('is_active', 1)
                ->first();
        }

        $isOwner = $activeBusiness && $activeBusiness->pivot->owner_id == $user->id ? true : false;
        $permissionList = $user?->select('permissions.*')
            ->join('business_users', 'users.id', 'business_users.user_id')
            ->join('business', 'business_users.business_id', 'business.id')
            ->join('permissions', 'business_users.role_id', 'permissions.role_id')
            ->where('business.id', $activeBusiness?->id)
            ->where('users.id', $user->id)
            ->get();

        $userPackage = $activeBusiness ? $activeBusiness->user->package : $user->package;

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'businesses' => $businesses,
            'activeBusiness' => $activeBusiness,
            'decimalPlace' => get_business_option('decimal_places', 2),
            'decimalSep' => get_business_option('decimal_sep', '.'),
            'thousandSep' => get_business_option('thousand_sep', ','),
            'baseCurrency' => get_business_option('currency', 'USD'),
            'currencyPosition' => get_business_option('currency_position', 'left'),
            'isOwner' => $isOwner,
            'permissionList' => $permissionList,
            'date_format' => get_business_option('date_format', 'Y-m-d'),
            'flash' => [
                'success' => fn() => $request->session()->get('success'),
                'error' => fn() => $request->session()->get('error'),
            ],
            'logo' => get_option('logo'),
            'userPackage' => $userPackage,
        ];
    }
}

<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use App\Models\Business;

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
        $active_business = null;

        if ($user && $user->user_type !== 'admin') {
            $businesses = Business::where('user_id', $user->id)->get();
            $active_business = $request->session()->get('active_business') ?? $businesses->first();
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user,
            ],
            'businesses' => $businesses,
            'active_business' => $active_business,
            'decimalPlace' => get_business_option('decimal_places', 2, $active_business->id),
            'decimalSep' => get_business_option('decimal_sep', '.', $active_business->id),
            'thousandSep' => get_business_option('thousand_sep', ',', $active_business->id),
            'baseCurrency' => get_business_option('base_currency', 'USD', $active_business->id),
            'currencyPosition' => get_business_option('currency_position', 'left', $active_business->id),
        ];
    }
}

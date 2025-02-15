<?php

namespace App\Http\Middleware;

use app\Models\Business;
use App\Models\UserPackage;
use Closure;
use Illuminate\Support\Facades\Session;

class SaaS {
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next) {
		if (auth()->check()) {
			$user = auth()->user();
			$user_package = UserPackage::where('user_id', $user->id)->first();
			$routeName = $request->route()->getName();

			if (!$request->has('businessList') && $user->user_type == 'user') {

				$business = $user->business();
				$businessList = $business->withoutGlobalScopes()->get();

				if (!$businessList->isEmpty()) {
					$activeBusiness = $business->withoutGlobalScopes()->wherePivot('is_active', 1)->with('user.package')->first();

					if ($activeBusiness == null) {
						$activeBusiness = $user->business()->withoutGlobalScopes()->with('user.package')->first();
						$user->business()->updateExistingPivot($activeBusiness->id, ['is_active' => 1]);
					}

					$isOwner = $activeBusiness->pivot->owner_id == $user->id ? true : false;
					$permissionList = $user->select('permissions.*')
						->join('business_users', 'users.id', 'business_users.user_id')
						->join('business', 'business_users.business_id', 'business.id')
						->join('permissions', 'business_users.role_id', 'permissions.role_id')
						->where('business.id', $activeBusiness->id)
						->where('users.id', $user->id)
						->get();

					date_default_timezone_set(get_business_option('timezone', 'Asia/Dhaka'));

					$request->merge([
						'businessList' => $businessList,
						'activeBusiness' => $activeBusiness,
						'isOwner' => $isOwner,
						'permissionList' => $permissionList,
					]);

					if ($user_package != null && $user_package->valid_to < date('Y-m-d')) {
						if ($isOwner) {
							if ($routeName != 'business.switch_business') {
								return redirect()->route('membership.payment_gateways')->with('error', _lang("Please make your subscription payment"));
							}
						} else {
							if ($routeName != 'dashboard.index' && $routeName != 'business.switch_business') {
								return redirect()->route('dashboard.index')->with('error', _lang("Your selected business subscription is expired"));
							}
							Session::flash('error', _lang("Your selected business subscription is expired"));
						}
					}
				} else {
					if ($user_package != null && ($user_package->valid_to < date('Y-m-d') || $user_package->valid_to == null)) {
						$package = UserPackage::where('user_id', $user->id)->first();

						//Apply Free Package
						if ($package->cost == 0) {
							$package->membership_type = 'member';
							$package->subscription_date = now();
							$package->valid_to = update_membership_date($package, $package->subscription_date);
							$user->s_email_send_at = null;
							$package->save();
							$user->save();
						}

						//Apply Trial Package
						if ($package->cost > 0 && $package->trial_days > 0 && $user->membership_type == '') {
							$package->membership_type = 'trial';
							$package->subscription_date = now();
							$package->valid_to = date('Y-m-d', strtotime($package->subscription_date . " + $package->trial_days days"));
							$package->save();
						}
					}

					if ($user_package != null) {
						$business = Business::createDefaultBusiness();
						return redirect()->route('business.edit', $business->id)->with('error', _lang("Please update your default business account"));
					}

					if ($user_package == null) {
						return redirect()->route('membership.packages')->with('error', _lang("Please choose your package first"));
					}

				}

			}
		}

		return $next($request);
	}
}

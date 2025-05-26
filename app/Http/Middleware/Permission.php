<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Request;

class Permission
{
	/**
	 * Handle an incoming request.
	 *
	 * @param  \Illuminate\Http\Request  $request
	 * @param  \Closure  $next
	 * @return mixed
	 */
	public function handle($request, Closure $next)
	{
		$user = auth()->user();
		$user_type = $user->user_type;

		if ($user_type != 'user') {
			return back()->with('error', _lang('Permission denied !'));
		}

		if ($user_type == 'user' && $request->isOwner == false) {
			$route_name = Request::route()->getName();

			if ($route_name != '' && $user_type == 'user') {

				if (explode(".", $route_name)[1] == "update") {
					$route_name = explode(".", $route_name)[0] . ".edit";
				} else if (explode(".", $route_name)[1] == "store") {
					$route_name = explode(".", $route_name)[0] . ".create";
				}
				if (! has_permission($route_name)) {
					return back()->with('error', _lang('Permission denied !'));
				}
			}
		}

		return $next($request);
	}
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Response;

class Business {
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next) {
        $user = auth()->user();

        if ($user->user_type != 'user' || $request->isOwner == false) {
            if (!$request->ajax()) {
                return back()->with('error', _lang('Permission denied !'));
            } else {
                return new Response('<h5 class="text-center text-danger">' . _lang('Permission denied !') . '</h5>');
            }
        }

        return $next($request);
    }
}

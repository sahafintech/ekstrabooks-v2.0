<?php

namespace App\Providers;

use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Model;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        Schema::defaultStringLength(191);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Paginator::useBootstrap();
        Model::unguard();
        Model::preventsLazyLoading();
        Model::preventAccessingMissingAttributes();

        // Super admins bypass all permission checks
        Gate::before(static function ($user): ?bool {
            return $user?->owner ? true : null;
        });
    }
}

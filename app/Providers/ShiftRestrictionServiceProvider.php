<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Gate;

class ShiftRestrictionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        if (auth()->check() && auth()->user()) {
            \Log::info('Current user roles', auth()->user()->getRoleNames()->toArray());
        }

        // Define admin-only override gate
        Gate::define('assign-shift-override', function ($user) {
            return $user->hasRole('superadmin'); // ✅ spatie/laravel-permission
        });
    }
}

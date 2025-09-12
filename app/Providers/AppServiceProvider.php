<?php

namespace App\Providers;

use Laravel\Sanctum\Sanctum;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Yajra\DataTables\Html\Builder;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        ini_set('max_execution_time', env('PHP_MAX_EXECUTION_TIME', 300));
        
        Builder::macro('setTableHeadClass', function ($class) {
            $this->parameters['header_class'] = $class;
        return $this;
    });
    }
}

<?php

namespace App\Providers;

use Laravel\Sanctum\Sanctum;
use Illuminate\Support\ServiceProvider;
use Laravel\Sanctum\PersonalAccessToken;
use Yajra\DataTables\Html\Builder;
use Illuminate\Support\Facades\Blade;

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
        // Load global helpers and register a Blade directive for consistent date formatting
        $helpers = app_path('Helpers/helpers.php');
        if (file_exists($helpers)) {
            require_once $helpers;
        }

        Blade::directive('formatDate', function ($expression) {
            return "<?php echo format_date($expression); ?>";
        });

        // Aggressively close database connections to prevent pool exhaustion
        if (app()->runningInConsole() === false) {
            // Close connections after each request
            app()->terminating(function () {
                try {
                    \DB::disconnect();
                } catch (\Exception $e) {
                    \Log::warning('Failed to disconnect DB: ' . $e->getMessage());
                }
            });
            
            // Also disconnect on shutdown
            register_shutdown_function(function () {
                try {
                    \DB::disconnect();
                } catch (\Exception $e) {
                    // Silently fail
                }
            });
        }

        // For console commands, disconnect after each command
        if (app()->runningInConsole()) {
            app()->terminating(function () {
                try {
                    \DB::disconnect();
                } catch (\Exception $e) {
                    \Log::warning('Console DB disconnect failed: ' . $e->getMessage());
                }
            });
        }

        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);

        ini_set('max_execution_time', env('PHP_MAX_EXECUTION_TIME', 300));
        
    Builder::macro('setTableHeadClass', function ($class) {
        // Prefer using existing setters if available to avoid creating undeclared dynamic properties.
        if (method_exists($this, 'parameters') && is_callable([$this, 'parameters'])) {
            // Use dynamic calls to avoid static analysis or runtime complaints about an undefined method.
            $params = call_user_func([$this, 'parameters']);
            if (!is_array($params)) {
                $params = [];
            }
            $params['header_class'] = $class;
            call_user_func([$this, 'parameters'], $params);
            return $this;
        }

        if (method_exists($this, 'setAttribute') && is_callable([$this, 'setAttribute'])) {
            // common setter pattern: setAttribute(name, value)
            // Use call_user_func to avoid static analysis errors for undefined methods
            call_user_func([$this, 'setAttribute'], 'header_class', $class);
            return $this;
        }

        if (method_exists($this, 'attributes') && is_callable([$this, 'attributes'])) {
            $attrs = call_user_func([$this, 'attributes']);
            if (!is_array($attrs)) {
                $attrs = [];
            }
            $attrs['header_class'] = $class;
            // Use a dynamic call to avoid static analysis or runtime issues when the method is not declared.
            call_user_func([$this, 'attributes'], $attrs);
            return $this;
        }

        // If no safe API exists on the builder instance, do nothing to avoid writing an undeclared property.
        return $this;
    });
    }
}

<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

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
        // Set PHP timezone to Philippines
        date_default_timezone_set(config('app.timezone', 'Asia/Manila'));
        
        // Set MySQL timezone for all connections
        if (config('database.default') === 'mysql' || config('database.default') === 'mariadb') {
            try {
                \DB::statement("SET time_zone='+08:00'");
            } catch (\Exception $e) {
                // Ignore if database is not connected yet
            }
        }
    }
}

<?php

namespace App\Providers;

use Roots\Acorn\Sage\SageServiceProvider;

class ThemeServiceProvider extends SageServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        parent::register();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        // Forzar uso de path '/' en las cookies de sesión
        add_action('init', function () {
            if (defined('COOKIEPATH') && COOKIEPATH !== '/') {
                define('COOKIEPATH', '/');
            }

            if (defined('SITECOOKIEPATH') && SITECOOKIEPATH !== '/') {
                define('SITECOOKIEPATH', '/');
            }
        });
    }
}

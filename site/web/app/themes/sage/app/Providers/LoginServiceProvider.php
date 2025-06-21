<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class LoginServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Redirige wp-login.php → /login
        add_action('init', function () {
            $uri = $_SERVER['REQUEST_URI'] ?? '';
            $is_login = str_contains($uri, 'wp-login.php');
            $is_api   = str_starts_with($uri, '/wp-json/');
            $is_cron  = defined('DOING_CRON') && DOING_CRON;

            if ($is_login && !$is_api && !$is_cron && !is_user_logged_in()) {
                wp_redirect(home_url('/login'));
                exit;
            }
        });

        // Redirige wp-admin → /login si no estás autenticado
        add_action('admin_init', function () {
            if (!is_user_logged_in() && !wp_doing_ajax()) {
                wp_redirect(home_url('/login'));
                exit;
            }
        });
    }
}

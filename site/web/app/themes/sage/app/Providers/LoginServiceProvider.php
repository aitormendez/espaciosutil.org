<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class LoginServiceProvider extends ServiceProvider
{
    public function register()
    {
        /**
         * Keep backend/editor auth on WordPress native login.
         *
         * PMPro rewrites wp_login_url() to the configured front-end login page.
         * For admin/editor flows we preserve wp-login.php so dashboard access,
         * block editor re-auth and other native WP auth modals do not go
         * through the front-end membership login page.
         */
        add_filter('login_url', function ($loginUrl, $redirect = '', $forceReauth = false) {
            $redirect = is_string($redirect) ? wp_unslash($redirect) : '';
            $redirectPath = wp_parse_url($redirect, PHP_URL_PATH);
            $requestPath = wp_parse_url(
                (string) wp_unslash($_SERVER['REQUEST_URI'] ?? ''),
                PHP_URL_PATH
            );

            $shouldUseDefaultLogin = is_admin()
                || !empty($forceReauth)
                || (is_string($requestPath) && str_starts_with($requestPath, '/wp/wp-admin'))
                || (is_string($requestPath) && str_starts_with($requestPath, '/wp/wp-login.php'))
                || (is_string($redirectPath) && str_starts_with($redirectPath, '/wp/wp-admin'));

            if (!$shouldUseDefaultLogin) {
                return $loginUrl;
            }

            $defaultLoginUrl = site_url('wp-login.php', 'login');

            if ($redirect !== '') {
                $defaultLoginUrl = add_query_arg('redirect_to', urlencode($redirect), $defaultLoginUrl);
            }

            if (!empty($forceReauth)) {
                $defaultLoginUrl = add_query_arg('reauth', '1', $defaultLoginUrl);
            }

            return $defaultLoginUrl;
        }, 100, 3);
    }
}

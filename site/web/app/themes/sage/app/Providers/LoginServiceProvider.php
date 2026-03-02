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
         * For redirects whose target is wp-admin, we preserve wp-login.php so
         * editor re-auth and dashboard access do not go through the front login page.
         */
        add_filter('login_url', function ($loginUrl, $redirect = '', $forceReauth = false) {
            $redirect = is_string($redirect) ? wp_unslash($redirect) : '';
            $redirectPath = wp_parse_url($redirect, PHP_URL_PATH);

            if (!is_string($redirectPath) || !str_starts_with($redirectPath, '/wp/wp-admin')) {
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

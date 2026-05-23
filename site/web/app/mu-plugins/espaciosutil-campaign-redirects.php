<?php

/**
 * Plugin Name: Espacio Sutil Campaign Redirects
 * Description: First-party short URLs for measured campaign traffic.
 */

declare(strict_types=1);

if (! function_exists('espaciosutil_campaign_redirect_destination')) {
    function espaciosutil_campaign_redirect_destination(string $requestUri): string
    {
        $path = parse_url($requestUri, PHP_URL_PATH);

        if (! is_string($path)) {
            return '';
        }

        $normalizedPath = '/'.trim($path, '/');

        if ($normalizedPath !== '/cde-tiktok') {
            return '';
        }

        return home_url('/curso-de-desarrollo-espiritual/?utm_source=tiktok&utm_medium=organic_social&utm_campaign=cde_launch_wave1&utm_content=profile_link');
    }
}

add_action('template_redirect', function (): void {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }

    $requestUri = (string) wp_unslash($_SERVER['REQUEST_URI'] ?? '');
    $destination = espaciosutil_campaign_redirect_destination($requestUri);

    if ($destination === '') {
        return;
    }

    wp_safe_redirect($destination, 302, 'Espacio Sutil Campaign Redirects');
    exit;
}, 0);

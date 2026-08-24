<?php

/**
 * Plugin Name: Espacio Sutil Legacy Redirects
 * Description: Permanent redirects for retired Espacio Sutil URLs with direct replacements.
 */

declare(strict_types=1);

if (! function_exists('espaciosutil_legacy_redirect_destination')) {
    function espaciosutil_legacy_redirect_destination(string $requestUri): string
    {
        $path = parse_url($requestUri, PHP_URL_PATH);

        if (! is_string($path)) {
            return '';
        }

        $normalizedPath = '/' . trim($path, '/');
        $redirects = [
            '/areas/un-manual-para-la-ascension' => '/series/un-manual-para-la-ascension/',
            '/noticias/satelite-de-terapia' => '/orientacion-terapeutica/',
        ];

        if (! isset($redirects[$normalizedPath])) {
            return '';
        }

        return home_url($redirects[$normalizedPath]);
    }
}

add_action('template_redirect', function (): void {
    if (is_admin() || wp_doing_ajax() || wp_doing_cron()) {
        return;
    }

    $requestUri = (string) wp_unslash($_SERVER['REQUEST_URI'] ?? '');
    $destination = espaciosutil_legacy_redirect_destination($requestUri);

    if ($destination === '') {
        return;
    }

    wp_safe_redirect($destination, 301, 'Espacio Sutil Legacy Redirects');
    exit;
}, 0);

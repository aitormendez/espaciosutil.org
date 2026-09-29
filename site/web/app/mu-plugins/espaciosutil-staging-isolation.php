<?php
/**
 * Plugin Name: Espacio Sutil — aislamiento de staging
 * Description: Bloquea correo e integraciones salientes en el entorno de pruebas.
 */

declare(strict_types=1);

if (! defined('WP_ENV') || WP_ENV !== 'staging') {
    return;
}

// Un bloqueo devuelve fallo; no simula una entrega correcta ni registra datos personales.
add_filter('pre_wp_mail', '__return_false', PHP_INT_MAX);
add_filter('pre_http_request', static function ($pre, $args, $url) {
    // Excepción de lectura exacta para la biblioteca del curso; sin redirecciones ni otros métodos.
    if (getenv('CDE_STAGING_BUNNY_READ') === '1'
        && strtoupper($args['method'] ?? 'GET') === 'GET'
        && ($args['redirection'] ?? 5) === 0
        && preg_match('~^https://video\.bunnycdn\.com/library/457097/videos/[a-f0-9-]{36}$~iD', $url)) {
        return $pre;
    }
    if (class_exists('EspacioSutil\\Mobile\\StripeMembership') && \EspacioSutil\Mobile\StripeMembership::allowsTestRequest($args, $url)) return $pre;
    return new WP_Error('staging_outbound_disabled', 'Las conexiones salientes están desactivadas en staging.');
}, PHP_INT_MAX, 3);
add_filter('espaciosutil_cde_listmonk_sync_enabled', '__return_false', PHP_INT_MAX);
add_filter('espaciosutil_pmpro_autocomplete_stripe_token_orders', '__return_false', PHP_INT_MAX);
add_filter('action_scheduler_allow_async_request_runner', '__return_false', PHP_INT_MAX);

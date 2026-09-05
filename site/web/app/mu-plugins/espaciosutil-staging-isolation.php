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
add_filter('pre_http_request', static function () {
    return new WP_Error('staging_outbound_disabled', 'Las conexiones salientes están desactivadas en staging.');
}, PHP_INT_MAX);
add_filter('espaciosutil_cde_listmonk_sync_enabled', '__return_false', PHP_INT_MAX);
add_filter('espaciosutil_pmpro_autocomplete_stripe_token_orders', '__return_false', PHP_INT_MAX);

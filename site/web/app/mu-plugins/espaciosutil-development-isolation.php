<?php
/**
 * Plugin Name: Espacio Sutil — protección de development
 * Description: Aísla correo, pagos e integraciones tras copiar datos de producción.
 */
declare(strict_types=1);
if (!defined('WP_ENV') || WP_ENV !== 'development') {
    return;
}
add_filter('pre_wp_mail', '__return_false', PHP_INT_MAX);
add_filter('pre_http_request', static function ($pre, $args, $url) {
    // Leer los metadatos del curso para resolver miniaturas, subtítulos y reproducción.
    // Sin escrituras, otras bibliotecas, parámetros adicionales ni redirecciones.
    if (strtoupper($args['method'] ?? 'GET') === 'GET'
        && ($args['redirection'] ?? 5) === 0
        && preg_match('~^https://video\.bunnycdn\.com/library/457097/videos/[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$~iD', $url)) {
        return $pre;
    }
    return new WP_Error('development_outbound_disabled', 'Las conexiones HTTP salientes están desactivadas en development.');
}, PHP_INT_MAX, 3);
add_filter('pre_option_pmpro_gateway_environment', static fn () => 'sandbox', PHP_INT_MAX);
// PMPro también permite claves heredadas: sandbox por sí solo no basta.
foreach (['pmpro_stripe_secretkey', 'pmpro_live_stripe_connect_secretkey', 'pmpro_sandbox_stripe_connect_secretkey'] as $option) {
    add_filter('pre_option_' . $option, '__return_empty_string', PHP_INT_MAX);
}
add_filter('espaciosutil_cde_listmonk_sync_enabled', '__return_false', PHP_INT_MAX);
add_filter('espaciosutil_pmpro_autocomplete_stripe_token_orders', '__return_false', PHP_INT_MAX);
add_filter('action_scheduler_allow_async_request_runner', '__return_false', PHP_INT_MAX);

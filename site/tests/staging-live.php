<?php
// Ejecutar mediante wp eval-file; las aserciones fallan antes de probar transportes si el entorno no es staging.
if (! defined('WP_ENV') || WP_ENV !== 'staging' || home_url() !== 'https://stage.espaciosutil.org') {
    WP_CLI::error('Entorno inesperado: no se ejecutaron pruebas.');
}
$checks = [
    'environment' => WP_ENV === 'staging',
    'cron_disabled' => defined('DISABLE_WP_CRON') && DISABLE_WP_CRON,
    'indexing_disabled' => defined('DISALLOW_INDEXING') && DISALLOW_INDEXING,
    'browser_analytics_disabled' => function_exists('matomo_tracking_url') && matomo_tracking_url() === '',
    'staging_admin' => (bool) get_user_by('login', 'staging-admin'),
    'mail_blocked' => wp_mail('fixture@example.invalid', 'Prueba de aislamiento', 'Mensaje sintético') === false,
    'listmonk_disabled' => apply_filters('espaciosutil_cde_listmonk_sync_enabled', true) === false,
];
foreach (['https://api.stripe.com/v1/charges', 'https://api.eu.mailgun.net/v3/messages', 'https://example.invalid/webhook'] as $index => $url) {
    $result = wp_remote_post($url, ['timeout' => 1, 'body' => ['fixture' => 'synthetic']]);
    $checks['http_blocked_' . $index] = is_wp_error($result) && $result->get_error_code() === 'staging_outbound_disabled';
}
$checks['no_live_integration_env'] = ! getenv('BUNNY_KEY') && ! getenv('YOUTUBE_API_KEY') && ! getenv('LISTMONK_API_KEY') && ! getenv('STRIPE_SECRET_KEY');
WP_CLI::line(wp_json_encode(['checks' => $checks, 'wordpress' => get_bloginfo('version'), 'users' => count_users()['total_users']], JSON_PRETTY_PRINT));
if (in_array(false, $checks, true)) { WP_CLI::error('Falló una comprobación de aislamiento.'); }

<?php
// Verificación de la copia; no imprime usuarios, pedidos ni credenciales.
if (gethostname() !== 'espacio-sutil-staging' || !defined('WP_ENV') || WP_ENV !== 'staging' || home_url() !== 'https://stage.espaciosutil.org') {
    WP_CLI::error('Entorno inesperado.');
}
global $wpdb;
$checks = [];
foreach (['active', 'none', 'expired', 'cancelled'] as $kind) {
    $user = get_user_by('login', 'cde-test-' . $kind);
    $checks['membership_' . $kind] = $user && (bool) pmpro_hasMembershipLevel([11, 12, 13], $user->ID) === ($kind === 'active');
}
$checks['sandbox'] = get_option('pmpro_gateway_environment') === 'sandbox';
$checks['sessions_removed'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE meta_key IN ('session_tokens', '_application_passwords')") === 0;
$checks['integration_options_removed'] = (int) $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name REGEXP '^easy_wp_smtp' OR option_name REGEXP '^(pmpro_.*stripe.*(key|secret|token|user_id|webhook)|espaciosutil_.*(token|secret)|woocommerce_.*(settings|token|secret|api_key))$'") === 0;
$checks['async_tasks_disabled'] = apply_filters('action_scheduler_allow_async_request_runner', true) === false;
$checks['cron_disabled'] = defined('DISABLE_WP_CRON') && DISABLE_WP_CRON;
WP_CLI::line(wp_json_encode(['checks' => $checks, 'users' => count_users()['total_users'], 'published_lessons' => (int) wp_count_posts('cde')->publish, 'published_pages' => (int) wp_count_posts('page')->publish], JSON_PRETTY_PRINT));
if (in_array(false, $checks, true)) WP_CLI::error('La copia no supera la verificación.');

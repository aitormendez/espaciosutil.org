<?php

declare(strict_types=1);

define('WP_ENV', $argv[1] ?? 'development');
$filters = [];
function add_filter($name, $callback, $priority = 10, $acceptedArgs = 1): void
{
    global $filters;
    $filters[$name] = $callback;
}
function __return_false(): bool { return false; }
function __return_empty_string(): string { return ''; }
class WP_Error
{
    public function __construct(public string $code, public string $message) {}
}
function verify(bool $condition, string $message): void
{
    if (!$condition) {
        throw new RuntimeException($message);
    }
}
require __DIR__ . '/../web/app/mu-plugins/espaciosutil-development-isolation.php';
if (WP_ENV !== 'development') {
    verify($filters === [], 'La protección local no debe alterar otros entornos.');
} else {
    $request = $filters['pre_http_request'];
    $url = 'https://video.bunnycdn.com/library/457097/videos/0aae5d43-0ff4-4035-bf55-a9cbcbdc6650';
    $read = ['method' => 'GET', 'redirection' => 0];
    verify($request(false, $read, $url) === false, 'Debe permitir leer los metadatos reales.');
    $previous = new WP_Error('previous_filter', 'Bloqueo previo');
    verify($request($previous, $read, $url) === $previous, 'No debe anular un bloqueo previo.');
    $denied = [
        [[], $url],
        [['method' => 'GET', 'redirection' => 1], $url],
        [$read, str_replace('/457097/', '/123456/', $url)],
        [$read, $url . '?redirect=https://example.com'],
        [$read, $url . '/'],
        [$read, $url . '/captions/es'],
        [$read, $url . "\n"],
        [$read, str_replace('https:', 'http:', $url)],
        [$read, str_replace('video.bunnycdn.com', 'video.bunnycdn.com.evil.invalid', $url)],
        [$read, str_replace('video.bunnycdn.com', 'user@video.bunnycdn.com', $url)],
        [$read, str_replace('videos/', 'videos/../videos/', $url)],
        [$read, str_replace('0aae5d43-0ff4-4035-bf55-a9cbcbdc6650', str_repeat('-', 36), $url)],
        [$read, 'https://api.stripe.com/v1/charges'],
        [$read, 'https://api.eu.mailgun.net/v3/messages'],
        [$read, 'http://127.0.0.1/wp-cron.php'],
    ];
    foreach (['POST', 'PUT', 'PATCH', 'DELETE', 'HEAD'] as $method) {
        $denied[] = [['method' => $method, 'redirection' => 0], $url];
    }
    foreach ($denied as [$args, $target]) {
        $result = $request(false, $args, $target);
        verify($result instanceof WP_Error && $result->code === 'development_outbound_disabled', 'Debe bloquear: ' . $target);
    }
    verify($filters['pre_wp_mail']() === false, 'Debe bloquear correo.');
    verify($filters['pre_option_pmpro_gateway_environment']() === 'sandbox', 'Debe conservar sandbox.');
    foreach (['pmpro_stripe_secretkey', 'pmpro_live_stripe_connect_secretkey', 'pmpro_sandbox_stripe_connect_secretkey'] as $option) {
        verify($filters['pre_option_' . $option]() === '', 'No debe exponer claves de Stripe.');
    }
    foreach (['espaciosutil_cde_listmonk_sync_enabled', 'espaciosutil_pmpro_autocomplete_stripe_token_orders', 'action_scheduler_allow_async_request_runner'] as $name) {
        verify($filters[$name]() === false, 'Debe conservar el bloqueo de ' . $name);
    }
}
echo 'Aislamiento local en ' . WP_ENV . ": OK\n";

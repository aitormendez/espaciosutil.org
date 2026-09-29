<?php

declare(strict_types=1);

// Ejecutar cada entorno en un proceso separado.
define('WP_ENV', $argv[1] ?? 'staging');
$filters = [];
function add_filter($name, $callback, $priority = 10): void
{
    global $filters;
    $filters[$name] = $callback;
}
function __return_false(): bool { return false; }
class WP_Error
{
    public function __construct(public string $code, public string $message) {}
}
require __DIR__ . '/../web/app/mu-plugins/espaciosutil-staging-isolation.php';
function verify(bool $condition, string $message): void
{
    if (! $condition) { throw new RuntimeException($message); }
}
if (WP_ENV !== 'staging') {
    verify($filters === [], 'Otro entorno no debe registrar bloqueos.');
} else {
    verify(($filters['pre_wp_mail'])(null, ['to' => 'fixture@example.invalid']) === false, 'Debe impedir el envío sin fingir éxito.');
    foreach (['https://api.stripe.com/v1/charges', 'https://api.eu.mailgun.net/v3/messages', 'https://example.invalid/webhook', 'http://127.0.0.1/wp-cron.php'] as $url) {
        foreach (['GET', 'POST'] as $method) {
            $result = ($filters['pre_http_request'])(false, ['method' => $method], $url);
            verify($result instanceof WP_Error && $result->code === 'staging_outbound_disabled', 'Debe cortar la petición antes del transporte.');
        }
    }
    putenv('CDE_STAGING_BUNNY_READ=1');
    $bunny='https://video.bunnycdn.com/library/457097/videos/00000000-0000-0000-0000-000000000001';
    verify(($filters['pre_http_request'])(false, ['method'=>'GET','redirection'=>0], $bunny) === false, 'Sólo debe permitir la lectura autorizada de Bunny.');
    foreach ([['method'=>'POST','redirection'=>0],['method'=>'GET','redirection'=>1]] as $args) {
        verify(($filters['pre_http_request'])(false, $args, $bunny) instanceof WP_Error, 'Debe bloquear escrituras y redirecciones.');
    }
    verify(($filters['pre_http_request'])(false, ['method'=>'GET','redirection'=>0], $bunny.'?redirect=1') instanceof WP_Error, 'Debe rechazar consultas fuera de la ruta exacta.');
    verify(($filters['espaciosutil_cde_listmonk_sync_enabled'])(true) === false, 'Debe desactivar la sincronización.');
    verify(($filters['espaciosutil_pmpro_autocomplete_stripe_token_orders'])(true) === false, 'Debe desactivar la consulta automática de pedidos.');
    verify(($filters['action_scheduler_allow_async_request_runner'])(true) === false, 'Debe impedir la ejecución asíncrona de tareas.');
}
echo 'Aislamiento ' . WP_ENV . ": OK\n";

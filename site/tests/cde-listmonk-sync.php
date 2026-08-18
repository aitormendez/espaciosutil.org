<?php

declare(strict_types=1);

const ABSPATH = __DIR__ . '/wordpress/';
const MINUTE_IN_SECONDS = 60;
const HOUR_IN_SECONDS = 3600;

class WP_Error
{
    public function __construct(
        private string $code,
        private string $message = '',
        private array $data = [],
    ) {}

    public function get_error_code(): string
    {
        return $this->code;
    }

    public function get_error_data(): array
    {
        return $this->data;
    }
}

class WP_REST_Request
{
    private array $headers = [];

    private array $params = [];

    public function set_header(string $key, string $value): void
    {
        $this->headers[strtolower($key)] = $value;
    }

    public function get_header(string $key): string
    {
        return $this->headers[strtolower($key)] ?? '';
    }

    public function set_body_params(array $params): void
    {
        $this->params = $params;
    }

    public function get_json_params(): array
    {
        return $this->params;
    }

    public function set_param(string $key, $value): void
    {
        $this->params[$key] = $value;
    }

    public function get_param(string $key)
    {
        return $this->params[$key] ?? null;
    }
}

class WP_User
{
    public function __construct(
        public int $ID,
        public string $user_email,
        public string $display_name,
    ) {}
}

function add_action(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): void
{
    $GLOBALS['test_actions'][$hook][] = compact('callback', 'priority', 'accepted_args');
}

function add_filter(string $hook, callable $callback, int $priority = 10, int $accepted_args = 1): void {}

function apply_filters(string $hook, $value)
{
    return $value;
}

function register_rest_route(string $namespace, string $route, array $args): void
{
    $GLOBALS['test_routes'][$namespace . $route] = $args;
}

function absint($value): int
{
    return abs((int) $value);
}

function sanitize_text_field($value): string
{
    return trim((string) $value);
}

function sanitize_key($value): string
{
    return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string) $value)) ?? '';
}

function wp_unslash($value)
{
    return $value;
}

function wp_json_encode($value)
{
    return json_encode($value);
}

function esc_url(string $value): string
{
    return $value;
}

function esc_html(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

function __(string $value, string $domain = 'default'): string
{
    return $value;
}

function get_privacy_policy_url(): string
{
    return 'https://espaciosutil.org/politica-de-privacidad/';
}

function wp_generate_uuid4(): string
{
    $GLOBALS['test_uuid_counter'] = ($GLOBALS['test_uuid_counter'] ?? 0) + 1;

    return sprintf('00000000-0000-4000-8000-%012d', $GLOBALS['test_uuid_counter']);
}

function current_time(string $type, bool $gmt = false)
{
    return $type === 'timestamp' ? 1787068800 : '2026-08-18 16:00:00';
}

function wp_date(string $format, int $timestamp, ?DateTimeZone $timezone = null): string
{
    return gmdate($format, $timestamp);
}

function get_user_by(string $field, $value)
{
    return $GLOBALS['test_users'][(int) $value] ?? false;
}

function get_userdata(int $user_id)
{
    return get_user_by('id', $user_id);
}

function update_user_meta(int $user_id, string $key, $value): void
{
    $GLOBALS['test_user_meta'][$user_id][$key] = $value;
}

function delete_user_meta(int $user_id, string $key): void
{
    unset($GLOBALS['test_user_meta'][$user_id][$key]);
}

function get_user_meta(int $user_id, string $key, bool $single = false)
{
    return $GLOBALS['test_user_meta'][$user_id][$key] ?? '';
}

function pmpro_getMembershipLevelsForUser($user_id = null, $include_inactive = false): array
{
    return $GLOBALS['test_pmpro_levels'][(int) $user_id] ?? [];
}

function as_enqueue_async_action(string $hook, array $args = [], string $group = '', bool $unique = false, int $priority = 10): int
{
    $GLOBALS['test_enqueued'][] = compact('hook', 'args', 'group', 'unique', 'priority');

    return count($GLOBALS['test_enqueued']);
}

function as_schedule_single_action(int $timestamp, string $hook, array $args = [], string $group = '', bool $unique = false, int $priority = 10): int
{
    $GLOBALS['test_scheduled'][] = compact('timestamp', 'hook', 'args', 'group', 'unique', 'priority');

    return count($GLOBALS['test_scheduled']);
}

function wp_remote_post(string $url, array $args = [])
{
    $GLOBALS['test_http_requests'][] = compact('url', 'args');

    return $GLOBALS['test_http_response'];
}

function wp_remote_retrieve_response_code($response): int
{
    return (int) ($response['response']['code'] ?? 0);
}

function wp_remote_retrieve_body($response): string
{
    return (string) ($response['body'] ?? '');
}

function is_wp_error($value): bool
{
    return $value instanceof WP_Error;
}

function assert_true(bool $condition, string $message): void
{
    if (! $condition) {
        fwrite(STDERR, $message . PHP_EOL);
        exit(1);
    }
}

function assert_same($expected, $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, 'Expected: ' . var_export($expected, true) . PHP_EOL);
        fwrite(STDERR, 'Actual:   ' . var_export($actual, true) . PHP_EOL);
        exit(1);
    }
}

class TestWpdb
{
    public string $pmpro_memberships_users = 'wp_pmpro_memberships_users';

    public array $prepared = [];

    public function prepare(string $query, ...$args): string
    {
        $this->prepared[] = compact('query', 'args');

        return $query;
    }

    public function get_var(string $query): int
    {
        return (int) ($GLOBALS['test_active_member_total'] ?? 0);
    }

    public function get_col(string $query): array
    {
        return $GLOBALS['test_active_member_ids'] ?? [];
    }
}

$plugin = __DIR__ . '/../web/app/mu-plugins/espaciosutil-cde-listmonk-sync.php';
assert_true(is_file($plugin), 'El mu-plugin específico CDE–Listmonk debe existir.');
require $plugin;

$GLOBALS['test_actions'] = $GLOBALS['test_actions'] ?? [];
$GLOBALS['test_enqueued'] = [];
$GLOBALS['test_scheduled'] = [];
$GLOBALS['test_http_requests'] = [];
$GLOBALS['test_users'] = [
    11 => new WP_User(11, 'mensual@example.test', 'Mensual'),
    12 => new WP_User(12, 'trial@example.test', 'Trial'),
    13 => new WP_User(13, 'anual@example.test', 'Anual'),
    20 => new WP_User(20, 'inactivo@example.test', 'Inactivo'),
];
$GLOBALS['test_pmpro_levels'] = [
    11 => [(object) ['id' => 11, 'name' => 'CDE mensual', 'enddate' => 0]],
    12 => [(object) ['id' => 12, 'name' => 'CDE semestral', 'enddate' => 1787673600, 'trial' => true]],
    13 => [(object) ['id' => 13, 'name' => 'CDE anual', 'enddate' => 0]],
    20 => [],
];

putenv('ESPACIOSUTIL_CDE_LISTMONK_SYNC_ENABLED');
assert_same(false, espaciosutil_cde_listmonk_sync_enabled(), 'El feature flag debe estar apagado por defecto.');
assert_same([11, 12, 13], espaciosutil_cde_listmonk_level_ids(), 'La regla CDE debe incluir exactamente los niveles 11, 12 y 13.');

foreach ([11, 12, 13] as $user_id) {
    assert_true(
        espaciosutil_cde_listmonk_active_level($user_id) !== null,
        sprintf('El nivel CDE activo del usuario %d debe resolverse.', $user_id),
    );
}
assert_true(
    espaciosutil_cde_listmonk_active_level(20) === null,
    'Un usuario sin nivel CDE activo debe resolverse como inactivo.',
);

espaciosutil_cde_listmonk_on_membership_change(11, 11, null);
assert_same([], $GLOBALS['test_enqueued'], 'El flag apagado no debe encolar eventos.');

putenv('ESPACIOSUTIL_CDE_LISTMONK_SYNC_ENABLED=1');
espaciosutil_cde_listmonk_on_membership_change(11, 11, null);
assert_same(1, count($GLOBALS['test_enqueued']), 'Un cambio PMPro debe encolar exactamente una acción local.');
assert_same([], $GLOBALS['test_http_requests'], 'El hook PMPro no debe realizar llamadas HTTP.');

$membership_event = $GLOBALS['test_enqueued'][0]['args']['event'] ?? [];
assert_same(
    ['event_id', 'external_subject', 'event_kind', 'occurred_at', 'contract_version'],
    array_keys($membership_event),
    'El evento debe contener solo el contrato mínimo aprobado.',
);
assert_same('wp_user:11', $membership_event['external_subject'], 'El evento debe usar la identidad estable de WordPress.');
assert_same('membership_changed', $membership_event['event_kind'], 'El hook PMPro debe identificar el tipo de evento.');
assert_true(! str_contains(json_encode($membership_event), 'example.test'), 'El evento no debe contener email.');
assert_true(! str_contains(json_encode($membership_event), 'Mensual'), 'El evento no debe contener nombre.');

$before_email_events = count($GLOBALS['test_enqueued']);
espaciosutil_cde_listmonk_on_profile_update(
    11,
    new WP_User(11, 'mensual@example.test', 'Mensual'),
    ['user_email' => 'mensual@example.test'],
);
assert_same($before_email_events, count($GLOBALS['test_enqueued']), 'Un perfil con el mismo email no debe encolar trabajo.');

espaciosutil_cde_listmonk_on_profile_update(
    11,
    new WP_User(11, 'anterior@example.test', 'Mensual'),
    ['user_email' => 'nuevo@example.test'],
);
assert_same($before_email_events + 1, count($GLOBALS['test_enqueued']), 'Un cambio de email debe encolar trabajo local.');
assert_same(
    'email_changed',
    $GLOBALS['test_enqueued'][$before_email_events]['args']['event']['event_kind'] ?? null,
    'El cambio de email debe usar su event_kind específico.',
);
assert_same([], $GLOBALS['test_http_requests'], 'El cambio de email no debe realizar llamadas HTTP.');

assert_true(
    function_exists('espaciosutil_cde_listmonk_register_rest_routes'),
    'El mu-plugin debe exponer el registro de rutas REST privadas.',
);
espaciosutil_cde_listmonk_register_rest_routes();
assert_same(
    [
        'espaciosutil/v1/cde-membership/snapshot',
        'espaciosutil/v1/cde-membership/members',
        'espaciosutil/v1/cde-membership/sync-result',
    ],
    array_keys($GLOBALS['test_routes']),
    'La API debe registrar solo snapshot, listado activo y resultado técnico.',
);

putenv('ESPACIOSUTIL_CDE_LISTMONK_REST_TOKEN=rest-test-token');
$missing_auth = espaciosutil_cde_listmonk_rest_permission(new WP_REST_Request());
assert_true(is_wp_error($missing_auth), 'La API privada debe rechazar una petición sin token.');
assert_same('rest_forbidden', $missing_auth->get_error_code(), 'La ausencia de token debe usar un código saneado.');

$authorized_request = new WP_REST_Request();
$authorized_request->set_header('authorization', 'Bearer rest-test-token');
assert_same(true, espaciosutil_cde_listmonk_rest_permission($authorized_request), 'El Bearer dedicado debe autorizar la API.');

$snapshot_request = new WP_REST_Request();
$snapshot_request->set_body_params(['external_subject' => 'wp_user:12']);
$trial_snapshot = espaciosutil_cde_listmonk_snapshot_handler($snapshot_request);
assert_same('active', $trial_snapshot['membership_status'], 'El snapshot debe representar el trial activo como membresía activa.');
assert_same(12, $trial_snapshot['level_id'], 'El snapshot debe informar el nivel CDE vigente.');
assert_same('trial@example.test', $trial_snapshot['email'], 'El snapshot privado debe entregar el email actual a n8n.');
assert_same('Trial', $trial_snapshot['display_name'], 'El snapshot privado debe entregar el nombre visible actual.');

$snapshot_request->set_body_params(['external_subject' => 'wp_user:20']);
$inactive_snapshot = espaciosutil_cde_listmonk_snapshot_handler($snapshot_request);
assert_same('inactive', $inactive_snapshot['membership_status'], 'El snapshot debe representar a un usuario sin CDE como inactivo.');
assert_same(null, $inactive_snapshot['level_id'], 'Un usuario inactivo no debe tener nivel vigente.');

$snapshot_request->set_body_params(['external_subject' => 'trial@example.test']);
$invalid_snapshot = espaciosutil_cde_listmonk_snapshot_handler($snapshot_request);
assert_true(is_wp_error($invalid_snapshot), 'El snapshot no debe aceptar email como identidad primaria.');
assert_same('invalid_subject', $invalid_snapshot->get_error_code(), 'La identidad inválida debe usar un código saneado.');

$GLOBALS['wpdb'] = new TestWpdb();
$GLOBALS['test_active_member_total'] = 3;
$GLOBALS['test_active_member_ids'] = [11, 12];
$members_request = new WP_REST_Request();
$members_request->set_param('page', 1);
$members_request->set_param('per_page', 2);
$members = espaciosutil_cde_listmonk_members_handler($members_request);
assert_same(2, count($members['items']), 'El listado debe devolver la página solicitada de miembros activos.');
assert_same(3, $members['pagination']['total'], 'El listado debe informar el total para reconciliación paginada.');
assert_same(2, $members['pagination']['total_pages'], 'El listado debe informar el total de páginas.');
assert_same('wp_user:11', $members['items'][0]['external_subject'], 'El listado debe usar la identidad estable.');
assert_true(
    str_contains($GLOBALS['wpdb']->prepared[0]['query'], "status = 'active'"),
    'La consulta de reconciliación debe limitarse a membresías PMPro activas.',
);

assert_true(
    function_exists('espaciosutil_cde_listmonk_process_event'),
    'El worker de Action Scheduler debe existir.',
);
$worker_hooks = $GLOBALS['test_actions']['espaciosutil_cde_listmonk_process_event'] ?? [];
assert_same(2, $worker_hooks[0]['accepted_args'] ?? null, 'El worker debe recibir evento e intento desde Action Scheduler.');

$valid_event = $membership_event;
assert_same(true, espaciosutil_cde_listmonk_validate_event($valid_event), 'El worker debe aceptar el contrato mínimo válido.');
$event_with_pii = $valid_event;
$event_with_pii['email'] = 'persona@example.test';
assert_same(false, espaciosutil_cde_listmonk_validate_event($event_with_pii), 'El worker debe rechazar eventos con campos fuera del contrato.');

putenv('ESPACIOSUTIL_CDE_LISTMONK_WEBHOOK_URL=https://n8n.example.test/webhook/cde-membership');
putenv('ESPACIOSUTIL_CDE_LISTMONK_WEBHOOK_TOKEN=webhook-test-token');
$GLOBALS['test_http_requests'] = [];
$GLOBALS['test_scheduled'] = [];
$GLOBALS['test_user_meta'] = [];
$GLOBALS['test_http_response'] = [
    'response' => ['code' => 200],
    'body' => json_encode(['status' => 'synced', 'code' => 'synced', 'readback_verified' => true]),
];

espaciosutil_cde_listmonk_process_event($valid_event, 1);
assert_same(1, count($GLOBALS['test_http_requests']), 'El worker debe hacer la llamada HTTP fuera del hook de checkout.');
$outbound = $GLOBALS['test_http_requests'][0];
assert_same('Bearer webhook-test-token', $outbound['args']['headers']['Authorization'], 'El webhook debe usar su credencial dedicada.');
assert_same($valid_event, json_decode($outbound['args']['body'], true), 'El worker debe enviar únicamente el evento versionado.');
assert_true(! str_contains($outbound['args']['body'], 'example.test'), 'El payload saliente no debe contener email.');
assert_same('synced', $GLOBALS['test_user_meta'][11]['_espaciosutil_cde_listmonk_sync_status'] ?? null, 'Una respuesta verificada debe persistir estado synced.');
assert_same('synced', $GLOBALS['test_user_meta'][11]['_espaciosutil_cde_listmonk_sync_code'] ?? null, 'El éxito debe limpiar códigos de reconciliación previos.');
assert_same([], $GLOBALS['test_scheduled'], 'Una respuesta final verificada no debe reintentarse.');

$GLOBALS['test_http_response'] = [
    'response' => ['code' => 503],
    'body' => '',
];
$GLOBALS['test_scheduled'] = [];
espaciosutil_cde_listmonk_process_event($valid_event, 1);
assert_same(1, count($GLOBALS['test_scheduled']), 'Un 503 debe programar un reintento local.');
$retry = $GLOBALS['test_scheduled'][0];
assert_same(2, $retry['args']['attempt'], 'El reintento debe incrementar el contador de intento.');
assert_true(
    $retry['timestamp'] >= 1787068800 + 60 && $retry['timestamp'] <= 1787068800 + 75,
    'El primer reintento debe aplicar backoff con jitter acotado.',
);

$GLOBALS['test_scheduled'] = [];
espaciosutil_cde_listmonk_process_event($valid_event, 5);
assert_same([], $GLOBALS['test_scheduled'], 'El último intento no debe volver a encolarse.');
assert_same(
    'reconciliation_required',
    $GLOBALS['test_user_meta'][11]['_espaciosutil_cde_listmonk_sync_status'] ?? null,
    'El agotamiento debe quedar recuperable por reconciliación.',
);
assert_same(
    'retry_exhausted',
    $GLOBALS['test_user_meta'][11]['_espaciosutil_cde_listmonk_sync_code'] ?? null,
    'El agotamiento debe persistir un código técnico saneado.',
);

$GLOBALS['test_http_response'] = [
    'response' => ['code' => 200],
    'body' => json_encode([
        'status' => 'reconciliation_required',
        'code' => 'email_owned_by_other_wp_user',
        'readback_verified' => true,
    ]),
];
espaciosutil_cde_listmonk_process_event($valid_event, 1);
assert_same(
    'email_owned_by_other_wp_user',
    $GLOBALS['test_user_meta'][11]['_espaciosutil_cde_listmonk_sync_code'] ?? null,
    'Un conflicto terminal debe conservar solo el código permitido.',
);

$GLOBALS['test_http_response']['body'] = json_encode([
    'status' => 'reconciliation_required',
    'code' => 'address_persona@example.test',
]);
espaciosutil_cde_listmonk_process_event($valid_event, 1);
assert_same(
    'upstream_reconciliation_required',
    $GLOBALS['test_user_meta'][11]['_espaciosutil_cde_listmonk_sync_code'] ?? null,
    'Un código remoto no permitido debe sanearse sin PII.',
);
assert_true(
    ! str_contains(json_encode($GLOBALS['test_user_meta']), 'example.test'),
    'El estado técnico no debe almacenar PII.',
);

$result_request = new WP_REST_Request();
$result_request->set_body_params([
    'external_subject' => 'wp_user:12',
    'status' => 'reconciliation_required',
    'code' => 'duplicate_wp_identity',
    'event_id' => $valid_event['event_id'],
]);
$result = espaciosutil_cde_listmonk_sync_result_handler($result_request);
assert_same('reconciliation_required', $result['status'], 'El endpoint técnico debe aceptar un resultado acotado.');
assert_same('duplicate_wp_identity', $GLOBALS['test_user_meta'][12]['_espaciosutil_cde_listmonk_sync_code'] ?? null, 'El endpoint debe persistir códigos permitidos.');

$result_request->set_body_params([
    'external_subject' => 'wp_user:12',
    'status' => 'reconciliation_required',
    'code' => 'duplicate_wp_identity',
]);
$missing_event_id = espaciosutil_cde_listmonk_sync_result_handler($result_request);
assert_true(is_wp_error($missing_event_id), 'El resultado técnico debe exigir correlación mediante event_id.');
assert_same('invalid_result', $missing_event_id->get_error_code(), 'La ausencia de event_id debe rechazarse con código saneado.');

$result_request->set_body_params([
    'external_subject' => 'wp_user:12',
    'status' => 'reconciliation_required',
    'code' => 'duplicate_wp_identity',
    'event_id' => 'not-a-uuid',
]);
$invalid_event_id = espaciosutil_cde_listmonk_sync_result_handler($result_request);
assert_true(is_wp_error($invalid_event_id), 'El resultado técnico debe rechazar un event_id inválido.');

$result_request->set_body_params([
    'external_subject' => 'wp_user:12',
    'status' => 'reconciliation_required',
    'code' => 'synced',
    'event_id' => $valid_event['event_id'],
]);
$inconsistent_result = espaciosutil_cde_listmonk_sync_result_handler($result_request);
assert_true(is_wp_error($inconsistent_result), 'reconciliation_required no debe aceptar el código synced.');

$result_request->set_body_params([
    'external_subject' => 'wp_user:12',
    'status' => 'inactive',
    'code' => 'synced',
    'membership_status' => 'inactive',
]);
$membership_mutation = espaciosutil_cde_listmonk_sync_result_handler($result_request);
assert_true(is_wp_error($membership_mutation), 'El endpoint técnico no debe aceptar cambios de membresía.');
assert_same('invalid_result', $membership_mutation->get_error_code(), 'Una escritura fuera del contrato debe rechazarse con código saneado.');

assert_true(
    function_exists('espaciosutil_cde_listmonk_render_checkout_notice'),
    'El mu-plugin debe exponer el aviso informativo de checkout.',
);
$GLOBALS['pmpro_review'] = false;
ob_start();
espaciosutil_cde_listmonk_render_checkout_notice((object) ['id' => 11]);
$checkout_notice = (string) ob_get_clean();
assert_true(str_contains($checkout_notice, get_privacy_policy_url()), 'El aviso debe enlazar la política de privacidad.');
assert_true(! str_contains($checkout_notice, '<input'), 'El aviso no debe introducir un checkbox comercial.');

ob_start();
espaciosutil_cde_listmonk_render_checkout_notice((object) ['id' => 99]);
$non_cde_notice = (string) ob_get_clean();
assert_same('', $non_cde_notice, 'El aviso solo debe mostrarse en checkout de niveles CDE.');

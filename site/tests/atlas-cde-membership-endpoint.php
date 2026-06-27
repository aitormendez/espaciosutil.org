<?php

declare(strict_types=1);

const ABSPATH = __DIR__ . '/wordpress/';
const DAY_IN_SECONDS = 86400;
const MINUTE_IN_SECONDS = 60;

class WP_Error
{
    public string $code;
    public string $message;
    public array $data;

    public function __construct(string $code, string $message = '', array $data = [])
    {
        $this->code = $code;
        $this->message = $message;
        $this->data = $data;
    }

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

    public function __construct(string $method = 'POST', string $route = '') {}

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
}

class WP_User
{
    public int $ID;
    public string $user_email;
    public string $display_name;

    public function __construct(int $id, string $email, string $display_name)
    {
        $this->ID = $id;
        $this->user_email = $email;
        $this->display_name = $display_name;
    }
}

function add_action(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): void {}
function register_rest_route(string $namespace, string $route, array $args): void {}
function apply_filters(string $hook_name, $value)
{
    return $value;
}
function get_option(string $option, $default = false)
{
    return $GLOBALS['test_options'][$option] ?? $default;
}
function update_option(string $option, $value): void
{
    $GLOBALS['test_options'][$option] = $value;
}
function get_user_by(string $field, $value)
{
    return $GLOBALS['test_users'][(int) $value] ?? false;
}
function get_current_user_id(): int
{
    return (int) ($GLOBALS['test_current_user_id'] ?? 0);
}
function sanitize_text_field($value): string
{
    return trim((string) $value);
}
function wp_unslash($value)
{
    return $value;
}
function is_wp_error($value): bool
{
    return $value instanceof WP_Error;
}
function current_time(string $type, bool $gmt = false)
{
    return $type === 'timestamp' ? 1782561600 : '2026-06-27 12:00:00';
}
function wp_date(string $format, int $timestamp, ?DateTimeZone $timezone = null): string
{
    return gmdate($format, $timestamp);
}
function wp_timezone(): DateTimeZone
{
    return new DateTimeZone('UTC');
}
function wp_json_encode($value)
{
    return json_encode($value);
}
function absint($value): int
{
    return abs((int) $value);
}
function home_url(string $path = ''): string
{
    return 'https://espaciosutil.org' . $path;
}
function add_query_arg(array $args, string $url): string
{
    return $url . '?' . http_build_query($args);
}
function wp_login_url(string $redirect = ''): string
{
    return 'https://espaciosutil.org/login/' . ($redirect !== '' ? '?redirect_to=' . rawurlencode($redirect) : '');
}
function pmpro_url(string $page, string $query = ''): string
{
    $paths = [
        'account' => '/cuenta-de-membresia/',
        'levels' => '/suscripcion/',
    ];

    return 'https://espaciosutil.org' . ($paths[$page] ?? '/' . $page . '/') . $query;
}

require __DIR__ . '/../web/app/mu-plugins/espaciosutil-atlas-cde-membership.php';

function assert_true(bool $condition, string $message): void
{
    if (!$condition) {
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

function make_request(array $params, string $token = 'secret-token'): WP_REST_Request
{
    $request = new WP_REST_Request('POST', '/espaciosutil/v1/atlas/membership');
    $request->set_header('authorization', 'Bearer ' . $token);
    $request->set_body_params($params);
    return $request;
}

$GLOBALS['test_options'] = [
    'espaciosutil_atlas_cde_membership_token' => 'secret-token',
    'espaciosutil_atlas_cde_access_token_secret' => 'access-token-secret',
];
$GLOBALS['test_users'] = [
    42 => new WP_User(42, 'persona@example.com', 'Persona CDE'),
    77 => new WP_User(77, 'sin-membresia@example.com', 'Sin Membresia'),
];

$active_level = (object) [
    'id' => 11,
    'name' => 'CDE mensual',
    'enddate' => strtotime('2026-07-27 00:00:00 UTC'),
];

$GLOBALS['test_pmpro_levels'] = [
    42 => [$active_level],
];

function pmpro_getMembershipLevelsForUser($user_id = null, $include_inactive = false)
{
    return $GLOBALS['test_pmpro_levels'][(int) $user_id] ?? [];
}

$missing_auth = espaciosutil_atlas_cde_membership_permission(new WP_REST_Request());
assert_true(is_wp_error($missing_auth), 'Rejects requests without backend token.');
assert_same('rest_forbidden', $missing_auth->get_error_code(), 'Uses REST forbidden error for missing token.');

$email_lookup = espaciosutil_atlas_cde_membership_handle_request(
    make_request(['external_subject' => 'persona@example.com']),
);
assert_true(is_wp_error($email_lookup), 'Rejects email lookup as primary identity.');
assert_same('invalid_subject', $email_lookup->get_error_code(), 'Reports invalid subject for email lookup.');

$response = espaciosutil_atlas_cde_membership_handle_request(
    make_request(['external_subject' => 'wp_user:42']),
);

assert_same('wordpress_pmpro', $response['provider'], 'Reports WordPress PMPro provider.');
assert_same('wp_user:42', $response['subject'], 'Reports stable WordPress subject.');
assert_same(42, $response['wordpress_user_id'], 'Reports WordPress user ID.');
assert_same('persona@example.com', $response['email'], 'Reports user email for Atlas support context.');
assert_same('Persona CDE', $response['display_name'], 'Reports display name.');
assert_same('active', $response['membership_status'], 'Reports active PMPro membership.');
assert_same(true, $response['grants_atlas'], 'Grants Atlas for configured active CDE level.');
assert_same(11, $response['level_id'], 'Reports active PMPro level ID.');
assert_same('CDE mensual', $response['level_name'], 'Reports active PMPro level name.');
assert_same('2026-07-27T00:00:00+00:00', $response['expires_at'], 'Reports membership expiration.');
assert_same('2026-06-27T12:00:00+00:00', $response['checked_at'], 'Reports check time.');
assert_same('espaciosutil_atlas_cde_membership_v1', $response['source_version'], 'Reports endpoint source version.');

$GLOBALS['test_current_user_id'] = 0;
$anonymous_access = espaciosutil_atlas_cde_access_state();
assert_same('anonymous', $anonymous_access['state'], 'Reports anonymous Atlas gate state.');
assert_same(false, $anonymous_access['grants_atlas'], 'Does not grant Atlas to anonymous visitors.');
assert_same('https://espaciosutil.org/login/?redirect_to=https%3A%2F%2Fespaciosutil.org%2Fatlas%2F', $anonymous_access['login_url'], 'Reports login URL back to Atlas gate.');

$GLOBALS['test_current_user_id'] = 77;
$GLOBALS['test_pmpro_levels'][77] = [];
$inactive_access = espaciosutil_atlas_cde_access_state();
assert_same('inactive', $inactive_access['state'], 'Reports inactive Atlas gate state for logged users without CDE membership.');
assert_same(false, $inactive_access['grants_atlas'], 'Does not grant Atlas without CDE membership.');
assert_same('https://espaciosutil.org/suscripcion/', $inactive_access['subscription_url'], 'Reports subscription URL for inactive users.');

$GLOBALS['test_current_user_id'] = 42;
$active_access = espaciosutil_atlas_cde_access_state();
assert_same('active', $active_access['state'], 'Reports active Atlas gate state for CDE members.');
assert_same(true, $active_access['grants_atlas'], 'Grants Atlas to active CDE members.');
assert_true(str_starts_with($active_access['atlas_url'], 'https://atlas.espaciosutil.org/?cde_access_token='), 'Builds Atlas launch URL with a CDE access token.');

$token = substr($active_access['atlas_url'], strlen('https://atlas.espaciosutil.org/?cde_access_token='));
$parts = explode('.', $token);
assert_same(2, count($parts), 'Emits signed token with payload and signature parts.');
$payload_json = base64_decode(strtr($parts[0], '-_', '+/'), true);
assert_true(is_string($payload_json), 'Encodes token payload as base64url JSON.');
$payload = json_decode($payload_json, true);
assert_same('wp_user:42', $payload['external_subject'], 'Token payload includes stable WordPress subject.');
assert_same(1782561600 + 300, $payload['expires_at'], 'Token expires after the default five minute TTL.');
assert_same('espaciosutil_cde', $payload['issuer'], 'Token payload names the CDE issuer.');

$token_response = espaciosutil_atlas_cde_membership_handle_request(
    make_request(['session_token' => $token]),
);
assert_same('wp_user:42', $token_response['subject'], 'Resolves a signed CDE access token to the WordPress subject.');
assert_same(true, $token_response['grants_atlas'], 'Grants Atlas for a valid signed token from an active CDE user.');

$tampered_token_response = espaciosutil_atlas_cde_membership_handle_request(
    make_request(['session_token' => $parts[0] . '.invalid']),
);
assert_true(is_wp_error($tampered_token_response), 'Rejects tampered CDE access tokens.');
assert_same('invalid_session_token', $tampered_token_response->get_error_code(), 'Reports invalid token for tampered CDE access tokens.');

$expired_payload = $payload;
$expired_payload['issued_at'] = 1782561600 - 600;
$expired_payload['expires_at'] = 1782561600 - 300;
$expired_payload_json = wp_json_encode($expired_payload);
$expired_encoded_payload = espaciosutil_atlas_cde_base64url_encode($expired_payload_json);
$expired_token = $expired_encoded_payload . '.' . espaciosutil_atlas_cde_base64url_encode(
    hash_hmac('sha256', $expired_encoded_payload, 'access-token-secret', true),
);
$expired_token_response = espaciosutil_atlas_cde_membership_handle_request(
    make_request(['session_token' => $expired_token]),
);
assert_true(is_wp_error($expired_token_response), 'Rejects expired CDE access tokens.');
assert_same('invalid_session_token', $expired_token_response->get_error_code(), 'Reports invalid token for expired CDE access tokens.');

$inactive_token = espaciosutil_atlas_cde_issue_access_token(
    $GLOBALS['test_users'][77],
    espaciosutil_atlas_cde_membership_response_for_user($GLOBALS['test_users'][77]),
);
$inactive_token_response = espaciosutil_atlas_cde_membership_handle_request(
    make_request(['session_token' => $inactive_token]),
);
assert_same('wp_user:77', $inactive_token_response['subject'], 'Resolves a signed token for an inactive user to the WordPress subject.');
assert_same(false, $inactive_token_response['grants_atlas'], 'Does not grant Atlas for a valid token when membership is inactive.');

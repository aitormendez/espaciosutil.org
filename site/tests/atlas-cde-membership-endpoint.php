<?php

declare(strict_types=1);

const ABSPATH = __DIR__ . '/wordpress/';
const DAY_IN_SECONDS = 86400;

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
function apply_filters(string $hook_name, $value) { return $value; }
function get_option(string $option, $default = false) { return $GLOBALS['test_options'][$option] ?? $default; }
function update_option(string $option, $value): void { $GLOBALS['test_options'][$option] = $value; }
function get_user_by(string $field, $value) { return $GLOBALS['test_users'][(int) $value] ?? false; }
function sanitize_text_field($value): string { return trim((string) $value); }
function wp_unslash($value) { return $value; }
function is_wp_error($value): bool { return $value instanceof WP_Error; }
function current_time(string $type, bool $gmt = false) { return $type === 'timestamp' ? 1782561600 : '2026-06-27 12:00:00'; }
function wp_date(string $format, int $timestamp, ?DateTimeZone $timezone = null): string { return gmdate($format, $timestamp); }
function wp_timezone(): DateTimeZone { return new DateTimeZone('UTC'); }
function absint($value): int { return abs((int) $value); }

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
];
$GLOBALS['test_users'] = [
    42 => new WP_User(42, 'persona@example.com', 'Persona CDE'),
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
    make_request(['external_subject' => 'persona@example.com'])
);
assert_true(is_wp_error($email_lookup), 'Rejects email lookup as primary identity.');
assert_same('invalid_subject', $email_lookup->get_error_code(), 'Reports invalid subject for email lookup.');

$response = espaciosutil_atlas_cde_membership_handle_request(
    make_request(['external_subject' => 'wp_user:42'])
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

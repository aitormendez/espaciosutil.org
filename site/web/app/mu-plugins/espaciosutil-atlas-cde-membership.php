<?php

/**
 * Plugin Name: Espacio Sutil Atlas CDE Membership
 * Description: Expone un endpoint privado para que Atlas valide autorizacion CDE desde WordPress/PMPro.
 */

if (!defined('ABSPATH')) {
    exit;
}

add_action('rest_api_init', function (): void {
    register_rest_route('espaciosutil/v1', '/atlas/membership', [
        'methods' => 'POST',
        'callback' => 'espaciosutil_atlas_cde_membership_handle_request',
        'permission_callback' => 'espaciosutil_atlas_cde_membership_permission',
    ]);
});

/**
 * PMPro level IDs that grant Atlas access from the CDE membership.
 *
 * @return array<int>
 */
function espaciosutil_atlas_cde_membership_level_ids(): array
{
    $level_ids = [11, 12, 13];

    return array_values(array_unique(array_filter(array_map(
        static fn($level_id): int => absint($level_id),
        apply_filters('espaciosutil_atlas_cde_membership_level_ids', $level_ids),
    ))));
}

function espaciosutil_atlas_cde_membership_token(): string
{
    $token = getenv('ESPACIOSUTIL_ATLAS_CDE_MEMBERSHIP_TOKEN');
    if (is_string($token) && $token !== '') {
        return $token;
    }

    $option_token = get_option('espaciosutil_atlas_cde_membership_token', '');
    return is_string($option_token) ? $option_token : '';
}

function espaciosutil_atlas_cde_access_token_secret(): string
{
    $token = getenv('ESPACIOSUTIL_ATLAS_CDE_ACCESS_TOKEN_SECRET');
    if (is_string($token) && $token !== '') {
        return $token;
    }

    $option_token = get_option('espaciosutil_atlas_cde_access_token_secret', '');
    if (is_string($option_token) && $option_token !== '') {
        return $option_token;
    }

    return espaciosutil_atlas_cde_membership_token();
}

function espaciosutil_atlas_cde_public_url(): string
{
    $url = getenv('ESPACIOSUTIL_ATLAS_URL');
    if (!is_string($url) || $url === '') {
        $url = 'https://atlas.espaciosutil.org/';
    }

    return rtrim($url, '/') . '/';
}

function espaciosutil_atlas_cde_gate_url(): string
{
    return home_url('/atlas/');
}

function espaciosutil_atlas_cde_subscription_url(): string
{
    if (function_exists('pmpro_url')) {
        return pmpro_url('levels');
    }

    return home_url('/suscripcion/');
}

function espaciosutil_atlas_cde_account_url(): string
{
    if (function_exists('pmpro_url')) {
        return pmpro_url('account');
    }

    return home_url('/cuenta-de-membresia/');
}

/**
 * Resolve the current visitor state for the WordPress/CDE Atlas gate.
 *
 * @return array<string, mixed>
 */
function espaciosutil_atlas_cde_access_state(): array
{
    $gate_url = espaciosutil_atlas_cde_gate_url();
    $base = [
        'state' => 'anonymous',
        'grants_atlas' => false,
        'login_url' => wp_login_url($gate_url),
        'subscription_url' => espaciosutil_atlas_cde_subscription_url(),
        'account_url' => espaciosutil_atlas_cde_account_url(),
        'atlas_url' => '',
        'membership' => null,
    ];

    $user_id = function_exists('get_current_user_id') ? (int) get_current_user_id() : 0;
    if ($user_id < 1) {
        return $base;
    }

    $user = get_user_by('id', $user_id);
    if (!$user instanceof WP_User) {
        return array_merge($base, [
            'state' => 'inactive',
        ]);
    }

    $membership = espaciosutil_atlas_cde_membership_response_for_user($user);
    if (empty($membership['grants_atlas'])) {
        return array_merge($base, [
            'state' => 'inactive',
            'membership' => $membership,
        ]);
    }

    $token = espaciosutil_atlas_cde_issue_access_token($user, $membership);
    if ($token === '') {
        return array_merge($base, [
            'state' => 'unavailable',
            'membership' => $membership,
        ]);
    }

    return array_merge($base, [
        'state' => 'active',
        'grants_atlas' => true,
        'atlas_url' => add_query_arg(
            ['cde_access_token' => $token],
            espaciosutil_atlas_cde_public_url(),
        ),
        'membership' => $membership,
    ]);
}

/**
 * Emit a short-lived signed proof for Atlas. Atlas sends it back to this endpoint for validation.
 *
 * @param array<string, mixed> $membership
 */
function espaciosutil_atlas_cde_issue_access_token(WP_User $user, array $membership): string
{
    $secret = espaciosutil_atlas_cde_access_token_secret();
    if ($secret === '') {
        return '';
    }

    $issued_at = (int) current_time('timestamp', true);
    $ttl = (int) apply_filters('espaciosutil_atlas_cde_access_token_ttl', 5 * MINUTE_IN_SECONDS);
    if ($ttl < 1 || $ttl > 15 * MINUTE_IN_SECONDS) {
        $ttl = 5 * MINUTE_IN_SECONDS;
    }

    $payload = [
        'version' => 1,
        'issuer' => 'espaciosutil_cde',
        'audience' => 'atlas',
        'external_subject' => 'wp_user:' . (int) $user->ID,
        'wordpress_user_id' => (int) $user->ID,
        'membership_status' => (string) ($membership['membership_status'] ?? ''),
        'grants_atlas' => (bool) ($membership['grants_atlas'] ?? false),
        'level_id' => $membership['level_id'] ?? null,
        'issued_at' => $issued_at,
        'expires_at' => $issued_at + $ttl,
        'nonce' => espaciosutil_atlas_cde_token_nonce(),
    ];

    $payload_json = wp_json_encode($payload);
    if (!is_string($payload_json) || $payload_json === '') {
        return '';
    }

    $encoded_payload = espaciosutil_atlas_cde_base64url_encode($payload_json);
    $signature = hash_hmac('sha256', $encoded_payload, $secret, true);

    return $encoded_payload . '.' . espaciosutil_atlas_cde_base64url_encode($signature);
}

function espaciosutil_atlas_cde_token_nonce(): string
{
    try {
        return bin2hex(random_bytes(16));
    } catch (Throwable $exception) {
        if (function_exists('wp_generate_uuid4')) {
            return (string) wp_generate_uuid4();
        }

        return sha1((string) microtime(true));
    }
}

function espaciosutil_atlas_cde_base64url_encode(string $value): string
{
    return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
}

function espaciosutil_atlas_cde_base64url_decode(string $value)
{
    $remainder = strlen($value) % 4;
    if ($remainder > 0) {
        $value .= str_repeat('=', 4 - $remainder);
    }

    return base64_decode(strtr($value, '-_', '+/'), true);
}

function espaciosutil_atlas_cde_membership_permission(WP_REST_Request $request)
{
    $configured_token = espaciosutil_atlas_cde_membership_token();
    if ($configured_token === '') {
        return new WP_Error('rest_forbidden', 'Atlas membership endpoint is not configured.', ['status' => 403]);
    }

    $authorization = $request->get_header('authorization');
    if (!is_string($authorization) || !preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)) {
        return new WP_Error('rest_forbidden', 'Missing Atlas membership token.', ['status' => 403]);
    }

    if (!hash_equals($configured_token, trim($matches[1]))) {
        return new WP_Error('rest_forbidden', 'Invalid Atlas membership token.', ['status' => 403]);
    }

    return true;
}

function espaciosutil_atlas_cde_membership_handle_request(WP_REST_Request $request)
{
    $permission = espaciosutil_atlas_cde_membership_permission($request);
    if (is_wp_error($permission)) {
        return $permission;
    }

    $params = $request->get_json_params();
    $external_subject = isset($params['external_subject'])
        ? sanitize_text_field(wp_unslash($params['external_subject']))
        : '';

    if ($external_subject === '' && !empty($params['session_token'])) {
        $token_payload = espaciosutil_atlas_cde_validate_access_token(
            sanitize_text_field(wp_unslash($params['session_token'])),
        );
        if (is_wp_error($token_payload)) {
            return $token_payload;
        }

        $external_subject = (string) ($token_payload['external_subject'] ?? '');
    }

    $wordpress_user_id = espaciosutil_atlas_cde_membership_parse_subject($external_subject);
    if ($wordpress_user_id < 1) {
        return new WP_Error(
            'invalid_subject',
            'Atlas membership checks require external_subject in wp_user:{id} format.',
            ['status' => 400],
        );
    }

    $user = get_user_by('id', $wordpress_user_id);
    if (!$user instanceof WP_User) {
        return espaciosutil_atlas_cde_membership_unknown_response($wordpress_user_id);
    }

    return espaciosutil_atlas_cde_membership_response_for_user($user);
}

function espaciosutil_atlas_cde_validate_access_token(string $session_token)
{
    $secret = espaciosutil_atlas_cde_access_token_secret();
    if ($secret === '') {
        return new WP_Error(
            'invalid_session_token',
            'Atlas CDE access-token validation is not configured.',
            ['status' => 403],
        );
    }

    $parts = explode('.', $session_token);
    if (count($parts) !== 2 || $parts[0] === '' || $parts[1] === '') {
        return new WP_Error(
            'invalid_session_token',
            'Atlas CDE access token is malformed.',
            ['status' => 403],
        );
    }

    [$encoded_payload, $encoded_signature] = $parts;
    $expected_signature = espaciosutil_atlas_cde_base64url_encode(
        hash_hmac('sha256', $encoded_payload, $secret, true),
    );
    if (!hash_equals($expected_signature, $encoded_signature)) {
        return new WP_Error(
            'invalid_session_token',
            'Atlas CDE access token signature is invalid.',
            ['status' => 403],
        );
    }

    $payload_json = espaciosutil_atlas_cde_base64url_decode($encoded_payload);
    if (!is_string($payload_json) || $payload_json === '') {
        return new WP_Error(
            'invalid_session_token',
            'Atlas CDE access token payload is invalid.',
            ['status' => 403],
        );
    }

    $payload = json_decode($payload_json, true);
    if (!is_array($payload)) {
        return new WP_Error(
            'invalid_session_token',
            'Atlas CDE access token payload is not valid JSON.',
            ['status' => 403],
        );
    }

    $now = (int) current_time('timestamp', true);
    $expires_at = isset($payload['expires_at']) ? (int) $payload['expires_at'] : 0;
    $issued_at = isset($payload['issued_at']) ? (int) $payload['issued_at'] : 0;
    $external_subject = isset($payload['external_subject']) ? (string) $payload['external_subject'] : '';
    $wordpress_user_id = isset($payload['wordpress_user_id']) ? (int) $payload['wordpress_user_id'] : 0;

    if (
        ($payload['version'] ?? null) !== 1
        || ($payload['issuer'] ?? '') !== 'espaciosutil_cde'
        || ($payload['audience'] ?? '') !== 'atlas'
        || $expires_at < $now
        || $issued_at > $now + MINUTE_IN_SECONDS
        || espaciosutil_atlas_cde_membership_parse_subject($external_subject) !== $wordpress_user_id
        || $wordpress_user_id < 1
    ) {
        return new WP_Error(
            'invalid_session_token',
            'Atlas CDE access token claims are invalid or expired.',
            ['status' => 403],
        );
    }

    return $payload;
}

function espaciosutil_atlas_cde_membership_parse_subject(string $external_subject): int
{
    if (!preg_match('/^wp_user:([1-9][0-9]*)$/', $external_subject, $matches)) {
        return 0;
    }

    return (int) $matches[1];
}

function espaciosutil_atlas_cde_membership_response_for_user(WP_User $user): array
{
    $granting_level = espaciosutil_atlas_cde_membership_active_level((int) $user->ID);
    if ($granting_level !== null) {
        return espaciosutil_atlas_cde_membership_payload(
            $user,
            'active',
            true,
            (int) $granting_level->id,
            (string) $granting_level->name,
            espaciosutil_atlas_cde_membership_format_timestamp($granting_level->enddate ?? null),
        );
    }

    $last_status = espaciosutil_atlas_cde_membership_last_pmpro_status((int) $user->ID);

    return espaciosutil_atlas_cde_membership_payload(
        $user,
        $last_status,
        false,
        null,
        null,
        null,
    );
}

function espaciosutil_atlas_cde_membership_active_level(int $user_id): ?object
{
    if (!function_exists('pmpro_getMembershipLevelsForUser')) {
        return null;
    }

    $levels = pmpro_getMembershipLevelsForUser($user_id);
    if (empty($levels) || !is_iterable($levels)) {
        return null;
    }

    $granting_level_ids = espaciosutil_atlas_cde_membership_level_ids();
    foreach ($levels as $level) {
        if (is_object($level) && isset($level->id) && in_array((int) $level->id, $granting_level_ids, true)) {
            return $level;
        }
    }

    return null;
}

function espaciosutil_atlas_cde_membership_last_pmpro_status(int $user_id): string
{
    global $wpdb;

    if (
        !isset($wpdb)
        || empty($wpdb->pmpro_memberships_users)
        || !method_exists($wpdb, 'get_var')
        || !method_exists($wpdb, 'prepare')
    ) {
        return 'unknown';
    }

    $raw_status = $wpdb->get_var($wpdb->prepare(
        "SELECT status FROM {$wpdb->pmpro_memberships_users} WHERE user_id = %d ORDER BY id DESC LIMIT 1",
        $user_id,
    ));

    return espaciosutil_atlas_cde_membership_normalize_pmpro_status($raw_status);
}

function espaciosutil_atlas_cde_membership_normalize_pmpro_status($status): string
{
    $status = is_string($status) ? strtolower($status) : '';

    if ($status === 'expired') {
        return 'expired';
    }

    if (in_array($status, ['cancelled', 'admin_cancelled', 'inactive'], true)) {
        return 'cancelled';
    }

    return 'unknown';
}

function espaciosutil_atlas_cde_membership_unknown_response(int $wordpress_user_id): array
{
    return [
        'provider' => 'wordpress_pmpro',
        'subject' => 'wp_user:' . $wordpress_user_id,
        'wordpress_user_id' => $wordpress_user_id,
        'email' => '',
        'display_name' => '',
        'membership_status' => 'unknown',
        'grants_atlas' => false,
        'level_id' => null,
        'level_name' => null,
        'expires_at' => null,
        'checked_at' => espaciosutil_atlas_cde_membership_now(),
        'source_version' => 'espaciosutil_atlas_cde_membership_v1',
    ];
}

function espaciosutil_atlas_cde_membership_payload(
    WP_User $user,
    string $membership_status,
    bool $grants_atlas,
    ?int $level_id,
    ?string $level_name,
    ?string $expires_at,
): array {
    return [
        'provider' => 'wordpress_pmpro',
        'subject' => 'wp_user:' . (int) $user->ID,
        'wordpress_user_id' => (int) $user->ID,
        'email' => (string) $user->user_email,
        'display_name' => (string) $user->display_name,
        'membership_status' => $membership_status,
        'grants_atlas' => $grants_atlas,
        'level_id' => $level_id,
        'level_name' => $level_name,
        'expires_at' => $expires_at,
        'checked_at' => espaciosutil_atlas_cde_membership_now(),
        'source_version' => 'espaciosutil_atlas_cde_membership_v1',
    ];
}

function espaciosutil_atlas_cde_membership_format_timestamp($timestamp): ?string
{
    if (empty($timestamp) || !is_numeric($timestamp)) {
        return null;
    }

    return wp_date(DATE_ATOM, (int) $timestamp, new DateTimeZone('UTC'));
}

function espaciosutil_atlas_cde_membership_now(): string
{
    return wp_date(DATE_ATOM, (int) current_time('timestamp', true), new DateTimeZone('UTC'));
}

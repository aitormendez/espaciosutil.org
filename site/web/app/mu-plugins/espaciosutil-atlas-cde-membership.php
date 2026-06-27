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
        static fn ($level_id): int => absint($level_id),
        apply_filters('espaciosutil_atlas_cde_membership_level_ids', $level_ids)
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
        return new WP_Error(
            'unsupported_session_token',
            'Session-token resolution is not enabled for Atlas membership checks.',
            ['status' => 400]
        );
    }

    $wordpress_user_id = espaciosutil_atlas_cde_membership_parse_subject($external_subject);
    if ($wordpress_user_id < 1) {
        return new WP_Error(
            'invalid_subject',
            'Atlas membership checks require external_subject in wp_user:{id} format.',
            ['status' => 400]
        );
    }

    $user = get_user_by('id', $wordpress_user_id);
    if (!$user instanceof WP_User) {
        return espaciosutil_atlas_cde_membership_unknown_response($wordpress_user_id);
    }

    return espaciosutil_atlas_cde_membership_response_for_user($user);
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
            espaciosutil_atlas_cde_membership_format_timestamp($granting_level->enddate ?? null)
        );
    }

    $last_status = espaciosutil_atlas_cde_membership_last_pmpro_status((int) $user->ID);

    return espaciosutil_atlas_cde_membership_payload(
        $user,
        $last_status,
        false,
        null,
        null,
        null
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
        $user_id
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
    ?string $expires_at
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

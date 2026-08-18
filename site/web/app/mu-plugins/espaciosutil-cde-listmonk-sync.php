<?php

/**
 * Plugin Name: Espacio Sutil CDE Listmonk Sync
 * Description: Sincroniza de forma asíncrona la membresía CDE con la automatización de Listmonk.
 */
if (! defined('ABSPATH')) {
    exit;
}

add_action('pmpro_after_change_membership_level', 'espaciosutil_cde_listmonk_on_membership_change', 10, 3);
add_action('profile_update', 'espaciosutil_cde_listmonk_on_profile_update', 10, 3);
add_action('rest_api_init', 'espaciosutil_cde_listmonk_register_rest_routes');
add_action('espaciosutil_cde_listmonk_process_event', 'espaciosutil_cde_listmonk_process_event', 10, 2);
add_action('pmpro_checkout_before_submit_button', 'espaciosutil_cde_listmonk_render_checkout_notice', 5, 1);

/**
 * Identificadores de nivel que conceden una membresía CDE activa.
 *
 * @return array<int>
 */
function espaciosutil_cde_membership_level_ids(): array
{
    $level_ids = apply_filters('espaciosutil_cde_membership_level_ids', [11, 12, 13]);

    return array_values(array_unique(array_filter(array_map(
        static fn($level_id): int => absint($level_id),
        is_array($level_ids) ? $level_ids : [],
    ))));
}

/**
 * @return array<int>
 */
function espaciosutil_cde_listmonk_level_ids(): array
{
    return espaciosutil_cde_membership_level_ids();
}

function espaciosutil_cde_listmonk_sync_enabled(): bool
{
    $raw_value = getenv('ESPACIOSUTIL_CDE_LISTMONK_SYNC_ENABLED');
    $enabled = is_string($raw_value)
        && in_array(strtolower(trim($raw_value)), ['1', 'true', 'yes', 'on'], true);

    return (bool) apply_filters('espaciosutil_cde_listmonk_sync_enabled', $enabled);
}

function espaciosutil_cde_listmonk_active_level(int $user_id): ?object
{
    if (! function_exists('pmpro_getMembershipLevelsForUser')) {
        return null;
    }

    $levels = pmpro_getMembershipLevelsForUser($user_id);
    if (empty($levels) || ! is_iterable($levels)) {
        return null;
    }

    foreach ($levels as $level) {
        if (
            is_object($level)
            && isset($level->id)
            && in_array((int) $level->id, espaciosutil_cde_listmonk_level_ids(), true)
        ) {
            return $level;
        }
    }

    return null;
}

function espaciosutil_cde_listmonk_on_membership_change($level_id, $user_id, $cancel_level_id): void
{
    espaciosutil_cde_listmonk_enqueue_event((int) $user_id, 'membership_changed');
}

/**
 * @param  array<string, mixed>  $userdata
 */
function espaciosutil_cde_listmonk_on_profile_update($user_id, $old_user_data, array $userdata = []): void
{
    if (! $old_user_data instanceof WP_User || ! isset($userdata['user_email'])) {
        return;
    }

    $old_email = strtolower(trim((string) $old_user_data->user_email));
    $new_email = strtolower(trim((string) $userdata['user_email']));
    if ($new_email === '' || $new_email === $old_email) {
        return;
    }

    espaciosutil_cde_listmonk_enqueue_event((int) $user_id, 'email_changed');
}

function espaciosutil_cde_listmonk_enqueue_event(int $user_id, string $event_kind): int
{
    if (
        ! espaciosutil_cde_listmonk_sync_enabled()
        || $user_id < 1
        || ! function_exists('as_enqueue_async_action')
    ) {
        return 0;
    }

    $event = espaciosutil_cde_listmonk_build_event($user_id, $event_kind);

    return (int) as_enqueue_async_action(
        'espaciosutil_cde_listmonk_process_event',
        [
            'event' => $event,
            'attempt' => 1,
        ],
        'espaciosutil_cde_listmonk',
    );
}

/**
 * @return array{event_id: string, external_subject: string, event_kind: string, occurred_at: string, contract_version: string}
 */
function espaciosutil_cde_listmonk_build_event(int $user_id, string $event_kind): array
{
    return [
        'event_id' => wp_generate_uuid4(),
        'external_subject' => 'wp_user:' . $user_id,
        'event_kind' => $event_kind,
        'occurred_at' => wp_date(DATE_ATOM, (int) current_time('timestamp', true), new DateTimeZone('UTC')),
        'contract_version' => '1',
    ];
}

function espaciosutil_cde_listmonk_register_rest_routes(): void
{
    $permission_callback = 'espaciosutil_cde_listmonk_rest_permission';

    register_rest_route('espaciosutil/v1', '/cde-membership/snapshot', [
        'methods' => 'POST',
        'callback' => 'espaciosutil_cde_listmonk_snapshot_handler',
        'permission_callback' => $permission_callback,
    ]);

    register_rest_route('espaciosutil/v1', '/cde-membership/members', [
        'methods' => 'GET',
        'callback' => 'espaciosutil_cde_listmonk_members_handler',
        'permission_callback' => $permission_callback,
    ]);

    register_rest_route('espaciosutil/v1', '/cde-membership/sync-result', [
        'methods' => 'POST',
        'callback' => 'espaciosutil_cde_listmonk_sync_result_handler',
        'permission_callback' => $permission_callback,
    ]);
}

function espaciosutil_cde_listmonk_rest_token(): string
{
    $token = getenv('ESPACIOSUTIL_CDE_LISTMONK_REST_TOKEN');

    return is_string($token) ? trim($token) : '';
}

function espaciosutil_cde_listmonk_rest_permission(WP_REST_Request $request)
{
    $configured_token = espaciosutil_cde_listmonk_rest_token();
    $authorization = $request->get_header('authorization');

    if (
        $configured_token === ''
        || ! is_string($authorization)
        || ! preg_match('/^Bearer\s+(.+)$/i', $authorization, $matches)
        || ! hash_equals($configured_token, trim($matches[1]))
    ) {
        return new WP_Error(
            'rest_forbidden',
            'CDE membership synchronization endpoint is not authorized.',
            ['status' => 403],
        );
    }

    return true;
}

function espaciosutil_cde_listmonk_parse_subject(string $external_subject): int
{
    if (! preg_match('/^wp_user:([1-9][0-9]*)$/', $external_subject, $matches)) {
        return 0;
    }

    return (int) $matches[1];
}

function espaciosutil_cde_listmonk_snapshot_handler(WP_REST_Request $request)
{
    $params = $request->get_json_params();
    $external_subject = isset($params['external_subject'])
        ? sanitize_text_field(wp_unslash($params['external_subject']))
        : '';
    $user_id = espaciosutil_cde_listmonk_parse_subject($external_subject);

    if ($user_id < 1) {
        return new WP_Error(
            'invalid_subject',
            'external_subject must use wp_user:{id}.',
            ['status' => 400],
        );
    }

    $user = get_user_by('id', $user_id);
    if (! $user instanceof WP_User) {
        return espaciosutil_cde_listmonk_unknown_snapshot($user_id);
    }

    return espaciosutil_cde_listmonk_snapshot_for_user($user);
}

/**
 * @return array<string, mixed>
 */
function espaciosutil_cde_listmonk_snapshot_for_user(WP_User $user): array
{
    $active_level = espaciosutil_cde_listmonk_active_level((int) $user->ID);

    return [
        'external_subject' => 'wp_user:' . (int) $user->ID,
        'wordpress_user_id' => (int) $user->ID,
        'email' => (string) $user->user_email,
        'display_name' => (string) $user->display_name,
        'membership_status' => $active_level === null ? 'inactive' : 'active',
        'level_id' => $active_level === null ? null : (int) $active_level->id,
        'level_name' => $active_level === null ? null : (string) ($active_level->name ?? ''),
        'expires_at' => $active_level === null
            ? null
            : espaciosutil_cde_listmonk_format_timestamp($active_level->enddate ?? null),
        'checked_at' => espaciosutil_cde_listmonk_now(),
        'contract_version' => '1',
    ];
}

/**
 * @return array<string, mixed>
 */
function espaciosutil_cde_listmonk_unknown_snapshot(int $user_id): array
{
    return [
        'external_subject' => 'wp_user:' . $user_id,
        'wordpress_user_id' => $user_id,
        'email' => '',
        'display_name' => '',
        'membership_status' => 'unknown',
        'level_id' => null,
        'level_name' => null,
        'expires_at' => null,
        'checked_at' => espaciosutil_cde_listmonk_now(),
        'contract_version' => '1',
    ];
}

function espaciosutil_cde_listmonk_members_handler(WP_REST_Request $request): array
{
    global $wpdb;

    $page = max(1, absint($request->get_param('page') ?: 1));
    $per_page = min(100, max(1, absint($request->get_param('per_page') ?: 50)));
    $offset = ($page - 1) * $per_page;
    $level_ids = implode(',', array_map('absint', espaciosutil_cde_listmonk_level_ids()));
    $now = (string) current_time('mysql');
    $active_where = "status = 'active'
        AND membership_id IN ({$level_ids})
        AND (enddate IS NULL OR enddate = '0000-00-00 00:00:00' OR enddate >= %s)";

    $count_query = $wpdb->prepare(
        "SELECT COUNT(DISTINCT user_id) FROM {$wpdb->pmpro_memberships_users} WHERE {$active_where}",
        $now,
    );
    $total = (int) $wpdb->get_var($count_query);

    $member_query = $wpdb->prepare(
        "SELECT DISTINCT user_id FROM {$wpdb->pmpro_memberships_users}
        WHERE {$active_where}
        ORDER BY user_id ASC LIMIT %d OFFSET %d",
        $now,
        $per_page,
        $offset,
    );
    $user_ids = array_map('absint', $wpdb->get_col($member_query));
    $items = [];

    foreach ($user_ids as $user_id) {
        $user = get_user_by('id', $user_id);
        if ($user instanceof WP_User) {
            $items[] = espaciosutil_cde_listmonk_snapshot_for_user($user);
        }
    }

    return [
        'items' => $items,
        'pagination' => [
            'page' => $page,
            'per_page' => $per_page,
            'total' => $total,
            'total_pages' => (int) ceil($total / $per_page),
        ],
        'contract_version' => '1',
    ];
}

function espaciosutil_cde_listmonk_format_timestamp($timestamp): ?string
{
    if (empty($timestamp) || ! is_numeric($timestamp)) {
        return null;
    }

    return wp_date(DATE_ATOM, (int) $timestamp, new DateTimeZone('UTC'));
}

function espaciosutil_cde_listmonk_now(): string
{
    return wp_date(DATE_ATOM, (int) current_time('timestamp', true), new DateTimeZone('UTC'));
}

/**
 * @param  array<string, mixed>  $event
 */
function espaciosutil_cde_listmonk_validate_event(array $event): bool
{
    $expected_keys = ['contract_version', 'event_id', 'event_kind', 'external_subject', 'occurred_at'];
    $actual_keys = array_keys($event);
    sort($actual_keys);

    if ($actual_keys !== $expected_keys) {
        return false;
    }

    return is_string($event['event_id'])
        && espaciosutil_cde_listmonk_is_valid_event_id($event['event_id'])
        && espaciosutil_cde_listmonk_parse_subject((string) $event['external_subject']) > 0
        && in_array($event['event_kind'], ['membership_changed', 'email_changed'], true)
        && is_string($event['occurred_at'])
        && strtotime($event['occurred_at']) !== false
        && $event['contract_version'] === '1';
}

function espaciosutil_cde_listmonk_webhook_url(): string
{
    $url = getenv('ESPACIOSUTIL_CDE_LISTMONK_WEBHOOK_URL');
    $url = is_string($url) ? trim($url) : '';
    $parts = $url === '' ? false : parse_url($url);

    if (
        ! is_array($parts)
        || ($parts['scheme'] ?? '') !== 'https'
        || empty($parts['host'])
    ) {
        return '';
    }

    return $url;
}

function espaciosutil_cde_listmonk_webhook_token(): string
{
    $token = getenv('ESPACIOSUTIL_CDE_LISTMONK_WEBHOOK_TOKEN');

    return is_string($token) ? trim($token) : '';
}

/**
 * @param  array<string, mixed>  $event
 */
function espaciosutil_cde_listmonk_process_event(array $event, int $attempt = 1): void
{
    if (! espaciosutil_cde_listmonk_sync_enabled()) {
        return;
    }

    $user_id = espaciosutil_cde_listmonk_parse_subject((string) ($event['external_subject'] ?? ''));
    if (! espaciosutil_cde_listmonk_validate_event($event)) {
        if ($user_id > 0) {
            espaciosutil_cde_listmonk_write_sync_state(
                $user_id,
                'reconciliation_required',
                'invalid_event',
                (string) ($event['event_id'] ?? ''),
            );
        }

        return;
    }

    $url = espaciosutil_cde_listmonk_webhook_url();
    $token = espaciosutil_cde_listmonk_webhook_token();
    if ($url === '' || $token === '') {
        espaciosutil_cde_listmonk_write_sync_state(
            $user_id,
            'reconciliation_required',
            'configuration_error',
            $event['event_id'],
        );

        return;
    }

    $response = wp_remote_post($url, [
        'headers' => [
            'Authorization' => 'Bearer ' . $token,
            'Content-Type' => 'application/json',
        ],
        'timeout' => (int) apply_filters('espaciosutil_cde_listmonk_webhook_timeout', 20),
        'redirection' => 0,
        'sslverify' => true,
        'body' => wp_json_encode($event),
        'data_format' => 'body',
    ]);
    $result = espaciosutil_cde_listmonk_classify_response($response);

    if ($result['outcome'] === 'synced') {
        espaciosutil_cde_listmonk_write_sync_state($user_id, 'synced', 'synced', $event['event_id']);

        return;
    }

    if ($result['outcome'] === 'reconciliation_required') {
        espaciosutil_cde_listmonk_write_sync_state(
            $user_id,
            'reconciliation_required',
            (string) $result['code'],
            $event['event_id'],
        );

        return;
    }

    espaciosutil_cde_listmonk_retry_or_exhaust($event, max(1, $attempt));
}

/**
 * @return array{outcome: string, code: string}
 */
function espaciosutil_cde_listmonk_classify_response($response): array
{
    if (is_wp_error($response)) {
        return ['outcome' => 'retry', 'code' => 'transport_error'];
    }

    $status_code = (int) wp_remote_retrieve_response_code($response);
    if (in_array($status_code, [408, 425, 429], true) || $status_code >= 500 || $status_code < 1) {
        return ['outcome' => 'retry', 'code' => 'transient_response'];
    }

    if (in_array($status_code, [401, 403], true)) {
        return ['outcome' => 'reconciliation_required', 'code' => 'authentication_failed'];
    }

    if ($status_code < 200 || $status_code >= 300) {
        return ['outcome' => 'reconciliation_required', 'code' => 'contract_rejected'];
    }

    $body = json_decode((string) wp_remote_retrieve_body($response), true);
    if (! is_array($body)) {
        return ['outcome' => 'reconciliation_required', 'code' => 'invalid_response'];
    }

    if (($body['status'] ?? '') === 'synced' && ($body['readback_verified'] ?? false) === true) {
        return ['outcome' => 'synced', 'code' => 'synced'];
    }

    if (($body['status'] ?? '') === 'reconciliation_required') {
        $code = sanitize_key((string) ($body['code'] ?? ''));

        return [
            'outcome' => 'reconciliation_required',
            'code' => espaciosutil_cde_listmonk_sanitize_sync_code($code),
        ];
    }

    return ['outcome' => 'reconciliation_required', 'code' => 'invalid_response'];
}

/**
 * @param  array<string, mixed>  $event
 */
function espaciosutil_cde_listmonk_retry_or_exhaust(array $event, int $attempt): void
{
    $user_id = espaciosutil_cde_listmonk_parse_subject((string) $event['external_subject']);
    $max_attempts = max(1, (int) apply_filters('espaciosutil_cde_listmonk_max_attempts', 5));

    if ($attempt >= $max_attempts || ! function_exists('as_schedule_single_action')) {
        espaciosutil_cde_listmonk_write_sync_state(
            $user_id,
            'reconciliation_required',
            'retry_exhausted',
            (string) $event['event_id'],
        );

        return;
    }

    as_schedule_single_action(
        (int) current_time('timestamp', true) + espaciosutil_cde_listmonk_retry_delay($attempt, (string) $event['event_id']),
        'espaciosutil_cde_listmonk_process_event',
        [
            'event' => $event,
            'attempt' => $attempt + 1,
        ],
        'espaciosutil_cde_listmonk',
    );
}

function espaciosutil_cde_listmonk_retry_delay(int $attempt, string $event_id): int
{
    $base = min(HOUR_IN_SECONDS, MINUTE_IN_SECONDS * (2 ** max(0, $attempt - 1)));
    $jitter_limit = max(1, (int) floor($base * 0.25));
    $hash = (int) sprintf('%u', crc32($event_id . ':' . $attempt));

    return $base + ($hash % ($jitter_limit + 1));
}

/**
 * @return array<int, string>
 */
function espaciosutil_cde_listmonk_allowed_sync_codes(): array
{
    return [
        'synced',
        'duplicate_wp_identity',
        'email_owned_by_other_wp_user',
        'suppression_persist_failed',
        'suppression_without_canonical_profile',
        'global_profile_blocked',
        'ambiguous_readback',
        'listmonk_conflict',
        'authentication_failed',
        'contract_rejected',
        'invalid_response',
        'retry_exhausted',
        'configuration_error',
        'invalid_event',
        'upstream_reconciliation_required',
    ];
}

/**
 * @return array<int, string>
 */
function espaciosutil_cde_listmonk_allowed_codes_for_status(string $status): array
{
    if ($status === 'synced') {
        return ['synced'];
    }

    return array_values(array_diff(espaciosutil_cde_listmonk_allowed_sync_codes(), ['synced']));
}

function espaciosutil_cde_listmonk_is_valid_event_id(string $event_id): bool
{
    return preg_match(
        '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
        $event_id,
    ) === 1;
}

function espaciosutil_cde_listmonk_sanitize_sync_code(string $code): string
{
    return in_array($code, espaciosutil_cde_listmonk_allowed_sync_codes(), true)
        ? $code
        : 'upstream_reconciliation_required';
}

function espaciosutil_cde_listmonk_write_sync_state(
    int $user_id,
    string $status,
    string $code,
    string $event_id = '',
): void {
    if ($user_id < 1 || ! in_array($status, ['synced', 'reconciliation_required'], true)) {
        return;
    }

    $safe_code = espaciosutil_cde_listmonk_sanitize_sync_code(sanitize_key($code));
    if ($status === 'synced') {
        $safe_code = 'synced';
    } elseif ($safe_code === 'synced') {
        $safe_code = 'upstream_reconciliation_required';
    }

    update_user_meta($user_id, '_espaciosutil_cde_listmonk_sync_status', $status);
    update_user_meta($user_id, '_espaciosutil_cde_listmonk_sync_code', $safe_code);
    update_user_meta($user_id, '_espaciosutil_cde_listmonk_sync_updated_at', espaciosutil_cde_listmonk_now());

    if (espaciosutil_cde_listmonk_is_valid_event_id($event_id)) {
        update_user_meta($user_id, '_espaciosutil_cde_listmonk_sync_event_id', $event_id);
    } else {
        delete_user_meta($user_id, '_espaciosutil_cde_listmonk_sync_event_id');
    }
}

function espaciosutil_cde_listmonk_sync_result_handler(WP_REST_Request $request)
{
    $params = $request->get_json_params();
    $expected_keys = ['code', 'event_id', 'external_subject', 'status'];
    $actual_keys = array_keys($params);
    sort($actual_keys);
    $user_id = espaciosutil_cde_listmonk_parse_subject((string) ($params['external_subject'] ?? ''));
    $status = sanitize_key((string) ($params['status'] ?? ''));
    $code = sanitize_key((string) ($params['code'] ?? ''));
    $event_id = (string) ($params['event_id'] ?? '');

    if (
        $actual_keys !== $expected_keys
        || $user_id < 1
        || ! (get_user_by('id', $user_id) instanceof WP_User)
        || ! in_array($status, ['synced', 'reconciliation_required'], true)
        || ! in_array($code, espaciosutil_cde_listmonk_allowed_codes_for_status($status), true)
        || ! espaciosutil_cde_listmonk_is_valid_event_id($event_id)
    ) {
        return new WP_Error(
            'invalid_result',
            'The technical synchronization result is outside the accepted contract.',
            ['status' => 400],
        );
    }

    espaciosutil_cde_listmonk_write_sync_state(
        $user_id,
        $status,
        $code,
        $event_id,
    );

    return [
        'external_subject' => 'wp_user:' . $user_id,
        'status' => $status,
        'code' => $code,
        'updated_at' => espaciosutil_cde_listmonk_now(),
        'contract_version' => '1',
    ];
}

function espaciosutil_cde_listmonk_render_checkout_notice($level = null): void
{
    global $pmpro_review;

    $level_id = is_object($level) && isset($level->id) ? (int) $level->id : 0;
    if (
        ! empty($pmpro_review)
        || ! in_array($level_id, espaciosutil_cde_listmonk_level_ids(), true)
    ) {
        return;
    }

    $privacy_url = get_privacy_policy_url();
    ?>
    <p class="pmpro_cde_service_email_notice">
        <?php
        printf(
            /* translators: %s is the privacy policy URL. */
            __(
                'Para gestionar tu membresía y enviarte únicamente comunicaciones necesarias sobre el servicio CDE, sincronizaremos el correo electrónico y el nombre de tu cuenta con nuestro proveedor de correo. No son comunicaciones comerciales y puedes oponerte a recibirlas sin perder el acceso al curso. Consulta nuestra <a href="%s" target="_blank" rel="noopener noreferrer">Política de privacidad</a>.',
                'espaciosutil-cde-listmonk',
            ),
            esc_url($privacy_url),
        );
    ?>
    </p>
    <?php
}

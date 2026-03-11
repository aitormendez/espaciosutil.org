<?php
/**
 * Plugin Name: Espacio Sutil PMPro Trials
 * Description: Configura pruebas gratuitas por codigo para niveles concretos de Paid Memberships Pro.
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * User meta flag storing whether the member has already consumed the trial.
 */
function espaciosutil_pmpro_trial_used_meta_key(): string
{
    return 'espaciosutil_pmpro_trial_used';
}

/**
 * Trial configuration keyed by PMPro level ID.
 *
 * @return array<int, array{delay_days:int}>
 */
function espaciosutil_pmpro_trial_configs(): array
{
    $configs = [
        11 => [
            'delay_days' => 7,
        ],
    ];

    return apply_filters('espaciosutil_pmpro_trial_configs', $configs);
}

/**
 * Return normalized trial configuration for a level object or level ID.
 *
 * @param mixed $level Level object or level ID.
 * @return array<string, mixed>|null
 */
function espaciosutil_pmpro_get_trial_config($level): ?array
{
    $level_id = 0;

    if (is_object($level) && isset($level->id)) {
        $level_id = (int) $level->id;
    } elseif (is_numeric($level)) {
        $level_id = (int) $level;
    }

    if ($level_id < 1) {
        return null;
    }

    $configs = espaciosutil_pmpro_trial_configs();
    if (!isset($configs[$level_id]) || !is_array($configs[$level_id])) {
        return null;
    }

    $delay_days = isset($configs[$level_id]['delay_days']) ? (int) $configs[$level_id]['delay_days'] : 0;
    if ($delay_days < 1) {
        return null;
    }

    return [
        'level_id' => $level_id,
        'delay_days' => $delay_days,
    ];
}

/**
 * Return a human-readable label for the configured trial.
 */
function espaciosutil_pmpro_get_trial_label(int $level_id): string
{
    $config = espaciosutil_pmpro_get_trial_config($level_id);
    if ($config === null) {
        return '';
    }

    return sprintf(
        _n('%d dia gratis', '%d dias gratis', (int) $config['delay_days'], 'espaciosutil-pmpro-trials'),
        (int) $config['delay_days']
    );
}

/**
 * Determine whether a user has already consumed their one-time trial.
 */
function espaciosutil_pmpro_user_has_used_trial(int $user_id): bool
{
    if ($user_id < 1) {
        return false;
    }

    return (bool) get_user_meta($user_id, espaciosutil_pmpro_trial_used_meta_key(), true);
}

/**
 * Mark the one-time trial as consumed for a user.
 */
function espaciosutil_pmpro_mark_trial_used(int $user_id): void
{
    if ($user_id < 1) {
        return;
    }

    update_user_meta($user_id, espaciosutil_pmpro_trial_used_meta_key(), 1);
}

/**
 * Decide whether a user is eligible for the configured trial.
 */
function espaciosutil_pmpro_user_is_eligible_for_trial(int $user_id, $level): bool
{
    if (espaciosutil_pmpro_get_trial_config($level) === null) {
        return false;
    }

    if ($user_id < 1) {
        return true;
    }

    return !espaciosutil_pmpro_user_has_used_trial($user_id);
}

/**
 * Return the best-effort user ID available during page rendering.
 */
function espaciosutil_pmpro_get_checkout_context_user_id(): int
{
    $user_id = get_current_user_id();
    if ($user_id > 0) {
        return $user_id;
    }

    if (!empty($_REQUEST['bemail'])) {
        $email_user_id = email_exists(sanitize_email(wp_unslash($_REQUEST['bemail'])));
        if (!empty($email_user_id)) {
            return (int) $email_user_id;
        }
    }

    return 0;
}

/**
 * Build the delayed start date used by PMPro gateways such as Stripe.
 */
function espaciosutil_pmpro_get_trial_start_date(int $delay_days): string
{
    return date('Y-m-d H:i:s', strtotime('+' . $delay_days . ' days', current_time('timestamp')));
}

/**
 * Build a short recurring billing phrase in Spanish.
 *
 * @param object $level
 * @return string
 */
function espaciosutil_pmpro_get_recurring_phrase($level): string
{
    if (!is_object($level)) {
        return '';
    }

    $cycle_number = isset($level->cycle_number) ? (int) $level->cycle_number : 0;
    $cycle_period = isset($level->cycle_period) ? strtolower((string) $level->cycle_period) : '';

    if ($cycle_number < 1 || $cycle_period === '') {
        return '';
    }

    $single_period_labels = [
        'day' => 'al dia',
        'week' => 'a la semana',
        'month' => 'al mes',
        'year' => 'al año',
    ];

    $plural_period_labels = [
        'day' => 'dias',
        'week' => 'semanas',
        'month' => 'meses',
        'year' => 'años',
    ];

    if ($cycle_number === 1 && isset($single_period_labels[$cycle_period])) {
        return $single_period_labels[$cycle_period];
    }

    if (!isset($plural_period_labels[$cycle_period])) {
        return '';
    }

    return sprintf('cada %1$d %2$s', $cycle_number, $plural_period_labels[$cycle_period]);
}

/**
 * Apply the trial to the checkout order only if the member is eligible.
 *
 * This runs after PMPro has already created/logged in the user, so we can make
 * the decision per account instead of per subscription.
 *
 * @param MemberOrder $order
 * @return MemberOrder
 */
function espaciosutil_pmpro_apply_trial_to_checkout_order($order)
{
    global $pmpro_level;

    if (!is_object($order) || empty($order->membership_id)) {
        return $order;
    }

    $level = method_exists($order, 'getMembershipLevelAtCheckout') ? $order->getMembershipLevelAtCheckout() : null;
    if (!is_object($level)) {
        return $order;
    }

    $config = espaciosutil_pmpro_get_trial_config($level);
    if ($config === null || !espaciosutil_pmpro_user_is_eligible_for_trial((int) $order->user_id, $level)) {
        return $order;
    }

    $level->initial_payment = 0;
    $level->trial_amount = 0;
    $level->trial_limit = 0;
    $level->profile_start_date = espaciosutil_pmpro_get_trial_start_date((int) $config['delay_days']);

    $order->membership_level = $level;
    $order->subtotal = 0;
    $order->tax = 0;
    $order->total = 0;
    $pmpro_level = $level;

    return $order;
}
add_filter('pmpro_checkout_order', 'espaciosutil_pmpro_apply_trial_to_checkout_order', 20);

/**
 * Return whether the current page context should display the trial copy.
 */
function espaciosutil_pmpro_should_show_trial_for_level($level): bool
{
    $config = espaciosutil_pmpro_get_trial_config($level);
    if ($config === null) {
        return false;
    }

    $user_id = espaciosutil_pmpro_get_checkout_context_user_id();
    return espaciosutil_pmpro_user_is_eligible_for_trial($user_id, $level);
}

/**
 * Clarify the cost text shown on checkout and other PMPro screens.
 *
 * @param string $cost_text
 * @param object $level
 * @param bool $tags
 * @param bool $short
 * @return string
 */
function espaciosutil_pmpro_append_trial_notice_to_cost_text($cost_text, $level, $tags = true, $short = false): string
{
    if (!espaciosutil_pmpro_should_show_trial_for_level($level)) {
        return $cost_text;
    }

    $config = espaciosutil_pmpro_get_trial_config($level);
    if ($config === null) {
        return $cost_text;
    }

    $trial_notice = sprintf(
        _n(
            'Incluye %d dia de prueba gratuita.',
            'Incluye %d dias de prueba gratuita.',
            (int) $config['delay_days'],
            'espaciosutil-pmpro-trials'
        ),
        (int) $config['delay_days']
    );

    $recurring_phrase = espaciosutil_pmpro_get_recurring_phrase($level);
    if ($recurring_phrase === '' || !isset($level->billing_amount)) {
        return $trial_notice . ' ' . $cost_text;
    }

    $billing_amount = pmpro_formatPrice($level->billing_amount);
    if ($tags) {
        $billing_amount = '<strong>' . $billing_amount . '</strong>';
    }

    return sprintf(
        '%1$s El primer cobro sera de %2$s %3$s al terminar la prueba.',
        $trial_notice,
        $billing_amount,
        $recurring_phrase
    );
}
add_filter('pmpro_level_cost_text', 'espaciosutil_pmpro_append_trial_notice_to_cost_text', 20, 4);

/**
 * Persist the one-time trial consumption after a successful checkout.
 */
function espaciosutil_pmpro_mark_trial_as_consumed_after_checkout($user_id, $order): void
{
    if (!is_object($order) || (int) $user_id < 1) {
        return;
    }

    $level = method_exists($order, 'getMembershipLevelAtCheckout') ? $order->getMembershipLevelAtCheckout() : null;
    if (!is_object($level)) {
        return;
    }

    $config = espaciosutil_pmpro_get_trial_config($level);
    if ($config === null) {
        return;
    }

    if ((float) $order->total !== 0.0) {
        return;
    }

    if (empty($level->profile_start_date)) {
        return;
    }

    espaciosutil_pmpro_mark_trial_used((int) $user_id);
}
add_action('pmpro_after_checkout', 'espaciosutil_pmpro_mark_trial_as_consumed_after_checkout', 20, 2);

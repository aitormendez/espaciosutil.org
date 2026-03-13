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
        12 => [
            'delay_days' => 7,
        ],
        13 => [
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
 * Get the checkout level snapshot stored on an order.
 *
 * @param mixed $order
 * @return object|null
 */
function espaciosutil_pmpro_get_order_level_snapshot($order): ?object
{
    if (!is_object($order)) {
        return null;
    }

    if (!empty($order->id)) {
        $checkout_level = get_pmpro_membership_order_meta((int) $order->id, 'checkout_level', true);
        if (is_array($checkout_level) && !empty($checkout_level['id'])) {
            return (object) $checkout_level;
        }
    }

    if (method_exists($order, 'getMembershipLevelAtCheckout')) {
        $level = $order->getMembershipLevelAtCheckout();
        if (is_object($level)) {
            return $level;
        }
    }

    if (!empty($order->membership_level) && is_object($order->membership_level)) {
        return $order->membership_level;
    }

    return null;
}

/**
 * Build order-specific trial details from the level snapshot stored at checkout.
 *
 * @param mixed $order
 * @return array<string, string|int>|null
 */
function espaciosutil_pmpro_get_order_trial_details($order): ?array
{
    $level = espaciosutil_pmpro_get_order_level_snapshot($order);
    if (!is_object($level)) {
        return null;
    }

    $config = espaciosutil_pmpro_get_trial_config($level);
    if ($config === null) {
        return null;
    }

    $profile_start_date = empty($level->profile_start_date) ? 0 : strtotime((string) $level->profile_start_date);
    $billing_amount = isset($level->billing_amount) ? (float) $level->billing_amount : 0.0;
    if ($profile_start_date <= 0 || $billing_amount <= 0) {
        return null;
    }

    $next_charge_date = wp_date('j \\d\\e F \\d\\e Y', $profile_start_date);
    $next_charge_amount = pmpro_formatPrice($billing_amount);
    $recurring_phrase = espaciosutil_pmpro_get_recurring_phrase($level);

    return [
        'delay_days' => (int) $config['delay_days'],
        'next_charge_timestamp' => $profile_start_date,
        'next_charge_date' => $next_charge_date,
        'next_charge_amount' => $next_charge_amount,
        'recurring_phrase' => $recurring_phrase,
    ];
}

/**
 * Build a short HTML summary for a trial-enabled order.
 *
 * @param mixed $order
 * @param bool $include_manage_link
 * @return string
 */
function espaciosutil_pmpro_get_order_trial_summary_html($order, bool $include_manage_link = false): string
{
    $details = espaciosutil_pmpro_get_order_trial_details($order);
    if ($details === null) {
        return '';
    }

    $trial_label = sprintf(
        _n('%d dia gratis', '%d dias gratis', (int) $details['delay_days'], 'espaciosutil-pmpro-trials'),
        (int) $details['delay_days']
    );

    $summary = sprintf(
        '<p><strong style="font-weight:200;">Prueba activada:</strong> %1$s desde hoy. El primer cobro sera el <strong style="font-weight:200;">%2$s</strong> por <strong style="font-weight:200;">%3$s</strong>%4$s.</p>',
        esc_html($trial_label),
        esc_html((string) $details['next_charge_date']),
        wp_kses_post((string) $details['next_charge_amount']),
        $details['recurring_phrase'] !== '' ? ' ' . esc_html((string) $details['recurring_phrase']) : ''
    );

    if (!$include_manage_link) {
        return $summary;
    }

    return $summary . sprintf(
        '<p>Puedes gestionar tu membresia desde <a href="%1$s" style="color:#b50000;text-decoration:none;">tu cuenta</a>.</p>',
        esc_url(pmpro_url('account'))
    );
}

/**
 * Build an email-only conditions block for trial-enabled orders.
 *
 * @param mixed $order
 * @param string $fallback_cost_text
 * @return string
 */
function espaciosutil_pmpro_get_order_trial_conditions_email_html($order, string $fallback_cost_text = ''): string
{
    $details = espaciosutil_pmpro_get_order_trial_details($order);
    if ($details === null) {
        if ($fallback_cost_text === '') {
            return '';
        }

        return sprintf(
            '<p style="margin:0 0 24px;"><strong style="color:#c7c3c3;font-weight:200;">Condiciones:</strong> %1$s</p>',
            wp_kses_post($fallback_cost_text)
        );
    }

    $trial_label = sprintf(
        _n('%d dia de prueba gratuita.', '%d dias de prueba gratuita.', (int) $details['delay_days'], 'espaciosutil-pmpro-trials'),
        (int) $details['delay_days']
    );

    $first_charge_line = sprintf(
        'El primer cobro sera de %1$s%2$s al terminar la prueba.',
        wp_kses_post((string) $details['next_charge_amount']),
        $details['recurring_phrase'] !== '' ? ' ' . esc_html((string) $details['recurring_phrase']) : ''
    );

    return sprintf(
        '<p style="margin:0 0 8px;color:#c7c3c3;">Condiciones</p><p style="margin:0 0 24px;">- %1$s<br />- %2$s</p>',
        esc_html($trial_label),
        $first_charge_line
    );
}

/**
 * Build an email-only activation block for trial-enabled orders.
 *
 * @param mixed $order
 * @param bool $include_manage_link
 * @return string
 */
function espaciosutil_pmpro_get_order_trial_summary_email_html($order, bool $include_manage_link = false): string
{
    $details = espaciosutil_pmpro_get_order_trial_details($order);
    if ($details === null) {
        return '';
    }

    $trial_label = sprintf(
        _n('%d dia gratis desde hoy.', '%d dias gratis desde hoy.', (int) $details['delay_days'], 'espaciosutil-pmpro-trials'),
        (int) $details['delay_days']
    );

    $first_charge_line = sprintf(
        'El primer cobro sera el %1$s por %2$s%3$s.',
        esc_html((string) $details['next_charge_date']),
        wp_kses_post((string) $details['next_charge_amount']),
        $details['recurring_phrase'] !== '' ? ' ' . esc_html((string) $details['recurring_phrase']) : ''
    );

    $summary = sprintf(
        '<p style="margin:0 0 8px;color:#c7c3c3;">Prueba activada</p><p style="margin:0 0 24px;">- %1$s<br />- %2$s</p>',
        esc_html($trial_label),
        $first_charge_line
    );

    if (!$include_manage_link) {
        return $summary;
    }

    return $summary . sprintf(
        '<p>Puedes gestionar tu membresia desde <a href="%1$s" style="color:#b50000;text-decoration:none;">tu cuenta</a>.</p>',
        esc_url(pmpro_url('account'))
    );
}

/**
 * Add a dedicated template for reminders before the first paid cycle after a trial.
 *
 * @param array<string, array<string, string>> $templates
 * @return array<string, array<string, string>>
 */
function espaciosutil_pmpro_add_trial_recurring_email_template(array $templates): array
{
    $templates['membership_recurring_trial'] = [
        'subject' => __('Tu periodo de prueba en !!sitename!! termina pronto', 'espaciosutil-pmpro-trials'),
        'description' => __('Recordatorio de fin de prueba', 'espaciosutil-pmpro-trials'),
        'body' => wp_kses_post(
            '<p>Tu periodo de prueba en !!sitename!! termina pronto.</p>
<p>El primer cobro de tu plan !!membership_level_name!! se realizara el !!renewaldate!! por !!billing_amount!!.</p>
<p>Si no deseas continuar, puedes cancelar tu suscripcion aqui: !!cancel_url!!</p>'
        ),
        'help_text' => __('Este correo se envia 2 dias antes del primer cobro posterior al periodo de prueba.', 'espaciosutil-pmpro-trials'),
    ];

    return $templates;
}
add_filter('pmproet_templates', 'espaciosutil_pmpro_add_trial_recurring_email_template');

/**
 * Get the first checkout order associated with a subscription.
 */
function espaciosutil_pmpro_get_first_order_for_subscription(PMPro_Subscription $subscription): ?MemberOrder
{
    global $wpdb;

    $subscription_transaction_id = (string) $subscription->get_subscription_transaction_id();
    if ($subscription_transaction_id === '') {
        return null;
    }

    $order_id = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT id
            FROM {$wpdb->pmpro_membership_orders}
            WHERE subscription_transaction_id = %s
                AND user_id = %d
                AND membership_id = %d
                AND status NOT IN ('token', 'error', 'review')
            ORDER BY timestamp ASC, id ASC
            LIMIT 1",
            $subscription_transaction_id,
            (int) $subscription->get_user_id(),
            (int) $subscription->get_membership_level_id()
        )
    );

    if (empty($order_id)) {
        return null;
    }

    $order = new MemberOrder();
    if (!$order->getMemberOrderByID((int) $order_id) || empty($order->id)) {
        return null;
    }

    return $order;
}

/**
 * Determine whether a subscription is still awaiting its first paid charge after a trial.
 */
function espaciosutil_pmpro_subscription_is_waiting_for_first_trial_charge(PMPro_Subscription $subscription): bool
{
    $initial_order = espaciosutil_pmpro_get_first_order_for_subscription($subscription);
    if (!$initial_order instanceof MemberOrder) {
        return false;
    }

    $trial_details = espaciosutil_pmpro_get_order_trial_details($initial_order);
    if ($trial_details === null) {
        return false;
    }

    $next_payment_timestamp = (int) $subscription->get_next_payment_date();
    if ($next_payment_timestamp <= 0) {
        return false;
    }

    return wp_date('Y-m-d', $next_payment_timestamp) === wp_date('Y-m-d', (int) $trial_details['next_charge_timestamp']);
}

/**
 * Register recurring payment reminders:
 * - 7 days for regular renewals
 * - 2 days for the first paid charge after a trial
 *
 * @param array<int, string> $emails
 * @return array<int, string>
 */
function espaciosutil_pmpro_customize_recurring_payment_reminders($emails): array
{
    if (!is_array($emails)) {
        $emails = [];
    }

    $emails[2] = 'membership_recurring_trial';
    ksort($emails, SORT_NUMERIC);

    return $emails;
}
add_filter('pmpro_upcoming_recurring_payment_reminder', 'espaciosutil_pmpro_customize_recurring_payment_reminders');

/**
 * Build PMPro email data for recurring reminder emails.
 *
 * @return array<string, string|int>
 */
function espaciosutil_pmpro_get_recurring_reminder_email_data(PMPro_Subscription $subscription, WP_User $user): array
{
    $membership_level = pmpro_getLevel($subscription->get_membership_level_id());

    return [
        'subject' => '',
        'name' => $user->display_name,
        'user_login' => $user->user_login,
        'sitename' => get_option('blogname'),
        'site_url' => home_url('/'),
        'membership_id' => $subscription->get_membership_level_id(),
        'membership_level_name' => empty($membership_level)
            ? sprintf(esc_html__('[Deleted level #%d]', 'paid-memberships-pro'), $subscription->get_membership_level_id())
            : $membership_level->name,
        'membership_cost' => $subscription->get_cost_text(),
        'billing_amount' => pmpro_formatPrice($subscription->get_billing_amount()),
        'renewaldate' => date_i18n(get_option('date_format'), $subscription->get_next_payment_date()),
        'siteemail' => get_option('pmpro_from_email'),
        'login_link' => wp_login_url(),
        'login_url' => wp_login_url(),
        'display_name' => $user->display_name,
        'user_email' => $user->user_email,
        'cancel_link' => wp_login_url(pmpro_url('cancel')),
        'cancel_url' => wp_login_url(pmpro_url('cancel')),
    ];
}

/**
 * Replace PMPro's recurring reminder sender so we can treat trial endings separately
 * without affecting normal renewal reminders.
 */
function espaciosutil_pmpro_send_recurring_payment_reminder_email($subscription_id, $template, $days = null): void
{
    $subscription = new PMPro_Subscription((int) $subscription_id);
    $user = get_userdata($subscription->get_user_id());

    $days_until_payment = floor(($subscription->get_next_payment_date() - current_time('timestamp')) / DAY_IN_SECONDS);

    if (empty($user)) {
        update_pmpro_subscription_meta((int) $subscription_id, 'pmprorm_last_next_payment_date', $subscription->get_next_payment_date('Y-m-d', false));
        update_pmpro_subscription_meta((int) $subscription_id, 'pmprorm_last_days', $days_until_payment);
        return;
    }

    $is_first_trial_charge = espaciosutil_pmpro_subscription_is_waiting_for_first_trial_charge($subscription);
    $should_send = match ((string) $template) {
        'membership_recurring_trial' => $is_first_trial_charge,
        'membership_recurring' => !$is_first_trial_charge,
        default => true,
    };

    if ($should_send) {
        $email = new PMProEmail();
        $email->email = $user->user_email;
        $email->template = (string) $template;
        $email->data = espaciosutil_pmpro_get_recurring_reminder_email_data($subscription, $user);
        $email->sendEmail();
    }

    update_pmpro_subscription_meta((int) $subscription_id, 'pmprorm_last_next_payment_date', $subscription->get_next_payment_date('Y-m-d', false));
    update_pmpro_subscription_meta((int) $subscription_id, 'pmprorm_last_days', $days_until_payment);
}

/**
 * Override PMPro's recurring reminder sender with Espacio Sutil's trial-aware version.
 */
function espaciosutil_pmpro_override_recurring_payment_reminder_sender(): void
{
    if (!class_exists('PMPro_Recurring_Actions')) {
        return;
    }

    remove_action(
        'pmpro_recurring_payment_reminder_email',
        [PMPro_Recurring_Actions::instance(), 'send_recurring_payment_reminder_email'],
        10
    );

    add_action(
        'pmpro_recurring_payment_reminder_email',
        'espaciosutil_pmpro_send_recurring_payment_reminder_email',
        10,
        3
    );
}
add_action('plugins_loaded', 'espaciosutil_pmpro_override_recurring_payment_reminder_sender', 30);

/**
 * Determine whether the current environment should auto-complete Stripe token orders.
 */
function espaciosutil_pmpro_should_autocomplete_stripe_token_orders(): bool
{
    $enabled = defined('WP_ENV') && WP_ENV === 'development';

    return (bool) apply_filters('espaciosutil_pmpro_autocomplete_stripe_token_orders', $enabled);
}

/**
 * Complete a Stripe Checkout token order on the confirmation page when webhooks
 * are unavailable in local development.
 */
function espaciosutil_pmpro_maybe_complete_stripe_token_order_on_confirmation(): void
{
    if (!espaciosutil_pmpro_should_autocomplete_stripe_token_orders()) {
        return;
    }

    if (is_admin() || !is_user_logged_in() || !function_exists('is_page')) {
        return;
    }

    $confirmation_page_id = (int) get_option('pmpro_confirmation_page_id');
    if ($confirmation_page_id < 1 || !is_page($confirmation_page_id)) {
        return;
    }

    if (!empty($_GET['pmpro_rechecked'])) {
        return;
    }

    $level_id = 0;
    if (!empty($_REQUEST['pmpro_level'])) {
        $level_id = (int) $_REQUEST['pmpro_level'];
    } elseif (!empty($_REQUEST['level'])) {
        $level_id = (int) $_REQUEST['level'];
    }

    $order = new MemberOrder();
    $order->getLastMemberOrder(get_current_user_id(), 'token', $level_id > 0 ? $level_id : null);
    if (empty($order->id) || $order->gateway !== 'stripe') {
        return;
    }

    $gateway = new PMProGateway_stripe();
    $result = $gateway->check_token_order($order);
    if ($result !== true) {
        return;
    }

    $redirect_url = add_query_arg('pmpro_rechecked', '1');
    wp_safe_redirect($redirect_url);
    exit;
}
add_action('template_redirect', 'espaciosutil_pmpro_maybe_complete_stripe_token_order_on_confirmation', 1);

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
        $billing_amount = '<strong style="font-weight:200;">' . $billing_amount . '</strong>';
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
 * Explain the trial on the confirmation page once the order exists.
 *
 * @param string $message
 * @param mixed $invoice
 * @return string
 */
function espaciosutil_pmpro_append_trial_summary_to_confirmation_message(string $message, $invoice): string
{
    $trial_summary = espaciosutil_pmpro_get_order_trial_summary_html($invoice, true);
    if ($trial_summary === '') {
        return $message;
    }

    return $message . $trial_summary;
}
add_filter('pmpro_confirmation_message', 'espaciosutil_pmpro_append_trial_summary_to_confirmation_message', 30, 2);

/**
 * Add trial-specific placeholders to PMPro emails for orders that started at 0.
 *
 * @param mixed $data
 * @param mixed $email
 * @return mixed
 */
function espaciosutil_pmpro_add_trial_summary_to_email_data($data, $email)
{
    if (!is_array($data)) {
        return $data;
    }

    $data['trial_summary'] = '';
    $data['trial_conditions'] = '';

    if (empty($data['order_id']) || !is_string($data['order_id'])) {
        return $data;
    }

    $order = new MemberOrder();
    if (!$order->getMemberOrderByCode($data['order_id'])) {
        return $data;
    }

    if (empty($order->discount_code) && !$order->getDiscountCode()) {
        $data['discount_code'] = '';
    }

    $data['trial_conditions'] = espaciosutil_pmpro_get_order_trial_conditions_email_html(
        $order,
        isset($data['membership_cost']) && is_string($data['membership_cost']) ? $data['membership_cost'] : ''
    );
    $data['trial_summary'] = espaciosutil_pmpro_get_order_trial_summary_email_html($order, true);

    return $data;
}
add_filter('pmpro_email_data', 'espaciosutil_pmpro_add_trial_summary_to_email_data', 20, 2);

/**
 * Add trial details to the invoice details list to clarify the 0 EUR initial order.
 *
 * @param mixed $invoice
 * @return void
 */
function espaciosutil_pmpro_render_trial_invoice_bullets($invoice): void
{
    $details = espaciosutil_pmpro_get_order_trial_details($invoice);
    if ($details === null) {
        return;
    }

?>
    <li class="<?php echo esc_attr(pmpro_get_element_class('pmpro_list_item')); ?>">
        <strong>Periodo de prueba:</strong>
        <?php
        echo esc_html(
            sprintf(
                _n('%d dia gratis', '%d dias gratis', (int) $details['delay_days'], 'espaciosutil-pmpro-trials'),
                (int) $details['delay_days']
            )
        );
        ?>
    </li>
    <li class="<?php echo esc_attr(pmpro_get_element_class('pmpro_list_item')); ?>">
        <strong>Primer cobro:</strong>
        <?php
        echo wp_kses_post((string) $details['next_charge_amount']);
        if ($details['recurring_phrase'] !== '') {
            echo ' ' . esc_html((string) $details['recurring_phrase']);
        }
        echo esc_html(' el ' . (string) $details['next_charge_date']);
        ?>
    </li>
<?php
}
add_action('pmpro_invoice_bullets_top', 'espaciosutil_pmpro_render_trial_invoice_bullets', 10, 1);

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

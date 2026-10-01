<?php

/** Offline contract test: real PMPro MemberOrder + site trial hooks; no WP bootstrap. */
declare(strict_types=1);
const ABSPATH = __DIR__ . '/unused-wordpress/';
date_default_timezone_set('UTC');
$hooks = [];
function add_filter($name, $callback, $priority = 10, $args = 1): void
{
    $GLOBALS['hooks'][$name][$priority][] = [$callback, $args];
}
function add_action($name, $callback, $priority = 10, $args = 1): void
{
    add_filter($name, $callback, $priority, $args);
}
function apply_filters($name, $value, ...$args)
{
    $callbacks = $GLOBALS['hooks'][$name] ?? [];
    ksort($callbacks);
    foreach ($callbacks as $group) {
        foreach ($group as [$callback, $count]) {
            $value = $callback(...array_slice([$value, ...$args], 0, $count));
        }
    }
    return $value;
}
function get_user_meta($id, $key, $single)
{
    return $id === 102;
}
function current_time($type)
{
    return strtotime('2026-09-29 12:00:00 UTC');
}
function pmpro_round_price($value)
{
    return round((float) $value, 2);
}
function __($text, $domain = '')
{
    return $text;
}
function wp_kses_post($text)
{
    return $text;
}
function sanitize_text_field($text)
{
    return trim(strip_tags($text));
}
function check($value, $message): void
{
    if (!$value) {
        throw new RuntimeException($message);
    }
}
// Forbidden side effects fail immediately, rather than being silently ignored.
function update_user_meta(...$args)
{
    throw new RuntimeException('Unexpected user write');
}
function wp_mail(...$args)
{
    throw new RuntimeException('Unexpected email');
}
function wp_remote_post(...$args)
{
    throw new RuntimeException('Unexpected network');
}
require __DIR__ . '/../web/app/plugins/paid-memberships-pro/classes/class.memberorder.php';
require __DIR__ . '/../web/app/mu-plugins/espaciosutil-pmpro-trials.php';
foreach ([11, 12, 13, 99] as $levelId) {
    foreach ([101, 102] as $userId) {
        foreach (['', 'SYNTHETIC'] as $discount) {
            $order = (new ReflectionClass(MemberOrder::class))->newInstanceWithoutConstructor();
            $order->membership_id = $levelId;
            $order->user_id = $userId;
            $order->membership_level = (object) ['id' => $levelId, 'initial_payment' => 30, 'billing_amount' => 30, 'trial_amount' => 0, 'discount_code' => $discount];
            $order->subtotal = 30;
            $order->tax = 0;
            $order->total = 30;
            $result = apply_filters('pmpro_checkout_order', $order);
            $eligible = $levelId !== 99 && $userId === 101 && $discount === '';
            check((float) $result->total === ($eligible ? 0.0 : 30.0), 'Unexpected trial total');
            check((float) $result->membership_level->billing_amount === 30.0, 'Recurring price changed');
            check((bool) $result->membership_level->espaciosutil_trial_applied === $eligible, 'Trial eligibility mismatch');
            if ($eligible) {
                check($result->membership_level->profile_start_date === '2026-10-06 12:00:00', 'Trial must defer exactly seven days');
            }
        }
    }
}
$reminders = apply_filters('pmpro_upcoming_recurring_payment_reminder', [7 => 'membership_recurring']);
check($reminders === [2 => 'membership_recurring_trial', 7 => 'membership_recurring'], 'Reminder timing changed');
check(isset(apply_filters('pmproet_templates', [])['membership_recurring_trial']), 'Custom template registration missing');
echo "PASS: 16 trial cases using PMPro MemberOrder, reminders and email registration; no external effects\n";

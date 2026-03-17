<?php

if (!defined('ABSPATH')) {
    fwrite(STDERR, "Este script debe ejecutarse con wp eval-file.\n");
    exit(1);
}

require_once ABSPATH . 'wp-admin/includes/user.php';

if (!class_exists('PMPro_Discount_Code') || !function_exists('pmpro_getLevel')) {
    fwrite(STDERR, "Paid Memberships Pro no está disponible.\n");
    exit(1);
}

$errors = [];
$created_user_ids = [];
$created_discount_code_id = 0;
$original_user_id = get_current_user_id();
$timestamp = time();
$discount_code = 'TRIALOFF' . $timestamp;

$assert = static function ($condition, string $message) use (&$errors): void {
    if (!$condition) {
        $errors[] = $message;
    }
};

$level_ids = [11, 12, 13];
$discounted_initial_payment = 1.0;
$discounted_billing_amount = 1.0;

$levels = [];
foreach ($level_ids as $level_id) {
    $level = pmpro_getLevel($level_id);
    $assert(is_object($level), "No se ha encontrado el nivel {$level_id}.");
    if (!is_object($level)) {
        continue;
    }

    $levels[$level_id] = [
        'initial_payment' => $discounted_initial_payment,
        'billing_amount' => $discounted_billing_amount,
        'cycle_number' => isset($level->cycle_number) ? (int) $level->cycle_number : 0,
        'cycle_period' => isset($level->cycle_period) ? (string) $level->cycle_period : 'Month',
        'billing_limit' => 1,
        'trial_amount' => 0,
        'trial_limit' => 0,
        'expiration_number' => isset($level->expiration_number) ? (int) $level->expiration_number : 0,
        'expiration_period' => isset($level->expiration_period) ? (string) $level->expiration_period : '',
    ];
}

if ($levels !== []) {
    $pmpro_discount_code = new PMPro_Discount_Code();
    $pmpro_discount_code->code = $discount_code;
    $pmpro_discount_code->starts = wp_date('Y-m-d');
    $pmpro_discount_code->expires = wp_date('Y-m-d', strtotime('+30 days', current_time('timestamp')));
    $pmpro_discount_code->uses = 0;
    $pmpro_discount_code->levels = $levels;

    $saved_discount_code = $pmpro_discount_code->save();
    if ($saved_discount_code && !empty($saved_discount_code->id)) {
        $created_discount_code_id = (int) $saved_discount_code->id;
    }

    $assert($created_discount_code_id > 0, 'No se ha podido crear el código de prueba de PMPro.');
}

$eligible_user_id = wp_create_user(
    'discount-eligible-' . $timestamp,
    wp_generate_password(20, true),
    'discount-eligible-' . $timestamp . '@example.com'
);

if (!is_wp_error($eligible_user_id)) {
    $created_user_ids[] = (int) $eligible_user_id;
}

$assert(!is_wp_error($eligible_user_id), 'No se ha podido crear el usuario elegible para probar el cupón.');

$build_order = static function (int $user_id, object $level): MemberOrder {
    $order = new MemberOrder();
    $order->user_id = $user_id;
    $order->membership_id = (int) $level->id;
    $order->membership_level = clone $level;
    $order->subtotal = isset($level->initial_payment) ? (float) $level->initial_payment : 0.0;
    $order->tax = 0;
    $order->total = isset($level->initial_payment) ? (float) $level->initial_payment : 0.0;

    return $order;
};

if (!is_wp_error($eligible_user_id)) {
    wp_set_current_user((int) $eligible_user_id);

    foreach ($level_ids as $level_id) {
        $discounted_level = pmpro_getLevelAtCheckout($level_id, $discount_code);
        $assert(is_object($discounted_level), "No se ha podido resolver el nivel {$level_id} con el cupón.");
        if (!is_object($discounted_level)) {
            continue;
        }

        $assert(
            !espaciosutil_pmpro_should_show_trial_for_level($discounted_level),
            "El trial sigue mostrándose para el nivel {$level_id} con cupón activo."
        );

        $discounted_order = apply_filters('pmpro_checkout_order', $build_order((int) $eligible_user_id, $discounted_level));

        $assert(
            abs((float) $discounted_order->total - $discounted_initial_payment) < 0.00001,
            "El total del pedido con cupón no respeta el importe descontado en el nivel {$level_id}."
        );
        $assert(
            empty($discounted_order->membership_level->profile_start_date),
            "El pedido con cupón todavía recibe profile_start_date de trial en el nivel {$level_id}."
        );

        $trial_flag_name = espaciosutil_pmpro_trial_applied_flag_name();
        $assert(
            property_exists($discounted_order->membership_level, $trial_flag_name)
                && !$discounted_order->membership_level->{$trial_flag_name},
            "El pedido con cupón marca erróneamente el trial como aplicado en el nivel {$level_id}."
        );
    }

    do_action('pmpro_after_checkout', (int) $eligible_user_id, $discounted_order);
    $assert(
        !espaciosutil_pmpro_user_has_used_trial((int) $eligible_user_id),
        'El usuario quedó marcado como si hubiera consumido el trial después de usar un cupón.'
    );
}

wp_set_current_user($original_user_id);

foreach ($created_user_ids as $created_user_id) {
    wp_delete_user($created_user_id);
}

if ($created_discount_code_id > 0) {
    global $wpdb;
    $wpdb->delete($wpdb->pmpro_discount_codes_levels, ['code_id' => $created_discount_code_id], ['%d']);
    $wpdb->delete($wpdb->pmpro_discount_codes, ['id' => $created_discount_code_id], ['%d']);
}

if (!empty($errors)) {
    fwrite(STDERR, "PMPro discount code verification failed:\n");
    foreach ($errors as $error) {
        fwrite(STDERR, '- ' . $error . "\n");
    }
    exit(1);
}

fwrite(STDOUT, "PMPro discount code verification passed.\n");
fwrite(STDOUT, "Los cupones no consumen el trial ni disparan profile_start_date.\n");
fwrite(STDOUT, "Niveles verificados: 11, 12 y 13.\n");

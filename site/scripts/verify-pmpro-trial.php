<?php

if (!defined('ABSPATH')) {
    fwrite(STDERR, "Este script debe ejecutarse con wp eval-file.\n");
    exit(1);
}

require_once ABSPATH . 'wp-admin/includes/user.php';

$errors = [];
$created_user_ids = [];
$original_user_id = get_current_user_id();

$assert = static function ($condition, string $message) use (&$errors): void {
    if (!$condition) {
        $errors[] = $message;
    }
};

$trial_level_id = 11;
$control_level_id = 12;
$expected_delay_days = 7;
$expected_billing_amount = 5.0;
$max_time_drift = 180;

$build_order = static function (int $user_id, object $level): MemberOrder {
    $order = new MemberOrder();
    $order->user_id = $user_id;
    $order->membership_id = (int) $level->id;
    $order->membership_level = clone $level;
    $order->subtotal = (float) $level->initial_payment;
    $order->tax = 0;
    $order->total = (float) $level->initial_payment;

    return $order;
};

$assert(function_exists('espaciosutil_pmpro_get_trial_config'), 'No existe la función espaciosutil_pmpro_get_trial_config().');
$assert(function_exists('espaciosutil_pmpro_user_is_eligible_for_trial'), 'No existe la función espaciosutil_pmpro_user_is_eligible_for_trial().');
$assert(function_exists('pmpro_getLevel'), 'No existe la función pmpro_getLevel().');

$trial_level = function_exists('pmpro_getLevel') ? pmpro_getLevel($trial_level_id) : null;
$control_level = function_exists('pmpro_getLevel') ? pmpro_getLevel($control_level_id) : null;

$assert(is_object($trial_level), 'No se ha encontrado el nivel mensual de prueba (ID 11).');
$assert(is_object($control_level), 'No se ha encontrado el nivel de control (ID 12).');

if (is_object($trial_level)) {
    $config = espaciosutil_pmpro_get_trial_config($trial_level);
    $assert(is_array($config), 'El nivel mensual no tiene configuración de trial.');
    $assert((int) ($config['delay_days'] ?? 0) === $expected_delay_days, 'La duración del trial no es de 7 días.');
}

$timestamp = time();
$eligible_user_id = wp_create_user('trial-eligible-' . $timestamp, wp_generate_password(20, true), 'trial-eligible-' . $timestamp . '@example.com');
$ineligible_user_id = wp_create_user('trial-used-' . $timestamp, wp_generate_password(20, true), 'trial-used-' . $timestamp . '@example.com');

if (!is_wp_error($eligible_user_id)) {
    $created_user_ids[] = (int) $eligible_user_id;
}
if (!is_wp_error($ineligible_user_id)) {
    $created_user_ids[] = (int) $ineligible_user_id;
}

$assert(!is_wp_error($eligible_user_id), 'No se ha podido crear el usuario de prueba elegible.');
$assert(!is_wp_error($ineligible_user_id), 'No se ha podido crear el usuario de prueba no elegible.');

if (!is_wp_error($ineligible_user_id)) {
    espaciosutil_pmpro_mark_trial_used((int) $ineligible_user_id);
}

if (is_object($trial_level) && !is_wp_error($eligible_user_id)) {
    $assert(
        espaciosutil_pmpro_user_is_eligible_for_trial((int) $eligible_user_id, $trial_level),
        'El usuario nuevo debería ser elegible para el trial.'
    );

    wp_set_current_user((int) $eligible_user_id);
    $assert(
        espaciosutil_pmpro_should_show_trial_for_level($trial_level),
        'El trial debería mostrarse para un usuario elegible.'
    );

    $eligible_order = apply_filters('pmpro_checkout_order', $build_order((int) $eligible_user_id, $trial_level));
    $assert((float) $eligible_order->total === 0.0, 'El pedido del usuario elegible no queda a total 0.');
    $assert((float) $eligible_order->subtotal === 0.0, 'El subtotal del usuario elegible no queda a 0.');
    $assert((float) $eligible_order->membership_level->initial_payment === 0.0, 'El pago inicial del usuario elegible no queda a 0.');
    $assert((float) $eligible_order->membership_level->billing_amount === $expected_billing_amount, 'El billing_amount del usuario elegible no coincide con 5€.');
    $assert(!empty($eligible_order->membership_level->profile_start_date), 'No se ha generado profile_start_date para el usuario elegible.');

    if (!empty($eligible_order->membership_level->profile_start_date)) {
        $expected_timestamp = strtotime('+' . $expected_delay_days . ' days', current_time('timestamp'));
        $actual_timestamp = strtotime((string) $eligible_order->membership_level->profile_start_date);
        $drift = abs($actual_timestamp - $expected_timestamp);
        $assert($drift <= $max_time_drift, 'profile_start_date no está retrasada aproximadamente 7 días para el usuario elegible.');
    }

    $eligible_cost_text = pmpro_getLevelCost($trial_level, false);
    $assert(
        strpos($eligible_cost_text, 'Incluye 7 dias de prueba gratuita.') !== false,
        'El usuario elegible no ve el mensaje del trial en el texto de coste.'
    );
}

if (is_object($trial_level) && !is_wp_error($ineligible_user_id)) {
    $assert(
        !espaciosutil_pmpro_user_is_eligible_for_trial((int) $ineligible_user_id, $trial_level),
        'El usuario que ya usó el trial sigue marcado como elegible.'
    );

    wp_set_current_user((int) $ineligible_user_id);
    $assert(
        !espaciosutil_pmpro_should_show_trial_for_level($trial_level),
        'El trial no debería mostrarse para un usuario que ya lo consumió.'
    );

    $ineligible_order = apply_filters('pmpro_checkout_order', $build_order((int) $ineligible_user_id, $trial_level));
    $assert(
        (float) $ineligible_order->total === (float) $trial_level->initial_payment,
        'El pedido del usuario no elegible no debería quedar a total 0.'
    );
    $assert(
        empty($ineligible_order->membership_level->profile_start_date),
        'El usuario no elegible no debería recibir profile_start_date diferida.'
    );

    $ineligible_cost_text = pmpro_getLevelCost($trial_level, false);
    $assert(
        strpos($ineligible_cost_text, 'Incluye 7 dias de prueba gratuita.') === false,
        'El usuario no elegible sigue viendo el copy del trial.'
    );
}

if (is_object($control_level) && !is_wp_error($eligible_user_id)) {
    wp_set_current_user((int) $eligible_user_id);
    $control_order = apply_filters('pmpro_checkout_order', $build_order((int) $eligible_user_id, $control_level));
    $assert(
        (float) $control_order->total === (float) $control_level->initial_payment,
        'El nivel semestral ha sido modificado por error.'
    );
    $assert(
        empty($control_order->membership_level->profile_start_date),
        'El nivel semestral no debería tener profile_start_date diferida.'
    );
}

wp_set_current_user($original_user_id);

foreach ($created_user_ids as $created_user_id) {
    wp_delete_user($created_user_id);
}

if (!empty($errors)) {
    fwrite(STDERR, "PMPro trial verification failed:\n");
    foreach ($errors as $error) {
        fwrite(STDERR, '- ' . $error . "\n");
    }
    exit(1);
}

fwrite(STDOUT, "PMPro trial verification passed.\n");
fwrite(STDOUT, "Usuario nuevo: trial aplicado una sola vez.\n");
fwrite(STDOUT, "Usuario con trial consumido: sin trial en nuevas suscripciones.\n");
fwrite(STDOUT, "Nivel de control (ID 12): sin cambios.\n");

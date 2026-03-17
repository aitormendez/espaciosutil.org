<?php

if (!defined('ABSPATH')) {
    fwrite(STDERR, "Este script debe ejecutarse con wp eval-file.\n");
    exit(1);
}

if (!class_exists('PMPro_Discount_Code') || !function_exists('pmpro_getLevel')) {
    fwrite(STDERR, "Paid Memberships Pro no está disponible.\n");
    exit(1);
}

$args = [];
foreach (array_slice($_SERVER['argv'] ?? [], 1) as $arg) {
    if ($arg === '--' || strpos($arg, '=') === false) {
        continue;
    }

    [$key, $value] = explode('=', $arg, 2);
    $args[$key] = $value;
}

$code = strtoupper(trim((string) ($args['code'] ?? '')));
$starts = trim((string) ($args['starts'] ?? wp_date('Y-m-d')));
$expires = trim((string) ($args['expires'] ?? ''));
$uses = isset($args['uses']) ? max(0, (int) $args['uses']) : 0;
$one_use_per_user = !empty($args['one_use_per_user']);
$level_ids = array_values(array_filter(array_map('intval', explode(',', (string) ($args['levels'] ?? '11,12,13')))));
$initial_payment = isset($args['initial_payment']) ? (float) $args['initial_payment'] : 0.0;
$billing_amount = isset($args['billing_amount']) ? (float) $args['billing_amount'] : 0.0;
$billing_limit = isset($args['billing_limit']) ? max(0, (int) $args['billing_limit']) : 1;
$trial_amount = isset($args['trial_amount']) ? (float) $args['trial_amount'] : 0.0;
$trial_limit = isset($args['trial_limit']) ? max(0, (int) $args['trial_limit']) : 0;

if ($code === '') {
    fwrite(STDERR, "Debes indicar code=CODIGO.\n");
    exit(1);
}

if ($expires === '') {
    fwrite(STDERR, "Debes indicar expires=YYYY-MM-DD.\n");
    exit(1);
}

if (strtotime($starts) === false || strtotime($expires) === false) {
    fwrite(STDERR, "Las fechas starts/expires deben estar en formato YYYY-MM-DD.\n");
    exit(1);
}

if ($level_ids === []) {
    fwrite(STDERR, "Debes indicar al menos un nivel en levels=11,12,13.\n");
    exit(1);
}

$levels = [];
foreach ($level_ids as $level_id) {
    $level = pmpro_getLevel($level_id);
    if (!is_object($level)) {
        fwrite(STDERR, "No se ha encontrado el nivel {$level_id}.\n");
        exit(1);
    }

    $levels[$level_id] = [
        'initial_payment' => $initial_payment,
        'billing_amount' => $billing_amount,
        'cycle_number' => isset($level->cycle_number) ? (int) $level->cycle_number : 0,
        'cycle_period' => isset($level->cycle_period) ? (string) $level->cycle_period : 'Month',
        'billing_limit' => $billing_limit,
        'trial_amount' => $trial_amount,
        'trial_limit' => $trial_limit,
        'expiration_number' => isset($level->expiration_number) ? (int) $level->expiration_number : 0,
        'expiration_period' => isset($level->expiration_period) ? (string) $level->expiration_period : '',
    ];
}

$discount_code = new PMPro_Discount_Code($code);
if (!is_object($discount_code)) {
    $discount_code = new PMPro_Discount_Code();
}

$discount_code->code = $code;
$discount_code->starts = $starts;
$discount_code->expires = $expires;
$discount_code->uses = $uses;
$discount_code->levels = $levels;

$saved_discount_code = $discount_code->save();
if (!$saved_discount_code || empty($saved_discount_code->id)) {
    fwrite(STDERR, "No se ha podido guardar el código {$code}.\n");
    exit(1);
}

global $wpdb;
$wpdb->update(
    $wpdb->pmpro_discount_codes,
    ['one_use_per_user' => $one_use_per_user ? 1 : 0],
    ['id' => (int) $saved_discount_code->id],
    ['%d'],
    ['%d']
);

fwrite(STDOUT, "Código guardado: {$code}\n");
fwrite(STDOUT, "ID: " . (int) $saved_discount_code->id . "\n");
fwrite(STDOUT, "Vigencia: {$starts} -> {$expires}\n");
fwrite(STDOUT, "Usos máximos: {$uses}\n");
fwrite(STDOUT, "Un uso por usuario: " . ($one_use_per_user ? 'sí' : 'no') . "\n");
foreach ($levels as $level_id => $level_config) {
    fwrite(
        STDOUT,
        sprintf(
            "Nivel %d: initial_payment=%s billing_amount=%s billing_limit=%d\n",
            $level_id,
            (string) $level_config['initial_payment'],
            (string) $level_config['billing_amount'],
            (int) $level_config['billing_limit']
        )
    );
}

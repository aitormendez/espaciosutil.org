<?php

/**
 * Plugin Name: Espacio Sutil Transactional Email
 * Description: Normaliza el remitente de correos transaccionales desde variables de entorno.
 */

function espaciosutil_get_transactional_email_from(): string
{
    $from = getenv('TRANSACTIONAL_EMAIL_FROM');

    if (!is_string($from)) {
        return '';
    }

    $from = sanitize_email($from);

    return is_email($from) ? $from : '';
}

function espaciosutil_get_transactional_email_name(): string
{
    $name = getenv('TRANSACTIONAL_EMAIL_NAME');

    if (!is_string($name)) {
        return '';
    }

    return sanitize_text_field($name);
}

function espaciosutil_filter_wp_mail_from(string $from): string
{
    return espaciosutil_get_transactional_email_from() ?: $from;
}
add_filter('wp_mail_from', 'espaciosutil_filter_wp_mail_from');

function espaciosutil_filter_wp_mail_from_name(string $name): string
{
    return espaciosutil_get_transactional_email_name() ?: $name;
}
add_filter('wp_mail_from_name', 'espaciosutil_filter_wp_mail_from_name');

function espaciosutil_filter_pmpro_from_email($value): string
{
    $from = espaciosutil_get_transactional_email_from();

    if ($from !== '') {
        return $from;
    }

    return is_string($value) ? $value : '';
}
add_filter('pre_option_pmpro_from_email', 'espaciosutil_filter_pmpro_from_email');

function espaciosutil_filter_pmpro_from_name($value): string
{
    $name = espaciosutil_get_transactional_email_name();

    if ($name !== '') {
        return $name;
    }

    return is_string($value) ? $value : '';
}
add_filter('pre_option_pmpro_from_name', 'espaciosutil_filter_pmpro_from_name');

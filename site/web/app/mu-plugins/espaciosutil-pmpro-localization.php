<?php

/**
 * Plugin Name: Espacio Sutil — PMPro es_ES
 * Description: Catálogo reproducible y acotado a Paid Memberships Pro 3.8.6.
 * License: GPL-2.0-only
 */

if (!defined('ABSPATH')) {
    exit;
}

/**
 * WordPress >= 6.6 supplies the effective locale and the final MO/PHP candidate.
 * Redirect both formats, including JIT/reloads, without changing locale or data.
 */
function espaciosutil_pmpro_translation_file(string $file, string $domain, string $locale): string
{
    if ($domain !== 'paid-memberships-pro' || $locale !== 'es_ES'
        || !defined('PMPRO_VERSION') || PMPRO_VERSION !== '3.8.6') {
        return $file;
    }

    $catalog = __DIR__ . '/espaciosutil-pmpro-l10n/paid-memberships-pro-es_ES.mo';

    return is_readable($catalog) ? $catalog : $file;
}
add_filter('load_translation_file', 'espaciosutil_pmpro_translation_file', 10, 3);

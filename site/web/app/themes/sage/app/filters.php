<?php

/**
 * Theme filters.
 */

namespace App;

/**
 * Add "… Continued" to the excerpt.
 *
 * @return string
 */
add_filter('excerpt_more', function () {
    return sprintf(' &hellip; <a href="%s">%s</a>', get_permalink(), __('Continued', 'sage'));
});

// add_action('init', function () {
//     // 1) ACCOUNT ─────────────
//     remove_shortcode('pmpro_account');

//     add_shortcode('pmpro_account', function ($atts, $content = null, $tag = '') {
//         // Opcional: pasa datos a la vista
//         return \Roots\view('pmpro.account', [
//             'atts' => $atts,
//         ])->render();
//     });

//     // 2) PROFILE ─────────────
//     // Repite el patrón si necesitas más shortcodes
//     remove_shortcode('pmpro_member_profile_edit');
//     add_shortcode('pmpro_member_profile_edit', function ($atts) {
//         return \Roots\view('pmpro.member_profile_edit')->render();
//     });

//     // 3) Billing, Cancel, etc. …
// });



// add_filter('pmpro_pages_custom_template_path', function ($templates, $page_name) {
//     $override = get_theme_file_path("paid-memberships-pro/pages/{$page_name}.php");

//     if (file_exists($override)) {
//         // Borra duplicados, por si ya estaba
//         $templates = array_values(array_unique($templates));

//         // Quita la ruta del override de donde esté…
//         $templates = array_filter($templates, fn($t) => $t !== $override);

//         // …y la coloca al principio
//         array_unshift($templates, $override);

//         error_log("🪵 Override activo para {$page_name}: {$override}");
//     }

//     return $templates;     // ← devuelvo ARRAY, no string
// }, 1, 2);

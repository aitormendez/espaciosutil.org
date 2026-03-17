<?php

if (!defined('ABSPATH')) {
    fwrite(STDERR, "Este script debe ejecutarse con wp eval-file.\n");
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

$keep_count = max(1, (int) ($args['keep_count'] ?? 12));
$dry_run = !empty($args['dry_run']);
$keep_ids = array_values(array_filter(array_map('intval', explode(',', (string) ($args['keep_ids'] ?? '')))));

$media_posts = get_posts([
    'post_type' => 'cde',
    'post_status' => ['publish', 'draft', 'future', 'pending', 'private'],
    'posts_per_page' => -1,
    'orderby' => [
        'menu_order' => 'ASC',
        'date' => 'ASC',
    ],
    'order' => 'ASC',
]);

$eligible_ids = [];
foreach ($media_posts as $post) {
    $has_video = (bool) get_field('featured_video_id', $post->ID);
    $has_audio = (bool) get_field('featured_audio_id', $post->ID);
    if (!$has_video && !$has_audio) {
        continue;
    }

    $eligible_ids[] = (int) $post->ID;
}

if ($keep_ids === []) {
    $keep_ids = array_slice($eligible_ids, 0, $keep_count);
}

if ($keep_ids === []) {
    fwrite(STDERR, "No se han encontrado lecciones con media para conservar.\n");
    exit(1);
}

$keep_lookup = array_fill_keys($keep_ids, true);
$publish_ids = $keep_ids;
foreach ($keep_ids as $keep_id) {
    $ancestor_ids = array_map('intval', array_reverse(get_post_ancestors($keep_id)));
    foreach ($ancestor_ids as $ancestor_id) {
        if (!isset($keep_lookup[$ancestor_id])) {
            $keep_lookup[$ancestor_id] = true;
            $publish_ids[] = $ancestor_id;
        }
    }
}

$publish_ids = array_values(array_unique(array_map('intval', $publish_ids)));
$all_cde_ids = get_posts([
    'post_type' => 'cde',
    'post_status' => ['publish', 'draft', 'future', 'pending', 'private'],
    'posts_per_page' => -1,
    'fields' => 'ids',
]);

$to_publish = [];
$to_draft = [];
$restriction_rows_by_post = [];
foreach ($all_cde_ids as $post_id) {
    $post_id = (int) $post_id;
    $current_status = get_post_status($post_id);
    if (in_array($post_id, $publish_ids, true)) {
        if ($current_status !== 'publish') {
            $to_publish[] = $post_id;
        }
        continue;
    }

    if ($current_status === 'publish') {
        $to_draft[] = $post_id;
    }
}

global $wpdb;
$restriction_updates = [];
if (function_exists('pmpro_update_post_level_restrictions')) {
    foreach ($publish_ids as $post_id) {
        $restriction_rows = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->pmpro_memberships_pages} WHERE page_id = %d",
                $post_id
            )
        );

        $restriction_rows_by_post[$post_id] = $restriction_rows;
        if ($restriction_rows > 0) {
            $restriction_updates[] = $post_id;
        }
    }
}

if (!$dry_run) {
    foreach ($to_publish as $post_id) {
        wp_update_post([
            'ID' => $post_id,
            'post_status' => 'publish',
        ]);
    }

    foreach ($to_draft as $post_id) {
        wp_update_post([
            'ID' => $post_id,
            'post_status' => 'draft',
        ]);
    }

    foreach ($restriction_updates as $post_id) {
        pmpro_update_post_level_restrictions($post_id, []);
    }
}

fwrite(STDOUT, "Lecciones con media conservadas: " . count($keep_ids) . "\n");
foreach ($keep_ids as $index => $post_id) {
    fwrite(STDOUT, sprintf("%d. %d\t%s\n", $index + 1, $post_id, get_the_title($post_id)));
}

fwrite(STDOUT, "Entradas publicadas en total tras incluir ancestros: " . count($publish_ids) . "\n");
fwrite(STDOUT, "Pasan a publish: " . count($to_publish) . "\n");
fwrite(STDOUT, "Pasan a draft: " . count($to_draft) . "\n");
fwrite(STDOUT, "Restricciones PMPro a limpiar en visibles: " . count($restriction_updates) . "\n");
fwrite(STDOUT, $dry_run ? "Modo simulación: sin cambios.\n" : "Cambios aplicados.\n");

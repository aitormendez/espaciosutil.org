<?php
/** Read-only: wp eval-file scripts/practice-audio-plan.php POST_ID --user=EDITOR > plan.json */
use EspacioSutil\Practicas\{Audio, Catalog, Guide};
if (!defined('WP_CLI') || !WP_CLI || !Catalog::editor()) { throw new RuntimeException('Se requiere WP-CLI y permiso editorial.'); }
$post = get_post((int) ($args[0] ?? 0));
if (!$post || $post->post_type !== 'practica') { throw new RuntimeException('Práctica no encontrada.'); }
$guide = Guide::fromHtml(wpautop($post->post_content));
if (!$guide['screens']) { throw new RuntimeException('La práctica requiere una adaptación editorial antes de narrar.'); }
echo wp_json_encode(['schema'=>'cde.practice.audio-plan', 'version'=>1, 'post_id'=>$post->ID, 'revision'=>Audio::revision($guide), 'screens'=>$guide['screens']], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n";

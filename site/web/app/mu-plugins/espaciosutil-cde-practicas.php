<?php
/** Plugin Name: Espacio Sutil — Prácticas CDE */

namespace EspacioSutil\Practicas;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__.'/cde-practicas/catalog.php';
require_once __DIR__.'/cde-practicas/guide.php';
require_once __DIR__.'/cde-practicas/audio.php';
add_action('template_redirect', static function () {
    if (($_GET['cde_practica_audio'] ?? null) === '1') { Audio::serve(); }
}, -1);
Catalog::boot();
if (defined('WP_CLI') && WP_CLI) {
    require_once __DIR__.'/cde-practicas/import.php';
    \WP_CLI::add_command('cde-practicas', ImportCommand::class);
}

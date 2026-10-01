<?php
/** Plugin Name: Espacio Sutil — Prácticas CDE */

namespace EspacioSutil\Practicas;

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__.'/cde-practicas/catalog.php';
Catalog::boot();
if (defined('WP_CLI') && WP_CLI) {
    require_once __DIR__.'/cde-practicas/import.php';
    \WP_CLI::add_command('cde-practicas', ImportCommand::class);
}

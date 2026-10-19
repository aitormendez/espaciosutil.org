<?php

/**
 * Plugin Name: Espacio Sutil — Presentaciones CDE
 * Description: Paquetes de diapositivas privados con acceso heredado de la lección.
 */
declare(strict_types=1);

// Activación de producción autorizada junto a Prácticas; conserva el acceso de la lección.
if (! defined('WP_ENV') || ! in_array(WP_ENV, ['staging', 'development', 'production'], true)) {
    return;
}
require_once __DIR__.'/cde-presentations/package.php';
require_once __DIR__.'/cde-presentations/service.php';
require_once __DIR__.'/cde-presentations/web.php';
require_once __DIR__.'/cde-presentations/editor.php';
if (defined('WP_CLI') && WP_CLI) {
    require_once __DIR__.'/cde-presentations/cli.php';
}

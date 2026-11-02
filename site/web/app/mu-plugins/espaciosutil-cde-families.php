<?php

/**
 * Plugin Name: Espacio Sutil — Familias CDE
 * Description: Catálogo explícito y familia heredada de la serie, sin migraciones automáticas.
 */
declare(strict_types=1);

require_once __DIR__ . '/cde-families/families.php';
\EspacioSutil\Cde\Families::boot();

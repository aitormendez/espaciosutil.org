<?php

/**
 * Plugin Name: Espacio Sutil — Familias CDE
 * Description: Familias de series del CDE: catálogo editable en Opciones (ACF), asignación a la serie raíz y herencia sin migraciones automáticas.
 */
declare(strict_types=1);

require_once __DIR__ . '/cde-families/families.php';
\EspacioSutil\Cde\Families::boot();

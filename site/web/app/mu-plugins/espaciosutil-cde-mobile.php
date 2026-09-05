<?php
/**
 * Plugin Name: Espacio Sutil — CDE móvil
 * Description: Dominio compartido y API de la aplicación CDE.
 */
declare(strict_types=1);
require_once __DIR__ . '/cde-mobile/access.php';

if ((defined('WP_ENV') && WP_ENV === 'staging') || getenv('CDE_MOBILE_ENABLED') === '1') {
    foreach (['storage','progress','media','api','legacy'] as $module) require_once __DIR__ . '/cde-mobile/' . $module . '.php';
}

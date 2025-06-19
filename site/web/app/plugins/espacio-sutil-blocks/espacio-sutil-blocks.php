<?php

/**
 * Plugin Name:       Espacio Sutil Blocks
 * Description:       Bloques personalizados de Espacio Sutil, incluyendo el bloque de video.
 * Version:           1.0.0
 * Requires at least: 6.7
 * Requires PHP:      7.4
 * Author:            Aitor Méndez
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       espacio-sutil-blocks
 *
 * @package FbBlocks
 */

if (! defined('ABSPATH')) {
	exit; // Exit if accessed directly.
}

require_once plugin_dir_path(__FILE__) . 'includes/api/video-resolutions.php';

/**
 * Registrar bloques desde build/blocks-manifest.php.
 */
function es_blocks_register_all_blocks()
{
	$manifest_file = __DIR__ . '/build/blocks-manifest.php';

	if (! file_exists($manifest_file)) {
		error_log('⚠️ Error: blocks-manifest.php no encontrado en ' . $manifest_file);
		return;
	}

	// Carga la información del manifest
	$manifest_data = require $manifest_file;

	// Registra automáticamente todos los bloques *excepto* "post"
	foreach (array_keys($manifest_data) as $block_type) {
		register_block_type(__DIR__ . "/build/{$block_type}");
	}
}
add_action('init', 'es_blocks_register_all_blocks');

<?php
/** Desde site: wp eval-file tests/cde-families-acf-render.php. Sin guardar opciones ni términos. */
use EspacioSutil\Cde\Families;
use EspacioSutil\Cde\FamilyCatalog;

if (wp_get_environment_type() !== 'development' || !function_exists('acf_render_fields')) {
    WP_CLI::error('Se necesita el entorno de desarrollo con ACF Pro.');
}
// Simulate the raw option values that ACF would read, ONLY for this PHP process.
add_filter('pre_option', static function ($previous, $key) {
    $rows = [
        ['codigo' => '09', 'nombre' => 'Familia de prueba B', 'icono' => '', 'color_fondo' => '#FFF123', 'color_texto' => 'black'],
        ['codigo' => '01', 'nombre' => 'Familia de prueba A', 'icono' => '', 'color_fondo' => '', 'color_texto' => ''],
    ];
    $prefix = 'options_' . FamilyCatalog::OPTION;
    if ($key === '_' . $prefix) { return FamilyCatalog::FIELD; }
    if ($key === $prefix) { return count($rows); }
    foreach ($rows as $index => $row) {
        foreach ($row as $name => $value) {
            if ($key === $prefix . '_' . $index . '_' . $name) { return $value; }
        }
    }
    return $previous;
}, 10, 2);
$field = acf_get_field(FamilyCatalog::FIELD);
if (!$field || array_column(Families::catalog(), 'code') !== ['09','01']) {
    WP_CLI::error('ACF no ha cargado las dos filas en el orden guardado, con ceros iniciales.');
}
$catalogue = Families::catalog();
if (array_column($catalogue, 'background_color') !== ['#fff123', null] || array_column($catalogue, 'text_color') !== ['black', 'white']) {
    WP_CLI::error('Los colores de las filas ACF no se resuelven con sus valores normalizados y por defecto.');
}
ob_start();
acf_render_fields([$field], 'options');
$html = ob_get_clean();
foreach (['Añadir familia', 'Familia de prueba A', 'Familia de prueba B', 'field_cde_familia_codigo', 'field_cde_familia_icono', 'field_cde_familia_color_fondo', 'field_cde_familia_color_texto', '#FFF123', 'Blanco', 'Negro'] as $text) {
    if (!str_contains($html, $text)) { WP_CLI::error('Falta en el formulario ACF real: ' . $text); }
}
WP_CLI::success('Repetidor ACF renderizado: dos familias editables en orden, icono y botón Añadir familia; sin escrituras.');

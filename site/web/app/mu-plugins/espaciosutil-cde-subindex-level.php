<?php
/**
 * Plugin Name: Espacio Sutil · Nivel del subíndice CDE
 * Description: Conserva las etiquetas del editor y unifica el valor de nivel que leen WordPress y el publicador.
 */

defined('ABSPATH') || exit;

add_filter('acf/load_field/key=field_cde_fields_lesson_subindex_items_level', static function (array $field): array {
    // ACF Builder trata las claves enteras de una lista plana como posiciones.
    // Definir aquí los valores efectivos también cubre los grupos ACF cacheados.
    $field['choices'] = [1 => 'Nivel 1', 2 => 'Nivel 2', 3 => 'Nivel 3', 4 => 'Nivel 4'];

    return $field;
});

function espaciosutil_cde_canonical_subindex_level($value)
{
    if (is_string($value) && preg_match('/^Nivel ([1-4])$/D', $value, $matches)) {
        return $matches[1];
    }

    return $value;
}

add_filter('acf/load_value/key=field_cde_fields_lesson_subindex_items_level', 'espaciosutil_cde_canonical_subindex_level');
add_filter('acf/update_value/key=field_cde_fields_lesson_subindex_items_level', 'espaciosutil_cde_canonical_subindex_level');

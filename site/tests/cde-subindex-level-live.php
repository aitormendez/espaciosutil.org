<?php
// wp eval-file site/tests/cde-subindex-level-live.php en un entorno de pruebas.
if (!in_array(wp_get_environment_type(), ['local', 'development', 'staging'], true)) {
    throw new RuntimeException('Esta prueba crea un borrador temporal y requiere un entorno de pruebas.');
}

$key = 'field_cde_fields_lesson_subindex_items_level';
$repeater = 'field_cde_fields_lesson_subindex_items';
$field = acf_get_field($key);
$postId = wp_insert_post(['post_type' => 'cde', 'post_status' => 'draft', 'post_title' => 'Prueba temporal del nivel del subíndice'], true);
if (is_wp_error($postId) || !$postId) {
    throw new RuntimeException('No se pudo crear el borrador temporal.');
}

try {
    foreach ([1, 2, 3, 4] as $level) {
        $value = (string) $level;
        $label = "Nivel $level";
        $meta = 'lesson_subindex_items_0_level';
        update_field($repeater, [['level' => $level, 'title' => 'Apartado']], $postId);
        if (get_post_meta($postId, $meta, true) !== $value) {
            throw new RuntimeException("Guardado del publicador incorrecto en $label.");
        }

        foreach ([$value, $label] as $historical) {
            update_post_meta($postId, $meta, $historical);
            acf_get_store('values')->reset();
            $rows = get_field('lesson_subindex_items', $postId);
            $field['value'] = $rows[0]['level'];
            ob_start();
            acf_render_field($field);
            $html = ob_get_clean();
            $document = new DOMDocument();
            $document->loadHTML($html);
            $selected = (new DOMXPath($document))->query('//option[@selected]');
            if ($selected->length !== 1 || $selected[0]->getAttribute('value') !== $value || $selected[0]->textContent !== $label) {
                throw new RuntimeException("Selección incorrecta al abrir el valor $historical.");
            }

            acf_update_values([$repeater => ['row-0' => [$key => $selected[0]->getAttribute('value')]]], $postId);
            if (get_post_meta($postId, $meta, true) !== $value) {
                throw new RuntimeException("Guardado del editor incorrecto en $label.");
            }
        }
    }
    echo "Correctos los cuatro niveles en los dos formatos históricos.\n";
} finally {
    wp_delete_post($postId, true);
}

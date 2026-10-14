<?php

/** Comprobación de solo lectura: wp eval-file tests/cde-presentation-fields-live.php */
$check = static function (bool $ok, string $message): void {
    if (! $ok) {
        throw new RuntimeException($message);
    }
};
$check(! acf_get_field_group('group_cde_presentation'), 'Persiste el grupo separado de presentación.');
$fields = acf_get_fields('group_cde_fields');
$tabs = array_column(array_filter($fields, static fn ($field) => $field['type'] === 'tab'), 'label');
$check(in_array('Presentación', $tabs, true), 'Falta la pestaña Presentación.');
foreach (['lesson_presentation_enabled', 'presentation_help'] as $name) {
    $field = acf_get_field('field_cde_presentation_'.$name);
    $check($field && $field['parent'] === 'group_cde_fields', 'La referencia original no pertenece a Lección CDE: '.$name);
}
$rest = apply_filters('acf/rest/get_fields', $fields);
$check(! in_array('lesson_presentation_enabled', array_column($rest, 'name'), true), 'La integración expone la activación en REST.');
$check(in_array('clave_leccion', array_column($rest, 'name'), true), 'La exclusión altera los demás campos REST.');
echo "OK: pestaña integrada, referencias compatibles y exclusión REST conservada.\n";

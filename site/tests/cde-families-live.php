<?php

/** Read-only host test: cd site && wp eval-file tests/cde-families-live.php */

use EspacioSutil\Cde\Families;

if (! defined('WP_ENV') || WP_ENV !== 'development') {
    WP_CLI::error('Esta prueba sólo se ejecuta en development.');
}
$check = static function (bool $ok, string $message): void {
    if (! $ok) {
        throw new RuntimeException($message);
    }
    WP_CLI::line('OK ' . $message);
};
$before = Families::catalog();
$check(! taxonomy_exists('familia_serie') && ! method_exists(Families::class, 'seedCatalog'), 'sin taxonomía ni API de siembra');
$field = acf_get_field('field_cde_familias_series');
$check($field && $field['type'] === 'repeater' && $field['name'] === 'cde_familias_series', 'repetidor ACF registrado');
$fields = array_column(acf_get_fields($field), 'type', 'name');
$check(($fields['codigo'] ?? null) === 'text' && ($fields['nombre'] ?? null) === 'text' && ($fields['icono'] ?? null) === 'image', 'campos editables código, nombre e imagen');
$textField = acf_get_field('field_cde_familia_color_texto');
$check(($fields['color_fondo'] ?? null) === 'color_picker' && ($fields['color_texto'] ?? null) === 'radio' && $textField['default_value'] === 'white' && array_keys($textField['choices']) === ['white', 'black'], 'selector de fondo y texto blanco o negro');
$checkColors = static function (array $row) use ($check): void {
    $check(array_key_exists('background_color', $row) && ($row['background_color'] === null || preg_match('/^#[0-9a-f]{6}$/D', $row['background_color']) === 1) && in_array($row['text_color'] ?? null, ['white', 'black'], true), 'colores canónicos o por defecto');
};
$originalUser = get_current_user_id();
try {
    wp_set_current_user(0);
    $response = rest_do_request(new WP_REST_Request('GET', '/cde/v1/familias'));
    $body = $response->get_data();
    $check($response->get_status() === 200 && $body['version'] === 2 && $body['items'] === $before, 'REST devuelve catálogo editable v2, incluso si está vacío');
    foreach ($body['items'] as $row) {
        $checkColors($row);
        $check(is_string($row['code']) && preg_match('/^(?!00)[0-9]{2}$/D', $row['code']) === 1 && $row['slug'] === $row['code'] && trim($row['label']) !== '' && is_int($row['icon_id']), 'forma pública con código estable y adjunto opcional');
    }
    $response = rest_do_request(new WP_REST_Request('GET', '/cde/v1/familias/inventario'));
    $check($response->get_status() === 401, 'inventario privado para anónimos');
    $users = get_users(['role' => 'administrator', 'number' => 1, 'fields' => 'ID']);
    $check(count($users) === 1, 'administrador local disponible para pruebas sólo lectura');
    wp_set_current_user((int) $users[0]);
    foreach (['series', 'lessons'] as $kind) {
        $request = new WP_REST_Request('GET', '/cde/v1/familias/inventario');
        $request->set_query_params(['kind' => $kind, 'page' => 1, 'per_page' => 2]);
        $response = rest_do_request($request);
        $body = $response->get_data();
        $check($response->get_status() === 200 && count($body['items']) <= 2 && $body['total'] >= count($body['items']), 'inventario ' . $kind . ' paginado con errores por fila');
        $check(($response->get_headers()['Cache-Control'] ?? '') === 'private, no-store', 'inventario ' . $kind . ' no cacheable');
        foreach ($body['items'] as $row) {
            if (!empty($row['family']['ok'])) { $checkColors($row['family']); }
            $check(isset($row['family']['ok']) && ($row['family']['ok'] || isset($row['family']['error']['code'])), 'estado familiar explícito ' . $kind);
        }
    }
    $request = new WP_REST_Request('GET', '/cde/v1/familias/inventario');
    $request->set_query_params(['per_page' => 101]);
    $check(rest_do_request($request)->get_status() === 400, 'límite de inventario validado');
    ob_start();
    do_action('serie_cde_add_form_fields', 'serie_cde');
    $markup = ob_get_clean();
    $check(str_contains($markup, 'cde_family_nonce') && str_contains($markup, '<select'), 'selector administrativo registrado con nonce');
    $roots = get_terms(['taxonomy' => 'serie_cde', 'hide_empty' => false, 'parent' => 0, 'number' => 1]);
    if ($roots) {
        ob_start();
        do_action('serie_cde_edit_form_fields', $roots[0], 'serie_cde');
        $markup = ob_get_clean();
        $check(str_contains($markup, 'cde_family_slug'), 'raíz editable desde administración WP');
        $children = get_terms(['taxonomy' => 'serie_cde', 'hide_empty' => false, 'parent' => $roots[0]->term_id, 'number' => 1]);
        if ($children) {
            ob_start();
            do_action('serie_cde_edit_form_fields', $children[0], 'serie_cde');
            $markup = ob_get_clean();
            $check(! str_contains($markup, '<select') && str_contains($markup, 'no editable'), 'bloque readonly en administración WP');
        }
    }
    $terms = get_terms(['taxonomy' => 'serie_cde', 'hide_empty' => false, 'number' => 1]);
    if ($terms) {
        $response = rest_do_request(new WP_REST_Request('GET', '/wp/v2/serie_cde/' . $terms[0]->term_id));
        $check($response->get_status() === 200 && isset($response->get_data()['familia_resuelta']['ok']), 'REST serie expone resolución derivada');
    }
    $lessons = get_posts(['post_type' => 'cde', 'post_status' => 'publish', 'numberposts' => 1]);
    if ($lessons) {
        $response = rest_do_request(new WP_REST_Request('GET', '/wp/v2/cde/' . $lessons[0]->ID));
        $check($response->get_status() === 200 && isset($response->get_data()['familia_resuelta']['ok']), 'REST lección expone resolución derivada');
    }
    $check(Families::catalog() === $before && ! taxonomy_exists('familia_serie'), 'catálogo inalterado después de lecturas');
} finally {
    wp_set_current_user($originalUser);
}
WP_CLI::success('Contrato familias verificado en WordPress local, sin escrituras de contenido.');

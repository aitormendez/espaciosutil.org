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
$before = (int) wp_count_terms(['taxonomy' => Families::TAXONOMY, 'hide_empty' => false]);
$originalUser = get_current_user_id();
try {
    wp_set_current_user(0);
    $response = rest_do_request(new WP_REST_Request('GET', '/cde/v1/familias'));
    $check($response->get_status() === 200 && count($response->get_data()['items']) === 4, 'catálogo público exacto sin escrituras implícitas');
    $taxonomy = get_taxonomy(Families::TAXONOMY);
    $check($taxonomy && $taxonomy->object_type === [] && ! $taxonomy->show_in_rest, 'taxonomía sin asignación manual por lección');
    $response = rest_do_request(new WP_REST_Request('GET', '/cde/v1/familias/inventario'));
    $check($response->get_status() === 401, 'inventario privado para anónimos');
    $request = new WP_REST_Request('PUT', '/cde/v1/familias/series/1');
    $request->set_body_params(['familia_serie' => 'espirituales-esenciales']);
    $check(rest_do_request($request)->get_status() === 401, 'asignación estructural requiere autorización');
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
            $check(isset($row['family']['ok']) && ($row['family']['ok'] || isset($row['family']['error']['code'])), 'estado familiar explícito ' . $kind);
        }
    }
    $request = new WP_REST_Request('GET', '/cde/v1/familias/inventario');
    $request->set_query_params(['per_page' => 101]);
    $check(rest_do_request($request)->get_status() === 400, 'límite de inventario validado');
    ob_start();
    do_action('serie_cde_add_form_fields', 'serie_cde');
    $markup = ob_get_clean();
    $check(str_contains($markup, 'cde_family_nonce') && str_contains($markup, 'Información complementaria'), 'selector administrativo registrado con nonce');
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
    $check((int) wp_count_terms(['taxonomy' => Families::TAXONOMY, 'hide_empty' => false]) === $before, 'lecturas no crean términos ni clasificaciones');
} finally {
    wp_set_current_user($originalUser);
}
WP_CLI::success('Contrato familias verificado en WordPress local, sin escrituras de contenido.');

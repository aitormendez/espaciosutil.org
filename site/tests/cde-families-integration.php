<?php

/** Local-only integration test with explicit opt-in and owned temporary fixtures. */
declare(strict_types=1);

use EspacioSutil\Cde\Families;

if (! defined('WP_ENV') || WP_ENV !== 'development' || getenv('CDE_FAMILIES_TEST_WRITE') !== '1') {
    WP_CLI::error('Sólo development, con CDE_FAMILIES_TEST_WRITE=1. Crea y elimina exclusivamente fixtures propios.');
}

$token = 'cde-family-test-' . wp_generate_uuid4();
$terms = [];
$posts = [];
$checks = 0;
$assert = static function (bool $ok, string $message) use (&$checks): void {
    if (! $ok) {
        throw new RuntimeException($message);
    }
    $checks++;
    WP_CLI::line('OK ' . $message);
};
$term = static function (string $suffix, int $parent = 0) use ($token, &$terms): int {
    $name = $token . '-' . $suffix;
    $result = wp_insert_term($name, 'serie_cde', ['slug' => $name, 'parent' => $parent]);
    if (is_wp_error($result)) {
        throw new RuntimeException($result->get_error_message());
    }
    $id = (int) $result['term_id'];
    $terms[$id] = $name;
    return $id;
};
$post = static function (string $suffix, int $parent = 0) use ($token, &$posts): int {
    $title = $token . '-' . $suffix;
    $id = wp_insert_post(['post_type' => 'cde', 'post_status' => 'draft', 'post_title' => $title, 'post_parent' => $parent], true);
    if (is_wp_error($id)) {
        throw new RuntimeException($id->get_error_message());
    }
    $posts[(int) $id] = $title;
    return (int) $id;
};
$errorIs = static fn($result, string $code): bool => is_wp_error($result) && $result->get_error_code() === 'cde_family_' . $code;
$failure = null;
$cleanupErrors = [];
try {
    $root = $term('series-a');
    $otherRoot = $term('series-b');
    $block = $term('block-a', $root);
    $otherBlock = $term('block-b', $otherRoot);
    $assert(Families::assignSeries($root, 'espirituales-esenciales') === true, 'asignación real a raíz de prueba');
    $assert(Families::assignSeries($root, 'espirituales-esenciales') === true, 'asignación repetida sin duplicar metadatos');
    $assert(count(get_term_meta($root, 'familia_serie', false)) === 1, 'un único metadato canónico');
    $assert(Families::assignSeries($otherRoot, 'otras-principales') === true, 'segunda raíz de prueba independiente');
    $assert($errorIs(Families::assignSeries($block, 'otras-principales'), 'root_required'), 'no se escribe familia en bloque');
    $assert(get_term_meta($block, 'familia_serie', false) === [], 'bloque conserva ausencia de asignación propia');

    $index = $post('index');
    $assigned = wp_set_object_terms($index, [$block], 'serie_cde');
    $assert(! is_wp_error($assigned), 'índice temporal vinculado a bloque real');
    $assert(! is_wp_error(Families::validatePublication(0, ['post_parent' => $index], 'espirituales-esenciales')), 'preflight de alta válido antes de crear lección');
    $lesson = $post('lesson', $index);
    $nested = $post('nested', $lesson);
    $resolved = Families::resolveLesson($nested);
    $assert(! is_wp_error($resolved) && $resolved['series_id'] === $root && $resolved['source_post_id'] === $index, 'lección anidada hereda raíz e índice reales');
    $assert(wp_get_object_terms($nested, 'familia_serie', ['fields' => 'ids']) === [], 'no hay copia taxonómica familiar en lección');
    $assert(get_post_meta($nested, 'familia_serie', true) === '', 'no hay copia de metadato familiar en lección');
    $assert($errorIs(Families::validatePublication($nested, [], 'otras-principales'), 'mismatch'), 'preflight rechaza familia NocoDB discordante');
    $assert($errorIs(Families::assignSeries($root, 'otras-espirituales'), 'reclassification_required'), 'setter conserva familia sin permiso de reclasificación');
    $assert(Families::assignSeries($root, 'otras-espirituales', true) === true, 'reclasificación explícita de fixture');
    $assert($errorIs(Families::validatePublication($nested, [], 'espirituales-esenciales'), 'mismatch'), 'readback detecta deriva respecto al plan anterior');
    $assert(Families::resolveLesson($nested)['familia_serie'] === 'otras-espirituales', 'herencia cambia sin actualizar lecciones');

    wp_set_object_terms($nested, [$otherBlock], 'serie_cde');
    $assert($errorIs(Families::resolveLesson($nested), 'ambiguous_series'), 'conflicto real entre término propio y ancestros');
    $assert(! is_wp_error(Families::validatePublication($nested, ['post_parent' => $index, 'serie_cde' => []], 'otras-espirituales')), 'preflight valida sólo la propuesta de corrección, no el estado persistido');
    $assert($errorIs(Families::validatePublication($nested, [], 'otras-espirituales'), 'ambiguous_series'), 'readback con propuesta vacía detecta conflicto real oculto por una propuesta');
    wp_set_object_terms($nested, [], 'serie_cde');
    $assert(! is_wp_error(Families::resolveLesson($nested)), 'resolución recuperada tras retirar sólo conflicto del fixture');
    $assert(get_post_status($index) === 'draft' && get_post_status($lesson) === 'draft' && get_post_status($nested) === 'draft', 'ningún fixture publicado o programado');
} catch (Throwable $error) {
    $failure = $error;
} finally {
    // Delete only IDs created by this invocation after verifying type and exact unique name.
    foreach (array_reverse($posts, true) as $id => $expectedTitle) {
        $owned = get_post($id);
        if (! $owned || $owned->post_type !== 'cde' || $owned->post_title !== $expectedTitle || $owned->post_status !== 'draft') {
            $cleanupErrors[] = 'Post no reconocido como fixture propio: ' . $id;
            continue;
        }
        if (! wp_delete_post($id, true) || get_post($id) !== null) {
            $cleanupErrors[] = 'No se eliminó fixture post: ' . $id;
        }
    }
    foreach (array_reverse($terms, true) as $id => $expectedName) {
        $owned = get_term($id, 'serie_cde');
        if (! $owned || is_wp_error($owned) || $owned->name !== $expectedName) {
            $cleanupErrors[] = 'Término no reconocido como fixture propio: ' . $id;
            continue;
        }
        $deleted = wp_delete_term($id, 'serie_cde');
        if (is_wp_error($deleted) || $deleted !== true || term_exists($id, 'serie_cde')) {
            $cleanupErrors[] = 'No se eliminó fixture término: ' . $id;
        }
    }
}
if ($cleanupErrors) {
    WP_CLI::error(implode('; ', $cleanupErrors) . '. Identidad de esta prueba: ' . $token);
}
if ($failure) {
    WP_CLI::error($failure->getMessage() . ' (fixtures propios eliminados)');
}
WP_CLI::success($checks . ' comprobaciones reales; todos los fixtures propios eliminados. No se alteró contenido existente.');

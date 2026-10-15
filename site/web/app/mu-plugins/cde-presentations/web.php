<?php

declare(strict_types=1);

namespace EspacioSutil\Presentations;

function deliver(): void
{
    nocache_headers();
    header('Cache-Control: private, no-store, max-age=0');
    header('X-Content-Type-Options: nosniff');
    header('X-Robots-Tag: noindex, noarchive');
    header('Vary: Cookie');
    $fail = static function (string $code, int $status): never {
        status_header($status);
        header('Content-Type: application/json; charset=UTF-8');
        if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
            echo wp_json_encode(['code' => $code]);
        }
        exit;
    };
    if (! in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true)) {
        header('Allow: GET, HEAD');
        $fail('method_not_allowed', 405);
    }
    $id = absint($_GET['lesson'] ?? 0);
    $preview = ($_GET['preview'] ?? '') === '1';
    $permission = Service::access($id, $preview);
    if (is_wp_error($permission)) {
        $fail($permission->get_error_code(), $permission->get_error_data()['status'] ?? 403);
    }
    try {
        $active = Service::active($id);
        $kind = is_string($_GET['kind'] ?? null) ? $_GET['kind'] : '';
        $requested = is_string($_GET['revision'] ?? null) ? $_GET['revision'] : '';
        $revision = $preview && $requested ? $requested : $active;
        if ($kind === 'source-image' && $preview) {
            $data = Service::load($id, $requested);
            $kind = 'image';
        } else {
            $data = null;
        }
        if (! $revision) {
            if ($kind === 'manifest') {
                header('Content-Type: application/json; charset=UTF-8');
                if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
                    echo wp_json_encode(['schema_version' => 1, 'lesson_id' => $id, 'available' => false, 'presentation' => null]);
                }
                exit;
            }
            $fail('presentation_unavailable', 404);
        }
        if ($kind === 'image' && $requested !== $revision) {
            $fail('presentation_revision_changed', 409);
        }
        $data ??= Service::presentation($id);
        if ($kind === 'manifest') {
            header('Content-Type: application/json; charset=UTF-8');
            if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
                echo wp_json_encode(Service::model($id, $data, $preview));
            }
            exit;
        }
        if ($kind !== 'image') {
            $fail('not_found', 404);
        }
        $page = absint($_GET['page'] ?? 0);
        $width = absint($_GET['width'] ?? 0);
        foreach ($data['slides'][$page - 1]['variants'] ?? [] as $variant) {
            if ($variant['width'] === $width) {
                $file = Package::verifyImage(Service::directory($id, $data['slides'][$page - 1]['source_revision'] ?? $revision), $variant);
                header('Content-Type: image/webp');
                header('Content-Length: '.$variant['bytes']);
                // El piloto transmite un archivo acotado después de autorizar cada petición.
                // No depende de una URL estática ni de cambiar Nginx en otros trabajos.
                if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
                    readfile($file);
                }
                exit;
            }
        }
        $fail('not_found', 404);
    } catch (\Throwable $error) {
        error_log('CDE presentation unavailable for lesson '.$id.': '.$error->getMessage());
        $fail('presentation_unavailable', 503);
    }
}
add_action('admin_post_cde_presentation', __NAMESPACE__.'\\deliver');
add_action('admin_post_nopriv_cde_presentation', __NAMESPACE__.'\\deliver');

// La respuesta de una lección con presentación tampoco debe entrar en una caché compartida.
add_action('template_redirect', static function () {
    if (is_singular('cde') && Service::active((int) get_queried_object_id())) {
        if (! defined('DONOTCACHEPAGE')) {
            define('DONOTCACHEPAGE', true);
        }
        nocache_headers();
        header('Cache-Control: private, no-store, max-age=0');
    }
});

// Mantener los campos de presentación fuera de REST al integrarlos en Lección CDE.
add_filter('acf/rest/get_fields', static function (array $fields): array {
    return array_filter($fields, static fn (array $field): bool => ! in_array($field['key'], [
        'field_cde_presentation_lesson_presentation_enabled',
        'field_cde_presentation_presentation_help',
        'field_cde_presentation_slides',
    ], true));
});

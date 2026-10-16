<?php

/** Desarrollo: CDE_TEST_IMAGE=/ruta/imagen-3840.webp wp eval-file .../test_editor_live.php */
use EspacioSutil\Presentations\Editor;
use EspacioSutil\Presentations\Package;
use EspacioSutil\Presentations\Service;

if (WP_ENV !== 'development' || ! is_file(getenv('CDE_TEST_IMAGE') ?: '')) {
    throw new RuntimeException('Requiere desarrollo y CDE_TEST_IMAGE');
}
$id = wp_insert_post(['post_type' => 'cde', 'post_status' => 'draft', 'post_title' => '[QA temporal] Variantes privadas']);
if (! $id) {
    throw new RuntimeException('No se pudo crear la lección de prueba');
}
$count = 0;
$check = static function ($valid, $message) use (&$count) {
    if (! $valid) {
        throw new RuntimeException($message);
    } $count++;
};
try {
    update_post_meta($id, 'clave_leccion', 'cde:99:99:98');
    update_post_meta($id, 'lesson_presentation_enabled', '1');
    $ref = Editor::importImage($id, getenv('CDE_TEST_IMAGE'));
    [$revision] = explode(':', $ref);
    $data = Package::read(Service::directory($id, $revision), true);
    $check(count($data['slides'][0]['variants']) === 5, 'Faltan variantes hasta 3840');
    $check(Editor::importImage($id, getenv('CDE_TEST_IMAGE')) === $ref, 'Subida repetida no idempotente');
    update_post_meta($id, Service::META, $revision);
    $check(count(Service::references($id)) === 1, 'Falla el paquete inicial');
    update_field('field_cde_presentation_slides', [['field_cde_presentation_image' => $ref], ['field_cde_presentation_image' => $ref]], $id);
    $check(count(Service::presentation($id)['slides']) === 2, 'No usa las filas guardadas');
    $check(Service::revision($id) !== $revision, 'No distingue edición del paquete');
    update_field('field_cde_presentation_slides', [], $id);
    $check(Service::references($id) === [] && Service::active($id) === '', 'No respeta el borrado total');
    $bad = tempnam(sys_get_temp_dir(), 'cde-invalid-');
    file_put_contents($bad, 'not-an-image');
    try {
        Editor::importImage($id, $bad);
        $failed = false;
    } catch (Throwable) {
        $failed = true;
    } finally {
        unlink($bad);
    }
    $check($failed, 'Acepta un archivo que no es imagen');
    try {
        Service::resolve(2389, $ref);
        $failed = false;
    } catch (Throwable) {
        $failed = true;
    }
    $check($failed, 'Acepta una imagen de otra lección');
    echo $count." comprobaciones del editor privado correctas\n";
} finally {
    wp_delete_post($id, true);
    $dir = Service::root().'/'.$id;
    if (is_dir($dir)) {
        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS), RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($it as $entry) {
            $entry->isDir() && ! $entry->isLink() ? rmdir($entry->getPathname()) : unlink($entry->getPathname());
        }rmdir($dir);
    }
}

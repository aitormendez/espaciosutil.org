<?php

declare(strict_types=1);

namespace EspacioSutil\Presentations;

final class Editor
{
    public static function postId(): int
    {
        return absint($_POST['post_ID'] ?? $_POST['_acf_post_id'] ?? get_the_ID());
    }

    public static function thumbnail(int $id, string $ref): string
    {
        $slide = Service::resolve($id, $ref);

        return Service::url($id, 'source-image', ['revision' => $slide['source_revision'], 'page' => $slide['page'], 'width' => $slide['variants'][0]['width'], 'preview' => 1]);
    }

    /** La subida manual genera variantes privadas; nunca pasa por uploads público. */
    public static function importImage(int $id, string $file): string
    {
        $size = @getimagesize($file);
        if (! $size || ! in_array($size['mime'], ['image/jpeg', 'image/png', 'image/webp'], true)
            || filesize($file) > 20 * 1024 * 1024 || $size[0] * $size[1] > 40000000 || max($size[0], $size[1]) / min($size[0], $size[1]) > 10) {
            throw new \RuntimeException('Usa una imagen JPEG, PNG o WebP de hasta 20 MB y 40 megapíxeles.');
        }
        $key = (string) get_post_meta($id, 'clave_leccion', true);
        if (! preg_match('/^cde:[0-9]+:[0-9]+:[0-9]+(?:\.[0-9]+)*$/D', $key)) {
            throw new \RuntimeException('Guarda primero una clave técnica válida para la lección.');
        }
        require_once ABSPATH.'wp-admin/includes/image.php';
        $editor = wp_get_image_editor($file);
        if (is_wp_error($editor)) {
            throw new \RuntimeException('No se puede procesar esta imagen.');
        }
        $editor->maybe_exif_rotate();
        $dimensions = $editor->get_size();
        $parent = Service::root().'/'.$id;
        if (is_link($parent) || (! is_dir($parent) && ! mkdir($parent, 0750, true))) {
            throw new \RuntimeException('No se puede escribir en el almacén privado.');
        }
        $tmp = $parent.'/.upload-'.bin2hex(random_bytes(8));
        mkdir($tmp, 0750);
        mkdir($tmp.'/slides', 0750);
        try {
            $max = min(3840, $dimensions['width']);
            $widths = array_unique(array_merge(array_filter([640, 1280, 1920, 2560, 3840], static fn ($w) => $w <= $max), [$max]));
            sort($widths);
            $variants = [];
            foreach ($widths as $width) {
                $image = clone $editor;
                $result = $width === $dimensions['width'] ? true : $image->resize($width, null, false);
                if (is_wp_error($result)) {
                    throw new \RuntimeException('No se puede redimensionar la imagen.');
                }
                $image->set_quality(88);
                $path = 'slides/0001-'.$width.'.webp';
                $saved = $image->save($tmp.'/'.$path, 'image/webp');
                if (is_wp_error($saved)) {
                    throw new \RuntimeException('No se puede guardar la imagen privada.');
                }
                chmod($tmp.'/'.$path, 0640);
                $variants[] = ['path' => $path, 'width' => $saved['width'], 'height' => $saved['height'], 'mime_type' => 'image/webp', 'bytes' => filesize($tmp.'/'.$path), 'sha256' => hash_file('sha256', $tmp.'/'.$path)];
            }
            $data = ['schema_version' => 1, 'clave_leccion' => $key, 'source_sha256' => hash_file('sha256', $file), 'renderer_version' => 'wordpress-image-editor', 'conversion_profile' => 'manual-image-webp-q88-v1', 'page_count' => 1,
                'slides' => [['page' => 1, 'width' => $dimensions['width'], 'height' => $dimensions['height'], 'variants' => $variants]]];
            $data['revision'] = hash('sha256', Package::canonical($data));
            file_put_contents($tmp.'/manifest.json', Package::canonical($data));
            chmod($tmp.'/manifest.json', 0640);
            Package::read($tmp, true);
            $dest = Service::directory($id, $data['revision']);
            if (is_dir($dest)) {
                Package::read($dest, true);
            } elseif (! rename($tmp, $dest)) {
                throw new \RuntimeException('No se puede completar la subida.');
            }

            return $data['revision'].':1';
        } finally {
            if (is_dir($tmp)) {
                foreach (glob($tmp.'/slides/*') as $path) {
                    unlink($path);
                }
                if (is_file($tmp.'/manifest.json')) {
                    unlink($tmp.'/manifest.json');
                }
                rmdir($tmp.'/slides');
                rmdir($tmp);
            }
        }
    }
}

add_action('acf/include_field_types', static function () {
    require_once __DIR__.'/private-image-field.php';
    new PrivateImageField;
});

// Materializar las páginas importadas como filas editables sin escribir al abrir el editor.
add_filter('acf/load_value/key=field_cde_presentation_slides', static function ($value, $id) {
    if (! is_numeric($id) || metadata_exists('post', (int) $id, 'lesson_presentation_slides')) {
        return $value;
    }
    try {
        return array_map(static fn ($ref) => ['field_cde_presentation_image' => $ref], Service::references((int) $id));
    } catch (\Throwable) {
        return $value;
    }
}, 20, 2);

add_action('wp_ajax_cde_presentation_upload', static function () {
    $id = absint($_POST['lesson'] ?? 0);
    if (! $id || get_post_type($id) !== 'cde' || ! current_user_can('edit_post', $id) || ! current_user_can('upload_files')) {
        wp_send_json_error(['message' => 'No tienes permiso para editar esta lección.'], 403);
    }
    check_ajax_referer('cde_presentation_upload_'.$id, 'nonce');
    try {
        $file = $_FILES['image'] ?? [];
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || ! is_uploaded_file($file['tmp_name'] ?? '')) {
            throw new \RuntimeException('No se ha recibido la imagen. Comprueba el tamaño del archivo.');
        }
        $ref = Editor::importImage($id, $file['tmp_name']);
        wp_send_json_success(['value' => $ref, 'url' => Editor::thumbnail($id, $ref)]);
    } catch (\Throwable $error) {
        wp_send_json_error(['message' => $error->getMessage()], 400);
    }
});

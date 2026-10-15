<?php

declare(strict_types=1);

namespace EspacioSutil\Presentations;

use EspacioSutil\Mobile\Access;

final class Service
{
    public const META = '_cde_presentation_revision';

    public static function root(): string
    {
        $root = defined('CDE_PRESENTATIONS_DIR') ? CDE_PRESENTATIONS_DIR : getenv('CDE_PRESENTATIONS_DIR');
        // La misma carpeta montada funciona desde WP-CLI en macOS y PHP-FPM en Lima.
        if (! $root && defined('WP_ENV') && WP_ENV === 'development') {
            $root = dirname(WP_CONTENT_DIR, 2).'/storage/cde-presentations';
        }
        if (! $root || ! is_dir($root) || is_link($root)) {
            throw new \RuntimeException('Configura CDE_PRESENTATIONS_DIR fuera del directorio público');
        }
        $real = realpath($root);
        $public = realpath(dirname(WP_CONTENT_DIR));
        if (! $real || ! $public || $real === $public || str_starts_with($real, $public.'/')) {
            throw new \RuntimeException('El almacén debe ser privado');
        }

        return $real;
    }

    public static function directory(int $id, string $revision): string
    {
        if ($id < 1 || ! preg_match('/^[a-f0-9]{64}$/D', $revision)) {
            throw new \RuntimeException('Referencia inválida');
        }
        $parent = self::root().'/'.$id;
        if (is_link($parent)) {
            throw new \RuntimeException('No se permiten enlaces');
        }

        return $parent.'/'.$revision;
    }

    public static function access(int $id, bool $preview = false): bool|\WP_Error
    {
        if ($preview) {
            return current_user_can('edit_post', $id) ? true : new \WP_Error('forbidden', 'Sin permiso de edición.', ['status' => 403]);
        }
        if (! class_exists(Access::class)) {
            return new \WP_Error('access_unavailable', 'Servicio no disponible.', ['status' => 503]);
        }

        return Access::lesson($id, get_current_user_id(), false);
    }

    public static function active(int $id): string
    {
        try {
            return get_post_meta($id, 'lesson_presentation_enabled', true) === '1'
                ? self::revision($id) : '';
        } catch (\Throwable $error) {
            error_log('CDE presentation unavailable for lesson '.$id.': '.$error->getMessage());

            return '';
        }
    }

    public static function references(int $id): array
    {
        if (metadata_exists('post', $id, 'lesson_presentation_slides')) {
            $count = (int) get_post_meta($id, 'lesson_presentation_slides', true);
            if ($count < 0 || $count > 100) {
                throw new \RuntimeException('Número de diapositivas inválido');
            }
            $refs = [];
            for ($i = 0; $i < $count; $i++) {
                $refs[] = (string) get_post_meta($id, "lesson_presentation_slides_{$i}_image", true);
            }

            return $refs;
        }

        return self::importedReferences($id);
    }

    public static function importedReferences(int $id): array
    {
        $revision = (string) get_post_meta($id, self::META, true);
        if (! $revision) {
            return [];
        }
        $data = self::load($id, $revision);

        return array_map(static fn ($slide) => $revision.':'.$slide['page'], $data['slides']);
    }

    public static function isEdited(int $id): bool
    {
        return metadata_exists('post', $id, 'lesson_presentation_slides') && self::references($id) !== self::importedReferences($id);
    }

    public static function revision(int $id): string
    {
        if (! self::isEdited($id)) {
            return (string) get_post_meta($id, self::META, true);
        }
        $refs = self::references($id);

        return $refs ? hash('sha256', Package::canonical($refs)) : '';
    }

    public static function resolve(int $id, string $reference): array
    {
        if (! preg_match('/^([a-f0-9]{64}):([1-9][0-9]{0,2})$/D', $reference, $match)) {
            throw new \RuntimeException('Imagen no válida');
        }
        $data = self::load($id, $match[1]);
        $slide = $data['slides'][(int) $match[2] - 1] ?? null;
        if (! $slide) {
            throw new \RuntimeException('Diapositiva ausente');
        }

        return $slide + ['source_revision' => $match[1]];
    }

    public static function presentation(int $id): array
    {
        $slides = [];
        foreach (self::references($id) as $ref) {
            $slide = self::resolve($id, $ref);
            $slide['page'] = count($slides) + 1;
            $slides[] = $slide;
        }

        return ['revision' => self::revision($id), 'page_count' => count($slides), 'slides' => $slides];
    }

    public static function load(int $id, string $revision): array
    {
        $data = Package::read(self::directory($id, $revision));
        if ($data['revision'] !== $revision || $data['clave_leccion'] !== get_post_meta($id, 'clave_leccion', true)) {
            throw new \RuntimeException('El paquete no corresponde a la lección');
        }

        return $data;
    }

    public static function url(int $id, string $kind, array $params = []): string
    {
        return add_query_arg(array_merge(['action' => 'cde_presentation', 'lesson' => $id, 'kind' => $kind], $params), admin_url('admin-post.php'));
    }

    public static function view(int $id): ?array
    {
        if (self::access($id) !== true || ! self::active($id)) {
            return null;
        }

        return ['manifestUrl' => self::url($id, 'manifest'), 'title' => get_the_title($id)];
    }

    public static function model(int $id, array $data, bool $preview): array
    {
        return ['schema_version' => 1, 'lesson_id' => $id, 'available' => true, 'presentation' => [
            'revision' => $data['revision'], 'page_count' => $data['page_count'],
            'slides' => array_map(static function ($slide) use ($id, $data, $preview) {
                return ['page' => $slide['page'], 'alt' => sprintf('Diapositiva %d de %d de %s', $slide['page'], $data['page_count'], get_the_title($id)),
                    'sources' => array_map(static fn ($v) => ['width' => $v['width'], 'height' => $v['height'], 'mime_type' => $v['mime_type'], 'bytes' => $v['bytes'],
                        'url' => self::url($id, 'image', ['revision' => $data['revision'], 'page' => $slide['page'], 'width' => $v['width'], 'preview' => $preview ? 1 : 0])], $slide['variants'])];
            }, $data['slides']),
        ]];
    }
}

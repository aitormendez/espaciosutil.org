<?php

declare(strict_types=1);

namespace EspacioSutil\Presentations;

final class Package
{
    public static function canonical(array $value): string
    {
        $sort = static function (array $data) use (&$sort): array {
            if (! array_is_list($data)) {
                ksort($data, SORT_STRING);
            }
            foreach ($data as &$item) {
                if (is_array($item)) {
                    $item = $sort($item);
                }
            }

            return $data;
        };

        return json_encode($sort($value), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }

    private static function require(bool $condition, string $message): void
    {
        if (! $condition) {
            throw new \RuntimeException($message);
        }
    }

    private static function keys(array $value, array $expected): void
    {
        $actual = array_keys($value);
        sort($actual);
        sort($expected);
        self::require($actual === $expected, 'Campos inesperados o ausentes');
    }

    public static function read(string $directory, bool $verifyFiles = false): array
    {
        self::require(! is_link($directory) && is_dir($directory), 'Directorio de paquete inválido');
        $file = $directory.'/manifest.json';
        self::require(is_file($file) && ! is_link($file) && filesize($file) <= 1024 * 1024, 'Manifiesto inválido');
        $data = json_decode(file_get_contents($file), true, 64, JSON_THROW_ON_ERROR);
        self::require(is_array($data) && ($data['schema_version'] ?? null) === 1, 'Esquema no soportado');
        self::require(preg_match('/^cde:[0-9]+:[0-9]+:[0-9]+(?:\.[0-9]+)*$/D', $data['clave_leccion'] ?? '') === 1, 'Clave inválida');
        self::require(preg_match('/^[a-f0-9]{64}$/D', $data['source_sha256'] ?? '') === 1, 'Hash fuente inválido');
        self::keys($data, ['schema_version', 'clave_leccion', 'source_sha256', 'renderer_version', 'conversion_profile', 'page_count', 'slides', 'revision']);
        self::require(is_string($data['renderer_version']) && is_string($data['conversion_profile']), 'Perfil inválido');
        $revision = $data['revision'] ?? '';
        unset($data['revision']);
        self::require(is_string($revision) && hash_equals(hash('sha256', self::canonical($data)), $revision), 'Revisión inconsistente');
        self::require(is_int($data['page_count']) && $data['page_count'] >= 1 && $data['page_count'] <= 100 && is_array($data['slides']) && array_is_list($data['slides']) && count($data['slides']) === $data['page_count'], 'Páginas inválidas');
        $expected = ['manifest.json'];
        $bytes = 0;
        foreach ($data['slides'] as $index => $slide) {
            self::keys($slide, ['page', 'width', 'height', 'variants']);
            self::require($slide['page'] === $index + 1 && ! isset($slide['text']) && ! isset($slide['text_status']), 'Orden o campos inválidos');
            self::require(is_int($slide['width']) && is_int($slide['height']) && $slide['width'] > 0 && $slide['height'] > 0 && $slide['width'] * $slide['height'] <= 40000000, 'Geometría inválida');
            self::require(is_array($slide['variants']) && count($slide['variants']) >= 1 && count($slide['variants']) <= 5, 'Variantes inválidas');
            $previous = 0;
            foreach ($slide['variants'] as $variant) {
                self::keys($variant, ['path', 'width', 'height', 'mime_type', 'bytes', 'sha256']);
                self::require(is_int($variant['width']) && $variant['width'] > $previous && $variant['width'] <= 3840 && is_int($variant['height']) && $variant['height'] > 0 && $variant['height'] <= 38400, 'Dimensiones inválidas');
                self::require(abs($variant['height'] - $slide['height'] * $variant['width'] / $slide['width']) <= 1, 'Proporción inválida');
                $previous = $variant['width'];
                $path = sprintf('slides/%04d-%d.webp', $slide['page'], $variant['width']);
                self::require($variant['path'] === $path && $variant['mime_type'] === 'image/webp' && is_int($variant['bytes']) && $variant['bytes'] > 0 && preg_match('/^[a-f0-9]{64}$/D', $variant['sha256']) === 1, 'Archivo inválido');
                $expected[] = $path;
                $bytes += $variant['bytes'];
                if ($verifyFiles) {
                    self::verifyImage($directory, $variant);
                }
            }
        }
        self::require($bytes <= 1024 ** 3, 'Paquete demasiado grande');
        if ($verifyFiles) {
            $actual = [];
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS), \RecursiveIteratorIterator::SELF_FIRST);
            foreach ($iterator as $entry) {
                self::require(! $entry->isLink(), 'No se permiten enlaces simbólicos');
                if ($entry->isFile()) {
                    $actual[] = substr($entry->getPathname(), strlen($directory) + 1);
                } else {
                    self::require($entry->isDir() && substr($entry->getPathname(), strlen($directory) + 1) === 'slides', 'Directorio inesperado');
                }
            }
            sort($actual);
            sort($expected);
            self::require($actual === $expected, 'Archivos extra o ausentes (no se admiten PDF)');
        }
        $data['revision'] = $revision;

        return $data;
    }

    public static function verifyImage(string $directory, array $variant): string
    {
        $file = $directory.'/'.$variant['path'];
        self::require(! is_link($directory.'/slides') && ! is_link($file) && is_file($file), 'Imagen ausente o enlace');
        self::require(filesize($file) === $variant['bytes'] && hash_equals($variant['sha256'], hash_file('sha256', $file)), 'Imagen alterada');
        $size = getimagesize($file);
        self::require($size && $size[0] === $variant['width'] && $size[1] === $variant['height'] && $size['mime'] === 'image/webp', 'Imagen o MIME inválido');

        return $file;
    }
}

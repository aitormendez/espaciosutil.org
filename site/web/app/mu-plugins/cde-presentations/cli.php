<?php

declare(strict_types=1);

namespace EspacioSutil\Presentations;

\WP_CLI::add_command('cde presentation import', static function (array $args, array $options) {
    $lock = null;
    try {
        if (empty($args[0]) || empty($options['lesson-key']) || ! array_key_exists('expected-revision', $options)) {
            throw new \RuntimeException('Uso: wp cde presentation import PAQUETE --lesson-key=CLAVE --expected-revision=none|SHA [--apply] [--activate]');
        }
        $source = rtrim($args[0], '/');
        $package = Package::read($source, true);
        if ($package['clave_leccion'] !== $options['lesson-key']) {
            throw new \RuntimeException('Clave del paquete diferente');
        }
        $posts = get_posts(['post_type' => 'cde', 'post_status' => ['publish', 'draft', 'future', 'pending', 'private'], 'posts_per_page' => 2, 'fields' => 'ids', 'meta_key' => 'clave_leccion', 'meta_value' => $options['lesson-key']]);
        if (count($posts) !== 1) {
            throw new \RuntimeException('La clave debe resolver exactamente una lección');
        }
        $id = (int) $posts[0];
        $expected = $options['expected-revision'] === 'none' ? '' : $options['expected-revision'];
        $parent = Service::root().'/'.$id;
        if (is_link($parent)) {
            throw new \RuntimeException('Almacén inválido');
        }
        if (! isset($options['apply'])) {
            if ((string) get_post_meta($id, Service::META, true) !== $expected) {
                throw new \RuntimeException('Revisión esperada diferente');
            }
            \WP_CLI::success('Dry-run: paquete válido; lección '.$id.'; revisión '.$package['revision']);

            return;
        }
        if (! is_dir($parent) && ! mkdir($parent, 0750, true)) {
            throw new \RuntimeException('No se puede crear el almacén');
        }
        $lock = fopen($parent.'/.import.lock', 'c');
        if (! $lock || ! flock($lock, LOCK_EX | LOCK_NB)) {
            throw new \RuntimeException('Importación concurrente');
        }
        wp_cache_delete($id, 'post_meta');
        $before = (string) get_post_meta($id, Service::META, true);
        if ($before !== $expected) {
            throw new \RuntimeException('Revisión esperada diferente');
        }
        $destination = Service::directory($id, $package['revision']);
        if (! is_dir($destination)) {
            $temporary = $parent.'/.incoming-'.bin2hex(random_bytes(8));
            mkdir($temporary, 0750);
            mkdir($temporary.'/slides', 0750);
            copy($source.'/manifest.json', $temporary.'/manifest.json');
            foreach ($package['slides'] as $slide) {
                foreach ($slide['variants'] as $variant) {
                    if (! copy($source.'/'.$variant['path'], $temporary.'/'.$variant['path'])) {
                        throw new \RuntimeException('Copia incompleta');
                    }
                }
            }
            $copied = Package::read($temporary, true);
            if ($copied['revision'] !== $package['revision']) {
                throw new \RuntimeException('La fuente cambió durante la copia');
            }
            if (! rename($temporary, $destination)) {
                throw new \RuntimeException('Instalación incompleta');
            }
        }
        Package::read($destination, true);
        if (isset($options['activate'])) {
            update_post_meta($id, Service::META, $package['revision']);
            update_post_meta($id, 'lesson_presentation_enabled', '1');
            if (Service::active($id) !== $package['revision']) {
                throw new \RuntimeException('No se pudo verificar activación');
            }
        }
        \WP_CLI::log(wp_json_encode(['lesson_id' => $id, 'previous_revision' => $before, 'revision' => $package['revision'], 'active_revision' => Service::active($id)]));
        \WP_CLI::success('Paquete verificado.');
    } catch (\Throwable $error) {
        \WP_CLI::error($error->getMessage(), false);
        \WP_CLI::halt(1);
    } finally {
        if (is_resource($lock)) {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
});

<?php

namespace EspacioSutil\Practicas;

/** Explicit local importer. It never publishes, contacts NocoDB or modifies the lesson engine. */
final class ImportCommand
{
    private static function json(string $path): array
    {
        $raw = file_get_contents($path);
        if ($raw === false || strlen($raw) > 8 * 1024 * 1024) {
            throw new \RuntimeException('Paquete ausente o demasiado grande.');
        }
        $data = json_decode($raw, true, 64, JSON_THROW_ON_ERROR);
        if (!is_array($data)) {
            throw new \RuntimeException('Se esperaba un objeto JSON.');
        }
        return $data;
    }

    private static function unique(string $type, string $field, string $key): ?\WP_Post
    {
        $posts = get_posts(['post_type' => $type, 'post_status' => array_keys(get_post_stati()), 'posts_per_page' => 2, 'meta_key' => $field, 'meta_value' => $key]);
        if (count($posts) > 1) {
            throw new \RuntimeException('Identidad duplicada: '.$key);
        }
        if ($posts && get_post_meta($posts[0]->ID, $field, true) !== $key) {
            throw new \RuntimeException('La identidad no coincide exactamente: '.$key);
        }
        return $posts[0] ?? null;
    }

    private static function snapshot(?\WP_Post $post): ?array
    {
        if (!$post) {
            return null;
        }
        return ['id' => $post->ID, 'title' => $post->post_title, 'content' => $post->post_content,
            'excerpt' => $post->post_excerpt, 'status' => $post->post_status,
            'code' => get_post_meta($post->ID, 'codigo_practica', true),
            'source' => get_post_meta($post->ID, 'referencia_fuente', true),
            'hash' => get_post_meta($post->ID, '_cde_practica_source_sha256', true),
            'relations' => get_post_meta($post->ID, Catalog::RELATION, true)];
    }

    public static function buildPlan(array $package): array
    {
        if (($package['schema'] ?? '') !== 'cde.practices.package' || ($package['version'] ?? null) !== 1
            || !is_array($package['practices'] ?? null) || !is_array($package['lessons'] ?? null)) {
            throw new \RuntimeException('Contrato de prácticas no válido.');
        }
        $result = ['schema' => 'cde.practices.wordpress-plan', 'version' => 1, 'site' => home_url(), 'package' => $package, 'practices' => [], 'lessons' => []];
        $keys = [];
        foreach ($package['practices'] as $practice) {
            $key = $practice['clave_practica'] ?? '';
            if (!is_string($key) || !preg_match('/^practica:[a-zA-Z0-9:._-]{1,120}$/D', $key) || isset($keys[$key])) {
                throw new \RuntimeException('Clave de práctica inválida o duplicada.');
            }
            $keys[$key] = true;
            foreach (['title', 'content', 'excerpt', 'codigo_practica', 'referencia_fuente', 'source_sha256'] as $field) {
                if (!isset($practice[$field]) || !is_string($practice[$field])) {
                    throw new \RuntimeException('Falta el campo '.$field);
                }
            }
            if (trim($practice['title']) === '' || trim($practice['content']) === '' || !preg_match('/^[a-f0-9]{64}$/D', $practice['source_sha256'])) {
                throw new \RuntimeException('Contenido o huella de origen no válido.');
            }
            foreach (['title', 'excerpt', 'codigo_practica', 'referencia_fuente'] as $plainField) {
                if (strip_tags($practice[$plainField]) !== $practice[$plainField]) {
                    throw new \RuntimeException('El campo '.$plainField.' debe ser texto plano.');
                }
            }
            if (wp_kses_post($practice['content']) !== $practice['content']) {
                throw new \RuntimeException('El contenido contiene HTML no permitido.');
            }
            $post = self::unique('practica', Catalog::KEY, $key);
            $before = self::snapshot($post);
            $same = $post && $post->post_title === $practice['title'] && $post->post_content === $practice['content']
                && $post->post_excerpt === $practice['excerpt'] && $before['code'] === $practice['codigo_practica']
                && $before['source'] === $practice['referencia_fuente'] && $before['hash'] === $practice['source_sha256'];
            // Existing editorial work is never overwritten by a fresh source delivery.
            if ($post && (!$same || in_array($post->post_status, ['trash', 'auto-draft'], true))) {
                throw new \RuntimeException('La práctica ya existe y requiere revisión editorial: '.$key);
            }
            $result['practices'][$key] = ['before' => $before, 'action' => $post ? 'keep' : 'create_draft'];
        }
        foreach ($package['lessons'] as $lesson) {
            $key = $lesson['clave_leccion'] ?? '';
            if (!is_string($key) || !preg_match('/^cde:[0-9]{2}:[0-9]{2}:[0-9]+(?:\.[0-9]+)*$/D', $key) || isset($result['lessons'][$key])) {
                throw new \RuntimeException('Clave de lección inválida o duplicada.');
            }
            $post = self::unique('cde', 'clave_leccion', $key);
            if (!$post || in_array($post->post_status, ['trash', 'auto-draft'], true)) {
                throw new \RuntimeException('Lección no disponible: '.$key);
            }
            $wanted = $lesson['practicas'] ?? null;
            if (!is_array($wanted) || !$wanted || count($wanted) !== count(array_unique($wanted))) {
                throw new \RuntimeException('Asignaciones inválidas.');
            }
            foreach ($wanted as $practiceKey) {
                if (!is_string($practiceKey) || !isset($keys[$practiceKey])) {
                    throw new \RuntimeException('Práctica sin definición en el paquete.');
                }
            }
            $current = get_post_meta($post->ID, Catalog::RELATION, true);
            if ($current === '') {
                $current = [];
            }
            if (!is_array($current)) {
                throw new \RuntimeException('Relación ACF incompatible.');
            }
            // Add confirmed relations; never infer removals from a partial inventory.
            $result['lessons'][$key] = ['id' => $post->ID, 'before' => $current, 'add' => $wanted];
        }
        return $result;
    }

    public static function execute(array $plan): array
    {
        if (($plan['schema'] ?? '') !== 'cde.practices.wordpress-plan' || ($plan['version'] ?? null) !== 1 || ($plan['site'] ?? '') !== home_url()) {
            throw new \RuntimeException('Plan o entorno incorrecto.');
        }
        if (!function_exists('update_field')) {
            throw new \RuntimeException('ACF no está disponible.');
        }
        // Compare the entire reviewed plan before the first write, including content and current links.
        if (self::buildPlan($plan['package']) !== $plan) {
            throw new \RuntimeException('WordPress ha cambiado desde el plan; generar y revisar otro plan.');
        }
        $ids = [];
        $observed = [];
        foreach ($plan['package']['practices'] as $practice) {
            $key = $practice['clave_practica'];
            if (self::snapshot(self::unique('practica', Catalog::KEY, $key)) !== $plan['practices'][$key]['before']) {
                throw new \RuntimeException('La práctica cambió durante la importación.');
            }
            $id = $plan['practices'][$key]['before']['id'] ?? 0;
            if (!$id) {
                $id = wp_insert_post(wp_slash(['post_type' => 'practica', 'post_status' => 'draft', 'post_title' => $practice['title'], 'post_content' => $practice['content'], 'post_excerpt' => $practice['excerpt']]), true);
                if (is_wp_error($id)) {
                    throw new \RuntimeException('No se pudo crear el borrador. Revisar las escrituras parciales antes de repetir.');
                }
                update_field('field_cde_clave_practica', $key, $id);
                update_field('field_cde_codigo_practica', $practice['codigo_practica'], $id);
                update_field('field_cde_fuente_practica', $practice['referencia_fuente'], $id);
                update_post_meta($id, '_cde_practica_source_sha256', $practice['source_sha256']);
            }
            $post = get_post($id);
            if (!$post || get_post_meta($id, Catalog::KEY, true) !== $key || $post->post_content !== $practice['content']
                || $post->post_title !== $practice['title'] || $post->post_excerpt !== $practice['excerpt']
                || get_post_meta($id, 'codigo_practica', true) !== $practice['codigo_practica']
                || get_post_meta($id, 'referencia_fuente', true) !== $practice['referencia_fuente']
                || get_post_meta($id, '_cde_practica_source_sha256', true) !== $practice['source_sha256']) {
                throw new \RuntimeException('Falló la verificación del borrador; detener y revisar.');
            }
            $ids[$key] = $id;
            $observed[] = ['clave_practica' => $key, 'id_post_wp' => $id, 'url_wp' => get_permalink($id), 'wp_status_verificado' => $post->post_status, 'wp_last_verified_at' => gmdate('c')];
        }
        foreach ($plan['lessons'] as $lesson) {
            $current = get_post_meta($lesson['id'], Catalog::RELATION, true);
            if ($current === '') {
                $current = [];
            }
            if ($current !== $lesson['before']) {
                throw new \RuntimeException('La relación cambió durante la importación; detener y revisar.');
            }
            $after = array_values(array_unique(array_merge(array_map('strval', $current), array_map(static fn ($key) => (string) $ids[$key], $lesson['add']))));
            update_field('field_cde_practicas', $after, $lesson['id']);
            if (array_map('strval', (array) get_post_meta($lesson['id'], Catalog::RELATION, true)) !== $after) {
                throw new \RuntimeException('Falló la verificación de asociaciones.');
            }
        }
        return ['schema' => 'cde.practices.wordpress-receipt', 'version' => 1, 'site' => home_url(), 'practices' => $observed];
    }

    /** Generate a read-only plan. Usage: wp cde-practicas plan package.json --out=plan.json --user=ADMIN */
    public function plan($args, $assoc): void
    {
        $this->run(function () use ($args, $assoc) {
            if (empty($assoc['out']) || file_exists($assoc['out'])) {
                throw new \RuntimeException('Indica --out con una ruta nueva.');
            }
            $plan = self::buildPlan(self::json($args[0] ?? ''));
            $handle = fopen($assoc['out'], 'x');
            if (!$handle) {
                throw new \RuntimeException('No se pudo crear el plan.');
            }
            fwrite($handle, wp_json_encode($plan, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)."\n");
            fclose($handle);
            \WP_CLI::success('Plan generado sin escrituras en WordPress.');
        });
    }

    /** Apply a reviewed plan; creates drafts and adds confirmed links. Usage: wp cde-practicas apply plan.json --user=ADMIN */
    public function apply($args): void
    {
        $this->run(function () use ($args) {
            if (!add_option('cde_practicas_import_lock', gmdate('c'), '', false)) {
                throw new \RuntimeException('Hay otra importación o un bloqueo pendiente de revisión.');
            }
            try {
                \WP_CLI::line(wp_json_encode(self::execute(self::json($args[0] ?? '')), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } finally {
                delete_option('cde_practicas_import_lock');
            }
        });
    }

    private function run(callable $operation): void
    {
        if (!current_user_can('manage_options')) {
            \WP_CLI::error('Selecciona un administrador con --user.');
        }
        try {
            $operation();
        } catch (\Throwable $error) {
            \WP_CLI::error($error->getMessage());
        }
    }
}

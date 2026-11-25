<?php

declare(strict_types=1);

namespace EspacioSutil\Cde;

require_once __DIR__ . '/family-catalog.php';

/** The root serie_cde owns the family; neither blocks nor lessons own a copy. */
final class Families
{
    public const META_KEY = 'familia_serie';

    public static function boot(): void
    {
        add_action('init', [self::class, 'register'], 30);
        add_action('rest_api_init', [self::class, 'rest']);
        add_filter('rest_pre_insert_cde', [self::class, 'validateRestPublication'], 10, 2);
        add_filter('rest_pre_insert_serie_cde', [self::class, 'validateRestSeries'], 10, 2);
        add_action('serie_cde_add_form_fields', [self::class, 'adminAddFields']);
        add_action('serie_cde_edit_form_fields', [self::class, 'adminEditFields']);
        add_action('created_serie_cde', [self::class, 'adminSave']);
        add_action('edited_serie_cde', [self::class, 'adminSave']);
        add_action('admin_notices', [self::class, 'adminNotice']);
        add_filter('wp_update_term_parent', [self::class, 'protectRootParent'], 10, 3);
        FamilyCatalog::boot();
    }

    public static function register(): void
    {
        register_term_meta('serie_cde', self::META_KEY, [
            'type' => 'string', 'single' => true, 'show_in_rest' => false,
            'description' => 'Código editorial de la familia; sólo en la serie raíz.',
        ]);
    }

    private static function error(string $code, string $message, array $data = []): \WP_Error
    {
        return new \WP_Error('cde_family_' . $code, $message, ['status' => 422] + $data);
    }

    public static function catalog(): array
    {
        return FamilyCatalog::items();
    }

    /** Only an explicit, confirmed human operation may clear an assignment, including a legacy one. */
    public static function unassignSeries(int $seriesId, bool $confirm = false): bool|\WP_Error
    {
        if (!$confirm) { return self::error('reclassification_required', 'Confirma la retirada de la familia.'); }
        $term = get_term($seriesId, 'serie_cde');
        if (!$term || is_wp_error($term)) { return self::error('series_missing', 'La serie no existe.'); }
        if ((int)$term->parent !== 0) { return self::error('root_required', 'Sólo una serie raíz puede quitar su familia.'); }
        $values = get_term_meta($seriesId, self::META_KEY, false);
        if (count($values) > 1) { return self::error('duplicate_meta', 'Existen asignaciones duplicadas; revisar antes de quitar la familia.'); }
        if (!$values) { return true; }
        return delete_term_meta($seriesId, self::META_KEY, $values[0]) ?: self::error('write_failed', 'No se pudo retirar la asignación.');
    }

    /** Structural operation. Reclassification requires replace=true, never inferred from lesson codes. */
    public static function assignSeries(int $seriesId, string $slug, bool $replace = false): bool|\WP_Error
    {
        if (! FamilyCatalog::find($slug)) {
            return self::error('unknown', 'Familia desconocida.', ['familia_serie' => $slug]);
        }
        $term = get_term($seriesId, 'serie_cde');
        if (! $term || is_wp_error($term)) {
            return self::error('series_missing', 'La serie no existe.', ['series_id' => $seriesId]);
        }
        if ((int) $term->parent !== 0) {
            return self::error('root_required', 'Asignar familia sólo a una serie raíz.');
        }
        $values = get_term_meta($seriesId, self::META_KEY, false);
        if (count($values) > 1) {
            return self::error('duplicate_meta', 'Hay múltiples asignaciones familiares; reparar antes de continuar.');
        }
        $current = $values[0] ?? '';
        if ($current !== '' && $current !== $slug && ! $replace) {
            return self::error('reclassification_required', 'Cambiar la familia exige una operación estructural explícita.');
        }
        if ($current === $slug) {
            return true;
        }
        // Do not knowingly replace a value that changed since the explicit decision above.
        $saved = $values === [] ? add_term_meta($seriesId, self::META_KEY, $slug, true)
            : update_term_meta($seriesId, self::META_KEY, $slug, $current);
        return is_wp_error($saved) ? $saved : ($saved !== false ? true : self::error('write_failed', 'No se pudo guardar la familia.'));
    }

    public static function resolveSeries(int $termId): array|\WP_Error
    {
        $seen = [];
        $source = $termId;
        while ($termId > 0) {
            if (isset($seen[$termId])) {
                return self::error('series_cycle', 'Ciclo en la jerarquía serie_cde.', ['term_id' => $termId]);
            }
            $seen[$termId] = true;
            $term = get_term($termId, 'serie_cde');
            if (! $term || is_wp_error($term)) {
                return self::error('series_missing', 'Término serie_cde inexistente.', ['term_id' => $termId]);
            }
            $values = get_term_meta($termId, self::META_KEY, false);
            if ((int) $term->parent !== 0) {
                if ($values !== []) {
                    return self::error('child_assignment', 'Un bloque contiene familia propia; sólo la raíz puede asignarla.', ['term_id' => $termId]);
                }
                $termId = (int) $term->parent;
                continue;
            }
            if (count($values) > 1) {
                return self::error('duplicate_meta', 'Múltiples familias en la serie raíz.', ['series_id' => $termId]);
            }
            $slug = $values[0] ?? '';
            if ($slug === '') {
                return self::error('unassigned', 'Serie pendiente de clasificación humana.', ['series_id' => $termId]);
            }
            if (is_string($slug) && preg_match('/^[a-z][a-z0-9-]*$/D', $slug)) {
                return self::error('legacy', 'Asignación anterior conservada: ' . $slug . '. Requiere revisión explícita.', ['series_id' => $termId, 'legacy_value' => $slug]);
            }
            $row = is_string($slug) ? FamilyCatalog::find($slug) : null;
            if (!$row) { return self::error('unknown', 'La familia guardada no pertenece al catálogo.', ['series_id' => $termId]); }
            return $row + ['familia_serie' => $slug, 'series_id' => $termId,
                'series_slug' => $term->slug, 'source_term_id' => $source];
        }
        return self::error('series_missing', 'Se necesita un identificador serie_cde válido.');
    }

    /** Resolve the proposed state as well as persisted lessons, before any publication write. */
    public static function resolveLesson(int $postId, array $proposed = []): array|\WP_Error
    {
        $post = $postId > 0 ? get_post($postId) : null;
        if ($postId > 0 && (! $post || $post->post_type !== 'cde')) {
            return self::error('lesson_missing', 'La lección CDE no existe.');
        }
        $parent = array_key_exists('post_parent', $proposed) ? $proposed['post_parent'] : (int) ($post->post_parent ?? 0);
        if (! is_int($parent) || $parent < 0) {
            return self::error('parent_invalid', 'post_parent debe ser un entero no negativo.');
        }
        $terms = array_key_exists('serie_cde', $proposed) ? $proposed['serie_cde'] : ($post ? wp_get_object_terms($postId, 'serie_cde', ['fields' => 'ids']) : []);
        if (is_wp_error($terms)) {
            return $terms;
        }
        if (! is_array($terms)) {
            return self::error('terms_invalid', 'serie_cde debe ser una lista de IDs.');
        }
        $sets = [['post_id' => $postId, 'terms' => $terms]];
        $seen = $postId > 0 ? [$postId => true] : [];
        while ($parent > 0) {
            if (isset($seen[$parent])) {
                return self::error('post_cycle', 'Ciclo en post_parent.', ['post_id' => $parent]);
            }
            $seen[$parent] = true;
            $ancestor = get_post($parent);
            if (! $ancestor || $ancestor->post_type !== 'cde') {
                return self::error('parent_invalid', 'Un ancestro no es una lección CDE.', ['post_id' => $parent]);
            }
            $ids = wp_get_object_terms($parent, 'serie_cde', ['fields' => 'ids']);
            if (is_wp_error($ids)) {
                return $ids;
            }
            $sets[] = ['post_id' => $parent, 'terms' => $ids];
            $parent = (int) $ancestor->post_parent;
        }
        $resolved = $legacy = $root = null;
        foreach ($sets as $set) {
            foreach ($set['terms'] as $id) {
                if (! is_int($id) || $id <= 0) {
                    return self::error('terms_invalid', 'Los identificadores serie_cde deben ser enteros positivos.');
                }
                $family = self::resolveSeries($id);
                if (is_wp_error($family)) {
                    if ($family->get_error_code() !== 'cde_family_legacy') { return $family; }
                    $legacy = $family;
                    $seriesId = $family->get_error_data()['series_id'];
                } else {
                    $seriesId = $family['series_id'];
                }
                if ($root !== null && $root !== $seriesId) {
                    return self::error('ambiguous_series', 'La lección o sus ancestros pertenecen a más de una serie.', ['series_ids' => [$root, $seriesId]]);
                }
                $root = $seriesId;
                if (!$resolved && !is_wp_error($family)) {
                    $resolved = $family + ['source_post_id' => $set['post_id'], 'inherited' => $set['post_id'] !== $postId];
                }
            }
        }
        return $legacy ?? $resolved ?? self::error('no_series', 'La lección y sus ancestros no tienen serie_cde.');
    }

    /** CLI/publication contract: validate before wp_insert_post/wp_update_post or other writes. */
    public static function validatePublication(int $postId, array $proposed, string $expectedFamily): array|\WP_Error
    {
        if (! FamilyCatalog::find($expectedFamily)) {
            return self::error('unknown', 'El publicador necesita un código familiar explícito del catálogo.');
        }
        $resolved = self::resolveLesson($postId, $proposed);
        if (is_wp_error($resolved)) {
            return $resolved;
        }
        if ($resolved['familia_serie'] !== $expectedFamily) {
            return self::error('mismatch', 'La familia indicada no coincide con la serie heredada.', ['expected' => $expectedFamily, 'actual' => $resolved['familia_serie']]);
        }
        return $resolved;
    }

    private static function view(array|\WP_Error $result): array
    {
        return is_wp_error($result)
            ? ['ok' => false, 'error' => ['code' => $result->get_error_code(), 'message' => $result->get_error_message(), 'data' => $result->get_error_data()]]
            : ['ok' => true] + $result;
    }

    public static function rest(): void
    {
        foreach (['serie_cde', 'cde'] as $type) {
            register_rest_field($type, 'familia_resuelta', [
                'get_callback' => static fn(array $item): array => self::view($type === 'cde' ? self::resolveLesson((int) $item['id']) : self::resolveSeries((int) $item['id'])),
                'schema' => ['description' => 'Familia derivada, nunca una asignación local.', 'type' => 'object', 'readonly' => true, 'context' => ['view', 'edit']],
            ]);
        }
        register_rest_route('cde/v1', '/familias', [
            'methods' => 'GET', 'permission_callback' => '__return_true',
            'callback' => static fn(): array => ['version' => 2, 'items' => self::catalog()],
        ]);
        register_rest_route('cde/v1', '/familias/series/(?P<id>\d+)', [
            [
                'methods' => 'PUT', 'permission_callback' => static fn(): bool => current_user_can('manage_categories'),
                'args' => ['familia_serie' => ['required' => true, 'type' => 'string', 'pattern' => '^(?:0[1-9]|[1-9][0-9])$'], 'replace' => ['type' => 'boolean', 'default' => false]],
                'callback' => static function (\WP_REST_Request $request): array|\WP_Error {
                    $result = self::assignSeries((int) $request['id'], $request['familia_serie'], $request['replace']);
                    return is_wp_error($result) ? $result : self::resolveSeries((int) $request['id']);
                },
            ],
            [
                'methods' => 'DELETE', 'permission_callback' => static fn(): bool => current_user_can('manage_categories'),
                'args' => ['replace' => ['required' => true, 'type' => 'boolean']],
                'callback' => static function (\WP_REST_Request $request): array|\WP_Error {
                    $result = self::unassignSeries((int)$request['id'], $request['replace'] === true);
                    return is_wp_error($result) ? $result : ['series_id' => (int)$request['id'], 'familia_serie' => null];
                },
            ],
        ]);
        register_rest_route('cde/v1', '/familias/inventario', [
            'methods' => 'GET', 'permission_callback' => static fn(): bool => current_user_can('manage_categories') && current_user_can('edit_posts'),
            'args' => ['page' => ['type' => 'integer', 'default' => 1, 'minimum' => 1], 'per_page' => ['type' => 'integer', 'default' => 100, 'minimum' => 1, 'maximum' => 100], 'kind' => ['type' => 'string', 'enum' => ['series', 'lessons'], 'default' => 'series']],
            'callback' => [self::class, 'inventory'],
        ]);
    }

    public static function inventory(\WP_REST_Request $request): \WP_REST_Response|\WP_Error
    {
        $limit = (int) $request['per_page'];
        $page = (int) $request['page'];
        $items = [];
        if ($request['kind'] === 'series') {
            $query = ['taxonomy' => 'serie_cde', 'hide_empty' => false, 'orderby' => 'term_id', 'order' => 'ASC'];
            $terms = get_terms($query + ['number' => $limit, 'offset' => ($page - 1) * $limit]);
            $total = wp_count_terms($query);
            if (is_wp_error($terms) || is_wp_error($total)) {
                return is_wp_error($terms) ? $terms : $total;
            }
            foreach ($terms as $term) {
                $items[] = ['term_id' => (int) $term->term_id, 'slug' => $term->slug, 'name' => $term->name,
                    'parent_id' => (int) $term->parent, 'kind' => $term->parent ? 'block' : 'series',
                    'family' => self::view(self::resolveSeries((int) $term->term_id))];
            }
        } else {
            $query = new \WP_Query(['post_type' => 'cde', 'post_status' => ['publish', 'draft', 'pending', 'private', 'future'],
                'posts_per_page' => $limit, 'paged' => $page, 'orderby' => 'ID', 'order' => 'ASC']);
            $total = $query->found_posts;
            foreach ($query->posts as $post) {
                $items[] = ['post_id' => (int) $post->ID, 'slug' => $post->post_name, 'status' => $post->post_status,
                    'parent_id' => (int) $post->post_parent, 'clave_leccion' => (string) get_post_meta($post->ID, 'clave_leccion', true),
                    'family' => self::view(self::resolveLesson((int) $post->ID))];
            }
        }
        $response = new \WP_REST_Response(['version' => 2, 'site_url' => home_url(), 'page' => $page, 'per_page' => $limit,
            'total' => (int) $total, 'total_pages' => (int) ceil((int) $total / $limit), 'items' => $items]);
        $response->header('Cache-Control', 'private, no-store');
        return $response;
    }

    /** Admin taxonomy editor, not a second lesson-level classification. */
    public static function adminAddFields(): void
    {
        if (! current_user_can('manage_categories')) {
            return;
        }
        echo '<div class="form-field"><label for="cde-family">Familia de la serie</label>';
        self::adminSelect('');
        echo '<p>Elegir sólo al crear una serie raíz. Los bloques heredan la familia de su serie.</p></div>';
    }

    public static function adminEditFields(\WP_Term $term): void
    {
        if (! current_user_can('manage_categories')) {
            return;
        }
        echo '<tr class="form-field"><th scope="row"><label for="cde-family">Familia de la serie</label></th><td>';
        if ((int) $term->parent !== 0) {
            $resolved = self::resolveSeries((int) $term->term_id);
            echo '<p>' . esc_html(is_wp_error($resolved) ? $resolved->get_error_message() : $resolved['label']) . '</p><p>Heredada de la serie raíz; no editable en este bloque.</p>';
        } else {
            $values = get_term_meta($term->term_id, self::META_KEY, false);
            $current = $values[0] ?? '';
            self::adminSelect(is_string($current) ? $current : '');
            echo '<p>No se infiere por código ni título. Dejar sin seleccionar conserva la asignación actual.</p>';
            if ($values !== []) {
                echo '<p><label><input type="checkbox" name="cde_family_replace" value="1"> Confirmo el cambio o la retirada de la familia de toda esta serie y sus lecciones.</label></p>';
                $resolved = self::resolveSeries((int) $term->term_id);
                if (is_wp_error($resolved)) {
                    echo '<p>' . esc_html($resolved->get_error_message()) . '</p>';
                }
            }
        }
        echo '</td></tr>';
    }

    private static function adminSelect(string $current): void
    {
        wp_nonce_field('cde_family_assignment', 'cde_family_nonce');
        echo '<select name="cde_family_slug" id="cde-family"><option value="">Sin asignar / conservar</option>';
        if ($current !== '') { echo '<option value="__clear__">Quitar la familia (requiere confirmación)</option>'; }
        foreach (self::catalog() as ['code' => $slug, 'label' => $label]) {
            echo '<option value="' . esc_attr($slug) . '"' . selected($current, $slug, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select>';
    }

    public static function adminSave(int $termId): void
    {
        if (! isset($_POST['cde_family_slug'])) {
            return;
        }
        if (! current_user_can('manage_categories') || ! isset($_POST['cde_family_nonce']) || ! is_string($_POST['cde_family_nonce']) || ! wp_verify_nonce(wp_unslash($_POST['cde_family_nonce']), 'cde_family_assignment')) {
            self::notice('No se guardó la familia: autorización o nonce inválido.');
            return;
        }
        $slug = wp_unslash($_POST['cde_family_slug']);
        if ($slug === '') {
            return; // Clearing via an accidental blank select is deliberately not supported.
        }
        $confirmed = ($_POST['cde_family_replace'] ?? '') === '1';
        $result = $slug === '__clear__' ? self::unassignSeries($termId, $confirmed) : (is_string($slug)
            ? self::assignSeries($termId, $slug, $confirmed)
            : self::error('unknown', 'Formato de familia inválido.'));
        if (is_wp_error($result)) {
            self::notice($result->get_error_message());
        }
    }

    private static function notice(string $message): void
    {
        set_transient('cde_family_notice_' . get_current_user_id(), $message, 120);
    }

    public static function adminNotice(): void
    {
        $key = 'cde_family_notice_' . get_current_user_id();
        $message = get_transient($key);
        if ($message) {
            delete_transient($key);
            echo '<div class="notice notice-error"><p>' . esc_html($message) . '</p></div>';
        }
    }

    public static function protectRootParent(int $parent, int $termId, string $taxonomy): int
    {
        if ($taxonomy === 'serie_cde' && $parent > 0 && get_term_meta($termId, self::META_KEY, false) !== []) {
            $term = get_term($termId, 'serie_cde');
            if ($term && ! is_wp_error($term) && (int) $term->parent === 0) {
                self::notice('La serie tiene una familia asignada y no puede convertirse en bloque. Se conserva como raíz.');
                return 0;
            }
        }
        return $parent;
    }

    /**
     * @param object $prepared Term data or a WP_Error from an earlier filter.
     */
    public static function validateRestSeries($prepared, \WP_REST_Request $request)
    {
        if (is_wp_error($prepared)) {
            return $prepared;
        }
        if ($request->has_param('parent') && (int) $request['parent'] > 0 && $request['id'] && get_term_meta((int) $request['id'], self::META_KEY, false) !== []) {
            return self::error('root_required', 'Una serie con familia asignada no puede convertirse en bloque por REST.');
        }
        return $prepared;
    }

    /**
     * @param object $prepared Post data or a WP_Error from an earlier filter.
     */
    public static function validateRestPublication($prepared, \WP_REST_Request $request)
    {
        if (is_wp_error($prepared)) {
            return $prepared;
        }
        $id = (int) ($request['id'] ?? 0);
        if (! $request->has_param('familia_serie')) {
            return $prepared; // Explicit opt-in: installing the plugin must not block the legacy publisher/editor.
        }
        $taxonomy = get_taxonomy('serie_cde');
        $base = $taxonomy->rest_base ?: 'serie_cde';
        $proposed = [];
        if ($request->has_param($base)) {
            $proposed['serie_cde'] = $request[$base];
        }
        if ($request->has_param('parent')) {
            $proposed['post_parent'] = $request['parent'];
        }
        $result = is_string($request['familia_serie'])
            ? self::validatePublication($id, $proposed, $request['familia_serie'])
            : self::error('unknown', 'familia_serie debe ser un código de texto.');
        return is_wp_error($result) ? $result : $prepared;
    }
}

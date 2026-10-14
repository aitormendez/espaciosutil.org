<?php

namespace EspacioSutil\Practicas;

final class Catalog
{
    public const RELATION = 'cde_practicas';
    public const KEY = 'clave_practica';

    public static function allowed(): bool
    {
        return get_current_user_id() > 0 && function_exists('pmpro_hasMembershipLevel')
            && (bool) pmpro_hasMembershipLevel(null, get_current_user_id());
    }

    public static function editor(): bool
    {
        return current_user_can('edit_others_posts');
    }

    public static function boot(): void
    {
        add_action('init', [self::class, 'register']);
        add_action('acf/init', [self::class, 'fields']);
        add_action('template_redirect', [self::class, 'protectPage'], 0);
        add_filter('rest_request_before_callbacks', [self::class, 'protectRest'], PHP_INT_MAX, 3);
        add_filter('rest_post_dispatch', static function ($response, $server, $request) {
            if (preg_match('#^/wp/v2/practicas(?:/|$)#', $request->get_route())) {
                $response->header('Cache-Control', 'private, no-store, max-age=0');
            }
            return $response;
        }, 10, 3);
        add_filter('oembed_response_data', static fn ($data, $post) => $post->post_type === 'practica' ? false : $data, 10, 2);
        add_filter('the_content', [self::class, 'protectContent'], PHP_INT_MAX);
        add_filter('get_the_excerpt', static function ($excerpt, $post) {
            return $post->post_type === 'practica' && !self::allowed() && !self::editor() ? '' : $excerpt;
        }, PHP_INT_MAX, 2);
        add_filter('wp_sitemaps_post_types', static function ($types) {
            unset($types['practica']);
            return $types;
        });
        add_filter('rest_post_search_query', static function ($args) {
            if (!self::allowed() && !self::editor()) {
                $types = (array) ($args['post_type'] ?? []);
                $args['post_type'] = array_values(array_diff($types, ['practica']));
                if (!$args['post_type']) {
                    $args['post__in'] = [0];
                }
            }
            return $args;
        });
        add_action('pre_get_posts', static function ($query) {
            if (!is_admin() && $query->is_main_query() && $query->is_post_type_archive('practica')) {
                $query->set('has_password', false);
                $query->set('orderby', 'title');
                $query->set('order', 'ASC');
                $query->set('posts_per_page', 12);
                $query->set('s', self::search());
                $series = self::selectedSeries();
                if ($series) {
                    $lessons = get_posts(['post_type'=>'cde', 'post_status'=>'publish', 'posts_per_page'=>-1,
                        'has_password'=>false, 'meta_key'=>'active_lesson', 'meta_value'=>'1',
                        'tax_query'=>[['taxonomy'=>'serie_cde', 'field'=>'term_id', 'terms'=>$series, 'include_children'=>true]]]);
                    $ids = [];
                    foreach ($lessons as $lesson) {
                        $related = get_post_meta($lesson->ID, self::RELATION, true);
                        if (is_array($related)) { $ids = array_merge($ids, array_map('intval', $related)); }
                    }
                    $query->set('post__in', array_values(array_unique($ids)) ?: [0]);
                }
            }
            if ($query->is_feed()) {
                $types = $query->get('post_type');
                if ($types === 'practica' || (is_array($types) && in_array('practica', $types, true))) {
                    $query->set('post__in', [0]);
                }
            }
        });
    }

    public static function search(): string
    {
        return isset($_GET['practice_search']) && is_string($_GET['practice_search']) ? sanitize_text_field(wp_unslash($_GET['practice_search'])) : '';
    }

    public static function selectedSeries(): int
    {
        return isset($_GET['practice_series']) && is_scalar($_GET['practice_series']) ? absint($_GET['practice_series']) : 0;
    }

    public static function series(): array
    {
        $terms = get_terms(['taxonomy'=>'serie_cde', 'parent'=>0, 'hide_empty'=>true]);
        return is_wp_error($terms) ? [] : $terms;
    }

    public static function register(): void
    {
        register_post_type('practica', [
            'labels' => ['name' => 'Prácticas', 'singular_name' => 'Práctica', 'add_new_item' => 'Añadir práctica', 'edit_item' => 'Editar práctica'],
            'public' => true,
            'exclude_from_search' => true,
            'show_in_rest' => true,
            'rest_base' => 'practicas',
            'has_archive' => 'practicas',
            'rewrite' => ['slug' => 'practicas', 'with_front' => false, 'feeds' => false],
            'supports' => ['title', 'editor', 'excerpt', 'revisions'],
            'menu_icon' => 'dashicons-universal-access-alt',
        ]);
    }

    public static function fields(): void
    {
        acf_add_local_field_group([
            'key' => 'group_cde_practica', 'title' => 'Práctica CDE', 'show_in_rest' => false,
            'fields' => [
                ['key' => 'field_cde_clave_practica', 'name' => self::KEY, 'label' => 'Clave de la práctica', 'type' => 'text', 'required' => 1],
                ['key' => 'field_cde_codigo_practica', 'name' => 'codigo_practica', 'label' => 'Código editorial', 'type' => 'text'],
            ],
            'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'practica']]],
        ]);
        acf_add_local_field_group([
            'key' => 'group_cde_practicas_leccion', 'title' => 'Prácticas de esta lección', 'show_in_rest' => false,
            'fields' => [[
                'key' => 'field_cde_practicas', 'name' => self::RELATION, 'label' => 'Prácticas',
                'type' => 'relationship', 'post_type' => ['practica'], 'filters' => ['search'],
                'return_format' => 'id',
                'instructions' => 'Asociaciones del inventario NocoDB. Los cambios manuales deben reconciliarse antes de otra importación.',
            ]],
            'location' => [[['param' => 'post_type', 'operator' => '==', 'value' => 'cde']]],
        ]);
    }

    public static function protectPage(): void
    {
        if (is_singular('practica') || is_post_type_archive('practica')) {
            if (!defined('DONOTCACHEPAGE')) {
                define('DONOTCACHEPAGE', true);
            }
            nocache_headers();
            header('Cache-Control: private, no-store, max-age=0');
            if (is_embed() || is_feed()) {
                status_header(404);
                exit;
            }
            if (!self::allowed() && !self::editor()) {
                status_header(403);
            }
        }
    }

    public static function protectRest($response, $server, $request)
    {
        if (preg_match('#^/wp/v2/practicas(?:/|$)#', $request->get_route())) {
            if (!self::allowed() && !self::editor()) {
                return new \WP_Error('cde_membership_required', 'Necesitas una membresía activa.', ['status' => get_current_user_id() ? 403 : 401]);
            }
        }
        return $response;
    }

    public static function protectContent($content)
    {
        if (get_post_type() === 'practica' && !self::allowed() && !self::editor()) {
            return '';
        }
        return $content;
    }

    public static function visible($post): bool
    {
        return $post instanceof \WP_Post && $post->post_status === 'publish' && $post->post_password === '';
    }

    public static function forLesson(int $id): array
    {
        if (!self::allowed() && !self::editor()) {
            return [];
        }
        $lesson = get_post($id);
        if (!self::visible($lesson) || $lesson->post_type !== 'cde' || get_post_meta($id, 'active_lesson', true) !== '1') {
            return [];
        }
        $ids = get_post_meta($id, self::RELATION, true);
        if (!is_array($ids)) {
            return [];
        }
        $result = [];
        foreach (array_unique(array_map('intval', $ids)) as $practiceId) {
            $post = get_post($practiceId);
            if (self::visible($post) && $post->post_type === 'practica') {
                $result[] = $post;
            }
        }
        return $result;
    }

    public static function lessonsFor(int $practiceId): array
    {
        if ((!self::allowed() && !self::editor()) || !self::visible(get_post($practiceId))) {
            return [];
        }
        // ACF Relationship stores an array of string IDs; quotes prevent matching 12 against 112.
        $posts = get_posts([
            'post_type' => 'cde', 'post_status' => 'publish', 'posts_per_page' => -1,
            'orderby' => ['menu_order' => 'ASC', 'title' => 'ASC'],
            'meta_query' => [
                ['key' => self::RELATION, 'value' => '"'.$practiceId.'"', 'compare' => 'LIKE'],
                ['key' => 'active_lesson', 'value' => '1'],
            ],
        ]);
        return array_values(array_filter($posts, [self::class, 'visible']));
    }
}

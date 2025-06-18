<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class RegisterContentProvider extends ServiceProvider
{
    public function register()
    {
        //
    }

    public function boot()
    {
        // Cargar configuración
        $config = require get_theme_file_path('config/cpts.php');

        // Asegurar que el registro ocurre en 'init'
        add_action('init', function () use ($config) {
            // CPTs
            foreach ($config['post'] ?? [] as $post_type => $args) {
                $this->registerPostType($post_type, $args);
            }

            // Taxonomías
            foreach ($config['taxonomy'] ?? [] as $taxonomy => $args) {
                $this->registerTaxonomy($taxonomy, $args);
            }
        });

        // Placeholder personalizado para el título
        add_filter('enter_title_here', function ($title, $post) use ($config) {
            foreach ($config['post'] ?? [] as $post_type => $args) {
                if (($args['enter_title_here'] ?? false) && $post->post_type === $post_type) {
                    return $args['enter_title_here'];
                }
            }
            return $title;
        }, 10, 2);

        // Columnas admin personalizadas (ejemplo con 'serie')
        add_filter('manage_edit-serie_columns', function ($columns) use ($config) {
            $args = $config['post']['serie'] ?? [];
            if (!empty($args['admin_cols'])) {
                foreach ($args['admin_cols'] as $key => $details) {
                    $columns[$key] = is_array($details) && isset($details['taxonomy']) ? ucfirst($details['taxonomy']) : ucfirst($key);
                }
            }
            return $columns;
        });

        add_action('manage_serie_posts_custom_column', function ($column, $post_id) use ($config) {
            $args = $config['post']['serie'] ?? [];
            if (!empty($args['admin_cols']) && isset($args['admin_cols'][$column])) {
                $tax = $args['admin_cols'][$column]['taxonomy'] ?? null;
                if ($tax) {
                    $terms = get_the_terms($post_id, $tax);
                    if (!empty($terms) && !is_wp_error($terms)) {
                        echo implode(', ', wp_list_pluck($terms, 'name'));
                    }
                }
            }
        }, 10, 2);
    }

    protected function registerPostType(string $post_type, array $args): void
    {
        if (!function_exists('register_extended_post_type')) {
            return;
        }

        $options = $args;
        unset($options['enter_title_here'], $options['admin_cols']);
        register_extended_post_type($post_type, $options);
    }

    protected function registerTaxonomy(string $taxonomy, array $args): void
    {
        if (!function_exists('register_extended_taxonomy')) {
            return;
        }

        $links = $args['links'] ?? [];
        $options = $args;
        unset($options['links']);
        register_extended_taxonomy($taxonomy, $links, $options);
    }
}

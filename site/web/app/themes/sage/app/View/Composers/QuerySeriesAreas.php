<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class QuerySeriesAreas extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        'series',
        'single-area',
        'taxonomy-revelador',
        'partials.serie-relacionada',
    ];

    /**
     * Data to be passed to view before rendering, but after merging.
     *
     * @return array
     */
    public function override()
    {
        return [
            'items_query' => $this->itemsQuery(),
            'series_relacionadas' => function () {
                global $post;
                $series_relacionadas = get_field('area_series_relacionadas', $post->ID);
                return $series_relacionadas;
            },
            'thumbnail_meta' => function ($post) {
                $image_id = get_post_thumbnail_id($post);
                return [
                    'alt' => get_post_meta($image_id, '_wp_attachment_image_alt', TRUE),
                ];
            },
            'taxonomias' => function ($post) {

                $reveladores_raw = get_the_terms($post, 'revelador');
                $canales_raw = get_the_terms($post, 'canal');
                $facilitadores_raw = get_the_terms($post, 'facilitador');
                $autores_raw = get_the_terms($post, 'autor');

                if ($autores_raw) {
                    $autores_string = '';
                    $autores_epigrafe = count($autores_raw) > 1 ? 'Autores' : 'Autor';

                    foreach ($autores_raw as $key => $autor) {
                        if ($key === array_key_last($autores_raw)) {
                            $autores_string .= '<a href="' . get_term_link($autor) . '">' . $autor->name . '</a>';
                        } else {
                            $autores_string .= '<a href="' . get_term_link($autor) . '">' . $autor->name . '</a>, ';
                        }
                    }
                } else {
                    $autores_string = false;
                    $autores_epigrafe = false;
                }

                if ($facilitadores_raw) {
                    $facilitadores_string = '';
                    $facilitadores_epigrafe = count($facilitadores_raw) > 1 ? 'Facilitadores' : 'Facilitador';

                    foreach ($facilitadores_raw as $key => $facilitador) {
                        if ($key === array_key_last($facilitadores_raw)) {
                            $facilitadores_string .= '<a href="' . get_term_link($facilitador) . '">' . $facilitador->name . '</a>';
                        } else {
                            $facilitadores_string .= '<a href="' . get_term_link($facilitador) . '">' . $facilitador->name . '</a>, ';
                        }
                    }
                } else {
                    $facilitadores_string = false;
                    $facilitadores_epigrafe = false;
                }

                if ($canales_raw) {
                    $canales_string = '';
                    $canales_epigrafe = count($canales_raw) > 1 ? 'Canales' : 'Canal';

                    foreach ($canales_raw as $key => $canal) {
                        if ($key === array_key_last($canales_raw)) {
                            $canales_string .= '<a href="' . get_term_link($canal) . '">' . $canal->name . '</a>';
                        } else {
                            $canales_string .= '<a href="' . get_term_link($canal) . '">' . $canal->name . '</a>, ';
                        }
                    }
                } else {
                    $canales_string = false;
                    $canales_epigrafe = false;
                }

                if ($reveladores_raw) {
                    $reveladores_string = '';
                    $reveladores_epigrafe = count($reveladores_raw) > 1 ? 'Reveladores' : 'Revelador';

                    foreach ($reveladores_raw as $key => $rev) {
                        if ($key === array_key_last($reveladores_raw)) {
                            $reveladores_string .= '<a href="' . get_term_link($rev) . '">' . $rev->name . '</a>';
                        } else {
                            $reveladores_string .= '<a href="' . get_term_link($rev) . '">' . $rev->name . '</a>, ';
                        }
                    }
                } else {
                    $reveladores_string = false;
                    $reveladores_epigrafe = false;
                }

                return [
                    'reveladores' => $reveladores_string,
                    'reveladores_epigrafe' => $reveladores_epigrafe,
                    'canales' => $canales_string,
                    'canales_epigrafe' => $canales_epigrafe,
                    'facilitadores' => $facilitadores_string,
                    'facilitadores_epigrafe' => $facilitadores_epigrafe,
                    'autores' => $autores_string,
                    'autores_epigrafe' => $autores_epigrafe,
                ];
            },
            'enlaces' => function ($post) {
                $enlaces = get_field('serie_enlaces', $post->ID);
                return is_array($enlaces) ? $enlaces : [];
            }
        ];
    }

    /**
     * Query de series y areas.
     *
     * @return object
     */
    public function itemsQuery()
    {
        if (is_page('textos-canalizados')) {
            $terms = 'textos-canalizados';
        } else if (is_page('otros-textos')) {
            $terms = 'otros-textos';
        } else if (is_page('otras-series')) {
            $terms = 'otras-series';
        } else {
            $terms = null;
        }

        $args = [
            'post_type'   => ['area', 'serie'],
            'post_status' => 'publish',
            'paged'       => (get_query_var('paged')) ? get_query_var('paged') : 1,
            'orderby'     => 'type',
            'order'     => 'ASC',
            'tax_query'   => [
                [
                    'taxonomy' => 'seccion',
                    'field'    => 'slug',
                    'terms'    => $terms,
                ],
            ],
            'posts_per_page' => -1,
        ];

        $the_query = new \WP_Query($args);

        return $the_query;
    }
}

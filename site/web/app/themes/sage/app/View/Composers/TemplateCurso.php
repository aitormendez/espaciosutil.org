<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class TemplateCurso extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        'template-curso',
    ];

    /**
     * Data to be passed to the view.
     *
     * @return array
     */
    public function with()
    {
        return [
            'series_cde_lessons' => $this->get_series_lessons(),
        ];
    }

    /**
     * Builds a hierarchical index of the 'cde' CPT.
     *
     * @return array
     */
    public function get_series_lessons()
    {
        $args = [
            'post_type' => 'cde',
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy' => 'serie_cde',
                    'field'    => 'term_id',
                    'terms'    => get_terms([
                        'taxonomy' => 'serie_cde',
                        'fields'   => 'ids',
                    ]),
                ],
            ],
        ];

        return get_posts($args);
    }
}

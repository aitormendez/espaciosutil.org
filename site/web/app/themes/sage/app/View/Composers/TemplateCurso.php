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
            'revelador_lessons' => $this->get_revelador_lessons(),
        ];
    }

    /**
     * Builds a hierarchical index of the 'cde' CPT.
     *
     * @return array
     */
    public function get_revelador_lessons()
    {
        $args = [
            'post_type' => 'cde',
            'posts_per_page' => -1,
            'tax_query' => [
                [
                    'taxonomy' => 'revelador',
                    'field'    => 'term_id',
                    'terms'    => get_terms([
                        'taxonomy' => 'revelador',
                        'fields'   => 'ids',
                    ]),
                ],
            ],
        ];

        return get_posts($args);
    }
}

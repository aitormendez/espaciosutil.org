<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class ContentSerie extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        'partials.content-serie',
    ];

    /**
     * Data to be passed to view before rendering.
     *
     * @return array
     */
    public function override()
    {
        return [
            'enlaces_series' => $this->enlacesSeries(),
        ];
    }

    /**
     * Returns the post title.
     *
     * @return array
     */
    public function enlacesSeries()
    {
        global $post;

        $enlaces = get_field('serie_enlaces');

        return $enlaces;
    }
}

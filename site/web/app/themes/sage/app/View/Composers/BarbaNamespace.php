<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class BarbaNamespace extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        'layouts.app',
    ];

    /**
     * Data to be passed to view before rendering, but after merging.
     *
     * @return array
     */
    public function override()
    {
        return [
            'barba_namespace' => $this->barbaNamespace(),
        ];
    }

    /**
     * Returns the post title.
     *
     * @return string
     */
    public function barbaNamespace()
    {
        if (is_front_page()) {
            return 'home';
        }

        if (is_page()) {
            return 'page';
        }

        return 'general';
    }
}

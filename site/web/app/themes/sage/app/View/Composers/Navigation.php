<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;
use Log1x\Navi\Facades\Navi;

class Navigation extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        'partials.navigation',
        'partials.footer-navigation',
    ];

    /**
     * Data to be passed to view before rendering.
     *
     * @return array
     */
    public function with()
    {
        return [
            'navigation' => $this->navigation(),
            'footer_navigation' => $this->footerNavigation(),
        ];
    }

    /**
     * Returns the primary navigation.
     *
     * @return array
     */
    public function navigation()
    {
        if (Navi::build()->isEmpty()) {
            return;
        }

        return Navi::build()->toArray();
    }

    /**
     * Returns the footer navigation.
     *
     * @return array
     */
    public function footerNavigation()
    {
        if (Navi::build('footer_navigation')->isEmpty()) {
            return;
        }

        return Navi::build('footer_navigation')->toArray();
    }
}

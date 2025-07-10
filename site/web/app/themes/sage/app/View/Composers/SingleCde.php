<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;


class SingleCde extends Composer
{
    /**
     * Las plantillas que usará este composer.
     *
     * @var array
     */
    protected static $views = [
        'partials.content-single-cde',
    ];

    /**
     * Variables disponibles para la vista.
     *
     * @return array
     */
    public function with()
    {

        $user_id = get_current_user_id();
        $completed_lessons = $user_id ? (get_user_meta($user_id, 'cde_completed_lessons', true) ?: []) : [];

        $has_access = is_user_logged_in() && function_exists('pmpro_hasMembershipLevel') && pmpro_hasMembershipLevel();

        return [
            'is_completed' => in_array(get_the_ID(), $completed_lessons, true),
            'has_access' => $has_access,
        ];
    }
}

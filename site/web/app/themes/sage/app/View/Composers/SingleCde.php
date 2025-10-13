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
        // Respetar las restricciones por entrada de PMPro (metabox/taxonomías).
        // Fallback: si no existe PMPro, no conceder acceso por defecto.
        $has_access = false;
        if (function_exists('pmpro_has_membership_access')) {
            $post_id = get_the_ID();

            if ($post_id) {
                [$access] = pmpro_has_membership_access($post_id, $user_id, true);
                $has_access = (bool) $access;
            }
        }

        $related_lessons_posts = get_field('cde_related_lessons');
        $related_lessons = [];

        if ($related_lessons_posts) {
            $bunny_pull_zone = getenv('BUNNY_PULL_ZONE');
            foreach ($related_lessons_posts as $post) {
                setup_postdata($post);
                $featured_video_id = get_field('featured_video_id', $post->ID);

                $poster_url = null;
                if ($featured_video_id && $bunny_pull_zone) {
                    $poster_url = "https://{$bunny_pull_zone}.b-cdn.net/{$featured_video_id}/thumbnail.jpg";
                }

                $related_lessons[] = [
                    'title' => get_the_title($post->ID),
                    'permalink' => get_permalink($post->ID),
                    'poster_url' => $poster_url,
                ];
            }
            wp_reset_postdata();
        }

        return [
            'is_completed' => in_array(get_the_ID(), $completed_lessons, true),
            'has_access' => $has_access,
            'related_lessons' => $related_lessons,
        ];
    }
}

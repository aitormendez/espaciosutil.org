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
            'course_index' => $this->buildCourseIndex(),
        ];
    }

    /**
     * Builds a hierarchical index of the 'cde' CPT.
     *
     * @return array
     */
    public function buildCourseIndex()
    {
        $args = [
            'post_type' => 'cde',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
        ];

        $posts = get_posts($args);
        $post_map = [];

        foreach ($posts as $post) {
            $post_map[$post->ID] = (object) [
                'id' => $post->ID,
                'title' => $post->post_title,
                'permalink' => get_permalink($post->ID),
                'parent' => $post->post_parent,
                'children' => [],
            ];
        }

        $tree = [];
        foreach ($post_map as $id => &$node) {
            if ($node->parent && isset($post_map[$node->parent])) {
                $post_map[$node->parent]->children[] = &$node;
            } else {
                $tree[] = &$node;
            }
        }

        return $tree;
    }
}

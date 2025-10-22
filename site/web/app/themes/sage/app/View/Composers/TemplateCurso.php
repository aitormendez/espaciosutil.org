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
            'series_cde_lessons' => $this->getSeriesWithBlocks(),
        ];
    }

    /**
     * Obtains the list of series and their block terms.
     *
     * @return array
     */
    public function getSeriesWithBlocks()
    {
        $series_terms = get_terms([
            'taxonomy' => 'serie_cde',
            'parent' => 0,
            'hide_empty' => false,
            'orderby' => 'term_order',
            'order' => 'ASC',
        ]);

        if (is_wp_error($series_terms) || empty($series_terms)) {
            return [];
        }

        return array_map(function ($series_term) {
            return [
                'term_id' => $series_term->term_id,
                'name' => $series_term->name,
                'slug' => $series_term->slug,
                'blocks' => $this->getBlocksForSeries($series_term->term_id),
            ];
        }, $series_terms);
    }

    /**
     * Retrieves the block terms and their associated root posts for a series.
     *
     * @param int $parent_term_id
     * @return array
     */
    protected function getBlocksForSeries($parent_term_id)
    {
        $block_terms = get_terms([
            'taxonomy' => 'serie_cde',
            'parent' => $parent_term_id,
            'hide_empty' => false,
            'orderby' => 'term_order',
            'order' => 'ASC',
        ]);

        if (is_wp_error($block_terms) || empty($block_terms)) {
            return [];
        }

        $blocks = array_map(function ($block_term) {
            $block_post = $this->getBlockRootPost($block_term->term_id);

            if (!$block_post) {
                return null;
            }

            return [
                'term_id' => $block_term->term_id,
                'name' => $block_term->name,
                'slug' => $block_term->slug,
                'post_id' => $block_post->ID,
                'post_title' => $block_post->post_title,
            ];
        }, $block_terms);

        return array_values(array_filter($blocks));
    }

    /**
     * Finds the root post that represents the given block term.
     *
     * @param int $term_id
     * @return \WP_Post|null
     */
    protected function getBlockRootPost($term_id)
    {
        $posts = get_posts([
            'post_type' => 'cde',
            'posts_per_page' => -1,
            'orderby' => 'menu_order',
            'order' => 'ASC',
            'tax_query' => [
                [
                    'taxonomy' => 'serie_cde',
                    'field' => 'term_id',
                    'terms' => $term_id,
                ],
            ],
        ]);

        if (empty($posts)) {
            return null;
        }

        foreach ($posts as $post) {
            $ancestor_ids = get_post_ancestors($post);

            $has_term_in_ancestor = array_filter($ancestor_ids, function ($ancestor_id) use ($term_id) {
                return has_term($term_id, 'serie_cde', $ancestor_id);
            });

            if (empty($has_term_in_ancestor)) {
                return $post;
            }
        }

        return $posts[0];
    }
}

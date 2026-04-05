<?php

namespace App\View\Composers;

use App\View\Composers\Concerns\InteractsWithCdeMedia;
use Roots\Acorn\View\Composer;

class TemplateCurso extends Composer
{
    use InteractsWithCdeMedia;

    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        'template-curso',
        'template-programa',
        'template-suscripcion',
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
            'program_guide_lesson' => $this->getProgramGuideLesson(),
        ];
    }

    /**
     * Resolve the guide lesson rendered in the Programa template.
     *
     * @return array|null
     */
    protected function getProgramGuideLesson(): ?array
    {
        $slug = apply_filters('espacio_sutil/program_guide_lesson_slug', 'presentacion-del-curso');
        $slug = is_string($slug) ? trim($slug) : '';

        if ($slug === '') {
            return null;
        }

        $posts = get_posts([
            'name' => $slug,
            'post_type' => 'cde',
            'post_status' => ['publish', 'future', 'draft', 'pending', 'private'],
            'posts_per_page' => 1,
            'orderby' => 'date',
            'order' => 'DESC',
            'suppress_filters' => false,
        ]);

        if (empty($posts) || !$posts[0] instanceof \WP_Post) {
            return null;
        }

        $post = $posts[0];
        $subindex = $this->buildLessonSubindex($post->ID);
        $featuredMedia = $this->prepareFeaturedMedia($subindex['chapters'] ?? [], $post->ID);

        if (!($featuredMedia['has_video'] ?? false) && !($featuredMedia['has_audio'] ?? false)) {
            return null;
        }

        return [
            'post_id' => $post->ID,
            'title' => get_the_title($post->ID),
            'permalink' => $post->post_status === 'publish' ? get_permalink($post->ID) : null,
            'status' => $post->post_status,
            'subindex' => $subindex,
            'featured_media' => $featuredMedia,
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
                'lessons_count' => $this->getLessonsCountForBlock($block_post->ID),
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

    /**
     * Counts published lessons nested under the block root post.
     *
     * @param int $block_root_post_id
     * @return int
     */
    protected function getLessonsCountForBlock($block_root_post_id)
    {
        if (!$block_root_post_id) {
            return 0;
        }

        $descendants = get_pages([
            'post_type' => 'cde',
            'post_status' => 'publish',
            'child_of' => (int) $block_root_post_id,
            'number' => 0,
        ]);

        return is_array($descendants) ? count($descendants) : 0;
    }
}

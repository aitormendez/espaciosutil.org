<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class Post extends Composer
{
    /**
     * List of views served by this composer.
     *
     * @var array
     */
    protected static $views = [
        'partials.page-header',
        'partials.content',
        'partials.content-*',
    ];


    /**
     * Data to be passed to view before rendering, but after merging.
     *
     * @return array
     */
    public function override()
    {
        return [
            'title' => $this->title(),
            'thumb' => $this->thumb(),
            'revelador' => $this->revelador(),
            'autor' => $this->autor(),
            'pagination' => function () {
                return $this->pagination();
            },
            'loop_thumb' => function () {
                $thumb['url'] = get_the_post_thumbnail_url();
                $thumb['id'] = get_post_thumbnail_id();
                $thumb['srcset'] = wp_get_attachment_image_srcset($thumb['id']);
                $thumb['alt'] = get_post_meta($thumb['id'], '_wp_attachment_image_alt', TRUE);

                return $thumb;
            },
            'loop_thumb_postid' => function ($post) {
                $thumb['url'] = get_the_post_thumbnail_url($post);
                $thumb['id'] = get_post_thumbnail_id($post);
                $thumb['srcset'] = wp_get_attachment_image_srcset($thumb['id']);
                $thumb['alt'] = get_post_meta($thumb['id'], '_wp_attachment_image_alt', TRUE);

                return $thumb;
            }
        ];
    }

    /**
     * Returns the post title.
     *
     * @return string
     */
    public function title()
    {
        if ($this->view->name() !== 'partials.page-header') {
            return get_the_title();
        }

        if (is_home()) {
            if ($home = get_option('page_for_posts', true)) {
                return get_the_title($home);
            }

            return __('Latest Posts', 'sage');
        }

        if (is_category('pregunta-frecuente')) {
            return 'Preguntas frecuentes';
        }

        if (is_post_type_archive('event')) {
            return 'Calendario';
        }

        if (is_archive()) {
            return get_the_archive_title();
        }

        if (is_search()) {
            return sprintf(
                /* translators: %s is replaced with the search query */
                __('Search Results for %s', 'sage'),
                get_search_query()
            );
        }

        if (is_404()) {
            return __('Not Found', 'sage');
        }

        return get_the_title();
    }

    /**
     * Returns post thumbnail.
     *
     * @return array
     */
    public function thumb()
    {
        $thumb['url'] = get_the_post_thumbnail_url();
        $thumb['id'] = get_post_thumbnail_id();
        $thumb['srcset'] = wp_get_attachment_image_srcset($thumb['id']);
        $thumb['alt'] = get_post_meta($thumb['id'], '_wp_attachment_image_alt', TRUE);

        return $thumb;
    }

    /**
     * Returns revelador terms.
     *
     */
    public function revelador()
    {
        if (is_singular('serie')) {
            global $post;
            $rev = get_the_terms($post, 'revelador');

            if ($rev) {
                $link = get_term_link($rev[0]);
                return [
                    'rev' => $rev,
                    'rev_link' => $link,
                ];
            } else {
                return [
                    'rev' => false,
                ];
            }
        }
    }

    /**
     * Returns autor terms.
     *
     */
    public function autor()
    {
        if (is_singular('serie')) {
            global $post;
            $aut = get_the_terms($post, 'autor');

            if ($aut) {
                $link = get_term_link($aut[0]);
                return [
                    'aut' => $aut,
                    'aut_link' => $link,
                ];
            } else {
                return [
                    'aut' => false,
                ];
            }
        }
    }

    /**
     * Retrieve the pagination links.
     */
    public function pagination(): string
    {
        return wp_link_pages([
            'echo' => 0,
            'before' => '<p>' . __('Pages:', 'sage'),
            'after' => '</p>',
        ]);
    }
}

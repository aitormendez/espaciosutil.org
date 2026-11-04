<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class SearchResult extends Composer
{
    /** @var array<int, string> */
    protected static $views = [
        'partials.content-search',
    ];

    /**
     * Presentation data for the current result.
     *
     * @return array<string, string>
     */
    public function override(): array
    {
        $post = get_post();
        $postType = $post ? (string) $post->post_type : '';
        $permalink = $post ? (string) get_permalink($post) : '';
        $path = (string) wp_parse_url($permalink, PHP_URL_PATH);
        $isCde = \in_array($postType, ['cde', 'practica'], true)
            || function_exists('nav_context_from_path') && nav_context_from_path($path) === 'cde';

        return [
            'result_context' => $isCde ? 'CDE' : 'Espacio Sutil',
            'result_type' => $this->typeLabel($postType),
            'result_excerpt' => $this->excerpt($post),
            'result_parent' => $postType === 'cde' ? $this->lessonSeries($post) : '',
        ];
    }

    protected function typeLabel(string $postType): string
    {
        $labels = [
            'area' => 'Área',
            'cde' => 'Lección',
            'event' => 'Actividad',
            'noticia' => 'Noticia',
            'page' => 'Página',
            'post' => 'Artículo',
            'practica' => 'Práctica',
            'serie' => 'Serie',
        ];

        if (isset($labels[$postType])) {
            return $labels[$postType];
        }

        $postTypeObject = get_post_type_object($postType);

        return $postTypeObject?->labels?->singular_name ?: 'Contenido';
    }

    protected function excerpt(?\WP_Post $post): string
    {
        if (!$post) {
            return '';
        }

        $source = $post->post_excerpt ?: $post->post_content;
        $source = strip_shortcodes((string) $source);
        $source = wp_strip_all_tags($source, true);

        return wp_trim_words($source, 30, '…');
    }

    protected function lessonSeries(?\WP_Post $post): string
    {
        if (!$post) {
            return '';
        }

        $ancestors = get_post_ancestors($post);
        if ($ancestors === []) {
            return '';
        }

        $seriesId = (int) end($ancestors);

        return $seriesId > 0 ? (string) get_the_title($seriesId) : '';
    }
}

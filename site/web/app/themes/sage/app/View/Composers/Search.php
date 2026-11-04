<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;

class Search extends Composer
{
    /** @var array<int, string> */
    protected static $views = [
        'search',
    ];

    /**
     * Data exposed to the search results page.
     *
     * @return array<string, int|string>
     */
    public function override(): array
    {
        global $wp_query;

        return [
            'search_query' => get_search_query(),
            'search_results_count' => isset($wp_query->found_posts) ? (int) $wp_query->found_posts : 0,
        ];
    }
}

<?php

$data = app()->bound('sage.data') && is_array(app('sage.data')) ? app('sage.data') : [];
$view = app()->bound('sage.view') ? app('sage.view') : null;

if (is_string($view) && $view !== '' && ! is_dir($view)) {
    echo \Roots\view($view, $data)->render();

    return;
}

$candidates = [];

if (is_singular()) {
    $templateSlug = get_page_template_slug();

    if (is_string($templateSlug) && $templateSlug !== '') {
        $templateView = preg_replace('/(\.blade)?\.php$/', '', basename($templateSlug));

        if (is_string($templateView) && $templateView !== '') {
            $candidates[] = $templateView;
        }
    }
}

if (is_front_page()) {
    $candidates[] = 'front-page';
}

if (is_home()) {
    $candidates[] = 'home';
}

if (is_search()) {
    $candidates[] = 'search';
}

if (is_404()) {
    $candidates[] = '404';
}

if (is_category()) {
    $category = get_queried_object();

    if (is_object($category) && ! empty($category->slug)) {
        $candidates[] = 'category-' . $category->slug;
    }

    $candidates[] = 'category';
}

if (is_tax()) {
    $taxonomy = get_query_var('taxonomy');

    if (is_string($taxonomy) && $taxonomy !== '') {
        $candidates[] = 'taxonomy-' . $taxonomy;
    }

    $candidates[] = 'taxonomy';
}

if (is_post_type_archive()) {
    $postType = get_query_var('post_type');

    if (is_array($postType)) {
        $postType = reset($postType);
    }

    if (is_string($postType) && $postType !== '') {
        $candidates[] = 'archive-' . $postType;
    }

    $candidates[] = 'archive';
}

if (is_singular()) {
    $postType = get_post_type();

    if (is_string($postType) && $postType !== '') {
        $candidates[] = 'single-' . $postType;
    }

    $candidates[] = is_page() ? 'page' : 'single';
}

$candidates[] = 'index';

foreach (array_values(array_unique(array_filter($candidates))) as $candidate) {
    if (\Roots\view()->exists($candidate)) {
        echo \Roots\view($candidate, $data)->render();

        return;
    }
}

if (is_string($view) && $view !== '' && is_file($view)) {
    echo \Roots\view($view, $data)->render();

    return;
}

wp_die(__('No se pudo resolver la plantilla del tema.', 'sage'));

<?php

function js_data()
{
    return [
        'ytKey' => env('YOUTUBE_API_KEY'),
    ];
}

/**
 * Resolve section context from a menu location hierarchy.
 *
 * A section is always the top-level ancestor menu item.
 *
 * @return array{key: string, color: string, menu_item_id: int, label: string}
 */
function navigation_section_context(string $menuLocation = 'primary_navigation'): array
{
    static $contextsByLocation = [];

    if (isset($contextsByLocation[$menuLocation]) && is_array($contextsByLocation[$menuLocation])) {
        return $contextsByLocation[$menuLocation];
    }

    $default = [
        'key' => 'home',
        'color' => '#000000',
        'menu_item_id' => 0,
        'label' => 'Inicio',
    ];

    $menuLocations = get_nav_menu_locations();
    $menuId = (int) ($menuLocations[$menuLocation] ?? 0);

    if (! $menuId) {
        $contextsByLocation[$menuLocation] = $default;

        return $contextsByLocation[$menuLocation];
    }

    $menuItems = wp_get_nav_menu_items($menuId, [
        'update_post_term_cache' => false,
    ]);

    if (! is_array($menuItems) || $menuItems === []) {
        $contextsByLocation[$menuLocation] = $default;

        return $contextsByLocation[$menuLocation];
    }

    $itemsById = [];
    foreach ($menuItems as $menuItem) {
        $itemsById[(int) $menuItem->ID] = $menuItem;
    }

    $currentPath = normalize_section_path((string) (wp_parse_url((string) wp_unslash($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/'));

    $bestMatch = null;
    $bestScore = -1;

    foreach ($menuItems as $menuItem) {
        $itemPath = menu_item_match_path($menuItem);

        if ($itemPath === null) {
            continue;
        }

        $isExact = $itemPath === $currentPath;
        $isPrefix = $itemPath !== '/' && str_starts_with($currentPath, $itemPath);
        $isHome = $itemPath === '/' && $currentPath === '/';

        if (! $isExact && ! $isPrefix && ! $isHome) {
            continue;
        }

        $score = strlen($itemPath) * 10;

        if ($isExact) {
            $score += 5;
        }

        if ((int) $menuItem->menu_item_parent === 0) {
            $score += 1;
        }

        if ($score > $bestScore) {
            $bestScore = $score;
            $bestMatch = $menuItem;
        }
    }

    if (! $bestMatch) {
        $queriedId = (int) get_queried_object_id();

        if ($queriedId > 0) {
            foreach ($menuItems as $menuItem) {
                if ((int) ($menuItem->object_id ?? 0) === $queriedId) {
                    $bestMatch = $menuItem;
                    break;
                }
            }
        }
    }

    if (! $bestMatch) {
        $contextsByLocation[$menuLocation] = $default;

        return $contextsByLocation[$menuLocation];
    }

    $topLevelItem = top_level_menu_item((int) $bestMatch->ID, $itemsById);

    if (! $topLevelItem) {
        $contextsByLocation[$menuLocation] = $default;

        return $contextsByLocation[$menuLocation];
    }

    $topLevelItemId = (int) $topLevelItem->ID;
    $color = get_field('menu_item_bg_color', $topLevelItemId);

    $contextsByLocation[$menuLocation] = [
        'key' => 'section-' . $topLevelItemId,
        'color' => is_string($color) && $color !== '' ? $color : '#000000',
        'menu_item_id' => $topLevelItemId,
        'label' => is_string($topLevelItem->title) ? $topLevelItem->title : '',
    ];

    return $contextsByLocation[$menuLocation];
}

/**
 * Resolve section context using legacy primary location.
 *
 * @return array{key: string, color: string, menu_item_id: int, label: string}
 */
function primary_navigation_section_context(): array
{
    return navigation_section_context('primary_navigation');
}

/**
 * Resolve section context using current active primary menu by context.
 *
 * @return array{key: string, color: string, menu_item_id: int, label: string}
 */
function current_navigation_section_context(): array
{
    $navContextData = nav_context_data();

    return navigation_section_context((string) ($navContextData['primary_menu_name'] ?? 'primary_navigation'));
}

/**
 * @param object $menuItem
 */
function menu_item_match_path(object $menuItem): ?string
{
    $url = trim((string) ($menuItem->url ?? ''));

    if ($url === '' || str_starts_with($url, '#')) {
        return null;
    }

    $itemHost = wp_parse_url($url, PHP_URL_HOST);
    $siteHost = wp_parse_url(home_url('/'), PHP_URL_HOST);

    if (
        is_string($itemHost)
        && is_string($siteHost)
        && $itemHost !== ''
        && strcasecmp($itemHost, $siteHost) !== 0
    ) {
        return null;
    }

    $path = wp_parse_url($url, PHP_URL_PATH);

    if (! is_string($path) || $path === '') {
        return '/';
    }

    return normalize_section_path($path);
}

/**
 * @param array<int, object> $itemsById
 */
function top_level_menu_item(int $menuItemId, array $itemsById): ?object
{
    if (! isset($itemsById[$menuItemId])) {
        return null;
    }

    $current = $itemsById[$menuItemId];
    $safety = 0;

    while ((int) $current->menu_item_parent !== 0 && $safety < 50) {
        $parentId = (int) $current->menu_item_parent;

        if (! isset($itemsById[$parentId])) {
            break;
        }

        $current = $itemsById[$parentId];
        $safety++;
    }

    return $current;
}

function normalize_section_path(string $path): string
{
    $path = trim($path);

    if ($path === '' || $path === '/') {
        return '/';
    }

    $normalized = '/' . trim($path, '/');

    return $normalized . '/';
}

/**
 * Resolve navigation context for the current request.
 *
 * @return 'es'|'cde'
 */
function current_nav_context(): string
{
    static $context = null;

    if (is_string($context)) {
        return $context;
    }

    if (
        is_singular('cde')
        || is_post_type_archive('cde')
        || is_tax(['serie_cde', 'nivel_cde'])
        || is_page_template('template-curso.blade.php')
    ) {
        $context = 'cde';

        return $context;
    }

    $requestPath = (string) (wp_parse_url((string) wp_unslash($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/');

    $context = nav_context_from_path($requestPath);

    return $context;
}

/**
 * Resolve navigation context from a URL path.
 *
 * @return 'es'|'cde'
 */
function nav_context_from_path(string $path): string
{
    $normalizedPath = normalize_section_path($path);

    $cdePrefixes = [
        '/curso-de-desarrollo-espiritual/',
        '/indice-de-lecciones/',
        '/suscripciones/',
        '/el-curso-en-profundidad/',
        '/bases-de-colaboracion/',
        '/login/',
        '/cuenta-de-membresia/',
        '/pago-de-membresia/',
        '/confirmacion-de-membresia/',
    ];

    foreach ($cdePrefixes as $prefix) {
        if ($normalizedPath === $prefix || str_starts_with($normalizedPath, $prefix)) {
            return 'cde';
        }
    }

    return 'es';
}

/**
 * Check whether a path should bypass Barba transitions.
 */
function is_barba_sensitive_path(string $path): bool
{
    $normalizedPath = normalize_section_path($path);

    $sensitivePrefixes = [
        '/login/',
        '/cuenta-de-membresia/',
        '/pago-de-membresia/',
        '/confirmacion-de-membresia/',
        '/suscripciones/',
        '/wp/wp-login.php/',
        '/wp/wp-admin/',
    ];

    foreach ($sensitivePrefixes as $prefix) {
        if ($normalizedPath === $prefix || str_starts_with($normalizedPath, $prefix)) {
            return true;
        }
    }

    return false;
}

/**
 * Determine if a URL should prevent Barba navigation.
 *
 * Rules:
 * - Always prevent on auth/membership sensitive routes.
 * - Prevent on cross-context navigation (ES <-> CDE).
 */
function should_prevent_barba_for_url(?string $url): bool
{
    if (! is_string($url)) {
        return false;
    }

    $trimmedUrl = trim($url);

    if (
        $trimmedUrl === ''
        || str_starts_with($trimmedUrl, '#')
        || str_starts_with($trimmedUrl, 'mailto:')
        || str_starts_with($trimmedUrl, 'tel:')
        || str_starts_with($trimmedUrl, 'javascript:')
    ) {
        return false;
    }

    $targetHost = wp_parse_url($trimmedUrl, PHP_URL_HOST);
    $siteHost = wp_parse_url(home_url('/'), PHP_URL_HOST);

    if (
        is_string($targetHost)
        && $targetHost !== ''
        && is_string($siteHost)
        && $siteHost !== ''
        && strcasecmp($targetHost, $siteHost) !== 0
    ) {
        return false;
    }

    $targetPath = wp_parse_url($trimmedUrl, PHP_URL_PATH);

    if (! is_string($targetPath) || $targetPath === '') {
        return false;
    }

    if (is_barba_sensitive_path($targetPath)) {
        return true;
    }

    return nav_context_from_path($targetPath) !== current_nav_context();
}

/**
 * Shared context data for navigation rendering.
 *
 * @return array{
 *   nav_context: 'es'|'cde',
 *   primary_menu_name: string,
 *   switch_target_url: string,
 *   switch_target_label: string,
 *   context_cross_link_url: string,
 *   context_cross_link_label: string,
 *   is_pmpro_page: bool,
 *   show_cde_hero_nav: bool
 * }
 */
function nav_context_data(): array
{
    static $contextData = null;

    if (is_array($contextData)) {
        return $contextData;
    }

    $context = current_nav_context();
    $esHomeUrl = home_url('/');

    $courseHubPage = get_page_by_path('curso-de-desarrollo-espiritual');
    $cdeHomeUrl = $courseHubPage ? get_permalink($courseHubPage) : '';
    if (! is_string($cdeHomeUrl) || $cdeHomeUrl === '') {
        $cdeHomeUrl = home_url('/curso-de-desarrollo-espiritual/');
    }

    $switchTargetUrl = $context === 'cde' ? $esHomeUrl : $cdeHomeUrl;
    $switchTargetLabel = $context === 'cde'
        ? __('Espacio Sutil', 'sage')
        : __('Curso Desarrollo Espiritual', 'sage');

    $queriedPageId = (int) get_queried_object_id();
    $isPmproPage = function_exists('\App\is_pmpro_core_page')
        ? \App\is_pmpro_core_page($queriedPageId)
        : false;

    $contextData = [
        'nav_context' => $context,
        'primary_menu_name' => $context === 'cde' ? 'cde_navigation' : 'primary_navigation',
        'switch_target_url' => $switchTargetUrl,
        'switch_target_label' => $switchTargetLabel,
        'context_cross_link_url' => $switchTargetUrl,
        'context_cross_link_label' => $switchTargetLabel,
        'is_pmpro_page' => $isPmproPage,
        'show_cde_hero_nav' => should_show_cde_hero_nav(),
    ];

    return $contextData;
}

/**
 * Normalize menu item classes into a flat array of class names.
 *
 * @return array<int, string>
 */
function nav_item_classes(object $item): array
{
    $classes = $item->classes ?? [];

    if (is_string($classes)) {
        $classes = preg_split('/\s+/', trim($classes)) ?: [];
    }

    if (! is_array($classes)) {
        return [];
    }

    return array_values(array_filter(array_map(static fn ($value) => trim((string) $value), $classes)));
}

/**
 * Evaluate whether a menu item should be rendered based on auth/context rules.
 */
function should_render_navigation_item(object $item): bool
{
    $classes = nav_item_classes($item);
    $classMap = array_fill_keys($classes, true);

    $isLoggedIn = is_user_logged_in();
    $hasMembership = function_exists('pmpro_hasMembershipLevel')
        ? (bool) pmpro_hasMembershipLevel()
        : false;

    if (isset($classMap['show-logged-in']) && ! $isLoggedIn) {
        return false;
    }

    if (isset($classMap['show-logged-out']) && $isLoggedIn) {
        return false;
    }

    if (isset($classMap['show-has-membership']) && ! ($isLoggedIn && $hasMembership)) {
        return false;
    }

    if (isset($classMap['hide-logged-in']) && $isLoggedIn) {
        return false;
    }

    if (isset($classMap['hide-logged-out']) && ! $isLoggedIn) {
        return false;
    }

    if (isset($classMap['hide-without-membership']) && ! $hasMembership) {
        return false;
    }

    $url = trim((string) ($item->url ?? ''));
    $path = wp_parse_url($url, PHP_URL_PATH);
    if (! is_string($path) || $path === '') {
        return true;
    }

    $normalizedPath = normalize_section_path($path);

    if ($normalizedPath === '/login/' || str_starts_with($normalizedPath, '/login/')) {
        return ! $isLoggedIn;
    }

    if (str_starts_with($normalizedPath, '/cuenta-de-membresia/cancelacion-de-membresia/')) {
        return $isLoggedIn && $hasMembership;
    }

    if (str_starts_with($normalizedPath, '/cuenta-de-membresia/')) {
        return $isLoggedIn;
    }

    return true;
}

/**
 * Determine whether the CDE hero navigation should be rendered.
 */
function should_show_cde_hero_nav(): bool
{
    if (current_nav_context() !== 'cde' || ! is_page()) {
        return false;
    }

    $queriedPageId = (int) get_queried_object_id();
    $isPmproPage = function_exists('\App\is_pmpro_core_page')
        ? \App\is_pmpro_core_page($queriedPageId)
        : false;

    if ($isPmproPage) {
        return false;
    }

    $requestPath = (string) (wp_parse_url((string) wp_unslash($_SERVER['REQUEST_URI'] ?? '/'), PHP_URL_PATH) ?? '/');
    $normalizedPath = normalize_section_path($requestPath);

    $heroPaths = [
        '/curso-de-desarrollo-espiritual/',
        '/indice-de-lecciones/',
        '/suscripciones/',
        '/el-curso-en-profundidad/',
        '/bases-de-colaboracion/',
    ];

    return in_array($normalizedPath, $heroPaths, true);
}

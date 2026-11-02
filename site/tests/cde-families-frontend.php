<?php

// Isolated frontend contract: no WordPress bootstrap, database or writes.
require __DIR__ . '/../web/app/themes/sage/app/helpers.php';

$count = 0;
function check($condition, string $message): void
{
    global $count;
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $count++;
}

check(cde_lesson_family_presentation(2389) === null, 'Missing plugin has no badge');

// Defined after the missing-plugin assertion, without autoloading WordPress.
eval('namespace EspacioSutil\\Cde; class Families {
    public static mixed $result = null;
    public static array $calls = [];
    public static function resolveLesson(...$args) {
        self::$calls[] = $args;
        return self::$result;
    }
}');

$expected = [
    'espirituales-esenciales' => ['Serie esencial', 'mercaba', '--color-serie-esencial'],
    'otras-principales' => ['Serie principal', 'torus', '--color-serie-principal'],
    'otras-espirituales' => ['Serie espiritual', 'triangle-spiral', '--color-serie-espiritual'],
    'informacion-complementaria' => ['Serie complementaria', 'cubes', '--color-serie-complementaria'],
];
foreach ($expected as $slug => [$label, $icon, $token]) {
    \EspacioSutil\Cde\Families::$result = [
        'familia_serie' => $slug, 'label' => 'Backend label is not frontend copy',
        'source_post_id' => 2387, 'inherited' => true,
    ];
    check(cde_lesson_family_presentation(2389) === [
        'slug' => $slug, 'label' => $label, 'icon' => 'cdefamily-' . $icon, 'color_token' => $token,
    ], 'Exact frontend mapping for ' . $slug);
    check(end(\EspacioSutil\Cde\Families::$calls) === [2389], 'Delegate saved lesson inheritance without proposed data');
}
foreach ([null, new stdClass(), [], ['familia_serie' => null], ['familia_serie' => []], ['familia_serie' => 'unknown']] as $invalid) {
    \EspacioSutil\Cde\Families::$result = $invalid;
    check(cde_lesson_family_presentation(2389) === null, 'Invalid resolution has no badge');
}
check(cde_lesson_family_presentation(0) === null, 'No inferred lesson');

// Minimal menu fixture intentionally has a different color from the CDE fallback.
function is_singular($type) { return $GLOBALS['lesson'] ?? true; }
function home_url($path) { return 'https://example.test' . $path; }
function get_page_by_path($path) { return null; }
function __($value, $domain) { return $value; }
function get_queried_object_id() { return 2389; }
function is_front_page() { return false; }
function is_page($page = null) { return false; }
function get_nav_menu_locations() { return ['cde_navigation' => 1]; }
function wp_get_nav_menu_items($id, $args) { return [(object) ['ID' => 7, 'url' => home_url('/'), 'menu_item_parent' => 0, 'title' => 'Menu', 'menu_order' => 0]]; }
function wp_parse_url($url, $part) { return parse_url($url, $part); }
function wp_unslash($value) { return $value; }
function get_field($key, $id) { return '#123456'; }

$context = current_navigation_section_context();
check($context['color'] === 'var(--color-cde)', 'Unknown family overrides unrelated menu color with CDE fallback');
check($context['menu_item_id'] === 7, 'Menu identity remains unchanged');
foreach ($expected as $slug => [$label, $icon, $token]) {
    \EspacioSutil\Cde\Families::$result = ['familia_serie' => $slug];
    check(current_navigation_section_context()['color'] === "var($token)", 'Persistent token for ' . $slug);
}
$GLOBALS['lesson'] = false;
check(current_navigation_section_context()['color'] === '#123456', 'Non-lesson page retains menu color');

$config = require __DIR__ . '/../web/app/themes/sage/config/blade-icons.php';
check($config['sets']['cde-family']['prefix'] === 'cdefamily', 'Custom icon prefix');
check($config['sets']['cde-family']['path'] === 'resources/images/icons/cde-families', 'Custom icon directory');
// Exercise the installed Blade Icons factory without booting Acorn/WordPress.
require __DIR__ . '/../vendor/autoload.php';
$filesystem = new \Illuminate\Filesystem\Filesystem();
$manifest = new \BladeUI\Icons\IconsManifest($filesystem, __DIR__ . '/nonexistent-icons-manifest.php');
$factory = new \BladeUI\Icons\Factory($filesystem, $manifest);
$set = $config['sets']['cde-family'];
$set['path'] = __DIR__ . '/../web/app/themes/sage/' . $set['path'];
$factory->add('cde-family', $set);
foreach ($expected as [$label, $icon]) {
    $html = $factory->svg('cdefamily-' . $icon, 'lesson-family-badge__icon', [
        'aria-hidden' => 'true', 'focusable' => 'false',
    ])->toHtml();
    check(str_contains($html, '<svg') && str_contains($html, 'currentColor'), 'Native SVG render: ' . $icon);
    check(str_contains($html, 'aria-hidden="true"') && str_contains($html, 'focusable="false"'), 'Decorative icon accessibility: ' . $icon);
}
// Containment: compile and render the actual partial for every family without WP or disk writes.
$partial = file_get_contents(__DIR__ . '/../web/app/themes/sage/resources/views/partials/lesson-family-badge.blade.php');
$compiler = new \Illuminate\View\Compilers\BladeCompiler($filesystem, sys_get_temp_dir());
$compiled = $compiler->compileString($partial);
foreach ($expected as $slug => [$label, $icon, $token]) {
    $family = ['slug' => $slug, 'label' => $label, 'icon' => 'cdefamily-' . $icon, 'color_token' => $token];
    ob_start();
    eval('?>' . $compiled);
    $html = ob_get_clean();
    check(str_contains($html, 'data-lesson-family="' . $slug . '"'), 'Containment preserves family identity: ' . $slug);
    check(str_contains($html, $label), 'Containment preserves family label: ' . $slug);
    check(!preg_match('/<(?:svg|img|x-icon)\b/i', $html), 'Unverified family symbols are not rendered: ' . $slug);
}
echo "$count frontend checks passed.\n";

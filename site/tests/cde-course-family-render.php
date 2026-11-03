<?php

declare(strict_types=1);

// Actual installed Illuminate Blade + Blade Icons; no WordPress, DB or network.
// Only dedicated compiled-view cache and exported visual fixtures are written.
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../web/app/themes/sage/vendor/autoload.php';
require __DIR__ . '/../web/app/themes/sage/vendor/roots/acorn/src/Illuminate/Foundation/helpers.php';

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\FileViewFinder;
use Illuminate\View\Factory;
use BladeUI\Icons\Factory as IconFactory;

// Minimal WordPress context for the real page template, never a WP bootstrap.
$GLOBALS['cde_fixture_loop'] = 0;
$GLOBALS['cde_fixture_logged_in'] = false;
$completionOnlyExport = in_array('--completions-only-export', $argv, true);
function have_posts(): bool { return $GLOBALS['cde_fixture_loop'] === 0; }
function the_post(): void { $GLOBALS['cde_fixture_loop']++; }
function get_the_content(): string { return ''; }
function get_the_title(): string { return 'Índice de lecciones'; }
function wp_strip_all_tags(string $value): string { return strip_tags($value); }
function wp_specialchars_decode(string $value, int $flags = ENT_NOQUOTES): string { return htmlspecialchars_decode($value, $flags); }
function is_user_logged_in(): bool { return $GLOBALS['cde_fixture_logged_in']; }

$count = 0;
function check(bool $condition, string $message): void
{
    global $count;
    if (!$condition) { throw new RuntimeException($message); }
    $count++;
}
function dom(string $html): DOMXPath
{
    $doc = new DOMDocument();
    $previous = libxml_use_internal_errors(true);
    $doc->loadHTML('<?xml encoding="UTF-8"><html><body>' . $html . '</body></html>');
    libxml_clear_errors();
    libxml_use_internal_errors($previous);
    return new DOMXPath($doc);
}
function elements(DOMXPath $xp, string $class): DOMNodeList
{
    return $xp->query('//*[contains(concat(" ", normalize-space(@class), " "), " ' . $class . ' ")]');
}
function paths(DOMXPath $xp): array
{
    return array_map(static fn (DOMElement $node): string => $node->getAttribute('d'), iterator_to_array($xp->query('//svg//path')));
}
function referencesAndIds(DOMXPath $xp, string $context): void
{
    $ids = [];
    foreach ($xp->query('//*[@id]') as $element) { $ids[] = $element->getAttribute('id'); }
    check(count($ids) === count(array_unique($ids)), "$context: IDs unique");
    foreach ($xp->query('//*[@aria-controls or @aria-labelledby]') as $element) {
        foreach (['aria-controls', 'aria-labelledby'] as $attribute) {
            if (!$element->hasAttribute($attribute)) { continue; }
            foreach (explode(' ', $element->getAttribute($attribute)) as $reference) {
                check(count(array_keys($ids, $reference, true)) === 1, "$context: $attribute resolves exactly once");
            }
        }
    }
}
function series(int $id, string $name, array $blockNames): array
{
    $blocks = [];
    foreach ($blockNames as $index => $label) {
        $blocks[] = ['term_id' => $id * 100 + $index, 'post_id' => 900000 + $id * 100 + $index, 'name' => $label, 'lessons_count' => 0];
    }
    return ['term_id' => $id, 'name' => $name, 'slug' => 'fixture-' . $id, 'blocks' => $blocks];
}

$theme = realpath(__DIR__ . '/../web/app/themes/sage');
$tmp = realpath(__DIR__ . '/../tmp');
if ($theme === false || $tmp === false) { throw new RuntimeException('Expected project directories unavailable'); }
$cache = $tmp . '/cde-course-render-cache-' . getmypid() . '-' . bin2hex(random_bytes(4));
if (!mkdir($cache, 0700)) { throw new RuntimeException('Cannot create dedicated cache'); }
$container = new Container();
Container::setInstance($container);
Illuminate\Support\Facades\Facade::setFacadeApplication($container);
$container->instance('config', new Illuminate\Config\Repository(['view' => ['compiled' => $cache]]));
$files = new Filesystem();
$blade = new BladeCompiler($files, $cache);
$container->instance('blade.compiler', $blade);
$engines = new EngineResolver();
$engines->register('blade', static fn () => new CompilerEngine($blade, $files));
$overrides = $cache . '/fixture-views';
if (!mkdir($overrides . '/layouts', 0700, true)) { throw new RuntimeException('Cannot create isolated layout directory'); }
if (file_put_contents($overrides . '/layouts/app.blade.php', "@yield('content')\n") === false) { throw new RuntimeException('Cannot create isolated minimal layout'); }
$finder = new FileViewFinder($files, [$overrides, $theme . '/resources/views']);
$views = new Factory($engines, $finder, new Illuminate\Events\Dispatcher($container));
$views->setContainer($container);
$views->share('__env', $views);
$container->instance('view', $views);
$container->instance(Illuminate\Contracts\View\Factory::class, $views);
$icons = new IconFactory($files, new BladeUI\Icons\IconsManifest($files, $cache . '/icons-manifest.php'));
$themeIcons = require $theme . '/config/blade-icons.php';
$familySet = $themeIcons['sets']['cde-family'];
$familySet['path'] = $theme . '/' . $familySet['path'];
$icons->add('cde-family', $familySet);
$container->instance(IconFactory::class, $icons);
$blade->component(BladeUI\Icons\Components\Icon::class, 'icon');
// The logged-in fixture also renders native installed Coolicons, without WP.
$coolicons = require __DIR__ . '/../vendor/codeat3/blade-coolicons/config/blade-coolicons.php';
$coolicons['path'] = __DIR__ . '/../vendor/codeat3/blade-coolicons/resources/svg';
$icons->add('coolicons', $coolicons);
foreach (['show', 'hide'] as $name) {
    $blade->component(BladeUI\Icons\Components\Svg::class, $name, $coolicons['prefix']);
}

// Explicit visual fixtures only. These are NOT assignments or production inventory.
$groups = [
    ['key' => 'espirituales-esenciales', 'label' => 'Series esenciales', 'icon' => 'cdefamily-mercaba', 'color_token' => '--color-serie-esencial', 'series' => [
        series(1, 'El libro de Urantia', ['Introducción (ejemplo)', 'Conceptos fundamentales (ejemplo)']),
        series(2, 'Seth', ['Introducción a Seth (ejemplo)']),
    ]],
    ['key' => 'otras-principales', 'label' => 'Series principales', 'icon' => 'cdefamily-torus', 'color_token' => '--color-serie-principal', 'series' => [
        series(3, 'El material de Jeshua', ['Presentación (ejemplo)']),
    ]],
    ['key' => 'otras-espirituales', 'label' => 'Series espirituales', 'icon' => 'cdefamily-triangle-spiral', 'color_token' => '--color-serie-espiritual', 'series' => [
        series(4, 'Gerardo Schmedling', ['Presentación (ejemplo)']),
    ]],
    ['key' => 'informacion-complementaria', 'label' => 'Series complementarias', 'icon' => 'cdefamily-cubes', 'color_token' => '--color-serie-complementaria', 'series' => [
        series(5, 'Información taygeteana — SWARUU', ['Introducción (ejemplo)']),
    ]],
    ['key' => 'unassigned', 'label' => 'Sin familia asignada', 'icon' => null, 'color_token' => '--color-cde', 'series' => [
        series(6, 'Guía de estudio', []),
    ]],
];
$sections = [];
foreach ($groups as $group) {
    $key = $group['key'];
    $html = $views->make('partials.course-family-section', ['group' => $group])->render();
    $xp = dom($html);
    $section = elements($xp, 'course-family')->item(0);
    check($section instanceof DOMElement && $section->getAttribute('data-course-family') === $key, "$key: family identity retained");
    check($section->getAttribute('style') === '--course-family-bg: var(' . $group['color_token'] . ')', "$key: exact family background token");
    check($xp->query('//h2')->item(0)->textContent === $group['label'], "$key: collection label visible");
    check(elements($xp, 'course-family__icon')->length === (int) ($group['icon'] !== null), "$key: icon only when supplied");
    if ($group['icon'] !== null) {
        $svg = $xp->query('//svg')->item(0);
        check($svg instanceof DOMElement && $svg->getAttribute('aria-hidden') === 'true' && $svg->getAttribute('focusable') === 'false', "$key: decorative accessible SVG");
        $expected = dom($icons->svg($group['icon'])->toHtml());
        check(paths($xp) !== [] && paths($xp) === paths($expected), "$key: exact native family SVG geometry");
    }
    check($xp->query('//nav')->length === 1, "$key: scoped series navigation");
    $toggles = elements($xp, 'serie-accordion-toggle');
    check($toggles->length === count($group['series']), "$key: every series rendered once");
    foreach ($toggles as $index => $toggle) {
        check($toggle->getAttribute('type') === 'button' && $toggle->getAttribute('aria-expanded') === 'false', "$key: collapsed series is real button");
        check($toggle->getAttribute('data-series-term') === (string) $group['series'][$index]['term_id'], "$key: series order and term ID retained");
        $panel = $xp->query('//*[@id="' . $toggle->getAttribute('aria-controls') . '"]')->item(0);
        check($panel instanceof DOMElement && $panel->getAttribute('aria-labelledby') === $toggle->getAttribute('id'), "$key: toggle/panel reciprocal ARIA");
        check(str_contains(' ' . $panel->getAttribute('class') . ' ', ' hidden '), "$key: panel initially hidden");
    }
    $topics = $xp->query('//*[@data-course-topics]');
    check($topics->length === 1 && $topics->item(0)->getAttribute('id') === 'topics-' . $key, "$key: dedicated topics target");
    check($topics->item(0)->getAttribute('aria-live') === 'polite' && $topics->item(0)->getAttribute('role') === 'region', "$key: dynamic topics announced politely");
    $blocks = array_merge(...array_column($group['series'], 'blocks'));
    $blockButtons = elements($xp, 'serie-cde-button');
    check($blockButtons->length === count($blocks), "$key: all blocks preserved");
    foreach ($blockButtons as $index => $button) {
        check($button->getAttribute('aria-controls') === 'topics-' . $key, "$key: block controls only its family's topics");
        check($button->getAttribute('data-post-id') === (string) $blocks[$index]['post_id'] && $button->getAttribute('data-block-term') === (string) $blocks[$index]['term_id'], "$key: block IDs/order retained");
        check(trim($button->textContent) === $blocks[$index]['name'], "$key: block text retained");
    }
    if ($blocks === []) { check(elements($xp, 'course-family__empty')->length === 1, "$key: empty series has honest empty state"); }
    referencesAndIds($xp, $key);
    $sections[] = $html;
}
$combined = implode("\n", $sections);
$all = dom($combined);
check($all->query('//section')->length === 5 && $all->query('//svg')->length === 4, 'Four symbols and one neutral iconless band');
referencesAndIds($all, 'All bands together');

$xss = $groups[0];
$xss['series'] = [series(20, '<img src=x onerror="alert(1)"> & «Serie»', ['<script>alert(2)</script> & «Bloque»'])];
$xssHtml = $views->make('partials.course-family-section', ['group' => $xss])->render();
$xssDom = dom($xssHtml);
check($xssDom->query('//img | //script | //@onerror')->length === 0, 'Series/block text cannot create executable elements');
check($xssDom->query('//*[@data-series-title]')->item(0)->textContent === $xss['series'][0]['name'], 'Series XSS escaped without losing literal text');
check(trim(elements($xssDom, 'serie-cde-button')->item(0)->textContent) === $xss['series'][0]['blocks'][0]['name'], 'Block XSS escaped without losing literal text');
check(str_contains($xssHtml, '&lt;img') && str_contains($xssHtml, '&lt;script'), 'Actual Blade escape for both names');

$duplicates = $groups[0];
$missing = series(3, 'Missing ID fixture', []);
unset($missing['term_id']);
$duplicates['series'] = [series(1, 'First', []), series(1, 'Repeated ID', []), $missing, series(2, 'Would collide with missing index', [])];
referencesAndIds(dom($views->make('partials.course-family-section', ['group' => $duplicates])->render()), 'Duplicate and missing term IDs');

$fixtureDir = $tmp . '/cde-index-design';
if (!is_dir($fixtureDir) && !mkdir($fixtureDir, 0755)) { throw new RuntimeException('Cannot create fixture directory'); }
$fixtureFile = $fixtureDir . '/family-fixtures.html';
$export = "<!-- VISUAL FIXTURES ONLY: actual Blade rendering; not production assignments or inventory. -->\n" . $combined;
if (!$completionOnlyExport && file_put_contents($fixtureFile, $export) === false) { throw new RuntimeException('Cannot export visual fixtures'); }
$GLOBALS['cde_fixture_loop'] = 0;
$fullHtml = $views->make('template-curso', ['course_family_groups' => $groups])->render();
$fullDom = dom($fullHtml);
check($fullDom->query('//h1')->length === 1 && $fullDom->query('//h1')->item(0)->textContent === 'Índice de lecciones', 'Full real template renders page title without a composer title variable');
check($fullDom->query('//section[@data-course-family]')->length === 5, 'Full real template includes all five family groups');
check($fullDom->query('//*[@id="contenido"]')->length === 1 && elements($fullDom, 'course-index-hero')->length === 1, 'Full real template preserves hero and content container');
$fullFile = $fixtureDir . '/full-index-fixture.html';
if (!$completionOnlyExport && file_put_contents($fullFile, "<!-- VISUAL FIXTURE ONLY: real template-curso with minimal isolated layout; no live WordPress data. -->\n" . $fullHtml) === false) { throw new RuntimeException('Cannot export full index fixture'); }

$items = [(object) [
    'id' => 990001, 'title' => 'Tema de ejemplo: introducción', 'permalink' => '#fixture-lesson-990001', 'active' => true,
    'children' => [
        (object) ['id' => 990002, 'title' => 'Lección anidada de ejemplo', 'permalink' => '#fixture-lesson-990002', 'active' => true, 'children' => []],
        (object) ['id' => 990003, 'title' => 'Lección no disponible de ejemplo', 'permalink' => '#fixture-lesson-990003', 'active' => false, 'children' => []],
    ],
]];
$ajaxHtml = $views->make('partials.course-index-item', ['items' => $items, 'completed_lessons' => []])->render();
$ajaxDom = dom($ajaxHtml);
check(elements($ajaxDom, 'course-index-item')->length === 3, 'Real AJAX partial renders three example items');
check($ajaxDom->query('//a')->length === 2 && elements($ajaxDom, 'course-index-inactive')->length === 1, 'Active and inactive AJAX example rows retain their semantics');
check($ajaxDom->query('//*[@data-children-wrapper]')->length === 1 && $ajaxDom->query('//*[@data-toggle-children]')->length === 1, 'Real AJAX partial retains nested wrapper and toggle');
check($ajaxDom->query('//*[@data-lesson-completion]')->length === 0, 'Anonymous AJAX fixture cannot expose completion writes');
$ajaxFile = $fixtureDir . '/ajax-fixture.html';
if (!$completionOnlyExport && file_put_contents($ajaxFile, "<!-- VISUAL FIXTURE ONLY: real course-index-item; example IDs and fragment-only links; anonymous context. -->\n" . $ajaxHtml) === false) { throw new RuntimeException('Cannot export AJAX fixture'); }
$GLOBALS['cde_fixture_logged_in'] = true;
$completedIds = [990001];
$completionHtml = $views->make('partials.course-index-item', ['items' => $items, 'completed_lessons' => $completedIds])->render();
$GLOBALS['cde_fixture_logged_in'] = false;
$completionDom = dom($completionHtml);
$completionButtons = $completionDom->query('//*[@data-lesson-completion]');
check($completionButtons->length === 2, 'Logged-in fixture renders controls only for active lessons');
check($completionDom->query('//*[@data-post-id="990003"]')->length === 0, 'Inactive lesson never gets a completion control');
check(elements($completionDom, 'course-index-item')->length === 3 && elements($completionDom, 'course-index-inactive')->length === 1, 'Logged-in fixture retains active and inactive nested tree');
foreach ($completionButtons as $button) {
    $id = (int) $button->getAttribute('data-post-id');
    $done = in_array($id, $completedIds, true);
    check($button->getAttribute('type') === 'button', "Completion $id is a native button");
    check($button->getAttribute('data-completed') === ($done ? 'true' : 'false'), "Completion $id preserves data state");
    check($button->getAttribute('aria-pressed') === ($done ? 'true' : 'false'), "Completion $id preserves ARIA state");
    check($button->getAttribute('aria-label') === ($done ? 'Marcar como no vista' : 'Marcar como vista') . ': ' . $button->getAttribute('data-lesson-title'), "Completion $id has the correct accessible action");
    check(trim($button->getAttribute('class')) === 'course-index-completion' . ($done ? ' completed' : ''), "Completion $id uses only semantic CSS state, no duplicate utilities");
    foreach (['show', 'hide'] as $name) {
        $matches = $completionDom->query('.//svg[contains(concat(" ", normalize-space(@class), " "), " icon-' . $name . ' ")]', $button);
        check($matches->length === 1, "Completion $id contains native $name SVG");
        $svg = $matches->item(0);
        check($svg->getAttribute('aria-hidden') === 'true' && trim($svg->getAttribute('class')) === 'icon-' . $name, "Completion $id $name icon has semantic class without size/hidden utilities");
        $actual = dom($svg->ownerDocument->saveHTML($svg));
        $expected = dom($icons->svg($coolicons['prefix'] . '-' . $name)->toHtml());
        check(paths($actual) !== [] && paths($actual) === paths($expected), "Completion $id $name geometry matches the installed Coolicon");
    }
}
foreach ($completionDom->query('//*[@class]') as $element) {
    $classes = preg_split('/\s+/', trim($element->getAttribute('class')));
    check(count(array_filter($classes, static fn (string $class): bool => str_starts_with($class, 'course-index-') || in_array($class, ['completed', 'icon-show', 'icon-hide'], true))) === count($classes), 'AJAX tree classes are semantic, without duplicate Tailwind styling');
}
$completionFile = $fixtureDir . '/ajax-completions-fixture.html';
if (file_put_contents($completionFile, "<!-- VISUAL FIXTURE ONLY: logged-in context is a stub; example IDs/fragment links; no nonce or live writes. -->\n" . $completionHtml) === false) { throw new RuntimeException('Cannot export completion fixture'); }
echo "$count real Blade course-family checks passed; no WordPress/DB/network.\nCompletion fixture: $completionFile\nDedicated cache: $cache\n";
echo $completionOnlyExport ? "Other fixture exports preserved (--completions-only-export).\n" : "Fixtures: $fixtureFile\nFull index: $fullFile\nAJAX: $ajaxFile\n";

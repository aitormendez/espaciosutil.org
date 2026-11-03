<?php

declare(strict_types=1);

// Real Illuminate Blade + Blade Icons, with only read-only WP context functions stubbed.
// No WordPress boot, DB or network. Compiled views live in this test's dedicated cache.
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../web/app/themes/sage/vendor/autoload.php';
require __DIR__ . '/../web/app/themes/sage/vendor/roots/acorn/src/Illuminate/Foundation/helpers.php';
require __DIR__ . '/../web/app/themes/sage/app/Support/CdeProductionType.php';

use Illuminate\Container\Container;
use Illuminate\Filesystem\Filesystem;
use Illuminate\View\Compilers\BladeCompiler;
use Illuminate\View\Engines\CompilerEngine;
use Illuminate\View\Engines\EngineResolver;
use Illuminate\View\FileViewFinder;
use Illuminate\View\Factory;
use BladeUI\Icons\Factory as IconFactory;

function is_singular($type = null): bool { return $type === 'cde'; }
function is_tax($taxonomy = null): bool { return false; }
function get_post_type($post = null): string { return 'cde'; }
function is_user_logged_in(): bool { return false; }

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

$theme = realpath(__DIR__ . '/../web/app/themes/sage');
$tmp = realpath(__DIR__ . '/../tmp');
if ($theme === false || $tmp === false) { throw new RuntimeException('Expected project directories unavailable'); }
$cache = $tmp . '/cde-header-render-cache-' . getmypid() . '-' . bin2hex(random_bytes(4));
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
$finder = new FileViewFinder($files, [$theme . '/resources/views']);
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
$tabler = require $theme . '/config/blade-tabler-icons.php';
$icons->add('tabler', ['prefix' => $tabler['prefix'], 'path' => __DIR__ . '/../vendor/secondnetwork/blade-tabler-icons/resources/svg']);
$container->instance(IconFactory::class, $icons);
$blade->component(BladeUI\Icons\Components\Icon::class, 'icon');
// Real aliases for components compiled in the anonymous-user hidden branch.
$blade->component(BladeUI\Icons\Components\Svg::class, 'coolicon-show');
$blade->component(BladeUI\Icons\Components\Svg::class, 'coolicon-hide');

$family = ['slug' => 'espirituales-esenciales', 'label' => 'Serie esencial', 'icon' => 'cdefamily-mercaba', 'color_token' => '--color-serie-esencial'];
$synthetic = ['value' => 'audio_sintético', 'label' => 'Voz sintética', 'icon' => 'tabler-robot'];
$human = ['value' => 'video_grabado', 'label' => 'Voz humana', 'icon' => 'tabler-robot-off'];
$base = ['family' => $family, 'series_name' => 'El libro de Urantia', 'production' => $human];
$cases = [
    'humano' => $base,
    'sintetico' => array_replace($base, ['production' => $synthetic]),
    'sin_dato' => ['family' => null, 'series_name' => null, 'production' => null],
    'solo_produccion' => ['family' => null, 'series_name' => null, 'production' => $synthetic],
    'solo_familia' => array_replace($base, ['production' => null]),
    'solo_serie' => ['family' => null, 'series_name' => 'Serie con raíz válida', 'production' => null],
    'familia_sin_nombre' => array_replace($base, ['series_name' => null, 'production' => null]),
    'largo' => array_replace($base, ['series_name' => 'El material de Ra — La Ley del Uno: una introducción extensa a la conciencia, el universo y la evolución espiritual']),
    'xss' => array_replace($base, ['series_name' => '<img src=x onerror="alert(1)"> & «Serie»']),
];
$headers = [];
foreach ($cases as $name => $metadata) {
    $html = $views->make('partials.lesson-metadata', ['metadata' => $metadata])->render();
    $xp = dom($html);
    $hasData = (bool) array_filter($metadata);
    check(elements($xp, 'lesson-metadata')->length === (int) $hasData, "$name: no empty metadata wrapper");
    check(elements($xp, 'lesson-family-badge')->length === (int) (bool) $metadata['family'], "$name: family conditional");
    check(elements($xp, 'lesson-metadata__production')->length === (int) (bool) $metadata['production'], "$name: production conditional");
    check(elements($xp, 'lesson-metadata__series-name')->length === (int) (bool) $metadata['series_name'], "$name: no empty series-name node");
    foreach ($xp->query('//svg') as $svg) {
        check($svg->getAttribute('aria-hidden') === 'true' && $svg->getAttribute('focusable') === 'false', "$name: decorative real SVG");
    }
    if ($metadata['production']) {
        check(elements($xp, 'lesson-metadata__production-label')->item(0)->textContent === $metadata['production']['label'], "$name: visible production label");
        $expected = $metadata['production']['value'] === 'video_grabado' ? 'tabler-robot-off' : 'tabler-robot';
        $expectedDom = dom($icons->svg($expected)->toHtml());
        $paths = static fn (DOMNodeList $nodes): array => array_map(static fn (DOMElement $node): string => $node->getAttribute('d'), iterator_to_array($nodes));
        $productionPaths = $xp->query('//svg[contains(concat(" ", normalize-space(@class), " "), " lesson-metadata__production-icon ")]/path');
        check($paths($productionPaths) === $paths($expectedDom->query('//svg/path')), "$name: exact native Tabler geometry");
        check(!str_contains($html, 'robot-face'), "$name: no robot-face substitution");
    }
    if ($metadata['family']) {
        check(elements($xp, 'lesson-family-badge__label')->item(0)->textContent === 'Serie esencial', "$name: family visible");
    }
    if ($name === 'xss') {
        check($xp->query('//img | //script | //@onerror')->length === 0, 'XSS creates no executable element or attribute');
        check(elements($xp, 'lesson-metadata__series-name')->item(0)->textContent === $metadata['series_name'], 'XSS safely escaped without losing literal text');
        check(str_contains($html, '&lt;img'), 'Blade actually escapes the raw series name');
    }

    $header = $views->make('partials.post-header', [
        'lesson_metadata' => $metadata,
        'title' => 'Qué es El libro de Urantia',
        'is_completed' => false,
        'cde_breadcrumb' => [
            ['label' => 'CDE', 'url' => '/curso-de-desarrollo-espiritual/'],
            ['label' => 'El libro de Urantia. Introducción', 'url' => '/lecciones-del-cde/urantia/'],
            ['label' => 'Qué es El libro de Urantia', 'url' => null],
        ],
    ])->render();
    $headerDom = dom($header);
    check($headerDom->query('//h1')->item(0)->textContent === 'Qué es El libro de Urantia', "$name: real post-header title");
    check($headerDom->query('//nav[@aria-label="Miga de pan"]')->length === 1, "$name: breadcrumb retained");
    check($headerDom->query('//*[@id="mark-complete"]')->length === 0, "$name: anonymous user has no completion control");
    check(elements($headerDom, 'post-header--metadata')->length === (int) $hasData, "$name: metadata header modifier conditional");
    if ($hasData) {
        check(strpos($header, 'class="lesson-metadata"') < strpos($header, '<h1'), "$name: metadata above title");
    }
    $headers[$name] = ['metadata' => $metadata, 'html' => $header];
}

// Direct partial render also exercises the standalone existing family badge.
$badge = $views->make('partials.lesson-family-badge', ['family' => $family, 'series_name' => null])->render();
check(elements(dom($badge), 'lesson-family-badge__icon')->length === 1, 'Family-only badge renders real family symbol');
check(elements(dom($badge), 'lesson-metadata__series-name')->length === 0, 'Family-only badge has no empty heading');

foreach ([
    'espirituales-esenciales' => ['Serie esencial', 'mercaba'],
    'otras-principales' => ['Serie principal', 'torus'],
    'otras-espirituales' => ['Serie espiritual', 'triangle-spiral'],
    'informacion-complementaria' => ['Serie complementaria', 'cubes'],
] as $slug => [$label, $icon]) {
    $familyHtml = $views->make('partials.lesson-family-badge', [
        'family' => ['slug' => $slug, 'label' => $label, 'icon' => 'cdefamily-' . $icon],
        'series_name' => 'Serie raíz de prueba',
    ])->render();
    $familyDom = dom($familyHtml);
    check(elements($familyDom, 'lesson-family-badge__label')->item(0)->textContent === $label, "$slug: existing family label visible");
    check($familyDom->query('//*[@data-lesson-family="' . $slug . '"]')->length === 1, "$slug: existing family identity preserved");
    check(elements($familyDom, 'lesson-family-badge__icon')->length === 1, "$slug: native existing family symbol rendered");
}

$fixtureDir = $tmp . '/cde-header-design';
if (!is_dir($fixtureDir) && !mkdir($fixtureDir, 0755)) { throw new RuntimeException('Cannot create fixture directory'); }
$fixtureFile = $fixtureDir . '/header-fixtures.json';
$export = ['source' => 'Real installed Illuminate Blade and Blade Icons; anonymous WP context fixtures, no WP/DB/network', 'title' => 'Qué es El libro de Urantia', 'cases' => $headers];
if (file_put_contents($fixtureFile, json_encode($export, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) . "\n") === false) {
    throw new RuntimeException('Cannot write real rendered fixtures');
}
echo "$count real Blade header checks passed.\nFixtures: $fixtureFile\nDedicated cache: $cache\n";

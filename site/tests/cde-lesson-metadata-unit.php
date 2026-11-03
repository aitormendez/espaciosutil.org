<?php

declare(strict_types=1);

// Pure fixtures, actual Families resolver/helper and Blade icon factory.
// No WordPress bootstrap, database, network, writes or frontend rendering changes.
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../web/app/themes/sage/app/Support/CdeProductionType.php';
require __DIR__ . '/../web/app/themes/sage/app/Support/CdeLessonMetadata.php';
require __DIR__ . '/../web/app/themes/sage/app/helpers.php';

use App\Support\CdeLessonMetadata as Metadata;

$count = 0;
function check(bool $condition, string $message): void
{
    global $count;
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $count++;
}

class WP_Error
{
    public function __construct(private string $code, private string $message, private array $data = []) {}
    public function get_error_code(): string { return $this->code; }
    public function get_error_data(): array { return $this->data; }
}
function is_wp_error($value): bool { return $value instanceof WP_Error; }
function get_post($id) { return $GLOBALS['posts'][$id] ?? null; }
function get_term($id, $taxonomy) { return $GLOBALS['terms'][$id] ?? null; }
function wp_get_object_terms($id, $taxonomy, $args) { return $GLOBALS['post_terms'][$id] ?? []; }
function get_term_meta($id, $key, $single) { return $GLOBALS['term_meta'][$id][$key] ?? []; }
function get_post_meta($id, $key, $single = false)
{
    $GLOBALS['meta_reads'][] = [$id, $key, $single];
    if ($key !== 'tipo_produccion' || $single !== false) {
        throw new RuntimeException('Only own canonical meta rows may be read');
    }
    return $GLOBALS['post_meta'][$id][$key] ?? [];
}
function get_field(...$args) { throw new RuntimeException('Must not use ACF defaults or formatting'); }

function fixtures(): void
{
    $GLOBALS['posts'] = [
        1 => (object) ['ID' => 1, 'post_type' => 'cde', 'post_parent' => 2, 'post_title' => 'Título de lección distinto'],
        2 => (object) ['ID' => 2, 'post_type' => 'cde', 'post_parent' => 0, 'post_title' => 'Bloque de contenido'],
        9 => (object) ['ID' => 9, 'post_type' => 'page', 'post_parent' => 0],
    ];
    $GLOBALS['terms'] = [
        10 => (object) ['term_id' => 10, 'taxonomy' => 'serie_cde', 'parent' => 0, 'slug' => 'raiz-real', 'name' => 'Nombre de serie canónico'],
        11 => (object) ['term_id' => 11, 'taxonomy' => 'serie_cde', 'parent' => 10, 'slug' => 'bloque', 'name' => 'Nombre de bloque'],
        20 => (object) ['term_id' => 20, 'taxonomy' => 'serie_cde', 'parent' => 0, 'slug' => 'otra-serie', 'name' => 'Otra serie'],
        21 => (object) ['term_id' => 21, 'taxonomy' => 'serie_cde', 'parent' => 20, 'slug' => 'otro-bloque', 'name' => 'Otro bloque'],
        30 => (object) ['term_id' => 30, 'taxonomy' => 'serie_cde', 'parent' => 0, 'slug' => 'tercera', 'name' => 'Tercera serie'],
        40 => (object) ['term_id' => 40, 'taxonomy' => 'serie_cde', 'parent' => 0, 'slug' => 'sin-clasificar', 'name' => 'Serie sin familia'],
    ];
    $GLOBALS['post_terms'] = [1 => [], 2 => [11]];
    $GLOBALS['term_meta'] = [
        10 => ['familia_serie' => ['espirituales-esenciales']],
        20 => ['familia_serie' => ['otras-principales']],
        30 => ['familia_serie' => ['espirituales-esenciales']],
    ];
    $GLOBALS['post_meta'] = [1 => ['tipo_produccion' => ['audio_sintético']], 2 => ['tipo_produccion' => ['video_grabado']]];
    $GLOBALS['meta_reads'] = [];
}
fixtures();
$withoutBackend = Metadata::forLesson(1);
check($withoutBackend['family'] === null && $withoutBackend['series_name'] === null, 'Backend absent: no invented family or series');
check($withoutBackend['production']['icon'] === 'tabler-robot', 'Own modality independent of family backend');

require __DIR__ . '/../web/app/mu-plugins/cde-families/families.php';
$data = Metadata::forLesson(1);
check(array_keys($data) === ['family', 'series_name', 'production'], 'Stable three-key contract');
check($data['family'] === cde_lesson_family_presentation(1), 'Family is unchanged existing helper data');
check($data['family']['icon'] === 'cdefamily-mercaba', 'Family symbol preserved');
check($data['family']['color_token'] === '--color-serie-esencial', 'Family color preserved');
check($data['series_name'] === 'Nombre de serie canónico', 'Root term name, never lesson or block title');
check($data['production'] === ['value' => 'audio_sintético', 'label' => 'Voz sintética', 'icon' => 'tabler-robot'], 'Exact synthetic mapping, not robot-face');
check(end($GLOBALS['meta_reads']) === [1, 'tipo_produccion', false], 'Only own raw metadata, no ancestors or ACF');

$GLOBALS['post_meta'][1]['tipo_produccion'] = ['video_grabado'];
check(Metadata::forLesson(1)['production'] === ['value' => 'video_grabado', 'label' => 'Voz humana', 'icon' => 'tabler-robot-off'], 'Exact human mapping');
foreach ([[], [null], [''], [false], [true], [0], [1], [['audio_sintético']], ['audio_sintetico'], ["audio_sinte\u{0301}tico"], ['Voz sintética'], [' video_grabado'], ['video_grabado '], ['VIDEO_GRABADO'], ['no_aplica'], ['<img src=x onerror=alert(1)>'], ['video_grabado', 'audio_sintético'], ['video_grabado', 'video_grabado']] as $rows) {
    $GLOBALS['post_meta'][1]['tipo_produccion'] = $rows;
    check(Metadata::forLesson(1)['production'] === null, 'Missing/invalid/duplicate modality does not infer human voice');
}
$GLOBALS['post_meta'][1] = ['featured_video_id' => ['some-video'], 'notice' => ['voz sintética']];
$GLOBALS['posts'][1]->post_content = 'Esta lección utiliza una voz generada mediante síntesis de audio.';
check(Metadata::forLesson(1)['production'] === null, 'No inference from media, notices, parent or family');
check(array_unique(array_column($GLOBALS['meta_reads'], 0)) === [1], 'Never reads parent modality');

fixtures();
$GLOBALS['post_terms'][1] = [21];
$conflict = Metadata::forLesson(1);
check($conflict['family'] === null && $conflict['series_name'] === null, 'Contradictory own and ancestor series suppressed by canonical resolver');
check($conflict['production']['value'] === 'audio_sintético', 'Series conflict cannot change own modality');
$GLOBALS['post_terms'][1] = [30];
check(Metadata::forLesson(1)['series_name'] === null, 'Different roots with same family remain ambiguous');
$GLOBALS['post_terms'][1] = [40];
check(Metadata::forLesson(1)['series_name'] === null, 'Do not trust first unassigned root before unresolved contradiction');
$GLOBALS['post_terms'][2] = [];
check(Metadata::forLesson(1)['series_name'] === null, 'Unassigned family: conservative omission instead of duplicated hierarchy logic');
$GLOBALS['post_terms'][1] = [];
check(Metadata::forLesson(1)['series_name'] === null, 'No series does not use lesson title');

fixtures();
$GLOBALS['post_terms'][1] = [10, 11];
check(Metadata::forLesson(1)['series_name'] === 'Nombre de serie canónico', 'Multiple terms sharing one root are not ambiguous');
foreach (['espirituales-esenciales', 'otras-principales', 'otras-espirituales', 'informacion-complementaria'] as $family) {
    $GLOBALS['term_meta'][10]['familia_serie'] = [$family];
    check(Metadata::forLesson(1)['family'] === cde_lesson_family_presentation(1), 'All existing family vocabulary preserved');
}
$rawName = '<img src=x onerror="alert(1)"> & «Serie»';
$GLOBALS['terms'][10]->name = $rawName;
check(Metadata::forLesson(1)['series_name'] === $rawName, 'Series is raw text data, not trusted HTML or a rendered fragment');
$escaped = htmlspecialchars(Metadata::forLesson(1)['series_name'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
check(!str_contains($escaped, '<img') && str_contains($escaped, '&lt;img'), 'Series name can be escaped safely by the view');
$GLOBALS['terms'][10]->name = '';
check(Metadata::forLesson(1)['series_name'] === null, 'Empty root name is not invented');
$GLOBALS['terms'][10]->name = '  ';
check(Metadata::forLesson(1)['series_name'] === null, 'Whitespace name omitted');

fixtures();
$GLOBALS['posts'][2]->post_parent = 1;
check(Metadata::forLesson(1)['series_name'] === null, 'Post ancestry cycle rejected by backend');
fixtures();
$GLOBALS['terms'][10]->parent = 11;
check(Metadata::forLesson(1)['series_name'] === null, 'Taxonomy hierarchy cycle rejected by backend');
fixtures();
$GLOBALS['post_terms'][1] = new WP_Error('terms_failure', 'Unavailable');
check(Metadata::forLesson(1)['series_name'] === null, 'Term lookup error is not a name');

fixtures();
foreach ([0, -1, 999, 9] as $invalidId) {
    check(Metadata::forLesson($invalidId) === ['family' => null, 'series_name' => null, 'production' => null], 'Invalid/missing/non-CDE post returns empty structured data');
}
check($GLOBALS['meta_reads'] === [], 'Invalid posts trigger no meta reads');

$config = require __DIR__ . '/../web/app/themes/sage/config/blade-tabler-icons.php';
check($config['prefix'] === 'tabler', 'Actual configured package prefix');
$filesystem = new Illuminate\Filesystem\Filesystem();
$manifest = new BladeUI\Icons\IconsManifest($filesystem, __DIR__ . '/nonexistent-lesson-icons-manifest.php');
$factory = new BladeUI\Icons\Factory($filesystem, $manifest);
$factory->add('tabler', ['prefix' => $config['prefix'], 'path' => __DIR__ . '/../vendor/secondnetwork/blade-tabler-icons/resources/svg']);
foreach (['robot', 'robot-off'] as $icon) {
    $html = $factory->svg('tabler-' . $icon)->toHtml();
    check(str_contains($html, 'icon-tabler-' . $icon . '"'), 'Installed exact Tabler icon resolves: ' . $icon);
    check(!str_contains($html, 'robot-face'), 'Not the unapproved robot-face variant');
}
echo "$count lesson metadata checks passed; no WordPress, DB, network or writes.\n";

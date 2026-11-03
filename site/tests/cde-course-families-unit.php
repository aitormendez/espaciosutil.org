<?php

declare(strict_types=1);

// Real backend resolver and composer; in-memory WP fixtures only. No WP/DB/network/writes.
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../web/app/themes/sage/vendor/autoload.php';
require __DIR__ . '/../web/app/themes/sage/app/Support/CdeCourseFamilies.php';
require __DIR__ . '/../web/app/themes/sage/app/helpers.php';

use App\Support\CdeCourseFamilies as Course;

$count = 0;
function check(bool $condition, string $message): void
{
    global $count;
    if (!$condition) { throw new RuntimeException($message); }
    $count++;
}
class WP_Error
{
    public function __construct(private string $code, private string $message, private array $data = []) {}
    public function get_error_code(): string { return $this->code; }
}
function is_wp_error($value): bool { return $value instanceof WP_Error; }
function get_term($id, $taxonomy)
{
    check($taxonomy === 'serie_cde', 'Only canonical series taxonomy queried');
    $GLOBALS['term_reads'][] = $id;
    return $GLOBALS['terms'][$id] ?? null;
}
function get_term_meta($id, $key, $single)
{
    check($key === 'familia_serie' && $single === false, 'Canonical raw family rows only');
    return $GLOBALS['term_meta'][$id] ?? [];
}
function get_post($id) { return $GLOBALS['posts'][$id] ?? null; }
function wp_get_object_terms($id, $taxonomy, $args) { return $GLOBALS['post_terms'][$id] ?? []; }
function get_field(...$args) { throw new RuntimeException('No ACF inference'); }
function row(mixed $id, string $name = 'Nombre no autoritativo'): array
{
    return [
        'term_id' => $id, 'name' => $name, 'slug' => 'arbitrary-name',
        'blocks' => [
            7 => ['term_id' => 222, 'post_id' => 903, 'post_title' => 'Bloque Z', 'lessons_count' => 12],
            2 => ['term_id' => 111, 'post_id' => 902, 'post_title' => 'Bloque A', 'lessons_count' => 0],
        ],
        'extra' => ['future_contract' => ['preserve' => true]],
    ];
}
function fixtures(): void
{
    $GLOBALS['terms'] = [];
    $GLOBALS['term_reads'] = [];
    $GLOBALS['term_meta'] = [
        10 => ['espirituales-esenciales'], 20 => ['otras-principales'],
        30 => ['otras-espirituales'], 40 => ['informacion-complementaria'],
        50 => ['espirituales-esenciales'], 70 => ['espirituales-esenciales'],
        71 => ['otras-principales'], 100 => ['espirituales-esenciales', 'espirituales-esenciales'],
        110 => ['unknown'], 120 => [['espirituales-esenciales']],
    ];
    foreach ([10,20,30,40,50,60,70,100,110,120] as $id) {
        $GLOBALS['terms'][$id] = (object) ['term_id' => $id, 'taxonomy' => 'serie_cde', 'parent' => 0, 'slug' => 'root-' . $id];
    }
    foreach ([11 => 10, 12 => 11, 71 => 70, 80 => 81, 81 => 80, 90 => 999] as $id => $parent) {
        $GLOBALS['terms'][$id] = (object) ['term_id' => $id, 'taxonomy' => 'serie_cde', 'parent' => $parent, 'slug' => 'child-' . $id];
    }
    $GLOBALS['posts'] = [1 => (object) ['ID' => 1, 'post_type' => 'cde', 'post_parent' => 0]];
    $GLOBALS['post_terms'] = [1 => [10]];
}
fixtures();
$missingBackend = [row(10, 'Guía de estudio'), row(20)];
check(Course::group([]) === [], 'No empty groups');
check(Course::group($missingBackend) === [[
    'key' => 'unassigned', 'label' => 'Sin familia asignada', 'icon' => null,
    'color_token' => '--color-cde', 'series' => $missingBackend,
]], 'Missing backend preserves everything in one neutral group');
check($GLOBALS['term_reads'] === [], 'Missing backend triggers no taxonomy calls');

require __DIR__ . '/../web/app/mu-plugins/cde-families/families.php';
$source = [row(40), row(60, 'Guía de estudio'), row(20), row(50, 'Una serie futura sin lista cerrada'), row(30), row(10)];
$original = serialize($source);
$groups = Course::group($source);
check(array_column($groups, 'key') === ['espirituales-esenciales', 'otras-principales', 'otras-espirituales', 'informacion-complementaria', 'unassigned'], 'Stable canonical band order, never first-appearance order');
check($groups[0]['series'] === [$source[3], $source[5]], 'Series relative order retained within family');
check($groups[1]['series'] === [$source[2]] && $groups[2]['series'] === [$source[4]] && $groups[3]['series'] === [$source[0]], 'Other families retain original records');
check($groups[4]['series'] === [$source[1]], 'Unassigned guide moved only to neutral final group');
check(serialize($source) === $original, 'No mutation of original records or blocks');
check(array_keys($groups[0]) === ['key', 'label', 'icon', 'color_token', 'series'], 'Stable public group contract');
foreach ($groups as $group) {
    check($group['series'] !== [], 'No empty group emitted');
    foreach ($group['series'] as $series) {
        check(array_keys($series['blocks']) === [7, 2], 'Block ordering and keys untouched');
        check($series['extra'] === ['future_contract' => ['preserve' => true]], 'Unknown properties retained');
    }
}

$expected = [
    10 => ['espirituales-esenciales', 'Series esenciales', 'Serie esencial'],
    20 => ['otras-principales', 'Series principales', 'Serie principal'],
    30 => ['otras-espirituales', 'Series espirituales', 'Serie espiritual'],
    40 => ['informacion-complementaria', 'Series complementarias', 'Serie complementaria'],
];
foreach ($expected as $id => [$slug, $plural, $singular]) {
    $GLOBALS['post_terms'][1] = [$id];
    $lesson = cde_lesson_family_presentation(1);
    $group = Course::group([row($id)])[0];
    check($group['key'] === $slug && $group['label'] === $plural, 'Exact collection label');
    check($lesson['label'] === $singular, 'Lesson label contract unchanged');
    check($group['icon'] === $lesson['icon'] && $group['color_token'] === $lesson['color_token'], 'Collection symbols and tokens identical to lessons');
    check(count(Course::group([row($id)])) === 1, 'Empty other families omitted');
}
$onlySpiritual = Course::group([row(30)]);
check(array_column($onlySpiritual, 'key') === ['otras-espirituales'], 'No leading essential/principal empty bands');
$child = row(12, 'Guía de estudio');
check(Course::group([$child])[0]['series'] === [$child], 'Canonical resolver may resolve descendant terms, preserving the input');
check(Course::group([$child])[0]['key'] === 'espirituales-esenciales', 'Nested series classification follows real backend ancestry');
$spoof = row(60, 'El libro de Urantia');
$spoof['familia_serie'] = 'espirituales-esenciales';
$spoof['code'] = 'ELDU-01';
check(Course::group([$spoof])[0]['key'] === 'unassigned', 'No inference from names, codes or caller-provided family');
$GLOBALS['term_meta'][60] = ['otras-principales'];
check(Course::group([row(60, 'Guía de estudio')])[0]['key'] === 'otras-principales', 'No name-based exclusion: explicit backend classification wins');
fixtures();

$errors = [999,71,80,90,100,110,120];
$expectedErrors = ['series_missing','child_assignment','series_cycle','series_missing','duplicate_meta','unknown','unknown'];
foreach ($errors as $index => $id) {
    $error = \EspacioSutil\Cde\Families::resolveSeries($id);
    check($error instanceof WP_Error && str_ends_with($error->get_error_code(), $expectedErrors[$index]), 'Fixture exercises expected real backend error');
    $input = [row(20), row($id), row(10)];
    $output = Course::group($input);
    check(array_column($output, 'key') === ['espirituales-esenciales','otras-principales','unassigned'], 'Backend error creates final neutral group, not omission or default');
    check($output[2]['series'] === [$input[1]], 'Unresolvable record and blocks retained verbatim');
}
$GLOBALS['terms'][500] = new WP_Error('lookup_failure', 'Cannot read term');
check(Course::group([row(500)])[0]['key'] === 'unassigned', 'Term lookup WP_Error retained in neutral band');

$GLOBALS['term_reads'] = [];
$invalid = [null, 0, -1, true, false, 10.0, '10foo', ' 10', '+10', '1e1', '010', '9999999999999999999999999', [], new stdClass()];
foreach ($invalid as $id) {
    $entry = row($id);
    $output = Course::group([$entry]);
    check($output[0]['key'] === 'unassigned' && $output[0]['series'] === [$entry], 'Malformed ID neither coerced nor discarded');
}
check($GLOBALS['term_reads'] === [], 'Malformed IDs never reach the resolver');
$stringId = row('10');
check(Course::group([$stringId])[0]['series'] === [$stringId], 'Canonical decimal-string ID accepted without mutating input');
$malformed = [['name' => 'Missing ID'], null, 'unexpected scalar'];
check(Course::group($malformed)[0]['series'] === $malformed, 'No input record silently omitted');
$duplicates = [row(10), row(10), row(60), row(60)];
$duplicateGroups = Course::group($duplicates);
check($duplicateGroups[0]['series'] === [$duplicates[0],$duplicates[1]] && $duplicateGroups[1]['series'] === [$duplicates[2],$duplicates[3]], 'Duplicates retained in original relative order');
$raw = row(10, '<script>alert("test")</script> & «Serie»');
check(Course::group([$raw])[0]['series'][0] === $raw, 'Raw display strings are data for view escaping, not HTML');

// Actual Acorn Composer, without booting WP/container; override only its data sources.
require __DIR__ . '/../web/app/themes/sage/app/View/Composers/TemplateCurso.php';
class CountingTemplateCurso extends \App\View\Composers\TemplateCurso
{
    public int $seriesCalls = 0;
    public int $guideCalls = 0;
    public array $fixtureSeries = [];
    public function getSeriesWithBlocks() { $this->seriesCalls++; return $this->fixtureSeries; }
    protected function getProgramGuideLesson(): ?array { $this->guideCalls++; return ['post_id' => 44, 'untouched' => true]; }
}
$composer = (new ReflectionClass(CountingTemplateCurso::class))->newInstanceWithoutConstructor();
$composer->fixtureSeries = $source;
$data = $composer->with();
check($composer->seriesCalls === 1, 'Composer calls getSeriesWithBlocks exactly once');
check($composer->guideCalls === 1, 'Existing guide resolution unchanged');
check($data['series_cde_lessons'] === $source, 'Original composer flat-list contract preserved');
check($data['course_family_groups'] === Course::group($source), 'Composer adds grouped API from same list');
check($data['program_guide_lesson'] === ['post_id' => 44, 'untouched' => true], 'Guide payload preserved');
$composer->fixtureSeries = [];
$data = $composer->with();
check($data['series_cde_lessons'] === [] && $data['course_family_groups'] === [], 'Empty composer state has no phantom bands');
check($composer->seriesCalls === 2, 'One series query per with() invocation');

echo "$count course family checks passed; real Families/Composer, no WordPress/DB/network/writes.\n";

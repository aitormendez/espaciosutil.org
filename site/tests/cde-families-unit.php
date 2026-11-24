<?php

/** Pure PHP fixtures: no WordPress boot, database, or production changes. */
declare(strict_types=1);

use EspacioSutil\Cde\Families;
use EspacioSutil\Cde\FamilyCatalog;

require_once __DIR__ . '/fixtures/wp-error.php';
function is_wp_error(mixed $value): bool
{
    return $value instanceof WP_Error;
}
function get_term(int $id, string $taxonomy)
{
    return $GLOBALS['terms'][$id] ?? null;
}
function get_term_meta(int $id, string $key, bool $single)
{
    if ($key !== 'familia_serie') {
        throw new RuntimeException('Se consultó una clave distinta a la que conserva las asignaciones históricas');
    }
    $values = $GLOBALS['meta'][$id] ?? [];
    return $single ? ($values[0] ?? '') : $values;
}
function simulateOtherWriter(int $id): void
{
    if (isset($GLOBALS['concurrent_value'])) {
        $GLOBALS['meta'][$id] = [$GLOBALS['concurrent_value']];
        unset($GLOBALS['concurrent_value']);
    }
}
function add_term_meta(int $id, string $key, mixed $value, bool $unique = false)
{
    simulateOtherWriter($id);
    if ($unique && ! empty($GLOBALS['meta'][$id])) {
        return false;
    }
    $GLOBALS['meta'][$id][] = $value;
    return 1;
}
function update_term_meta(int $id, string $key, mixed $value, mixed $previous = '')
{
    simulateOtherWriter($id);
    if ($previous !== '' && ($GLOBALS['meta'][$id] ?? []) !== [$previous]) {
        return false;
    }
    $GLOBALS['meta'][$id] = [$value];
    return 1;
}
function get_post(int $id)
{
    return $GLOBALS['posts'][$id] ?? null;
}
function wp_get_object_terms(int $id, string $taxonomy, array $args)
{
    return $GLOBALS['assignments'][$id] ?? [];
}
function get_field(string $field, mixed $scope)
{
    if ($field !== 'cde_familias_series' || $scope !== 'option') {
        throw new RuntimeException('Lectura ACF inesperada');
    }
    return $GLOBALS['catalog'];
}
function get_post_type(mixed $id)
{
    return in_array($id, [97, 98, 99], true) ? 'attachment' : false;
}
function get_post_mime_type(int $id)
{
    return $id === 99 ? 'application/pdf' : 'image/png';
}
function get_terms(array $args)
{
    $query = $args['meta_query'][0];
    if ($args['taxonomy'] !== 'serie_cde' || $args['hide_empty'] !== false || $query['key'] !== 'familia_serie' || $query['compare'] !== 'IN') {
        throw new RuntimeException('La comprobación de uso debe incluir series sin lecciones');
    }
    if ($GLOBALS['lookup_error'] ?? false) {
        return new WP_Error('storage', 'No disponible');
    }
    return array_keys(array_filter($GLOBALS['meta'], static fn($values) => array_intersect($values, $query['value']) !== []));
}
function delete_term_meta(int $id, string $key, mixed $value = ''): bool
{
    unset($GLOBALS['meta'][$id]);
    return true;
}
function register_rest_field(string $type, string $field, array $args): void {}
function register_rest_route(string $namespace, string $route, array $args): void
{
    foreach (isset($args['methods']) ? [$args] : $args as $endpoint) {
        if (isset($endpoint['methods'])) {
            $GLOBALS['endpoints'][$namespace . $route][$endpoint['methods']] = $endpoint;
        }
    }
}
function get_taxonomy(string $taxonomy)
{
    return (object) ['rest_base' => false];
}
function current_user_can(string $cap): bool
{
    return $GLOBALS['allowed'] ?? true;
}
function wp_verify_nonce(string $nonce, string $action): bool
{
    return $nonce === 'valid';
}
function wp_unslash(mixed $value)
{
    return $value;
}
function get_current_user_id(): int
{
    return 1;
}
function set_transient(string $key, mixed $value, int $ttl): bool
{
    $GLOBALS['notices'][$key] = $value;
    return true;
}
function get_transient(string $key)
{
    return $GLOBALS['notices'][$key] ?? false;
}
function delete_transient(string $key): bool
{
    unset($GLOBALS['notices'][$key]);
    return true;
}
function esc_html(string $value): string
{
    return htmlspecialchars($value);
}
function esc_attr(mixed $value): string
{
    return htmlspecialchars($value);
}
function selected(mixed $current, mixed $value, bool $echo): string
{
    return $current === $value ? ' selected="selected"' : '';
}
function wp_nonce_field(string $action, string $name): void
{
    echo '<input name="' . $name . '" value="valid">';
}
final class FamilyFixtureTerm
{
    public function __construct(public int $term_id, public int $parent) {}
}
class_alias(FamilyFixtureTerm::class, 'WP_Term');
final class FamilyFixtureRequest implements ArrayAccess
{
    public function __construct(private array $params) {}
    public function has_param(string $key): bool
    {
        return array_key_exists($key, $this->params);
    }
    public function offsetExists(mixed $key): bool
    {
        return isset($this->params[$key]);
    }
    public function offsetGet(mixed $key): mixed
    {
        return $this->params[$key] ?? null;
    }
    public function offsetSet(mixed $key, mixed $value): void
    {
        $this->params[$key] = $value;
    }
    public function offsetUnset(mixed $key): void
    {
        unset($this->params[$key]);
    }
}
class_alias(FamilyFixtureRequest::class, 'WP_REST_Request');
require __DIR__ . '/../web/app/mu-plugins/cde-families/families.php';
function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    } echo "OK $message\n";
}
function errorIs(mixed $result, string $code): bool
{
    return is_wp_error($result) && $result->get_error_code() === 'cde_family_' . $code;
}
function resetFixtures(): void
{
    $GLOBALS['terms'] = [];
    foreach ([1 => 0, 2 => 1, 3 => 2, 4 => 0, 5 => 4, 6 => 0] as $id => $parent) {
        $GLOBALS['terms'][$id] = (object) ['term_id' => $id, 'parent' => $parent, 'slug' => 'series-' . $id];
    }
    $GLOBALS['meta'] = [1 => ['01'], 4 => ['02'], 6 => ['01']];
    $GLOBALS['posts'] = [];
    foreach ([10 => 0, 11 => 10, 12 => 11, 13 => 0] as $id => $parent) {
        $GLOBALS['posts'][$id] = (object) ['ID' => $id, 'post_type' => 'cde', 'post_parent' => $parent, 'post_status' => 'publish'];
    }
    $GLOBALS['assignments'] = [10 => [2], 13 => [5]];
    $GLOBALS['catalog'] = [
        ['codigo' => '01', 'nombre' => 'Nombre editable', 'icono' => 97],
        ['codigo' => '02', 'nombre' => 'Segunda', 'icono' => false],
        ['codigo' => '12', 'nombre' => 'Tipo añadido', 'icono' => 98],
    ];
}
resetFixtures();
check(! method_exists(Families::class, 'seedCatalog'), 'no existe operación de siembra');
$rows = Families::catalog();
check(array_column($rows, 'code') === ['01', '02', '12'], 'catálogo editable conserva orden, códigos y ceros iniciales');
check($rows[0]['icon_id'] === 97 && $rows[1]['icon_id'] === 0, 'icono de adjunto opcional, sin sustituto');
check(array_key_exists('background_color', $rows[0]) && $rows[0]['background_color'] === null && $rows[0]['text_color'] === 'white', 'filas antiguas conservan los colores por defecto sin migración');
$meta = $GLOBALS['meta'];
$GLOBALS['catalog'][0]['color_fondo'] = '#ABc123';
$GLOBALS['catalog'][0]['color_texto'] = 'black';
foreach ([Families::catalog()[0], Families::resolveSeries(2), Families::resolveLesson(12)] as $resolvedColor) {
    check($resolvedColor['background_color'] === '#abc123' && $resolvedColor['text_color'] === 'black', 'catálogo, bloque y lección devuelven los colores normalizados de la familia');
}
$GLOBALS['catalog'][0]['color_fondo'] = '#abc;display:none';
$GLOBALS['catalog'][0]['color_texto'] = 'inherit';
$invalidColor = Families::resolveLesson(12);
check($invalidColor['background_color'] === null && $invalidColor['text_color'] === 'white', 'colores dañados en disco no se publican ni rompen la clasificación');
$GLOBALS['catalog'][0]['nombre'] = 'Renombrada';
$GLOBALS['catalog'][0]['icono'] = 98;
$resolved = Families::resolveLesson(12);
check($resolved['code'] === '01' && $resolved['label'] === 'Renombrada' && $resolved['icon_id'] === 98 && $GLOBALS['meta'] === $meta, 'editar nombre e icono no cambia identidad ni persiste en lección');
$GLOBALS['catalog'] = [];
check(Families::catalog() === [] && is_wp_error(Families::resolveLesson(12)), 'catálogo vacío no activa fallback ni seed');
resetFixtures();
$form = [];
foreach ($GLOBALS['catalog'] as $i => $row) {
    $form['row-' . $i] = ['field_cde_familia_codigo' => $row['codigo'], 'field_cde_familia_nombre' => $row['nombre'], 'field_cde_familia_icono' => $row['icono']];
}
check(FamilyCatalog::validate(true, $form) === true, 'ACF acepta filas reales con claves de campo y familia futura');
$styled = $form;
$styled['row-0']['field_cde_familia_color_fondo'] = '#ABC123';
$styled['row-0']['field_cde_familia_color_texto'] = 'black';
$catalogField = ['key' => FamilyCatalog::FIELD];
check(FamilyCatalog::validate(true, $styled) === true && FamilyCatalog::guardWrite(null, $styled, 'options', $catalogField) === null, 'validador y guard permiten guardar los colores del formulario');
foreach ([['color_fondo', '#abc'], ['color_fondo', '#12345678'], ['color_fondo', 'var(--unsafe)'], ['color_fondo', []], ['color_texto', 'red'], ['color_texto', ['black']]] as [$key, $invalid]) {
    $bad = $styled;
    $bad['row-0']['field_cde_familia_' . $key] = $invalid;
    check(is_string(FamilyCatalog::validate(true, $bad)) && FamilyCatalog::guardWrite(null, $bad, 'options', $catalogField) === false, 'validación y guard rechazan ' . $key . ' no canónico');
}
check(FamilyCatalog::validate('Error previo', $form) === 'Error previo', 'validación conserva errores ACF anteriores');
foreach ([['codigo', '00'], ['codigo', '1'], ['codigo', 1], ['codigo', '01'], ['nombre', '   '], ['nombre', ' NOMBRE EDITABLE '], ['nombre', '<b>Nombre</b>'], ['icono', 'https://example.invalid/icono.svg'], ['icono', 99], ['icono', 666]] as [$field, $invalid]) {
    $copy = $form;
    $copy['row-2']['field_cde_familia_' . $field] = $invalid;
    check(is_string(FamilyCatalog::validate(true, $copy)), 'ACF rechaza ' . $field . ' inválido o repetido: ' . var_export($invalid, true));
}
$copy = $form;
unset($copy['row-0']);
check(is_string(FamilyCatalog::validate(true, $copy)), 'no borrar un código referenciado por una raíz');
$copy = $form;
$copy['row-0']['field_cde_familia_codigo'] = '14';
check(is_string(FamilyCatalog::validate(true, $copy)), 'no cambiar un código utilizado por otra serie');
$copy = $form;
unset($copy['row-2']);
check(FamilyCatalog::validate(true, $copy) === true, 'sí se puede retirar una familia sin uso');
$GLOBALS['lookup_error'] = true;
check(is_string(FamilyCatalog::validate(true, $copy)), 'si no se puede comprobar el uso, no se permite el borrado');
$GLOBALS['lookup_error'] = false;
$field = ['key' => 'field_cde_familias_series'];
check(FamilyCatalog::guardWrite(null, $form, 'options', $field) === null, 'actualización ACF válida continúa por el cauce normal');
check(FamilyCatalog::guardWrite(null, [], 'options', $field) === false, 'update_field no puede saltarse la protección de códigos en uso');
check(FamilyCatalog::guardWrite(null, $form, 12, $field) === false, 'catálogo no escribible en lecciones');
$GLOBALS['allowed'] = false;
check(FamilyCatalog::guardWrite(null, $form, 'options', $field) === false, 'no escribir catálogo sin permisos');
check(FamilyCatalog::guardWrite(null, $form, 12, ['key' => 'field_ajeno']) === null, 'no interferir en campos ACF ajenos');
$GLOBALS['allowed'] = true;
check(Families::resolveSeries(3)['series_id'] === 1, 'herencia de bloque y subbloque');
check(Families::resolveLesson(12)['source_post_id'] === 10 && Families::resolveLesson(12)['inherited'], 'herencia por cadena post_parent');
check(Families::resolveLesson(10)['inherited'] === false, 'asignación propia al índice de bloque');
check(Families::resolveLesson(0, ['post_parent' => 10])['series_id'] === 1, 'preflight de alta sin crear post');
check(Families::validatePublication(0, ['post_parent' => 10], '01')['familia_serie'] === '01', 'preflight coherente');
check(errorIs(Families::validatePublication(12, [], '12'), 'mismatch'), 'discrepancia de familia externa');
check(errorIs(Families::validatePublication(12, [], 'U'), 'unknown'), 'no inferir por códigos ni aceptar etiquetas');
check(errorIs(Families::resolveLesson(12, ['post_parent' => '10']), 'parent_invalid'), 'contrato PHP no convierte IDs arbitrarios');
check(errorIs(Families::resolveLesson(12, ['serie_cde' => ['2']]), 'terms_invalid'), 'lista exige IDs enteros');
check(errorIs(Families::resolveLesson(12, ['serie_cde' => [5]]), 'ambiguous_series'), 'conflicto asignación propia y ancestro');
check(errorIs(Families::resolveLesson(12, ['serie_cde' => [6]]), 'ambiguous_series'), 'dos series incluso si tienen misma familia');
check(Families::resolveLesson(12, ['serie_cde' => [1, 2, 3]])['series_id'] === 1, 'varios términos de misma serie admitidos');
check(errorIs(Families::resolveLesson(999), 'lesson_missing'), 'lección inexistente');
check(errorIs(Families::resolveLesson(0), 'no_series'), 'sin clasificación heredada');
check(errorIs(Families::resolveLesson(12, ['post_parent' => 12]), 'post_cycle'), 'autociclo post_parent');
check(errorIs(Families::resolveLesson(12, ['post_parent' => 999]), 'parent_invalid'), 'ancestro desaparecido');
$GLOBALS['assignments'][12] = [5];
check(Families::validatePublication(12, ['serie_cde' => []], '01')['code'] === '01', 'preflight evalúa la corrección propuesta');
check(errorIs(Families::validatePublication(12, [], '01'), 'ambiguous_series'), 'readback del estado persistido detecta el conflicto que la propuesta ocultaría');
unset($GLOBALS['assignments'][12]);
$GLOBALS['posts'][10]->post_parent = 12;
check(errorIs(Families::resolveLesson(12), 'post_cycle'), 'ciclo indirecto post_parent');
resetFixtures();
$GLOBALS['posts'][10]->post_type = 'page';
check(errorIs(Families::resolveLesson(12), 'parent_invalid'), 'ancestro ajeno a CDE');
resetFixtures();
$GLOBALS['terms'][1]->parent = 3;
check(errorIs(Families::resolveSeries(2), 'child_assignment'), 'familia no permitida en bloque');
$GLOBALS['meta'] = [];
check(errorIs(Families::resolveSeries(2), 'series_cycle'), 'ciclo indirecto taxonomía');
resetFixtures();
unset($GLOBALS['terms'][1]);
check(errorIs(Families::resolveSeries(2), 'series_missing'), 'padre taxonómico desaparecido');
resetFixtures();
$GLOBALS['meta'][1] = [];
check(errorIs(Families::resolveSeries(2), 'unassigned'), 'pendiente de clasificación explícita');
$GLOBALS['meta'][1] = ['99'];
check(errorIs(Families::resolveSeries(2), 'unknown'), 'metadato desconocido no silenciado');
$GLOBALS['meta'][1] = ['01', '02'];
check(errorIs(Families::resolveSeries(2), 'duplicate_meta'), 'metadatos múltiples rechazados');
check(errorIs(Families::assignSeries(1, '02', true), 'duplicate_meta'), 'setter no oculta corrupción');
resetFixtures();
check(errorIs(Families::assignSeries(2, '02'), 'root_required'), 'setter limitado a raíces');
check(errorIs(Families::assignSeries(999, '02'), 'series_missing'), 'setter serie inexistente');
check(errorIs(Families::assignSeries(1, 'otra'), 'unknown'), 'setter rechaza valor ausente del catálogo');
check(Families::assignSeries(1, '01') === true, 'asignación idempotente');
check(errorIs(Families::assignSeries(1, '02'), 'reclassification_required'), 'no reclasificación accidental');
check(Families::assignSeries(1, '02', true) === true && Families::resolveLesson(12)['familia_serie'] === '02', 'reclasificación estructural explícita heredada inmediatamente');
resetFixtures();
$GLOBALS['meta'][1] = [];
$GLOBALS['concurrent_value'] = '12';
check(is_wp_error(Families::assignSeries(1, '01')) && $GLOBALS['meta'][1] === ['12'], 'alta no pisa una asignación que ya apareció entre lectura y escritura');
$GLOBALS['meta'][1] = ['otras-principales'];
$GLOBALS['concurrent_value'] = '12';
check(is_wp_error(Families::assignSeries(1, '01', true)) && $GLOBALS['meta'][1] === ['12'], 'confirmar la sustitución legacy no pisa una edición concurrente');
resetFixtures();
foreach (['espirituales-esenciales', 'otras-principales', 'otras-espirituales', 'informacion-complementaria'] as $legacy) {
    $GLOBALS['meta'][1] = [$legacy];
    $before = $GLOBALS['meta'];
    $result = Families::resolveLesson(12);
    check(errorIs($result, 'legacy') && $result->get_error_data()['legacy_value'] === $legacy, 'lectura diagnostica el valor anterior: ' . $legacy);
    check(is_wp_error(Families::assignSeries(1, '01')) && is_wp_error(Families::validatePublication(12, [], $legacy)) && $GLOBALS['meta'] === $before, 'sin traducción implícita ni sobrescritura del valor ' . $legacy);
}
check(errorIs(Families::resolveLesson(12, ['serie_cde' => [4]]), 'ambiguous_series'), 'una asignación legacy no enmascara conflicto con otra raíz');
check(Families::assignSeries(1, '02', true) === true && Families::resolveLesson(12)['code'] === '02', 'reclasificación legacy humana explícita');
$before = $GLOBALS['meta'];
check(is_wp_error(Families::unassignSeries(1)) && is_wp_error(Families::unassignSeries(2, true)) && $GLOBALS['meta'] === $before, 'retirar exige raíz y confirmación explícita');
check(Families::unassignSeries(1, true) === true && errorIs(Families::resolveLesson(12), 'unassigned'), 'retirar una asignación confirmada no crea otra');
resetFixtures();
Families::rest();
$route = $GLOBALS['endpoints']['cde/v1/familias/series/(?P<id>\d+)'];
$GLOBALS['allowed'] = false;
foreach (['PUT', 'DELETE'] as $method) {
    check(isset($route[$method]) && $route[$method]['permission_callback']() === false, 'REST ' . $method . ' exige permisos de clasificación');
}
$GLOBALS['allowed'] = true;
$prepared = (object) ['post_title' => 'Sin cambios de clasificación'];
check(Families::validateRestPublication($prepared, new FamilyFixtureRequest(['status' => 'publish', 'parent' => 10])) === $prepared, 'REST alta hereda antes de publicar');
check(Families::validateRestPublication($prepared, new FamilyFixtureRequest(['status' => 'publish'])) === $prepared, 'REST publicación legacy sin opt-in no bloqueada');
check(errorIs(Families::validateRestPublication($prepared, new FamilyFixtureRequest(['status' => 'publish', 'familia_serie' => '01'])), 'no_series'), 'REST publicación con opt-in inválida rechazada antes de insertar');
check(errorIs(Families::validateRestPublication($prepared, new FamilyFixtureRequest(['status' => 'publish', 'parent' => 10, 'familia_serie' => '02'])), 'mismatch'), 'REST familia entrante discrepante');
check(errorIs(Families::validateRestPublication($prepared, new FamilyFixtureRequest(['familia_serie' => []])), 'unknown'), 'REST tipo inválido devuelve error');
$GLOBALS['meta'] = [];
check(Families::validateRestPublication($prepared, new FamilyFixtureRequest(['id' => 12, 'title' => 'Editar histórico'])) === $prepared, 'edición histórica no exige migración');
check(Families::validateRestPublication($prepared, new FamilyFixtureRequest(['id' => 12, 'parent' => 13])) === $prepared, 'cambio estructural legacy sin opt-in no bloqueado');
check(errorIs(Families::validateRestPublication($prepared, new FamilyFixtureRequest(['id' => 12, 'parent' => 13, 'familia_serie' => '02'])), 'unassigned'), 'cambio estructural opt-in exige clasificación');
resetFixtures();
check(errorIs(Families::validateRestSeries($prepared, new FamilyFixtureRequest(['id' => 1, 'parent' => 4])), 'root_required'), 'REST no degrada raíz clasificada a bloque');
resetFixtures();
check(Families::catalog()[0]['slug'] === '01', 'alias slug devuelve código, no un término');
ob_start();
Families::adminAddFields();
$html = ob_get_clean();
check(str_contains($html, 'cde_family_nonce') && substr_count($html, '<option') === 4, 'admin crear raíz con nonce y opciones configuradas');
ob_start();
Families::adminEditFields(new FamilyFixtureTerm(1, 0));
$html = ob_get_clean();
check(str_contains($html, 'cde_family_replace') && str_contains($html, 'selected="selected"'), 'admin raíz muestra familia y confirmación estructural');
ob_start();
Families::adminEditFields(new FamilyFixtureTerm(2, 1));
$html = ob_get_clean();
check(! str_contains($html, '<select') && str_contains($html, 'Nombre editable'), 'bloque muestra herencia readonly');
$_POST = ['cde_family_slug' => '02', 'cde_family_nonce' => 'bad', 'cde_family_replace' => '1'];
Families::adminSave(1);
check(Families::resolveSeries(1)['familia_serie'] === '01', 'nonce inválido no escribe');
$_POST['cde_family_nonce'] = 'valid';
$GLOBALS['allowed'] = false;
Families::adminSave(1);
check(Families::resolveSeries(1)['familia_serie'] === '01', 'sin capability no escribe');
ob_start();
Families::adminAddFields();
$html = ob_get_clean();
check($html === '', 'sin capability no muestra selector');
$GLOBALS['allowed'] = true;
unset($_POST['cde_family_replace']);
Families::adminSave(1);
check(Families::resolveSeries(1)['familia_serie'] === '01', 'admin reclasificación requiere confirmación');
ob_start();
Families::adminNotice();
$html = ob_get_clean();
check(str_contains($html, 'notice-error') && str_contains($html, 'estructural'), 'admin feedback explícito de error');
$_POST['cde_family_replace'] = '1';
Families::adminSave(1);
check(Families::resolveSeries(1)['familia_serie'] === '02', 'admin reclasificación confirmada');
Families::adminSave(2);
check(get_term_meta(2, 'familia_serie', false) === [], 'formulario manipulado no asigna familia al bloque');
$GLOBALS['meta'][6] = [];
Families::adminSave(6);
check(Families::resolveSeries(6)['familia_serie'] === '02', 'admin asigna serie raíz pendiente');
$_POST['cde_family_slug'] = '';
Families::adminSave(1);
check(Families::resolveSeries(1)['familia_serie'] === '02', 'selector vacío conserva asignación');
check(Families::protectRootParent(4, 1, 'serie_cde') === 0, 'administración no convierte raíz clasificada en bloque');
check(Families::protectRootParent(4, 2, 'serie_cde') === 4, 'bloque puede cambiar padre explícitamente');
$_POST = [];
echo "Pruebas unitarias de familias completadas.\n";

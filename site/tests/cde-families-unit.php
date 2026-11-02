<?php

/** Pure PHP fixtures: no WordPress boot, database, or production changes. */
declare(strict_types=1);

use EspacioSutil\Cde\Families;

class WP_Error
{
    public function __construct(private string $code, private string $message, private array $data = []) {}
    public function get_error_code(): string
    {
        return $this->code;
    }
    public function get_error_message(): string
    {
        return $this->message;
    }
    public function get_error_data(): array
    {
        return $this->data;
    }
}
function is_wp_error($value): bool
{
    return $value instanceof WP_Error;
}
function get_term($id, $taxonomy)
{
    return $GLOBALS['terms'][$id] ?? null;
}
function get_term_meta($id, $key, $single)
{
    $values = $GLOBALS['meta'][$id] ?? [];
    return $single ? ($values[0] ?? '') : $values;
}
function update_term_meta($id, $key, $value)
{
    $GLOBALS['meta'][$id] = [$value];
    return 1;
}
function get_post($id)
{
    return $GLOBALS['posts'][$id] ?? null;
}
function wp_get_object_terms($id, $taxonomy, $args)
{
    return $GLOBALS['assignments'][$id] ?? [];
}
function get_term_by($field, $slug, $taxonomy)
{
    return $GLOBALS['catalog'][$slug] ?? false;
}
function wp_insert_term($label, $taxonomy, $args)
{
    $id = count($GLOBALS['catalog']) + 1000;
    $GLOBALS['catalog'][$args['slug']] = (object) ['term_id' => $id, 'name' => $label, 'slug' => $args['slug'], 'parent' => 0];
    return ['term_id' => $id];
}
function get_taxonomy($taxonomy)
{
    return (object) ['rest_base' => false];
}
function current_user_can($cap): bool
{
    return $GLOBALS['allowed'] ?? true;
}
function wp_verify_nonce($nonce, $action): bool
{
    return $nonce === 'valid';
}
function wp_unslash($value)
{
    return $value;
}
function get_current_user_id(): int
{
    return 1;
}
function set_transient($key, $value, $ttl): bool
{
    $GLOBALS['notices'][$key] = $value;
    return true;
}
function get_transient($key)
{
    return $GLOBALS['notices'][$key] ?? false;
}
function delete_transient($key): bool
{
    unset($GLOBALS['notices'][$key]);
    return true;
}
function esc_html($value): string
{
    return htmlspecialchars($value);
}
function esc_attr($value): string
{
    return htmlspecialchars($value);
}
function selected($current, $value, $echo): string
{
    return $current === $value ? ' selected="selected"' : '';
}
function wp_nonce_field($action, $name): void
{
    echo '<input name="' . $name . '" value="valid">';
}
class WP_Term
{
    public function __construct(public int $term_id, public int $parent) {}
}
class WP_REST_Request implements ArrayAccess
{
    public function __construct(private array $params) {}
    public function has_param($key): bool
    {
        return array_key_exists($key, $this->params);
    }
    public function offsetExists($key): bool
    {
        return isset($this->params[$key]);
    }
    public function offsetGet($key): mixed
    {
        return $this->params[$key] ?? null;
    }
    public function offsetSet($key, $value): void
    {
        $this->params[$key] = $value;
    }
    public function offsetUnset($key): void
    {
        unset($this->params[$key]);
    }
}
require __DIR__ . '/../web/app/mu-plugins/cde-families/families.php';
function check(bool $condition, string $message): void
{
    if (! $condition) {
        throw new RuntimeException($message);
    } echo "OK $message\n";
}
function errorIs($result, string $code): bool
{
    return is_wp_error($result) && $result->get_error_code() === 'cde_family_' . $code;
}
function resetFixtures(): void
{
    $GLOBALS['terms'] = [];
    foreach ([1 => 0, 2 => 1, 3 => 2, 4 => 0, 5 => 4, 6 => 0] as $id => $parent) {
        $GLOBALS['terms'][$id] = (object) ['term_id' => $id, 'parent' => $parent, 'slug' => 'series-' . $id];
    }
    $GLOBALS['meta'] = [1 => ['espirituales-esenciales'], 4 => ['otras-principales'], 6 => ['espirituales-esenciales']];
    $GLOBALS['posts'] = [];
    foreach ([10 => 0, 11 => 10, 12 => 11, 13 => 0] as $id => $parent) {
        $GLOBALS['posts'][$id] = (object) ['ID' => $id, 'post_type' => 'cde', 'post_parent' => $parent, 'post_status' => 'publish'];
    }
    $GLOBALS['assignments'] = [10 => [2], 13 => [5]];
    $GLOBALS['catalog'] = [];
}
resetFixtures();
check(count(Families::CATALOG) === 4 && Families::CATALOG['otras-principales'] === 'Otras enseñanzas del grupo principal', 'cuatro familias y etiquetas exactas');
check(count(Families::seedCatalog()) === 4 && count(Families::seedCatalog()) === 4 && count($GLOBALS['catalog']) === 4, 'semilla explícita e idempotente');
$GLOBALS['catalog']['otras-principales']->name = 'Incorrecta';
check(errorIs(Families::seedCatalog(), 'catalog_conflict'), 'semilla no sobrescribe catálogo discrepante');
resetFixtures();
check(Families::resolveSeries(3)['series_id'] === 1, 'herencia de bloque y subbloque');
check(Families::resolveLesson(12)['source_post_id'] === 10 && Families::resolveLesson(12)['inherited'], 'herencia por cadena post_parent');
check(Families::resolveLesson(10)['inherited'] === false, 'asignación propia al índice de bloque');
check(Families::resolveLesson(0, ['post_parent' => 10])['series_id'] === 1, 'preflight de alta sin crear post');
check(Families::validatePublication(0, ['post_parent' => 10], 'espirituales-esenciales')['familia_serie'] === 'espirituales-esenciales', 'preflight coherente');
check(errorIs(Families::validatePublication(12, [], 'otras-espirituales'), 'mismatch'), 'discrepancia de familia externa');
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
$GLOBALS['meta'][1] = ['inventada'];
check(errorIs(Families::resolveSeries(2), 'unknown'), 'metadato desconocido no silenciado');
$GLOBALS['meta'][1] = ['espirituales-esenciales', 'otras-principales'];
check(errorIs(Families::resolveSeries(2), 'duplicate_meta'), 'metadatos múltiples rechazados');
check(errorIs(Families::assignSeries(1, 'otras-principales', true), 'duplicate_meta'), 'setter no oculta corrupción');
resetFixtures();
check(errorIs(Families::assignSeries(2, 'otras-principales'), 'root_required'), 'setter limitado a raíces');
check(errorIs(Families::assignSeries(999, 'otras-principales'), 'series_missing'), 'setter serie inexistente');
check(errorIs(Families::assignSeries(1, 'otra'), 'unknown'), 'setter catálogo cerrado');
check(Families::assignSeries(1, 'espirituales-esenciales') === true, 'asignación idempotente');
check(errorIs(Families::assignSeries(1, 'otras-principales'), 'reclassification_required'), 'no reclasificación accidental');
check(Families::assignSeries(1, 'otras-principales', true) === true && Families::resolveLesson(12)['familia_serie'] === 'otras-principales', 'reclasificación estructural explícita heredada inmediatamente');
resetFixtures();
$prepared = (object) ['post_title' => 'Sin cambios de clasificación'];
check(Families::validateRestPublication($prepared, new WP_REST_Request(['status' => 'publish', 'parent' => 10])) === $prepared, 'REST alta hereda antes de publicar');
check(Families::validateRestPublication($prepared, new WP_REST_Request(['status' => 'publish'])) === $prepared, 'REST publicación legacy sin opt-in no bloqueada');
check(errorIs(Families::validateRestPublication($prepared, new WP_REST_Request(['status' => 'publish', 'familia_serie' => 'espirituales-esenciales'])), 'no_series'), 'REST publicación con opt-in inválida rechazada antes de insertar');
check(errorIs(Families::validateRestPublication($prepared, new WP_REST_Request(['status' => 'publish', 'parent' => 10, 'familia_serie' => 'otras-principales'])), 'mismatch'), 'REST familia entrante discrepante');
check(errorIs(Families::validateRestPublication($prepared, new WP_REST_Request(['familia_serie' => []])), 'unknown'), 'REST tipo inválido devuelve error');
$GLOBALS['meta'] = [];
check(Families::validateRestPublication($prepared, new WP_REST_Request(['id' => 12, 'title' => 'Editar histórico'])) === $prepared, 'edición histórica no exige migración');
check(Families::validateRestPublication($prepared, new WP_REST_Request(['id' => 12, 'parent' => 13])) === $prepared, 'cambio estructural legacy sin opt-in no bloqueado');
check(errorIs(Families::validateRestPublication($prepared, new WP_REST_Request(['id' => 12, 'parent' => 13, 'familia_serie' => 'otras-principales'])), 'unassigned'), 'cambio estructural opt-in exige clasificación');
resetFixtures();
check(errorIs(Families::validateRestSeries($prepared, new WP_REST_Request(['id' => 1, 'parent' => 4])), 'root_required'), 'REST no degrada raíz clasificada a bloque');
resetFixtures();
check(Families::catalog()[0]['term_id'] === null && Families::resolveLesson(12)['familia_serie'] === 'espirituales-esenciales', 'resolución por slug independiente del seed de catálogo');
ob_start();
Families::adminAddFields();
$html = ob_get_clean();
check(str_contains($html, 'cde_family_nonce') && substr_count($html, '<option') === 5, 'admin crear raíz con nonce y cuatro opciones');
ob_start();
Families::adminEditFields(new WP_Term(1, 0));
$html = ob_get_clean();
check(str_contains($html, 'cde_family_replace') && str_contains($html, 'selected="selected"'), 'admin raíz muestra familia y confirmación estructural');
ob_start();
Families::adminEditFields(new WP_Term(2, 1));
$html = ob_get_clean();
check(! str_contains($html, '<select') && str_contains($html, 'Enseñanzas espirituales esenciales'), 'bloque muestra herencia readonly');
$_POST = ['cde_family_slug' => 'otras-principales', 'cde_family_nonce' => 'bad', 'cde_family_replace' => '1'];
Families::adminSave(1);
check(Families::resolveSeries(1)['familia_serie'] === 'espirituales-esenciales', 'nonce inválido no escribe');
$_POST['cde_family_nonce'] = 'valid';
$GLOBALS['allowed'] = false;
Families::adminSave(1);
check(Families::resolveSeries(1)['familia_serie'] === 'espirituales-esenciales', 'sin capability no escribe');
ob_start();
Families::adminAddFields();
$html = ob_get_clean();
check($html === '', 'sin capability no muestra selector');
$GLOBALS['allowed'] = true;
unset($_POST['cde_family_replace']);
Families::adminSave(1);
check(Families::resolveSeries(1)['familia_serie'] === 'espirituales-esenciales', 'admin reclasificación requiere confirmación');
ob_start();
Families::adminNotice();
$html = ob_get_clean();
check(str_contains($html, 'notice-error') && str_contains($html, 'estructural'), 'admin feedback explícito de error');
$_POST['cde_family_replace'] = '1';
Families::adminSave(1);
check(Families::resolveSeries(1)['familia_serie'] === 'otras-principales', 'admin reclasificación confirmada');
Families::adminSave(2);
check(get_term_meta(2, 'familia_serie', false) === [], 'formulario manipulado no asigna familia al bloque');
$GLOBALS['meta'][6] = [];
Families::adminSave(6);
check(Families::resolveSeries(6)['familia_serie'] === 'otras-principales', 'admin asigna serie raíz pendiente');
$_POST['cde_family_slug'] = '';
Families::adminSave(1);
check(Families::resolveSeries(1)['familia_serie'] === 'otras-principales', 'selector vacío conserva asignación');
check(Families::protectRootParent(4, 1, 'serie_cde') === 0, 'administración no convierte raíz clasificada en bloque');
check(Families::protectRootParent(4, 2, 'serie_cde') === 4, 'bloque puede cambiar padre explícitamente');
$_POST = [];
echo "Pruebas unitarias de familias completadas.\n";

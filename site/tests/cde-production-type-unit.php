<?php

// Contrato aislado: builder y renderer ACF reales, sin WordPress, BD ni escrituras.
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../web/app/themes/sage/app/Support/CdeProductionType.php';
require __DIR__ . '/../web/app/themes/sage/app/Fields/Cde.php';

use App\Support\CdeProductionType as Type;

$count = 0;
function check(bool $ok, string $message): void
{
    global $count;
    if (!$ok) {
        throw new RuntimeException($message);
    }
    $count++;
}

$GLOBALS['registered_filters'] = [];
function add_filter(string $hook, callable|string|array $callback, int $priority = 10, int $acceptedArgs = 1): void
{
    $GLOBALS['registered_filters'][$hook] = [$callback, $priority, $acceptedArgs];
}
Type::register();
check(isset($GLOBALS['registered_filters']['acf/validate_value/key=' . Type::FIELD_KEY]), 'Validador registrado por clave exacta');
check(isset($GLOBALS['registered_filters']['acf/rest/get_field_schema/key=' . Type::FIELD_KEY]), 'Schema REST registrado por clave exacta');

$builder = (new ReflectionClass(App\Fields\Cde::class))->newInstanceWithoutConstructor();
$group = $builder->fields();
$fields = array_column($group['fields'], null, 'name');
$field = $fields[Type::FIELD_NAME];
check($field['key'] === Type::FIELD_KEY, 'Clave ACF estable producida por builder real');
check($field['type'] === 'radio', 'Opciones excluyentes');
check($field['choices'] === ['video_grabado' => 'Voz humana', 'audio_sintético' => 'Voz sintética', 'sin_voz' => 'Sin voz'], 'Contrato exacto con tilde y opción sin locución');
check($fields['active_lesson']['key'] === Type::ACTIVE_KEY, 'Clave de actividad recibida del builder real');
check($field['required'] === 1 && $field['allow_null'] === 1 && $field['default_value'] === '', 'Obligatorio sin preselección implícita de ACF');
check($field['other_choice'] === 0 && $field['save_other_choice'] === 0, 'Sin variantes libres');
check($field['return_format'] === 'value', 'ACF devuelve escalar, no etiqueta ni array');
check($field['layout'] === 'horizontal', 'Radio horizontal');
check($group['show_in_rest'] === 1, 'Campo dentro del grupo REST');
$names = array_keys($fields);
check(array_search('general_tab', $names) < array_search(Type::FIELD_NAME, $names)
    && array_search(Type::FIELD_NAME, $names) < array_search('lesson_subindex_tab', $names), 'Campo en pestaña General');
check($fields['active_lesson']['default_value'] === 1, 'Estado de lección activa conservado');
check($fields['rich_excerpt']['type'] === 'wysiwyg', 'Extracto conservado');

foreach ([null, '1', 1, true, [], 'off', '0', 0, false] as $active) {
    $_POST['acf'] = $active === null ? [] : [Type::ACTIVE_KEY => $active];
    foreach (array_keys(Type::CHOICES) as $value) {
        $expected = $value !== Type::NO_VOICE || in_array($active, ['0', 0, false], true);
        check((Type::validateValue(true, $value) === true) === $expected, 'Valida la pareja enviada por el editor');
    }
}
unset($_POST['acf']);

require_once __DIR__ . '/fixtures/wp-error.php';
function is_wp_error($v): bool { return $v instanceof WP_Error; }
class WP_REST_Request {
    public function __construct(private array $acf, private int $id = 0) {}
    public function get_param($name) { return $name === 'acf' ? $this->acf : null; }
    public function get_url_params(): array { return ['id' => $this->id]; }
}
function get_post_meta($id, $key, $single) { return [1 => ['active_lesson' => '0', 'tipo_produccion' => 'sin_voz'], 2 => ['active_lesson' => '1']][$id][$key] ?? ''; }
check(isset($GLOBALS['registered_filters']['rest_pre_insert_cde']), 'Protege REST antes de escritura');
foreach ([[[],2,true], [['tipo_produccion'=>'sin_voz'],0,false], [['tipo_produccion'=>'sin_voz'],1,true], [['tipo_produccion'=>'sin_voz'],2,false], [['active_lesson'=>true],1,false], [['active_lesson'=>false,'tipo_produccion'=>'sin_voz'],0,true], [['active_lesson'=>true,'tipo_produccion'=>'video_grabado'],1,true]] as [$acf,$id,$ok]) {
    $prepared = new stdClass();
    $r = Type::validateRestPair($prepared, new WP_REST_Request($acf,$id));
    check($ok ? $r === $prepared : is_wp_error($r), 'Valida petición REST parcial y completa');
}
foreach ([null, '', false, true, 0, 1, [], ['audio_sintético'], 'audio_sintetico', "audio_sinte\u{0301}tico", ' video_grabado', 'video_grabado ', 'VIDEO_GRABADO', 'Voz humana', 'no_aplica', 'robot'] as $value) {
    check(!Type::isValid($value), 'Rechaza variante o tipo inválido');
    check(is_string(Type::validateValue(true, $value)), 'Error legible, sin convertir a vídeo');
}
check(Type::validateValue('Error previo', 'video_grabado') === 'Error previo', 'Preserva error de otro validador');
check(Type::validateValue(false, 'video_grabado') === false, 'Preserva fallo de validación');
$schema = Type::restSchema(['type' => ['string', 'null'], 'enum' => ['video_grabado', null], 'default' => 'video_grabado']);
check($schema['type'] === 'string', 'REST no acepta null ni array');
check($schema['enum'] === array_keys(Type::CHOICES), 'REST enumera las tres modalidades canónicas');
check($schema['required'] === true && !array_key_exists('default', $schema), 'REST obligatorio sin fallback');

// Renderer instalado real. Se sustituyen sólo registro y escapes de WP/ACF;
// no se reproduce ni se simula su decisión de cuál radio está seleccionado.
class acf_field {}
function acf_register_field_type(string $class): void {}
function esc_attr(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); }
function sanitize_title(string $value): string { return str_replace(' ', '-', (string) $value); }
function acf_esc_html(string $value): string { return esc_attr($value); }
function acf_get_hidden_input(array $attrs): string { return '<input type="hidden" name="' . esc_attr($attrs['name']) . '" />'; }
function acf_esc_attrs(array $attrs): string
{
    $result = [];
    foreach ($attrs as $name => $value) {
        $result[] = esc_attr($name) . '="' . esc_attr($value) . '"';
    }
    return implode(' ', $result);
}
require __DIR__ . '/../web/app/plugins/advanced-custom-fields-pro/includes/fields/class-acf-field-radio.php';
$radio = new acf_field_radio();
$render = static function ($value, array $overrides = []) use ($radio, $field): string {
    $renderField = array_merge($field, ['id' => $field['key'], 'class' => '', 'value' => $value], $overrides);
    ob_start();
    $radio->render_field($renderField);
    return ob_get_clean();
};
foreach (['', null, false, 'valor_antiguo_invalido'] as $value) {
    $html = $render($value);
    check(substr_count($html, 'type="radio"') === 3, 'Renderiza exactamente tres opciones');
    check(!str_contains($html, 'checked='), 'Sin dato o dato inválido: ningún radio marcado');
}
foreach (array_keys(Type::CHOICES) as $value) {
    $html = $render($value);
    check(substr_count($html, 'checked="checked"') === 1, 'Dato trasladado: una opción marcada');
    check(str_contains($html, 'value="' . $value . '" checked="checked"'), 'Marca exactamente la opción persistida');
}
check(str_contains($render('', ['allow_null' => 0]), 'value="video_grabado" checked="checked"'), 'Control de regresión: ACF preseleccionaría vídeo sin allow_null');
echo "$count comprobaciones de modalidad correctas; sin BD ni escrituras.\n";

<?php

declare(strict_types=1);

namespace EspacioSutil\Cde;

use function in_array;
use function is_array;
use function is_int;
use function is_string;

/** The ACF repeater is the ONLY catalogue. Reading or registering it never seeds options. */
final class FamilyCatalog
{
    public const OPTION = 'cde_familias_series';
    public const FIELD = 'field_cde_familias_series';
    private const CODE = 'field_cde_familia_codigo';
    private const NAME = 'field_cde_familia_nombre';
    private const ICON = 'field_cde_familia_icono';
    private const BACKGROUND = 'field_cde_familia_color_fondo';
    private const TEXT = 'field_cde_familia_color_texto';

    public static function boot(): void
    {
        // Sage registers «Opciones» on acf/init at priority 100.
        add_action('acf/init', [self::class, 'register'], 120);
        add_filter('acf/validate_value/key=' . self::FIELD, [self::class, 'validate'], 20, 4);
        // update_field() does not run ACF's form validation; stop BEFORE repeater writes its subfields.
        add_filter('acf/pre_update_value', [self::class, 'guardWrite'], 1, 4);
    }

    public static function register(): void
    {
        if (!function_exists('acf_add_local_field_group') || !function_exists('acf_add_options_page')) {
            return;
        }
        $page = 'opciones';
        if (!acf_get_options_page($page)) {
            $page = 'cde-familias-series';
            acf_add_options_page(['page_title' => 'Familias de series', 'menu_title' => 'Familias de series',
                'menu_slug' => $page, 'capability' => 'edit_theme_options', 'redirect' => false, 'post_id' => 'options']);
        }
        acf_add_local_field_group([
            'key' => 'group_cde_familias_series', 'title' => 'Familias de series', 'active' => true, 'show_in_rest' => false,
            'location' => [[['param' => 'options_page', 'operator' => '==', 'value' => $page]]],
            'fields' => [[
                'key' => self::FIELD, 'name' => self::OPTION, 'label' => 'Familias de series', 'type' => 'repeater',
                'instructions' => 'Una fila por familia. El código identifica la familia aunque cambien el nombre, el icono o el orden. Retira sus asignaciones antes de borrar o cambiar un código en uso.',
                'layout' => 'table', 'button_label' => 'Añadir familia', 'pagination' => false,
                'sub_fields' => [
                    ['key' => self::CODE, 'name' => 'codigo', 'label' => 'Código estable (01–99)', 'type' => 'text', 'required' => 1, 'maxlength' => 2],
                    ['key' => self::NAME, 'name' => 'nombre', 'label' => 'Nombre', 'type' => 'text', 'required' => 1],
                    ['key' => self::ICON, 'name' => 'icono', 'label' => 'Icono', 'type' => 'image', 'return_format' => 'id', 'required' => 0, 'preview_size' => 'thumbnail'],
                    ['key' => self::BACKGROUND, 'name' => 'color_fondo', 'label' => 'Color de fondo', 'type' => 'color_picker', 'required' => 0,
                        'enable_opacity' => false, 'return_format' => 'string', 'instructions' => 'Opcional. Déjalo vacío para usar el fondo predeterminado.'],
                    ['key' => self::TEXT, 'name' => 'color_texto', 'label' => 'Color de texto', 'type' => 'radio', 'layout' => 'horizontal',
                        'choices' => ['white' => 'Blanco', 'black' => 'Negro'], 'default_value' => 'white', 'return_format' => 'value'],
                ],
            ]],
        ]);
    }

    public static function isCode(mixed $code): bool
    {
        return is_string($code) && $code !== '00' && preg_match('/^[0-9]{2}$/D', $code) === 1;
    }

    private static function raw(): mixed
    {
        return function_exists('get_field') ? get_field(self::OPTION, 'option', false) : [];
    }

    /** Accept both ACF key-indexed form/raw rows and name-indexed programmatic rows. */
    private static function parse(mixed $value, bool $forWrite = false): array|string
    {
        if ($value === false || $value === null || $value === '') {
            return [];
        }
        if (!is_array($value)) {
            return 'El catálogo debe ser una lista de familias.';
        }
        $items = $names = $codes = [];
        foreach ($value as $key => $row) {
            if ($key === 'acfcloneindex') { continue; } // ACF empty row template, never data.
            if (!is_array($row)) { return 'Fila de familia no válida.'; }
            $code = $row[self::CODE] ?? $row['codigo'] ?? null;
            $name = $row[self::NAME] ?? $row['nombre'] ?? null;
            $icon = $row[self::ICON] ?? $row['icono'] ?? 0;
            if (!self::isCode($code)) { return 'Cada código debe ser texto de dos cifras entre 01 y 99; 00 está reservado.'; }
            if (isset($codes[$code])) { return 'Hay un código de familia duplicado: ' . $code . '.'; }
            if (!is_string($name) || trim($name) === '' || strip_tags($name) !== $name) { return 'Cada familia necesita un nombre de texto sin etiquetas HTML.'; }
            $name = trim($name);
            $normalized = function_exists('mb_strtolower') ? mb_strtolower($name, 'UTF-8') : strtolower($name);
            if (isset($names[$normalized])) { return 'Hay un nombre de familia duplicado.'; }
            if ($icon === '' || $icon === false) { $icon = 0; }
            $numericString = is_string($icon) && ctype_digit($icon);
            $integerId = is_int($icon) || $numericString;
            if (!$integerId || (int)$icon < 0) { return 'El icono debe ser el ID de una imagen de Medios.'; }
            $icon = (int)$icon;
            if ($forWrite && $icon && (get_post_type($icon) !== 'attachment' || !str_starts_with((string)get_post_mime_type($icon), 'image/'))) {
                return 'El icono debe ser una imagen existente de Medios.';
            }
            // Appearance is optional: old rows need no rewrite. Corrupt styling on disk must
            // never leak raw CSS or make a structurally valid family disappear on read.
            $background = $row[self::BACKGROUND] ?? $row['color_fondo'] ?? null;
            $text = $row[self::TEXT] ?? $row['color_texto'] ?? null;
            if ($background === '' || $background === false) { $background = null; }
            if ($background !== null && (!is_string($background) || preg_match('/^#[a-f0-9]{6}$/iD', $background) !== 1)) {
                if ($forWrite) { return 'El color de fondo debe estar vacío o tener el formato #RRGGBB.'; }
                $background = null;
            }
            if ($text === '' || $text === false || $text === null) { $text = 'white'; }
            if (!in_array($text, ['white', 'black'], true)) {
                if ($forWrite) { return 'Elige blanco o negro para el color de texto.'; }
                $text = 'white';
            }
            $items[] = ['code' => $code, 'slug' => $code, 'label' => $name, 'icon_id' => $icon,
                'background_color' => $background === null ? null : strtolower($background), 'text_color' => $text];
            $codes[$code] = $names[$normalized] = true;
        }
        return $items;
    }

    public static function items(): array
    {
        $items = self::parse(self::raw());
        return is_array($items) ? $items : []; // Invalid persisted data is never assignable.
    }

    public static function find(string $code): ?array
    {
        foreach (self::items() as $item) {
            if ($item['code'] === $code) { return $item; }
        }
        return null;
    }

    public static function validate(mixed $valid, mixed $value, array $field = [], string $input = ''): mixed
    {
        if ($valid !== true) { return $valid; }
        $items = self::parse($value, true);
        if (!is_array($items)) { return $items; }
        // Even a damaged/partial stored row must not accidentally allow dropping an assigned code.
        $oldCodes = [];
        foreach ((array)self::raw() as $row) {
            if (!is_array($row)) { continue; }
            $code = $row[self::CODE] ?? $row['codigo'] ?? null;
            if (self::isCode($code)) { $oldCodes[] = $code; }
        }
        $removed = array_values(array_diff($oldCodes, array_column($items, 'code')));
        if ($removed) {
            $used = get_terms(['taxonomy' => 'serie_cde', 'hide_empty' => false, 'fields' => 'ids', 'number' => 1,
                'meta_query' => [['key' => Families::META_KEY, 'value' => $removed, 'compare' => 'IN']]]);
            if (is_wp_error($used)) { return 'No se pudo verificar el uso de las familias. No se ha guardado el catálogo.'; }
            if ($used) { return 'No se puede eliminar ni cambiar el código de una familia asignada. Reasigna las series primero.'; }
        }
        return true;
    }

    public static function guardWrite(mixed $check, mixed $value, mixed $postId, array $field): mixed
    {
        if (($field['key'] ?? '') !== self::FIELD) { return $check; }
        if (!in_array($postId, ['option', 'options'], true) || !current_user_can('edit_theme_options') || self::validate(true, $value) !== true) { return false; }
        return $check;
    }
}

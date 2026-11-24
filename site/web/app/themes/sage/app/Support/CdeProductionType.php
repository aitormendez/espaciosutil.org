<?php

namespace App\Support;

/** Contrato de modalidad por lección. No inferir valores ni escribir en NocoDB. */
final class CdeProductionType
{
    public const FIELD_NAME = 'tipo_produccion';
    public const FIELD_KEY = 'field_cde_fields_tipo_produccion';
    public const ACTIVE_KEY = 'field_cde_fields_active_lesson';
    public const NO_VOICE = 'sin_voz';
    public const CHOICES = [
        'video_grabado' => 'Voz humana',
        'audio_sintético' => 'Voz sintética',
        self::NO_VOICE => 'Sin voz',
    ];

    public static function register(): void
    {
        add_filter('acf/validate_value/key=' . self::FIELD_KEY, [self::class, 'validateValue'], 10, 2);
        add_filter('acf/rest/get_field_schema/key=' . self::FIELD_KEY, [self::class, 'restSchema']);
        add_filter('rest_pre_insert_cde', [self::class, 'validateRestPair'], 10, 2);
    }

    public static function isValid(mixed $value): bool
    {
        return is_string($value) && array_key_exists($value, self::CHOICES);
    }

    public static function validateValue(mixed $valid, mixed $value): mixed
    {
        // Conservar errores de ACF u otros validadores: nunca convertirlos en éxito.
        if ($valid !== true) {
            return $valid;
        }

        $acf = $_POST['acf'] ?? [];
        return self::validatePair($value, is_array($acf) ? ($acf[self::ACTIVE_KEY] ?? null) : null);
    }

    private static function validatePair(mixed $value, mixed $active): bool|string
    {
        if (!self::isValid($value)) {
            return 'Selecciona una modalidad de producción.';
        }
        if ($value === self::NO_VOICE && !in_array($active, [false, 0, '0'], true)) {
            return 'Una lección activa necesita Voz humana o Voz sintética. Sin voz solo se admite en lecciones inactivas.';
        }
        return true;
    }

    public static function validateRestPair(mixed $prepared, \WP_REST_Request $request): mixed
    {
        $acf = $request->get_param('acf');
        if (is_wp_error($prepared) || !is_array($acf) || (!array_key_exists(self::FIELD_NAME, $acf) && !array_key_exists('active_lesson', $acf))) {
            return $prepared;
        }
        // Partial updates use the stored counterpart. Missing activity means active.
        $id = (int) ($request->get_url_params()['id'] ?? 0);
        $active = array_key_exists('active_lesson', $acf) ? $acf['active_lesson'] : ($id ? get_post_meta($id, 'active_lesson', true) : null);
        $voice = array_key_exists(self::FIELD_NAME, $acf) ? $acf[self::FIELD_NAME] : ($id ? get_post_meta($id, self::FIELD_NAME, true) : null);
        $valid = self::validatePair($voice, $active);
        return $valid === true ? $prepared : new \WP_Error('cde_invalid_production_type', $valid, ['status' => 400]);
    }

    public static function restSchema(array $schema): array
    {
        // allow_null evita la preselección del primer radio en el editor, pero
        // no autoriza guardar null ni vaciar la modalidad mediante REST.
        $schema['type'] = 'string';
        $schema['enum'] = array_keys(self::CHOICES);
        $schema['required'] = true;
        unset($schema['default']);

        return $schema;
    }
}

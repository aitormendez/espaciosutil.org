<?php

namespace App\Support;

/** Contrato de modalidad por lección. No inferir valores ni escribir en NocoDB. */
final class CdeProductionType
{
    public const FIELD_NAME = 'tipo_produccion';
    public const FIELD_KEY = 'field_cde_fields_tipo_produccion';
    public const CHOICES = [
        'video_grabado' => 'Voz humana',
        'audio_sintético' => 'Voz sintética',
    ];

    public static function register(): void
    {
        add_filter('acf/validate_value/key=' . self::FIELD_KEY, [self::class, 'validateValue'], 10, 4);
        add_filter('acf/rest/get_field_schema/key=' . self::FIELD_KEY, [self::class, 'restSchema'], 10, 2);
    }

    public static function isValid($value): bool
    {
        return is_string($value) && array_key_exists($value, self::CHOICES);
    }

    public static function validateValue($valid, $value, array $field, $input)
    {
        // Conservar errores de ACF u otros validadores: nunca convertirlos en éxito.
        if ($valid !== true) {
            return $valid;
        }

        return self::isValid($value)
            ? true
            : 'Selecciona Voz humana o Voz sintética. La modalidad es obligatoria.';
    }

    public static function restSchema(array $schema, array $field): array
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

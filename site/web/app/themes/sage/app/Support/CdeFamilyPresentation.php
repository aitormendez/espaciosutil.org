<?php

declare(strict_types=1);

namespace App\Support;

/** Display adapter only. These historical designs are not catalog entries or assignments. */
final class CdeFamilyPresentation
{
    private const LEGACY_ART = [
        'espirituales-esenciales' => ['Serie esencial', 'Series esenciales', 'mercaba', '--color-serie-esencial'],
        'otras-principales' => ['Serie principal', 'Series principales', 'torus', '--color-serie-principal'],
        'otras-espirituales' => ['Serie espiritual', 'Series espirituales', 'triangle-spiral', '--color-serie-espiritual'],
        'informacion-complementaria' => ['Serie complementaria', 'Series complementarias', 'cubes', '--color-serie-complementaria'],
    ];

    public static function fromResolution(mixed $result, bool $collection = false): ?array
    {
        if ($result instanceof \WP_Error && $result->get_error_code() === 'cde_family_legacy') {
            $slug = $result->get_error_data()['legacy_value'] ?? null;
            if (!is_string($slug) || !isset(self::LEGACY_ART[$slug])) {
                return null;
            }
            [$single, $plural, $symbol, $color] = self::LEGACY_ART[$slug];
            return ['slug' => $slug, 'label' => $collection ? $plural : $single,
                'icon' => 'cdefamily-' . $symbol, 'icon_id' => 0, 'color_token' => $color, 'background_color' => null,
                'text_color' => $slug === 'informacion-complementaria' ? 'black' : 'white', 'legacy' => true];
        }

        if (!is_array($result) || !is_string($result['familia_serie'] ?? null) || $result['familia_serie'] === ''
            || !is_string($result['label'] ?? null) || trim($result['label']) === '') {
            return null;
        }
        // No presentation guesses from the code, position, series title or icon absence.
        return ['slug' => $result['familia_serie'], 'label' => $result['label'], 'icon' => null,
            'icon_id' => $result['icon_id'] ?? 0, 'color_token' => '--color-cde',
            'background_color' => is_string($result['background_color'] ?? null) && preg_match('/^#[0-9a-f]{6}$/iD', $result['background_color']) ? $result['background_color'] : null,
            'text_color' => ($result['text_color'] ?? '') === 'black' ? 'black' : 'white', 'legacy' => false];
    }

    /** Historical groups retain their display order only while series still have old metadata. */
    public static function legacyOrder(): array
    {
        return array_keys(self::LEGACY_ART);
    }
}

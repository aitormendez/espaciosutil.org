<?php

declare(strict_types=1);

namespace App\Support;

use EspacioSutil\Cde\Families;

/** Read-only presentation data. Strings are data, not HTML: escape at render time. */
final class CdeLessonMetadata
{
    private const PRODUCTION_ICONS = [
        'video_grabado' => 'tabler-robot-off',
        'audio_sintético' => 'tabler-robot',
    ];

    /**
     * @return array{
     *   family: array{slug:string,label:string,icon:string,color_token:string}|null,
     *   series_name: string|null,
     *   production: array{value:string,label:string,icon:string}|null
     * }
     */
    public static function forLesson(int $postId): array
    {
        $data = ['family' => null, 'series_name' => null, 'production' => null];
        if ($postId < 1 || !function_exists('get_post')) {
            return $data;
        }

        $post = get_post($postId);
        if (!$post || ($post->post_type ?? null) !== 'cde') {
            return $data;
        }

        if (function_exists('cde_lesson_family_presentation')) {
            // Preserve the existing vocabulary, symbols and color tokens verbatim.
            $data['family'] = \cde_lesson_family_presentation($postId);
        }
        $data['series_name'] = self::seriesName($postId);
        $data['production'] = self::production($postId);

        return $data;
    }

    private static function seriesName(int $postId): ?string
    {
        if (!class_exists(Families::class) || !function_exists('get_term')) {
            return null;
        }

        $resolved = Families::resolveLesson($postId);
        // An unassigned error may identify the first root before checking other
        // roots. Never reuse error data as a supposedly unambiguous series.
        if (!is_array($resolved) || !is_int($resolved['series_id'] ?? null) || $resolved['series_id'] < 1) {
            return null;
        }

        $root = get_term($resolved['series_id'], 'serie_cde');
        if (!is_object($root)
            || ($root->taxonomy ?? null) !== 'serie_cde'
            || (int) ($root->parent ?? -1) !== 0
            || !is_string($root->name ?? null)
            || trim($root->name) === '') {
            return null;
        }

        return $root->name;
    }

    /** Only the lesson's own single canonical meta value; no ACF defaults/inheritance. */
    private static function production(int $postId): ?array
    {
        if (!function_exists('get_post_meta')) {
            return null;
        }

        $values = get_post_meta($postId, CdeProductionType::FIELD_NAME, false);
        if (!is_array($values) || count($values) !== 1) {
            return null;
        }
        $value = reset($values);
        if (!CdeProductionType::isValid($value) || !isset(self::PRODUCTION_ICONS[$value])) {
            return null;
        }

        return [
            'value' => $value,
            'label' => CdeProductionType::CHOICES[$value],
            'icon' => self::PRODUCTION_ICONS[$value],
        ];
    }
}

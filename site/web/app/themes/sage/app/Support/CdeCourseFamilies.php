<?php

declare(strict_types=1);

namespace App\Support;

use EspacioSutil\Cde\Families;

/** Read-only grouping; the backend, never names or course codes, owns classification. */
final class CdeCourseFamilies
{
    /** Collection vocabulary; symbols and colors match lesson presentation. */
    private const PRESENTATION = [
        'espirituales-esenciales' => [
            'label' => 'Series esenciales',
            'icon' => 'cdefamily-mercaba',
            'color_token' => '--color-serie-esencial',
        ],
        'otras-principales' => [
            'label' => 'Series principales',
            'icon' => 'cdefamily-torus',
            'color_token' => '--color-serie-principal',
        ],
        'otras-espirituales' => [
            'label' => 'Series espirituales',
            'icon' => 'cdefamily-triangle-spiral',
            'color_token' => '--color-serie-espiritual',
        ],
        'informacion-complementaria' => [
            'label' => 'Series complementarias',
            'icon' => 'cdefamily-cubes',
            'color_token' => '--color-serie-complementaria',
        ],
    ];

    /**
     * Families follow the collection order above; series retain their relative order.
     * Every input record (including its blocks and unknown properties) is kept
     * verbatim. Unresolvable records share one neutral group, always at the end.
     * Strings remain data: escape them at render time.
     *
     * @return list<array{key:string,label:string,icon:?string,color_token:string,series:array}>
     */
    public static function group(array $series): array
    {
        if ($series === []) {
            return [];
        }

        $groups = [];
        $unassigned = [];
        $canResolve = class_exists(Families::class) && is_callable([Families::class, 'resolveSeries']);

        foreach ($series as $entry) {
            $id = is_array($entry) ? ($entry['term_id'] ?? null) : null;
            // WP term IDs are integers; tolerate canonical decimal strings only.
            // Never coerce bools, floats, names or numeric prefixes into term IDs.
            if (is_string($id) && ctype_digit($id)) {
                $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
            }
            $resolved = $canResolve && is_int($id) && $id > 0 ? Families::resolveSeries($id) : null;
            $slug = is_array($resolved) ? ($resolved['familia_serie'] ?? null) : null;

            if (!is_string($slug) || !isset(self::PRESENTATION[$slug])) {
                $unassigned[] = $entry;
                continue;
            }

            if (!isset($groups[$slug])) {
                $groups[$slug] = ['key' => $slug] + self::PRESENTATION[$slug] + ['series' => []];
            }
            $groups[$slug]['series'][] = $entry;
        }

        $ordered = [];
        foreach (self::PRESENTATION as $slug => $_presentation) {
            if (isset($groups[$slug])) {
                $ordered[] = $groups[$slug];
            }
        }

        if ($unassigned !== []) {
            $ordered[] = [
                'key' => 'unassigned',
                'label' => 'Sin familia asignada',
                'icon' => null,
                'color_token' => '--color-cde',
                'series' => $unassigned,
            ];
        }

        return $ordered;
    }
}

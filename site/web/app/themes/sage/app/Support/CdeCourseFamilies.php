<?php

declare(strict_types=1);

namespace App\Support;

use EspacioSutil\Cde\Families;

use function is_array;
use function is_int;
use function is_string;

/** Read-only grouping; the backend, never names or course codes, owns classification. */
final class CdeCourseFamilies
{
    /**
     * Families follow the editable catalog order; series retain their relative order.
     * Every input record (including its blocks and unknown properties) is kept
     * verbatim. Unresolvable records share one neutral group, always at the end.
     * Strings remain data: escape them at render time.
     *
     * @return list<array{key:string,label:string,icon:?string,icon_id:int,legacy:bool,color_token:string,background_color:?string,text_color:string,series:array}>
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
            $display = CdeFamilyPresentation::fromResolution($resolved, true);
            if ($display === null) {
                $unassigned[] = $entry;
                continue;
            }
            $slug = $display['slug'];
            if (!isset($groups[$slug])) {
                unset($display['slug']);
                $groups[$slug] = ['key' => $slug] + $display + ['series' => []];
            }
            $groups[$slug]['series'][] = $entry;
        }

        $ordered = [];
        $order = $canResolve ? array_column(Families::catalog(), 'slug') : [];
        foreach (array_merge($order, CdeFamilyPresentation::legacyOrder()) as $slug) {
            if (isset($groups[$slug])) {
                $ordered[] = $groups[$slug];
            }
        }

        if ($unassigned !== []) {
            $ordered[] = [
                'key' => 'unassigned',
                'label' => 'Otras series',
                'icon' => null, 'icon_id' => 0, 'legacy' => false,
                'color_token' => '--color-cde', 'background_color' => null, 'text_color' => 'white',
                'series' => $unassigned,
            ];
        }

        return $ordered;
    }
}

<?php

namespace App\View\Composers\Concerns;

trait InteractsWithCdeMedia
{
    /**
     * Build lesson subindex from ACF repeater.
     *
     * @param int|null $postId
     * @return array
     */
    protected function buildLessonSubindex(?int $postId = null): array
    {
        if (!function_exists('get_field')) {
            return ['items' => [], 'chapters' => []];
        }

        $rawItems = get_field('lesson_subindex_items', $postId ?: false) ?: [];

        if (!is_array($rawItems) || empty($rawItems)) {
            return ['items' => [], 'chapters' => []];
        }

        $tree = [];
        $chapters = [];
        $stack = [
            0 => &$tree,
        ];

        foreach ($rawItems as $rawItem) {
            $level = $this->resolveLevel($rawItem['level'] ?? 1);

            $title = is_string($rawItem['title'] ?? null) ? trim($rawItem['title']) : '';
            if ($title === '') {
                continue;
            }

            $description = is_string($rawItem['description'] ?? null) ? trim($rawItem['description']) : null;
            $anchor = null;
            if (is_string($rawItem['anchor'] ?? null)) {
                $slug = trim($rawItem['anchor']);
                if ($slug !== '') {
                    $anchor = function_exists('sanitize_title') ? sanitize_title($slug) : $slug;
                }
            }

            $timecode = $this->normalizeTimecode($rawItem['timecode'] ?? null);

            $parentLevel = $level - 1;

            while ($parentLevel > 0 && !isset($stack[$parentLevel])) {
                $parentLevel--;
            }

            if (!isset($stack[$parentLevel])) {
                $parentLevel = 0;
                $stack[$parentLevel] = &$tree;
            }

            $node = [
                'title' => $title,
                'description' => $description ?: null,
                'anchor' => $anchor,
                'timecode' => $timecode,
                'level' => $level,
                'children' => [],
            ];

            $stack[$parentLevel][] = $node;
            $parent = &$stack[$parentLevel];
            $lastKey = array_key_last($parent);

            $stack[$level] = &$parent[$lastKey]['children'];

            foreach ($stack as $stackLevel => $_) {
                if ($stackLevel > $level) {
                    unset($stack[$stackLevel]);
                }
            }

            if ($timecode !== null) {
                $chapters[] = [
                    'title' => $title,
                    'description' => $description ?: null,
                    'anchor' => $anchor,
                    'time' => $timecode['seconds'],
                    'time_label' => $timecode['label'],
                    'level' => $level,
                ];
            }
        }

        return [
            'items' => $tree,
            'chapters' => $chapters,
        ];
    }

    /**
     * Prepara la información de video/audio destacado para la vista.
     *
     * @param array $chapters
     * @param int|null $postId
     * @return array
     */
    protected function prepareFeaturedMedia(array $chapters, ?int $postId = null): array
    {
        $pull_zone = getenv('BUNNY_PULL_ZONE') ?: null;

        $video = $this->resolveMediaEntry('featured_video', $pull_zone, $chapters, $postId);
        $audio = $this->resolveMediaEntry('featured_audio', $pull_zone, $chapters, $postId);

        return [
            'video' => $video,
            'audio' => $audio,
            'pull_zone' => $pull_zone,
            'has_video' => $video !== null,
            'has_audio' => $audio !== null,
        ];
    }

    /**
     * Resuelve los campos ACF de un medio destacado.
     *
     * @param string $fieldPrefix
     * @param string|null $pullZone
     * @param array $chapters
     * @param int|null $postId
     * @return array|null
     */
    protected function resolveMediaEntry(string $fieldPrefix, ?string $pullZone, array $chapters, ?int $postId = null): ?array
    {
        if (!function_exists('get_field')) {
            return null;
        }

        $context = $postId ?: false;
        $idRaw = get_field("{$fieldPrefix}_id", $context);
        $libraryRaw = get_field("{$fieldPrefix}_library_id", $context);
        $nameRaw = get_field("{$fieldPrefix}_name", $context);

        $mediaId = is_scalar($idRaw) ? trim((string) $idRaw) : '';

        if ($mediaId === '') {
            return null;
        }

        $libraryId = is_scalar($libraryRaw) ? trim((string) $libraryRaw) : '';
        if ($libraryId === '') {
            $libraryId = '457097';
        }

        $name = is_string($nameRaw) ? trim($nameRaw) : '';
        $name = $name !== '' ? $name : null;

        $defaultHlsUrl = null;
        $defaultThumbnailUrl = null;

        if ($pullZone) {
            $defaultHlsUrl = sprintf('https://%s.b-cdn.net/%s/playlist.m3u8', $pullZone, $mediaId);
            $defaultThumbnailUrl = sprintf('https://%s.b-cdn.net/%s/thumbnail.jpg', $pullZone, $mediaId);
        }

        return [
            'type' => $fieldPrefix === 'featured_audio' ? 'audio' : 'video',
            'id' => $mediaId,
            'library_id' => $libraryId,
            'name' => $name,
            'chapters' => $chapters,
            'default_hls_url' => $defaultHlsUrl,
            'default_thumbnail_url' => $defaultThumbnailUrl,
        ];
    }

    /**
     * Extract numeric level from ACF value.
     *
     * @param mixed $value
     * @return int
     */
    protected function resolveLevel($value): int
    {
        if (is_int($value)) {
            return max(1, min(4, $value));
        }

        if (is_string($value)) {
            $value = trim($value);
            if ($value === '') {
                return 1;
            }

            if (ctype_digit($value)) {
                return max(1, min(4, (int) $value));
            }

            if (preg_match('/\d+/', $value, $matches)) {
                return max(1, min(4, (int) $matches[0]));
            }
        }

        return 1;
    }

    /**
     * Normalize timecode string to seconds and formatted label.
     *
     * @param mixed $value
     * @return array|null
     */
    protected function normalizeTimecode($value): ?array
    {
        if (!is_string($value)) {
            return null;
        }

        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $parts = array_map('trim', explode(':', $value));
        if (count($parts) < 2 || count($parts) > 3) {
            return null;
        }

        $hours = 0;
        $minutes = 0;
        $seconds = 0;

        if (count($parts) === 3) {
            [$hours, $minutes, $seconds] = $parts;
        } else {
            [$minutes, $seconds] = $parts;
        }

        if (!ctype_digit((string) $minutes) || !ctype_digit((string) $seconds)) {
            return null;
        }

        $hours = ctype_digit((string) $hours) ? (int) $hours : 0;
        $minutes = (int) $minutes;
        $seconds = (int) $seconds;

        if ($minutes > 59 || $seconds > 59) {
            return null;
        }

        $totalSeconds = ($hours * 3600) + ($minutes * 60) + $seconds;

        $label = $hours > 0
            ? sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds)
            : sprintf('%02d:%02d', $minutes, $seconds);

        return [
            'seconds' => $totalSeconds,
            'label' => $label,
        ];
    }
}

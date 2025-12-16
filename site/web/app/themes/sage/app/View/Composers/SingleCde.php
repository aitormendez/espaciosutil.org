<?php

namespace App\View\Composers;

use Roots\Acorn\View\Composer;


class SingleCde extends Composer
{
    /**
     * Las plantillas que usará este composer.
     *
     * @var array
     */
    protected static $views = [
        'partials.content-single-cde',
    ];

    /**
     * Variables disponibles para la vista.
     *
     * @return array
     */
    public function with()
    {
        $user_id = get_current_user_id();
        $completed_lessons = $user_id ? (get_user_meta($user_id, 'cde_completed_lessons', true) ?: []) : [];
        // Respetar las restricciones por entrada de PMPro (metabox/taxonomías).
        // Fallback: si no existe PMPro, no conceder acceso por defecto.
        $has_access = false;
        if (function_exists('pmpro_has_membership_access')) {
            $post_id = get_the_ID();

            if ($post_id) {
                [$access] = pmpro_has_membership_access($post_id, $user_id, true);
                $has_access = (bool) $access;
            }
        }

        $related_lessons_posts = get_field('cde_related_lessons');
        $related_lessons = [];

        if ($related_lessons_posts) {
            $bunny_pull_zone = getenv('BUNNY_PULL_ZONE');
            foreach ($related_lessons_posts as $post) {
                setup_postdata($post);
                $featured_video_id = get_field('featured_video_id', $post->ID);

                $poster_url = null;
                if ($featured_video_id && $bunny_pull_zone) {
                    $poster_url = "https://{$bunny_pull_zone}.b-cdn.net/{$featured_video_id}/thumbnail.jpg";
                }

                $related_lessons[] = [
                    'title' => get_the_title($post->ID),
                    'permalink' => get_permalink($post->ID),
                    'poster_url' => $poster_url,
                ];
            }
            wp_reset_postdata();
        }

        $lesson_subindex = $this->buildLessonSubindex();

        return [
            'is_completed' => in_array(get_the_ID(), $completed_lessons, true),
            'has_access' => $has_access,
            'related_lessons' => $related_lessons,
            'lesson_subindex' => $lesson_subindex,
            'lesson_subindex_root_title' => get_the_title(),
            'lesson_quiz' => $this->buildLessonQuiz(),
            'featured_media' => $this->prepareFeaturedMedia($lesson_subindex['chapters'] ?? []),
        ];
    }

    /**
     * Build lesson subindex from ACF repeater.
     *
     * @return array
     */
    protected function buildLessonSubindex(): array
    {
        if (!function_exists('get_field')) {
            return ['items' => [], 'chapters' => []];
        }

        $rawItems = get_field('lesson_subindex_items') ?: [];

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
     * Prepara los datos del cuestionario desde ACF.
     *
     * @return array
     */
    protected function buildLessonQuiz(): array
    {
        if (!function_exists('get_field')) {
            return [
                'enabled' => false,
                'questions' => [],
                'count' => 0,
                'post_id' => get_the_ID(),
            ];
        }

        $enabledRaw = get_field('quiz_enabled');
        $rawQuestions = get_field('quiz_questions') ?: [];

        $questions = [];

        foreach ($rawQuestions as $rawQuestion) {
            $questionText = is_string($rawQuestion['question'] ?? null) ? trim($rawQuestion['question']) : '';

            if ($questionText === '') {
                continue;
            }

            $rawAnswers = $rawQuestion['answers'] ?? [];

            if (!is_array($rawAnswers) || empty($rawAnswers)) {
                continue;
            }

            $answers = [];
            $correctCount = 0;

            foreach ($rawAnswers as $rawAnswer) {
                $answerText = is_string($rawAnswer['answer_text'] ?? null) ? trim($rawAnswer['answer_text']) : '';

                if ($answerText === '') {
                    continue;
                }

                $isCorrect = !empty($rawAnswer['is_correct']);

                $answers[] = [
                    'text' => $answerText,
                    'is_correct' => $isCorrect,
                ];

                if ($isCorrect) {
                    $correctCount++;
                }
            }

            if (count($answers) < 2 || $correctCount < 1) {
                continue;
            }

            $questions[] = [
                'question' => $questionText,
                'answers' => $answers,
            ];
        }

        $enabled = (bool) $enabledRaw && !empty($questions);

        return [
            'enabled' => $enabled,
            'questions' => $questions,
            'count' => count($questions),
            'post_id' => get_the_ID(),
        ];
    }

    /**
     * Prepara la información de video/audio destacado para la vista.
     *
     * @param array $chapters
     * @return array
     */
    protected function prepareFeaturedMedia(array $chapters): array
    {
        $pull_zone = getenv('BUNNY_PULL_ZONE') ?: null;

        $video = $this->resolveMediaEntry('featured_video', $pull_zone, $chapters);
        $audio = $this->resolveMediaEntry('featured_audio', $pull_zone, $chapters);

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
     * @param string $field_prefix
     * @param string|null $pull_zone
     * @param array $chapters
     * @return array|null
     */
    protected function resolveMediaEntry(string $field_prefix, ?string $pull_zone, array $chapters): ?array
    {
        if (!function_exists('get_field')) {
            return null;
        }

        $id_raw = get_field("{$field_prefix}_id");
        $library_raw = get_field("{$field_prefix}_library_id");
        $name_raw = get_field("{$field_prefix}_name");

        $media_id = is_scalar($id_raw) ? trim((string) $id_raw) : '';

        if ($media_id === '') {
            return null;
        }

        $library_id = is_scalar($library_raw) ? trim((string) $library_raw) : '';
        if ($library_id === '') {
            $library_id = '457097';
        }

        $name = is_string($name_raw) ? trim($name_raw) : '';
        $name = $name !== '' ? $name : null;

        $default_hls_url = null;
        $default_thumbnail_url = null;

        if ($pull_zone) {
            $default_hls_url = sprintf('https://%s.b-cdn.net/%s/playlist.m3u8', $pull_zone, $media_id);
            $default_thumbnail_url = sprintf('https://%s.b-cdn.net/%s/thumbnail.jpg', $pull_zone, $media_id);
        }

        return [
            'type' => $field_prefix === 'featured_audio' ? 'audio' : 'video',
            'id' => $media_id,
            'library_id' => $library_id,
            'name' => $name,
            'chapters' => $chapters,
            'default_hls_url' => $default_hls_url,
            'default_thumbnail_url' => $default_thumbnail_url,
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

<?php

namespace App\View\Composers;

use App\View\Composers\Concerns\InteractsWithCdeMedia;
use Roots\Acorn\View\Composer;


class SingleCde extends Composer
{
    use InteractsWithCdeMedia;

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
            foreach ($related_lessons_posts as $related_lesson_raw) {
                $related_lesson = $this->resolveRelatedLessonPost($related_lesson_raw);

                if (!$related_lesson) {
                    continue;
                }

                $featured_video_id = get_field('featured_video_id', $related_lesson->ID);

                $poster_url = null;
                if ($featured_video_id && $bunny_pull_zone) {
                    $poster_url = "https://{$bunny_pull_zone}.b-cdn.net/{$featured_video_id}/thumbnail.jpg";
                }

                $related_lessons[] = [
                    'title' => get_the_title($related_lesson->ID),
                    'permalink' => get_permalink($related_lesson->ID),
                    'poster_url' => $poster_url,
                ];
            }
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
     * Normalize related lesson values from ACF to a WP_Post instance.
     *
     * @param mixed $value
     * @return \WP_Post|null
     */
    protected function resolveRelatedLessonPost($value): ?\WP_Post
    {
        if ($value instanceof \WP_Post) {
            return $value;
        }

        if (is_object($value) && isset($value->ID) && is_scalar($value->ID)) {
            $post = get_post((int) $value->ID);

            return $post instanceof \WP_Post ? $post : null;
        }

        if (is_scalar($value)) {
            $post = get_post((int) $value);

            return $post instanceof \WP_Post ? $post : null;
        }

        return null;
    }

}

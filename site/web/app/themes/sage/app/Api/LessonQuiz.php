<?php

namespace App\Api;

use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

class LessonQuiz
{
    public function register_routes(): void
    {
        register_rest_route('cde/v1', '/quiz/result', [
            'methods' => WP_REST_Server::READABLE, // GET
            'callback' => [$this, 'getResult'],
            'permission_callback' => fn () => is_user_logged_in(),
        ]);

        register_rest_route('cde/v1', '/quiz/submit', [
            'methods' => WP_REST_Server::CREATABLE, // POST
            'callback' => [$this, 'submit'],
            'permission_callback' => fn () => is_user_logged_in(),
        ]);
    }

    public function getResult(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $postId = absint($request->get_param('post_id'));
        $userId = get_current_user_id();

        if (!$postId || !$userId) {
            return new WP_Error('invalid_request', 'Faltan parámetros.', ['status' => 400]);
        }

        $result = get_user_meta($userId, $this->metaKey($postId), true);

        return new WP_REST_Response([
            'exists' => !empty($result),
            'result' => $result ?: null,
        ], 200);
    }

    public function submit(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        $postId = absint($request->get_param('post_id'));
        $userId = get_current_user_id();
        $answersPayload = $request->get_param('answers');

        if (!$postId || !$userId) {
            return new WP_Error('invalid_request', 'Faltan parámetros.', ['status' => 400]);
        }

        if (!function_exists('get_field')) {
            return new WP_Error('acf_missing', 'ACF no está disponible.', ['status' => 500]);
        }

        $enabled = (bool) get_field('quiz_enabled', $postId);
        if (!$enabled) {
            return new WP_Error('quiz_disabled', 'El cuestionario no está activo para esta lección.', ['status' => 400]);
        }

        $questions = get_field('quiz_questions', $postId) ?: [];

        if (empty($questions)) {
            return new WP_Error('no_questions', 'No hay preguntas configuradas.', ['status' => 400]);
        }

        if (!is_array($answersPayload)) {
            return new WP_Error('invalid_answers', 'El formato de respuestas es inválido.', ['status' => 400]);
        }

        $normalizedQuestions = $this->normalizeQuestions($questions);

        $result = $this->evaluate($normalizedQuestions, $answersPayload);

        if (is_wp_error($result)) {
            return $result;
        }

        update_user_meta($userId, $this->metaKey($postId), $result);

        return new WP_REST_Response([
            'saved' => true,
            'result' => $result,
        ], 200);
    }

    protected function normalizeQuestions(array $questions): array
    {
        $normalized = [];

        foreach ($questions as $index => $question) {
            $questionText = is_string($question['question'] ?? null) ? trim($question['question']) : '';
            if ($questionText === '') {
                continue;
            }

            $rawAnswers = $question['answers'] ?? [];
            $answers = [];

            foreach ($rawAnswers as $answerIndex => $answer) {
                $text = is_string($answer['answer_text'] ?? null) ? trim($answer['answer_text']) : '';
                if ($text === '') {
                    continue;
                }

                $answers[] = [
                    'text' => $text,
                    'is_correct' => !empty($answer['is_correct']),
                ];
            }

            if (count($answers) < 2) {
                continue;
            }

            $normalized[] = [
                'index' => $index,
                'question' => $questionText,
                'answers' => $answers,
            ];
        }

        return $normalized;
    }

    protected function evaluate(array $questions, array $answersPayload): array|WP_Error
    {
        $total = count($questions);
        if ($total === 0) {
            return new WP_Error('no_questions', 'No hay preguntas configuradas.', ['status' => 400]);
        }

        $correctCount = 0;
        $evaluated = [];

        foreach ($questions as $position => $question) {
            $userSelection = $this->findUserSelection($answersPayload, $position);

            if (is_wp_error($userSelection)) {
                return $userSelection;
            }

            $correctIndexes = [];
            foreach ($question['answers'] as $i => $answer) {
                if (!empty($answer['is_correct'])) {
                    $correctIndexes[] = $i;
                }
            }

            if (empty($correctIndexes)) {
                // Si no hay correctas configuradas, consideramos la pregunta inválida
                $evaluated[] = [
                    'question' => $question['question'],
                    'correct' => false,
                    'selected' => $userSelection,
                    'correct_indexes' => $correctIndexes,
                ];
                continue;
            }

            sort($correctIndexes);
            $selectedSorted = $userSelection;
            sort($selectedSorted);

            $isCorrect = $selectedSorted === $correctIndexes;

            if ($isCorrect) {
                $correctCount++;
            }

            $evaluated[] = [
                'question' => $question['question'],
                'correct' => $isCorrect,
                'selected' => $userSelection,
                'correct_indexes' => $correctIndexes,
            ];
        }

        return [
            'correct' => $correctCount,
            'total' => $total,
            'percentage' => $total > 0 ? round(($correctCount / $total) * 100, 2) : 0,
            'answers' => $evaluated,
            'saved_at' => current_time('mysql'),
        ];
    }

    /**
     * Obtiene la selección del usuario para una posición de pregunta.
     *
     * @param array $answersPayload
     * @param int $position
     * @return array|WP_Error
     */
    protected function findUserSelection(array $answersPayload, int $position): array|WP_Error
    {
        $matches = array_values(array_filter($answersPayload, function ($entry) use ($position) {
            return isset($entry['question_index']) && (int) $entry['question_index'] === $position;
        }));

        if (empty($matches)) {
            return new WP_Error('missing_selection', sprintf('Falta la respuesta de la pregunta %d.', $position + 1), ['status' => 400]);
        }

        $selected = $matches[0]['selected'] ?? [];

        if (!is_array($selected)) {
            return new WP_Error('invalid_selection', sprintf('Formato inválido en la pregunta %d.', $position + 1), ['status' => 400]);
        }

        $clean = [];

        foreach ($selected as $value) {
            if (is_numeric($value)) {
                $clean[] = (int) $value;
            }
        }

        // Permitir 0 selecciones si el usuario envía vacío; la evaluación marcará incorrecto.
        return array_values(array_unique($clean));
    }

    protected function metaKey(int $postId): string
    {
        return 'cde_quiz_result_' . $postId;
    }
}

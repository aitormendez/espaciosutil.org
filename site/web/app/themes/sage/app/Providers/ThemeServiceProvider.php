<?php

namespace App\Providers;

use Roots\Acorn\Sage\SageServiceProvider;

class ThemeServiceProvider extends SageServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        parent::register();
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        parent::boot();

        // Asignar rol "estudiante" si el pedido completado incluye un curso
        add_action('woocommerce_order_status_completed', [$this, 'asignarRolEstudiantePorCategoria']);
        add_action('acf/save_post', [$this, 'importLessonSubindexFromJson'], 20);
        add_action('acf/save_post', [$this, 'importLessonQuizFromJson'], 20);
        add_action('admin_notices', [$this, 'renderAcfFallbackNotices']);
    }

    public function asignarRolEstudiantePorCategoria($order_id)
    {
        $order = wc_get_order($order_id);
        $user_id = $order->get_user_id();

        if (!$user_id) {
            return;
        }

        $user = get_user_by('id', $user_id);
        if (!$user) {
            return;
        }

        foreach ($order->get_items() as $item) {
            $product = wc_get_product($item->get_product_id());
            if (!$product) {
                continue;
            }

            // Verifica si el producto pertenece a la categoría "cursos"
            if (has_term('curso', 'product_cat', $product->get_id())) {
                if (!in_array('estudiante', (array) $user->roles)) {
                    $user->add_role('estudiante');
                }
                break;
            }
        }
    }

    /**
     * Importa el subíndice desde el campo JSON y lo vuelca en el repeater.
     *
     * @param int|string $postId
     * @return void
     */
    public function importLessonSubindexFromJson($postId): void
    {
        if (!is_numeric($postId)) {
            return;
        }

        $postId = (int) $postId;

        if ($postId <= 0) {
            return;
        }

        if (\wp_is_post_revision($postId) || \wp_is_post_autosave($postId)) {
            return;
        }

        if (\get_post_type($postId) !== 'cde') {
            return;
        }

        $rawJson = \get_field('lesson_subindex_import', $postId);

        if (empty($rawJson)) {
            return;
        }

        $decoded = json_decode($rawJson, true);

        if (!is_array($decoded)) {
            $message = 'No se pudo leer el JSON del subíndice. ' . (function_exists('json_last_error_msg') ? json_last_error_msg() : '');
            $this->addAcfNotice(trim($message), 'error');
            return;
        }

        $items = [];
        $errors = [];

        foreach ($decoded as $index => $entry) {
            $rowNumber = $index + 1;

            if (!is_array($entry)) {
                $errors[] = "El elemento {$rowNumber} no es un objeto válido.";
                continue;
            }

            $title = isset($entry['title']) ? trim((string) $entry['title']) : '';

            if ($title === '') {
                $errors[] = "El elemento {$rowNumber} no tiene título.";
                continue;
            }

            $level = $this->normalizeLevel($entry['level'] ?? 1);
            $timecode = $this->normalizeTimecode($entry['timecode'] ?? null);
            $anchor = isset($entry['anchor']) ? trim((string) $entry['anchor']) : '';
            $anchor = $anchor !== '' ? \sanitize_title($anchor) : '';

            $items[] = [
                'level' => $level,
                'title' => $title,
                'timecode' => $timecode ?: '',
                'anchor' => $anchor,
            ];
        }

        if (!empty($errors)) {
            $this->addAcfNotice(implode(' ', $errors), 'error');
            return;
        }

        if (empty($items)) {
            $this->addAcfNotice('No se importó ningún apartado. Verifica que el JSON contenga al menos un elemento.', 'warning');
            return;
        }

        \update_field('lesson_subindex_items', $items, $postId);
        \update_field('lesson_subindex_import', '', $postId);

        $this->addAcfNotice(sprintf('Subíndice importado correctamente (%d elementos).', count($items)), 'success');
    }

    /**
     * Importa el cuestionario desde el campo JSON y lo vuelca en el repeater.
     *
     * @param int|string $postId
     * @return void
     */
    public function importLessonQuizFromJson($postId): void
    {
        if (!is_numeric($postId)) {
            return;
        }

        $postId = (int) $postId;

        if ($postId <= 0) {
            return;
        }

        if (\wp_is_post_revision($postId) || \wp_is_post_autosave($postId)) {
            return;
        }

        if (\get_post_type($postId) !== 'cde') {
            return;
        }

        $rawJson = \get_field('quiz_json_import', $postId);

        if (empty($rawJson)) {
            return;
        }

        $decoded = json_decode($rawJson, true);

        if (!is_array($decoded)) {
            $message = 'No se pudo leer el JSON del cuestionario. ' . (function_exists('json_last_error_msg') ? json_last_error_msg() : '');
            $this->addAcfNotice(trim($message), 'error', 'lesson_quiz');
            return;
        }

        $questions = [];
        $errors = [];

        foreach ($decoded as $index => $entry) {
            $rowNumber = $index + 1;

            if (!is_array($entry)) {
                $errors[] = "La pregunta {$rowNumber} no es un objeto válido.";
                continue;
            }

            $questionText = isset($entry['question']) ? trim((string) $entry['question']) : '';

            if ($questionText === '') {
                $errors[] = "La pregunta {$rowNumber} no tiene texto.";
                continue;
            }

            $answers = $entry['answers'] ?? [];

            if (!is_array($answers) || empty($answers)) {
                $errors[] = "La pregunta {$rowNumber} no tiene respuestas.";
                continue;
            }

            $normalizedAnswers = [];
            $correctCount = 0;

            foreach ($answers as $answerIndex => $answerEntry) {
                $answerNumber = $answerIndex + 1;

                if (!is_array($answerEntry)) {
                    $errors[] = "La respuesta {$answerNumber} de la pregunta {$rowNumber} no es un objeto válido.";
                    continue;
                }

                $answerText = isset($answerEntry['text']) ? trim((string) $answerEntry['text']) : '';

                if ($answerText === '') {
                    $errors[] = "La respuesta {$answerNumber} de la pregunta {$rowNumber} está vacía.";
                    continue;
                }

                $isCorrect = !empty($answerEntry['is_correct']) ? 1 : 0;

                $normalizedAnswers[] = [
                    'answer_text' => $answerText,
                    'is_correct' => $isCorrect,
                ];

                if ($isCorrect) {
                    $correctCount++;
                }
            }

            if (count($normalizedAnswers) < 2) {
                $errors[] = "La pregunta {$rowNumber} debe tener al menos dos respuestas.";
                continue;
            }

            if ($correctCount < 1) {
                $errors[] = "La pregunta {$rowNumber} debe tener al menos una respuesta correcta.";
                continue;
            }

            $questions[] = [
                'question' => $questionText,
                'answers' => $normalizedAnswers,
            ];
        }

        if (!empty($errors)) {
            $this->addAcfNotice(implode(' ', $errors), 'error', 'lesson_quiz');
            return;
        }

        if (empty($questions)) {
            $this->addAcfNotice('No se importó ninguna pregunta. Verifica que el JSON contenga al menos una pregunta con respuestas.', 'warning', 'lesson_quiz');
            return;
        }

        \update_field('quiz_questions', $questions, $postId);
        \update_field('quiz_json_import', '', $postId);

        $this->addAcfNotice(sprintf('Cuestionario importado correctamente (%d preguntas).', count($questions)), 'success', 'lesson_quiz');
    }

    protected function addAcfNotice(string $message, string $class = 'info', string $context = 'lesson_subindex'): void
    {
        if (function_exists('acf_add_admin_notice')) {
            acf_add_admin_notice($message, $class);
            return;
        }

        $noticeKey = "{$context}_notice";
        $typeKey = "{$context}_notice_type";

        \add_filter('redirect_post_location', function ($location) use ($message, $class, $noticeKey, $typeKey) {
            return \add_query_arg([
                $noticeKey => rawurlencode($message),
                $typeKey => $class,
            ], $location);
        }, 10, 1);
    }

    public function renderAcfFallbackNotices(): void
    {
        $contexts = [
            'lesson_subindex',
            'lesson_quiz',
        ];

        foreach ($contexts as $context) {
            $noticeKey = "{$context}_notice";
            $typeKey = "{$context}_notice_type";

            if (empty($_GET[$noticeKey])) {
                continue;
            }

            $message = \sanitize_text_field(\wp_unslash($_GET[$noticeKey]));
            $type = \sanitize_key($_GET[$typeKey] ?? 'info');

            $classMap = [
                'success' => 'notice notice-success',
                'warning' => 'notice notice-warning',
                'error' => 'notice notice-error',
                'info' => 'notice notice-info',
            ];

            $class = $classMap[$type] ?? $classMap['info'];

            printf('<div class="%1$s"><p>%2$s</p></div>', \esc_attr($class), \esc_html($message));
        }
    }

    protected function normalizeLevel($value): int
    {
        if (is_int($value)) {
            return max(1, min(4, $value));
        }

        if (is_string($value) && preg_match('/\d+/', $value, $matches)) {
            return max(1, min(4, (int) $matches[0]));
        }

        return 1;
    }

    protected function normalizeTimecode($value): ?string
    {
        if (!is_string($value)) {
            return null;
        }

        $clean = trim($value);

        if ($clean === '') {
            return null;
        }

        $parts = array_map('trim', explode(':', $clean));

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

        return $hours > 0
            ? sprintf('%02d:%02d:%02d', $hours, $minutes, $seconds)
            : sprintf('%02d:%02d', $minutes, $seconds);
    }
}

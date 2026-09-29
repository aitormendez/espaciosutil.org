<?php
namespace EspacioSutil\Mobile;

final class Quiz
{
    public static function normalize(array $raw): array
    {
        $questions = [];
        foreach ($raw as $index => $question) {
            $text = trim((string)($question['question'] ?? '')); $options = [];
            foreach ((array)($question['answers'] ?? []) as $answer) {
                $label = trim((string)($answer['answer_text'] ?? ''));
                if ($label !== '') $options[] = ['id' => 'a-'.count($options), 'text' => wp_strip_all_tags($label), 'is_correct' => !empty($answer['is_correct'])];
            }
            if ($text === '' || count($options) < 2 || !array_filter($options, fn($a) => $a['is_correct'])) continue;
            $questions[] = ['id' => 'q-'.$index, 'text' => wp_strip_all_tags($text), 'multiple' => count(array_filter($options, fn($a) => $a['is_correct'])) > 1, 'options' => $options];
        }
        return $questions;
    }

    public static function definition(int $lesson): array
    {
        if (!function_exists('get_field')) throw new \RuntimeException('ACF no disponible.');
        $questions = self::normalize((array)get_field('quiz_questions', $lesson));
        $enabled = (bool)get_field('quiz_enabled', $lesson) && count($questions) > 0;
        return ['available' => $enabled, 'version' => hash('sha256', wp_json_encode([$enabled, $questions])), 'questions' => $enabled ? $questions : []];
    }

    public static function webDefinition(int $lesson): array
    {
        $d = self::definition($lesson);
        return ['enabled' => $d['available'], 'questions' => array_map(fn($q) => ['question' => $q['text'], 'answers' => array_map(fn($a) => ['text' => $a['text'], 'is_correct' => $a['is_correct']], $q['options'])], $d['questions']), 'count' => count($d['questions']), 'post_id' => $lesson];
    }

    private static function meta(int $user, string $key): mixed
    {
        global $wpdb; $raw = $wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key=%s ORDER BY umeta_id LIMIT 1", $user, $key)); return $raw === null ? null : maybe_unserialize($raw);
    }
    private static function save(int $user, string $key, mixed $value): void
    {
        global $wpdb; $exists = $wpdb->get_var($wpdb->prepare("SELECT umeta_id FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key=%s LIMIT 1", $user, $key));
        $result = $exists ? $wpdb->update($wpdb->usermeta, ['meta_value' => maybe_serialize($value)], ['user_id' => $user, 'meta_key' => $key]) : $wpdb->insert($wpdb->usermeta, ['user_id' => $user, 'meta_key' => $key, 'meta_value' => maybe_serialize($value)]);
        if ($result === false) throw new \RuntimeException('No se pudo guardar el intento.');
    }
    private static function revision(int $user, int $lesson): int
    {
        global $wpdb; $table = Schema::table('revisions'); return (int)$wpdb->get_var($wpdb->prepare("SELECT revision FROM $table WHERE user_id=%d AND resource=%s", $user, 'q:'.$lesson));
    }
    private static function score(mixed $result): ?array
    {
        if (!is_array($result) || !isset($result['total'], $result['correct'], $result['percentage'])) return null;
        return ['correct' => (int)$result['correct'], 'total' => (int)$result['total'], 'percentage' => (float)$result['percentage'], 'saved_at' => (string)($result['saved_at'] ?? '')];
    }
    public static function read(int $user, int $lesson): array
    {
        global $wpdb; $lock = 'cde-mobile-user-'.$user;
        if ((int)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,5)', $lock)) !== 1) throw new \RuntimeException('Intento ocupado.');
        try { return self::snapshot($user, $lesson); }
        finally { $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock)); }
    }
    private static function snapshot(int $user, int $lesson, ?array $definition = null): array
    {
        $d = $definition ?? self::definition($lesson); $attempt = self::meta($user, 'cde_quiz_attempt_'.$lesson);
        if (!$d['available'] || !is_array($attempt) || ($attempt['quiz_version'] ?? '') !== $d['version']) $attempt = null;
        return ['lesson_id' => $lesson, 'title' => wp_strip_all_tags(get_the_title($lesson)), 'available' => $d['available'], 'version' => $d['version'], 'questions' => array_map(fn($q) => ['id' => $q['id'], 'text' => $q['text'], 'multiple' => $q['multiple'], 'options' => array_map(fn($a) => ['id' => $a['id'], 'text' => $a['text']], $q['options'])], $d['questions']), 'attempt' => $attempt, 'last_result' => self::score(self::meta($user, 'cde_quiz_result_'.$lesson)), 'revision' => self::revision($user, $lesson)];
    }
    private static function fresh(array $definition): array
    {
        return ['id' => wp_generate_uuid4(), 'quiz_version' => $definition['version'], 'status' => 'in_progress', 'answers' => array_map(fn($q) => ['question_id' => $q['id'], 'selected' => [], 'validated' => false, 'correct' => null, 'correct_option_ids' => []], $definition['questions']), 'result' => null];
    }

    public static function write(int $user, int $lesson, array $body, ?array $legacyAnswers = null): array|\WP_Error
    {
        $permission = Access::lesson($lesson, $user); if ($permission !== true) return $permission;
        $d = self::definition($lesson); if (!$d['available']) return Access::error('quiz_unavailable', 404);
        $legacy = $legacyAnswers !== null;
        if (!$legacy && $body['quiz_version'] !== $d['version']) return Access::error('quiz_changed', 409);
        global $wpdb; $lock = 'cde-mobile-user-'.$user;
        if ((int)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,5)', $lock)) !== 1) return Access::error('storage_busy', 503);
        $receipts = Schema::table('receipts'); $revisions = Schema::table('revisions');
        ksort($body); $hash = hash('sha256', wp_json_encode(['quiz', $lesson, $body]));
        try {
            Schema::query('START TRANSACTION');
            if (!$legacy) {
                $receipt = $wpdb->get_row($wpdb->prepare("SELECT * FROM $receipts WHERE user_id=%d AND operation_id=%s", $user, $body['operation_id']));
                if ($receipt) { Schema::query('COMMIT'); return hash_equals($receipt->body_hash, $hash) ? json_decode($receipt->response, true) : Access::error('operation_reused', 409); }
            }
            $current = self::snapshot($user, $lesson, $d); $attempt = $current['attempt']; $action = $body['action'] ?? 'legacy';
            $error = null;
            if (!$legacy && $body['expected_revision'] !== $current['revision']) $error = Access::error('revision_conflict', 409);
            elseif ($legacy) {
                $attempt = self::fresh($d);
                if (count($legacyAnswers) !== count($d['questions'])) $error = Access::error('invalid_selection', 422);
                $seen = [];
                foreach ($legacyAnswers as $entry) {
                    $index = $entry['question_index'] ?? null; $selected = $entry['selected'] ?? null;
                    if (!is_int($index) || !isset($d['questions'][$index]) || isset($seen[$index]) || !is_array($selected)) { $error = Access::error('invalid_selection', 422); break; }
                    $seen[$index] = true;
                    foreach ($selected as $value) if (!is_int($value)) { $error = Access::error('invalid_selection', 422); break 2; }
                    $answer = self::answer($d['questions'][$index], array_map(fn($n) => 'a-'.$n, $selected), true, true);
                    if (is_wp_error($answer)) { $error = $answer; break; } $attempt['answers'][$index] = $answer;
                }
            } elseif (in_array($action, ['start','restart'], true)) {
                if (($body['attempt_id'] ?? null) !== ($attempt['id'] ?? null)) $error = Access::error('attempt_changed', 409);
                elseif ($action === 'start' && $attempt !== null) $error = Access::error('attempt_exists', 409);
                else $attempt = self::fresh($d);
            } elseif (!$attempt || $body['attempt_id'] !== $attempt['id'] || $attempt['status'] !== 'in_progress') $error = Access::error('attempt_changed', 409);
            elseif (in_array($action, ['save','validate'], true)) {
                $index = array_search($body['question_id'], array_column($d['questions'], 'id'), true);
                if ($index === false) $error = Access::error('invalid_question', 422);
                elseif ($attempt['answers'][$index]['validated']) $error = Access::error('answer_validated', 409);
                else { $answer = self::answer($d['questions'][$index], $body['selected'], $action === 'validate'); if (is_wp_error($answer)) $error = $answer; else $attempt['answers'][$index] = $answer; }
            } elseif ($action !== 'finish') $error = Access::error('invalid_action', 422);
            if ($error) { Schema::query('ROLLBACK'); return $error; }
            if ($legacy || $action === 'finish') {
                if (count(array_filter($attempt['answers'], fn($a) => $a['validated'])) !== count($d['questions'])) { Schema::query('ROLLBACK'); return Access::error('quiz_incomplete', 422); }
                $correct = count(array_filter($attempt['answers'], fn($a) => $a['correct']));
                $result = ['correct' => $correct, 'total' => count($d['questions']), 'percentage' => round($correct / count($d['questions']) * 100, 2), 'saved_at' => current_time('mysql'), 'answers' => []];
                foreach ($d['questions'] as $i => $q) { $a = $attempt['answers'][$i]; $result['answers'][] = ['question' => $q['text'], 'correct' => $a['correct'], 'selected' => array_map(fn($v) => (int)substr($v, 2), $a['selected']), 'correct_indexes' => array_map(fn($v) => (int)substr($v, 2), $a['correct_option_ids'])]; }
                self::save($user, 'cde_quiz_result_'.$lesson, $result); $attempt['status'] = 'completed'; $attempt['result'] = self::score($result);
            }
            self::save($user, 'cde_quiz_attempt_'.$lesson, $attempt);
            Schema::query($wpdb->prepare("INSERT INTO $revisions (user_id,resource,revision,updated_at) VALUES (%d,%s,%d,%s) ON DUPLICATE KEY UPDATE revision=VALUES(revision),updated_at=VALUES(updated_at)", $user, 'q:'.$lesson, $current['revision'] + 1, gmdate('c')));
            $response = ['operation_id' => $body['operation_id'], 'quiz' => self::snapshot($user, $lesson, $d)];
            if (!$legacy && $wpdb->insert($receipts, ['user_id' => $user, 'operation_id' => $body['operation_id'], 'body_hash' => $hash, 'response' => wp_json_encode($response), 'created' => time()]) === false) throw new \RuntimeException('No se pudo guardar recibo.');
            Schema::query('COMMIT'); wp_cache_delete($user, 'user_meta'); return $response;
        } catch (\Throwable $e) { $wpdb->query('ROLLBACK'); throw $e; }
        finally { $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $lock)); }
    }

    private static function answer(array $question, array $selected, bool $validate, bool $allowEmpty = false): array|\WP_Error
    {
        $ids = array_column($question['options'], 'id');
        if (count($selected) !== count(array_unique($selected)) || array_diff($selected, $ids) || (!$question['multiple'] && count($selected) > 1) || ($validate && !$allowEmpty && !$selected)) return Access::error('invalid_selection', 422);
        $correct = array_column(array_filter($question['options'], fn($a) => $a['is_correct']), 'id'); sort($selected); sort($correct);
        return ['question_id' => $question['id'], 'selected' => $selected, 'validated' => $validate, 'correct' => $validate ? $selected === $correct : null, 'correct_option_ids' => $validate ? $correct : []];
    }
}

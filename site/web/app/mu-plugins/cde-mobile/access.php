<?php
namespace EspacioSutil\Mobile;

final class Access
{
    public static function error(string $code, int $status): \WP_Error
    {
        return new \WP_Error($code, match ($status) {
            401 => 'Inicia sesión para continuar.', 403 => 'Tu cuenta no tiene acceso a este contenido.',
            404 => 'El recurso no está disponible.', 503 => 'El servicio no está disponible temporalmente.',
            default => 'La solicitud no es válida.',
        }, ['status' => $status]);
    }

    public static function member(int $user): bool|\WP_Error
    {
        if (!$user) return self::error('session_required', 401);
        if (!function_exists('pmpro_hasMembershipLevel') || !function_exists('pmpro_has_membership_access')) return self::error('membership_unavailable', 503);
        return pmpro_hasMembershipLevel([11, 12, 13], $user) ? true : self::error('membership_required', 403);
    }

    public static function lesson(int $id, int $user, bool $course = true): bool|\WP_Error
    {
        $post = get_post($id);
        if (!$post || $post->post_type !== 'cde' || $post->post_status !== 'publish' || $post->post_password !== '' || get_post_meta($id, 'active_lesson', true) !== '1') return self::error('lesson_unavailable', 404);
        if ($course && ($member = self::member($user)) !== true) return $member;
        if (!function_exists('pmpro_has_membership_access')) return self::error('membership_unavailable', 503);
        return pmpro_has_membership_access($id, $user) ? true : self::error('membership_required', $user ? 403 : 401);
    }

    public static function media(string $id, string $library, int $user): bool|\WP_Error
    {
        if (!preg_match('/^[a-f0-9-]{36}$/i', $id) || $library !== '457097') return self::error('media_unavailable', 404);
        // El editor puede previsualizar un medio antes de guardar su bloque.
        if (user_can($user, 'edit_others_posts')) return true;
        global $wpdb;
        $posts = $wpdb->get_col($wpdb->prepare("SELECT DISTINCT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ('featured_video_id','featured_audio_id') AND meta_value = %s", $id));
        foreach ($posts as $postId) {
            foreach (['video','audio'] as $kind) {
                $saved = (string) get_post_meta($postId, "featured_{$kind}_id", true);
                $savedLibrary = (string) get_post_meta($postId, "featured_{$kind}_library_id", true) ?: '457097';
                if ($saved === $id && $savedLibrary === $library && self::lesson((int) $postId, $user, false) === true) return true;
            }
        }
        // Sólo referencias explícitas a bloques publicados; nunca buscar el ID como autorización por sí solo.
        $candidates = $wpdb->get_results($wpdb->prepare("SELECT ID,post_content FROM {$wpdb->posts} WHERE post_status='publish' AND post_password='' AND post_type IN ('post','page') AND post_content LIKE %s", '%' . $wpdb->esc_like($id) . '%'));
        foreach ($candidates as $post) {
            if (!function_exists('pmpro_has_membership_access')) return self::error('membership_unavailable', 503);
            if (pmpro_has_membership_access($post->ID, $user) && self::blockHasMedia(parse_blocks($post->post_content), $id, $library)) return true;
        }
        return self::error('media_unavailable', $user ? 403 : 401);
    }

    private static function blockHasMedia(array $blocks, string $id, string $library): bool
    {
        foreach ($blocks as $block) {
            if (($block['blockName'] ?? '') === 'espacio-sutil-blocks/video' && ($block['attrs']['videoId'] ?? '') === $id && ($block['attrs']['libraryId'] ?? '457097') === $library) return true;
            if (self::blockHasMedia($block['innerBlocks'] ?? [], $id, $library)) return true;
        }
        return false;
    }
}

add_filter('rest_request_before_callbacks', static function ($response, $handler, $request) {
    if ($response !== null) return $response;
    $route = rtrim($request->get_route(), '/');
    $user = get_current_user_id();
    if (in_array($route, ['/cde/v1/complete','/cde/v1/quiz/submit','/cde/v1/quiz/result'], true)) {
        $permission = Access::lesson(absint($request->get_param('post_id')), $user);
    } elseif (in_array($route, ['/espacio-sutil/v1/video-resolutions','/espacio-sutil/v1/video-progress'], true)) {
        if ($route === '/espacio-sutil/v1/video-progress' && !$user) return Access::error('session_required', 401);
        $permission = Access::media((string) $request->get_param('video_id'), (string) ($request->get_param('library_id') ?: '457097'), $user);
        if ($permission === true && $route === '/espacio-sutil/v1/video-progress' && $request->get_method() === 'POST') {
            $position = $request->get_param('progress');
            if (!is_numeric($position) || !is_finite((float) $position) || $position < 0) return Access::error('invalid_position', 422);
        }
    } else return $response;
    return $permission === true ? $response : $permission;
}, 10, 3);

add_filter('rest_prepare_cde', static function ($response, $post, $request) {
    if (current_user_can('edit_post', $post->ID)) return $response;
    if (Access::lesson($post->ID, get_current_user_id(), false) !== true) {
        // Mantener título/enlace del índice, sin cuerpo, ACF, respuestas o metadatos premium.
        $data = $response->get_data();
        foreach (['acf', 'meta', 'content', 'excerpt'] as $key) unset($data[$key]);
        $response->set_data($data);
    }
    $response->header('Cache-Control', 'private, no-store');
    return $response;
}, PHP_INT_MAX, 3);

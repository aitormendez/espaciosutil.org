<?php

namespace App\Api;

use WP_REST_Request;
use WP_REST_Response;

class CompletedLessons
{
    public function register_routes(): void
    {
        register_rest_route('cde/v1', '/complete/', [
            'methods' => 'POST',
            'callback' => [$this, 'mark_as_completed'],
            'permission_callback' => fn() => is_user_logged_in(),
        ]);
    }

    public function mark_as_completed(WP_REST_Request $request): WP_REST_Response
    {
        $post_id = absint($request->get_param('post_id'));
        $user_id = get_current_user_id();

        if (!$post_id || !$user_id) {
            return new WP_REST_Response(['error' => 'Invalid request.'], 400);
        }

        $action = $request->get_param('action') ?: 'complete';
        $completed = get_user_meta($user_id, 'cde_completed_lessons', true) ?: [];

        if ($action === 'complete') {
            if (!in_array($post_id, $completed, true)) {
                $completed[] = $post_id;
                update_user_meta($user_id, 'cde_completed_lessons', $completed);
            }
        } elseif ($action === 'uncomplete') {
            $completed = array_filter($completed, fn($id) => $id !== $post_id);
            update_user_meta($user_id, 'cde_completed_lessons', array_values($completed));
        }

        return new WP_REST_Response([
            'success' => true,
            'completed_lessons' => $completed,
        ]);
    }
}

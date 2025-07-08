<?php

namespace App\Api;

use WP_REST_Server;
use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

class VideoProgress
{
    public function register_routes()
    {
        register_rest_route('espacio-sutil/v1', '/video-progress', [
            'methods'             => WP_REST_Server::READABLE, // GET
            'callback'            => [$this, 'get_video_progress'],
            'permission_callback' => [$this, 'permission_check'],
        ]);

        register_rest_route('espacio-sutil/v1', '/video-progress', [
            'methods'             => WP_REST_Server::CREATABLE, // POST
            'callback'            => [$this, 'save_video_progress'],
            'permission_callback' => [$this, 'permission_check'],
        ]);
    }

    public function permission_check(WP_REST_Request $request)
    {
        return is_user_logged_in();
    }

    public function get_video_progress(WP_REST_Request $request)
    {
        $user_id = get_current_user_id();
        $video_id = $request->get_param('video_id');

        if (empty($video_id)) {
            return new WP_Error('no_video_id', 'Video ID is required.', ['status' => 400]);
        }

        $progress = get_user_meta($user_id, 'video_progress_' . $video_id, true);

        return new WP_REST_Response(['progress' => (float) $progress], 200);
    }

    public function save_video_progress(WP_REST_Request $request)
    {
        $user_id = get_current_user_id();
        $video_id = $request->get_param('video_id');
        $progress = $request->get_param('progress');

        if (empty($video_id) || !isset($progress)) {
            return new WP_Error('missing_params', 'Video ID and progress are required.', ['status' => 400]);
        }

        update_user_meta($user_id, 'video_progress_' . $video_id, (float) $progress);

        return new WP_REST_Response(['message' => 'Progress saved.'], 200);
    }
}

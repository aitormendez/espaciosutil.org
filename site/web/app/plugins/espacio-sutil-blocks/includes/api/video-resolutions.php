<?php

/**
 * Plugin integration for Bunny.net video resolution fetching.
 * Registers REST endpoint: /wp-json/espacio-sutil/v1/video-resolutions
 */

add_action('rest_api_init', function () {
    register_rest_route('espacio-sutil/v1', '/video-resolutions', [
        'methods'             => 'GET',
        'callback'            => 'es_blocks_get_video_resolutions',
        'permission_callback' => '__return_true',
    ]);
});

function es_blocks_get_video_resolutions($data)
{
    $video_id   = sanitize_text_field($data->get_param('video_id'));
    $library_id = sanitize_text_field($data->get_param('library_id')) ?? '457097';

    if (!$video_id) {
        return new WP_Error('no_video_id', 'No video ID provided', ['status' => 400]);
    }

    $video_details = es_blocks_fetch_video_details($video_id, $library_id);

    if (empty($video_details)) {
        return new WP_Error('no_encodings', 'No video resolutions found', ['status' => 404]);
    }

    $response = rest_ensure_response($video_details);
    $response->header('Cache-Control', 'no-cache, no-store, must-revalidate');
    $response->header('Pragma', 'no-cache');
    $response->header('Expires', '0');

    return $response;
}

function es_blocks_fetch_video_details($video_id, $library_id)
{
    $api_key   = getenv('BUNNY_KEY');
    $pull_zone = getenv('BUNNY_PULL_ZONE');

    if (!$api_key || !$pull_zone) {
        return [];
    }

    $api_endpoint = "https://video.bunnycdn.com/library/{$library_id}/videos/{$video_id}";

    $response = wp_remote_get($api_endpoint, [
        'headers' => [
            'AccessKey' => $api_key,
        ],
    ]);

    if (is_wp_error($response)) {
        return [];
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!isset($data['availableResolutions'])) {
        return [];
    }

    $captions = [];

    if (!empty($data['captions']) && is_array($data['captions'])) {
        foreach ($data['captions'] as $caption) {
            $lang = isset($caption['srclang']) ? sanitize_key($caption['srclang']) : '';

            if (!$lang) {
                continue;
            }

            $label = isset($caption['label']) ? sanitize_text_field($caption['label']) : strtoupper($lang);
            $file  = isset($caption['file']) ? ltrim(sanitize_text_field($caption['file']), '/') : "captions/{$lang}.vtt";
            $file  = str_replace(['..', '\\'], '', $file);
            $src   = "https://{$pull_zone}.b-cdn.net/{$video_id}/{$file}";

            $captions[] = [
                'lang'    => $lang,
                'label'   => $label,
                'src'     => esc_url_raw($src),
                'default' => !empty($caption['isDefault']),
            ];
        }
    }

    // Bunny expone varios campos posibles para el póster: priorizamos la URL completa y
    // caemos a rutas relativas dentro del mismo directorio del vídeo.
    $thumbnail_candidates = [
        $data['thumbnailUrl'] ?? null,
        $data['thumbnailFileUrl'] ?? null,
        $data['thumbnailFilePath'] ?? null,
        $data['thumbnailFileName'] ?? null,
        $data['thumbnail'] ?? null,
    ];

    $poster_url = null;

    foreach ($thumbnail_candidates as $candidate) {
        if (empty($candidate) || !is_string($candidate)) {
            continue;
        }

        $candidate = trim($candidate);

        if ($candidate === '') {
            continue;
        }

        if (strpos($candidate, '://') !== false) {
            $poster_url = esc_url_raw($candidate);
            break;
        }

        $sanitized_path = ltrim(str_replace(['..', '\\'], '', $candidate), '/');

        if ($sanitized_path === '') {
            continue;
        }

        $poster_url = sprintf(
            'https://%s.b-cdn.net/%s/%s',
            $pull_zone,
            $video_id,
            $sanitized_path
        );

        break;
    }

    if (!$poster_url) {
        $poster_url = "https://{$pull_zone}.b-cdn.net/{$video_id}/thumbnail.jpg";
    }

    return [
        'hlsUrl'       => "https://{$pull_zone}.b-cdn.net/{$video_id}/playlist.m3u8",
        'thumbnailUrl' => $poster_url,
        'captions'     => $captions,
    ];
}

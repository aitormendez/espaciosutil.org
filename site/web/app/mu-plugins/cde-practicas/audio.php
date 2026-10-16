<?php

namespace EspacioSutil\Practicas;

/** Prepared narration stays outside the web root and is served only to members. */
final class Audio
{
    public static function directory(): string
    {
        return dirname(ABSPATH, 2).'/private/cde-practicas-audio';
    }

    public static function revision(array $guide): string
    {
        return hash('sha256', wp_json_encode($guide['screens'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    public static function attach(int $id, array $guide): array
    {
        $revision = self::revision($guide);
        foreach ($guide['screens'] as $i => &$screen) {
            $file = self::directory().'/'.$id.'/'.$revision.'/'.$i.'.mp3';
            $screen['audio'] = is_file($file) && filesize($file) > 0
                ? add_query_arg(['cde_practica_audio'=>'1', 'practice'=>$id, 'screen'=>$i, 'revision'=>$revision], home_url('/')) : null;
        }
        unset($screen);
        return $guide;
    }

    public static function serve(): void
    {
        nocache_headers();
        header('Cache-Control: private, no-store, max-age=0');
        if (!Catalog::allowed() && !Catalog::editor()) { status_header(403); exit; }
        $id = isset($_GET['practice']) && is_scalar($_GET['practice']) ? absint($_GET['practice']) : 0;
        $post = get_post($id);
        if (!$post || $post->post_type !== 'practica' || post_password_required($post)
            || (!Catalog::visible($post) && !(Catalog::editor() && current_user_can('edit_post', $id)))) { status_header(404); exit; }
        $guide = Guide::fromHtml(wpautop($post->post_content));
        $revision = self::revision($guide);
        $requestedRevision = $_GET['revision'] ?? null;
        $index = $_GET['screen'] ?? null;
        if (!is_string($requestedRevision) || !hash_equals($revision, $requestedRevision)
            || !is_string($index) || !preg_match('/^(0|[1-9][0-9]{0,4})$/D', $index) || !isset($guide['screens'][(int) $index])) { status_header(404); exit; }
        $file = self::directory().'/'.$id.'/'.$revision.'/'.$index.'.mp3';
        if (!is_file($file)) { status_header(404); exit; }
        header('Content-Type: audio/mpeg');
        header('X-Content-Type-Options: nosniff');
        header('Content-Length: '.filesize($file));
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'HEAD') { readfile($file); }
        exit;
    }
}

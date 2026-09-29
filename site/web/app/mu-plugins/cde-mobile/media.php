<?php
namespace EspacioSutil\Mobile;

final class Media
{
    public static function identities(int $lesson): array
    {
        $items=[];
        foreach (['video','audio'] as $kind) {
            $id=(string)get_post_meta($lesson,'featured_'.$kind.'_id',true);
            $library=(string)get_post_meta($lesson,'featured_'.$kind.'_library_id',true)?:'457097';
            if ($id && $library==='457097') $items[]=['id'=>$id,'version'=>hash('sha256',$id.':'.$library)];
        }
        return $items;
    }
    /** Invalida la verificación si cambia cualquiera de los medios o capítulos. */
    public static function audioChapterFingerprint(int $lesson): string
    {
        $source=[];
        foreach (['video','audio'] as $kind) {
            $source[]=(string)get_post_meta($lesson,'featured_'.$kind.'_id',true);
            $source[]=(string)get_post_meta($lesson,'featured_'.$kind.'_library_id',true)?:'457097';
        }
        $source[]=function_exists('get_field')?(array)get_field('lesson_subindex_items',$lesson):[];
        return hash('sha256',wp_json_encode($source));
    }
    public static function audioChaptersVerified(int $lesson): bool
    {
        $verified=(string)get_post_meta($lesson,'_cde_mobile_audio_chapters_verified',true);
        return $verified!=='' && hash_equals(self::audioChapterFingerprint($lesson),$verified);
    }
    public static function entries(int $lesson): array
    {
        $items=[];
        foreach (['video','audio'] as $kind) {
            $id=(string)get_post_meta($lesson,'featured_'.$kind.'_id',true);
            $library=(string)get_post_meta($lesson,'featured_'.$kind.'_library_id',true)?:'457097';
            if (!preg_match('/^[a-f0-9-]{36}$/i',$id) || $library!=='457097') continue;
            $details=self::details($id,$library);
            if (is_wp_error($details)) throw new \RuntimeException('Medio temporalmente no disponible.');
            $chapters=[];
            // Sólo reutilizar el subíndice tras verificar el par y su mapa temporal.
            if (($kind==='video' || self::audioChaptersVerified($lesson)) && function_exists('get_field')) foreach ((array)get_field('lesson_subindex_items',$lesson) as $i=>$row) {
                $time=(string)($row['timecode']??'');
                if (!preg_match('/^(?:\d{1,2}:)?[0-5]?\d:[0-5]\d$/',$time)) continue;
                $seconds=0;foreach(explode(':',$time) as $part)$seconds=$seconds*60+(int)$part;
                $chapters[]=['id'=>'chapter-'.$i,'title'=>wp_strip_all_tags((string)($row['title']??'')),'start_seconds'=>$seconds,'end_seconds'=>null,'section_id'=>($row['anchor']??'')?:null];
            }
            $captions=[];foreach ($details['captions'] as $c) $captions[]=['id'=>$c['lang'],'language'=>$c['lang'],'label'=>$c['label'],'url'=>$c['src'],'format'=>'webvtt','is_default'=>$c['default']];
            $items[]=['id'=>$id,'version'=>hash('sha256',$id.':'.$library),'kind'=>$kind,'duration_seconds'=>$details['length']>0?$details['length']:null,'hls_url'=>$details['hlsUrl'],'request_headers'=>['Referer'=>'https://espaciosutil.org/'],'thumbnail_url'=>$details['thumbnailUrl'],'expires_at'=>null,'chapters'=>$chapters,'captions'=>$captions];
        }
        return $items;
    }
    public static function details(string $id,string $library): array|\WP_Error
    {
        $key='cde_media_'.hash('sha256',$library.':'.$id);
        $cached=get_transient($key);if (is_array($cached)) return $cached;
        if (!function_exists('es_blocks_fetch_media_details')) return Access::error('media_unavailable',503);
        $data=es_blocks_fetch_media_details($id,$library);
        if (!$data || !isset($data['length']) || $data['length']<=0) return Access::error('media_unavailable',503);
        set_transient($key,$data,300);return $data;
    }
    public static function lessonFor(string $id): ?int
    {
        global $wpdb;
        return ($p=$wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key IN ('featured_video_id','featured_audio_id') AND meta_value=%s LIMIT 1",$id)))?(int)$p:null;
    }
}

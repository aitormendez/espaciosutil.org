<?php
namespace EspacioSutil\Mobile;

final class Progress
{
    private static function meta(int $user,string $key): mixed
    {
        global $wpdb;
        $raw=$wpdb->get_var($wpdb->prepare("SELECT meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key=%s ORDER BY umeta_id LIMIT 1",$user,$key));
        return $raw===null?null:maybe_unserialize($raw);
    }
    private static function saveMeta(int $user,string $key,mixed $value): void
    {
        global $wpdb; $raw=maybe_serialize($value);
        $exists=$wpdb->get_var($wpdb->prepare("SELECT umeta_id FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key=%s LIMIT 1",$user,$key));
        $ok=$exists?$wpdb->update($wpdb->usermeta,['meta_value'=>$raw],['user_id'=>$user,'meta_key'=>$key]):$wpdb->insert($wpdb->usermeta,['user_id'=>$user,'meta_key'=>$key,'meta_value'=>$raw]);
        if ($ok===false) throw new \RuntimeException('No se pudo guardar progreso.');
    }
    private static function revision(int $user,string $resource): array
    {
        global $wpdb;$t=Schema::table('revisions');
        $r=$wpdb->get_row($wpdb->prepare("SELECT revision,updated_at FROM $t WHERE user_id=%d AND resource=%s",$user,$resource),ARRAY_A);
        return ['revision'=>$r?(int)$r['revision']:0,'updated_at'=>$r?$r['updated_at']:null];
    }
    public static function read(int $user,int $lesson,array $media): array
    {
        $canonical=self::meta($user,'cde_lesson_progress_'.$lesson);
        if (is_array($canonical) && isset($canonical['position_seconds'])) {
            $position=(float)$canonical['position_seconds'];
            $revision=self::revision($user,'p:lesson:'.$lesson);
        } else {
            // Lectura sin migración: última escritura conocida, nunca máximo de posición.
            $position=0.0;$revision=['revision'=>0,'updated_at'=>null];$selected=false;
            foreach (Media::identities($lesson) as $entry) {
                $legacy=self::revision($user,'p:'.$entry['id']);
                $revision['revision']=max($revision['revision'],$legacy['revision']);
                $raw=self::meta($user,'video_progress_'.$entry['id']);
                if ($raw===null) continue;
                if (!$selected || strcmp($legacy['updated_at']??'', $revision['updated_at']??'')>0) {
                    $position=(float)$raw;$revision['updated_at']=$legacy['updated_at'];$selected=true;
                }
            }
        }
        return ['lesson_id'=>$lesson,'media_id'=>$media['id'],'media_version'=>$media['version'],'position_seconds'=>max(0,$position)]+$revision;
    }

    public static function completion(int $user,int $lesson): array
    {
        $ids=array_map('intval',(array)self::meta($user,'cde_completed_lessons'));
        return ['viewed'=>in_array($lesson,$ids,true)]+self::revision($user,'c:'.$lesson);
    }
    public static function write(int $user,int $lesson,array $body,?array $media=null,bool $legacy=false): array|\WP_Error
    {
        global $wpdb;
        $permission=Access::lesson($lesson,$user);
        if ($permission!==true) return $permission;
        if ($media && ($body['media_id']!==$media['id'] || $body['media_version']!==$media['version'])) return Access::error('media_changed',409);
        if ($media && (!is_numeric($body['position_seconds']) || !is_finite((float)$body['position_seconds']) || $body['position_seconds']<0 || ($media['duration_seconds']!==null && $body['position_seconds']>$media['duration_seconds']))) return Access::error('invalid_position',422);
        $lock='cde-mobile-user-'.$user;
        if ((int)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,5)',$lock))!==1) return Access::error('storage_busy',503);
        $resource=$media?'p:lesson:'.$lesson:'c:'.$lesson;$receipts=Schema::table('receipts');$revisions=Schema::table('revisions');
        ksort($body);$hash=hash('sha256',wp_json_encode([$lesson,$resource,$body]));
        try {
            Schema::query('START TRANSACTION');
            if (!$legacy) {
                $receipt=$wpdb->get_row($wpdb->prepare("SELECT * FROM $receipts WHERE user_id=%d AND operation_id=%s",$user,$body['operation_id']));
                if ($receipt) { Schema::query('COMMIT');return hash_equals($receipt->body_hash,$hash)?json_decode($receipt->response,true):Access::error('operation_reused',409); }
            }
            $current=$media?self::read($user,$lesson,$media):self::completion($user,$lesson);
            if (!$legacy && $body['expected_revision']!==$current['revision']) { Schema::query('ROLLBACK'); return Access::error('revision_conflict',409); }
            if ($media) self::saveMeta($user,'cde_lesson_progress_'.$lesson,['position_seconds'=>(float)$body['position_seconds']]);
            else {
                $ids=array_map('intval',(array)self::meta($user,'cde_completed_lessons'));
                $ids=array_values(array_diff($ids,[$lesson]));if ($body['viewed']) $ids[]=$lesson;
                self::saveMeta($user,'cde_completed_lessons',$ids);
            }
            $revision=$current['revision']+1;$now=gmdate('c');
            Schema::query($wpdb->prepare("INSERT INTO $revisions (user_id,resource,revision,updated_at) VALUES (%d,%s,%d,%s) ON DUPLICATE KEY UPDATE revision=VALUES(revision),updated_at=VALUES(updated_at)",$user,$resource,$revision,$now));
            if ($media) {
                // Proyecciones para lectores web antiguos, atómicas con el dato canónico.
                foreach (Media::identities($lesson) as $entry) {
                    self::saveMeta($user,'video_progress_'.$entry['id'],(float)$body['position_seconds']);
                    Schema::query($wpdb->prepare("INSERT INTO $revisions (user_id,resource,revision,updated_at) VALUES (%d,%s,%d,%s) ON DUPLICATE KEY UPDATE revision=VALUES(revision),updated_at=VALUES(updated_at)",$user,'p:'.$entry['id'],$revision,$now));
                }
            }
            $state=$media?self::read($user,$lesson,$media):self::completion($user,$lesson);
            $result=$media?['operation_id'=>$body['operation_id'],'progress'=>$state]:['operation_id'=>$body['operation_id'],'lesson_id'=>$lesson,'completion'=>$state];
            if (!$legacy) {
                $ok=$wpdb->insert($receipts,['user_id'=>$user,'operation_id'=>$body['operation_id'],'body_hash'=>$hash,'response'=>wp_json_encode($result),'created'=>time()]);
                if ($ok===false) throw new \RuntimeException('No se pudo guardar recibo.');
            }
            Schema::query('COMMIT');wp_cache_delete($user,'user_meta');return $result;
        } catch (\Throwable $e) { $wpdb->query('ROLLBACK');throw $e; }
        finally { $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$lock)); }
    }
}

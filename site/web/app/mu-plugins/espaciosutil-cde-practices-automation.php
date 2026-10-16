<?php
/** Plugin Name: Espacio Sutil — Automatización de prácticas CDE */
namespace EspacioSutil\Practicas;

/** Native endpoint used only through authenticated WP-CLI. No public write route. */
final class LessonAutomation
{
    public const VERSION = '1.0.0';
    private const META = '_cde_practice_lesson_automation';

    private static function hash(string $value): string { return hash('sha256', $value); }
    private static function fail(string $message): never { throw new \RuntimeException($message); }
    private static function sha($value): string {
        if (!is_string($value) || !preg_match('/^[a-f0-9]{64}$/D', $value)) self::fail('Invalid practice hash');
        return $value;
    }
    private static function root(): string { return dirname(ABSPATH, 2).'/private/cde-practicas-audio'; }
    private static function unique(string $type, string $field, string $key): ?\WP_Post {
        $posts=get_posts(['post_type'=>$type,'post_status'=>array_keys(get_post_stati()),'posts_per_page'=>2,'meta_key'=>$field,'meta_value'=>$key]);
        if (count($posts)>1 || ($posts && get_post_meta($posts[0]->ID,$field,true)!==$key)) self::fail('Duplicate or inexact practice identity');
        return $posts[0]??null;
    }
    private static function lesson(array $package): \WP_Post {
        if (count($package['lessons']??[])!==1) self::fail('Exactly one host lesson required');
        $key=$package['lessons'][0]['clave_leccion']??'';
        if (!is_string($key) || !preg_match('/^cde:[0-9]{2}:[0-9]{2}:[0-9]+(?:\.(?:[0-9]+|[A-Za-z]))*$/D',$key)) self::fail('Invalid canonical lesson');
        $post=self::unique('cde','clave_leccion',$key);
        if (!$post || !in_array($post->post_status,['draft','future','publish'],true) || $post->post_password!=='' || (int)get_post_meta($post->ID,'active_lesson',true)!==1) self::fail('Host lesson unavailable');
        return $post;
    }
    private static function same(\WP_Post $p,array $item): bool {
        return $p->post_title===$item['title'] && $p->post_content===$item['content'] && $p->post_excerpt===$item['excerpt']
            && get_post_meta($p->ID,'codigo_practica',true)===$item['codigo_practica']
            && get_post_meta($p->ID,'_cde_practica_source_sha256',true)===$item['source_sha256'];
    }
    public static function preview(array $package): array {
        if (($package['schema']??'')!=='cde.practices.package' || ($package['version']??0)!==1 || empty($package['practices']) || count($package['lessons']??[])!==1) self::fail('Invalid practice package');
        $plans=[]; $keys=[];
        foreach ($package['practices'] as &$p) {
            foreach (['clave_practica','codigo_practica','title','content','excerpt','referencia_fuente','source_sha256'] as $field) if (!isset($p[$field]) || !is_string($p[$field])) self::fail('Missing practice field');
            self::sha($p['source_sha256']);
            if (!preg_match('/^practica:[a-zA-Z0-9:._-]{1,120}$/D',$p['clave_practica']) || !preg_match('/^EP-[0-9]+(?:\.(?:[0-9]+|[A-Za-z]))*$/D',$p['codigo_practica'])) self::fail('Invalid practice identity');
            if (trim($p['title'])==='' || trim($p['content'])==='' || wp_kses_post($p['content'])!==$p['content']) self::fail('Unsupported practice HTML');
            foreach (['title','excerpt','codigo_practica','referencia_fuente'] as $field) if (strip_tags($p[$field])!==$p[$field]) self::fail('Invalid plain practice field');
            // Recognize an earlier import by exact source AND code, never by title.
            $prior=get_posts(['post_type'=>'practica','post_status'=>array_keys(get_post_stati()),'posts_per_page'=>2,'meta_query'=>[
                ['key'=>'_cde_practica_source_sha256','value'=>$p['source_sha256']],['key'=>'codigo_practica','value'=>$p['codigo_practica']]]]);
            if (count($prior)>1) self::fail('Ambiguous existing practice source');
            if ($prior) {
                $existingKey=get_post_meta($prior[0]->ID,'clave_practica',true);
                if (!is_string($existingKey) || !preg_match('/^practica:[a-zA-Z0-9:._-]{1,120}$/D',$existingKey)) self::fail('Existing practice has no valid key');
                $p['clave_practica']=$existingKey;
            }
            $key=$p['clave_practica'];
            if (isset($keys[$key])) self::fail('Duplicate practice package key');
            $keys[$key]=true;
            $post=self::unique('practica','clave_practica',$key);
            if ($post && (!self::same($post,$p) || !in_array($post->post_status,['draft','publish'],true) || $post->post_password!=='')) self::fail('Existing editorial practice differs; preserve it');
            $guide=Guide::fromHtml(wpautop($p['content']));
            if (empty($guide['screens'])) self::fail('Practice requires editorial guide adaptation');
            $plans[$key]=['schema'=>'cde.practice.audio-plan','version'=>1,'post_id'=>0,'revision'=>Audio::revision($guide),'screens'=>$guide['screens']];
        }
        unset($p);
        $package['lessons'][0]['practicas']=array_keys($keys);
        return ['package'=>$package,'plans'=>$plans];
    }
    private static function checked(array $p): array {
        $preview=self::preview($p['package']);
        if ($preview['package']!==$p['package'] || $preview['plans']!==$p['plans']) self::fail('Canonical practice guide/package changed');
        $lesson=self::lesson($p['package']);
        self::sha($p['review_sha256']??null);
        $target=$p['target']['post_status']??null;
        if (!in_array($target,['draft','future','publish'],true)) self::fail('Invalid practice visibility target');
        if ($target==='publish' && $lesson->post_status!=='publish') self::fail('Cannot publish a host lesson through practices');
        if ($lesson->post_status==='future' && $target!=='future') self::fail('Do not alter scheduled lesson scope');
        foreach ($preview['plans'] as $key=>$plan) {
            $audio=$p['audio'][$key]??[];
            if (count($audio)!==count($plan['screens'])) self::fail('Incomplete practice audio');
            for ($i=0;$i<count($plan['screens']);$i++) self::sha($audio[$i]??null);
        }
        return [$preview,$lesson];
    }
    private static function upload(array $payload): array {
        $sha=self::sha($payload['sha256']??null);$raw=base64_decode($payload['bytes']??'',true);
        if ($raw===false || strlen($raw)===0 || strlen($raw)>16*1024*1024 || self::hash($raw)!==$sha) self::fail('Invalid audio upload');
        $dir=self::root().'/.incoming'; if (!is_dir($dir) && !wp_mkdir_p($dir)) self::fail('Cannot create private audio staging');
        $path=$dir.'/'.$sha;
        if (file_exists($path)) { if (is_link($path) || hash_file('sha256',$path)!==$sha) self::fail('Private audio staging collision'); }
        else { $tmp=tempnam($dir,'.upload-'); try { if (file_put_contents($tmp,$raw)!==strlen($raw) || !link($tmp,$path)) self::fail('Private audio upload failed'); chmod($path,0640); } finally { unlink($tmp); } }
        return ['sha256'=>$sha];
    }
    private static function apply(array $payload): array {
        global $wpdb;
        [, $lesson]=self::checked($payload);
        // A connection-scoped native lock covers competing lessons sharing practices.
        $lock='cde-practices-automation-v1';
        if ((string)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,0)',$lock))!=='1') self::fail('Practice writer already active');
        try {
            [, $lesson]=self::checked($payload);
            foreach ($payload['audio'] as $files) foreach ($files as $sha) {
                $file=self::root().'/.incoming/'.$sha;
                if (!is_file($file) || is_link($file) || hash_file('sha256',$file)!==$sha) self::fail('Reviewed audio not completely staged');
            }
            $related=get_post_meta($lesson->ID,'cde_practicas',true); if ($related==='') $related=[];
            if (!is_array($related)) self::fail('Invalid existing practice relationship');
            foreach ($payload['package']['practices'] as $p) {
                $key=$p['clave_practica'];$post=self::unique('practica','clave_practica',$key);
                if (!$post) {
                    foreach ([$wpdb->posts,$wpdb->postmeta] as $table) {
                        $engine=$wpdb->get_var($wpdb->prepare('SELECT ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME=%s',$table));
                        if (strtolower((string)$engine)!=='innodb') self::fail('Atomic practice identity requires transactional WordPress tables');
                    }
                    if ($wpdb->query('START TRANSACTION')===false) self::fail('Cannot start practice identity transaction');
                    try {
                    $id=wp_insert_post(wp_slash(['post_type'=>'practica','post_status'=>'draft','post_title'=>$p['title'],'post_content'=>$p['content'],'post_excerpt'=>$p['excerpt'],
                        'meta_input'=>['clave_practica'=>$key,'_clave_practica'=>'field_cde_clave_practica','codigo_practica'=>$p['codigo_practica'],'_codigo_practica'=>'field_cde_codigo_practica','_cde_practica_source_sha256'=>$p['source_sha256']]]),true);
                    if (is_wp_error($id)) self::fail('Practice draft insert failed');
                    $post=get_post($id);
                    if (!$post || !self::same($post,$p) || get_post_meta($id,'clave_practica',true)!==$key) self::fail('Atomic practice identity readback differs');
                    if ($wpdb->query('COMMIT')===false) self::fail('Practice identity commit uncertain; reconcile before retry');
                    } catch (\Throwable $error) { $wpdb->query('ROLLBACK'); if (isset($id) && is_int($id)) clean_post_cache($id); throw $error; }
                }
                if (!$post || !self::same($post,$p)) self::fail('Practice readback differs');
                $id=$post->ID; $revision=$payload['plans'][$key]['revision'];
                $dir=self::root().'/'.$id.'/'.$revision;
                if (!is_dir($dir) && !wp_mkdir_p($dir)) self::fail('Private practice directory unavailable');
                if (is_link($dir)) self::fail('Unexpected private directory symlink');
                foreach ($payload['audio'][$key] as $i=>$sha) {
                    $to=$dir.'/'.$i.'.mp3';
                    if (file_exists($to)) { if (is_link($to) || hash_file('sha256',$to)!==$sha) self::fail('Installed audio revision is immutable'); }
                    else if (!link(self::root().'/.incoming/'.$sha,$to)) self::fail('Audio installation failed');
                }
                $marker=get_post_meta($id,self::META,true); if ($marker==='') $marker=['bindings'=>[]];
                if (!is_array($marker) || !isset($marker['bindings'])) self::fail('Practice marker incompatible');
                $marker['bindings'][(string)$lesson->ID]=['review_sha256'=>$payload['review_sha256'],'content_sha256'=>self::hash($p['content']),'revision'=>$revision,'audio'=>$payload['audio'][$key],'target'=>$payload['target']];
                update_post_meta($id,self::META,$marker);
                if (!in_array((string)$id,array_map('strval',$related),true)) $related[]=(string)$id;
            }
            $fresh=get_post_meta($lesson->ID,'cde_practicas',true); if ($fresh==='') $fresh=[];
            $before=get_post_meta($lesson->ID,'cde_practicas',true);
            if (!is_array($fresh) || ($before!=='' && !is_array($before))) self::fail('Relationship changed concurrently');
            // Merge again to preserve relations added by another editorial writer.
            $related=array_values(array_unique(array_merge(array_map('strval',$fresh),array_map('strval',$related))));
            update_field('field_cde_practicas',$related,$lesson->ID);
            if (array_map('strval',(array)get_post_meta($lesson->ID,'cde_practicas',true))!==array_map('strval',$related)) self::fail('Practice relationship readback differs');
            self::synchronize('publish',$lesson->post_status,$lesson);
            return self::readback($payload);
        } finally { $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$lock)); }
    }
    public static function synchronize($new,$old,$lesson): void {
        if (!($lesson instanceof \WP_Post) || $lesson->post_type!=='cde' || $lesson->post_status!=='publish' || $lesson->post_password!=='' || (int)get_post_meta($lesson->ID,'active_lesson',true)!==1) return;
        foreach ((array)get_post_meta($lesson->ID,'cde_practicas',true) as $id) {
            $post=get_post((int)$id);$marker=get_post_meta((int)$id,self::META,true);$binding=$marker['bindings'][(string)$lesson->ID]??null;
            if (!$post || $post->post_type!=='practica' || !$binding || $post->post_status!=='draft') continue;
            if (self::hash($post->post_content)!==$binding['content_sha256']) continue;
            $guide=Guide::fromHtml(wpautop($post->post_content));
            if (Audio::revision($guide)!==$binding['revision'] || count($guide['screens'])!==count($binding['audio'])) continue;
            foreach ($binding['audio'] as $i=>$sha) {
                $file=self::root().'/'.$post->ID.'/'.$binding['revision'].'/'.$i.'.mp3';
                if (!is_file($file) || is_link($file) || hash_file('sha256',$file)!==$sha) continue 2;
            }
            // The host has already become public. No independent practice cron can
            // expose it first; publication uses the host's actual date and timezone.
            wp_update_post(['ID'=>$post->ID,'post_status'=>'publish','post_date'=>$lesson->post_date,'post_date_gmt'=>$lesson->post_date_gmt],true);
        }
    }
    private static function readback(array $payload): array {
        [, $lesson]=self::checked($payload);$rows=[];$related=array_map('intval',(array)get_post_meta($lesson->ID,'cde_practicas',true));
        foreach ($payload['package']['practices'] as $p) {
            $post=self::unique('practica','clave_practica',$p['clave_practica']);
            if (!$post || !self::same($post,$p) || !in_array($post->ID,$related,true)) self::fail('Practice or relation missing');
            $marker=get_post_meta($post->ID,self::META,true);$b=$marker['bindings'][(string)$lesson->ID]??null;
            if (!$b || $b['review_sha256']!==$payload['review_sha256'] || $b['audio']!==$payload['audio'][$p['clave_practica']]) self::fail('Practice review binding missing');
            $hashes=[];foreach ($b['audio'] as $i=>$sha) {
                $file=self::root().'/'.$post->ID.'/'.$b['revision'].'/'.$i.'.mp3';
                if (!is_file($file) || is_link($file) || hash_file('sha256',$file)!==$sha) self::fail('Practice private audio readback differs');
                $hashes[$i]=$sha;
            }
            if ($lesson->post_status==='publish' && $post->post_status!=='publish') self::fail('Practice publication pending or failed');
            if (!in_array($post->post_status,['draft','publish'],true)) self::fail('Practice status changed');
            $rows[]=['key'=>$p['clave_practica'],'id'=>$post->ID,'status'=>$post->post_status,'revision'=>$b['revision'],'audio'=>$hashes,'lesson_id'=>$lesson->ID,'url'=>get_permalink($post->ID)];
        }
        return ['schema'=>'cde.practices.readback.v1','practices'=>$rows];
    }
    public static function dispatch(string $action,array $payload): array {
        if (!defined('WP_CLI') || !WP_CLI || !class_exists(Guide::class) || !class_exists(Audio::class) || !post_type_exists('practica') || !function_exists('update_field')) self::fail('Authenticated practice capability unavailable');
        return match($action) {
            'preview'=>self::preview($payload['package']),
            'check'=>(function()use($payload){self::checked($payload);return ['valid'=>true];})(),
            'upload'=>self::upload($payload), 'apply'=>self::apply($payload), 'readback'=>self::readback($payload),
            default=>throw new \RuntimeException('Unsupported practice operation'),
        };
    }
}
add_action('transition_post_status',[LessonAutomation::class,'synchronize'],20,3);

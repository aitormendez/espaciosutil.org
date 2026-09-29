<?php
/** Estado y continuidad; sólo datos sintéticos en staging, con limpieza. */
use EspacioSutil\Mobile\{API,Progress,Media,Quiz,Schema};
if(gethostname()!=='espacio-sutil-staging'||WP_ENV!=='staging')throw new RuntimeException('Sólo staging');
$user=get_user_by('login','cde-test-active');if(!$user)throw new RuntimeException('Falta fixture');
global $checks;
$uid=(int)$user->ID;wp_set_current_user($uid);$checks=[];$posts=[];$mediaIds=[];$extra=[];$operations=[];
global $wpdb;$table=Schema::table('revisions');$before=get_user_meta($uid,'cde_completed_lessons',true);
function courseCheck($ok,$name){global $checks;$checks[$name]=(bool)$ok;if(!$ok)throw new RuntimeException($name);}
function state04($uid,$id){$nodes=array_column(API::course($uid)['nodes'],null,'id');return $nodes['lesson:'.$id]['study_state']??null;}
try {
 foreach(['A','B','Contenedor'] as $label){$id=wp_insert_post(['post_type'=>'cde','post_status'=>'publish','post_title'=>'Prueba sintética 0.4 '.$label],true);if(is_wp_error($id))throw new RuntimeException('Fixture');$posts[]=$id;update_post_meta($id,'active_lesson',$label==='Contenedor'?'0':'1');}
 [$a,$b,$container]=$posts;wp_update_post(['ID'=>$b,'post_parent'=>$container]);
 foreach(['video','audio'] as $kind){$id=wp_generate_uuid4();$mediaIds[]=$id;update_post_meta($a,'featured_'.$kind.'_id',$id);update_post_meta($a,'featured_'.$kind.'_library_id','457097');set_transient('cde_media_'.hash('sha256','457097:'.$id),['length'=>120,'captions'=>[],'hlsUrl'=>'https://example.invalid/test.m3u8','thumbnailUrl'=>null],300);}
 courseCheck(state04($uid,$a)==='not_started','new_lesson_not_started');
 courseCheck(state04($uid,$container)==='unknown'&&state04($uid,$b)==='not_started','container_preserves_authorized_child');
 $baseline=API::course($uid);$metaCount=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE user_id=%d",$uid));$revCount=(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE user_id=%d",$uid));API::course($uid);
 courseCheck($metaCount===(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->usermeta} WHERE user_id=%d",$uid))&&$revCount===(int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE user_id=%d",$uid)),'get_has_no_writes');
 update_user_meta($uid,'video_progress_'.$mediaIds[1],12);
 courseCheck(state04($uid,$a)==='in_progress','legacy_audio_position_in_progress');
 courseCheck(API::course($uid)['continue_lesson_id']===$baseline['continue_lesson_id'],'legacy_without_date_does_not_invent_recency');
 $media=Media::entries($a)[0];$s=Progress::read($uid,$a,$media);$op=wp_generate_uuid4();$operations[]=$op;
 $saved=Progress::write($uid,$a,['operation_id'=>$op,'expected_revision'=>$s['revision'],'media_id'=>$media['id'],'media_version'=>$media['version'],'position_seconds'=>0],$media);
 courseCheck(!is_wp_error($saved)&&state04($uid,$a)==='in_progress','confirmed_rewind_zero_remains_started');
 $wpdb->update($table,['updated_at'=>'2090-01-01T00:00:00+00:00'],['user_id'=>$uid,'resource'=>'p:lesson:'.$a]);
 courseCheck(API::course($uid)['continue_lesson_id']===$a,'canonical_lesson_resource_continues');
 $revA=API::course($uid)['revision'];
 // Un cuestionario cuenta como actividad aunque no haya reproducción.
 $key=get_field_object('quiz_questions',2875,false,false)['key'];$enabled=get_field_object('quiz_enabled',2875,false,false)['key'];
 update_field($key,[['question'=>'Pregunta sintética','answers'=>[['answer_text'=>'A','is_correct'=>1],['answer_text'=>'B','is_correct'=>0]]]],$b);update_field($enabled,1,$b);
 $q=Quiz::read($uid,$b);$op=wp_generate_uuid4();$operations[]=$op;
 $saved=Quiz::write($uid,$b,['operation_id'=>$op,'expected_revision'=>$q['revision'],'quiz_version'=>$q['version'],'attempt_id'=>null,'action'=>'start','question_id'=>null,'selected'=>[]]);
 courseCheck(!is_wp_error($saved)&&state04($uid,$b)==='in_progress','quiz_start_without_media_in_progress');
 $wpdb->update($table,['updated_at'=>'2090-01-02T00:00:00+00:00'],['user_id'=>$uid,'resource'=>'q:'.$b]);
 courseCheck(API::course($uid)['continue_lesson_id']===$b,'quiz_activity_continues');
 courseCheck(API::course($uid)['revision']!==$revA,'course_revision_includes_continuity');
 $op=wp_generate_uuid4();$operations[]=$op;$s=Progress::completion($uid,$a);
 $saved=Progress::write($uid,$a,['operation_id'=>$op,'expected_revision'=>$s['revision'],'viewed'=>true]);
 courseCheck(!is_wp_error($saved)&&state04($uid,$a)==='viewed','manual_viewed_precedes_activity');
 $op=wp_generate_uuid4();$operations[]=$op;$s=Progress::completion($uid,$a);Progress::write($uid,$a,['operation_id'=>$op,'expected_revision'=>$s['revision'],'viewed'=>false]);
 courseCheck(state04($uid,$a)==='in_progress','unmark_restores_in_progress');
 courseCheck(get_user_meta($uid,'cde_completed_lessons',true)===$before,'activity_never_marks_viewed');
 // Entradas recientes ajenas al árbol no ocultan una lección válida más antigua.
 for($i=0;$i<25;$i++){$r='p:'.wp_generate_uuid4();$extra[]=$r;$wpdb->insert($table,['user_id'=>$uid,'resource'=>$r,'revision'=>1,'updated_at'=>'2091-01-01T00:00:00+00:00']);}
 courseCheck(API::course($uid)['continue_lesson_id']===$b,'unmapped_recent_resources_do_not_hide_continuity');
 update_post_meta($b,'active_lesson','0');
 courseCheck(API::course($uid)['continue_lesson_id']===$a&&state04($uid,$b)===null,'inaccessible_lesson_never_continues');
 $other=get_user_by('login','cde-test-none');if(!$other)throw new RuntimeException('Falta fixture sin acceso');
 $visible=array_column(API::course((int)$other->ID)['nodes'],'lesson_id');
 courseCheck(!in_array($a,$visible,true)&&!in_array($b,$visible,true),'other_membership_has_no_premium_lesson');
 echo wp_json_encode(['passed'=>true,'count'=>count($checks),'checks'=>$checks],JSON_PRETTY_PRINT);
} finally {
 update_user_meta($uid,'cde_completed_lessons',$before);
 foreach($posts as $id){wp_delete_post($id,true);foreach(['cde_lesson_progress_','cde_quiz_attempt_','cde_quiz_result_'] as $key)delete_user_meta($uid,$key.$id);foreach(['p:lesson:','q:','c:'] as $prefix)$wpdb->delete($table,['user_id'=>$uid,'resource'=>$prefix.$id]);}
 foreach($mediaIds as $id){delete_user_meta($uid,'video_progress_'.$id);$wpdb->delete($table,['user_id'=>$uid,'resource'=>'p:'.$id]);delete_transient('cde_media_'.hash('sha256','457097:'.$id));}
 foreach($extra as $resource)$wpdb->delete($table,['user_id'=>$uid,'resource'=>$resource]);
 foreach($operations as $op)$wpdb->delete(Schema::table('receipts'),['user_id'=>$uid,'operation_id'=>$op]);
 wp_set_current_user(0);
}

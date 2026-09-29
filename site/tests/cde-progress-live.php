<?php
/** Sólo staging; cuenta y lección sintéticas. No modifica cuentas reales. */
use EspacioSutil\Mobile\{Progress,Media,Schema};
if(gethostname()!=='espacio-sutil-staging'||WP_ENV!=='staging')throw new RuntimeException('Sólo staging');
$user=get_user_by('login','cde-test-active');if(!$user)throw new RuntimeException('Falta fixture');
global $checks,$operations;
$uid=(int)$user->ID;wp_set_current_user($uid);$checks=[];$post=0;$ids=[];$operations=[];
function check031($ok,$name){global $checks;$checks[$name]=(bool)$ok;if(!$ok)throw new RuntimeException($name);}
function body031($state,$position){global $operations;$id=wp_generate_uuid4();$operations[]=$id;return ['operation_id'=>$id,'expected_revision'=>$state['revision'],'media_id'=>$state['media_id'],'media_version'=>$state['media_version'],'position_seconds'=>$position];}
global $wpdb;$revisions=Schema::table('revisions');$before=get_user_meta($uid,'cde_completed_lessons',true);
try {
 $post=wp_insert_post(['post_type'=>'cde','post_status'=>'publish','post_title'=>'Prueba sintética de progreso compartido'],true);if(is_wp_error($post))throw new RuntimeException('Fixture');
 update_post_meta($post,'active_lesson','1');
 foreach(['video','audio'] as $kind){$id=wp_generate_uuid4();$ids[]=$id;update_post_meta($post,'featured_'.$kind.'_id',$id);update_post_meta($post,'featured_'.$kind.'_library_id','457097');set_transient('cde_media_'.hash('sha256','457097:'.$id),['length'=>120,'captions'=>[],'hlsUrl'=>'https://example.invalid/test.m3u8','thumbnailUrl'=>null],300);}
 $media=Media::entries($post);[$video,$audio]=$media;
 update_user_meta($uid,'video_progress_'.$video['id'],100.0);update_user_meta($uid,'video_progress_'.$audio['id'],20.0);
 $wpdb->insert($revisions,['user_id'=>$uid,'resource'=>'p:'.$video['id'],'revision'=>9,'updated_at'=>'2026-09-05T01:00:00+00:00']);
 $wpdb->insert($revisions,['user_id'=>$uid,'resource'=>'p:'.$audio['id'],'revision'=>4,'updated_at'=>'2026-09-06T01:00:00+00:00']);
 $v=Progress::read($uid,$post,$video);$a=Progress::read($uid,$post,$audio);
 check031($v['position_seconds']===20.0&&$a['position_seconds']===20.0&&$v['revision']===9,'legacy_latest_time_not_largest_position');
 check031(!metadata_exists('user',$uid,'cde_lesson_progress_'.$post),'get_does_not_migrate');
 $r=new WP_REST_Request('GET','/espacio-sutil/v1/video-progress');$r->set_query_params(['video_id'=>$video['id']]);$response=rest_do_request($r);check031($response->get_status()===200&&(float)($response->get_data()['progress']??-1)===20.0,'web_get_uses_shared_legacy_selection');
 $body=body031($v,60);$saved=Progress::write($uid,$post,$body,$video);check031(!is_wp_error($saved),'video_write');
 $a=Progress::read($uid,$post,$audio);check031($a['position_seconds']===60.0&&$a['revision']===10,'audio_reads_video_position_revision');
 check031(wp_json_encode(Progress::write($uid,$post,$body,$video))===wp_json_encode($saved),'idempotent_receipt');
 $bad=$body;$bad['position_seconds']=80;$result=Progress::write($uid,$post,$bad,$video);check031(is_wp_error($result)&&$result->get_error_code()==='operation_reused','uuid_reuse_rejected');
 $stale=body031($v,30);$stale['media_id']=$audio['id'];$stale['media_version']=$audio['version'];$result=Progress::write($uid,$post,$stale,$audio);check031(is_wp_error($result)&&$result->get_error_code()==='revision_conflict','stale_other_media_conflicts');
 $saved=Progress::write($uid,$post,body031($a,8),$audio);$v=Progress::read($uid,$post,$video);check031($v['position_seconds']===8.0&&$v['revision']===11,'audio_backward_shared');
 check031((float)get_user_meta($uid,'video_progress_'.$video['id'],true)===8.0&&(float)get_user_meta($uid,'video_progress_'.$audio['id'],true)===8.0,'legacy_meta_projected_atomically');
 $bad=body031($v,121);$result=Progress::write($uid,$post,$bad,$video);check031(is_wp_error($result)&&Progress::read($uid,$post,$video)===$v,'out_of_range_does_not_write');
 $bad=body031($v,40);$bad['media_version']='old';$result=Progress::write($uid,$post,$bad,$video);check031(is_wp_error($result)&&Progress::read($uid,$post,$audio)['position_seconds']===8.0,'changed_media_rejected');
 $r=new WP_REST_Request('POST','/espacio-sutil/v1/video-progress');$r->set_body_params(['video_id'=>$audio['id'],'progress'=>42]);$response=rest_do_request($r);$v=Progress::read($uid,$post,$video);check031($response->get_status()===200&&$v['position_seconds']===42.0&&$v['revision']===12,'web_audio_write_updates_video');
 check031(get_user_meta($uid,'cde_completed_lessons',true)===$before,'never_marks_viewed');
 wp_set_current_user(0);$result=rest_do_request($r);check031($result->get_status()===401,'anonymous_cannot_write');wp_set_current_user($uid);
 update_post_meta($post,'active_lesson','0');$result=Progress::write($uid,$post,$body,$video);check031(is_wp_error($result),'receipt_revalidates_access');
 echo wp_json_encode(['passed'=>true,'checks'=>$checks,'count'=>count($checks)],JSON_PRETTY_PRINT);
} finally {
 if(is_int($post)&&$post){wp_delete_post($post,true);delete_user_meta($uid,'cde_lesson_progress_'.$post);$wpdb->delete($revisions,['user_id'=>$uid,'resource'=>'p:lesson:'.$post]);}
 foreach($ids as $id){delete_user_meta($uid,'video_progress_'.$id);$wpdb->delete($revisions,['user_id'=>$uid,'resource'=>'p:'.$id]);delete_transient('cde_media_'.hash('sha256','457097:'.$id));}
 foreach($operations as $id)$wpdb->delete(Schema::table('receipts'),['user_id'=>$uid,'operation_id'=>$id]);
 // A legacy request generates its own receipt; it belongs only to this synthetic lesson.
 if(is_int($post)&&$post)$wpdb->query($wpdb->prepare('DELETE FROM '.Schema::table('receipts').' WHERE user_id=%d AND response LIKE %s',$uid,'%"lesson_id":'.$post.',%'));
 wp_set_current_user(0);
}

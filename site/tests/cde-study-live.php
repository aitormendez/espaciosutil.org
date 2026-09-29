<?php
/** Sólo staging: contenido y usuario sintéticos; elimina la lección al terminar. */
use EspacioSutil\Mobile\{Quiz,Study,Schema};
if (!str_contains(home_url(), 'stage.espaciosutil.org')) throw new RuntimeException('Sólo staging');
$user=get_user_by('login','cde-test-active');if(!$user)throw new RuntimeException('Falta cuenta sintética');
wp_set_current_user($user->ID);$uid=(int)$user->ID;$GLOBALS['checks']=[];$checks=&$GLOBALS['checks'];$post=0;
function verify($ok,$name){global $checks;$checks[$name]=(bool)$ok;if(!$ok)throw new RuntimeException($name);}
function bodyFor($state,$action,$question=null,$selected=[]){return ['operation_id'=>wp_generate_uuid4(),'expected_revision'=>$state['revision'],'quiz_version'=>$state['version'],'attempt_id'=>$state['attempt']['id']??null,'action'=>$action,'question_id'=>$question,'selected'=>$selected];}
$before=get_user_meta($uid,'cde_completed_lessons',true);
try {
 $post=wp_insert_post(['post_type'=>'cde','post_status'=>'publish','post_title'=>'Prueba sintética 0.2','post_content'=>'<h2 id="uno">Uno</h2><p>Texto <strong>fuerte</strong>.</p><h2 id="dos">Dos</h2><ul><li>Elemento</li></ul>'],true);if(is_wp_error($post))throw new RuntimeException('No se creó fixture');
 update_post_meta($post,'active_lesson','1');
 $key=get_field_object('quiz_questions',2875,false,false)['key'];$enabled=get_field_object('quiz_enabled',2875,false,false)['key'];
 $raw=[['question'=>'Pregunta múltiple','answers'=>[['answer_text'=>'A','is_correct'=>1],['answer_text'=>'B','is_correct'=>1],['answer_text'=>'C','is_correct'=>0]]]];
 update_field($key,$raw,$post);update_field($enabled,1,$post);
 $s=Quiz::read($uid,$post);verify($s['available']&&$s['questions'][0]['multiple'],'synthetic_multiple_available');
 $result=Quiz::write($uid,$post,bodyFor($s,'start'));verify(!is_wp_error($result),'synthetic_start');$s=$result['quiz'];
 $bad=Quiz::write($uid,$post,bodyFor($s,'validate','q-0',['a-999']));verify(is_wp_error($bad)&&Quiz::read($uid,$post)===$s,'invalid_write_atomic');
 $result=Quiz::write($uid,$post,bodyFor($s,'validate','q-0',['a-1','a-0']));verify($result['quiz']['attempt']['answers'][0]['correct'],'multiple_set_equality');$s=$result['quiz'];
 $result=Quiz::write($uid,$post,bodyFor($s,'finish'));verify($result['quiz']['last_result']['correct']===1,'multiple_finish');$s=$result['quiz'];
 $stale=bodyFor($s,'restart');
 $request=new WP_REST_Request('POST','/cde/v1/quiz/submit');$request->set_body_params(['post_id'=>$post,'answers'=>[['question_index'=>0,'selected'=>[2]]]]);$response=rest_do_request($request);$data=$response->get_data();
 verify($response->get_status()===200&&$data['saved']&&$data['result']['correct']===0,'legacy_web_submit');
 $fresh=Quiz::read($uid,$post);verify($fresh['revision']===$s['revision']+1&&$fresh['attempt']['status']==='completed'&&$fresh['last_result']['correct']===0,'web_updates_mobile_revision_and_result');
 verify(is_wp_error(Quiz::write($uid,$post,$stale)),'web_write_conflicts_stale_mobile');
 $raw[0]['question']='Pregunta editada';update_field($key,$raw,$post);$changed=Quiz::read($uid,$post);verify($changed['version']!==$fresh['version']&&$changed['attempt']===null&&$changed['last_result']['correct']===0,'content_change_preserves_result_resets_visible_attempt');
 $old=Quiz::write($uid,$post,bodyFor($fresh,'restart'));verify(is_wp_error($old)&&$old->get_error_code()==='quiz_changed','old_definition_rejected');
 verify(Study::read($post)['chapters'][1]['id']==='dos','chapter_anchor');
 update_field($enabled,0,$post);verify(!Quiz::read($uid,$post)['available']&&is_wp_error(Quiz::write($uid,$post,bodyFor($changed,'start'))),'disabled_quiz_unavailable');
 verify(get_user_meta($uid,'cde_completed_lessons',true)===$before,'quiz_never_marks_viewed');
 $ids=get_posts(['post_type'=>'cde','post_status'=>'publish','numberposts'=>-1,'fields'=>'ids','exclude'=>[$post]]);$unsupported=[];$chapters=0;
 foreach($ids as $id){$doc=Study::read($id);$chapters+=count($doc['chapters']);if($doc['unsupported_blocks'])$unsupported[$id]=$doc['unsupported_blocks'];}
 verify(!$unsupported,'published_content_supported');
 echo wp_json_encode(['passed'=>true,'checks'=>$checks,'count'=>count($checks),'published_lessons'=>count($ids),'chapters'=>$chapters]);
} finally {
 if($post){wp_delete_post($post,true);delete_user_meta($uid,'cde_quiz_attempt_'.$post);delete_user_meta($uid,'cde_quiz_result_'.$post);global $wpdb;$wpdb->delete(Schema::table('revisions'),['user_id'=>$uid,'resource'=>'q:'.$post]);$wpdb->query($wpdb->prepare('DELETE FROM '.Schema::table('receipts').' WHERE user_id=%d AND response LIKE %s',$uid,'%"lesson_id":'.$post.',%'));}
}

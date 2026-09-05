<?php
if(gethostname()!=='espacio-sutil-staging'||WP_ENV!=='staging')WP_CLI::error('Sólo staging.');
use EspacioSutil\Mobile\Sessions;
use EspacioSutil\Mobile\Progress;
use EspacioSutil\Mobile\Media;
$input=json_decode(stream_get_contents(STDIN),true);$password=$input['test_password'];$user=get_user_by('login','cde-test-active')->ID;
$checks=[];
$pair=Sessions::login(['login'=>'cde-test-active','password'=>$password,'device_label'=>'Prueba dominio']);
if(is_wp_error($pair))WP_CLI::error('Login de prueba no disponible.');
try {
    wp_set_password(wp_generate_password(40),$user);
    $checks['password_change_revokes_access']=is_wp_error(Sessions::user($pair['access_token']));
    $checks['password_change_revokes_refresh']=is_wp_error(Sessions::refresh($pair['refresh_token']));
}finally{wp_set_password($password,$user);}
wp_set_current_user($user);$lesson=2875;$media=Media::entries($lesson)[0];$before=Progress::read($user,$lesson,$media);
$r=new WP_REST_Request('POST','/espacio-sutil/v1/video-progress');$r->set_body_params(['video_id'=>$media['id'],'progress'=>18]);$response=rest_do_request($r);
$after=Progress::read($user,$lesson,$media);
$checks['web_writer_shared_revision']=$response->get_status()===200&&$after['revision']===$before['revision']+1&&$after['position_seconds']===18.0;
$checks['canonical_web_meta']=(float)get_user_meta($user,'video_progress_'.$media['id'],true)===18.0;
$body=['operation_id'=>wp_generate_uuid4(),'expected_revision'=>$before['revision'],'media_id'=>$media['id'],'media_version'=>$media['version'],'position_seconds'=>3];
$result=Progress::write($user,$lesson,$body,$media);$checks['mobile_stale_after_web']=is_wp_error($result)&&$result->get_error_data()['status']===409;
$r=new WP_REST_Request('POST','/cde/v1/complete');$r->set_body_params(['post_id'=>$lesson,'action'=>'complete']);$before=Progress::completion($user,$lesson);$response=rest_do_request($r);$after=Progress::completion($user,$lesson);
$checks['web_completion_shared_revision']=$response->get_status()===200&&$after['viewed']&&$after['revision']===$before['revision']+1;
$r->set_body_params(['post_id'=>$lesson,'action'=>'uncomplete']);rest_do_request($r);
$checks['outbound_post_bunny_blocked']=is_wp_error(wp_remote_post('https://video.bunnycdn.com/library/457097/videos/'.$media['id'],['redirection'=>0]));
$checks['outbound_other_library_blocked']=is_wp_error(wp_remote_get('https://video.bunnycdn.com/library/1/videos/'.$media['id'],['redirection'=>0]));
$checks['outbound_redirects_blocked']=is_wp_error(wp_remote_get('https://video.bunnycdn.com/library/457097/videos/'.$media['id'],['redirection'=>1]));
wp_set_current_user(0);WP_CLI::line(wp_json_encode($checks,JSON_PRETTY_PRINT));if(in_array(false,$checks,true))WP_CLI::error('Falló el dominio compartido.');

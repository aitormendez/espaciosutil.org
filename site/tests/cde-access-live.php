<?php
if (gethostname() !== 'espacio-sutil-staging' || WP_ENV !== 'staging') WP_CLI::error('Sólo staging.');
use EspacioSutil\Mobile\Access;
global $checks;
$checks = [];
function check_cde($name, $ok) { global $checks; $checks[$name] = (bool) $ok; }
function request_cde($route, $params = [], $method = 'GET') {
    $r = new WP_REST_Request($method, $route); $r->set_query_params($params);
    return rest_do_request($r);
}
$lesson = 2875;
$video = (string) get_post_meta($lesson, 'featured_video_id', true);
$future = get_posts(['post_type'=>'cde', 'post_status'=>'future', 'numberposts'=>1])[0]->ID;
$member = get_user_by('login','cde-test-active')->ID;
$none = get_user_by('login','cde-test-none')->ID;
foreach (['anonymous'=>0,'none'=>$none,'expired'=>get_user_by('login','cde-test-expired')->ID,'cancelled'=>get_user_by('login','cde-test-cancelled')->ID] as $kind=>$id) {
    wp_set_current_user($id);
    $r=request_cde('/wp/v2/cde/'.$lesson, ['_fields'=>'id,acf,meta,content,excerpt']); $data=$r->get_data();
    check_cde($kind.'_standard_redacted', !isset($data['acf']) && !isset($data['content']) && !isset($data['meta']));
    $r=request_cde('/espacio-sutil/v1/video-resolutions',['video_id'=>$video,'library_id'=>'457097']);
    check_cde($kind.'_media_denied',in_array($r->get_status(),[401,403],true));
    foreach (['/cde/v1/quiz/result','/cde/v1/quiz/submit','/cde/v1/complete'] as $route) {
        $r=request_cde($route,['post_id'=>$lesson],$route==='/cde/v1/quiz/result'?'GET':'POST');
        check_cde($kind.'_'.$route,in_array($r->get_status(),[401,403],true));
    }
    $r=request_cde('/espacio-sutil/v1/video-progress',['video_id'=>$video,'progress'=>10],'POST');
    check_cde($kind.'_progress_denied',in_array($r->get_status(),[401,403],true));
}
wp_set_current_user($member);
check_cde('member_lesson_allowed',Access::lesson($lesson,$member)===true);
check_cde('member_media_allowed',Access::media($video,'457097',$member)===true);
check_cde('foreign_library_denied',is_wp_error(Access::media($video,'1',$member)));
check_cde('unknown_media_denied',is_wp_error(Access::media('00000000-0000-0000-0000-000000000001','457097',$member)));
check_cde('future_lesson_denied',Access::lesson($future,$member)->get_error_data()['status']===404);
check_cde('foreign_post_denied',is_wp_error(Access::lesson(1,$member)));
$r=request_cde('/cde/v1/quiz/result',['post_id'=>$lesson]);check_cde('member_quiz_allowed',$r->get_status()===200);
$r=request_cde('/espacio-sutil/v1/video-progress',['video_id'=>$video,'progress'=>-1],'POST');check_cde('negative_position_denied',$r->get_status()===422);
wp_set_current_user(get_user_by('login','staging-admin')->ID);
$r=request_cde('/wp/v2/cde/'.$lesson,['context'=>'edit']);check_cde('editor_preserved',$r->get_status()===200 && isset($r->get_data()['acf']));
$publicId=wp_insert_post(['post_type'=>'post','post_status'=>'publish','post_title'=>'Fixture de permisos CDE','post_content'=>'<!-- wp:espacio-sutil-blocks/video {"videoId":"00000000-0000-0000-0000-000000000002","libraryId":"457097"} /-->']);
try { wp_set_current_user(0); check_cde('public_editorial_media_preserved',Access::media('00000000-0000-0000-0000-000000000002','457097',0)===true); }
finally { wp_delete_post($publicId,true); wp_set_current_user(0); }
WP_CLI::line(wp_json_encode($checks,JSON_PRETTY_PRINT));
if (in_array(false,$checks,true)) WP_CLI::error('Falló la regresión de acceso.');

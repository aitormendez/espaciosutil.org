<?php
namespace EspacioSutil\Mobile;

// Adaptadores de las escrituras web: el dato y la revisión se confirman en el mismo servicio.
add_filter('rest_request_before_callbacks',static function($response,$handler,$request){
    if($response!==null || $request->get_method()!=='POST')return $response;
    $route=rtrim($request->get_route(),'/');$user=get_current_user_id();
    if(!in_array($route,['/cde/v1/complete','/espacio-sutil/v1/video-progress','/cde/v1/quiz/submit'],true))return $response;
    if(!Schema::ready())return Access::error('storage_unavailable',503);
    try {
        if($route==='/cde/v1/quiz/submit'){
            $answers=$request->get_param('answers');if(!is_array($answers))return Access::error('invalid_selection',422);
            $lesson=absint($request->get_param('post_id'));$result=Quiz::write($user,$lesson,['operation_id'=>wp_generate_uuid4()],$answers);
            return is_wp_error($result)?$result:new \WP_REST_Response(['saved'=>true,'result'=>get_user_meta($user,'cde_quiz_result_'.$lesson,true)]);
        }
        if($route==='/cde/v1/complete') {
            $action=$request->get_param('action')?:'complete';if(!in_array($action,['complete','uncomplete'],true))return Access::error('invalid_action',422);
            $result=Progress::write($user,absint($request->get_param('post_id')),['operation_id'=>wp_generate_uuid4(),'viewed'=>$action==='complete'],null,true);
            return is_wp_error($result)?$result:new \WP_REST_Response(['success'=>true,'completed_lessons'=>get_user_meta($user,'cde_completed_lessons',true)]);
        }
        $mediaId=(string)$request->get_param('video_id');$lesson=Media::lessonFor($mediaId);
        if(!$lesson)return $response; // Vídeos editoriales fuera del dominio de lecciones.
        foreach(Media::entries($lesson) as $media)if($media['id']===$mediaId){
            $result=Progress::write($user,$lesson,['operation_id'=>wp_generate_uuid4(),'media_id'=>$mediaId,'media_version'=>$media['version'],'position_seconds'=>$request->get_param('progress')],$media,true);
            return is_wp_error($result)?$result:new \WP_REST_Response(['message'=>'Progress saved.']);
        }
        return Access::error('media_unavailable',404);
    }catch(\Throwable $e){return Access::error('service_unavailable',503);}
},20,3);

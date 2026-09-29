<?php
namespace EspacioSutil\Mobile;

final class API
{
    public static function register(): void
    {
        foreach ([
            ['/membership','GET','membership',null],['/membership/intent','POST','membershipIntent','MembershipIntent'],
            ['/membership/purchase','POST','membershipPurchase','MembershipPurchase'],['/membership/manage','POST','membershipManage','MembershipManage'],
            ['/membership/notifications/apple','POST','appleNotification','AppleNotification'],['/membership/notifications/google','POST','googleNotification','GoogleNotification'],
            ['/auth/register','POST','register','RegistrationStart'],['/auth/register/confirm','POST','registerConfirm','RegistrationConfirm'],
            ['/account/delete','POST','accountDelete','AccountDelete'],
            ['/support/challenge','GET','supportChallenge',null],['/support','POST','supportPublic','GuestSupportWrite'],['/privacy','GET','publicPrivacy',null],
            ['/account/profile','GET','accountProfile',null],['/account/profile','PUT','accountProfileWrite','ProfileWrite'],
            ['/account/avatar','PUT','accountAvatar','AvatarWrite'],['/account/email','POST','accountEmail','EmailStart'],
            ['/account/email/confirm','POST','accountEmailConfirm','EmailConfirm'],['/account/password','POST','accountPassword','PasswordWrite'],
            ['/account/privacy','GET','accountPrivacy',null],['/account/support','POST','accountSupport','SupportWrite'],
            ['/auth/login','POST','login','LoginRequest'],['/auth/refresh','POST','refresh','RefreshRequest'],['/auth/logout','POST','logout','RefreshRequest'],
            ['/lessons/(?P<lessonId>\d+)/study','GET','study',null],['/lessons/(?P<lessonId>\d+)/quiz','GET','quiz',null],['/lessons/(?P<lessonId>\d+)/quiz/attempt','PUT','quizWrite','QuizWrite'],
            ['/me','GET','me',null],['/course','GET','course',null],['/lessons/(?P<lessonId>\d+)','GET','lesson',null],
            ['/lessons/(?P<lessonId>\d+)/media','GET','media',null],['/progress','GET','progress',null],
            ['/progress/(?P<lessonId>\d+)','PUT','saveProgress','ProgressWrite'],['/lessons/(?P<lessonId>\d+)/completion','PUT','saveCompletion','CompletionWrite'],
        ] as [$path,$method,$action,$schema]) register_rest_route('cde-mobile/v1',$path,[
            'methods'=>$method,'permission_callback'=>'__return_true',
            'callback'=>static function($r) use($action,$schema) { return self::dispatch($r,$action,$schema); },
        ]);
    }
    private static function response($data): \WP_REST_Response
    {
        if (is_wp_error($data)) {
            $status=$data->get_error_data()['status']??500;
            $r=new \WP_REST_Response(['code'=>$data->get_error_code(),'message'=>$data->get_error_message(),'request_id'=>wp_generate_uuid4(),'retryable'=>in_array($status,[429,503],true)],$status);
            if ($status===429)$r->header('Retry-After','900');
        } else $r=new \WP_REST_Response($data,$data===null?204:200);
        $r->header('Cache-Control','private, no-store');$r->header('Vary','Authorization');return $r;
    }
    private static function dispatch($r,string $action,?string $schema): \WP_REST_Response
    {
        try {
            if (!Schema::ready()) return self::response(Access::error('storage_unavailable',503));
            if (!is_ssl()) return self::response(Access::error('https_required',403));
            $body=$r->get_json_params()??[];
            if ($schema) {
                $schemas=json_decode(file_get_contents(__DIR__.'/contract.json'),true);
                $valid=rest_validate_value_from_schema($body,$schemas[$schema],'body');
                if (is_wp_error($valid)) return self::response(Access::error('invalid_request',422));
            }
            if ($action==='register')return self::response(Registration::start($body));
            if ($action==='registerConfirm')return self::response(Registration::confirm($body));
            if ($action==='appleNotification'||$action==='googleNotification')return self::response(Membership::notification($action==='appleNotification'?'apple':'google',$body,$r->get_header('authorization')));
            if ($action==='supportChallenge')return self::response(Account::supportChallenge());
            if ($action==='supportPublic')return self::response(Account::publicSupport($body));
            if ($action==='publicPrivacy')return self::response(Account::privacy());
            if ($action==='login') return self::response(Sessions::login($body));
            if ($action==='refresh' || $action==='logout') return self::response(Sessions::refresh($body['refresh_token'],$action==='logout'));
            $header=$r->get_header('authorization');
            if (!preg_match('/^Bearer ([A-Za-z0-9_-]{43})$/D',$header,$m)) return self::response(Access::error('session_required',401));
            $user=Sessions::user($m[1]);if (is_wp_error($user)) return self::response($user);
            if(str_starts_with($action,'membership'))return self::response(match($action){
                'membership'=>Membership::read($user),'membershipIntent'=>Membership::intent($user,$body),
                'membershipPurchase'=>Membership::purchase($user,$body),'membershipManage'=>StripeMembership::manage($user,$body),
            });
            if ($action==='me') return self::response(self::me($user));
            if (str_starts_with($action,'account')) return self::response(match($action) {
                'accountDelete'=>Registration::delete($user,$body), 'accountProfile'=>Account::profile($user), 'accountProfileWrite'=>Account::updateProfile($user,$body),
                'accountAvatar'=>Account::avatar($user,$body), 'accountEmail'=>Account::startEmail($user,$body),
                'accountEmailConfirm'=>Account::confirmEmail($user,$body), 'accountPassword'=>Account::passwordChange($user,$body),
                'accountPrivacy'=>Account::privacy(), 'accountSupport'=>Account::support($user,$body),
            });
            if (($permission=Access::member($user))!==true)return self::response($permission);
            $lesson=absint($r->get_param('lessonId'));
            if ($lesson && ($permission=Access::lesson($lesson,$user))!==true) return self::response($permission);
            return self::response(match($action) {
                'study'=>Study::read($lesson), 'quiz'=>Quiz::read($user,$lesson), 'quizWrite'=>Quiz::write($user,$lesson,$body),
                'course'=>self::course($user), 'lesson'=>self::lesson($user,$lesson),
                'media'=>['lesson_id'=>$lesson,'items'=>Media::entries($lesson)],
                'progress'=>self::progress($user),
                'saveProgress'=>self::saveProgress($user,$lesson,$body),
                'saveCompletion'=>Progress::write($user,$lesson,$body),
            });
        } catch (\Throwable $e) { return self::response(Access::error('service_unavailable',503)); }
    }
    private static function me(int $user): array|\WP_Error
    {
        global $wpdb;$permission=Access::member($user);
        if (is_wp_error($permission) && $permission->get_error_data()['status']===503)return $permission;
        $granted=$permission===true;$reason=$granted?'active':'no_membership';$expires=null;
        $t=$wpdb->prefix.'pmpro_memberships_users';
        $row=$wpdb->get_row($wpdb->prepare("SELECT status,enddate FROM $t WHERE user_id=%d AND membership_id IN (11,12,13) ORDER BY (status='active') DESC,id DESC LIMIT 1",$user));
        if ($row && !$granted) $reason=match($row->status){'expired'=>'expired','cancelled','admin_cancelled','inactive'=>'cancelled',default=>'no_membership'};
        if ($row && $row->enddate!=='0000-00-00 00:00:00') $expires=gmdate('c',strtotime($row->enddate.' UTC'));
        foreach(Membership::rows($user) as $native)if(Membership::grants($native,time())&&!($row&&$row->status==='active'&&$row->enddate==='0000-00-00 00:00:00'))$expires=gmdate('c',max((int)$native['expires_at'],$expires?strtotime($expires):0));
        return ['id'=>$user,'display_name'=>get_userdata($user)->display_name,'access'=>['granted'=>$granted,'reason'=>$reason,'checked_at'=>gmdate('c'),'expires_at'=>$expires]];
    }
    /** Deriva actividad sin escribir ni consultar Bunny; sólo lecciones autorizadas. */
    private static function activity(int $user,array $lessons): array
    {
        global $wpdb;
        $keys=['cde_completed_lessons'];$resources=[];$streams=[];
        foreach($lessons as $id) {
            foreach(['cde_lesson_progress_','cde_quiz_attempt_','cde_quiz_result_'] as $prefix)$keys[]=$prefix.$id;
            $resources['p:lesson:'.$id]=$id;$resources['q:'.$id]=$id;
            foreach(Media::identities($id) as $media) {
                $keys[]='video_progress_'.$media['id'];
                $resources['p:'.$media['id']]=$id;$streams[$id][]=$media['id'];
            }
        }
        $keys=array_values(array_unique($keys));$placeholders=implode(',',array_fill(0,count($keys),'%s'));
        $rows=$wpdb->get_results($wpdb->prepare("SELECT meta_key,meta_value FROM {$wpdb->usermeta} WHERE user_id=%d AND meta_key IN ($placeholders) ORDER BY umeta_id",$user,...$keys),ARRAY_A);
        if($wpdb->last_error)throw new \RuntimeException('Actividad no disponible.');
        $meta=[];foreach($rows as $row)if(!array_key_exists($row['meta_key'],$meta))$meta[$row['meta_key']]=maybe_unserialize($row['meta_value']);
        $completed=array_map('intval',(array)($meta['cde_completed_lessons']??[]));$started=[];$recent=[];
        $table=Schema::table('revisions');
        $rows=$wpdb->get_results($wpdb->prepare("SELECT resource,revision,updated_at FROM $table WHERE user_id=%d",$user),ARRAY_A);
        if($wpdb->last_error)throw new \RuntimeException('Actividad no disponible.');
        foreach($rows as $row) {
            $id=$resources[$row['resource']]??null;
            if(!$id || (int)$row['revision']<=0)continue;
            $started[$id]=true;
            // No hay fechas inventadas para las claves históricas sin revisión.
            $time=strtotime($row['updated_at']);
            if($time!==false)$recent[$id]=max($recent[$id]??PHP_INT_MIN,$time);
        }
        $states=[];
        foreach($lessons as $id) {
            $active=isset($started[$id]) || (float)($meta['cde_lesson_progress_'.$id]['position_seconds']??0)>0
                || !empty($meta['cde_quiz_attempt_'.$id]) || !empty($meta['cde_quiz_result_'.$id]);
            foreach($streams[$id]??[] as $stream)if((float)($meta['video_progress_'.$stream]??0)>0)$active=true;
            $states[$id]=in_array($id,$completed,true)?'viewed':($active?'in_progress':'not_started');
        }
        $continue=null;$latest=PHP_INT_MIN;
        foreach($recent as $id=>$time)if($time>$latest || ($time===$latest && ($continue===null || $id<$continue))){$continue=$id;$latest=$time;}
        return ['states'=>$states,'continue'=>$continue];
    }
    public static function course(int $user): array
    {
        $posts=get_posts(['post_type'=>'cde','post_status'=>'publish','posts_per_page'=>-1,'orderby'=>['menu_order'=>'ASC','ID'=>'ASC'],'suppress_filters'=>false]);
        $terms=get_terms(['taxonomy'=>'serie_cde','hide_empty'=>false,'orderby'=>'term_order']);
        if (is_wp_error($terms))throw new \RuntimeException('Índice no disponible.');
        $map=[];$termMap=[];$nodes=[];$allowed=[];
        foreach($posts as $post)$map[$post->ID]=$post;
        foreach($terms as $term)$termMap[$term->term_id]=$term;
        foreach($posts as $post)if(Access::lesson($post->ID,$user)===true){$allowed[$post->ID]=true;foreach(get_post_ancestors($post) as $p)if(isset($map[$p]))$allowed[$p]??=false;}
        $activity=self::activity($user,array_keys(array_filter($allowed)));
        foreach($terms as $term)$nodes['term:'.$term->term_id]=['id'=>'term:'.$term->term_id,'parent_id'=>$term->parent?'term:'.$term->parent:null,'kind'=>$term->parent?'block':'series','title'=>$term->name,'order'=>(int)($term->term_order??$term->term_id),'has_children'=>false,'lesson_id'=>null,'study_state'=>'unknown'];
        foreach($allowed as $id=>$open) {
            $post=$map[$id];$parent=null;
            if($post->post_parent && isset($allowed[$post->post_parent]))$parent='lesson:'.$post->post_parent;
            else {
                $assigned=wp_get_post_terms($id,'serie_cde');
                if(!is_wp_error($assigned)&&$assigned){usort($assigned,fn($a,$b)=>count(get_ancestors($b->term_id,'serie_cde'))<=>count(get_ancestors($a->term_id,'serie_cde'))?:$a->term_id<=>$b->term_id);$parent='term:'.$assigned[0]->term_id;}
            }
            $nodes['lesson:'.$id]=['id'=>'lesson:'.$id,'parent_id'=>$parent,'kind'=>$open?'lesson':'container','title'=>wp_strip_all_tags(get_the_title($id)),'order'=>(int)$post->menu_order,'has_children'=>false,'lesson_id'=>$open?$id:null,'study_state'=>$open?$activity['states'][$id]:'unknown'];
        }
        // Eliminar términos vacíos y marcar hijos después de construir el árbol autorizado.
        do {$changed=false;$parents=array_column($nodes,'parent_id');foreach($nodes as $id=>$node)if(str_starts_with($id,'term:')&&!in_array($id,$parents,true)){unset($nodes[$id]);$changed=true;}}while($changed);
        foreach($nodes as $node)if($node['parent_id']&&isset($nodes[$node['parent_id']]))$nodes[$node['parent_id']]['has_children']=true;
        $items=array_values($nodes);usort($items,fn($a,$b)=>$a['order']<=>$b['order']?:strcmp($a['id'],$b['id']));
        $continue=$activity['continue'];
        return ['revision'=>hash('sha256',wp_json_encode([$items,$continue])),'nodes'=>$items,'continue_lesson_id'=>$continue];
    }
    private static function lesson(int $user,int $id): array
    {
        $nodes=array_column(self::course($user)['nodes'],null,'id');$parents=[];$parent=$nodes['lesson:'.$id]['parent_id']??null;
        while($parent && isset($nodes[$parent])&&!in_array($parent,$parents,true)){$parents[]=$parent;$parent=$nodes[$parent]['parent_id'];}
        return ['id'=>$id,'title'=>wp_strip_all_tags(get_the_title($id)),'node_id'=>'lesson:'.$id,'breadcrumb_node_ids'=>array_reverse($parents),'content_revision'=>hash('sha256',get_post($id)->post_modified_gmt),'completion'=>Progress::completion($user,$id)];
    }
    private static function progress(int $user): array
    {
        $items=[];foreach(self::course($user)['nodes'] as $node)if($node['lesson_id'])foreach(Media::identities($node['lesson_id']) as $media)$items[]=Progress::read($user,$node['lesson_id'],$media);
        return ['items'=>$items];
    }
    private static function saveProgress(int $user,int $lesson,array $body): array|\WP_Error
    {
        foreach(Media::entries($lesson) as $media)if($media['id']===$body['media_id'])return Progress::write($user,$lesson,$body,$media);
        return Access::error('media_unavailable',404);
    }
}
add_action('rest_api_init',[API::class,'register']);

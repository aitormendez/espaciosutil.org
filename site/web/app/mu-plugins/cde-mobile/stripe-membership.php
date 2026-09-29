<?php
namespace EspacioSutil\Mobile;

/** Gestión de la suscripción web propia. Clave separada y comprobación de entorno. */
final class StripeMembership
{
    private static function key(): ?string {
        $cfg=Membership::config()['stripe']??[];$file=$cfg['secret_key_file']??'';
        if(($cfg['enabled']??false)!==true||($cfg['environment']??'')!==Membership::environment()||!is_readable($file))return null;
        $key=trim(file_get_contents($file));$prefix=Membership::environment()==='sandbox'?'sk_test_':'sk_live_';
        return str_starts_with($key,$prefix)?$key:null;
    }
    public static function enabled(): bool {return self::key()!==null;}
    public static function allowsTestRequest(array $args,string $url): bool {
        if(Membership::environment()!=='sandbox'||!preg_match('~^https://api\.stripe\.com/v1/subscriptions/sub_[A-Za-z0-9]+$~D',$url)||($args['redirection']??5)!==0)return false;
        $key=self::key();$headers=array_change_key_case((array)($args['headers']??[]),CASE_LOWER);
        if(!$key||!hash_equals('Bearer '.$key,(string)($headers['authorization']??'')))return false;
        $method=strtoupper($args['method']??'GET');
        return $method==='GET'||($method==='POST'&&is_array($args['body']??null)&&array_keys($args['body'])===['cancel_at_period_end']&&in_array($args['body']['cancel_at_period_end'],['true','false'],true));
    }
    private static function request(string $id,?array $body=null): array|\WP_Error {
        $key=self::key();if(!$key)return Membership::error('stripe_unavailable','La gestión de la suscripción web no está disponible ahora.',503);
        if(!preg_match('/^sub_[A-Za-z0-9]+$/D',$id))return Membership::error('subscription_unavailable','La suscripción no está disponible.',503);
        $args=['timeout'=>12,'redirection'=>0,'headers'=>['Authorization'=>'Bearer '.$key,'Stripe-Version'=>'2025-02-24.acacia']];
        if($body!==null){$args['method']='POST';$args['body']=$body;}
        $response=wp_remote_request('https://api.stripe.com/v1/subscriptions/'.rawurlencode($id),$args);
        if(is_wp_error($response)||wp_remote_retrieve_response_code($response)!==200)return Membership::error('stripe_unavailable','No se pudo comprobar el cambio. Actualiza el estado antes de repetirlo.',503);
        $data=json_decode(wp_remote_retrieve_body($response),true);
        if(!is_array($data)||($data['id']??'')!==$id||($data['livemode']??null)!==(Membership::environment()==='production'))return Membership::error('stripe_environment','No se pudo validar la suscripción.',503);
        return $data;
    }
    public static function snapshot(string $id): array|\WP_Error {
        $s=self::request($id);if(is_wp_error($s))return $s;
        return ['status'=>$s['status'],'auto_renew'=>in_array($s['status'],['active','trialing','past_due'],true)&&!($s['cancel_at_period_end']??false),'expires_at'=>isset($s['current_period_end'])?gmdate('c',(int)$s['current_period_end']):null];
    }
    private static function own(int $user,string $id): mixed {
        if(!preg_match('/^web:(\d+)$/D',$id,$m)||!class_exists('PMPro_Subscription'))return null;
        $s=\PMPro_Subscription::get_subscription(['id'=>(int)$m[1]]);
        return $s&&(int)$s->get_user_id()===$user&&$s->get_gateway()==='stripe'&&$s->get_gateway_environment()===Membership::webEnvironment()&&in_array((int)$s->get_membership_level_id(),[11,12,13],true)?$s:null;
    }
    public static function manage(int $user,array $body): array|\WP_Error {
        if(!Sessions::limit('membership-manage:'.$user,12))return Membership::error('rate_limited','Espera unos minutos.',429);
        $u=get_userdata($user);if(!$u||!wp_check_password($body['current_password'],$u->user_pass,$user))return Membership::error('invalid_password','La contraseña actual no es correcta.');
        return Membership::locked('user:'.$user,function()use($user,$body){
            clean_user_cache($user);$u=get_userdata($user);if(!$u||!wp_check_password($body['current_password'],$u->user_pass,$user))return Membership::error('invalid_password','La contraseña actual no es correcta.');
            $s=self::own($user,$body['id']);if(!$s)return Membership::error('subscription_unavailable','No se puede gestionar esa suscripción.',404);
            $id=$s->get_subscription_transaction_id();$current=self::request($id);if(is_wp_error($current))return $current;
            if(!in_array($current['status']??'', ['active','trialing','past_due'],true))return Membership::error('subscription_inactive','Esta suscripción ya no admite cambios de renovación.',409);
            $cancel=$body['action']==='cancel_renewal';
            if(($current['cancel_at_period_end']??false)!==$cancel){$current=self::request($id,['cancel_at_period_end'=>$cancel?'true':'false']);if(is_wp_error($current))return $current;}
            // No usar cancel_at_gateway de PMPro: cancelaría ahora y afectaría a facturas.
            return ['status'=>$cancel?'renewal_cancelled':'renewal_resumed','auto_renew'=>!$current['cancel_at_period_end'],'expires_at'=>isset($current['current_period_end'])?gmdate('c',(int)$current['current_period_end']):null];
        });
    }
}

<?php
namespace EspacioSutil\Mobile;

/** Policy shared with the existing PMPro trial flag. Mutations require the account lock. */
final class SharedTrial
{
    public static function used(int $user): bool {
        wp_cache_delete($user,'user_meta');
        global $wpdb;$used=(bool)get_user_meta($user,'espaciosutil_pmpro_trial_used',true);
        if($wpdb->last_error)throw new \RuntimeException('No se puede comprobar la prueba');
        return $used;
    }
    public static function offer(int $user,array $product,bool $changing=false): ?array {
        if(!function_exists('espaciosutil_pmpro_get_trial_config'))return null;
        $web=espaciosutil_pmpro_get_trial_config((int)($product['level_id']??0));
        if(($web['delay_days']??0)!==7||!preg_match('/^[a-z0-9][a-z0-9-]{0,62}$/D',$product['trial_offer_id']??'')||!in_array($product['billing_period']??'',['P1M','P6M','P1Y'],true))return null;
        $eligible=!$changing&&!self::used($user);
        return ['eligible'=>$eligible,'days'=>7,'offer_id'=>$eligible?$product['trial_offer_id']:''];
    }
    public static function validSnapshot(array $data,array $product): bool {
        if(!is_string($data['offer_id']??null)||!is_bool($data['trial_used']??null))return false;
        $offer=$data['offer_id'];
        if($offer!==''&&($offer!==($product['trial_offer_id']??null)))return false;
        if($offer!==''&&in_array($data['status']??'',['active','grace','cancelled','hold','paused'],true)&&!$data['trial_used'])return false;
        return !$data['trial_used']||($offer!==''&& !in_array($data['status']??'',['pending'],true));
    }
    public static function pendingWebOrder(int $user): bool {
        global $wpdb;
        if(empty($wpdb->pmpro_membership_orders))throw new \RuntimeException('No se puede comprobar el checkout web');
        $found=$wpdb->get_var($wpdb->prepare("SELECT id FROM {$wpdb->pmpro_membership_orders} WHERE user_id=%d AND membership_id IN (11,12,13) AND status IN ('token','review') LIMIT 1",$user));
        if($wpdb->last_error)throw new \RuntimeException('No se puede comprobar el checkout web');
        return (bool)$found;
    }
    public static function reserve(int $user,string $provider,string $product,string $base,string $offer): bool|\WP_Error {
        wp_cache_delete($user,'user_meta');
        $old=get_user_meta($user,'cde_purchase_intent',true);
        $choice=['provider'=>$provider,'product_id'=>$product,'base_plan_id'=>$base,'offer_id'=>$offer];
        if(is_array($old)&&($old['expires']??0)>time()) {
            // Web checkout cannot open another payment while an earlier attempt is unresolved.
            if($provider==='web'||array_diff_assoc($choice,$old))return Membership::error('purchase_in_progress','Hay una contratación iniciada. Comprueba su resultado antes de elegir otro plan o proveedor.',409);
            return true;
        }
        $choice['expires']=time()+86400;
        update_user_meta($user,'cde_purchase_intent',$choice);
        if(get_user_meta($user,'cde_purchase_intent',true)!==$choice)return Membership::error('storage_unavailable','No se pudo reservar la contratación. Inténtalo más tarde.',503);
        return true;
    }
    public static function consume(int $user,string $referenceHash): bool|\WP_Error {
        $used=self::used($user);
        $owner=get_user_meta($user,'cde_trial_reference',true);
        if($used&&$owner!==$referenceHash)return Membership::error('trial_already_used','La prueba de esta cuenta ya se utilizó. Contacta con soporte para revisar esta compra.',409);
        if(!$used) {
            $intent=get_user_meta($user,'cde_purchase_intent',true);
            if(is_array($intent)&&($intent['provider']??'')==='web'&&($intent['expires']??0)>time())return Membership::error('purchase_in_progress','Hay una contratación web pendiente. Contacta con soporte para revisar su resultado.',409);
            if(self::pendingWebOrder($user))return Membership::error('purchase_in_progress','Hay una contratación web pendiente. Contacta con soporte para revisar su resultado.',409);
        }
        update_user_meta($user,'cde_trial_reference',$referenceHash);
        update_user_meta($user,'espaciosutil_pmpro_trial_used',1);
        if(get_user_meta($user,'cde_trial_reference',true)!==$referenceHash||!get_user_meta($user,'espaciosutil_pmpro_trial_used',true))throw new \RuntimeException('No se pudo registrar la prueba');
        return true;
    }
    public static function webCheck($continue,$level=null,bool $reserve=false): bool {
        if(!$continue)return false;
        $level=$level??($GLOBALS['pmpro_level']??null);
        $user=get_current_user_id();
        if(!$user||!in_array((int)($level->id??0),[11,12,13],true))return (bool)$continue;
        try {
            $result=Membership::locked('user:'.$user,function()use($user,$level,$reserve){
                foreach(Membership::rows($user) as $row)if(Membership::grants($row,time())||in_array($row['state'],['pending','hold','paused'],true))return Membership::error('already_subscribed','Gestiona tu suscripción en Apple o Google Play para evitar otra contratación.',409);
                wp_cache_delete($user,'user_meta');
                $intent=get_user_meta($user,'cde_purchase_intent',true);
                if(is_array($intent)&&($intent['expires']??0)>time())return Membership::error('purchase_in_progress','Hay una contratación iniciada. Comprueba su resultado antes de contratar de nuevo.',409);
                if(self::pendingWebOrder($user))return Membership::error('purchase_in_progress','Hay una contratación web pendiente. Comprueba su resultado antes de contratar de nuevo.',409);
                return $reserve?self::reserve($user,'web',(string)$level->id,'',''):true;
            });
            if(!is_wp_error($result))return $result===true;
            pmpro_setMessage($result->get_error_message(),'pmpro_error');
        }catch(\Throwable $e){pmpro_setMessage('No se puede comprobar la membresía ahora. Inténtalo más tarde.','pmpro_error');}
        return false;
    }
}

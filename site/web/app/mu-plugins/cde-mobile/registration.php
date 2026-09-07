<?php
namespace EspacioSutil\Mobile;

final class Registration
{
    public static function start(array $body): array|\WP_Error {
        $email=strtolower(sanitize_email($body['email']));$name=trim(sanitize_text_field($body['name']));
        if(!is_email($email)||$name==='')return Membership::error('invalid_registration','Escribe un nombre y un correo válidos.');
        if(!Sessions::limit('signup-ip:'.($_SERVER['REMOTE_ADDR']??''),10)||!Sessions::limit('signup-mail:'.$email,3))return Membership::error('rate_limited','Espera unos minutos antes de solicitar otro código.',429);
        $token=Sessions::token();$code=(string)random_int(100000,999999);$expires=time()+900;
        $pending=['name'=>$name,'email'=>$email,'code_hash'=>hash_hmac('sha256',$code,wp_salt('auth').':'.$token),'expires'=>$expires,'attempts'=>0];
        // No revelar si existe una cuenta ni sobrescribirla durante el alta.
        $exists=(bool)email_exists($email);
        $message=$exists?'Ya hay una cuenta asociada a este correo. Inicia sesión o recupera la contraseña desde la app.':"Tu código para crear la cuenta es: $code\n\nCaduca en 15 minutos. Si no has solicitado el alta, ignora este mensaje.";
        if(!wp_mail($email,'Tu cuenta de Espacio Sutil',$message))return Membership::error('email_delivery_failed','No se pudo enviar el correo. Inténtalo más tarde.',503);
        if(!$exists&&!set_transient('cde-register-'.hash('sha256',$token),$pending,900))return Membership::error('registration_unavailable','No se pudo iniciar el registro.',503);
        return ['registration_token'=>$token,'expires_at'=>gmdate('c',$expires)];
    }
    public static function confirm(array $body): array|\WP_Error {
        if(!Sessions::limit('signup-confirm-ip:'.($_SERVER['REMOTE_ADDR']??''),25))return Membership::error('rate_limited','Espera unos minutos.',429);
        $key='cde-register-'.hash('sha256',$body['registration_token']);
        return Membership::locked($key,function()use($body,$key){
            $p=get_transient($key);
            if(!is_array($p)||$p['expires']<=time()||$p['attempts']>=5)return Membership::error('invalid_registration_code','El código no es válido o ha caducado. Solicita otro.');
            if(!hash_equals($p['code_hash'],hash_hmac('sha256',$body['code'],wp_salt('auth').':'.$body['registration_token']))){$p['attempts']++;set_transient($key,$p,max(1,$p['expires']-time()));return Membership::error('invalid_registration_code','El código no es válido o ha caducado. Solicita otro.');}
            return Membership::locked('email:'.$p['email'],function()use($p,$body,$key){
                if(email_exists($p['email'])){delete_transient($key);return Membership::error('account_exists','La cuenta ya existe. Inicia sesión o recupera tu contraseña.',409);}
                $result=wp_insert_user(['user_login'=>'cde_'.str_replace('-','',wp_generate_uuid4()),'user_email'=>$p['email'],'display_name'=>$p['name'],'user_pass'=>$body['password'],'role'=>'subscriber']);
                if(is_wp_error($result))return Membership::error('registration_unavailable','No se pudo crear la cuenta. Inténtalo de nuevo.',503);
                update_user_meta($result,'cde_email_verified_at',gmdate('c'));delete_transient($key);return ['status'=>'created'];
            });
        });
    }
    private static function eraseSupport(int $user,string $email): void {
        global $wpdb;$table=$wpdb->prefix.'hf_submissions';
        if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)))!==$table)return;
        $refs=$wpdb->get_col($wpdb->prepare('SELECT operation_id FROM '.Schema::table('receipts').' WHERE user_id=%d',$user));
        $needles=array_merge([$email],$refs);
        foreach($needles as $needle) {
            $rows=$wpdb->get_results($wpdb->prepare("SELECT id,data FROM $table WHERE data LIKE %s",'%'.$wpdb->esc_like($needle).'%'),ARRAY_A);
            if($wpdb->last_error)throw new \RuntimeException('No se pudo borrar soporte');
            foreach($rows as $row){$data=json_decode($row['data'],true);
                if(is_array($data)&&(($data['EMAIL']??'')===$email||in_array($data['MOBILE_REFERENCE']??'', $refs,true)))if($wpdb->delete($table,['id'=>$row['id']])===false)throw new \RuntimeException('No se pudo borrar soporte');
            }
        }
    }
    public static function delete(int $user,array $body): array|\WP_Error {
        if(!Sessions::limit('account-delete:'.$user,5))return Membership::error('rate_limited','Espera unos minutos.',429);
        $u=get_userdata($user);if(!$u||!wp_check_password($body['current_password'],$u->user_pass,$user))return Membership::error('invalid_password','La contraseña actual no es correcta.');
        if(user_can($user,'edit_posts')||user_can($user,'manage_options')||is_super_admin($user))return Membership::error('protected_account','Esta cuenta requiere gestión administrativa para su eliminación.',403);
        return Membership::locked('user:'.$user,function()use($user,$body){
            clean_user_cache($user);$u=get_userdata($user);if(!$u||!wp_check_password($body['current_password'],$u->user_pass,$user))return Membership::error('invalid_password','La contraseña actual no es correcta.');
            global $wpdb;require_once ABSPATH.'wp-admin/includes/user.php';
            // Conservar sólo registros financieros que PMPro deba retener. No cancelar cobros ni borrar historial financiero.
            $noCancel=static fn($cancel,$id)=>(int)$id===$user?false:$cancel;
            add_filter('pmpro_user_deletion_cancel_active_subscriptions',$noCancel,PHP_INT_MAX,2);
            unset($_REQUEST['pmpro_delete_active_subscriptions'],$_REQUEST['pmpro_delete_member_history']);
            Schema::query('START TRANSACTION');
            try{
            self::eraseSupport($user,$u->user_email);
            try{$ok=wp_delete_user($user);}finally{remove_filter('pmpro_user_deletion_cancel_active_subscriptions',$noCancel,PHP_INT_MAX);}
            if(!$ok)throw new \RuntimeException('Eliminación incompleta');
            // Las compras quedan sin identidad personal, pero no se reasignan a una cuenta futura.
            if(Membership::ready())Schema::query($wpdb->prepare('UPDATE '.Schema::table('subscriptions').' SET user_id=0 WHERE user_id=%d',$user));
            foreach(['sessions','revisions','receipts'] as $name)Schema::query($wpdb->prepare('DELETE FROM '.Schema::table($name).' WHERE user_id=%d',$user));
            Schema::query('COMMIT');return ['status'=>'deleted'];
            }catch(\Throwable $e){$wpdb->query('ROLLBACK');clean_user_cache($user);throw $e;}
        });
    }
}

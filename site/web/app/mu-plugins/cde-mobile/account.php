<?php
namespace EspacioSutil\Mobile;

/** Cuenta propia: ninguna operación acepta usuario, rol o capacidades del cliente. */
final class Account
{
    private const AVATAR = 'cde_mobile_private_avatar';
    private const EMAIL = 'cde_mobile_pending_email';
    private static function error(string $code,string $message,int $status=422): \WP_Error { return new \WP_Error($code,$message,['status'=>$status]); }
    private static function locked(int $user,callable $action): mixed {
        global $wpdb;$lock='cde-mobile-user-'.$user;
        if((int)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,5)',$lock))!==1)return self::error('storage_busy','Inténtalo de nuevo en unos segundos.',503);
        try { clean_user_cache($user);return $action(); }
        finally { $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$lock)); }
    }
    private static function password(int $user,string $password): bool { $u=get_userdata($user);return $u && wp_check_password($password,$u->user_pass,$user); }
    public static function profile(int $user): array {
        $u=get_userdata($user);$avatar=get_user_meta($user,self::AVATAR,true);
        $data=['display_name'=>$u->display_name,'first_name'=>(string)get_user_meta($user,'first_name',true),'last_name'=>(string)get_user_meta($user,'last_name',true),'email'=>$u->user_email,'avatar_base64'=>is_string($avatar)&&$avatar!==''?$avatar:null];
        $data['revision']=hash('sha256',wp_json_encode($data));
        $pending=get_user_meta($user,self::EMAIL,true);
        $data['pending_email']=is_array($pending)&&($pending['expires']??0)>time()&&($pending['attempts']??5)<5&&($pending['old_email']??'')===$u->user_email&&hash_equals($pending['password_hash']??'',hash('sha256',$u->user_pass))?['email'=>$pending['email'],'expires_at'=>gmdate('c',$pending['expires'])]:null;
        return $data;
    }
    public static function updateProfile(int $user,array $body): array|\WP_Error {
        return self::locked($user,function()use($user,$body){
            if(!hash_equals(self::profile($user)['revision'],$body['expected_revision']))return self::error('profile_changed','El perfil ha cambiado. Recárgalo antes de guardar.',409);
            $values=['ID'=>$user];foreach(['display_name','first_name','last_name'] as $key)$values[$key]=trim(sanitize_text_field($body[$key]));
            if($values['display_name']==='')return self::error('name_required','Escribe el nombre que quieres mostrar.');
            $result=wp_update_user($values);if(is_wp_error($result))return self::error('profile_save_failed','No se pudo guardar el perfil.',503);
            clean_user_cache($user);return self::profile($user);
        });
    }
    /** Recodifica exclusivamente imágenes raster: sin EXIF, archivos públicos ni original retenido. */
    public static function normalizeAvatar(?string $encoded): string|\WP_Error|null {
        if($encoded===null)return null;
        if(strlen($encoded)>2800000 || !preg_match('~^[A-Za-z0-9+/]+={0,2}$~D',$encoded))return self::error('invalid_image','Selecciona una imagen JPEG, PNG o WebP de hasta 2 MB.');
        $raw=base64_decode($encoded,true);if($raw===false||strlen($raw)>2097152)return self::error('invalid_image','La imagen supera el tamaño permitido.');
        $size=@getimagesizefromstring($raw);
        if(!$size||!in_array($size['mime'],['image/jpeg','image/png','image/webp'],true)||$size[0]>4096||$size[1]>4096||$size[0]*$size[1]>16000000)return self::error('invalid_image','La imagen no es válida o es demasiado grande.');
        if(!function_exists('imagecreatefromstring'))return self::error('image_unavailable','No se pueden procesar imágenes ahora.',503);
        $source=@imagecreatefromstring($raw);if(!$source)return self::error('invalid_image','No se pudo leer la imagen.');
        $target=imagecreatetruecolor(256,256);$white=imagecolorallocate($target,255,255,255);imagefill($target,0,0,$white);
        $side=min($size[0],$size[1]);imagecopyresampled($target,$source,0,0,(int)(($size[0]-$side)/2),(int)(($size[1]-$side)/2),256,256,$side,$side);
        ob_start();imagejpeg($target,null,85);$jpeg=ob_get_clean();unset($source,$target);
        if(!is_string($jpeg)||$jpeg==='')return self::error('invalid_image','No se pudo preparar la imagen.');
        return base64_encode($jpeg);
    }
    public static function avatar(int $user,array $body): array|\WP_Error {
        if(!Sessions::limit('avatar:'.$user,20))return self::error('rate_limited','Espera unos minutos antes de cambiar otra vez la imagen.',429);
        $avatar=self::normalizeAvatar($body['image_base64']);if(is_wp_error($avatar))return $avatar;
        return self::locked($user,function()use($user,$body,$avatar){
            if(!hash_equals(self::profile($user)['revision'],$body['expected_revision']))return self::error('profile_changed','El perfil ha cambiado. Recárgalo antes de guardar.',409);
            if($avatar===null)delete_user_meta($user,self::AVATAR);else update_user_meta($user,self::AVATAR,$avatar);
            if((get_user_meta($user,self::AVATAR,true)?:null)!==$avatar)return self::error('avatar_save_failed','No se pudo guardar la imagen.',503);
            return self::profile($user);
        });
    }
    public static function startEmail(int $user,array $body): array|\WP_Error {
        if(!Sessions::limit('account-sensitive:'.$user,12)||!Sessions::limit('email-start:'.$user,4))return self::error('rate_limited','Espera unos minutos antes de volver a intentarlo.',429);
        return self::locked($user,function()use($user,$body){
            if(!self::password($user,$body['current_password']))return self::error('incorrect_password','La contraseña actual no es correcta.');
            $email=sanitize_email($body['email']);$u=get_userdata($user);
            if(!is_email($email)||strtolower($email)===strtolower($u->user_email))return self::error('invalid_email','Escribe una dirección nueva y válida.');
            if(email_exists($email))return self::error('email_unavailable','No se puede utilizar esa dirección.');
            $code=(string)random_int(100000,999999);$expires=time()+900;
            $pending=['email'=>$email,'old_email'=>$u->user_email,'password_hash'=>hash('sha256',$u->user_pass),'code_hash'=>hash_hmac('sha256',$code,wp_salt('auth').':'.$user),'expires'=>$expires,'attempts'=>0];
            update_user_meta($user,self::EMAIL,$pending);
            if(get_user_meta($user,self::EMAIL,true)!==$pending)return self::error('email_start_failed','No se pudo iniciar el cambio.',503);
            $sent=wp_mail($email,'Código para cambiar tu correo en Espacio Sutil',"Tu código es: $code\n\nCaduca en 15 minutos. Escríbelo en la app para confirmar el cambio. Si no has solicitado este cambio, ignora este mensaje.");
            if(!$sent){delete_user_meta($user,self::EMAIL);return self::error('email_delivery_failed','No se pudo enviar el código. Tu correo no ha cambiado.',503);}
            return ['status'=>'pending','expires_at'=>gmdate('c',$expires)];
        });
    }
    public static function confirmEmail(int $user,array $body): array|\WP_Error {
        if(!Sessions::limit('email-confirm:'.$user,15))return self::error('rate_limited','Espera unos minutos antes de volver a intentarlo.',429);
        return self::locked($user,function()use($user,$body){
            $p=get_user_meta($user,self::EMAIL,true);$u=get_userdata($user);
            if(!is_array($p)||($p['expires']??0)<=time()||($p['attempts']??5)>=5||($p['old_email']??'')!==$u->user_email||!hash_equals($p['password_hash']??'',hash('sha256',$u->user_pass))){delete_user_meta($user,self::EMAIL);return self::error('email_code_expired','El código ha caducado. Solicita uno nuevo.');}
            if(!hash_equals($p['code_hash'],hash_hmac('sha256',$body['code'],wp_salt('auth').':'.$user))){$p['attempts']++;update_user_meta($user,self::EMAIL,$p);return self::error('invalid_email_code','El código no es correcto.');}
            // Serializar el mismo destino también entre usuarios distintos.
            global $wpdb;$emailLock='cde-email-'.substr(hash('sha256',strtolower($p['email'])),0,48);
            if((int)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,5)',$emailLock))!==1)return self::error('storage_busy','Inténtalo de nuevo en unos segundos.',503);
            try {
                if(email_exists($p['email']))return self::error('email_unavailable','No se puede utilizar esa dirección.');
                $result=wp_update_user(['ID'=>$user,'user_email'=>$p['email']]);if(is_wp_error($result))return self::error('email_save_failed','No se pudo confirmar el cambio.',503);
                delete_user_meta($user,self::EMAIL);clean_user_cache($user);return self::profile($user);
            } finally { $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$emailLock)); }
        });
    }
    public static function passwordChange(int $user,array $body): array|\WP_Error {
        if(!Sessions::limit('account-sensitive:'.$user,12))return self::error('rate_limited','Espera unos minutos antes de volver a intentarlo.',429);
        return self::locked($user,function()use($user,$body){
            if(!self::password($user,$body['current_password']))return self::error('incorrect_password','La contraseña actual no es correcta.');
            if($body['new_password']===$body['current_password'])return self::error('password_unchanged','Elige una contraseña diferente.');
            $result=wp_update_user(['ID'=>$user,'user_pass'=>$body['new_password']]);if(is_wp_error($result))return self::error('password_save_failed','No se pudo cambiar la contraseña.',503);
            delete_user_meta($user,self::EMAIL);\WP_Session_Tokens::get_instance($user)->destroy_all();
            global $wpdb;Schema::query($wpdb->prepare('UPDATE '.Schema::table('sessions').' SET revoked=1 WHERE user_id=%d',$user));
            return ['status'=>'password_changed'];
        });
    }
    public static function privacy(): array|\WP_Error {
        $page=get_post((int)get_option('wp_page_for_privacy_policy'));
        if(!$page||$page->post_type!=='page'||$page->post_status!=='publish')return self::error('privacy_unavailable','No se pudo cargar la política de privacidad.',503);
        return ['title'=>wp_strip_all_tags(get_the_title($page)),'version'=>hash('sha256',$page->post_content),'blocks'=>(new Study())->document($page->post_content)];
    }
    public static function supportChallenge(): array|\WP_Error {
        if(!Sessions::limit('support-challenge-ip:'.($_SERVER['REMOTE_ADDR']??''),20))return self::error('rate_limited','Espera unos minutos antes de volver a intentarlo.',429);
        $p=['id'=>wp_generate_uuid4(),'a'=>random_int(1,9),'b'=>random_int(1,9),'expires'=>time()+900];
        $payload=rtrim(strtr(base64_encode(wp_json_encode($p)),'+/','-_'),'=');
        $token=$payload.'.'.hash_hmac('sha256',$payload,wp_salt('auth').':cde-mobile-support');
        return ['id'=>$p['id'],'token'=>$token,'question'=>'¿Cuánto es '.$p['a'].' + '.$p['b'].'?','expires_at'=>gmdate('c',$p['expires'])];
    }
    private static function verifySupportChallenge(array $body): true|\WP_Error {
        if($body['website']!==''||!preg_match('/^([A-Za-z0-9_-]+)\.([a-f0-9]{64})$/D',$body['challenge_token'],$m)||!hash_equals(hash_hmac('sha256',$m[1],wp_salt('auth').':cde-mobile-support'),$m[2]))return self::error('support_challenge_invalid','La comprobación no es válida. Solicita una nueva.');
        $p=json_decode(base64_decode(strtr($m[1],'-_','+/'),true),true);
        if(!is_array($p)||($p['id']??'')!==$body['operation_id'])return self::error('support_challenge_invalid','La comprobación no es válida. Solicita una nueva.');
        if(($p['expires']??0)<=time())return self::error('support_challenge_expired','La comprobación ha caducado. Solicita una nueva.');
        if((int)$body['answer']!==($p['a']+$p['b']))return self::error('support_answer_invalid','Revisa la respuesta a la comprobación.');
        return true;
    }
    public static function publicSupport(array $body): array|\WP_Error {
        return self::locked(0,function()use($body){
            $name=trim(sanitize_text_field($body['name']));$email=sanitize_email($body['email']);
            if($name===''||!is_email($email))return self::error('contact_required','Escribe tu nombre y un correo válido.');
            return self::storeSupport(0,$body,$name,$email,static function()use($body,$email){
                if(!Sessions::limit('guest-support-ip:'.($_SERVER['REMOTE_ADDR']??''),5)||!Sessions::limit('guest-support-email:'.strtolower($email),5))return self::error('rate_limited','Espera unos minutos antes de enviar otra solicitud.',429);
                return self::verifySupportChallenge($body);
            });
        });
    }
    public static function support(int $user,array $body): array|\WP_Error {
        return self::locked($user,function()use($user,$body){
            $u=get_userdata($user);
            return self::storeSupport($user,$body,$u->display_name,$u->user_email,static fn()=>Sessions::limit('support:'.$user,5)?true:self::error('rate_limited','Espera unos minutos antes de enviar otra solicitud.',429));
        });
    }
    /** Un único almacenamiento y recibo para contacto autenticado y sin sesión. */
    private static function storeSupport(int $user,array $body,string $name,string $email,callable $guard): array|\WP_Error {
        global $wpdb;$receipts=Schema::table('receipts');$hash=hash('sha256',wp_json_encode(['account-support',$body]));
        $receipt=$wpdb->get_row($wpdb->prepare("SELECT * FROM $receipts WHERE user_id=%d AND operation_id=%s",$user,$body['operation_id']));
        if($receipt)return hash_equals($receipt->body_hash,$hash)?json_decode($receipt->response,true):self::error('operation_reused','La solicitud ya se utilizó con otro contenido.',409);
        $allowed=$guard();if(is_wp_error($allowed))return $allowed;
        $post=get_page_by_path('contacto',OBJECT,'html-form');
        if(!$post||!function_exists('hf_get_form')||!class_exists('HTML_Forms\\Submission'))return self::error('support_unavailable','El soporte no está disponible ahora.',503);
        $form=hf_get_form($post->ID);if(empty($form->settings['save_submissions']))return self::error('support_unavailable','El registro de soporte no está disponible ahora.',503);
        $submission=new \HTML_Forms\Submission();$submission->form_id=$post->ID;$submission->submitted_at=gmdate('Y-m-d H:i:s');
        $submission->data=['NAME'=>$name,'EMAIL'=>$email,'TEMA'=>['Otro motivo de contacto'],'MESSAGE'=>sanitize_text_field($body['subject'])."\n\n".sanitize_textarea_field($body['message']),'PRIVACY_CONSENT'=>'1','MOBILE_REFERENCE'=>$body['operation_id'],'MOBILE_ORIGIN'=>$user?'App: cuenta autenticada':'App: consulta sin sesión, identidad no verificada'];
        $result=['operation_id'=>$body['operation_id'],'reference'=>'APP-'.strtoupper(substr($body['operation_id'],0,8)),'status'=>'received'];
        Schema::query('START TRANSACTION');
        try {
            $submission->save();if(!$submission->id)throw new \RuntimeException('No se pudo registrar soporte.');
            if($wpdb->insert($receipts,['user_id'=>$user,'operation_id'=>$body['operation_id'],'body_hash'=>$hash,'response'=>wp_json_encode($result),'created'=>time()])===false)throw new \RuntimeException('No se pudo guardar el recibo.');
            Schema::query('COMMIT');
        } catch(\Throwable $e){$wpdb->query('ROLLBACK');throw $e;}
        // La confirmación acredita registro en HTML Forms, no entrega de correo.
        foreach($form->settings['actions']??[] as $action)if(($action['type']??'')==='email') {
            try { do_action('hf_process_form_action_email',$action,$submission,$form); }catch(\Throwable $e){ /* El registro permanece disponible en WordPress. */ }
        }
        return $result;
    }
}

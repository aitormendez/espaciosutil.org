<?php
namespace EspacioSutil\Mobile;

/** Derechos de tienda separados de facturación PMPro/Stripe. */
final class Membership
{
    public static function error(string $code,string $message,int $status=422): \WP_Error { return new \WP_Error($code,$message,['status'=>$status]); }
    public static function environment(): string { return defined('WP_ENV') && WP_ENV==='production'?'production':'sandbox'; }
    public static function webEnvironment(): string { return self::environment()==='production'?'live':'sandbox'; }
    public static function ready(): bool { return get_option('cde_mobile_billing_schema')==='1'; }
    public static function config(): array {
        $path=getenv('CDE_BILLING_CONFIG_FILE');
        if(!$path||!is_readable($path))return [];
        $real=realpath($path);$web=realpath(ABSPATH.'/..');
        if(!$real||($web&&str_starts_with($real,$web.'/')))return [];
        $data=json_decode(file_get_contents($real),true);return is_array($data)?$data:[];
    }
    public static function enabled(string $provider): bool {
        $cfg=self::config()[$provider]??[];
        if(!function_exists('proc_open')||!function_exists('sodium_crypto_secretbox'))return false;
        if($provider==='apple'){
            foreach(['bundle_id','subscription_group_id','issuer_id','key_id','private_key_file'] as $key)if(empty($cfg[$key])||!is_string($cfg[$key]))return false;
            if(!is_readable($cfg['private_key_file'])||empty($cfg['root_certificates'])||!is_array($cfg['root_certificates']))return false;
            foreach($cfg['root_certificates'] as $root)if(!is_string($root)||!is_readable($root))return false;
            if(self::environment()==='production'&&(!is_int($cfg['app_apple_id']??null)||$cfg['app_apple_id']<=0))return false;
        }elseif($provider==='google'){
            if(empty($cfg['package_id'])||!is_readable($cfg['service_account_file']??''))return false;
        }else return false;
        return self::ready()&&($cfg['enabled']??false)===true&&($cfg['environment']??'')===self::environment()
            &&is_executable(getenv('CDE_BILLING_PYTHON')?:'')&&is_readable(getenv('CDE_BILLING_VERIFIER')?:'');
    }
    public static function install(): void {
        if(!defined('WP_CLI')||!WP_CLI||WP_ENV!=='staging'||gethostname()!=='espacio-sutil-staging')throw new \RuntimeException('Sólo migración staging');
        global $wpdb;require_once ABSPATH.'wp-admin/includes/upgrade.php';$table=Schema::table('subscriptions');$collate=$wpdb->get_charset_collate();
        dbDelta("CREATE TABLE $table (
            id char(36) NOT NULL,
            user_id bigint unsigned NOT NULL,
            provider varchar(10) NOT NULL,
            environment varchar(12) NOT NULL,
            reference_hash char(64) NOT NULL,
            reference_cipher longtext NOT NULL,
            account_token char(36) NOT NULL,
            product_id varchar(200) NOT NULL,
            level_id int NOT NULL,
            state varchar(20) NOT NULL,
            expires_at bigint NOT NULL,
            auto_renew tinyint NOT NULL,
            checked_at bigint NOT NULL,
            last_attempt bigint NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY reference_hash (reference_hash),
            KEY user_id (user_id),
            KEY last_attempt (last_attempt)
        ) ENGINE=InnoDB $collate;");
        if($wpdb->get_var($wpdb->prepare('SHOW TABLES LIKE %s',$wpdb->esc_like($table)))!==$table)throw new \RuntimeException('Migración incompleta');
        update_option('cde_mobile_billing_schema','1',false);
        if(!wp_next_scheduled('cde_mobile_reconcile_memberships'))wp_schedule_event(time()+300,'cde_five_minutes','cde_mobile_reconcile_memberships');
    }
    public static function locked(string $key,callable $action): mixed {
        global $wpdb;$lock=str_starts_with($key,'user:')?'cde-mobile-user-'.substr($key,5):(str_starts_with($key,'email:')?'cde-email-'.substr(hash('sha256',strtolower(substr($key,6))),0,48):'cde-billing-'.substr(hash('sha256',$key),0,48));
        if((int)$wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s,5)',$lock))!==1)return self::error('storage_busy','Inténtalo de nuevo en unos segundos.',503);
        try{return $action();}finally{$wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)',$lock));}
    }
    public static function accountToken(int $user): string {
        if(!get_userdata($user))throw new \RuntimeException('Cuenta no disponible');
        $token=get_user_meta($user,'cde_billing_account_token',true);
        if(is_string($token)&&preg_match('/^[a-f0-9-]{36}$/D',$token))return $token;
        $token=wp_generate_uuid4();update_user_meta($user,'cde_billing_account_token',$token);
        if(get_user_meta($user,'cde_billing_account_token',true)!==$token)throw new \RuntimeException('No se pudo guardar identidad');
        return $token;
    }
    public static function grants(array $row,int $now): bool {
        return in_array($row['state']??'', ['active','grace','cancelled'],true) && ($row['expires_at']??0)>$now;
    }
    public static function rows(int $user): array {
        if(!self::ready())return [];
        global $wpdb;$table=Schema::table('subscriptions');
        $rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE user_id=%d AND environment=%s ORDER BY expires_at DESC",$user,self::environment()),ARRAY_A);
        if($wpdb->last_error)throw new \RuntimeException('Membresías no disponibles');return $rows?:[];
    }
    public static function levels($levels,int $user): array {
        $levels=is_array($levels)?$levels:[];
        foreach(self::rows($user) as $row)if(self::grants($row,time())&&!in_array((int)$row['level_id'],array_map('intval',array_column($levels,'id')),true)) {
            $level=pmpro_getLevel((int)$row['level_id']);if(!$level)continue;$level=clone $level;
            $level->ID=$level->id;$level->subscription_id=null;$level->code_id=null;$level->startdate=(int)$row['checked_at'];$level->enddate=(int)$row['expires_at'];
            $level->initial_payment=0;$level->billing_amount=0;$level->billing_limit=0;$level->cycle_number=0;$level->cycle_period='';$level->trial_amount=0;$level->trial_limit=0;
            $levels[]=$level;
        }
        return $levels;
    }
    public static function webSubscriptions(int $user): array {
        if(!class_exists('PMPro_Subscription'))return [];
        return \PMPro_Subscription::get_subscriptions(['user_id'=>$user,'membership_level_id'=>[11,12,13],'limit'=>100])?:[];
    }
    private static function products(): array {
        $out=[];foreach(['apple','google'] as $provider)if(self::enabled($provider))foreach(self::config()[$provider]['products']??[] as $id=>$value) {
            if(!in_array((int)($value['level_id']??0),[11,12,13],true))continue;
            $level=pmpro_getLevel((int)$value['level_id']);if(!$level)continue;
            $out[]=['provider'=>$provider,'product_id'=>(string)$id,'base_plan_id'=>$value['base_plan_id']??'','level_id'=>(int)$level->id,'title'=>wp_strip_all_tags($level->name)];
        }return $out;
    }
    public static function read(int $user): array|\WP_Error {
        if(!self::ready())return self::error('membership_unavailable','No se puede consultar la membresía ahora.',503);
        return self::locked('user:'.$user,function()use($user){
            $subs=[];foreach(self::rows($user) as $row){$level=pmpro_getLevel((int)$row['level_id']);$subs[]=['id'=>$row['id'],'provider'=>$row['provider'],'product_id'=>$row['product_id'],'plan'=>$level?wp_strip_all_tags($level->name):'Membresía CDE','status'=>$row['state'],'expires_at'=>(int)$row['expires_at']>0?gmdate('c',(int)$row['expires_at']):null,'auto_renew'=>(bool)$row['auto_renew'],'manage'=>'store'];}
            foreach(self::webSubscriptions($user) as $s) {
                $level=pmpro_getLevel((int)$s->get_membership_level_id());
                $item=['id'=>'web:'.$s->get_id(),'provider'=>$s->get_gateway()==='stripe'?'stripe':'web','product_id'=>null,'plan'=>$level?wp_strip_all_tags($level->name):'Membresía web','status'=>$s->get_status(),'expires_at'=>null,'auto_renew'=>null,'manage'=>'support'];
                if($s->get_gateway()==='stripe'&&$s->get_gateway_environment()===self::webEnvironment()&&StripeMembership::enabled()) {
                    $live=StripeMembership::snapshot($s->get_subscription_transaction_id());
                    if(!is_wp_error($live))$item=array_merge($item,$live,['manage'=>'stripe']);
                }
                $subs[]=$item;
            }
            $permission=Access::member($user);if(is_wp_error($permission)&&($permission->get_error_data()['status']??0)===503)return $permission;$granted=$permission===true;
            return ['access'=>$granted,'purchase_available'=>!$granted&&!array_filter($subs,static fn($s)=>in_array($s['status'],['active','trialing','past_due','pending','hold','paused','grace'],true)),'store_ready'=>['apple'=>self::enabled('apple'),'google'=>self::enabled('google')],'products'=>self::products(),'subscriptions'=>$subs,'account_token'=>self::accountToken($user)];
        });
    }
    public static function intent(int $user,array $body): array|\WP_Error {
        if(!Sessions::limit('purchase-intent:'.$user,30))return self::error('rate_limited','Espera unos minutos.',429);
        return self::locked('user:'.$user,function()use($user,$body){
            if(!self::enabled($body['provider']))return self::error('store_unavailable','La contratación no está disponible todavía.',503);
            $found=false;foreach(self::products() as $p)if($p['provider']===$body['provider']&&$p['product_id']===$body['product_id']&&$p['base_plan_id']===($body['base_plan_id']??''))$found=true;
            if(!$found)return self::error('product_unavailable','Este plan no está disponible.');
            if(!empty($body['subscription_id'])) {
                $owned=array_values(array_filter(self::rows($user),static fn($r)=>$r['id']===$body['subscription_id']&&$r['provider']===$body['provider']&&self::grants($r,time())));
                if(count($owned)!==1)return self::error('subscription_unavailable','Restaura primero la suscripción que quieres cambiar.',409);
                foreach(self::webSubscriptions($user) as $s)if($s->get_status()==='active')return self::error('multiple_subscriptions','Hay otra suscripción web. Contacta con soporte antes de cambiar de plan.',409);
                return ['account_token'=>self::accountToken($user)];
            }
            // La contratación inicial no debe duplicar derechos de otro proveedor.
            $permission=Access::member($user);if(is_wp_error($permission)&&($permission->get_error_data()['status']??0)===503)return $permission;
            if($permission===true)return self::error('already_member','Ya tienes acceso. Puedes gestionar tu membresía actual.',409);
            foreach(self::webSubscriptions($user) as $s)if($s->get_status()==='active')return self::error('already_subscribed','Ya tienes una suscripción. Revísala antes de contratar.',409);
            foreach(self::rows($user) as $r)if(in_array($r['state'],['pending','hold','paused','grace'],true))return self::error('purchase_pending','Hay una compra pendiente. Restáurala o gestiona su proveedor.',409);
            $intent=get_user_meta($user,'cde_purchase_intent',true);
            if(is_array($intent)&&$intent['expires']>time()&&$intent['provider']!==$body['provider'])return self::error('purchase_in_progress','Hay una contratación iniciada en otra tienda. Espera a comprobar su resultado.',409);
            update_user_meta($user,'cde_purchase_intent',['provider'=>$body['provider'],'expires'=>time()+86400]);
            return ['account_token'=>self::accountToken($user)];
        });
    }
    private static function crypt(string $value,bool $decrypt=false): string {
        $key=hash('sha256',wp_salt('secure_auth').':billing',true);
        if($decrypt){$raw=base64_decode($value,true);$plain=$raw?sodium_crypto_secretbox_open(substr($raw,24),substr($raw,0,24),$key):false;if($plain===false)throw new \RuntimeException('Referencia inválida');return $plain;}
        $nonce=random_bytes(24);return base64_encode($nonce.sodium_crypto_secretbox($value,$nonce,$key));
    }
    public static function referenceHash(string $provider,string $reference): string {return hash('sha256',$provider.':'.self::environment().':'.$reference);}
    public static function verify(string $provider,string $reference,string $action='verify',array $extra=[]): array|\WP_Error {
        if(!self::enabled($provider))return self::error('store_unavailable','La verificación de compras no está disponible todavía.',503);
        $command=[getenv('CDE_BILLING_PYTHON'),getenv('CDE_BILLING_VERIFIER'),getenv('CDE_BILLING_CONFIG_FILE')];
        $proc=proc_open($command,[0=>['pipe','r'],1=>['pipe','w'],2=>['file','/dev/null','a']],$pipes);
        if(!is_resource($proc))return self::error('verification_unavailable','No se pudo comprobar la compra. Inténtalo de nuevo.',503);
        fwrite($pipes[0],wp_json_encode(array_merge($extra,['provider'=>$provider,'reference'=>$reference,'environment'=>self::environment(),'action'=>$action])));fclose($pipes[0]);
        stream_set_timeout($pipes[1],28);$output=stream_get_contents($pipes[1],32769);$meta=stream_get_meta_data($pipes[1]);fclose($pipes[1]);
        if($meta['timed_out'])proc_terminate($proc);$exit=proc_close($proc);$data=json_decode($output,true);
        if($exit!==0||!is_array($data)||isset($data['error'])||strlen($output)>32768)return self::error('verification_unavailable','No se pudo comprobar la compra. Puedes restaurarla más tarde.',503);
        return $data;
    }
    public static function validate(array $data,string $provider,string $token,array $products): bool {
        return ($data['provider']??'')===$provider&&($data['environment']??'')===self::environment()
            &&is_string($data['account_token']??null)&&hash_equals(strtolower($token),strtolower($data['account_token']))
            &&is_string($data['reference']??null)&&strlen($data['reference'])>0&&strlen($data['reference'])<=4096
            &&isset($products[$data['product_id']??''])&&in_array((int)$products[$data['product_id']]['level_id'],[11,12,13],true)
            &&in_array($data['status']??'', ['active','grace','cancelled','expired','hold','paused','pending','revoked','replaced'],true)
            &&($provider!=='google'||($data['base_plan_id']??'')===($products[$data['product_id']]['base_plan_id']??''))
            &&is_int($data['expires_at']??null)&&$data['expires_at']>=0&&is_bool($data['auto_renew']??null)
            &&is_int($data['checked_at']??null)&&abs(time()-$data['checked_at'])<120;
    }
    public static function purchase(int $user,array $body): array|\WP_Error {
        if(!Sessions::limit('verify:'.$user,60))return self::error('rate_limited','Espera unos minutos antes de restaurar otra vez.',429);
        return self::locked('user:'.$user,function()use($user,$body){
            $token=self::accountToken($user);$provider=$body['provider'];$data=self::verify($provider,$body['reference']);if(is_wp_error($data))return $data;
            if(!self::validate($data,$provider,$token,self::config()[$provider]['products']??[]))return self::error('purchase_account_mismatch','No se puede vincular esta compra a tu cuenta. Contacta con soporte.',403);
            $result=self::persist($user,$data);
            if(!is_wp_error($result)&&$provider==='google'&&in_array($data['status'],['active','grace','cancelled'],true))self::verify($provider,$body['reference'],'acknowledge');
            return $result;
        });
    }
    public static function persist(int $user,array $data): array|\WP_Error {
        global $wpdb;$table=Schema::table('subscriptions');$hash=self::referenceHash($data['provider'],$data['reference']);
        return self::locked('reference:'.$hash,function()use($user,$data,$table,$hash,$wpdb){
            Schema::query('START TRANSACTION');try {
                $previous=$wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE reference_hash=%s FOR UPDATE",$hash),ARRAY_A);
                if($previous&&((int)$previous['user_id']!==$user||$previous['state']==='replaced')){Schema::query('ROLLBACK');return self::error('purchase_owned','Esta compra ya está vinculada o ha sido sustituida. Contacta con soporte.',409);}
                if(!empty($data['linked_reference'])&&$data['linked_reference']!==$data['reference']){
                    $linked=self::referenceHash($data['provider'],$data['linked_reference']);
                    $old=$wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE reference_hash=%s FOR UPDATE",$linked),ARRAY_A);
                    if($old&&(int)$old['user_id']!==$user){Schema::query('ROLLBACK');return self::error('purchase_owned','No se puede vincular esta compra.',409);}
                    if($old)Schema::query($wpdb->prepare("UPDATE $table SET state='replaced',auto_renew=0,expires_at=0 WHERE reference_hash=%s",$linked));
                    else if($wpdb->insert($table,['id'=>wp_generate_uuid4(),'user_id'=>$user,'provider'=>$data['provider'],'environment'=>self::environment(),'reference_hash'=>$linked,'reference_cipher'=>self::crypt($data['linked_reference']),'account_token'=>strtolower($data['account_token']),'product_id'=>$data['product_id'],'level_id'=>(int)self::config()[$data['provider']]['products'][$data['product_id']]['level_id'],'state'=>'replaced','expires_at'=>0,'auto_renew'=>0,'checked_at'=>time(),'last_attempt'=>time()])===false)throw new \RuntimeException('Persistencia de sustitución');
                }
                $row=['id'=>$previous['id']??wp_generate_uuid4(),'user_id'=>$user,'provider'=>$data['provider'],'environment'=>self::environment(),'reference_hash'=>$hash,'reference_cipher'=>self::crypt($data['reference']),'account_token'=>strtolower($data['account_token']),'product_id'=>$data['product_id'],'level_id'=>(int)self::config()[$data['provider']]['products'][$data['product_id']]['level_id'],'state'=>$data['status'],'expires_at'=>$data['expires_at'],'auto_renew'=>(int)$data['auto_renew'],'checked_at'=>time(),'last_attempt'=>time()];
                $ok=$previous?$wpdb->update($table,$row,['id'=>$previous['id']]):$wpdb->insert($table,$row);if($ok===false)throw new \RuntimeException('Persistencia');
                Schema::query('COMMIT');wp_cache_delete('user_'.$user.'_levels_active','pmpro');wp_cache_delete('user_'.$user.'_levels_all','pmpro');
            }catch(\Throwable $e){$wpdb->query('ROLLBACK');throw $e;}
            return ['verified'=>true,'finishable'=>!in_array($data['status'],['pending','hold','paused'],true)];
        });
    }
    public static function refreshRow(array $row): array|\WP_Error {
        return self::locked('user:'.$row['user_id'],function()use($row){
            global $wpdb;$table=Schema::table('subscriptions');
            $wpdb->update($table,['last_attempt'=>time()],['id'=>$row['id']]);
            $data=self::verify($row['provider'],self::crypt($row['reference_cipher'],true));
            if(is_wp_error($data))return $data;
            if(!self::validate($data,$row['provider'],$row['account_token'],self::config()[$row['provider']]['products']??[]))return self::error('verification_failed','No se pudo validar la compra.',503);
            $result=self::persist((int)$row['user_id'],$data);
            if(!is_wp_error($result)&&$row['provider']==='google'&&in_array($data['status'],['active','grace','cancelled'],true))self::verify('google',$data['reference'],'acknowledge');
            return $result;
        });
    }
    public static function reconcile(): void {
        if(!self::ready())return;global $wpdb;$table=Schema::table('subscriptions');
        $rows=$wpdb->get_results($wpdb->prepare("SELECT * FROM $table WHERE user_id>0 AND environment=%s AND state NOT IN ('replaced') AND last_attempt<%d ORDER BY last_attempt ASC LIMIT 20",self::environment(),time()-300),ARRAY_A);
        foreach($rows as $row)if(self::enabled($row['provider']))try{self::refreshRow($row);}catch(\Throwable $e){/* Reintentar cada fila sin registrar recibos ni identidades. */}
    }
    public static function notification(string $provider,array $body,string $authorization): array|\WP_Error {
        if(!Sessions::limit('store-notification:'.($_SERVER['REMOTE_ADDR']??''),1000))return self::error('rate_limited','Inténtalo más tarde.',429);
        // Un aviso sólo despierta la consulta autenticada del estado actual. Nunca concede acceso por su contenido.
        $notice=self::verify($provider,'','notification',['notification'=>$body,'authorization'=>$authorization]);
        if(is_wp_error($notice))return $notice;
        if(!is_string($notice['reference']??null)||strlen($notice['reference'])>4096)return self::error('invalid_notification','Notificación no válida.',403);
        global $wpdb;$table=Schema::table('subscriptions');
        $row=$wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE reference_hash=%s",self::referenceHash($provider,$notice['reference'])),ARRAY_A);
        if(!$row&&$notice['reference']!=='') {
            $fresh=self::verify($provider,$notice['reference']);if(is_wp_error($fresh))return $fresh;
            if(!is_string($fresh['account_token']??null))return ['status'=>'received'];
            $users=get_users(['meta_key'=>'cde_billing_account_token','meta_value'=>strtolower($fresh['account_token']),'fields'=>'ID','number'=>2]);
            if(count($users)!==1)return ['status'=>'received'];
            return self::purchase((int)$users[0],['provider'=>$provider,'reference'=>$notice['reference']]);
        }
        if($row&&(int)$row['user_id']>0&&$row['state']!=='replaced'){$result=self::refreshRow($row);if(is_wp_error($result))return $result;}
        return ['status'=>'received'];
    }
}
add_filter('pmpro_get_membership_levels_for_user',[Membership::class,'levels'],20,2);
add_filter('cron_schedules',static function($s){$s['cde_five_minutes']=['interval'=>300,'display'=>'CDE: cada cinco minutos'];return $s;});
add_action('cde_mobile_reconcile_memberships',[Membership::class,'reconcile']);

// Also protect web checkout: a native subscriber must manage the original provider.
add_filter('pmpro_checkout_checks',static function($continue,$level){
    $user=get_current_user_id();if(!$user||!in_array((int)($level->id??0),[11,12,13],true))return $continue;
    foreach(Membership::rows($user) as $row)if(Membership::grants($row,time())||in_array($row['state'],['pending','hold','paused'],true)){
        pmpro_setMessage('Ya tienes una suscripción contratada desde la app. Gestiona su plan y renovación con Apple o Google Play para evitar una compra duplicada.','pmpro_error');return false;
    }
    $intent=get_user_meta($user,'cde_purchase_intent',true);
    if(is_array($intent)&&($intent['expires']??0)>time()){pmpro_setMessage('Hay una contratación iniciada en la app. Comprueba su resultado antes de contratar en la web.','pmpro_error');return false;}
    return $continue;
},20,2);

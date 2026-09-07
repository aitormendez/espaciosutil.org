<?php
use EspacioSutil\Mobile\Membership;
use EspacioSutil\Mobile\Registration;
use EspacioSutil\Mobile\Access;
use EspacioSutil\Mobile\Schema;
if(WP_ENV!=='staging'||gethostname()!=='espacio-sutil-staging'||!defined('WP_CLI'))throw new RuntimeException('Sólo staging CLI');
$_SERVER['HTTPS']='on';$_SERVER['REMOTE_ADDR']='198.51.100.'.random_int(1,250);
$GLOBALS['billing_checks']=[];$created=[];$code='';$suffix=strtolower(wp_generate_password(12,false));$email='cde-billing-'.$suffix.'@example.invalid';$password=wp_generate_password(28,true,true);
$cfg=tempnam(sys_get_temp_dir(),'cde-billing-fixture-');chmod($cfg,0600);
file_put_contents($cfg,json_encode(['google'=>['enabled'=>false,'environment'=>'sandbox','products'=>['synthetic.month'=>['level_id'=>11,'base_plan_id'=>'month']]],'apple'=>['enabled'=>false],'stripe'=>['enabled'=>false]]));putenv('CDE_BILLING_CONFIG_FILE='.$cfg);
$mail=static function($pre,$attributes)use(&$code,$email){if($attributes['to']===$email&&preg_match('/\b([0-9]{6})\b/',$attributes['message'],$m))$code=$m[1];return true;};add_filter('pre_wp_mail',$mail,PHP_INT_MAX,2);
function check_billing($condition,$name){if(!$condition)throw new RuntimeException('FAIL: '.$name);$GLOBALS['billing_checks'][]=$name;}
function request_billing($path,$body=null,$token=null,$method=null){$r=new WP_REST_Request($method??($body===null?'GET':'POST'),'/cde-mobile/v1'.$path);$r->set_header('content-type','application/json');if($token)$r->set_header('authorization','Bearer '.$token);if($body!==null)$r->set_body(wp_json_encode($body));return rest_do_request($r);}
try {
 check_billing(request_billing('/membership')->get_status()===401,'membership requires session');
 check_billing(request_billing('/auth/register',['name'=>'Prueba sintética','email'=>$email,'consent'=>false])->get_status()===422,'consent required');
 $start=request_billing('/auth/register',['name'=>'Prueba sintética','email'=>$email,'consent'=>true]);check_billing($start->get_status()===200,'registration challenge');
 check_billing(!email_exists($email)&&strlen($code)===6,'no account before email verification (mail captured, not sent)');$reg=$start->get_data()['registration_token'];
 check_billing(request_billing('/auth/register/confirm',['registration_token'=>$reg,'code'=>'000000','password'=>$password])->get_status()===422,'incorrect code rejected');
 $confirm=request_billing('/auth/register/confirm',['registration_token'=>$reg,'code'=>$code,'password'=>$password]);check_billing($confirm->get_status()===200,'registration confirmed');$user=(int)email_exists($email);$created[]=$user;
 check_billing($user>0&&get_userdata($user)->roles===['subscriber'],'new account is subscriber');
 check_billing(request_billing('/auth/register/confirm',['registration_token'=>$reg,'code'=>$code,'password'=>$password])->get_status()===422,'registration cannot replay');
 $login=request_billing('/auth/login',['login'=>$email,'password'=>$password,'device_label'=>'Synthetic billing QA']);check_billing($login->get_status()===200,'login after registration');$token=$login->get_data()['access_token'];
 $supportId=wp_generate_uuid4();$support=request_billing('/account/support',['operation_id'=>$supportId,'subject'=>'Uso de la app','message'=>'Consulta sintética temporal para comprobar eliminación de datos.','privacy_consent'=>true],$token);check_billing($support->get_status()===200,'synthetic support stored');
 $me=request_billing('/me',null,$token);check_billing(!$me->get_data()['access']['granted'],'registration alone grants no course');
 $membership=request_billing('/membership',null,$token);check_billing($membership->get_status()===200,'membership read');$data=$membership->get_data();check_billing($data['store_ready']===['apple'=>false,'google'=>false]&&$data['products']===[],'stores fail closed without accounts');
 check_billing(request_billing('/membership/purchase',['provider'=>'google','reference'=>'synthetic-token','user_id'=>1],$token)->get_status()===422,'client cannot set purchase owner');
 check_billing(request_billing('/membership/purchase',['provider'=>'google','reference'=>'synthetic-token'],$token)->get_status()===503,'unconfigured verification never succeeds');
 check_billing(request_billing('/membership/intent',['provider'=>'google','product_id'=>'synthetic.month'],$token)->get_status()===503,'unconfigured checkout never starts');
 check_billing(request_billing('/account/delete',['current_password'=>'incorrect','confirm'=>true,'understands_subscriptions'=>true],$token)->get_status()===422,'deletion requires current password');
 check_billing(request_billing('/membership/manage',['id'=>'web:1','action'=>'cancel_renewal','current_password'=>$password],$token)->get_status()===404,'subscription management scoped to owner');
 $other=wp_insert_user(['user_login'=>'cde_synthetic_'.$suffix,'user_email'=>'other-'.$email,'user_pass'=>$password,'role'=>'subscriber']);if(is_wp_error($other))throw new RuntimeException('fixture user');$created[]=$other;
 $snapshot=['provider'=>'google','environment'=>'sandbox','account_token'=>$data['account_token'],'reference'=>'synthetic-'.$suffix,'product_id'=>'synthetic.month','base_plan_id'=>'month','status'=>'active','expires_at'=>time()+3600,'auto_renew'=>true,'checked_at'=>time()];
 check_billing(Membership::validate($snapshot,'google',$data['account_token'],['synthetic.month'=>['level_id'=>11,'base_plan_id'=>'month']]),'synthetic verified shape');
 $saved=Membership::persist($user,$snapshot);check_billing(!is_wp_error($saved),'synthetic entitlement persisted');check_billing(Access::member($user)===true,'native entitlement reaches shared PMPro access');
 wp_set_current_user($user);check_billing(apply_filters('pmpro_checkout_checks',true,pmpro_getLevel(12))===false,'native membership blocks duplicate web checkout');wp_set_current_user(0);
 check_billing(count(Membership::webSubscriptions($user))===0,'native entitlement creates no Stripe subscription');
 check_billing(!is_wp_error(Membership::persist($user,$snapshot))&&count(Membership::rows($user))===1,'purchase persistence idempotent');
 check_billing(is_wp_error(Membership::persist($other,$snapshot)),'reference cannot move to another account');
 $snapshot['status']='revoked';check_billing(!is_wp_error(Membership::persist($user,$snapshot))&&Access::member($user)!==true,'revocation withdraws only native right');
 // A synthetic PMPro grant exercises coexistence without invoking checkout or a gateway.
 pmpro_changeMembershipLevel(11,$user);check_billing(Access::member($user)===true,'independent web right survives native revocation');
 $snapshot['reference']='replacement-'.$suffix;$snapshot['linked_reference']='missing-old-'.$suffix;$snapshot['status']='active';Membership::persist($user,$snapshot);
 $old=$snapshot;unset($old['linked_reference']);$old['reference']='missing-old-'.$suffix;check_billing(is_wp_error(Membership::persist($user,$old)),'unseen replaced reference remains unusable');
 // Verify deletion suppression even when PMPro's optional hook is enabled elsewhere.
 $GLOBALS['billing_cancelled']=0;$onChange=static function(){$GLOBALS['billing_cancelled']++;};add_action('pmpro_after_change_membership_level',$onChange);$cancelFilter=static fn()=>true;add_filter('pmpro_user_deletion_cancel_active_subscriptions',$cancelFilter,10,2);
 $deleted=request_billing('/account/delete',['current_password'=>$password,'confirm'=>true,'understands_subscriptions'=>true],$token);remove_filter('pmpro_user_deletion_cancel_active_subscriptions',$cancelFilter,10);
 remove_action('pmpro_after_change_membership_level',$onChange);check_billing($GLOBALS['billing_cancelled']===0,'deletion does not invoke membership cancellation');
 check_billing($deleted->get_status()===200&&!get_userdata($user),'account deletion completed');
 check_billing(request_billing('/me',null,$token)->get_status()===401,'deleted account token invalid');
 global $wpdb;check_billing((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}hf_submissions WHERE data LIKE %s",'%'.$wpdb->esc_like($supportId).'%'))===0,'support data erased with account');$table=Schema::table('subscriptions');check_billing((int)$wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table WHERE user_id=%d",$user))===0,'native records no longer identify deleted user');
 check_billing(is_wp_error(Membership::persist($other,$snapshot)),'deleted purchase cannot be rebound');
 echo wp_json_encode(['environment'=>'staging','store_calls'=>0,'email_delivery'=>'captured-only','passed'=>count($GLOBALS['billing_checks']),'checks'=>$GLOBALS['billing_checks']],JSON_PRETTY_PRINT)."\n";
} finally {
 require_once ABSPATH.'wp-admin/includes/user.php';
 foreach($created as $id)if($id&&get_userdata($id)){$_REQUEST['pmpro_delete_member_history']='1';wp_delete_user($id);}unset($_REQUEST['pmpro_delete_member_history']);
 global $wpdb;$table=Schema::table('subscriptions');$wpdb->query($wpdb->prepare("DELETE FROM $table WHERE product_id='synthetic.month' AND (reference_hash IN (%s,%s,%s))",Membership::referenceHash('google','synthetic-'.$suffix),Membership::referenceHash('google','replacement-'.$suffix),Membership::referenceHash('google','missing-old-'.$suffix)));
 foreach($created as $id){foreach(['sessions','revisions','receipts'] as $t)$wpdb->delete(Schema::table($t),['user_id'=>$id]);$wpdb->delete($wpdb->prefix.'pmpro_memberships_users',['user_id'=>$id]);}
 remove_filter('pre_wp_mail',$mail,PHP_INT_MAX);putenv('CDE_BILLING_CONFIG_FILE');unlink($cfg);
}

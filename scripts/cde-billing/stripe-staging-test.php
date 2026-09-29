<?php
use EspacioSutil\Mobile\StripeMembership;
use EspacioSutil\Mobile\Schema;
if(WP_ENV!=='staging'||gethostname()!=='espacio-sutil-staging'||!defined('WP_CLI'))throw new RuntimeException('Sólo staging CLI');
$checks=[];$user=0;$sub=null;$keyFile=tempnam(sys_get_temp_dir(),'cde-key-fixture-');$config=tempnam(sys_get_temp_dir(),'cde-config-fixture-');chmod($keyFile,0600);chmod($config,0600);
file_put_contents($keyFile,'sk_test_'.str_repeat('0',24));file_put_contents($config,wp_json_encode(['stripe'=>['enabled'=>true,'environment'=>'sandbox','secret_key_file'=>$keyFile]]));putenv('CDE_BILLING_CONFIG_FILE='.$config);
$identifier='sub_synthetic'.str_replace('-','',wp_generate_uuid4());$live=false;$cancel=false;$posts=[];
$transport=static function($pre,$args,$url)use($identifier,&$live,&$cancel,&$posts){
 if($url!=='https://api.stripe.com/v1/subscriptions/'.$identifier)return new WP_Error('synthetic_transport_only','External transport blocked in test');
 if(($args['method']??'GET')==='POST'){$posts[]=$args['body'];$cancel=$args['body']['cancel_at_period_end']==='true';}
 return ['response'=>['code'=>200,'message'=>'OK'],'headers'=>[],'body'=>wp_json_encode(['id'=>$identifier,'livemode'=>$live,'status'=>'active','cancel_at_period_end'=>$cancel,'current_period_end'=>time()+3600]),'cookies'=>[]];
};
add_filter('pre_http_request',$transport,PHP_INT_MAX,3);add_filter('pre_wp_mail','__return_false',PHP_INT_MAX);
function stripe_check($yes,$label){if(!$yes)throw new RuntimeException('FAIL: '.$label);}
try {
 $password=wp_generate_password(28,true,true);$user=wp_insert_user(['user_login'=>'cde_stripe_'.str_replace('-','',wp_generate_uuid4()),'user_email'=>'cde-stripe-'.wp_generate_uuid4().'@example.invalid','user_pass'=>$password,'role'=>'subscriber']);if(is_wp_error($user))throw new RuntimeException('fixture');
 $sub=PMPro_Subscription::create(['user_id'=>$user,'membership_level_id'=>11,'gateway'=>'stripe','gateway_environment'=>'sandbox','subscription_transaction_id'=>$identifier,'status'=>'active']);stripe_check($sub!==null,'PMPro fixture');
 $id='web:'.$sub->get_id();
 $result=StripeMembership::manage($user,['id'=>$id,'action'=>'cancel_renewal','current_password'=>'wrong']);stripe_check(is_wp_error($result)&&count($posts)===0,'password prevents mutation');$checks[]='password prevents mutation';
 $result=StripeMembership::manage($user,['id'=>$id,'action'=>'cancel_renewal','current_password'=>$password]);stripe_check(!is_wp_error($result)&&$result['auto_renew']===false&&$posts===[['cancel_at_period_end'=>'true']],'cancellation defers to period end');$checks[]='cancellation defers to period end';
 $state=StripeMembership::snapshot($identifier);stripe_check(!is_wp_error($state)&&$state['auto_renew']===false,'state reads provider');$checks[]='state reads provider';
 StripeMembership::manage($user,['id'=>$id,'action'=>'cancel_renewal','current_password'=>$password]);stripe_check(count($posts)===1,'same cancellation is idempotent');$checks[]='same cancellation is idempotent';
 $result=StripeMembership::manage($user,['id'=>$id,'action'=>'resume_renewal','current_password'=>$password]);stripe_check(!is_wp_error($result)&&$result['auto_renew']===true&&$posts[1]===['cancel_at_period_end'=>'false'],'renewal resumes');$checks[]='renewal resumes';
 $live=true;$result=StripeMembership::manage($user,['id'=>$id,'action'=>'cancel_renewal','current_password'=>$password]);stripe_check(is_wp_error($result)&&count($posts)===2,'live response cannot mutate');$checks[]='live response cannot mutate';
 $args=['method'=>'POST','redirection'=>0,'headers'=>['Authorization'=>'Bearer '.file_get_contents($keyFile)],'body'=>['cancel_at_period_end'=>'true']];$url='https://api.stripe.com/v1/subscriptions/'.$identifier;
 stripe_check(StripeMembership::allowsTestRequest($args,$url),'specific test request allowed');
 foreach(['refunds','charges'] as $path)stripe_check(!StripeMembership::allowsTestRequest($args,'https://api.stripe.com/v1/'.$path),'no '.$path);$checks[]='network exception excludes charges and refunds';
 $args['body']=['items'=>[]];stripe_check(!StripeMembership::allowsTestRequest($args,$url),'no price changes through renewal endpoint');$checks[]='network exception excludes unapproved changes';
 echo wp_json_encode(['environment'=>'staging','provider'=>'Stripe','verification'=>'HTTP responses simulated; no Stripe requests','passed'=>count($checks),'checks'=>$checks],JSON_PRETTY_PRINT)."\n";
}finally{
 remove_filter('pre_http_request',$transport,PHP_INT_MAX);global $wpdb;if($sub)$wpdb->delete($wpdb->prefix.'pmpro_subscriptions',['id'=>$sub->get_id()]);
 if(is_int($user)&&$user>0){require_once ABSPATH.'wp-admin/includes/user.php';wp_delete_user($user);$wpdb->delete(Schema::table('limits'),['bucket'=>'unused-fixture']);}
 putenv('CDE_BILLING_CONFIG_FILE');unlink($keyFile);unlink($config);
}

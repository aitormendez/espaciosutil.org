<?php
namespace {
    define('WP_ENV','staging');
    class WP_Error { public function __construct(public $code,public $message,public $data=[]){} public function get_error_message(){return $this->message;} }
    function is_wp_error($v){return $v instanceof WP_Error;}
    function add_filter(...$args){} function add_action(...$args){} function wp_cache_delete(...$args){}
    $meta=[];$writeFailure=false;
    function get_user_meta($u,$k,$single=true){return $GLOBALS['meta'][$u][$k]??'';}
    function update_user_meta($u,$k,$v){if($GLOBALS['writeFailure'])return false;$GLOBALS['meta'][$u][$k]=$v;return true;}
    function espaciosutil_pmpro_get_trial_config($id){return in_array($id,[11,12,13],true)?['delay_days'=>7]:null;}
    class FakeDatabase {
        public $pmpro_membership_orders='wp_pmpro_membership_orders';public $last_error='';public $pending=null;
        function prepare($sql,...$args){return $sql;}
        function get_var($sql){return $this->pending;}
    }
    $wpdb=new FakeDatabase();
}
namespace EspacioSutil\Mobile {
    require __DIR__.'/../../site/web/app/mu-plugins/cde-mobile/membership.php';
    function expect($condition,$label){if(!$condition)throw new \RuntimeException($label);}
    $p=['level_id'=>11,'billing_period'=>'P1M','trial_offer_id'=>'prueba-7-dias'];
    expect(SharedTrial::offer(1,$p)['eligible'],'new account');
    $GLOBALS['meta'][1]['espaciosutil_pmpro_trial_used']=1;
    expect(SharedTrial::offer(1,$p)['offer_id']==='','web trial already used');
    expect(!SharedTrial::offer(2,$p,true)['eligible'],'plan change is never a new trial');
    expect(SharedTrial::offer(2,['level_id'=>11])===null,'missing config fails closed');
    expect(SharedTrial::reserve(2,'google','month','monthly','prueba-7-dias')===true,'reserve first choice');
    expect(!SharedTrial::used(2),'intent does not consume trial');
    expect(SharedTrial::reserve(2,'google','month','monthly','prueba-7-dias')===true,'retry same choice');
    expect(is_wp_error(SharedTrial::reserve(2,'google','year','yearly','prueba-7-dias')),'different plan cannot race');
    expect(is_wp_error(SharedTrial::reserve(2,'web','11','','')),'web cannot race app');
    expect(SharedTrial::consume(2,'purchase-hash')===true,'verified purchase consumes');
    expect(SharedTrial::used(2),'web sees native trial used');
    expect(SharedTrial::consume(2,'purchase-hash')===true,'restore is idempotent');
    expect(is_wp_error(SharedTrial::consume(2,'another-purchase')),'second reference cannot consume');
    expect(is_wp_error(SharedTrial::consume(1,'native-after-web')),'web trial excludes native trial');
    expect(SharedTrial::reserve(3,'web','11','','')===true,'reserve web first');
    expect(is_wp_error(SharedTrial::reserve(3,'google','month','monthly','prueba-7-dias')),'app cannot race web');
    expect(is_wp_error(SharedTrial::consume(3,'late-native')),'late native receipt cannot override web reservation');
    $GLOBALS['wpdb']->pending=123;
    expect(is_wp_error(SharedTrial::consume(4,'native')),'pending web token blocks trial even without recent reservation');
    $GLOBALS['wpdb']->pending=null;$GLOBALS['writeFailure']=true;
    expect(is_wp_error(SharedTrial::reserve(5,'google','month','monthly','prueba-7-dias')),'reservation write failure');
    try {SharedTrial::consume(6,'hash');throw new \LogicException('write failure not detected');}catch(\RuntimeException $e){expect(!SharedTrial::used(6),'failed persistence cannot claim trial');}
    $GLOBALS['writeFailure']=false;
    foreach([['offer_id'=>'other','trial_used'=>true,'status'=>'active'],['offer_id'=>'','trial_used'=>true,'status'=>'active'],['offer_id'=>'prueba-7-dias','trial_used'=>true,'status'=>'pending']] as $bad)expect(!SharedTrial::validSnapshot($bad,$p),'reject invalid snapshot');
    expect(SharedTrial::validSnapshot(['offer_id'=>'prueba-7-dias','trial_used'=>true,'status'=>'active'],$p),'verified trial snapshot');
    echo "shared-trial: 24 checks passed (synthetic)\n";
}

<?php
namespace EspacioSutil\Mobile;
define('WP_ENV','staging');
function add_filter(...$args){}function add_action(...$args){}
require __DIR__.'/../../site/web/app/mu-plugins/cde-mobile/membership.php';
function check($yes,$label){if(!$yes)throw new \RuntimeException($label);}
foreach(['active','grace','cancelled','expired','hold','paused','pending','revoked','replaced'] as $state){
    check(Membership::grants(['state'=>$state,'expires_at'=>200],100)===in_array($state,['active','grace','cancelled'],true),'grant '.$state);
    check(!Membership::grants(['state'=>$state,'expires_at'=>99],100),'expiry '.$state);
}
$products=['synthetic'=>['level_id'=>11,'base_plan_id'=>'month']];
$d=['provider'=>'google','environment'=>'sandbox','account_token'=>'a','reference'=>'synthetic-token','product_id'=>'synthetic','base_plan_id'=>'month','status'=>'active','expires_at'=>time()+60,'auto_renew'=>true,'checked_at'=>time()];
check(Membership::validate($d,'google','a',$products),'valid fixture');
foreach(['environment'=>'production','account_token'=>'other','product_id'=>'other','base_plan_id'=>'other','checked_at'=>1,'status'=>'unknown','expires_at'=>-1,'auto_renew'=>'true'] as $key=>$value){$bad=$d;$bad[$key]=$value;check(!Membership::validate($bad,'google','a',$products),'reject '.$key);}
echo "membership-domain: 27 checks passed (synthetic)\n";

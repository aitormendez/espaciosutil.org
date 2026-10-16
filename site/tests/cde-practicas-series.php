<?php
/** php site/tests/cde-practicas-series.php — isolated hierarchy regression, no database. */
require __DIR__.'/../web/app/mu-plugins/cde-practicas/catalog.php';
use EspacioSutil\Practicas\Catalog;
$fixtures = [
    10 => ['terms'=>[61], 'parents'=>[], 'related'=>['100']],
    11 => ['terms'=>[], 'parents'=>[20], 'related'=>['101']],
    12 => ['terms'=>[], 'parents'=>[21,20], 'related'=>['101','102']],
    13 => ['terms'=>[70], 'parents'=>[], 'related'=>['103']],
    14 => ['terms'=>[], 'parents'=>[], 'related'=>['104']],
    15 => ['terms'=>[62], 'parents'=>[], 'related'=>'invalid'],
    20 => ['terms'=>[62], 'parents'=>[]],
    21 => ['terms'=>[], 'parents'=>[20]],
];
class WP_Error {}
function get_term_children($id,$taxonomy) { return $id === 999 ? new WP_Error : ($id === 61 ? [62] : []); }
function is_wp_error($v) { return $v instanceof WP_Error; }
function get_posts($args) {
    if ($args['post_status']!=='publish' || $args['has_password']!==false || $args['meta_query'][0]!==['key'=>'active_lesson','value'=>'1']) throw new RuntimeException('Se pierde el filtro de lecciones disponibles');
    return array_map(fn($id)=>(object)['ID'=>$id],range(10,15));
}
function get_post_ancestors($id) { return $GLOBALS['fixtures'][$id]['parents']; }
function has_term($terms,$taxonomy,$id) { return (bool)array_intersect($terms,$GLOBALS['fixtures'][$id]['terms']); }
function get_post_meta($id,$key,$single) { return $GLOBALS['fixtures'][$id]['related']??''; }
$cases=[
    [61,[100,101,102],'serie directa, bloque hijo, padre y abuelo sin duplicados'],
    [62,[101,102],'bloque heredado sin incluir otras ramas'],
    [70,[103],'otra serie aislada'],
    [80,[],'serie sin prácticas'],
    [999,[],'taxonomía inválida'],
];
foreach($cases as [$series,$expected,$label]) if(Catalog::practiceIdsForSeries($series)!==$expected)throw new RuntimeException($label);
echo count($cases)." casos de filtro por serie correctos\n";

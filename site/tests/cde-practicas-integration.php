<?php
/** Run only in disposable WP + SQLite: wp eval-file /absolute/path/to/this.php --user=qa */

use EspacioSutil\Practicas\Catalog;
use EspacioSutil\Practicas\ImportCommand;

if (!defined('DB_ENGINE') || DB_ENGINE !== 'sqlite' || wp_get_environment_type() !== 'local'
    || !str_contains(ABSPATH, 'cde-practicas-qa') || home_url() !== 'http://127.0.0.1:8897') {
    throw new RuntimeException('Esta prueba requiere el WordPress SQLite desechable de prácticas.');
}
add_filter('pre_wp_mail', '__return_false');
add_filter('pre_http_request', static fn () => new WP_Error('qa_no_network'), PHP_INT_MAX);
register_post_type('cde', ['public' => true, 'supports' => ['title', 'editor'], 'show_in_rest' => true]);
$GLOBALS['practice_checks'] = 0;
$GLOBALS['practice_rejections'] = 0;
function check_practice($condition, $message) {
    if (!$condition) throw new RuntimeException($message);
    $GLOBALS['practice_checks']++;
}
function rejects_practice(callable $operation, string $message) {
    try { $operation(); } catch (Throwable $e) { $GLOBALS['practice_rejections']++; return; }
    throw new RuntimeException($message);
}
$run = 'qa'.bin2hex(random_bytes(4));
$admin = get_current_user_id();
$user = wp_create_user($run, wp_generate_password(), $run.'@example.invalid');
check_practice(!is_wp_error($user), 'Usuario de prueba creado');
wp_set_current_user(0);
check_practice(!Catalog::allowed(), 'Anónimo denegado');
$request = new WP_REST_Request('GET', '/wp/v2/practicas');
check_practice(is_wp_error(Catalog::protectRest(null, null, $request)), 'REST anónimo denegado');
wp_set_current_user($user);
check_practice(!Catalog::allowed(), 'Usuario sin membresía denegado');
global $wpdb;
$wpdb->insert($wpdb->pmpro_membership_levels, ['name' => $run, 'description' => '', 'confirmation' => '', 'billing_amount' => 0, 'initial_payment' => 0]);
$level = (int) $wpdb->insert_id;
check_practice($level > 0, 'Nivel de prueba creado');
check_practice(pmpro_changeMembershipLevel($level, $user), 'Membresía de prueba activa');
pmpro_clear_level_cache_for_user($user);
check_practice(Catalog::allowed(), 'Cualquier membresía activa da acceso, sin hardcode 11/12/13');
check_practice(Catalog::protectRest(null, null, $request) === null, 'REST miembro permitido');
wp_set_current_user($admin);
$lesson = wp_insert_post(['post_type'=>'cde','post_status'=>'publish','post_title'=>'Lección QA '.$run]);
$lessonKey='cde:08:00:'.random_int(100000,999999);
update_post_meta($lesson, 'clave_leccion', $lessonKey);
update_post_meta($lesson, 'active_lesson', '1');
$practiceKey='practica:qa:'.$run;
$practice=['clave_practica'=>$practiceKey,'title'=>'Práctica QA '.$run,'content'=>'<p>Texto &lt;seguro&gt; con "comillas" y \\ barra.</p>', 'excerpt'=>'Resumen de prueba','codigo_practica'=>'EP-QA','referencia_fuente'=>'Fuente de prueba','source_sha256'=>str_repeat('a',64)];
$package=['schema'=>'cde.practices.package','version'=>1,'practices'=>[$practice],'lessons'=>[['clave_leccion'=>$lessonKey,'practicas'=>[$practiceKey]]]];
$plan=ImportCommand::buildPlan($package);
check_practice($plan['practices'][$practiceKey]['action']==='create_draft','Plan crea borrador');
$receipt=ImportCommand::execute($plan);
$id=$receipt['practices'][0]['id_post_wp'];
check_practice(get_post_status($id)==='draft','Importación nunca publica');
check_practice(get_post_meta($lesson,Catalog::RELATION,true)===[(string)$id],'Relationship guarda IDs como cadenas ACF');
check_practice(get_post_field('post_content',$id)===$practice['content'],'Conserva comillas, barras y HTML');
wp_set_current_user($user);
check_practice(Catalog::forLesson($lesson)===[],'Borradores no se muestran en lecciones');
wp_set_current_user($admin);
$repeat=ImportCommand::buildPlan($package);
check_practice($repeat['practices'][$practiceKey]['action']==='keep','Repetición idempotente');
ImportCommand::execute($repeat);
check_practice(count(get_post_meta($lesson,Catalog::RELATION,true))===1,'Sin enlaces duplicados');
$changed=$package;
$changed['practices'][0]['content']='<p>Otro texto</p>';
rejects_practice(fn()=>ImportCommand::buildPlan($changed),'No sobrescribe trabajo editorial');
$stale=ImportCommand::buildPlan($package);
update_field('field_cde_practicas',[(string)$id,'999999'],$lesson);
rejects_practice(fn()=>ImportCommand::execute($stale),'Detecta cambios posteriores al plan');
update_field('field_cde_practicas',[(string)$id],$lesson);
wp_update_post(['ID'=>$id,'post_status'=>'publish']);
$another=wp_insert_post(['post_type'=>'cde','post_status'=>'publish','post_title'=>'Otra lección QA']);
update_post_meta($another,'active_lesson','1');
update_field('field_cde_practicas',[(string)$id],$another);
$inactive=wp_insert_post(['post_type'=>'cde','post_status'=>'publish','post_title'=>'Lección inactiva']);
update_post_meta($inactive,'active_lesson','0');
update_field('field_cde_practicas',[(string)$id],$inactive);
$otherId=wp_insert_post(['post_type'=>'practica','post_status'=>'publish','post_title'=>'Otra práctica']);
$collision=wp_insert_post(['post_type'=>'cde','post_status'=>'publish','post_title'=>'No relacionada']);
update_post_meta($collision,'active_lesson','1');
update_field('field_cde_practicas',[(string)($id.'12')],$collision);
wp_set_current_user($user);
check_practice(count(Catalog::forLesson($lesson))===1,'Práctica publicada en lección');
$inverse=array_map(fn($p)=>$p->ID,Catalog::lessonsFor($id));
check_practice(count($inverse)===2 && in_array($lesson,$inverse,true) && in_array($another,$inverse,true),'Consulta inversa exacta, múltiple y solo activa');
wp_update_post(['ID'=>$another,'post_password'=>'private']);
check_practice(count(Catalog::lessonsFor($id))===1,'No muestra lecciones con contraseña');
$duplicate=wp_insert_post(['post_type'=>'practica','post_status'=>'draft','post_title'=>'Duplicado']);
update_field('field_cde_clave_practica',$practiceKey,$duplicate);
rejects_practice(fn()=>ImportCommand::buildPlan($package),'Rechaza identidad duplicada');
wp_delete_post($duplicate,true);
$protected=clone get_post($id);
$protected->post_password='password';
check_practice(!Catalog::visible($protected),'Prácticas con contraseña no se insertan en otras vistas');
$otherSite=ImportCommand::buildPlan($package);
$otherSite['site']='https://wrong.example.invalid';
rejects_practice(fn()=>ImportCommand::execute($otherSite),'Rechaza plan de otro entorno');
$badExcerpt=$package;
$badExcerpt['practices'][0]['excerpt']='<script>alert(1)</script>';
rejects_practice(fn()=>ImportCommand::buildPlan($badExcerpt),'Rechaza scripts en el extracto');
$bad=$package;
$bad['practices'][0]['content']='<script>alert(1)</script>';
rejects_practice(fn()=>ImportCommand::buildPlan($bad),'Rechaza HTML activo');
pmpro_cancelMembershipLevel($level,$user);
pmpro_clear_level_cache_for_user($user);
check_practice(!Catalog::allowed(),'Membresía cancelada denegada');
check_practice(Catalog::forLesson($lesson)===[] && Catalog::lessonsFor($id)===[],'Cancelación oculta las dos direcciones');
wp_set_current_user(0);
$response=rest_do_request(new WP_REST_Request('GET','/wp/v2/practicas'));
check_practice($response->get_status()===401,'Colección REST anónima denegada');
check_practice(get_oembed_response_data($id,600)===false,'oEmbed no expone título ni extracto');
$response=rest_do_request(new WP_REST_Request('GET','/wp/v2/practicas/'.$id));
check_practice($response->get_status()===401,'Endpoint real deniega contenido anónimo: '.wp_json_encode([$response->get_status(), $response->get_data(), get_current_user_id()]));
$searchRequest=new WP_REST_Request('GET','/wp/v2/search');
$searchRequest->set_param('subtype','practica');
$response=rest_do_request($searchRequest);
check_practice(strpos(wp_json_encode($response->get_data()),$run)===false,'Búsqueda REST no filtra prácticas');
wp_set_current_user($admin);
foreach ([$lesson,$another,$inactive,$collision,$id,$otherId] as $postId) wp_delete_post($postId,true);
require_once ABSPATH.'wp-admin/includes/user.php';
wp_delete_user($user);
$wpdb->delete($wpdb->pmpro_membership_levels,['id'=>$level]);
WP_CLI::success("Prácticas: ".$GLOBALS['practice_checks']." comprobaciones y ".$GLOBALS['practice_rejections']." rechazos de conflicto completados.");

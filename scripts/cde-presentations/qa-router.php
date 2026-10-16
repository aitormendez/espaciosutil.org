<?php
// Harness de pruebas local: ejecuta el controlador y Access reales con primitivas WP simuladas.
// Nunca desplegar este archivo. Solo escucha en loopback mediante php -S 127.0.0.1.
declare(strict_types=1);
$root = dirname(__DIR__, 2);
$package = getenv('CDE_QA_PACKAGE');
$store = getenv('CDE_QA_STORE');
$manifest = json_decode(file_get_contents($package . '/manifest.json'), true);
define('WP_CONTENT_DIR', $root . '/site/web/app');
define('CDE_PRESENTATIONS_DIR', $store);
class WP_Error {
    public function __construct(public $code, public $message, public $data) {}
    public function get_error_code() { return $this->code; }
    public function get_error_data() { return $this->data; }
}
function add_action(...$args) {}
function add_filter(...$args) {}
function is_wp_error($x) {return $x instanceof WP_Error;}
function get_current_user_id() {return match($_COOKIE['qa_mode'] ?? '') {'member'=>1,'denied'=>2,'editor'=>3,default=>0};}
function get_post($id) {return $id===2389 ? (object)['post_type'=>'cde','post_status'=>($_COOKIE['qa_state'] ?? 'publish'),'post_password'=>''] : null;}
function get_post_meta($id,$key,$single=true) {global $manifest;return match($key) {'active_lesson'=>'1','clave_leccion'=>'cde:01:00:0.1','lesson_presentation_enabled'=>'1','_cde_presentation_revision'=>$manifest['revision'],default=>''};}
if (($_COOKIE['qa_dependency'] ?? '') !== 'missing') {
    function pmpro_has_membership_access($id,$user) {return ($_COOKIE['qa_public'] ?? '') === '1' || in_array($user,[1,3]);}
    function pmpro_hasMembershipLevel($levels,$user) {return $user===1;}
}
function current_user_can($cap,$id=0) {return get_current_user_id()===3 && $id===2389;}
function get_the_title($id) {return 'Qué es El libro de Urantia';}
function admin_url($path) {return 'http://127.0.0.1:8877/'.$path;}
function add_query_arg($params,$url) {return $url.'?'.http_build_query($params);}
function absint($value) {return abs((int)$value);}
function nocache_headers() {header('Cache-Control: no-store');}
function status_header($status) {http_response_code($status);}
function wp_json_encode($data) {return json_encode($data);}
function wp_send_json($data) {header('Content-Type: application/json');echo json_encode($data);exit;}
require $root.'/site/web/app/mu-plugins/cde-mobile/access.php';
require $root.'/site/web/app/mu-plugins/cde-presentations/package.php';
require $root.'/site/web/app/mu-plugins/cde-presentations/service.php';
require $root.'/site/web/app/mu-plugins/cde-presentations/web.php';
if (parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)==='/wp-admin/admin-post.php' || parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)==='/admin-post.php') {
    \EspacioSutil\Presentations\deliver();
}
if (parse_url($_SERVER['REQUEST_URI'],PHP_URL_PATH)==='/') {
    $template=file_get_contents($root.'/site/web/app/themes/sage/resources/views/partials/lesson-presentation.blade.php');
    $template=preg_replace('/^@if.*\n|^@endif\s*$/m','',$template);
    $template=preg_replace("/'@json\([^\n]+\)'/", "'".htmlspecialchars(json_encode(['manifestUrl'=>'http://127.0.0.1:8877/admin-post.php?action=cde_presentation&kind=manifest&lesson=2389']),ENT_QUOTES)."'",$template);
    echo '<!doctype html><html lang="es"><meta name="viewport" content="width=device-width,initial-scale=1"><title>QA presentación · Urantia</title><style>body{margin:0;background:#24152e;font-family:Arial,sans-serif}*{box-sizing:border-box}dialog:not([open]){display:none}</style>';
    echo '<link rel="stylesheet" href="http://127.0.0.1:5177/app/themes/sage/public/build/resources/css/layouts/presentation.css">'.$template;
    echo '<script type="module">import init,{destroyPresentations} from "http://127.0.0.1:5177/app/themes/sage/public/build/resources/js/lessons/presentation.js";window.qaInit=init;window.qaDestroy=destroyPresentations;init();</script></html>';
    exit;
}
http_response_code(404);

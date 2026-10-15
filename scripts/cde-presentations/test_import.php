<?php
// Harness WP-CLI aislado: ejecuta el importador real, sin escribir una base WordPress.
declare(strict_types=1);
$root = dirname(__DIR__, 2);
$source = realpath($argv[1]);
$store = sys_get_temp_dir() . '/cde-import-test-' . bin2hex(random_bytes(5));
mkdir($store);
putenv('CDE_PRESENTATIONS_DIR=' . $store);
define('WP_CONTENT_DIR', $root . '/site/web/app');
$meta = [];
$ids = [2389];
function get_posts($args) { global $ids; return $ids; }
function get_post_meta($id,$key,$single=true) {global $meta;return $meta[$key]??'';}
function update_post_meta($id,$key,$value) {global $meta;$meta[$key]=$value;return true;}
function metadata_exists($type,$id,$key) {global $meta;return array_key_exists($key,$meta);}
function delete_field($key,$id) {global $meta;foreach(array_keys($meta) as $name)if(str_starts_with($name,'lesson_presentation_slides'))unset($meta[$name]);}
function wp_cache_delete(...$args) {}
function wp_json_encode($data) {return json_encode($data);}
class WP_CLI {
    public static $callback;
    public static function add_command($name,$callback) {self::$callback=$callback;}
    public static function success($message) {}
    public static function log($message) {}
    public static function error($message,$exit) {}
    public static function halt($status) {throw new RuntimeException('CLI failed');}
}
require $root.'/site/web/app/mu-plugins/cde-presentations/package.php';
require $root.'/site/web/app/mu-plugins/cde-presentations/service.php';
require $root.'/site/web/app/mu-plugins/cde-presentations/cli.php';
use EspacioSutil\Presentations\Service;
use EspacioSutil\Presentations\Package;
$count=0;
function check($condition,$message) {global $count;if(!$condition)throw new RuntimeException($message);$count++;}
function runImport($path,$expected='none',$apply=true) {($GLOBALS['callback'])([$path],['lesson-key'=>'cde:01:00:0.1','expected-revision'=>$expected]+($apply?['apply'=>true,'activate'=>true]:[]));}
function fails($fn) {try{$fn();return false;}catch(Throwable){return true;}}
$callback=WP_CLI::$callback;
try {
 $rev=Package::read($source,true)['revision'];
 runImport($source,'none',false);check($meta===[],'Dry-run modifica metadata');check(count(scandir($store))===2,'Dry-run crea archivos');
 $ids=[2389,2390];check(fails(fn()=>runImport($source)),'Aceptó clave duplicada');$ids=[2389];
 runImport($source);check(Service::active(2389)===$rev,'No activa');
 runImport($source,$rev);check(count(glob($store.'/2389/*',GLOB_ONLYDIR))===1,'Importación no idempotente');
 check(fails(fn()=>runImport($source,'none')),'No detecta revisión concurrente');check(Service::active(2389)===$rev,'Conflicto altera puntero');
 $lock=fopen($store.'/2389/.import.lock','c');flock($lock,LOCK_EX);check(fails(fn()=>runImport($source,$rev)),'No bloquea importación concurrente');flock($lock,LOCK_UN);fclose($lock);
 // Crear una segunda revisión de prueba sin alterar imágenes ni PDF editorial.
 $v2=$store.'/candidate';mkdir($v2);mkdir($v2.'/slides');
 $data=Package::read($source,true);unset($data['revision']);$data['conversion_profile']='test-revision-2';$data['revision']=hash('sha256',Package::canonical($data));
 file_put_contents($v2.'/manifest.json',Package::canonical($data));
 foreach($data['slides'] as $slide)foreach($slide['variants'] as $v)copy($source.'/'.$v['path'],$v2.'/'.$v['path']);
 runImport($v2,$rev);check(Service::active(2389)===$data['revision'],'No sustituye');
 runImport($source,$data['revision']);check(Service::active(2389)===$rev,'No restaura revisión');
 file_put_contents($v2.'/slides/0001-640.webp','invalid');check(fails(fn()=>runImport($v2,$rev)),'Aceptó paquete roto');check(Service::active(2389)===$rev,'Fallo destruye revisión activa');
 // Edición manual: orden, borrado total y protección frente a importaciones.
 $meta['clave_leccion']='cde:01:00:0.1';
 $meta['lesson_presentation_slides']=2;
 $meta['lesson_presentation_slides_0_image']=$rev.':2';
 $meta['lesson_presentation_slides_1_image']=$rev.':1';
 $ordered=Service::presentation(2389);
 check($ordered['slides'][0]['variants'][0]['path']==='slides/0002-640.webp','No respeta el orden manual');
 check($ordered['slides'][1]['page']===2,'No renumera el orden visible');
 check(Service::active(2389)!==$rev,'No cambia la revisión al editar');
 check(fails(fn()=>runImport($source,Service::revision(2389))),'La importación pisa la edición manual');
 $meta['lesson_presentation_slides']=0;
 check(Service::active(2389)==='' && Service::references(2389)===[],'Borrar todo recupera páginas importadas');
 $meta['lesson_presentation_slides']=1;
 check(fails(fn()=>Service::resolve(2389,'../manifest.json')),'Referencia insegura aceptada');
 $meta['lesson_presentation_slides_0_image']=$rev.':101';
 check(fails(fn()=>Service::presentation(2389)),'Página inexistente aceptada');
 $meta['lesson_presentation_slides_0_image']=$rev.':1';
 $expected=Service::revision(2389);
 ($callback)([$source],['lesson-key'=>'cde:01:00:0.1','expected-revision'=>$expected,'apply'=>true,'activate'=>true,'replace-editor-slides'=>true]);
 check(Service::active(2389)===$rev && !metadata_exists('post',2389,'lesson_presentation_slides'),'Sustitución explícita no restaura el paquete');
 $meta['lesson_presentation_slides']=count(Package::read($source)['slides']);
 foreach(Package::read($source)['slides'] as $i=>$slide)$meta['lesson_presentation_slides_'.$i.'_image']=$rev.':'.$slide['page'];
 check(!Service::isEdited(2389) && Service::revision(2389)===$rev,'Guardar filas sin cambios se considera edición manual');
 runImport($source,$rev);
 check(Service::active(2389)===$rev,'Guardar otra sección impide importar');
 $meta['lesson_presentation_slides']=1;
 $meta[Service::META]=str_repeat('a',64);
 check(Service::active(2389)==='', 'Un paquete ausente rompe la página de la lección');
 echo "$count comprobaciones de importación correctas\n";
} finally {
 $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($store,FilesystemIterator::SKIP_DOTS),RecursiveIteratorIterator::CHILD_FIRST);
 foreach($it as $entry){$entry->isDir()&&!$entry->isLink()?rmdir($entry->getPathname()):unlink($entry->getPathname());}rmdir($store);
}

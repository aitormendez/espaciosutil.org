<?php
class WP_Error { public function __construct(public string $code, public string $message, public array $data=[]) {} }
function is_wp_error($v) { return $v instanceof WP_Error; }
require $argv[1];
use EspacioSutil\Mobile\Account;
$n=0;
function check($ok,$name) { global $n;if(!$ok)throw new Exception($name);$n++; }
check(Account::normalizeAvatar(null)===null,'quitar avatar');
check(is_wp_error(Account::normalizeAvatar('not base64')),'base64 inválido');
check(is_wp_error(Account::normalizeAvatar(base64_encode('<svg onload="alert(1)"></svg>'))),'SVG rechazado');
check(is_wp_error(Account::normalizeAvatar(base64_encode('<?php echo 1;'))),'código rechazado');
check(is_wp_error(Account::normalizeAvatar(str_repeat('a',2800001))),'límite previo a decodificar');
if(function_exists('imagecreatetruecolor')) {
 $im=imagecreatetruecolor(600,400);imagefill($im,0,0,imagecolorallocate($im,80,10,20));ob_start();imagepng($im);$raw=ob_get_clean();unset($im);
 $out=Account::normalizeAvatar(base64_encode($raw));check(is_string($out),'PNG aceptado');$info=getimagesizefromstring(base64_decode($out));check($info[0]===256&&$info[1]===256&&$info['mime']==='image/jpeg','normalizado JPEG cuadrado');
 $jpeg=base64_decode($out);$marker="Exif\0\0SYNTHETIC_METADATA";$tagged=substr($jpeg,0,2)."\xff\xe1".pack('n',strlen($marker)+2).$marker.substr($jpeg,2);
 check(str_contains($tagged,'SYNTHETIC_METADATA'),'fixture con metadatos');$clean=Account::normalizeAvatar(base64_encode($tagged));check(is_string($clean)&&!str_contains(base64_decode($clean),'SYNTHETIC_METADATA'),'metadatos eliminados');
}
echo json_encode(['passed'=>$n,'failed'=>0])."\n";

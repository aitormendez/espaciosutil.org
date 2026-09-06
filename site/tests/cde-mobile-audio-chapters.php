<?php
// Prueba aislada: no requiere WordPress ni modifica datos reales.
$meta=['featured_video_id'=>'video-a','featured_audio_id'=>'audio-a'];
$rows=[['title'=>'Capítulo','timecode'=>'1:00']];
function get_post_meta($id,$key,$single){global $meta;return $meta[$key]??'';}
function get_field($key,$id){global $rows;return $rows;}
function wp_json_encode($value){return json_encode($value);}
require __DIR__.'/../web/app/mu-plugins/cde-mobile/media.php';
use EspacioSutil\Mobile\Media;
function check($name,$value){if(!$value)throw new Exception($name);echo "PASS $name\n";}
check('audio sin verificación no hereda capítulos',!Media::audioChaptersVerified(1));
$meta['_cde_mobile_audio_chapters_verified']=Media::audioChapterFingerprint(1);
check('par y mapa verificados',Media::audioChaptersVerified(1));
$meta['featured_audio_id']='audio-b';
check('sustituir audio invalida',!Media::audioChaptersVerified(1));
$meta['featured_audio_id']='audio-a';$rows[0]['timecode']='2:00';
check('cambiar capítulo invalida',!Media::audioChaptersVerified(1));
$rows[0]['timecode']='1:00';$meta['featured_video_id']='video-b';
check('sustituir vídeo invalida',!Media::audioChaptersVerified(1));

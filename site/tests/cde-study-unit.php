<?php
function wp_strip_all_tags($s){return strip_tags($s);} function home_url($path=''){return 'https://stage.espaciosutil.org'.$path;} function wp_parse_url($url,$component=-1){return parse_url($url,$component);}
require __DIR__.'/../web/app/mu-plugins/cde-mobile/study.php';
require __DIR__.'/../web/app/mu-plugins/cde-mobile/quiz.php';
function check($condition,$name){if(!$condition)throw new RuntimeException($name);echo "OK $name\n";}
$p=new \EspacioSutil\Mobile\Study();
$b=$p->document('<h2 id="uno">Síntesis</h2><p>Texto <strong>fuerte</strong> y <em>suave</em> <a href="javascript:alert(1)">seguro</a>.</p><ul><li>Uno<ul><li>Dos</li></ul></li></ul><p><img src="/app/uploads/test.png" alt="Ejemplo"/></p><script>neverExecute()</script>');
check($b[0]['anchor']==='uno' && $b[0]['content'][0]['text']==='Síntesis','título y ancla UTF-8');
check($b[1]['content'][1]['bold'] && $b[1]['content'][3]['italic'],'énfasis conservado');
check(!str_contains(json_encode($b),'javascript:')&&!str_contains(json_encode($b),'neverExecute'),'contenido activo eliminado');
check($b[2]['type']==='list' && $b[2]['items'][0][1]['type']==='list','listas anidadas');
check($b[3]['type']==='image' && $b[3]['url']==='https://stage.espaciosutil.org/app/uploads/test.png','imagen dentro de párrafo');
$q=\EspacioSutil\Mobile\Quiz::normalize([['question'=>'Inválida','answers'=>[['answer_text'=>'A']]],['question'=>'Válida','answers'=>[['answer_text'=>'A','is_correct'=>1],['answer_text'=>'B','is_correct'=>1],['answer_text'=>'']]]]);
check(count($q)===1 && $q[0]['id']==='q-1' && $q[0]['multiple'],'normalización compartida y selección múltiple');

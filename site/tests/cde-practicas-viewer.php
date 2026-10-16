<?php
/** Read-only regression: wp eval-file tests/cde-practicas-viewer.php PRACTICE LESSON MEMBER */
use EspacioSutil\Practicas\{Guide, Audio, Catalog};
if (!defined('WP_CLI') || !WP_CLI || wp_get_environment_type() !== 'development') {
    throw new RuntimeException('Ejecutar sólo en WordPress de desarrollo.');
}
[$practiceId, $lessonId, $memberId] = array_map('intval', $args);
$practice = get_post($practiceId);
if (!$practice || $practice->post_type !== 'practica' || !get_user_by('id', $memberId)) {
    throw new RuntimeException('Se requieren una práctica, su lección y un miembro de prueba.');
}
$checks = 0;
$check = static function ($condition, $message) use (&$checks) {
    if (!$condition) { throw new RuntimeException($message); }
    $checks++;
};
$g = Guide::fromHtml('<h2>1. Padre</h2><h3>1.1. Hijo</h3><h4>1.1.1. Nieto</h4><p>Una idea. Su desarrollo.</p><h3>1.2. Otro hijo</h3><p>Otra idea.</p>');
$check(count($g['screens']) === 6, 'Cada encabezado tiene pantalla, incluidos los padres.');
$check($g['screens'][2]['path'] === ['1. Padre', '1.1. Hijo', '1.1.1. Nieto'], 'Ruta de tres niveles.');
$check($g['screens'][4]['path'] === ['1. Padre', '1.2. Otro hijo'], 'Eliminar descendientes al cambiar de epígrafe.');
$check(count($g['sections']) === 1 && $g['sections'][0]['screen'] === 0, 'Índice apunta al inicio del bloque.');
$check($g['screens'][3]['speech'] === 'Una idea. Su desarrollo.', 'No dividir párrafos en frases.');
$check($g['screens'][2]['speech'] === 'Uno punto uno punto uno. Nieto.', 'Narrar sólo el epígrafe nuevo con punto final.');
$check($g['screens'][0]['path'] === ['1. Padre'] && $g['screens'][0]['speech'] === 'Uno. Padre.', 'Portada de bloque sin punto visual y con punto hablado.');
foreach ([
    '1.1. Hijo' => 'Uno punto uno. Hijo.',
    '1.2. Hijo' => 'Uno punto dos. Hijo.',
    '2.2. Hijo' => 'Dos punto dos. Hijo.',
    '2.2 Hijo' => 'Dos punto dos. Hijo.',
    '12.21.32. Hijo.' => 'Doce punto veintiuno punto treinta y dos. Hijo.',
    '100.101.999. Hijo' => 'Cien punto ciento uno punto novecientos noventa y nueve. Hijo.',
    '01.2. Hijo' => 'Cero uno punto dos. Hijo.',
    '1000. Hijo' => 'Uno cero cero cero. Hijo.',
    'Sin numeración' => 'Sin numeración.',
    'En el punto 2.2' => 'En el punto 2.2.',
    '2026 enseñanzas' => '2026 enseñanzas.',
] as $title => $speech) {
    $heading = Guide::fromHtml('<h2>'.$title.'</h2>')['screens'][0];
    $check($heading['speech'] === $speech && $heading['path'] === [$title], 'Numeración hablada sin alterar el título: '.$title);
}
$check(Guide::fromHtml('<p>1.2. Un párrafo.</p>')['screens'][0]['speech'] === '1.2. Un párrafo.', 'No reinterpretar numeración en párrafos.');
$reading = Guide::readingHtml('<h2>1. Padre</h2><p>Contexto.</p><h3>1.1. Hijo</h3>', 'instance-a', $g['sections']);
$check(str_contains($reading, 'id="instance-a-section-0"') && str_contains($reading, 'data-practice-heading="0"'), 'Índice de lectura apunta a encabezados reales de la instancia.');
$other = Guide::readingHtml('<h2>1. Padre</h2>', 'instance-b', $g['sections']);
$check(!str_contains($other, 'instance-a') && str_contains($other, 'instance-b-section-0'), 'Dos visores no comparten IDs.');
$standalone = Guide::fromHtml('<h2>Padre</h2><p>Contexto propio.</p><h3>Hijo</h3><p>Desarrollo.</p><h2>Final</h2>');
$check(count($standalone['screens']) === 5 && count($standalone['sections']) === 2, 'Conservar padres con texto propio y cabecera final.');
foreach (['<ul><li>Paso</li></ul>', '<table><tr><td>Dato</td></tr></table>', '<p>Antes</p><img src="x">', 'Texto sin párrafo', '<h1>Título no adaptado</h1>', '<p>Instrucción con <img src="x"> imagen.</p>', '<p><audio src="x"></audio></p>'] as $html) {
    $check(Guide::fromHtml($html)['screens'] === [], 'Contenido no adaptado debe conservar lectura completa, sin omisiones.');
}
$safe = Guide::fromHtml('<p onclick="alert(1)">Sentir <strong>ahora</strong>. <a href="javascript:alert(1)">Enlace</a></p>');
$check(!str_contains($safe['screens'][0]['html'], 'onclick') && !str_contains($safe['screens'][0]['html'], 'javascript:'), 'Eliminar atributos y URLs ejecutables.');
$check(str_contains($safe['screens'][0]['html'], '<strong>ahora</strong>'), 'Conservar énfasis editorial.');
$revision = Audio::revision($g);
$check($revision === Audio::revision(Guide::fromHtml('<h2>1. Padre</h2><h3>1.1. Hijo</h3><h4>1.1.1. Nieto</h4><p>Una idea. Su desarrollo.</p><h3>1.2. Otro hijo</h3><p>Otra idea.</p>')), 'Huella determinista.');
$g['screens'][1]['speech'] .= ' Cambio.';
$check($revision !== Audio::revision($g), 'Un cambio editorial invalida audio anterior.');
wp_set_current_user(0);
$check(!Catalog::allowed() && Guide::forPost($practice)['screens'] === [], 'Anónimo no recibe guía.');
$check(Catalog::forLesson($lessonId) === [] && Catalog::lessonsFor($practiceId) === [], 'Anónimo no recibe relaciones.');
wp_set_current_user($memberId);
$check(Catalog::allowed(), 'Cualquier membresía activa da acceso.');
$actual = Guide::forPost($practice);
$check(count($actual['screens']) === 49 && count($actual['sections']) === 6, 'EP-0.1: 49 pantallas y seis bloques.');
$check(count(array_filter(array_column($actual['screens'], 'audio'))) === 49, 'Audio preparado para las 49 pantallas.');
$check(in_array($practiceId, array_column(Catalog::forLesson($lessonId), 'ID'), true), 'Relación desde lección.');
$check(in_array($lessonId, array_column(Catalog::lessonsFor($practiceId), 'ID'), true), 'Consulta inversa de contexto.');
$check(!str_contains(Audio::directory(), '/web/'), 'Audios fuera del directorio público.');
$originalQuery = $GLOBALS['wp_the_query'];
$originalGet = $_GET;
try {
    $query = new WP_Query(); $GLOBALS['wp_the_query'] = $query;
    $_GET = ['practice_search'=>'suelo', 'practice_series'=>0];
    $query->query(['post_type'=>'practica']);
    $check(in_array($practiceId, array_column($query->posts, 'ID'), true), 'Búsqueda encuentra la práctica.');
    $_GET = ['practice_search'=>'texto_inexistente_qa_765432'];
    $query->query(['post_type'=>'practica']);
    $check($query->post_count === 0, 'Búsqueda sin coincidencias.');
    $terms = wp_get_object_terms($lessonId, 'serie_cde');
    $check(!is_wp_error($terms) && count($terms) > 0, 'Lección de prueba tiene serie.');
    $_GET = ['practice_series'=>$terms[0]->term_id]; $query->query(['post_type'=>'practica']);
    $check(in_array($practiceId, array_column($query->posts, 'ID'), true), 'Filtro hereda serie de la lección.');
    $_GET = ['practice_series'=>999999]; $query->query(['post_type'=>'practica']);
    $check($query->post_count === 0, 'Serie sin relaciones devuelve vacío.');
    $_GET = ['practice_search'=>['entrada'], 'practice_series'=>['entrada']];
    $check(Catalog::search() === '' && Catalog::selectedSeries() === 0, 'Parámetros de tipo inesperado son ignorados.');
} finally { $GLOBALS['wp_the_query'] = $originalQuery; $_GET = $originalGet; }
echo "Prácticas: {$checks} comprobaciones correctas, sin escrituras en la base de datos.\n";

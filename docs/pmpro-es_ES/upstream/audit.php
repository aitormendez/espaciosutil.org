<?php
// Uso: php audit.php /ruta/site/web/wp /ruta/pmpro-3.8.6-extracted.pot
// No arranca WordPress ni PMPro. No requiere BD ni red.
require $argv[1] . '/wp-includes/pomo/po.php';
require $argv[1] . '/wp-includes/pomo/mo.php';
$pot = new PO(); $po = new PO(); $mo = new MO();
if (!$pot->import_from_file($argv[2]) || !$po->import_from_file(__DIR__.'/official-es_ES.po') || !$mo->import_from_file(__DIR__.'/official-es_ES.mo')) { throw new RuntimeException('No se pudo importar un catálogo'); }
$groups = [
 'paginas' => '~^pages/~',
 'preheaders' => '~^preheaders/~',
 'flujo_comun' => '~^includes/(functions|localization|checkout|email|login)\.php~',
 'campos' => '~^classes/class-pmpro-field(-group)?\.php~',
 'stripe' => '~^classes/gateways/class\.pmprogateway_stripe\.php~',
 'emails' => '~^classes/(email-templates/|class\.pmproemail\.php)~',
];
$stats=['pot_entries'=>count($pot->entries),'translated_exact'=>0,'groups'=>[]];
$rows=[];
foreach($pot->entries as $key=>$entry) {
 $translation=$po->entries[$key]??null;
 $ok=$translation && !in_array('fuzzy',$translation->flags,true) && !empty($translation->translations[0]);
 if($entry->is_plural) $ok=$ok && !empty($translation->translations[1]);
 $stats['translated_exact']+=(int)$ok;
 foreach($groups as $group=>$pattern) {
  if(!preg_grep($pattern,$entry->references)) continue;
  $stats['groups'][$group]['total']=($stats['groups'][$group]['total']??0)+1;
  $stats['groups'][$group]['translated']=($stats['groups'][$group]['translated']??0)+(int)$ok;
  $rows[]=[$group,$ok?'translated':'missing',$entry->context??'',$entry->singular,implode(' | ',$translation->translations??[]),implode(' ',$entry->references)];
 }
}
$out=fopen(__DIR__.'/coverage.csv','w');
fputcsv($out,['group','status','context','original','translation','references'],',','"','');
foreach($rows as $row) fputcsv($out,$row,',','"','');
fclose($out);
$examples=[];
foreach(['Membership Account','Membership Level','Account Information','Username','Password','Email Address','Submit and Check Out','You have already used the discount code provided.','This payment does not match the amount due for this checkout.','Your membership confirmation for {{ sitename }}'] as $text) $examples[$text]=$mo->translate($text);
$stats['mo_examples']=$examples;
file_put_contents(__DIR__.'/summary.json',json_encode($stats,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n");
echo json_encode($stats,JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)."\n";

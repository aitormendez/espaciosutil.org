<?php
/** Regression: PMPro email rendering must have every artwork referenced by Vite. */
declare(strict_types=1);
$theme=__DIR__.'/../web/app/themes/sage';
$setup=file_get_contents($theme.'/app/setup.php');
preg_match_all("/Vite::asset\\('([^']*images\/email\/[^']+)'\\)/",$setup,$matches);
if (!$matches[1]) { throw new RuntimeException('No email artwork references found'); }
foreach (array_unique($matches[1]) as $asset) {
    $path=$theme.'/'.$asset;
    if (!is_file($path) || file_get_contents($path,false,null,0,8)!=="\x89PNG\r\n\x1a\n") {
        throw new RuntimeException('Missing or invalid email artwork: '.$asset);
    }
}
echo 'PASS: '.count(array_unique($matches[1]))." email images available for Vite build\n";

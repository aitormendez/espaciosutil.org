<?php

declare(strict_types=1);

$hookPath = __DIR__ . '/../../trellis/deploy-hooks/build-before.yml';
$hook = file_get_contents($hookPath);

if (! is_string($hook)) {
    fwrite(STDERR, "Unable to read the Trellis build hook.\n");
    exit(1);
}

$failures = [];

if (str_contains($hook, 'Debug auth.json') || preg_match('/command:\s*cat\s+.*auth\.json/', $hook) === 1) {
    $failures[] = 'The deploy hook must not print auth.json.';
}

if (! str_contains($hook, 'no_log: true')) {
    $failures[] = 'The auth.json template task must suppress sensitive output.';
}

if (preg_match('/mode:\s*["\']?0600["\']?/', $hook) !== 1) {
    $failures[] = 'The temporary auth.json file must use mode 0600.';
}

if (preg_match('/always:.*?auth\.json.*?state:\s*absent/s', $hook) !== 1) {
    $failures[] = 'The temporary auth.json file must be removed in an always block.';
}

if ($failures !== []) {
    fwrite(STDERR, implode("\n", $failures) . "\n");
    exit(1);
}

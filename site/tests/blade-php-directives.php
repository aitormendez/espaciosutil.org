<?php

declare(strict_types=1);

$viewsDirectory = __DIR__ . '/../web/app/themes/sage/resources/views';
$invalidDirectives = [];
$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($viewsDirectory));

foreach ($iterator as $file) {
    if (! $file->isFile() || ! str_ends_with($file->getFilename(), '.blade.php')) {
        continue;
    }

    $lines = file($file->getPathname());

    foreach ($lines as $lineNumber => $line) {
        if (str_contains($line, '@php(')) {
            $relativePath = substr($file->getPathname(), strlen($viewsDirectory) + 1);
            $invalidDirectives[] = $relativePath . ':' . ($lineNumber + 1);
        }
    }
}

if ($invalidDirectives !== []) {
    fwrite(STDERR, "Blade views must use explicit @php ... @endphp blocks:\n");
    fwrite(STDERR, implode("\n", $invalidDirectives) . "\n");
    exit(1);
}

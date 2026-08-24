<?php

declare(strict_types=1);

function add_action(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): void {}

function home_url(string $path = ''): string
{
    return 'https://espaciosutil.org' . $path;
}

$plugin = __DIR__ . '/../web/app/mu-plugins/espaciosutil-legacy-redirects.php';

if (! is_file($plugin)) {
    fwrite(STDERR, 'The legacy redirects plugin does not exist.' . PHP_EOL);
    exit(1);
}

require $plugin;

function assert_legacy_redirect(string $expected, string $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message . PHP_EOL);
        fwrite(STDERR, "Expected: {$expected}" . PHP_EOL);
        fwrite(STDERR, "Actual:   {$actual}" . PHP_EOL);
        exit(1);
    }
}

$manualDestination = 'https://espaciosutil.org/series/un-manual-para-la-ascension/';
$satelliteDestination = 'https://espaciosutil.org/orientacion-terapeutica/';

assert_legacy_redirect($manualDestination, espaciosutil_legacy_redirect_destination('/areas/un-manual-para-la-ascension/'), 'Redirects the legacy manual area.');
assert_legacy_redirect($manualDestination, espaciosutil_legacy_redirect_destination('/areas/un-manual-para-la-ascension'), 'Redirects the legacy manual area without trailing slash.');
assert_legacy_redirect($satelliteDestination, espaciosutil_legacy_redirect_destination('/noticias/satelite-de-terapia/?source=google'), 'Redirects the legacy satellite taxonomy URL and ignores its query string.');
assert_legacy_redirect('', espaciosutil_legacy_redirect_destination('/series/de-la-ascension/'), 'Leaves unrelated intentional 404 URLs untouched.');

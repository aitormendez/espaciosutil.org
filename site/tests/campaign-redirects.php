<?php

declare(strict_types=1);

function add_action(string $hook_name, callable $callback, int $priority = 10, int $accepted_args = 1): void {}

function home_url(string $path = ''): string
{
    return 'https://espaciosutil.org'.$path;
}

require __DIR__.'/../web/app/mu-plugins/espaciosutil-campaign-redirects.php';

function assert_same(string $expected, string $actual, string $message): void
{
    if ($expected !== $actual) {
        fwrite(STDERR, $message.PHP_EOL);
        fwrite(STDERR, "Expected: {$expected}".PHP_EOL);
        fwrite(STDERR, "Actual:   {$actual}".PHP_EOL);
        exit(1);
    }
}

$destination = 'https://espaciosutil.org/curso-de-desarrollo-espiritual/?utm_source=tiktok&utm_medium=organic_social&utm_campaign=cde_launch_wave1&utm_content=profile_link';
$cdeDestination = 'https://espaciosutil.org/curso-de-desarrollo-espiritual/';

assert_same($destination, espaciosutil_campaign_redirect_destination('/cde-tiktok'), 'Redirects the short TikTok path.');
assert_same($destination, espaciosutil_campaign_redirect_destination('/cde-tiktok/'), 'Redirects the short TikTok path with trailing slash.');
assert_same($destination, espaciosutil_campaign_redirect_destination('/cde-tiktok?x=1'), 'Ignores query strings on the short TikTok path.');
assert_same($cdeDestination, espaciosutil_campaign_redirect_destination('/cde'), 'Redirects the short CDE path.');
assert_same($cdeDestination, espaciosutil_campaign_redirect_destination('/cde/'), 'Redirects the short CDE path with trailing slash.');
assert_same($cdeDestination, espaciosutil_campaign_redirect_destination('/cde?utm_source=instagram'), 'Ignores query strings on the short CDE path.');
assert_same('', espaciosutil_campaign_redirect_destination('/curso-de-desarrollo-espiritual/'), 'Does not redirect the destination hub.');

<?php
add_filter('pre_wp_mail','__return_false');
add_filter('pre_http_request',static fn()=>new WP_Error('qa_network_disabled'),PHP_INT_MAX);
// SQLite compatibility for PMPro date queries in this UTC-only disposable fixture.
$GLOBALS['@pdo']->createFunction('CONVERT_TZ', static function($date,$from,$to) {
    if (!$date || str_starts_with($date,'0000')) return $date;
    return (new DateTimeImmutable($date,new DateTimeZone($from ?: 'UTC')))->setTimezone(new DateTimeZone($to ?: 'UTC'))->format('Y-m-d H:i:s');
}, 3);
$GLOBALS['@pdo']->createFunction('STRCMP', static fn($a,$b)=>strcmp($a,$b), 2);
// Qualify the PMPro GROUP BY alias, which SQLite otherwise resolves ambiguously.
add_filter('query', static function($query) {
    if (str_contains($query, 'pmpro_memberships_users AS mu')) {
        return str_replace('GROUP BY ID','GROUP BY l.id',$query);
    }
    return $query;
});

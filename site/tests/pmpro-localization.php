<?php

/**
 * Offline only. Real WP hooks/l10n/controller + PMPro locale function.
 * No WordPress bootstrap, DB, HTTP, users, sends, cron or Stripe calls.
 * php site/tests/pmpro-localization.php /path/to/wp /path/to/pmpro /scratch
 */
$wp = $argv[1] ?? __DIR__ . '/../web/wp';
$pmpro = $argv[2] ?? __DIR__ . '/../web/app/plugins/paid-memberships-pro';
$scratch = $argv[3] ?? getenv('PAPERCLIP_RUN_SCRATCH_DIR');
if (!$scratch || !is_dir($scratch)) {
    throw new RuntimeException('Indica un scratch existente.');
}
$root = dirname(__DIR__, 2);
$dir = $root . '/docs/pmpro-es_ES';
function check($ok, string $message): void
{
    if (!$ok) {
        throw new RuntimeException($message);
    }
}
require $wp . '/wp-includes/version.php';
check($wp_version === '7.1.2', 'La prueba requiere los fuentes de WordPress 7.1.2.');
define('ABSPATH', $wp . '/');
define('WPINC', 'wp-includes');
define('WP_LANG_DIR', $scratch . '/pmpro-l10n-fixture');
define('WP_PLUGIN_DIR', dirname($pmpro));
if (!is_dir(WP_LANG_DIR . '/plugins')) {
    mkdir(WP_LANG_DIR . '/plugins', 0700, true);
}
require $wp . '/wp-includes/plugin.php';
require $wp . '/wp-includes/pomo/po.php';
require $wp . '/wp-includes/pomo/mo.php';
require $wp . '/wp-includes/l10n.php';
require $wp . '/wp-includes/class-wp-textdomain-registry.php';
foreach (['controller','file','file-mo','file-php','plural-form','translations'] as $class) {
    // WP_Translations has a different filename from the other classes.
    $file = $class === 'translations' ? 'class-wp-translations.php' : 'class-wp-translation-' . $class . '.php';
    if (file_exists($wp . '/wp-includes/l10n/' . $file)) {
        require $wp . '/wp-includes/l10n/' . $file;
    }
}
$wp_textdomain_registry = new WP_Textdomain_Registry();
$locale = 'es_ES';
add_filter('pre_determine_locale', fn() => $GLOBALS['locale']);
// Minimal environment doubles, not a functional WP/PHP-FPM test.
function esc_attr($s)
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
function esc_html($s)
{
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}
function wp_kses_post($s)
{
    return $s;
} // Sanitizer is outside the translation assertion.
function get_option($key, $default = false)
{
    throw new RuntimeException('BD no permitida: ' . $key);
}
function wp_mail(...$args)
{
    throw new RuntimeException('Envío no permitido');
}
require $root . '/site/web/app/mu-plugins/espaciosutil-pmpro-localization.php';
$domain = 'paid-memberships-pro';
$fixture = WP_LANG_DIR . '/plugins/paid-memberships-pro-es_ES.mo';
check(apply_filters('load_translation_file', $fixture, $domain, 'es_ES') === $fixture, 'Plugin inactivo debe quedar intacto');
check(str_contains(file_get_contents($pmpro . '/paid-memberships-pro.php'), "define( 'PMPRO_VERSION', '3.8.6' )"), 'Fuentes PMPro incorrectos');
define('PMPRO_VERSION', '3.8.6');
$official = new MO();
$official->import_from_file($dir . '/upstream/official-es_ES.mo');
$official->export_to_file($fixture);
// A competing PHP catalogue must never be executed for this domain+locale.
$phpFixture = substr($fixture, 0, -3) . '.l10n.php';
file_put_contents($phpFixture, '<?php throw new RuntimeException("Catálogo PHP ajeno seleccionado");');
require $pmpro . '/includes/localization.php';
check(has_action('init', 'pmpro_load_textdomain') === 1, 'Prioridad init PMPro');
do_action('init');
check(__('You have already used the discount code provided.', $domain) === 'Ya has utilizado el código de descuento indicado.', 'Precedencia sobre global/local/PHP');
check(_n('Yes, cancel this membership', 'Yes, cancel these memberships', 1, $domain) === 'Sí, cancelar esta membresía', 'Cancelación singular');
check(_n('Yes, cancel this membership', 'Yes, cancel these memberships', 2, $domain) === 'Sí, cancelar estas membresías', 'Cancelación plural');
check(_x('&#8212;', 'A dash is shown when there is no expiration date.', $domain) === '&#8212;', 'Contexto de caducidad');
check(sprintf(__('Page %s', $domain), '2') === 'Página 2', 'Formato paginación');
$expected = 'Confirmación de tu membresía en {{ sitename }}';
check(__('Your membership confirmation for {{ sitename }}', $domain) === $expected, 'Asunto Liquid');
// PMPro unloads on init; a second load must still pick the package.
pmpro_load_textdomain();
check(__('Your membership confirmation for {{ sitename }}', $domain) === $expected, 'Recarga de PMPro');
foreach ([['another-domain','es_ES'],[$domain,'fr_FR'],[$domain,'es_MX'],[$domain,'en_US']] as [$d,$l]) {
    check(apply_filters('load_translation_file', $fixture, $d, $l) === $fixture, 'Aislamiento dominio/locale');
}
$locale = 'fr_FR';
check(!load_textdomain($domain, $scratch . '/absent-fr_FR.mo', 'fr_FR'), 'Sin español forzado en francés');
check(__('Membership Account', $domain) === 'Membership Account', 'No filtrar español hacia el locale francés');
$locale = 'es_ES';
load_textdomain($domain, $fixture, 'es_ES');

$pot = new PO();
$pot->import_from_file($dir . '/upstream/pmpro-3.8.6-extracted.pot');
$mo = new MO();
check($mo->import_from_file($root . '/site/web/app/mu-plugins/espaciosutil-pmpro-l10n/paid-memberships-pro-es_ES.mo'), 'MO legible');
function tokens(string $s): array
{
    preg_match_all('/\{\{.*?\}\}|\{%.*?%\}|!![A-Za-z0-9_]+!!/s', $s, $m);
    sort($m[0]);
    return $m[0];
}
function formats(string $s): array
{
    preg_match_all('/%(?:(\d+)\$)?[-+0\x20]*(?:\d+)?(?:\.\d+)?[bcdeEfFgGosuxX%]/', $s, $m);
    $result = [];
    $i = 0;
    foreach ($m[0] as $f) {
        if ($f === '%%') {
            $result[] = '%%';
            continue;
        }
        preg_match('/^%(\d+)\$/', $f, $slot);
        $result[] = ($slot[1] ?? ++$i) . ':' . substr($f, -1);
    }
    sort($result);
    return $result;
}
$inventory = fopen($dir . '/inventory.csv', 'r');
$head = fgetcsv($inventory, 0, ',', '"', '');
$count = 0;
while ($values = fgetcsv($inventory, 0, ',', '"', '')) {
    $row = array_combine($head, $values);
    if ($row['scope'] !== 'required') {
        continue;
    }
    $key = ($row['context'] !== '' ? $row['context'] . "\4" : '') . $row['original'];
    $entry = $pot->entries[$key];
    $translation = $mo->entries[$key] ?? null;
    check($translation !== null, 'Clave ausente: ' . $key);
    foreach ($translation->translations as $i => $text) {
        $original = $i && $entry->is_plural ? $entry->plural : $entry->singular;
        check($text !== '', 'Traducción vacía: ' . $key);
        check(tokens($original) === tokens($text), 'Tokens alterados: ' . $key);
        check(formats($original) === formats($text), 'printf alterado: ' . $key);
        preg_match_all('~</?([a-z][a-z0-9]*)\b[^>]*>~i', $original, $a);
        preg_match_all('~</?([a-z][a-z0-9]*)\b[^>]*>~i', $text, $b);
        check($a[1] === $b[1], 'Estructura HTML alterada: ' . $key);
        if ($row['source'] === 'supplement') {
            check($a[0] === $b[0], 'HTML del suplemento alterado: ' . $key);
        }
    }
    check(!$entry->is_plural || count($translation->translations) === 2, 'Plural incompleto: ' . $key);
    ++$count;
}
fclose($inventory);
foreach (json_decode(file_get_contents($dir . '/SHA256.json'), true) as $path => $hash) {
    check(hash_file('sha256', $dir . '/' . $path) === $hash, 'Hash: ' . $path);
}
// Real static PMPro defaults (no constructor, send, DB or email body priority simulation).
require $pmpro . '/classes/email-templates/class-pmpro-email-template.php';
$slugs = ['checkout-paid','checkout-paid-admin','checkout-free','checkout-free-admin','invoice','membership-recurring','billing','billing-admin','billing-failure','billing-failure-admin','cancel','cancel-admin','cancel-on-next-payment-date','cancel-on-next-payment-date-admin','payment-action','payment-action-admin'];
$samples = [];
foreach ($slugs as $slug) {
    require $pmpro . '/classes/email-templates/class-pmpro-email-template-' . $slug . '.php';
    $class = 'PMPro_Email_Template_' . str_replace('-', '_', ucwords($slug, '-'));
    $subject = $class::get_default_subject();
    $body = $class::get_default_body();
    check(!str_contains($body, 'Your membership') && !str_contains($body, 'Log in to'), 'Cuerpo inglés: ' . $slug);
    $samples[$slug] = ['subject' => $subject, 'body' => $body];
}
file_put_contents($scratch . '/pmpro-email-defaults.json', json_encode($samples, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
unlink($fixture);
unlink($phpFixture);
echo "PASS: $count claves críticas; tokens, printf, HTML, plurales, contexto, hashes.\n";
echo "PASS: WP $wp_version hooks/l10n + init real PMPro 3.8.6; MO global/local/PHP y recarga; aislamiento de dominio/locale.\n";
echo "PASS: 16 asuntos/cuerpos por métodos upstream. Sin BD, bootstrap WP, render Liquid completo, FPM, envío ni transacción.\n";
echo sprintf(__('Page %s', $domain), '2') . "\n" . __('You have already used the discount code provided.', $domain) . "\n";

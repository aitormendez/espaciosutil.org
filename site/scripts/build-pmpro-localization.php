<?php
/** Offline: php site/scripts/build-pmpro-localization.php /path/to/wordpress */
require ($argv[1] ?? __DIR__ . '/../web/wp') . '/wp-includes/pomo/po.php';
require ($argv[1] ?? __DIR__ . '/../web/wp') . '/wp-includes/pomo/mo.php';
$root = dirname(__DIR__, 2);
$dir = $root . '/docs/pmpro-es_ES';
foreach (json_decode(file_get_contents($dir . '/upstream/SHA256.json'), true) as $path => $hash) {
    if (hash_file('sha256', $dir . '/upstream/' . $path) !== $hash) {
        throw new RuntimeException('Snapshot upstream alterado: ' . $path);
    }
}
$pot = new PO(); $official = new PO(); $supplement = new PO();
foreach ([[$pot, '/upstream/pmpro-3.8.6-extracted.pot'], [$official, '/upstream/official-es_ES.po'], [$supplement, '/supplement-es_ES.po']] as [$catalog, $file]) {
    if (!$catalog->import_from_file($dir . $file)) {
        throw new RuntimeException('No se puede importar ' . $file);
    }
}
$merged = new MO();
$merged->set_headers($supplement->headers);
foreach ([$official, $supplement] as $source) {
    foreach ($source->entries as $key => $entry) {
        if (in_array('fuzzy', $entry->flags, true) || empty($entry->translations[0])) continue;
        if ($source === $supplement && !isset($pot->entries[$key])) throw new RuntimeException('Clave ajena a 3.8.6: ' . $key);
        $merged->entries[$key] = clone $entry;
    }
}

/** Delimitación por referencias del POT fijado, no por porcentaje global. */
function scope_reason(Translation_Entry $entry): array
{
    $s = $entry->singular;
    foreach ($entry->references as $ref) {
        [$file, $line] = array_pad(explode(':', $ref), 2, 0); $line = (int) $line;
        if (str_starts_with($file, 'pages/') || str_starts_with($file, 'preheaders/') || $file === 'shortcodes/pmpro_account.php') {
            return ['required', 'Interfaz de cuenta/facturación/cancelación/checkout y preenvío; incluye accesibilidad y formas plurales.'];
        }
        if ($file === 'includes/scripts.php' && $s !== 'Plugin updated successfully.') return ['required', 'Etiquetas JS de contraseña/accesibilidad.'];
        if (in_array($file, ['includes/localization.php', 'includes/login.php'], true)) return ['required', 'Periodicidad, acceso y recuperación de cuenta.'];
        if ($file === 'includes/functions.php' && (($line < 1200) || ($line >= 1638 && $line <= 3500) || $line >= 5200)) return ['required', 'Precios/periodos, códigos, paginación, acceso, archivos y resultado de pedido.'];
        if ($file === 'includes/email.php' && $line >= 373 && $line <= 389) return ['required', 'Texto dinámico de membresía en emails.'];
        if (str_starts_with($file, 'classes/class-pmpro-field') && ($line > 400 || str_contains($file, 'field-group'))) return ['required', 'Campos de formulario, validación y accesibilidad; incluye archivos como respaldo.'];
        if ($file === 'classes/gateways/class.pmprogateway_stripe.php' && (($line >= 1243 && $line <= 1430) || ($line >= 2030 && $line <= 2699) || $line === 2810 || ($line >= 3300 && $line <= 4300) || $line >= 4719)) return ['required', 'Stripe: checkout, preenvío, portal, autenticación, errores de pago y confirmación.'];
        if (preg_match('~classes/email-templates/class-pmpro-email-template-(checkout-(paid|free)(-admin)?|invoice|membership-recurring|billing(-admin|-failure|-failure-admin)?|cancel(-admin|-on-next-payment-date|-on-next-payment-date-admin)?|payment-action(-admin)?)\.php$~', $file)
            && (str_contains($s, '{{') || str_starts_with($s, '<p>'))) return ['required', 'Asunto/cuerpo de respaldo de alta, factura, renovación, fallo, SCA, facturación o cancelación; BD/tema mantienen prioridad.'];
    }
    if (str_contains(implode(' ', $entry->references), 'classes/email-templates/')) return ['excluded', 'Metadatos del editor o email fuera del alcance (cambios admin, reembolso, caducidad legacy o cheque); no es cuerpo/asunto de los flujos seleccionados.'];
    if (str_contains(implode(' ', $entry->references), 'class.pmprogateway_stripe')) return ['excluded', 'Configuración/conexión/webhooks del administrador o diagnóstico interno de suscripción; no checkout de cliente.'];
    return ['excluded', 'Administración, diagnóstico/deprecación interna o superficie fuera de los flujos contratados.'];
}
$coverage = [];
$handle = fopen($dir . '/upstream/coverage.csv', 'r'); fgetcsv($handle, 0, ',', '"', '');
while ($row = fgetcsv($handle, 0, ',', '"', '')) $coverage[($row[2] !== '' ? $row[2] . "\4" : '') . $row[3]] = true;
fclose($handle);
$out = fopen($dir . '/inventory.csv', 'w');
fputcsv($out, ['scope','reason','source','context','original','plural','translations','references'], ',', '"', '');
$stats = ['required' => 0, 'excluded' => 0, 'supplement' => count($supplement->entries)];
foreach ($pot->entries as $key => $entry) {
    [$scope, $reason] = scope_reason($entry);
    if ($scope !== 'required' && !isset($coverage[$key])) continue;
    $translation = $merged->entries[$key] ?? null;
    if ($scope === 'required') {
        if (!$translation || empty($translation->translations[0]) || ($entry->is_plural && empty($translation->translations[1]))) {
            // Neutral glyphs are explicitly accounted for, never counted as Spanish text.
            if (in_array($entry->singular, ['-', '&#8212;'], true)) {
                $translation = clone $entry; $translation->translations = [$entry->singular]; $merged->entries[$key] = $translation;
                $reason .= ' Glifo neutro, conservado literalmente.';
            } else throw new RuntimeException('Falta traducción crítica: ' . $key);
        }
    }
    ++$stats[$scope];
    fputcsv($out, [$scope, $reason, isset($supplement->entries[$key]) ? 'supplement' : ($translation ? 'official/neutral' : 'fallback'), $entry->context ?? '', $entry->singular, $entry->plural ?? '', implode(' | ', $translation->translations ?? []), implode(' ', $entry->references)], ',', '"', '');
}
fclose($out);
$target = $root . '/site/web/app/mu-plugins/espaciosutil-pmpro-l10n/paid-memberships-pro-es_ES.mo';
if (!$merged->export_to_file($target)) throw new RuntimeException('No se puede escribir MO');
$hashes = [];
foreach (['upstream/official-es_ES.po','upstream/official-es_ES.mo','upstream/pmpro-3.8.6-extracted.pot','upstream/coverage.csv','upstream/LICENSE-PMPro.txt','supplement-es_ES.po','inventory.csv'] as $path) $hashes[$path] = hash_file('sha256', $dir . '/' . $path);
$hashes['../../site/web/app/mu-plugins/espaciosutil-pmpro-l10n/paid-memberships-pro-es_ES.mo'] = hash_file('sha256', $target);
file_put_contents($dir . '/SHA256.json', json_encode($hashes, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES) . "\n");
echo json_encode($stats, JSON_PRETTY_PRINT) . "\n";

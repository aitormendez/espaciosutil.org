<?php

namespace EspacioSutil\Practicas;

/** Portable, deterministic guide derived from the editorial HTML. No generated prose. */
final class Guide
{
    public static function forPost(\WP_Post $post): array
    {
        if ((!Catalog::allowed() && !Catalog::editor()) || post_password_required($post)) {
            return ['screens' => [], 'sections' => []];
        }
        return Audio::attach($post->ID, self::fromHtml(wpautop($post->post_content)));
    }

    public static function readingHtml(string $html, string $prefix, array $sections): string
    {
        if (!$sections) { return $html; }
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><body>'.$html.'</body>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $next = 0;
        foreach ((new \DOMXPath($doc))->query('//h2|//h3|//h4|//h5|//h6') as $heading) {
            $title = trim(preg_replace('/\s+/u', ' ', $heading->textContent));
            if (isset($sections[$next]) && $sections[$next]['title'] === $title) {
                $heading->setAttribute('id', $prefix.'-section-'.$next);
                $heading->setAttribute('data-practice-heading', (string) $next);
                $heading->setAttribute('tabindex', '-1');
                $next++;
            }
        }
        $result = '';
        foreach ($doc->getElementsByTagName('body')->item(0)->childNodes as $node) { $result .= $doc->saveHTML($node); }
        return $result;
    }

    /** Spell editorial numbering only; visual titles and paragraph speech stay intact. */
    private static function headingSpeech(string $heading): string
    {
        if (preg_match('/^(\d+(?:\.\d+)*)(\.?)\s+(.+)$/u', $heading, $parts)
            && ($parts[2] === '.' || str_contains($parts[1], '.'))) {
            $numbers = array_map(static fn ($part) => self::numberSpeech($part), explode('.', $parts[1]));
            $heading = ucfirst(implode(' punto ', $numbers)).'. '.$parts[3];
        }
        return rtrim($heading, '. ').'.';
    }

    private static function numberSpeech(string $digits): string
    {
        $small = ['cero', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve', 'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve', 'veinte', 'veintiuno', 'veintidós', 'veintitrés', 'veinticuatro', 'veinticinco', 'veintiséis', 'veintisiete', 'veintiocho', 'veintinueve'];
        // Preserve leading zeroes and unusually long identifiers without integer overflow.
        if (strlen($digits) > 3 || (strlen($digits) > 1 && $digits[0] === '0')) {
            return implode(' ', array_map(static fn ($digit) => $small[(int) $digit], str_split($digits)));
        }
        $number = (int) $digits;
        if ($number < 30) { return $small[$number]; }
        if ($number < 100) {
            $tens = [3 => 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
            return $tens[intdiv($number, 10)].($number % 10 ? ' y '.$small[$number % 10] : '');
        }
        if ($number === 100) { return 'cien'; }
        $hundreds = [1 => 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];
        return $hundreds[intdiv($number, 100)].($number % 100 ? ' '.self::numberSpeech((string) ($number % 100)) : '');
    }

    public static function fromHtml(string $html): array
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $doc->loadHTML('<?xml encoding="UTF-8"><body>'.$html.'</body>', LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($previous);
        $body = $doc->getElementsByTagName('body')->item(0);
        $screens = $sections = $path = [];
        $pending = false;
        $unsupported = false;
        $flush = static function () use (&$pending, &$path, &$screens, &$sections) {
            if (!$pending || !$path) { return; }
            $route = array_values($path);
            $section = $route[0];
            if (!isset($sections[$section])) {
                $sections[$section] = ['title' => $section, 'screen' => count($screens)];
            }
            $screens[] = ['type' => 'heading', 'path' => $route, 'html' => '', 'speech' => self::headingSpeech(end($route)), 'section' => $section];
            $pending = false;
        };
        $visit = static function ($node) use (&$visit, &$path, &$pending, &$unsupported, &$screens, $flush, $doc) {
            if ($node instanceof \DOMComment) { return; }
            if ($node instanceof \DOMText) {
                if (trim($node->textContent) !== '') { $unsupported = true; }
                return;
            }
            if (!($node instanceof \DOMElement)) { return; }
            $tag = strtolower($node->tagName);
            $text = trim(preg_replace('/\s+/u', ' ', $node->textContent));
            if (preg_match('/^h([2-6])$/', $tag, $match)) {
                $level = (int) $match[1];
                if ($pending) { $flush(); }
                foreach (array_keys($path) as $key) { if ($key >= $level) { unset($path[$key]); } }
                if ($text !== '') { $path[$level] = $text; $pending = true; }
            } elseif ($tag === 'p') {
                foreach ($node->getElementsByTagName('*') as $inline) {
                    if (!in_array(strtolower($inline->tagName), ['em', 'strong', 'b', 'i', 'br', 'a'], true)) { $unsupported = true; }
                }
                if ($text === '') { return; }
                $flush();
                $screens[] = ['type' => 'paragraph', 'path' => array_values($path), 'html' => wp_kses($doc->saveHTML($node), ['p'=>[], 'em'=>[], 'strong'=>[], 'b'=>[], 'i'=>[], 'br'=>[], 'a'=>['href'=>[], 'title'=>[]]]), 'speech' => $text, 'section' => $path ? reset($path) : ''];
            } elseif (in_array($tag, ['div', 'section', 'article'], true)) {
                foreach ($node->childNodes as $child) { $visit($child); }
            } else {
                // Lists, tables, media and custom blocks need editorial adaptation; never silently omit them.
                $unsupported = true;
            }
        };
        if ($body) { foreach ($body->childNodes as $node) { $visit($node); } }
        $flush();
        if ($unsupported) { return ['screens' => [], 'sections' => []]; }
        return ['screens' => $screens, 'sections' => array_values($sections)];
    }
}

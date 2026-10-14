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
            $screens[] = ['type' => 'heading', 'path' => $route, 'html' => '', 'speech' => implode('. ', $route), 'section' => $section];
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
                if ($pending && $path && $level <= max(array_keys($path))) { $flush(); }
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

<?php
namespace EspacioSutil\Mobile;

/** Convierte contenido editorial a nodos nativos; nunca ejecuta bloques ni shortcodes. */
final class Study
{
    private array $unsupported = [];

    public static function read(int $lesson): array
    {
        $parser = new self();
        $intro = $parser->document((string) get_field('rich_excerpt', $lesson));
        $blocks = $parser->document((string) get_post($lesson)->post_content);
        $chapters = []; $current = null;
        foreach ($blocks as $block) {
            if ($block['type'] === 'heading' && $block['level'] <= 2) {
                if ($current !== null) $chapters[] = $current;
                $title = implode('', array_column($block['content'], 'text'));
                $current = ['id' => ($block['anchor'] ?? '') ?: 'chapter-'.count($chapters).'-'.substr(hash('sha256', $title), 0, 12), 'title' => $title, 'blocks' => []];
            } else {
                $current ??= ['id' => 'introduction', 'title' => 'Introducción', 'blocks' => []];
                $current['blocks'][] = $block;
            }
        }
        if ($current !== null) $chapters[] = $current;
        $data = ['lesson_id' => $lesson, 'title' => wp_strip_all_tags(get_the_title($lesson)), 'introduction' => $intro, 'chapters' => $chapters, 'unsupported_blocks' => array_values(array_unique($parser->unsupported)), 'summary_available' => count($chapters) > 0, 'quiz_available' => Quiz::definition($lesson)['available']];
        return ['version' => hash('sha256', wp_json_encode($data))] + $data;
    }

    public function document(string $html): array
    {
        if (trim($html) === '') return [];
        if (strlen($html) > 2000000) throw new \RuntimeException('Documento demasiado grande.');
        $dom = new \DOMDocument();
        $previous = libxml_use_internal_errors(true);
        try { $dom->loadHTML('<?xml encoding="UTF-8"><html><body>'.$html.'</body></html>', LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING); }
        finally { libxml_clear_errors(); libxml_use_internal_errors($previous); }
        return $this->blocks($dom->getElementsByTagName('body')->item(0));
    }

    private function blocks(?\DOMNode $node, int $depth = 0): array
    {
        if (!$node || $depth > 40) return [];
        $out = []; $pending = [];
        $flush = static function() use (&$out, &$pending) { if (trim(implode('', array_column($pending, 'text'))) !== '') $out[] = ['type' => 'paragraph', 'content' => $pending]; $pending = []; };
        foreach ($node->childNodes as $child) {
            if ($child instanceof \DOMComment) continue;
            $tag = strtolower($child->nodeName);
            if (in_array($tag, ['script','style','iframe','object','embed','form','input','button','svg'], true)) { $this->unsupported[] = $tag; continue; }
            if ($child instanceof \DOMText || in_array($tag, ['a','em','i','strong','b','code','span','br','u','s'], true)) { $pending = array_merge($pending, $this->inline($child)); continue; }
            $flush();
            if (preg_match('/^h([1-6])$/', $tag, $m)) $out[] = ['type' => 'heading', 'level' => (int)$m[1], 'anchor' => $child->getAttribute('id'), 'content' => $this->inline($child)];
            elseif ($tag === 'p') $out = array_merge($out, $this->blocks($child, $depth + 1));
            elseif ($tag === 'pre') $out[] = ['type' => 'paragraph', 'content' => $this->inline($child, ['code' => $tag === 'pre'])];
            elseif ($tag === 'blockquote') { foreach ($this->blocks($child, $depth + 1) as $b) { if ($b['type'] === 'paragraph') $b['type'] = 'quote'; $out[] = $b; } }
            elseif ($tag === 'ul' || $tag === 'ol') {
                $items = []; foreach ($child->childNodes as $li) if ($li->nodeName === 'li') $items[] = $this->blocks($li, $depth + 1);
                $out[] = ['type' => 'list', 'ordered' => $tag === 'ol', 'items' => $items];
            } elseif ($tag === 'img') {
                $url = $this->url($child->getAttribute('src'), true);
                if ($url) $out[] = ['type' => 'image', 'url' => $url, 'alt' => $child->getAttribute('alt'), 'caption' => []]; else $this->unsupported[] = 'image-source';
            } elseif ($tag === 'hr') $out[] = ['type' => 'separator'];
            else { if (!in_array($tag, ['div','section','figure','figcaption','article'], true)) $this->unsupported[] = $tag; $out = array_merge($out, $this->blocks($child, $depth + 1)); }
        }
        $flush(); return array_values(array_filter($out, fn($b) => !isset($b['content']) || trim(implode('', array_column($b['content'], 'text'))) !== ''));
    }

    private function inline(\DOMNode $node, array $marks = [], int $depth = 0): array
    {
        if ($depth > 40) return [];
        $marks += ['bold' => false, 'italic' => false, 'code' => false, 'href' => null];
        if ($node instanceof \DOMText) return [['text' => $node->textContent] + $marks];
        $tag = strtolower($node->nodeName);
        if (in_array($tag, ['script','style','iframe','object','embed'], true)) { $this->unsupported[] = $tag; return []; }
        if ($tag === 'br') return [['text' => "\n"] + $marks];
        if ($tag === 'strong' || $tag === 'b') $marks['bold'] = true;
        if ($tag === 'em' || $tag === 'i') $marks['italic'] = true;
        if ($tag === 'code') $marks['code'] = true;
        if ($tag === 'a') $marks['href'] = $this->url($node->getAttribute('href'));
        $out = []; foreach ($node->childNodes as $child) $out = array_merge($out, $this->inline($child, $marks, $depth + 1)); return $out;
    }

    private function url(string $url, bool $image = false): ?string
    {
        $url = trim(html_entity_decode($url, ENT_QUOTES | ENT_HTML5, 'UTF-8'));
        if (!$image && str_starts_with($url, '#')) return $url;
        if (str_starts_with($url, '/') && !str_starts_with($url, '//')) $url = home_url($url);
        $parts = wp_parse_url($url);
        if (!$parts || !in_array($parts['scheme'] ?? '', ['https','http'], true) || isset($parts['user']) || isset($parts['pass'])) return null;
        if ($image && (($parts['scheme'] ?? '') !== 'https' || ($parts['host'] ?? '') !== wp_parse_url(home_url(), PHP_URL_HOST))) return null;
        return $url;
    }
}

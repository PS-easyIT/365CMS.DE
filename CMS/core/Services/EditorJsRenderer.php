<?php
/**
 * Editor.js JSON → HTML Renderer
 *
 * Konvertiert gespeicherte Editor.js Block-Daten in sicheres HTML.
 *
 * @package CMSv2\Services
 */

declare(strict_types=1);

namespace CMS\Services;

if (!defined('ABSPATH')) {
    exit;
}

final class EditorJsRenderer
{
    private static ?self $instance = null;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct() {}

    /**
     * Editor.js Output-Objekt zu HTML rendern.
     *
     * @param string|array $data  JSON-String oder bereits dekodiertes Array
     * @return string             Sicheres HTML
     */
    public function render(string|array $data): string
    {
        if (is_string($data)) {
            $data = json_decode($data, true);
        }
        if (!is_array($data) || empty($data['blocks'])) {
            return '';
        }

        $html = '';
        foreach ($data['blocks'] as $block) {
            $type = $block['type'] ?? '';
            $blockData = $block['data'] ?? [];
            $html .= match ($type) {
                'paragraph'  => $this->renderParagraph($blockData),
                'header'     => $this->renderHeader($blockData),
                'list'       => $this->renderList($blockData),
                'checklist'  => $this->renderChecklist($blockData),
                'quote'      => $this->renderQuote($blockData),
                'warning'    => $this->renderWarning($blockData),
                'code'       => $this->renderCode($blockData),
                'raw'        => $this->renderRaw($blockData),
                'table'      => $this->renderTable($blockData),
                'image'      => $this->renderImage($blockData),
                'attaches'   => $this->renderAttaches($blockData),
                'linkTool'   => $this->renderLink($blockData),
                'delimiter'  => "<hr>\n",
                default      => '',
            };
        }

        return $html;
    }

    private function renderParagraph(array $d): string
    {
        $text = $this->sanitizeInline($d['text'] ?? '');
        if ($text === '') {
            return '';
        }
        return "<p>{$text}</p>\n";
    }

    private function renderHeader(array $d): string
    {
        $text  = $this->sanitizeInline($d['text'] ?? '');
        $level = max(1, min(6, (int)($d['level'] ?? 2)));
        if ($text === '') {
            return '';
        }
        $id = $this->slugify(strip_tags($text));
        return "<h{$level} id=\"{$id}\">{$text}</h{$level}>\n";
    }

    private function renderList(array $d): string
    {
        $style = ($d['style'] ?? 'unordered') === 'ordered' ? 'ol' : 'ul';
        $items = $d['items'] ?? [];
        if (empty($items)) {
            return '';
        }
        $html = "<{$style}>\n";
        foreach ($items as $item) {
            // Editor.js list plugin v2+ uses {content, items} structure
            $content = is_array($item) ? ($item['content'] ?? $item['text'] ?? '') : (string)$item;
            $html .= '  <li>' . $this->sanitizeInline($content);
            // Nested items
            if (is_array($item) && !empty($item['items'])) {
                $html .= "\n" . $this->renderList(['style' => $d['style'] ?? 'unordered', 'items' => $item['items']]);
            }
            $html .= "</li>\n";
        }
        $html .= "</{$style}>\n";
        return $html;
    }

    private function renderChecklist(array $d): string
    {
        $items = $d['items'] ?? [];
        if (empty($items)) {
            return '';
        }
        $html = "<ul class=\"checklist\">\n";
        foreach ($items as $item) {
            $text    = $this->sanitizeInline($item['text'] ?? '');
            $checked = !empty($item['checked']) ? ' checked' : '';
            $icon    = !empty($item['checked']) ? '☑' : '☐';
            $html   .= "  <li class=\"checklist-item{$checked}\">{$icon} {$text}</li>\n";
        }
        $html .= "</ul>\n";
        return $html;
    }

    private function renderQuote(array $d): string
    {
        $text    = $this->sanitizeInline($d['text'] ?? '');
        $caption = $this->sanitizeInline($d['caption'] ?? '');
        if ($text === '') {
            return '';
        }
        $html = "<blockquote><p>{$text}</p>";
        if ($caption !== '') {
            $html .= "<cite>{$caption}</cite>";
        }
        $html .= "</blockquote>\n";
        return $html;
    }

    private function renderWarning(array $d): string
    {
        $title   = $this->sanitizeInline($d['title'] ?? '');
        $message = $this->sanitizeInline($d['message'] ?? '');
        if ($title === '' && $message === '') {
            return '';
        }
        $html = "<div class=\"warning-block\">\n";
        if ($title !== '') {
            $html .= "  <strong>{$title}</strong>\n";
        }
        if ($message !== '') {
            $html .= "  <p>{$message}</p>\n";
        }
        $html .= "</div>\n";
        return $html;
    }

    private function renderCode(array $d): string
    {
        $code = htmlspecialchars($d['code'] ?? '', ENT_QUOTES, 'UTF-8');
        if ($code === '') {
            return '';
        }
        return "<pre><code>{$code}</code></pre>\n";
    }

    private function renderRaw(array $d): string
    {
        // Roher HTML-Block — muss via PurifierService gesäubert werden
        $html = $d['html'] ?? '';
        if (function_exists('sanitize_html')) {
            return sanitize_html($html) . "\n";
        }
        return strip_tags($html, '<p><a><strong><em><ul><ol><li><br><h2><h3><h4><h5><div><span><img><table><tr><td><th><thead><tbody><blockquote><pre><code><hr>') . "\n";
    }

    private function renderTable(array $d): string
    {
        $content  = $d['content'] ?? [];
        $withHead = !empty($d['withHeadings']);
        if (empty($content)) {
            return '';
        }
        $html = "<table class=\"editorjs-table\">\n";
        foreach ($content as $i => $row) {
            if ($i === 0 && $withHead) {
                $html .= "<thead><tr>\n";
                foreach ($row as $cell) {
                    $html .= '  <th>' . $this->sanitizeInline($cell) . "</th>\n";
                }
                $html .= "</tr></thead>\n<tbody>\n";
            } else {
                $html .= "<tr>\n";
                foreach ($row as $cell) {
                    $html .= '  <td>' . $this->sanitizeInline($cell) . "</td>\n";
                }
                $html .= "</tr>\n";
            }
        }
        $html .= $withHead ? "</tbody>\n</table>\n" : "</table>\n";
        return $html;
    }

    private function renderImage(array $d): string
    {
        $url     = filter_var($d['file']['url'] ?? '', FILTER_VALIDATE_URL) ?: '';
        $caption = $this->sanitizeInline($d['caption'] ?? '');
        if ($url === '') {
            return '';
        }
        $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

        $classes = ['editorjs-image'];
        if (!empty($d['stretched']))     { $classes[] = 'image-stretched'; }
        if (!empty($d['withBorder']))    { $classes[] = 'image-bordered'; }
        if (!empty($d['withBackground'])){ $classes[] = 'image-bg'; }

        $cls = implode(' ', $classes);
        $alt = $caption !== '' ? $caption : 'Bild';
        $html = "<figure class=\"{$cls}\">\n";
        $html .= "  <img src=\"{$url}\" alt=\"" . htmlspecialchars(strip_tags($alt), ENT_QUOTES, 'UTF-8') . "\" loading=\"lazy\">\n";
        if ($caption !== '') {
            $html .= "  <figcaption>{$caption}</figcaption>\n";
        }
        $html .= "</figure>\n";
        return $html;
    }

    private function renderAttaches(array $d): string
    {
        $url   = filter_var($d['file']['url'] ?? '', FILTER_VALIDATE_URL) ?: '';
        $title = $this->sanitizeInline($d['title'] ?? ($d['file']['name'] ?? 'Datei'));
        $size  = (int)($d['file']['size'] ?? 0);
        if ($url === '') {
            return '';
        }
        $url  = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');
        $sizeStr = $size > 0 ? ' (' . $this->formatBytes($size) . ')' : '';
        return "<div class=\"editorjs-attaches\"><a href=\"{$url}\" target=\"_blank\" rel=\"noopener noreferrer\">📎 {$title}{$sizeStr}</a></div>\n";
    }

    private function renderLink(array $d): string
    {
        $link  = filter_var($d['link'] ?? '', FILTER_VALIDATE_URL) ?: '';
        if ($link === '') {
            return '';
        }
        $link  = htmlspecialchars($link, ENT_QUOTES, 'UTF-8');
        $meta  = $d['meta'] ?? [];
        $title = $this->sanitizeInline($meta['title'] ?? $link);
        $desc  = $this->sanitizeInline($meta['description'] ?? '');
        $image = filter_var($meta['image']['url'] ?? '', FILTER_VALIDATE_URL) ?: '';

        $html  = "<div class=\"editorjs-link\">\n";
        $html .= "  <a href=\"{$link}\" target=\"_blank\" rel=\"noopener noreferrer\">\n";
        if ($image !== '') {
            $html .= "    <img src=\"" . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . "\" alt=\"\" loading=\"lazy\">\n";
        }
        $html .= "    <strong>{$title}</strong>\n";
        if ($desc !== '') {
            $html .= "    <p>{$desc}</p>\n";
        }
        $html .= "  </a>\n</div>\n";
        return $html;
    }

    /**
     * Erlaubte Inline-Tags sanitieren (Editor.js inline formatting).
     */
    private function sanitizeInline(string $html): string
    {
        return strip_tags($html, '<b><i><u><a><code><mark><sub><sup><br><strong><em>');
    }

    private function slugify(string $text): string
    {
        $text = mb_strtolower(trim($text));
        $text = preg_replace('/[^\p{L}\p{N}\s-]/u', '', $text);
        $text = preg_replace('/[\s-]+/', '-', $text);
        return trim($text, '-') ?: 'section';
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024)           return $bytes . ' B';
        if ($bytes < 1048576)        return round($bytes / 1024, 1) . ' KB';
        return round($bytes / 1048576, 1) . ' MB';
    }
}

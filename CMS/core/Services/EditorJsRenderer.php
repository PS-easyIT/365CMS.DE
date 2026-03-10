<?php
/**
 * Editor.js Renderer
 *
 * Rendert Editor.js JSON-Daten in sauberes HTML für das Frontend.
 * Unterstützt Standard-Tools sowie zusätzliche 365CMS-Plugins.
 *
 * @package CMSv2\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Json;

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

    private function __construct()
    {
    }

    /**
     * Editor.js JSON rendern.
     *
     * @param string|array<string,mixed> $data
     */
    public function render(string|array $data): string
    {
        if (is_string($data)) {
            $data = Json::decodeArray($data, []);
        }

        if (!is_array($data) || !isset($data['blocks']) || !is_array($data['blocks'])) {
            return '';
        }

        return $this->renderBlocks($data['blocks']);
    }

    /**
     * @param array<int, array<string,mixed>> $blocks
     */
    private function renderBlocks(array $blocks): string
    {
        $html = '';
        $count = count($blocks);

        for ($index = 0; $index < $count; $index++) {
            $block = $blocks[$index] ?? null;
            if (!is_array($block)) {
                continue;
            }

            $type = (string)($block['type'] ?? '');
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];

            if ($type === 'accordion') {
                $nestedBlocks = [];
                $blockCount = max(1, (int)($data['settings']['blockCount'] ?? 3));

                for ($offset = 1; $offset <= $blockCount; $offset++) {
                    $nextBlock = $blocks[$index + $offset] ?? null;
                    if (!is_array($nextBlock) || (string)($nextBlock['type'] ?? '') === 'accordion') {
                        break;
                    }
                    $nestedBlocks[] = $nextBlock;
                }

                $html .= $this->renderAccordion($data, $nestedBlocks);
                $index += count($nestedBlocks);
                continue;
            }

            $html .= $this->renderBlock($block);
        }

        return $html;
    }

    /**
     * @param array<string,mixed> $block
     */
    private function renderBlock(array $block): string
    {
        $type = (string)($block['type'] ?? '');
        $data = is_array($block['data'] ?? null) ? $block['data'] : [];
        $tunes = is_array($block['tunes'] ?? null) ? $block['tunes'] : [];

        return match ($type) {
            'paragraph' => $this->renderParagraph($data),
            'header' => $this->renderHeader($data),
            'list' => $this->renderList($data),
            'checklist' => $this->renderChecklist($data),
            'quote' => $this->renderQuote($data),
            'warning' => $this->renderWarning($data),
            'code' => $this->renderCode($data),
            'raw' => $this->renderRaw($data),
            'table' => $this->renderTable($data),
            'image' => $this->renderImage($data, $tunes),
            'attaches' => $this->renderAttaches($data),
            'linkTool' => $this->renderLinkTool($data),
            'delimiter' => '<div class="editorjs-block editorjs-delimiter"><hr></div>',
            'spacer' => $this->renderSpacer($data),
            'embed' => $this->renderEmbed($data),
            'imageGallery' => $this->renderImageGallery($data),
            'carousel' => $this->renderCarousel($data),
            'columns' => $this->renderColumns($data),
            'drawingTool' => $this->renderDrawingTool($data),
            default => '',
        };
    }

    /** @param array<string,mixed> $data */
    private function renderParagraph(array $data): string
    {
        $text = $this->sanitizeInline((string)($data['text'] ?? ''));
        return $text !== '' ? '<div class="editorjs-block editorjs-paragraph"><p>' . $text . '</p></div>' : '';
    }

    /** @param array<string,mixed> $data */
    private function renderHeader(array $data): string
    {
        $text = $this->sanitizeInline((string)($data['text'] ?? ''));
        $level = max(1, min(6, (int)($data['level'] ?? 2)));
        return $text !== '' ? '<div class="editorjs-block editorjs-header"><h' . $level . '>' . $text . '</h' . $level . '></div>' : '';
    }

    /** @param array<string,mixed> $data */
    private function renderList(array $data): string
    {
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        if ($items === []) {
            return '';
        }

        $style = (string)($data['style'] ?? 'unordered');
        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
        return '<div class="editorjs-block editorjs-list">' . $this->renderListMarkup($items, $style, $meta) . '</div>';
    }

    /** @param array<int, mixed> $items */
    private function renderListMarkup(array $items, string $style, array $meta = []): string
    {
        $tag = $style === 'ordered' ? 'ol' : 'ul';
        $attributes = '';

        if ($style === 'ordered') {
            $start = max(1, (int)($meta['start'] ?? 1));
            if ($start > 1) {
                $attributes .= ' start="' . $start . '"';
            }

            $counterType = (string)($meta['counterType'] ?? 'numeric');
            $typeMap = [
                'numeric' => '1',
                'lower-alpha' => 'a',
                'upper-alpha' => 'A',
                'lower-roman' => 'i',
                'upper-roman' => 'I',
            ];
            if (isset($typeMap[$counterType])) {
                $attributes .= ' type="' . $typeMap[$counterType] . '"';
            }
        }

        $class = 'editorjs-list__items';
        if ($style === 'checklist') {
            $class .= ' editorjs-list__items--checklist';
        }

        $html = '<' . $tag . ' class="' . $class . '"' . $attributes . '>';
        foreach ($items as $item) {
            if (is_string($item)) {
                $item = ['content' => $item, 'items' => []];
            }
            if (!is_array($item)) {
                continue;
            }

            $content = $this->sanitizeInline((string)($item['content'] ?? $item['text'] ?? ''));
            $children = is_array($item['items'] ?? null) ? $item['items'] : [];
            $itemMeta = is_array($item['meta'] ?? null) ? $item['meta'] : [];

            $html .= '<li class="editorjs-list__item">';
            if ($style === 'checklist') {
                $checked = !empty($itemMeta['checked']) ? ' checked' : '';
                $html .= '<span class="editorjs-checklist__label"><input type="checkbox" disabled' . $checked . '><span>' . $content . '</span></span>';
            } else {
                $html .= $content;
            }

            if ($children !== []) {
                $html .= $this->renderListMarkup($children, $style, $itemMeta);
            }
            $html .= '</li>';
        }
        $html .= '</' . $tag . '>';

        return $html;
    }

    /** @param array<string,mixed> $data */
    private function renderChecklist(array $data): string
    {
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];
        if ($items === []) {
            return '';
        }

        $html = '<div class="editorjs-block editorjs-checklist"><ul>';
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $text = $this->sanitizeInline((string)($item['text'] ?? ''));
            $checked = !empty($item['checked']) ? ' checked' : '';
            $html .= '<li><label><input type="checkbox" disabled' . $checked . '> ' . $text . '</label></li>';
        }
        $html .= '</ul></div>';

        return $html;
    }

    /** @param array<string,mixed> $data */
    private function renderQuote(array $data): string
    {
        $text = $this->sanitizeInline((string)($data['text'] ?? ''));
        $caption = $this->sanitizeInline((string)($data['caption'] ?? ''));
        if ($text === '') {
            return '';
        }

        $html = '<div class="editorjs-block editorjs-quote"><blockquote><p>' . $text . '</p>';
        if ($caption !== '') {
            $html .= '<cite>' . $caption . '</cite>';
        }
        $html .= '</blockquote></div>';

        return $html;
    }

    /** @param array<string,mixed> $data */
    private function renderWarning(array $data): string
    {
        $title = $this->sanitizeInline((string)($data['title'] ?? 'Hinweis'));
        $message = $this->sanitizeInline((string)($data['message'] ?? ''));
        if ($title === '' && $message === '') {
            return '';
        }

        return '<div class="editorjs-block editorjs-warning"><div class="warning-title">' . $title . '</div><div class="warning-message">' . $message . '</div></div>';
    }

    /** @param array<string,mixed> $data */
    private function renderCode(array $data): string
    {
        $code = htmlspecialchars((string)($data['code'] ?? ''), ENT_QUOTES, 'UTF-8');
        if ($code === '') {
            return '';
        }

        $language = trim((string)($data['language'] ?? ''));
        $class = $language !== '' ? ' class="language-' . htmlspecialchars($language, ENT_QUOTES, 'UTF-8') . '"' : '';

        return '<div class="editorjs-block editorjs-code"><pre><code' . $class . '>' . $code . '</code></pre></div>';
    }

    /** @param array<string,mixed> $data */
    private function renderRaw(array $data): string
    {
        $html = (string)($data['html'] ?? '');
        return $html !== '' ? '<div class="editorjs-block editorjs-raw">' . $html . '</div>' : '';
    }

    /** @param array<string,mixed> $data */
    private function renderTable(array $data): string
    {
        $rows = is_array($data['content'] ?? null) ? $data['content'] : [];
        if ($rows === []) {
            return '';
        }

        $withHeadings = !empty($data['withHeadings']);
        $html = '<div class="editorjs-block editorjs-table"><table>';

        foreach ($rows as $rowIndex => $row) {
            if (!is_array($row)) {
                continue;
            }
            $html .= '<tr>';
            foreach ($row as $cell) {
                $tag = $withHeadings && $rowIndex === 0 ? 'th' : 'td';
                $html .= '<' . $tag . '>' . $this->sanitizeInline((string)$cell) . '</' . $tag . '>';
            }
            $html .= '</tr>';
        }

        $html .= '</table></div>';
        return $html;
    }

    /**
     * @param array<string,mixed> $data
     * @param array<string,mixed> $tunes
     */
    private function renderImage(array $data, array $tunes = []): string
    {
        $file = is_array($data['file'] ?? null) ? $data['file'] : [];
        $imageUrl = (string)($file['url'] ?? '');

        foreach (['Cropper', 'CropperTune'] as $tuneKey) {
            if (!empty($tunes[$tuneKey]['croppedImage']) && filter_var((string)$tunes[$tuneKey]['croppedImage'], FILTER_VALIDATE_URL)) {
                $imageUrl = (string)$tunes[$tuneKey]['croppedImage'];
                break;
            }
        }

        if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            return '';
        }

        $caption = $this->sanitizeInline((string)($data['caption'] ?? ''));
        $classes = ['editorjs-block', 'editorjs-image'];
        if (!empty($data['withBorder'])) {
            $classes[] = 'editorjs-image--border';
        }
        if (!empty($data['withBackground'])) {
            $classes[] = 'editorjs-image--background';
        }
        if (!empty($data['stretched'])) {
            $classes[] = 'editorjs-image--stretched';
        }

        $html = '<figure class="' . implode(' ', $classes) . '">';
        $html .= '<img src="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" alt="' . htmlspecialchars(strip_tags($caption), ENT_QUOTES, 'UTF-8') . '" loading="lazy">';
        if ($caption !== '') {
            $html .= '<figcaption>' . $caption . '</figcaption>';
        }
        $html .= '</figure>';

        return $html;
    }

    /** @param array<string,mixed> $data */
    private function renderAttaches(array $data): string
    {
        $file = is_array($data['file'] ?? null) ? $data['file'] : [];
        $url = (string)($file['url'] ?? '');
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $name = htmlspecialchars((string)($file['name'] ?? 'Download'), ENT_QUOTES, 'UTF-8');
        $size = max(0, (int)($file['size'] ?? 0));
        $sizeLabel = $size > 0 ? ' <span>(' . $this->formatFileSize($size) . ')</span>' : '';

        return '<div class="editorjs-block editorjs-attaches"><a href="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">' . $name . $sizeLabel . '</a></div>';
    }

    /** @param array<string,mixed> $data */
    private function renderLinkTool(array $data): string
    {
        $link = (string)($data['link'] ?? '');
        if (!filter_var($link, FILTER_VALIDATE_URL)) {
            return '';
        }

        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
        $title = $this->sanitizeInline((string)($meta['title'] ?? $link));
        $description = $this->sanitizeInline((string)($meta['description'] ?? ''));
        $image = (string)($meta['image']['url'] ?? '');

        $html = '<div class="editorjs-block editorjs-link"><a href="' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">';
        if (filter_var($image, FILTER_VALIDATE_URL)) {
            $html .= '<div class="editorjs-link__image"><img src="' . htmlspecialchars($image, ENT_QUOTES, 'UTF-8') . '" alt="" loading="lazy"></div>';
        }
        $html .= '<div class="editorjs-link__content"><strong>' . $title . '</strong>';
        if ($description !== '') {
            $html .= '<p>' . $description . '</p>';
        }
        $html .= '<small>' . htmlspecialchars($link, ENT_QUOTES, 'UTF-8') . '</small></div></a></div>';

        return $html;
    }

    /** @param array<string,mixed> $data */
    private function renderEmbed(array $data): string
    {
        $embedUrl = (string)($data['embed'] ?? $data['source'] ?? '');
        if (!filter_var($embedUrl, FILTER_VALIDATE_URL)) {
            return '';
        }

        $caption = $this->sanitizeInline((string)($data['caption'] ?? ''));
        $width = max(320, (int)($data['width'] ?? 640));
        $height = max(180, (int)($data['height'] ?? 360));

        $html = '<figure class="editorjs-block editorjs-embed">';
        $html .= '<div class="editorjs-embed__frame"><iframe src="' . htmlspecialchars($embedUrl, ENT_QUOTES, 'UTF-8') . '" loading="lazy" allowfullscreen width="' . $width . '" height="' . $height . '"></iframe></div>';
        if ($caption !== '') {
            $html .= '<figcaption>' . $caption . '</figcaption>';
        }
        $html .= '</figure>';

        return $html;
    }

    /** @param array<string,mixed> $data */
    private function renderSpacer(array $data): string
    {
        $allowedHeights = [15, 25, 40, 60, 75, 100];
        $height = (int)($data['height'] ?? 15);

        if (!in_array($height, $allowedHeights, true)) {
            $height = 15;
        }

        return '<div class="editorjs-block editorjs-spacer" aria-hidden="true" data-height="' . $height . '" style="height:' . $height . 'px"></div>';
    }

    /** @param array<string,mixed> $data */
    private function renderImageGallery(array $data): string
    {
        $urls = is_array($data['urls'] ?? null) ? $data['urls'] : [];
        $urls = array_values(array_filter($urls, static fn($url) => filter_var((string)$url, FILTER_VALIDATE_URL)));

        if ($urls === []) {
            return '';
        }

        $html = '<div class="editorjs-block editorjs-gallery">';
        foreach ($urls as $url) {
            $html .= '<figure class="editorjs-gallery__item"><img src="' . htmlspecialchars((string)$url, ENT_QUOTES, 'UTF-8') . '" alt="" loading="lazy"></figure>';
        }
        $html .= '</div>';

        return $html;
    }

    /** @param array<string,mixed> $data */
    private function renderCarousel(array $data): string
    {
        if (!array_is_list($data) || $data === []) {
            return '';
        }

        $html = '<div class="editorjs-block editorjs-carousel">';
        foreach ($data as $item) {
            if (!is_array($item)) {
                continue;
            }

            $url = (string)($item['url'] ?? '');
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $caption = $this->sanitizeInline((string)($item['caption'] ?? ''));
            $html .= '<figure class="editorjs-carousel__item">';
            $html .= '<img src="' . htmlspecialchars($url, ENT_QUOTES, 'UTF-8') . '" alt="" loading="lazy">';
            if ($caption !== '') {
                $html .= '<figcaption>' . $caption . '</figcaption>';
            }
            $html .= '</figure>';
        }
        $html .= '</div>';

        return $html;
    }

    /** @param array<string,mixed> $data */
    private function renderColumns(array $data): string
    {
        $columns = is_array($data['cols'] ?? null) ? $data['cols'] : [];
        if ($columns === []) {
            return '';
        }

        $html = '<div class="editorjs-block editorjs-columns editorjs-columns--' . count($columns) . '">';
        foreach ($columns as $column) {
            if (!is_array($column)) {
                continue;
            }
            $html .= '<div class="editorjs-columns__col">' . $this->render($column) . '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * @param array<string,mixed> $data
     * @param array<int, array<string,mixed>> $nestedBlocks
     */
    private function renderAccordion(array $data, array $nestedBlocks): string
    {
        $title = $this->sanitizeInline((string)($data['title'] ?? 'Accordion'));
        $content = $this->renderBlocks($nestedBlocks);
        if ($content === '') {
            return '';
        }

        $open = !empty($data['settings']['defaultExpanded']) ? ' open' : '';
        return '<div class="editorjs-block editorjs-accordion"><details' . $open . '><summary>' . $title . '</summary><div class="editorjs-accordion__content">' . $content . '</div></details></div>';
    }

    /** @param array<string,mixed> $data */
    private function renderDrawingTool(array $data): string
    {
        $images = is_array($data['canvasImages'] ?? null) ? $data['canvasImages'] : [];
        if ($images === []) {
            return '';
        }

        $html = '<div class="editorjs-block editorjs-drawing">';
        foreach ($images as $image) {
            if (!is_array($image)) {
                continue;
            }

            $src = (string)($image['src'] ?? '');
            if (!filter_var($src, FILTER_VALIDATE_URL) && !str_starts_with($src, 'data:image/')) {
                continue;
            }

            $html .= '<img src="' . htmlspecialchars($src, ENT_QUOTES, 'UTF-8') . '" alt="Zeichnung" loading="lazy">';
        }
        $html .= '</div>';

        return $html;
    }

    private function sanitizeInline(string $html): string
    {
        $sanitized = strip_tags($html, '<b><i><u><a><code><mark><sub><sup><br><strong><em><span>');
        return preg_replace(
            '/<span class="tg-spoiler">(.*?)<\/span>/is',
            '<span class="tg-spoiler" style="background:#111827;color:transparent;border-radius:0.25rem;padding:0 0.2rem;">$1</span>',
            $sanitized
        ) ?? $sanitized;
    }

    private function formatFileSize(int $bytes): string
    {
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 1) . ' MB';
        }
        if ($bytes >= 1024) {
            return round($bytes / 1024, 1) . ' KB';
        }

        return $bytes . ' B';
    }
}

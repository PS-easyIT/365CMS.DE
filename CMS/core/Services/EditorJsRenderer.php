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
            'mediaText' => $this->renderMediaText($data),
            'callout' => $this->renderCallout($data),
            'terminal' => $this->renderTerminal($data),
            'codeTabs' => $this->renderCodeTabs($data),
            'mermaid' => $this->renderMermaid($data),
            'apiEndpoint' => $this->renderApiEndpoint($data),
            'changelog' => $this->renderChangelog($data),
            'prosCons' => $this->renderProsCons($data),
            default => '',
        };
    }

    /** @param array<string,mixed> $data */
    private function renderParagraph(array $data): string
    {
        $rawText = (string)($data['text'] ?? '');
        $text = $this->sanitizeInline($rawText);

        if ($text === '') {
            return array_key_exists('text', $data)
                ? '<div class="editorjs-block editorjs-paragraph"><p><br></p></div>'
                : '';
        }

        return '<div class="editorjs-block editorjs-paragraph"><p>' . $text . '</p></div>';
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
        $alignment = (string)($data['alignment'] ?? 'left');
        $alignmentClass = in_array($alignment, ['left', 'center'], true) ? ' editorjs-quote--' . $alignment : '';
        if ($text === '') {
            return '';
        }

        $html = '<div class="editorjs-block editorjs-quote' . $alignmentClass . '"><blockquote><p>' . $text . '</p>';
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
        $imageUrl = \CMS\Services\MediaDeliveryService::getInstance()->normalizeUrl((string)($file['url'] ?? ''), true);

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
        $url = \CMS\Services\MediaDeliveryService::getInstance()->normalizeUrl((string)($file['url'] ?? ''), false);
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
        $columns = (int)($data['columns'] ?? 3);
        if (!in_array($columns, [2, 3, 4, 6], true)) {
            $columns = 3;
        }

        $images = [];
        foreach ((is_array($data['images'] ?? null) ? $data['images'] : []) as $item) {
            if (!is_array($item)) {
                continue;
            }

            $file = is_array($item['file'] ?? null) ? $item['file'] : [];
            $url = \CMS\Services\MediaDeliveryService::getInstance()->normalizeUrl((string)($file['url'] ?? ''), true);
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                continue;
            }

            $images[] = [
                'url' => $url,
                'caption' => $this->sanitizeInline((string)($item['caption'] ?? '')),
                'alt' => htmlspecialchars((string)($item['caption'] ?? ''), ENT_QUOTES, 'UTF-8'),
            ];
        }

        if ($images === []) {
            $urls = is_array($data['urls'] ?? null) ? $data['urls'] : [];
            foreach ($urls as $url) {
                $normalizedUrl = \CMS\Services\MediaDeliveryService::getInstance()->normalizeUrl((string)$url, true);
                if (!filter_var($normalizedUrl, FILTER_VALIDATE_URL)) {
                    continue;
                }

                $images[] = [
                    'url' => $normalizedUrl,
                    'caption' => '',
                    'alt' => '',
                ];
            }
        }

        if ($images === []) {
            return '';
        }

        $gap = 16;
        $maxWidth = 'calc((100% - ' . max(0, ($columns - 1) * $gap) . 'px) / ' . $columns . ')';

        $html = '<div class="editorjs-block editorjs-gallery" style="display:flex;flex-wrap:wrap;gap:' . $gap . 'px;align-items:flex-start;">';
        foreach ($images as $image) {
            $html .= '<figure class="editorjs-gallery__item" style="margin:0;flex:1 1 ' . $maxWidth . ';max-width:' . $maxWidth . ';min-width:140px;">';
            $html .= '<img src="' . htmlspecialchars($image['url'], ENT_QUOTES, 'UTF-8') . '" alt="' . $image['alt'] . '" loading="lazy" style="display:block;width:100%;height:auto;aspect-ratio:4/3;object-fit:cover;border-radius:12px;">';
            if ($image['caption'] !== '') {
                $html .= '<figcaption style="margin-top:0.6rem;font-size:0.92rem;color:#475569;">' . $image['caption'] . '</figcaption>';
            }
            $html .= '</figure>';
        }
        $html .= '</div>';

        return $html;
    }

    /** @param array<string,mixed> $data */
    private function renderMediaText(array $data): string
    {
        $file = is_array($data['file'] ?? null) ? $data['file'] : [];
        $imageUrl = \CMS\Services\MediaDeliveryService::getInstance()->normalizeUrl((string)($file['url'] ?? ''), true);
        if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            $imageUrl = '';
        }

        $textHtml = $this->renderPlainTextContent((string)($data['text'] ?? ''));
        if ($imageUrl === '' && $textHtml === '') {
            return '';
        }

        $altText = trim((string)($data['alt'] ?? ''));
        $alt = htmlspecialchars($altText, ENT_QUOTES, 'UTF-8');

        $html = '<section class="editorjs-block editorjs-media-text" style="display:flex;flex-wrap:wrap;align-items:flex-start;gap:24px;">';
        if ($imageUrl !== '') {
            $html .= '<figure class="editorjs-media-text__media" style="margin:0;flex:0 1 30%;min-width:220px;max-width:360px;">';
            $html .= '<img src="' . htmlspecialchars($imageUrl, ENT_QUOTES, 'UTF-8') . '" alt="' . $alt . '" loading="lazy" style="display:block;width:100%;height:auto;aspect-ratio:4/3;object-fit:cover;border-radius:14px;">';
            $html .= '</figure>';
        }

        $html .= '<div class="editorjs-media-text__content" style="flex:1 1 360px;min-width:260px;">';
        $html .= $textHtml !== '' ? $textHtml : '<p></p>';
        $html .= '</div>';
        $html .= '</section>';

        return $html;
    }

    /** @param array<string,mixed> $data */
    private function renderCallout(array $data): string
    {
        $variant = (string) ($data['variant'] ?? 'info');
        $title = $this->sanitizeInline((string) ($data['title'] ?? ''));
        $message = $this->sanitizeInline((string) ($data['message'] ?? ''));

        if ($title === '' && $message === '') {
            return '';
        }

        $variantMap = [
            'info' => ['class' => 'callout-info', 'icon' => 'ℹ️'],
            'warning' => ['class' => 'callout-warn', 'icon' => '⚠️'],
            'success' => ['class' => 'callout-ok', 'icon' => '✅'],
        ];
        $resolved = $variantMap[$variant] ?? $variantMap['info'];

        return '<aside class="editorjs-block editorjs-callout callout ' . $resolved['class'] . '"><span class="ci">' . $resolved['icon'] . '</span><div><strong>' . ($title !== '' ? $title : 'Hinweis') . '</strong>' . ($message !== '' ? '<div>' . $message . '</div>' : '') . '</div></aside>';
    }

    /** @param array<string,mixed> $data */
    private function renderTerminal(array $data): string
    {
        $shell = htmlspecialchars((string) ($data['shell'] ?? 'bash'), ENT_QUOTES, 'UTF-8');
        $title = $this->sanitizeInline((string) ($data['title'] ?? ''));
        $command = trim((string) ($data['command'] ?? ''));
        $output = trim((string) ($data['output'] ?? ''));

        if ($command === '') {
            return '';
        }

        $html = '<section class="editorjs-block editorjs-terminal" style="margin:1.5rem 0;border:1px solid #1f2937;border-radius:14px;overflow:hidden;background:#0f172a;color:#e2e8f0;box-shadow:0 18px 34px rgba(15,23,42,.16);">';
        $html .= '<header style="display:flex;align-items:center;justify-content:space-between;gap:1rem;padding:.7rem 1rem;background:linear-gradient(180deg,#111827 0%,#0f172a 100%);border-bottom:1px solid rgba(148,163,184,.16);"><strong style="font-size:.85rem;color:#f8fafc;">' . ($title !== '' ? $title : 'Terminal') . '</strong><span style="font:600 .72rem/1.2 var(--font-mono,ui-monospace,monospace);letter-spacing:.08em;text-transform:uppercase;color:#93c5fd;">' . $shell . '</span></header>';
        $html .= '<pre style="margin:0;padding:1rem 1.1rem 0;font:500 .84rem/1.7 var(--font-mono,ui-monospace,monospace);white-space:pre-wrap;"><code>' . htmlspecialchars($command, ENT_QUOTES, 'UTF-8') . '</code></pre>';
        if ($output !== '') {
            $html .= '<div style="padding:.85rem 1.1rem 1rem;border-top:1px solid rgba(148,163,184,.14);font:500 .8rem/1.7 var(--font-mono,ui-monospace,monospace);color:#94a3b8;white-space:pre-wrap;">' . htmlspecialchars($output, ENT_QUOTES, 'UTF-8') . '</div>';
        }
        $html .= '</section>';

        return $html;
    }

    /** @param array<string,mixed> $data */
    private function renderCodeTabs(array $data): string
    {
        $tabs = is_array($data['tabs'] ?? null) ? $data['tabs'] : [];
        if ($tabs === []) {
            return '';
        }

        $instanceId = 'editorjs-code-tabs-' . uniqid();
        $title = $this->sanitizeInline((string) ($data['title'] ?? ''));
        $buttonsHtml = '';
        $panesHtml = '';
        $renderedTabs = 0;

        foreach ($tabs as $index => $tab) {
            if (!is_array($tab)) {
                continue;
            }

            $tabId = $instanceId . '-pane-' . $index;
            $label = htmlspecialchars((string) ($tab['label'] ?? ('Tab ' . ($index + 1))), ENT_QUOTES, 'UTF-8');
            $language = htmlspecialchars((string) ($tab['language'] ?? ''), ENT_QUOTES, 'UTF-8');
            $code = trim((string) ($tab['code'] ?? ''));

            if ($code === '') {
                continue;
            }

            $isActive = $renderedTabs === 0;
            $renderedTabs++;

            $buttonsHtml .= '<button type="button" data-target="' . htmlspecialchars($tabId, ENT_QUOTES, 'UTF-8') . '" aria-pressed="' . ($isActive ? 'true' : 'false') . '" style="padding:.5rem .85rem;border:1px solid ' . ($isActive ? '#60a5fa' : 'rgba(148,163,184,.16)') . ';border-radius:999px;background:' . ($isActive ? 'rgba(59,130,246,.16)' : 'transparent') . ';color:' . ($isActive ? '#dbeafe' : '#94a3b8') . ';font:600 .75rem/1.2 var(--font-sans,system-ui,sans-serif);cursor:pointer;">' . $label . '</button>';
            $panesHtml .= '<div id="' . htmlspecialchars($tabId, ENT_QUOTES, 'UTF-8') . '" data-code-tab-pane style="display:' . ($isActive ? 'block' : 'none') . ';">'
                . '<div style="display:flex;justify-content:flex-end;padding:.65rem 1rem 0;color:#94a3b8;font:600 .68rem/1.2 var(--font-mono,ui-monospace,monospace);text-transform:uppercase;letter-spacing:.08em;">' . ($language !== '' ? $language : 'code') . '</div>'
                . '<pre style="margin:0;padding:.8rem 1rem 1rem;overflow:auto;"><code class="' . ($language !== '' ? 'language-' . $language : '') . '">' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</code></pre>'
                . '</div>';
        }

        if ($buttonsHtml === '' || $panesHtml === '') {
            return '';
        }

        $script = '(function(){var root=document.getElementById(' . json_encode($instanceId) . ');if(!root){return;}var buttons=root.querySelectorAll("[data-target]");var panes=root.querySelectorAll("[data-code-tab-pane]");buttons.forEach(function(button){button.addEventListener("click",function(){var targetId=button.getAttribute("data-target");buttons.forEach(function(item){var active=item===button;item.setAttribute("aria-pressed",active?"true":"false");item.style.borderColor=active?"#60a5fa":"rgba(148,163,184,.16)";item.style.background=active?"rgba(59,130,246,.16)":"transparent";item.style.color=active?"#dbeafe":"#94a3b8";});panes.forEach(function(pane){pane.style.display=pane.id===targetId?"block":"none";});});});})();';

        $html = '<section class="editorjs-block editorjs-code-tabs" id="' . htmlspecialchars($instanceId, ENT_QUOTES, 'UTF-8') . '" style="margin:1.5rem 0;border:1px solid #1f2937;border-radius:16px;overflow:hidden;background:#0f172a;color:#e2e8f0;">';
        if ($title !== '') {
            $html .= '<header style="padding:1rem 1rem 0;color:#f8fafc;font:700 .95rem/1.3 var(--font-sans,system-ui,sans-serif);">' . $title . '</header>';
        }
        $html .= '<div style="display:flex;flex-wrap:wrap;gap:.55rem;padding:1rem 1rem .35rem;">' . $buttonsHtml . '</div>';
        $html .= $panesHtml;
        $html .= '<script>' . $script . '</script>';
        $html .= '</section>';

        return $html;
    }

    /** @param array<string,mixed> $data */
    private function renderMermaid(array $data): string
    {
        $code = trim((string) ($data['code'] ?? ''));
        if ($code === '') {
            return '';
        }

        $instanceId = 'editorjs-mermaid-' . uniqid();
        $title = $this->sanitizeInline((string) ($data['title'] ?? ''));
        $caption = $this->sanitizeInline((string) ($data['caption'] ?? ''));
        $script = '(function(){var root=document.getElementById(' . json_encode($instanceId) . ');if(!root||!window.mermaid||typeof window.mermaid.render!=="function"){return;}var source=root.querySelector("[data-mermaid-source]");var target=root.querySelector("[data-mermaid-target]");var preview=root.querySelector("[data-mermaid-preview]");if(!source||!target){return;}try{window.mermaid.initialize({startOnLoad:false,securityLevel:"strict"});window.mermaid.render(' . json_encode($instanceId . '-svg') . ',source.textContent||"").then(function(result){target.innerHTML=result.svg;target.style.display="block";if(preview){preview.style.display="none";}}).catch(function(){});}catch(error){}})();';

        $html = '<figure class="editorjs-block editorjs-mermaid" id="' . htmlspecialchars($instanceId, ENT_QUOTES, 'UTF-8') . '" style="margin:1.5rem 0;padding:1rem 1.1rem;border:1px solid #dbe4f0;border-radius:16px;background:linear-gradient(180deg,#fff 0%,#f8fbff 100%);">';
        if ($title !== '') {
            $html .= '<div style="margin-bottom:.65rem;font:700 .95rem/1.3 var(--font-sans,system-ui,sans-serif);color:#0f172a;">' . $title . '</div>';
        }
        $html .= '<div data-mermaid-target style="display:none;overflow:auto;"></div>';
        $html .= '<pre data-mermaid-preview style="margin:0;padding:1rem;border-radius:12px;background:#0f172a;color:#dbeafe;overflow:auto;"><code data-mermaid-source class="language-mermaid">' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</code></pre>';
        if ($caption !== '') {
            $html .= '<figcaption style="margin-top:.75rem;color:#64748b;font-size:.86rem;">' . $caption . '</figcaption>';
        }
        $html .= '<script>' . $script . '</script>';
        $html .= '</figure>';

        return $html;
    }

    /** @param array<string,mixed> $data */
    private function renderApiEndpoint(array $data): string
    {
        $method = htmlspecialchars((string) ($data['method'] ?? 'GET'), ENT_QUOTES, 'UTF-8');
        $path = htmlspecialchars((string) ($data['path'] ?? ''), ENT_QUOTES, 'UTF-8');
        $summary = $this->sanitizeInline((string) ($data['summary'] ?? ''));
        $auth = $this->sanitizeInline((string) ($data['auth'] ?? ''));
        $requestExample = trim((string) ($data['requestExample'] ?? ''));
        $responseExample = trim((string) ($data['responseExample'] ?? ''));

        if ($path === '') {
            return '';
        }

        $html = '<section class="editorjs-block editorjs-api-endpoint" style="margin:1.5rem 0;padding:1rem 1.1rem;border:1px solid #dbe4f0;border-radius:16px;background:#fff;box-shadow:0 10px 26px rgba(15,23,42,.04);">';
        $html .= '<div style="display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;margin-bottom:.8rem;"><span style="display:inline-flex;align-items:center;justify-content:center;min-width:4.25rem;padding:.28rem .65rem;border-radius:999px;background:#dbeafe;color:#1d4ed8;font:700 .72rem/1.2 var(--font-sans,system-ui,sans-serif);letter-spacing:.06em;">' . $method . '</span><code style="font:700 .9rem/1.4 var(--font-mono,ui-monospace,monospace);color:#0f172a;">' . $path . '</code></div>';
        if ($summary !== '') {
            $html .= '<p style="margin:.2rem 0 .85rem;color:#475569;">' . $summary . '</p>';
        }
        if ($auth !== '') {
            $html .= '<div style="margin:0 0 .85rem;padding:.65rem .8rem;border-radius:12px;background:#f8fafc;color:#334155;font-size:.84rem;"><strong>Auth:</strong> ' . $auth . '</div>';
        }
        if ($requestExample !== '' || $responseExample !== '') {
            $html .= '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1rem;">';
            if ($requestExample !== '') {
                $html .= '<div><div style="margin-bottom:.35rem;font:700 .74rem/1.2 var(--font-sans,system-ui,sans-serif);letter-spacing:.06em;text-transform:uppercase;color:#64748b;">Request</div><pre style="margin:0;padding:.85rem 1rem;border-radius:12px;background:#0f172a;color:#dbeafe;overflow:auto;"><code>' . htmlspecialchars($requestExample, ENT_QUOTES, 'UTF-8') . '</code></pre></div>';
            }
            if ($responseExample !== '') {
                $html .= '<div><div style="margin-bottom:.35rem;font:700 .74rem/1.2 var(--font-sans,system-ui,sans-serif);letter-spacing:.06em;text-transform:uppercase;color:#64748b;">Response</div><pre style="margin:0;padding:.85rem 1rem;border-radius:12px;background:#0f172a;color:#dbeafe;overflow:auto;"><code>' . htmlspecialchars($responseExample, ENT_QUOTES, 'UTF-8') . '</code></pre></div>';
            }
            $html .= '</div>';
        }
        $html .= '</section>';

        return $html;
    }

    /** @param array<string,mixed> $data */
    private function renderChangelog(array $data): string
    {
        $title = $this->sanitizeInline((string) ($data['title'] ?? ''));
        $version = htmlspecialchars((string) ($data['version'] ?? ''), ENT_QUOTES, 'UTF-8');
        $date = htmlspecialchars((string) ($data['date'] ?? ''), ENT_QUOTES, 'UTF-8');
        $items = is_array($data['items'] ?? null) ? $data['items'] : [];

        if ($version === '' && $items === []) {
            return '';
        }

        $html = '<section class="editorjs-block editorjs-changelog" style="margin:1.5rem 0;padding:1rem 1.1rem;border:1px solid #e2e8f0;border-radius:16px;background:linear-gradient(180deg,#ffffff 0%,#f8fafc 100%);">';
        $html .= '<header style="display:flex;justify-content:space-between;align-items:flex-start;gap:1rem;flex-wrap:wrap;margin-bottom:.75rem;">';
        $html .= '<div>' . ($title !== '' ? '<strong style="display:block;color:#0f172a;">' . $title . '</strong>' : '<strong style="display:block;color:#0f172a;">Changelog</strong>') . '</div>';
        $html .= '<div style="display:flex;gap:.5rem;flex-wrap:wrap;">' . ($version !== '' ? '<span class="badge badge-b">' . $version . '</span>' : '') . ($date !== '' ? '<span class="badge">' . $date . '</span>' : '') . '</div>';
        $html .= '</header>';
        if ($items !== []) {
            $html .= '<ul class="changelog-list" style="padding:0;margin:0;">';
            foreach ($items as $item) {
                $itemText = $this->renderMarkdownInline((string) $item);
                if ($itemText === '') {
                    continue;
                }
                $html .= '<li>' . $itemText . '</li>';
            }
            $html .= '</ul>';
        }
        $html .= '</section>';

        return $html;
    }

    /** @param array<string,mixed> $data */
    private function renderProsCons(array $data): string
    {
        $title = $this->sanitizeInline((string) ($data['title'] ?? ''));
        $prosTitle = $this->sanitizeInline((string) ($data['prosTitle'] ?? 'Vorteile'));
        $consTitle = $this->sanitizeInline((string) ($data['consTitle'] ?? 'Nachteile'));
        $pros = is_array($data['pros'] ?? null) ? $data['pros'] : [];
        $cons = is_array($data['cons'] ?? null) ? $data['cons'] : [];

        if ($pros === [] && $cons === []) {
            return '';
        }

        $html = '<section class="editorjs-block editorjs-pros-cons" style="margin:1.5rem 0;">';
        if ($title !== '') {
            $html .= '<h3 style="margin:0 0 .85rem;color:#0f172a;font:700 1rem/1.35 var(--font-sans,system-ui,sans-serif);">' . $title . '</h3>';
        }
        $html .= '<div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(240px,1fr));gap:1rem;">';
        $html .= '<div style="padding:1rem;border-radius:16px;background:#f0fdf4;border:1px solid #bbf7d0;"><strong style="display:block;margin-bottom:.6rem;color:#166534;">' . ($prosTitle !== '' ? $prosTitle : 'Vorteile') . '</strong><ul style="margin:0;padding-left:1.1rem;display:grid;gap:.45rem;">';
        foreach ($pros as $item) {
            $itemText = $this->sanitizeInline((string) $item);
            if ($itemText !== '') {
                $html .= '<li>' . $itemText . '</li>';
            }
        }
        $html .= '</ul></div>';
        $html .= '<div style="padding:1rem;border-radius:16px;background:#fff7ed;border:1px solid #fdba74;"><strong style="display:block;margin-bottom:.6rem;color:#9a3412;">' . ($consTitle !== '' ? $consTitle : 'Nachteile') . '</strong><ul style="margin:0;padding-left:1.1rem;display:grid;gap:.45rem;">';
        foreach ($cons as $item) {
            $itemText = $this->sanitizeInline((string) $item);
            if ($itemText !== '') {
                $html .= '<li>' . $itemText . '</li>';
            }
        }
        $html .= '</ul></div>';
        $html .= '</div></section>';

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

    private function renderMarkdownInline(string $markdown): string
    {
        $markdown = trim($markdown);
        if ($markdown === '') {
            return '';
        }

        $escaped = htmlspecialchars($markdown, ENT_QUOTES, 'UTF-8');
        $placeholders = [];
        $placeholderIndex = 0;

        $escaped = preg_replace_callback(
            '/\[([^\]]+)\]\((https?:\/\/[^\s\)]+)\)/i',
            function (array $matches) use (&$placeholders, &$placeholderIndex): string {
                $label = $matches[1];
                $href = html_entity_decode($matches[2], ENT_QUOTES, 'UTF-8');
                if (!filter_var($href, FILTER_VALIDATE_URL)) {
                    return $matches[0];
                }

                $key = '@@MDLINK' . $placeholderIndex++ . '@@';
                $placeholders[$key] = '<a href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '" target="_blank" rel="noopener noreferrer">'
                    . $label
                    . '</a>';

                return $key;
            },
            $escaped
        ) ?? $escaped;

        $escaped = preg_replace('/`([^`]+)`/', '<code>$1</code>', $escaped) ?? $escaped;
        $escaped = preg_replace('/\*\*([^*]+)\*\*/', '<strong>$1</strong>', $escaped) ?? $escaped;
        $escaped = preg_replace('/__([^_]+)__/', '<strong>$1</strong>', $escaped) ?? $escaped;
        $escaped = preg_replace('/(?<!\*)\*([^*]+)\*(?!\*)/', '<em>$1</em>', $escaped) ?? $escaped;
        $escaped = preg_replace('/(?<!_)_([^_]+)_(?!_)/', '<em>$1</em>', $escaped) ?? $escaped;

        if ($placeholders !== []) {
            $escaped = strtr($escaped, $placeholders);
        }

        return $escaped;
    }

    private function renderPlainTextContent(string $text): string
    {
        $normalized = trim((string) preg_replace('/\r\n?/', "\n", $text));
        if ($normalized === '') {
            return '';
        }

        $paragraphs = preg_split('/\n{2,}/', $normalized) ?: [];
        $html = '';

        foreach ($paragraphs as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph === '') {
                continue;
            }

            $html .= '<p>' . nl2br(htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8')) . '</p>';
        }

        return $html;
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

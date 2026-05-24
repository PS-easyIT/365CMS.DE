<?php
/**
 * Editor.js Content Normalizer.
 *
 * Repariert gespeicherte EditorJS-Payloads und übersetzt WordPress-/HTML-Blöcke
 * in den 365CMS-EditorJS-Datenvertrag, damit Public-Rendering nicht leer läuft.
 *
 * @package CMSv2\Services\EditorJs
 */

declare(strict_types=1);

namespace CMS\Services\EditorJs;

use CMS\Json;

if (!defined('ABSPATH')) {
    exit;
}

final class EditorJsContentNormalizer
{
    /** @return array{blocks: array<int, array<string,mixed>>} */
    public static function normalize(string|array $content): array
    {
        if (is_array($content)) {
            return self::normalizeArrayPayload($content);
        }

        $raw = trim($content);
        if ($raw === '') {
            return ['blocks' => []];
        }

        $decoded = Json::decodeArray($raw, []);
        if (is_array($decoded) && $decoded !== []) {
            return self::normalizeArrayPayload($decoded);
        }

        if (str_contains($raw, '<!-- wp:') || preg_match('/\bwp-block-[a-z0-9_-]+\b/i', $raw) === 1) {
            return ['blocks' => self::normalizeBlocks(self::wordpressHtmlToBlocks($raw))];
        }

        if (str_contains($raw, '<')) {
            return ['blocks' => self::normalizeBlocks(self::htmlToBlocks($raw))];
        }

        return ['blocks' => self::textToParagraphBlocks($raw)];
    }

    /** @return array{blocks: array<int, array<string,mixed>>} */
    private static function normalizeArrayPayload(array $payload): array
    {
        $blocks = [];
        if (isset($payload['blocks']) && is_array($payload['blocks'])) {
            $blocks = $payload['blocks'];
        } elseif (array_is_list($payload)) {
            $blocks = $payload;
        }

        return ['blocks' => self::normalizeBlocks($blocks)];
    }

    /**
     * @param array<int,mixed> $blocks
     * @return array<int, array<string,mixed>>
     */
    private static function normalizeBlocks(array $blocks): array
    {
        $normalized = [];

        foreach ($blocks as $block) {
            $cleanBlock = self::normalizeBlock($block);
            if ($cleanBlock !== null) {
                $normalized[] = $cleanBlock;
            }
        }

        return $normalized;
    }

    /** @return array<string,mixed>|null */
    private static function normalizeBlock(mixed $block): ?array
    {
        if (is_string($block)) {
            $text = self::sanitizeInline($block);
            return $text !== '' ? ['type' => 'paragraph', 'data' => ['text' => $text]] : null;
        }

        if (!is_array($block)) {
            return null;
        }

        $type = self::normalizeType((string) ($block['type'] ?? $block['name'] ?? ''));
        $data = is_array($block['data'] ?? null)
            ? $block['data']
            : (is_string($block['data'] ?? null) ? ['text' => (string) $block['data']] : []);

        if ($type === '') {
            $type = self::guessTypeFromData($data);
        }

        if ($type === '') {
            return null;
        }

        $cleanBlock = ['type' => $type, 'data' => self::normalizeBlockData($type, $data)];
        if (isset($block['tunes']) && is_array($block['tunes'])) {
            $cleanBlock['tunes'] = $block['tunes'];
        }

        return $cleanBlock;
    }

    private static function normalizeType(string $type): string
    {
        $type = trim($type);
        $aliases = [
            'checklist' => 'list',
            'link' => 'linkTool',
            'gallery' => 'imageGallery',
            'image_gallery' => 'imageGallery',
            'image-gallery' => 'imageGallery',
            'media-text' => 'mediaText',
            'media_text' => 'mediaText',
            'mediatext' => 'mediaText',
            'core/media-text' => 'mediaText',
            'wp:media-text' => 'mediaText',
            'core/image' => 'image',
            'wp:image' => 'image',
            'core/paragraph' => 'paragraph',
            'wp:paragraph' => 'paragraph',
            'core/heading' => 'header',
            'heading' => 'header',
            'wp:heading' => 'header',
            'core/list' => 'list',
            'wp:list' => 'list',
            'core/quote' => 'quote',
            'wp:quote' => 'quote',
            'core/html' => 'raw',
            'html' => 'raw',
            'wp:html' => 'raw',
            'core/separator' => 'delimiter',
            'separator' => 'delimiter',
            'wp:separator' => 'delimiter',
            'space' => 'spacer',
        ];

        return $aliases[$type] ?? $aliases[strtolower($type)] ?? $type;
    }

    private static function guessTypeFromData(array $data): string
    {
        if (isset($data['file']) || isset($data['url'])) {
            return 'image';
        }
        if (isset($data['html'])) {
            return 'raw';
        }
        if (isset($data['items'])) {
            return 'list';
        }
        if (isset($data['text']) || isset($data['content'])) {
            return 'paragraph';
        }

        return '';
    }

    /** @return array<string,mixed> */
    private static function normalizeBlockData(string $type, array $data): array
    {
        return match ($type) {
            'paragraph' => self::normalizeTextBlockData($data),
            'header' => [
                'text' => self::sanitizeInline((string) ($data['text'] ?? $data['content'] ?? '')),
                'level' => max(1, min(6, (int) ($data['level'] ?? 2))),
                'alignment' => self::normalizeTextAlignment((string) ($data['alignment'] ?? 'left')),
                'spacing' => self::normalizeTextSpacing((string) ($data['spacing'] ?? 'normal')),
            ],
            'image' => self::normalizeImageData($data),
            'mediaText' => self::normalizeMediaTextData($data),
            'list' => self::normalizeListData($data),
            'quote' => [
                'text' => self::sanitizeInline((string) ($data['text'] ?? $data['content'] ?? '')),
                'caption' => self::sanitizeInline((string) ($data['caption'] ?? $data['cite'] ?? '')),
                'alignment' => in_array(($data['alignment'] ?? 'left'), ['left', 'center'], true) ? (string) $data['alignment'] : 'left',
            ],
            'raw' => ['html' => EditorJsHtmlSanitizer::sanitizeRawBlock((string) ($data['html'] ?? $data['content'] ?? $data['text'] ?? ''))],
            'delimiter' => [],
            default => $data,
        };
    }

    /** @return array<string,mixed> */
    private static function normalizeTextBlockData(array $data): array
    {
        return [
            'text' => self::sanitizeInline((string) ($data['text'] ?? $data['content'] ?? $data['html'] ?? '')),
            'alignment' => self::normalizeTextAlignment((string) ($data['alignment'] ?? 'left')),
            'spacing' => self::normalizeTextSpacing((string) ($data['spacing'] ?? 'normal')),
        ];
    }

    /** @return array<string,mixed> */
    private static function normalizeImageData(array $data): array
    {
        $file = is_array($data['file'] ?? null) ? $data['file'] : [];
        $url = (string) ($file['url'] ?? $data['url'] ?? $data['src'] ?? '');
        $caption = (string) ($data['caption'] ?? $data['alt'] ?? '');
        $alignment = (string) ($data['alignment'] ?? $data['align'] ?? 'center');
        $size = (string) ($data['size'] ?? $data['widthPreset'] ?? 'normal');
        $borderStyle = (string) ($data['borderStyle'] ?? (!empty($data['withBorder']) ? 'thin' : 'none'));
        if (!in_array($alignment, ['left', 'center', 'right'], true)) {
            $alignment = 'center';
        }
        if (!empty($data['stretched'])) {
            $size = 'full';
        }
        if (!in_array($size, ['normal', 'wide', 'full'], true)) {
            $size = 'normal';
        }
        if (!in_array($borderStyle, ['none', 'thin', 'medium', 'thick'], true)) {
            $borderStyle = !empty($data['withBorder']) ? 'thin' : 'none';
        }

        $normalized = [
            'file' => array_merge($file, ['url' => $url]),
            'caption' => self::sanitizeInline($caption),
            'alignment' => $alignment,
            'size' => $size,
            'widthPreset' => $size,
            'borderStyle' => $borderStyle,
            'withBorder' => $borderStyle !== 'none',
            'withBackground' => !empty($data['withBackground']),
            'stretched' => $size === 'full' || !empty($data['stretched']),
            'shadow' => !empty($data['shadow']),
        ];

        if (array_key_exists('rounded', $data)) {
            $normalized['rounded'] = !empty($data['rounded']);
        }

        return $normalized;
    }

    /** @return array<string,mixed> */
    private static function normalizeMediaTextData(array $data): array
    {
        $file = is_array($data['file'] ?? null) ? $data['file'] : [];
        $position = (string) ($data['imagePosition'] ?? $data['position'] ?? $data['mediaPosition'] ?? 'left');
        $width = (string) ($data['imageWidth'] ?? $data['mediaWidth'] ?? '40');

        return [
            'file' => array_merge($file, ['url' => (string) ($file['url'] ?? $data['url'] ?? $data['src'] ?? '')]),
            'alt' => strip_tags((string) ($data['alt'] ?? $data['caption'] ?? ''), ''),
            'text' => self::sanitizeMediaTextContent((string) ($data['text'] ?? $data['content'] ?? '')),
            'imagePosition' => in_array($position, ['left', 'right'], true) ? $position : 'left',
            'imageWidth' => self::normalizeMediaWidth($width),
        ];
    }

    /** @return array<string,mixed> */
    private static function normalizeListData(array $data): array
    {
        $style = (string) ($data['style'] ?? 'unordered');
        if (!in_array($style, ['ordered', 'unordered', 'checklist'], true)) {
            $style = 'unordered';
        }

        $items = is_array($data['items'] ?? null) ? $data['items'] : (is_array($data['content'] ?? null) ? $data['content'] : []);

        return [
            'style' => $style,
            'meta' => self::normalizeListMeta($style, is_array($data['meta'] ?? null) ? $data['meta'] : []),
            'items' => self::normalizeListItems($items, $style),
        ];
    }

    /** @param array<int,mixed> $items @return array<int,array<string,mixed>> */
    private static function normalizeListItems(array $items, string $style = 'unordered'): array
    {
        $normalized = [];
        foreach ($items as $item) {
            if (is_string($item)) {
                $normalized[] = [
                    'content' => self::sanitizeInline($item),
                    'meta' => self::normalizeListMeta($style, []),
                    'items' => [],
                ];
                continue;
            }
            if (is_array($item)) {
                $normalized[] = [
                    'content' => self::sanitizeInline((string) ($item['content'] ?? $item['text'] ?? '')),
                    'meta' => self::normalizeListMeta($style, is_array($item['meta'] ?? null) ? $item['meta'] : (!empty($item['checked']) ? ['checked' => true] : [])),
                    'items' => self::normalizeListItems(is_array($item['items'] ?? null) ? $item['items'] : [], $style),
                ];
            }
        }

        return $normalized;
    }

    /** @return array<string,mixed> */
    private static function normalizeListMeta(string $style, array $meta): array
    {
        $counterType = (string) ($meta['counterType'] ?? 'numeric');

        return match ($style) {
            'ordered' => [
                'start' => max(1, (int) ($meta['start'] ?? 1)),
                'counterType' => in_array($counterType, ['numeric', 'lower-roman', 'upper-roman', 'lower-alpha', 'upper-alpha'], true)
                    ? $counterType
                    : 'numeric',
            ],
            'checklist' => ['checked' => !empty($meta['checked'])],
            default => [],
        };
    }

    /** @return array<int,array<string,mixed>> */
    private static function wordpressHtmlToBlocks(string $html): array
    {
        $blocks = [];
        $offset = 0;
        $pattern = '/<!--\s+wp:([a-z0-9_\-\/]+)(\s+(\{.*?\}))?\s*-->(.*?)<!--\s+\/wp:\1\s+-->/is';

        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) === 0) {
            return self::wordpressSelfClosingHtmlToBlocks($html);
        }

        foreach ($matches as $match) {
            $start = (int) $match[0][1];
            if ($start > $offset) {
                $blocks = array_merge($blocks, self::wordpressSelfClosingHtmlToBlocks(substr($html, $offset, $start - $offset)));
            }

            $name = self::normalizeType((string) $match[1][0]);
            $attrs = isset($match[3][0]) && trim((string) $match[3][0]) !== ''
                ? Json::decodeArray((string) $match[3][0], [])
                : [];
            $innerHtml = (string) ($match[4][0] ?? '');
            $blocks = array_merge($blocks, self::wordpressBlockToBlocks($name, is_array($attrs) ? $attrs : [], $innerHtml));
            $offset = $start + strlen((string) $match[0][0]);
        }

        if ($offset < strlen($html)) {
            $blocks = array_merge($blocks, self::wordpressSelfClosingHtmlToBlocks(substr($html, $offset)));
        }

        return $blocks;
    }

    /** @return array<int,array<string,mixed>> */
    private static function wordpressSelfClosingHtmlToBlocks(string $html): array
    {
        $blocks = [];
        $offset = 0;
        $pattern = '/<!--\s+wp:([a-z0-9_\-\/]+)(\s+(\{.*?\}))?\s+\/-->/is';

        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE) === 0) {
            return self::htmlToBlocks($html);
        }

        foreach ($matches as $match) {
            $start = (int) $match[0][1];
            if ($start > $offset) {
                $blocks = array_merge($blocks, self::htmlToBlocks(substr($html, $offset, $start - $offset)));
            }

            $name = self::normalizeType((string) $match[1][0]);
            $attrs = isset($match[3][0]) && trim((string) $match[3][0]) !== ''
                ? Json::decodeArray((string) $match[3][0], [])
                : [];
            $blocks = array_merge($blocks, self::wordpressSelfClosingBlockToBlocks($name, is_array($attrs) ? $attrs : []));
            $offset = $start + strlen((string) $match[0][0]);
        }

        if ($offset < strlen($html)) {
            $blocks = array_merge($blocks, self::htmlToBlocks(substr($html, $offset)));
        }

        return $blocks;
    }

    /** @return array<int,array<string,mixed>> */
    private static function wordpressSelfClosingBlockToBlocks(string $type, array $attrs): array
    {
        if ($type === 'image') {
            $image = self::normalizeImageData([
                'file' => ['url' => (string) ($attrs['url'] ?? $attrs['src'] ?? '')],
                'caption' => (string) ($attrs['caption'] ?? $attrs['alt'] ?? ''),
                'alt' => (string) ($attrs['alt'] ?? ''),
            ]);

            return ($image['file']['url'] ?? '') !== '' ? [['type' => 'image', 'data' => $image]] : [];
        }

        if ($type === 'delimiter') {
            return [['type' => 'delimiter', 'data' => []]];
        }

        if ($type === 'spacer') {
            return [['type' => 'spacer', 'data' => ['height' => max(0, min(200, (int) ($attrs['height'] ?? 40)))]]];
        }

        return [];
    }

    /** @return array<int,array<string,mixed>> */
    private static function wordpressBlockToBlocks(string $type, array $attrs, string $innerHtml): array
    {
        if ($type === 'mediaText') {
            return [self::extractMediaTextBlock($innerHtml, $attrs)];
        }
        if ($type === 'image') {
            $image = self::extractImageData($innerHtml);
            if (($image['file']['url'] ?? '') !== '') {
                return [['type' => 'image', 'data' => $image]];
            }
        }
        if ($type === 'raw') {
            return [['type' => 'raw', 'data' => ['html' => $innerHtml]]];
        }
        if ($type === 'delimiter') {
            return [['type' => 'delimiter', 'data' => []]];
        }

        return self::htmlToBlocks($innerHtml);
    }

    /** @return array<int,array<string,mixed>> */
    private static function htmlToBlocks(string $html): array
    {
        $html = trim(preg_replace('/<!--\s*\/?wp:[^>]*-->/i', '', $html) ?? $html);
        if ($html === '') {
            return [];
        }
        if (!class_exists(\DOMDocument::class)) {
            return [['type' => 'raw', 'data' => ['html' => EditorJsHtmlSanitizer::sanitizeRawBlock($html)]]];
        }

        $doc = self::createDomDocument($html);
        $root = self::getNormalizerRoot($doc);
        if ($root === null) {
            return [];
        }

        $blocks = [];
        foreach ($root->childNodes as $child) {
            $blocks = array_merge($blocks, self::domNodeToBlocks($child));
        }

        return $blocks;
    }

    /** @return array<int,array<string,mixed>> */
    private static function domNodeToBlocks(\DOMNode $node): array
    {
        if ($node instanceof \DOMText) {
            $text = trim($node->textContent ?? '');
            return $text !== '' ? [['type' => 'paragraph', 'data' => ['text' => self::sanitizeInline($text)]]] : [];
        }
        if (!$node instanceof \DOMElement) {
            return [];
        }

        $tag = strtolower($node->tagName);
        $class = ' ' . strtolower($node->getAttribute('class')) . ' ';

        if (str_contains($class, ' wp-block-media-text ')) {
            return [self::extractMediaTextBlockFromElement($node, [])];
        }
        if (preg_match('/^h([1-6])$/', $tag, $levelMatch) === 1) {
            return [['type' => 'header', 'data' => ['text' => self::sanitizeInline($node->textContent ?? ''), 'level' => (int) $levelMatch[1]]]];
        }
        if ($tag === 'p') {
            $html = self::innerHtml($node);
            $text = self::sanitizeInline($html !== '' ? $html : ($node->textContent ?? ''));
            return $text !== '' ? [['type' => 'paragraph', 'data' => ['text' => $text]]] : [];
        }
        if ($tag === 'img') {
            return [['type' => 'image', 'data' => self::imageDataFromElement($node)]];
        }
        if ($tag === 'figure') {
            $image = self::firstElementByTag($node, 'img');
            if ($image instanceof \DOMElement) {
                $data = self::imageDataFromElement($image);
                $caption = self::firstElementByTag($node, 'figcaption');
                if ($caption instanceof \DOMElement) {
                    $data['caption'] = self::sanitizeInline(self::innerHtml($caption));
                }
                return [['type' => 'image', 'data' => $data]];
            }
        }
        if ($tag === 'ul' || $tag === 'ol') {
            return [['type' => 'list', 'data' => ['style' => $tag === 'ol' ? 'ordered' : 'unordered', 'items' => self::listElementToItems($node)]]];
        }
        if ($tag === 'blockquote') {
            return [['type' => 'quote', 'data' => ['text' => self::sanitizeInline(self::innerHtml($node)), 'caption' => '', 'alignment' => 'left']]];
        }
        if ($tag === 'pre') {
            return [['type' => 'code', 'data' => ['code' => (string) $node->textContent]]];
        }
        if ($tag === 'hr') {
            return [['type' => 'delimiter', 'data' => []]];
        }
        if (in_array($tag, ['div', 'section', 'article', 'main'], true)) {
            $blocks = [];
            foreach ($node->childNodes as $child) {
                $blocks = array_merge($blocks, self::domNodeToBlocks($child));
            }
            if ($blocks !== []) {
                return $blocks;
            }
        }

        $raw = EditorJsHtmlSanitizer::sanitizeRawBlock(self::outerHtml($node));
        return $raw !== '' ? [['type' => 'raw', 'data' => ['html' => $raw]]] : [];
    }

    /** @return array<string,mixed> */
    private static function extractMediaTextBlock(string $html, array $attrs): array
    {
        $doc = self::createDomDocument($html);
        $root = self::getNormalizerRoot($doc);
        if ($root === null) {
            return ['type' => 'mediaText', 'data' => self::normalizeMediaTextData($attrs)];
        }

        foreach ($root->getElementsByTagName('*') as $element) {
            if ($element instanceof \DOMElement && str_contains(' ' . strtolower($element->getAttribute('class')) . ' ', ' wp-block-media-text ')) {
                return self::extractMediaTextBlockFromElement($element, $attrs);
            }
        }

        return self::extractMediaTextBlockFromElement($root, $attrs);
    }

    /** @return array<string,mixed> */
    private static function extractMediaTextBlockFromElement(\DOMElement $element, array $attrs): array
    {
        $image = self::firstElementByTag($element, 'img');
        $contentElement = self::firstElementByClass($element, 'wp-block-media-text__content');
        $class = ' ' . strtolower($element->getAttribute('class')) . ' ';
        $position = str_contains($class, ' has-media-on-the-right ') ? 'right' : (string) ($attrs['mediaPosition'] ?? $attrs['imagePosition'] ?? 'left');
        $width = self::normalizeMediaWidth((string) ($attrs['mediaWidth'] ?? $attrs['imageWidth'] ?? self::extractWidthFromStyle($element->getAttribute('style'))));
        $text = $contentElement instanceof \DOMElement ? self::innerHtml($contentElement) : self::innerTextWithoutFirstImage($element);

        return [
            'type' => 'mediaText',
            'data' => self::normalizeMediaTextData([
                'file' => ['url' => $image instanceof \DOMElement ? $image->getAttribute('src') : ''],
                'alt' => $image instanceof \DOMElement ? $image->getAttribute('alt') : '',
                'text' => $text,
                'imagePosition' => $position,
                'imageWidth' => $width,
            ]),
        ];
    }

    /** @return array<string,mixed> */
    private static function extractImageData(string $html): array
    {
        $doc = self::createDomDocument($html);
        $root = self::getNormalizerRoot($doc);
        $image = $root instanceof \DOMElement ? self::firstElementByTag($root, 'img') : null;

        return $image instanceof \DOMElement ? self::imageDataFromElement($image) : self::normalizeImageData([]);
    }

    /** @return array<string,mixed> */
    private static function imageDataFromElement(\DOMElement $image): array
    {
        return self::normalizeImageData([
            'file' => ['url' => $image->getAttribute('src')],
            'alt' => $image->getAttribute('alt'),
            'caption' => $image->getAttribute('alt'),
        ]);
    }

    /** @return array<int,array<string,mixed>> */
    private static function textToParagraphBlocks(string $text): array
    {
        $blocks = [];
        foreach (preg_split('/\n{2,}/', str_replace("\r", '', $text)) ?: [] as $paragraph) {
            $paragraph = trim($paragraph);
            if ($paragraph !== '') {
                $blocks[] = ['type' => 'paragraph', 'data' => ['text' => nl2br(htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8'))]];
            }
        }

        return $blocks;
    }

    /** @return array<int,array<string,mixed>> */
    private static function listElementToItems(\DOMElement $list): array
    {
        $items = [];
        foreach ($list->childNodes as $child) {
            if (!$child instanceof \DOMElement || strtolower($child->tagName) !== 'li') {
                continue;
            }

            $clone = $child->cloneNode(true);
            $nested = [];
            if ($clone instanceof \DOMElement) {
                foreach (iterator_to_array($clone->childNodes) as $nestedChild) {
                    if (!$nestedChild instanceof \DOMElement || !in_array(strtolower($nestedChild->tagName), ['ul', 'ol'], true)) {
                        continue;
                    }
                    $nested = array_merge($nested, self::listElementToItems($nestedChild));
                    $nestedChild->parentNode?->removeChild($nestedChild);
                }
            }

            $items[] = ['content' => self::sanitizeInline($clone instanceof \DOMElement ? self::innerHtml($clone) : self::innerHtml($child)), 'items' => $nested];
        }

        return $items;
    }

    private static function normalizeTextAlignment(string $alignment): string
    {
        return in_array($alignment, ['left', 'center', 'right', 'justify'], true) ? $alignment : 'left';
    }

    private static function normalizeTextSpacing(string $spacing): string
    {
        return in_array($spacing, ['compact', 'normal', 'relaxed', 'loose'], true) ? $spacing : 'normal';
    }

    private static function createDomDocument(string $html): \DOMDocument
    {
        $doc = new \DOMDocument('1.0', 'UTF-8');
        $previousState = libxml_use_internal_errors(true);
        @$doc->loadHTML('<!DOCTYPE html><html><head><meta charset="UTF-8"></head><body><div id="cms-editorjs-normalizer-root">' . $html . '</div></body></html>', LIBXML_HTML_NODEFDTD | LIBXML_COMPACT);
        libxml_clear_errors();
        libxml_use_internal_errors($previousState);

        return $doc;
    }

    private static function getNormalizerRoot(\DOMDocument $doc): ?\DOMElement
    {
        $node = (new \DOMXPath($doc))->query('//*[@id="cms-editorjs-normalizer-root"]')->item(0);
        return $node instanceof \DOMElement ? $node : null;
    }

    private static function firstElementByTag(\DOMElement $root, string $tag): ?\DOMElement
    {
        $node = $root->getElementsByTagName($tag)->item(0);
        return $node instanceof \DOMElement ? $node : null;
    }

    private static function firstElementByClass(\DOMElement $root, string $className): ?\DOMElement
    {
        foreach ($root->getElementsByTagName('*') as $node) {
            if ($node instanceof \DOMElement && str_contains(' ' . $node->getAttribute('class') . ' ', ' ' . $className . ' ')) {
                return $node;
            }
        }

        return null;
    }

    private static function innerHtml(\DOMNode $node): string
    {
        $html = '';
        foreach ($node->childNodes as $child) {
            $html .= $node->ownerDocument?->saveHTML($child) ?: '';
        }

        return trim($html);
    }

    private static function outerHtml(\DOMNode $node): string
    {
        return $node->ownerDocument?->saveHTML($node) ?: '';
    }

    private static function innerTextWithoutFirstImage(\DOMElement $element): string
    {
        $clone = $element->cloneNode(true);
        if ($clone instanceof \DOMElement) {
            $image = self::firstElementByTag($clone, 'img');
            if ($image instanceof \DOMElement && $image->parentNode !== null) {
                $image->parentNode->removeChild($image);
            }

            return self::innerHtml($clone);
        }

        return '';
    }

    private static function extractWidthFromStyle(string $style): string
    {
        return preg_match('/(?:grid-template-columns|width)\s*:\s*([0-9]{1,3})%/i', $style, $match) === 1 ? $match[1] : '40';
    }

    private static function normalizeMediaWidth(string $width): string
    {
        $value = (int) preg_replace('/[^0-9]/', '', $width);
        if ($value <= 0) {
            return '40';
        }
        if ($value <= 36) {
            return '33';
        }
        if ($value >= 46) {
            return '50';
        }

        return '40';
    }

    private static function sanitizeInline(string $html): string
    {
        return EditorJsHtmlSanitizer::sanitizeInline($html);
    }

    private static function sanitizeMediaTextContent(string $html): string
    {
        return EditorJsHtmlSanitizer::sanitizeRawBlock($html);
    }
}
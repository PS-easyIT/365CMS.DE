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

        $rawType = (string) ($block['type'] ?? $block['name'] ?? '');
        $type = self::normalizeType($rawType);
        $data = is_array($block['data'] ?? null)
            ? $block['data']
            : (is_string($block['data'] ?? null) ? ['text' => (string) $block['data']] : []);

        if (trim($rawType) === 'checklist' && $type === 'list' && !isset($data['style'])) {
            $data['style'] = 'checklist';
        }

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
            'core/gallery' => 'imageGallery',
            'wp:gallery' => 'imageGallery',
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
            'imageGallery' => self::normalizeGalleryData($data),
            'mediaText' => self::normalizeMediaTextData($data),
            'list' => self::normalizeListData($data),
            'quote' => [
                'text' => self::sanitizeInline((string) ($data['text'] ?? $data['content'] ?? '')),
                'caption' => self::sanitizeInline((string) ($data['caption'] ?? $data['cite'] ?? '')),
                'alignment' => in_array(($data['alignment'] ?? 'left'), ['left', 'center'], true) ? (string) $data['alignment'] : 'left',
                'design' => in_array(($data['design'] ?? 'bar'), ['bar', 'card', 'minimal', 'mark'], true) ? (string) $data['design'] : 'bar',
            ],
            'raw' => ['html' => EditorJsHtmlSanitizer::sanitizeRawBlock((string) ($data['html'] ?? $data['content'] ?? $data['text'] ?? ''))],
            'delimiter' => self::normalizeDelimiterData($data),
            'spacer' => self::normalizeSpacerData($data),
            default => $data,
        };
    }

    /** @return array<string,mixed> */
    private static function normalizeSpacerData(array $data): array
    {
        $allowedHeights = [0, 8, 10, 15, 16, 24, 25, 32, 40, 48, 56, 60, 64, 72, 75, 80, 96, 100, 120, 140, 150, 160, 180, 200];
        $presetMap = [
            'none' => 0,
            'xs' => 8,
            'small' => 15,
            'sm' => 15,
            'medium' => 40,
            'md' => 40,
            'normal' => 40,
            'large' => 75,
            'lg' => 75,
            'xl' => 100,
            'xlarge' => 100,
            'huge' => 140,
            'xxl' => 160,
        ];

        $raw = (string) ($data['height'] ?? $data['size'] ?? $data['value'] ?? $data['spacer'] ?? $data['space'] ?? $data['preset'] ?? '40');
        $key = strtolower(preg_replace('/\s+/', '', trim($raw)) ?? trim($raw));
        $height = $presetMap[$key] ?? (int) preg_replace('/[^0-9]/', '', $key);
        if (!in_array($height, $allowedHeights, true)) {
            $height = max(0, min(200, $height));
        }

        return [
            'height' => $height,
            'preset' => $height . 'px',
        ];
    }

    /** @return array<string,mixed> */
    private static function normalizeDelimiterData(array $data): array
    {
        $style = strtolower((string) ($data['style'] ?? $data['type'] ?? 'line'));
        $lineWidth = (int) ($data['lineWidth'] ?? $data['width'] ?? 35);
        $lineThickness = (int) ($data['lineThickness'] ?? $data['thickness'] ?? 2);

        if (!in_array($style, ['star', 'dash', 'line'], true)) {
            $style = 'line';
        }
        if (!in_array($lineWidth, [8, 15, 25, 35, 50, 60, 100], true)) {
            $lineWidth = max(8, min(100, $lineWidth));
        }
        if (!in_array($lineThickness, [1, 2, 3, 4, 5, 6], true)) {
            $lineThickness = max(1, min(6, $lineThickness));
        }

        return $style === 'line'
            ? ['style' => $style, 'lineWidth' => $lineWidth, 'lineThickness' => $lineThickness]
            : ['style' => $style];
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
            'imageFit' => self::normalizeImageFit($data['imageFit'] ?? $data['objectFit'] ?? $data['fit'] ?? 'contain', 'contain'),
            'objectFit' => self::normalizeImageFit($data['imageFit'] ?? $data['objectFit'] ?? $data['fit'] ?? 'contain', 'contain'),
            'maxHeight' => self::normalizeImageMaxHeight($data['maxHeight'] ?? $data['imageMaxHeight'] ?? $data['max_height'] ?? 0),
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
            'heading' => self::truncatePlainText(strip_tags((string) ($data['heading'] ?? $data['title'] ?? $data['headline'] ?? ''), ''), 180),
            'text' => self::sanitizeMediaTextContent((string) ($data['text'] ?? $data['content'] ?? '')),
            'imagePosition' => in_array($position, ['left', 'right'], true) ? $position : 'left',
            'imageWidth' => self::normalizeMediaWidth($width),
            'imageFit' => self::normalizeImageFit($data['imageFit'] ?? $data['objectFit'] ?? $data['fit'] ?? 'cover', 'cover'),
            'objectFit' => self::normalizeImageFit($data['imageFit'] ?? $data['objectFit'] ?? $data['fit'] ?? 'cover', 'cover'),
            'verticalAlignment' => self::normalizeMediaTextVerticalAlignment($data['verticalAlignment'] ?? $data['mediaVerticalAlignment'] ?? $data['imageVerticalAlignment'] ?? $data['verticalAlign'] ?? 'top'),
            'showBorder' => filter_var($data['showBorder'] ?? $data['border'] ?? $data['hasBorder'] ?? false, FILTER_VALIDATE_BOOLEAN),
            'spacingTop' => self::normalizeMediaTextSpacing($data['spacingTop'] ?? $data['marginTop'] ?? $data['blockSpacingTop'] ?? 10),
            'spacingBottom' => self::normalizeMediaTextSpacing($data['spacingBottom'] ?? $data['marginBottom'] ?? $data['blockSpacingBottom'] ?? 10),
        ];
    }

    /** @return array<string,mixed> */
    private static function normalizeGalleryData(array $data): array
    {
        $columns = (int) ($data['columns'] ?? $data['cols'] ?? $data['columnCount'] ?? 3);
        $borderStyle = (string) ($data['borderStyle'] ?? (!empty($data['withBorder']) ? 'thin' : 'none'));
        if (!in_array($columns, [2, 3, 4, 5, 6], true)) {
            $columns = 3;
        }
        if (!in_array($borderStyle, ['none', 'thin', 'medium', 'thick'], true)) {
            $borderStyle = !empty($data['withBorder']) ? 'thin' : 'none';
        }

        $sources = [];
        foreach (['images', 'items', 'files', 'gallery'] as $key) {
            if (isset($data[$key]) && is_array($data[$key])) {
                $sources = array_merge($sources, $data[$key]);
            }
        }
        if (isset($data['urls']) && is_array($data['urls'])) {
            $sources = array_merge($sources, $data['urls']);
        }

        $images = [];
        $seen = [];
        foreach ($sources as $item) {
            $image = self::normalizeGalleryItem($item);
            $url = (string) ($image['file']['url'] ?? '');
            if ($url === '' || isset($seen[$url])) {
                continue;
            }

            $seen[$url] = true;
            $images[] = $image;
        }

        return [
            'columns' => $columns,
            'borderStyle' => $borderStyle,
            'withBorder' => $borderStyle !== 'none',
            'images' => $images,
            'urls' => array_values(array_map(static fn(array $item): string => (string) ($item['file']['url'] ?? ''), $images)),
        ];
    }

    /** @return array{file:array<string,mixed>,caption:string} */
    private static function normalizeGalleryItem(mixed $item): array
    {
        if (is_string($item)) {
            return [
                'file' => ['url' => trim($item)],
                'caption' => '',
            ];
        }

        if (!is_array($item)) {
            return ['file' => ['url' => ''], 'caption' => ''];
        }

        $file = is_array($item['file'] ?? null) ? $item['file'] : [];
        $url = (string) ($file['url'] ?? $item['url'] ?? $item['src'] ?? $item['source'] ?? '');

        return [
            'file' => array_merge($file, ['url' => trim($url)]),
            'caption' => self::sanitizeInline((string) ($item['caption'] ?? $item['alt'] ?? $item['title'] ?? '')),
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
        if ($type === 'imageGallery') {
            $gallery = self::normalizeGalleryData($attrs);
            return ($gallery['images'] ?? []) !== [] ? [['type' => 'imageGallery', 'data' => $gallery]] : [];
        }

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
        if ($type === 'imageGallery') {
            return [self::extractGalleryBlock($innerHtml, $attrs)];
        }
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

        if (self::isGalleryClass($class)) {
            return [self::extractGalleryBlockFromElement($node, [])];
        }
        if (self::isMediaTextClass($class)) {
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
            if ($element instanceof \DOMElement && self::isMediaTextClass(' ' . strtolower($element->getAttribute('class')) . ' ')) {
                return self::extractMediaTextBlockFromElement($element, $attrs);
            }
        }

        return self::extractMediaTextBlockFromElement($root, $attrs);
    }

    /** @return array<string,mixed> */
    private static function extractGalleryBlock(string $html, array $attrs): array
    {
        $doc = self::createDomDocument($html);
        $root = self::getNormalizerRoot($doc);
        if ($root === null) {
            return ['type' => 'imageGallery', 'data' => self::normalizeGalleryData($attrs)];
        }

        foreach ($root->getElementsByTagName('*') as $element) {
            if (!$element instanceof \DOMElement) {
                continue;
            }

            if (self::isGalleryClass(' ' . strtolower($element->getAttribute('class')) . ' ')) {
                return self::extractGalleryBlockFromElement($element, $attrs);
            }
        }

        return self::extractGalleryBlockFromElement($root, $attrs);
    }

    /** @return array<string,mixed> */
    private static function extractGalleryBlockFromElement(\DOMElement $element, array $attrs): array
    {
        $items = [];
        foreach ($element->getElementsByTagName('img') as $image) {
            if (!$image instanceof \DOMElement) {
                continue;
            }

            $url = trim($image->getAttribute('src'));
            if ($url === '') {
                $url = trim($image->getAttribute('data-src'));
            }
            if ($url === '') {
                continue;
            }

            $figure = self::closestElementByTag($image, 'figure', $element);
            $caption = $figure instanceof \DOMElement ? self::firstElementByTag($figure, 'figcaption') : null;
            $items[] = [
                'file' => ['url' => $url],
                'caption' => $caption instanceof \DOMElement ? self::innerHtml($caption) : $image->getAttribute('alt'),
            ];
        }

        $attrs['images'] = array_merge(is_array($attrs['images'] ?? null) ? $attrs['images'] : [], $items);

        return ['type' => 'imageGallery', 'data' => self::normalizeGalleryData($attrs)];
    }

    /** @return array<string,mixed> */
    private static function extractMediaTextBlockFromElement(\DOMElement $element, array $attrs): array
    {
        $image = self::firstElementByTag($element, 'img');
        $contentElement = self::firstElementByClass($element, 'wp-block-media-text__content')
            ?? self::firstElementByClass($element, 'editorjs-media-text__content');
        $headingElement = self::firstElementByClass($element, 'editorjs-media-text__heading');
        $class = ' ' . strtolower($element->getAttribute('class')) . ' ';
        $position = self::resolveMediaTextPosition($element, $attrs, $class);
        $width = self::resolveMediaTextWidth($element, $attrs);
        $imageFit = self::resolveMediaTextImageFit($element, $attrs, $class);
        $verticalAlignment = self::resolveMediaTextVerticalAlignment($element, $attrs, $class);
        $spacingTop = (string) ($attrs['spacingTop'] ?? $attrs['marginTop'] ?? $attrs['blockSpacingTop'] ?? '');
        if ($spacingTop === '') {
            $spacingTop = $element->getAttribute('data-spacing-top');
        }
        if ($spacingTop === '') {
            $spacingTop = self::resolveMediaTextSpacingFromClass($class, 'top');
        }
        if ($spacingTop === '') {
            $spacingTop = '10';
        }

        $spacingBottom = (string) ($attrs['spacingBottom'] ?? $attrs['marginBottom'] ?? $attrs['blockSpacingBottom'] ?? '');
        if ($spacingBottom === '') {
            $spacingBottom = $element->getAttribute('data-spacing-bottom');
        }
        if ($spacingBottom === '') {
            $spacingBottom = self::resolveMediaTextSpacingFromClass($class, 'bottom');
        }
        if ($spacingBottom === '') {
            $spacingBottom = '10';
        }
        $text = $contentElement instanceof \DOMElement ? self::innerHtml($contentElement) : self::innerTextWithoutFirstImage($element);

        return [
            'type' => 'mediaText',
            'data' => self::normalizeMediaTextData([
                'file' => ['url' => $image instanceof \DOMElement ? $image->getAttribute('src') : ''],
                'alt' => $image instanceof \DOMElement ? $image->getAttribute('alt') : '',
                'heading' => $headingElement instanceof \DOMElement ? trim((string) $headingElement->textContent) : (string) ($attrs['heading'] ?? ''),
                'text' => $text,
                'imagePosition' => $position,
                'imageWidth' => $width,
                'imageFit' => $imageFit,
                'verticalAlignment' => $verticalAlignment,
                'showBorder' => !empty($attrs['showBorder']) || str_contains($class, ' editorjs-media-text--bordered '),
                'spacingTop' => $spacingTop,
                'spacingBottom' => $spacingBottom,
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
        $className = strtolower($className);

        foreach ($root->getElementsByTagName('*') as $node) {
            if ($node instanceof \DOMElement && str_contains(' ' . strtolower($node->getAttribute('class')) . ' ', ' ' . $className . ' ')) {
                return $node;
            }
        }

        return null;
    }

    private static function closestElementByTag(\DOMElement $node, string $tagName, \DOMElement $stopAt): ?\DOMElement
    {
        $tagName = strtolower($tagName);
        $current = $node;
        while ($current->parentNode instanceof \DOMElement) {
            $current = $current->parentNode;
            if (strtolower($current->tagName) === $tagName) {
                return $current;
            }
            if ($current === $stopAt) {
                break;
            }
        }

        return null;
    }

    private static function isGalleryClass(string $class): bool
    {
        return str_contains($class, ' wp-block-gallery ')
            || str_contains($class, ' editorjs-gallery ')
            || str_contains($class, ' blocks-gallery-grid ')
            || str_contains($class, ' gallery ')
            || str_contains($class, ' gallery-grid ')
            || str_contains($class, ' tiled-gallery ');
    }

    private static function isMediaTextClass(string $class): bool
    {
        return str_contains($class, ' wp-block-media-text ')
            || str_contains($class, ' editorjs-media-text ');
    }

    /** @param array<string,mixed> $attrs */
    private static function resolveMediaTextPosition(\DOMElement $element, array $attrs, string $class): string
    {
        $position = (string) ($attrs['mediaPosition'] ?? $attrs['imagePosition'] ?? $element->getAttribute('data-image-position') ?: 'left');

        if (str_contains($class, ' has-media-on-the-right ') || str_contains($class, ' editorjs-media-text--image-right ')) {
            $position = 'right';
        }

        return in_array($position, ['left', 'right'], true) ? $position : 'left';
    }

    /** @param array<string,mixed> $attrs */
    private static function resolveMediaTextWidth(\DOMElement $element, array $attrs): string
    {
        $width = (string) ($attrs['mediaWidth'] ?? $attrs['imageWidth'] ?? $element->getAttribute('data-image-width') ?: self::extractWidthFromStyle($element->getAttribute('style')));

        return self::normalizeMediaWidth($width);
    }

    /** @param array<string,mixed> $attrs */
    private static function resolveMediaTextVerticalAlignment(\DOMElement $element, array $attrs, string $class): string
    {
        $alignment = (string) ($attrs['verticalAlignment'] ?? $attrs['mediaVerticalAlignment'] ?? $attrs['imageVerticalAlignment'] ?? $element->getAttribute('data-vertical-alignment') ?: 'top');

        if (preg_match('/ editorjs-media-text--valign-(top|center|bottom) /', $class, $match) === 1) {
            $alignment = (string) $match[1];
        } elseif (preg_match('/ is-vertically-aligned-(top|center|bottom) /', $class, $match) === 1) {
            $alignment = (string) $match[1];
        }

        return self::normalizeMediaTextVerticalAlignment($alignment);
    }

    /** @param array<string,mixed> $attrs */
    private static function resolveMediaTextImageFit(\DOMElement $element, array $attrs, string $class): string
    {
        $fit = (string) ($attrs['imageFit'] ?? $attrs['objectFit'] ?? $attrs['fit'] ?? $element->getAttribute('data-image-fit') ?: 'cover');

        if (preg_match('/ editorjs-media-text--image-fit-([a-z-]+) /', $class, $match) === 1) {
            $fit = (string) $match[1];
        }

        return self::normalizeImageFit($fit, 'cover');
    }

    private static function resolveMediaTextSpacingFromClass(string $class, string $edge): string
    {
        if (!in_array($edge, ['top', 'bottom'], true)) {
            return '';
        }

        return preg_match('/ editorjs-media-text--spacing-' . $edge . '-([0-9]{1,3}) /', $class, $match) === 1
            ? self::normalizeMediaTextSpacing($match[1])
            : '';
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

    private static function normalizeMediaTextSpacing(mixed $value): string
    {
        $spacing = (int) preg_replace('/[^0-9]/', '', (string) $value);
        $allowed = [0, 5, 10, 15, 20, 30, 40, 60, 80, 100];

        return in_array($spacing, $allowed, true) ? (string) $spacing : '10';
    }

    private static function normalizeMediaTextVerticalAlignment(mixed $value): string
    {
        $alignment = strtolower(trim((string) $value));

        return match ($alignment) {
            'middle', 'centre', 'center' => 'center',
            'bottom', 'end', 'flex-end' => 'bottom',
            'top', 'start', 'flex-start' => 'top',
            default => 'top',
        };
    }

    private static function normalizeImageFit(mixed $value, string $fallback): string
    {
        $fit = (string) $value;

        return in_array($fit, ['contain', 'cover', 'fill', 'none', 'scale-down'], true) ? $fit : $fallback;
    }

    private static function normalizeImageMaxHeight(mixed $value): string
    {
        $height = (int) preg_replace('/[^0-9]/', '', (string) $value);
        $allowed = [0, 200, 300, 400, 500, 600, 800, 1000];

        if (in_array($height, $allowed, true)) {
            return (string) $height;
        }

        return (string) max(0, min(1000, $height));
    }

    private static function truncatePlainText(string $value, int $maxLength): string
    {
        $value = trim($value);

        return function_exists('mb_substr')
            ? mb_substr($value, 0, $maxLength, 'UTF-8')
            : substr($value, 0, $maxLength);
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
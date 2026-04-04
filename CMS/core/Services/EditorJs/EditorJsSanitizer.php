<?php
/**
 * Editor.js Sanitizer für Block-Payloads.
 *
 * @package CMSv2\Services
 */

declare(strict_types=1);

namespace CMS\Services\EditorJs;

use CMS\Json;

if (!defined('ABSPATH')) {
    exit;
}

final class EditorJsSanitizer
{
    public function sanitize(string $json): string
    {
        $data = Json::decodeArray($json, []);
        if (!is_array($data) || empty($data['blocks']) || !is_array($data['blocks'])) {
            return '{"blocks":[]}';
        }

        $cleaned = $this->sanitizePayload($data);
        return (string) json_encode($cleaned, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private function sanitizePayload(array $payload): array
    {
        $blocks = [];

        foreach (($payload['blocks'] ?? []) as $block) {
            if (!is_array($block)) {
                continue;
            }

            $cleanBlock = $this->sanitizeBlock($block);
            if ($cleanBlock !== null) {
                $blocks[] = $cleanBlock;
            }
        }

        return ['blocks' => $blocks];
    }

    private function sanitizeBlock(array $block): ?array
    {
        $allowedTypes = [
            'paragraph', 'header', 'list', 'checklist', 'quote', 'warning',
            'code', 'raw', 'table', 'image', 'attaches', 'linkTool', 'delimiter',
            'embed', 'imageGallery', 'carousel', 'columns', 'accordion', 'drawingTool', 'spacer', 'mediaText',
            'callout', 'terminal', 'codeTabs', 'mermaid', 'apiEndpoint', 'changelog', 'prosCons',
        ];

        $type = (string) ($block['type'] ?? '');
        if (!in_array($type, $allowedTypes, true)) {
            return null;
        }

        $data = $this->sanitizeBlockData($type, is_array($block['data'] ?? null) ? $block['data'] : []);
        $cleanBlock = [
            'type' => $type,
            'data' => $data,
        ];

        $tunes = $this->sanitizeTunes($type, is_array($block['tunes'] ?? null) ? $block['tunes'] : []);
        if ($tunes !== []) {
            $cleanBlock['tunes'] = $tunes;
        }

        return $cleanBlock;
    }

    private function sanitizeBlockData(string $type, array $data): array
    {
        $inlineAllowed = '<b><i><u><a><code><mark><sub><sup><br><strong><em><span>';

        switch ($type) {
            case 'paragraph':
                $data['text'] = strip_tags((string) ($data['text'] ?? ''), $inlineAllowed);
                break;

            case 'header':
                $data['text'] = strip_tags((string) ($data['text'] ?? ''), $inlineAllowed);
                $data['level'] = max(1, min(6, (int) ($data['level'] ?? 2)));
                break;

            case 'list':
                $style = (string) ($data['style'] ?? 'unordered');
                $data['style'] = in_array($style, ['ordered', 'unordered', 'checklist'], true) ? $style : 'unordered';
                $data['meta'] = $this->sanitizeListMeta($data['style'], is_array($data['meta'] ?? null) ? $data['meta'] : []);
                $data['items'] = $this->sanitizeListItems(is_array($data['items'] ?? null) ? $data['items'] : [], $data['style']);
                break;

            case 'checklist':
                $data['items'] = array_values(array_filter(array_map(static function ($item) use ($inlineAllowed) {
                    if (!is_array($item)) {
                        return null;
                    }

                    return [
                        'text' => strip_tags((string) ($item['text'] ?? ''), $inlineAllowed),
                        'checked' => !empty($item['checked']),
                    ];
                }, is_array($data['items'] ?? null) ? $data['items'] : [])));
                break;

            case 'quote':
                $data['text'] = strip_tags((string) ($data['text'] ?? ''), $inlineAllowed);
                $data['caption'] = strip_tags((string) ($data['caption'] ?? ''), $inlineAllowed);
                $data['alignment'] = in_array(($data['alignment'] ?? 'left'), ['left', 'center'], true) ? (string) $data['alignment'] : 'left';
                break;

            case 'warning':
                $data['title'] = strip_tags((string) ($data['title'] ?? ''), $inlineAllowed);
                $data['message'] = strip_tags((string) ($data['message'] ?? ''), $inlineAllowed);
                break;

            case 'code':
                $data['code'] = (string) ($data['code'] ?? '');
                if (isset($data['language'])) {
                    $data['language'] = preg_replace('/[^a-z0-9_\-+#]/i', '', (string) $data['language']);
                }
                break;

            case 'raw':
                $data['html'] = strip_tags((string) ($data['html'] ?? ''), '<p><a><strong><em><ul><ol><li><br><h1><h2><h3><h4><h5><h6><div><span><img><table><tr><td><th><thead><tbody><blockquote><pre><code><hr><figure><figcaption><iframe>');
                break;

            case 'table':
                $data['withHeadings'] = !empty($data['withHeadings']);
                $data['content'] = array_values(array_map(function ($row) use ($inlineAllowed) {
                    if (!is_array($row)) {
                        return [];
                    }

                    return array_values(array_map(static fn($cell) => strip_tags((string) $cell, $inlineAllowed), $row));
                }, is_array($data['content'] ?? null) ? $data['content'] : []));
                break;

            case 'image':
                $data['file'] = $this->sanitizeFileInfo(is_array($data['file'] ?? null) ? $data['file'] : []);
                $data['caption'] = strip_tags((string) ($data['caption'] ?? ''), $inlineAllowed);
                $data['withBorder'] = !empty($data['withBorder']);
                $data['withBackground'] = !empty($data['withBackground']);
                $data['stretched'] = !empty($data['stretched']);
                break;

            case 'attaches':
                $data['file'] = $this->sanitizeFileInfo(is_array($data['file'] ?? null) ? $data['file'] : []);
                $data['title'] = strip_tags((string) ($data['title'] ?? ''), $inlineAllowed);
                break;

            case 'linkTool':
                $data['link'] = filter_var((string) ($data['link'] ?? ''), FILTER_VALIDATE_URL) ?: '';
                $data['meta'] = $this->sanitizeLinkMeta(is_array($data['meta'] ?? null) ? $data['meta'] : []);
                break;

            case 'embed':
                $data['service'] = preg_replace('/[^a-z0-9\-]/i', '', (string) ($data['service'] ?? 'embed'));
                $data['source'] = filter_var((string) ($data['source'] ?? ''), FILTER_VALIDATE_URL) ?: '';
                $data['embed'] = filter_var((string) ($data['embed'] ?? ''), FILTER_VALIDATE_URL) ?: '';
                $data['width'] = max(0, (int) ($data['width'] ?? 0));
                $data['height'] = max(0, (int) ($data['height'] ?? 0));
                $data['caption'] = strip_tags((string) ($data['caption'] ?? ''), $inlineAllowed);
                break;

            case 'imageGallery':
                $columns = (int) ($data['columns'] ?? 3);
                if (!in_array($columns, [2, 3, 4, 6], true)) {
                    $columns = 3;
                }

                $images = array_values(array_filter(array_map(function ($item) use ($inlineAllowed) {
                    if (!is_array($item)) {
                        return null;
                    }

                    $file = $this->sanitizeFileInfo(is_array($item['file'] ?? null) ? $item['file'] : $item);
                    if ($file['url'] === '') {
                        return null;
                    }

                    return [
                        'file' => $file,
                        'caption' => strip_tags((string) ($item['caption'] ?? ''), $inlineAllowed),
                    ];
                }, is_array($data['images'] ?? null) ? $data['images'] : [])));

                if ($images === []) {
                    $images = array_values(array_map(function (string $url): array {
                        return [
                            'file' => [
                                'url' => $url,
                                'name' => '',
                                'size' => 0,
                                'extension' => '',
                            ],
                            'caption' => '',
                        ];
                    }, $this->sanitizeUrlList(is_array($data['urls'] ?? null) ? $data['urls'] : [])));
                }

                $data = [
                    'columns' => $columns,
                    'images' => $images,
                    'urls' => array_values(array_map(static fn(array $item): string => (string) ($item['file']['url'] ?? ''), $images)),
                ];
                break;

            case 'mediaText':
                $data['file'] = $this->sanitizeFileInfo(is_array($data['file'] ?? null) ? $data['file'] : []);
                $data['alt'] = strip_tags((string) ($data['alt'] ?? ''), '');
                $data['text'] = trim((string) ($data['text'] ?? ''));
                break;

            case 'callout':
                $variant = (string) ($data['variant'] ?? 'info');
                $data = [
                    'variant' => in_array($variant, ['info', 'warning', 'success'], true) ? $variant : 'info',
                    'title' => strip_tags((string) ($data['title'] ?? ''), $inlineAllowed),
                    'message' => strip_tags((string) ($data['message'] ?? ''), $inlineAllowed),
                ];
                break;

            case 'terminal':
                $shell = (string) ($data['shell'] ?? 'bash');
                $data = [
                    'shell' => in_array($shell, ['bash', 'sh', 'zsh', 'powershell', 'cmd'], true) ? $shell : 'bash',
                    'title' => strip_tags((string) ($data['title'] ?? ''), $inlineAllowed),
                    'command' => (string) ($data['command'] ?? ''),
                    'output' => (string) ($data['output'] ?? ''),
                ];
                break;

            case 'codeTabs':
                $tabs = array_values(array_filter(array_map(static function ($tab) {
                    if (!is_array($tab)) {
                        return null;
                    }

                    $label = strip_tags((string) ($tab['label'] ?? ''), '');
                    $language = preg_replace('/[^a-z0-9_\-+#]/i', '', (string) ($tab['language'] ?? ''));
                    $code = (string) ($tab['code'] ?? '');

                    if ($label === '' && trim($code) === '') {
                        return null;
                    }

                    return [
                        'label' => $label !== '' ? $label : 'Tab',
                        'language' => $language,
                        'code' => $code,
                    ];
                }, is_array($data['tabs'] ?? null) ? $data['tabs'] : [])));

                $data = [
                    'title' => strip_tags((string) ($data['title'] ?? ''), $inlineAllowed),
                    'tabs' => array_slice($tabs, 0, 8),
                ];
                break;

            case 'mermaid':
                $data = [
                    'title' => strip_tags((string) ($data['title'] ?? ''), $inlineAllowed),
                    'code' => (string) ($data['code'] ?? ''),
                    'caption' => strip_tags((string) ($data['caption'] ?? ''), $inlineAllowed),
                ];
                break;

            case 'apiEndpoint':
                $method = strtoupper((string) ($data['method'] ?? 'GET'));
                $data = [
                    'method' => in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'HEAD'], true) ? $method : 'GET',
                    'path' => strip_tags((string) ($data['path'] ?? ''), ''),
                    'summary' => strip_tags((string) ($data['summary'] ?? ''), $inlineAllowed),
                    'auth' => strip_tags((string) ($data['auth'] ?? ''), $inlineAllowed),
                    'requestExample' => (string) ($data['requestExample'] ?? ''),
                    'responseExample' => (string) ($data['responseExample'] ?? ''),
                ];
                break;

            case 'changelog':
                $data = [
                    'title' => strip_tags((string) ($data['title'] ?? ''), $inlineAllowed),
                    'version' => strip_tags((string) ($data['version'] ?? ''), ''),
                    'date' => strip_tags((string) ($data['date'] ?? ''), ''),
                    'items' => array_values(array_filter(array_map(static fn($item) => trim(strip_tags((string) $item, $inlineAllowed)), is_array($data['items'] ?? null) ? $data['items'] : []))),
                ];
                break;

            case 'prosCons':
                $data = [
                    'title' => strip_tags((string) ($data['title'] ?? ''), $inlineAllowed),
                    'prosTitle' => strip_tags((string) ($data['prosTitle'] ?? 'Vorteile'), $inlineAllowed),
                    'consTitle' => strip_tags((string) ($data['consTitle'] ?? 'Nachteile'), $inlineAllowed),
                    'pros' => array_values(array_filter(array_map(static fn($item) => trim(strip_tags((string) $item, $inlineAllowed)), is_array($data['pros'] ?? null) ? $data['pros'] : []))),
                    'cons' => array_values(array_filter(array_map(static fn($item) => trim(strip_tags((string) $item, $inlineAllowed)), is_array($data['cons'] ?? null) ? $data['cons'] : []))),
                ];
                break;

            case 'carousel':
                $data = array_values(array_filter(array_map(function ($item) use ($inlineAllowed) {
                    if (!is_array($item)) {
                        return null;
                    }

                    $url = filter_var((string) ($item['url'] ?? ''), FILTER_VALIDATE_URL);
                    if ($url === false) {
                        return null;
                    }

                    return [
                        'url' => $url,
                        'caption' => strip_tags((string) ($item['caption'] ?? ''), $inlineAllowed),
                    ];
                }, $data)));
                break;

            case 'columns':
                $cleanCols = [];
                foreach ((is_array($data['cols'] ?? null) ? $data['cols'] : []) as $column) {
                    if (!is_array($column)) {
                        continue;
                    }
                    $cleanCols[] = $this->sanitizePayload($column);
                }
                $data['cols'] = $cleanCols;
                break;

            case 'accordion':
                $settings = is_array($data['settings'] ?? null) ? $data['settings'] : [];
                $data['settings'] = [
                    'blockCount' => max(1, min(10, (int) ($settings['blockCount'] ?? 3))),
                    'defaultExpanded' => !empty($settings['defaultExpanded']),
                ];
                $data['title'] = strip_tags((string) ($data['title'] ?? ''), $inlineAllowed);
                break;

            case 'drawingTool':
                $data['canvasJson'] = is_string($data['canvasJson'] ?? null) ? $data['canvasJson'] : null;
                $data['canvasHeight'] = max(150, min(3000, (int) ($data['canvasHeight'] ?? 700)));
                $data['canvasImages'] = array_values(array_filter(array_map(function ($item) {
                    if (!is_array($item)) {
                        return null;
                    }

                    $src = (string) ($item['src'] ?? '');
                    if (!$this->isValidAssetUrl($src)) {
                        return null;
                    }

                    return [
                        'id' => preg_replace('/[^a-z0-9_\-]/i', '', (string) ($item['id'] ?? 'img')),
                        'src' => $src,
                        'attrs' => is_array($item['attrs'] ?? null) ? $item['attrs'] : [],
                    ];
                }, is_array($data['canvasImages'] ?? null) ? $data['canvasImages'] : [])));
                break;

            case 'spacer':
                $allowedHeights = [15, 25, 40, 60, 75, 100];
                $height = (int) ($data['height'] ?? 15);
                if (!in_array($height, $allowedHeights, true)) {
                    $height = 15;
                }

                $data = [
                    'height' => $height,
                    'preset' => $height . 'px',
                ];
                break;
        }

        return $data;
    }

    private function sanitizeTunes(string $type, array $tunes): array
    {
        if ($type !== 'image') {
            return [];
        }

        $cleanTunes = [];
        foreach (['Cropper', 'CropperTune'] as $key) {
            if (!isset($tunes[$key]) || !is_array($tunes[$key])) {
                continue;
            }

            $croppedImage = (string) ($tunes[$key]['croppedImage'] ?? '');
            if ($croppedImage !== '' && $this->isValidAssetUrl($croppedImage)) {
                $cleanTunes[$key] = ['croppedImage' => $croppedImage];
            }
        }

        return $cleanTunes;
    }

    private function sanitizeListItems(array $items, string $style): array
    {
        $inlineAllowed = '<b><i><u><a><code><mark><sub><sup><br><strong><em><span>';
        $cleanItems = [];

        foreach ($items as $item) {
            if (is_string($item)) {
                $cleanItems[] = [
                    'content' => strip_tags($item, $inlineAllowed),
                    'meta' => $style === 'checklist' ? ['checked' => false] : [],
                    'items' => [],
                ];
                continue;
            }

            if (!is_array($item)) {
                continue;
            }

            $cleanItems[] = [
                'content' => strip_tags((string) ($item['content'] ?? $item['text'] ?? ''), $inlineAllowed),
                'meta' => $this->sanitizeListMeta($style, is_array($item['meta'] ?? null) ? $item['meta'] : []),
                'items' => $this->sanitizeListItems(is_array($item['items'] ?? null) ? $item['items'] : [], $style),
            ];
        }

        return $cleanItems;
    }

    private function sanitizeListMeta(string $style, array $meta): array
    {
        return match ($style) {
            'ordered' => [
                'start' => max(1, (int) ($meta['start'] ?? 1)),
                'counterType' => in_array(($meta['counterType'] ?? 'numeric'), ['numeric', 'lower-roman', 'upper-roman', 'lower-alpha', 'upper-alpha'], true)
                    ? (string) $meta['counterType']
                    : 'numeric',
            ],
            'checklist' => [
                'checked' => !empty($meta['checked']),
            ],
            default => [],
        };
    }

    private function sanitizeFileInfo(array $file): array
    {
        $url = filter_var((string) ($file['url'] ?? ''), FILTER_VALIDATE_URL) ?: '';

        return [
            'url' => $url,
            'name' => strip_tags((string) ($file['name'] ?? ''), ''),
            'size' => max(0, (int) ($file['size'] ?? 0)),
            'extension' => preg_replace('/[^a-z0-9]/i', '', (string) ($file['extension'] ?? '')),
        ];
    }

    private function sanitizeLinkMeta(array $meta): array
    {
        $inlineAllowed = '<b><i><u><a><code><mark><sub><sup><br><strong><em><span>';

        return [
            'title' => strip_tags((string) ($meta['title'] ?? ''), $inlineAllowed),
            'description' => strip_tags((string) ($meta['description'] ?? ''), $inlineAllowed),
            'site_name' => strip_tags((string) ($meta['site_name'] ?? ''), $inlineAllowed),
            'image' => [
                'url' => filter_var((string) ($meta['image']['url'] ?? ''), FILTER_VALIDATE_URL) ?: '',
            ],
        ];
    }

    /** @return string[] */
    private function sanitizeUrlList(array $urls): array
    {
        $cleanUrls = [];
        foreach ($urls as $url) {
            $sanitized = filter_var((string) $url, FILTER_VALIDATE_URL);
            if ($sanitized !== false) {
                $cleanUrls[] = $sanitized;
            }
        }

        return array_values(array_unique($cleanUrls));
    }

    private function isValidAssetUrl(string $url): bool
    {
        if (str_starts_with($url, 'data:image/')) {
            return true;
        }

        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

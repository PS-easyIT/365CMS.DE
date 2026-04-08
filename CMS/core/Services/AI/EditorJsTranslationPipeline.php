<?php
declare(strict_types=1);

namespace CMS\Services\AI;

if (!defined('ABSPATH')) {
    exit;
}

final class EditorJsTranslationPipeline
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
     * @param array<string, mixed> $payload
     * @param array<string, mixed> $translationConfig
     * @return array<string, mixed>
     */
    public function translate(array $payload, AiProviderInterface $provider, array $translationConfig): array
    {
        $editorData = is_array($payload['editor_data'] ?? null) ? $payload['editor_data'] : ['blocks' => []];
        $blocks = is_array($editorData['blocks'] ?? null) ? $editorData['blocks'] : [];
        $supportedBlockTypes = array_values(array_unique(array_filter(array_map('strval', (array) ($translationConfig['supported_block_types'] ?? [])))));
        $skipHtmlBlocks = !empty($translationConfig['skip_html_blocks']);
        $preserveUnsupportedBlocks = !empty($translationConfig['preserve_unsupported_blocks']);

        $meta = [
            'title' => trim((string) ($payload['title'] ?? '')),
            'excerpt' => trim((string) ($payload['excerpt'] ?? '')),
            'slug' => trim((string) ($payload['slug'] ?? '')),
        ];

        $segments = [];
        if ($meta['title'] !== '') {
            $segments[] = ['scope' => 'meta', 'key' => 'title', 'text' => $meta['title']];
        }
        if ($meta['excerpt'] !== '') {
            $segments[] = ['scope' => 'meta', 'key' => 'excerpt', 'text' => $meta['excerpt']];
        }

        $translatedBlockIndexes = [];
        $preservedBlockCount = 0;
        $droppedBlockCount = 0;
        $skippedBlockTypes = [];
        $translatedEditorData = ['blocks' => []];

        foreach ($blocks as $blockIndex => $block) {
            if (!is_array($block)) {
                continue;
            }

            $type = (string) ($block['type'] ?? '');
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];

            if ($type === '') {
                if ($preserveUnsupportedBlocks) {
                    $translatedEditorData['blocks'][] = $block;
                    $preservedBlockCount++;
                } else {
                    $droppedBlockCount++;
                }
                continue;
            }

            if (!in_array($type, $supportedBlockTypes, true) || ($skipHtmlBlocks && $type === 'raw')) {
                $skippedBlockTypes[$type] = $type;

                if ($preserveUnsupportedBlocks) {
                    $translatedEditorData['blocks'][] = $block;
                    $preservedBlockCount++;
                } else {
                    $droppedBlockCount++;
                }

                continue;
            }

            $translatedBlockIndexes[$blockIndex] = true;
            $translatedEditorData['blocks'][] = $block;
            $targetBlockIndex = count($translatedEditorData['blocks']) - 1;
            $this->collectBlockSegments($segments, $type, $data, $targetBlockIndex);
        }

        $translatedTexts = $provider->translateBatch(
            array_values(array_map(static fn (array $segment): string => (string) ($segment['text'] ?? ''), $segments)),
            [
                'content_type' => (string) ($payload['content_type'] ?? 'editorjs'),
                'source_locale' => (string) ($payload['source_locale'] ?? 'de'),
                'target_locale' => (string) ($payload['target_locale'] ?? 'en'),
            ]
        );

        foreach ($segments as $segmentIndex => $segment) {
            $translatedText = (string) ($translatedTexts[$segmentIndex] ?? ($segment['text'] ?? ''));
            $scope = (string) ($segment['scope'] ?? '');

            if ($scope === 'meta') {
                $metaKey = (string) ($segment['key'] ?? '');
                if ($metaKey !== '') {
                    $meta[$metaKey] = $translatedText;
                }
                continue;
            }

            $path = is_array($segment['path'] ?? null) ? $segment['path'] : [];
            if ($path !== []) {
                $this->setNestedValue($translatedEditorData, $path, $translatedText);
            }
        }

        $meta['slug'] = $this->buildLocalizedSlug($meta['title'], $meta['slug'], (string) ($payload['target_locale'] ?? 'en'));
        $editorJson = json_encode($translatedEditorData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{"blocks":[]}';

        return [
            'title' => $meta['title'],
            'excerpt' => $meta['excerpt'],
            'slug' => $meta['slug'],
            'editor_data' => $translatedEditorData,
            'editor_json' => $editorJson,
            'warnings' => $segments === []
                ? ['Keine unterstützten Textsegmente für die Übersetzung gefunden. Nicht unterstützte Blöcke wurden unverändert übernommen.']
                : [],
            'stats' => [
                'total_blocks' => count($blocks),
                'translated_blocks' => count($translatedBlockIndexes),
                'translated_segments' => count($segments),
                'preserved_blocks' => $preservedBlockCount,
                'dropped_blocks' => $droppedBlockCount,
                'skipped_block_types' => array_values($skippedBlockTypes),
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $segments
     * @param array<string, mixed> $data
     */
    private function collectBlockSegments(array &$segments, string $type, array $data, int $blockIndex): void
    {
        switch ($type) {
            case 'paragraph':
            case 'header':
                $this->appendSegment($segments, ['blocks', $blockIndex, 'data', 'text'], (string) ($data['text'] ?? ''));
                return;

            case 'quote':
                $this->appendSegment($segments, ['blocks', $blockIndex, 'data', 'text'], (string) ($data['text'] ?? ''));
                $this->appendSegment($segments, ['blocks', $blockIndex, 'data', 'caption'], (string) ($data['caption'] ?? ''));
                return;

            case 'warning':
                $this->appendSegment($segments, ['blocks', $blockIndex, 'data', 'title'], (string) ($data['title'] ?? ''));
                $this->appendSegment($segments, ['blocks', $blockIndex, 'data', 'message'], (string) ($data['message'] ?? ''));
                return;

            case 'callout':
                $this->appendSegment($segments, ['blocks', $blockIndex, 'data', 'title'], (string) ($data['title'] ?? ''));
                $this->appendSegment($segments, ['blocks', $blockIndex, 'data', 'message'], (string) ($data['message'] ?? ''));
                return;

            case 'checklist':
                foreach ((array) ($data['items'] ?? []) as $itemIndex => $item) {
                    if (!is_array($item)) {
                        continue;
                    }

                    $this->appendSegment($segments, ['blocks', $blockIndex, 'data', 'items', $itemIndex, 'text'], (string) ($item['text'] ?? ''));
                }
                return;

            case 'list':
                $this->collectListItemSegments($segments, ['blocks', $blockIndex, 'data', 'items'], (array) ($data['items'] ?? []));
                return;

            case 'mediaText':
                $this->appendSegment($segments, ['blocks', $blockIndex, 'data', 'text'], (string) ($data['text'] ?? ''));
                return;
        }
    }

    /**
     * @param array<int, array<string, mixed>> $segments
     * @param array<int|string> $basePath
     * @param array<int, mixed> $items
     */
    private function collectListItemSegments(array &$segments, array $basePath, array $items): void
    {
        foreach ($items as $itemIndex => $item) {
            if (is_string($item)) {
                $this->appendSegment($segments, [...$basePath, $itemIndex], $item);
                continue;
            }

            if (!is_array($item)) {
                continue;
            }

            $contentKey = array_key_exists('content', $item) ? 'content' : 'text';
            $this->appendSegment($segments, [...$basePath, $itemIndex, $contentKey], (string) ($item[$contentKey] ?? ''));

            if (is_array($item['items'] ?? null)) {
                $this->collectListItemSegments($segments, [...$basePath, $itemIndex, 'items'], (array) $item['items']);
            }
        }
    }

    /**
     * @param array<int, array<string, mixed>> $segments
     * @param array<int|string> $path
     */
    private function appendSegment(array &$segments, array $path, string $text): void
    {
        if (trim($text) === '') {
            return;
        }

        $segments[] = [
            'scope' => 'block',
            'path' => $path,
            'text' => $text,
        ];
    }

    /**
     * @param array<string, mixed> $subject
     * @param array<int|string> $path
     */
    private function setNestedValue(array &$subject, array $path, string $value): void
    {
        $reference = &$subject;
        $lastIndex = count($path) - 1;

        foreach ($path as $index => $key) {
            if ($index === $lastIndex) {
                $reference[$key] = $value;
                return;
            }

            if (!isset($reference[$key]) || !is_array($reference[$key])) {
                return;
            }

            $reference = &$reference[$key];
        }
    }

    private function buildLocalizedSlug(string $translatedTitle, string $sourceSlug, string $targetLocale): string
    {
        $candidate = trim($translatedTitle);
        $candidate = preg_replace('/^\[[A-Z0-9 _-]+\]\s*/u', '', $candidate) ?? $candidate;
        $candidate = strip_tags($candidate);

        if ($candidate === '') {
            $candidate = trim($sourceSlug);
        }

        if ($candidate === '') {
            $candidate = $targetLocale;
        }

        if (function_exists('iconv')) {
            $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $candidate);
            if (is_string($ascii) && trim($ascii) !== '') {
                $candidate = $ascii;
            }
        }

        $candidate = strtolower(trim($candidate));
        $candidate = preg_replace('/[^a-z0-9\-]+/', '-', $candidate) ?? $candidate;
        $candidate = preg_replace('/-+/', '-', $candidate) ?? $candidate;
        $candidate = trim($candidate, '-');

        if ($candidate === '') {
            $fallback = trim($sourceSlug);
            if ($fallback !== '' && !str_ends_with($fallback, '-' . $targetLocale)) {
                return $fallback . '-' . $targetLocale;
            }

            return $targetLocale;
        }

        return $candidate;
    }
}
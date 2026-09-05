<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\AuditLogger;
use CMS\Json;
use CMS\Logger;
use CMS\Services\AI\AiService;
use CMS\Services\EditorJs\EditorJsSanitizer;

final class AiEditorJsSeoMetadataModule
{
    private const int MAX_EDITOR_JSON_LENGTH = 250000;
    private const int MAX_CONTENT_TYPE_LENGTH = 20;
    private const int MAX_LOCALE_LENGTH = 20;

    private AiService $aiService;
    private EditorJsSanitizer $editorJsSanitizer;

    public function __construct()
    {
        $this->aiService = AiService::getInstance();
        $this->editorJsSanitizer = new EditorJsSanitizer();
    }

    /** @return array<string, mixed> */
    public function handleRequest(array $post, int $userId): array
    {
        try {
            $contentType = $this->sanitizeContentType((string) ($post['content_type'] ?? ''));
            $locale = $this->sanitizeLocale((string) ($post['locale'] ?? 'de'));
            $editorData = $this->sanitizeEditorJson((string) ($post['editor_data'] ?? ''));
            $sourceText = $this->extractSourceText($editorData);

            if ($sourceText === '') {
                throw new \InvalidArgumentException('Im Haupttext wurden keine auswertbaren Textsegmente gefunden.');
            }

            $result = $this->aiService->generateSeoMetadataDraft([
                'user_id' => $userId,
                'content_type' => $contentType,
                'locale' => $locale,
                'source_text' => $sourceText,
                'block_count' => count((array) ($editorData['blocks'] ?? [])),
            ]);

            $telemetry = is_array($result['telemetry'] ?? null) ? $result['telemetry'] : [];
            unset($result['telemetry']);

            AuditLogger::instance()->log(
                AuditLogger::CAT_CONTENT,
                'ai.editorjs.seo_metadata.processed',
                'SEO-Metadaten wurden aus einem Editor.js-Haupttext als Entwurf erzeugt.',
                $contentType,
                null,
                array_filter([
                    'user_id' => $userId,
                    'provider' => (string) ($result['provider']['slug'] ?? ''),
                    'locale' => $locale,
                    'source_char_count' => isset($telemetry['source_char_count']) ? (int) $telemetry['source_char_count'] : null,
                    'source_block_count' => isset($telemetry['source_block_count']) ? (int) $telemetry['source_block_count'] : null,
                    'source_truncated' => !empty($telemetry['source_truncated']),
                    'generated_field_count' => (int) ($result['stats']['generated_field_count'] ?? 0),
                    'duration_ms' => (int) ($telemetry['duration_ms'] ?? 0),
                    'source_hash' => (string) ($telemetry['source_hash'] ?? ''),
                    'resolved_via' => (string) ($result['provider']['resolved_via'] ?? 'direct'),
                ], static fn (mixed $value): bool => $value !== '' && $value !== null),
                'info'
            );

            return [
                'success' => true,
                'message' => 'SEO-Felder wurden aus dem Haupttext als Entwurf erzeugt. Titel, Slug und URL-Felder bleiben unverändert.',
            ] + $result;
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('admin.ai-seo-metadata')->error('SEO-Metadaten konnten nicht aus dem Editor.js-Haupttext erzeugt werden.', [
                'exception' => $e::class,
                'message' => $this->sanitizeLogMessage($e->getMessage()),
                'user_id' => $userId,
            ]);

            AuditLogger::instance()->log(
                AuditLogger::CAT_CONTENT,
                'ai.editorjs.seo_metadata.failed',
                'SEO-Metadaten konnten nicht aus dem Editor.js-Haupttext erzeugt werden.',
                'editorjs',
                null,
                [
                    'exception' => $e::class,
                    'user_id' => $userId,
                ],
                'warning'
            );

            return [
                'success' => false,
                'error' => 'SEO-Felder konnten nicht per AI erzeugt werden. Bitte Logs prüfen.',
            ];
        }
    }

    /** @return array<string, mixed> */
    private function sanitizeEditorJson(string $value): array
    {
        if (strlen($value) > self::MAX_EDITOR_JSON_LENGTH) {
            throw new \InvalidArgumentException('Der Editor.js-Haupttext überschreitet die erlaubte Eingabegröße.');
        }

        $sanitizedJson = $this->editorJsSanitizer->sanitize($value);
        $editorData = Json::decodeArray($sanitizedJson, ['blocks' => []]);

        return is_array($editorData['blocks'] ?? null) ? $editorData : ['blocks' => []];
    }

    private function sanitizeContentType(string $value): string
    {
        $value = strtolower(trim($value));
        $value = substr($value, 0, self::MAX_CONTENT_TYPE_LENGTH);

        if (!in_array($value, ['post', 'page'], true)) {
            throw new \InvalidArgumentException('Für die SEO-Generierung ist ein gültiger Inhaltstyp erforderlich.');
        }

        return $value;
    }

    private function sanitizeLocale(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_-]+/', '', $value) ?? '';
        $value = substr($value, 0, self::MAX_LOCALE_LENGTH);

        return $value !== '' ? $value : 'de';
    }

    /** @param array<string, mixed> $editorData */
    private function extractSourceText(array $editorData): string
    {
        $parts = [];

        foreach ((array) ($editorData['blocks'] ?? []) as $block) {
            if (!is_array($block)) {
                continue;
            }

            $type = (string) ($block['type'] ?? '');
            $data = is_array($block['data'] ?? null) ? $block['data'] : [];
            $this->collectBlockText($parts, $type, $data);
        }

        return trim(implode("\n\n", array_filter($parts, static fn (string $part): bool => $part !== '')));
    }

    /** @param list<string> $parts */
    private function collectBlockText(array &$parts, string $type, array $data): void
    {
        switch ($type) {
            case 'paragraph':
            case 'header':
            case 'mediaText':
                $this->appendText($parts, $data['text'] ?? null);
                return;

            case 'quote':
                $this->appendText($parts, $data['text'] ?? null);
                $this->appendText($parts, $data['caption'] ?? null);
                return;

            case 'image':
                $this->appendText($parts, $data['caption'] ?? null);
                return;

            case 'table':
                foreach ((array) ($data['content'] ?? []) as $row) {
                    if (!is_array($row)) {
                        continue;
                    }
                    foreach ($row as $cell) {
                        $this->appendText($parts, $cell);
                    }
                }
                return;

            case 'attaches':
                $this->appendText($parts, $data['title'] ?? null);
                return;

            case 'linkTool':
                $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
                $this->appendText($parts, $meta['title'] ?? null);
                $this->appendText($parts, $meta['description'] ?? null);
                return;

            case 'warning':
            case 'callout':
                $this->appendText($parts, $data['title'] ?? null);
                $this->appendText($parts, $data['message'] ?? null);
                return;

            case 'alert':
                $this->appendText($parts, $data['message'] ?? null);
                return;

            case 'accordion':
                $this->appendText($parts, $data['title'] ?? null);
                return;

            case 'imageGallery':
                foreach ((array) ($data['images'] ?? []) as $image) {
                    if (is_array($image)) {
                        $this->appendText($parts, $image['caption'] ?? null);
                    }
                }
                return;

            case 'checklist':
                foreach ((array) ($data['items'] ?? []) as $item) {
                    if (is_array($item)) {
                        $this->appendText($parts, $item['text'] ?? null);
                    }
                }
                return;

            case 'list':
                $this->collectListText($parts, (array) ($data['items'] ?? []));
                return;
        }
    }

    /** @param list<string> $parts */
    private function collectListText(array &$parts, array $items): void
    {
        foreach ($items as $item) {
            if (is_string($item)) {
                $this->appendText($parts, $item);
                continue;
            }

            if (!is_array($item)) {
                continue;
            }

            $this->appendText($parts, $item['content'] ?? $item['text'] ?? null);
            if (is_array($item['items'] ?? null)) {
                $this->collectListText($parts, $item['items']);
            }
        }
    }

    /** @param list<string> $parts */
    private function appendText(array &$parts, mixed $value): void
    {
        if (!is_scalar($value)) {
            return;
        }

        $text = html_entity_decode(strip_tags((string) $value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');
        if ($text !== '') {
            $parts[] = $text;
        }
    }

    private function sanitizeLogMessage(string $value): string
    {
        $value = trim(strip_tags($value));

        return function_exists('mb_substr') ? mb_substr($value, 0, 180, 'UTF-8') : substr($value, 0, 180);
    }
}

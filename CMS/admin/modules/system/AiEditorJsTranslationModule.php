<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\AuditLogger;
use CMS\Logger;
use CMS\Services\AI\AiProviderGateway;

final class AiEditorJsTranslationModule
{
    private const int MAX_TEXT_LENGTH = 2000;
    private const int MAX_TITLE_LENGTH = 255;
    private const int MAX_SLUG_LENGTH = 255;
    private const int MAX_EDITOR_JSON_LENGTH = 250000;

    private AiProviderGateway $gateway;

    public function __construct()
    {
        $this->gateway = AiProviderGateway::getInstance();
    }

    /** @return array<string, mixed> */
    public function handleRequest(array $post, int $userId): array
    {
        try {
            $contentType = $this->sanitizeContentType((string) ($post['content_type'] ?? 'editorjs'));
            $title = $this->sanitizeText((string) ($post['title'] ?? ''), self::MAX_TITLE_LENGTH);
            $excerpt = $this->sanitizeText((string) ($post['excerpt'] ?? ''), self::MAX_TEXT_LENGTH);
            $slug = $this->sanitizeSlug((string) ($post['slug'] ?? ''));
            $sourceLocale = $this->sanitizeLocale((string) ($post['source_locale'] ?? 'de'), 'de');
            $targetLocale = $this->sanitizeLocale((string) ($post['target_locale'] ?? 'en'), 'en');
            $editorData = $this->sanitizeEditorJson((string) ($post['editor_data'] ?? ''));

            $result = $this->gateway->translateEditorJsDraft([
                'content_type' => $contentType,
                'title' => $title,
                'excerpt' => $excerpt,
                'slug' => $slug,
                'source_locale' => $sourceLocale,
                'target_locale' => $targetLocale,
                'editor_data' => $editorData,
            ]);

            $telemetry = is_array($result['telemetry'] ?? null) ? $result['telemetry'] : [];
            unset($result['telemetry']);

            AuditLogger::instance()->log(
                AuditLogger::CAT_CONTENT,
                'ai.editorjs.translate.processed',
                'Editor.js-Inhalt wurde über die AI-Translation-Pipeline verarbeitet.',
                $contentType,
                null,
                array_filter([
                    'user_id' => $userId,
                    'provider' => (string) ($result['provider']['slug'] ?? 'mock'),
                    'target_locale' => $targetLocale,
                    'translated_blocks' => (int) (($result['stats']['translated_blocks'] ?? 0)),
                    'translated_segments' => (int) (($result['stats']['translated_segments'] ?? 0)),
                    'duration_ms' => (int) ($telemetry['duration_ms'] ?? 0),
                    'source_hash' => (string) ($telemetry['source_hash'] ?? ''),
                    'translated_hash' => (string) ($telemetry['translated_hash'] ?? ''),
                    'resolved_via' => (string) ($result['provider']['resolved_via'] ?? 'direct'),
                ], static fn (mixed $value): bool => $value !== '' && $value !== null),
                'info'
            );

            return [
                'success' => true,
                'message' => 'AI-Übersetzung für Editor.js wurde erzeugt.',
            ] + $result;
        } catch (\Throwable $e) {
            Logger::instance()->withChannel('admin.ai-translate')->error('Editor.js-AI-Übersetzung konnte nicht verarbeitet werden.', [
                'exception' => $e::class,
                'message' => $this->sanitizeText($e->getMessage(), 180),
                'user_id' => $userId,
            ]);

            AuditLogger::instance()->log(
                AuditLogger::CAT_CONTENT,
                'ai.editorjs.translate.failed',
                'Editor.js-AI-Übersetzung konnte nicht verarbeitet werden.',
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
                'error' => $this->sanitizeText($e->getMessage(), 180) ?: 'Editor.js-AI-Übersetzung konnte nicht verarbeitet werden.',
            ];
        }
    }

    private function sanitizeContentType(string $value): string
    {
        $value = strtolower(trim($value));

        return in_array($value, ['post', 'page'], true) ? $value : 'editorjs';
    }

    private function sanitizeLocale(string $value, string $fallback): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9_-]+/', '', $value) ?? '';

        return $value !== '' ? $value : $fallback;
    }

    private function sanitizeSlug(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9\-]+/', '-', $value) ?? '';
        $value = preg_replace('/-+/', '-', $value) ?? '';
        $value = trim($value, '-');

        return function_exists('mb_substr')
            ? mb_substr($value, 0, self::MAX_SLUG_LENGTH)
            : substr($value, 0, self::MAX_SLUG_LENGTH);
    }

    private function sanitizeText(string $value, int $maxLength): string
    {
        $value = trim(strip_tags($value));
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/u', ' ', $value) ?? '';

        return function_exists('mb_substr')
            ? mb_substr($value, 0, $maxLength)
            : substr($value, 0, $maxLength);
    }

    private function sanitizeEditorJson(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '{"blocks":[]}';
        }

        $length = function_exists('mb_strlen') ? mb_strlen($value, '8bit') : strlen($value);
        if ($length > self::MAX_EDITOR_JSON_LENGTH) {
            throw new \InvalidArgumentException('Die Editor.js-Payload ist für die AI-Übersetzung zu groß.');
        }

        return $value;
    }
}
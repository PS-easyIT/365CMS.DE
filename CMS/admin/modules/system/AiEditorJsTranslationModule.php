<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\AuditLogger;
use CMS\Logger;
use CMS\Services\AI\AiService;

final class AiEditorJsTranslationModule
{
    private const int MAX_TEXT_LENGTH = 2000;
    private const int MAX_TITLE_LENGTH = 255;
    private const int MAX_SLUG_LENGTH = 255;
    private const int MAX_EDITOR_JSON_LENGTH = 250000;
    private const int MAX_EDITOR_BLOCKS = 120;
    private const int MAX_BLOCK_TYPE_LENGTH = 80;
    private const string CHUNK_SESSION_KEY = 'ai_editorjs_translation_chunks';
    private const int CHUNK_SESSION_TTL_SECONDS = 1800;
    private const int CHUNK_RETRY_ALLOWANCE = 10;
    private const int MAX_CHUNK_SESSIONS = 5;
    private const string CHUNK_SESSION_INVALID_MESSAGE = 'Die blockweise AI-Übersetzung ist abgelaufen oder ungültig. Bitte die Übersetzung neu starten.';

    private AiService $aiService;

    public function __construct()
    {
        $this->aiService = AiService::getInstance();
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
            $this->assertValidChunkPayload($post, $editorData, $title, $excerpt);
            $chunk = $this->resolveChunkSession($post, $userId, $contentType, $targetLocale);

            $result = $this->aiService->translateEditorJsDraft([
                'user_id' => $userId,
                'content_type' => $contentType,
                'title' => $title,
                'excerpt' => $excerpt,
                'slug' => $slug,
                'source_locale' => $sourceLocale,
                'target_locale' => $targetLocale,
                'editor_data' => $editorData,
                'count_user_request' => $chunk === null || $chunk['count_user_request'],
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
                    'translation_batches' => (int) (($result['stats']['translation_batches'] ?? 0)),
                    'char_count' => isset($telemetry['char_count']) ? (int) $telemetry['char_count'] : null,
                    'block_count' => isset($telemetry['block_count']) ? (int) $telemetry['block_count'] : null,
                    'duration_ms' => (int) ($telemetry['duration_ms'] ?? 0),
                    'source_hash' => (string) ($telemetry['source_hash'] ?? ''),
                    'translated_hash' => (string) ($telemetry['translated_hash'] ?? ''),
                    'resolved_via' => (string) ($result['provider']['resolved_via'] ?? 'direct'),
                    'chunked' => $chunk !== null ? 1 : null,
                ], static fn (mixed $value): bool => $value !== '' && $value !== null),
                'info'
            );

            $response = [
                'success' => true,
                'message' => 'AI-Übersetzung für Editor.js wurde erzeugt.',
            ] + $result;

            if ($chunk !== null) {
                $response['chunk'] = [
                    'session' => $chunk['session_id'],
                    'remaining' => $chunk['remaining'],
                ];
            }

            return $response;
        } catch (\DomainException $e) {
            Logger::instance()->withChannel('admin.ai-translate')->warning('Blockweise Editor.js-AI-Übersetzung mit ungültiger Sitzung abgelehnt.', [
                'user_id' => $userId,
            ]);

            return [
                'success' => false,
                'error' => self::CHUNK_SESSION_INVALID_MESSAGE,
                'error_code' => 'chunk_session_invalid',
            ];
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
                'error' => 'Editor.js-AI-Übersetzung konnte nicht verarbeitet werden. Bitte Logs prüfen.',
            ];
        }
    }

    /**
     * Block-by-block translations send one request per Editor.js block. The first chunk reserves the
     * user-visible operation; continuation chunks prove membership via a server-side session entry so
     * that a single document does not consume one daily request per block. Characters and provider
     * calls are still counted for every chunk.
     *
     * @return array{session_id:string,count_user_request:bool,remaining:int}|null
     */
    private function resolveChunkSession(array $post, int $userId, string $contentType, string $targetLocale): ?array
    {
        $rawSessionId = $post['chunk_session'] ?? '';
        $rawTotal = $post['chunk_total'] ?? 0;
        $sessionId = is_scalar($rawSessionId) ? strtolower(trim((string) $rawSessionId)) : 'invalid';
        $declaredTotal = is_scalar($rawTotal) ? (int) $rawTotal : 0;

        if ($sessionId === '' && $declaredTotal <= 0) {
            return null;
        }

        if (session_status() !== PHP_SESSION_ACTIVE || $userId <= 0) {
            throw new \DomainException(self::CHUNK_SESSION_INVALID_MESSAGE);
        }

        $now = time();
        $sessions = is_array($_SESSION[self::CHUNK_SESSION_KEY] ?? null) ? $_SESSION[self::CHUNK_SESSION_KEY] : [];
        $sessions = array_filter(
            $sessions,
            static fn (mixed $entry): bool => is_array($entry) && (int) ($entry['expires'] ?? 0) > $now
        );

        if ($sessionId === '') {
            $total = min($declaredTotal, self::MAX_EDITOR_BLOCKS + 1);
            $remaining = ($total - 1) + min(self::CHUNK_RETRY_ALLOWANCE, $total);
            $sessionId = bin2hex(random_bytes(16));

            if ($remaining > 0) {
                $sessions[$sessionId] = [
                    'user_id' => $userId,
                    'content_type' => $contentType,
                    'target_locale' => $targetLocale,
                    'remaining' => $remaining,
                    'expires' => $now + self::CHUNK_SESSION_TTL_SECONDS,
                ];
                if (count($sessions) > self::MAX_CHUNK_SESSIONS) {
                    $sessions = array_slice($sessions, -self::MAX_CHUNK_SESSIONS, null, true);
                }
            }
            $_SESSION[self::CHUNK_SESSION_KEY] = $sessions;

            return ['session_id' => $sessionId, 'count_user_request' => true, 'remaining' => $remaining];
        }

        $entry = preg_match('/^[a-f0-9]{32}$/', $sessionId) === 1 ? ($sessions[$sessionId] ?? null) : null;
        if (!is_array($entry)
            || (int) ($entry['user_id'] ?? 0) !== $userId
            || (string) ($entry['content_type'] ?? '') !== $contentType
            || (string) ($entry['target_locale'] ?? '') !== $targetLocale
            || (int) ($entry['remaining'] ?? 0) <= 0
        ) {
            $_SESSION[self::CHUNK_SESSION_KEY] = $sessions;
            throw new \DomainException(self::CHUNK_SESSION_INVALID_MESSAGE);
        }

        $entry['remaining'] = (int) $entry['remaining'] - 1;
        $entry['expires'] = $now + self::CHUNK_SESSION_TTL_SECONDS;
        if ($entry['remaining'] > 0) {
            $sessions[$sessionId] = $entry;
        } else {
            unset($sessions[$sessionId]);
        }
        $_SESSION[self::CHUNK_SESSION_KEY] = $sessions;

        return ['session_id' => $sessionId, 'count_user_request' => false, 'remaining' => (int) $entry['remaining']];
    }

    /**
     * Chunk requests must stay small: at most one Editor.js block, and metadata (title/excerpt) only in the
     * request that opens a chunk session. This prevents continuation chunks from carrying whole documents.
     */
    private function assertValidChunkPayload(array $post, string $editorJson, string $title, string $excerpt): void
    {
        $rawSessionId = $post['chunk_session'] ?? '';
        $rawTotal = $post['chunk_total'] ?? 0;
        $isContinuation = !is_scalar($rawSessionId) || trim((string) $rawSessionId) !== '';
        $isChunkStart = is_scalar($rawTotal) && (int) $rawTotal > 0;

        if (!$isContinuation && !$isChunkStart) {
            return;
        }

        $decoded = json_decode($editorJson, true);
        $blockCount = is_array($decoded) && is_array($decoded['blocks'] ?? null) ? count($decoded['blocks']) : 0;
        if ($blockCount > 1 || ($isContinuation && ($title !== '' || $excerpt !== ''))) {
            throw new \InvalidArgumentException('Ein Teilauftrag der blockweisen AI-Übersetzung ist zu groß.');
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

        try {
            $decoded = json_decode($value, true, 64, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new \InvalidArgumentException('Die Editor.js-Payload ist kein gültiges JSON.');
        }

        if (!is_array($decoded)) {
            throw new \InvalidArgumentException('Die Editor.js-Payload hat eine ungültige Struktur.');
        }

        $blocks = $decoded['blocks'] ?? [];
        if (!is_array($blocks) || count($blocks) > self::MAX_EDITOR_BLOCKS) {
            throw new \InvalidArgumentException('Die Editor.js-Payload enthält zu viele oder ungültige Blöcke.');
        }

        foreach ($blocks as $block) {
            if (!is_array($block)) {
                throw new \InvalidArgumentException('Die Editor.js-Payload enthält ungültige Blockdaten.');
            }

            $type = trim((string) ($block['type'] ?? ''));
            if ($type === ''
                || strlen($type) > self::MAX_BLOCK_TYPE_LENGTH
                || preg_match('/^[a-zA-Z0-9_-]+$/', $type) !== 1
                || (isset($block['data']) && !is_array($block['data']))
            ) {
                throw new \InvalidArgumentException('Die Editor.js-Payload enthält ungültige Block-Metadaten.');
            }
        }

        $encoded = json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($encoded) || $encoded === '') {
            throw new \InvalidArgumentException('Die Editor.js-Payload konnte nicht normalisiert werden.');
        }

        return $encoded;
    }
}

<?php
declare(strict_types=1);

namespace CMS\Services\AI\Providers;

use CMS\Services\AI\AiProviderInterface;

if (!defined('ABSPATH')) {
    exit;
}

abstract class AbstractPromptingAiProvider implements AiProviderInterface
{
    private string $providerId;
    private string $label;
    private string $defaultModel;

    public function __construct(string $providerId, string $label, string $defaultModel)
    {
        $this->providerId = trim($providerId) !== '' ? trim($providerId) : 'provider';
        $this->label = trim($label) !== '' ? trim($label) : 'AI Provider';
        $this->defaultModel = trim($defaultModel);
    }

    public function getSlug(): string
    {
        return $this->providerId;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function isMock(): bool
    {
        return false;
    }

    public function getDefaultModel(): string
    {
        return $this->defaultModel;
    }

    /**
     * @param list<string> $segments
     * @param array<string, mixed> $context
     * @return array{system:string,user:string}
     */
    protected function buildTranslationPrompt(array $segments, array $context = []): array
    {
        $sourceLocale = strtolower(trim((string) ($context['source_locale'] ?? 'de')));
        $targetLocale = strtolower(trim((string) ($context['target_locale'] ?? 'en')));
        $contentType = strtolower(trim((string) ($context['content_type'] ?? 'editorjs')));

        $systemPrompt = 'You are a strict translation engine for a CMS. '
            . 'Translate each input string from ' . strtoupper($sourceLocale) . ' to ' . strtoupper($targetLocale) . '. '
            . 'Preserve HTML tags, Markdown, placeholders, variable names, URLs, email addresses, numbers, list markers, punctuation and line breaks. '
            . 'Do not explain anything. Do not merge or split items. '
            . 'Return only valid JSON with the exact shape {"translations":["..."]}. '
            . 'The translations array must have exactly ' . count($segments) . ' items in the same order as provided.';

        $userPayload = [
            'task' => 'translate_batch',
            'content_type' => $contentType,
            'source_locale' => $sourceLocale,
            'target_locale' => $targetLocale,
            'segments' => array_values(array_map(static fn (string $segment): string => $segment, $segments)),
        ];

        return [
            'system' => $systemPrompt,
            'user' => (string) json_encode($userPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];
    }

    /**
     * @param list<string> $segments
     * @return list<string>
     */
    protected function extractTranslationsFromResponse(string $rawContent, array $segments): array
    {
        $payload = $this->decodeStructuredPayload($rawContent);
        $translations = [];

        if ($this->isListOfStrings($payload)) {
            $translations = $payload;
        } elseif (is_array($payload) && $this->isListOfStrings($payload['translations'] ?? null)) {
            $translations = $payload['translations'];
        }

        if (count($translations) !== count($segments)) {
            throw new \RuntimeException('AI-Provider lieferte keine valide Batch-Antwort mit passender Segmentanzahl zurück.');
        }

        $normalized = [];
        foreach ($segments as $index => $segment) {
            $translated = trim((string) ($translations[$index] ?? ''));
            $normalized[] = $translated !== '' ? $translated : (string) $segment;
        }

        return $normalized;
    }

    /** @return array<string, mixed>|list<string> */
    private function decodeStructuredPayload(string $rawContent): array
    {
        $candidates = [];
        $trimmed = trim($rawContent);
        if ($trimmed !== '') {
            $candidates[] = $trimmed;
        }

        $withoutCodeFence = preg_replace('/^```(?:json)?\s*|\s*```$/iu', '', $trimmed) ?? $trimmed;
        $withoutCodeFence = trim($withoutCodeFence);
        if ($withoutCodeFence !== '' && !in_array($withoutCodeFence, $candidates, true)) {
            $candidates[] = $withoutCodeFence;
        }

        $firstBrace = strpos($withoutCodeFence, '{');
        $lastBrace = strrpos($withoutCodeFence, '}');
        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            $jsonObject = substr($withoutCodeFence, $firstBrace, $lastBrace - $firstBrace + 1);
            if ($jsonObject !== '' && !in_array($jsonObject, $candidates, true)) {
                $candidates[] = $jsonObject;
            }
        }

        $firstBracket = strpos($withoutCodeFence, '[');
        $lastBracket = strrpos($withoutCodeFence, ']');
        if ($firstBracket !== false && $lastBracket !== false && $lastBracket > $firstBracket) {
            $jsonArray = substr($withoutCodeFence, $firstBracket, $lastBracket - $firstBracket + 1);
            if ($jsonArray !== '' && !in_array($jsonArray, $candidates, true)) {
                $candidates[] = $jsonArray;
            }
        }

        foreach ($candidates as $candidate) {
            try {
                $decoded = json_decode($candidate, true, 512, JSON_THROW_ON_ERROR);
                if (is_array($decoded)) {
                    return $decoded;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        throw new \RuntimeException('AI-Provider-Antwort konnte nicht als JSON-Übersetzungsnutzlast gelesen werden.');
    }

    private function isListOfStrings(mixed $value): bool
    {
        if (!is_array($value)) {
            return false;
        }

        foreach ($value as $key => $entry) {
            if (!is_int($key) || !is_string($entry)) {
                return false;
            }
        }

        return true;
    }
}
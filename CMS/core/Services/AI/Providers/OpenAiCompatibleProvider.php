<?php
declare(strict_types=1);

namespace CMS\Services\AI\Providers;

use CMS\Http\Client;

if (!defined('ABSPATH')) {
    exit;
}

final class OpenAiCompatibleProvider extends AbstractPromptingAiProvider
{
    private Client $httpClient;
    private string $endpoint;
    private string $apiKey;
    private int $timeoutSeconds;

    public function __construct(
        string $providerId,
        string $label,
        string $defaultModel,
        string $endpoint,
        string $apiKey,
        Client $httpClient,
        int $timeoutSeconds
    ) {
        parent::__construct($providerId, $label, $defaultModel);

        $this->endpoint = rtrim(trim($endpoint), '/');
        $this->apiKey = trim($apiKey);
        $this->httpClient = $httpClient;
        $this->timeoutSeconds = max(5, $timeoutSeconds);
    }

    /**
     * @param list<array{role:string,content:string}> $messages
     * @param array<string, mixed> $options
     */
    public function complete(array $messages, array $options = []): string
    {
        $payload = [
            'model' => $this->getDefaultModel(),
            'messages' => $messages,
            'temperature' => (float) ($options['temperature'] ?? 0.2),
        ];

        $response = $this->httpClient->post(
            $this->endpoint . '/chat/completions',
            (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            [
                'headers' => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                    'Authorization: Bearer ' . $this->apiKey,
                ],
                'timeout' => $this->timeoutSeconds,
                'connectTimeout' => min(5, $this->timeoutSeconds),
                'maxBytes' => 2 * 1024 * 1024,
                'allowedContentTypes' => ['application/json', 'text/plain'],
            ]
        );

        if (!$response['success']) {
            throw new \RuntimeException($this->buildTransportError($response));
        }

        try {
            $decoded = json_decode((string) $response['body'], true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw new \RuntimeException($this->getLabel() . ' lieferte keine gültige JSON-Antwort zurück.');
        }

        $content = $this->extractAssistantContent($decoded);
        if ($content === '') {
            throw new \RuntimeException($this->getLabel() . ' lieferte keine verwertbare Antwort zurück.');
        }

        return $content;
    }

    /**
     * @param list<string> $segments
     * @param array<string, mixed> $context
     * @return list<string>
     */
    public function translateBatch(array $segments, array $context = []): array
    {
        if ($segments === []) {
            return [];
        }

        $prompt = $this->buildTranslationPrompt($segments, $context);
        $content = $this->complete([
            ['role' => 'system', 'content' => $prompt['system']],
            ['role' => 'user', 'content' => $prompt['user']],
        ], [
            'temperature' => 0.1,
        ]);

        return $this->extractTranslationsFromResponse($content, $segments);
    }

    /** @param array<string, mixed> $payload */
    private function extractAssistantContent(array $payload): string
    {
        $content = $payload['choices'][0]['message']['content'] ?? '';

        if (is_string($content)) {
            return trim($content);
        }

        if (!is_array($content)) {
            return '';
        }

        $parts = [];
        foreach ($content as $entry) {
            if (is_array($entry) && isset($entry['text']) && is_string($entry['text'])) {
                $parts[] = $entry['text'];
            }
        }

        return trim(implode("\n", $parts));
    }

    /** @param array<string, mixed> $response */
    private function buildTransportError(array $response): string
    {
        $message = trim((string) ($response['error'] ?? ''));
        $status = max(0, (int) ($response['status'] ?? 0));
        $body = trim((string) ($response['body'] ?? ''));

        if ($body !== '') {
            try {
                $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                $bodyMessage = trim((string) ($decoded['error']['message'] ?? $decoded['message'] ?? $decoded['error'] ?? ''));
                if ($bodyMessage !== '') {
                    $message = $bodyMessage;
                }
            } catch (\Throwable) {
                // Ignore non-JSON bodies.
            }
        }

        $message = $message !== '' ? $message : 'AI-Request ist fehlgeschlagen.';

        return $this->getLabel() . ': ' . ($status > 0 ? 'HTTP-Status ' . $status . '. ' : '') . $message;
    }
}

<?php
declare(strict_types=1);

namespace CMS\Services\AI\Providers;

use CMS\Http\Client;

if (!defined('ABSPATH')) {
    exit;
}

final class OllamaAiProvider extends AbstractPromptingAiProvider
{
    private Client $httpClient;
    private string $endpoint;
    private int $timeoutSeconds;

    public function __construct(
        string $providerId,
        string $label,
        string $defaultModel,
        string $endpoint,
        Client $httpClient,
        int $timeoutSeconds
    ) {
        parent::__construct($providerId, $label, $defaultModel);

        $this->endpoint = rtrim(trim($endpoint), '/');
        $this->httpClient = $httpClient;
        $this->timeoutSeconds = max(5, $timeoutSeconds);
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
        $payload = [
            'model' => $this->getDefaultModel(),
            'stream' => false,
            'format' => 'json',
            'messages' => [
                ['role' => 'system', 'content' => $prompt['system']],
                ['role' => 'user', 'content' => $prompt['user']],
            ],
            'options' => [
                'temperature' => 0.1,
            ],
        ];

        $response = $this->httpClient->post(
            $this->endpoint . '/api/chat',
            (string) json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            [
                'headers' => [
                    'Content-Type: application/json',
                    'Accept: application/json',
                ],
                'timeout' => $this->timeoutSeconds,
                'connectTimeout' => min(5, $this->timeoutSeconds),
                'maxBytes' => 2 * 1024 * 1024,
                'allowedContentTypes' => ['application/json', 'text/plain'],
                'allowPrivateHosts' => true,
            ]
        );

        if (!$response['success']) {
            throw new \RuntimeException($this->buildTransportError($response, 'Ollama'));
        }

        try {
            $decoded = json_decode((string) $response['body'], true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw new \RuntimeException('Ollama lieferte keine gültige JSON-Antwort zurück.');
        }

        $content = trim((string) (($decoded['message']['content'] ?? $decoded['response'] ?? '')));
        if ($content === '') {
            throw new \RuntimeException('Ollama lieferte keine verwertbare Übersetzungsantwort zurück.');
        }

        return $this->extractTranslationsFromResponse($content, $segments);
    }

    /** @param array<string, mixed> $response */
    private function buildTransportError(array $response, string $providerLabel): string
    {
        $message = trim((string) ($response['error'] ?? ''));
        $body = trim((string) ($response['body'] ?? ''));

        if ($body !== '') {
            try {
                $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
                $bodyMessage = trim((string) ($decoded['error'] ?? $decoded['message'] ?? ''));
                if ($bodyMessage !== '') {
                    $message = $bodyMessage;
                }
            } catch (\Throwable) {
                // Ignore non-JSON bodies.
            }
        }

        $message = $message !== '' ? $message : $providerLabel . '-Request ist fehlgeschlagen.';

        return $providerLabel . ': ' . $message;
    }
}
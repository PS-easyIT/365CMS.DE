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
    /** @var list<string> */
    private array $allowedInternalHosts;

    public function __construct(
        string $providerId,
        string $label,
        string $defaultModel,
        string $endpoint,
        Client $httpClient,
        int $timeoutSeconds,
        array $allowedInternalHosts = []
    ) {
        parent::__construct($providerId, $label, $defaultModel);

        $this->endpoint = rtrim(trim($endpoint), '/');
        $this->httpClient = $httpClient;
        $this->timeoutSeconds = max(5, $timeoutSeconds);
        $this->allowedInternalHosts = array_values(array_unique(array_filter(array_map(
            static fn (mixed $host): string => strtolower(trim((string) $host)),
            $allowedInternalHosts
        ), static fn (string $host): bool => $host !== '')));
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
            'format' => 'json',
        ]);

        return $this->extractTranslationsFromResponse($content, $segments);
    }

    /**
     * @param list<array{role:string,content:string}> $messages
     * @param array<string, mixed> $options
     */
    public function complete(array $messages, array $options = []): string
    {
        $payload = [
            'model' => $this->getDefaultModel(),
            'stream' => false,
            'format' => (string) ($options['format'] ?? ''),
            'messages' => $messages,
            'options' => [
                'temperature' => (float) ($options['temperature'] ?? 0.2),
            ],
        ];
        if ($payload['format'] === '') {
            unset($payload['format']);
        }

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
                'allowedPrivateHosts' => $this->allowedInternalHosts,
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
            throw new \RuntimeException('Ollama lieferte keine verwertbare Antwort zurück.');
        }

        return $content;
    }

    /** @param array<string, mixed> $response */
    private function buildTransportError(array $response, string $providerLabel): string
    {
        $message = trim((string) ($response['error'] ?? ''));
        $status = max(0, (int) ($response['status'] ?? 0));
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

        return $providerLabel . ': ' . ($status > 0 ? 'HTTP-Status ' . $status . '. ' : '') . $message;
    }
}
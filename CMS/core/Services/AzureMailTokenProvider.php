<?php
/**
 * Azure OAuth2 Token-Provider für SMTP / XOAUTH2.
 *
 * @package CMSv2\Core\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Http\Client as HttpClient;
use CMS\Logger;

if (!defined('ABSPATH')) {
    exit;
}

class AzureMailTokenProvider
{
    private const GROUP = 'mail';
    private const CACHE_KEY = 'azure_token_cache';
    private const DEFAULT_SCOPE = 'https://outlook.office365.com/.default';
    private const ALLOWED_TOKEN_HOSTS = ['login.microsoftonline.com'];

    private static ?self $instance = null;

    private SettingsService $settings;
    private Logger $logger;
    private HttpClient $httpClient;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->settings = SettingsService::getInstance();
        $this->logger = Logger::instance()->withChannel('mail.azure');
        $this->httpClient = HttpClient::getInstance();
    }

    /**
     * @return array{configured:bool,tenant_id:string,client_id:string,mailbox:string,scope:string,token_endpoint:string}
     */
    public function getConfiguration(): array
    {
        $tenantId = $this->settings->getString(self::GROUP, 'azure_tenant_id');
        $clientId = $this->settings->getString(self::GROUP, 'azure_client_id');
        $mailbox = $this->settings->getString(
            self::GROUP,
            'azure_mailbox',
            $this->settings->getString(self::GROUP, 'smtp_username', '')
        );
        $scope = $this->settings->getString(self::GROUP, 'azure_scope', self::DEFAULT_SCOPE);
        $customEndpoint = $this->settings->getString(self::GROUP, 'azure_token_endpoint');
        $defaultTokenEndpoint = $this->buildTokenEndpoint($tenantId !== '' ? $tenantId : 'common');

        return [
            'configured' => $tenantId !== '' && $clientId !== '' && $this->settings->getString(self::GROUP, 'azure_client_secret') !== '' && $mailbox !== '',
            'tenant_id' => $tenantId,
            'client_id' => $clientId,
            'mailbox' => $mailbox,
            'scope' => $scope !== '' ? $scope : self::DEFAULT_SCOPE,
            'token_endpoint' => $this->normalizeTokenEndpoint($customEndpoint !== '' ? $customEndpoint : $defaultTokenEndpoint, $defaultTokenEndpoint),
        ];
    }

    public function isConfigured(): bool
    {
        return $this->getConfiguration()['configured'];
    }

    /**
     * @return array{access_token:string,token_type:string,expires_at:int,expires_in:int}
     */
    public function getAccessToken(bool $forceRefresh = false): array
    {
        if (!$forceRefresh) {
            $cached = $this->settings->get(self::GROUP, self::CACHE_KEY, []);
            if (is_array($cached)
                && !empty($cached['access_token'])
                && !empty($cached['expires_at'])
                && (int) $cached['expires_at'] > (time() + 60)
            ) {
                return [
                    'access_token' => (string) $cached['access_token'],
                    'token_type' => (string) ($cached['token_type'] ?? 'Bearer'),
                    'expires_at' => (int) $cached['expires_at'],
                    'expires_in' => max(0, (int) $cached['expires_at'] - time()),
                ];
            }
        }

        $config = $this->getConfiguration();
        $clientSecret = $this->settings->getString(self::GROUP, 'azure_client_secret');

        if (!$config['configured'] || $clientSecret === '') {
            throw new \RuntimeException('Azure-Mail-Konfiguration ist unvollständig.');
        }

        $response = $this->requestToken(
            $config['token_endpoint'],
            [
                'client_id' => $config['client_id'],
                'client_secret' => $clientSecret,
                'scope' => $config['scope'],
                'grant_type' => 'client_credentials',
            ]
        );

        $expiresIn = max(60, (int) ($response['expires_in'] ?? 3600));
        $token = [
            'access_token' => (string) ($response['access_token'] ?? ''),
            'token_type' => (string) ($response['token_type'] ?? 'Bearer'),
            'expires_at' => time() + $expiresIn,
            'expires_in' => $expiresIn,
        ];

        if ($token['access_token'] === '') {
            throw new \RuntimeException('Azure hat keinen Access Token zurückgegeben.');
        }

        $this->settings->set(self::GROUP, self::CACHE_KEY, $token, true, 0);

        return $token;
    }

    public function clearCache(): void
    {
        $this->settings->forget(self::GROUP, self::CACHE_KEY);
    }

    private function buildTokenEndpoint(string $tenantId): string
    {
        return 'https://login.microsoftonline.com/' . rawurlencode($tenantId) . '/oauth2/v2.0/token';
    }

    /**
     * @return array<string, mixed>
     */
    private function requestToken(string $url, array $payload): array
    {
        $response = $this->httpClient->postForm($url, $payload, [
            'userAgent' => '365CMS-AzureMail/1.0',
            'timeout' => 20,
            'connectTimeout' => 10,
            'maxBytes' => 256 * 1024,
            'allowedContentTypes' => ['application/json'],
        ]);

        $body = (string) ($response['body'] ?? '');
        $httpCode = (int) ($response['status'] ?? 0);

        if ($body === '') {
            throw new \RuntimeException('Azure OAuth2 lieferte keine Antwort. ' . (string) ($response['error'] ?? ''));
        }

        try {
            $decoded = json_decode($body, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Azure OAuth2 lieferte ungültiges JSON: ' . $e->getMessage());
        }

        if (($response['success'] ?? false) !== true || $httpCode >= 400) {
            $message = (string) ($decoded['error_description'] ?? $decoded['error'] ?? 'Unbekannter Azure-Fehler');
            $this->logger->warning('Azure-Tokenabruf fehlgeschlagen', [
                'http_code' => $httpCode,
                'message' => $message,
                'client_error' => (string) ($response['error'] ?? ''),
            ]);
            throw new \RuntimeException('Azure OAuth2 Fehler (' . $httpCode . '): ' . trim($message));
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeTokenEndpoint(string $url, string $fallback): string
    {
        $normalized = rtrim(trim($url), '/');
        if ($normalized === '') {
            return $fallback;
        }

        $parts = parse_url($normalized);
        $host = strtolower((string)($parts['host'] ?? ''));
        $scheme = strtolower((string)($parts['scheme'] ?? ''));
        $path = (string)($parts['path'] ?? '');

        if (!is_array($parts)
            || $scheme !== 'https'
            || $host === ''
            || !in_array($host, self::ALLOWED_TOKEN_HOSTS, true)
            || !str_ends_with($path, '/oauth2/v2.0/token')
        ) {
            $this->logger->warning('Azure-Token-Endpoint verworfen: Host, Schema oder Pfad nicht erlaubt', [
                'url' => $url,
                'fallback' => $fallback,
                'allowed_hosts' => self::ALLOWED_TOKEN_HOSTS,
            ]);
            return $fallback;
        }

        return $normalized;
    }
}

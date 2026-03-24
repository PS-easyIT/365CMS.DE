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
    private const ALLOWED_TOKEN_TENANTS = ['common', 'organizations', 'consumers'];
    private const ALLOWED_SCOPES = [
        'https://outlook.office365.com/.default',
        'https://outlook.office.com/.default',
    ];
    private const MAX_RESPONSE_BYTES = 262144;
    private const MAX_REMOTE_ERROR_LENGTH = 180;

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
        $tenantId = $this->sanitizeTenantId($this->settings->getString(self::GROUP, 'azure_tenant_id'));
        $clientId = $this->sanitizeClientId($this->settings->getString(self::GROUP, 'azure_client_id'));
        $mailbox = $this->normalizeMailbox($this->settings->getString(
            self::GROUP,
            'azure_mailbox',
            $this->settings->getString(self::GROUP, 'smtp_username', '')
        ));
        $scope = $this->normalizeScope($this->settings->getString(self::GROUP, 'azure_scope', self::DEFAULT_SCOPE));
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
            $cached = $this->getValidCachedToken();
            if ($cached !== null) {
                return $cached;
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
            'token_type' => $this->normalizeTokenType((string) ($response['token_type'] ?? 'Bearer')),
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
        $normalizedTenant = $this->sanitizeTenantId($tenantId);
        if ($normalizedTenant === '') {
            $normalizedTenant = 'common';
        }

        return 'https://login.microsoftonline.com/' . rawurlencode($normalizedTenant) . '/oauth2/v2.0/token';
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
            'maxBytes' => self::MAX_RESPONSE_BYTES,
            'allowedContentTypes' => ['application/json'],
        ]);

        return $this->decodeJsonResponse($response, 'Azure OAuth2');
    }

    private function normalizeTokenEndpoint(string $url, string $fallback): string
    {
        $normalized = $this->normalizeAllowedUrl($url, $fallback, self::ALLOWED_TOKEN_HOSTS, 'Azure-Token-Endpoint');
        $parts = parse_url($normalized);
        $path = (string)($parts['path'] ?? '');

        if (!is_array($parts) || isset($parts['query']) || isset($parts['fragment'])) {
            $this->logger->warning('Azure-Token-Endpoint verworfen: Query oder Fragment nicht erlaubt', [
                'url' => $url,
                'fallback' => $fallback,
            ]);
            return $fallback;
        }

        if (!str_ends_with($path, '/oauth2/v2.0/token')) {
            $this->logger->warning('Azure-Token-Endpoint verworfen: unerwarteter Pfad', [
                'url' => $url,
                'fallback' => $fallback,
            ]);
            return $fallback;
        }

        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        $tenantSegment = rawurldecode((string)($segments[0] ?? ''));
        if ($this->sanitizeTenantId($tenantSegment) === '') {
            $this->logger->warning('Azure-Token-Endpoint verworfen: ungültiger Tenant-Pfad', [
                'url' => $url,
                'fallback' => $fallback,
            ]);
            return $fallback;
        }

        return $normalized;
    }

    /**
     * @return array{access_token:string,token_type:string,expires_at:int,expires_in:int}|null
     */
    private function getValidCachedToken(): ?array
    {
        $cached = $this->settings->get(self::GROUP, self::CACHE_KEY, []);
        if (!is_array($cached)) {
            return null;
        }

        $accessToken = trim((string)($cached['access_token'] ?? ''));
        $expiresAt = (int)($cached['expires_at'] ?? 0);
        if ($accessToken === '' || $expiresAt <= (time() + 60)) {
            if ($cached !== []) {
                $this->settings->forget(self::GROUP, self::CACHE_KEY);
            }

            return null;
        }

        return [
            'access_token' => $accessToken,
            'token_type' => $this->normalizeTokenType((string)($cached['token_type'] ?? 'Bearer')),
            'expires_at' => $expiresAt,
            'expires_in' => max(0, $expiresAt - time()),
        ];
    }

    /**
     * @param string[] $allowedHosts
     */
    private function normalizeAllowedUrl(string $url, string $fallback, array $allowedHosts, string $label): string
    {
        $normalized = rtrim(trim($url), '/');
        if ($normalized === '') {
            return rtrim($fallback, '/');
        }

        $parts = parse_url($normalized);
        $host = strtolower((string)($parts['host'] ?? ''));
        $scheme = strtolower((string)($parts['scheme'] ?? ''));

        if (!is_array($parts) || $scheme !== 'https' || $host === '' || !in_array($host, $allowedHosts, true)) {
            $this->logger->warning($label . ' verworfen: Host oder Schema nicht erlaubt', [
                'url' => $url,
                'fallback' => $fallback,
                'allowed_hosts' => $allowedHosts,
            ]);
            return rtrim($fallback, '/');
        }

        return $normalized;
    }

    private function sanitizeTenantId(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', '', $value) ?? '';
        if ($value === '') {
            return '';
        }

        if (in_array($value, self::ALLOWED_TOKEN_TENANTS, true)) {
            return $value;
        }

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $value)) {
            return $value;
        }

        if (preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,63}$/', $value)) {
            return $value;
        }

        $this->logger->warning('Azure-Tenant-ID verworfen', ['tenant_id' => $value]);
        return '';
    }

    private function sanitizeClientId(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', '', $value) ?? '';
        if ($value === '') {
            return '';
        }

        if (preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $value)) {
            return $value;
        }

        $this->logger->warning('Azure-Client-ID verworfen', ['client_id' => $value]);
        return '';
    }

    private function normalizeMailbox(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', '', $value) ?? '';
        if ($value === '') {
            return '';
        }

        $validated = filter_var($value, FILTER_VALIDATE_EMAIL);
        if ($validated === false) {
            $this->logger->warning('Azure-Mailbox verworfen', ['mailbox' => $this->sanitizeRemoteMessage($value)]);
            return '';
        }

        return (string)$validated;
    }

    private function normalizeScope(string $value): string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', '', $value) ?? '';
        if ($value === '') {
            return self::DEFAULT_SCOPE;
        }

        if (!in_array($value, self::ALLOWED_SCOPES, true)) {
            $this->logger->warning('Azure-Mail-Scope verworfen', ['scope' => $value]);
            return self::DEFAULT_SCOPE;
        }

        return $value;
    }

    private function normalizeTokenType(string $value): string
    {
        $value = trim($value);
        if (strcasecmp($value, 'bearer') === 0 || $value === '') {
            return 'Bearer';
        }

        return 'Bearer';
    }

    /**
     * @param array{success?: bool, status?: int, body?: string, headers?: array<string,string>, contentType?: string, error?: string} $response
     * @return array<string, mixed>
     */
    private function decodeJsonResponse(array $response, string $contextLabel): array
    {
        $responseBody = (string)($response['body'] ?? '');
        if ($responseBody === '') {
            throw new \RuntimeException($contextLabel . ' lieferte keine Antwort.');
        }

        try {
            $decoded = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw new \RuntimeException($contextLabel . ' lieferte ungültiges JSON.');
        }

        $httpCode = (int)($response['status'] ?? 0);
        if (($response['success'] ?? false) !== true || $httpCode >= 400) {
            $message = $this->extractRemoteError(is_array($decoded) ? $decoded : []);
            $this->logger->warning('Azure-Tokenabruf fehlgeschlagen', [
                'http_code' => $httpCode,
                'message' => $message,
                'client_error' => $this->sanitizeRemoteMessage((string)($response['error'] ?? '')),
            ]);
            throw new \RuntimeException($contextLabel . ' Fehler (' . $httpCode . '): ' . $message);
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $decoded
     */
    private function extractRemoteError(array $decoded): string
    {
        $message = $decoded['error_description']
            ?? $decoded['error']['message']
            ?? $decoded['error']
            ?? 'Unbekannter Azure-Fehler';

        return $this->sanitizeRemoteMessage((string)$message);
    }

    private function sanitizeRemoteMessage(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';
        if ($value === '') {
            return 'Unbekannter Azure-Fehler';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, self::MAX_REMOTE_ERROR_LENGTH);
        }

        return substr($value, 0, self::MAX_REMOTE_ERROR_LENGTH);
    }
}

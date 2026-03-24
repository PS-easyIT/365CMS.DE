<?php
/**
 * Kleiner Microsoft-Graph-Service auf Basis von Client-Credentials.
 *
 * Bewusst leichtgewichtig via cURL, damit keine zusätzliche SDK-Ladung im
 * Deployment zwingend nötig ist.
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

class GraphApiService
{
    private const GROUP = 'graph';
    private const CACHE_KEY = 'token_cache';
    private const DEFAULT_SCOPE = 'https://graph.microsoft.com/.default';
    private const DEFAULT_BASE_URL = 'https://graph.microsoft.com/v1.0';
    private const ALLOWED_GRAPH_HOSTS = ['graph.microsoft.com'];
    private const ALLOWED_TOKEN_HOSTS = ['login.microsoftonline.com'];
    private const ALLOWED_TOKEN_TENANTS = ['common', 'organizations', 'consumers'];
    private const ALLOWED_GRAPH_BASE_PATHS = ['/v1.0', '/beta'];
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
        $this->logger = Logger::instance()->withChannel('graph');
        $this->httpClient = HttpClient::getInstance();
    }

    /**
     * @return array{configured:bool,tenant_id:string,client_id:string,scope:string,base_url:string,token_endpoint:string}
     */
    public function getConfiguration(): array
    {
        $tenantId = $this->sanitizeTenantId($this->settings->getString(self::GROUP, 'tenant_id'));
        $clientId = $this->sanitizeClientId($this->settings->getString(self::GROUP, 'client_id'));
        $scope = $this->normalizeScope($this->settings->getString(self::GROUP, 'scope', self::DEFAULT_SCOPE));
        $baseUrl = $this->normalizeGraphBaseUrl($this->settings->getString(self::GROUP, 'base_url', self::DEFAULT_BASE_URL));
        $customEndpoint = $this->settings->getString(self::GROUP, 'token_endpoint');
        $defaultTokenEndpoint = $this->buildTokenEndpoint($tenantId !== '' ? $tenantId : 'common');

        return [
            'configured' => $tenantId !== '' && $clientId !== '' && $this->settings->getString(self::GROUP, 'client_secret') !== '',
            'tenant_id' => $tenantId,
            'client_id' => $clientId,
            'scope' => $scope !== '' ? $scope : self::DEFAULT_SCOPE,
            'base_url' => $baseUrl,
            'token_endpoint' => $this->normalizeTokenEndpoint($customEndpoint !== '' ? $customEndpoint : $defaultTokenEndpoint, $defaultTokenEndpoint),
        ];
    }

    public function isConfigured(): bool
    {
        return $this->getConfiguration()['configured'];
    }

    public function clearCache(): void
    {
        $this->settings->forget(self::GROUP, self::CACHE_KEY);
    }

    /**
     * @return array{success:bool,message?:string,error?:string,organization?:array<string,mixed>,token_expires_at?:int}
     */
    public function testConnection(bool $forceRefresh = false): array
    {
        try {
            $token = $this->getAccessToken($forceRefresh);
            $config = $this->getConfiguration();
            $organization = $this->fetchOrganization($token['access_token'], $config['base_url']);

            return [
                'success' => true,
                'message' => 'Microsoft Graph erfolgreich verbunden.',
                'organization' => $organization,
                'token_expires_at' => (int) $token['expires_at'],
            ];
        } catch (\Throwable $e) {
            $this->logger->warning('Microsoft-Graph-Test fehlgeschlagen', [
                'exception' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Microsoft-Graph-Verbindung fehlgeschlagen. Konfiguration und Logs prüfen.',
            ];
        }
    }

    /**
     * @return array{access_token:string,expires_at:int}
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
                    'expires_at' => (int) $cached['expires_at'],
                ];
            }
        }

        $config = $this->getConfiguration();
        $clientSecret = $this->settings->getString(self::GROUP, 'client_secret');
        if (!$config['configured'] || $clientSecret === '') {
            throw new \RuntimeException('Microsoft-Graph-Konfiguration ist unvollständig.');
        }

        $response = $this->requestToken($config['token_endpoint'], [
            'client_id' => $config['client_id'],
            'client_secret' => $clientSecret,
            'scope' => $config['scope'],
            'grant_type' => 'client_credentials',
        ]);

        $expiresIn = max(60, (int) ($response['expires_in'] ?? 3600));
        $token = [
            'access_token' => (string) ($response['access_token'] ?? ''),
            'expires_at' => time() + $expiresIn,
        ];

        if ($token['access_token'] === '') {
            throw new \RuntimeException('Microsoft Graph hat keinen Access Token zurückgegeben.');
        }

        $this->settings->set(self::GROUP, self::CACHE_KEY, $token, true, 0);

        return $token;
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchOrganization(string $accessToken, string $baseUrl): array
    {
        $url = $baseUrl . '/organization?$select=id,displayName,verifiedDomains';
        $response = $this->requestJson('GET', $url, [
            'Authorization: Bearer ' . $accessToken,
            'Accept: application/json',
        ]);

        $items = $response['value'] ?? [];
        if (!is_array($items) || empty($items[0]) || !is_array($items[0])) {
            return [];
        }

        return $this->normalizeOrganization($items[0]);
    }

    /**
     * @return array<string, mixed>
     */
    private function requestToken(string $url, array $payload): array
    {
        $response = $this->httpClient->postForm($url, $payload, [
            'userAgent' => '365CMS-GraphClient/1.0',
            'timeout' => 20,
            'connectTimeout' => 10,
            'maxBytes' => self::MAX_RESPONSE_BYTES,
            'allowedContentTypes' => ['application/json'],
        ]);

        return $this->decodeJsonResponse($response, 'Microsoft-Graph-Token');
    }

    /**
     * @return array<string, mixed>
     */
    private function requestJson(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        $options = [
            'headers' => $headers,
            'timeout' => 20,
            'connectTimeout' => 5,
            'maxBytes' => self::MAX_RESPONSE_BYTES,
            'allowedContentTypes' => ['application/json', 'application/problem+json', 'text/json'],
            'userAgent' => '365CMS-GraphClient/1.0',
        ];

        $response = strtoupper($method) === 'POST'
            ? $this->httpClient->post($url, $body ?? '', $options)
            : $this->httpClient->get($url, $options);

        return $this->decodeJsonResponse($response, 'Microsoft Graph');
    }

    private function normalizeGraphBaseUrl(string $url): string
    {
        $normalized = rtrim($this->normalizeAllowedUrl($url, self::DEFAULT_BASE_URL, self::ALLOWED_GRAPH_HOSTS, 'Microsoft-Graph-Basis-URL'), '/');
        $parts = parse_url($normalized);
        $path = (string)($parts['path'] ?? '');

        if (!is_array($parts)
            || isset($parts['query'])
            || isset($parts['fragment'])
            || !in_array($path, self::ALLOWED_GRAPH_BASE_PATHS, true)
        ) {
            $this->logger->warning('Microsoft-Graph-Basis-URL verworfen: Pfad nicht erlaubt', [
                'url' => $url,
                'fallback' => self::DEFAULT_BASE_URL,
            ]);

            return self::DEFAULT_BASE_URL;
        }

        return $normalized;
    }

    private function normalizeTokenEndpoint(string $url, string $fallback): string
    {
        $normalized = $this->normalizeAllowedUrl($url, $fallback, self::ALLOWED_TOKEN_HOSTS, 'Microsoft-Graph-Token-Endpoint');
        $parts = parse_url($normalized);
        $path = (string) ($parts['path'] ?? '');

        if (!is_array($parts) || isset($parts['query']) || isset($parts['fragment'])) {
            $this->logger->warning('Microsoft-Graph-Token-Endpoint verworfen: Query oder Fragment nicht erlaubt', [
                'url' => $url,
                'fallback' => $fallback,
            ]);
            return $fallback;
        }

        if (!str_ends_with($path, '/oauth2/v2.0/token')) {
            $this->logger->warning('Microsoft-Graph-Token-Endpoint verworfen: unerwarteter Pfad', [
                'url' => $url,
                'fallback' => $fallback,
            ]);
            return $fallback;
        }

        $segments = array_values(array_filter(explode('/', trim($path, '/'))));
        $tenantSegment = rawurldecode((string)($segments[0] ?? ''));
        if ($this->sanitizeTenantId($tenantSegment) === '') {
            $this->logger->warning('Microsoft-Graph-Token-Endpoint verworfen: ungültiger Tenant-Pfad', [
                'url' => $url,
                'fallback' => $fallback,
            ]);
            return $fallback;
        }

        return $normalized;
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

    private function buildTokenEndpoint(string $tenantId): string
    {
        $normalizedTenant = $this->sanitizeTenantId($tenantId);
        if ($normalizedTenant === '') {
            $normalizedTenant = 'common';
        }

        return 'https://login.microsoftonline.com/' . rawurlencode($normalizedTenant) . '/oauth2/v2.0/token';
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

        $this->logger->warning('Microsoft-Graph-Tenant-ID verworfen', ['tenant_id' => $value]);
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

        $this->logger->warning('Microsoft-Graph-Client-ID verworfen', ['client_id' => $value]);
        return '';
    }

    private function normalizeScope(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', '', $value) ?? '';
        if ($value === '') {
            return self::DEFAULT_SCOPE;
        }

        if (!preg_match('#^https://graph\.microsoft\.com/\.default$#i', $value)) {
            $this->logger->warning('Microsoft-Graph-Scope verworfen', ['scope' => $value]);
            return self::DEFAULT_SCOPE;
        }

        return self::DEFAULT_SCOPE;
    }

    /**
     * @param array{success: bool, status: int, body: string, headers: array<string,string>, contentType: string, error?: string} $response
     * @return array<string, mixed>
     */
    private function decodeJsonResponse(array $response, string $contextLabel): array
    {
        $responseBody = (string) ($response['body'] ?? '');
        if ($responseBody === '') {
            throw new \RuntimeException($contextLabel . ' lieferte keine Antwort.');
        }

        try {
            $decoded = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable) {
            throw new \RuntimeException($contextLabel . ' lieferte ungültiges JSON.');
        }

        if (($response['success'] ?? false) !== true) {
            $httpCode = (int) ($response['status'] ?? 0);
            $error = $this->extractRemoteError($decoded);
            throw new \RuntimeException($contextLabel . ' Fehler (' . $httpCode . '): ' . $error);
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param array<string, mixed> $decoded
     */
    private function extractRemoteError(array $decoded): string
    {
        $message = $decoded['error']['message']
            ?? $decoded['error_description']
            ?? $decoded['error']['code']
            ?? $decoded['error']
            ?? 'Unbekannter Graph-Fehler';

        return $this->sanitizeRemoteMessage((string) $message);
    }

    private function sanitizeRemoteMessage(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';
        if ($value === '') {
            return 'Unbekannter Graph-Fehler';
        }

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, self::MAX_REMOTE_ERROR_LENGTH);
        }

        return substr($value, 0, self::MAX_REMOTE_ERROR_LENGTH);
    }

    /**
     * @param array<string, mixed> $organization
     * @return array<string, mixed>
     */
    private function normalizeOrganization(array $organization): array
    {
        $verifiedDomains = [];
        $domains = $organization['verifiedDomains'] ?? [];

        if (is_array($domains)) {
            foreach (array_slice($domains, 0, 20) as $domain) {
                if (!is_array($domain)) {
                    continue;
                }

                $verifiedDomains[] = [
                    'name' => $this->sanitizeRemoteMessage((string)($domain['name'] ?? '')),
                    'type' => $this->sanitizeRemoteMessage((string)($domain['type'] ?? '')),
                    'isDefault' => !empty($domain['isDefault']),
                    'isInitial' => !empty($domain['isInitial']),
                ];
            }
        }

        return [
            'id' => $this->sanitizeRemoteMessage((string)($organization['id'] ?? '')),
            'displayName' => $this->sanitizeRemoteMessage((string)($organization['displayName'] ?? '')),
            'verifiedDomains' => $verifiedDomains,
        ];
    }
}

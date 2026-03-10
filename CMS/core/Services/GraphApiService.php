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
        $tenantId = $this->settings->getString(self::GROUP, 'tenant_id');
        $clientId = $this->settings->getString(self::GROUP, 'client_id');
        $scope = $this->settings->getString(self::GROUP, 'scope', self::DEFAULT_SCOPE);
        $baseUrl = $this->normalizeGraphBaseUrl($this->settings->getString(self::GROUP, 'base_url', self::DEFAULT_BASE_URL));
        $customEndpoint = $this->settings->getString(self::GROUP, 'token_endpoint');
        $defaultTokenEndpoint = 'https://login.microsoftonline.com/' . rawurlencode($tenantId !== '' ? $tenantId : 'common') . '/oauth2/v2.0/token';

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
                'exception' => $e,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
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

        return $items[0];
    }

    /**
     * @return array<string, mixed>
     */
    private function requestToken(string $url, array $payload): array
    {
        return $this->requestJson('POST', $url, [], http_build_query($payload, '', '&', PHP_QUERY_RFC3986));
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
            'allowedContentTypes' => ['application/json'],
            'userAgent' => '365CMS-GraphClient/1.0',
        ];

        $response = strtoupper($method) === 'POST'
            ? $this->httpClient->post($url, $body ?? '', $options)
            : $this->httpClient->get($url, $options);

        $responseBody = (string) ($response['body'] ?? '');
        if ($responseBody === '') {
            throw new \RuntimeException('Microsoft Graph lieferte keine Antwort. ' . (string) ($response['error'] ?? '')); 
        }

        try {
            $decoded = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Microsoft Graph lieferte ungültiges JSON: ' . $e->getMessage());
        }

        if (!$response['success']) {
            $httpCode = (int) ($response['status'] ?? 0);
            $error = $decoded['error']['message'] ?? $decoded['error_description'] ?? $decoded['error'] ?? 'Unbekannter Graph-Fehler';
            throw new \RuntimeException('Microsoft Graph Fehler (' . $httpCode . '): ' . trim((string) $error));
        }

        return is_array($decoded) ? $decoded : [];
    }

    private function normalizeGraphBaseUrl(string $url): string
    {
        return rtrim($this->normalizeAllowedUrl($url, self::DEFAULT_BASE_URL, self::ALLOWED_GRAPH_HOSTS, 'Microsoft-Graph-Basis-URL'), '/');
    }

    private function normalizeTokenEndpoint(string $url, string $fallback): string
    {
        $normalized = $this->normalizeAllowedUrl($url, $fallback, self::ALLOWED_TOKEN_HOSTS, 'Microsoft-Graph-Token-Endpoint');
        $path = (string) (parse_url($normalized, PHP_URL_PATH) ?? '');

        if (!str_ends_with($path, '/oauth2/v2.0/token')) {
            $this->logger->warning('Microsoft-Graph-Token-Endpoint verworfen: unerwarteter Pfad', [
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
}

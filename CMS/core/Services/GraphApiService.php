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

    private static ?self $instance = null;

    private SettingsService $settings;
    private Logger $logger;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->settings = SettingsService::getInstance();
        $this->logger = Logger::instance()->withChannel('graph');
    }

    /**
     * @return array{configured:bool,tenant_id:string,client_id:string,scope:string,base_url:string,token_endpoint:string}
     */
    public function getConfiguration(): array
    {
        $tenantId = $this->settings->getString(self::GROUP, 'tenant_id');
        $clientId = $this->settings->getString(self::GROUP, 'client_id');
        $scope = $this->settings->getString(self::GROUP, 'scope', self::DEFAULT_SCOPE);
        $baseUrl = rtrim($this->settings->getString(self::GROUP, 'base_url', self::DEFAULT_BASE_URL), '/');
        $customEndpoint = $this->settings->getString(self::GROUP, 'token_endpoint');

        return [
            'configured' => $tenantId !== '' && $clientId !== '' && $this->settings->getString(self::GROUP, 'client_secret') !== '',
            'tenant_id' => $tenantId,
            'client_id' => $clientId,
            'scope' => $scope !== '' ? $scope : self::DEFAULT_SCOPE,
            'base_url' => $baseUrl !== '' ? $baseUrl : self::DEFAULT_BASE_URL,
            'token_endpoint' => $customEndpoint !== ''
                ? $customEndpoint
                : 'https://login.microsoftonline.com/' . rawurlencode($tenantId !== '' ? $tenantId : 'common') . '/oauth2/v2.0/token',
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
        return $this->requestJson('POST', $url, ['Content-Type: application/x-www-form-urlencoded'], http_build_query($payload, '', '&', PHP_QUERY_RFC3986));
    }

    /**
     * @return array<string, mixed>
     */
    private function requestJson(string $method, string $url, array $headers = [], ?string $body = null): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new \RuntimeException('cURL konnte für Microsoft Graph nicht initialisiert werden.');
        }

        curl_setopt_array($ch, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 20,
            CURLOPT_HTTPHEADER => $headers,
        ]);

        if ($body !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        $responseBody = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if (!is_string($responseBody) || $responseBody === '') {
            throw new \RuntimeException('Microsoft Graph lieferte keine Antwort. ' . $curlError);
        }

        try {
            $decoded = json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);
        } catch (\Throwable $e) {
            throw new \RuntimeException('Microsoft Graph lieferte ungültiges JSON: ' . $e->getMessage());
        }

        if ($httpCode >= 400) {
            $error = $decoded['error']['message'] ?? $decoded['error_description'] ?? $decoded['error'] ?? 'Unbekannter Graph-Fehler';
            throw new \RuntimeException('Microsoft Graph Fehler (' . $httpCode . '): ' . trim((string) $error));
        }

        return is_array($decoded) ? $decoded : [];
    }
}

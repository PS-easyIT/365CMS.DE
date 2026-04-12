<?php
declare(strict_types=1);

namespace CMS\Http;

if (!defined('ABSPATH')) {
    exit;
}

final class Client
{
    private static ?self $instance = null;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
    }

    /**
     * @param array{
     *   userAgent?: string,
     *   headers?: string[],
     *   body?: string,
     *   timeout?: int,
     *   connectTimeout?: int,
     *   maxBytes?: int,
     *   allowedContentTypes?: string[],
    *   allowPrivateHosts?: bool,
    *   allowUnresolvedHosts?: bool
     * } $options
     * @return array{success: bool, status: int, body: string, headers: array<string,string>, contentType: string, error?: string}
     */
    public function get(string $url, array $options = []): array
    {
        return $this->request('GET', $url, $options);
    }

    /**
     * @param array{
     *   userAgent?: string,
     *   headers?: string[],
     *   body?: string,
     *   timeout?: int,
     *   connectTimeout?: int,
     *   maxBytes?: int,
     *   allowedContentTypes?: string[],
    *   allowPrivateHosts?: bool,
    *   allowUnresolvedHosts?: bool
     * } $options
     * @return array{success: bool, status: int, body: string, headers: array<string,string>, contentType: string, error?: string}
     */
    public function post(string $url, string $body = '', array $options = []): array
    {
        $options['body'] = $body;

        return $this->request('POST', $url, $options);
    }

    /**
     * @param array<string, scalar|null> $formData
     * @param array{
     *   userAgent?: string,
     *   headers?: string[],
     *   timeout?: int,
     *   connectTimeout?: int,
     *   maxBytes?: int,
     *   allowedContentTypes?: string[],
    *   allowPrivateHosts?: bool,
    *   allowUnresolvedHosts?: bool
     * } $options
     * @return array{success: bool, status: int, body: string, headers: array<string,string>, contentType: string, error?: string}
     */
    public function postForm(string $url, array $formData, array $options = []): array
    {
        $headers = (array) ($options['headers'] ?? []);
        $headers[] = 'Content-Type: application/x-www-form-urlencoded';
        $options['headers'] = $headers;
        $options['body'] = http_build_query($formData, '', '&', PHP_QUERY_RFC3986);

        return $this->request('POST', $url, $options);
    }

    /**
     * @param array{
     *   userAgent?: string,
     *   headers?: string[],
     *   body?: string,
     *   timeout?: int,
     *   connectTimeout?: int,
     *   maxBytes?: int,
     *   allowedContentTypes?: string[],
    *   allowPrivateHosts?: bool,
    *   allowUnresolvedHosts?: bool
     * } $options
     * @return array{success: bool, status: int, body: string, headers: array<string,string>, contentType: string, error?: string}
     */
    public function put(string $url, string $body = '', array $options = []): array
    {
        $options['body'] = $body;

        return $this->request('PUT', $url, $options);
    }

    /**
     * @param array{
     *   userAgent?: string,
     *   headers?: string[],
     *   body?: string,
     *   timeout?: int,
     *   connectTimeout?: int,
     *   maxBytes?: int,
     *   allowedContentTypes?: string[],
    *   allowPrivateHosts?: bool,
    *   allowUnresolvedHosts?: bool
     * } $options
     * @return array{success: bool, status: int, body: string, headers: array<string,string>, contentType: string, error?: string}
     */
    private function request(string $method, string $url, array $options = []): array
    {
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->failure('Ungültige URL.');
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));
        if (!in_array($scheme, ['http', 'https'], true)) {
            return $this->failure('Nur HTTP- und HTTPS-URLs sind erlaubt.');
        }

        if (!(bool) ($options['allowPrivateHosts'] ?? false) && !$this->isSafeExternalUrl($url, (bool) ($options['allowUnresolvedHosts'] ?? false))) {
            return $this->failure('URL wurde durch den SSRF-Schutz blockiert.');
        }

        if (!extension_loaded('curl')) {
            return $this->failure('cURL ist nicht verfügbar.');
        }

        $responseHeaders = [];
        $curlHeaders = ['User-Agent: ' . ($options['userAgent'] ?? '365CMS-HttpClient/1.0')];

        foreach ((array) ($options['headers'] ?? []) as $header) {
            if (is_string($header) && trim($header) !== '') {
                $curlHeaders[] = $header;
            }
        }

        $ch = curl_init($url);
        $curlOptions = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_CONNECTTIMEOUT => max(1, (int) ($options['connectTimeout'] ?? 5)),
            CURLOPT_TIMEOUT        => max(1, (int) ($options['timeout'] ?? 10)),
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_HTTPHEADER     => $curlHeaders,
            CURLOPT_CUSTOMREQUEST  => strtoupper($method),
            CURLOPT_HEADERFUNCTION => static function ($curl, string $headerLine) use (&$responseHeaders): int {
                $trimmed = trim($headerLine);
                if ($trimmed === '' || !str_contains($trimmed, ':')) {
                    return strlen($headerLine);
                }

                [$name, $value] = explode(':', $trimmed, 2);
                $responseHeaders[strtolower(trim($name))] = trim($value);

                return strlen($headerLine);
            },
        ];

        $normalizedMethod = strtoupper($method);

        if ($normalizedMethod === 'POST') {
            $curlOptions[CURLOPT_POST] = true;
            $curlOptions[CURLOPT_POSTFIELDS] = (string) ($options['body'] ?? '');
        } elseif (in_array($normalizedMethod, ['PUT', 'PATCH', 'DELETE'], true) && array_key_exists('body', $options)) {
            $curlOptions[CURLOPT_POSTFIELDS] = (string) ($options['body'] ?? '');
        }

        curl_setopt_array($ch, $curlOptions);

        $response = curl_exec($ch);
        $curlError = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $contentType = strtolower(trim((string) curl_getinfo($ch, CURLINFO_CONTENT_TYPE)));
        curl_close($ch);

        if ($contentType !== '' && str_contains($contentType, ';')) {
            $contentType = trim((string) strtok($contentType, ';'));
        }

        if ($response === false) {
            return $this->failure('HTTP-Request fehlgeschlagen: ' . $curlError, $status, $responseHeaders, '', $contentType);
        }

        $body = (string) $response;
        $maxBytes = max(0, (int) ($options['maxBytes'] ?? 0));
        if ($maxBytes > 0 && strlen($body) > $maxBytes) {
            return $this->failure('HTTP-Response überschreitet die erlaubte Maximalgröße.', $status, $responseHeaders, '', $contentType);
        }

        if (!$this->matchesAllowedContentType($contentType, (array) ($options['allowedContentTypes'] ?? []))) {
            return $this->failure('HTTP-Response hat einen nicht erlaubten Content-Type.', $status, $responseHeaders, '', $contentType);
        }

        if ($status < 200 || $status >= 300) {
            return $this->failure('HTTP-Request lieferte Status ' . $status . '.', $status, $responseHeaders, $body, $contentType);
        }

        return [
            'success' => true,
            'status' => $status,
            'body' => $body,
            'headers' => $responseHeaders,
            'contentType' => $contentType,
        ];
    }

    /**
     * @param string[] $allowedContentTypes
     */
    private function matchesAllowedContentType(string $contentType, array $allowedContentTypes): bool
    {
        if ($allowedContentTypes === [] || $contentType === '') {
            return true;
        }

        foreach ($allowedContentTypes as $allowedType) {
            if (stripos($contentType, (string) $allowedType) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{success: bool, status: int, body: string, headers: array<string,string>, contentType: string, error: string}
     */
    private function failure(string $message, int $status = 0, array $headers = [], string $body = '', string $contentType = ''): array
    {
        return [
            'success' => false,
            'status' => $status,
            'body' => $body,
            'headers' => $headers,
            'contentType' => $contentType,
            'error' => $message,
        ];
    }

    private function isPrivateOrReservedIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
    }

    private function isSafeExternalUrl(string $url, bool $allowUnresolvedHosts = false): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        if ($host === '') {
            return false;
        }

        if (in_array($host, ['localhost', 'localhost.localdomain', 'ip6-localhost', 'ip6-loopback'], true)) {
            return false;
        }

        $resolvedIps = $this->resolveHostIps($host);
        if ($resolvedIps === []) {
            \CMS\Logger::instance()->withChannel('http-client')->warning('External HTTP request blocked because the host could not be resolved safely.', [
                'host' => $host,
                'url' => $url,
            ]);
            return $allowUnresolvedHosts;
        }

        foreach ($resolvedIps as $ip) {
            if ($ip !== '' && $this->isPrivateOrReservedIp($ip)) {
                \CMS\Logger::instance()->withChannel('http-client')->warning('External HTTP request blocked because the host resolved to a private IP.', [
                    'host' => $host,
                    'ip' => $ip,
                    'url' => $url,
                ]);
                return false;
            }
        }

        return true;
    }

    /**
     * @return string[]
     */
    private function resolveHostIps(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ips = [];

        if (function_exists('dns_get_record')) {
            $records = @dns_get_record($host, DNS_A | DNS_AAAA);
            if (is_array($records)) {
                foreach ($records as $record) {
                    $ip = trim((string) ($record['ip'] ?? $record['ipv6'] ?? ''));
                    if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                        $ips[] = $ip;
                    }
                }
            }
        }

        if ($ips === [] && function_exists('gethostbynamel')) {
            $fallbackRecords = @gethostbynamel($host);
            if (is_array($fallbackRecords)) {
                foreach ($fallbackRecords as $ip) {
                    $ip = trim((string) $ip);
                    if ($ip !== '' && filter_var($ip, FILTER_VALIDATE_IP)) {
                        $ips[] = $ip;
                    }
                }
            }
        }

        return array_values(array_unique($ips));
    }
}
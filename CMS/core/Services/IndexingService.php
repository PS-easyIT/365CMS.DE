<?php
/**
 * SEO Indexing-Service für IndexNow und Google URL Notifications.
 *
 * @package CMSv2\Core\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Logger;
use CMS\VendorRegistry;
use Melbahja\Seo\Indexing\GoogleIndexer;
use Melbahja\Seo\Indexing\IndexNowEngine;
use Melbahja\Seo\Indexing\IndexNowIndexer;
use Melbahja\Seo\Indexing\URLIndexingType;

if (!defined('ABSPATH')) {
    exit;
}

VendorRegistry::instance()->loadPackage('melbahja-seo');

final class IndexingService
{
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
        $this->logger = Logger::instance()->withChannel('seo.indexing');
    }

    /**
     * Sendet URLs an alle IndexNow-kompatiblen Endpunkte.
     */
    public function submitIndexNow(string|array $urls): bool
    {
        $urlList = $this->normalizeUrls($urls);
        if ($urlList === []) {
            $this->logger->warning('IndexNow-Submission ohne URLs verworfen.');
            return false;
        }

        $apiKey = $this->resolveIndexNowKey();
        if ($apiKey === '') {
            $this->logger->warning('IndexNow-Submission übersprungen: kein API-Key konfiguriert.');
            return false;
        }

        try {
            $indexer = new IndexNowIndexer($apiKey);
            $success = true;

            foreach (IndexNowEngine::cases() as $engine) {
                $results = $indexer->submitUrls($urlList, $engine, URLIndexingType::UPDATE);
                if (in_array(false, $results, true)) {
                    $success = false;
                    $this->logger->warning('IndexNow-Engine meldete Teilerfolg für {engine}.', [
                        'engine' => $engine->name,
                        'url_count' => count($urlList),
                    ]);
                }
            }

            return $success;
        } catch (\Throwable $e) {
            $this->logger->error('IndexNow-Submission fehlgeschlagen.', [
                'url_count' => count($urlList),
                'exception' => $e,
            ]);
            return false;
        }
    }

    /**
     * Sendet URLs an die Google Indexing API.
     */
    public function submitGoogle(string|array $urls, string $accessToken): bool
    {
        $urlList = $this->normalizeUrls($urls);
        $token = trim($accessToken);

        if ($urlList === [] || $token === '') {
            $this->logger->warning('Google-Submission verworfen: Token oder URLs fehlen.', [
                'url_count' => count($urlList),
            ]);
            return false;
        }

        try {
            $indexer = new GoogleIndexer($token);
            $results = $indexer->submitUrls($urlList, URLIndexingType::UPDATE);
            return !in_array(false, $results, true);
        } catch (\Throwable $e) {
            $this->logger->error('Google-Submission fehlgeschlagen.', [
                'url_count' => count($urlList),
                'exception' => $e,
            ]);
            return false;
        }
    }

    /**
     * Entfernt eine URL via Google Indexing API aus dem Index.
     */
    public function deleteGoogle(string $url, string $accessToken): bool
    {
        $normalizedUrl = trim($url);
        $token = trim($accessToken);

        if ($normalizedUrl === '' || $token === '') {
            $this->logger->warning('Google-Delete verworfen: URL oder Token fehlen.');
            return false;
        }

        try {
            $indexer = new GoogleIndexer($token);
            return $indexer->submitUrl($normalizedUrl, URLIndexingType::DELETE);
        } catch (\Throwable $e) {
            $this->logger->error('Google-Delete fehlgeschlagen.', [
                'exception' => $e,
            ]);
            return false;
        }
    }

    public function hasIndexNowKey(): bool
    {
        return $this->resolveIndexNowKey() !== '';
    }

    private function resolveIndexNowKey(): string
    {
        try {
            if (function_exists('config')) {
                $value = config('seo.indexnow_key');
                if (is_string($value) && trim($value) !== '') {
                    return trim($value);
                }
            }
        } catch (\Throwable) {
            // Fallbacks unten greifen.
        }

        $candidates = [
            $this->settings->getString('seo', 'indexnow_key', ''),
            defined('SEO_INDEXNOW_KEY') ? (string) SEO_INDEXNOW_KEY : '',
            function_exists('get_option') ? (string) get_option('seo.indexnow_key', '') : '',
            function_exists('get_option') ? (string) get_option('seo_indexnow_key', '') : '',
        ];

        foreach ($candidates as $candidate) {
            $candidate = trim($candidate);
            if ($candidate !== '') {
                return $candidate;
            }
        }

        return '';
    }

    /**
     * @return array<int, string>
     */
    private function normalizeUrls(string|array $urls): array
    {
        $list = is_array($urls)
            ? $urls
            : (preg_split('/\r\n|\r|\n|,/', $urls) ?: []);
        $normalized = [];

        foreach ($list as $url) {
            $url = trim((string) $url);
            if ($url === '' || filter_var($url, FILTER_VALIDATE_URL) === false) {
                continue;
            }
            $normalized[] = $url;
        }

        return array_values(array_unique($normalized));
    }
}
<?php
/**
 * Feed Service – RSS/Atom-Feed-Parsing via SimplePie
 *
 * Zentraler Service für das Laden und Parsen von RSS/Atom-Feeds.
 * Nutzt die SimplePie-Bibliothek mit File-Cache und optionalem CMS-Cache-Backend.
 *
 * @package CMSv2\Core\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\CacheManager;
use CMS\Logger;

if (!defined('ABSPATH')) {
    exit;
}

final class FeedService
{
    private const MAX_ITEMS_PER_FEED = 100;
    private const MAX_FEEDS_PER_BATCH = 20;
    private const MAX_TITLE_LENGTH = 300;
    private const MAX_DESCRIPTION_LENGTH = 12000;
    private const MAX_CONTENT_LENGTH = 40000;
    private const MAX_CATEGORY_LENGTH = 80;
    private const MAX_CATEGORY_COUNT = 10;
    private const MAX_GUID_LENGTH = 255;
    private const MAX_ERROR_LENGTH = 180;

    private static ?self $instance = null;

    private readonly string $cachePath;
    private readonly bool $available;
    private readonly bool $cacheEnabled;
    private readonly Logger $logger;

    /** Standard-Timeout für Feed-Fetches in Sekunden */
    private int $fetchTimeout = 15;

    /** Standard-Cache-Dauer in Sekunden (1 Stunde) */
    private int $cacheDuration = 3600;

    /** User-Agent-String */
    private string $userAgent;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->cachePath = ABSPATH . '/cache/feeds/';
        $this->available = class_exists(\SimplePie\SimplePie::class);
        $this->logger = Logger::instance()->withChannel('feeds');
        $this->userAgent = '365CMS/' . (defined('CMS_VERSION') ? CMS_VERSION : '2.0') . ' (SimplePie)';
        $this->cacheEnabled = $this->ensureDirectory($this->cachePath, 'Feed-Cache-Verzeichnis');
    }

    // ──────────────────────────────────────────────────────────
    //  Öffentliche API
    // ──────────────────────────────────────────────────────────

    /**
     * Prüft ob SimplePie verfügbar ist.
     */
    public function isAvailable(): bool
    {
        return $this->available;
    }

    /**
     * Einen Feed laden und parsen.
     *
     * @param string $url       Feed-URL (RSS 2.0, RSS 1.0, Atom)
     * @param int    $maxItems  Max. Anzahl Items (0 = alle)
     *
     * @return array{
     *   success: bool,
     *   title: string,
     *   description: string,
     *   link: string,
     *   image: string|null,
     *   language: string|null,
     *   items: array<int, array{
     *     title: string,
     *     link: string,
     *     description: string,
     *     content: string,
     *     author: string|null,
     *     date: string|null,
     *     date_timestamp: int|null,
     *     categories: string[],
     *     thumbnail: string|null,
     *     guid: string
     *   }>,
     *   error: string|null
     * }
     */
    public function fetch(string $url, int $maxItems = 50): array
    {
        if (!$this->available) {
            return $this->errorResult('SimplePie nicht verfügbar');
        }

        $normalizedUrl = $this->normalizeFeedUrl($url);
        if ($normalizedUrl === '') {
            return $this->errorResult('Ungültige Feed-URL');
        }

        $normalizedMaxItems = $this->normalizeMaxItems($maxItems, 50);

        try {
            $feed = $this->createSimplePie($normalizedUrl);
            $feed->init();

            if ($feed->error()) {
                $this->logger->warning('Feed konnte nicht geladen werden.', [
                    'url' => $normalizedUrl,
                    'error' => $this->sanitizeErrorMessage((string) $feed->error()),
                ]);

                return $this->errorResult('Feed konnte nicht geladen werden.');
            }

            $feedItems = $normalizedMaxItems > 0
                ? $feed->get_items(0, $normalizedMaxItems)
                : $feed->get_items();

            $items = [];
            foreach (is_array($feedItems) ? $feedItems : [] as $item) {
                $items[] = $this->parseItem($item);
            }

            return [
                'success'     => true,
                'title'       => $this->sanitizePlainText((string) ($feed->get_title() ?? ''), self::MAX_TITLE_LENGTH),
                'description' => $this->sanitizeHtml((string) ($feed->get_description() ?? ''), self::MAX_DESCRIPTION_LENGTH),
                'link'        => $this->normalizePublicUrl((string) ($feed->get_permalink() ?? '')),
                'image'       => $this->normalizePublicUrl((string) ($feed->get_image_url() ?: '')),
                'language'    => $this->normalizeLanguage((string) ($feed->get_language() ?: '')),
                'items'       => $items,
                'error'       => null,
            ];
        } catch (\Throwable $e) {
            $this->logger->warning('Feed-Abruf ist fehlgeschlagen.', [
                'url' => $normalizedUrl,
                'error' => $this->sanitizeErrorMessage($e->getMessage()),
            ]);

            return $this->errorResult('Feed konnte nicht geladen werden.');
        }
    }

    /**
     * Mehrere Feeds gleichzeitig laden und als vereinte Item-Liste zurückgeben.
     *
     * @param string[] $urls       Array von Feed-URLs
     * @param int      $maxItems   Max. Items insgesamt
     * @param bool     $sortByDate Nach Datum sortieren (neueste zuerst)?
     *
     * @return array{success: bool, items: array, errors: array<string, string>}
     */
    public function fetchMultiple(array $urls, int $maxItems = 100, bool $sortByDate = true): array
    {
        $allItems = [];
        $errors   = [];

        $normalizedUrls = $this->normalizeFeedUrls($urls);
        $normalizedMaxItems = $this->normalizeMaxItems($maxItems, 100);

        if ($normalizedUrls === []) {
            return [
                'success' => false,
                'items' => [],
                'errors' => ['feeds' => 'Keine gültigen Feed-URLs vorhanden.'],
            ];
        }

        foreach ($normalizedUrls as $url) {
            $result = $this->fetch($url, 0);
            if ($result['success']) {
                // Feed-Metadaten an jedes Item anhängen
                foreach ($result['items'] as $item) {
                    $item['_feed_title'] = $result['title'];
                    $item['_feed_url']   = $url;
                    $allItems[] = $item;
                }
            } else {
                $errors[$url] = $result['error'] ?? 'Feed konnte nicht geladen werden.';
            }
        }

        // Nach Datum sortieren
        if ($sortByDate && !empty($allItems)) {
            usort($allItems, function (array $a, array $b): int {
                return ($b['date_timestamp'] ?? 0) <=> ($a['date_timestamp'] ?? 0);
            });
        }

        // Auf maxItems begrenzen
        if ($normalizedMaxItems > 0 && count($allItems) > $normalizedMaxItems) {
            $allItems = array_slice($allItems, 0, $normalizedMaxItems);
        }

        return [
            'success' => count($errors) < count($normalizedUrls), // mind. 1 Feed erfolgreich
            'items'   => $allItems,
            'errors'  => $errors,
        ];
    }

    /**
     * Feed-Metadaten abrufen (ohne Items).
     *
     * @return array{title: string, description: string, link: string, image: string|null, language: string|null}|null
     */
    public function getFeedInfo(string $url): ?array
    {
        $normalizedUrl = $this->normalizeFeedUrl($url);
        if (!$this->available || $normalizedUrl === '') {
            return null;
        }

        try {
            $feed = $this->createSimplePie($normalizedUrl);
            $feed->set_item_limit(0);
            $feed->init();

            if ($feed->error()) {
                $this->logger->warning('Feed-Metadaten konnten nicht geladen werden.', [
                    'url' => $normalizedUrl,
                    'error' => $this->sanitizeErrorMessage((string) $feed->error()),
                ]);
                return null;
            }

            return [
                'title'       => $this->sanitizePlainText((string) ($feed->get_title() ?? ''), self::MAX_TITLE_LENGTH),
                'description' => $this->sanitizeHtml((string) ($feed->get_description() ?? ''), self::MAX_DESCRIPTION_LENGTH),
                'link'        => $this->normalizePublicUrl((string) ($feed->get_permalink() ?? '')),
                'image'       => $this->normalizePublicUrl((string) ($feed->get_image_url() ?: '')),
                'language'    => $this->normalizeLanguage((string) ($feed->get_language() ?: '')),
            ];
        } catch (\Throwable $e) {
            $this->logger->warning('Feed-Metadaten-Abruf ist fehlgeschlagen.', [
                'url' => $normalizedUrl,
                'error' => $this->sanitizeErrorMessage($e->getMessage()),
            ]);

            return null;
        }
    }

    /**
     * URL auf Feed-Existenz prüfen (Validierung).
     */
    public function validateFeedUrl(string $url): bool
    {
        $normalizedUrl = $this->normalizeFeedUrl($url);
        if (!$this->available || $normalizedUrl === '') {
            return false;
        }

        try {
            $feed = $this->createSimplePie($normalizedUrl);
            $feed->set_item_limit(1);
            $feed->init();

            return !$feed->error();
        } catch (\Throwable $e) {
            $this->logger->warning('Feed-Validierung ist fehlgeschlagen.', [
                'url' => $normalizedUrl,
                'error' => $this->sanitizeErrorMessage($e->getMessage()),
            ]);

            return false;
        }
    }

    /**
     * Feed-Cache leeren.
     */
    public function clearCache(): bool
    {
        if (!is_dir($this->cachePath)) {
            return true;
        }

        $success = true;
        $cacheRoot = realpath($this->cachePath);
        if ($cacheRoot === false) {
            return false;
        }

        try {
            $iterator = new \FilesystemIterator($cacheRoot, \FilesystemIterator::SKIP_DOTS);
            foreach ($iterator as $fileInfo) {
                if (!$fileInfo->isFile() || $fileInfo->isLink()) {
                    continue;
                }

                if (!$this->deleteFile($fileInfo->getPathname(), $cacheRoot)) {
                    $success = false;
                }
            }
        } catch (\Throwable $e) {
            $this->logger->warning('Feed-Cache konnte nicht vollständig geleert werden.', [
                'path' => $this->cachePath,
                'error' => $this->sanitizeErrorMessage($e->getMessage()),
            ]);

            return false;
        }

        return $success;
    }

    /**
     * Fetch-Timeout anpassen.
     */
    public function setFetchTimeout(int $seconds): void
    {
        $this->fetchTimeout = max(1, min(60, $seconds));
    }

    /**
     * Cache-Dauer anpassen.
     */
    public function setCacheDuration(int $seconds): void
    {
        $this->cacheDuration = max(0, $seconds);
    }

    // ──────────────────────────────────────────────────────────
    //  Interne Helfer
    // ──────────────────────────────────────────────────────────

    /**
     * SimplePie-Instanz erstellen und konfigurieren.
     */
    private function createSimplePie(string $url): \SimplePie\SimplePie
    {
        $feed = new \SimplePie\SimplePie();
        $feed->set_feed_url($url);
        $feed->set_timeout($this->fetchTimeout);
        $feed->set_useragent($this->userAgent);

        if ($this->cacheEnabled) {
            $feed->set_cache_location($this->cachePath);
            $feed->set_cache_duration($this->cacheDuration);
            $feed->enable_cache(true);
        } else {
            $feed->enable_cache(false);
        }

        // SimplePie soll nicht automatisch sortieren
        $feed->enable_order_by_date(true);

        // Erzwinge UTF-8
        $feed->set_output_encoding('UTF-8');

        return $feed;
    }

    /**
     * Ein einzelnes Feed-Item in ein normalisiertes Array parsen.
     */
    private function parseItem(\SimplePie\Item $item): array
    {
        // Kategorien sammeln
        $categories = [];
        if ($item->get_categories()) {
            foreach ($item->get_categories() as $cat) {
                $label = $cat->get_label();
                if ($label !== null) {
                    $normalizedLabel = $this->sanitizePlainText($label, self::MAX_CATEGORY_LENGTH);
                    if ($normalizedLabel !== '') {
                        $categories[] = $normalizedLabel;
                    }
                }
            }
        }
        $categories = array_values(array_slice(array_unique($categories), 0, self::MAX_CATEGORY_COUNT));

        // Thumbnail aus Enclosure oder Media
        $thumbnail = null;
        if ($enclosure = $item->get_enclosure()) {
            $thumb = $enclosure->get_thumbnail();
            $link  = $enclosure->get_link();
            $type  = $enclosure->get_type() ?? '';

            if ($thumb) {
                $thumbnail = $this->normalizePublicUrl($thumb);
            } elseif ($link && str_starts_with($type, 'image/')) {
                $thumbnail = $this->normalizePublicUrl($link);
            }
        }

        // Autor
        $author = null;
        if ($authorObj = $item->get_author()) {
            $author = $this->sanitizePlainText((string) ($authorObj->get_name() ?: $authorObj->get_email()), 120);
        }

        // Datum
        $dateStr = $item->get_date('Y-m-d H:i:s');
        $timestamp = $item->get_date('U');

        return [
            'title'          => $this->sanitizePlainText((string) ($item->get_title() ?? ''), self::MAX_TITLE_LENGTH),
            'link'           => $this->normalizePublicUrl((string) ($item->get_permalink() ?? '')),
            'description'    => $this->sanitizeHtml((string) ($item->get_description() ?? ''), self::MAX_DESCRIPTION_LENGTH),
            'content'        => $this->sanitizeHtml((string) ($item->get_content() ?? ''), self::MAX_CONTENT_LENGTH),
            'author'         => $author !== '' ? $author : null,
            'date'           => $dateStr ?: null,
            'date_timestamp' => $timestamp ? (int)$timestamp : null,
            'categories'     => $categories,
            'thumbnail'      => $thumbnail,
            'guid'           => $this->sanitizeGuid((string) ($item->get_id() ?? $item->get_permalink() ?? '')),
        ];
    }

    /**
     * Fehler-Ergebnis zurückgeben.
     */
    private function errorResult(string $message): array
    {
        return [
            'success'     => false,
            'title'       => '',
            'description' => '',
            'link'        => '',
            'image'       => null,
            'language'    => null,
            'items'       => [],
            'error'       => $message,
        ];
    }

    private function ensureDirectory(string $path, string $label): bool
    {
        if (is_dir($path)) {
            return is_writable($path);
        }

        if (!mkdir($path, 0755, true) && !is_dir($path)) {
            $this->logger->warning(sprintf('FeedService: %s konnte nicht erstellt werden.', $label), ['path' => $path]);
            return false;
        }

        if (!is_writable($path)) {
            $this->logger->warning(sprintf('FeedService: %s ist nicht beschreibbar.', $label), ['path' => $path]);
            return false;
        }

        return true;
    }

    private function deleteFile(string $path, string $cacheRoot = ''): bool
    {
        if (!is_file($path)) {
            return true;
        }

        if ($cacheRoot !== '') {
            $realPath = realpath($path);
            if ($realPath === false || !str_starts_with($realPath, rtrim($cacheRoot, '\\/') . DIRECTORY_SEPARATOR)) {
                $this->logger->warning('FeedService: Cache-Datei außerhalb des Cache-Roots verworfen.', ['path' => $path]);
                return false;
            }
        }

        if (!unlink($path)) {
            $this->logger->warning('FeedService: Cache-Datei konnte nicht gelöscht werden.', ['path' => $path]);
            return false;
        }

        return true;
    }

    /**
     * @param string[] $urls
     * @return string[]
     */
    private function normalizeFeedUrls(array $urls): array
    {
        $normalized = [];

        foreach ($urls as $url) {
            if (!is_string($url)) {
                continue;
            }

            $normalizedUrl = $this->normalizeFeedUrl($url);
            if ($normalizedUrl === '') {
                continue;
            }

            $normalized[] = $normalizedUrl;
            if (count($normalized) >= self::MAX_FEEDS_PER_BATCH) {
                break;
            }
        }

        return array_values(array_unique($normalized));
    }

    private function normalizeFeedUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL)) {
            return '';
        }

        $parts = parse_url($url);
        if (!is_array($parts)) {
            return '';
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        $host = trim((string) ($parts['host'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true) || $host === '' || isset($parts['user']) || isset($parts['pass'])) {
            return '';
        }

        $asciiHost = $this->normalizeHost($host);
        if ($asciiHost === '' || !$this->isSafeRemoteHost($asciiHost)) {
            $this->logger->warning('Feed-URL verworfen.', ['url' => $url, 'host' => $host]);
            return '';
        }

        unset($parts['fragment']);

        return $this->buildUrl($parts, $asciiHost);
    }

    private function normalizeMaxItems(int $maxItems, int $default): int
    {
        if ($maxItems === 0) {
            return 0;
        }

        return max(1, min(self::MAX_ITEMS_PER_FEED, $maxItems > 0 ? $maxItems : $default));
    }

    private function sanitizePlainText(string $value, int $maxLength): string
    {
        $value = trim(strip_tags($value));
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return $this->truncate($value, $maxLength);
    }

    private function sanitizeHtml(string $value, int $maxLength): string
    {
        $value = $this->truncate($value, $maxLength);
        return PurifierService::getInstance()->purify($value, 'default');
    }

    private function normalizePublicUrl(string $url): ?string
    {
        $normalized = trim($url);
        if ($normalized === '' || !filter_var($normalized, FILTER_VALIDATE_URL)) {
            return null;
        }

        $parts = parse_url($normalized);
        if (!is_array($parts)) {
            return null;
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? ''));
        if (!in_array($scheme, ['http', 'https'], true) || isset($parts['user']) || isset($parts['pass'])) {
            return null;
        }

        return $this->buildUrl($parts, $this->normalizeHost((string) ($parts['host'] ?? '')));
    }

    private function normalizeLanguage(string $value): ?string
    {
        $value = strtolower(trim($value));
        $value = preg_replace('/[^a-z0-9\-_]/', '', $value) ?? '';

        return $value !== '' ? substr($value, 0, 16) : null;
    }

    private function sanitizeGuid(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        return $this->truncate($value, self::MAX_GUID_LENGTH);
    }

    private function sanitizeErrorMessage(string $value): string
    {
        $value = trim($value);
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '';
        $value = preg_replace('/\s+/u', ' ', $value) ?? '';

        return $this->truncate($value, self::MAX_ERROR_LENGTH);
    }

    private function normalizeHost(string $host): string
    {
        $host = strtolower(trim($host));
        if ($host === '') {
            return '';
        }

        if (function_exists('idn_to_ascii')) {
            $ascii = idn_to_ascii($host, IDNA_DEFAULT, INTL_IDNA_VARIANT_UTS46);
            if (is_string($ascii) && $ascii !== '') {
                return $ascii;
            }
        }

        return $host;
    }

    private function isSafeRemoteHost(string $host): bool
    {
        if (in_array($host, ['localhost', 'localhost.localdomain', 'ip6-localhost', 'ip6-loopback'], true)) {
            return false;
        }

        $resolvedIps = $this->resolveHostIps($host);
        if ($resolvedIps === []) {
            return false;
        }

        foreach ($resolvedIps as $ip) {
            if ($ip !== '' && $this->isPrivateOrReservedIp($ip)) {
                return false;
            }
        }

        return true;
    }

    private function isPrivateOrReservedIp(string $ip): bool
    {
        return filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        ) === false;
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

    /**
     * @param array<string, mixed> $parts
     */
    private function buildUrl(array $parts, string $host): string
    {
        if ($host === '') {
            return '';
        }

        $url = strtolower((string) ($parts['scheme'] ?? 'https')) . '://' . $host;
        if (isset($parts['port']) && is_int($parts['port'])) {
            $url .= ':' . $parts['port'];
        }

        $path = (string) ($parts['path'] ?? '');
        $url .= $path !== '' ? $path : '/';

        if (isset($parts['query']) && $parts['query'] !== '') {
            $url .= '?' . (string) $parts['query'];
        }

        return $url;
    }

    private function truncate(string $value, int $maxLength): string
    {
        if ($maxLength <= 0 || $value === '') {
            return $value;
        }

        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $maxLength);
        }

        return substr($value, 0, $maxLength);
    }
}

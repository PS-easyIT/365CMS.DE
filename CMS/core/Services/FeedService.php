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

if (!defined('ABSPATH')) {
    exit;
}

final class FeedService
{
    private static ?self $instance = null;

    private readonly string $cachePath;
    private readonly bool $available;

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
        $this->userAgent = '365CMS/' . (defined('CMS_VERSION') ? CMS_VERSION : '2.0') . ' (SimplePie)';

        if (!is_dir($this->cachePath)) {
            @mkdir($this->cachePath, 0755, true);
        }
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

        if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->errorResult('Ungültige Feed-URL');
        }

        try {
            $feed = $this->createSimplePie($url);
            $feed->init();

            if ($feed->error()) {
                return $this->errorResult('Feed-Fehler: ' . $feed->error());
            }

            $feedItems = $maxItems > 0
                ? $feed->get_items(0, $maxItems)
                : $feed->get_items();

            $items = [];
            foreach ($feedItems as $item) {
                $items[] = $this->parseItem($item);
            }

            return [
                'success'     => true,
                'title'       => $feed->get_title() ?? '',
                'description' => $feed->get_description() ?? '',
                'link'        => $feed->get_permalink() ?? '',
                'image'       => $feed->get_image_url() ?: null,
                'language'    => $feed->get_language() ?: null,
                'items'       => $items,
                'error'       => null,
            ];
        } catch (\Throwable $e) {
            error_log('FeedService::fetch() Fehler für ' . $url . ': ' . $e->getMessage());
            return $this->errorResult($e->getMessage());
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

        foreach ($urls as $url) {
            $result = $this->fetch($url, 0);
            if ($result['success']) {
                // Feed-Metadaten an jedes Item anhängen
                foreach ($result['items'] as $item) {
                    $item['_feed_title'] = $result['title'];
                    $item['_feed_url']   = $url;
                    $allItems[] = $item;
                }
            } else {
                $errors[$url] = $result['error'] ?? 'Unbekannter Fehler';
            }
        }

        // Nach Datum sortieren
        if ($sortByDate && !empty($allItems)) {
            usort($allItems, function (array $a, array $b): int {
                return ($b['date_timestamp'] ?? 0) <=> ($a['date_timestamp'] ?? 0);
            });
        }

        // Auf maxItems begrenzen
        if ($maxItems > 0 && count($allItems) > $maxItems) {
            $allItems = array_slice($allItems, 0, $maxItems);
        }

        return [
            'success' => count($errors) < count($urls), // mind. 1 Feed erfolgreich
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
        if (!$this->available || empty($url)) {
            return null;
        }

        try {
            $feed = $this->createSimplePie($url);
            $feed->set_item_limit(0);
            $feed->init();

            if ($feed->error()) {
                return null;
            }

            return [
                'title'       => $feed->get_title() ?? '',
                'description' => $feed->get_description() ?? '',
                'link'        => $feed->get_permalink() ?? '',
                'image'       => $feed->get_image_url() ?: null,
                'language'    => $feed->get_language() ?: null,
            ];
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * URL auf Feed-Existenz prüfen (Validierung).
     */
    public function validateFeedUrl(string $url): bool
    {
        if (!$this->available || empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
            return false;
        }

        try {
            $feed = $this->createSimplePie($url);
            $feed->set_item_limit(1);
            $feed->init();

            return !$feed->error();
        } catch (\Throwable $e) {
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

        $files = glob($this->cachePath . '*');
        if (!$files) {
            return true;
        }

        $success = true;
        foreach ($files as $file) {
            if (is_file($file)) {
                if (!@unlink($file)) {
                    $success = false;
                }
            }
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
        $feed->set_cache_location($this->cachePath);
        $feed->set_cache_duration($this->cacheDuration);
        $feed->set_timeout($this->fetchTimeout);
        $feed->set_useragent($this->userAgent);
        $feed->enable_cache(true);

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
                    $categories[] = $label;
                }
            }
        }

        // Thumbnail aus Enclosure oder Media
        $thumbnail = null;
        if ($enclosure = $item->get_enclosure()) {
            $thumb = $enclosure->get_thumbnail();
            $link  = $enclosure->get_link();
            $type  = $enclosure->get_type() ?? '';

            if ($thumb) {
                $thumbnail = $thumb;
            } elseif ($link && str_starts_with($type, 'image/')) {
                $thumbnail = $link;
            }
        }

        // Autor
        $author = null;
        if ($authorObj = $item->get_author()) {
            $author = $authorObj->get_name() ?: $authorObj->get_email();
        }

        // Datum
        $dateStr = $item->get_date('Y-m-d H:i:s');
        $timestamp = $item->get_date('U');

        return [
            'title'          => strip_tags($item->get_title() ?? ''),
            'link'           => $item->get_permalink() ?? '',
            'description'    => $item->get_description() ?? '',
            'content'        => $item->get_content() ?? '',
            'author'         => $author,
            'date'           => $dateStr ?: null,
            'date_timestamp' => $timestamp ? (int)$timestamp : null,
            'categories'     => $categories,
            'thumbnail'      => $thumbnail,
            'guid'           => $item->get_id() ?? $item->get_permalink() ?? '',
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
}

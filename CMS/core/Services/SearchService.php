<?php
/**
 * Search Service – Volltextsuche via TNTSearch
 *
 * Nutzt die TNTSearch-Bibliothek (SQLite-Engine, GermanStemmer) für
 * performante Volltextsuche über Pages, Posts und Plugin-Inhalte.
 *
 * @package CMSv2\Core\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;
use CMS\Hooks;
use TeamTNT\TNTSearch\TNTSearch;
use TeamTNT\TNTSearch\Stemmer\GermanStemmer;
use TeamTNT\TNTSearch\Support\Highlighter;

if (!defined('ABSPATH')) {
    exit;
}

final class SearchService
{
    private static ?self $instance = null;

    private readonly Database $db;
    private readonly string $prefix;
    private readonly string $storagePath;
    private ?TNTSearch $tnt = null;
    private bool $available = false;
    private string $unavailableReason = '';

    /**
     * Konfiguration der Suchindizes.
     * Key = Index-Name, Value = SQL-Query + Felder.
     */
    private array $indexDefinitions = [];

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->db     = Database::instance();
        $this->prefix = $this->db->getPrefix();

        // Storage-Pfad für SQLite-Indizes
        $this->storagePath = ABSPATH . '/cache/search/';

        if (!is_dir($this->storagePath)) {
            @mkdir($this->storagePath, 0755, true);
        }

        $this->initTNTSearch();
        $this->registerDefaultIndices();
        $this->initHooks();
    }

    /**
     * TNTSearch-Instanz initialisieren.
     */
    private function initTNTSearch(): void
    {
        // SQLite-Extension prüfen (TNTSearch speichert Indizes als SQLite-DBs)
        if (!extension_loaded('pdo_sqlite')) {
            $this->unavailableReason = 'PHP-Extension pdo_sqlite ist nicht geladen. Bitte in der php.ini aktivieren.';
            error_log('SearchService: ' . $this->unavailableReason);
            return;
        }

        if (!class_exists(TNTSearch::class)) {
            $this->unavailableReason = 'TNTSearch-Klasse nicht verfügbar. CMS/assets/autoload.php prüfen.';
            error_log('SearchService: ' . $this->unavailableReason);
            return;
        }

        // Storage-Verzeichnis beschreibbar?
        if (!is_dir($this->storagePath) || !is_writable($this->storagePath)) {
            $this->unavailableReason = 'Verzeichnis cache/search/ existiert nicht oder ist nicht beschreibbar.';
            error_log('SearchService: ' . $this->unavailableReason);
            return;
        }

        try {
            $this->tnt = new TNTSearch();

            $this->tnt->loadConfig([
                'driver'  => 'mysql',
                'host'    => DB_HOST,
                'database' => DB_NAME,
                'username' => DB_USER,
                'password' => DB_PASS,
                'storage' => $this->storagePath,
                'stemmer' => GermanStemmer::class,
                'wal'     => true,
            ]);

            // MySQL-PDO aus CMS-Database nutzen (Connection-Reuse)
            $this->tnt->setDatabaseHandle($this->db->getPdo());

            $this->available = true;
        } catch (\Throwable $e) {
            $this->unavailableReason = $e->getMessage();
            error_log('SearchService: TNTSearch-Init fehlgeschlagen: ' . $e->getMessage());
            $this->available = false;
        }
    }

    /**
     * Standard-Indizes registrieren (Pages + Posts).
     */
    private function registerDefaultIndices(): void
    {
        // Pages-Index
        $this->indexDefinitions['pages'] = [
            'query'      => "SELECT id, title, content, excerpt, slug FROM {$this->prefix}pages WHERE status = 'published'",
            'primaryKey' => 'id',
        ];

        // Posts-Index (Blog)
        $this->indexDefinitions['posts'] = [
            'query'      => "SELECT id, title, content, excerpt, slug FROM {$this->prefix}posts WHERE status = 'published'",
            'primaryKey' => 'id',
        ];
    }

    /**
     * Hooks für automatisches Index-Update registrieren.
     */
    private function initHooks(): void
    {
        // Index-Rebuild bei Page/Post-Speichern
        Hooks::addAction('page_saved', [$this, 'onPageSaved'], 50);
        Hooks::addAction('page_deleted', [$this, 'onPageDeleted'], 50);
        Hooks::addAction('post_saved', [$this, 'onPostSaved'], 50);
        Hooks::addAction('post_deleted', [$this, 'onPostDeleted'], 50);

        // Ermögliche Plugins, eigene Indizes zu registrieren
        Hooks::addAction('cms_init', function () {
            Hooks::doAction('search_register_indices', $this);
        }, 99);
    }

    // ──────────────────────────────────────────────────────────
    //  Öffentliche API
    // ──────────────────────────────────────────────────────────

    /**
     * Prüft, ob die Volltextsuche verfügbar ist.
     */
    public function isAvailable(): bool
    {
        return $this->available;
    }

    /**
     * Gibt den Grund zurück, warum die Suche nicht verfügbar ist.
     */
    public function getUnavailableReason(): string
    {
        return $this->unavailableReason;
    }

    /**
     * Einen zusätzlichen Suchindex registrieren (für Plugins).
     *
     * @param string $name     Index-Name (z. B. 'experts', 'events')
     * @param string $query    SQL-Query zum Indexieren (muss id + Text-Felder liefern)
     * @param string $primaryKey  Primärschlüssel-Feld (Standard: 'id')
     */
    public function registerIndex(string $name, string $query, string $primaryKey = 'id'): void
    {
        $this->indexDefinitions[$name] = [
            'query'      => $query,
            'primaryKey' => $primaryKey,
        ];
    }

    /**
     * Einen einzelnen Index komplett neu erstellen.
     */
    public function buildIndex(string $name): bool
    {
        if (!$this->available || !isset($this->indexDefinitions[$name])) {
            return false;
        }

        $def = $this->indexDefinitions[$name];

        try {
            $indexer = $this->tnt->createIndex("{$name}.index", true);
            $indexer->setLanguage('german');
            $indexer->setPrimaryKey($def['primaryKey']);
            $indexer->query($def['query']);
            $indexer->run();

            return true;
        } catch (\Throwable $e) {
            error_log("SearchService::buildIndex({$name}) Fehler: " . $e->getMessage());
            if (function_exists('cms_log')) {
                cms_log('error', "Index-Build '{$name}' fehlgeschlagen: " . $e->getMessage(), ['channel' => 'search']);
            }
            return false;
        }
    }

    /**
     * Alle registrierten Indizes neu erstellen.
     */
    public function rebuildAllIndices(): array
    {
        $results = [];
        foreach (array_keys($this->indexDefinitions) as $name) {
            $results[$name] = $this->buildIndex($name);
        }
        return $results;
    }

    /**
     * Volltextsuche über einen bestimmten Index.
     *
     * @param string $query     Suchbegriff
     * @param string $indexName Index-Name (z. B. 'pages', 'posts')
     * @param int    $limit     Max. Ergebnisse
     * @param bool   $fuzzy     Fuzzy-Suche aktivieren
     *
     * @return array{ids: int[], hits: int, execution_time: string}
     */
    public function search(string $query, string $indexName = 'pages', int $limit = 50, bool $fuzzy = false): array
    {
        if (!$this->available) {
            return ['ids' => [], 'hits' => 0, 'execution_time' => '0 ms'];
        }

        $indexFile = "{$indexName}.index";
        $indexPath = $this->storagePath . $indexFile;

        // Falls Index nicht existiert → automatisch erstellen
        if (!file_exists($indexPath)) {
            $built = $this->buildIndex($indexName);
            if (!$built) {
                return ['ids' => [], 'hits' => 0, 'execution_time' => '0 ms'];
            }
        }

        try {
            $this->tnt->selectIndex($indexFile);
            $this->tnt->fuzziness = $fuzzy;

            $result = $this->tnt->search($query, $limit);

            return [
                'ids'            => $result['ids'] ?? [],
                'hits'           => $result['hits'] ?? 0,
                'execution_time' => $result['execution_time'] ?? '0 ms',
            ];
        } catch (\Throwable $e) {
            error_log("SearchService::search() Fehler: " . $e->getMessage());
            return ['ids' => [], 'hits' => 0, 'execution_time' => '0 ms'];
        }
    }

    /**
     * Übergreifende Suche über Pages + Posts (+ registrierte Plugin-Indizes).
     *
     * @return array Array mit Ergebnis-Datensätzen (angereichert mit _type, _type_label, slug, title, meta_description)
     */
    public function searchAll(string $query, int $limit = 50, bool $fuzzy = false): array
    {
        $allResults = [];

        // Pages
        $pageResult = $this->search($query, 'pages', $limit, $fuzzy);
        if (!empty($pageResult['ids'])) {
            $rows = $this->fetchRowsByIds("{$this->prefix}pages", $pageResult['ids']);
            foreach ($rows as $row) {
                $row['_type']       = 'page';
                $row['_type_label'] = 'Seite';
                $allResults[] = $row;
            }
        }

        // Posts
        $postResult = $this->search($query, 'posts', $limit, $fuzzy);
        if (!empty($postResult['ids'])) {
            $rows = $this->fetchRowsByIds("{$this->prefix}posts", $postResult['ids']);
            foreach ($rows as $row) {
                $row['_type']       = 'post';
                $row['_type_label'] = 'Beitrag';
                $allResults[] = $row;
            }
        }

        // Plugins können eigene Ergebnisse hinzufügen
        $allResults = Hooks::applyFilters('search_results', $allResults, $query, $limit);

        return $allResults;
    }

    /**
     * Text-Highlighting für Suchergebnisse.
     */
    public function highlight(string $text, string $query, string $tag = 'mark'): string
    {
        if (!$this->available || empty($query)) {
            return $text;
        }

        try {
            $highlighter = new Highlighter();
            return $highlighter->highlight($text, $query, $tag);
        } catch (\Throwable $e) {
            return $text;
        }
    }

    /**
     * Snippet (Textauszug) erstellen, der den Suchbegriff enthält.
     */
    public function snippet(string $text, string $query, int $length = 200): string
    {
        // HTML entfernen
        $plain = strip_tags($text);

        if (empty($query)) {
            return mb_substr($plain, 0, $length) . (mb_strlen($plain) > $length ? '…' : '');
        }

        // Position des ersten Treffers finden
        $pos = mb_stripos($plain, $query);

        if ($pos === false) {
            // Kein Treffer → Anfang zurückgeben
            return mb_substr($plain, 0, $length) . (mb_strlen($plain) > $length ? '…' : '');
        }

        // Kontext um den Treffer herum
        $start = max(0, $pos - (int)($length / 3));
        $snippet = mb_substr($plain, $start, $length);

        // Am Wortanfang beginnen
        if ($start > 0) {
            $firstSpace = mb_strpos($snippet, ' ');
            if ($firstSpace !== false && $firstSpace < 30) {
                $snippet = mb_substr($snippet, $firstSpace + 1);
            }
            $snippet = '…' . $snippet;
        }

        if ($start + $length < mb_strlen($plain)) {
            $snippet .= '…';
        }

        return $snippet;
    }

    // ──────────────────────────────────────────────────────────
    //  Inkrementelle Index-Updates (Hook-Handler)
    // ──────────────────────────────────────────────────────────

    /**
     * Page gespeichert → Index aktualisieren.
     */
    public function onPageSaved(int $pageId): void
    {
        $this->updateDocument('pages', $pageId, "{$this->prefix}pages", [
            'title', 'content', 'meta_description', 'slug',
        ]);
    }

    /**
     * Page gelöscht → aus Index entfernen.
     */
    public function onPageDeleted(int $pageId): void
    {
        $this->removeDocument('pages', $pageId);
    }

    /**
     * Post gespeichert → Index aktualisieren.
     */
    public function onPostSaved(int $postId): void
    {
        $this->updateDocument('posts', $postId, "{$this->prefix}posts", [
            'title', 'content', 'excerpt', 'slug',
        ]);
    }

    /**
     * Post gelöscht → aus Index entfernen.
     */
    public function onPostDeleted(int $postId): void
    {
        $this->removeDocument('posts', $postId);
    }

    // ──────────────────────────────────────────────────────────
    //  Interne Helfer
    // ──────────────────────────────────────────────────────────

    /**
     * Einzelnes Dokument im Index aktualisieren (Insert oder Update).
     */
    private function updateDocument(string $indexName, int $docId, string $table, array $fields): void
    {
        if (!$this->available) {
            return;
        }

        $indexFile = "{$indexName}.index";
        $indexPath = $this->storagePath . $indexFile;

        if (!file_exists($indexPath)) {
            // Index existiert noch nicht → kompletten Rebuild auslösen
            $this->buildIndex($indexName);
            return;
        }

        try {
            $this->tnt->selectIndex($indexFile);

            // Dokument aus DB holen
            $cols = implode(', ', array_merge(['id'], $fields));
            $stmt = $this->db->prepare("SELECT {$cols} FROM {$table} WHERE id = ? AND status = 'published'");
            $stmt->execute([$docId]);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);

            $indexer = $this->tnt->createIndex($indexFile, true);
            $indexer->setLanguage('german');

            if ($row) {
                // Dokument existiert und ist published → Update/Insert
                $this->tnt->engine->update($docId, $row);
            } else {
                // Dokument nicht gefunden oder nicht published → aus Index löschen
                try {
                    $this->tnt->engine->delete($docId);
                } catch (\Throwable $ignore) {
                    // Dokument war ggf. nicht im Index
                }
            }
        } catch (\Throwable $e) {
            error_log("SearchService::updateDocument({$indexName}, {$docId}) Fehler: " . $e->getMessage());
        }
    }

    /**
     * Einzelnes Dokument aus dem Index entfernen.
     */
    private function removeDocument(string $indexName, int $docId): void
    {
        if (!$this->available) {
            return;
        }

        $indexFile = "{$indexName}.index";
        $indexPath = $this->storagePath . $indexFile;

        if (!file_exists($indexPath)) {
            return;
        }

        try {
            $this->tnt->selectIndex($indexFile);
            $this->tnt->engine->delete($docId);
        } catch (\Throwable $e) {
            error_log("SearchService::removeDocument({$indexName}, {$docId}) Fehler: " . $e->getMessage());
        }
    }

    /**
     * Zeilen anhand einer ID-Liste aus der Datenbank laden (IN-Clause).
     */
    private function fetchRowsByIds(string $table, array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        // Nur Integer-IDs zulassen
        $ids = array_map('intval', $ids);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $this->db->prepare("SELECT * FROM {$table} WHERE id IN ({$placeholders})");
        $stmt->execute($ids);
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC) ?: [];

        // Ergebnis in der Reihenfolge der IDs sortieren (TNTSearch-Relevanz)
        $indexed = [];
        foreach ($rows as $row) {
            $indexed[(int)$row['id']] = $row;
        }

        $sorted = [];
        foreach ($ids as $id) {
            if (isset($indexed[$id])) {
                $sorted[] = $indexed[$id];
            }
        }

        return $sorted;
    }

    /**
     * Alle registrierten Index-Definitionen abfragen.
     *
     * @return array<string, array{query: string, primaryKey: string}>
     */
    public function getIndexDefinitions(): array
    {
        return $this->indexDefinitions;
    }

    /**
     * Prüft, ob ein bestimmter Index bereits erstellt wurde.
     */
    public function indexExists(string $name): bool
    {
        return file_exists($this->storagePath . "{$name}.index");
    }

    /**
     * Gibt Infos über alle Indizes zurück (für Admin-Dashboard).
     *
     * @return array<string, array{exists: bool, size: int, modified: string|null}>
     */
    public function getIndexInfo(): array
    {
        $info = [];
        foreach (array_keys($this->indexDefinitions) as $name) {
            $path = $this->storagePath . "{$name}.index";
            $info[$name] = [
                'exists'   => file_exists($path),
                'size'     => file_exists($path) ? filesize($path) : 0,
                'modified' => file_exists($path) ? date('Y-m-d H:i:s', filemtime($path)) : null,
            ];
        }
        return $info;
    }
}

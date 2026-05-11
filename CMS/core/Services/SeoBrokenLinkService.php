<?php
declare(strict_types=1);

namespace CMS\Services;

use CMS\AuditLogger;
use CMS\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class SeoBrokenLinkService
{
    private const SETTINGS_GROUP = 'seo_broken_links';
    private const REPORT_KEY = 'report';
    private const IGNORE_KEY = 'ignored_paths';
    private const MAX_FINDINGS = 150;
    private const MAX_SOURCE_PREVIEW = 5;
    private const STATIC_PUBLIC_PATHS = [
        '/',
        '/blog',
        '/search',
        '/login',
        '/register',
        '/forgot-password',
        '/reset-password',
        '/cms-login',
        '/cms-register',
        '/cms-forgot-password',
        '/cms-reset-password',
        '/en',
        '/en/blog',
        '/en/search',
        '/en/login',
        '/en/register',
        '/en/forgot-password',
        '/en/reset-password',
    ];
    private const SOURCE_LABELS = [
        'content' => 'Inhalte',
        'sitemap' => 'Sitemap',
        'redirect_rule' => 'Redirect-Regeln',
        'not_found_log' => '404-Monitor',
    ];

    private static ?self $instance = null;

    private Database $db;
    private SettingsService $settings;
    private SEOService $seoService;
    private RedirectService $redirectService;
    private PermalinkService $permalinkService;
    private string $prefix;
    private ?array $knownSiteAuthorities = null;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->db = Database::instance();
        $this->settings = SettingsService::getInstance();
        $this->seoService = SEOService::getInstance();
        $this->redirectService = RedirectService::getInstance();
        $this->permalinkService = PermalinkService::getInstance();
        $this->prefix = $this->db->getPrefix();
    }

    /**
     * @return array<string, mixed>
     */
    public function getAdminData(): array
    {
        return $this->buildAdminPayload($this->getStoredReport(), $this->getIgnoredEntries());
    }

    /**
     * @return array<string, mixed>
     */
    public function runScan(string $trigger = 'manual'): array
    {
        $startedAt = microtime(true);

        try {
            $auditRows = $this->loadAuditRows();
            $redirectData = $this->redirectService->getAdminData();
            $knownValidPaths = $this->collectKnownValidPaths($auditRows, $redirectData);

            $findings = [];
            $sourceOccurrences = [
                'content' => 0,
                'sitemap' => 0,
                'redirect_rule' => 0,
                'not_found_log' => 0,
            ];

            foreach ($auditRows as $row) {
                $sourceTitle = trim((string) ($row['title'] ?? ''));
                $sourceType = trim((string) ($row['type'] ?? ''));
                $sourceSlug = trim((string) ($row['slug'] ?? ''));
                $contentLinks = $this->extractInternalPathsFromHtml((string) ($row['content'] ?? ''));

                foreach ($contentLinks as $targetPath) {
                    if (isset($knownValidPaths[$targetPath])) {
                        continue;
                    }

                    $sourceLabel = $sourceTitle !== '' ? $sourceTitle : ($sourceSlug !== '' ? $sourceSlug : 'Inhalt');
                    $sourceDetail = trim($sourceType . ($sourceSlug !== '' ? ' · ' . $sourceSlug : ''));

                    if ($this->addFinding($findings, $targetPath, 'content', $sourceLabel, $sourceDetail, 'content|' . $sourceType . '|' . $sourceSlug)) {
                        $sourceOccurrences['content']++;
                    }
                }
            }

            foreach ($this->loadSitemapCandidates() as $candidate) {
                $targetPath = (string) ($candidate['target_path'] ?? '');
                if ($targetPath === '' || isset($knownValidPaths[$targetPath])) {
                    continue;
                }

                $sourceLabel = (string) ($candidate['source_label'] ?? 'Sitemap');
                $sourceDetail = (string) ($candidate['source_detail'] ?? 'XML-Eintrag');
                if ($this->addFinding($findings, $targetPath, 'sitemap', $sourceLabel, $sourceDetail, 'sitemap|' . $sourceLabel . '|' . $targetPath)) {
                    $sourceOccurrences['sitemap']++;
                }
            }

            foreach ((array) ($redirectData['redirects'] ?? []) as $redirectRow) {
                if ((int) ($redirectRow['is_active'] ?? 0) !== 1) {
                    continue;
                }

                $targetPath = $this->normalizeInternalPath((string) ($redirectRow['target_url'] ?? ''));
                if ($targetPath === '' || isset($knownValidPaths[$targetPath])) {
                    continue;
                }

                $sourcePath = (string) ($redirectRow['source_path'] ?? '');
                $sourceLabel = $sourcePath !== '' ? $sourcePath : 'Weiterleitung';
                $sourceDetail = 'Redirect-Ziel';
                if ($this->addFinding($findings, $targetPath, 'redirect_rule', $sourceLabel, $sourceDetail, 'redirect|' . (string) ($redirectRow['id'] ?? 0))) {
                    $sourceOccurrences['redirect_rule']++;
                }
            }

            foreach ((array) ($redirectData['logs'] ?? []) as $logRow) {
                if ((int) ($logRow['redirect_id'] ?? 0) > 0) {
                    continue;
                }

                $requestPath = $this->normalizeInternalPath((string) ($logRow['request_path'] ?? ''));
                if ($requestPath === '' || isset($knownValidPaths[$requestPath])) {
                    continue;
                }

                $hostLabel = trim((string) ($logRow['request_host_label'] ?? '404-Monitor'));
                $sourceDetail = '404-Hits: ' . (int) ($logRow['hit_count'] ?? 0);
                if ($this->addFinding(
                    $findings,
                    $requestPath,
                    'not_found_log',
                    $hostLabel !== '' ? $hostLabel : '404-Monitor',
                    $sourceDetail,
                    'log|' . $requestPath . '|' . (string) ($logRow['request_host'] ?? ''),
                    (int) ($logRow['hit_count'] ?? 0),
                    (string) ($logRow['first_seen_at'] ?? ''),
                    (string) ($logRow['last_seen_at'] ?? '')
                )) {
                    $sourceOccurrences['not_found_log']++;
                }
            }

            $rawFindings = $this->finalizeFindings($findings);
            $report = [
                'generated_at' => gmdate('c'),
                'trigger' => $trigger,
                'duration_ms' => (int) round((microtime(true) - $startedAt) * 1000),
                'raw_findings' => $rawFindings,
                'source_occurrences' => $sourceOccurrences,
                'known_valid_paths' => count($knownValidPaths),
                'audit_row_count' => count($auditRows),
            ];

            $this->settings->set(self::SETTINGS_GROUP, self::REPORT_KEY, $report, false, 0);

            AuditLogger::instance()->log(
                AuditLogger::CAT_SETTING,
                'seo.broken_links.scan_completed',
                'SEO Broken-Link-Report aktualisiert',
                'seo',
                null,
                [
                    'trigger' => $trigger,
                    'findings' => count($rawFindings),
                    'occurrences' => array_sum($sourceOccurrences),
                    'duration_ms' => (int) ($report['duration_ms'] ?? 0),
                ],
                'info'
            );

            return [
                'success' => true,
                'message' => sprintf(
                    'Broken-Link-Report aktualisiert: %d Zielpfad(e), %d Quellen-Treffer.',
                    count($rawFindings),
                    array_sum($sourceOccurrences)
                ),
            ];
        } catch (\Throwable $e) {
            AuditLogger::instance()->log(
                AuditLogger::CAT_SETTING,
                'seo.broken_links.scan_failed',
                'SEO Broken-Link-Report fehlgeschlagen',
                'seo',
                null,
                ['exception' => $this->sanitizeLogValue($e->getMessage(), 220)],
                'error'
            );

            return [
                'success' => false,
                'error' => 'Broken-Link-Report konnte nicht erstellt werden. Bitte Logs prüfen.',
            ];
        }
    }

    public function runScheduledScan(): void
    {
        if (!$this->isScheduledScanEnabled()) {
            return;
        }

        $this->runScan('cron');
    }

    /**
     * @return array<string, mixed>
     */
    public function ignorePath(string $path): array
    {
        $normalizedPath = $this->normalizeInternalPath($path);
        if ($normalizedPath === '' || $normalizedPath === '/') {
            return ['success' => false, 'error' => 'Ungültiger Zielpfad für die Ignore-Liste.'];
        }

        $entries = $this->getIgnoredEntries();
        foreach ($entries as $entry) {
            if ((string) ($entry['path'] ?? '') === $normalizedPath) {
                return ['success' => true, 'message' => 'Zielpfad steht bereits auf der Ignore-Liste.'];
            }
        }

        $entries[] = [
            'path' => $normalizedPath,
            'added_at' => gmdate('c'),
        ];

        $this->storeIgnoredEntries($entries);

        AuditLogger::instance()->log(
            AuditLogger::CAT_SETTING,
            'seo.broken_links.ignore_added',
            'Broken-Link-Ziel ignoriert',
            'seo',
            null,
            ['target_path' => $normalizedPath],
            'info'
        );

        return ['success' => true, 'message' => 'Zielpfad wurde zur Ignore-Liste hinzugefügt.'];
    }

    /**
     * @return array<string, mixed>
     */
    public function removeIgnoredPath(string $path): array
    {
        $normalizedPath = $this->normalizeInternalPath($path);
        if ($normalizedPath === '') {
            return ['success' => false, 'error' => 'Ungültiger Zielpfad für die Ignore-Liste.'];
        }

        $entries = array_values(array_filter(
            $this->getIgnoredEntries(),
            static fn(array $entry): bool => (string) ($entry['path'] ?? '') !== $normalizedPath
        ));

        $this->storeIgnoredEntries($entries);

        AuditLogger::instance()->log(
            AuditLogger::CAT_SETTING,
            'seo.broken_links.ignore_removed',
            'Broken-Link-Ziel aus Ignore-Liste entfernt',
            'seo',
            null,
            ['target_path' => $normalizedPath],
            'info'
        );

        return ['success' => true, 'message' => 'Zielpfad wurde aus der Ignore-Liste entfernt.'];
    }

    /**
     * @return array<string, mixed>
     */
    private function buildAdminPayload(array $report, array $ignoredEntries): array
    {
        $ignoredMap = [];
        foreach ($ignoredEntries as $entry) {
            $path = (string) ($entry['path'] ?? '');
            if ($path !== '') {
                $ignoredMap[$path] = true;
            }
        }

        $rawFindings = is_array($report['raw_findings'] ?? null) ? $report['raw_findings'] : [];
        $visibleFindings = array_values(array_filter($rawFindings, static fn(array $finding): bool => !isset($ignoredMap[(string) ($finding['target_path'] ?? '')])));
        $sourceStats = $this->buildSourceStats($visibleFindings);
        $generatedAt = (string) ($report['generated_at'] ?? '');
        $generatedTs = $generatedAt !== '' ? strtotime($generatedAt) : false;
        $occurrencesTotal = array_sum(array_map(static fn(array $row): int => (int) ($row['occurrences_total'] ?? 0), $visibleFindings));

        return [
            'available' => $generatedAt !== '',
            'generated_at' => $generatedAt,
            'generated_at_label' => $generatedTs ? date('d.m.Y H:i', (int) $generatedTs) : '',
            'trigger' => (string) ($report['trigger'] ?? ''),
            'trigger_label' => $this->mapTriggerLabel((string) ($report['trigger'] ?? '')),
            'duration_ms' => (int) ($report['duration_ms'] ?? 0),
            'findings_total' => count($visibleFindings),
            'raw_findings_total' => count($rawFindings),
            'occurrences_total' => $occurrencesTotal,
            'ignored_total' => count($ignoredEntries),
            'suppressed_total' => max(0, count($rawFindings) - count($visibleFindings)),
            'source_stats' => $sourceStats,
            'findings' => array_slice($visibleFindings, 0, self::MAX_FINDINGS),
            'ignored_paths' => $ignoredEntries,
            'notes' => [
                'Lokaler read-only Report aus Inhalten, XML-Sitemaps, aktiven Redirect-Zielen und 404-Monitor.',
                'Keine externe URL-Prüfung und keine Token in URLs.',
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadAuditRows(): array
    {
        $rows = $this->seoService->getAuditRows();
        return is_array($rows) ? $rows : [];
    }

    /**
     * @param array<int, array<string, mixed>> $auditRows
     * @param array<string, mixed> $redirectData
     * @return array<string, true>
     */
    private function collectKnownValidPaths(array $auditRows, array $redirectData): array
    {
        $paths = [];

        foreach (self::STATIC_PUBLIC_PATHS as $path) {
            $normalized = $this->normalizeInternalPath($path);
            if ($normalized !== '') {
                $paths[$normalized] = true;
            }
        }

        foreach ($auditRows as $row) {
            $slug = trim((string) ($row['slug'] ?? ''));
            $type = trim((string) ($row['type'] ?? ''));
            if ($slug === '') {
                continue;
            }

            if ($type === 'post') {
                $postPath = $this->normalizeInternalPath($this->permalinkService->buildPostPathFromValues(
                    $slug,
                    (string) ($row['published_at'] ?? ''),
                    (string) ($row['created_at'] ?? '')
                ));
                $legacyPath = $this->normalizeInternalPath($this->permalinkService->getLegacyPostPath($slug));

                if ($postPath !== '') {
                    $paths[$postPath] = true;
                }
                if ($legacyPath !== '') {
                    $paths[$legacyPath] = true;
                }

                continue;
            }

            $pagePath = $this->normalizeInternalPath('/' . ltrim($slug, '/'));
            if ($pagePath !== '') {
                $paths[$pagePath] = true;
            }
        }

        foreach ((array) ($redirectData['targets']['pages'] ?? []) as $target) {
            $path = $this->normalizeInternalPath((string) ($target['url'] ?? ''));
            if ($path !== '') {
                $paths[$path] = true;
            }
        }

        foreach ((array) ($redirectData['targets']['posts'] ?? []) as $target) {
            $path = $this->normalizeInternalPath((string) ($target['url'] ?? ''));
            if ($path !== '') {
                $paths[$path] = true;
            }
        }

        foreach ((array) ($redirectData['targets']['hubs'] ?? []) as $target) {
            $path = $this->normalizeInternalPath((string) ($target['url'] ?? ''));
            if ($path !== '') {
                $paths[$path] = true;
            }
        }

        foreach ((array) ($redirectData['redirects'] ?? []) as $redirectRow) {
            if ((int) ($redirectRow['is_active'] ?? 0) !== 1) {
                continue;
            }

            $path = $this->normalizeInternalPath((string) ($redirectRow['source_path'] ?? ''));
            if ($path !== '') {
                $paths[$path] = true;
            }
        }

        foreach ($this->loadTaxonomyArchivePaths('post_categories', 'category') as $path) {
            $paths[$path] = true;
        }
        foreach ($this->loadTaxonomyArchivePaths('post_tags', 'tag') as $path) {
            $paths[$path] = true;
        }

        return $paths;
    }

    /**
     * @return list<string>
     */
    private function loadTaxonomyArchivePaths(string $table, string $type): array
    {
        $rows = $this->db->get_results(
            "SELECT slug FROM {$this->prefix}{$table} WHERE slug IS NOT NULL AND slug != '' ORDER BY slug ASC LIMIT 500"
        ) ?: [];

        $paths = [];
        foreach ($rows as $row) {
            $slug = trim((string) ($row->slug ?? ''));
            if ($slug === '') {
                continue;
            }

            if (function_exists('cms_get_archive_path')) {
                try {
                    $archivePath = $this->normalizeInternalPath((string) \cms_get_archive_path($type, $slug));
                    if ($archivePath !== '') {
                        $paths[] = $archivePath;
                        continue;
                    }
                } catch (\Throwable) {
                    // Fallback unten.
                }
            }

            $paths[] = '/' . $type . '/' . rawurlencode($slug);
            $paths[] = '/en/' . $type . '/' . rawurlencode($slug);
        }

        $normalizedPaths = [];
        foreach ($paths as $path) {
            $normalized = $this->normalizeInternalPath($path);
            if ($normalized !== '') {
                $normalizedPaths[$normalized] = $normalized;
            }
        }

        return array_values($normalizedPaths);
    }

    /**
     * @return list<array<string, string>>
     */
    private function loadSitemapCandidates(): array
    {
        $candidates = [];

        foreach (['sitemap.xml', 'pages.xml', 'posts.xml', 'images.xml', 'news.xml'] as $fileName) {
            $filePath = ABSPATH . $fileName;
            if (!is_file($filePath) || !is_readable($filePath)) {
                continue;
            }

            $xml = @file_get_contents($filePath);
            if (!is_string($xml) || $xml === '') {
                continue;
            }

            if (!preg_match_all('/<loc>\s*([^<]+)\s*<\/loc>/i', $xml, $matches)) {
                continue;
            }

            foreach ($matches[1] as $loc) {
                $targetPath = $this->normalizeInternalPath((string) $loc);
                if ($targetPath === '') {
                    continue;
                }

                $candidates[] = [
                    'target_path' => $targetPath,
                    'source_label' => $fileName,
                    'source_detail' => 'XML-Eintrag',
                ];
            }
        }

        return $candidates;
    }

    /**
     * @return list<string>
     */
    private function extractInternalPathsFromHtml(string $html): array
    {
        if ($html === '' || !preg_match_all('/<a\b[^>]*href=["\']([^"\']+)["\']/i', $html, $matches)) {
            return [];
        }

        $paths = [];
        foreach ($matches[1] as $href) {
            $path = $this->normalizeInternalPath((string) $href);
            if ($path === '') {
                continue;
            }

            $paths[$path] = $path;
        }

        return array_values($paths);
    }

    /**
     * @param array<string, array<string, mixed>> $findings
     */
    private function addFinding(
        array &$findings,
        string $targetPath,
        string $sourceKind,
        string $sourceLabel,
        string $sourceDetail,
        string $occurrenceKey,
        int $observed404Hits = 0,
        string $firstSeenAt = '',
        string $lastSeenAt = ''
    ): bool {
        if ($targetPath === '' || !isset(self::SOURCE_LABELS[$sourceKind])) {
            return false;
        }

        if (!isset($findings[$targetPath])) {
            $findings[$targetPath] = [
                'target_path' => $targetPath,
                'occurrences_total' => 0,
                'observed_404_hits' => 0,
                'source_counts' => [],
                'sources' => [],
                '_seen_occurrences' => [],
                'first_seen_at' => '',
                'last_seen_at' => '',
            ];
        }

        if (isset($findings[$targetPath]['_seen_occurrences'][$occurrenceKey])) {
            if ($observed404Hits > 0) {
                $findings[$targetPath]['observed_404_hits'] = max((int) $findings[$targetPath]['observed_404_hits'], $observed404Hits);
            }

            return false;
        }

        $findings[$targetPath]['_seen_occurrences'][$occurrenceKey] = true;
        $findings[$targetPath]['occurrences_total']++;
        $findings[$targetPath]['observed_404_hits'] += max(0, $observed404Hits);
        $findings[$targetPath]['source_counts'][$sourceKind] = (int) ($findings[$targetPath]['source_counts'][$sourceKind] ?? 0) + 1;

        if (count($findings[$targetPath]['sources']) < self::MAX_SOURCE_PREVIEW) {
            $findings[$targetPath]['sources'][] = [
                'kind' => $sourceKind,
                'kind_label' => self::SOURCE_LABELS[$sourceKind],
                'label' => $sourceLabel,
                'detail' => $sourceDetail,
            ];
        }

        if ($firstSeenAt !== '' && ($findings[$targetPath]['first_seen_at'] === '' || $firstSeenAt < $findings[$targetPath]['first_seen_at'])) {
            $findings[$targetPath]['first_seen_at'] = $firstSeenAt;
        }
        if ($lastSeenAt !== '' && ($findings[$targetPath]['last_seen_at'] === '' || $lastSeenAt > $findings[$targetPath]['last_seen_at'])) {
            $findings[$targetPath]['last_seen_at'] = $lastSeenAt;
        }

        return true;
    }

    /**
     * @param array<string, array<string, mixed>> $findings
     * @return list<array<string, mixed>>
     */
    private function finalizeFindings(array $findings): array
    {
        $rows = [];
        foreach ($findings as $targetPath => $finding) {
            unset($finding['_seen_occurrences']);
            $finding['target_url'] = $this->buildPublicUrl($targetPath);
            $finding['source_badges'] = $this->buildSourceBadges((array) ($finding['source_counts'] ?? []));
            $rows[] = $finding;
        }

        usort($rows, static function (array $left, array $right): int {
            $leftHits = (int) ($left['observed_404_hits'] ?? 0);
            $rightHits = (int) ($right['observed_404_hits'] ?? 0);
            if ($leftHits !== $rightHits) {
                return $rightHits <=> $leftHits;
            }

            $leftOccurrences = (int) ($left['occurrences_total'] ?? 0);
            $rightOccurrences = (int) ($right['occurrences_total'] ?? 0);
            if ($leftOccurrences !== $rightOccurrences) {
                return $rightOccurrences <=> $leftOccurrences;
            }

            return strcmp((string) ($left['target_path'] ?? ''), (string) ($right['target_path'] ?? ''));
        });

        return array_slice($rows, 0, self::MAX_FINDINGS);
    }

    /**
     * @param list<array<string, mixed>> $findings
     * @return list<array<string, mixed>>
     */
    private function buildSourceStats(array $findings): array
    {
        $stats = [];
        foreach ($findings as $finding) {
            foreach ((array) ($finding['source_counts'] ?? []) as $sourceKind => $count) {
                if (!isset(self::SOURCE_LABELS[$sourceKind])) {
                    continue;
                }

                if (!isset($stats[$sourceKind])) {
                    $stats[$sourceKind] = [
                        'key' => $sourceKind,
                        'label' => self::SOURCE_LABELS[$sourceKind],
                        'count' => 0,
                    ];
                }

                $stats[$sourceKind]['count'] += (int) $count;
            }
        }

        return array_values($stats);
    }

    /**
     * @param array<string, int> $sourceCounts
     * @return list<array<string, mixed>>
     */
    private function buildSourceBadges(array $sourceCounts): array
    {
        $badges = [];
        foreach ($sourceCounts as $sourceKind => $count) {
            if (!isset(self::SOURCE_LABELS[$sourceKind])) {
                continue;
            }

            $badges[] = [
                'key' => $sourceKind,
                'label' => self::SOURCE_LABELS[$sourceKind],
                'count' => (int) $count,
            ];
        }

        return $badges;
    }

    /**
     * @return array<string, mixed>
     */
    private function getStoredReport(): array
    {
        $report = $this->settings->get(self::SETTINGS_GROUP, self::REPORT_KEY, []);
        return is_array($report) ? $report : [];
    }

    /**
     * @return list<array{path: string, added_at: string}>
     */
    private function getIgnoredEntries(): array
    {
        $entries = $this->settings->get(self::SETTINGS_GROUP, self::IGNORE_KEY, []);
        if (!is_array($entries)) {
            return [];
        }

        $normalized = [];
        foreach ($entries as $entry) {
            $path = $this->normalizeInternalPath((string) (($entry['path'] ?? $entry) ?? ''));
            if ($path === '') {
                continue;
            }

            $normalized[$path] = [
                'path' => $path,
                'added_at' => trim((string) ($entry['added_at'] ?? '')),
            ];
        }

        return array_values($normalized);
    }

    /**
     * @param list<array{path: string, added_at: string}> $entries
     */
    private function storeIgnoredEntries(array $entries): void
    {
        $this->settings->set(self::SETTINGS_GROUP, self::IGNORE_KEY, array_values($entries), false, 0);
    }

    private function isScheduledScanEnabled(): bool
    {
        $value = $this->db->get_var(
            "SELECT option_value FROM {$this->prefix}settings WHERE option_name = ? LIMIT 1",
            ['seo_technical_broken_link_scan']
        );

        return (string) $value === '1';
    }

    private function normalizeInternalPath(string $value): string
    {
        $value = trim($value);
        if ($value === '' || str_starts_with($value, '#')) {
            return '';
        }

        $lower = strtolower($value);
        if (
            str_starts_with($lower, 'mailto:')
            || str_starts_with($lower, 'tel:')
            || str_starts_with($lower, 'javascript:')
            || str_starts_with($lower, 'data:')
        ) {
            return '';
        }

        if (preg_match('#^https?://#i', $value) === 1) {
            $authority = $this->normalizeComparableAuthority($value);
            if ($authority === '' || !in_array($authority, $this->getKnownSiteAuthorities(), true)) {
                return '';
            }

            $value = (string) parse_url($value, PHP_URL_PATH);
        }

        if (!str_starts_with($value, '/')) {
            return '';
        }

        $path = (string) parse_url($value, PHP_URL_PATH);
        $path = '/' . ltrim(trim($path), '/');
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        if ($this->shouldIgnorePath($path)) {
            return '';
        }

        return $path;
    }

    private function shouldIgnorePath(string $path): bool
    {
        if ($path === '' || $path === '/') {
            return false;
        }

        foreach (['/admin', '/member', '/api', '/assets', '/uploads', '/vendor'] as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        if (preg_match('/\.(css|js|map|png|jpe?g|gif|svg|webp|ico|woff2?|ttf|eot|pdf|xml|txt)$/i', $path) === 1) {
            return true;
        }

        return in_array($path, ['/favicon.ico', '/robots.txt', '/sitemap.xml'], true);
    }

    /**
     * @return list<string>
     */
    private function getKnownSiteAuthorities(): array
    {
        if ($this->knownSiteAuthorities !== null) {
            return $this->knownSiteAuthorities;
        }

        $authorities = [];
        $candidates = [];

        if (defined('SITE_URL')) {
            $candidates[] = (string) SITE_URL;
        }

        if (function_exists('cms_runtime_base_url')) {
            try {
                $candidates[] = (string) \cms_runtime_base_url();
            } catch (\Throwable) {
                // Fallback bleibt SITE_URL.
            }
        }

        foreach ($candidates as $candidate) {
            $authority = $this->normalizeComparableAuthority($candidate);
            if ($authority !== '') {
                $authorities[$authority] = true;
            }
        }

        $this->knownSiteAuthorities = array_keys($authorities);

        return $this->knownSiteAuthorities;
    }

    private function normalizeComparableAuthority(string $url): string
    {
        $parts = parse_url(trim($url));
        if (!is_array($parts) || empty($parts['host'])) {
            return '';
        }

        $authority = strtolower((string) $parts['host']);
        $port = isset($parts['port']) ? (int) $parts['port'] : 0;
        if ($port > 0) {
            $authority .= ':' . $port;
        }

        return $authority;
    }

    private function buildPublicUrl(string $path): string
    {
        $path = '/' . ltrim($path, '/');
        if ($path === '//') {
            $path = '/';
        }

        if (function_exists('cms_runtime_base_url')) {
            try {
                $url = (string) \cms_runtime_base_url(ltrim($path, '/'));
                if ($url !== '') {
                    return $url;
                }
            } catch (\Throwable) {
                // Fallback unten.
            }
        }

        if (defined('SITE_URL')) {
            $siteUrl = rtrim((string) SITE_URL, '/');
            if ($siteUrl !== '') {
                return $siteUrl . ($path === '/' ? '/' : $path);
            }
        }

        return $path;
    }

    private function mapTriggerLabel(string $trigger): string
    {
        return match ($trigger) {
            'cron' => 'Cron',
            'manual' => 'Manuell',
            default => $trigger !== '' ? ucfirst($trigger) : '—',
        };
    }

    private function sanitizeLogValue(string $value, int $maxLength = 240): string
    {
        $value = preg_replace('/[\x00-\x1F\x7F]+/u', ' ', trim($value)) ?? '';
        return mb_substr($value, 0, $maxLength);
    }
}

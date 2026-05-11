<?php
/**
 * SEO Trend Service
 *
 * Verdichtet SEO-/Redirect-/404-Kennzahlen für das Dashboard.
 * Schreibt Snapshots ausschließlich im Cron-Kontext und ergänzt im Admin
 * einen read-only Live-Fallback ohne GET-Mutationen.
 *
 * @package CMS\Services
 */

declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;

if (!defined('ABSPATH')) {
    exit;
}

final class SeoTrendService
{
    private const HISTORY_DAYS = 14;

    private static ?self $instance = null;

    private readonly Database $db;
    private readonly string $prefix;
    private readonly SEOService $seoService;
    private readonly SeoAnalysisService $analysisService;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->db = Database::instance();
        $this->prefix = $this->db->getPrefix();
        $this->seoService = SEOService::getInstance();
        $this->analysisService = SeoAnalysisService::getInstance();
    }

    public function ensureTables(): void
    {
        try {
            $this->db->getPdo()->exec(
                "CREATE TABLE IF NOT EXISTS {$this->prefix}seo_dashboard_trends (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    snapshot_date DATE NOT NULL,
                    captured_at DATETIME NOT NULL,
                    content_total INT UNSIGNED NOT NULL DEFAULT 0,
                    average_score SMALLINT UNSIGNED NOT NULL DEFAULT 0,
                    score_good INT UNSIGNED NOT NULL DEFAULT 0,
                    score_warning INT UNSIGNED NOT NULL DEFAULT 0,
                    score_bad INT UNSIGNED NOT NULL DEFAULT 0,
                    redirects_total INT UNSIGNED NOT NULL DEFAULT 0,
                    redirects_active INT UNSIGNED NOT NULL DEFAULT 0,
                    not_found_total INT UNSIGNED NOT NULL DEFAULT 0,
                    not_found_hits INT UNSIGNED NOT NULL DEFAULT 0,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uniq_snapshot_date (snapshot_date),
                    INDEX idx_captured_at (captured_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
        } catch (\Throwable) {
            // Trendpersistenz ist Komfortfunktion und darf die Runtime nicht blockieren.
        }
    }

    /**
     * @param array<int, array<string, mixed>> $auditRows
     * @param array<string, mixed> $overview
     * @return array<string, mixed>
     */
    public function buildDashboardTrendData(array $auditRows, array $overview): array
    {
        $currentSnapshot = $this->buildSnapshotPayloadFromContext($auditRows, $overview);
        $dates = $this->buildDateWindow(self::HISTORY_DAYS);
        $storedSnapshots = $this->loadRecentSnapshots(self::HISTORY_DAYS + 7);
        $storedByDate = [];
        $lastCapturedAt = null;

        foreach ($storedSnapshots as $snapshot) {
            $snapshotDate = (string)($snapshot['snapshot_date'] ?? '');
            if ($snapshotDate === '') {
                continue;
            }

            $storedByDate[$snapshotDate] = $snapshot;

            $capturedAt = trim((string)($snapshot['captured_at'] ?? ''));
            if ($capturedAt !== '' && ($lastCapturedAt === null || strcmp($capturedAt, $lastCapturedAt) > 0)) {
                $lastCapturedAt = $capturedAt;
            }
        }

        $scoreFallback = $this->buildScoreFallbackSeries($auditRows, $dates, (int)($currentSnapshot['average_score'] ?? 0));
        $notFoundFallback = $this->buildCumulativeSeries(
            'not_found_logs',
            'first_seen_at',
            $dates,
            (int)($currentSnapshot['not_found_total'] ?? 0)
        );
        $redirectFallback = $this->buildCumulativeSeries(
            'redirect_rules',
            'created_at',
            $dates,
            (int)($currentSnapshot['redirects_total'] ?? 0)
        );

        $metrics = [
            $this->buildMetricCard(
                'seo_score',
                'Ø SEO-Score',
                (int)($currentSnapshot['average_score'] ?? 0),
                '/100',
                sprintf(
                    'Gut %d · Warnung %d · Kritisch %d',
                    (int)($currentSnapshot['score_good'] ?? 0),
                    (int)($currentSnapshot['score_warning'] ?? 0),
                    (int)($currentSnapshot['score_bad'] ?? 0)
                ),
                'success',
                '#2fb344',
                $this->resolveMetricPoints('average_score', $dates, $storedByDate, $scoreFallback, $currentSnapshot)
            ),
            $this->buildMetricCard(
                'not_found_total',
                '404-Pfade',
                (int)($currentSnapshot['not_found_total'] ?? 0),
                '',
                'Hits gesamt ' . number_format((int)($currentSnapshot['not_found_hits'] ?? 0)),
                'danger',
                '#d63939',
                $this->resolveMetricPoints('not_found_total', $dates, $storedByDate, $notFoundFallback, $currentSnapshot),
                true
            ),
            $this->buildMetricCard(
                'redirects_total',
                'Redirect-Regeln',
                (int)($currentSnapshot['redirects_total'] ?? 0),
                '',
                'Aktiv ' . number_format((int)($currentSnapshot['redirects_active'] ?? 0)),
                'info',
                '#206bc4',
                $this->resolveMetricPoints('redirects_total', $dates, $storedByDate, $redirectFallback, $currentSnapshot)
            ),
        ];

        $storedPointCount = count($storedByDate);
        $historyMode = $storedPointCount >= 7 ? 'snapshots' : ($storedPointCount > 0 ? 'mixed' : 'live-proxy');

        $note = match ($historyMode) {
            'snapshots' => 'Stündliche Cron-Snapshots verdichten SEO-Score, Redirects und 404-Pfade; der aktuelle Stand wird im Dashboard zusätzlich live ergänzt.',
            'mixed' => 'Verlauf kombiniert vorhandene Cron-Snapshots mit read-only Live-Proxies aus Inhalts-, Redirect- und 404-Zeitstempeln.',
            default => 'Noch keine gespeicherten Snapshots vorhanden: Die Sparkline-Karten basieren vorläufig read-only auf vorhandenen Zeitstempeln aus Inhalten, Redirects und 404-Logs.',
        };

        return [
            'metrics' => $metrics,
            'history_days' => self::HISTORY_DAYS,
            'last_captured_at' => $lastCapturedAt,
            'mode' => $historyMode,
            'note' => $note,
        ];
    }

    public function runScheduledSnapshot(): array
    {
        try {
            $this->ensureTables();
            $snapshot = $this->buildLiveSnapshot();
            $this->upsertSnapshot($snapshot);

            return [
                'success' => true,
                'message' => 'SEO-Trend-Snapshot aktualisiert.',
                'snapshot_date' => $snapshot['snapshot_date'],
            ];
        } catch (\Throwable $e) {
            if (class_exists('CMS\\Logger')) {
                \CMS\Logger::instance()->withChannel('seo')->warning('SEO trend snapshot could not be recorded.', [
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);
            }

            return [
                'success' => false,
                'error' => 'SEO-Trend-Snapshot konnte nicht aktualisiert werden.',
            ];
        }
    }

    /**
     * @param array<int, array<string, mixed>> $auditRows
     * @param array<string, mixed> $overview
     * @return array<string, int|string>
     */
    private function buildSnapshotPayloadFromContext(array $auditRows, array $overview): array
    {
        $scoreGood = (int)(($overview['scores']['good'] ?? 0));
        $scoreWarning = (int)(($overview['scores']['warning'] ?? 0));
        $scoreBad = (int)(($overview['scores']['bad'] ?? 0));
        $contentTotal = (int)($overview['total'] ?? count($auditRows));
        $averageScore = (int)($overview['average_score'] ?? $this->averageScoresFromAuditRows($auditRows));
        $redirectStats = $this->loadCurrentRedirectStats();

        return [
            'snapshot_date' => date('Y-m-d'),
            'captured_at' => date('Y-m-d H:i:s'),
            'content_total' => $contentTotal,
            'average_score' => $averageScore,
            'score_good' => $scoreGood,
            'score_warning' => $scoreWarning,
            'score_bad' => $scoreBad,
            'redirects_total' => (int)($redirectStats['redirects_total'] ?? 0),
            'redirects_active' => (int)($redirectStats['redirects_active'] ?? 0),
            'not_found_total' => (int)($redirectStats['not_found_total'] ?? 0),
            'not_found_hits' => (int)($redirectStats['not_found_hits'] ?? 0),
        ];
    }

    /**
     * @return array<string, int|string>
     */
    private function buildLiveSnapshot(): array
    {
        $auditRows = $this->analysisService->enrichAuditRows($this->seoService->getAuditRows());
        $overview = $this->buildOverviewFromAuditRows($auditRows);

        return $this->buildSnapshotPayloadFromContext($auditRows, $overview);
    }

    /**
     * @param array<int, array<string, mixed>> $auditRows
     * @return array<string, mixed>
     */
    private function buildOverviewFromAuditRows(array $auditRows): array
    {
        $scores = ['good' => 0, 'warning' => 0, 'bad' => 0];
        $totalScore = 0;

        foreach ($auditRows as $row) {
            $analysis = (array)($row['analysis'] ?? []);
            $status = (string)($analysis['status'] ?? ($row['seo_score'] ?? 'warning'));
            if (!isset($scores[$status])) {
                $status = 'warning';
            }

            $scores[$status]++;
            $totalScore += (int)($analysis['score'] ?? ($row['seo_score_value'] ?? 0));
        }

        return [
            'scores' => $scores,
            'total' => count($auditRows),
            'average_score' => count($auditRows) > 0 ? (int)round($totalScore / count($auditRows)) : 0,
        ];
    }

    /**
     * @return array<string, int>
     */
    private function loadCurrentRedirectStats(): array
    {
        try {
            return [
                'redirects_total' => (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}redirect_rules"),
                'redirects_active' => (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}redirect_rules WHERE is_active = 1"),
                'not_found_total' => (int)$this->db->get_var("SELECT COUNT(*) FROM {$this->prefix}not_found_logs"),
                'not_found_hits' => (int)$this->db->get_var("SELECT COALESCE(SUM(hit_count), 0) FROM {$this->prefix}not_found_logs"),
            ];
        } catch (\Throwable) {
            return [
                'redirects_total' => 0,
                'redirects_active' => 0,
                'not_found_total' => 0,
                'not_found_hits' => 0,
            ];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function loadRecentSnapshots(int $days): array
    {
        $lookbackDays = max(1, $days - 1);

        try {
            return array_map(static fn(object $row): array => (array)$row, $this->db->get_results(
                "SELECT snapshot_date, captured_at, content_total, average_score, score_good, score_warning, score_bad,
                        redirects_total, redirects_active, not_found_total, not_found_hits
                 FROM {$this->prefix}seo_dashboard_trends
                 WHERE snapshot_date >= DATE_SUB(CURDATE(), INTERVAL {$lookbackDays} DAY)
                 ORDER BY snapshot_date ASC"
            ) ?: []);
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @return array<int, string>
     */
    private function buildDateWindow(int $days): array
    {
        $days = max(2, $days);
        $start = new \DateTimeImmutable('today -' . ($days - 1) . ' days');
        $dates = [];

        for ($i = 0; $i < $days; $i++) {
            $dates[] = $start->modify('+' . $i . ' days')->format('Y-m-d');
        }

        return $dates;
    }

    /**
     * @param array<int, array<string, mixed>> $auditRows
     * @param array<int, string> $dates
     * @return array<string, int>
     */
    private function buildScoreFallbackSeries(array $auditRows, array $dates, int $currentAverage): array
    {
        $firstDate = $dates[0] ?? date('Y-m-d');
        $dateSet = array_fill_keys($dates, true);
        $dailyScores = [];
        $olderScores = [];

        foreach ($auditRows as $row) {
            $score = (int)($row['seo_score_value'] ?? ($row['analysis']['score'] ?? 0));
            $dateKey = $this->normalizeDateKey((string)($row['updated_at'] ?? $row['created_at'] ?? ''));
            if ($dateKey === null) {
                continue;
            }

            if ($dateKey < $firstDate) {
                $olderScores[] = $score;
                continue;
            }

            if (!isset($dateSet[$dateKey])) {
                continue;
            }

            $dailyScores[$dateKey][] = $score;
        }

        $lastKnown = $olderScores !== [] ? $this->averageInt($olderScores) : ($currentAverage > 0 ? $currentAverage : 0);
        $series = [];

        foreach ($dates as $date) {
            if (!empty($dailyScores[$date])) {
                $lastKnown = $this->averageInt($dailyScores[$date]);
            }

            $series[$date] = $lastKnown;
        }

        return $series;
    }

    /**
     * @param array<int, string> $dates
     * @return array<string, int>
     */
    private function buildCumulativeSeries(string $table, string $dateColumn, array $dates, int $currentTotal): array
    {
        $firstDate = $dates[0] ?? date('Y-m-d');
        $dateSet = array_fill_keys($dates, true);
        $perDay = [];
        $baseline = 0;

        try {
            $rows = $this->db->get_results(
                "SELECT DATE({$dateColumn}) AS day, COUNT(*) AS total
                 FROM {$this->prefix}{$table}
                 WHERE {$dateColumn} IS NOT NULL
                 GROUP BY DATE({$dateColumn})
                 ORDER BY day ASC"
            ) ?: [];

            foreach ($rows as $row) {
                $day = trim((string)($row->day ?? ''));
                $count = (int)($row->total ?? 0);
                if ($day === '') {
                    continue;
                }

                if ($day < $firstDate) {
                    $baseline += $count;
                    continue;
                }

                if (!isset($dateSet[$day])) {
                    continue;
                }

                $perDay[$day] = ($perDay[$day] ?? 0) + $count;
            }
        } catch (\Throwable) {
            // Fallback bleibt weiter unten nutzbar.
        }

        $series = [];
        $running = $baseline;
        foreach ($dates as $date) {
            $running += (int)($perDay[$date] ?? 0);
            $series[$date] = $running;
        }

        if ($series !== []) {
            $lastDate = $dates[array_key_last($dates)] ?? null;
            if ($lastDate !== null) {
                $series[$lastDate] = $currentTotal;
            }
        }

        return $series;
    }

    /**
     * @param array<string, array<string, mixed>> $storedByDate
     * @param array<string, int> $fallbackSeries
     * @param array<string, int|string> $currentSnapshot
     * @param array<int, string> $dates
     * @return array<int, array<string, int|string>>
     */
    private function resolveMetricPoints(string $field, array $dates, array $storedByDate, array $fallbackSeries, array $currentSnapshot): array
    {
        $today = date('Y-m-d');
        $points = [];

        foreach ($dates as $date) {
            if ($date === $today) {
                $value = (int)($currentSnapshot[$field] ?? 0);
            } elseif (isset($storedByDate[$date])) {
                $value = (int)($storedByDate[$date][$field] ?? 0);
            } else {
                $value = (int)($fallbackSeries[$date] ?? 0);
            }

            $points[] = [
                'date' => $date,
                'value' => $value,
            ];
        }

        return $points;
    }

    /**
     * @param array<int, array<string, int|string>> $points
     * @return array<string, mixed>
     */
    private function buildMetricCard(
        string $key,
        string $label,
        int $value,
        string $suffix,
        string $secondary,
        string $tone,
        string $sparklineColor,
        array $points,
        bool $invertDelta = false
    ): array {
        $previousValue = count($points) >= 2 ? (int)($points[count($points) - 2]['value'] ?? $value) : $value;
        $delta = $value - $previousValue;
        $deltaTone = $tone;

        if ($invertDelta) {
            $deltaTone = $delta > 0 ? 'danger' : ($delta < 0 ? 'success' : 'neutral');
        } elseif ($key === 'seo_score') {
            $deltaTone = $delta > 0 ? 'success' : ($delta < 0 ? 'warning' : 'neutral');
        } elseif ($key === 'redirects_total') {
            $deltaTone = $delta === 0 ? 'neutral' : 'info';
        }

        return [
            'key' => $key,
            'label' => $label,
            'value' => $value,
            'suffix' => $suffix,
            'secondary' => $secondary,
            'tone' => $tone,
            'delta' => $delta,
            'delta_tone' => $deltaTone,
            'points' => $points,
            'sparkline_color' => $sparklineColor,
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $auditRows
     */
    private function averageScoresFromAuditRows(array $auditRows): int
    {
        $scores = array_map(static fn(array $row): int => (int)($row['seo_score_value'] ?? ($row['analysis']['score'] ?? 0)), $auditRows);
        return $this->averageInt($scores);
    }

    /**
     * @param array<int, int> $values
     */
    private function averageInt(array $values): int
    {
        $values = array_values(array_filter($values, static fn(int $value): bool => $value >= 0));
        if ($values === []) {
            return 0;
        }

        return (int)round(array_sum($values) / count($values));
    }

    private function normalizeDateKey(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        $timestamp = strtotime($value);
        if ($timestamp === false) {
            return null;
        }

        return date('Y-m-d', $timestamp);
    }

    /**
     * @param array<string, int|string> $snapshot
     */
    private function upsertSnapshot(array $snapshot): void
    {
        $snapshotDate = (string)($snapshot['snapshot_date'] ?? '');
        if ($snapshotDate === '') {
            return;
        }

        $existingId = (int)$this->db->get_var(
            "SELECT id FROM {$this->prefix}seo_dashboard_trends WHERE snapshot_date = ? LIMIT 1",
            [$snapshotDate]
        );

        $payload = [
            'snapshot_date' => $snapshotDate,
            'captured_at' => (string)($snapshot['captured_at'] ?? date('Y-m-d H:i:s')),
            'content_total' => (int)($snapshot['content_total'] ?? 0),
            'average_score' => (int)($snapshot['average_score'] ?? 0),
            'score_good' => (int)($snapshot['score_good'] ?? 0),
            'score_warning' => (int)($snapshot['score_warning'] ?? 0),
            'score_bad' => (int)($snapshot['score_bad'] ?? 0),
            'redirects_total' => (int)($snapshot['redirects_total'] ?? 0),
            'redirects_active' => (int)($snapshot['redirects_active'] ?? 0),
            'not_found_total' => (int)($snapshot['not_found_total'] ?? 0),
            'not_found_hits' => (int)($snapshot['not_found_hits'] ?? 0),
        ];

        if ($existingId > 0) {
            $this->db->update('seo_dashboard_trends', $payload, ['id' => $existingId]);
            return;
        }

        $this->db->insert('seo_dashboard_trends', $payload);
    }
}
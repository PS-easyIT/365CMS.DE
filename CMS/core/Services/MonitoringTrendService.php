<?php
declare(strict_types=1);

namespace CMS\Services;

use CMS\Database;
use CMS\Http\Client as HttpClient;

if (!defined('ABSPATH')) {
    exit;
}

final class MonitoringTrendService
{
    private const RETENTION_DAYS = 120;
    private const HOURLY_RANGE_HOURS = 24;
    private const DAILY_RANGE_DAYS = [7, 30];

    private static ?self $instance = null;

    private readonly Database $db;
    private readonly string $prefix;
    private ?bool $trendTableExists = null;

    public static function getInstance(): self
    {
        return self::$instance ??= new self();
    }

    private function __construct()
    {
        $this->db = Database::instance();
        $this->prefix = $this->db->getPrefix();
    }

    public function ensureTables(): void
    {
        try {
            $this->db->getPdo()->exec(
                "CREATE TABLE IF NOT EXISTS {$this->prefix}monitoring_trends (
                    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    captured_hour DATETIME NOT NULL,
                    captured_at DATETIME NOT NULL,
                    response_time_ms INT UNSIGNED DEFAULT NULL,
                    response_status_code SMALLINT UNSIGNED DEFAULT NULL,
                    response_has_error TINYINT(1) NOT NULL DEFAULT 0,
                    disk_used_percent DECIMAL(5,2) DEFAULT NULL,
                    disk_free_bytes BIGINT UNSIGNED DEFAULT NULL,
                    disk_total_bytes BIGINT UNSIGNED DEFAULT NULL,
                    cron_last_run_at DATETIME DEFAULT NULL,
                    cron_lag_minutes INT UNSIGNED DEFAULT NULL,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    UNIQUE KEY uniq_captured_hour (captured_hour),
                    INDEX idx_captured_at (captured_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
            );
            $this->trendTableExists = true;
        } catch (\Throwable) {
            // Komfortfunktion: darf die Runtime nicht blockieren.
        }
    }

    /** @return array<string, mixed> */
    public function runScheduledSnapshot(): array
    {
        try {
            $this->ensureTables();
            $snapshot = $this->buildLiveSnapshot();
            $this->upsertSnapshot($snapshot);
            $this->pruneOldSnapshots();

            return [
                'success' => true,
                'message' => 'Monitoring-Trend-Snapshot aktualisiert.',
                'captured_hour' => $snapshot['captured_hour'],
            ];
        } catch (\Throwable $e) {
            if (class_exists('CMS\\Logger')) {
                \CMS\Logger::instance()->withChannel('monitoring')->warning('Monitoring trend snapshot could not be recorded.', [
                    'exception' => $e::class,
                    'message' => $e->getMessage(),
                ]);
            }

            return [
                'success' => false,
                'error' => 'Monitoring-Trend-Snapshot konnte nicht aktualisiert werden.',
            ];
        }
    }

    /** @param array<string, mixed> $currentResponse */
    public function buildResponseTimeTrendData(array $currentResponse): array
    {
        $currentValue = isset($currentResponse['duration_ms']) ? (float) ((int) $currentResponse['duration_ms']) : null;

        return $this->buildTrendPayload(
            'response_time_ms',
            'Response Time',
            'ms',
            '#206bc4',
            $currentValue,
            'lower-better',
            'avg',
            'Stündliche Snapshots verdichten die Antwortzeit; die aktuelle Kennzahl wird im Admin zusätzlich live gegen die Hauptseite gemessen.'
        );
    }

    /** @param array<string, mixed> $currentDisk */
    public function buildDiskUsageTrendData(array $currentDisk): array
    {
        $currentValue = isset($currentDisk['used_percent']) && $currentDisk['used_percent'] !== null
            ? (float) $currentDisk['used_percent']
            : null;

        return $this->buildTrendPayload(
            'disk_used_percent',
            'Disk-Auslastung',
            '%',
            '#f59f00',
            $currentValue,
            'lower-better',
            'max',
            'Die Historie speichert die Dateisystem-Auslastung pro Stunde; aktuelle Verzeichnisgrößen und freie Bytes bleiben read-only Live-Daten des Servers.'
        );
    }

    /** @param array<string, mixed> $currentCron */
    public function buildCronTrendData(array $currentCron): array
    {
        $lastRun = is_array($currentCron['last_run'] ?? null) ? $currentCron['last_run'] : [];
        $currentValue = isset($lastRun['age_minutes']) && $lastRun['age_minutes'] !== null
            ? (float) ((int) $lastRun['age_minutes'])
            : null;

        return $this->buildTrendPayload(
            'cron_lag_minutes',
            'Cron-Lag',
            ' min',
            '#d63939',
            $currentValue,
            'lower-better',
            'max',
            'Gespeichert wird der Abstand zum zuletzt dokumentierten stündlichen Core-Cron-Lauf. So wird sichtbar, ob Cron stabil taktet oder nach hinten wegläuft.'
        );
    }

    /** @return array<string, int|float|string|null> */
    private function buildLiveSnapshot(): array
    {
        $capturedAt = date('Y-m-d H:i:s');
        $capturedHour = date('Y-m-d H:00:00');
        $response = $this->measureResponseTime((string) SITE_URL);
        $disk = $this->getDiskUsageData();
        $cron = $this->getCronLagSnapshot();

        return [
            'captured_hour' => $capturedHour,
            'captured_at' => $capturedAt,
            'response_time_ms' => $response['duration_ms'],
            'response_status_code' => $response['status_code'],
            'response_has_error' => $response['error'] !== null ? 1 : 0,
            'disk_used_percent' => $disk['used_percent'],
            'disk_free_bytes' => $disk['free_bytes'],
            'disk_total_bytes' => $disk['total_bytes'],
            'cron_last_run_at' => $cron['last_run_at'],
            'cron_lag_minutes' => $cron['age_minutes'],
        ];
    }

    /** @return array<string, mixed> */
    private function buildTrendPayload(
        string $field,
        string $label,
        string $unit,
        string $sparklineColor,
        ?float $currentValue,
        string $trendDirection,
        string $dailyAggregation,
        string $snapshotNote
    ): array {
        $snapshots = $this->loadRecentSnapshots(max(self::DAILY_RANGE_DAYS) + 14);
        $lastCapturedAt = $this->extractLastCapturedAt($snapshots);
        $historyMode = $this->resolveHistoryMode($snapshots);

        return [
            'label' => $label,
            'unit' => $unit,
            'sparkline_color' => $sparklineColor,
            'current_value' => $currentValue,
            'last_captured_at' => $lastCapturedAt,
            'mode' => $historyMode,
            'note' => $historyMode === 'live-only'
                ? 'Noch keine gespeicherten Monitoring-Snapshots vorhanden. Der Bereich zeigt derzeit nur Live-Werte; neue Historienpunkte entstehen über `cms_cron_hourly`.'
                : ($historyMode === 'mixed'
                    ? $snapshotNote . ' Es liegen bereits Snapshots vor, die Historie ist aber noch nicht über alle Zeitfenster vollständig gefüllt.'
                    : $snapshotNote),
            'ranges' => [
                $this->buildHourlyRange($snapshots, $field, $label, $unit, $currentValue, $trendDirection),
                $this->buildDailyRange($snapshots, $field, $label, $unit, 7, $currentValue, $trendDirection, $dailyAggregation),
                $this->buildDailyRange($snapshots, $field, $label, $unit, 30, $currentValue, $trendDirection, $dailyAggregation),
            ],
        ];
    }

    /**
     * @param array<int, array<string, mixed>> $snapshots
     * @return array<string, mixed>
     */
    private function buildHourlyRange(array $snapshots, string $field, string $label, string $unit, ?float $currentValue, string $trendDirection): array
    {
        $windowHours = self::HOURLY_RANGE_HOURS;
        $baseline = $this->resolveBaselineForHourlyRange($snapshots, $field, $windowHours, $currentValue);
        $valuesByHour = [];
        $now = new \DateTimeImmutable('now');
        $currentHourKey = $now->format('Y-m-d H:00:00');

        foreach ($snapshots as $snapshot) {
            $hourKey = (string) ($snapshot['captured_hour'] ?? '');
            $value = $this->normalizeSnapshotValue($snapshot[$field] ?? null);
            if ($hourKey === '' || $value === null) {
                continue;
            }

            $valuesByHour[$hourKey] = $value;
        }

        $start = $now->modify('-' . ($windowHours - 1) . ' hours');
        $points = [];
        $lastKnown = $baseline;
        for ($i = 0; $i < $windowHours; $i++) {
            $slot = $start->modify('+' . $i . ' hours');
            $hourKey = $slot->format('Y-m-d H:00:00');
            if ($hourKey === $currentHourKey && $currentValue !== null) {
                $value = $currentValue;
            } elseif (isset($valuesByHour[$hourKey])) {
                $value = $valuesByHour[$hourKey];
            } else {
                $value = $lastKnown;
            }

            if ($value === null) {
                $value = 0.0;
            }

            $lastKnown = $value;
            $points[] = [
                'label' => $slot->format('H:i'),
                'timestamp' => $slot->format('Y-m-d H:i:s'),
                'value' => $value,
            ];
        }

        return $this->finalizeRangePayload('24h', '24 h', $label, $unit, $points, $trendDirection);
    }

    /**
     * @param array<int, array<string, mixed>> $snapshots
     * @return array<string, mixed>
     */
    private function buildDailyRange(array $snapshots, string $field, string $label, string $unit, int $days, ?float $currentValue, string $trendDirection, string $aggregation): array
    {
        $baseline = $this->resolveBaselineForDailyRange($snapshots, $field, $days, $currentValue, $aggregation);
        $valuesByDate = $this->aggregateSnapshotsByDay($snapshots, $field, $aggregation);
        $today = new \DateTimeImmutable('today');
        $currentDateKey = $today->format('Y-m-d');
        $start = $today->modify('-' . ($days - 1) . ' days');
        $points = [];
        $lastKnown = $baseline;

        for ($i = 0; $i < $days; $i++) {
            $slot = $start->modify('+' . $i . ' days');
            $dateKey = $slot->format('Y-m-d');
            if ($dateKey === $currentDateKey && $currentValue !== null) {
                $value = $currentValue;
            } elseif (isset($valuesByDate[$dateKey])) {
                $value = $valuesByDate[$dateKey];
            } else {
                $value = $lastKnown;
            }

            if ($value === null) {
                $value = 0.0;
            }

            $lastKnown = $value;
            $points[] = [
                'label' => $slot->format('d.m.'),
                'timestamp' => $slot->format('Y-m-d 00:00:00'),
                'value' => $value,
            ];
        }

        return $this->finalizeRangePayload($days . 'd', $days . ' Tage', $label, $unit, $points, $trendDirection);
    }

    /**
     * @param array<int, array<string, mixed>> $snapshots
     * @return array<string, float>
     */
    private function aggregateSnapshotsByDay(array $snapshots, string $field, string $aggregation): array
    {
        $grouped = [];

        foreach ($snapshots as $snapshot) {
            $capturedAt = trim((string) ($snapshot['captured_at'] ?? ''));
            $value = $this->normalizeSnapshotValue($snapshot[$field] ?? null);
            if ($capturedAt === '' || $value === null) {
                continue;
            }

            $dateKey = substr($capturedAt, 0, 10);
            $grouped[$dateKey][] = $value;
        }

        $aggregated = [];
        foreach ($grouped as $dateKey => $values) {
            if ($values === []) {
                continue;
            }

            $aggregated[$dateKey] = match ($aggregation) {
                'max' => max($values),
                'last' => $values[array_key_last($values)],
                default => round(array_sum($values) / count($values), 2),
            };
        }

        return $aggregated;
    }

    /**
     * @param array<int, array<string, mixed>> $points
     * @return array<string, mixed>
     */
    private function finalizeRangePayload(string $key, string $label, string $metricLabel, string $unit, array $points, string $trendDirection): array
    {
        $values = array_map(static fn(array $point): float => (float) ($point['value'] ?? 0.0), $points);
        $current = $values !== [] ? (float) $values[array_key_last($values)] : 0.0;
        $previous = count($values) >= 2 ? (float) $values[count($values) - 2] : $current;
        $delta = round($current - $previous, 2);
        $deltaTone = $this->mapDeltaTone($delta, $trendDirection);

        return [
            'key' => $key,
            'label' => $label,
            'metric_label' => $metricLabel,
            'unit' => $unit,
            'points' => $points,
            'point_count' => count($points),
            'min' => $values !== [] ? min($values) : 0.0,
            'max' => $values !== [] ? max($values) : 0.0,
            'average' => $values !== [] ? round(array_sum($values) / count($values), 2) : 0.0,
            'current' => $current,
            'delta' => $delta,
            'delta_tone' => $deltaTone,
        ];
    }

    private function mapDeltaTone(float $delta, string $trendDirection): string
    {
        if (abs($delta) < 0.01) {
            return 'neutral';
        }

        if ($trendDirection === 'lower-better') {
            return $delta < 0 ? 'success' : 'danger';
        }

        return $delta > 0 ? 'success' : 'warning';
    }

    /**
     * @param array<int, array<string, mixed>> $snapshots
     * @return array<int, array<string, mixed>>
     */
    private function loadRecentSnapshots(int $days): array
    {
        if (!$this->trendTableExists()) {
            return [];
        }

        try {
            return array_map(static fn(object $row): array => (array) $row, $this->db->get_results(
                "SELECT captured_hour, captured_at, response_time_ms, response_status_code, response_has_error,
                        disk_used_percent, disk_free_bytes, disk_total_bytes, cron_last_run_at, cron_lag_minutes
                 FROM {$this->prefix}monitoring_trends
                 WHERE captured_hour >= DATE_SUB(NOW(), INTERVAL {$days} DAY)
                 ORDER BY captured_hour ASC"
            ) ?: []);
        } catch (\Throwable) {
            return [];
        }
    }

    private function trendTableExists(): bool
    {
        if ($this->trendTableExists !== null) {
            return $this->trendTableExists;
        }

        try {
            $table = $this->prefix . 'monitoring_trends';
            $quotedTable = $this->db->getPdo()->quote($table);
            if (!is_string($quotedTable)) {
                $this->trendTableExists = false;
                return false;
            }

            $result = $this->db->getPdo()->query('SHOW TABLES LIKE ' . $quotedTable);
            $this->trendTableExists = $result !== false && $result->fetchColumn() !== false;
        } catch (\Throwable) {
            $this->trendTableExists = false;
        }

        return $this->trendTableExists;
    }

    /** @param array<int, array<string, mixed>> $snapshots */
    private function extractLastCapturedAt(array $snapshots): ?string
    {
        $last = null;
        foreach ($snapshots as $snapshot) {
            $capturedAt = trim((string) ($snapshot['captured_at'] ?? ''));
            if ($capturedAt !== '' && ($last === null || strcmp($capturedAt, $last) > 0)) {
                $last = $capturedAt;
            }
        }

        return $last;
    }

    /** @param array<int, array<string, mixed>> $snapshots */
    private function resolveHistoryMode(array $snapshots): string
    {
        $count = count($snapshots);
        if ($count === 0) {
            return 'live-only';
        }

        return $count >= 24 ? 'snapshots' : 'mixed';
    }

    /** @param array<int, array<string, mixed>> $snapshots */
    private function resolveBaselineForHourlyRange(array $snapshots, string $field, int $hours, ?float $fallback): ?float
    {
        $cutoff = (new \DateTimeImmutable('now'))->modify('-' . ($hours - 1) . ' hours')->format('Y-m-d H:00:00');
        $baseline = null;

        foreach ($snapshots as $snapshot) {
            $hourKey = (string) ($snapshot['captured_hour'] ?? '');
            if ($hourKey === '' || $hourKey >= $cutoff) {
                continue;
            }

            $value = $this->normalizeSnapshotValue($snapshot[$field] ?? null);
            if ($value !== null) {
                $baseline = $value;
            }
        }

        return $baseline ?? $fallback;
    }

    /** @param array<int, array<string, mixed>> $snapshots */
    private function resolveBaselineForDailyRange(array $snapshots, string $field, int $days, ?float $fallback, string $aggregation): ?float
    {
        $cutoff = (new \DateTimeImmutable('today'))->modify('-' . ($days - 1) . ' days')->format('Y-m-d');
        $daily = $this->aggregateSnapshotsByDay($snapshots, $field, $aggregation);
        $baseline = null;
        foreach ($daily as $dateKey => $value) {
            if ($dateKey >= $cutoff) {
                continue;
            }

            $baseline = $value;
        }

        return $baseline ?? $fallback;
    }

    private function normalizeSnapshotValue(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        return null;
    }

    /** @param array<string, int|float|string|null> $snapshot */
    private function upsertSnapshot(array $snapshot): void
    {
        $capturedHour = (string) ($snapshot['captured_hour'] ?? '');
        if ($capturedHour === '') {
            return;
        }

        $existingId = (int) ($this->db->get_var(
            "SELECT id FROM {$this->prefix}monitoring_trends WHERE captured_hour = ? LIMIT 1",
            [$capturedHour]
        ) ?? 0);

        $payload = [
            'captured_hour' => $capturedHour,
            'captured_at' => (string) ($snapshot['captured_at'] ?? date('Y-m-d H:i:s')),
            'response_time_ms' => $snapshot['response_time_ms'] !== null ? (int) $snapshot['response_time_ms'] : null,
            'response_status_code' => $snapshot['response_status_code'] !== null ? (int) $snapshot['response_status_code'] : null,
            'response_has_error' => !empty($snapshot['response_has_error']) ? 1 : 0,
            'disk_used_percent' => $snapshot['disk_used_percent'] !== null ? round((float) $snapshot['disk_used_percent'], 2) : null,
            'disk_free_bytes' => $snapshot['disk_free_bytes'] !== null ? (int) $snapshot['disk_free_bytes'] : null,
            'disk_total_bytes' => $snapshot['disk_total_bytes'] !== null ? (int) $snapshot['disk_total_bytes'] : null,
            'cron_last_run_at' => $snapshot['cron_last_run_at'] !== null ? (string) $snapshot['cron_last_run_at'] : null,
            'cron_lag_minutes' => $snapshot['cron_lag_minutes'] !== null ? (int) $snapshot['cron_lag_minutes'] : null,
        ];

        if ($existingId > 0) {
            $this->db->update('monitoring_trends', $payload, ['id' => $existingId]);
            return;
        }

        $this->db->insert('monitoring_trends', $payload);
    }

    private function pruneOldSnapshots(): void
    {
        if (!$this->trendTableExists()) {
            return;
        }

        try {
            $this->db->query(
                "DELETE FROM {$this->prefix}monitoring_trends WHERE captured_hour < DATE_SUB(NOW(), INTERVAL " . self::RETENTION_DAYS . " DAY)"
            );
        } catch (\Throwable) {
            // Optionales Housekeeping.
        }
    }

    /** @return array{duration_ms:int,status_code:int,error:?string} */
    private function measureResponseTime(string $url): array
    {
        $start = microtime(true);
        $response = HttpClient::getInstance()->get($url, [
            'userAgent' => '365CMS-MonitorTrend/1.0',
            'timeout' => 5,
            'connectTimeout' => 3,
            'maxBytes' => 256 * 1024,
            'allowPrivateHosts' => true,
        ]);

        return [
            'duration_ms' => (int) round((microtime(true) - $start) * 1000),
            'status_code' => (int) ($response['status'] ?? 0),
            'error' => !empty($response['success']) ? null : (string) ($response['error'] ?? 'Anfrage fehlgeschlagen'),
        ];
    }

    /** @return array{total_bytes:int,free_bytes:int,used_percent:?float} */
    private function getDiskUsageData(): array
    {
        $total = @disk_total_space(ABSPATH);
        $free = @disk_free_space(ABSPATH);
        $used = ($total !== false && $free !== false) ? $total - $free : 0;

        return [
            'total_bytes' => $total !== false ? (int) $total : 0,
            'free_bytes' => $free !== false ? (int) $free : 0,
            'used_percent' => ($total !== false && $total > 0) ? round(($used / $total) * 100, 2) : null,
        ];
    }

    /** @return array{last_run_at:?string,age_minutes:?int} */
    private function getCronLagSnapshot(): array
    {
        $lastRunRaw = trim(SettingsService::getInstance()->getString('cron', 'hourly_last_run', ''));
        if ($lastRunRaw === '') {
            return ['last_run_at' => null, 'age_minutes' => null];
        }

        $lastRunTs = strtotime($lastRunRaw);
        if ($lastRunTs === false) {
            return ['last_run_at' => null, 'age_minutes' => null];
        }

        return [
            'last_run_at' => date('Y-m-d H:i:s', $lastRunTs),
            'age_minutes' => max(0, (int) floor((time() - $lastRunTs) / 60)),
        ];
    }
}
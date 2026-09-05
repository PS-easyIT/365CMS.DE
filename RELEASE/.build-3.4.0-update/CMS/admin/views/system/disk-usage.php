<?php
declare(strict_types=1);

if (!defined('ABSPATH')) exit;
if (!defined('CMS_ADMIN_SYSTEM_VIEW')) exit;

$disk = is_array($data['disk'] ?? null) ? $data['disk'] : [];
$dirs = is_array($disk['directories'] ?? null) ? $disk['directories'] : [];
$trend = is_array($data['trend_history'] ?? null) ? $data['trend_history'] : [];
$trendRanges = is_array($trend['ranges'] ?? null) ? $trend['ranges'] : [];
$trendNote = (string)($trend['note'] ?? '');
$trendLastCapturedAt = (string)($trend['last_captured_at'] ?? '');
$trendUnit = (string)($trend['unit'] ?? '%');
$trendColor = (string)($trend['sparkline_color'] ?? '#f59f00');
$trendSignatures = [];
foreach ($trendRanges as $range) {
    if (!is_array($range)) {
        continue;
    }
    $trendSignatures[] = md5(json_encode([
        'avg' => (float) ($range['average'] ?? 0),
        'min' => (float) ($range['min'] ?? 0),
        'max' => (float) ($range['max'] ?? 0),
        'delta' => (float) ($range['delta'] ?? 0),
        'points' => is_array($range['points'] ?? null) ? array_map(static fn(array $point): float => (float) ($point['value'] ?? 0), (array) $range['points']) : [],
    ]) ?: '');
}
$showNoHistoryOverlay = $trendRanges !== []
    && str_contains($trendNote, 'Noch keine gespeicherten Monitoring-Snapshots vorhanden')
    && count(array_unique($trendSignatures)) <= 1;

$deltaToneClasses = [
    'success' => 'bg-green-lt text-green',
    'danger' => 'bg-red-lt text-red',
    'warning' => 'bg-yellow-lt text-yellow',
    'info' => 'bg-blue-lt text-blue',
    'neutral' => 'bg-secondary-lt text-secondary',
];

$formatBytes = static function (int $bytes): string {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2, ',', '.') . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2, ',', '.') . ' KB';
    return $bytes . ' B';
};

$formatNumber = static function (float $value, int $decimals = 1): string {
    return number_format($value, $decimals, ',', '.');
};

$formatDelta = static function (float $delta, string $unit) use ($formatNumber): string {
    $prefix = $delta > 0 ? '+' : '';
    return $prefix . $formatNumber($delta, 1) . $unit;
};

$renderSparkline = static function (array $points, string $strokeColor, string $label): string {
    $values = array_map(static fn(array $point): float => (float)($point['value'] ?? 0.0), $points);
    if ($values === []) {
        return '<div class="text-secondary small">Keine Daten</div>';
    }

    $width = 100.0;
    $height = 32.0;
    $padding = 3.0;
    $min = min($values);
    $max = max($values);
    $range = max(0.01, $max - $min);
    $count = max(1, count($values) - 1);
    $path = [];

    foreach ($values as $index => $value) {
        $x = $padding + (($width - (2 * $padding)) * ($index / $count));
        $normalized = ($value - $min) / $range;
        $y = $height - $padding - (($height - (2 * $padding)) * $normalized);
        $path[] = sprintf('%s%.2F %.2F', $index === 0 ? 'M ' : 'L ', $x, $y);
    }

    $lastX = $padding + (($width - (2 * $padding)) * ((count($values) - 1) / $count));
    $lastValue = (float)$values[array_key_last($values)];
    $lastY = $height - $padding - (($height - (2 * $padding)) * (($lastValue - $min) / $range));

    return sprintf(
        '<svg viewBox="0 0 100 32" role="img" aria-label="%s"><path d="%s" fill="none" stroke="%s" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path><circle cx="%.2F" cy="%.2F" r="2.5" fill="%s"></circle></svg>',
        htmlspecialchars($label, ENT_QUOTES),
        htmlspecialchars(implode(' ', $path), ENT_QUOTES),
        htmlspecialchars($strokeColor, ENT_QUOTES),
        $lastX,
        $lastY,
        htmlspecialchars($strokeColor, ENT_QUOTES)
    );
};
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Diagnose</div>
                <h2 class="page-title">Disk-Usage</h2>
                <div class="text-secondary mt-1">Dateisystem-Auslastung und Verzeichnisgrößen für Uploads, Cache, Logs und Assets — inklusive stündlich verdichteter Verlaufssicht.</div>
            </div>
        </div>
    </div>
</div>
<div class="page-body">
    <div class="container-xl">
        <?php $alertData = $alert; $alertMarginClass = 'mb-4'; require __DIR__ . '/../partials/flash-alert.php'; ?>
        <?php require __DIR__ . '/subnav.php'; ?>

        <div class="row row-deck row-cards mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Gesamt</div>
                        <div class="h1 mb-0"><?php echo htmlspecialchars($formatBytes((int)($disk['total_bytes'] ?? 0))); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Frei</div>
                        <div class="h1 mb-0"><?php echo htmlspecialchars($formatBytes((int)($disk['free_bytes'] ?? 0))); ?></div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <div class="subheader">Auslastung</div>
                        <div class="h1 mb-0"><?php echo htmlspecialchars((string)($disk['used_percent'] ?? '0')); ?>%</div>
                    </div>
                </div>
            </div>
        </div>

        <?php if ($trendRanges !== []): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <div>
                        <h3 class="card-title mb-1">Trendhistorie</h3>
                        <div class="text-secondary small">
                            <?php echo htmlspecialchars($trendNote); ?>
                            <?php if ($trendLastCapturedAt !== ''): ?>
                                · Letzter Snapshot: <?php echo htmlspecialchars($trendLastCapturedAt); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row row-deck row-cards">
                        <?php foreach ($trendRanges as $range): ?>
                            <?php
                            $deltaClass = $deltaToneClasses[(string)($range['delta_tone'] ?? 'neutral')] ?? $deltaToneClasses['neutral'];
                            $points = is_array($range['points'] ?? null) ? $range['points'] : [];
                            ?>
                            <div class="col-md-4">
                                <div class="card h-100 border-0 bg-body-lt admin-monitor-trend-card">
                                    <div class="card-body d-flex flex-column gap-3">
                                        <div class="d-flex justify-content-between align-items-start gap-3">
                                            <div>
                                                <div class="subheader"><?php echo htmlspecialchars((string)($range['label'] ?? 'Verlauf')); ?></div>
                                                <div class="h2 mb-1">Ø <?php echo $formatNumber((float)($range['average'] ?? 0), 1); ?><?php echo htmlspecialchars($trendUnit); ?></div>
                                                <div class="text-secondary small">Min <?php echo $formatNumber((float)($range['min'] ?? 0), 1); ?><?php echo htmlspecialchars($trendUnit); ?> · Max <?php echo $formatNumber((float)($range['max'] ?? 0), 1); ?><?php echo htmlspecialchars($trendUnit); ?></div>
                                            </div>
                                            <div class="chart-sparkline chart-sparkline-wide" aria-hidden="true">
                                                <?php echo $renderSparkline($points, $trendColor, 'Disk-Auslastung ' . (string)($range['label'] ?? '')); ?>
                                            </div>
                                        </div>
                                        <div class="d-flex justify-content-between align-items-center gap-2">
                                            <span class="badge <?php echo htmlspecialchars($deltaClass); ?>"><?php echo htmlspecialchars($formatDelta((float)($range['delta'] ?? 0.0), $trendUnit)); ?> vs. letzter Punkt</span>
                                            <span class="text-secondary small"><?php echo (int)($range['point_count'] ?? 0); ?> Punkte</span>
                                        </div>
                                    </div>
                                    <?php if ($showNoHistoryOverlay): ?>
                                        <div class="admin-monitor-trend-overlay">Noch keine Verlaufsdaten</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Verzeichnisgrößen</h3></div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                    <tr>
                        <th>Verzeichnis</th>
                        <th>Größe</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($dirs as $dir => $info): ?>
                        <tr>
                            <td><?php echo htmlspecialchars((string)$dir); ?></td>
                            <td><?php echo htmlspecialchars((string)(is_array($info) ? ($info['formatted'] ?? '-') : $info)); ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

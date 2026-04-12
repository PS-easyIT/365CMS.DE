<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
if (!defined('CMS_ADMIN_SYSTEM_VIEW')) exit;

$disk = $data['disk'] ?? [];
$dirs = $disk['directories'] ?? [];
$formatBytes = static function (int $bytes): string {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2, ',', '.') . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2, ',', '.') . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2, ',', '.') . ' KB';
    return $bytes . ' B';
};
?>
<div class="page-header d-print-none"><div class="container-xl"><div class="row g-2 align-items-center"><div class="col"><div class="page-pretitle">Diagnose</div><h2 class="page-title">Disk-Usage</h2><div class="text-secondary mt-1">Dateisystem-Auslastung und Verzeichnisgrößen für Uploads, Cache, Logs und Assets.</div></div></div></div></div>
<div class="page-body"><div class="container-xl"><?php $alertData = $alert; $alertMarginClass = 'mb-4'; require __DIR__ . '/../partials/flash-alert.php'; ?><?php require __DIR__ . '/subnav.php'; ?><div class="row row-deck row-cards mb-4"><div class="col-md-4"><div class="card"><div class="card-body"><div class="subheader">Gesamt</div><div class="h1 mb-0"><?php echo htmlspecialchars($formatBytes((int)($disk['total_bytes'] ?? 0))); ?></div></div></div></div><div class="col-md-4"><div class="card"><div class="card-body"><div class="subheader">Frei</div><div class="h1 mb-0"><?php echo htmlspecialchars($formatBytes((int)($disk['free_bytes'] ?? 0))); ?></div></div></div></div><div class="col-md-4"><div class="card"><div class="card-body"><div class="subheader">Auslastung</div><div class="h1 mb-0"><?php echo htmlspecialchars((string)($disk['used_percent'] ?? '0')); ?>%</div></div></div></div></div><div class="card"><div class="card-header"><h3 class="card-title">Verzeichnisgrößen</h3></div><div class="table-responsive"><table class="table table-vcenter card-table"><thead><tr><th>Verzeichnis</th><th>Größe</th></tr></thead><tbody><?php foreach ($dirs as $dir => $info): ?><tr><td><?php echo htmlspecialchars((string)$dir); ?></td><td><?php echo htmlspecialchars((string)(is_array($info) ? ($info['formatted'] ?? '-') : $info)); ?></td></tr><?php endforeach; ?></tbody></table></div></div></div></div>

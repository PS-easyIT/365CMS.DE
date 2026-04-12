<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
if (!defined('CMS_ADMIN_SYSTEM_VIEW')) exit;

$health = $data['health'] ?? [];
$checks = $health['checks'] ?? [];
?>
<div class="page-header d-print-none"><div class="container-xl"><div class="row g-2 align-items-center"><div class="col"><div class="page-pretitle">Diagnose</div><h2 class="page-title">Health-Check</h2><div class="text-secondary mt-1">Sammelt zentrale Plattform-Checks für Datenbank, Verzeichnisse, Antwortzeit und Disk-Auslastung.</div></div></div></div></div>
<div class="page-body"><div class="container-xl"><?php $alertData = $alert; $alertMarginClass = 'mb-4'; require __DIR__ . '/../partials/flash-alert.php'; ?><?php require __DIR__ . '/subnav.php'; ?><div class="row row-deck row-cards mb-4"><div class="col-md-4"><div class="card"><div class="card-body"><div class="subheader">Bestanden</div><div class="h1 mb-0"><?php echo (int)($health['passed'] ?? 0); ?>/<?php echo (int)($health['total'] ?? 0); ?></div></div></div></div></div><div class="card"><div class="card-header"><h3 class="card-title">Checkliste</h3></div><div class="table-responsive"><table class="table table-vcenter card-table table-striped"><thead><tr><th>Check</th><th>Status</th><th>Detail</th></tr></thead><tbody><?php foreach ($checks as $check): ?><tr><td><?php echo htmlspecialchars((string)$check['label']); ?></td><td><span class="badge bg-<?php echo !empty($check['passed']) ? 'success' : 'warning'; ?>-lt"><?php echo !empty($check['passed']) ? 'OK' : 'Prüfen'; ?></span></td><td><?php echo htmlspecialchars((string)$check['detail']); ?></td></tr><?php endforeach; ?></tbody></table></div></div></div></div>

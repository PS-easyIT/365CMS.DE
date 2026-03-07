<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$tasks = $data['scheduled_tasks']['tasks'] ?? [];
?>
<div class="page-header d-print-none"><div class="container-xl"><div class="row g-2 align-items-center"><div class="col"><div class="page-pretitle">Info & Diagnose</div><h2 class="page-title">Scheduled Tasks</h2><div class="text-secondary mt-1">Listet bekannte Cron-/Task-Hooks aus dem aktuellen Codebestand samt Vorkommen auf.</div></div></div></div></div>
<div class="page-body"><div class="container-xl"><?php if (!empty($alert)): ?><div class="alert alert-<?php echo htmlspecialchars($alert['type'] ?? 'info'); ?> mb-4"><?php echo htmlspecialchars($alert['message'] ?? ''); ?></div><?php endif; ?><?php require __DIR__ . '/subnav.php'; ?><div class="card"><div class="card-header"><h3 class="card-title">Registrierte Tasks</h3></div><div class="table-responsive"><table class="table table-vcenter card-table table-striped"><thead><tr><th>Task</th><th>Beschreibung</th><th>Vorkommen</th><th>Dateien</th></tr></thead><tbody><?php if (empty($tasks)): ?><tr><td colspan="4" class="text-center text-secondary py-4">Keine Tasks gefunden.</td></tr><?php else: ?><?php foreach ($tasks as $task): ?><tr><td><code><?php echo htmlspecialchars((string)$task['name']); ?></code></td><td><?php echo htmlspecialchars((string)$task['description']); ?></td><td><?php echo (int)$task['occurrences']; ?></td><td class="text-break"><?php echo htmlspecialchars(implode(', ', (array)$task['files'])); ?></td></tr><?php endforeach; ?><?php endif; ?></tbody></table></div></div></div></div>

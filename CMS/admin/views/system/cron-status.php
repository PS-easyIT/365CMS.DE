<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;
if (!defined('CMS_ADMIN_SYSTEM_VIEW')) exit;

$cron = $data['cron'] ?? [];
$hooks = $cron['hooks'] ?? [];
$commands = $cron['commands'] ?? [];
$mailQueue = $cron['mail_queue'] ?? [];
?>
<div class="page-header d-print-none">
	<div class="container-xl">
		<div class="row g-2 align-items-center">
			<div class="col">
				<div class="page-pretitle">Info & Diagnose</div>
				<h2 class="page-title">Cron-Job Status</h2>
				<div class="text-secondary mt-1">Erkennt registrierte Cron-Hooks im Codebestand und zeigt die zentrale 365CMS-Cron-Schnittstelle für CLI- und URL-Cronjobs.</div>
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
						<div class="subheader">Cron-Datei</div>
						<div class="h1 mb-0 <?php echo !empty($cron['cron_file_exists']) ? 'text-success' : 'text-warning'; ?>">
							<?php echo !empty($cron['cron_file_exists']) ? 'Vorhanden' : 'Nicht gefunden'; ?>
						</div>
						<?php if (!empty($cron['cron_file_path'])): ?>
							<div class="text-secondary mt-2 small text-break"><code><?php echo htmlspecialchars((string) $cron['cron_file_path']); ?></code></div>
						<?php endif; ?>
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="card">
					<div class="card-body">
						<div class="subheader">Registrierte Hooks</div>
						<div class="h1 mb-0"><?php echo (int)($cron['hook_count'] ?? 0); ?></div>
					</div>
				</div>
			</div>
			<div class="col-md-4">
				<div class="card">
					<div class="card-body">
						<div class="subheader">Mail-Queue Batch</div>
						<div class="h1 mb-0"><?php echo (int)($mailQueue['batch_size'] ?? 0); ?></div>
						<div class="text-secondary mt-2 small">
							<?php echo !empty($mailQueue['enabled']) ? 'Mail-Queue aktiv' : 'Mail-Queue deaktiviert'; ?>
						</div>
					</div>
				</div>
			</div>
		</div>

		<div class="card mb-4">
			<div class="card-header">
				<h3 class="card-title">Zentrale Cron-Schnittstelle</h3>
			</div>
			<div class="card-body">
				<p class="text-secondary mb-3">Die zentrale <code>cron.php</code> läuft standardmäßig still und eignet sich für Webhosting-Cronjobs ohne störenden Output. Verwende bevorzugt <code>task=all</code>.</p>
				<div class="row g-3">
					<div class="col-lg-6">
						<label class="form-label">CLI – kompletter Lauf</label>
						<pre class="bg-light border rounded p-3 small text-break mb-0"><code><?php echo htmlspecialchars((string)($commands['cli_all'] ?? '')); ?></code></pre>
					</div>
					<div class="col-lg-6">
						<label class="form-label">CLI – nur Mail-Queue</label>
						<pre class="bg-light border rounded p-3 small text-break mb-0"><code><?php echo htmlspecialchars((string)($commands['cli_mail_queue'] ?? '')); ?></code></pre>
					</div>
					<div class="col-lg-6">
						<label class="form-label">URL – kompletter Lauf</label>
						<pre class="bg-light border rounded p-3 small text-break mb-0"><code><?php echo htmlspecialchars((string)($commands['web_all'] ?? '')); ?></code></pre>
					</div>
					<div class="col-lg-6">
						<label class="form-label">URL – nur Mail-Queue</label>
						<pre class="bg-light border rounded p-3 small text-break mb-0"><code><?php echo htmlspecialchars((string)($commands['web_mail_queue'] ?? '')); ?></code></pre>
					</div>
				</div>
			</div>
		</div>

		<div class="card">
			<div class="card-header">
				<h3 class="card-title">Gefundene Cron-Hooks</h3>
			</div>
			<div class="table-responsive">
				<table class="table table-vcenter card-table table-striped">
					<thead>
					<tr>
						<th>Hook</th>
						<th>Vorkommen</th>
						<th>Quelldateien</th>
					</tr>
					</thead>
					<tbody>
					<?php if (empty($hooks)): ?>
						<tr>
							<td colspan="3" class="text-center text-secondary py-4">Keine registrierten Cron-Hooks gefunden.</td>
						</tr>
					<?php else: ?>
						<?php foreach ($hooks as $hook): ?>
							<tr>
								<td><code><?php echo htmlspecialchars((string)$hook['hook']); ?></code></td>
								<td><?php echo (int)$hook['occurrences']; ?></td>
								<td class="text-break"><?php echo htmlspecialchars(implode(', ', (array)$hook['files'])); ?></td>
							</tr>
						<?php endforeach; ?>
					<?php endif; ?>
					</tbody>
				</table>
			</div>
		</div>
	</div>
</div>

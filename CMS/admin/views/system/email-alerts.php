<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
	exit;
}

if (!defined('CMS_ADMIN_SYSTEM_VIEW')) {
	exit;
}

$settings = $data['email_alerts'] ?? [];
?>
<div class="page-header d-print-none">
	<div class="container-xl">
		<div class="row g-2 align-items-center">
			<div class="col">
				<div class="page-pretitle">Diagnose</div>
				<h2 class="page-title">E-Mail-Benachrichtigungen</h2>
				<div class="text-secondary mt-1">Konfiguriert Schwellenwerte und Empfängeradresse für künftige Performance-/Health-Warnungen.</div>
			</div>
		</div>
	</div>
</div>

<div class="page-body">
	<div class="container-xl">
		<?php $alertData = $alert; $alertMarginClass = 'mb-4'; require __DIR__ . '/../partials/flash-alert.php'; ?>

		<?php require __DIR__ . '/subnav.php'; ?>

		<div class="row row-cards">
			<div class="col-12 col-xl-8">
				<div class="card h-100">
					<div class="card-header"><h3 class="card-title">Alert-Konfiguration</h3></div>
					<div class="card-body">
						<form method="post">
							<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
							<input type="hidden" name="action" value="save_monitoring_alerts">

							<label class="form-check form-switch mb-4">
								<input class="form-check-input" type="checkbox" name="monitor_email_notifications_enabled" value="1" <?php echo ($settings['monitor_email_notifications_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>>
								<span class="form-check-label">E-Mail-Benachrichtigungen aktivieren</span>
							</label>

							<div class="row">
								<div class="col-md-6">
									<div class="mb-3">
										<label class="form-label">Empfänger-E-Mail</label>
										<input class="form-control" type="email" name="monitor_alert_email" value="<?php echo htmlspecialchars((string)($settings['monitor_alert_email'] ?? '')); ?>">
									</div>
								</div>
								<div class="col-md-3">
									<div class="mb-3">
										<label class="form-label">Antwortzeit-Schwelle (ms)</label>
										<input class="form-control" type="number" min="100" name="monitor_response_threshold_ms" value="<?php echo htmlspecialchars((string)($settings['monitor_response_threshold_ms'] ?? '800')); ?>">
									</div>
								</div>
								<div class="col-md-3">
									<div class="mb-3">
										<label class="form-label">Disk-Warnung ab (%)</label>
										<input class="form-control" type="number" min="1" max="99" name="monitor_disk_threshold_percent" value="<?php echo htmlspecialchars((string)($settings['monitor_disk_threshold_percent'] ?? '85')); ?>">
									</div>
								</div>
							</div>

							<label class="form-check form-switch mb-3">
								<input class="form-check-input" type="checkbox" name="monitor_health_endpoint_enabled" value="1" <?php echo ($settings['monitor_health_endpoint_enabled'] ?? '0') === '1' ? 'checked' : ''; ?>>
								<span class="form-check-label">Health-Endpunkt aktiv markieren</span>
							</label>

							<div class="mb-4">
								<label class="form-label">Health-Endpunkt-Pfad</label>
								<input class="form-control" type="text" name="monitor_health_endpoint_path" value="<?php echo htmlspecialchars((string)($settings['monitor_health_endpoint_path'] ?? '/health')); ?>">
							</div>

							<button type="submit" class="btn btn-primary">Einstellungen speichern</button>
						</form>
					</div>
				</div>
			</div>

			<div class="col-12 col-xl-4">
				<div class="card h-100">
					<div class="card-header"><h3 class="card-title">Test-E-Mail</h3></div>
					<div class="card-body">
						<p class="text-secondary small">Prüft direkt im Backend, ob die zentrale Mail-Implementierung des CMS Nachrichten an die konfigurierte Monitoring-Adresse versenden kann.</p>

						<form method="post" class="d-flex flex-column gap-3">
							<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
							<input type="hidden" name="action" value="send_monitoring_test_email">

							<div>
								<label class="form-label">Test-E-Mail an</label>
								<input class="form-control" type="email" name="test_email_recipient" value="<?php echo htmlspecialchars((string)($settings['monitor_alert_email'] ?? '')); ?>" placeholder="monitoring@example.com">
							</div>

							<button type="submit" class="btn btn-outline-primary">Test-E-Mail senden</button>
						</form>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

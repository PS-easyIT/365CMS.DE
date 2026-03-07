<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * View: Member Dashboard Konfiguration
 *
 * @var array  $data
 * @var string $csrfToken
 * @var array|null $alert
 */

$settings = $data['settings'] ?? [];
$stats    = $data['stats'] ?? [];
$widgets  = $data['widgets'] ?? [];
$profileFields = $data['profileFields'] ?? [];
$overview = $data['overview'] ?? [];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Mitglieder &amp; Zugriff</div>
                <h2 class="page-title">Member Dashboard</h2>
                <div class="text-muted mt-1">Mitgliederbereich konfigurieren</div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
<div class="container-xl">

    <?php if ($alert): ?>
        <div class="alert alert-<?php echo htmlspecialchars($alert['type']); ?> alert-dismissible" role="alert">
            <?php echo htmlspecialchars($alert['message']); ?>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="Close"></a>
        </div>
    <?php endif; ?>

    <?php require __DIR__ . '/subnav.php'; ?>

    <!-- KPI Cards -->
    <div class="row row-deck row-cards mb-4">
        <div class="col-sm-6 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Registrierte Mitglieder</div>
                    <div class="h1 mb-0 mt-2"><?php echo (int)($stats['total'] ?? 0); ?></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Aktive Mitglieder</div>
                    <div class="h1 mb-0 mt-2"><?php echo (int)($stats['active'] ?? 0); ?></div>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-lg-4">
            <div class="card">
                <div class="card-body">
                    <div class="subheader">Diese Woche registriert</div>
                    <div class="h1 mb-0 mt-2"><?php echo (int)($stats['thisWeek'] ?? 0); ?></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cards mb-4">
        <div class="col-12">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title">Aktueller Dashboard-Status</h3>
                </div>
                <div class="card-body">
                    <div class="list-group list-group-flush">
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <span>Dashboard aktiv</span>
                            <span class="badge <?php echo !empty($settings['dashboard_enabled']) ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger'; ?>">
                                <?php echo !empty($settings['dashboard_enabled']) ? 'Aktiv' : 'Deaktiviert'; ?>
                            </span>
                        </div>
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <span>Registrierung</span>
                            <span class="badge <?php echo !empty($overview['registrationEnabled']) ? 'bg-success-lt text-success' : 'bg-secondary-lt text-secondary'; ?>">
                                <?php echo !empty($overview['registrationEnabled']) ? 'Erlaubt' : 'Geschlossen'; ?>
                            </span>
                        </div>
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <span>E-Mail-Verifizierung</span>
                            <span class="badge <?php echo !empty($overview['verificationEnabled']) ? 'bg-primary-lt text-primary' : 'bg-secondary-lt text-secondary'; ?>">
                                <?php echo !empty($overview['verificationEnabled']) ? 'Aktiv' : 'Optional'; ?>
                            </span>
                        </div>
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <span>Sichtbare Profil-Felder</span>
                            <strong><?php echo (int)($overview['enabledProfileFields'] ?? 0); ?></strong>
                        </div>
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <span>Aktive Kern-Widgets</span>
                            <strong><?php echo (int)($overview['enabledWidgets'] ?? 0); ?></strong>
                        </div>
                        <div class="list-group-item px-0 d-flex justify-content-between align-items-center">
                            <span>Eigene Info-Widgets</span>
                            <strong><?php echo (int)($overview['customWidgetCount'] ?? 0); ?></strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row row-cards">
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title">Widget-Vorschau</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($settings['widgets'])): ?>
                        <p class="text-muted mb-0">Aktuell sind noch keine Kern-Widgets aktiviert.</p>
                    <?php else: ?>
                        <div class="d-flex flex-wrap gap-2">
                            <?php foreach ($settings['widgets'] as $widgetKey): ?>
                                <span class="badge bg-azure-lt text-azure">
                                    <?php echo htmlspecialchars((string)($widgets[$widgetKey]['label'] ?? $widgetKey)); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-lg-6">
            <div class="card h-100">
                <div class="card-header">
                    <h3 class="card-title">Profil-Vorschau</h3>
                </div>
                <div class="card-body">
                    <?php if (empty($settings['profile_fields'])): ?>
                        <p class="text-muted mb-0">Noch keine zusätzlichen Profil-Felder ausgewählt.</p>
                    <?php else: ?>
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <?php foreach ($settings['profile_fields'] as $fieldKey): ?>
                                <span class="badge bg-green-lt text-green">
                                    <?php echo htmlspecialchars((string)($profileFields[$fieldKey]['label'] ?? $fieldKey)); ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <div class="text-muted small">
                        Abo-Menüpunkt für Mitglieder: <strong><?php echo !empty($overview['subscriptionVisible']) ? 'sichtbar' : 'ausgeblendet'; ?></strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

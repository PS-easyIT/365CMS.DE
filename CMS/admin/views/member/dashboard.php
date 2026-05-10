<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_MEMBER_VIEW')) {
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
$dashboardPreview = is_array($data['dashboardPreview'] ?? null) ? $data['dashboardPreview'] : [];
$previewMode = (string)($_GET['preview'] ?? '') === '1';
$previewUrl = '/admin/member-dashboard?preview=1';
$overviewUrl = '/admin/member-dashboard';
$previewDesign = is_array($dashboardPreview['design'] ?? null) ? $dashboardPreview['design'] : [];
$previewPrimary = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($previewDesign['primary'] ?? '')) === 1 ? (string)$previewDesign['primary'] : '#6366f1';
$previewAccent = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($previewDesign['accent'] ?? '')) === 1 ? (string)$previewDesign['accent'] : '#8b5cf6';
$previewBg = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($previewDesign['bg'] ?? '')) === 1 ? (string)$previewDesign['bg'] : '#f1f5f9';
$previewCardBg = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($previewDesign['card_bg'] ?? '')) === 1 ? (string)$previewDesign['card_bg'] : '#ffffff';
$previewText = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($previewDesign['text'] ?? '')) === 1 ? (string)$previewDesign['text'] : '#1e293b';
$previewModules = is_array($dashboardPreview['frontend_modules'] ?? null) ? $dashboardPreview['frontend_modules'] : [];
$previewSectionLabels = [
    'quick_start' => 'Schnellstart',
    'stats' => 'Statistiken',
    'widgets' => 'Info-Widgets',
    'plugins' => 'Plugin-Widgets',
];
$previewSectionOrder = [];
foreach ((array)($dashboardPreview['section_order'] ?? []) as $previewSectionKey) {
    $previewSectionKey = (string)$previewSectionKey;
    if (isset($previewSectionLabels[$previewSectionKey])) {
        $previewSectionOrder[] = $previewSectionKey;
    }
}
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Mitglieder &amp; Zugriff</div>
                <h2 class="page-title">Member Dashboard</h2>
                <div class="text-muted mt-1">Mitgliederbereich konfigurieren</div>
            </div>
            <div class="col-auto ms-auto d-print-none">
                <?php if ($previewMode): ?>
                    <a href="<?php echo htmlspecialchars($overviewUrl, ENT_QUOTES); ?>" class="btn btn-outline-secondary">Vorschau schließen</a>
                <?php else: ?>
                    <a href="<?php echo htmlspecialchars($previewUrl, ENT_QUOTES); ?>" class="btn btn-outline-primary">Frontend-Vorschau öffnen</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
<div class="container-xl">

    <?php $alertData = $alert; $alertMarginClass = 'mb-4'; require __DIR__ . '/../partials/flash-alert.php'; ?>

    <?php require __DIR__ . '/subnav.php'; ?>

    <?php if ($previewMode): ?>
        <div class="alert alert-info" role="status">
            <strong>Read-only Vorschau:</strong>
            Diese Ansicht nutzt ausschließlich gespeicherte Member-Runtime-Einstellungen. Es wird nichts gespeichert, kein Sicherheitstoken in der URL transportiert und ungültige Werte fallen auf sichere Defaults zurück.
        </div>

        <div class="card mb-4">
            <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                <div>
                    <h3 class="card-title mb-0">Member-Dashboard-Vorschau</h3>
                    <div class="text-secondary small">Simulation der wichtigsten `/member/dashboard`-Sektionen für ein Beispielmitglied.</div>
                </div>
                <span class="badge <?php echo !empty($dashboardPreview['dashboard_enabled']) ? 'bg-success-lt text-success' : 'bg-danger-lt text-danger'; ?>">
                    <?php echo !empty($dashboardPreview['dashboard_enabled']) ? 'Dashboard aktiv' : 'Dashboard deaktiviert'; ?>
                </span>
            </div>
            <div class="card-body" style="background: <?php echo htmlspecialchars($previewBg, ENT_QUOTES); ?>; color: <?php echo htmlspecialchars($previewText, ENT_QUOTES); ?>;">
                <?php if (empty($dashboardPreview['dashboard_enabled'])): ?>
                    <div class="alert alert-warning" role="status">Das Dashboard ist aktuell deaktiviert. Mitglieder würden auf ihr Profil weitergeleitet; die Vorschau zeigt trotzdem die gespeicherte Konfiguration.</div>
                <?php endif; ?>

                <?php if ($previewSectionOrder !== []): ?>
                    <div class="card mb-3" style="background: <?php echo htmlspecialchars($previewCardBg, ENT_QUOTES); ?>;">
                        <div class="card-body py-3">
                            <div class="text-secondary small mb-2">Gespeicherte Bereichsreihenfolge</div>
                            <div class="d-flex flex-wrap gap-2">
                                <?php foreach ($previewSectionOrder as $sectionIndex => $previewSectionKey): ?>
                                    <span class="badge bg-purple-lt text-purple">
                                        <?php echo (int)$sectionIndex + 1; ?>. <?php echo htmlspecialchars($previewSectionLabels[$previewSectionKey] ?? $previewSectionKey); ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($dashboardPreview['show_welcome'])): ?>
                    <div class="card mb-3" style="background: <?php echo htmlspecialchars($previewCardBg, ENT_QUOTES); ?>; border-left: 4px solid <?php echo htmlspecialchars($previewPrimary, ENT_QUOTES); ?>;">
                        <div class="card-body">
                            <div class="page-pretitle">Persönlicher Überblick</div>
                            <h3 class="mb-2"><?php echo htmlspecialchars(str_replace('{name}', 'Max Mitglied', (string)($dashboardPreview['greeting'] ?? 'Guten Tag, {name}!'))); ?></h3>
                            <p class="text-secondary mb-0"><?php echo htmlspecialchars((string)($dashboardPreview['welcome_text'] ?? '')); ?></p>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($previewModules['show_quickstart'])): ?>
                    <div class="row row-cards mb-3">
                        <?php foreach ([['Profil', 'Persönliche Daten verwalten'], ['Sicherheit', 'MFA, Passkeys und Passwort'], ['Dateien', 'Medien und Uploads']] as $quickLink): ?>
                            <div class="col-md-4">
                                <div class="card h-100" style="background: <?php echo htmlspecialchars($previewCardBg, ENT_QUOTES); ?>;">
                                    <div class="card-body">
                                        <div class="fw-semibold"><?php echo htmlspecialchars($quickLink[0]); ?></div>
                                        <div class="text-secondary small"><?php echo htmlspecialchars($quickLink[1]); ?></div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <?php if (!empty($previewModules['show_stats'])): ?>
                    <div class="row row-cards mb-3">
                        <?php foreach ([['👋', '12 Logins', 'in den letzten 30 Tagen'], ['🛡️', '82/100', 'Security Score'], ['✉️', '3 ungelesen', 'Nachrichten'], ['📦', '2 aktive Sessions', 'Beispielstatus']] as $statCard): ?>
                            <div class="col-sm-6 col-lg-3">
                                <div class="card card-sm h-100" style="background: <?php echo htmlspecialchars($previewCardBg, ENT_QUOTES); ?>;">
                                    <div class="card-body d-flex gap-2 align-items-center">
                                        <span class="avatar" style="background: <?php echo htmlspecialchars($previewPrimary, ENT_QUOTES); ?>; color: #fff;"><?php echo htmlspecialchars($statCard[0]); ?></span>
                                        <div>
                                            <div class="fw-semibold"><?php echo htmlspecialchars($statCard[1]); ?></div>
                                            <div class="text-secondary small"><?php echo htmlspecialchars($statCard[2]); ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

                <div class="row row-cards">
                    <div class="col-lg-8">
                        <?php if (!empty($previewModules['show_onboarding_panel']) && !empty($dashboardPreview['onboarding']['enabled'])): ?>
                            <div class="card mb-3" style="background: <?php echo htmlspecialchars($previewCardBg, ENT_QUOTES); ?>;">
                                <div class="card-header"><h4 class="card-title mb-0"><?php echo htmlspecialchars((string)($dashboardPreview['onboarding']['title'] ?? 'Onboarding')); ?></h4></div>
                                <div class="card-body">
                                    <p class="text-secondary"><?php echo htmlspecialchars((string)($dashboardPreview['onboarding']['intro'] ?? '')); ?></p>
                                    <?php if (!empty($dashboardPreview['onboarding']['steps']) && is_array($dashboardPreview['onboarding']['steps'])): ?>
                                        <ol class="mb-0">
                                            <?php foreach (array_slice($dashboardPreview['onboarding']['steps'], 0, 5) as $step): ?>
                                                <li><?php echo htmlspecialchars((string)$step); ?></li>
                                            <?php endforeach; ?>
                                        </ol>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($previewModules['show_custom_widgets']) && !empty($dashboardPreview['custom_widgets']) && is_array($dashboardPreview['custom_widgets'])): ?>
                            <div class="row row-cards mb-3">
                                <?php foreach ($dashboardPreview['custom_widgets'] as $previewWidget): ?>
                                    <div class="col-md-6">
                                        <div class="card h-100" style="background: <?php echo htmlspecialchars($previewCardBg, ENT_QUOTES); ?>;">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <span class="avatar avatar-sm"><?php echo htmlspecialchars((string)($previewWidget['icon'] ?? '✨')); ?></span>
                                                    <h4 class="card-title mb-0"><?php echo htmlspecialchars((string)($previewWidget['title'] ?? 'Widget')); ?></h4>
                                                </div>
                                                <div class="text-secondary small"><?php echo htmlspecialchars((string)($previewWidget['content'] ?? '')); ?></div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if (!empty($previewModules['show_plugin_widgets']) && !empty($dashboardPreview['plugin_widgets']) && is_array($dashboardPreview['plugin_widgets'])): ?>
                            <div class="row row-cards">
                                <?php foreach (array_slice($dashboardPreview['plugin_widgets'], 0, 4) as $pluginWidget): ?>
                                    <?php $pluginColor = preg_match('/^#[0-9a-fA-F]{6}$/', (string)($pluginWidget['color'] ?? '')) === 1 ? (string)$pluginWidget['color'] : $previewAccent; ?>
                                    <div class="col-md-6">
                                        <div class="card h-100" style="background: <?php echo htmlspecialchars($previewCardBg, ENT_QUOTES); ?>;">
                                            <div class="card-body">
                                                <div class="d-flex justify-content-between gap-2">
                                                    <div>
                                                        <div class="text-secondary small">Plugin</div>
                                                        <h4 class="card-title mb-1"><?php echo htmlspecialchars((string)($pluginWidget['label'] ?? 'Plugin')); ?></h4>
                                                        <p class="text-secondary small mb-0"><?php echo htmlspecialchars((string)($pluginWidget['description'] ?? '')); ?></p>
                                                    </div>
                                                    <span class="avatar" style="background: <?php echo htmlspecialchars($pluginColor, ENT_QUOTES); ?>; color: #fff;"><?php echo htmlspecialchars((string)($pluginWidget['icon'] ?? '🔌')); ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="col-lg-4">
                        <div class="card mb-3" style="background: <?php echo htmlspecialchars($previewCardBg, ENT_QUOTES); ?>;">
                            <div class="card-header"><h4 class="card-title mb-0">Aktive Kern-Widgets</h4></div>
                            <div class="card-body">
                                <?php if (empty($dashboardPreview['core_widgets']) || !is_array($dashboardPreview['core_widgets'])): ?>
                                    <div class="text-secondary">Keine Kern-Widgets aktiv.</div>
                                <?php else: ?>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ($dashboardPreview['core_widgets'] as $coreWidget): ?>
                                            <span class="badge bg-azure-lt text-azure"><?php echo htmlspecialchars((string)($coreWidget['label'] ?? 'Widget')); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="card mb-3" style="background: <?php echo htmlspecialchars($previewCardBg, ENT_QUOTES); ?>;">
                            <div class="card-header"><h4 class="card-title mb-0">Profil-Felder</h4></div>
                            <div class="card-body">
                                <?php if (empty($dashboardPreview['profile_fields']) || !is_array($dashboardPreview['profile_fields'])): ?>
                                    <div class="text-secondary">Keine zusätzlichen Profilfelder aktiv.</div>
                                <?php else: ?>
                                    <div class="d-flex flex-wrap gap-2">
                                        <?php foreach ($dashboardPreview['profile_fields'] as $profileField): ?>
                                            <span class="badge bg-green-lt text-green"><?php echo htmlspecialchars((string)($profileField['label'] ?? 'Profilfeld')); ?></span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($previewModules['show_notifications_panel']) && !empty($dashboardPreview['notifications']['center_enabled'])): ?>
                            <div class="card" style="background: <?php echo htmlspecialchars($previewCardBg, ENT_QUOTES); ?>;">
                                <div class="card-header"><h4 class="card-title mb-0">Benachrichtigungen</h4></div>
                                <div class="card-body text-secondary"><?php echo htmlspecialchars((string)($dashboardPreview['notifications']['empty_text'] ?? 'Aktuell gibt es keine neuen Meldungen.')); ?></div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

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

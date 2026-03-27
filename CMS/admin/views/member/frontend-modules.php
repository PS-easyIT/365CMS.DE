<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_MEMBER_VIEW')) {
    exit;
}

$settings = $data['settings'] ?? [];
$modules = $settings['frontend_modules'] ?? [];
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Mitglieder &amp; Zugriff</div>
                <h2 class="page-title">Member Dashboard – Frontend-Module</h2>
                <div class="text-muted mt-1">Steuere, welche Frontend-Sektionen das Member-Dashboard sichtbar, prominent oder bewusst schlank halten.</div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php $alertData = $alert; $alertMarginClass = 'mb-4'; require __DIR__ . '/../partials/flash-alert.php'; ?>

        <?php require __DIR__ . '/subnav.php'; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="settings_section" value="frontend-modules">

            <div class="row row-cards">
                <?php
                $items = [
                    'show_quickstart' => ['title' => 'Schnellstart', 'desc' => 'Zeigt die Action-Bar mit Profil, Sicherheit und Schnelllinks oberhalb der Inhalte.'],
                    'show_stats' => ['title' => 'Statistik-Kacheln', 'desc' => 'Mitgliedsstatus, Abo, Aktivität und Sicherheit prominent im Dashboard anzeigen.'],
                    'show_custom_widgets' => ['title' => 'Eigene Info-Widgets', 'desc' => 'Die individuell gepflegten Infoboxen aus der Widget-Seite ausgeben.'],
                    'show_plugin_widgets' => ['title' => 'Plugin-Widgets', 'desc' => 'Plugin- und Bereichs-Widgets im Frontend anzeigen.'],
                    'show_notifications_panel' => ['title' => 'Benachrichtigungs-Panel', 'desc' => 'Ein kompaktes Panel für Meldungen und Aufmerksamkeitspunkte einblenden.'],
                    'show_onboarding_panel' => ['title' => 'Onboarding-Panel', 'desc' => 'Neue Mitglieder mit Checkliste und Call-to-Action direkt im Dashboard begrüßen.'],
                ];
                ?>
                <?php foreach ($items as $key => $item): ?>
                    <div class="col-md-6 col-xl-4">
                        <label class="card card-sm h-100 cursor-pointer">
                            <div class="card-body">
                                <div class="form-check form-switch mb-2">
                                    <input class="form-check-input" type="checkbox" name="<?php echo htmlspecialchars($key); ?>" value="1" <?php echo !empty($modules[$key]) ? 'checked' : ''; ?>>
                                    <span class="form-check-label fw-semibold"><?php echo htmlspecialchars($item['title']); ?></span>
                                </div>
                                <div class="text-muted small"><?php echo htmlspecialchars($item['desc']); ?></div>
                            </div>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex justify-content-end mt-4">
                <button type="submit" class="btn btn-primary">Frontend-Module speichern</button>
            </div>
        </form>
    </div>
</div>

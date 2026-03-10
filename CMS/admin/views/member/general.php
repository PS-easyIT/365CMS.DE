<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_MEMBER_VIEW')) {
    exit;
}

$settings = $data['settings'] ?? [];
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Mitglieder &amp; Zugriff</div>
                <h2 class="page-title">Member Dashboard – Allgemein</h2>
                <div class="text-muted mt-1">Grundfunktionen, Begrüßungslogik und Dashboard-Einstieg des Member-Bereichs steuern.</div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if (!empty($alert)): ?>
            <div class="alert alert-<?php echo htmlspecialchars($alert['type'] ?? 'info'); ?> mb-4" role="alert">
                <?php echo htmlspecialchars($alert['message'] ?? ''); ?>
            </div>
        <?php endif; ?>

        <?php require __DIR__ . '/subnav.php'; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="settings_section" value="general">

            <div class="row row-cards">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Zugang zum Member-Dashboard</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input type="checkbox" name="dashboard_enabled" class="form-check-input" value="1" <?php echo !empty($settings['dashboard_enabled']) ? 'checked' : ''; ?>>
                                    <span class="form-check-label">Member-Dashboard aktivieren</span>
                                </label>
                                <div class="form-hint">Schaltet die zentrale Mitgliederoberfläche im Frontend ein oder aus.</div>
                            </div>
                            <div class="alert alert-info mb-0" role="alert">
                                Öffentliche Registrierung, Standardrolle und Authentifizierungsoptionen werden zentral unter
                                <a href="<?php echo htmlspecialchars((defined('SITE_URL') ? SITE_URL : '') . '/admin/user-settings'); ?>" class="alert-link">Benutzer &amp; Gruppen → Einstellungen</a>
                                verwaltet.
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Kurztext &amp; Hinweise</h3>
                        </div>
                        <div class="card-body">
                            <div>
                                <label class="form-label" for="welcome_message">Interne Willkommensnachricht</label>
                                <textarea id="welcome_message" name="welcome_message" class="form-control" rows="4" placeholder="Kurze Einführung für neue Mitglieder ..."><?php echo htmlspecialchars((string)($settings['welcome_message'] ?? '')); ?></textarea>
                                <div class="form-hint">Kann als Basistext für Onboarding oder Dashboard-Hinweise genutzt werden.</div>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Header &amp; Begrüßung</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input type="checkbox" name="show_welcome" class="form-check-input" value="1" <?php echo !empty($settings['show_welcome']) ? 'checked' : ''; ?>>
                                    <span class="form-check-label">Willkommensbanner im Frontend anzeigen</span>
                                </label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="dashboard_greeting">Begrüßungszeile</label>
                                <input id="dashboard_greeting" name="dashboard_greeting" type="text" class="form-control" value="<?php echo htmlspecialchars((string)($settings['dashboard_greeting'] ?? 'Guten Tag, {name}!')); ?>">
                                <div class="form-hint">Platzhalter <code>{name}</code> wird automatisch ersetzt.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="dashboard_logo">Logo-URL für Dashboard-Header</label>
                                <input id="dashboard_logo" name="dashboard_logo" type="text" class="form-control" value="<?php echo htmlspecialchars((string)($settings['dashboard_logo'] ?? '')); ?>" placeholder="https://.../logo.svg">
                            </div>
                            <div>
                                <label class="form-label" for="dashboard_welcome_text">Willkommenstext im Frontend</label>
                                <textarea id="dashboard_welcome_text" name="dashboard_welcome_text" class="form-control" rows="5" placeholder="Dieser Text erscheint im hervorgehobenen Begrüßungsbereich des Member-Dashboards."><?php echo htmlspecialchars((string)($settings['dashboard_welcome_text'] ?? '')); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card sticky-top" style="top: 1rem;">
                        <div class="card-header">
                            <h3 class="card-title">Speichern</h3>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small">Diese Seite steuert den Einstieg in den Member-Bereich: Aktivierung, Begrüßung und Dashboard-Header.</p>
                            <button type="submit" class="btn btn-primary w-100">Allgemeine Einstellungen speichern</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

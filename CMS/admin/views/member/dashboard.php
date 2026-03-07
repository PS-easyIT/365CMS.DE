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

$profileFields = [
    'first_name'  => 'Vorname',
    'last_name'   => 'Nachname',
    'bio'         => 'Biografie',
    'website'     => 'Website',
    'phone'       => 'Telefon',
    'company'     => 'Firma',
    'position'    => 'Position',
    'location'    => 'Standort',
    'social'      => 'Social-Media-Links',
    'avatar'      => 'Profilbild',
];
?>

<div class="container-xl">
    <!-- Header -->
    <div class="page-header d-flex align-items-center mb-4">
        <div>
            <h2 class="page-title">Member Dashboard</h2>
            <div class="text-muted mt-1">Mitgliederbereich konfigurieren</div>
        </div>
    </div>

    <?php if ($alert): ?>
        <div class="alert alert-<?php echo htmlspecialchars($alert['type']); ?> alert-dismissible" role="alert">
            <?php echo htmlspecialchars($alert['message']); ?>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="Close"></a>
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

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="save">

        <div class="row">
            <div class="col-lg-8">
                <!-- Allgemeine Einstellungen -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Allgemein</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input type="checkbox" name="dashboard_enabled" class="form-check-input" value="1"
                                       <?php echo !empty($settings['dashboard_enabled']) ? 'checked' : ''; ?>>
                                <span class="form-check-label">Member-Dashboard aktiviert</span>
                            </label>
                        </div>
                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input type="checkbox" name="registration_enabled" class="form-check-input" value="1"
                                       <?php echo !empty($settings['registration_enabled']) ? 'checked' : ''; ?>>
                                <span class="form-check-label">Registrierung erlauben</span>
                            </label>
                        </div>
                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input type="checkbox" name="email_verification" class="form-check-input" value="1"
                                       <?php echo !empty($settings['email_verification']) ? 'checked' : ''; ?>>
                                <span class="form-check-label">E-Mail-Verifizierung erforderlich</span>
                            </label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Standard-Rolle für neue Mitglieder</label>
                            <select name="default_role" class="form-select">
                                <option value="member" <?php echo ($settings['default_role'] ?? 'member') === 'member' ? 'selected' : ''; ?>>Mitglied</option>
                                <option value="author" <?php echo ($settings['default_role'] ?? '') === 'author' ? 'selected' : ''; ?>>Autor</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Willkommensnachricht</label>
                            <textarea name="welcome_message" class="form-control" rows="3" placeholder="Wird auf dem Dashboard des Mitglieds angezeigt..."><?php echo htmlspecialchars($settings['welcome_message'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Dashboard Widgets -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Dashboard Widgets</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($widgets as $key => $label): ?>
                                <div class="col-md-6 mb-2">
                                    <label class="form-check">
                                        <input type="checkbox" name="widgets[<?php echo htmlspecialchars($key); ?>]"
                                               class="form-check-input" value="1"
                                               <?php echo in_array($key, $settings['widgets'] ?? []) ? 'checked' : ''; ?>>
                                        <span class="form-check-label"><?php echo htmlspecialchars($label); ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>

                <!-- Profile Fields -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Profil-Felder</h3>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Wähle aus, welche Felder im Mitglieder-Profil angezeigt werden.</p>
                        <div class="row">
                            <?php foreach ($profileFields as $key => $label): ?>
                                <div class="col-md-6 mb-2">
                                    <label class="form-check">
                                        <input type="checkbox" name="profile_fields[<?php echo htmlspecialchars($key); ?>]"
                                               class="form-check-input" value="1"
                                               <?php echo in_array($key, $settings['profile_fields'] ?? []) ? 'checked' : ''; ?>>
                                        <span class="form-check-label"><?php echo htmlspecialchars($label); ?></span>
                                    </label>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card sticky-top" style="top: 1rem;">
                    <div class="card-body">
                        <button type="submit" class="btn btn-primary w-100">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-device-floppy" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2"/><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M14 4l0 4l-6 0l0 -4"/></svg>
                            Einstellungen speichern
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

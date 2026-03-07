<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var array $data Einstellungsdaten
 * @var string $csrfToken CSRF-Token
 */

$s          = $data['settings'];
$timezones  = $data['timezones'];
$languages  = $data['languages'];
?>

<div class="container-xl">
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center">
            <div class="col-auto">
                <h2 class="page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-settings me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10.325 4.317c.426 -1.756 2.924 -1.756 3.35 0a1.724 1.724 0 0 0 2.573 1.066c1.543 -.94 3.31 .826 2.37 2.37a1.724 1.724 0 0 0 1.066 2.573c1.756 .426 1.756 2.924 0 3.35a1.724 1.724 0 0 0 -1.066 2.573c.94 1.543 -.826 3.31 -2.37 2.37a1.724 1.724 0 0 0 -2.573 1.066c-.426 1.756 -2.924 1.756 -3.35 0a1.724 1.724 0 0 0 -2.573 -1.066c-1.543 .94 -3.31 -.826 -2.37 -2.37a1.724 1.724 0 0 0 -1.066 -2.573c-1.756 -.426 -1.756 -2.924 0 -3.35a1.724 1.724 0 0 0 1.066 -2.573c-.94 -1.543 .826 -3.31 2.37 -2.37c1 .608 2.296 .07 2.572 -1.065z"/><path d="M9 12a3 3 0 1 0 6 0a3 3 0 0 0 -6 0"/></svg>
                    Allgemeine Einstellungen
                </h2>
            </div>
        </div>
    </div>

    <form method="post" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="save">

        <div class="row">
            <!-- Website-Grunddaten -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Website</h3></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label required">Website-Name</label>
                            <input type="text" name="site_name" class="form-control" value="<?php echo htmlspecialchars($s['site_name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Beschreibung</label>
                            <textarea name="site_description" class="form-control" rows="2"><?php echo htmlspecialchars($s['site_description']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Website-URL</label>
                            <input type="url" name="site_url" class="form-control" value="<?php echo htmlspecialchars($s['site_url']); ?>" required>
                        </div>
                        <div>
                            <label class="form-label required">Admin-E-Mail</label>
                            <input type="email" name="admin_email" class="form-control" value="<?php echo htmlspecialchars($s['admin_email']); ?>" required>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lokalisierung -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Lokalisierung</h3></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Sprache</label>
                            <select name="language" class="form-select">
                                <?php foreach ($languages as $code => $label): ?>
                                    <option value="<?php echo $code; ?>" <?php echo $s['language'] === $code ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Zeitzone</label>
                            <select name="timezone" class="form-select">
                                <?php foreach ($timezones as $tz): ?>
                                    <option value="<?php echo $tz; ?>" <?php echo $s['timezone'] === $tz ? 'selected' : ''; ?>><?php echo htmlspecialchars($tz); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label">Datumsformat</label>
                                <select name="date_format" class="form-select">
                                    <?php foreach (['d.m.Y' => '31.12.2025', 'Y-m-d' => '2025-12-31', 'm/d/Y' => '12/31/2025', 'd/m/Y' => '31/12/2025'] as $fmt => $example): ?>
                                        <option value="<?php echo $fmt; ?>" <?php echo $s['date_format'] === $fmt ? 'selected' : ''; ?>><?php echo $example; ?> (<?php echo $fmt; ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Zeitformat</label>
                                <select name="time_format" class="form-select">
                                    <?php foreach (['H:i' => '14:30', 'H:i:s' => '14:30:00', 'g:i A' => '2:30 PM'] as $fmt => $example): ?>
                                        <option value="<?php echo $fmt; ?>" <?php echo $s['time_format'] === $fmt ? 'selected' : ''; ?>><?php echo $example; ?> (<?php echo $fmt; ?>)</option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Inhalte -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Inhalte</h3></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Beiträge pro Seite</label>
                            <input type="number" name="posts_per_page" class="form-control" value="<?php echo (int)$s['posts_per_page']; ?>" min="1" max="100">
                        </div>
                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" name="registration_enabled" value="1" <?php echo $s['registration_enabled'] ? 'checked' : ''; ?>>
                                <span class="form-check-label">Benutzerregistrierung aktivieren</span>
                            </label>
                        </div>
                        <div>
                            <label class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" name="comments_enabled" value="1" <?php echo $s['comments_enabled'] ? 'checked' : ''; ?>>
                                <span class="form-check-label">Kommentare aktivieren</span>
                            </label>
                        </div>
                        <div class="mt-3">
                            <label class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" name="marketplace_enabled" value="1" <?php echo $s['marketplace_enabled'] ? 'checked' : ''; ?>>
                                <span class="form-check-label">Marketplace anzeigen (Theme- & Plugin-Marketplace)</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Wartung -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Wartungsmodus</h3></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" name="maintenance_mode" value="1" <?php echo $s['maintenance_mode'] ? 'checked' : ''; ?>>
                                <span class="form-check-label">Wartungsmodus aktivieren</span>
                            </label>
                            <small class="form-hint">Besucher sehen eine Wartungsseite. Administratoren haben weiterhin Zugriff.</small>
                        </div>
                        <div>
                            <label class="form-label">Wartungsnachricht</label>
                            <textarea name="maintenance_message" class="form-control" rows="3"><?php echo htmlspecialchars($s['maintenance_message']); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Erweitert -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Erweitert</h3></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Google Analytics / Tag Manager ID</label>
                            <input type="text" name="google_analytics" class="form-control" value="<?php echo htmlspecialchars($s['google_analytics']); ?>" placeholder="G-XXXXXXXXXX oder UA-XXXXXX-X">
                        </div>
                        <div>
                            <label class="form-label">robots.txt</label>
                            <textarea name="robots_txt" class="form-control" rows="5" style="font-family: monospace; font-size: 13px;" placeholder="User-agent: *&#10;Allow: /"><?php echo htmlspecialchars($s['robots_txt']); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-device-floppy me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2"/><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M14 4l0 4l-6 0l0 -4"/></svg>
                Einstellungen speichern
            </button>
        </div>
    </form>
</div>

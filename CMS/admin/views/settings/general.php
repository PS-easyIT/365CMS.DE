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
$mail       = $data['mail'] ?? [];
$timezones  = $data['timezones'];
$languages  = $data['languages'];
$currentTab = ($currentTab ?? 'general') === 'content' ? 'content' : 'general';
$settingsBaseUrl = (defined('SITE_URL') ? SITE_URL : '') . '/admin/settings';
$hideSettingsTabs = $hideSettingsTabs ?? false;
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

    <?php if (!empty($alert)): ?>
        <div class="alert alert-<?php echo htmlspecialchars($alert['type'] ?? 'info'); ?> mb-4" role="alert">
            <?php echo htmlspecialchars($alert['message'] ?? ''); ?>
        </div>
    <?php endif; ?>

    <form method="post" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="tab" value="<?php echo htmlspecialchars($currentTab); ?>">

        <?php if (!$hideSettingsTabs): ?>
        <div class="mb-4">
            <ul class="nav nav-tabs">
                <li class="nav-item">
                    <a class="nav-link <?php echo $currentTab === 'general' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($settingsBaseUrl); ?>">
                        Allgemein
                    </a>
                </li>
                <li class="nav-item ms-auto">
                    <a class="nav-link <?php echo $currentTab === 'content' ? 'active' : ''; ?>" href="<?php echo htmlspecialchars($settingsBaseUrl); ?>?tab=content">
                        Beiträge &amp; Sites
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>

        <?php if ($currentTab === 'general'): ?>
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
                            <label class="form-label">Website-Logo</label>
                            <input type="text" name="site_logo" class="form-control" value="<?php echo htmlspecialchars($s['site_logo'] ?? ''); ?>" placeholder="/uploads/logo.svg oder https://...">
                            <div class="form-hint">Theme-unabhängiger Logo-Pfad bzw. eine URL, die Themes optional laden können.</div>
                            <?php if (!empty($s['site_logo'])): ?>
                                <div class="mt-2">
                                    <img src="<?php echo htmlspecialchars($s['site_logo']); ?>" alt="Website-Logo Vorschau" style="max-height:48px; max-width:220px; border-radius:6px; border:1px solid var(--tblr-border-color); padding:6px; background:#fff;">
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label required">Website-URL</label>
                            <input type="url" name="site_url" class="form-control" value="<?php echo htmlspecialchars($s['site_url']); ?>" required>
                            <div class="form-hint">Diese URL wird jetzt auch in der Runtime-Konfiguration (`config/app.php`) aktualisiert, damit `SITE_URL` zentral systemweit mitzieht.</div>
                        </div>
                        <div class="border rounded p-3 bg-light mb-0">
                            <div class="fw-semibold mb-1">Zentrale URL-Umstellung</div>
                            <div class="text-secondary small mb-3">
                                Aktive Runtime-URL: <code><?php echo htmlspecialchars((string)($s['runtime_site_url'] ?? $s['site_url'])); ?></code><br>
                                Beim Speichern kann 365CMS absolute Verweise von der alten Basis-URL auf die neue URL in Inhalten, Settings, Tabellen und Weiterleitungen mitmigrieren.
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Alte Basis-URL für Nachmigration <span class="text-secondary small">(optional)</span></label>
                                <input type="url" class="form-control" name="migrate_from_site_url" value="" placeholder="https://alte-domain.tld">
                                <div class="form-hint">Optional für bereits abgeschlossene Umzüge: Wenn hier eine alte Domain eingetragen wird, ersetzt 365CMS beim Speichern auch dann noch alte Bild-, Upload- und Medien-URLs zentral, selbst wenn die aktuelle Website-URL bereits korrekt gesetzt ist.</div>
                            </div>
                            <label class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" name="migrate_site_url_references" value="1" checked>
                                <span class="form-check-label">Alte absolute CMS-URLs zentral auf die neue Website-URL umstellen</span>
                            </label>
                            <div class="mt-3 d-flex flex-wrap gap-2 align-items-center">
                                <button type="submit" class="btn btn-outline-warning" name="action" value="run_site_url_migration">
                                    Nur URL-Nachmigration ausführen
                                </button>
                                <span class="text-secondary small">Führt nur die URL-Ersetzung aus und speichert keine anderen Einstellungen neu.</span>
                            </div>
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

            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="card-title mb-0">Mail-System</h3>
                        <span class="badge bg-<?php echo !empty($mail['uses_smtp']) ? 'success' : 'warning'; ?>-lt">
                            <?php echo htmlspecialchars((string)($mail['transport_label'] ?? 'Mailversand')); ?>
                        </span>
                    </div>
                    <div class="card-body">
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Absender</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars((string)(($mail['from_name'] ?? '') !== '' ? ($mail['from_name'] . ' <' . ($mail['from_email'] ?? '') . '>') : ($mail['from_email'] ?? ''))); ?>" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">SMTP-Host</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars((string)($mail['host'] ?? '')); ?>" readonly>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Port / TLS</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars((string)(($mail['port'] ?? '') . ' / ' . ($mail['encryption'] ?? 'none'))); ?>" readonly>
                            </div>
                        </div>

                        <div class="alert alert-info mb-3" role="alert">
                            Mail-Transport, Azure OAuth2, Microsoft Graph und Versand-Logs werden jetzt zentral unter
                            <a href="<?php echo htmlspecialchars((defined('SITE_URL') ? SITE_URL : '') . '/admin/mail-settings'); ?>" class="alert-link">System → Mail &amp; Azure OAuth2</a>
                            verwaltet.
                        </div>

                        <div class="d-flex flex-wrap gap-2 align-items-center">
                            <a href="<?php echo htmlspecialchars((defined('SITE_URL') ? SITE_URL : '') . '/admin/mail-settings'); ?>" class="btn btn-outline-primary">Mail-System öffnen</a>
                            <span class="text-secondary small">Hier siehst du nur den aktiven Laufzeitstatus.</span>
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
                            <div class="alert alert-info mb-0" role="alert">
                                Benutzer-, Registrierungs- und Authentifizierungsoptionen werden jetzt unter
                                <a href="<?php echo htmlspecialchars((defined('SITE_URL') ? SITE_URL : '') . '/admin/user-settings'); ?>" class="alert-link">Benutzer &amp; Gruppen → Einstellungen</a>
                                verwaltet.
                            </div>
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

            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Website Slugs</h3></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Beitrags-URL-Struktur</label>
                            <select name="post_permalink_preset" class="form-select">
                                <option value="blog" <?php echo ($s['post_permalink_preset'] ?? 'blog') === 'blog' ? 'selected' : ''; ?>>/blog/beitragsname</option>
                                <option value="dated" <?php echo ($s['post_permalink_preset'] ?? '') === 'dated' ? 'selected' : ''; ?>>/jahr/monat/tag/beitragsname</option>
                                <option value="slug" <?php echo ($s['post_permalink_preset'] ?? '') === 'slug' ? 'selected' : ''; ?>>/beitragsname</option>
                                <option value="year" <?php echo ($s['post_permalink_preset'] ?? '') === 'year' ? 'selected' : ''; ?>>/jahr/beitragsname</option>
                                <option value="custom" <?php echo ($s['post_permalink_preset'] ?? '') === 'custom' ? 'selected' : ''; ?>>Benutzerdefiniert</option>
                            </select>
                            <div class="form-hint">Erlaubte Platzhalter: <code>%year%</code>, <code>%monthnum%</code>, <code>%day%</code> und <code>%postname%</code>.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Benutzerdefinierte Struktur</label>
                            <input type="text" name="post_permalink_custom" class="form-control" value="<?php echo htmlspecialchars((string)($s['post_permalink_custom'] ?? '')); ?>" placeholder="/%year%/%monthnum%/%day%/%postname%">
                        </div>
                        <div class="alert alert-info mb-3" role="alert">
                            <div class="fw-semibold mb-1">Aktive Beispiel-URL</div>
                            <code><?php echo htmlspecialchars((string)($s['post_permalink_example'] ?? '/blog/beispielbeitrag')); ?></code>
                            <div class="small mt-1">Die Blog-Übersicht bleibt weiterhin unter <code>/blog</code> erreichbar.</div>
                        </div>
                        <div class="border rounded p-3 bg-light">
                            <div class="fw-semibold mb-1">Manuelle Nachkorrektur importierter Slugs</div>
                            <p class="text-secondary small mb-3">Prüft importierte Beiträge und Seiten aus dem WordPress-Importer und übernimmt – wenn möglich – den ursprünglichen Quell-Slug. Bereits verlinkte Pfade bleiben per Weiterleitung erreichbar.</p>
                            <button type="submit" class="btn btn-outline-warning" name="action" value="repair_imported_slugs">
                                Falsche Import-Slugs jetzt manuell korrigieren
                            </button>
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
        <?php else: ?>
        <div class="row">
            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Editor</h3></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Standard-Editor</label>
                            <select name="editor_type" class="form-select">
                                <option value="editorjs" <?php echo $s['editor_type'] === 'editorjs' ? 'selected' : ''; ?>>Editor.js</option>
                                <option value="suneditor" <?php echo $s['editor_type'] === 'suneditor' ? 'selected' : ''; ?>>SunEditor</option>
                            </select>
                            <small class="form-hint">Gilt für Seiten und Beiträge beim Erstellen und Bearbeiten.</small>
                        </div>
                        <div>
                            <label class="form-label">Beitrags-Editorbreite</label>
                            <div class="input-group">
                                <input type="number" name="post_editor_width" class="form-control" min="320" max="1600" step="10" value="<?php echo (int)$s['post_editor_width']; ?>">
                                <span class="input-group-text">px</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Standard beim Speichern</h3></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Neue Beiträge</label>
                            <select name="post_default_status" class="form-select">
                                <option value="draft" <?php echo $s['post_default_status'] === 'draft' ? 'selected' : ''; ?>>Als Entwurf speichern</option>
                                <option value="published" <?php echo $s['post_default_status'] === 'published' ? 'selected' : ''; ?>>Direkt veröffentlichen</option>
                            </select>
                        </div>
                        <div>
                            <label class="form-label">Neue Seiten / Sites</label>
                            <select name="page_default_status" class="form-select">
                                <option value="draft" <?php echo $s['page_default_status'] === 'draft' ? 'selected' : ''; ?>>Als Entwurf speichern</option>
                                <option value="published" <?php echo $s['page_default_status'] === 'published' ? 'selected' : ''; ?>>Direkt veröffentlichen</option>
                                <option value="private" <?php echo $s['page_default_status'] === 'private' ? 'selected' : ''; ?>>Privat anlegen</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Seiten-Editor</h3></div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Seiten-Editorbreite</label>
                            <div class="input-group">
                                <input type="number" name="page_editor_width" class="form-control" min="320" max="1600" step="10" value="<?php echo (int)$s['page_editor_width']; ?>">
                                <span class="input-group-text">px</span>
                            </div>
                            <small class="form-hint">Bestimmt die nutzbare Inhaltsbreite im Editor für Seiten.</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Hinweise</h3></div>
                    <div class="card-body">
                        <ul class="mb-0 text-secondary small ps-3">
                            <li class="mb-2">Die Editor-Auswahl greift global für Seiten und Beiträge.</li>
                            <li class="mb-2">Standard-Status gilt vor allem für neue Einträge; bestehende Inhalte behalten ihren Status.</li>
                            <li>Breiten werden direkt in den Editor-Ansichten übernommen.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary" name="action" value="save">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-device-floppy me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2"/><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M14 4l0 4l-6 0l0 -4"/></svg>
                Einstellungen speichern
            </button>
        </div>
    </form>
</div>

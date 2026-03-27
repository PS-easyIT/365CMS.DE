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
$editorMediaToken = $editorMediaToken ?? '';
$defaultPluginRegistryUrl = 'https://365cms.de/marketplace/plugins/index.json';
$defaultThemeMarketplaceUrl = 'https://365cms.de/marketplace/themes';
$defaultCoreUpdateUrl = 'https://365cms.de/marketplace/core/365cms/update.json';
$usesDefaultPluginRegistry = (($s['plugin_registry_url'] ?? $defaultPluginRegistryUrl) === $defaultPluginRegistryUrl);
$usesDefaultThemeMarketplace = (($s['theme_marketplace_url'] ?? $defaultThemeMarketplaceUrl) === $defaultThemeMarketplaceUrl);
$usesDefaultCoreUpdate = (($s['core_update_url'] ?? $defaultCoreUpdateUrl) === $defaultCoreUpdateUrl);
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

    <?php require dirname(__DIR__) . '/partials/flash-alert.php'; ?>

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
                            <div class="input-group">
                                <input
                                    type="text"
                                    id="siteLogoInput"
                                    name="site_logo"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($s['site_logo'] ?? ''); ?>"
                                    placeholder="/uploads/logo.svg oder https://..."
                                    data-media-target-input>
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    data-open-media-picker
                                    data-target-input="siteLogoInput"
                                    data-preview-id="siteLogoPreview"
                                    data-picker-title="Website-Logo auswählen">
                                    Medien
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    data-clear-media-input
                                    data-target-input="siteLogoInput"
                                    data-preview-id="siteLogoPreview">
                                    Leeren
                                </button>
                            </div>
                            <div class="form-hint">Theme-unabhängiger Logo-Pfad bzw. eine URL, die Themes optional laden können.</div>
                            <div
                                id="siteLogoPreview"
                                class="mt-2"
                                data-media-preview
                                data-preview-variant="logo"
                                data-input-id="siteLogoInput"
                                <?php echo empty($s['site_logo']) ? 'hidden' : ''; ?>>
                                <?php if (!empty($s['site_logo'])): ?>
                                    <img src="<?php echo htmlspecialchars($s['site_logo']); ?>" alt="Website-Logo Vorschau" style="max-height:48px; max-width:220px; border-radius:6px; border:1px solid var(--tblr-border-color); padding:6px; background:#fff;">
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Favicon</label>
                            <div class="input-group">
                                <input
                                    type="text"
                                    id="siteFaviconInput"
                                    name="site_favicon"
                                    class="form-control"
                                    value="<?php echo htmlspecialchars($s['site_favicon'] ?? ''); ?>"
                                    placeholder="/uploads/favicon.ico oder /uploads/favicon.png"
                                    data-media-target-input>
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    data-open-media-picker
                                    data-target-input="siteFaviconInput"
                                    data-preview-id="siteFaviconPreview"
                                    data-picker-title="Favicon auswählen">
                                    Medien
                                </button>
                                <button
                                    type="button"
                                    class="btn btn-outline-secondary"
                                    data-clear-media-input
                                    data-target-input="siteFaviconInput"
                                    data-preview-id="siteFaviconPreview">
                                    Leeren
                                </button>
                            </div>
                            <div class="form-hint">Globales Favicon für 365CMS. Unterstützt relative Pfade unterhalb der Website oder absolute HTTPS-URLs.</div>
                            <div
                                id="siteFaviconPreview"
                                class="mt-2 d-flex align-items-center gap-2"
                                data-media-preview
                                data-preview-variant="favicon"
                                data-input-id="siteFaviconInput"
                                <?php echo empty($s['site_favicon']) ? 'hidden' : ''; ?>>
                                <?php if (!empty($s['site_favicon'])): ?>
                                    <img src="<?php echo htmlspecialchars($s['site_favicon']); ?>" alt="Favicon Vorschau" width="32" height="32" style="border-radius:8px; border:1px solid var(--tblr-border-color); padding:4px; background:#fff; object-fit:contain;">
                                    <code><?php echo htmlspecialchars((string)$s['site_favicon']); ?></code>
                                <?php endif; ?>
                            </div>
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
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Kategorie-Basis (DE)</label>
                                <div class="input-group">
                                    <span class="input-group-text">/</span>
                                    <input type="text" name="category_base_de" class="form-control" value="<?php echo htmlspecialchars((string)($s['category_base_de'] ?? 'kategorie')); ?>" placeholder="kategorie">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Kategorie-Basis (EN)</label>
                                <div class="input-group">
                                    <span class="input-group-text">/</span>
                                    <input type="text" name="category_base_en" class="form-control" value="<?php echo htmlspecialchars((string)($s['category_base_en'] ?? 'category')); ?>" placeholder="category">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tag-Basis (DE)</label>
                                <div class="input-group">
                                    <span class="input-group-text">/</span>
                                    <input type="text" name="tag_base_de" class="form-control" value="<?php echo htmlspecialchars((string)($s['tag_base_de'] ?? 'tag')); ?>" placeholder="tag">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Tag-Basis (EN)</label>
                                <div class="input-group">
                                    <span class="input-group-text">/</span>
                                    <input type="text" name="tag_base_en" class="form-control" value="<?php echo htmlspecialchars((string)($s['tag_base_en'] ?? 'tag')); ?>" placeholder="tag">
                                </div>
                            </div>
                        </div>
                        <div class="form-hint mb-3">Die Archive werden sprachabhängig auf dieselben Theme-Templates <code>category.php</code> und <code>tag.php</code> geleitet. Beispiel: <code>/<?php echo htmlspecialchars((string)($s['category_base_de'] ?? 'kategorie')); ?>/azure</code> bzw. <code>/en/<?php echo htmlspecialchars((string)($s['category_base_en'] ?? 'category')); ?>/azure</code>.</div>
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

            <div class="col-lg-6 mb-4">
                <div class="card h-100">
                    <div class="card-header"><h3 class="card-title">Marketplace &amp; Updates</h3></div>
                    <div class="card-body">
                        <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
                            <span class="badge bg-green-lt text-green">Offizielle 365CMS-Endpunkte</span>
                            <span class="text-secondary small">Empfohlene produktive Standardwerte für Plugin-Marketplace, Theme-Katalog und Core-Updates.</span>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                                <label class="form-label mb-0">Plugin-Registry</label>
                                <?php if ($usesDefaultPluginRegistry): ?>
                                    <span class="badge bg-azure-lt text-azure">Produktiv-Standard aktiv</span>
                                <?php else: ?>
                                    <span class="badge bg-yellow-lt text-yellow">Individuell überschrieben</span>
                                <?php endif; ?>
                            </div>
                            <input type="url" name="plugin_registry_url" class="form-control" value="<?php echo htmlspecialchars((string)($s['plugin_registry_url'] ?? 'https://365cms.de/marketplace/plugins/index.json')); ?>" placeholder="https://365cms.de/marketplace/plugins/index.json">
                            <div class="form-hint">Zentraler JSON-Feed für den Plugin-Marketplace im Admin. Offizieller Standard: <code><?php echo htmlspecialchars($defaultPluginRegistryUrl); ?></code></div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                                <label class="form-label mb-0">Theme-Marketplace-Basis</label>
                                <?php if ($usesDefaultThemeMarketplace): ?>
                                    <span class="badge bg-azure-lt text-azure">Produktiv-Standard aktiv</span>
                                <?php else: ?>
                                    <span class="badge bg-yellow-lt text-yellow">Individuell überschrieben</span>
                                <?php endif; ?>
                            </div>
                            <input type="url" name="theme_marketplace_url" class="form-control" value="<?php echo htmlspecialchars((string)($s['theme_marketplace_url'] ?? 'https://365cms.de/marketplace/themes')); ?>" placeholder="https://365cms.de/marketplace/themes">
                            <div class="form-hint">Basis-URL für Theme-Katalog und Theme-Manifeste. Offizieller Standard: <code><?php echo htmlspecialchars($defaultThemeMarketplaceUrl); ?></code>. Das System ergänzt intern weiterhin <code>/index.json</code>.</div>
                        </div>
                        <div class="mb-3">
                            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-1">
                                <label class="form-label mb-0">Core-Update-Feed</label>
                                <?php if ($usesDefaultCoreUpdate): ?>
                                    <span class="badge bg-azure-lt text-azure">Produktiv-Standard aktiv</span>
                                <?php else: ?>
                                    <span class="badge bg-yellow-lt text-yellow">Individuell überschrieben</span>
                                <?php endif; ?>
                            </div>
                            <input type="url" name="core_update_url" class="form-control" value="<?php echo htmlspecialchars((string)($s['core_update_url'] ?? 'https://365cms.de/marketplace/core/365cms/update.json')); ?>" placeholder="https://365cms.de/marketplace/core/365cms/update.json">
                            <div class="form-hint">Expliziter Feed für 365CMS-Core-Updates. Offizieller Standard: <code><?php echo htmlspecialchars($defaultCoreUpdateUrl); ?></code>.</div>
                        </div>
                        <div class="alert alert-info mb-0" role="alert">
                            Diese Felder machen die aktuell verwendeten zentralen Marketplace-Endpunkte sichtbar. Leere oder ungültige Werte fallen beim Speichern automatisch wieder auf die offiziellen 365CMS-Defaults zurück. So bleibt das System produktionssicher, auch wenn jemand hier einmal kreativ wird.
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

    <?php if ($currentTab === 'general'): ?>
        <div class="modal modal-blur fade" id="settingsMediaPickerModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" data-media-picker-title>Datei auswählen</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                    </div>
                    <div class="modal-body">
                        <div
                            data-media-picker-modal
                            data-api-url="<?php echo htmlspecialchars(SITE_URL . '/api/media', ENT_QUOTES); ?>"
                            data-csrf-token="<?php echo htmlspecialchars($editorMediaToken, ENT_QUOTES); ?>">
                            <p class="text-secondary small mb-3">Ein Klick übernimmt eine Datei direkt in das aktuell gewählte Feld. Angezeigt werden interne Bilddateien, Logos und Favicons.</p>
                            <div class="row g-2 align-items-center mb-3">
                                <div class="col-md-8">
                                    <input type="search" class="form-control" placeholder="Dateien filtern …" data-media-picker-search>
                                </div>
                                <div class="col-md-4 text-secondary small" data-media-picker-status>
                                    Lade Medien …
                                </div>
                            </div>
                            <div class="row row-cards g-3" data-media-picker-grid></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

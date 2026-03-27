<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * View: Font Manager
 *
 * @var array  $data
 * @var string $csrfToken
 * @var array|null $alert
 */

$systemFonts  = $data['systemFonts'] ?? [];
$fontStacks   = $data['fontStacks'] ?? [];
$customFonts  = $data['customFonts'] ?? [];
$customFontRows = is_array($data['customFontRows'] ?? null) ? $data['customFontRows'] : [];
$headingFont  = $data['headingFont'] ?? 'system-ui';
$bodyFont     = $data['bodyFont'] ?? 'system-ui';
$useLocalFonts = !empty($data['useLocalFonts']);
$fontSize     = $data['fontSize'] ?? '16';
$lineHeight   = $data['lineHeight'] ?? '1.6';
$scanResults  = $data['scanResults'] ?? ['theme' => '', 'scannedFiles' => 0, 'detectedFonts' => []];
$fontCatalog  = $data['fontCatalog'] ?? [];
$activeThemeSlug = $data['activeThemeSlug'] ?? '';
$scanSummary = is_array($data['scanSummary'] ?? null) ? $data['scanSummary'] : ['scannedFiles' => 0, 'skippedFiles' => 0, 'warnings' => []];
$constraints = is_array($data['constraints'] ?? null) ? $data['constraints'] : [];
$detectedFonts = (array)($scanResults['detectedFonts'] ?? []);
$detectedInstallableFonts = array_values(array_filter($detectedFonts, static fn(array $font): bool => empty($font['installed'])));
$fontManagerConfig = [
    'fontStacks' => is_array($fontStacks) ? $fontStacks : [],
    'deleteModal' => [
        'title' => 'Schriftart löschen',
        'confirmText' => 'Löschen',
        'confirmClass' => 'btn-danger',
    ],
];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Themes &amp; Design</div>
                <h2 class="page-title">Font Manager</h2>
                <div class="text-secondary mt-1">Schriften prüfen, Theme-Fonts scannen und lokal self-hosten</div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
<div class="container-xl">

    <?php if (!empty($alert)): ?>
        <?php $alertData = $alert; $alertMarginClass = 'mb-3'; require __DIR__ . '/../partials/flash-alert.php'; ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-lg-8">
            <div class="card mb-3">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <div>
                        <h3 class="card-title mb-1">Schritt 1 · Theme-Fonts scannen</h3>
                        <div class="text-muted small">Aktives Theme: <code><?php echo htmlspecialchars($activeThemeSlug); ?></code></div>
                    </div>
                    <div class="d-flex gap-2">
                        <?php if ($detectedInstallableFonts !== []): ?>
                            <form method="post" data-font-manager-form="download-detected-fonts">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="action" value="download_detected_fonts">
                                <button type="submit" class="btn btn-success btn-sm" data-pending-text="Lädt lokal …">Alle lokal laden</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" data-font-manager-form="scan-theme-fonts">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" name="action" value="scan_theme_fonts">
                            <button type="submit" class="btn btn-primary btn-sm" data-pending-text="Scan läuft …">Scan starten</button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <?php
                    $alertData = [
                        'type' => $useLocalFonts ? 'success' : 'warning',
                        'message' => $useLocalFonts
                            ? 'Lokale Fonts sind für das Frontend aktiv. Externe Google-Font-Requests sollten damit unterdrückt werden.'
                            : 'Lokale Fonts sind derzeit nicht fürs Frontend aktiviert. Solange der Schalter unten nicht gesetzt ist, bleibt der Google-Fonts-Fallback aktiv.',
                    ];
                    $alertDismissible = false;
                    $alertMarginClass = 'mb-3';
                    require __DIR__ . '/../partials/flash-alert.php';
                    ?>

                    <p class="text-muted">Der Scan durchsucht das aktive Theme nach Google-Font-Imports und bekannten Schriftfamilien, damit du genutzte Fonts lokal self-hosten kannst.</p>
                    <?php
                    $scanConstraintDetails = array_values(array_filter([
                        !empty($constraints['download_total_byte_limit_label']) ? 'Remote-Gesamtlimit pro Import: ' . (string) $constraints['download_total_byte_limit_label'] : '',
                        !empty($constraints['download_remote_file_byte_limit_label']) ? 'Einzeldatei-Limit: ' . (string) $constraints['download_remote_file_byte_limit_label'] : '',
                        !empty($constraints['scan_file_size_limit_label']) ? 'Scan-Dateilimit: ' . (string) $constraints['scan_file_size_limit_label'] : '',
                        !empty($constraints['scan_total_byte_limit_label']) ? 'Scan-Gesamtlimit: ' . (string) $constraints['scan_total_byte_limit_label'] : '',
                        !empty($constraints['allowed_remote_hosts_label']) ? 'Erlaubte Hosts: ' . (string) $constraints['allowed_remote_hosts_label'] : '',
                        !empty($constraints['scan_allowed_extensions_label']) ? 'Scan-Endungen: ' . (string) $constraints['scan_allowed_extensions_label'] : '',
                        !empty($constraints['scan_skipped_segments_label']) ? 'Übersprungene Segmente: ' . (string) $constraints['scan_skipped_segments_label'] : '',
                    ]));
                    ?>
                    <?php if ($scanConstraintDetails !== []): ?>
                        <?php
                        $alertData = [
                            'type' => 'info',
                            'message' => 'Theme-Scan und Remote-Downloads laufen mit festen Schutzgrenzen.',
                            'details' => $scanConstraintDetails,
                        ];
                        $alertDismissible = false;
                        $alertMarginClass = 'mb-3';
                        require __DIR__ . '/../partials/flash-alert.php';
                        ?>
                    <?php endif; ?>
                    <div class="small text-muted mb-3">
                        <?php echo (int)($scanSummary['scannedFiles'] ?? 0); ?> Dateien geprüft
                        <?php if (!empty($scanSummary['skippedFiles'])): ?>
                            · <?php echo (int)($scanSummary['skippedFiles'] ?? 0); ?> übersprungen
                        <?php endif; ?>
                        <?php if (!empty($constraints['scan_file_limit'])): ?>
                            · Limit <?php echo (int)($constraints['scan_file_limit'] ?? 0); ?> Dateien
                        <?php endif; ?>
                        <?php if (!empty($scanSummary['source'])): ?>
                            · Quelle <?php echo htmlspecialchars((string)($scanSummary['source'] ?? 'live')); ?>
                        <?php endif; ?>
                        <?php if (!empty($scanSummary['generatedAt'])): ?>
                            · Stand <?php echo htmlspecialchars((string)($scanSummary['generatedAt'] ?? '')); ?>
                        <?php endif; ?>
                        <?php if (!empty($constraints['download_remote_file_limit'])): ?>
                            · Download-Limit <?php echo (int)($constraints['download_remote_file_limit'] ?? 0); ?> Dateien
                        <?php endif; ?>
                        <?php if (!empty($constraints['scan_total_byte_limit_label'])): ?>
                            · Gesamt <?php echo htmlspecialchars((string)($constraints['scan_total_byte_limit_label'] ?? '')); ?>
                        <?php endif; ?>
                    </div>
                    <?php if (!empty($scanSummary['warnings']) && is_array($scanSummary['warnings'])): ?>
                        <?php
                        $alertData = [
                            'type' => 'warning',
                            'message' => 'Der Theme-Scan wurde mit Schutzgrenzen ausgeführt.',
                            'details' => array_values(array_map(static fn(mixed $warning): string => (string) $warning, $scanSummary['warnings'])),
                        ];
                        $alertDismissible = false;
                        $alertMarginClass = 'mb-3';
                        require __DIR__ . '/../partials/flash-alert.php';
                        ?>
                    <?php endif; ?>
                    <?php if ($detectedInstallableFonts !== []): ?>
                        <?php
                        $alertData = [
                            'type' => 'info',
                            'message' => count($detectedInstallableFonts) . ' erkannte Schrift' . (count($detectedInstallableFonts) === 1 ? '' : 'en') . ' sind noch extern eingebunden und können gesammelt lokal gespeichert werden.',
                        ];
                        $alertDismissible = false;
                        $alertMarginClass = 'mb-3';
                        require __DIR__ . '/../partials/flash-alert.php';
                        ?>
                    <?php endif; ?>

                    <?php if ($detectedFonts !== []): ?>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>Schrift</th>
                                        <th>Typ</th>
                                        <th>Status</th>
                                        <th>Gefunden in</th>
                                        <th class="w-1">Aktion</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($detectedFonts as $font): ?>
                                        <tr>
                                            <td>
                                                <div class="fw-semibold"><?php echo htmlspecialchars($font['name'] ?? ''); ?></div>
                                                <?php if (!empty($font['reason'])): ?>
                                                    <div class="text-muted small"><?php echo htmlspecialchars($font['reason']); ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td><span class="badge bg-azure-lt"><?php echo htmlspecialchars($font['style'] ?? 'Font'); ?></span></td>
                                            <td>
                                                <?php if (!empty($font['installed'])): ?>
                                                    <span class="badge bg-green">Lokal vorhanden</span>
                                                <?php else: ?>
                                                    <span class="badge bg-orange">Extern / nicht lokal</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php foreach (($font['sources'] ?? []) as $source): ?>
                                                    <div class="small"><code><?php echo htmlspecialchars($source['file'] ?? ''); ?></code> <span class="text-muted">· <?php echo htmlspecialchars($source['type'] ?? 'Quelle'); ?></span></div>
                                                <?php endforeach; ?>
                                            </td>
                                            <td>
                                                <?php if (empty($font['installed'])): ?>
                                                    <form method="post" data-font-manager-form="download-detected-single-font">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                        <input type="hidden" name="action" value="download_google_font">
                                                        <input type="hidden" name="google_font_family" value="<?php echo htmlspecialchars($font['name'] ?? ''); ?>">
                                                        <button type="submit" class="btn btn-outline-primary btn-sm" data-pending-text="Lädt …">Self-Host</button>
                                                    </form>
                                                <?php else: ?>
                                                    <span class="text-muted small">Bereits lokal</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <?php
                        $alertData = [
                            'type' => 'secondary',
                            'message' => 'Noch keine bekannten externen Theme-Schriften erkannt. Starte den Scan erneut, falls du gerade Fonts im Theme geändert hast.',
                        ];
                        $alertDismissible = false;
                        $alertMarginClass = 'mb-0';
                        require __DIR__ . '/../partials/flash-alert.php';
                        ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Schritt 2 · Empfohlene Schriftbibliothek</h3>
                </div>
                <div class="card-body">
                    <?php
                    $alertData = [
                        'type' => 'success',
                        'message' => 'Self-Hosting statt CDN: Alle Downloads werden lokal in /uploads/fonts abgelegt, damit Themes keine externen Font-CDNs mehr brauchen.',
                    ];
                    $alertDismissible = false;
                    $alertMarginClass = 'mb-3';
                    require __DIR__ . '/../partials/flash-alert.php';
                    ?>

                    <?php foreach ($fontCatalog as $category => $fonts): ?>
                        <div class="mb-4">
                            <h4 class="mb-3"><?php echo htmlspecialchars($category); ?></h4>
                            <div class="row row-cards">
                                <?php foreach ($fonts as $font): ?>
                                    <div class="col-md-6">
                                        <div class="card card-sm mb-2">
                                            <div class="card-body">
                                                <div class="d-flex align-items-start justify-content-between gap-3">
                                                    <div>
                                                        <div class="fw-semibold"><?php echo htmlspecialchars($font['name']); ?></div>
                                                        <div class="text-muted small mb-1"><?php echo htmlspecialchars($font['style']); ?></div>
                                                        <div class="small"><?php echo htmlspecialchars($font['reason']); ?></div>
                                                    </div>
                                                    <div class="text-end">
                                                        <?php if (!empty($font['installed'])): ?>
                                                            <span class="badge bg-green mb-2">Lokal</span>
                                                        <?php else: ?>
                                                            <form method="post" data-font-manager-form="download-library-font">
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                                <input type="hidden" name="action" value="download_google_font">
                                                                <input type="hidden" name="google_font_family" value="<?php echo htmlspecialchars($font['name']); ?>">
                                                                <button type="submit" class="btn btn-outline-primary btn-sm" data-pending-text="Lädt …">Self-Host</button>
                                                            </form>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <?php if (!empty($customFontRows)): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Lokale Schriftarten</h3>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Format</th>
                                    <th>Quelle</th>
                                    <th>Datei</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customFontRows as $font): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-semibold"><?php echo htmlspecialchars((string) ($font['name'] ?? '')); ?></div>
                                            <?php if (!empty($font['file_size_label'])): ?>
                                                <div class="text-muted small"><?php echo htmlspecialchars((string) ($font['file_size_label'] ?? '')); ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-azure"><?php echo htmlspecialchars((string) ($font['format'] ?? '')); ?></span></td>
                                        <td>
                                            <?php if (($font['source'] ?? '') === 'google-fonts-local'): ?>
                                                <span class="badge bg-green">Google (lokal)</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Manuell</span>
                                            <?php endif; ?>
                                            <?php if (($font['asset_status'] ?? '') === 'warning'): ?>
                                                <span class="badge bg-warning-lt">Assets prüfen</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted small">
                                            <div><?php echo htmlspecialchars((string) ($font['file_path'] ?? '')); ?></div>
                                            <?php if (!empty($font['css_path'])): ?>
                                                <div>CSS: <?php echo htmlspecialchars((string) ($font['css_path'] ?? '')); ?></div>
                                            <?php endif; ?>
                                            <div>
                                                Datei <?php echo !empty($font['file_exists']) ? 'vorhanden' : 'fehlt'; ?>
                                                <?php if (($font['css_path'] ?? '') !== ''): ?>
                                                    · CSS <?php echo !empty($font['css_exists']) ? 'vorhanden' : 'fehlt'; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                        <td>
                                            <form method="post" class="d-inline" data-font-manager-form="delete-font">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="action" value="delete_font">
                                                <input type="hidden" name="font_id" value="<?php echo (int)($font['id'] ?? 0); ?>">
                                                <button type="button" class="btn btn-outline-danger btn-sm js-font-delete" data-font-name="<?php echo htmlspecialchars((string) ($font['name'] ?? ''), ENT_QUOTES); ?>" data-pending-text="Löscht …">Löschen</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" class="card mb-3" data-font-manager-form="save-assignments">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="save">
                <div class="card-header">
                    <h3 class="card-title">Schritt 3 · Typografie-Zuordnung</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <label class="form-label">Überschriften-Schrift</label>
                            <select name="heading_font" class="form-select" id="headingFontSelect">
                                <?php foreach ($systemFonts as $key => $label): ?>
                                    <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $headingFont === $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Text-Schrift</label>
                            <select name="body_font" class="form-select" id="bodyFontSelect">
                                <?php foreach ($systemFonts as $key => $label): ?>
                                    <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $bodyFont === $key ? 'selected' : ''; ?>><?php echo htmlspecialchars($label); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Basis-Schriftgröße (px)</label>
                                <input type="number" name="font_size" class="form-control" value="<?php echo (int)$fontSize; ?>" min="<?php echo (int)($constraints['font_size_min'] ?? 12); ?>" max="<?php echo (int)($constraints['font_size_max'] ?? 24); ?>" step="1" inputmode="numeric">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Zeilenhöhe</label>
                            <input type="number" name="line_height" class="form-control" value="<?php echo htmlspecialchars($lineHeight); ?>" min="<?php echo htmlspecialchars((string)($constraints['line_height_min'] ?? '1.0')); ?>" max="<?php echo htmlspecialchars((string)($constraints['line_height_max'] ?? '2.5')); ?>" step="0.1" inputmode="decimal">
                        </div>
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" role="switch" id="useLocalFontsSwitch" name="use_local_fonts" value="1" <?php echo $useLocalFonts ? 'checked' : ''; ?>>
                        <label class="form-check-label" for="useLocalFontsSwitch">
                            Lokale On-Prem-Fonts im Frontend aktivieren
                        </label>
                        <div class="form-hint">Schaltet Theme und Core auf lokal gespeicherte Schrift-CSS um und unterdrückt Google-Fonts-Requests im Frontend.</div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary" data-pending-text="Speichert …">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-device-floppy" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2"/><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M14 4l0 4l-6 0l0 -4"/></svg>
                        Zuweisung speichern
                    </button>
                </div>
            </form>

            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Direktdownload einer Google Font</h3>
                </div>
                <div class="card-body">
                    <form method="post" data-font-manager-form="direct-download-font">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="download_google_font">
                        <div class="row g-2 align-items-end">
                            <div class="col">
                                <label class="form-label">Google Font Name</label>
                                <input type="text" name="google_font_family" class="form-control" placeholder="z.B. Inter, Roboto, Open Sans" required maxlength="<?php echo (int)($constraints['google_font_family_max_length'] ?? 120); ?>" pattern="[a-zA-Z0-9 ]+" title="Nur Buchstaben, Zahlen und Leerzeichen" autocomplete="off" spellcheck="false">
                                <small class="form-hint">Exakter Name von <a href="https://fonts.google.com" target="_blank" rel="noopener noreferrer">fonts.google.com</a>; pro Import maximal <?php echo (int)($constraints['download_remote_file_limit'] ?? 20); ?> Font-Dateien, je Datei bis <?php echo htmlspecialchars((string)($constraints['download_remote_file_byte_limit_label'] ?? '5,0 MB')); ?>, ausschließlich von <?php echo htmlspecialchars((string)($constraints['allowed_remote_hosts_label'] ?? 'fonts.googleapis.com, fonts.gstatic.com')); ?>.</small>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-outline-primary" data-pending-text="Lädt …">Herunterladen</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card sticky-top" style="top: 1rem;">
                <div class="card-header">
                    <h3 class="card-title">Vorschau</h3>
                </div>
                <div class="card-body" id="fontPreview">
                    <h2 class="mb-2" id="previewHeading">Überschrift Beispiel</h2>
                    <h4 class="mb-3" id="previewSubheading">Unterüberschrift</h4>
                    <p id="previewBody">Dies ist ein Beispieltext, um die ausgewählte Schriftart zu zeigen. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                    <p id="previewSmall" class="text-muted small">Kleinerer Text für Beschreibungen und Meta-Informationen.</p>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script type="application/json" id="font-manager-config">
<?php echo json_encode($fontManagerConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>
</script>

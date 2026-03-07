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
$headingFont  = $data['headingFont'] ?? 'system-ui';
$bodyFont     = $data['bodyFont'] ?? 'system-ui';
$fontSize     = $data['fontSize'] ?? '16';
$lineHeight   = $data['lineHeight'] ?? '1.6';
$scanResults  = $data['scanResults'] ?? ['theme' => '', 'scannedFiles' => 0, 'detectedFonts' => []];
$fontCatalog  = $data['fontCatalog'] ?? [];
$activeThemeSlug = $data['activeThemeSlug'] ?? '';
$detectedFonts = (array)($scanResults['detectedFonts'] ?? []);
$detectedInstallableFonts = array_values(array_filter($detectedFonts, static fn(array $font): bool => empty($font['installed'])));
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

    <?php if ($alert): ?>
        <div class="alert alert-<?php echo htmlspecialchars($alert['type']); ?> alert-dismissible" role="alert">
            <?php echo htmlspecialchars($alert['message']); ?>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="Close"></a>
        </div>
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
                            <form method="post">
                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                <input type="hidden" name="action" value="download_detected_fonts">
                                <button type="submit" class="btn btn-success btn-sm">Alle lokal laden</button>
                            </form>
                        <?php endif; ?>
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" name="action" value="scan_theme_fonts">
                            <button type="submit" class="btn btn-primary btn-sm">Scan starten</button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    <p class="text-muted">Der Scan durchsucht das aktive Theme nach Google-Font-Imports und bekannten Schriftfamilien, damit du genutzte Fonts lokal self-hosten kannst.</p>
                    <div class="small text-muted mb-3"><?php echo (int)($scanResults['scannedFiles'] ?? 0); ?> Dateien geprüft</div>
                    <?php if ($detectedInstallableFonts !== []): ?>
                        <div class="alert alert-info">
                            <?php echo count($detectedInstallableFonts); ?> erkannte Schrift<?php echo count($detectedInstallableFonts) === 1 ? '' : 'en'; ?> sind noch extern eingebunden und können gesammelt lokal gespeichert werden.
                        </div>
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
                                                    <form method="post">
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                        <input type="hidden" name="action" value="download_google_font">
                                                        <input type="hidden" name="google_font_family" value="<?php echo htmlspecialchars($font['name'] ?? ''); ?>">
                                                        <button type="submit" class="btn btn-outline-primary btn-sm">Self-Host</button>
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
                        <div class="alert alert-secondary mb-0">Noch keine bekannten externen Theme-Schriften erkannt. Starte den Scan erneut, falls du gerade Fonts im Theme geändert hast.</div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header">
                    <h3 class="card-title">Schritt 2 · Empfohlene Schriftbibliothek</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-success mb-3">
                        <div class="d-flex">
                            <div>
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-shield-check alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M11.46 20.846a12 12 0 0 1 -7.96 -14.846a12 12 0 0 0 8.5 -3a12 12 0 0 0 8.5 3a12 12 0 0 1 -.09 7.06"/><path d="M15 19l2 2l4 -4"/></svg>
                            </div>
                            <div>
                                <h4 class="alert-title">Self-Hosting statt CDN</h4>
                                <div class="text-secondary">Alle Downloads werden lokal in <code>/uploads/fonts</code> abgelegt, damit Themes keine externen Font-CDNs mehr brauchen.</div>
                            </div>
                        </div>
                    </div>

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
                                                            <form method="post">
                                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                                <input type="hidden" name="action" value="download_google_font">
                                                                <input type="hidden" name="google_font_family" value="<?php echo htmlspecialchars($font['name']); ?>">
                                                                <button type="submit" class="btn btn-outline-primary btn-sm">Self-Host</button>
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

            <?php if (!empty($customFonts)): ?>
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
                                <?php foreach ($customFonts as $font): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($font->name ?? ''); ?></td>
                                        <td><span class="badge bg-azure"><?php echo htmlspecialchars($font->format ?? ''); ?></span></td>
                                        <td>
                                            <?php if (($font->source ?? '') === 'google-fonts-local'): ?>
                                                <span class="badge bg-green">Google (lokal)</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Manuell</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-muted small"><?php echo htmlspecialchars($font->file_path ?? ''); ?></td>
                                        <td>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                <input type="hidden" name="action" value="delete_font">
                                                <input type="hidden" name="font_id" value="<?php echo (int)($font->id ?? 0); ?>">
                                                <button type="button" class="btn btn-outline-danger btn-sm btn-delete-font" data-name="<?php echo htmlspecialchars($font->name ?? ''); ?>">Löschen</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            <?php endif; ?>

            <form method="post" class="card mb-3">
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
                            <input type="number" name="font_size" class="form-control" value="<?php echo (int)$fontSize; ?>" min="12" max="24" step="1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Zeilenhöhe</label>
                            <input type="number" name="line_height" class="form-control" value="<?php echo htmlspecialchars($lineHeight); ?>" min="1.0" max="2.5" step="0.1">
                        </div>
                    </div>
                </div>
                <div class="card-footer text-end">
                    <button type="submit" class="btn btn-primary">
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
                    <form method="post">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="download_google_font">
                        <div class="row g-2 align-items-end">
                            <div class="col">
                                <label class="form-label">Google Font Name</label>
                                <input type="text" name="google_font_family" class="form-control" placeholder="z.B. Inter, Roboto, Open Sans" required pattern="[a-zA-Z0-9 ]+" title="Nur Buchstaben, Zahlen und Leerzeichen">
                                <small class="form-hint">Exakter Name von <a href="https://fonts.google.com" target="_blank" rel="noopener noreferrer">fonts.google.com</a></small>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-outline-primary">Herunterladen</button>
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
                    <h2 class="mb-2" id="previewHeading" style="font-family: var(--font-heading);">Überschrift Beispiel</h2>
                    <h4 class="mb-3" id="previewSubheading" style="font-family: var(--font-heading);">Unterüberschrift</h4>
                    <p id="previewBody" style="font-family: var(--font-body);">Dies ist ein Beispieltext, um die ausgewählte Schriftart zu zeigen. Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.</p>
                    <p id="previewSmall" class="text-muted small" style="font-family: var(--font-body);">Kleinerer Text für Beschreibungen und Meta-Informationen.</p>
                </div>
            </div>
        </div>
    </div>
</div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var fontStacks = <?php echo json_encode($fontStacks); ?>;

    var headingSelect = document.getElementById('headingFontSelect');
    var bodySelect    = document.getElementById('bodyFontSelect');
    var previewH      = document.getElementById('previewHeading');
    var previewSH     = document.getElementById('previewSubheading');
    var previewB      = document.getElementById('previewBody');
    var previewS      = document.getElementById('previewSmall');

    function updatePreview() {
        var hStack = fontStacks[headingSelect.value] || 'sans-serif';
        var bStack = fontStacks[bodySelect.value] || 'sans-serif';
        if (previewH) previewH.style.fontFamily = hStack;
        if (previewSH) previewSH.style.fontFamily = hStack;
        if (previewB) previewB.style.fontFamily = bStack;
        if (previewS) previewS.style.fontFamily = bStack;
    }

    if (headingSelect) headingSelect.addEventListener('change', updatePreview);
    if (bodySelect) bodySelect.addEventListener('change', updatePreview);
    updatePreview();

    document.querySelectorAll('.btn-delete-font').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var name = this.dataset.name;
            var form = this.closest('form');
            cmsConfirm({
                title: 'Schriftart löschen',
                message: 'Soll "' + name + '" wirklich gelöscht werden?',
                confirmText: 'Löschen',
                confirmClass: 'btn-danger',
                onConfirm: function() { form.submit(); }
            });
        });
    });
});
</script>

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
?>

<div class="container-xl">
    <!-- Header -->
    <div class="page-header d-flex align-items-center mb-4">
        <div>
            <h2 class="page-title">Font Manager</h2>
            <div class="text-muted mt-1">Schriftarten und Typografie-Einstellungen</div>
        </div>
    </div>

    <?php if ($alert): ?>
        <div class="alert alert-<?php echo htmlspecialchars($alert['type']); ?> alert-dismissible" role="alert">
            <?php echo htmlspecialchars($alert['message']); ?>
            <a class="btn-close" data-bs-dismiss="alert" aria-label="Close"></a>
        </div>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="save">

        <div class="row">
            <!-- Settings -->
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">Schriftarten</h3>
                    </div>
                    <div class="card-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label">Überschriften-Schrift</label>
                                <select name="heading_font" class="form-select" id="headingFontSelect">
                                    <?php foreach ($systemFonts as $key => $label): ?>
                                        <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $headingFont === $key ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Text-Schrift</label>
                                <select name="body_font" class="form-select" id="bodyFontSelect">
                                    <?php foreach ($systemFonts as $key => $label): ?>
                                        <option value="<?php echo htmlspecialchars($key); ?>" <?php echo $bodyFont === $key ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($label); ?>
                                        </option>
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
                            Speichern
                        </button>
                    </div>
                </div>

                <!-- Custom Fonts -->
                <?php if (!empty($customFonts)): ?>
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Benutzerdefinierte Schriftarten</h3>
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
                                                    <button type="button" class="btn btn-outline-danger btn-sm btn-delete-font" data-name="<?php echo htmlspecialchars($font->name ?? ''); ?>">
                                                        Löschen
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Google Fonts DSGVO Download -->
                <div class="card mb-3">
                    <div class="card-header">
                        <h3 class="card-title">
                            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-download me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/><path d="M7 11l5 5l5 -5"/><path d="M12 4l0 12"/></svg>
                            Google Font herunterladen
                        </h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-success mb-3">
                            <div class="d-flex">
                                <div>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-shield-check alert-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M11.46 20.846a12 12 0 0 1 -7.96 -14.846a12 12 0 0 0 8.5 -3a12 12 0 0 0 8.5 3a12 12 0 0 1 -.09 7.06"/><path d="M15 19l2 2l4 -4"/></svg>
                                </div>
                                <div>
                                    <h4 class="alert-title">DSGVO-konform</h4>
                                    <div class="text-secondary">Schriften werden lokal auf dem Server gespeichert. Besucher laden keine Daten von Google-Servern – kein externer CDN-Aufruf.</div>
                                </div>
                            </div>
                        </div>
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
                                    <button type="submit" class="btn btn-primary">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-download me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"/><path d="M7 11l5 5l5 -5"/><path d="M12 4l0 12"/></svg>
                                        Herunterladen
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Preview -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Vorschau</h3>
                    </div>
                    <div class="card-body" id="fontPreview">
                        <h2 class="mb-2" id="previewHeading" style="font-family: var(--font-heading);">Überschrift Beispiel</h2>
                        <h4 class="mb-3" id="previewSubheading" style="font-family: var(--font-heading);">Unterüberschrift</h4>
                        <p id="previewBody" style="font-family: var(--font-body);">
                            Dies ist ein Beispieltext, um die ausgewählte Schriftart zu zeigen.
                            Lorem ipsum dolor sit amet, consectetur adipiscing elit.
                            Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua.
                        </p>
                        <p id="previewSmall" class="text-muted small" style="font-family: var(--font-body);">
                            Kleinerer Text für Beschreibungen und Meta-Informationen.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </form>
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

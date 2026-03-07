<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * @var array $data Design-Einstellungen-Daten
 * @var string $csrfToken CSRF-Token
 */

$colors      = $data['colors'];
$layout      = $data['layout'];
$header      = $data['header'];
$footer      = $data['footer'];
$performance = $data['performance'];
$customCss   = $data['custom_css'];
?>

<div class="container-xl">
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center">
            <div class="col-auto">
                <h2 class="page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-palette me-2" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 21a9 9 0 0 1 0 -18c4.97 0 9 3.582 9 8c0 1.06 -.474 2.078 -1.318 2.828c-.844 .75 -1.989 1.172 -3.182 1.172h-2.5a2 2 0 0 0 -1 3.75a1.3 1.3 0 0 1 -1 2.25" /><path d="M8.5 10.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M12.5 7.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /><path d="M16.5 10.5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0" /></svg>
                    Design-Einstellungen
                </h2>
            </div>
        </div>
    </div>

    <form method="post" autocomplete="off">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="save">

        <div class="row">
            <!-- Farben -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Farbschema</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label">Primärfarbe</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="color_primary" value="<?php echo htmlspecialchars($colors['primary']); ?>">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($colors['primary']); ?>" data-color-text="color_primary" readonly>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Sekundärfarbe</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="color_secondary" value="<?php echo htmlspecialchars($colors['secondary']); ?>">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($colors['secondary']); ?>" data-color-text="color_secondary" readonly>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Akzentfarbe</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="color_accent" value="<?php echo htmlspecialchars($colors['accent']); ?>">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($colors['accent']); ?>" data-color-text="color_accent" readonly>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Textfarbe</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="color_text" value="<?php echo htmlspecialchars($colors['text']); ?>">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($colors['text']); ?>" data-color-text="color_text" readonly>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Hintergrund (Hell)</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="color_bg" value="<?php echo htmlspecialchars($colors['bg']); ?>">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($colors['bg']); ?>" data-color-text="color_bg" readonly>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Hintergrund (Dunkel)</label>
                                <div class="input-group">
                                    <input type="color" class="form-control form-control-color" name="color_bg_dark" value="<?php echo htmlspecialchars($colors['bg_dark']); ?>">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($colors['bg_dark']); ?>" data-color-text="color_bg_dark" readonly>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Layout -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Layout</h3>
                    </div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-sm-6">
                                <label class="form-label">Container-Breite (px)</label>
                                <input type="number" name="container_width" class="form-control" value="<?php echo (int)$layout['container_width']; ?>" min="960" max="1920" step="10">
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label">Border Radius (px)</label>
                                <input type="number" name="border_radius" class="form-control" value="<?php echo (int)$layout['border_radius']; ?>" min="0" max="32">
                            </div>
                            <div class="col-12">
                                <label class="form-label">Sidebar-Position</label>
                                <div class="form-selectgroup">
                                    <?php foreach (['left' => 'Links', 'right' => 'Rechts', 'none' => 'Keine'] as $val => $label): ?>
                                        <label class="form-selectgroup-item">
                                            <input type="radio" name="sidebar_position" value="<?php echo $val; ?>" class="form-selectgroup-input" <?php echo $layout['sidebar_position'] === $val ? 'checked' : ''; ?>>
                                            <span class="form-selectgroup-label"><?php echo $label; ?></span>
                                        </label>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Header -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Header</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" name="header_sticky" value="1" <?php echo $header['sticky'] ? 'checked' : ''; ?>>
                                <span class="form-check-label">Sticky Header</span>
                            </label>
                        </div>
                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" name="header_transparent" value="1" <?php echo $header['transparent'] ? 'checked' : ''; ?>>
                                <span class="form-check-label">Transparenter Header</span>
                            </label>
                        </div>
                        <div>
                            <label class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" name="header_search" value="1" <?php echo $header['search'] ? 'checked' : ''; ?>>
                                <span class="form-check-label">Suchfeld im Header</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Footer</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-label">Spaltenanzahl</label>
                            <select name="footer_columns" class="form-select">
                                <?php for ($i = 1; $i <= 6; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo (int)$footer['columns'] === $i ? 'selected' : ''; ?>><?php echo $i; ?> Spalte<?php echo $i > 1 ? 'n' : ''; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                        <div>
                            <label class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" name="footer_dark" value="1" <?php echo $footer['dark'] ? 'checked' : ''; ?>>
                                <span class="form-check-label">Dunkler Footer</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Performance -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Performance</h3>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" name="lazy_loading" value="1" <?php echo $performance['lazy_loading'] ? 'checked' : ''; ?>>
                                <span class="form-check-label">Lazy Loading für Bilder</span>
                            </label>
                        </div>
                        <div class="mb-3">
                            <label class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" name="minify_css" value="1" <?php echo $performance['minify_css'] ? 'checked' : ''; ?>>
                                <span class="form-check-label">CSS minifizieren</span>
                            </label>
                        </div>
                        <div>
                            <label class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" name="minify_js" value="1" <?php echo $performance['minify_js'] ? 'checked' : ''; ?>>
                                <span class="form-check-label">JavaScript minifizieren</span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Custom CSS -->
            <div class="col-lg-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Eigenes CSS</h3>
                    </div>
                    <div class="card-body">
                        <textarea name="custom_css" class="form-control" rows="8" style="font-family: 'SFMono-Regular', Menlo, Monaco, Consolas, monospace; font-size: 13px;" placeholder="/* Eigenes CSS hier einfügen */"><?php echo htmlspecialchars($customCss); ?></textarea>
                        <small class="form-hint mt-2">CSS wird nach allen Theme-Styles geladen und überschreibt vorhandene Regeln.</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vorschau -->
        <div class="card mb-4">
            <div class="card-header">
                <h3 class="card-title">Vorschau</h3>
            </div>
            <div class="card-body">
                <div id="design-preview" class="p-4 rounded" style="border: 1px solid var(--tblr-border-color);">
                    <div class="d-flex gap-3 mb-3">
                        <div class="rounded p-3 text-white text-center" id="preview-primary" style="background: <?php echo htmlspecialchars($colors['primary']); ?>; min-width: 100px;">Primär</div>
                        <div class="rounded p-3 text-white text-center" id="preview-secondary" style="background: <?php echo htmlspecialchars($colors['secondary']); ?>; min-width: 100px;">Sekundär</div>
                        <div class="rounded p-3 text-white text-center" id="preview-accent" style="background: <?php echo htmlspecialchars($colors['accent']); ?>; min-width: 100px;">Akzent</div>
                    </div>
                    <p id="preview-text" style="color: <?php echo htmlspecialchars($colors['text']); ?>;">Beispieltext in der gewählten Textfarbe.</p>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end">
            <button type="submit" class="btn btn-primary">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-device-floppy me-1" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2" /><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0" /><path d="M14 4l0 4l-6 0l0 -4" /></svg>
                Einstellungen speichern
            </button>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('input[type="color"]').forEach(function(input) {
        input.addEventListener('input', function() {
            var textInput = this.nextElementSibling;
            if (textInput) textInput.value = this.value;
            updatePreview();
        });
    });

    function updatePreview() {
        var primary   = document.querySelector('input[name="color_primary"]').value;
        var secondary = document.querySelector('input[name="color_secondary"]').value;
        var accent    = document.querySelector('input[name="color_accent"]').value;
        var text      = document.querySelector('input[name="color_text"]').value;

        document.getElementById('preview-primary').style.background   = primary;
        document.getElementById('preview-secondary').style.background = secondary;
        document.getElementById('preview-accent').style.background    = accent;
        document.getElementById('preview-text').style.color           = text;
    }
});
</script>

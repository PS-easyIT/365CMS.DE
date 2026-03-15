<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$settings = $data['settings'] ?? [];
$styleOptions = $data['styleOptions'] ?? [];
$stats = $data['stats'] ?? [];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/site-tables" class="btn btn-ghost-secondary btn-sm me-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6"/></svg>
                    Zurück
                </a>
            </div>
            <div class="col">
                <div class="page-pretitle">Seiten &amp; Beiträge</div>
                <h2 class="page-title">Tabellen-Einstellungen</h2>
            </div>
            <div class="col-auto">
                <a href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/site-tables?action=edit" class="btn btn-primary btn-sm">Neue Tabelle</a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php
        $alertData = $alert ?? [];
        $alertDismissible = true;
        $alertMarginClass = 'mb-3';
        require __DIR__ . '/../partials/flash-alert.php';
        ?>

        <form method="post" action="<?php echo htmlspecialchars(SITE_URL); ?>/admin/site-tables">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="save_settings">

            <div class="row row-deck row-cards mb-4">
                <div class="col-sm-6 col-lg-4">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="h1 mb-1"><?php echo (int)($stats['table_count'] ?? 0); ?></div>
                            <div class="text-secondary">Aktive Content-Tabellen</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="h1 mb-1"><?php echo (int)($stats['enabled_style_count'] ?? 0); ?>/4</div>
                            <div class="text-secondary">Freigeschaltete Styles</div>
                        </div>
                    </div>
                </div>
                <div class="col-sm-6 col-lg-4">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="h1 mb-1"><?php echo htmlspecialchars((string)($styleOptions[$settings['default_style'] ?? '']['label'] ?? 'Standard')); ?></div>
                            <div class="text-secondary">Standard-Style</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-8">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Anzeige &amp; Meta-Infos</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <?php
                                $toggleLabels = [
                                    'show_meta_panel' => 'Meta-Bereich oberhalb der Tabelle anzeigen',
                                    'show_table_name' => 'Tabellenname im Frontend anzeigen',
                                    'show_description' => 'Beschreibung / Meta-Text anzeigen',
                                    'show_export_links' => 'Export-Links anzeigen',
                                    'show_caption' => 'Tabellenunterschrift (Caption) anzeigen',
                                    'responsive_default' => 'Responsive Tabellen standardmäßig aktivieren',
                                ];
                                foreach ($toggleLabels as $key => $label): ?>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" name="<?php echo htmlspecialchars($key); ?>" value="1" <?php echo !empty($settings[$key]) ? 'checked' : ''; ?>>
                                            <span class="form-check-label"><?php echo htmlspecialchars($label); ?></span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Verfügbare Styles</h3>
                        </div>
                        <div class="card-body">
                            <p class="text-secondary mb-3">Hier legst du fest, welche der maximal vier Tabellen-Stile im Editor auswählbar sind und welcher Stil als Standard verwendet wird.</p>
                            <div class="row g-3">
                                <?php foreach ($styleOptions as $styleKey => $style): ?>
                                    <div class="col-md-6">
                                        <label class="form-check border rounded p-3 h-100">
                                            <input class="form-check-input mt-1" type="checkbox" name="enabled_styles[]" value="<?php echo htmlspecialchars((string)$styleKey); ?>" <?php echo in_array($styleKey, $settings['enabled_styles'] ?? [], true) ? 'checked' : ''; ?>>
                                            <span class="form-check-label d-block ms-2">
                                                <span class="fw-semibold d-block"><?php echo htmlspecialchars((string)($style['label'] ?? $styleKey)); ?></span>
                                                <span class="text-secondary small d-block mt-1"><?php echo htmlspecialchars((string)($style['description'] ?? '')); ?></span>
                                            </span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Standard-Style</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label" for="default_style">Style für neue Tabellen</label>
                                <select class="form-select" id="default_style" name="default_style">
                                    <?php foreach ($styleOptions as $styleKey => $style): ?>
                                        <option value="<?php echo htmlspecialchars((string)$styleKey); ?>" <?php echo ($settings['default_style'] ?? '') === $styleKey ? 'selected' : ''; ?>><?php echo htmlspecialchars((string)($style['label'] ?? $styleKey)); ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <span class="form-hint">Falls ein Tabellenstil deaktiviert wird, fällt die Ausgabe automatisch auf den gewählten Standard zurück.</span>
                            </div>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            <button type="submit" class="btn btn-primary w-100">Einstellungen speichern</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>
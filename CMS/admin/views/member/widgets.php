<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$settings = $data['settings'] ?? [];
$widgets  = $data['widgets'] ?? [];
$sectionOrderOptions = $data['sectionOrderOptions'] ?? [];
$customWidgets = $settings['custom_widgets'] ?? [];
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Mitglieder &amp; Zugriff</div>
                <h2 class="page-title">Member Dashboard – Widgets</h2>
                <div class="text-muted mt-1">Kern-Widgets auswählen, Dashboard-Layout strukturieren und eigene Infoboxen pflegen.</div>
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
            <input type="hidden" name="settings_section" value="widgets">

            <div class="row row-cards mb-4">
                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-header">
                            <h3 class="card-title">Kern-Widgets</h3>
                        </div>
                        <div class="card-body">
                            <div class="row row-cards">
                                <?php foreach ($widgets as $key => $widget): ?>
                                    <div class="col-md-6">
                                        <label class="card card-sm cursor-pointer h-100">
                                            <div class="card-body">
                                                <div class="form-check mb-2">
                                                    <input type="checkbox" class="form-check-input" name="widgets[<?php echo htmlspecialchars((string)$key); ?>]" value="1" <?php echo in_array($key, $settings['widgets'] ?? [], true) ? 'checked' : ''; ?>>
                                                    <span class="form-check-label fw-semibold"><?php echo htmlspecialchars((string)($widget['label'] ?? $key)); ?></span>
                                                </div>
                                                <div class="text-muted small mb-2"><?php echo htmlspecialchars((string)($widget['description'] ?? '')); ?></div>
                                                <?php if (!empty($widget['recommended'])): ?>
                                                    <span class="badge bg-green-lt text-green">Empfohlen</span>
                                                <?php endif; ?>
                                            </div>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h3 class="card-title">Layout</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label" for="dashboard_columns">Widget-Spalten</label>
                                <select id="dashboard_columns" name="dashboard_columns" class="form-select">
                                    <?php foreach ([1, 2, 3, 4] as $columns): ?>
                                        <option value="<?php echo $columns; ?>" <?php echo (int)($settings['dashboard_columns'] ?? 3) === $columns ? 'selected' : ''; ?>>
                                            <?php echo $columns; ?> Spalte<?php echo $columns === 1 ? '' : 'n'; ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div>
                                <label class="form-label" for="section_order">Bereichsreihenfolge</label>
                                <select id="section_order" name="section_order" class="form-select">
                                    <?php foreach ($sectionOrderOptions as $value => $label): ?>
                                        <option value="<?php echo htmlspecialchars((string)$value); ?>" <?php echo ($settings['section_order'] ?? '') === $value ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars((string)$label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h3 class="card-title">Eigene Info-Widgets</h3>
                </div>
                <div class="card-body">
                    <div class="row row-cards">
                        <?php for ($i = 1; $i <= 4; $i++):
                            $widget = $customWidgets[$i] ?? ['title' => '', 'icon' => '', 'content' => ''];
                        ?>
                            <div class="col-md-6 col-xl-3">
                                <div class="card card-sm h-100 border">
                                    <div class="card-header">
                                        <h4 class="card-title mb-0">Infobox <?php echo $i; ?></h4>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label class="form-label" for="custom_widget_<?php echo $i; ?>_title">Titel</label>
                                            <input id="custom_widget_<?php echo $i; ?>_title" type="text" class="form-control" name="custom_widgets[<?php echo $i; ?>][title]" value="<?php echo htmlspecialchars((string)($widget['title'] ?? '')); ?>">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label" for="custom_widget_<?php echo $i; ?>_icon">Icon / Emoji</label>
                                            <input id="custom_widget_<?php echo $i; ?>_icon" type="text" class="form-control" name="custom_widgets[<?php echo $i; ?>][icon]" value="<?php echo htmlspecialchars((string)($widget['icon'] ?? '')); ?>" placeholder="📌">
                                        </div>
                                        <div>
                                            <label class="form-label" for="custom_widget_<?php echo $i; ?>_content">Inhalt</label>
                                            <textarea id="custom_widget_<?php echo $i; ?>_content" class="form-control" rows="5" name="custom_widgets[<?php echo $i; ?>][content]"><?php echo htmlspecialchars((string)($widget['content'] ?? '')); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endfor; ?>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Widget-Seite speichern</button>
            </div>
        </form>
    </div>
</div>

<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_MEMBER_VIEW')) {
    exit;
}

$pageAssets = $pageAssets ?? [];
$pageAssets['js'] = is_array($pageAssets['js'] ?? null) ? $pageAssets['js'] : [];
$pageAssets['js'][] = cms_asset_url('js/admin-member-dashboard.js');

$settings = $data['settings'] ?? [];
$widgets  = $data['widgets'] ?? [];
$sectionOrderOptions = $data['sectionOrderOptions'] ?? [];
$customWidgets = $settings['custom_widgets'] ?? [];
$availableWidgetKeys = array_map('strval', array_keys($widgets));
$widgetOrder = array_values(array_filter(array_map('strval', (array)($settings['widgets'] ?? [])), static function (string $widgetKey) use ($widgets): bool {
    return isset($widgets[$widgetKey]);
}));
foreach ($availableWidgetKeys as $widgetKey) {
    if (!in_array($widgetKey, $widgetOrder, true)) {
        $widgetOrder[] = $widgetKey;
    }
}
$customWidgetOrder = array_values(array_filter(array_map('strval', (array)($settings['custom_widget_order'] ?? ['1', '2', '3', '4'])), static function (string $widgetKey): bool {
    return in_array($widgetKey, ['1', '2', '3', '4'], true);
}));
foreach (['1', '2', '3', '4'] as $widgetKey) {
    if (!in_array($widgetKey, $customWidgetOrder, true)) {
        $customWidgetOrder[] = $widgetKey;
    }
}
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
        <?php $alertData = $alert; $alertMarginClass = 'mb-4'; require __DIR__ . '/../partials/flash-alert.php'; ?>

        <?php require __DIR__ . '/subnav.php'; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="settings_section" value="widgets">
            <input type="hidden" name="widget_order" id="member_widget_order" value="<?php echo htmlspecialchars(implode(',', $widgetOrder), ENT_QUOTES); ?>">
            <input type="hidden" name="custom_widget_order" id="member_custom_widget_order" value="<?php echo htmlspecialchars(implode(',', $customWidgetOrder), ENT_QUOTES); ?>">

            <div class="row row-cards mb-4">
                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-header">
                            <h3 class="card-title">Kern-Widgets</h3>
                        </div>
                        <div class="card-body">
                            <p class="text-secondary small mb-3">Sortiere die Widget-Reihenfolge per Drag &amp; Drop oder über die Pfeilbuttons. Gespeichert wird nur die allowlist-geprüfte Reihenfolge aktiver Widgets.</p>
                            <div class="dashboard-sortable-list d-flex flex-column gap-3" data-member-sortable-list="1" data-order-input="member_widget_order" aria-live="polite">
                                <?php foreach ($widgetOrder as $key): ?>
                                    <?php $widget = $widgets[$key] ?? null; ?>
                                    <?php if (!is_array($widget)) { continue; } ?>
                                    <div class="dashboard-sortable-item card card-sm" data-sort-key="<?php echo htmlspecialchars((string)$key, ENT_QUOTES); ?>">
                                        <div class="card-body d-flex flex-column flex-lg-row gap-3 align-items-start align-items-lg-center">
                                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                                <span class="badge bg-secondary-lt dashboard-sortable-handle" data-sort-handle="1" aria-hidden="true">⇅</span>
                                            </div>
                                            <div class="flex-fill">
                                                <label class="form-check mb-2">
                                                    <input type="checkbox" class="form-check-input" name="widgets[<?php echo htmlspecialchars((string)$key); ?>]" value="1" <?php echo in_array($key, $settings['widgets'] ?? [], true) ? 'checked' : ''; ?>>
                                                    <span class="form-check-label fw-semibold"><?php echo htmlspecialchars((string)($widget['label'] ?? $key)); ?></span>
                                                </label>
                                                <div class="text-muted small mb-2"><?php echo htmlspecialchars((string)($widget['description'] ?? '')); ?></div>
                                                <?php if (!empty($widget['recommended'])): ?>
                                                    <span class="badge bg-green-lt text-green">Empfohlen</span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="dashboard-sortable-actions d-flex gap-2" aria-label="Reihenfolge für <?php echo htmlspecialchars((string)($widget['label'] ?? $key), ENT_QUOTES); ?> anpassen">
                                                <button type="button" class="btn btn-outline-secondary btn-sm" data-sort-move="up" aria-label="<?php echo htmlspecialchars((string)($widget['label'] ?? $key), ENT_QUOTES); ?> nach oben">↑</button>
                                                <button type="button" class="btn btn-outline-secondary btn-sm" data-sort-move="down" aria-label="<?php echo htmlspecialchars((string)($widget['label'] ?? $key), ENT_QUOTES); ?> nach unten">↓</button>
                                            </div>
                                        </div>
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
                    <p class="text-secondary small mb-3">Die Reihenfolge steuert die Auslieferung der eigenen Info-Widgets im Preview- und Member-Runtime-Pfad. Die Inhalte bleiben auf ihren sicheren Slot-IDs gespeichert.</p>
                    <div class="dashboard-sortable-list d-flex flex-column gap-3" data-member-sortable-list="1" data-order-input="member_custom_widget_order" aria-live="polite">
                        <?php foreach ($customWidgetOrder as $widgetPosition): ?>
                            <?php $i = (int)$widgetPosition; ?>
                            <?php $widget = $customWidgets[$i] ?? ['title' => '', 'icon' => '', 'content' => '']; ?>
                            <div class="dashboard-sortable-item card card-sm border" data-sort-key="<?php echo htmlspecialchars((string)$widgetPosition, ENT_QUOTES); ?>">
                                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-2">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="badge bg-secondary-lt dashboard-sortable-handle" data-sort-handle="1" aria-hidden="true">⇅</span>
                                        <h4 class="card-title mb-0">Infobox</h4>
                                        <span class="badge bg-secondary-lt text-secondary">Slot <?php echo $i; ?></span>
                                    </div>
                                    <div class="dashboard-sortable-actions d-flex gap-2" aria-label="Reihenfolge für Infobox <?php echo $i; ?> anpassen">
                                        <button type="button" class="btn btn-outline-secondary btn-sm" data-sort-move="up" aria-label="Infobox <?php echo $i; ?> nach oben">↑</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm" data-sort-move="down" aria-label="Infobox <?php echo $i; ?> nach unten">↓</button>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label" for="custom_widget_<?php echo $i; ?>_title">Titel</label>
                                            <input id="custom_widget_<?php echo $i; ?>_title" type="text" class="form-control" name="custom_widgets[<?php echo $i; ?>][title]" value="<?php echo htmlspecialchars((string)($widget['title'] ?? '')); ?>">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label" for="custom_widget_<?php echo $i; ?>_icon">Icon / Emoji</label>
                                            <input id="custom_widget_<?php echo $i; ?>_icon" type="text" class="form-control" name="custom_widgets[<?php echo $i; ?>][icon]" value="<?php echo htmlspecialchars((string)($widget['icon'] ?? '')); ?>" placeholder="📌">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label" for="custom_widget_<?php echo $i; ?>_content">Inhalt</label>
                                            <textarea id="custom_widget_<?php echo $i; ?>_content" class="form-control" rows="5" name="custom_widgets[<?php echo $i; ?>][content]"><?php echo htmlspecialchars((string)($widget['content'] ?? '')); ?></textarea>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Widget-Seite speichern</button>
            </div>
        </form>
    </div>
</div>

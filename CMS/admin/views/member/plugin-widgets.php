<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_MEMBER_VIEW')) {
    exit;
}

$settings = $data['settings'] ?? [];
$pluginWidgets = $data['pluginWidgets'] ?? [];
$order = $settings['plugin_widget_order'] ?? [];

if ($order !== []) {
    usort($pluginWidgets, static function (array $a, array $b) use ($order): int {
        $aPos = array_search($a['plugin'], $order, true);
        $bPos = array_search($b['plugin'], $order, true);
        $aPos = $aPos === false ? 999 : (int)$aPos;
        $bPos = $bPos === false ? 999 : (int)$bPos;
        return $aPos <=> $bPos;
    });
}
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Mitglieder &amp; Zugriff</div>
                <h2 class="page-title">Member Dashboard – Plugin-Widgets</h2>
                <div class="text-muted mt-1">Sortiere Plugin-Kacheln per Drag &amp; Drop und steuere ihre Sichtbarkeit im Frontend-Dashboard.</div>
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

        <form method="post" id="pluginWidgetForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="settings_section" value="plugin-widgets">
            <input type="hidden" name="plugin_widget_order" id="plugin_widget_order" value="<?php echo htmlspecialchars(implode(',', $order)); ?>">

            <div class="card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h3 class="card-title">Reihenfolge &amp; Sichtbarkeit</h3>
                    <button type="submit" class="btn btn-primary btn-sm">Plugin-Widgets speichern</button>
                </div>
                <div class="card-body">
                    <?php if ($pluginWidgets === []): ?>
                        <div class="text-muted">Aktuell sind keine registrierten Plugin-Widgets vorhanden.</div>
                    <?php else: ?>
                        <div class="row row-cards" id="pluginWidgetList">
                            <?php foreach ($pluginWidgets as $widget):
                                $pluginSlug = (string)($widget['plugin'] ?? '');
                                $visible = ($settings['member_dashboard_plugin_' . $pluginSlug] ?? '1') === '1';
                            ?>
                                <div class="col-md-6 col-xl-4" data-plugin="<?php echo htmlspecialchars($pluginSlug); ?>" draggable="true">
                                    <div class="card card-sm h-100 border">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start gap-3 mb-3">
                                                <div style="width:44px;height:44px;border-radius:10px;background:<?php echo htmlspecialchars((string)($widget['color'] ?? '#4f46e5')); ?>20;display:flex;align-items:center;justify-content:center;font-size:1.25rem;">
                                                    <?php echo htmlspecialchars((string)($widget['icon'] ?? '🔌')); ?>
                                                </div>
                                                <div class="flex-fill">
                                                    <div class="fw-semibold"><?php echo htmlspecialchars((string)($widget['label'] ?? $pluginSlug)); ?></div>
                                                    <div class="text-muted small"><?php echo htmlspecialchars((string)($widget['description'] ?? '')); ?></div>
                                                </div>
                                                <span class="badge bg-secondary-lt text-secondary">⇅</span>
                                            </div>
                                            <label class="form-check form-switch mb-0">
                                                <input type="checkbox" class="form-check-input" name="plugin_visible[<?php echo htmlspecialchars($pluginSlug); ?>]" value="1" <?php echo $visible ? 'checked' : ''; ?>>
                                                <span class="form-check-label">Im Frontend anzeigen</span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
(function () {
    var list = document.getElementById('pluginWidgetList');
    var orderInput = document.getElementById('plugin_widget_order');
    if (!list || !orderInput) {
        return;
    }

    var dragItem = null;

    var syncOrder = function () {
        var values = [];
        list.querySelectorAll('[data-plugin]').forEach(function (item) {
            values.push(item.getAttribute('data-plugin') || '');
        });
        orderInput.value = values.filter(Boolean).join(',');
    };

    list.querySelectorAll('[data-plugin]').forEach(function (item) {
        item.addEventListener('dragstart', function () {
            dragItem = item;
            item.classList.add('opacity-50');
        });

        item.addEventListener('dragend', function () {
            item.classList.remove('opacity-50');
            dragItem = null;
            syncOrder();
        });

        item.addEventListener('dragover', function (event) {
            event.preventDefault();
        });

        item.addEventListener('drop', function (event) {
            event.preventDefault();
            if (!dragItem || dragItem === item) {
                return;
            }
            var nodes = Array.prototype.slice.call(list.children);
            var dragIndex = nodes.indexOf(dragItem);
            var dropIndex = nodes.indexOf(item);
            if (dragIndex < dropIndex) {
                list.insertBefore(dragItem, item.nextSibling);
            } else {
                list.insertBefore(dragItem, item);
            }
            syncOrder();
        });
    });

    syncOrder();
})();
</script>

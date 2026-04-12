<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_SYSTEM_VIEW')) {
    exit;
}

$groups = is_array($data['groups'] ?? null) ? $data['groups'] : [];
?>

<div class="container-xl">
    <div class="page-header d-print-none mb-4">
        <div class="row align-items-center g-3">
            <div class="col">
                <div class="page-pretitle">System</div>
                <h2 class="page-title">Module</h2>
                <div class="text-secondary mt-1">Kernmodule sind fest in 365CMS integriert und werden zentral vom Entwickler gesteuert – deutlich mehr Maschinenraum als bei normalen Plugins.</div>
            </div>
        </div>
    </div>

    <?php if (!empty($alert)): ?>
        <?php $alertData = $alert; $alertMarginClass = 'mb-4'; require __DIR__ . '/../partials/flash-alert.php'; ?>
    <?php endif; ?>

    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars((string) $csrfToken); ?>">
        <input type="hidden" name="action" value="save_modules">

        <div class="card mb-4 border-azure">
            <div class="card-body">
                <div class="d-flex flex-column flex-lg-row gap-3 justify-content-between align-items-lg-center">
                    <div>
                        <h3 class="card-title mb-1">Core statt Plugin</h3>
                        <div class="text-secondary">Hier sind bewusst nur die echten Root-Core-Module schaltbar. Interne Untermodule und Abhängigkeitsverträge bleiben verborgen, damit ein Root-Schalter seinen ganzen Bereich sauber, fail-closed und ohne Mikromanagement ein- oder ausschaltet.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">Module speichern</button>
                </div>
            </div>
        </div>

        <?php if ($groups === []): ?>
            <div class="card">
                <div class="card-body text-secondary">Aktuell sind keine Core-Module registriert.</div>
            </div>
        <?php else: ?>
            <div class="row g-4">
                <?php foreach ($groups as $group): ?>
                    <?php
                    $modules = is_array($group['modules'] ?? null) ? $group['modules'] : [];
                    $enabledCount = (int) ($group['enabled_count'] ?? 0);
                    $totalCount = (int) ($group['total_count'] ?? count($modules));
                    ?>
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
                                <div>
                                    <h3 class="card-title mb-1"><?php echo htmlspecialchars((string) ($group['label'] ?? 'Modulgruppe')); ?></h3>
                                    <div class="text-secondary">Aktiv: <?php echo $enabledCount; ?> von <?php echo $totalCount; ?> Modulen</div>
                                </div>
                                <span class="badge bg-<?php echo $enabledCount === $totalCount ? 'success' : ($enabledCount > 0 ? 'warning' : 'secondary'); ?>-lt">
                                    <?php echo $enabledCount === $totalCount ? 'Alles aktiv' : ($enabledCount > 0 ? 'Teilweise aktiv' : 'Derzeit deaktiviert'); ?>
                                </span>
                            </div>
                            <div class="list-group list-group-flush">
                                <?php foreach ($modules as $module): ?>
                                    <?php
                                    $moduleSlug = (string) ($module['slug'] ?? '');
                                    $storedEnabled = !empty($module['stored_enabled']);
                                    $effectiveEnabled = !empty($module['effective_enabled']);
                                    $adminLabels = array_values(array_filter((array) ($module['admin_labels'] ?? []), static fn (mixed $value): bool => is_string($value) && trim($value) !== ''));
                                    $dependencyLabels = array_values(array_filter((array) ($module['dependency_labels'] ?? []), static fn (mixed $value): bool => is_string($value) && trim($value) !== ''));
                                    $legacySetting = trim((string) ($module['legacy_setting'] ?? ''));
                                    ?>
                                    <div class="list-group-item">
                                        <div class="row g-3 align-items-start">
                                            <div class="col-lg-7">
                                                <label class="form-check form-switch mb-2">
                                                    <input
                                                        type="checkbox"
                                                        class="form-check-input"
                                                        name="modules[<?php echo htmlspecialchars($moduleSlug); ?>]"
                                                        value="1"
                                                        <?php echo $storedEnabled ? 'checked' : ''; ?>
                                                    >
                                                    <span class="form-check-label fw-semibold"><?php echo htmlspecialchars((string) ($module['label'] ?? $moduleSlug)); ?></span>
                                                </label>
                                                <div class="text-secondary mb-2"><?php echo htmlspecialchars((string) ($module['description'] ?? '')); ?></div>
                                                <div class="small text-secondary">
                                                    <strong>Status:</strong>
                                                    <?php echo htmlspecialchars((string) ($module['status_reason'] ?? '')); ?>
                                                </div>
                                            </div>
                                            <div class="col-lg-5">
                                                <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                                                    <span class="badge bg-<?php echo $effectiveEnabled ? 'success' : 'secondary'; ?>-lt">
                                                        <?php echo $effectiveEnabled ? 'Effektiv aktiv' : 'Effektiv aus'; ?>
                                                    </span>
                                                    <span class="badge bg-<?php echo $storedEnabled ? 'azure' : 'secondary'; ?>-lt">
                                                        <?php echo $storedEnabled ? 'Gespeichert: an' : 'Gespeichert: aus'; ?>
                                                    </span>
                                                </div>

                                                <?php if ($adminLabels !== []): ?>
                                                    <div class="mt-3 small text-secondary text-lg-end">
                                                        <strong>Admin-Bereiche:</strong><br>
                                                        <?php echo htmlspecialchars(implode(', ', $adminLabels)); ?>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($dependencyLabels !== []): ?>
                                                    <div class="mt-2 small text-secondary text-lg-end">
                                                        <strong>Abhängig von:</strong><br>
                                                        <?php echo htmlspecialchars(implode(', ', $dependencyLabels)); ?>
                                                    </div>
                                                <?php endif; ?>

                                                <?php if ($legacySetting !== ''): ?>
                                                    <div class="mt-2 small text-secondary text-lg-end">
                                                        <strong>Legacy-Setting:</strong><br>
                                                        <code><?php echo htmlspecialchars($legacySetting); ?></code>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="mt-4 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Module speichern</button>
            </div>
        <?php endif; ?>
    </form>
</div>

<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$settings = $data['settings'] ?? [];
$profileFields = $data['profileFields'] ?? [];
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Mitglieder &amp; Zugriff</div>
                <h2 class="page-title">Member Dashboard – Profil-Felder</h2>
                <div class="text-muted mt-1">Pflege sichtbare Profilinformationen und steuere zusätzliche Menüpunkte für Mitglieder.</div>
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
            <input type="hidden" name="settings_section" value="profile-fields">

            <div class="row row-cards mb-4">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Sichtbare Profil-Felder</h3>
                        </div>
                        <div class="card-body">
                            <div class="row row-cards">
                                <?php foreach ($profileFields as $key => $field): ?>
                                    <div class="col-md-6">
                                        <label class="card card-sm cursor-pointer h-100">
                                            <div class="card-body">
                                                <div class="form-check mb-2">
                                                    <input type="checkbox" class="form-check-input" name="profile_fields[<?php echo htmlspecialchars((string)$key); ?>]" value="1" <?php echo in_array($key, $settings['profile_fields'] ?? [], true) ? 'checked' : ''; ?>>
                                                    <span class="form-check-label fw-semibold"><?php echo htmlspecialchars((string)($field['label'] ?? $key)); ?></span>
                                                </div>
                                                <div class="text-muted small mb-2"><?php echo htmlspecialchars((string)($field['description'] ?? '')); ?></div>
                                                <?php if (!empty($field['recommended'])): ?>
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
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Navigation</h3>
                        </div>
                        <div class="card-body">
                            <label class="form-check form-switch">
                                <input type="checkbox" name="subscription_visible" class="form-check-input" value="1" <?php echo !empty($settings['subscription_visible']) ? 'checked' : ''; ?>>
                                <span class="form-check-label">Abo-Menüpunkt im Member-Bereich anzeigen</span>
                            </label>
                            <div class="form-hint mt-2">Steuert den zusätzlichen Menüeintrag „Abonnement“ im Mitgliederbereich.</div>
                        </div>
                    </div>

                    <div class="card sticky-top" style="top: 1rem;">
                        <div class="card-header">
                            <h3 class="card-title">Speichern</h3>
                        </div>
                        <div class="card-body">
                            <p class="text-muted small">Änderungen wirken sich direkt auf die Profilansicht und optionale Menüpunkte im Member-Bereich aus.</p>
                            <button type="submit" class="btn btn-primary w-100">Profil-Felder speichern</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

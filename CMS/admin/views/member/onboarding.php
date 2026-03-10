<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_MEMBER_VIEW')) {
    exit;
}

$settings = $data['settings'] ?? [];
$onboarding = $settings['onboarding'] ?? [];
$stepsValue = implode("\n", $onboarding['steps'] ?? []);
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Mitglieder &amp; Zugriff</div>
                <h2 class="page-title">Member Dashboard – Mitglieder-Onboarding</h2>
                <div class="text-muted mt-1">Lege Checkliste, Einführungstext und Call-to-Action für neue Mitglieder fest.</div>
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
            <input type="hidden" name="settings_section" value="onboarding">

            <div class="row row-cards">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header"><h3 class="card-title">Onboarding-Inhalt</h3></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input type="checkbox" class="form-check-input" name="onboarding_enabled" value="1" <?php echo !empty($onboarding['enabled']) ? 'checked' : ''; ?>>
                                    <span class="form-check-label">Onboarding-Panel aktivieren</span>
                                </label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="onboarding_title">Titel</label>
                                <input id="onboarding_title" name="onboarding_title" type="text" class="form-control" value="<?php echo htmlspecialchars((string)($onboarding['title'] ?? '')); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="onboarding_intro">Einleitung</label>
                                <textarea id="onboarding_intro" name="onboarding_intro" class="form-control" rows="4"><?php echo htmlspecialchars((string)($onboarding['intro'] ?? '')); ?></textarea>
                            </div>
                            <div>
                                <label class="form-label" for="onboarding_steps">Checkliste (eine Zeile = ein Schritt)</label>
                                <textarea id="onboarding_steps" name="onboarding_steps" class="form-control" rows="8"><?php echo htmlspecialchars($stepsValue); ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header"><h3 class="card-title">Aktion</h3></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label" for="onboarding_cta_label">CTA-Label</label>
                                <input id="onboarding_cta_label" name="onboarding_cta_label" type="text" class="form-control" value="<?php echo htmlspecialchars((string)($onboarding['cta_label'] ?? '')); ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="onboarding_cta_url">CTA-URL</label>
                                <input id="onboarding_cta_url" name="onboarding_cta_url" type="text" class="form-control" value="<?php echo htmlspecialchars((string)($onboarding['cta_url'] ?? '')); ?>">
                            </div>
                            <label class="form-check form-switch">
                                <input type="checkbox" class="form-check-input" name="onboarding_require_profile_completion" value="1" <?php echo !empty($onboarding['require_profile_completion']) ? 'checked' : ''; ?>>
                                <span class="form-check-label">Profil-Vervollständigung hervorheben</span>
                            </label>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">Onboarding speichern</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

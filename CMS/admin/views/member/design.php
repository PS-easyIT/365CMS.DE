<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

if (!defined('CMS_ADMIN_MEMBER_VIEW')) {
    exit;
}

$settings = $data['settings'] ?? [];
$design = $settings['design'] ?? [];
?>
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Mitglieder &amp; Zugriff</div>
                <h2 class="page-title">Member Dashboard – Design &amp; Farben</h2>
                <div class="text-muted mt-1">Pflege die zentrale Farbwelt des Frontend-Dashboards mit sofort wirksamen Design-Tokens.</div>
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
            <input type="hidden" name="settings_section" value="design">

            <div class="row row-cards">
                <div class="col-lg-8">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Farbpalette</h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="color_primary">Primärfarbe</label>
                                    <input type="color" class="form-control form-control-color" id="color_primary" name="color_primary" value="<?php echo htmlspecialchars((string)($design['primary'] ?? '#6366f1')); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="color_accent">Akzentfarbe</label>
                                    <input type="color" class="form-control form-control-color" id="color_accent" name="color_accent" value="<?php echo htmlspecialchars((string)($design['accent'] ?? '#8b5cf6')); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="color_bg">Seitenhintergrund</label>
                                    <input type="color" class="form-control form-control-color" id="color_bg" name="color_bg" value="<?php echo htmlspecialchars((string)($design['bg'] ?? '#f1f5f9')); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="color_card_bg">Kartenhintergrund</label>
                                    <input type="color" class="form-control form-control-color" id="color_card_bg" name="color_card_bg" value="<?php echo htmlspecialchars((string)($design['card_bg'] ?? '#ffffff')); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="color_text">Textfarbe</label>
                                    <input type="color" class="form-control form-control-color" id="color_text" name="color_text" value="<?php echo htmlspecialchars((string)($design['text'] ?? '#1e293b')); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="color_border">Border-Farbe</label>
                                    <input type="color" class="form-control form-control-color" id="color_border" name="color_border" value="<?php echo htmlspecialchars((string)($design['border'] ?? '#e2e8f0')); ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="card-title">Live-Stimmung</h3>
                        </div>
                        <div class="card-body">
                            <div style="background:<?php echo htmlspecialchars((string)($design['bg'] ?? '#f1f5f9')); ?>; border:1px solid <?php echo htmlspecialchars((string)($design['border'] ?? '#e2e8f0')); ?>; border-radius:12px; padding:1rem;">
                                <div style="background:linear-gradient(135deg, <?php echo htmlspecialchars((string)($design['primary'] ?? '#6366f1')); ?> 0%, <?php echo htmlspecialchars((string)($design['accent'] ?? '#8b5cf6')); ?> 100%); color:#fff; border-radius:10px; padding:1rem; margin-bottom:1rem;">
                                    <strong>Willkommen im Member-Hub</strong><br>
                                    <small>Deine Farbwelt für das Frontend</small>
                                </div>
                                <div style="background:<?php echo htmlspecialchars((string)($design['card_bg'] ?? '#ffffff')); ?>; border:1px solid <?php echo htmlspecialchars((string)($design['border'] ?? '#e2e8f0')); ?>; color:<?php echo htmlspecialchars((string)($design['text'] ?? '#1e293b')); ?>; border-radius:10px; padding:1rem;">
                                    Karten, Panels und Statistiken übernehmen diese Tokens direkt im Member-Dashboard.
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">Design speichern</button>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

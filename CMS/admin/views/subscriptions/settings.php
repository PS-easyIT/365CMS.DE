<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$siteUrl  = defined('SITE_URL') ? SITE_URL : '';
$settings = $data['settings'] ?? [];
$pages    = $data['pages'] ?? [];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Aboverwaltung</div>
                <h2 class="page-title">Abo-Einstellungen</h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <?php if (!empty($alert)): ?>
            <div class="alert alert-<?= htmlspecialchars($alert['type']) ?> alert-dismissible" role="alert">
                <div><?= htmlspecialchars($alert['message']) ?></div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></a>
            </div>
        <?php endif; ?>

        <form method="post">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

            <div class="row g-4">
                <!-- Allgemein -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Allgemein</h3></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input type="checkbox" name="subscription_enabled" class="form-check-input" <?= ($settings['subscription_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                                    <span class="form-check-label">Abo-System aktivieren</span>
                                </label>
                            </div>
                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input type="checkbox" name="auto_renewal" class="form-check-input" <?= ($settings['auto_renewal'] ?? '0') === '1' ? 'checked' : '' ?>>
                                    <span class="form-check-label">Automatische Verlängerung</span>
                                </label>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Kulanzzeit (Tage)</label>
                                    <input type="number" name="grace_period_days" class="form-control" min="0" value="<?= (int)($settings['grace_period_days'] ?? 3) ?>">
                                    <small class="form-hint">Nach Ablauf, bevor Abo deaktiviert wird</small>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Kündigungsfrist (Tage)</label>
                                    <input type="number" name="cancellation_period_days" class="form-control" min="0" value="<?= (int)($settings['cancellation_period_days'] ?? 0) ?>">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Testphase -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Testphase</h3></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-check form-switch">
                                    <input type="checkbox" name="trial_enabled" class="form-check-input" <?= ($settings['trial_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                                    <span class="form-check-label">Testphase aktivieren</span>
                                </label>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Testphase (Tage)</label>
                                <input type="number" name="trial_days" class="form-control" min="1" max="365" value="<?= (int)($settings['trial_days'] ?? 14) ?>">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Rechnungen & Steuern -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Rechnungen & Steuern</h3></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Rechnungspräfix</label>
                                    <input type="text" name="invoice_prefix" class="form-control" value="<?= htmlspecialchars($settings['invoice_prefix'] ?? 'INV-') ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Nächste Nummer</label>
                                    <input type="number" name="invoice_next_number" class="form-control" min="1" value="<?= (int)($settings['invoice_next_number'] ?? 1001) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Steuersatz (%)</label>
                                    <input type="number" name="tax_rate" class="form-control" min="0" max="100" value="<?= (int)($settings['tax_rate'] ?? 19) ?>">
                                </div>
                                <div class="col-md-6 d-flex align-items-end">
                                    <label class="form-check form-switch">
                                        <input type="checkbox" name="tax_included" class="form-check-input" <?= ($settings['tax_included'] ?? '1') === '1' ? 'checked' : '' ?>>
                                        <span class="form-check-label">Preise inkl. MwSt.</span>
                                    </label>
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label">Zahlungsmethoden</label>
                                <select name="payment_methods" class="form-select">
                                    <option value="invoice" <?= ($settings['payment_methods'] ?? '') === 'invoice' ? 'selected' : '' ?>>Rechnung</option>
                                    <option value="stripe" <?= ($settings['payment_methods'] ?? '') === 'stripe' ? 'selected' : '' ?>>Stripe</option>
                                    <option value="paypal" <?= ($settings['payment_methods'] ?? '') === 'paypal' ? 'selected' : '' ?>>PayPal</option>
                                    <option value="all" <?= ($settings['payment_methods'] ?? '') === 'all' ? 'selected' : '' ?>>Alle</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Benachrichtigungen & Seiten -->
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Benachrichtigungen & Seiten</h3></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Tage vor Ablauf benachrichtigen</label>
                                <input type="number" name="notification_before_expiry" class="form-control" min="0" value="<?= (int)($settings['notification_before_expiry'] ?? 7) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Benachrichtigungs-E-Mail</label>
                                <input type="email" name="notification_email" class="form-control" placeholder="admin@example.com" value="<?= htmlspecialchars($settings['notification_email'] ?? '') ?>">
                                <small class="form-hint">E-Mail für Admin-Benachrichtigungen (leer = Standard-Admin-E-Mail)</small>
                            </div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">AGB-Seite</label>
                                    <select name="terms_page_id" class="form-select">
                                        <option value="0">– Keine –</option>
                                        <?php foreach ($pages as $p): ?>
                                            <option value="<?= (int)$p['id'] ?>" <?= (int)($settings['terms_page_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($p['title']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Widerrufsbelehrung</label>
                                    <select name="cancellation_page_id" class="form-select">
                                        <option value="0">– Keine –</option>
                                        <?php foreach ($pages as $p): ?>
                                            <option value="<?= (int)$p['id'] ?>" <?= (int)($settings['cancellation_page_id'] ?? 0) === (int)$p['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($p['title']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-4">
                <button type="submit" class="btn btn-primary">Einstellungen speichern</button>
            </div>
        </form>

    </div>
</div>

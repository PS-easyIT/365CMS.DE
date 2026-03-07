<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$siteUrl  = defined('SITE_URL') ? SITE_URL : '';
$settings = $data['settings'] ?? [];
$plans    = $data['plans'] ?? [];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Aboverwaltung</div>
                <h2 class="page-title">Einstellungen</h2>
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
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Betriebsmodus</h3></div>
                        <div class="card-body">
                            <label class="form-check form-switch mb-3">
                                <input type="checkbox" name="subscription_limits_enabled" class="form-check-input" <?= ($settings['subscription_limits_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                                <span class="form-check-label">Abo-Limits systemweit durchsetzen</span>
                            </label>
                            <div class="form-hint mb-3">Wenn deaktiviert, gibt es im gesamten CMS keine Paket-Limits mehr – unabhängig von Zuweisungen oder Planstufen.</div>

                            <label class="form-check form-switch mb-3">
                                <input type="checkbox" name="subscription_member_area_enabled" class="form-check-input" <?= ($settings['subscription_member_area_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                                <span class="form-check-label">Abo-Bereich im Member-Dashboard anzeigen</span>
                            </label>

                            <label class="form-check form-switch mb-3">
                                <input type="checkbox" name="subscription_ordering_enabled" class="form-check-input" <?= ($settings['subscription_ordering_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                                <span class="form-check-label">Bestell- und Upgrade-Prozesse zulassen</span>
                            </label>

                            <label class="form-check form-switch mb-0">
                                <input type="checkbox" name="subscription_public_pricing_enabled" class="form-check-input" <?= ($settings['subscription_public_pricing_enabled'] ?? '1') === '1' ? 'checked' : '' ?>>
                                <span class="form-check-label">Pakete öffentlich kommunizieren</span>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Standard-Zuweisung</h3></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Standardpaket für neue Mitglieder</label>
                                <select name="subscription_default_plan_id" class="form-select">
                                    <option value="0">– Keine automatische Zuweisung –</option>
                                    <?php foreach ($plans as $plan): ?>
                                        <option value="<?= (int)$plan['id'] ?>" <?= (int)($settings['subscription_default_plan_id'] ?? 0) === (int)$plan['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($plan['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="alert alert-warning mb-0" role="alert">
                                Dieser Bereich enthält bewusst nur <strong>Standardverhalten</strong> der Aboverwaltung. Paketdetails, Preise, Testphase, Steuern und Bestellprozesse pflegen Sie direkt auf den Bereichen <strong>Pakete &amp; Abo-Einstellungen</strong> sowie <strong>Bestellungen &amp; Zuweisung</strong>.
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Hinweis bei Deaktivierung</h3></div>
                        <div class="card-body">
                            <label class="form-label">Text für Admin-/Member-Hinweis</label>
                            <textarea name="subscription_disabled_notice" class="form-control" rows="5"><?= htmlspecialchars($settings['subscription_disabled_notice'] ?? '') ?></textarea>
                            <div class="form-hint mt-2">Dieser Text kann für interne Hinweise oder spätere Frontend-Ausgaben verwendet werden, wenn die Aboverwaltung vollständig deaktiviert ist.</div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Kurzüberblick</h3></div>
                        <div class="card-body">
                            <ul class="mb-0 text-secondary">
                                <li><strong>Pakete &amp; Abo-Einstellungen</strong>: Preise, Limits, Trial, Steuern, AGB-Links.</li>
                                <li><strong>Bestellungen &amp; Zuweisung</strong>: Bestellstatus und manuelle Paketzuweisung.</li>
                                <li><strong>Einstellungen</strong>: globaler Schalter, Standardverhalten und komplette Deaktivierung der Limitlogik.</li>
                            </ul>
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

<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$siteUrl  = defined('SITE_URL') ? SITE_URL : '';
$packages = $data['packages'] ?? [];
$stats    = $data['stats'] ?? [];
$settings = $data['settings'] ?? [];
$pages    = $data['pages'] ?? [];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Aboverwaltung</div>
                <h2 class="page-title">Pakete &amp; Abo-Einstellungen</h2>
            </div>
            <div class="col-auto ms-auto d-flex gap-2">
                <form method="post" class="d-inline">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                    <input type="hidden" name="action" value="seed_defaults">
                    <button type="submit" class="btn btn-outline-primary">
                        6 Standardpakete hinterlegen
                    </button>
                </form>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#packageModal" onclick="resetPackageForm()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                    Neues Paket
                </button>
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

        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Pakete gesamt</div>
                        </div>
                        <div class="h1 mb-0"><?= (int)($stats['total'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Aktiv</div>
                        </div>
                        <div class="h1 mb-0 text-success"><?= (int)($stats['active'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
            <div class="col-sm-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader">Empfohlen</div>
                        </div>
                        <div class="h1 mb-0 text-primary"><?= (int)($stats['featured'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php if (empty($packages)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-muted" width="40" height="40" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3l8 4.5v9l-8 4.5l-8 -4.5v-9l8 -4.5"/><path d="M12 12l8 -4.5"/><path d="M12 12v9"/><path d="M12 12l-8 -4.5"/></svg>
                    </div>
                    <h3 class="text-muted">Noch keine Pakete hinterlegt</h3>
                    <p class="text-secondary">Legen Sie per Klick die 6 Standardpakete an oder erstellen Sie eigene Pakete für Limits, Plugins und Premium-Funktionen.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="row row-deck row-cards">
                <?php foreach ($packages as $pkg): ?>
                    <?php
                    $monthlyPrice = (float)($pkg['price_monthly'] ?? $pkg['price'] ?? 0);
                    $yearlyPrice = (float)($pkg['price_yearly'] ?? (isset($pkg['price']) ? ((float)$pkg['price'] * 12) : 0));
                    $currency     = strtoupper((string)($pkg['currency'] ?? 'EUR'));
                    $currencyUnit = $currency === 'EUR' ? '€' : $currency;
                    ?>
                    <div class="col-sm-6 col-lg-4">
                        <div class="card<?= (int)$pkg['is_featured'] ? ' border-primary' : '' ?><?= !(int)$pkg['is_active'] ? ' opacity-50' : '' ?>">
                            <?php if ((int)$pkg['is_featured']): ?>
                                <div class="card-status-top bg-primary"></div>
                            <?php endif; ?>
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h3 class="card-title mb-1"><?= htmlspecialchars($pkg['name']) ?></h3>
                                        <span class="badge <?= (int)$pkg['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                            <?= (int)$pkg['is_active'] ? 'Aktiv' : 'Inaktiv' ?>
                                        </span>
                                        <?php if ((int)$pkg['is_featured']): ?>
                                            <span class="badge bg-primary">Empfohlen</span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="dropdown">
                                        <a href="#" class="btn-action" data-bs-toggle="dropdown"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/></svg></a>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item" href="#" onclick="editPackage(<?= htmlspecialchars(json_encode($pkg)) ?>)">Bearbeiten</a>
                                            <form method="post" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                <input type="hidden" name="action" value="toggle">
                                                <input type="hidden" name="id" value="<?= (int)$pkg['id'] ?>">
                                                <button type="submit" class="dropdown-item"><?= (int)$pkg['is_active'] ? 'Deaktivieren' : 'Aktivieren' ?></button>
                                            </form>
                                            <button class="dropdown-item text-danger" onclick="cmsConfirm({title:'Paket löschen?',message:'Dieses Paket wird unwiderruflich gelöscht.',onConfirm:function(){document.getElementById('deleteForm-<?= (int)$pkg['id'] ?>').submit();}})">Löschen</button>
                                        </div>
                                    </div>
                                </div>
                                <div class="h1 mb-1">
                                    <?= number_format($monthlyPrice, 2, ',', '.') ?>
                                    <span class="text-secondary fs-5"><?= htmlspecialchars($currencyUnit) ?>/Monat</span>
                                </div>
                                <div class="text-secondary mb-3">
                                    <?= number_format($yearlyPrice, 2, ',', '.') ?> <?= htmlspecialchars($currencyUnit) ?>/Jahr
                                </div>
                                <?php if (!empty($pkg['description'])): ?>
                                    <p class="text-secondary small mb-3"><?= htmlspecialchars($pkg['description']) ?></p>
                                <?php endif; ?>
                                <div class="row g-2 small text-secondary mb-3">
                                    <div class="col-6">Experts: <strong><?= (int)($pkg['limit_experts'] ?? 0) < 0 ? '∞' : (int)($pkg['limit_experts'] ?? 0) ?></strong></div>
                                    <div class="col-6">Firmen: <strong><?= (int)($pkg['limit_companies'] ?? 0) < 0 ? '∞' : (int)($pkg['limit_companies'] ?? 0) ?></strong></div>
                                    <div class="col-6">Events: <strong><?= (int)($pkg['limit_events'] ?? 0) < 0 ? '∞' : (int)($pkg['limit_events'] ?? 0) ?></strong></div>
                                    <div class="col-6">Speaker: <strong><?= (int)($pkg['limit_speakers'] ?? 0) < 0 ? '∞' : (int)($pkg['limit_speakers'] ?? 0) ?></strong></div>
                                    <div class="col-12">Speicher: <strong><?= number_format((float)($pkg['limit_storage_mb'] ?? 0), 0, ',', '.') ?> MB</strong></div>
                                </div>
                                <?php if (!empty($pkg['features_list'])): ?>
                                    <ul class="list-unstyled space-y-1">
                                        <?php foreach ($pkg['features_list'] as $feat): ?>
                                            <li>
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon text-success" width="20" height="20" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                                                <?= htmlspecialchars($feat) ?>
                                            </li>
                                        <?php endforeach; ?>
                                    </ul>
                                <?php endif; ?>
                                <div class="mt-3 text-secondary small">
                                    <span class="badge bg-azure-lt"><?= (int)($pkg['subscriber_count'] ?? 0) ?> Abonnenten</span>
                                </div>
                            </div>
                            <form id="deleteForm-<?= (int)$pkg['id'] ?>" method="post" class="d-none">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?= (int)$pkg['id'] ?>">
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="row row-deck row-cards mt-4">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Abo-Einstellungen für Pakete & Prozesse</h3>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                            <input type="hidden" name="action" value="save_package_settings">

                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-check form-switch mb-3">
                                        <input type="checkbox" name="subscription_enabled" class="form-check-input" <?= ($settings['subscription_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                                        <span class="form-check-label">Aboverwaltung sichtbar/aktiv</span>
                                    </label>
                                    <label class="form-check form-switch mb-3">
                                        <input type="checkbox" name="trial_enabled" class="form-check-input" <?= ($settings['trial_enabled'] ?? '0') === '1' ? 'checked' : '' ?>>
                                        <span class="form-check-label">Testphase aktivieren</span>
                                    </label>
                                    <label class="form-check form-switch mb-3">
                                        <input type="checkbox" name="auto_renewal" class="form-check-input" <?= ($settings['auto_renewal'] ?? '0') === '1' ? 'checked' : '' ?>>
                                        <span class="form-check-label">Automatische Verlängerung</span>
                                    </label>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label">Testphase (Tage)</label>
                                        <input type="number" name="trial_days" class="form-control" min="1" value="<?= (int)($settings['trial_days'] ?? 14) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Kulanzzeit (Tage)</label>
                                        <input type="number" name="grace_period_days" class="form-control" min="0" value="<?= (int)($settings['grace_period_days'] ?? 3) ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label">Kündigungsfrist (Tage)</label>
                                        <input type="number" name="cancellation_period_days" class="form-control" min="0" value="<?= (int)($settings['cancellation_period_days'] ?? 0) ?>">
                                    </div>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Zahlungsmethoden</label>
                                    <select name="payment_methods" class="form-select">
                                        <?php foreach (['invoice' => 'Rechnung', 'stripe' => 'Stripe', 'paypal' => 'PayPal', 'all' => 'Alle'] as $value => $label): ?>
                                            <option value="<?= $value ?>" <?= ($settings['payment_methods'] ?? 'invoice') === $value ? 'selected' : '' ?>><?= $label ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Rechnungspräfix</label>
                                    <input type="text" name="invoice_prefix" class="form-control" value="<?= htmlspecialchars($settings['invoice_prefix'] ?? 'INV-') ?>">
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Nächste Rechnungsnummer</label>
                                    <input type="number" name="invoice_next_number" class="form-control" min="1" value="<?= (int)($settings['invoice_next_number'] ?? 1001) ?>">
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label">Steuersatz (%)</label>
                                    <input type="number" name="tax_rate" class="form-control" min="0" max="100" value="<?= (int)($settings['tax_rate'] ?? 19) ?>">
                                </div>
                                <div class="col-md-4 d-flex align-items-end">
                                    <label class="form-check form-switch mb-3">
                                        <input type="checkbox" name="tax_included" class="form-check-input" <?= ($settings['tax_included'] ?? '1') === '1' ? 'checked' : '' ?>>
                                        <span class="form-check-label">Preise inkl. MwSt.</span>
                                    </label>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Ablaufhinweis (Tage vorher)</label>
                                    <input type="number" name="notification_before_expiry" class="form-control" min="0" value="<?= (int)($settings['notification_before_expiry'] ?? 7) ?>">
                                </div>

                                <div class="col-md-6">
                                    <label class="form-label">Benachrichtigungs-E-Mail</label>
                                    <input type="email" name="notification_email" class="form-control" value="<?= htmlspecialchars($settings['notification_email'] ?? '') ?>">
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">AGB-Seite</label>
                                    <select name="terms_page_id" class="form-select">
                                        <option value="0">– Keine –</option>
                                        <?php foreach ($pages as $page): ?>
                                            <option value="<?= (int)$page['id'] ?>" <?= (int)($settings['terms_page_id'] ?? 0) === (int)$page['id'] ? 'selected' : '' ?>><?= htmlspecialchars($page['title']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Widerrufs-Seite</label>
                                    <select name="cancellation_page_id" class="form-select">
                                        <option value="0">– Keine –</option>
                                        <?php foreach ($pages as $page): ?>
                                            <option value="<?= (int)$page['id'] ?>" <?= (int)($settings['cancellation_page_id'] ?? 0) === (int)$page['id'] ? 'selected' : '' ?>><?= htmlspecialchars($page['title']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">Einstellungen speichern</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card h-100">
                    <div class="card-header">
                        <h3 class="card-title">Hinweis</h3>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-primary" role="alert">
                            Über <strong>Einstellungen</strong> in der Sidebar können Sie die <strong>Limit-Durchsetzung komplett deaktivieren</strong>. Dann greifen im gesamten CMS keine Abo-Limits mehr – praktisch, wenn Sie intern testen oder das System temporär ohne Restriktionen nutzen möchten.
                        </div>
                        <ul class="text-secondary mb-0">
                            <li>Standardpakete legen die 6 üblichen Tarife an.</li>
                            <li>Ein hervorgehobenes Paket wird als empfohlen markiert.</li>
                            <li>Plugins, Limits und Premium-Features sind direkt paketbezogen pflegbar.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<div class="modal modal-blur fade" id="packageModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <form method="post" id="packageForm">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="action" value="save">
                <input type="hidden" name="id" id="pkg-id" value="0">
                <div class="modal-header">
                    <h5 class="modal-title" id="packageModalTitle">Neues Paket</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label required">Paketname</label>
                            <input type="text" name="name" id="pkg-name" class="form-control" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Slug</label>
                            <input type="text" name="slug" id="pkg-slug" class="form-control" placeholder="auto">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label required">Preis / Monat</label>
                            <input type="number" name="price_monthly" id="pkg-price_monthly" class="form-control" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Preis / Jahr</label>
                            <input type="number" name="price_yearly" id="pkg-price_yearly" class="form-control" step="0.01" min="0" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sortierung</label>
                            <input type="number" name="sort_order" id="pkg-sort_order" class="form-control" min="0" value="0">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Beschreibung</label>
                            <textarea name="description" id="pkg-description" class="form-control" rows="2"></textarea>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label">Experts-Limit</label>
                            <input type="number" name="limit_experts" id="pkg-limit_experts" class="form-control" value="-1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Firmen-Limit</label>
                            <input type="number" name="limit_companies" id="pkg-limit_companies" class="form-control" value="-1">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Events-Limit</label>
                            <input type="number" name="limit_events" id="pkg-limit_events" class="form-control" value="-1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Speaker-Limit</label>
                            <input type="number" name="limit_speakers" id="pkg-limit_speakers" class="form-control" value="-1">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Speicher (MB)</label>
                            <input type="number" name="limit_storage_mb" id="pkg-limit_storage_mb" class="form-control" value="1000" min="0">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Plugin-Zugriff</label>
                            <div class="row g-2">
                                <?php foreach (['experts' => 'Experts', 'companies' => 'Firmen', 'events' => 'Events', 'speakers' => 'Speaker'] as $field => $label): ?>
                                    <div class="col-6">
                                        <label class="form-check">
                                            <input type="checkbox" class="form-check-input" name="plugin_<?= $field ?>" id="pkg-plugin_<?= $field ?>">
                                            <span class="form-check-label"><?= $label ?></span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label">Premium-Funktionen</label>
                            <div class="row g-2">
                                <?php foreach ([
                                    'analytics' => 'Analytics',
                                    'advanced_search' => 'Erweiterte Suche',
                                    'api_access' => 'API-Zugriff',
                                    'custom_branding' => 'Branding',
                                    'priority_support' => 'Priority Support',
                                    'export_data' => 'Export',
                                    'integrations' => 'Integrationen',
                                    'custom_domains' => 'Eigene Domains',
                                ] as $field => $label): ?>
                                    <div class="col-6">
                                        <label class="form-check">
                                            <input type="checkbox" class="form-check-input" name="feature_<?= $field ?>" id="pkg-feature_<?= $field ?>">
                                            <span class="form-check-label"><?= $label ?></span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-check form-switch mt-4">
                                <input type="checkbox" name="is_active" id="pkg-is_active" class="form-check-input" checked>
                                <span class="form-check-label">Aktiv</span>
                            </label>
                        </div>
                        <div class="col-md-4">
                            <label class="form-check form-switch mt-4">
                                <input type="checkbox" name="is_featured" id="pkg-is_featured" class="form-check-input">
                                <span class="form-check-label">Empfohlen</span>
                            </label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-primary">Speichern</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function resetPackageForm() {
    document.getElementById('packageModalTitle').textContent = 'Neues Paket';
    document.getElementById('pkg-id').value = '0';
    document.getElementById('pkg-name').value = '';
    document.getElementById('pkg-slug').value = '';
    document.getElementById('pkg-price_monthly').value = '';
    document.getElementById('pkg-price_yearly').value = '';
    document.getElementById('pkg-description').value = '';
    document.getElementById('pkg-limit_experts').value = '-1';
    document.getElementById('pkg-limit_companies').value = '-1';
    document.getElementById('pkg-limit_events').value = '-1';
    document.getElementById('pkg-limit_speakers').value = '-1';
    document.getElementById('pkg-limit_storage_mb').value = '1000';
    document.getElementById('pkg-sort_order').value = '0';
    document.getElementById('pkg-is_active').checked = true;
    document.getElementById('pkg-is_featured').checked = false;
    ['experts','companies','events','speakers'].forEach(function (field) {
        document.getElementById('pkg-plugin_' + field).checked = true;
    });
    ['analytics','advanced_search','api_access','custom_branding','priority_support','export_data','integrations','custom_domains'].forEach(function (field) {
        document.getElementById('pkg-feature_' + field).checked = false;
    });
}

function editPackage(pkg) {
    document.getElementById('packageModalTitle').textContent = 'Paket bearbeiten';
    const monthlyPrice = pkg.price_monthly ?? pkg.price ?? '';
    const yearlyPrice = pkg.price_yearly ?? ((pkg.price ?? null) !== null ? Number(pkg.price || 0) * 12 : '');
    document.getElementById('pkg-id').value = pkg.id || 0;
    document.getElementById('pkg-name').value = pkg.name || '';
    document.getElementById('pkg-slug').value = pkg.slug || '';
    document.getElementById('pkg-price_monthly').value = monthlyPrice;
    document.getElementById('pkg-price_yearly').value = yearlyPrice;
    document.getElementById('pkg-description').value = pkg.description || '';
    document.getElementById('pkg-limit_experts').value = pkg.limit_experts ?? -1;
    document.getElementById('pkg-limit_companies').value = pkg.limit_companies ?? -1;
    document.getElementById('pkg-limit_events').value = pkg.limit_events ?? -1;
    document.getElementById('pkg-limit_speakers').value = pkg.limit_speakers ?? -1;
    document.getElementById('pkg-limit_storage_mb').value = pkg.limit_storage_mb ?? 1000;
    document.getElementById('pkg-sort_order').value = pkg.sort_order || 0;
    document.getElementById('pkg-is_active').checked = !!parseInt(pkg.is_active);
    document.getElementById('pkg-is_featured').checked = !!parseInt(pkg.is_featured);
    ['experts','companies','events','speakers'].forEach(function (field) {
        document.getElementById('pkg-plugin_' + field).checked = !!parseInt(pkg['plugin_' + field]);
    });
    ['analytics','advanced_search','api_access','custom_branding','priority_support','export_data','integrations','custom_domains'].forEach(function (field) {
        document.getElementById('pkg-feature_' + field).checked = !!parseInt(pkg['feature_' + field]);
    });
    new bootstrap.Modal(document.getElementById('packageModal')).show();
}
</script>

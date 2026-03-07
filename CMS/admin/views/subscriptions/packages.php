<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$siteUrl  = defined('SITE_URL') ? SITE_URL : '';
$packages = $data['packages'] ?? [];
$stats    = $data['stats'] ?? [];
?>

<!-- Page Header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Aboverwaltung</div>
                <h2 class="page-title">Abo-Pakete</h2>
            </div>
            <div class="col-auto ms-auto">
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#packageModal" onclick="resetPackageForm()">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                    Neues Paket
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Page Body -->
<div class="page-body">
    <div class="container-xl">

        <?php if (!empty($alert)): ?>
            <div class="alert alert-<?= htmlspecialchars($alert['type']) ?> alert-dismissible" role="alert">
                <div><?= htmlspecialchars($alert['message']) ?></div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></a>
            </div>
        <?php endif; ?>

        <!-- KPI Cards -->
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

        <!-- Packages Grid -->
        <?php if (empty($packages)): ?>
            <div class="card">
                <div class="card-body text-center py-5">
                    <div class="mb-3">
                        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-lg text-muted" width="40" height="40" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3l8 4.5v9l-8 4.5l-8 -4.5v-9l8 -4.5"/><path d="M12 12l8 -4.5"/><path d="M12 12v9"/><path d="M12 12l-8 -4.5"/></svg>
                    </div>
                    <h3 class="text-muted">Noch keine Pakete angelegt</h3>
                    <p class="text-secondary">Erstellen Sie Ihr erstes Abo-Paket, um Ihren Kunden Abonnements anzubieten.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="row row-deck row-cards">
                <?php foreach ($packages as $pkg): ?>
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
                                    <?= number_format((float)$pkg['price'], 2, ',', '.') ?>
                                    <span class="text-secondary fs-5"><?= htmlspecialchars($pkg['currency']) ?></span>
                                </div>
                                <div class="text-secondary mb-3">
                                    <?= (int)$pkg['duration'] ?> Tage Laufzeit
                                    <?php if ($pkg['max_users']): ?> · max. <?= (int)$pkg['max_users'] ?> Benutzer<?php endif; ?>
                                </div>
                                <?php if (!empty($pkg['description'])): ?>
                                    <p class="text-secondary small mb-3"><?= htmlspecialchars($pkg['description']) ?></p>
                                <?php endif; ?>
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

    </div>
</div>

<!-- Package Modal -->
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
                            <label class="form-label required">Preis</label>
                            <div class="input-group">
                                <input type="number" name="price" id="pkg-price" class="form-control" step="0.01" min="0" required>
                                <select name="currency" id="pkg-currency" class="form-select" style="max-width:80px">
                                    <option value="EUR">EUR</option>
                                    <option value="USD">USD</option>
                                    <option value="CHF">CHF</option>
                                    <option value="GBP">GBP</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Laufzeit (Tage)</label>
                            <input type="number" name="duration" id="pkg-duration" class="form-control" min="1" value="30">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Max. Benutzer</label>
                            <input type="number" name="max_users" id="pkg-max_users" class="form-control" min="0" placeholder="unbegrenzt">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Beschreibung</label>
                            <textarea name="description" id="pkg-description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Features (eine pro Zeile)</label>
                            <textarea name="features" id="pkg-features" class="form-control" rows="4" placeholder="Unbegrenzte Seiten&#10;E-Mail-Support&#10;Premium-Themes"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Sortierung</label>
                            <input type="number" name="sort_order" id="pkg-sort_order" class="form-control" min="0" value="0">
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
    document.getElementById('pkg-price').value = '';
    document.getElementById('pkg-currency').value = 'EUR';
    document.getElementById('pkg-duration').value = '30';
    document.getElementById('pkg-max_users').value = '';
    document.getElementById('pkg-description').value = '';
    document.getElementById('pkg-features').value = '';
    document.getElementById('pkg-sort_order').value = '0';
    document.getElementById('pkg-is_active').checked = true;
    document.getElementById('pkg-is_featured').checked = false;
}

function editPackage(pkg) {
    document.getElementById('packageModalTitle').textContent = 'Paket bearbeiten';
    document.getElementById('pkg-id').value = pkg.id || 0;
    document.getElementById('pkg-name').value = pkg.name || '';
    document.getElementById('pkg-slug').value = pkg.slug || '';
    document.getElementById('pkg-price').value = pkg.price || '';
    document.getElementById('pkg-currency').value = pkg.currency || 'EUR';
    document.getElementById('pkg-duration').value = pkg.duration || 30;
    document.getElementById('pkg-max_users').value = pkg.max_users || '';
    document.getElementById('pkg-description').value = pkg.description || '';
    var features = pkg.features_list || [];
    document.getElementById('pkg-features').value = features.join('\n');
    document.getElementById('pkg-sort_order').value = pkg.sort_order || 0;
    document.getElementById('pkg-is_active').checked = !!parseInt(pkg.is_active);
    document.getElementById('pkg-is_featured').checked = !!parseInt(pkg.is_featured);
    new bootstrap.Modal(document.getElementById('packageModal')).show();
}
</script>

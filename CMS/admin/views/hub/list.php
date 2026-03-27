<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$sites = $data['sites'] ?? [];
$total = (int)($data['total'] ?? 0);
$search = (string)($data['search'] ?? '');
$templateOptions = $data['templateOptions'] ?? [];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">Seiten &amp; Beiträge</div>
                <h2 class="page-title">Hub-Sites</h2>
            </div>
            <div class="col-auto ms-auto">
                <a href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites?action=edit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                    Neue Hub Site
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item"><a class="nav-link active" href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites">Content</a></li>
            <li class="nav-item"><a class="nav-link" href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites?action=templates">Templates</a></li>
        </ul>

        <?php
        $alertData = $alert ?? [];
        $alertDismissible = true;
        $alertMarginClass = 'mb-3';
        require __DIR__ . '/../partials/flash-alert.php';
        ?>

        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="font-weight-medium"><?php echo $total; ?> Hub Sites</div>
                        <div class="text-secondary">Mit 5 Layout-Varianten für Routing- und Sammelseiten</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <div class="row w-100 g-2 align-items-center">
                    <div class="col">
                        <span class="text-secondary">Öffentliche Hub-Sites laufen direkt über ihren Slug im Frontend.</span>
                    </div>
                    <div class="col-auto">
                        <input type="text" class="form-control form-control-sm js-hub-sites-search-input" value="<?php echo htmlspecialchars($search); ?>" placeholder="Suchen…"
                               data-search-url="<?php echo htmlspecialchars(SITE_URL . '/admin/hub-sites', ENT_QUOTES); ?>">
                    </div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Template</th>
                            <th>Einträge</th>
                            <th>Aktualisiert</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($sites === []): ?>
                        <?php
                        $emptyStateColspan = 6;
                        $emptyStateMessage = 'Noch keine Hub-Sites vorhanden.';
                        $emptyStateSubtitle = 'Erstellen Sie eine Hub-Site, um Sammelseiten mit eigenem Slug bereitzustellen.';
                        $emptyStateIcon = 'hub';
                        require __DIR__ . '/../partials/empty-table-row.php';
                        ?>
                    <?php else: ?>
                        <?php foreach ($sites as $site): ?>
                            <tr>
                                <td class="text-secondary"><?php echo (int)$site['id']; ?></td>
                                <td>
                                    <a href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites?action=edit&id=<?php echo (int)$site['id']; ?>" class="text-reset font-weight-medium">
                                        <?php echo htmlspecialchars((string)$site['table_name']); ?>
                                    </a>
                                    <div class="text-secondary small">
                                        <code>/<?php echo htmlspecialchars((string)($site['hub_slug'] ?? '')); ?></code>
                                    </div>
                                    <?php if (!empty($site['hub_slug'])): ?>
                                        <div class="mt-2">
                                            <button type="button"
                                                    class="btn btn-outline-secondary btn-sm me-1 js-copy-hub-url"
                                                    data-hub-public-url="<?php echo htmlspecialchars(SITE_URL . '/' . ltrim((string)$site['hub_slug'], '/'), ENT_QUOTES); ?>">
                                                Slug kopieren
                                            </button>
                                            <a href="<?php echo htmlspecialchars(SITE_URL . '/' . ltrim((string)$site['hub_slug'], '/')); ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-primary btn-sm">
                                                Public Site öffnen
                                            </a>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-azure-lt"><?php echo htmlspecialchars((string)($templateOptions[$site['template']] ?? $site['template'])); ?></span></td>
                                <td><?php echo (int)$site['card_count']; ?></td>
                                <td class="text-secondary"><?php echo htmlspecialchars(date('d.m.Y', strtotime((string)$site['updated_at']))); ?></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-ghost-secondary btn-icon btn-sm" data-bs-toggle="dropdown">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/><circle cx="12" cy="5" r="1"/></svg>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item" href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites?action=edit&id=<?php echo (int)$site['id']; ?>">Bearbeiten</a>
                                            <?php if (!empty($site['hub_slug'])): ?>
                                                <a class="dropdown-item" href="<?php echo htmlspecialchars(SITE_URL . '/' . ltrim((string)$site['hub_slug'], '/')); ?>" target="_blank" rel="noopener noreferrer">Public Site öffnen</a>
                                            <?php endif; ?>
                                            <button type="button" class="dropdown-item js-duplicate-hub-site" data-hub-site-id="<?php echo (int)$site['id']; ?>">Duplizieren</button>
                                            <div class="dropdown-divider"></div>
                                            <button type="button" class="dropdown-item text-danger js-delete-hub-site"
                                                    data-hub-site-id="<?php echo (int)$site['id']; ?>"
                                                    data-hub-site-name="<?php echo htmlspecialchars((string)$site['table_name'], ENT_QUOTES); ?>">
                                                Löschen
                                            </button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<form id="deleteForm" method="post" class="d-none">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteId">
</form>
<form id="duplicateForm" method="post" class="d-none">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    <input type="hidden" name="action" value="duplicate">
    <input type="hidden" name="id" id="duplicateId">
</form>

<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/bootstrap.php';

$controller->handleMediaRequest();

$pageTitle = 'Dateien';
$pageKey = 'media';
$pageAssets = [];
$media = $controller->getMediaOverview();
$items = is_array($media['items'] ?? null) ? $media['items'] : ['folders' => [], 'files' => []];
$folders = is_array($items['folders'] ?? null) ? $items['folders'] : [];
$files = is_array($items['files'] ?? null) ? $items['files'] : [];
$mediaSettings = is_array($media['settings'] ?? null) ? $media['settings'] : [];
$memberPath = (string)($media['path'] ?? 'member');

include __DIR__ . '/partials/header.php';
?>
<div class="row g-4">
    <div class="col-xl-4">
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Upload mit FilePond</h3></div>
            <div class="card-body">
                <?php if (!empty($mediaSettings['member_uploads_enabled'])): ?>
                    <input
                        type="file"
                        class="filepond"
                        multiple
                        data-upload-endpoint="<?= htmlspecialchars(SITE_URL) ?>/api/upload"
                        data-upload-token="<?= htmlspecialchars($controller->csrfToken('media_action'), ENT_QUOTES) ?>"
                        data-upload-path="<?= htmlspecialchars($memberPath, ENT_QUOTES) ?>"
                    >
                    <div class="text-secondary small mt-3">
                        Max. Größe: <?= (int)($mediaSettings['member_max_upload_size'] ?? 10) ?> MB · Erlaubte Typen: <?= htmlspecialchars(implode(', ', (array)($mediaSettings['member_allowed_types'] ?? []))) ?>
                    </div>
                <?php else: ?>
                    <div class="text-secondary">Uploads für Mitglieder sind aktuell deaktiviert.</div>
                <?php endif; ?>
            </div>
        </div>
        <form class="card" method="post" action="">
            <input type="hidden" name="action" value="media_folder_create">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($controller->csrfToken('media_action'), ENT_QUOTES) ?>">
            <div class="card-header"><h3 class="card-title">Ordner erstellen</h3></div>
            <div class="card-body">
                <label class="form-label" for="folder_name">Ordnername</label>
                <input class="form-control" id="folder_name" name="folder_name" type="text" required>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">Ordner anlegen</button>
            </div>
        </form>
    </div>
    <div class="col-xl-8">
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Ordner</h3></div>
            <div class="list-group list-group-flush">
                <?php if ($folders === []): ?>
                    <div class="card-body text-secondary">Noch keine Ordner angelegt.</div>
                <?php else: ?>
                    <?php foreach ($folders as $folder): ?>
                        <div class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-medium">📁 <?= htmlspecialchars((string)($folder['name'] ?? 'Ordner')) ?></div>
                                <div class="text-secondary small"><?= htmlspecialchars((string)($folder['path'] ?? '')) ?></div>
                            </div>
                            <div class="text-secondary small"><?= (int)($folder['items_count'] ?? 0) ?> Elemente</div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Dateien</h3></div>
            <div class="table-responsive">
                <table class="table card-table table-vcenter">
                    <thead>
                        <tr>
                            <th>Datei</th>
                            <th>Typ</th>
                            <th>Größe</th>
                            <th>Geändert</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($files === []): ?>
                            <tr><td colspan="5" class="text-secondary">Noch keine Dateien vorhanden.</td></tr>
                        <?php else: ?>
                            <?php foreach ($files as $file): ?>
                                <tr>
                                    <td>
                                        <div class="fw-medium"><a href="<?= htmlspecialchars((string)($file['url'] ?? '#')) ?>" target="_blank" rel="noopener noreferrer"><?= htmlspecialchars((string)($file['name'] ?? 'Datei')) ?></a></div>
                                        <div class="text-secondary small"><?= htmlspecialchars((string)($file['path'] ?? '')) ?></div>
                                    </td>
                                    <td><?= htmlspecialchars(strtoupper((string)($file['type'] ?? ''))) ?></td>
                                    <td><?= number_format(((int)($file['size'] ?? 0)) / 1024, 1, ',', '.') ?> KB</td>
                                    <td><?= !empty($file['modified']) ? htmlspecialchars(date('d.m.Y H:i', (int)$file['modified'])) : '–' ?></td>
                                    <td>
                                        <?php if (!empty($mediaSettings['member_delete_own'])): ?>
                                            <form method="post" action="" onsubmit="return confirm('Datei wirklich löschen?');">
                                                <input type="hidden" name="action" value="media_delete">
                                                <input type="hidden" name="path" value="<?= htmlspecialchars((string)($file['path'] ?? ''), ENT_QUOTES) ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($controller->csrfToken('media_action'), ENT_QUOTES) ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger">Löschen</button>
                                            </form>
                                        <?php endif; ?>
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
<?php include __DIR__ . '/partials/footer.php';

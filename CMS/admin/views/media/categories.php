<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Media – Kategorien View
 *
 * Erwartet: $data (aus MediaModule::getCategoriesData())
 *           $alert, $csrfToken
 */

$categories = $data['categories'] ?? [];
$systemSlugs = ['themes', 'plugins', 'assets', 'fonts', 'dl-manager', 'form-uploads', 'member'];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">Medienverwaltung</div>
                <h2 class="page-title">Kategorien</h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <?php if (!empty($alert)): ?>
            <?php $alertData = $alert; $alertMarginClass = 'mb-3'; require __DIR__ . '/../partials/flash-alert.php'; ?>
        <?php endif; ?>

        <div class="row">
            <!-- Neue Kategorie -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Neue Kategorie</h3></div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                            <input type="hidden" name="action" value="add_category">
                            <div class="mb-3">
                                <label class="form-label" for="catName">Name</label>
                                <input type="text" class="form-control" id="catName" name="name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="catSlug">Slug</label>
                                <input type="text" class="form-control" id="catSlug" name="slug" placeholder="wird automatisch generiert">
                                <small class="form-hint">Nur Kleinbuchstaben, Zahlen und Bindestriche.</small>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Kategorie erstellen</button>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Kategorien-Liste -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Kategorien</h3></div>
                    <?php if (empty($categories)): ?>
                        <div class="card-body">
                            <div class="empty">
                                <p class="empty-title">Keine Kategorien vorhanden</p>
                                <p class="empty-subtitle text-secondary">Erstellen Sie eine neue Kategorie über das Formular links.</p>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-vcenter card-table">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Slug</th>
                                        <th>Dateien</th>
                                        <th>Typ</th>
                                        <th class="w-1"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($categories as $cat): ?>
                                        <?php $isSystem = in_array($cat['slug'], $systemSlugs, true); ?>
                                        <tr>
                                            <td class="fw-bold"><?php echo htmlspecialchars($cat['name']); ?></td>
                                            <td class="text-secondary"><?php echo htmlspecialchars($cat['slug']); ?></td>
                                            <td><?php echo (int)($cat['count'] ?? 0); ?></td>
                                            <td>
                                                <?php if ($isSystem): ?>
                                                    <span class="badge bg-blue-lt">System</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-lt">Benutzerdefiniert</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if (!$isSystem): ?>
                                                    <button class="btn btn-ghost-danger btn-icon btn-sm" title="Löschen" onclick="deleteCat('<?php echo htmlspecialchars(addslashes($cat['slug']), ENT_QUOTES); ?>', '<?php echo htmlspecialchars(addslashes($cat['name']), ENT_QUOTES); ?>')">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                                                    </button>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

    </div>
</div>

<!-- Delete-Formular -->
<form id="deleteCatForm" method="post" class="d-none">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    <input type="hidden" name="action" value="delete_category">
    <input type="hidden" name="slug" id="deleteCatSlug">
</form>

<script>
function deleteCat(slug, name) {
    var submitDelete = function() {
        document.getElementById('deleteCatSlug').value = slug;
        document.getElementById('deleteCatForm').submit();
    };

    if (typeof cmsConfirm === 'function') {
        cmsConfirm({
            title: 'Kategorie löschen',
            message: 'Kategorie ' + name + ' wirklich löschen? Die zugeordneten Dateien bleiben erhalten.',
            confirmText: 'Löschen',
            confirmClass: 'btn-danger',
            onConfirm: submitDelete
        });
        return;
    }

    if (window.confirm('Kategorie ' + name + ' wirklich löschen? Die zugeordneten Dateien bleiben erhalten.')) {
        submitDelete();
    }
}
</script>

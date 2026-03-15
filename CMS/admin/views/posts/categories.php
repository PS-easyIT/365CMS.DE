<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$categories = $data['categories'] ?? [];
$counts = $data['counts'] ?? [];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">Seiten &amp; Beiträge</div>
                <h2 class="page-title">Beitrags-Kategorien</h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php if (!empty($alert)): ?>
            <?php $alertData = $alert; $alertMarginClass = 'mb-3'; require __DIR__ . '/../partials/flash-alert.php'; ?>
        <?php endif; ?>

        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto"><span class="bg-primary text-white avatar">#</span></div>
                            <div class="col">
                                <div class="font-weight-medium"><?php echo (int) ($counts['total'] ?? 0); ?> Kategorien</div>
                                <div class="text-secondary">Gesamt</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-4">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto"><span class="bg-azure text-white avatar">📝</span></div>
                            <div class="col">
                                <div class="font-weight-medium"><?php echo (int) ($counts['assigned_posts'] ?? 0); ?></div>
                                <div class="text-secondary">Beitragszuweisungen</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-cards">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Neue Kategorie</h3></div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                            <input type="hidden" name="action" value="save_category">
                            <input type="hidden" name="cat_id" value="0">
                            <div class="mb-3">
                                <label class="form-label" for="postCategoryName">Name</label>
                                <input type="text" class="form-control" id="postCategoryName" name="cat_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="postCategorySlug">Slug</label>
                                <input type="text" class="form-control" id="postCategorySlug" name="cat_slug" placeholder="wird automatisch generiert">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Kategorie speichern</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Vorhandene Kategorien</h3></div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>Name</th>
                                    <th>Slug</th>
                                    <th>Beiträge</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if ($categories === []): ?>
                                    <tr><td colspan="4" class="text-secondary text-center py-4">Noch keine Kategorien vorhanden.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($categories as $category): ?>
                                    <tr>
                                        <td class="fw-medium"><?php echo htmlspecialchars((string) ($category['name'] ?? ''), ENT_QUOTES); ?></td>
                                        <td><code><?php echo htmlspecialchars((string) ($category['slug'] ?? ''), ENT_QUOTES); ?></code></td>
                                        <td><?php echo (int) ($category['post_count'] ?? 0); ?></td>
                                        <td>
                                            <form method="post" onsubmit="return confirm('Kategorie wirklich löschen? Zugeordnete Beiträge verlieren nur die Kategorie.');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                                                <input type="hidden" name="action" value="delete_category">
                                                <input type="hidden" name="cat_id" value="<?php echo (int) ($category['id'] ?? 0); ?>">
                                                <button type="submit" class="btn btn-ghost-danger btn-sm">Löschen</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

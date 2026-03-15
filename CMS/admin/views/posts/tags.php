<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$tags = $data['tags'] ?? [];
$counts = $data['counts'] ?? [];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">Seiten &amp; Beiträge</div>
                <h2 class="page-title">Beitrags-Tags</h2>
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
                            <div class="col-auto"><span class="bg-indigo text-white avatar">#</span></div>
                            <div class="col">
                                <div class="font-weight-medium"><?php echo (int) ($counts['total'] ?? 0); ?> Tags</div>
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
                            <div class="col-auto"><span class="bg-purple text-white avatar">🏷️</span></div>
                            <div class="col">
                                <div class="font-weight-medium"><?php echo (int) ($counts['assigned_posts'] ?? 0); ?></div>
                                <div class="text-secondary">Tag-Zuweisungen</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row row-cards">
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Neuen Tag anlegen</h3></div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                            <input type="hidden" name="action" value="save_tag">
                            <input type="hidden" name="tag_id" value="0">
                            <div class="mb-3">
                                <label class="form-label" for="postTagName">Name</label>
                                <input type="text" class="form-control" id="postTagName" name="tag_name" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="postTagSlug">Slug</label>
                                <input type="text" class="form-control" id="postTagSlug" name="tag_slug" placeholder="wird automatisch generiert">
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Tag speichern</button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Vorhandene Tags</h3></div>
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
                                <?php if ($tags === []): ?>
                                    <tr><td colspan="4" class="text-secondary text-center py-4">Noch keine Tags vorhanden.</td></tr>
                                <?php endif; ?>
                                <?php foreach ($tags as $tag): ?>
                                    <tr>
                                        <td class="fw-medium"><?php echo htmlspecialchars((string) ($tag['name'] ?? ''), ENT_QUOTES); ?></td>
                                        <td><code><?php echo htmlspecialchars((string) ($tag['slug'] ?? ''), ENT_QUOTES); ?></code></td>
                                        <td><?php echo (int) ($tag['post_count'] ?? 0); ?></td>
                                        <td>
                                            <form method="post" onsubmit="return confirm('Tag wirklich löschen? Zugeordnete Beziehungen werden entfernt.');">
                                                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                                                <input type="hidden" name="action" value="delete_tag">
                                                <input type="hidden" name="tag_id" value="<?php echo (int) ($tag['id'] ?? 0); ?>">
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

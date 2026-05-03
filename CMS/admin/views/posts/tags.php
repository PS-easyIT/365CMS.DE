<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$tags = $data['tags'] ?? [];
$tagOptions = $data['tagOptions'] ?? [];
$counts = $data['counts'] ?? [];
$editTag = $editTag ?? ($data['editTag'] ?? null);
$formValues = is_array($formValues ?? null) ? $formValues : [];
$formAlert = is_array($formAlert ?? null) ? $formAlert : null;
$editTagId = (int) ($formValues['tag_id'] ?? ($editTag['id'] ?? 0));
$editTagName = (string) ($formValues['tag_name'] ?? ($editTag['name'] ?? ''));
$editTagSlug = (string) ($formValues['tag_slug'] ?? ($editTag['slug'] ?? ''));
$isEditing = $editTagId > 0;
$deleteTagOptions = array_values(array_filter(
    $tagOptions,
    static fn(array $tagOption): bool => (int) ($tagOption['id'] ?? 0) > 0
));
$deleteTagSubmitDisabled = count($deleteTagOptions) <= 1;
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
                    <div class="card-header"><h3 class="card-title"><?php echo $isEditing ? 'Tag bearbeiten' : 'Neuen Tag anlegen'; ?></h3></div>
                    <div class="card-body">
                        <?php if (!empty($formAlert)): ?>
                            <?php $alertData = $formAlert; $alertMarginClass = 'mb-3'; $alertDismissible = false; require __DIR__ . '/../partials/flash-alert.php'; ?>
                        <?php endif; ?>

                        <form method="post">
                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                            <input type="hidden" name="action" value="save_tag">
                            <input type="hidden" name="tag_id" value="<?php echo $editTagId; ?>">
                            <div class="mb-3">
                                <label class="form-label" for="postTagName">Name</label>
                                <input type="text" class="form-control" id="postTagName" name="tag_name" value="<?php echo htmlspecialchars($editTagName, ENT_QUOTES); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="postTagSlug">Slug</label>
                                <input type="text" class="form-control" id="postTagSlug" name="tag_slug" value="<?php echo htmlspecialchars($editTagSlug, ENT_QUOTES); ?>" placeholder="wird automatisch generiert">
                            </div>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary flex-fill"><?php echo $isEditing ? 'Tag aktualisieren' : 'Tag speichern'; ?></button>
                                <?php if ($isEditing): ?>
                                    <a href="/admin/post-tags" class="btn btn-outline-secondary">Abbrechen</a>
                                <?php endif; ?>
                            </div>
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
                                            <div class="d-flex gap-2">
                                                <a href="<?php echo htmlspecialchars('/admin/post-tags?edit=' . (int) ($tag['id'] ?? 0), ENT_QUOTES); ?>" class="btn btn-outline-primary btn-sm">Bearbeiten</a>
                                                <form method="post" class="js-delete-tag-form"
                                                      data-tag-id="<?php echo (int) ($tag['id'] ?? 0); ?>"
                                                      data-tag-name="<?php echo htmlspecialchars((string) ($tag['name'] ?? ''), ENT_QUOTES); ?>"
                                                      data-assigned-posts="<?php echo (int) ($tag['post_count'] ?? 0); ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                                                    <input type="hidden" name="action" value="delete_tag">
                                                    <input type="hidden" name="tag_id" value="<?php echo (int) ($tag['id'] ?? 0); ?>">
                                                    <button type="submit" class="btn btn-ghost-danger btn-sm">Löschen</button>
                                                </form>
                                            </div>
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

<div class="modal modal-blur fade" id="deleteTagModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="post" id="deleteTagModalForm">
                <div class="modal-header">
                    <h5 class="modal-title">Tag löschen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken, ENT_QUOTES); ?>">
                    <input type="hidden" name="action" value="delete_tag">
                    <input type="hidden" name="tag_id" id="deleteTagId" value="0">

                    <p class="mb-2">Der Tag <strong id="deleteTagName"></strong> wird gelöscht.</p>
                    <p class="text-secondary mb-0" id="deleteTagHint">Zugeordnete Beziehungen werden dabei entfernt.</p>
                    <p class="mt-3 mb-0 fw-medium d-none" id="deleteTagQuestion">In welchen Ersatztag sollen die zugeordneten Artikel verschoben werden?</p>

                    <div class="mt-3 d-none" id="deleteTagReassignWrap">
                        <label class="form-label" for="replacementTagId">Neuer Tag für betroffene Beiträge</label>
                        <select class="form-select" id="replacementTagId" name="replacement_tag_id">
                            <option value="0">Bitte auswählen…</option>
                            <?php foreach ($deleteTagOptions as $tagOption): ?>
                                <option value="<?php echo (int) ($tagOption['id'] ?? 0); ?>">
                                    <?php echo htmlspecialchars((string) ($tagOption['name'] ?? ''), ENT_QUOTES); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-hint">Beiträge mit diesem Tag werden vor dem Löschen auf den gewählten Ersatztag umgestellt.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-link link-secondary me-auto" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-danger" id="deleteTagSubmit" <?php echo $deleteTagSubmitDisabled ? 'disabled' : ''; ?>>Löschen bestätigen</button>
                </div>
            </form>
        </div>
    </div>
</div>

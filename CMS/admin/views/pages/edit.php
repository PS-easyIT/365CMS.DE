<?php
declare(strict_types=1);

/**
 * Pages Edit View – Seite erstellen / bearbeiten
 *
 * Erwartet:
 *   $editData['page']   – object|null  Bestehende Seite oder null
 *   $editData['isNew']  – bool         Neue Seite?
 *   $csrfToken          – string       CSRF-Token
 *   $alert              – array|null   Erfolgs-/Fehlermeldung
 *
 * @package CMSv2\Admin\Views
 */

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Services\EditorService;

$siteUrl = defined('SITE_URL') ? SITE_URL : '';
$assetsUrl = defined('ASSETS_URL') ? ASSETS_URL : $siteUrl . '/assets';
$page    = $editData['page'] ?? null;
$isNew   = $editData['isNew'] ?? true;
$pageEditorWidth = function_exists('get_option') ? (int)get_option('setting_page_editor_width', 1050) : 1050;
$pageEditorWidth = max(320, min(1600, $pageEditorWidth));
$pageDefaultStatus = function_exists('get_option') ? (string)get_option('setting_page_default_status', 'draft') : 'draft';
if (!in_array($pageDefaultStatus, ['draft', 'published', 'private'], true)) {
    $pageDefaultStatus = 'draft';
}
?>

<!-- Page Header -->
<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Seiten & Beiträge</div>
                <h2 class="page-title"><?= $isNew ? 'Neue Seite' : 'Seite bearbeiten' ?></h2>
            </div>
            <div class="col-auto ms-auto">
                <a href="<?= $siteUrl ?>/admin/pages" class="btn btn-outline-secondary">
                    ← Zurück zur Liste
                </a>
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

        <form method="post" id="pageForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" value="save">
            <?php if (!$isNew): ?>
                <input type="hidden" name="id" value="<?= (int)$page->id ?>">
            <?php endif; ?>

            <?php
            $pageTitleValue = (string)($page->title ?? '');
            $pageSlugValue = (string)($page->slug ?? '');
            $pageStatusValue = (string)($page->status ?? $pageDefaultStatus);
            $pageContentValue = (string)($page->content ?? '');
            $pageMetaTitleValue = (string)($page->meta_title ?? '');
            $pageMetaDescriptionValue = (string)($page->meta_description ?? '');
            $pageFeaturedImageValue = (string)($page->featured_image ?? '');
            $pagePreviewUrl = $siteUrl . '/' . ltrim($pageSlugValue, '/');
            ?>

            <div class="row g-3">
                <div class="col-lg-6 d-flex">
                    <div class="card cms-edit-card cms-edit-top-card h-100 w-100">
                        <div class="card-body">
                            <div class="row g-3 align-items-end mb-3">
                                <div class="col-md-8">
                                     <label class="form-label required" for="pageTitle">Titel</label>
                                     <input type="text" name="title" id="pageTitle" class="form-control form-control-lg"
                                           placeholder="Seitentitel"
                                           value="<?= htmlspecialchars($pageTitleValue) ?>" required>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label d-block">&nbsp;</label>
                                    <label class="form-check mb-0 mt-md-2">
                                        <input type="checkbox" name="hide_title" value="1"
                                               class="form-check-input"
                                               id="hideTitle"
                                               <?= !empty($page->hide_title) ? 'checked' : '' ?>>
                                        <span class="form-check-label">Titel ausblenden</span>
                                    </label>
                                </div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label" for="pageSlug">Slug</label>
                                <div class="input-group">
                                    <span class="input-group-text">/</span>
                                    <input type="text" name="slug" id="pageSlug" class="form-control"
                                           placeholder="seiten-url"
                                           value="<?= htmlspecialchars($pageSlugValue) ?>">
                                </div>
                                <small class="form-hint">Wird automatisch aus dem Titel generiert, wenn leer.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 d-flex">
                    <div class="card cms-edit-card cms-edit-top-card h-100 w-100">
                        <div class="card-header">
                            <h3 class="card-title">Veröffentlichen</h3>
                        </div>
                        <div class="card-body flex-fill">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select" id="pageStatusSelect">
                                    <option value="draft"<?= $pageStatusValue === 'draft' ? ' selected' : '' ?>>Entwurf</option>
                                    <option value="published"<?= $pageStatusValue === 'published' ? ' selected' : '' ?>>Veröffentlicht</option>
                                    <option value="private"<?= $pageStatusValue === 'private' ? ' selected' : '' ?>>Privat</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-footer d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <?= $isNew ? 'Seite erstellen' : 'Speichern' ?>
                            </button>
                            <?php if (!$isNew): ?>
                                <a href="<?= htmlspecialchars($pagePreviewUrl) ?>"
                                   class="btn btn-outline-secondary" target="_blank" rel="noopener">
                                    Ansehen
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card cms-edit-card cms-editor-card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Inhalt</h3>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($useEditorJs)): ?>
                            <div class="editorjs-wrap editorjs-wrap--page cms-editor-live-wrap"
                                   style="--editorjs-content-width:<?= (int)$pageEditorWidth ?>px; --editorjs-content-padding-x:50px;">
                                <div id="editorjs" class="editorjs-holder cms-editor-live-holder" style="min-height: 300px;"></div>
                            </div>
                            <input type="hidden" name="content" id="editorContent"
                                   value="<?= htmlspecialchars($pageContentValue) ?>">
                            <?php else: ?>
                                <?= EditorService::getInstance()->render('content', $pageContentValue, [
                                    'height' => '420',
                                    'context' => 'page',
                                    'content_width' => $pageEditorWidth,
                                    'content_padding_x' => 50,
                                ]) ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card cms-edit-card h-100">
                        <div class="card-header">
                            <h3 class="card-title">SEO</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Meta-Titel</label>
                                <input type="text" name="meta_title" class="form-control" id="pageMetaTitle"
                                       placeholder="SEO-Titel (Standard: Seitentitel)"
                                       maxlength="70"
                                       value="<?= htmlspecialchars($pageMetaTitleValue) ?>">
                                <small class="form-hint"><span id="metaTitleCount">0</span>/70 Zeichen</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Meta-Beschreibung</label>
                                <textarea name="meta_description" class="form-control" rows="3" id="pageMetaDescription"
                                          placeholder="Kurze Beschreibung für Suchmaschinen…"
                                          maxlength="160"><?= htmlspecialchars($pageMetaDescriptionValue) ?></textarea>
                                <small class="form-hint"><span id="metaDescriptionCount">0</span>/160 Zeichen</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card cms-edit-card h-100">
                        <div class="card-header">
                            <h3 class="card-title">Vorschaubild</h3>
                        </div>
                        <div class="card-body">
                            <div id="featuredImagePreview">
                                <?php if ($pageFeaturedImageValue !== ''): ?>
                                    <img src="<?= htmlspecialchars($pageFeaturedImageValue) ?>" alt="" class="img-fluid rounded mb-2">
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="featured_image" id="featuredImageInput"
                                   value="<?= htmlspecialchars($pageFeaturedImageValue) ?>">
                            <div id="featuredImageEmpty" class="text-secondary small <?= $pageFeaturedImageValue !== '' ? 'd-none' : '' ?>">Noch kein Vorschaubild ausgewählt.</div>
                            <div class="btn-list">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="featuredImageBtn">
                                    Bild auswählen
                                </button>
                                <button type="button" class="btn btn-sm btn-outline-secondary d-none" id="featuredImageRemove">
                                    Entfernen
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card cms-edit-card h-100">
                        <div class="card-header">
                            <h3 class="card-title">Analyse</h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-secondary small mb-1">Titel</div>
                                        <div class="h3 m-0" id="pageTitleCount">0</div>
                                        <div class="text-secondary small">Zeichen</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-secondary small mb-1">Slug</div>
                                        <div class="h3 m-0" id="pageSlugCount">0</div>
                                        <div class="text-secondary small">Zeichen</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <div class="text-secondary small mb-1">Status</div>
                                <span class="badge bg-azure-lt text-azure" id="pageStatusBadge">Entwurf</span>
                            </div>

                            <div>
                                <div class="text-secondary small mb-1">Vorschau-URL</div>
                                <div class="form-control-plaintext text-break small" id="pagePreviewUrl"><?= htmlspecialchars($pagePreviewUrl) ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card cms-edit-card h-100">
                        <div class="card-header">
                            <h3 class="card-title">Hinweise</h3>
                        </div>
                        <div class="card-body">
                            <ul class="mb-0 text-secondary small ps-3">
                                <li class="mb-2">Halte den Titel prägnant und eindeutig.</li>
                                <li class="mb-2">Ein sauberer Slug verbessert Lesbarkeit und SEO.</li>
                                <li class="mb-2">Meta-Titel idealerweise unter 70 Zeichen halten.</li>
                                <li>Die Meta-Beschreibung sollte kurz und klickstark formuliert sein.</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <?php
        $pickerModalId = 'pageFeaturedImageModal';
        $pickerOpenButtonId = 'featuredImageBtn';
        $pickerInputId = 'featuredImageInput';
        $pickerPreviewContainerId = 'featuredImagePreview';
        $pickerRemoveButtonId = 'featuredImageRemove';
        $pickerEmptyStateId = 'featuredImageEmpty';
        $pickerTitleInputId = 'pageTitle';
        $pickerSlugInputId = 'pageSlug';
        $pickerDialogTitle = 'Seitenbild auswählen';
        require __DIR__ . '/../partials/featured-image-picker.php';
        ?>

    </div><!-- /.container-xl -->
</div><!-- /.page-body -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Featured Image Remove
    const removeBtn = document.getElementById('featuredImageRemove');
    const imageInput = document.getElementById('featuredImageInput');
    const preview = document.getElementById('featuredImagePreview');
    const titleInput = document.querySelector('input[name="title"]');
    const slugInput = document.querySelector('input[name="slug"]');
    const metaTitleInput = document.getElementById('pageMetaTitle');
    const metaDescriptionInput = document.getElementById('pageMetaDescription');
    const statusSelect = document.getElementById('pageStatusSelect');
    const titleCount = document.getElementById('pageTitleCount');
    const slugCount = document.getElementById('pageSlugCount');
    const metaTitleCount = document.getElementById('metaTitleCount');
    const metaDescriptionCount = document.getElementById('metaDescriptionCount');
    const previewUrl = document.getElementById('pagePreviewUrl');
    const statusBadge = document.getElementById('pageStatusBadge');

    const statusMap = {
        draft: { label: 'Entwurf', className: 'badge bg-yellow-lt text-yellow' },
        published: { label: 'Veröffentlicht', className: 'badge bg-green-lt text-green' },
        private: { label: 'Privat', className: 'badge bg-azure-lt text-azure' }
    };

    const updateCounts = function () {
        if (titleInput && titleCount) {
            titleCount.textContent = String(titleInput.value.length);
        }

        if (slugInput && slugCount) {
            slugCount.textContent = String(slugInput.value.length);
        }

        if (metaTitleInput && metaTitleCount) {
            metaTitleCount.textContent = String(metaTitleInput.value.length);
        }

        if (metaDescriptionInput && metaDescriptionCount) {
            metaDescriptionCount.textContent = String(metaDescriptionInput.value.length);
        }

        if (previewUrl && slugInput) {
            const slug = slugInput.value.trim().replace(/^\/+/, '');
            previewUrl.textContent = slug ? '<?= htmlspecialchars($siteUrl) ?>/' + slug : '<?= htmlspecialchars($siteUrl) ?>/';
        }

        if (statusSelect && statusBadge) {
            const currentStatus = statusMap[statusSelect.value] || statusMap.draft;
            statusBadge.className = currentStatus.className;
            statusBadge.textContent = currentStatus.label;
        }
    };

    if (imageInput && imageInput.value) {
        removeBtn?.classList.remove('d-none');
    }

    removeBtn?.addEventListener('click', function() {
        imageInput.value = '';
        preview.innerHTML = '';
        const emptyState = document.getElementById('featuredImageEmpty');
        emptyState?.classList.remove('d-none');
        this.classList.add('d-none');
    });

    [titleInput, slugInput, metaTitleInput, metaDescriptionInput, statusSelect].forEach(function(el) {
        el?.addEventListener('input', updateCounts);
        el?.addEventListener('change', updateCounts);
    });

    updateCounts();
});
</script>

<?php if (!empty($useEditorJs)): ?>
<!-- EditorJS wird via $inlineJs in footer.php initialisiert -->
<?php endif; ?>

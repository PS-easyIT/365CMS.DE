<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Services\EditorService;

/**
 * Posts – Edit / Create View
 *
 * Erwartet: $data (aus PostsModule::getEditData())
 *           $csrfToken
 */

$post       = $data['post'] ?? null;
$isNew      = $data['isNew'] ?? true;
$categories = $data['categories'] ?? [];
$postEditorWidth = function_exists('get_option') ? (int)get_option('setting_post_editor_width', 750) : 750;
$postEditorWidth = max(320, min(1600, $postEditorWidth));
$postDefaultStatus = function_exists('get_option') ? (string)get_option('setting_post_default_status', 'draft') : 'draft';
if (!in_array($postDefaultStatus, ['draft', 'published'], true)) {
    $postDefaultStatus = 'draft';
}

$title      = htmlspecialchars($post['title'] ?? '');
$slug       = htmlspecialchars($post['slug'] ?? '');
$content    = $post['content'] ?? '';
$excerpt    = htmlspecialchars($post['excerpt'] ?? '');
$status     = $post['status'] ?? $postDefaultStatus;
$categoryId = (int)($post['category_id'] ?? 0);
$featuredImg = htmlspecialchars($post['featured_image'] ?? '');
$metaTitle  = htmlspecialchars($post['meta_title'] ?? '');
$metaDesc   = htmlspecialchars($post['meta_description'] ?? '');
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/posts" class="btn btn-ghost-secondary btn-sm me-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6"/></svg>
                    Zurück
                </a>
            </div>
            <div class="col">
                <div class="page-pretitle">Beiträge</div>
                <h2 class="page-title"><?php echo $isNew ? 'Neuer Beitrag' : 'Beitrag bearbeiten'; ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <form method="post" action="<?php echo htmlspecialchars(SITE_URL); ?>/admin/posts" id="postForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="save">
            <?php if (!$isNew): ?>
                <input type="hidden" name="id" value="<?php echo (int)$post['id']; ?>">
            <?php endif; ?>

            <?php
            $postTitleValue = (string)($post['title'] ?? '');
            $postSlugValue = (string)($post['slug'] ?? '');
            $postContentValue = (string)$content;
            $postExcerptValue = (string)($post['excerpt'] ?? '');
            $postStatusValue = (string)$status;
            $postMetaTitleValue = (string)($post['meta_title'] ?? '');
            $postMetaDescriptionValue = (string)($post['meta_description'] ?? '');
            $postFeaturedImageValue = (string)($post['featured_image'] ?? '');
            $postPreviewUrl = htmlspecialchars(SITE_URL) . '/blog/' . ltrim($postSlugValue, '/');
            $selectedCategoryName = 'Keine Kategorie';
            foreach ($categories as $cat) {
                if ($categoryId === (int)($cat['id'] ?? 0)) {
                    $selectedCategoryName = (string)($cat['name'] ?? 'Keine Kategorie');
                    break;
                }
            }
            ?>

            <div class="row g-3">
                <div class="col-lg-6 d-flex">
                    <div class="card cms-edit-card cms-edit-top-card h-100 w-100">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label required" for="title">Titel</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($postTitleValue); ?>" required>
                            </div>
                            <div class="mb-0">
                                <label class="form-label" for="slug">Slug</label>
                                <div class="input-group">
                                    <span class="input-group-text">/blog/</span>
                                    <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($postSlugValue); ?>" placeholder="wird automatisch generiert">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 d-flex">
                    <div class="card cms-edit-card cms-edit-top-card h-100 w-100">
                        <div class="card-header">
                            <h3 class="card-title">Veröffentlichung</h3>
                        </div>
                        <div class="card-body flex-fill">
                            <div class="mb-3">
                                <label class="form-label" for="status">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft" <?php if ($postStatusValue === 'draft') echo 'selected'; ?>>Entwurf</option>
                                    <option value="published" <?php if ($postStatusValue === 'published') echo 'selected'; ?>>Veröffentlicht</option>
                                </select>
                            </div>
                            <div class="mb-0">
                                <label class="form-label" for="categoryId">Kategorie</label>
                                <select class="form-select" id="categoryId" name="category_id">
                                    <option value="0">Keine Kategorie</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo (int)$cat['id']; ?>" <?php if ($categoryId === (int)$cat['id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($cat['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="card-footer d-flex gap-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <?php echo $isNew ? 'Erstellen' : 'Aktualisieren'; ?>
                            </button>
                            <?php if (!$isNew): ?>
                                <a href="<?php echo $postPreviewUrl; ?>" class="btn btn-outline-secondary" target="_blank" rel="noopener noreferrer" title="Vorschau">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/></svg>
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
                            <div class="editorjs-wrap editorjs-wrap--post cms-editor-live-wrap"
                                 style="--editorjs-content-width:<?php echo (int)$postEditorWidth; ?>px; --editorjs-content-padding-x:50px; --editorjs-content-width-expanded:1100px;">
                                <div id="editorjs" class="editorjs-holder cms-editor-live-holder" style="min-height:300px;"></div>
                            </div>
                            <input type="hidden" name="content" id="contentInput" value="<?php echo htmlspecialchars($postContentValue); ?>">
                            <?php else: ?>
                                <?php echo EditorService::getInstance()->render('content', $postContentValue, [
                                    'height' => '420',
                                    'context' => 'post',
                                    'content_width' => $postEditorWidth,
                                    'content_width_expanded' => 1100,
                                    'content_padding_x' => 50,
                                ]); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 col-xl-3">
                    <div class="card cms-edit-card h-100">
                        <div class="card-header">
                            <h3 class="card-title">Kurzfassung</h3>
                        </div>
                        <div class="card-body">
                            <textarea class="form-control" id="excerpt" name="excerpt" rows="7" placeholder="Kurze Zusammenfassung für Übersichten…"><?php echo htmlspecialchars($postExcerptValue); ?></textarea>
                            <span class="form-hint"><span id="excerptCount">0</span> Zeichen</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 col-xl-3">
                    <div class="card cms-edit-card h-100">
                        <div class="card-header">
                            <h3 class="card-title">SEO</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label" for="metaTitle">Meta-Titel</label>
                                <input type="text" class="form-control" id="metaTitle" name="meta_title" value="<?php echo htmlspecialchars($postMetaTitleValue); ?>" maxlength="70">
                                <span class="form-hint"><span id="metaTitleCount">0</span>/70 Zeichen</span>
                            </div>
                            <div class="mb-0">
                                <label class="form-label" for="metaDesc">Meta-Beschreibung</label>
                                <textarea class="form-control" id="metaDesc" name="meta_description" rows="4" maxlength="160"><?php echo htmlspecialchars($postMetaDescriptionValue); ?></textarea>
                                <span class="form-hint"><span id="metaDescCount">0</span>/160 Zeichen</span>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 col-xl-3">
                    <div class="card cms-edit-card h-100">
                        <div class="card-header">
                            <h3 class="card-title">Beitragsbild</h3>
                        </div>
                        <div class="card-body">
                            <div id="featuredPreview" class="mb-2 <?php echo $postFeaturedImageValue !== '' ? '' : 'd-none'; ?>">
                                <img src="<?php echo htmlspecialchars($postFeaturedImageValue); ?>" class="img-fluid rounded" id="featuredImg" alt="Beitragsbild">
                            </div>
                            <div id="featuredEmpty" class="text-secondary small mb-2 <?php echo $postFeaturedImageValue !== '' ? 'd-none' : ''; ?>">Noch kein Beitragsbild ausgewählt.</div>
                            <input type="hidden" name="featured_image" id="featuredInput" value="<?php echo htmlspecialchars($postFeaturedImageValue); ?>">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="btnSelectImage">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8h.01"/><path d="M3 6a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v12a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3v-12z"/><path d="M6 18l3.5 -4a4 4 0 0 1 5 -.5l5.5 4.5"/></svg>
                                    Bild wählen
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm <?php echo $postFeaturedImageValue !== '' ? '' : 'd-none'; ?>" id="btnRemoveImage">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12"/><path d="M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6 col-xl-3">
                    <div class="card cms-edit-card h-100">
                        <div class="card-header">
                            <h3 class="card-title">Analyse</h3>
                        </div>
                        <div class="card-body">
                            <div class="row g-3 mb-3">
                                <div class="col-6">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-secondary small mb-1">Titel</div>
                                        <div class="h3 m-0" id="postTitleCount">0</div>
                                        <div class="text-secondary small">Zeichen</div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="border rounded p-3 h-100">
                                        <div class="text-secondary small mb-1">Slug</div>
                                        <div class="h3 m-0" id="postSlugCount">0</div>
                                        <div class="text-secondary small">Zeichen</div>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-2">
                                <div class="text-secondary small mb-1">Status</div>
                                <span class="badge bg-yellow-lt text-yellow" id="postStatusBadge">Entwurf</span>
                            </div>

                            <div class="mb-2">
                                <div class="text-secondary small mb-1">Kategorie</div>
                                <div class="small" id="postCategoryLabel"><?php echo htmlspecialchars($selectedCategoryName); ?></div>
                            </div>

                            <div>
                                <div class="text-secondary small mb-1">Vorschau-URL</div>
                                <div class="form-control-plaintext text-break small" id="postPreviewUrl"><?php echo htmlspecialchars($postPreviewUrl); ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </form>

        <?php
        $pickerModalId = 'postFeaturedImageModal';
        $pickerOpenButtonId = 'btnSelectImage';
        $pickerInputId = 'featuredInput';
        $pickerPreviewContainerId = 'featuredPreview';
        $pickerRemoveButtonId = 'btnRemoveImage';
        $pickerEmptyStateId = 'featuredEmpty';
        $pickerTitleInputId = 'title';
        $pickerSlugInputId = 'slug';
        $pickerDialogTitle = 'Beitragsbild auswählen';
        require __DIR__ . '/../partials/featured-image-picker.php';
        ?>
    </div>
</div>

<script>
// Featured Image Remove + Analyse
(function() {
    var btnRemove = document.getElementById('btnRemoveImage');
    var featuredInput = document.getElementById('featuredInput');
    var featuredPreview = document.getElementById('featuredPreview');
    var featuredEmpty = document.getElementById('featuredEmpty');
    var titleInput = document.getElementById('title');
    var slugInput = document.getElementById('slug');
    var excerptInput = document.getElementById('excerpt');
    var metaTitleInput = document.getElementById('metaTitle');
    var metaDescInput = document.getElementById('metaDesc');
    var statusInput = document.getElementById('status');
    var categoryInput = document.getElementById('categoryId');

    var titleCount = document.getElementById('postTitleCount');
    var slugCount = document.getElementById('postSlugCount');
    var excerptCount = document.getElementById('excerptCount');
    var metaTitleCount = document.getElementById('metaTitleCount');
    var metaDescCount = document.getElementById('metaDescCount');
    var statusBadge = document.getElementById('postStatusBadge');
    var categoryLabel = document.getElementById('postCategoryLabel');
    var previewUrl = document.getElementById('postPreviewUrl');

    var statusMap = {
        draft: { label: 'Entwurf', className: 'badge bg-yellow-lt text-yellow' },
        published: { label: 'Veröffentlicht', className: 'badge bg-green-lt text-green' }
    };

    var updateAnalysis = function() {
        if (titleInput && titleCount) {
            titleCount.textContent = String(titleInput.value.length);
        }

        if (slugInput && slugCount) {
            slugCount.textContent = String(slugInput.value.length);
        }

        if (excerptInput && excerptCount) {
            excerptCount.textContent = String(excerptInput.value.length);
        }

        if (metaTitleInput && metaTitleCount) {
            metaTitleCount.textContent = String(metaTitleInput.value.length);
        }

        if (metaDescInput && metaDescCount) {
            metaDescCount.textContent = String(metaDescInput.value.length);
        }

        if (statusInput && statusBadge) {
            var statusConfig = statusMap[statusInput.value] || statusMap.draft;
            statusBadge.className = statusConfig.className;
            statusBadge.textContent = statusConfig.label;
        }

        if (categoryInput && categoryLabel) {
            var selectedOption = categoryInput.options[categoryInput.selectedIndex];
            categoryLabel.textContent = selectedOption ? selectedOption.text : 'Keine Kategorie';
        }

        if (previewUrl && slugInput) {
            var slug = slugInput.value.trim().replace(/^\/+/, '');
            previewUrl.textContent = slug ? '<?php echo htmlspecialchars(SITE_URL); ?>/blog/' + slug : '<?php echo htmlspecialchars(SITE_URL); ?>/blog/';
        }
    };

    if (btnRemove) {
        btnRemove.addEventListener('click', function() {
            if (featuredInput) {
                featuredInput.value = '';
            }
            if (featuredPreview) {
                featuredPreview.classList.add('d-none');
                featuredPreview.innerHTML = '';
            }
            if (featuredEmpty) {
                featuredEmpty.classList.remove('d-none');
            }
            btnRemove.classList.add('d-none');
        });
    }

    [titleInput, slugInput, excerptInput, metaTitleInput, metaDescInput, statusInput, categoryInput].forEach(function(el) {
        if (!el) {
            return;
        }
        el.addEventListener('input', updateAnalysis);
        el.addEventListener('change', updateAnalysis);
    });

    updateAnalysis();
})();
</script>

<?php if (!empty($useEditorJs)): ?>
<!-- EditorJS wird via $inlineJs in footer.php initialisiert -->
<?php endif; ?>

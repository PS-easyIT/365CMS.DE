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
$seoMeta = $editData['seoMeta'] ?? [];
$seoTemplateSettings = \CMS\Services\SeoAnalysisService::getInstance()->getSettings();
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
            $pageFocusKeyphraseValue = (string)($seoMeta['focus_keyphrase'] ?? '');
            $pageCanonicalUrlValue = (string)($seoMeta['canonical_url'] ?? '');
            $pageRobotsIndexValue = !array_key_exists('robots_index', $seoMeta) || !empty($seoMeta['robots_index']);
            $pageRobotsFollowValue = !array_key_exists('robots_follow', $seoMeta) || !empty($seoMeta['robots_follow']);
            $pageOgTitleValue = (string)($seoMeta['og_title'] ?? '');
            $pageOgDescriptionValue = (string)($seoMeta['og_description'] ?? '');
            $pageOgImageValue = (string)($seoMeta['og_image'] ?? '');
            $pageTwitterTitleValue = (string)($seoMeta['twitter_title'] ?? '');
            $pageTwitterDescriptionValue = (string)($seoMeta['twitter_description'] ?? '');
            $pageTwitterImageValue = (string)($seoMeta['twitter_image'] ?? '');
            $pageTwitterCardValue = (string)($seoMeta['twitter_card'] ?? 'summary_large_image');
            $pageSchemaTypeValue = (string)($seoMeta['schema_type'] ?? 'WebPage');
            $pageSitemapPriorityValue = (string)($seoMeta['sitemap_priority'] ?? '');
            $pageSitemapChangefreqValue = (string)($seoMeta['sitemap_changefreq'] ?? 'weekly');
            $pageHreflangGroupValue = (string)($seoMeta['hreflang_group'] ?? '');
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
                                <label class="form-label" for="pageFocusKeyphrase">Fokus-Keyphrase</label>
                                <input type="text" name="focus_keyphrase" class="form-control" id="pageFocusKeyphrase"
                                       placeholder="z. B. Mitgliedschaft B2B-Netzwerk"
                                       value="<?= htmlspecialchars($pageFocusKeyphraseValue) ?>">
                            </div>
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
                            <div id="pagePublishWarning" class="alert alert-warning mb-0" role="alert"></div>
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
                            <h3 class="card-title">SEO-Score</h3>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <div class="h1 m-0" id="pageSeoScoreLabel">0</div>
                                <span class="badge bg-danger-lt text-danger" id="pageSeoScoreBadge">Rot</span>
                            </div>
                            <div class="progress progress-sm mb-3">
                                <div class="progress-bar bg-danger" id="pageSeoScoreBar" style="width:0%"></div>
                            </div>
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
                                <div class="mt-2"><span class="badge bg-success-lt text-success" id="pageSlugState">Slug gültig</span></div>
                            </div>
                            <div class="mt-3" id="pageSeoRules"></div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="card cms-edit-card h-100">
                        <div class="card-header"><h3 class="card-title">SERP-Vorschau</h3></div>
                        <div class="card-body">
                            <div class="border rounded p-3 bg-light">
                                <div id="pageSerpTitle" class="fw-semibold text-primary mb-1"><?= htmlspecialchars($pageMetaTitleValue ?: $pageTitleValue) ?></div>
                                <div id="pageSerpUrl" class="small text-success mb-1"><?= htmlspecialchars($pagePreviewUrl) ?></div>
                                <div id="pageSerpDescription" class="small text-secondary"><?= htmlspecialchars($pageMetaDescriptionValue ?: 'Meta-Beschreibung wird automatisch aus dem ersten Absatz erzeugt.') ?></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="card cms-edit-card h-100">
                        <div class="card-header"><h3 class="card-title">Social Preview</h3></div>
                        <div class="card-body">
                            <div class="border rounded overflow-hidden bg-light">
                                <img id="pageSocialImage" src="<?= htmlspecialchars($pageOgImageValue !== '' ? $pageOgImageValue : $pageFeaturedImageValue) ?>" alt="" style="display:<?= ($pageOgImageValue !== '' || $pageFeaturedImageValue !== '') ? 'block' : 'none' ?>; width:100%; height:160px; object-fit:cover;">
                                <div class="p-3">
                                    <div class="text-uppercase text-secondary small mb-1">facebook / x</div>
                                    <div id="pageSocialTitle" class="fw-semibold mb-1"><?= htmlspecialchars($pageMetaTitleValue ?: $pageTitleValue) ?></div>
                                    <div id="pageSocialDescription" class="small text-secondary"><?= htmlspecialchars($pageMetaDescriptionValue ?: 'Social-Vorschau aus SEO-Daten') ?></div>
                                </div>
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

                <div class="col-12">
                    <div class="card cms-edit-card">
                        <div class="card-header"><h3 class="card-title">Erweitertes SEO &amp; Social</h3></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-lg-4">
                                    <label class="form-label" for="pageCanonicalUrl">Kanonische URL</label>
                                    <input type="text" name="canonical_url" id="pageCanonicalUrl" class="form-control" value="<?= htmlspecialchars($pageCanonicalUrlValue) ?>" placeholder="Automatisch self-referencing, wenn leer">
                                </div>
                                <div class="col-lg-2">
                                    <label class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" name="robots_index" value="1" <?= $pageRobotsIndexValue ? 'checked' : '' ?>>
                                        <span class="form-check-label">index</span>
                                    </label>
                                </div>
                                <div class="col-lg-2">
                                    <label class="form-check mt-4">
                                        <input class="form-check-input" type="checkbox" name="robots_follow" value="1" <?= $pageRobotsFollowValue ? 'checked' : '' ?>>
                                        <span class="form-check-label">follow</span>
                                    </label>
                                </div>
                                <div class="col-lg-4">
                                    <label class="form-label" for="pageSchemaType">Schema-Typ</label>
                                    <select class="form-select" name="schema_type" id="pageSchemaType">
                                        <?php foreach (['WebPage', 'FAQPage', 'HowTo', 'Person', 'Event', 'Article'] as $type): ?>
                                            <option value="<?= htmlspecialchars($type) ?>" <?= $pageSchemaTypeValue === $type ? 'selected' : '' ?>><?= htmlspecialchars($type) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-lg-6">
                                    <label class="form-label" for="pageOgTitle">OG-Titel</label>
                                    <input type="text" class="form-control" id="pageOgTitle" name="og_title" value="<?= htmlspecialchars($pageOgTitleValue) ?>">
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-label" for="pageOgImage">OG-Bild</label>
                                    <input type="text" class="form-control" id="pageOgImage" name="og_image" value="<?= htmlspecialchars($pageOgImageValue) ?>">
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-label" for="pageOgDescription">OG-Beschreibung</label>
                                    <textarea class="form-control" id="pageOgDescription" name="og_description" rows="3"><?= htmlspecialchars($pageOgDescriptionValue) ?></textarea>
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-label" for="pageTwitterTitle">Twitter-/X-Titel</label>
                                    <input type="text" class="form-control" id="pageTwitterTitle" name="twitter_title" value="<?= htmlspecialchars($pageTwitterTitleValue) ?>">
                                    <label class="form-label mt-3" for="pageTwitterCard">Twitter Card</label>
                                    <select class="form-select" id="pageTwitterCard" name="twitter_card">
                                        <?php foreach (['summary_large_image', 'summary'] as $card): ?>
                                            <option value="<?= htmlspecialchars($card) ?>" <?= $pageTwitterCardValue === $card ? 'selected' : '' ?>><?= htmlspecialchars($card) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-label" for="pageTwitterDescription">Twitter-/X-Beschreibung</label>
                                    <textarea class="form-control" id="pageTwitterDescription" name="twitter_description" rows="3"><?= htmlspecialchars($pageTwitterDescriptionValue) ?></textarea>
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-label" for="pageTwitterImage">Twitter-/X-Bild</label>
                                    <input type="text" class="form-control" id="pageTwitterImage" name="twitter_image" value="<?= htmlspecialchars($pageTwitterImageValue) ?>">
                                </div>
                                <div class="col-lg-3">
                                    <label class="form-label" for="pageSitemapPriority">Sitemap Priority</label>
                                    <input type="text" class="form-control" id="pageSitemapPriority" name="sitemap_priority" value="<?= htmlspecialchars($pageSitemapPriorityValue) ?>">
                                </div>
                                <div class="col-lg-3">
                                    <label class="form-label" for="pageSitemapChangefreq">Sitemap Changefreq</label>
                                    <select class="form-select" id="pageSitemapChangefreq" name="sitemap_changefreq">
                                        <?php foreach (['always', 'daily', 'weekly', 'monthly', 'yearly'] as $freq): ?>
                                            <option value="<?= htmlspecialchars($freq) ?>" <?= $pageSitemapChangefreqValue === $freq ? 'selected' : '' ?>><?= htmlspecialchars($freq) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-label" for="pageHreflangGroup">hreflang-Gruppe</label>
                                    <input type="text" class="form-control" id="pageHreflangGroup" name="hreflang_group" value="<?= htmlspecialchars($pageHreflangGroupValue) ?>">
                                </div>
                            </div>
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

<script src="<?= htmlspecialchars($siteUrl) ?>/assets/js/admin-seo-editor.js"></script>
<script>
(function () {
    if (!window.cmsSeoEditor) {
        return;
    }

    window.cmsSeoEditor.init({
        formId: 'pageForm',
        titleId: 'pageTitle',
        slugId: 'pageSlug',
        metaTitleId: 'pageMetaTitle',
        metaDescId: 'pageMetaDescription',
        focusKeyphraseId: 'pageFocusKeyphrase',
        ogImageId: 'pageOgImage',
        featuredImageId: 'featuredImageInput',
        statusId: 'pageStatusSelect',
        contentInputId: 'editorContent',
        editorContainerId: 'editorjs',
        serpTitleId: 'pageSerpTitle',
        serpUrlId: 'pageSerpUrl',
        serpDescriptionId: 'pageSerpDescription',
        scoreBarId: 'pageSeoScoreBar',
        scoreLabelId: 'pageSeoScoreLabel',
        scoreBadgeId: 'pageSeoScoreBadge',
        scoreRulesId: 'pageSeoRules',
        socialTitleId: 'pageSocialTitle',
        socialDescriptionId: 'pageSocialDescription',
        socialImageId: 'pageSocialImage',
        publishWarningId: 'pagePublishWarning',
        slugStateId: 'pageSlugState',
        previewBaseUrl: '<?= htmlspecialchars($siteUrl) ?>/',
        siteName: '<?= htmlspecialchars((string)SITE_NAME, ENT_QUOTES) ?>',
        siteTitleFormat: '<?= htmlspecialchars((string)($seoTemplateSettings['site_title_format'] ?? '%%title%% %%sep%% %%sitename%%'), ENT_QUOTES) ?>',
        titleSeparator: '<?= htmlspecialchars((string)($seoTemplateSettings['title_separator'] ?? '|'), ENT_QUOTES) ?>',
        minWords: <?= (int)($seoTemplateSettings['analysis_min_words'] ?? 300) ?>,
        maxSentenceWords: <?= (int)($seoTemplateSettings['analysis_sentence_words'] ?? 24) ?>,
        maxParagraphWords: <?= (int)($seoTemplateSettings['analysis_paragraph_words'] ?? 120) ?>,
        fallbackImage: '<?= htmlspecialchars($pageFeaturedImageValue, ENT_QUOTES) ?>'
    });
})();
</script>

<?php if (!empty($useEditorJs)): ?>
<!-- EditorJS wird via $inlineJs in footer.php initialisiert -->
<?php endif; ?>

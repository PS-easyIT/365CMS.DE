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
            $pageTitleEnValue = (string)($page->title_en ?? '');
            $pageContentEnValue = (string)($page->content_en ?? '');
            $pageMetaTitleValue = (string)($page->meta_title ?? '');
            $pageMetaDescriptionValue = (string)($page->meta_description ?? '');
            $pageFeaturedImageValue = (string)($page->featured_image ?? '');
            $pagePreviewUrl = $siteUrl . '/' . ltrim($pageSlugValue, '/');
            $pagePreviewUrlEn = rtrim($pagePreviewUrl, '/') . '/en';
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
                <div class="col-lg-4 d-flex">
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

                <div class="col-lg-4 d-flex">
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
                                    DE
                                </a>
                                <a href="<?= htmlspecialchars($pagePreviewUrlEn) ?>"
                                   class="btn btn-outline-secondary" target="_blank" rel="noopener">
                                    EN
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Contentheader Bild (sichtbar im Hauptformular) -->
                <div class="col-lg-4 d-flex">
                    <div class="card cms-edit-card cms-edit-top-card h-100 w-100">
                        <div class="card-header">
                            <h3 class="card-title">Contentheader Bild</h3>
                        </div>
                        <div class="card-body flex-fill">
                            <div class="small text-secondary mb-2">Erscheint links vom Seitentitel im Content-Header.</div>
                            <div id="featuredImagePreview" class="mb-2">
                                <?php if ($pageFeaturedImageValue !== ''): ?>
                                    <img src="<?= htmlspecialchars(\CMS\Services\MediaDeliveryService::getInstance()->normalizeUrl($pageFeaturedImageValue, true)) ?>" alt="" class="img-fluid rounded" style="max-height:120px;object-fit:cover;width:100%;">
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="featured_image" id="featuredImageInput" value="<?= htmlspecialchars($pageFeaturedImageValue) ?>">
                            <input type="hidden" name="featured_image_temp_path" id="featuredImageInput_temp_path" value="">
                            <div id="featuredImageEmpty" class="text-secondary small <?= $pageFeaturedImageValue !== '' ? 'd-none' : '' ?>">Noch kein Bild ausgewählt.</div>
                            <div class="btn-list mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary" id="featuredImageBtn">Bild auswählen</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary <?= $pageFeaturedImageValue === '' ? 'd-none' : '' ?>" id="featuredImageRemove">Entfernen</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card cms-edit-card cms-editor-card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
                            <h3 class="card-title">Inhalt</h3>
                            <div class="btn-group" role="group" aria-label="Inhaltssprache wählen">
                                <button class="btn btn-primary" type="button" id="pageLangToggleDe" data-lang-toggle="de" aria-pressed="true">Deutsch</button>
                                <button class="btn btn-outline-primary" type="button" id="pageLangToggleEn" data-lang-toggle="en" aria-pressed="false">English</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="pageLanguagePaneDe" data-page-lang-pane="de">
                                <div class="mb-3">
                                    <div class="text-secondary small mb-2">Standardansicht unter <code><?= htmlspecialchars($pagePreviewUrl) ?></code></div>
                                </div>
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
                            <div id="pageLanguagePaneEn" data-page-lang-pane="en" class="d-none">
                                <div class="mb-3">
                                    <label class="form-label" for="pageTitleEn">Englischer Titel</label>
                                    <input type="text" name="title_en" id="pageTitleEn" class="form-control" value="<?= htmlspecialchars($pageTitleEnValue) ?>" placeholder="English page title">
                                    <div class="form-hint">Die englische Version ist unter <code><?= htmlspecialchars($pagePreviewUrlEn) ?></code> erreichbar.</div>
                                </div>
                                <?php if (!empty($useEditorJs)): ?>
                                <div class="editorjs-wrap editorjs-wrap--page cms-editor-live-wrap"
                                       style="--editorjs-content-width:<?= (int)$pageEditorWidth ?>px; --editorjs-content-padding-x:50px;">
                                    <div id="editorjsEn" class="editorjs-holder cms-editor-live-holder" style="min-height: 300px;"></div>
                                </div>
                                <input type="hidden" name="content_en" id="editorContentEn"
                                       value="<?= htmlspecialchars($pageContentEnValue) ?>">
                                <?php else: ?>
                                    <?= EditorService::getInstance()->render('content_en', $pageContentEnValue, [
                                        'height' => '420',
                                        'context' => 'page',
                                        'content_width' => $pageEditorWidth,
                                        'content_padding_x' => 50,
                                    ]) ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 d-flex">
                    <div class="card cms-edit-card h-100 w-100">
                        <div class="card-header"><h3 class="card-title">SEO-Card</h3></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label" for="pageFocusKeyphrase">Fokus-Keyphrase</label>
                                <input type="text" name="focus_keyphrase" class="form-control" id="pageFocusKeyphrase" placeholder="z. B. Mitgliedschaft B2B-Netzwerk" value="<?= htmlspecialchars($pageFocusKeyphraseValue) ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Meta-Titel</label>
                                <input type="text" name="meta_title" class="form-control" id="pageMetaTitle" placeholder="SEO-Titel (Standard: Seitentitel)" maxlength="70" value="<?= htmlspecialchars($pageMetaTitleValue) ?>">
                                <small class="form-hint"><span id="metaTitleCount">0</span>/70 Zeichen</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Meta-Beschreibung</label>
                                <textarea name="meta_description" class="form-control" rows="3" id="pageMetaDescription" placeholder="Kurze Beschreibung für Suchmaschinen…" maxlength="160"><?= htmlspecialchars($pageMetaDescriptionValue) ?></textarea>
                                <small class="form-hint"><span id="metaDescriptionCount">0</span>/160 Zeichen</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="pageCanonicalUrl">Kanonische URL</label>
                                <input type="text" name="canonical_url" id="pageCanonicalUrl" class="form-control" value="<?= htmlspecialchars($pageCanonicalUrlValue) ?>" placeholder="Automatisch self-referencing, wenn leer">
                            </div>
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2"><span class="text-secondary small">Vorschau-URL</span><span class="badge bg-success-lt text-success" id="pageSlugState">Slug gültig</span></div>
                            <div class="form-control-plaintext text-break small mb-3" id="pagePreviewUrl"><?= htmlspecialchars($pagePreviewUrl) ?></div>
                            <div id="pagePublishWarning" class="alert alert-warning mb-0" role="alert"></div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 d-flex">
                    <div class="card cms-edit-card h-100 w-100">
                        <div class="card-header d-flex justify-content-between align-items-center"><h3 class="card-title">Lesbarkeits-Card</h3><span class="badge bg-danger-lt text-danger" id="pageReadabilityBadge">Kritisch</span></div>
                        <div class="card-body">
                            <div class="text-secondary small mb-3" id="pageReadabilitySummary">0 Wörter · 0 lange Sätze · 0 lange Absätze</div>
                            <div class="row g-3">
                                <div class="col-6"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Wörter</div><div class="h3 m-0" id="pageWordCount">0</div></div></div>
                                <div class="col-6"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Keyphrase-Dichte</div><div class="h3 m-0" id="pageDensity">0%</div></div></div>
                                <div class="col-6"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Interne Links</div><div class="h3 m-0" id="pageInternalLinks">0</div></div></div>
                                <div class="col-6"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Externe Links</div><div class="h3 m-0" id="pageExternalLinks">0</div></div></div>
                                <div class="col-6"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Signalwörter</div><div class="h3 m-0" id="pageTransitionWords">0</div></div></div>
                                <div class="col-6"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Bilder ohne Alt</div><div class="h3 m-0" id="pageMissingAlt">0</div></div></div>
                                <div class="col-6"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Lange Sätze</div><div class="h3 m-0" id="pageLongSentences">0</div></div></div>
                                <div class="col-6"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Lange Absätze</div><div class="h3 m-0" id="pageLongParagraphs">0</div></div></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 d-flex">
                    <div class="card cms-edit-card h-100 w-100">
                        <div class="card-header"><h3 class="card-title">Vorschau-Card</h3></div>
                        <div class="card-body">
                            <div class="text-uppercase text-secondary small mb-2">SERP</div>
                            <div class="border rounded p-3 bg-light mb-4">
                                <div id="pageSerpTitle" class="fw-semibold text-primary mb-1"><?= htmlspecialchars($pageMetaTitleValue ?: $pageTitleValue) ?></div>
                                <div id="pageSerpUrl" class="small text-success mb-1"><?= htmlspecialchars($pagePreviewUrl) ?></div>
                                <div id="pageSerpDescription" class="small text-secondary"><?= htmlspecialchars($pageMetaDescriptionValue ?: 'Meta-Beschreibung wird automatisch aus dem ersten Absatz erzeugt.') ?></div>
                            </div>
                            <div class="text-uppercase text-secondary small mb-2">Social</div>
                            <div class="border rounded overflow-hidden bg-light">
                                <img id="pageSocialImage" src="<?= htmlspecialchars($pageOgImageValue !== '' ? $pageOgImageValue : $pageFeaturedImageValue) ?>" alt="" style="display:<?= ($pageOgImageValue !== '' || $pageFeaturedImageValue !== '') ? 'block' : 'none' ?>; width:100%; height:160px; object-fit:cover;">
                                <div class="p-3">
                                    <div class="text-uppercase text-secondary small mb-1">facebook / x</div>
                                    <div id="pageSocialTitle" class="fw-semibold mb-1"><?= htmlspecialchars($pageOgTitleValue !== '' ? $pageOgTitleValue : ($pageMetaTitleValue ?: $pageTitleValue)) ?></div>
                                    <div id="pageSocialDescription" class="small text-secondary"><?= htmlspecialchars($pageOgDescriptionValue !== '' ? $pageOgDescriptionValue : ($pageMetaDescriptionValue ?: 'Social-Vorschau aus SEO-Daten')) ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <details class="card cms-edit-card" open>
                        <summary class="card-header" style="cursor:pointer; list-style:none;"><div class="d-flex align-items-center justify-content-between w-100"><h3 class="card-title mb-0">SEO-Score &amp; Checkliste</h3><div class="d-flex align-items-center gap-3"><span class="badge bg-danger-lt text-danger" id="pageSeoScoreBadge">Rot</span><strong class="h2 mb-0" id="pageSeoScoreLabel">0</strong></div></div></summary>
                        <div class="card-body">
                            <div class="progress progress-sm mb-3"><div class="progress-bar bg-danger" id="pageSeoScoreBar" style="width:0%"></div></div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-3"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Titel</div><div class="h3 m-0" id="pageTitleCount">0</div><div class="text-secondary small">Zeichen</div></div></div>
                                <div class="col-md-3"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Slug</div><div class="h3 m-0" id="pageSlugCount">0</div><div class="text-secondary small">Zeichen</div></div></div>
                                <div class="col-md-3"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Status</div><span class="badge bg-azure-lt text-azure" id="pageStatusBadge">Entwurf</span></div></div>
                                <div class="col-md-3"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Hinweis</div><div class="small">Slug, Meta, Lesbarkeit und Social-Preview live.</div></div></div>
                            </div>
                            <div id="pageSeoRules"></div>
                        </div>
                    </details>
                </div>

                <div class="col-12">
                    <details class="card cms-edit-card">
                        <summary class="card-header" style="cursor:pointer; list-style:none;"><div class="d-flex align-items-center justify-content-between w-100"><h3 class="card-title mb-0">Erweitertes SEO &amp; Social</h3><span class="text-secondary small">Canonical, Robots, Social, Schema, Sitemap, Vorschaubild</span></div></summary>
                        <div class="card-body">
                            <div class="row g-3 mb-4">
                                <div class="col-lg-4">
                                    <label class="form-label">Vorschaubild / OG-Bild</label>
                                    <div class="text-secondary small">Das Contentheader-Bild wird oben im Formular unter <strong>Contentheader Bild</strong> gesetzt. Hier kann ein abweichendes OG-Bild für Social Media hinterlegt werden.</div>
                                </div>
                                <div class="col-lg-4">
                                    <label class="form-label" for="pageSchemaType">Schema-Typ</label>
                                    <select class="form-select" name="schema_type" id="pageSchemaType">
                                        <?php foreach (['WebPage', 'FAQPage', 'HowTo', 'Person', 'Event', 'Article'] as $type): ?>
                                            <option value="<?= htmlspecialchars($type) ?>" <?= $pageSchemaTypeValue === $type ? 'selected' : '' ?>><?= htmlspecialchars($type) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label class="form-label mt-3" for="pageSitemapPriority">Sitemap Priority</label>
                                    <input type="text" class="form-control" id="pageSitemapPriority" name="sitemap_priority" value="<?= htmlspecialchars($pageSitemapPriorityValue) ?>">
                                    <label class="form-label mt-3" for="pageSitemapChangefreq">Sitemap Changefreq</label>
                                    <select class="form-select" id="pageSitemapChangefreq" name="sitemap_changefreq">
                                        <?php foreach (['always', 'daily', 'weekly', 'monthly', 'yearly'] as $freq): ?>
                                            <option value="<?= htmlspecialchars($freq) ?>" <?= $pageSitemapChangefreqValue === $freq ? 'selected' : '' ?>><?= htmlspecialchars($freq) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-lg-4 d-flex flex-column justify-content-end gap-2">
                                    <label class="form-check"><input class="form-check-input" type="checkbox" name="robots_index" value="1" <?= $pageRobotsIndexValue ? 'checked' : '' ?>><span class="form-check-label">index</span></label>
                                    <label class="form-check"><input class="form-check-input" type="checkbox" name="robots_follow" value="1" <?= $pageRobotsFollowValue ? 'checked' : '' ?>><span class="form-check-label">follow</span></label>
                                    <label class="form-label mt-2" for="pageHreflangGroup">hreflang-Gruppe</label>
                                    <input type="text" class="form-control" id="pageHreflangGroup" name="hreflang_group" value="<?= htmlspecialchars($pageHreflangGroupValue) ?>">
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
                            </div>
                        </div>
                    </details>
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
        $pickerIsNew = $isNew;
        $pickerContentType = 'page';
        require __DIR__ . '/../partials/featured-image-picker.php';

        $pageContentUiConfig = [
            'formId' => 'pageForm',
            'removeButtonId' => 'featuredImageRemove',
            'imageInputId' => 'featuredImageInput',
            'previewContainerId' => 'featuredImagePreview',
            'emptyStateId' => 'featuredImageEmpty',
            'slugInputId' => 'pageSlug',
            'previewUrlId' => 'pagePreviewUrl',
            'previewBaseUrl' => rtrim($siteUrl, '/') . '/',
            'statusSelectId' => 'pageStatusSelect',
            'statusBadgeId' => 'pageStatusBadge',
            'statusMap' => [
                'draft' => ['label' => 'Entwurf', 'className' => 'badge bg-yellow-lt text-yellow'],
                'published' => ['label' => 'Veröffentlicht', 'className' => 'badge bg-green-lt text-green'],
                'private' => ['label' => 'Privat', 'className' => 'badge bg-azure-lt text-azure'],
            ],
            'languageToggleSelector' => '[data-lang-toggle]',
            'languagePaneSelector' => '[data-page-lang-pane]',
            'languageAttribute' => 'data-lang-toggle',
            'languagePaneAttribute' => 'data-page-lang-pane',
            'defaultLanguage' => 'de',
            'countBindings' => [
                ['sourceId' => 'pageTitle', 'targetId' => 'pageTitleCount'],
                ['sourceId' => 'pageSlug', 'targetId' => 'pageSlugCount'],
                ['sourceId' => 'pageMetaTitle', 'targetId' => 'metaTitleCount'],
                ['sourceId' => 'pageMetaDescription', 'targetId' => 'metaDescriptionCount'],
            ],
        ];

        $pageContentSeoConfig = [
            'formId' => 'pageForm',
            'titleId' => 'pageTitle',
            'slugId' => 'pageSlug',
            'metaTitleId' => 'pageMetaTitle',
            'metaDescId' => 'pageMetaDescription',
            'focusKeyphraseId' => 'pageFocusKeyphrase',
            'ogTitleId' => 'pageOgTitle',
            'ogDescriptionId' => 'pageOgDescription',
            'ogImageId' => 'pageOgImage',
            'twitterTitleId' => 'pageTwitterTitle',
            'twitterDescriptionId' => 'pageTwitterDescription',
            'twitterImageId' => 'pageTwitterImage',
            'featuredImageId' => 'featuredImageInput',
            'statusId' => 'pageStatusSelect',
            'contentInputId' => 'editorContent',
            'editorContainerId' => 'editorjs',
            'serpTitleId' => 'pageSerpTitle',
            'serpUrlId' => 'pageSerpUrl',
            'serpDescriptionId' => 'pageSerpDescription',
            'scoreBarId' => 'pageSeoScoreBar',
            'scoreLabelId' => 'pageSeoScoreLabel',
            'scoreBadgeId' => 'pageSeoScoreBadge',
            'scoreRulesId' => 'pageSeoRules',
            'socialTitleId' => 'pageSocialTitle',
            'socialDescriptionId' => 'pageSocialDescription',
            'socialImageId' => 'pageSocialImage',
            'publishWarningId' => 'pagePublishWarning',
            'slugStateId' => 'pageSlugState',
            'wordCountId' => 'pageWordCount',
            'densityId' => 'pageDensity',
            'internalLinksId' => 'pageInternalLinks',
            'externalLinksId' => 'pageExternalLinks',
            'transitionWordsId' => 'pageTransitionWords',
            'longSentencesId' => 'pageLongSentences',
            'longParagraphsId' => 'pageLongParagraphs',
            'missingAltId' => 'pageMissingAlt',
            'readabilityBadgeId' => 'pageReadabilityBadge',
            'readabilitySummaryId' => 'pageReadabilitySummary',
            'previewBaseUrl' => rtrim($siteUrl, '/') . '/',
            'siteName' => (string)SITE_NAME,
            'siteTitleFormat' => (string)($seoTemplateSettings['site_title_format'] ?? '%%title%% %%sep%% %%sitename%%'),
            'titleSeparator' => (string)($seoTemplateSettings['title_separator'] ?? '|'),
            'minWords' => (int)($seoTemplateSettings['analysis_min_words'] ?? 300),
            'maxSentenceWords' => (int)($seoTemplateSettings['analysis_sentence_words'] ?? 24),
            'maxParagraphWords' => (int)($seoTemplateSettings['analysis_paragraph_words'] ?? 120),
            'fallbackImage' => $pageFeaturedImageValue,
        ];

        $pageContentEditorJsConfig = !empty($useEditorJs) ? [
            'formId' => 'pageForm',
            'mediaUploadUrl' => rtrim((defined('SITE_URL') ? SITE_URL : ''), '/') . '/api/media',
            'csrfToken' => $editorMediaToken ?? '',
            'editors' => [
                ['key' => 'de', 'holderId' => 'editorjs', 'inputId' => 'editorContent', 'lazy' => false],
                ['key' => 'en', 'holderId' => 'editorjsEn', 'inputId' => 'editorContentEn', 'lazy' => true, 'activateButtonId' => 'pageLangToggleEn'],
            ],
        ] : null;
        ?>

        <input type="hidden" id="contentEditorUiConfig" value="<?= htmlspecialchars((string) json_encode($pageContentUiConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES) ?>">
        <input type="hidden" id="contentEditorSeoConfig" value="<?= htmlspecialchars((string) json_encode($pageContentSeoConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES) ?>">
        <?php if ($pageContentEditorJsConfig !== null): ?>
            <input type="hidden" id="contentEditorEditorJsConfig" value="<?= htmlspecialchars((string) json_encode($pageContentEditorJsConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES) ?>">
        <?php endif; ?>

    </div><!-- /.container-xl -->
</div><!-- /.page-body -->

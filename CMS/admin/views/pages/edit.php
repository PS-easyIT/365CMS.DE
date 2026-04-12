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

$aiTranslationEnabled = !empty($aiTranslationEnabled);

$pageAdminBaseUrl = '/admin/pages';
$page    = $editData['page'] ?? null;
$isNew   = $editData['isNew'] ?? true;
$categories = $editData['categories'] ?? [];
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
                <a href="<?= $pageAdminBaseUrl ?>" class="btn btn-outline-secondary">
                    ← Zurück zur Liste
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Page Body -->
<div class="page-body">
    <div class="container-xl">

        <?php
        $alertData = is_array($alert ?? null) ? $alert : [];
        $alertMarginClass = 'mb-3';
        include __DIR__ . '/../partials/flash-alert.php';
        ?>

        <form method="post" id="pageForm">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" value="save">
            <?php if (!$isNew): ?>
                <input type="hidden" name="id" value="<?= (int)$page->id ?>">
            <?php endif; ?>

            <?php
            $pageTitleValue = (string)($page->title ?? '');
            $pageSlugValue = (string)($page->slug ?? '');
            $pageSlugEnValue = (string)($page->slug_en ?? '');
            $pageStatusValue = (string)($page->status ?? $pageDefaultStatus);
            $pageContentValue = (string)($page->content ?? '');
            $pageTitleEnValue = (string)($page->title_en ?? '');
            $pageContentEnValue = (string)($page->content_en ?? '');
            $pageHideTitleValue = (int)($page->hide_title ?? 0);
            $pageCategoryIdValue = (int)($page->category_id ?? 0);
            $pageMetaTitleValue = (string)($page->meta_title ?? '');
            $pageMetaDescriptionValue = (string)($page->meta_description ?? '');
            $pageFeaturedImageValue = (string)($page->featured_image ?? '');
            $pagePreviewSlug = ltrim($pageSlugValue, '/');
            $pagePreviewSlugEn = ltrim($pageSlugEnValue !== '' ? $pageSlugEnValue : $pageSlugValue, '/');
            $pagePreviewUrl = $pagePreviewSlug !== '' ? '/' . $pagePreviewSlug : '/';
            $pagePreviewUrlEn = $pagePreviewSlugEn !== '' ? '/' . $pagePreviewSlugEn . '/en' : '/en';
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
                                                 <?= $pageHideTitleValue === 1 ? 'checked' : '' ?>>
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
                                    <option value="private"<?= $pageStatusValue === 'private' ? ' selected' : '' ?>>Privat (nur Mitglieder)</option>
                                </select>
                                <div class="form-hint mt-2">Private Seiten sind nicht öffentlich erreichbar und nur für eingeloggte Mitglieder bzw. Administratoren sichtbar.</div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label" for="pageCategoryId">Kategorie</label>
                                <select name="category_id" class="form-select" id="pageCategoryId">
                                    <option value="0">Keine Kategorie</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= (int)($category['id'] ?? 0) ?>"<?= $pageCategoryIdValue === (int)($category['id'] ?? 0) ? ' selected' : '' ?>><?= htmlspecialchars((string)($category['option_label'] ?? $category['name'] ?? '')) ?></option>
                                    <?php endforeach; ?>
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
                                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
                                    <div class="text-secondary small">Die englische Version ist unter <code><?= htmlspecialchars($pagePreviewUrlEn) ?></code> erreichbar.</div>
                                    <div class="btn-list">
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="copyPageDeToEnButton">Alles aus DE nach EN kopieren</button>
                                        <?php if ($aiTranslationEnabled): ?>
                                            <button type="button" class="btn btn-primary btn-sm" id="translatePageDeToEnButton">Mit AI nach EN übersetzen</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-lg-7">
                                        <label class="form-label" for="pageTitleEn">Englischer Titel</label>
                                        <input type="text" name="title_en" id="pageTitleEn" class="form-control" value="<?= htmlspecialchars($pageTitleEnValue) ?>" placeholder="English page title">
                                    </div>
                                    <div class="col-lg-5">
                                        <label class="form-label" for="pageSlugEn">Englischer Slug</label>
                                        <input type="text" name="slug_en" id="pageSlugEn" class="form-control" value="<?= htmlspecialchars($pageSlugEnValue) ?>" placeholder="optional: english-page-slug">
                                        <div class="form-hint">Wenn leer, nutzt die EN-URL weiterhin den Standardslug.</div>
                                    </div>
                                </div>
                                <div class="mb-3 text-secondary small">Der Kopier-Button übernimmt Titel und alle Editor.js-Blöcke aus der DE-Version in die englische Bearbeitung.</div>
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
                    <?php
                    $readabilityCard = [
                        'badgeId' => 'pageReadabilityBadge',
                        'summaryId' => 'pageReadabilitySummary',
                        'metrics' => [
                            ['id' => 'pageWordCount', 'label' => 'Wörter'],
                            ['id' => 'pageDensity', 'label' => 'Keyphrase-Dichte'],
                            ['id' => 'pageInternalLinks', 'label' => 'Interne Links'],
                            ['id' => 'pageExternalLinks', 'label' => 'Externe Links'],
                            ['id' => 'pageTransitionWords', 'label' => 'Signalwörter'],
                            ['id' => 'pageMissingAlt', 'label' => 'Bilder ohne Alt'],
                            ['id' => 'pageLongSentences', 'label' => 'Lange Sätze'],
                            ['id' => 'pageLongParagraphs', 'label' => 'Lange Absätze'],
                        ],
                    ];
                    require __DIR__ . '/../partials/content-readability-card.php';
                    ?>
                </div>

                <div class="col-xl-4 d-flex">
                    <?php
                    $previewCard = [
                        'serpTitleId' => 'pageSerpTitle',
                        'serpTitle' => $pageMetaTitleValue ?: $pageTitleValue,
                        'serpUrlId' => 'pageSerpUrl',
                        'serpUrl' => $pagePreviewUrl,
                        'serpDescriptionId' => 'pageSerpDescription',
                        'serpDescription' => $pageMetaDescriptionValue ?: 'Meta-Beschreibung wird automatisch aus dem ersten Absatz erzeugt.',
                        'socialImageId' => 'pageSocialImage',
                        'socialImage' => $pageOgImageValue !== '' ? $pageOgImageValue : $pageFeaturedImageValue,
                        'socialImageVisible' => $pageOgImageValue !== '' || $pageFeaturedImageValue !== '',
                        'socialTitleId' => 'pageSocialTitle',
                        'socialTitle' => $pageOgTitleValue !== '' ? $pageOgTitleValue : ($pageMetaTitleValue ?: $pageTitleValue),
                        'socialDescriptionId' => 'pageSocialDescription',
                        'socialDescription' => $pageOgDescriptionValue !== '' ? $pageOgDescriptionValue : ($pageMetaDescriptionValue ?: 'Social-Vorschau aus SEO-Daten'),
                    ];
                    require __DIR__ . '/../partials/content-preview-card.php';
                    ?>
                </div>

                <div class="col-12">
                    <?php
                    $seoScorePanel = [
                        'badgeId' => 'pageSeoScoreBadge',
                        'scoreLabelId' => 'pageSeoScoreLabel',
                        'scoreBarId' => 'pageSeoScoreBar',
                        'rulesId' => 'pageSeoRules',
                        'summaryCards' => [
                            ['width' => 'col-md-3', 'label' => 'Titel', 'valueId' => 'pageTitleCount', 'suffix' => 'Zeichen'],
                            ['width' => 'col-md-3', 'label' => 'Slug', 'valueId' => 'pageSlugCount', 'suffix' => 'Zeichen'],
                            ['width' => 'col-md-3', 'label' => 'Status', 'badgeId' => 'pageStatusBadge', 'badgeText' => 'Entwurf', 'badgeClass' => 'badge bg-yellow-lt text-yellow'],
                            ['width' => 'col-md-3', 'label' => 'Hinweis', 'bodyText' => 'Slug, Meta, Lesbarkeit und Social-Preview live.'],
                        ],
                    ];
                    require __DIR__ . '/../partials/content-seo-score-panel.php';
                    ?>
                </div>

                <div class="col-12">
                    <?php
                    $advancedSeoPanel = [
                        'hint' => 'Das Contentheader-Bild wird oben im Formular unter Contentheader Bild gesetzt. Hier kann ein abweichendes OG-Bild für Social Media hinterlegt werden.',
                        'schemaTypeId' => 'pageSchemaType',
                        'schemaTypeName' => 'schema_type',
                        'schemaTypeValue' => $pageSchemaTypeValue,
                        'schemaTypeOptions' => ['WebPage', 'FAQPage', 'HowTo', 'Person', 'Event', 'Article'],
                        'sitemapPriorityId' => 'pageSitemapPriority',
                        'sitemapPriorityName' => 'sitemap_priority',
                        'sitemapPriorityValue' => $pageSitemapPriorityValue,
                        'sitemapChangefreqId' => 'pageSitemapChangefreq',
                        'sitemapChangefreqName' => 'sitemap_changefreq',
                        'sitemapChangefreqValue' => $pageSitemapChangefreqValue,
                        'sitemapChangefreqOptions' => ['always', 'daily', 'weekly', 'monthly', 'yearly'],
                        'robotsIndexName' => 'robots_index',
                        'robotsIndexChecked' => $pageRobotsIndexValue,
                        'robotsFollowName' => 'robots_follow',
                        'robotsFollowChecked' => $pageRobotsFollowValue,
                        'hreflangGroupId' => 'pageHreflangGroup',
                        'hreflangGroupName' => 'hreflang_group',
                        'hreflangGroupValue' => $pageHreflangGroupValue,
                        'ogTitleId' => 'pageOgTitle',
                        'ogTitleValue' => $pageOgTitleValue,
                        'ogImageId' => 'pageOgImage',
                        'ogImageValue' => $pageOgImageValue,
                        'ogDescriptionId' => 'pageOgDescription',
                        'ogDescriptionValue' => $pageOgDescriptionValue,
                        'twitterTitleId' => 'pageTwitterTitle',
                        'twitterTitleValue' => $pageTwitterTitleValue,
                        'twitterCardId' => 'pageTwitterCard',
                        'twitterCardName' => 'twitter_card',
                        'twitterCardValue' => $pageTwitterCardValue,
                        'twitterCardOptions' => ['summary_large_image', 'summary'],
                        'twitterDescriptionId' => 'pageTwitterDescription',
                        'twitterDescriptionValue' => $pageTwitterDescriptionValue,
                        'twitterImageId' => 'pageTwitterImage',
                        'twitterImageValue' => $pageTwitterImageValue,
                    ];
                    require __DIR__ . '/../partials/content-advanced-seo-panel.php';
                    ?>
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
            'previewBaseUrl' => '/',
            'statusSelectId' => 'pageStatusSelect',
            'statusBadgeId' => 'pageStatusBadge',
            'statusMap' => [
                'draft' => ['label' => 'Entwurf', 'className' => 'badge bg-yellow-lt text-yellow'],
                'published' => ['label' => 'Veröffentlicht', 'className' => 'badge bg-green-lt text-green'],
                'private' => ['label' => 'Privat', 'className' => 'badge bg-purple-lt text-purple'],
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
            'previewBaseUrl' => '/',
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
            'mediaUploadUrl' => '/api/media',
            'csrfToken' => $editorMediaToken ?? '',
            'initialCopyOnFirstActivate' => [
                'sourceKey' => 'de',
                'targetKey' => 'en',
            ],
            'copyAction' => [
                'buttonId' => 'copyPageDeToEnButton',
                'sourceEditorKey' => 'de',
                'targetEditorKey' => 'en',
                'sourceTitleId' => 'pageTitle',
                'targetTitleId' => 'pageTitleEn',
                'sourceSlugId' => 'pageSlug',
                'targetSlugId' => 'pageSlugEn',
                'targetPaneButtonId' => 'pageLangToggleEn',
            ],
            'aiTranslation' => $aiTranslationEnabled ? [
                'buttonId' => 'translatePageDeToEnButton',
                'endpointUrl' => (string) ($aiTranslationUrl ?? '/admin/ai-translate-editorjs'),
                'csrfToken' => (string) ($aiTranslationToken ?? ''),
                'contentType' => 'page',
                'sourceLocale' => 'de',
                'targetLocale' => 'en',
                'sourceEditorKey' => 'de',
                'targetEditorKey' => 'en',
                'sourceTitleId' => 'pageTitle',
                'targetTitleId' => 'pageTitleEn',
                'sourceSlugId' => 'pageSlug',
                'targetSlugId' => 'pageSlugEn',
                'targetPaneButtonId' => 'pageLangToggleEn',
            ] : null,
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

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
$titleEn    = htmlspecialchars($post['title_en'] ?? '');
$slug       = htmlspecialchars($post['slug'] ?? '');
$content    = $post['content'] ?? '';
$contentEn  = $post['content_en'] ?? '';
$excerpt    = htmlspecialchars($post['excerpt'] ?? '');
$excerptEn  = htmlspecialchars($post['excerpt_en'] ?? '');
$status     = $post['status'] ?? $postDefaultStatus;
$categoryId = (int)($post['category_id'] ?? 0);
$featuredImg = htmlspecialchars($post['featured_image'] ?? '');
$metaTitle  = htmlspecialchars($post['meta_title'] ?? '');
$metaDesc   = htmlspecialchars($post['meta_description'] ?? '');
$seoMeta = $data['seoMeta'] ?? [];
$seoTemplateSettings = \CMS\Services\SeoAnalysisService::getInstance()->getSettings();
$focusKeyphrase = htmlspecialchars((string)($seoMeta['focus_keyphrase'] ?? ''));
$canonicalUrl = htmlspecialchars((string)($seoMeta['canonical_url'] ?? ''));
$robotsIndex = !array_key_exists('robots_index', $seoMeta) || !empty($seoMeta['robots_index']);
$robotsFollow = !array_key_exists('robots_follow', $seoMeta) || !empty($seoMeta['robots_follow']);
$ogTitle = htmlspecialchars((string)($seoMeta['og_title'] ?? ''));
$ogDescription = htmlspecialchars((string)($seoMeta['og_description'] ?? ''));
$ogImage = htmlspecialchars((string)($seoMeta['og_image'] ?? ''));
$twitterTitle = htmlspecialchars((string)($seoMeta['twitter_title'] ?? ''));
$twitterDescription = htmlspecialchars((string)($seoMeta['twitter_description'] ?? ''));
$twitterImage = htmlspecialchars((string)($seoMeta['twitter_image'] ?? ''));
$twitterCard = htmlspecialchars((string)($seoMeta['twitter_card'] ?? 'summary_large_image'));
$schemaType = htmlspecialchars((string)($seoMeta['schema_type'] ?? 'Article'));
$sitemapPriority = htmlspecialchars((string)($seoMeta['sitemap_priority'] ?? ''));
$sitemapChangefreq = htmlspecialchars((string)($seoMeta['sitemap_changefreq'] ?? 'monthly'));
$hreflangGroup = htmlspecialchars((string)($seoMeta['hreflang_group'] ?? ''));
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
            $postContentEnValue = (string)$contentEn;
            $postExcerptValue = (string)($post['excerpt'] ?? '');
            $postTitleEnValue = (string)($post['title_en'] ?? '');
            $postExcerptEnValue = (string)($post['excerpt_en'] ?? '');
            $postStatusValue = (string)$status;
            $postMetaTitleValue = (string)($post['meta_title'] ?? '');
            $postMetaDescriptionValue = (string)($post['meta_description'] ?? '');
            $postFeaturedImageValue = (string)($post['featured_image'] ?? '');
            $postPreviewUrl = htmlspecialchars(SITE_URL) . '/blog/' . ltrim($postSlugValue, '/');
            $postPreviewUrlEn = $postPreviewUrl . '/en';
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
                                    DE
                                </a>
                                <a href="<?php echo htmlspecialchars($postPreviewUrlEn); ?>" class="btn btn-outline-secondary" target="_blank" rel="noopener noreferrer" title="English preview">
                                    EN
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <div class="card cms-edit-card cms-editor-card mb-3">
                        <div class="card-header d-flex justify-content-between align-items-center gap-3 flex-wrap">
                            <h3 class="card-title">Inhalt</h3>
                            <div class="btn-group" role="group" aria-label="Inhaltssprache wählen">
                                <button class="btn btn-primary" type="button" id="postLangToggleDe" data-post-lang-toggle="de" aria-pressed="true">Deutsch</button>
                                <button class="btn btn-outline-primary" type="button" id="postLangToggleEn" data-post-lang-toggle="en" aria-pressed="false">English</button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="postLanguagePaneDe" data-post-lang-pane="de">
                                <div class="mb-3 text-secondary small">Standardansicht unter <code><?php echo htmlspecialchars($postPreviewUrl); ?></code></div>
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
                            <div id="postLanguagePaneEn" data-post-lang-pane="en" class="d-none">
                                <div class="row g-3 mb-3">
                                    <div class="col-lg-7">
                                        <label class="form-label" for="titleEn">Englischer Titel</label>
                                        <input type="text" class="form-control" id="titleEn" name="title_en" value="<?php echo htmlspecialchars($postTitleEnValue); ?>" placeholder="English article title">
                                    </div>
                                    <div class="col-lg-5">
                                        <label class="form-label" for="excerptEn">Englische Kurzfassung</label>
                                        <textarea class="form-control" id="excerptEn" name="excerpt_en" rows="2" placeholder="Short English summary"><?php echo htmlspecialchars($postExcerptEnValue); ?></textarea>
                                    </div>
                                </div>
                                <div class="mb-3 text-secondary small">Die englische Version ist unter <code><?php echo htmlspecialchars($postPreviewUrlEn); ?></code> erreichbar.</div>
                                <?php if (!empty($useEditorJs)): ?>
                                <div class="editorjs-wrap editorjs-wrap--post cms-editor-live-wrap"
                                     style="--editorjs-content-width:<?php echo (int)$postEditorWidth; ?>px; --editorjs-content-padding-x:50px; --editorjs-content-width-expanded:1100px;">
                                    <div id="editorjsEn" class="editorjs-holder cms-editor-live-holder" style="min-height:300px;"></div>
                                </div>
                                <input type="hidden" name="content_en" id="contentInputEn" value="<?php echo htmlspecialchars($postContentEnValue); ?>">
                                <?php else: ?>
                                    <?php echo EditorService::getInstance()->render('content_en', $postContentEnValue, [
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
                </div>

                <div class="col-xl-4 d-flex">
                    <div class="card cms-edit-card h-100 w-100">
                        <div class="card-header"><h3 class="card-title">SEO-Card</h3></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label" for="excerpt">Kurzfassung</label>
                                <textarea class="form-control" id="excerpt" name="excerpt" rows="5" placeholder="Kurze Zusammenfassung für Übersichten…"><?php echo htmlspecialchars($postExcerptValue); ?></textarea>
                                <span class="form-hint"><span id="excerptCount">0</span> Zeichen</span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="focusKeyphrase">Fokus-Keyphrase</label>
                                <input type="text" class="form-control" id="focusKeyphrase" name="focus_keyphrase" value="<?php echo $focusKeyphrase; ?>" placeholder="z. B. KI-Strategie Mittelstand">
                                <span class="form-hint">Mehrere Varianten per Komma möglich.</span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="metaTitle">Meta-Titel</label>
                                <input type="text" class="form-control" id="metaTitle" name="meta_title" value="<?php echo htmlspecialchars($postMetaTitleValue); ?>" maxlength="70">
                                <span class="form-hint"><span id="metaTitleCount">0</span>/70 Zeichen</span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="metaDesc">Meta-Beschreibung</label>
                                <textarea class="form-control" id="metaDesc" name="meta_description" rows="4" maxlength="160"><?php echo htmlspecialchars($postMetaDescriptionValue); ?></textarea>
                                <span class="form-hint"><span id="metaDescCount">0</span>/160 Zeichen</span>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="canonicalUrl">Kanonische URL</label>
                                <input type="text" class="form-control" id="canonicalUrl" name="canonical_url" value="<?php echo $canonicalUrl; ?>" placeholder="Automatisch self-referencing, wenn leer">
                            </div>
                            <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
                                <span class="text-secondary small">Vorschau-URL</span>
                                <span class="badge bg-success-lt text-success" id="postSlugState">Slug gültig</span>
                            </div>
                            <div class="form-control-plaintext text-break small mb-3" id="postPreviewUrl"><?php echo htmlspecialchars($postPreviewUrl); ?></div>
                            <div id="postPublishWarning" class="alert alert-warning mb-0" role="alert"></div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 d-flex">
                    <div class="card cms-edit-card h-100 w-100">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h3 class="card-title">Lesbarkeits-Card</h3>
                            <span class="badge bg-danger-lt text-danger" id="postReadabilityBadge">Kritisch</span>
                        </div>
                        <div class="card-body">
                            <div class="text-secondary small mb-3" id="postReadabilitySummary">0 Wörter · 0 lange Sätze · 0 lange Absätze</div>
                            <div class="row g-3">
                                <div class="col-6"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Wörter</div><div class="h3 m-0" id="postWordCount">0</div></div></div>
                                <div class="col-6"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Keyphrase-Dichte</div><div class="h3 m-0" id="postDensity">0%</div></div></div>
                                <div class="col-6"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Interne Links</div><div class="h3 m-0" id="postInternalLinks">0</div></div></div>
                                <div class="col-6"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Externe Links</div><div class="h3 m-0" id="postExternalLinks">0</div></div></div>
                                <div class="col-6"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Signalwörter</div><div class="h3 m-0" id="postTransitionWords">0</div></div></div>
                                <div class="col-6"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Bilder ohne Alt</div><div class="h3 m-0" id="postMissingAlt">0</div></div></div>
                                <div class="col-6"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Lange Sätze</div><div class="h3 m-0" id="postLongSentences">0</div></div></div>
                                <div class="col-6"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Lange Absätze</div><div class="h3 m-0" id="postLongParagraphs">0</div></div></div>
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
                                <div id="postSerpTitle" class="fw-semibold text-primary mb-1"><?php echo htmlspecialchars($postMetaTitleValue ?: $postTitleValue); ?></div>
                                <div id="postSerpUrl" class="small text-success mb-1"><?php echo htmlspecialchars($postPreviewUrl); ?></div>
                                <div id="postSerpDescription" class="small text-secondary"><?php echo htmlspecialchars($postMetaDescriptionValue ?: 'Meta-Beschreibung wird automatisch aus dem ersten Absatz erzeugt.'); ?></div>
                            </div>
                            <div class="text-uppercase text-secondary small mb-2">Social</div>
                            <div class="border rounded overflow-hidden bg-light">
                                <img id="postSocialImage" src="<?php echo $ogImage !== '' ? $ogImage : $postFeaturedImageValue; ?>" alt="" style="display:<?php echo ($ogImage !== '' || $postFeaturedImageValue !== '') ? 'block' : 'none'; ?>; width:100%; height:160px; object-fit:cover;">
                                <div class="p-3">
                                    <div class="text-uppercase text-secondary small mb-1">facebook / x</div>
                                    <div id="postSocialTitle" class="fw-semibold mb-1"><?php echo htmlspecialchars($ogTitle !== '' ? html_entity_decode($ogTitle, ENT_QUOTES, 'UTF-8') : ($postMetaTitleValue ?: $postTitleValue)); ?></div>
                                    <div id="postSocialDescription" class="small text-secondary"><?php echo htmlspecialchars($ogDescription !== '' ? html_entity_decode($ogDescription, ENT_QUOTES, 'UTF-8') : ($postMetaDescriptionValue ?: 'Social-Vorschau aus SEO-Daten')); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-12">
                    <details class="card cms-edit-card" open>
                        <summary class="card-header" style="cursor:pointer; list-style:none;">
                            <div class="d-flex align-items-center justify-content-between w-100">
                                <h3 class="card-title mb-0">SEO-Score &amp; Checkliste</h3>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge bg-danger-lt text-danger" id="postSeoScoreBadge">Rot</span>
                                    <strong class="h2 mb-0" id="postSeoScoreLabel">0</strong>
                                </div>
                            </div>
                        </summary>
                        <div class="card-body">
                            <div class="progress progress-sm mb-3"><div class="progress-bar bg-danger" id="postSeoScoreBar" style="width:0%"></div></div>
                            <div class="row g-3 mb-3">
                                <div class="col-md-2"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Titel</div><div class="h3 m-0" id="postTitleCount">0</div><div class="text-secondary small">Zeichen</div></div></div>
                                <div class="col-md-2"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Slug</div><div class="h3 m-0" id="postSlugCount">0</div><div class="text-secondary small">Zeichen</div></div></div>
                                <div class="col-md-3"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Status</div><span class="badge bg-yellow-lt text-yellow" id="postStatusBadge">Entwurf</span></div></div>
                                <div class="col-md-5"><div class="border rounded p-3 h-100"><div class="text-secondary small mb-1">Kategorie</div><div class="small" id="postCategoryLabel"><?php echo htmlspecialchars($selectedCategoryName); ?></div></div></div>
                            </div>
                            <div id="postSeoRules"></div>
                        </div>
                    </details>
                </div>

                <div class="col-12">
                    <details class="card cms-edit-card">
                        <summary class="card-header" style="cursor:pointer; list-style:none;">
                            <div class="d-flex align-items-center justify-content-between w-100">
                                <h3 class="card-title mb-0">Erweitertes SEO &amp; Social</h3>
                                <span class="text-secondary small">Canonical, Robots, Social, Schema, Sitemap, Bilder</span>
                            </div>
                        </summary>
                        <div class="card-body">
                            <div class="row g-3 mb-4">
                                <div class="col-lg-4">
                                    <label class="form-label">Beitragsbild</label>
                                    <div id="featuredPreview" class="mb-2 <?php echo $postFeaturedImageValue !== '' ? '' : 'd-none'; ?>">
                                        <img src="<?php echo htmlspecialchars($postFeaturedImageValue); ?>" class="rounded" id="featuredImg" alt="Beitragsbild" style="max-width:100%;max-height:120px;object-fit:cover;display:block;">
                                    </div>
                                    <div id="featuredEmpty" class="text-secondary small mb-2 <?php echo $postFeaturedImageValue !== '' ? 'd-none' : ''; ?>">Noch kein Beitragsbild ausgewählt.</div>
                                    <input type="hidden" name="featured_image" id="featuredInput" value="<?php echo htmlspecialchars($postFeaturedImageValue); ?>">
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="btnSelectImage">Bild wählen</button>
                                        <button type="button" class="btn btn-outline-danger btn-sm <?php echo $postFeaturedImageValue !== '' ? '' : 'd-none'; ?>" id="btnRemoveImage">Entfernen</button>
                                    </div>
                                </div>
                                <div class="col-lg-4">
                                    <label class="form-label" for="schemaType">Schema-Typ</label>
                                    <select class="form-select" id="schemaType" name="schema_type">
                                        <?php foreach (['Article', 'BlogPosting', 'FAQPage', 'HowTo', 'Person', 'Event'] as $type): ?>
                                            <option value="<?php echo htmlspecialchars($type); ?>" <?php echo $schemaType === $type ? 'selected' : ''; ?>><?php echo htmlspecialchars($type); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <label class="form-label mt-3" for="sitemapPriority">Sitemap Priority</label>
                                    <input type="text" class="form-control" id="sitemapPriority" name="sitemap_priority" value="<?php echo $sitemapPriority; ?>" placeholder="0.6">
                                    <label class="form-label mt-3" for="sitemapChangefreq">Sitemap Changefreq</label>
                                    <select class="form-select" id="sitemapChangefreq" name="sitemap_changefreq">
                                        <?php foreach (['always', 'daily', 'weekly', 'monthly', 'yearly'] as $freq): ?>
                                            <option value="<?php echo htmlspecialchars($freq); ?>" <?php echo $sitemapChangefreq === $freq ? 'selected' : ''; ?>><?php echo htmlspecialchars($freq); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-lg-4 d-flex flex-column justify-content-end gap-2">
                                    <label class="form-check"><input class="form-check-input" type="checkbox" name="robots_index" value="1" <?php echo $robotsIndex ? 'checked' : ''; ?>><span class="form-check-label">index</span></label>
                                    <label class="form-check"><input class="form-check-input" type="checkbox" name="robots_follow" value="1" <?php echo $robotsFollow ? 'checked' : ''; ?>><span class="form-check-label">follow</span></label>
                                    <label class="form-label mt-2" for="hreflangGroup">hreflang-Gruppe</label>
                                    <input type="text" class="form-control" id="hreflangGroup" name="hreflang_group" value="<?php echo $hreflangGroup; ?>" placeholder="z. B. blog-ki-strategie">
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-label" for="ogTitle">OG-Titel</label>
                                    <input type="text" class="form-control" id="ogTitle" name="og_title" value="<?php echo $ogTitle; ?>">
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-label" for="ogImage">OG-Bild</label>
                                    <input type="text" class="form-control" id="ogImage" name="og_image" value="<?php echo $ogImage; ?>">
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-label" for="ogDescription">OG-Beschreibung</label>
                                    <textarea class="form-control" id="ogDescription" name="og_description" rows="3"><?php echo $ogDescription; ?></textarea>
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-label" for="twitterTitle">Twitter-/X-Titel</label>
                                    <input type="text" class="form-control" id="twitterTitle" name="twitter_title" value="<?php echo $twitterTitle; ?>">
                                    <label class="form-label mt-3" for="twitterCard">Twitter Card</label>
                                    <select class="form-select" id="twitterCard" name="twitter_card">
                                        <?php foreach (['summary_large_image', 'summary'] as $card): ?>
                                            <option value="<?php echo htmlspecialchars($card); ?>" <?php echo $twitterCard === $card ? 'selected' : ''; ?>><?php echo htmlspecialchars($card); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-label" for="twitterDescription">Twitter-/X-Beschreibung</label>
                                    <textarea class="form-control" id="twitterDescription" name="twitter_description" rows="3"><?php echo $twitterDescription; ?></textarea>
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-label" for="twitterImage">Twitter-/X-Bild</label>
                                    <input type="text" class="form-control" id="twitterImage" name="twitter_image" value="<?php echo $twitterImage; ?>">
                                </div>
                            </div>
                        </div>
                    </details>
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
    var languageButtons = document.querySelectorAll('[data-post-lang-toggle]');
    var languagePanes = document.querySelectorAll('[data-post-lang-pane]');

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

    var switchLanguage = function(lang) {
        languagePanes.forEach(function(pane) {
            pane.classList.toggle('d-none', pane.getAttribute('data-post-lang-pane') !== lang);
        });

        languageButtons.forEach(function(button) {
            var isActive = button.getAttribute('data-post-lang-toggle') === lang;
            button.classList.toggle('btn-primary', isActive);
            button.classList.toggle('btn-outline-primary', !isActive);
            button.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        });
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

    languageButtons.forEach(function(button) {
        button.addEventListener('click', function() {
            switchLanguage(button.getAttribute('data-post-lang-toggle') || 'de');
        });
    });

    updateAnalysis();
    switchLanguage('de');
})();
</script>

<script src="<?php echo htmlspecialchars(SITE_URL); ?>/assets/js/admin-seo-editor.js"></script>
<script>
(function () {
    if (!window.cmsSeoEditor) {
        return;
    }

    window.cmsSeoEditor.init({
        formId: 'postForm',
        titleId: 'title',
        slugId: 'slug',
        metaTitleId: 'metaTitle',
        metaDescId: 'metaDesc',
        focusKeyphraseId: 'focusKeyphrase',
        ogTitleId: 'ogTitle',
        ogDescriptionId: 'ogDescription',
        ogImageId: 'ogImage',
        twitterTitleId: 'twitterTitle',
        twitterDescriptionId: 'twitterDescription',
        twitterImageId: 'twitterImage',
        featuredImageId: 'featuredInput',
        statusId: 'status',
        contentInputId: 'contentInput',
        editorContainerId: 'editorjs',
        serpTitleId: 'postSerpTitle',
        serpUrlId: 'postSerpUrl',
        serpDescriptionId: 'postSerpDescription',
        scoreBarId: 'postSeoScoreBar',
        scoreLabelId: 'postSeoScoreLabel',
        scoreBadgeId: 'postSeoScoreBadge',
        scoreRulesId: 'postSeoRules',
        socialTitleId: 'postSocialTitle',
        socialDescriptionId: 'postSocialDescription',
        socialImageId: 'postSocialImage',
        publishWarningId: 'postPublishWarning',
        slugStateId: 'postSlugState',
        wordCountId: 'postWordCount',
        densityId: 'postDensity',
        internalLinksId: 'postInternalLinks',
        externalLinksId: 'postExternalLinks',
        transitionWordsId: 'postTransitionWords',
        longSentencesId: 'postLongSentences',
        longParagraphsId: 'postLongParagraphs',
        missingAltId: 'postMissingAlt',
        readabilityBadgeId: 'postReadabilityBadge',
        readabilitySummaryId: 'postReadabilitySummary',
        previewBaseUrl: '<?php echo htmlspecialchars(SITE_URL); ?>/blog/',
        siteName: '<?php echo htmlspecialchars((string)SITE_NAME, ENT_QUOTES); ?>',
        siteTitleFormat: '<?php echo htmlspecialchars((string)($seoTemplateSettings['site_title_format'] ?? '%%title%% %%sep%% %%sitename%%'), ENT_QUOTES); ?>',
        titleSeparator: '<?php echo htmlspecialchars((string)($seoTemplateSettings['title_separator'] ?? '|'), ENT_QUOTES); ?>',
        minWords: <?php echo (int)($seoTemplateSettings['analysis_min_words'] ?? 300); ?>,
        maxSentenceWords: <?php echo (int)($seoTemplateSettings['analysis_sentence_words'] ?? 24); ?>,
        maxParagraphWords: <?php echo (int)($seoTemplateSettings['analysis_paragraph_words'] ?? 120); ?>,
        fallbackImage: '<?php echo htmlspecialchars($postFeaturedImageValue, ENT_QUOTES); ?>'
    });
})();
</script>

<?php if (!empty($useEditorJs)): ?>
<!-- EditorJS wird via $inlineJs in footer.php initialisiert -->
<?php endif; ?>

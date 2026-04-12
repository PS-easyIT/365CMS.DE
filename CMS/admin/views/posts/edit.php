<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Services\EditorService;

$aiTranslationEnabled = !empty($aiTranslationEnabled);

/**
 * Posts – Edit / Create View
 *
 * Erwartet: $data (aus PostsModule::getEditData())
 *           $csrfToken
 */

$post       = $data['post'] ?? null;
$isNew      = $data['isNew'] ?? true;
$categories = $data['categories'] ?? [];
$assignedCategoryIds = array_values(array_unique(array_map('intval', (array) ($data['assignedCategoryIds'] ?? []))));
$availableTags = $data['tags'] ?? [];
$postTagsData  = $data['postTags'] ?? [];
$postEditorWidth = function_exists('get_option') ? (int)get_option('setting_post_editor_width', 750) : 750;
$postEditorWidth = max(320, min(1600, $postEditorWidth));
$postDefaultStatus = function_exists('get_option') ? (string)get_option('setting_post_default_status', 'draft') : 'draft';
if (!in_array($postDefaultStatus, ['draft', 'published', 'private'], true)) {
    $postDefaultStatus = 'draft';
}

$title      = htmlspecialchars($post['title'] ?? '');
$titleEn    = htmlspecialchars($post['title_en'] ?? '');
$slug       = htmlspecialchars($post['slug'] ?? '');
$slugEn     = htmlspecialchars($post['slug_en'] ?? '');
$content    = $post['content'] ?? '';
$contentEn  = $post['content_en'] ?? '';
$excerpt    = htmlspecialchars($post['excerpt'] ?? '');
$excerptEn  = htmlspecialchars($post['excerpt_en'] ?? '');
$status     = $post['status'] ?? $postDefaultStatus;
$categoryId = (int)($post['category_id'] ?? 0);
$featuredImg = htmlspecialchars($post['featured_image'] ?? '');
$publishedAtValue = (string)($post['published_at'] ?? '');
$publishDate = '';
$publishTime = '';
if ($publishedAtValue !== '') {
    $publishedTimestamp = strtotime($publishedAtValue);
    if ($publishedTimestamp !== false) {
        $publishDate = date('Y-m-d', $publishedTimestamp);
        $publishTime = date('H:i', $publishedTimestamp);
    }
} elseif ($isNew) {
    $publishDate = date('Y-m-d');
    $publishTime = date('H:i');
}
$isScheduledPost = $post !== null && \cms_post_is_scheduled($post);
$metaTitle  = htmlspecialchars($post['meta_title'] ?? '');
$metaDesc   = htmlspecialchars($post['meta_description'] ?? '');
$authorDisplayName = htmlspecialchars($post['author_display_name'] ?? '', ENT_QUOTES);
$tagString  = htmlspecialchars(implode(', ', array_map(static fn(array $tag): string => (string)($tag['name'] ?? ''), $postTagsData)), ENT_QUOTES);
$seoMeta = $data['seoMeta'] ?? [];
$seoTemplateSettings = \CMS\Services\SeoAnalysisService::getInstance()->getSettings();
$permalinkService = class_exists('\CMS\Services\PermalinkService') ? \CMS\Services\PermalinkService::getInstance() : null;
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
$hasGermanVariant = trim((string)($post['title'] ?? '')) !== ''
    || trim((string)($post['content'] ?? '')) !== ''
    || trim((string)($post['excerpt'] ?? '')) !== '';
$hasEnglishVariant = trim((string)($post['title_en'] ?? '')) !== ''
    || trim((string)($post['content_en'] ?? '')) !== ''
    || trim((string)($post['excerpt_en'] ?? '')) !== ''
    || trim((string)($post['slug_en'] ?? '')) !== '';
$isEnglishOnlyPost = !$hasGermanVariant && $hasEnglishVariant;
$defaultContentLanguage = $isEnglishOnlyPost ? 'en' : 'de';
$additionalCategoryIds = array_values(array_filter(
    $assignedCategoryIds,
    static fn (int $assignedId): bool => $assignedId > 0 && $assignedId !== $categoryId
));
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="/admin/posts" class="btn btn-ghost-secondary btn-sm me-2">
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
        <?php if (!empty($alert)): ?>
            <?php $alertData = $alert; $alertMarginClass = 'mb-3'; require __DIR__ . '/../partials/flash-alert.php'; ?>
        <?php endif; ?>

        <form method="post" id="postForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="save">
            <?php if (!$isNew): ?>
                <input type="hidden" name="id" value="<?php echo (int)$post['id']; ?>">
            <?php endif; ?>

            <?php
            $postTitleValue = (string)($post['title'] ?? '');
            $postSlugValue = (string)($post['slug'] ?? '');
            $postSlugEnValue = (string)($post['slug_en'] ?? '');
            $postContentValue = (string)$content;
            $postContentEnValue = (string)$contentEn;
            $postExcerptValue = (string)($post['excerpt'] ?? '');
            $postTitleEnValue = (string)($post['title_en'] ?? '');
            $postExcerptEnValue = (string)($post['excerpt_en'] ?? '');
            $postStatusValue = (string)$status;
            $postMetaTitleValue = (string)($post['meta_title'] ?? '');
            $postMetaDescriptionValue = (string)($post['meta_description'] ?? '');
            $postFeaturedImageValue = (string)($post['featured_image'] ?? '');
            $postPreviewUrlTemplate = $permalinkService !== null
                ? $permalinkService->buildPostUrlTemplate((string)($post['published_at'] ?? ''), (string)($post['created_at'] ?? ''))
                : '/blog/{slug}';
            $postPreviewUrlTemplateEn = $permalinkService !== null
                ? $permalinkService->buildPostUrlTemplate((string)($post['published_at'] ?? ''), (string)($post['created_at'] ?? ''), 'en')
                : '/blog/{slug}/en';
            $postPreviewUrl = str_replace('{slug}', ltrim($postSlugValue !== '' ? $postSlugValue : 'beitrag', '/'), $postPreviewUrlTemplate);
            $postPreviewUrlEn = str_replace('{slug}', ltrim($postSlugEnValue !== '' ? $postSlugEnValue : ($postSlugValue !== '' ? $postSlugValue : 'beitrag'), '/'), $postPreviewUrlTemplateEn);
            $postPermalinkHint = $permalinkService !== null
                ? $permalinkService->getPostPermalinkStructure()
                : '/blog/%postname%';
            $selectedCategoryName = 'Keine Kategorie';
            foreach ($categories as $cat) {
                if ($categoryId === (int)($cat['id'] ?? 0)) {
                    $selectedCategoryName = (string)($cat['name'] ?? 'Keine Kategorie');
                    break;
                }
            }
            ?>

            <div class="row g-3">
                <div class="col-lg-4 d-flex">
                    <div class="card cms-edit-card cms-edit-top-card h-100 w-100">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label <?php echo $isEnglishOnlyPost ? '' : 'required'; ?>" for="title"><?php echo $isEnglishOnlyPost ? 'Deutscher Titel' : 'Titel'; ?></label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($postTitleValue); ?>" <?php echo $isEnglishOnlyPost ? 'placeholder="Optional, falls du zusätzlich eine deutsche Variante pflegen möchtest"' : 'required'; ?>>
                                <?php if ($isEnglishOnlyPost): ?>
                                <div class="form-hint">Dieser Beitrag enthält aktuell nur englische Inhalte. Die Bearbeitung startet deshalb direkt in der EN-Ansicht; deutsche Felder bleiben optional.</div>
                                <?php endif; ?>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="slug"><?php echo $isEnglishOnlyPost ? 'Standard-Slug' : 'Slug'; ?></label>
                                <input type="text" class="form-control" id="slug" name="slug" value="<?php echo htmlspecialchars($postSlugValue); ?>" placeholder="wird automatisch generiert">
                                <div class="form-hint">Aktive Struktur: <code><?php echo htmlspecialchars($postPermalinkHint); ?></code></div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="categoryId">Kategorie</label>
                                <select class="form-select" id="categoryId" name="category_id">
                                    <option value="0">Keine Kategorie</option>
                                    <?php foreach ($categories as $cat): ?>
                                        <option value="<?php echo (int)$cat['id']; ?>" <?php if ($categoryId === (int)$cat['id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars((string) ($cat['option_label'] ?? $cat['name'] ?? '')); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-hint">Primäre Kategorie für Listen, Archive und Vorschau.</div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="additionalCategoryIds">Weitere Kategorien</label>
                                <select class="form-select" id="additionalCategoryIds" name="additional_category_ids[]" multiple size="6">
                                    <?php foreach ($categories as $cat): ?>
                                        <?php $catId = (int) ($cat['id'] ?? 0); ?>
                                        <?php if ($catId <= 0) { continue; } ?>
                                        <option value="<?php echo $catId; ?>" <?php echo in_array($catId, $additionalCategoryIds, true) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars((string) ($cat['option_label'] ?? $cat['name'] ?? '')); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-hint">Optional zusätzliche Kategorien für Archive, Taxonomie-Zuordnungen und Routing. Die Primärkategorie oben bleibt führend für Listen und Standard-Vorschau.</div>
                            </div>
                            <div class="mb-0">
                                <label class="form-label" for="postTags">Tags</label>
                                <input type="text" class="form-control" id="postTags" name="tags" value="<?php echo $tagString; ?>" list="postTagsSuggestions" placeholder="z. B. Microsoft 365, PowerShell, Security">
                                <div class="form-hint">Mehrere Tags mit Komma trennen.</div>
                                <?php if (!empty($availableTags)): ?>
                                <datalist id="postTagsSuggestions">
                                    <?php foreach ($availableTags as $tag): ?>
                                    <option value="<?php echo htmlspecialchars((string)($tag['name'] ?? ''), ENT_QUOTES); ?>"></option>
                                    <?php endforeach; ?>
                                </datalist>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <div class="row g-3 h-100">
                        <div class="col-12 d-flex">
                            <div class="card cms-edit-card cms-edit-top-card w-100">
                                <div class="card-header"><h3 class="card-title">Beitragsbild</h3></div>
                                <div class="card-body d-flex flex-column gap-2">
                                    <div id="featuredPreview" class="<?php echo $postFeaturedImageValue !== '' ? '' : 'd-none'; ?>">
                                        <img src="<?php echo htmlspecialchars(\CMS\Services\MediaDeliveryService::getInstance()->normalizeUrl($postFeaturedImageValue, true)); ?>" class="rounded" id="featuredImg" alt="Beitragsbild" style="max-width:100%;max-height:120px;object-fit:cover;display:block;">
                                    </div>
                                    <div id="featuredEmpty" class="text-secondary small <?php echo $postFeaturedImageValue !== '' ? 'd-none' : ''; ?>">Noch kein Beitragsbild ausgewählt.</div>
                                    <input type="hidden" name="featured_image" id="featuredInput" value="<?php echo htmlspecialchars($postFeaturedImageValue); ?>">
                                    <input type="hidden" name="featured_image_temp_path" id="featuredInput_temp_path" value="">
                                    <div class="d-flex gap-2 mt-auto">
                                        <button type="button" class="btn btn-outline-primary btn-sm w-100" id="btnSelectImage">Bild auswählen</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm <?php echo $postFeaturedImageValue !== '' ? '' : 'd-none'; ?>" id="btnRemoveImage">Entfernen</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-12 d-flex">
                            <div class="card cms-edit-card cms-edit-top-card w-100">
                                <div class="card-header">
                                    <h3 class="card-title">Aktionen</h3>
                                </div>
                                <div class="card-body d-flex flex-column gap-2">
                                    <button type="submit" class="btn btn-primary w-100">
                                        <?php echo $isNew ? 'Erstellen' : 'Aktualisieren'; ?>
                                    </button>
                                    <?php if (!$isNew): ?>
                                    <div class="d-flex gap-2">
                                        <a href="<?php echo $postPreviewUrl; ?>" class="btn btn-outline-secondary w-100" target="_blank" rel="noopener noreferrer" title="Vorschau Deutsch">
                                            Public View DE
                                        </a>
                                        <a href="<?php echo htmlspecialchars($postPreviewUrlEn); ?>" class="btn btn-outline-secondary w-100" target="_blank" rel="noopener noreferrer" title="English public preview">
                                            Public View EN
                                        </a>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 d-flex">
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
                                    <option value="private" <?php if ($postStatusValue === 'private') echo 'selected'; ?>>Privat (nur Mitglieder)</option>
                                </select>
                                                        <div class="form-hint mt-2">Wenn der Status auf <strong>Veröffentlicht</strong> steht und Datum/Uhrzeit in der Zukunft liegen, wird der Beitrag automatisch erst zu diesem Zeitpunkt öffentlich sichtbar. <strong>Privat</strong> ist nur für eingeloggte Mitglieder sichtbar.</div>
                            </div>
                            <div class="row g-2 mt-1">
                                <div class="col-sm-7">
                                    <label class="form-label" for="publishDate">Veröffentlichungsdatum</label>
                                    <input type="date" class="form-control" id="publishDate" name="publish_date" value="<?php echo htmlspecialchars($publishDate); ?>">
                                </div>
                                <div class="col-sm-5">
                                    <label class="form-label" for="publishTime">Uhrzeit</label>
                                    <input type="time" class="form-control" id="publishTime" name="publish_time" value="<?php echo htmlspecialchars($publishTime); ?>" step="60">
                                </div>
                            </div>
                            <div class="mt-3">
                                <label class="form-label" for="authorDisplayName">Autoren-Anzeigename im Artikel</label>
                                <input type="text" class="form-control" id="authorDisplayName" name="author_display_name" value="<?php echo $authorDisplayName; ?>" maxlength="150" placeholder="Leer lassen = normaler 365CMS-Anzeigename des Autors">
                                <div class="form-hint">Optionaler Override nur für diesen Beitrag. Wenn leer, wird automatisch der Anzeigename des zugewiesenen 365CMS-Autors verwendet.</div>
                            </div>
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
                                <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-3">
                                    <div class="text-secondary small">Die englische Version ist unter <code><?php echo htmlspecialchars($postPreviewUrlEn); ?></code> erreichbar.</div>
                                    <div class="btn-list">
                                        <button type="button" class="btn btn-outline-primary btn-sm" id="copyPostDeToEnButton">Alles aus DE nach EN kopieren</button>
                                        <?php if ($aiTranslationEnabled): ?>
                                            <button type="button" class="btn btn-primary btn-sm" id="translatePostDeToEnButton">Mit AI nach EN übersetzen</button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="row g-3 mb-3">
                                    <div class="col-lg-7">
                                        <label class="form-label" for="titleEn">Englischer Titel</label>
                                        <input type="text" class="form-control" id="titleEn" name="title_en" value="<?php echo htmlspecialchars($postTitleEnValue); ?>" placeholder="English article title">
                                    </div>
                                    <div class="col-lg-5">
                                        <label class="form-label" for="slugEn">Englischer Slug</label>
                                        <input type="text" class="form-control" id="slugEn" name="slug_en" value="<?php echo htmlspecialchars($postSlugEnValue); ?>" placeholder="optional: english-url-slug">
                                        <div class="form-hint">Wenn leer, nutzt die EN-URL weiterhin den Standardslug.</div>
                                    </div>
                                    <div class="col-lg-12">
                                        <label class="form-label" for="excerptEn">Englische Kurzfassung</label>
                                        <textarea class="form-control" id="excerptEn" name="excerpt_en" rows="2" placeholder="Short English summary"><?php echo htmlspecialchars($postExcerptEnValue); ?></textarea>
                                    </div>
                                </div>
                                <div class="mb-3 text-secondary small">Der Kopier-Button übernimmt Titel, Slug, Kurzfassung und alle Editor.js-Blöcke aus der DE-Version. Vorhandene Medien werden dabei nur referenziert und nicht erneut hochgeladen.</div>
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
                                <span class="badge <?php echo $isScheduledPost ? 'bg-azure-lt text-azure' : 'bg-success-lt text-success'; ?>" id="postSlugState"><?php echo $isScheduledPost ? 'Geplant' : 'Slug gültig'; ?></span>
                            </div>
                            <div class="form-control-plaintext text-break small mb-3" id="postPreviewUrl"><?php echo htmlspecialchars($postPreviewUrl); ?></div>
                            <div id="postPublishWarning" class="alert <?php echo $isScheduledPost ? 'alert-info' : 'alert-warning'; ?> mb-0<?php echo $isScheduledPost ? '' : ' d-none'; ?>" role="alert"><?php echo $isScheduledPost ? 'Dieser Beitrag ist geplant und wird automatisch zum gewählten Termin veröffentlicht.' : ''; ?></div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-4 d-flex">
                    <?php
                    $readabilityCard = [
                        'badgeId' => 'postReadabilityBadge',
                        'summaryId' => 'postReadabilitySummary',
                        'metrics' => [
                            ['id' => 'postWordCount', 'label' => 'Wörter'],
                            ['id' => 'postDensity', 'label' => 'Keyphrase-Dichte'],
                            ['id' => 'postInternalLinks', 'label' => 'Interne Links'],
                            ['id' => 'postExternalLinks', 'label' => 'Externe Links'],
                            ['id' => 'postTransitionWords', 'label' => 'Signalwörter'],
                            ['id' => 'postMissingAlt', 'label' => 'Bilder ohne Alt'],
                            ['id' => 'postLongSentences', 'label' => 'Lange Sätze'],
                            ['id' => 'postLongParagraphs', 'label' => 'Lange Absätze'],
                        ],
                    ];
                    require __DIR__ . '/../partials/content-readability-card.php';
                    ?>
                </div>

                <div class="col-xl-4 d-flex">
                    <?php
                    $previewCard = [
                        'serpTitleId' => 'postSerpTitle',
                        'serpTitle' => $postMetaTitleValue ?: $postTitleValue,
                        'serpUrlId' => 'postSerpUrl',
                        'serpUrl' => $postPreviewUrl,
                        'serpDescriptionId' => 'postSerpDescription',
                        'serpDescription' => $postMetaDescriptionValue ?: 'Meta-Beschreibung wird automatisch aus dem ersten Absatz erzeugt.',
                        'socialImageId' => 'postSocialImage',
                        'socialImage' => $ogImage !== '' ? html_entity_decode($ogImage, ENT_QUOTES, 'UTF-8') : $postFeaturedImageValue,
                        'socialImageVisible' => $ogImage !== '' || $postFeaturedImageValue !== '',
                        'socialTitleId' => 'postSocialTitle',
                        'socialTitle' => $ogTitle !== '' ? html_entity_decode($ogTitle, ENT_QUOTES, 'UTF-8') : ($postMetaTitleValue ?: $postTitleValue),
                        'socialDescriptionId' => 'postSocialDescription',
                        'socialDescription' => $ogDescription !== '' ? html_entity_decode($ogDescription, ENT_QUOTES, 'UTF-8') : ($postMetaDescriptionValue ?: 'Social-Vorschau aus SEO-Daten'),
                    ];
                    require __DIR__ . '/../partials/content-preview-card.php';
                    ?>
                </div>

                <div class="col-12">
                    <?php
                    $seoScorePanel = [
                        'badgeId' => 'postSeoScoreBadge',
                        'scoreLabelId' => 'postSeoScoreLabel',
                        'scoreBarId' => 'postSeoScoreBar',
                        'rulesId' => 'postSeoRules',
                        'summaryCards' => [
                            ['width' => 'col-md-2', 'label' => 'Titel', 'valueId' => 'postTitleCount', 'suffix' => 'Zeichen'],
                            ['width' => 'col-md-2', 'label' => 'Slug', 'valueId' => 'postSlugCount', 'suffix' => 'Zeichen'],
                            ['width' => 'col-md-3', 'label' => 'Status', 'badgeId' => 'postStatusBadge', 'badgeText' => 'Entwurf', 'badgeClass' => 'badge bg-yellow-lt text-yellow'],
                            ['width' => 'col-md-5', 'label' => 'Kategorie', 'bodyText' => $selectedCategoryName],
                        ],
                    ];
                    require __DIR__ . '/../partials/content-seo-score-panel.php';
                    ?>
                </div>

                <div class="col-12">
                    <?php
                    $advancedSeoPanel = [
                        'hint' => 'Das Beitragsbild wird oben im Formular gesetzt. Hier kann ein separates OG-Bild für Social Media hinterlegt werden.',
                        'schemaTypeId' => 'schemaType',
                        'schemaTypeName' => 'schema_type',
                        'schemaTypeValue' => html_entity_decode($schemaType, ENT_QUOTES, 'UTF-8'),
                        'schemaTypeOptions' => ['Article', 'BlogPosting', 'FAQPage', 'HowTo', 'Person', 'Event'],
                        'sitemapPriorityId' => 'sitemapPriority',
                        'sitemapPriorityName' => 'sitemap_priority',
                        'sitemapPriorityValue' => html_entity_decode($sitemapPriority, ENT_QUOTES, 'UTF-8'),
                        'sitemapChangefreqId' => 'sitemapChangefreq',
                        'sitemapChangefreqName' => 'sitemap_changefreq',
                        'sitemapChangefreqValue' => html_entity_decode($sitemapChangefreq, ENT_QUOTES, 'UTF-8'),
                        'sitemapChangefreqOptions' => ['always', 'daily', 'weekly', 'monthly', 'yearly'],
                        'robotsIndexName' => 'robots_index',
                        'robotsIndexChecked' => $robotsIndex,
                        'robotsFollowName' => 'robots_follow',
                        'robotsFollowChecked' => $robotsFollow,
                        'hreflangGroupId' => 'hreflangGroup',
                        'hreflangGroupName' => 'hreflang_group',
                        'hreflangGroupValue' => html_entity_decode($hreflangGroup, ENT_QUOTES, 'UTF-8'),
                        'ogTitleId' => 'ogTitle',
                        'ogTitleValue' => html_entity_decode($ogTitle, ENT_QUOTES, 'UTF-8'),
                        'ogImageId' => 'ogImage',
                        'ogImageValue' => html_entity_decode($ogImage, ENT_QUOTES, 'UTF-8'),
                        'ogDescriptionId' => 'ogDescription',
                        'ogDescriptionValue' => html_entity_decode($ogDescription, ENT_QUOTES, 'UTF-8'),
                        'twitterTitleId' => 'twitterTitle',
                        'twitterTitleValue' => html_entity_decode($twitterTitle, ENT_QUOTES, 'UTF-8'),
                        'twitterCardId' => 'twitterCard',
                        'twitterCardName' => 'twitter_card',
                        'twitterCardValue' => html_entity_decode($twitterCard, ENT_QUOTES, 'UTF-8'),
                        'twitterCardOptions' => ['summary_large_image', 'summary'],
                        'twitterDescriptionId' => 'twitterDescription',
                        'twitterDescriptionValue' => html_entity_decode($twitterDescription, ENT_QUOTES, 'UTF-8'),
                        'twitterImageId' => 'twitterImage',
                        'twitterImageValue' => html_entity_decode($twitterImage, ENT_QUOTES, 'UTF-8'),
                    ];
                    require __DIR__ . '/../partials/content-advanced-seo-panel.php';
                    ?>
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
        $pickerIsNew = $isNew;
        $pickerContentType = 'post';
        require __DIR__ . '/../partials/featured-image-picker.php';

        $postContentUiConfig = [
            'formId' => 'postForm',
            'removeButtonId' => 'btnRemoveImage',
            'imageInputId' => 'featuredInput',
            'previewContainerId' => 'featuredPreview',
            'emptyStateId' => 'featuredEmpty',
            'currentTimestamp' => (int) round(microtime(true) * 1000),
            'slugInputId' => 'slug',
            'previewUrlId' => 'postPreviewUrl',
            'previewBaseUrl' => '/blog/',
            'previewUrlTemplate' => $postPreviewUrlTemplate,
            'previewPlaceholderSlug' => 'beitrag',
            'statusSelectId' => 'status',
            'publishDateId' => 'publishDate',
            'publishTimeId' => 'publishTime',
            'publishWarningId' => 'postPublishWarning',
            'statusBadgeId' => 'postStatusBadge',
            'categorySelectId' => 'categoryId',
            'categoryLabelId' => 'postCategoryLabel',
            'statusMap' => [
                'draft' => ['label' => 'Entwurf', 'className' => 'badge bg-yellow-lt text-yellow'],
                'published' => ['label' => 'Veröffentlicht', 'className' => 'badge bg-green-lt text-green'],
                'private' => ['label' => 'Privat', 'className' => 'badge bg-purple-lt text-purple'],
                'scheduled' => ['label' => 'Geplant', 'className' => 'badge bg-azure-lt text-azure'],
            ],
            'languageToggleSelector' => '[data-post-lang-toggle]',
            'languagePaneSelector' => '[data-post-lang-pane]',
            'languageAttribute' => 'data-post-lang-toggle',
            'languagePaneAttribute' => 'data-post-lang-pane',
            'defaultLanguage' => $defaultContentLanguage,
            'countBindings' => [
                ['sourceId' => 'title', 'targetId' => 'postTitleCount'],
                ['sourceId' => 'slug', 'targetId' => 'postSlugCount'],
                ['sourceId' => 'excerpt', 'targetId' => 'excerptCount'],
                ['sourceId' => 'metaTitle', 'targetId' => 'metaTitleCount'],
                ['sourceId' => 'metaDesc', 'targetId' => 'metaDescCount'],
            ],
        ];

        $postContentSeoConfig = [
            'formId' => 'postForm',
            'titleId' => 'title',
            'slugId' => 'slug',
            'metaTitleId' => 'metaTitle',
            'metaDescId' => 'metaDesc',
            'focusKeyphraseId' => 'focusKeyphrase',
            'ogTitleId' => 'ogTitle',
            'ogDescriptionId' => 'ogDescription',
            'ogImageId' => 'ogImage',
            'twitterTitleId' => 'twitterTitle',
            'twitterDescriptionId' => 'twitterDescription',
            'twitterImageId' => 'twitterImage',
            'featuredImageId' => 'featuredInput',
            'statusId' => 'status',
            'contentInputId' => 'contentInput',
            'editorContainerId' => 'editorjs',
            'serpTitleId' => 'postSerpTitle',
            'serpUrlId' => 'postSerpUrl',
            'serpDescriptionId' => 'postSerpDescription',
            'scoreBarId' => 'postSeoScoreBar',
            'scoreLabelId' => 'postSeoScoreLabel',
            'scoreBadgeId' => 'postSeoScoreBadge',
            'scoreRulesId' => 'postSeoRules',
            'socialTitleId' => 'postSocialTitle',
            'socialDescriptionId' => 'postSocialDescription',
            'socialImageId' => 'postSocialImage',
            'publishWarningId' => 'postPublishWarning',
            'slugStateId' => 'postSlugState',
            'wordCountId' => 'postWordCount',
            'densityId' => 'postDensity',
            'internalLinksId' => 'postInternalLinks',
            'externalLinksId' => 'postExternalLinks',
            'transitionWordsId' => 'postTransitionWords',
            'longSentencesId' => 'postLongSentences',
            'longParagraphsId' => 'postLongParagraphs',
            'missingAltId' => 'postMissingAlt',
            'readabilityBadgeId' => 'postReadabilityBadge',
            'readabilitySummaryId' => 'postReadabilitySummary',
            'previewBaseUrl' => '/blog/',
            'previewUrlTemplate' => $postPreviewUrlTemplate,
            'previewPlaceholderSlug' => 'beitrag',
            'siteName' => (string)SITE_NAME,
            'siteTitleFormat' => (string)($seoTemplateSettings['site_title_format'] ?? '%%title%% %%sep%% %%sitename%%'),
            'titleSeparator' => (string)($seoTemplateSettings['title_separator'] ?? '|'),
            'minWords' => (int)($seoTemplateSettings['analysis_min_words'] ?? 300),
            'maxSentenceWords' => (int)($seoTemplateSettings['analysis_sentence_words'] ?? 24),
            'maxParagraphWords' => (int)($seoTemplateSettings['analysis_paragraph_words'] ?? 120),
            'fallbackImage' => $postFeaturedImageValue,
        ];

        $postContentEditorJsConfig = !empty($useEditorJs) ? [
            'formId' => 'postForm',
            'mediaUploadUrl' => '/api/media',
            'csrfToken' => $editorMediaToken ?? '',
            'initialCopyOnFirstActivate' => [
                'sourceKey' => 'de',
                'targetKey' => 'en',
            ],
            'copyAction' => [
                'buttonId' => 'copyPostDeToEnButton',
                'sourceEditorKey' => 'de',
                'targetEditorKey' => 'en',
                'sourceTitleId' => 'title',
                'targetTitleId' => 'titleEn',
                'sourceSlugId' => 'slug',
                'targetSlugId' => 'slugEn',
                'sourceExcerptId' => 'excerpt',
                'targetExcerptId' => 'excerptEn',
                'targetPaneButtonId' => 'postLangToggleEn',
            ],
            'aiTranslation' => $aiTranslationEnabled ? [
                'buttonId' => 'translatePostDeToEnButton',
                'endpointUrl' => (string) ($aiTranslationUrl ?? '/admin/ai-translate-editorjs'),
                'csrfToken' => (string) ($aiTranslationToken ?? ''),
                'contentType' => 'post',
                'sourceLocale' => 'de',
                'targetLocale' => 'en',
                'sourceEditorKey' => 'de',
                'targetEditorKey' => 'en',
                'sourceTitleId' => 'title',
                'targetTitleId' => 'titleEn',
                'sourceSlugId' => 'slug',
                'targetSlugId' => 'slugEn',
                'sourceExcerptId' => 'excerpt',
                'targetExcerptId' => 'excerptEn',
                'targetPaneButtonId' => 'postLangToggleEn',
            ] : null,
            'editors' => [
                ['key' => 'de', 'holderId' => 'editorjs', 'inputId' => 'contentInput', 'lazy' => false],
                ['key' => 'en', 'holderId' => 'editorjsEn', 'inputId' => 'contentInputEn', 'lazy' => $defaultContentLanguage !== 'en', 'activateButtonId' => 'postLangToggleEn'],
            ],
        ] : null;
        ?>

        <input type="hidden" id="contentEditorUiConfig" value="<?php echo htmlspecialchars((string) json_encode($postContentUiConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES); ?>">
        <input type="hidden" id="contentEditorSeoConfig" value="<?php echo htmlspecialchars((string) json_encode($postContentSeoConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES); ?>">
        <?php if ($postContentEditorJsConfig !== null): ?>
            <input type="hidden" id="contentEditorEditorJsConfig" value="<?php echo htmlspecialchars((string) json_encode($postContentEditorJsConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES); ?>">
        <?php endif; ?>
    </div>
</div>

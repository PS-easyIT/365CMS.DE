<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Posts – Edit / Create View
 *
 * Erwartet: $data (aus PostsModule::getEditData())
 *           $csrfToken
 */

$post       = $data['post'] ?? null;
$isNew      = $data['isNew'] ?? true;
$categories = $data['categories'] ?? [];

$title      = htmlspecialchars($post['title'] ?? '');
$slug       = htmlspecialchars($post['slug'] ?? '');
$content    = $post['content'] ?? '';
$excerpt    = htmlspecialchars($post['excerpt'] ?? '');
$status     = $post['status'] ?? 'draft';
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

            <div class="row">
                <!-- Hauptbereich -->
                <div class="col-lg-8">

                    <!-- Titel & Slug -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label required" for="title">Titel</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo $title; ?>" required>
                            </div>
                            <div class="mb-0">
                                <label class="form-label" for="slug">Slug</label>
                                <div class="input-group">
                                    <span class="input-group-text">/blog/</span>
                                    <input type="text" class="form-control" id="slug" name="slug" value="<?php echo $slug; ?>" placeholder="wird automatisch generiert">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Excerpt -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Kurzfassung</h3>
                        </div>
                        <div class="card-body">
                            <textarea class="form-control" name="excerpt" rows="3" placeholder="Kurze Zusammenfassung für Übersichten…"><?php echo $excerpt; ?></textarea>
                        </div>
                    </div>

                    <!-- Inhalt (EditorJS) -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Inhalt</h3>
                        </div>
                        <div class="card-body">
                            <div id="editorjs" class="border rounded p-3" style="min-height:300px;"></div>
                            <input type="hidden" name="content" id="contentInput" value="<?php echo htmlspecialchars($content); ?>">
                        </div>
                    </div>

                    <!-- SEO -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">SEO</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label" for="metaTitle">Meta-Titel</label>
                                <input type="text" class="form-control" id="metaTitle" name="meta_title" value="<?php echo $metaTitle; ?>" maxlength="70">
                                <span class="form-hint">Max. 70 Zeichen</span>
                            </div>
                            <div class="mb-0">
                                <label class="form-label" for="metaDesc">Meta-Beschreibung</label>
                                <textarea class="form-control" id="metaDesc" name="meta_description" rows="2" maxlength="160"><?php echo $metaDesc; ?></textarea>
                                <span class="form-hint">Max. 160 Zeichen</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="col-lg-4">

                    <!-- Status & Kategorie -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Veröffentlichung</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label" for="status">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="draft" <?php if ($status === 'draft') echo 'selected'; ?>>Entwurf</option>
                                    <option value="published" <?php if ($status === 'published') echo 'selected'; ?>>Veröffentlicht</option>
                                </select>
                            </div>
                            <div class="mb-3">
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
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <?php echo $isNew ? 'Erstellen' : 'Aktualisieren'; ?>
                                </button>
                                <?php if (!$isNew): ?>
                                    <a href="<?php echo htmlspecialchars(SITE_URL); ?>/blog/<?php echo $slug; ?>" class="btn btn-outline-secondary" target="_blank" rel="noopener noreferrer" title="Vorschau">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 12a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M21 12c-2.4 4 -5.4 6 -9 6c-3.6 0 -6.6 -2 -9 -6c2.4 -4 5.4 -6 9 -6c3.6 0 6.6 2 9 6"/></svg>
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Featured Image -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Beitragsbild</h3>
                        </div>
                        <div class="card-body">
                            <div id="featuredPreview" class="mb-2 <?php echo $featuredImg ? '' : 'd-none'; ?>">
                                <img src="<?php echo $featuredImg; ?>" class="img-fluid rounded" id="featuredImg" alt="Beitragsbild">
                            </div>
                            <input type="hidden" name="featured_image" id="featuredInput" value="<?php echo $featuredImg; ?>">
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-secondary btn-sm w-100" id="btnSelectImage">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 8h.01"/><path d="M3 6a3 3 0 0 1 3 -3h12a3 3 0 0 1 3 3v12a3 3 0 0 1 -3 3h-12a3 3 0 0 1 -3 -3v-12z"/><path d="M6 18l3.5 -4a4 4 0 0 1 5 -.5l5.5 4.5"/></svg>
                                    Bild wählen
                                </button>
                                <button type="button" class="btn btn-outline-danger btn-sm <?php echo $featuredImg ? '' : 'd-none'; ?>" id="btnRemoveImage">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12"/><path d="M6 6l12 12"/></svg>
                                </button>
                            </div>
                        </div>
                    </div>

                </div>
            </div>

        </form>
    </div>
</div>

<script>
// Featured Image Remove
(function() {
    var btnRemove = document.getElementById('btnRemoveImage');
    if (btnRemove) {
        btnRemove.addEventListener('click', function() {
            document.getElementById('featuredInput').value = '';
            document.getElementById('featuredPreview').classList.add('d-none');
            btnRemove.classList.add('d-none');
        });
    }
})();
</script>

<!-- EditorJS wird via $inlineJs in footer.php initialisiert -->

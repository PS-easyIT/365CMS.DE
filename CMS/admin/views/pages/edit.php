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

$siteUrl = defined('SITE_URL') ? SITE_URL : '';
$assetsUrl = defined('ASSETS_URL') ? ASSETS_URL : $siteUrl . '/assets';
$page    = $editData['page'] ?? null;
$isNew   = $editData['isNew'] ?? true;
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

            <div class="row g-3">
                <!-- Hauptinhalt (links) -->
                <div class="col-lg-8">
                    <!-- Titel -->
                    <div class="card mb-3">
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label required">Titel</label>
                                <input type="text" name="title" class="form-control form-control-lg"
                                       placeholder="Seitentitel"
                                       value="<?= htmlspecialchars($page->title ?? '') ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Slug</label>
                                <div class="input-group">
                                    <span class="input-group-text">/</span>
                                    <input type="text" name="slug" class="form-control"
                                           placeholder="seiten-url"
                                           value="<?= htmlspecialchars($page->slug ?? '') ?>">
                                </div>
                                <small class="form-hint">Wird automatisch aus dem Titel generiert, wenn leer.</small>
                            </div>
                        </div>
                    </div>

                    <!-- Inhalt (Editor) -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Inhalt</h3>
                        </div>
                        <div class="card-body">
                            <div id="editorjs" style="min-height: 300px; border: 1px solid var(--tblr-border-color); border-radius: var(--tblr-border-radius); padding: 1rem;"></div>
                            <input type="hidden" name="content" id="editorContent"
                                   value="<?= htmlspecialchars($page->content ?? '') ?>">
                        </div>
                    </div>

                    <!-- SEO -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">SEO</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Meta-Titel</label>
                                <input type="text" name="meta_title" class="form-control"
                                       placeholder="SEO-Titel (Standard: Seitentitel)"
                                       maxlength="70"
                                       value="<?= htmlspecialchars($page->meta_title ?? '') ?>">
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Meta-Beschreibung</label>
                                <textarea name="meta_description" class="form-control" rows="2"
                                          placeholder="Kurze Beschreibung für Suchmaschinen…"
                                          maxlength="160"><?= htmlspecialchars($page->meta_description ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar (rechts) -->
                <div class="col-lg-4">
                    <!-- Veröffentlichen -->
                    <div class="card mb-3">
                        <div class="card-header">
                            <h3 class="card-title">Veröffentlichen</h3>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="draft"<?= ($page->status ?? 'draft') === 'draft' ? ' selected' : '' ?>>Entwurf</option>
                                    <option value="published"<?= ($page->status ?? '') === 'published' ? ' selected' : '' ?>>Veröffentlicht</option>
                                    <option value="private"<?= ($page->status ?? '') === 'private' ? ' selected' : '' ?>>Privat</option>
                                </select>
                            </div>
                            <div class="form-check mb-3">
                                <input type="checkbox" name="hide_title" value="1"
                                       class="form-check-input"
                                       id="hideTitle"
                                       <?= !empty($page->hide_title) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="hideTitle">Titel im Frontend ausblenden</label>
                            </div>
                        </div>
                        <div class="card-footer d-flex gap-2">
                            <button type="submit" class="btn btn-primary flex-fill">
                                <?= $isNew ? 'Seite erstellen' : 'Speichern' ?>
                            </button>
                            <?php if (!$isNew): ?>
                                <a href="<?= $siteUrl ?>/<?= htmlspecialchars($page->slug ?? '') ?>"
                                   class="btn btn-outline-secondary" target="_blank" rel="noopener">
                                    Ansehen
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Featured Image -->
                    <div class="card">
                        <div class="card-header">
                            <h3 class="card-title">Vorschaubild</h3>
                        </div>
                        <div class="card-body">
                            <div id="featuredImagePreview">
                                <?php if (!empty($page->featured_image)): ?>
                                    <img src="<?= htmlspecialchars($page->featured_image) ?>" alt="" class="img-fluid rounded mb-2">
                                <?php endif; ?>
                            </div>
                            <input type="hidden" name="featured_image" id="featuredImageInput"
                                   value="<?= htmlspecialchars($page->featured_image ?? '') ?>">
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
            </div>
        </form>

    </div><!-- /.container-xl -->
</div><!-- /.page-body -->

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Featured Image Remove
    const removeBtn = document.getElementById('featuredImageRemove');
    const imageInput = document.getElementById('featuredImageInput');
    const preview = document.getElementById('featuredImagePreview');

    if (imageInput && imageInput.value) {
        removeBtn?.classList.remove('d-none');
    }

    removeBtn?.addEventListener('click', function() {
        imageInput.value = '';
        preview.innerHTML = '';
        this.classList.add('d-none');
    });
});
</script>

<!-- EditorJS wird via $inlineJs in footer.php initialisiert -->

<?php
/**
 * Admin Pages Management
 *
 * @package CMSv2\Admin
 */

declare(strict_types=1);

// Load configuration first
require_once dirname(__DIR__) . '/config.php';

// Load autoloader
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\PageManager;
use CMS\Services\EditorService;
use CMS\Hooks;
use CMS\Security;

if (!defined('ABSPATH')) {
    exit;
}

// Security check
if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

$auth = Auth::instance();
$user = $auth->getCurrentUser();
$pageManager = PageManager::instance();

// ── Handle Page Create / Update / Delete ────────────────────────────────────
$pageActionError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['page_action'])) {
    if (!Security::instance()->verifyToken($_POST['csrf_token'] ?? '', 'page_manage')) {
        $pageActionError = 'Sicherheitscheck fehlgeschlagen.';
    } else {
        $currentUserId = (int)($user->id ?? 1);
        switch ($_POST['page_action']) {
            case 'create_page':
                $pTitle     = trim($_POST['page_title'] ?? '');
                $pContent   = wp_kses_post($_POST['page_content'] ?? '');
                $pStatus    = in_array($_POST['page_status'] ?? '', ['published', 'draft', 'private'])
                              ? $_POST['page_status'] : 'draft';
                $pSlug      = trim($_POST['page_slug'] ?? '');
                $pHideTitle = isset($_POST['page_hide_title']) ? 1 : 0;
                if (empty($pTitle)) {
                    $pageActionError = 'Bitte geben Sie einen Titel ein.';
                } else {
                    $newId = $pageManager->createPage($pTitle, $pContent, $pStatus, $currentUserId, $pHideTitle);
                    if ($newId > 0 && !empty($pSlug)) {
                        $safeSlug = $pageManager->generateSlug($pSlug);
                        $pageManager->updatePage($newId, ['slug' => $safeSlug]);
                    }
                    if ($newId > 0) {
                        $createdPage = $pageManager->getPage($newId);
                        $createdSlug = $createdPage['slug'] ?? '';
                        header('Location: ?msg=created&slug=' . urlencode($createdSlug));
                        exit;
                    }
                    $pageActionError = 'Fehler beim Erstellen der Seite.';
                }
                break;

            case 'update_page':
                $pId        = (int)($_POST['page_id'] ?? 0);
                $pTitle     = trim($_POST['page_title'] ?? '');
                $pRawContent = $_POST['page_content'] ?? null;  // null = not submitted (SunEditor not loaded)
                $pContent   = $pRawContent !== null ? wp_kses_post($pRawContent) : null;
                $pStatus    = in_array($_POST['page_status'] ?? '', ['published', 'draft', 'private'])
                              ? $_POST['page_status'] : 'draft';
                $pSlug      = trim($_POST['page_slug'] ?? '');
                $pHideTitle = isset($_POST['page_hide_title']) ? 1 : 0;
                if ($pId < 1 || empty($pTitle)) {
                    $pageActionError = 'Ungültige Eingaben.';
                } else {
                    $upData = ['title' => $pTitle, 'status' => $pStatus, 'hide_title' => $pHideTitle];
                    // Only overwrite content when SunEditor actually submitted something
                    if ($pContent !== null) {
                        $upData['content'] = $pContent;
                    }
                    if (!empty($pSlug)) {
                        $upData['slug'] = $pageManager->generateSlug($pSlug);
                    }
                    if ($pageManager->updatePage($pId, $upData)) {
                        header('Location: ?msg=updated');
                        exit;
                    }
                    $pageActionError = 'Fehler beim Aktualisieren der Seite.';
                }
                break;

            case 'delete_page':
                $pId = (int)($_POST['page_id'] ?? 0);
                if ($pId > 0 && $pageManager->deletePage($pId)) {
                    header('Location: ?msg=deleted');
                    exit;
                }
                $pageActionError = 'Fehler beim Löschen der Seite.';
                break;
        }
    }
}

// ── Action mode: new | edit | (list) ─────────────────────────────────────────
$action       = $_GET['action'] ?? '';
$editPageId   = (int)($_GET['id'] ?? 0);
$editPageData = null;

if ($action === 'edit' && $editPageId > 0) {
    $editPageData = $pageManager->getPage($editPageId);
    if (!$editPageData) {
        $action = '';
    }
}

// Success messages from redirect
if (isset($_GET['msg'])) {
    switch ($_GET['msg']) {
        case 'created':
            $createdSlug = trim($_GET['slug'] ?? '');
            $success = '✅ Seite erfolgreich erstellt.';
            if ($createdSlug) {
                $success .= ' URL: <a href="' . htmlspecialchars(SITE_URL . '/' . $createdSlug, ENT_QUOTES) . '" target="_blank">/' . htmlspecialchars($createdSlug, ENT_QUOTES) . '</a>';
            }
            break;
        case 'updated': $success = '✅ Seite erfolgreich aktualisiert.';  break;
        case 'deleted': $success = '🗑️ Seite erfolgreich gelöscht.';      break;
    }
}

// Get all pages
$pages = $pageManager->listPages();

$pagesCsrfToken = Security::instance()->generateToken('page_manage');

// Messages sammeln
$messages = [];
if (isset($success)) {
    $messages[] = ['type' => 'success', 'text' => $success];
}
if ($pageActionError) {
    $messages[] = ['type' => 'error', 'text' => $pageActionError];
}

// Load admin menu
require_once __DIR__ . '/partials/admin-menu.php';
renderAdminLayoutStart('Seiten', 'pages');
?>

<?php foreach ($messages as $m):
    $alertCls = $m['type'] === 'success' ? 'alert-success' : 'alert-danger';
?>
<div class="alert <?php echo $alertCls; ?> alert-dismissible" role="alert">
    <div class="d-flex"><div><?php echo $m['text']; ?></div></div>
    <a class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></a>
</div>
<?php endforeach; ?>

<?php /* ================================================================
        EDIT / NEU ANSICHT
   ================================================================ */
if ($action === 'new' || $action === 'edit'):
    $isEdit       = ($action === 'edit' && $editPageData !== null);
    // Neue Seiten standardmäßig als 'published' vorbelegen (nicht 'draft'),
    // damit die Seite nach dem Speichern direkt unter ihrer URL erreichbar ist.
    $pData        = $editPageData ?? ['id' => 0, 'title' => '', 'slug' => '', 'content' => '', 'status' => 'published', 'hide_title' => 0, 'created_at' => '', 'updated_at' => ''];
    $pContent     = $pData['content'] ?? '';
    // Fix: Ensure slug is always editable for new pages if title is empty
    $hasCustomSlug = $isEdit && !empty($pData['slug']);
?>

<div class="page-header d-print-none mb-3">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo $isEdit ? 'Bearbeiten' : 'Erstellen'; ?></div>
                <h2 class="page-title"><?php echo $isEdit ? '✏️ Seite bearbeiten' : '➕ Neue Seite'; ?></h2>
                <?php if ($isEdit): ?>
                <div class="text-secondary mt-1">ID #<?php echo (int)$pData['id']; ?> &middot; <code>/<?php echo htmlspecialchars($pData['slug'], ENT_QUOTES, 'UTF-8'); ?></code></div>
                <?php endif; ?>
            </div>
            <div class="col-auto ms-auto">
                <?php if ($isEdit): ?>
                <a href="<?php echo SITE_URL; ?>/<?php echo htmlspecialchars($pData['slug'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-secondary">👁️ Vorschau</a>
                <?php endif; ?>
                <a href="<?php echo SITE_URL; ?>/admin/pages" class="btn btn-secondary">↩️ Zurück</a>
                <button type="submit" form="pageEditorForm" class="btn btn-primary">💾 <?php echo $isEdit ? 'Aktualisieren' : 'Speichern'; ?></button>
            </div>
        </div>
    </div>
</div>

<form method="post"
      action="<?php echo SITE_URL; ?>/admin/pages"
      enctype="multipart/form-data"
      id="pageEditorForm">
    <input type="hidden" name="csrf_token"  value="<?php echo $pagesCsrfToken; ?>">
    <input type="hidden" name="page_action" value="<?php echo $isEdit ? 'update_page' : 'create_page'; ?>">
    <?php if ($isEdit): ?>
    <input type="hidden" name="page_id" value="<?php echo (int)$pData['id']; ?>">
    <?php endif; ?>

    <div class="row g-3">

        <!-- Haupt-Spalte -->
        <div class="col-lg-9">

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">📄 Inhalt</h3></div>
                <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Titel <span class="text-danger">*</span></label>
                    <input type="text"
                           id="page_title"
                           name="page_title"
                           class="form-control"
                           value="<?php echo htmlspecialchars($pData['title'], ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder="Seitentitel…"
                           required
                           style="font-size:1.1rem; font-weight:600;"
                           oninput="pageUpdateSlug(this.value)">
                </div>

                <div class="form-group">
                    <label class="form-label" style="display:flex; justify-content:space-between; align-items:center;">
                        <span>Slug / Permalink</span>
                        <label style="font-weight:400; color:#64748b; font-size:0.85rem; cursor:pointer;">
                            <input type="checkbox" id="slug_toggle" onchange="toggleSlugEdit(this.checked)"
                                   <?php echo $hasCustomSlug ? 'checked' : ''; ?>> Manuell anpassen
                        </label>
                    </label>
                    
                    <div id="slug_custom_section" style="<?php echo $hasCustomSlug ? '' : 'display:none;'; ?> margin-bottom:0.5rem;">
                        <div style="display:flex; align-items:center;">
                            <span style="background:#f1f5f9; border:1px solid #cbd5e1; border-right:0; padding:0.5rem 0.75rem; color:#64748b; border-radius:6px 0 0 6px;">/</span>
                            <input type="text"
                                   id="page_slug_input"
                                   class="form-control"
                                   style="border-top-left-radius:0; border-bottom-left-radius:0;"
                                   placeholder="eigener-url-pfad"
                                   value="<?php echo htmlspecialchars($pData['slug'], ENT_QUOTES, 'UTF-8'); ?>"
                                   oninput="updateSlugHidden(this.value)">
                        </div>
                    </div>
                    
                    <input type="hidden" id="page_slug_hidden" name="page_slug"
                           value="<?php echo $hasCustomSlug ? htmlspecialchars($pData['slug'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                    
                    <div style="font-size:0.85rem; color:#64748b;">
                        Vorschau: <span style="color:#0f172a;"><?php echo SITE_URL; ?>/<strong id="slugPreviewVal"><?php echo htmlspecialchars($pData['slug'], ENT_QUOTES, 'UTF-8'); ?></strong></span>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Inhalt</label>
                    <?php echo EditorService::getInstance()->render('page_content', $pContent, ['height' => 500]); ?>
                </div>
            </div>

        </div><!-- /.main-col -->

        <!-- Seiten-Spalte -->
        <div class="col-lg-3">

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">⚙️ Veröffentlichung</h3></div>
                <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select name="page_status" id="page_status" class="form-select">
                        <option value="published" <?php echo ($pData['status'] ?? 'published') === 'published' ? 'selected' : ''; ?>>✅ Veröffentlicht</option>
                        <option value="draft"     <?php echo ($pData['status'] ?? '') === 'draft'     ? 'selected' : ''; ?>>📝 Entwurf</option>
                        <option value="private"   <?php echo ($pData['status'] ?? '') === 'private'   ? 'selected' : ''; ?>>🔒 Privat</option>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label class="form-check">
                        <input type="checkbox" name="page_hide_title" value="1" class="form-check-input"
                               <?php echo !empty($pData['hide_title']) ? 'checked' : ''; ?>>
                        <span class="form-check-label">Seitentitel auf Seite ausblenden</span>
                    </label>
                    <small class="form-hint">Der Titel wird im Frontend nicht als H1 ausgegeben.</small>
                </div>

                <?php if ($isEdit): ?>
                <div style="font-size:0.85rem; color:#64748b; border-top:1px solid #f1f5f9; padding-top:1rem; margin-top:1rem; display:flex; flex-direction:column; gap:0.5rem;">
                    <div>📅 Erstellt: <?php echo date('d.m.Y', strtotime($pData['created_at'])); ?></div>
                    <div>✍️ Geändert: <?php echo date('d.m.Y', strtotime($pData['updated_at'])); ?></div>
                </div>
                <div style="margin-top:1rem; border-top:1px solid #f1f5f9; padding-top:1rem;">
                    <button type="button"
                            class="btn btn-danger btn-sm"
                            onclick="openDeletePageModal(<?php echo (int)$pData['id']; ?>, true)"
                            style="width:100%;">🗑️ Seite löschen</button>
                </div>
                <?php endif; ?>
            </div>

        </div><!-- /.side-col -->
    </div><!-- /.grid -->

</form>

<?php if ($isEdit): ?>
<form id="deletePageForm" method="post" action="<?php echo SITE_URL; ?>/admin/pages" style="display:none;">
    <input type="hidden" name="csrf_token"  value="<?php echo $pagesCsrfToken; ?>">
    <input type="hidden" name="page_action" value="delete_page">
    <input type="hidden" name="page_id"     value="<?php echo (int)$pData['id']; ?>">
</form>
<?php endif; ?>

<?php /* ================================================================
        LISTEN-ANSICHT
   ================================================================ */
else:
    // Status-Zähler
    $counts = ['all' => count($pages)];
    foreach ($pages as $pg) {
        $counts[$pg['status']] = ($counts[$pg['status']] ?? 0) + 1;
    }
    $filterStatus = $_GET['status'] ?? 'all';
    $filteredPages = $filterStatus === 'all'
        ? $pages
        : array_values(array_filter($pages, fn($pg) => $pg['status'] === $filterStatus));
?>

<div class="page-header d-print-none mb-3">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Verwaltung</div>
                <h2 class="page-title">📄 Seiten</h2>
                <div class="text-secondary mt-1"><?php echo count($filteredPages); ?> Seite<?php echo count($filteredPages) !== 1 ? 'n' : ''; ?></div>
            </div>
            <div class="col-auto ms-auto">
                <a href="<?php echo SITE_URL; ?>/admin/pages?action=new" class="btn btn-primary">➕ Neue Seite</a>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-3">
    <?php foreach (['all' => ['Alle', ''], 'published' => ['Veröffentlicht', '✅'], 'draft' => ['Entwürfe', '📝'], 'private' => ['Privat', '🔒']] as $s => [$label, $icon]): ?>
    <li class="nav-item">
        <a href="<?php echo SITE_URL; ?>/admin/pages?status=<?php echo $s; ?>"
           class="nav-link <?php echo $filterStatus === $s ? 'active' : ''; ?>">
            <?php echo $icon; ?> <?php echo $label; ?> <span class="badge bg-secondary ms-1"><?php echo $counts[$s] ?? 0; ?></span>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<?php if (empty($filteredPages)): ?>
<div class="empty-state">
    <p style="font-size:2.5rem; margin:0;">📄</p>
    <p><strong>Keine Seiten gefunden.</strong></p>
    <?php if ($filterStatus !== 'all'): ?>
    <p class="text-muted">In diesem Status gibt es keine Seiten.</p>
    <a href="<?php echo SITE_URL; ?>/admin/pages" class="btn btn-secondary" style="margin-top:1rem;">Alle anzeigen</a>
    <?php else: ?>
    <p class="text-muted">Erstellen Sie die erste Seite für Ihre Website.</p>
    <a href="<?php echo SITE_URL; ?>/admin/pages?action=new" class="btn btn-primary" style="margin-top:1rem;">➕ Erste Seite erstellen</a>
    <?php endif; ?>
</div>
<?php else: ?>
<div class="card">
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
        <thead>
            <tr>
                <th>Titel</th>
                <th>Slug / URL</th>
                <th>Status</th>
                <th>Erstellt</th>
                <th style="text-align:right;">Aktionen</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($filteredPages as $pg):
            $sbadge = match($pg['status']) {
                'published' => ['Veröffentlicht', 'bg-success-lt'],
                'draft'     => ['Entwurf', 'bg-secondary-lt'],
                'private'   => ['Privat', 'bg-yellow-lt'],
                default     => [ucfirst($pg['status']), 'bg-secondary-lt']
            };
        ?>
        <tr>
            <td>
                <a href="<?php echo SITE_URL; ?>/admin/pages?action=edit&id=<?php echo (int)$pg['id']; ?>"
                   style="font-weight:600; color:#1e293b; text-decoration:none;">
                    <?php echo htmlspecialchars($pg['title'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
            </td>
            <td style="color:#64748b; font-size:0.875rem;">
                <code style="background:#f1f5f9; padding:2px 4px; border-radius:4px;">/<?php echo htmlspecialchars($pg['slug'], ENT_QUOTES, 'UTF-8'); ?></code>
            </td>
            <td><span class="badge <?php echo $sbadge[1]; ?>"><?php echo $sbadge[0]; ?></span></td>
            <td style="font-size:0.875rem; color:#64748b;"><?php echo date('d.m.Y', strtotime($pg['created_at'])); ?></td>
            <td style="text-align:right; white-space:nowrap;">
                <div style="display:flex; justify-content:flex-end; gap:0.5rem;">
                    <a href="<?php echo SITE_URL; ?>/<?php echo htmlspecialchars($pg['slug'], ENT_QUOTES, 'UTF-8'); ?>"
                       target="_blank" class="btn btn-sm btn-secondary" title="Vorschau">👁️</a>
                    <a href="<?php echo SITE_URL; ?>/admin/pages?action=edit&id=<?php echo (int)$pg['id']; ?>"
                       class="btn btn-sm btn-secondary" title="Bearbeiten">✏️</a>
                    <button type="button" class="btn btn-sm btn-danger"
                            onclick="deletePage(<?php echo (int)$pg['id']; ?>)"
                            title="Löschen">🗑️</button>
                </div>
            </td>
        </tr>
        <?php endforeach; ?>
        </table>
    </div>
</div>
<?php endif; ?>

<form id="listDeletePageForm" method="post" action="<?php echo SITE_URL; ?>/admin/pages" style="display:none;">
    <input type="hidden" name="csrf_token"  value="<?php echo $pagesCsrfToken; ?>">
    <input type="hidden" name="page_action" value="delete_page">
    <input type="hidden" name="page_id"     id="listDeletePageId" value="">
</form>

<?php endif; // end views ?>

<!-- Seite löschen – Bootstrap 5 Modal -->
<div class="modal modal-blur fade" id="pageDeleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon mb-2 text-danger icon-lg" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/><path d="M12 16h.01"/></svg>
                <h3>Seite löschen</h3>
                <div class="text-secondary">Seite wirklich <strong>unwiderruflich löschen</strong>? Diese Aktion kann nicht rückgängig gemacht werden.</div>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col"><button type="button" class="btn w-100" data-bs-dismiss="modal">Abbrechen</button></div>
                        <div class="col"><button type="button" class="btn btn-danger w-100" id="pageDeleteConfirmBtn">🗑️ Löschen</button></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($action === 'new' || $action === 'edit'): ?>
<link  rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/suneditor/css/suneditor.min.css">
<script src="<?php echo SITE_URL; ?>/assets/suneditor/suneditor.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/suneditor/lang/de.js"></script>
<?php endif; ?>

<script>
function slugify(text) {
    return text.toLowerCase()
        .replace(/ä/g,'ae').replace(/ö/g,'oe').replace(/ü/g,'ue').replace(/ß/g,'ss')
        .replace(/[^a-z0-9\-]/g,'-').replace(/-+/g,'-').replace(/^-|-$/g,'');
}
function pageUpdateSlug(val) {
    const toggle = document.getElementById('slug_toggle');
    if (toggle && toggle.checked) return;
    const s = slugify(val);
    const preview = document.getElementById('slugPreviewVal');
    if (preview) preview.textContent = s ? s : '...';
}
function toggleSlugEdit(isChecked) {
    const sec = document.getElementById('slug_custom_section');
    const hidden = document.getElementById('page_slug_hidden');
    const input  = document.getElementById('page_slug_input');
    if (!sec) return;
    sec.style.display = isChecked ? '' : 'none';
    if (!isChecked && hidden) { hidden.value = ''; }
    if (isChecked && input && !input.value) {
        const title = document.getElementById('page_title');
        if (title) { input.value = slugify(title.value); updateSlugHidden(input.value); }
    }
}
function updateSlugHidden(val) {
    const hidden  = document.getElementById('page_slug_hidden');
    const preview = document.getElementById('slugPreviewVal');
    if (hidden)  hidden.value = val;
    if (preview) preview.textContent = val ? val : '...';
}
const _pageDelModal = new bootstrap.Modal(document.getElementById('pageDeleteModal'));
function deletePage(id) {
    openDeletePageModal(id, false);
}
function openDeletePageModal(id, isEditorView) {
    document.getElementById('pageDeleteConfirmBtn').onclick = function() {
        _pageDelModal.hide();
        if (isEditorView) {
            document.getElementById('deletePageForm').submit();
        } else {
            document.getElementById('listDeletePageId').value = id;
            document.getElementById('listDeletePageForm').submit();
        }
    };
    _pageDelModal.show();
}
document.addEventListener('DOMContentLoaded', function() {
    const pageForm = document.getElementById('pageEditorForm');
    if (pageForm && typeof SUNEDITOR !== 'undefined') {
        pageForm.addEventListener('submit', function() {
            const ta = pageForm.querySelector('textarea[name="page_content"]');
            if (ta && ta.id) {
                const ed = SUNEDITOR.get(ta.id);
                if (ed) ed.save();
            }
        });
    }
});
</script>

<?php renderAdminLayoutEnd(); ?>

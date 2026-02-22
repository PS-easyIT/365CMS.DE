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
                $pContent   = $_POST['page_content'] ?? '';
                $pStatus    = in_array($_POST['page_status'] ?? '', ['published', 'draft', 'private'])
                              ? $_POST['page_status'] : 'draft';
                $pSlug      = trim($_POST['page_slug'] ?? '');
                $pHideTitle = isset($_POST['page_hide_title']) ? 1 : 0;
                if (empty($pTitle)) {
                    $pageActionError = 'Bitte geben Sie einen Titel ein.';
                } else {
                    $newId = $pageManager->createPage($pTitle, $pContent, $pStatus, $currentUserId, $pHideTitle);
                    if ($newId > 0 && !empty($pSlug)) {
                        $safeSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $pSlug)));
                        $pageManager->updatePage($newId, ['slug' => $safeSlug]);
                    }
                    if ($newId > 0) {
                        header('Location: ?msg=created');
                        exit;
                    }
                    $pageActionError = 'Fehler beim Erstellen der Seite.';
                }
                break;

            case 'update_page':
                $pId        = (int)($_POST['page_id'] ?? 0);
                $pTitle     = trim($_POST['page_title'] ?? '');
                $pContent   = $_POST['page_content'] ?? null;  // null = not submitted (SunEditor not loaded)
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
                        $upData['slug'] = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $pSlug)));
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
        case 'created': $success = '✅ Seite erfolgreich erstellt.';      break;
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
    $cls = $m['type'] === 'success' ? 'alert alert-success' : 'alert alert-error';
?>
<div class="<?php echo $cls; ?>"><?php echo htmlspecialchars($m['text'], ENT_QUOTES, 'UTF-8'); ?></div>
<?php endforeach; ?>

<?php /* ================================================================
        EDIT / NEU ANSICHT
   ================================================================ */
if ($action === 'new' || $action === 'edit'):
    $isEdit       = ($action === 'edit' && $editPageData !== null);
    $pData        = $editPageData ?? ['id' => 0, 'title' => '', 'slug' => '', 'content' => '', 'status' => 'draft', 'hide_title' => 0, 'created_at' => '', 'updated_at' => ''];
    $pContent     = $pData['content'] ?? '';
    // Fix: Ensure slug is always editable for new pages if title is empty
    $hasCustomSlug = $isEdit && !empty($pData['slug']);
?>

<div class="admin-page-header">
    <div>
        <h2><?php echo $isEdit ? '✏️ Seite bearbeiten' : '➕ Neue Seite'; ?></h2>
        <?php if ($isEdit): ?>
        <p>ID #<?php echo (int)$pData['id']; ?> &nbsp;&middot;&nbsp; <code>/<?php echo htmlspecialchars($pData['slug'], ENT_QUOTES, 'UTF-8'); ?></code></p>
        <?php else: ?>
        <p>Neue statische Seite anlegen und veröffentlichen</p>
        <?php endif; ?>
    </div>
    <div class="header-actions">
        <?php if ($isEdit): ?>
        <a href="<?php echo SITE_URL; ?>/<?php echo htmlspecialchars($pData['slug'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank" class="btn btn-secondary">👁️ Vorschau</a>
        <?php endif; ?>
        <a href="<?php echo SITE_URL; ?>/admin/pages" class="btn btn-secondary">↩️ Zurück</a>
        <button type="submit" form="pageEditorForm" class="btn btn-primary">💾 <?php echo $isEdit ? 'Aktualisieren' : 'Speichern'; ?></button>
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

    <div class="form-grid" style="display:grid; grid-template-columns: 3fr 1fr; gap:1.5rem;">

        <!-- Haupt-Spalte -->
        <div style="display:flex; flex-direction:column; gap:1.5rem;">

            <div class="admin-card">
                <h3>📄 Inhalt</h3>
                <div class="form-group">
                    <label class="form-label">Titel <span style="color:#ef4444;">*</span></label>
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

                <div class="form-group">
                    <label class="form-label">Inhalt</label>
                    <?php echo EditorService::getInstance()->render('page_content', $pContent, ['height' => 500]); ?>
                </div>
            </div>

        </div><!-- /.main-col -->

        <!-- Seiten-Spalte -->
        <div style="display:flex; flex-direction:column; gap:1.5rem;">

            <div class="admin-card">
                <h3>⚙️ Veröffentlichung</h3>
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="page_status" id="page_status" class="form-control">
                        <option value="draft"     <?php echo ($pData['status'] ?? 'draft') === 'draft'     ? 'selected' : ''; ?>>📝 Entwurf</option>
                        <option value="published" <?php echo ($pData['status'] ?? '') === 'published' ? 'selected' : ''; ?>>✅ Veröffentlicht</option>
                        <option value="private"   <?php echo ($pData['status'] ?? '') === 'private'   ? 'selected' : ''; ?>>🔒 Privat</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="page_hide_title" value="1"
                               <?php echo !empty($pData['hide_title']) ? 'checked' : ''; ?>>
                        Seitentitel auf Seite ausblenden
                    </label>
                    <small class="form-text">Der Titel wird im Frontend nicht als H1 ausgegeben.</small>
                </div>

                <?php if ($isEdit): ?>
                <div style="font-size:0.85rem; color:#64748b; border-top:1px solid #f1f5f9; padding-top:1rem; margin-top:1rem; display:flex; flex-direction:column; gap:0.5rem;">
                    <div>📅 Erstellt: <?php echo date('d.m.Y', strtotime($pData['created_at'])); ?></div>
                    <div>✍️ Geändert: <?php echo date('d.m.Y', strtotime($pData['updated_at'])); ?></div>
                </div>
                <div style="margin-top:1rem; border-top:1px solid #f1f5f9; padding-top:1rem;">
                    <button type="submit" form="deletePageForm"
                            class="btn btn-danger btn-sm"
                            onclick="return confirm('Seite wirklich und unwiderruflich löschen?')"
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

<div class="admin-page-header">
    <div>
        <h2>📄 Seiten</h2>
        <p><?php echo count($filteredPages); ?> Seite<?php echo count($filteredPages) !== 1 ? 'n' : ''; ?> &nbsp;&middot;&nbsp; Statische Inhalte der Website</p>
    </div>
    <div class="header-actions">
        <a href="<?php echo SITE_URL; ?>/admin/pages?action=new" class="btn btn-primary">➕ Neue Seite</a>
    </div>
</div>

<div class="tabs" style="margin-bottom:1.5rem;">
    <?php foreach (['all' => ['Alle', ''], 'published' => ['Veröffentlicht', '✅'], 'draft' => ['Entwürfe', '📝'], 'private' => ['Privat', '🔒']] as $s => [$label, $icon]): ?>
    <a href="<?php echo SITE_URL; ?>/admin/pages?status=<?php echo $s; ?>"
       class="tab-btn <?php echo $filterStatus === $s ? 'active' : ''; ?>">
        <?php echo $icon; ?> <?php echo $label; ?> <span class="nav-badge" style="margin-left:0.35rem; font-size:0.75rem;"><?php echo $counts[$s] ?? 0; ?></span>
    </a>
    <?php endforeach; ?>
</div>

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
<div class="users-table-container">
    <table class="users-table">
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
            $smap   = ['published' => ['Veröffentlicht','active'], 'draft' => ['Entwurf','inactive'], 'private' => ['Privat','admin']];
            $sbadge = $smap[$pg['status']] ?? [ucfirst($pg['status']), ''];
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
            <td><span class="status-badge <?php echo $sbadge[1]; ?>"><?php echo $sbadge[0]; ?></span></td>
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
        </tbody>
    </table>
</div>
<?php endif; ?>

<form id="listDeletePageForm" method="post" action="<?php echo SITE_URL; ?>/admin/pages" style="display:none;">
    <input type="hidden" name="csrf_token"  value="<?php echo $pagesCsrfToken; ?>">
    <input type="hidden" name="page_action" value="delete_page">
    <input type="hidden" name="page_id"     id="listDeletePageId" value="">
</form>

<?php endif; // end views ?>

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
function deletePage(id) {
    if (confirm('Seite wirklich löschen?')) {
        document.getElementById('listDeletePageId').value = id;
        document.getElementById('listDeletePageForm').submit();
    }
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

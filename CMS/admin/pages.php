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
<style>
/* ── Pages Admin (gleich wie Posts) ─────────────────────────────────────── */
.posts-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;margin-bottom:1.5rem;}
.posts-tabs{display:flex;gap:0;border-bottom:2px solid #e2e8f0;margin-bottom:1.5rem;}
.posts-tab{padding:.5rem 1rem;font-size:.875rem;color:#64748b;text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;transition:color .15s;}
.posts-tab.active{color:#2563eb;border-bottom-color:#2563eb;font-weight:600;}
.posts-tab:hover{color:#1d4ed8;}
.posts-tab .badge{display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;padding:0 5px;background:#e2e8f0;border-radius:10px;font-size:.7rem;font-weight:700;margin-left:.3rem;}
.posts-tab.active .badge{background:#dbeafe;color:#1d4ed8;}
.posts-table{width:100%;border-collapse:collapse;font-size:.875rem;}
.posts-table th{background:#f8fafc;padding:.55rem .7rem;text-align:left;font-weight:600;color:#374151;border-bottom:2px solid #e2e8f0;white-space:nowrap;}
.posts-table td{padding:.6rem .7rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
.posts-table tr:hover td{background:#f8fafc;}
.col-actions{width:140px;text-align:right;}
.status-badge{display:inline-flex;align-items:center;gap:.2rem;padding:.2rem .5rem;border-radius:99px;font-size:.74rem;font-weight:600;}
.status-published{background:#dcfce7;color:#15803d;}
.status-draft{background:#fef9c3;color:#854d0e;}
.status-private{background:#ede9fe;color:#6d28d9;}
.btn-sm{padding:.3rem .65rem;font-size:.8rem;border-radius:5px;border:none;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:.2rem;transition:background .14s;}
.btn-primary{background:#2563eb;color:#fff;}.btn-primary:hover{background:#1d4ed8;}
.btn-secondary{background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;}.btn-secondary:hover{background:#e2e8f0;}
.btn-danger{background:#fee2e2;color:#b91c1c;}.btn-danger:hover{background:#fecaca;}
.btn-lg{padding:.55rem 1.2rem;font-size:.9375rem;}
.notice{padding:.65rem .9rem;border-radius:7px;margin-bottom:.9rem;font-size:.875rem;}
.notice-success{background:#dcfce7;color:#15803d;border:1px solid #86efac;}
.notice-error{background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5;}

/* Edit Layout (identisch Posts) */
.post-edit-layout{display:grid;grid-template-columns:1fr 275px;gap:1.5rem;align-items:start;}
@media(max-width:880px){.post-edit-layout{grid-template-columns:1fr;}}
.post-edit-main,.post-edit-side{display:flex;flex-direction:column;gap:1.2rem;}
.post-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:1.2rem;}
.post-card h3{font-size:.8375rem;font-weight:700;color:#374151;margin:0 0 .9rem;padding-bottom:.45rem;border-bottom:1px solid #f1f5f9;}
.field-group{margin-bottom:.9rem;}
.field-group label{display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:.3rem;}
.field-group input[type=text],.field-group select,.field-group textarea{width:100%;padding:.4rem .6rem;border:1px solid #cbd5e1;border-radius:6px;font-size:.875rem;box-sizing:border-box;background:#fff;color:#1e293b;}
.field-hint{font-size:.74rem;color:#94a3b8;margin-top:.2rem;}
.slug-row{font-size:.74rem;color:#64748b;margin-top:.25rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;}
</style>

<?php foreach ($messages as $m):
    $cls = $m['type'] === 'success' ? 'notice-success' : 'notice-error';
?>
<div class="notice <?php echo $cls; ?>"><?php echo htmlspecialchars($m['text'], ENT_QUOTES, 'UTF-8'); ?></div>
<?php endforeach; ?>

<?php /* ================================================================
        EDIT / NEU ANSICHT
   ================================================================ */
if ($action === 'new' || $action === 'edit'):
    $isEdit       = ($action === 'edit' && $editPageData !== null);
    $pData        = $editPageData ?? ['id' => 0, 'title' => '', 'slug' => '', 'content' => '', 'status' => 'draft', 'hide_title' => 0, 'created_at' => '', 'updated_at' => ''];
    $pContent     = $pData['content'] ?? '';
    $hasCustomSlug = $isEdit && !empty($pData['slug']);
?>

<div class="posts-header">
    <h2 style="margin:0;"><?php echo $isEdit ? '✏️ Seite bearbeiten' : '➕ Neue Seite'; ?></h2>
    <a href="<?php echo SITE_URL; ?>/admin/pages" class="btn-sm btn-secondary">← Alle Seiten</a>
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

    <div class="post-edit-layout">

        <!-- Haupt-Spalte -->
        <div class="post-edit-main">

            <div class="post-card">
                <div class="field-group" style="margin-bottom:.7rem;">
                    <label for="page_title">Titel *</label>
                    <input type="text"
                           id="page_title"
                           name="page_title"
                           value="<?php echo htmlspecialchars($pData['title'], ENT_QUOTES, 'UTF-8'); ?>"
                           placeholder="Seitentitel…"
                           required
                           style="font-size:1.05rem;font-weight:600;"
                           oninput="pageUpdateSlug(this.value)">
                </div>
                <div class="field-group" style="margin-bottom:0;">
                    <label for="page_slug_input">Slug / Permalink
                        <label style="font-weight:400;color:#94a3b8;margin-left:.5rem;">
                            <input type="checkbox" id="slug_toggle" onchange="toggleSlugEdit(this.checked)"
                                   <?php echo $hasCustomSlug ? 'checked' : ''; ?>> Anpassen
                        </label>
                    </label>
                    <div id="slug_custom_section" style="<?php echo $hasCustomSlug ? '' : 'display:none;'; ?>">
                        <input type="text"
                               id="page_slug_input"
                               placeholder="eigener-url-pfad"
                               value="<?php echo htmlspecialchars($pData['slug'], ENT_QUOTES, 'UTF-8'); ?>"
                               oninput="updateSlugHidden(this.value)">
                    </div>
                    <input type="hidden" id="page_slug_hidden" name="page_slug"
                           value="<?php echo $hasCustomSlug ? htmlspecialchars($pData['slug'], ENT_QUOTES, 'UTF-8') : ''; ?>">
                    <div class="slug-row"><?php echo SITE_URL; ?>/<strong id="slugPreviewVal"><?php echo htmlspecialchars($pData['slug'], ENT_QUOTES, 'UTF-8'); ?></strong></div>
                </div>
            </div>

            <div class="post-card" style="padding:0;overflow:hidden;">
                <div style="padding:.65rem 1.1rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:.85rem;font-weight:700;color:#374151;">📝 Inhalt</div>
                <div style="padding:1rem;">
                    <?php echo EditorService::getInstance()->render('page_content', $pContent, ['height' => 480]); ?>
                </div>
            </div>

        </div><!-- /.post-edit-main -->

        <!-- Seiten-Spalte -->
        <div class="post-edit-side">

            <div class="post-card">
                <h3>📋 Status & Sichtbarkeit</h3>
                <div class="field-group" style="margin-bottom:.9rem;">
                    <label>Status</label>
                    <select name="page_status" id="page_status">
                        <option value="draft"     <?php echo ($pData['status'] ?? 'draft') === 'draft'     ? 'selected' : ''; ?>>📝 Entwurf</option>
                        <option value="published" <?php echo ($pData['status'] ?? '') === 'published' ? 'selected' : ''; ?>>✅ Veröffentlicht</option>
                        <option value="private"   <?php echo ($pData['status'] ?? '') === 'private'   ? 'selected' : ''; ?>>🔒 Privat</option>
                    </select>
                </div>
                <div class="field-group" style="margin-bottom:.9rem;">
                    <label style="display:flex;align-items:center;gap:.5rem;font-size:.875rem;cursor:pointer;font-weight:400;">
                        <input type="checkbox" name="page_hide_title" value="1"
                               <?php echo !empty($pData['hide_title']) ? 'checked' : ''; ?>>
                        Seitentitel öffentlich ausblenden
                    </label>
                </div>
                <?php if ($isEdit): ?>
                <div style="font-size:.76rem;color:#94a3b8;border-top:1px solid #f1f5f9;padding-top:.6rem;display:flex;flex-direction:column;gap:.15rem;">
                    <span>Erstellt: <?php echo date('d.m.Y H:i', strtotime($pData['created_at'])); ?></span>
                    <span>Geändert: <?php echo date('d.m.Y H:i', strtotime($pData['updated_at'])); ?></span>
                    <span>ID: #<?php echo (int)$pData['id']; ?></span>
                </div>
                <?php endif; ?>
                <div style="display:flex;flex-direction:column;gap:.45rem;margin-top:.9rem;">
                    <button type="submit" class="btn-sm btn-primary btn-lg">💾 <?php echo $isEdit ? 'Aktualisieren' : 'Erstellen'; ?></button>
                    <?php if ($isEdit): ?>
                    <a href="<?php echo SITE_URL; ?>/<?php echo htmlspecialchars($pData['slug'], ENT_QUOTES, 'UTF-8'); ?>" target="_blank"
                       class="btn-sm btn-secondary" style="text-align:center;">👁️ Vorschau</a>
                    <button type="submit" form="deletePageForm"
                            class="btn-sm btn-danger"
                            onclick="return confirm('Seite wirklich löschen?')"
                            style="width:100%;">🗑️ Seite löschen</button>
                    <?php endif; ?>
                </div>
            </div>

        </div><!-- /.post-edit-side -->
    </div><!-- /.post-edit-layout -->
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

<div class="posts-header">
    <h2 style="margin:0;">📄 Seiten</h2>
    <a href="<?php echo SITE_URL; ?>/admin/pages?action=new" class="btn-sm btn-primary">➕ Neue Seite</a>
</div>

<div class="posts-tabs">
    <?php foreach (['all' => 'Alle', 'published' => 'Veröffentlicht', 'draft' => 'Entwürfe', 'private' => 'Privat'] as $s => $label): ?>
    <a href="<?php echo SITE_URL; ?>/admin/pages?status=<?php echo $s; ?>"
       class="posts-tab <?php echo $filterStatus === $s ? 'active' : ''; ?>">
        <?php echo $label; ?><span class="badge"><?php echo $counts[$s] ?? 0; ?></span>
    </a>
    <?php endforeach; ?>
</div>

<?php if (empty($filteredPages)): ?>
<div class="post-card" style="text-align:center;padding:3rem;color:#94a3b8;">
    <div style="font-size:3rem;margin-bottom:1rem;">📄</div>
    <p>Noch keine Seiten vorhanden.</p>
    <a href="<?php echo SITE_URL; ?>/admin/pages?action=new" class="btn-sm btn-primary btn-lg" style="margin-top:.75rem;display:inline-flex;">➕ Erste Seite erstellen</a>
</div>
<?php else: ?>
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:auto;">
    <table class="posts-table">
        <thead><tr>
            <th>Titel</th>
            <th style="width:200px;">Slug / URL</th>
            <th style="width:130px;">Status</th>
            <th style="width:140px;">Erstellt</th>
            <th class="col-actions"></th>
        </tr></thead>
        <tbody>
        <?php foreach ($filteredPages as $pg):
            $smap  = ['published' => ['Veröffentlicht','status-published'], 'draft' => ['Entwurf','status-draft'], 'private' => ['Privat','status-private']];
            $sbadge = $smap[$pg['status']] ?? [ucfirst($pg['status']), ''];
        ?>
        <tr>
            <td>
                <a href="<?php echo SITE_URL; ?>/admin/pages?action=edit&id=<?php echo (int)$pg['id']; ?>"
                   style="font-weight:600;color:#1e293b;text-decoration:none;">
                    <?php echo htmlspecialchars($pg['title'], ENT_QUOTES, 'UTF-8'); ?>
                </a>
                <div style="font-size:.74rem;color:#94a3b8;margin-top:.12rem;">
                    <code style="font-size:.7rem;">/<?php echo htmlspecialchars($pg['slug'], ENT_QUOTES, 'UTF-8'); ?></code>
                </div>
            </td>
            <td style="color:#64748b;font-size:.8rem;">/<?php echo htmlspecialchars($pg['slug'], ENT_QUOTES, 'UTF-8'); ?></td>
            <td><span class="status-badge <?php echo $sbadge[1]; ?>"><?php echo $sbadge[0]; ?></span></td>
            <td style="font-size:.78rem;color:#64748b;"><?php echo date('d.m.Y H:i', strtotime($pg['created_at'])); ?></td>
            <td class="col-actions" style="white-space:nowrap;">
                <a href="<?php echo SITE_URL; ?>/<?php echo htmlspecialchars($pg['slug'], ENT_QUOTES, 'UTF-8'); ?>"
                   target="_blank" class="btn-sm btn-secondary" title="Ansehen">👁️</a>
                <a href="<?php echo SITE_URL; ?>/admin/pages?action=edit&id=<?php echo (int)$pg['id']; ?>"
                   class="btn-sm btn-secondary" title="Bearbeiten">✏️</a>
                <button type="button" class="btn-sm btn-danger"
                        onclick="deletePage(<?php echo (int)$pg['id']; ?>)"
                        title="Löschen">🗑️</button>
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
    if (preview) preview.textContent = s;
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
    if (preview) preview.textContent = val;
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
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Seiten - <?php echo htmlspecialchars(SITE_NAME); ?></title>
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/main.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/admin.css">
    <?php renderAdminSidebarStyles(); ?>
    <style>
        /* ── Page Editor ─────────────────────────────────────────────────────── */
        .page-editor-topbar {
            display: flex;
            align-items: center;
            gap: 1rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 2px solid #e2e8f0;
        }
        .page-editor-headline {
            flex: 1;
            margin: 0;
            font-size: 1.25rem;
            color: #1e293b;
        }
        .page-editor-layout {
            display: grid;
            grid-template-columns: 1fr 280px;
            gap: 1.5rem;
            align-items: start;
        }
        @media (max-width: 900px) {
            .page-editor-layout { grid-template-columns: 1fr; }
        }
        .page-editor-section {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.25rem;
        }
        .form-label-strong {
            display: block;
            font-weight: 600;
            color: #374151;
            margin-bottom: 0.4rem;
            font-size: 0.9rem;
        }
        .page-title-input {
            font-size: 1.15rem;
            font-weight: 600;
            padding: 0.65rem 0.9rem;
        }
        /* Slug row */
        .slug-row {
            display: flex;
            align-items: center;
            gap: 0.6rem;
            margin-top: 0.75rem;
            flex-wrap: wrap;
        }
        .slug-preview-label { font-size: 0.82rem; color: #64748b; }
        .slug-preview-text  { font-size: 0.82rem; color: #3b82f6; background: #eff6ff; padding: 2px 7px; border-radius: 4px; }
        .slug-toggle-label  { display: flex; align-items: center; gap: 0.35rem; font-size: 0.82rem; color: #64748b; cursor: pointer; margin-left: auto; }
        .slug-toggle-label input[type=checkbox] { cursor: pointer; }
        .slug-custom-section {
            margin-top: 0.85rem;
            padding-top: 0.85rem;
            border-top: 1px dashed #e2e8f0;
        }
        .slug-input-group {
            display: flex;
            align-items: center;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 6px;
            overflow: hidden;
        }
        .slug-prefix-badge {
            padding: 0.5rem 0.75rem;
            background: #e2e8f0;
            color: #64748b;
            font-weight: 600;
            border-right: 1px solid #cbd5e1;
            font-size: 0.9rem;
        }
        .slug-input-group .form-control {
            border: none;
            background: transparent;
            border-radius: 0;
            box-shadow: none;
        }
        .slug-hint-note {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            color: #92400e;
            background: #fef3c7;
            border: 1px solid #fde68a;
            border-radius: 5px;
            padding: 0.4rem 0.7rem;
        }
        .page-editor-content-area { margin-top: 0.5rem; }
        /* Sidebar / Properties */
        .page-editor-sidebar { position: sticky; top: 1rem; }
        .page-props-panel {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            overflow: hidden;
        }
        .page-props-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.85rem 1rem;
            background: #f8fafc;
            border-bottom: 1px solid #e2e8f0;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.9rem;
            color: #374151;
            user-select: none;
        }
        .page-props-header:hover { background: #f1f5f9; }
        .props-toggle-icon { font-size: 0.75rem; transition: transform .2s; }
        .page-props-body { padding: 1rem; }
        .form-label-small { font-size: 0.82rem; font-weight: 600; color: #374151; display: block; margin-bottom: 0.3rem; }
        .form-control-sm { padding: 0.4rem 0.6rem; font-size: 0.875rem; }
        .props-info-row {
            display: flex;
            justify-content: space-between;
            font-size: 0.78rem;
            color: #64748b;
            padding: 0.25rem 0;
            border-top: 1px solid #f1f5f9;
        }
        .props-label { font-weight: 500; }
        .props-divider { border: none; border-top: 1px solid #e2e8f0; margin: 0.75rem 0; }
        .props-actions-row { margin-top: 0.85rem; }
        .btn-full { width: 100%; justify-content: center; }
        .action-cell { white-space: nowrap; }
    </style>
</head>
<body class="admin-body">
    
    <?php renderAdminSidebar('pages'); ?>
    
    <!-- Main Content -->
    <div class="admin-content">
        
        <!-- Page Header -->
        <div class="admin-page-header">
            <h2>Seiten verwalten</h2>
            <?php if ($action === ''): ?>
                <a href="?action=new" class="btn btn-primary">➕ Neue Seite</a>
            <?php endif; ?>
        </div>
        
        <!-- Success/Error Messages -->
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-error">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <!-- Content -->
        <?php if ($action === 'new' || $action === 'edit'): ?>
            <?php
                $isEdit        = ($action === 'edit' && $editPageData !== null);
                $formHeadline  = $isEdit ? '✏️ Seite bearbeiten' : '➕ Neue Seite erstellen';
                $pData         = $editPageData ?? ['title' => '', 'slug' => '', 'content' => '', 'status' => 'draft', 'hide_title' => 0, 'created_at' => '', 'updated_at' => ''];
                $pContent      = $pData['content'] ?? '';
                $hasCustomSlug = $isEdit && !empty($pData['slug']);
            ?>

            <?php if ($pageActionError): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($pageActionError); ?></div>
            <?php endif; ?>

            <form method="POST" class="page-editor-form" id="pageEditorForm">
                <input type="hidden" name="csrf_token"  value="<?php echo $pagesCsrfToken; ?>">
                <input type="hidden" name="page_action" value="<?php echo $isEdit ? 'update_page' : 'create_page'; ?>">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="page_id" value="<?php echo (int)$pData['id']; ?>">
                <?php endif; ?>

                <!-- Topbar: Zurück · Titel · Speichern -->
                <div class="page-editor-topbar">
                    <a href="?tab=pages" class="btn btn-secondary btn-sm">← Zurück</a>
                    <h3 class="page-editor-headline"><?php echo $formHeadline; ?></h3>
                    <button type="submit" class="btn btn-primary">
                        💾 <?php echo $isEdit ? 'Aktualisieren' : 'Seite erstellen'; ?>
                    </button>
                </div>

                <!-- 2-Spalten-Layout: Hauptbereich + Sidebar -->
                <div class="page-editor-layout">

                    <!-- ── Linke Hauptspalte ─────────────────────── -->
                    <div class="page-editor-main">

                        <!-- SEKTION 1: Titel + Slug -->
                        <div class="page-editor-section">
                            <div class="form-group">
                                <label for="page_title" class="form-label-strong">Seitentitel</label>
                                <input type="text"
                                       id="page_title"
                                       name="page_title"
                                       class="form-control page-title-input"
                                       value="<?php echo htmlspecialchars($pData['title']); ?>"
                                       placeholder="Titel der Seite…"
                                       required
                                       oninput="syncSlugPreview(this.value)">
                            </div>

                            <!-- Slug-Zeile (Preview + Toggle) -->
                            <div class="slug-row">
                                <span class="slug-preview-label">🔗 URL:</span>
                                <code class="slug-preview-text" id="slug_preview_display">/<?php echo htmlspecialchars($pData['slug']); ?></code>
                                <label class="slug-toggle-label">
                                    <input type="checkbox"
                                           id="slug_toggle"
                                           onchange="toggleSlugEdit(this.checked)"
                                           <?php echo $hasCustomSlug ? 'checked' : ''; ?>>
                                    Slug anpassen
                                </label>
                            </div>

                            <!-- Aufklappbare Slug-Eingabe -->
                            <div class="slug-custom-section" id="slug_custom_section"
                                 style="display:<?php echo $hasCustomSlug ? 'block' : 'none'; ?>">
                                <div class="slug-input-group">
                                    <span class="slug-prefix-badge">/</span>
                                    <input type="text"
                                           id="page_slug_input"
                                           class="form-control"
                                           placeholder="eigener-url-pfad"
                                           value="<?php echo htmlspecialchars($pData['slug']); ?>"
                                           oninput="updateSlugHidden(this.value)">
                                </div>
                                <p class="slug-hint-note">
                                    ⚠️ <strong>Hinweis:</strong> Der Titel wird nicht als URL-Pfad verwendet — der Slug bestimmt die öffentliche Adresse der Seite.
                                </p>
                            </div>
                            <!-- Hidden field: leer = Auto-Slug aus Titel -->
                            <input type="hidden" id="page_slug_hidden" name="page_slug"
                                   value="<?php echo $hasCustomSlug ? htmlspecialchars($pData['slug']) : ''; ?>">
                        </div>

                        <!-- SEKTION 2: Inhalt (SunEditor) -->
                        <div class="page-editor-section">
                            <label class="form-label-strong">Inhalt</label>
                            <div class="page-editor-content-area">
                                <?php echo EditorService::getInstance()->render('page_content', $pContent, ['height' => 480]); ?>
                            </div>
                        </div>

                    </div><!-- /page-editor-main -->

                    <!-- ── Rechte Sidebar: Eigenschaften ─────────── -->
                    <div class="page-editor-sidebar">
                        <div class="page-props-panel">
                            <div class="page-props-header" onclick="togglePropsPanel(this)">
                                <span>⚙️ Seiteneigenschaften</span>
                                <span class="props-toggle-icon">▼</span>
                            </div>
                            <div class="page-props-body" id="page_props_body">

                                <div class="form-group">
                                    <label for="page_status" class="form-label-small">Status</label>
                                    <select id="page_status" name="page_status" class="form-control form-control-sm">
                                        <option value="draft"     <?php echo ($pData['status'] ?? 'draft') === 'draft'     ? 'selected' : ''; ?>>📝 Entwurf</option>
                                        <option value="published" <?php echo ($pData['status'] ?? '')      === 'published' ? 'selected' : ''; ?>>✅ Veröffentlicht</option>
                                        <option value="private"   <?php echo ($pData['status'] ?? '')      === 'private'   ? 'selected' : ''; ?>>🔒 Privat</option>
                                    </select>
                                </div>

                                <div class="form-group" style="margin-top:0.85rem; padding-top:0.75rem; border-top:1px solid #f1f5f9;">
                                    <label class="form-label-small" style="margin-bottom:0.5rem;">Darstellung</label>
                                    <label style="display:flex; align-items:center; gap:0.5rem; font-size:0.85rem; color:#374151; cursor:pointer; user-select:none;">
                                        <input type="checkbox"
                                               id="page_hide_title"
                                               name="page_hide_title"
                                               value="1"
                                               <?php echo !empty($pData['hide_title']) ? 'checked' : ''; ?>
                                               style="width:16px; height:16px; cursor:pointer;">
                                        <span>Seitentitel auf Publicseite ausblenden</span>
                                    </label>
                                    <p style="margin:0.35rem 0 0 1.6rem; font-size:0.78rem; color:#64748b;">Der Titel bleibt im Admin sichtbar.</p>
                                </div>

                                <?php if ($isEdit): ?>
                                    <div class="props-info-row">
                                        <span class="props-label">Erstellt:</span>
                                        <span class="props-value"><?php echo date('d.m.Y H:i', strtotime($pData['created_at'])); ?></span>
                                    </div>
                                    <div class="props-info-row">
                                        <span class="props-label">Geändert:</span>
                                        <span class="props-value"><?php echo date('d.m.Y H:i', strtotime($pData['updated_at'])); ?></span>
                                    </div>
                                    <div class="props-info-row">
                                        <span class="props-label">ID:</span>
                                        <span class="props-value">#<?php echo (int)$pData['id']; ?></span>
                                    </div>
                                <?php endif; ?>

                                <div class="props-actions-row">
                                    <button type="submit" class="btn btn-primary btn-full">
                                        💾 <?php echo $isEdit ? 'Aktualisieren' : 'Erstellen'; ?>
                                    </button>
                                    <?php if ($isEdit): ?>
                                        <hr class="props-divider">
                                        <button type="submit" form="deletePageForm" class="btn btn-danger btn-full" onclick="return confirm('Seite wirklich unwiderruflich löschen?');">
                                            🗑️ Seite löschen
                                        </button>
                                    <?php endif; ?>
                                </div>

                            </div><!-- /page-props-body -->
                        </div><!-- /page-props-panel -->
                    </div><!-- /page-editor-sidebar -->

                </div><!-- /page-editor-layout -->
            </form>

            <?php if ($isEdit): ?>
                <form id="deletePageForm" method="POST" style="display:none;">
                    <input type="hidden" name="csrf_token"  value="<?php echo $pagesCsrfToken; ?>">
                    <input type="hidden" name="page_action" value="delete_page">
                    <input type="hidden" name="page_id"     value="<?php echo (int)$pData['id']; ?>">
                </form>
            <?php endif; ?>

        <?php else: ?>
            <!-- ── Seiten-Liste ────────────────────────────────── -->
            <?php if ($pageActionError): ?>
                <div class="alert alert-error"><?php echo htmlspecialchars($pageActionError); ?></div>
            <?php endif; ?>

            <?php if (!empty($pages)): ?>
                <div class="users-table-container">
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Titel</th>
                                <th>Slug</th>
                                <th>Status</th>
                                <th>Erstellt</th>
                                <th>Aktionen</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pages as $page): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($page['title']); ?></strong></td>
                                    <td><code><?php echo htmlspecialchars($page['slug']); ?></code></td>
                                    <td>
                                        <span class="status-badge status-<?php echo $page['status']; ?>">
                                            <?php
                                                $statusLabels = ['published' => '✅ Veröffentlicht', 'draft' => '📝 Entwurf', 'private' => '🔒 Privat'];
                                                echo $statusLabels[$page['status']] ?? ucfirst($page['status']);
                                            ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($page['created_at'])); ?></td>
                                    <td class="action-cell">
                                        <a href="?action=edit&id=<?php echo (int)$page['id']; ?>"
                                           class="btn btn-secondary btn-sm">✏️ Bearbeiten</a>
                                        <form method="POST" style="display:inline;"
                                              onsubmit="return confirm('Seite wirklich löschen?');">
                                            <input type="hidden" name="csrf_token"  value="<?php echo $pagesCsrfToken; ?>">
                                            <input type="hidden" name="page_action" value="delete_page">
                                            <input type="hidden" name="page_id"     value="<?php echo (int)$page['id']; ?>">
                                            <button type="submit" class="btn btn-danger btn-sm">🗑️</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="empty-state">
                    <h3>Keine Seiten vorhanden</h3>
                    <p class="text-muted">Erstellen Sie Ihre erste Seite mit dem Button oben.</p>
                </div>
            <?php endif; ?>

        <?php endif; ?>
        
    </div>
    
    <script src="<?php echo SITE_URL; ?>/assets/js/admin.js"></script>
    <script>
        // Simple WYSIWYG Editor Functions
        function insertTag(tag, textareaId, selfClosing = false) {
            const textarea = document.getElementById(textareaId);
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;
            const selectedText = textarea.value.substring(start, end);
            const before = textarea.value.substring(0, start);
            const after = textarea.value.substring(end);
            
            let insertText;
            if (selfClosing) {
                insertText = `<${tag}>`;
            } else {
                insertText = selectedText 
                    ? `<${tag}>${selectedText}</${tag}>` 
                    : `<${tag}></${tag}>`;
            }
            
            textarea.value = before + insertText + after;
            textarea.focus();
            
            // Set cursor position
            const newPos = selfClosing ? start + insertText.length : start + tag.length + 2;
            textarea.setSelectionRange(newPos, newPos);
        }
        
        function insertLink(textareaId) {
            const url = prompt('URL eingeben:', 'https://');
            if (!url) return;
            
            const linkText = prompt('Link-Text eingeben:', url);
            if (!linkText) return;
            
            const textarea = document.getElementById(textareaId);
            const start = textarea.selectionStart;
            const before = textarea.value.substring(0, start);
            const after = textarea.value.substring(start);
            
            const link = `<a href="${url}">${linkText}</a>`;
            textarea.value = before + link + after;
            textarea.focus();
        }

        // ── Page Editor: Slug-Funktionen ────────────────────────────────────
        function slugify(text) {
            return text
                .toLowerCase()
                .trim()
                .replace(/[äöüß]/g, c => ({ä:'ae',ö:'oe',ü:'ue',ß:'ss'}[c]))
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/^-+|-+$/g, '');
        }

        function syncSlugPreview(titleValue) {
            const preview = document.getElementById('slug_preview_display');
            const toggle  = document.getElementById('slug_toggle');
            if (!preview) return;
            // Only auto-update when custom slug is NOT active
            if (!toggle || !toggle.checked) {
                const slug = slugify(titleValue);
                preview.textContent = '/' + (slug || '');
            }
        }

        function toggleSlugEdit(isChecked) {
            const section = document.getElementById('slug_custom_section');
            const hidden  = document.getElementById('page_slug_hidden');
            const input   = document.getElementById('page_slug_input');
            const preview = document.getElementById('slug_preview_display');
            if (!section) return;
            if (isChecked) {
                section.style.display = 'block';
                // Pre-fill input with current auto-slug if empty
                if (input && !input.value) {
                    const titleEl = document.getElementById('page_title');
                    if (titleEl) {
                        const autoSlug = slugify(titleEl.value);
                        input.value = autoSlug;
                        if (hidden) hidden.value = autoSlug;
                        if (preview) preview.textContent = '/' + autoSlug;
                    }
                }
            } else {
                section.style.display = 'none';
                if (hidden) hidden.value = '';
                // Restore auto-preview
                const titleEl = document.getElementById('page_title');
                if (titleEl && preview) {
                    preview.textContent = '/' + slugify(titleEl.value);
                }
            }
        }

        function updateSlugHidden(val) {
            const hidden  = document.getElementById('page_slug_hidden');
            const preview = document.getElementById('slug_preview_display');
            if (hidden)  hidden.value = val;
            if (preview) preview.textContent = '/' + val;
        }

        // ── Seiteneigenschaften Panel Toggle ───────────────────────────────
        function togglePropsPanel(header) {
            const body = document.getElementById('page_props_body');
            const icon = header.querySelector('.props-toggle-icon');
            if (!body) return;
            const isOpen = body.style.display !== 'none';
            body.style.display = isOpen ? 'none' : 'block';
            if (icon) icon.style.transform = isOpen ? 'rotate(-90deg)' : 'rotate(0deg)';
        }

        // Init slug preview on page load (edit mode)
        document.addEventListener('DOMContentLoaded', function() {
            const titleEl  = document.getElementById('page_title');
            const preview  = document.getElementById('slug_preview_display');
            const toggle   = document.getElementById('slug_toggle');
            if (titleEl && preview && toggle && !toggle.checked) {
                preview.textContent = '/' + slugify(titleEl.value);
            }

            // ── SunEditor sync before form submit ───────────────────────────
            // SunEditor speichert Inhalt in einer versteckten Textarea.
            // Ohne explizites .save() kann der content beim Submit leer sein.
            const pageForm = document.getElementById('pageEditorForm');
            if (pageForm) {
                pageForm.addEventListener('submit', function(e) {
                    if (typeof SUNEDITOR === 'undefined') return;
                    const ta = pageForm.querySelector('textarea[name="page_content"]');
                    if (ta && ta.id) {
                        const ed = SUNEDITOR.get(ta.id);
                        if (ed) {
                            ed.save(); // Schreibt Editor-Inhalt zurück in die Textarea
                        }
                    }
                });
            }
        });
    </script>
    
</body>
</html>

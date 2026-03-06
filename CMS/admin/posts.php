<?php
declare(strict_types=1);

/**
 * Admin: Beiträge (Blog-Posts)
 *
 * Vollständige CRUD-Verwaltung für Blog-Beiträge.
 * Views: list | edit (create/update) | categories
 *
 * @package CMSv2\Admin
 */

require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Security;
use CMS\Database;
use CMS\Services\EditorService;

if (!defined('ABSPATH')) {
    exit;
}

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL);
    exit;
}

$security = Security::instance();
$db       = Database::instance();
$user     = Auth::instance()->getCurrentUser();
$prefix   = $db->getPrefix();

// ── Aktive Ansicht ─────────────────────────────────────────────────────────────
$view    = in_array($_GET['view'] ?? '', ['list', 'edit', 'categories'], true) ? $_GET['view'] : 'list';
$editId  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status  = in_array($_GET['status'] ?? '', ['all', 'published', 'draft', 'trash'], true) ? $_GET['status'] : 'all';
$search  = trim($_GET['search'] ?? '');
$page    = max(1, (int)($_GET['p'] ?? 1));
$perPage = 20;

$messages = [];

// ── Hilfsfunktionen ────────────────────────────────────────────────────────────
function posts_slugify(string $text): string
{
    $text = mb_strtolower($text, 'UTF-8');
    $text = preg_replace('/[äÄ]/u', 'ae', $text);
    $text = preg_replace('/[öÖ]/u', 'oe', $text);
    $text = preg_replace('/[üÜ]/u', 'ue', $text);
    $text = preg_replace('/ß/u', 'ss', $text);
    $text = preg_replace('/[^a-z0-9\-]/u', '-', $text);
    $text = preg_replace('/-+/', '-', $text);
    return trim($text, '-');
}

function posts_unique_slug(Database $db, string $slug, int $excludeId = 0): string
{
    $base    = $slug;
    $counter = 1;
    do {
        $existing = $db->get_var(
            "SELECT id FROM {$db->getPrefix()}posts WHERE slug = ? AND id != ?",
            [$slug, $excludeId]
        );
        if ($existing === null) break;
        $slug = $base . '-' . $counter++;
    } while (true);
    return $slug;
}

// ── POST-Verarbeitung ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['_action'])) {

    if (!$security->verifyToken($_POST['_csrf'] ?? '', 'admin_posts')) {
        $messages[] = ['type' => 'error', 'text' => 'Ungültiger Sicherheits-Token.'];
    } else {

        $action = $_POST['_action'];

        // ── Beitrag speichern (Neu / Update) ────────────────────────────────
        if ($action === 'save_post') {
            $postTitle    = $security->sanitize($_POST['post_title']   ?? '', 'text');
            $postSlug     = $security->sanitize($_POST['post_slug']    ?? '', 'text');
            $postContent  = $_POST['post_content'] ?? '';
            $postExcerpt  = $security->sanitize($_POST['post_excerpt'] ?? '', 'text');
            $postStatus   = in_array($_POST['post_status'] ?? '', ['draft', 'published', 'trash'], true)
                            ? $_POST['post_status'] : 'draft';
            $postCategory = (int)($_POST['post_category'] ?? 0) ?: null;
            $postTags     = $security->sanitize($_POST['post_tags'] ?? '', 'text');
            $allowComments = isset($_POST['allow_comments']) ? 1 : 0;
            $metaTitle    = $security->sanitize($_POST['meta_title']       ?? '', 'text');
            $metaDesc     = $security->sanitize($_POST['meta_description'] ?? '', 'text');
            $publishedAt  = null;

            if ($postStatus === 'published') {
                $publishedAt = !empty($_POST['published_at'])
                    ? date('Y-m-d H:i:s', strtotime($_POST['published_at']))
                    : date('Y-m-d H:i:s');
            }

            if (empty($postTitle)) {
                $messages[] = ['type' => 'error', 'text' => 'Titel darf nicht leer sein.'];
            } else {
                if (empty($postSlug)) {
                    $postSlug = posts_slugify($postTitle);
                } else {
                    $postSlug = posts_slugify($postSlug);
                }
                $updateId = (int)($_POST['post_id'] ?? 0);
                $postSlug = posts_unique_slug($db, $postSlug, $updateId);

                // Featured Image Upload
                $featuredImage = $security->sanitize($_POST['existing_featured_image'] ?? '', 'text');
                if (isset($_FILES['featured_image']) && $_FILES['featured_image']['error'] === UPLOAD_ERR_OK) {
                    $ext = strtolower(pathinfo($_FILES['featured_image']['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                        $uploadDir = ABSPATH . 'uploads/posts/';
                        if (!is_dir($uploadDir)) { mkdir($uploadDir, 0755, true); }
                        $filename = 'post-' . time() . '-' . bin2hex(random_bytes(4)) . '.' . $ext;
                        if (move_uploaded_file($_FILES['featured_image']['tmp_name'], $uploadDir . $filename)) {
                            $featuredImage = SITE_URL . '/uploads/posts/' . $filename;
                        }
                    } else {
                        $messages[] = ['type' => 'error', 'text' => 'Nur JPG, PNG, WEBP, GIF erlaubt.'];
                    }
                }
                if (isset($_POST['remove_featured_image']) && $_POST['remove_featured_image'] === '1') {
                    $featuredImage = '';
                }

                if (empty($messages)) {
                    if ($updateId > 0) {
                        $db->execute(
                            "UPDATE {$prefix}posts SET
                                title=?, slug=?, content=?, excerpt=?, featured_image=?,
                                status=?, category_id=?, tags=?, allow_comments=?,
                                meta_title=?, meta_description=?, published_at=?, updated_at=NOW()
                             WHERE id=?",
                            [$postTitle, $postSlug, $postContent, $postExcerpt, $featuredImage,
                             $postStatus, $postCategory, $postTags, $allowComments,
                             $metaTitle, $metaDesc, $publishedAt, $updateId]
                        );
                        $messages[] = ['type' => 'success', 'text' => 'Beitrag erfolgreich aktualisiert.'];
                        $editId = $updateId;
                    } else {
                        $db->execute(
                            "INSERT INTO {$prefix}posts
                                (title, slug, content, excerpt, featured_image, status, author_id,
                                 category_id, tags, allow_comments, meta_title, meta_description,
                                 published_at, created_at, updated_at)
                             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW(),NOW())",
                            [$postTitle, $postSlug, $postContent, $postExcerpt, $featuredImage,
                             $postStatus, (int)$user->id, $postCategory, $postTags, $allowComments,
                             $metaTitle, $metaDesc, $publishedAt]
                        );
                        $newId = $db->insert_id();
                        header('Location: ' . SITE_URL . '/admin/posts?view=edit&id=' . $newId
                            . '&msg=' . urlencode('Beitrag erstellt.') . '&mtype=success');
                        exit;
                    }
                }
            }
            $view   = 'edit';
            $editId = (int)($_POST['post_id'] ?? $editId);
        }

        // ── Beitrag löschen ──────────────────────────────────────────────────
        if ($action === 'delete_post') {
            $delId   = (int)($_POST['post_id']    ?? 0);
            $delMode = $_POST['delete_mode'] ?? 'trash';
            if ($delId > 0) {
                if ($delMode === 'permanent') {
                    $db->execute("DELETE FROM {$prefix}posts WHERE id=?", [$delId]);
                    $messages[] = ['type' => 'success', 'text' => 'Beitrag endgültig gelöscht.'];
                } else {
                    $db->execute("UPDATE {$prefix}posts SET status='trash', updated_at=NOW() WHERE id=?", [$delId]);
                    $messages[] = ['type' => 'success', 'text' => 'Beitrag in den Papierkorb verschoben.'];
                }
            }
            $view = 'list';
        }

        // ── Bulk-Aktion ──────────────────────────────────────────────────────
        if ($action === 'bulk' && !empty($_POST['bulk_ids'])) {
            $bulkAction = $_POST['bulk_action'] ?? '';
            $ids = array_filter(array_map('intval', (array)$_POST['bulk_ids']));
            if (!empty($ids)) {
                $phs = implode(',', array_fill(0, count($ids), '?'));
                if ($bulkAction === 'publish') {
                    $db->execute("UPDATE {$prefix}posts SET status='published', published_at=COALESCE(published_at,NOW()), updated_at=NOW() WHERE id IN ($phs)", $ids);
                    $messages[] = ['type' => 'success', 'text' => count($ids) . ' Beitrag/Beiträge veröffentlicht.'];
                } elseif ($bulkAction === 'draft') {
                    $db->execute("UPDATE {$prefix}posts SET status='draft', updated_at=NOW() WHERE id IN ($phs)", $ids);
                    $messages[] = ['type' => 'success', 'text' => count($ids) . ' Beitrag/Beiträge als Entwurf gesetzt.'];
                } elseif ($bulkAction === 'trash') {
                    $db->execute("UPDATE {$prefix}posts SET status='trash', updated_at=NOW() WHERE id IN ($phs)", $ids);
                    $messages[] = ['type' => 'success', 'text' => count($ids) . ' Beiträge in Papierkorb verschoben.'];
                } elseif ($bulkAction === 'delete') {
                    $db->execute("DELETE FROM {$prefix}posts WHERE id IN ($phs)", $ids);
                    $messages[] = ['type' => 'success', 'text' => count($ids) . ' Beiträge endgültig gelöscht.'];
                }
            }
            $view = 'list';
        }

        // ── Kategorie speichern ──────────────────────────────────────────────
        if ($action === 'save_category') {
            $catName = $security->sanitize($_POST['cat_name'] ?? '', 'text');
            $catSlug = $security->sanitize($_POST['cat_slug'] ?? '', 'text');
            $catDesc = $security->sanitize($_POST['cat_desc'] ?? '', 'text');
            $catId   = (int)($_POST['cat_id'] ?? 0);
            if (empty($catName)) {
                $messages[] = ['type' => 'error', 'text' => 'Kategoriename darf nicht leer sein.'];
            } else {
                if (empty($catSlug)) $catSlug = posts_slugify($catName);
                if ($catId > 0) {
                    $db->execute("UPDATE {$prefix}post_categories SET name=?, slug=?, description=? WHERE id=?",
                        [$catName, $catSlug, $catDesc, $catId]);
                    $messages[] = ['type' => 'success', 'text' => 'Kategorie aktualisiert.'];
                } else {
                    $db->execute("INSERT INTO {$prefix}post_categories (name, slug, description) VALUES (?,?,?)",
                        [$catName, $catSlug, $catDesc]);
                    $messages[] = ['type' => 'success', 'text' => 'Kategorie erstellt.'];
                }
            }
            $view = 'categories';
        }

        // ── Kategorie löschen ────────────────────────────────────────────────
        if ($action === 'delete_category') {
            $delCatId = (int)($_POST['cat_id'] ?? 0);
            if ($delCatId > 0) {
                $db->execute("UPDATE {$prefix}posts SET category_id=NULL WHERE category_id=?", [$delCatId]);
                $db->execute("DELETE FROM {$prefix}post_categories WHERE id=?", [$delCatId]);
                $messages[] = ['type' => 'success', 'text' => 'Kategorie gelöscht.'];
            }
            $view = 'categories';
        }

        // PRG-Redirect (nur wenn keine Fehler und kein Edit-Modus)
        $hasError = array_filter($messages, fn($m) => $m['type'] === 'error');
        if (empty($hasError) && $view !== 'edit') {
            $qs = '?view=' . $view;
            if (!empty($messages)) {
                $qs .= '&msg=' . urlencode($messages[0]['text']) . '&mtype=' . $messages[0]['type'];
            }
            header('Location: ' . SITE_URL . '/admin/posts' . $qs);
            exit;
        }
    }
}

// ── GET-Meldungen ──────────────────────────────────────────────────────────────
if (isset($_GET['msg'])) {
    $messages[] = [
        'type' => in_array($_GET['mtype'] ?? '', ['success','error','info'], true) ? $_GET['mtype'] : 'info',
        'text' => urldecode($_GET['msg']),
    ];
}

// ── Stammdaten ─────────────────────────────────────────────────────────────────
$categories = $db->get_results(
    "SELECT * FROM {$prefix}post_categories ORDER BY name ASC"
) ?: [];

// H-13: Batch-Query statt N+1-Einzelabfragen für Status-Zählung
$statusCountRows = $db->get_results(
    "SELECT status, COUNT(*) AS cnt FROM {$prefix}posts GROUP BY status"
);
$counts = ['all' => 0, 'published' => 0, 'draft' => 0, 'trash' => 0];
foreach ($statusCountRows as $sc) {
    if ($sc->status === 'trash') {
        $counts['trash'] = (int) $sc->cnt;
    } else {
        $counts['all'] += (int) $sc->cnt;
        if (isset($counts[$sc->status])) {
            $counts[$sc->status] = (int) $sc->cnt;
        }
    }
}

$csrf = $security->generateToken('admin_posts');

require_once __DIR__ . '/partials/admin-menu.php';
if ($view === 'edit') {
    EditorService::getInstance();
}
renderAdminLayoutStart('Beiträge', 'posts');
?>

<?php foreach ($messages as $m):
    $cls = match($m['type']){'success'=>'alert-success','error'=>'alert-danger',default=>''};
?>
<div class="alert <?php echo $cls; ?> alert-dismissible" role="alert">
    <div class="d-flex"><div><?php echo htmlspecialchars($m['text'], ENT_QUOTES, 'UTF-8'); ?></div></div>
    <a class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></a>
</div>
<?php endforeach; ?>

<div id="postsNoticeContainer"></div>

<?php /* ===============================================================
        KATEGORIEN-ANSICHT
   =============================================================== */
if ($view === 'categories'): ?>

<div class="page-header d-print-none mb-3">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Verwaltung</div>
                <h2 class="page-title">🏷️ Kategorien</h2>
                <div class="text-secondary mt-1"><?php echo count($categories); ?> Kategorie<?php echo count($categories) !== 1 ? 'n' : ''; ?></div>
            </div>
            <div class="col-auto ms-auto">
                <a href="<?php echo SITE_URL; ?>/admin/posts" class="btn btn-secondary">← Zu Beiträgen</a>
            </div>
        </div>
    </div>
</div>
<div class="row g-3" style="align-items:start;">

    <?php
    $editCat = null;
    if (!empty($_GET['cat_id'])) {
        $editCat = $db->get_row("SELECT * FROM {$prefix}post_categories WHERE id=?", [(int)$_GET['cat_id']]);
    }
    ?>
    <div class="col-lg-6">
    <div class="card">
        <div class="card-header"><h3 class="card-title"><?php echo $editCat ? '✏️ Kategorie bearbeiten' : '➕ Neue Kategorie'; ?></h3></div>
        <div class="card-body">
        <form method="post" action="<?php echo SITE_URL; ?>/admin/posts?view=categories">
            <input type="hidden" name="_csrf"    value="<?php echo $csrf; ?>">
            <input type="hidden" name="_action"  value="save_category">
            <?php if ($editCat): ?><input type="hidden" name="cat_id" value="<?php echo (int)$editCat->id; ?>"><?php endif; ?>
            <div class="mb-3">
                <label class="form-label">Name <span class="text-danger">*</span></label>
                <input type="text" name="cat_name" class="form-control" value="<?php echo htmlspecialchars($editCat->name ?? '', ENT_QUOTES); ?>" required>
            </div>
            <div class="mb-3">
                <label class="form-label">Slug</label>
                <input type="text" name="cat_slug" class="form-control" value="<?php echo htmlspecialchars($editCat->slug ?? '', ENT_QUOTES); ?>" placeholder="auto-generiert">
            </div>
            <div class="mb-3">
                <label class="form-label">Beschreibung</label>
                <textarea name="cat_desc" class="form-control"><?php echo htmlspecialchars($editCat->description ?? '', ENT_QUOTES); ?></textarea>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-primary"><?php echo $editCat ? '💾 Speichern' : '➕ Erstellen'; ?></button>
                <?php if ($editCat): ?><a href="<?php echo SITE_URL; ?>/admin/posts?view=categories" class="btn btn-secondary">Abbrechen</a><?php endif; ?>
            </div>
        </form>
        </div>
    </div>
    </div>

    <div class="col-lg-6">
    <div class="card">
        <div class="card-header"><h3 class="card-title">📋 Alle Kategorien (<?php echo count($categories); ?>)</h3></div>
        <div class="card-body">
        <?php if (empty($categories)): ?>
            <p style="color:#94a3b8;font-size:.875rem;">Noch keine Kategorien vorhanden.</p>
        <?php else: ?>
        <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead><tr><th>Name</th><th>Slug</th><th style="text-align:right;">Beiträge</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($categories as $cat):
                $cc = (int)$db->get_var("SELECT COUNT(*) FROM {$prefix}posts WHERE category_id=? AND status!='trash'", [(int)$cat->id]);
            ?>
            <tr>
                <td><strong><?php echo htmlspecialchars($cat->name, ENT_QUOTES); ?></strong></td>
                <td style="color:#64748b;font-size:.8rem;"><?php echo htmlspecialchars($cat->slug, ENT_QUOTES); ?></td>
                <td style="text-align:right;"><?php echo $cc; ?></td>
                <td style="text-align:right;">
                    <a href="?view=categories&cat_id=<?php echo (int)$cat->id; ?>" class="btn btn-secondary btn-sm">✏️</a>
                    <form id="catDeleteForm_<?php echo (int)$cat->id; ?>" method="post" action="<?php echo SITE_URL; ?>/admin/posts?view=categories">
                        <input type="hidden" name="_csrf"    value="<?php echo $csrf; ?>">
                        <input type="hidden" name="_action"  value="delete_category">
                        <input type="hidden" name="cat_id"   value="<?php echo (int)$cat->id; ?>">
                        <button type="button" class="btn btn-danger btn-sm"
                                onclick="confirmCategoryDelete('catDeleteForm_<?php echo (int)$cat->id; ?>', '<?php echo htmlspecialchars($cat->name, ENT_QUOTES); ?>')">🗑️</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        </div>
        <?php endif; ?>
        </div>
    </div>
    </div>

</div>

<?php /* ===============================================================
        EDIT-ANSICHT
   =============================================================== */
elseif ($view === 'edit'):
    $post     = $editId > 0 ? $db->get_row("SELECT * FROM {$prefix}posts WHERE id=?", [$editId]) : null;
    $isNew    = $post === null;
    $pd = [
        'id'               => $post->id               ?? 0,
        'title'            => $post->title             ?? '',
        'slug'             => $post->slug              ?? '',
        'content'          => $post->content           ?? '',
        'excerpt'          => $post->excerpt           ?? '',
        'featured_image'   => $post->featured_image    ?? '',
        'status'           => $post->status            ?? 'draft',
        'category_id'      => $post->category_id       ?? '',
        'tags'             => $post->tags              ?? '',
        'allow_comments'   => $post->allow_comments    ?? 1,
        'meta_title'       => $post->meta_title        ?? '',
        'meta_description' => $post->meta_description  ?? '',
        'published_at'     => $post->published_at      ?? '',
    ];
?>
<div class="page-header d-print-none mb-3">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle"><?php echo $isNew ? 'Erstellen' : 'Bearbeiten'; ?></div>
                <h2 class="page-title"><?php echo $isNew ? '➕ Neuer Beitrag' : '✏️ Beitrag bearbeiten'; ?></h2>
                <?php if (!$isNew): ?>
                <div class="text-secondary mt-1">ID #<?php echo (int)$pd['id']; ?> &middot; /blog/<?php echo htmlspecialchars($pd['slug'], ENT_QUOTES); ?></div>
                <?php endif; ?>
            </div>
            <div class="col-auto ms-auto">
                <?php if (!$isNew): ?>
                <a href="<?php echo SITE_URL; ?>/blog/<?php echo htmlspecialchars($pd['slug']); ?>" target="_blank" class="btn btn-secondary">👁️ Vorschau</a>
                <?php endif; ?>
                <a href="<?php echo SITE_URL; ?>/admin/posts" class="btn">← Alle Beiträge</a>
                <button type="submit" form="postEditForm" class="btn btn-primary">💾 <?php echo $isNew ? 'Erstellen' : 'Speichern'; ?></button>
            </div>
        </div>
    </div>
</div>

<form method="post"
      action="<?php echo SITE_URL; ?>/admin/posts?view=edit<?php echo $editId ? '&id='.$editId : ''; ?>"
      enctype="multipart/form-data" id="postEditForm">
    <input type="hidden" name="_csrf"   value="<?php echo $csrf; ?>">
    <input type="hidden" name="_action" value="save_post">
    <input type="hidden" name="post_id" value="<?php echo (int)$pd['id']; ?>">

    <div class="row g-3">

        <!-- Haupt-Spalte -->
        <div class="col-lg-8">

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">📝 Titel &amp; Permalink</h3></div>
                <div class="card-body">
                    <div class="mb-2">
                        <label for="post_title" class="form-label">Titel <span class="text-danger">*</span></label>
                        <input type="text" id="post_title" name="post_title" class="form-control"
                               value="<?php echo htmlspecialchars($pd['title'], ENT_QUOTES); ?>"
                               placeholder="Beitragstitel…" required
                               style="font-size:1.05rem;font-weight:600;"
                               oninput="updateSlugPreview(this.value)">
                    </div>
                    <div class="mb-0">
                        <label for="post_slug" class="form-label">Slug / Permalink</label>
                        <input type="text" id="post_slug" name="post_slug" class="form-control"
                               value="<?php echo htmlspecialchars($pd['slug'], ENT_QUOTES); ?>"
                               placeholder="auto-generiert">
                        <div class="form-hint mt-1"><?php echo SITE_URL; ?>/blog/<strong id="slugPreviewVal"><?php echo htmlspecialchars($pd['slug'], ENT_QUOTES); ?></strong></div>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">✏️ Inhalt</h3></div>
                <div class="card-body">
                    <?php echo EditorService::getInstance()->render('post_content', $pd['content'], ['height' => 700]); ?>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">📄 Auszug (Teaser)</h3></div>
                <div class="card-body">
                    <textarea name="post_excerpt" rows="3" class="form-control" placeholder="Kurze Zusammenfassung – max. 300 Zeichen…"><?php echo htmlspecialchars($pd['excerpt'], ENT_QUOTES); ?></textarea>
                    <div class="form-hint">Wird in Beitragsübersichten verwendet.</div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">🔍 SEO</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Meta-Titel <span class="text-secondary fw-normal">(leer = Beitragstitel)</span></label>
                        <input type="text" name="meta_title" class="form-control" value="<?php echo htmlspecialchars($pd['meta_title'], ENT_QUOTES); ?>" placeholder="max. 70 Zeichen" maxlength="70">
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Meta-Beschreibung <span class="text-secondary fw-normal">(leer = Auszug)</span></label>
                        <textarea name="meta_description" rows="2" class="form-control" placeholder="max. 165 Zeichen" maxlength="165"><?php echo htmlspecialchars($pd['meta_description'], ENT_QUOTES); ?></textarea>
                    </div>
                </div>
            </div>

        </div><!-- /.col-lg-8 -->

        <!-- Seiten-Spalte -->
        <div class="col-lg-4">

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">📋 Status &amp; Sichtbarkeit</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Status</label>
                        <select name="post_status" id="post_status" class="form-select" onchange="togglePublishedAt(this.value)">
                            <option value="draft"     <?php echo $pd['status']==='draft'     ?'selected':''; ?>>📝 Entwurf</option>
                            <option value="published" <?php echo $pd['status']==='published' ?'selected':''; ?>>✅ Veröffentlicht</option>
                            <option value="trash"     <?php echo $pd['status']==='trash'     ?'selected':''; ?>>🗑️ Papierkorb</option>
                        </select>
                    </div>
                    <div class="mb-3" id="publishedAtField" style="<?php echo $pd['status']!=='published'?'display:none;':''; ?>">
                        <label class="form-label">Veröffentlicht am</label>
                        <input type="datetime-local" name="published_at" class="form-control"
                               value="<?php echo $pd['published_at'] ? date('Y-m-d\TH:i', strtotime($pd['published_at'])) : date('Y-m-d\TH:i'); ?>">
                    </div>
                    <?php if (!$isNew): ?>
                    <button type="button" class="btn btn-danger w-100" onclick="confirmEditorDeletePost()">🗑️ In Papierkorb</button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">🏷️ Kategorie &amp; Tags</h3></div>
                <div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Kategorie</label>
                        <select name="post_category" class="form-select">
                            <option value="">– keine –</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo (int)$cat->id; ?>" <?php echo (string)$pd['category_id']===(string)$cat->id?'selected':''; ?>>
                                <?php echo htmlspecialchars($cat->name, ENT_QUOTES); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-hint"><a href="<?php echo SITE_URL; ?>/admin/posts?view=categories" style="color:var(--tblr-primary);">+ Kategorien verwalten</a></div>
                    </div>
                    <div class="mb-0">
                        <label class="form-label">Tags <span class="text-secondary fw-normal">(kommagetrennt)</span></label>
                        <input type="text" name="post_tags" class="form-control" value="<?php echo htmlspecialchars($pd['tags'], ENT_QUOTES); ?>" placeholder="php, tutorial, news">
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">🖼️ Beitragsbild</h3></div>
                <div class="card-body">
                    <div id="featImgWrap">
                        <?php if (!empty($pd['featured_image'])): ?>
                            <img src="<?php echo htmlspecialchars($pd['featured_image'], ENT_QUOTES); ?>"
                                 class="feat-img-preview" id="featImgPreview" alt="">
                        <?php else: ?>
                            <div class="feat-img-placeholder" onclick="document.getElementById('featImgInput').click()">🖼️ Bild hochladen</div>
                        <?php endif; ?>
                        <input type="hidden" name="existing_featured_image" id="existingFeatImg"
                               value="<?php echo htmlspecialchars($pd['featured_image'], ENT_QUOTES); ?>">
                        <input type="hidden" name="remove_featured_image" id="removeFeatImg" value="0">
                    </div>
                    <div class="d-flex gap-2 mt-2 flex-wrap">
                        <label class="btn btn-secondary btn-sm" style="cursor:pointer;">
                            📁 Bild wählen
                            <input type="file" id="featImgInput" name="featured_image" accept="image/*" style="display:none;" onchange="previewFeatImg(this)">
                        </label>
                        <?php if (!empty($pd['featured_image'])): ?>
                        <button type="button" class="btn btn-danger btn-sm" onclick="removeFeatImg()">✕ Entfernen</button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="card mb-3">
                <div class="card-header"><h3 class="card-title">💬 Kommentare</h3></div>
                <div class="card-body">
                    <label class="form-check">
                        <input type="checkbox" name="allow_comments" value="1" class="form-check-input" <?php echo $pd['allow_comments'] ? 'checked' : ''; ?>>
                        <span class="form-check-label">Kommentare erlauben</span>
                    </label>
                </div>
            </div>

        </div><!-- /.col-lg-4 -->
    </div><!-- /.row -->

</form>

<?php if (!$isNew): ?>
<form id="deletePostForm" method="post" action="<?php echo SITE_URL; ?>/admin/posts" style="display:none;">
    <input type="hidden" name="_csrf"       value="<?php echo $csrf; ?>">
    <input type="hidden" name="_action"     value="delete_post">
    <input type="hidden" name="post_id"     value="<?php echo (int)$pd['id']; ?>">
    <input type="hidden" name="delete_mode" value="trash">
</form>
<?php endif; ?>

<?php /* ===============================================================
        LISTEN-ANSICHT
   =============================================================== */
else: // view === 'list'

    $whereParts = [];
    $params     = [];
    if ($status === 'all') {
        $whereParts[] = "p.status != 'trash'";
    } else {
        $whereParts[] = "p.status = ?";
        $params[]     = $status;
    }
    if (!empty($search)) {
        $whereParts[] = "(p.title LIKE ? OR p.excerpt LIKE ?)";
        $params[]     = '%' . $search . '%';
        $params[]     = '%' . $search . '%';
    }
    $where      = $whereParts ? ' WHERE ' . implode(' AND ', $whereParts) : '';
    // For COUNT query, we don't use aliases since it's just one table
    $countWhere = str_replace(['p.status', 'p.title', 'p.excerpt'], ['status', 'title', 'excerpt'], $where);
    $total      = (int)$db->get_var("SELECT COUNT(*) FROM {$prefix}posts" . $countWhere, $params);
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page       = min($page, $totalPages);
    $offset     = ($page - 1) * $perPage;

    $posts = $db->get_results(
        "SELECT p.*, u.display_name AS author_name, c.name AS category_name
         FROM {$prefix}posts p
         LEFT JOIN {$prefix}users u ON u.id = p.author_id
         LEFT JOIN {$prefix}post_categories c ON c.id = p.category_id"
        . $where . " ORDER BY p.updated_at DESC LIMIT {$perPage} OFFSET {$offset}",
        $params
    ) ?: [];

    $buildUrl = fn(array $extra = []) =>
        SITE_URL . '/admin/posts?' . http_build_query(array_merge(
            ['view' => 'list', 'status' => $status],
            $search ? ['search' => $search] : [],
            $page > 1 ? ['p' => $page] : [],
            $extra
        ));
?>

<div class="page-header d-print-none mb-3">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col">
                <div class="page-pretitle">Verwaltung</div>
                <h2 class="page-title">✍️ Beiträge</h2>
                <div class="text-secondary mt-1"><?php echo $total; ?> Beitrag<?php echo $total !== 1 ? 'e' : ''; ?><?php echo $search ? ' für &bdquo;'.htmlspecialchars($search,ENT_QUOTES).'&ldquo;' : ''; ?></div>
            </div>
            <div class="col-auto ms-auto">
                <a href="<?php echo SITE_URL; ?>/admin/posts?view=categories" class="btn btn-secondary">🏷️ Kategorien</a>
                <a href="<?php echo SITE_URL; ?>/admin/posts?view=edit" class="btn btn-primary">➕ Neuer Beitrag</a>
            </div>
        </div>
    </div>
</div>

<ul class="nav nav-tabs mb-3">
    <?php foreach (['all'=>['Alle','📄'],'published'=>['Veröffentlicht','✅'],'draft'=>['Entwürfe','📝'],'trash'=>['Papierkorb','🗑️']] as $s=>[$label,$icon]): ?>
    <li class="nav-item">
        <a href="<?php echo SITE_URL; ?>/admin/posts?status=<?php echo $s; ?><?php echo $search?'&search='.urlencode($search):''; ?>"
           class="nav-link <?php echo $status===$s?'active':''; ?>">
            <?php echo $icon; ?> <?php echo $label; ?> <span class="badge bg-secondary ms-1"><?php echo $counts[$s]; ?></span>
        </a>
    </li>
    <?php endforeach; ?>
</ul>


<form method="post" action="<?php echo SITE_URL; ?>/admin/posts" id="bulkForm">
    <input type="hidden" name="_csrf"   value="<?php echo $csrf; ?>">
    <input type="hidden" name="_action" value="bulk">
    <div class="bulk-bar">
        <select name="bulk_action">
            <option value="">Aktion wählen…</option>
            <option value="publish">Veröffentlichen</option>
            <option value="draft">Als Entwurf</option>
            <option value="trash">In Papierkorb</option>
            <?php if ($status === 'trash'): ?><option value="delete">Endgültig löschen</option><?php endif; ?>
        </select>
        <button type="submit" class="btn btn-secondary btn-sm" onclick="return confirmBulk(this.form)">Anwenden</button>
    </div>
    <div id="bulkIdsContainer"></div>
</form>

<!-- Grid.js Posts-Tabelle -->
<link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/gridjs/mermaid.min.css">
<div id="posts-grid"></div>

<script src="<?php echo SITE_URL; ?>/assets/gridjs/gridjs.umd.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/js/gridjs-init.js"></script>
<script>
(function() {
    var SITE = <?php echo json_encode(SITE_URL); ?>;
    var STATUS = <?php echo json_encode($status); ?>;
    var _sel = new Set();

    function esc(s) { return cmsEsc(s); }

    // Bulk-Checkbox-Handling
    window._postsBulkIds = _sel;
    var origSubmit = document.getElementById('bulkForm').onsubmit;
    document.getElementById('bulkForm').addEventListener('submit', function() {
        var c = document.getElementById('bulkIdsContainer');
        c.innerHTML = '';
        _sel.forEach(function(id) {
            var inp = document.createElement('input');
            inp.type = 'hidden'; inp.name = 'bulk_ids[]'; inp.value = id;
            c.appendChild(inp);
        });
    });

    var statusMap = {
        published: ['Veröffentlicht', 'status-published'],
        draft:     ['Entwurf',        'status-draft'],
        trash:     ['Papierkorb',     'status-trash']
    };

    cmsGrid('#posts-grid', {
        url: SITE + '/api/v1/admin/posts',
        extraParams: { status: STATUS },
        limit: 20,
        sortMap: { 1: 'title', 2: 'title', 3: 'status', 4: 'views', 5: 'updated_at' },
        columns: [
            {
                id: '_check',
                name: gridjs.html('<input type="checkbox" onchange="document.querySelectorAll(\'.gjs-post-chk\').forEach(function(c){c.checked=this.checked;if(this.checked)window._postsBulkIds.add(+c.value);else window._postsBulkIds.delete(+c.value);}.bind(this))">'),
                width: '36px',
                sort: false,
                formatter: function (_, row) {
                    var id = row.cells[11].data;
                    var chk = _sel.has(id) ? ' checked' : '';
                    return gridjs.html('<input type="checkbox" class="gjs-post-chk" value="' + id + '"' + chk + ' onchange="this.checked?window._postsBulkIds.add(' + id + '):window._postsBulkIds.delete(' + id + ')">');
                }
            },
            {
                id: 'featured_image',
                name: '',
                width: '48px',
                sort: false,
                formatter: function (cell) {
                    if (cell) return gridjs.html('<img src="' + esc(cell) + '" style="width:36px;height:36px;object-fit:cover;border-radius:4px;" alt="">');
                    return gridjs.html('<div style="width:36px;height:36px;background:#f1f5f9;border-radius:4px;display:flex;align-items:center;justify-content:center;font-size:.8rem;">🖼️</div>');
                }
            },
            {
                id: 'title',
                name: 'Titel',
                formatter: function (cell, row) {
                    var d = row.cells;
                    var id = d[11].data, title = cell, slug = d[9].data, author = d[10].data || 'Unbekannt';
                    return gridjs.html(
                        '<a href="' + SITE + '/admin/posts?view=edit&id=' + id + '" style="font-weight:600;color:#1e293b;text-decoration:none;">' + esc(title) + '</a>' +
                        '<div style="font-size:.74rem;color:#94a3b8;margin-top:.12rem;">' + esc(author) + ' · <code style="font-size:.7rem;">/blog/' + esc(slug) + '</code></div>'
                    );
                }
            },
            {
                id: 'category_name',
                name: 'Kategorie',
                width: '120px',
                formatter: function (cell) {
                    return cell ? esc(cell) : gridjs.html('<span style="color:#cbd5e1;">—</span>');
                }
            },
            {
                id: 'status',
                name: 'Status',
                width: '110px',
                formatter: function (cell) {
                    var st = statusMap[cell] || [cell, ''];
                    return gridjs.html('<span class="status-badge ' + st[1] + '">' + st[0] + '</span>');
                }
            },
            {
                id: 'views',
                name: '👁️',
                width: '60px',
                formatter: function (cell) {
                    return gridjs.html('<span style="color:#64748b;font-size:.8rem;">' + Number(cell || 0).toLocaleString('de-DE') + '</span>');
                }
            },
            {
                id: 'updated_at',
                name: 'Datum',
                width: '110px',
                formatter: function (_, row) {
                    var d = row.cells;
                    var st = d[4].data, pub = d[8].data, upd = d[6].data;
                    var raw = (st === 'published' && pub) ? pub : upd;
                    return gridjs.html('<span style="font-size:.78rem;color:#64748b;" title="' + esc(raw) + '">' + cmsTimeAgo(raw) + '</span>');
                }
            },
            {
                id: '_actions',
                name: '',
                width: '120px',
                sort: false,
                formatter: function (_, row) {
                    var d = row.cells;
                    var id = d[11].data, slug = d[9].data, st = d[4].data;
                    var del = st === 'trash'
                        ? '<button type="button" class="btn btn-danger btn-sm" onclick="deletePost(' + id + ',\'permanent\',\'ENDGÜLTIG löschen?\')">☠️</button>'
                        : '<button type="button" class="btn btn-danger btn-sm" onclick="deletePost(' + id + ',\'trash\',\'In Papierkorb?\')">🗑️</button>';
                    return gridjs.html(
                        '<div style="display:flex;gap:.3rem;white-space:nowrap;">' +
                        '<a href="' + SITE + '/blog/' + esc(slug) + '" target="_blank" class="btn btn-secondary btn-sm" title="Ansehen">👁️</a>' +
                        '<a href="' + SITE + '/admin/posts?view=edit&id=' + id + '" class="btn btn-secondary btn-sm" title="Bearbeiten">✏️</a>' +
                        del + '</div>'
                    );
                }
            },
            // Hidden data columns (used by formatters via row.cells)
            { id: 'published_at', hidden: true },
            { id: 'slug', hidden: true },
            { id: 'author_name', hidden: true },
            { id: 'id', hidden: true }
        ]
    });
})();
</script>

<form id="listDeleteForm" method="post" action="<?php echo SITE_URL; ?>/admin/posts" style="display:none;">
    <input type="hidden" name="_csrf"       value="<?php echo $csrf; ?>">
    <input type="hidden" name="_action"     value="delete_post">
    <input type="hidden" name="post_id"     id="listDeleteId" value="">
    <input type="hidden" name="delete_mode" id="listDeleteMode" value="">
</form>

<!-- Post löschen – Bootstrap 5 Modal -->
<div class="modal modal-blur fade" id="postDeleteModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
        <div class="modal-content">
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Schließen"></button>
            <div class="modal-status bg-danger"></div>
            <div class="modal-body text-center py-4">
                <h3 id="postDeleteModalTitle">Beitrag löschen</h3>
                <div class="text-secondary" id="postDeleteModalMsg"></div>
            </div>
            <div class="modal-footer">
                <div class="w-100">
                    <div class="row">
                        <div class="col"><button type="button" class="btn w-100" data-bs-dismiss="modal">Abbrechen</button></div>
                        <div class="col"><button type="button" class="btn btn-danger w-100" id="postDeleteModalConfirm">🗑️ Löschen</button></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<script>
const _postDelModal = new bootstrap.Modal(document.getElementById('postDeleteModal'));
function showPostsNotice(type, message) {
    var container = document.getElementById('postsNoticeContainer');
    if (!container) return;
    var cls = type === 'error' ? 'alert-danger' : (type === 'warning' ? 'alert-warning' : 'alert-info');
    container.innerHTML = '<div class="alert ' + cls + ' alert-dismissible" role="alert">'
        + '<div class="d-flex"><div>' + cmsEsc(message) + '</div></div>'
        + '<a class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></a>'
        + '</div>';
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function deletePost(id, mode, msg) {
    document.getElementById('postDeleteModalMsg').textContent = msg;
    document.getElementById('postDeleteModalTitle').textContent = mode === 'permanent' ? 'Endgültig löschen' : 'In Papierkorb';
    document.getElementById('postDeleteModalConfirm').onclick = function() {
        _postDelModal.hide();
        document.getElementById('listDeleteId').value = id;
        document.getElementById('listDeleteMode').value = mode;
        document.getElementById('listDeleteForm').submit();
    };
    _postDelModal.show();
}

function confirmCategoryDelete(formId, categoryName) {
    cmsConfirm('Kategorie „' + categoryName + '“ wirklich löschen?', function() {
        document.getElementById(formId).submit();
    }, 'Kategorie löschen');
}

function confirmEditorDeletePost() {
    cmsConfirm('Diesen Beitrag wirklich in den Papierkorb verschieben?', function() {
        document.getElementById('deletePostForm').submit();
    }, 'Beitrag verschieben');
}
</script>

<?php endif; // end views ?>

<!-- ── JS (Edit-Modus) ────────────────────────────────────────────────────────── -->
<?php if ($view === 'edit'): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    function slugify(text) {
        return text.toLowerCase()
            .replace(/ä/g,'ae').replace(/ö/g,'oe').replace(/ü/g,'ue').replace(/ß/g,'ss')
            .replace(/[^a-z0-9\-]/g,'-').replace(/-+/g,'-').replace(/^-|-$/g,'');
    }
    window.updateSlugPreview = function(val) {
        const slugInput = document.getElementById('post_slug');
        if (!slugInput.dataset.manual) {
            const s = slugify(val);
            slugInput.value = s;
            document.getElementById('slugPreviewVal').textContent = s;
        }
    };
    document.getElementById('post_slug').addEventListener('input', function() {
        this.dataset.manual = '1';
        document.getElementById('slugPreviewVal').textContent = this.value;
    });
});
window.togglePublishedAt = function(val) {
    document.getElementById('publishedAtField').style.display = val === 'published' ? '' : 'none';
};
window.previewFeatImg = function(input) {
    const f = input.files[0];
    if (!f) return;
    const r = new FileReader();
    r.onload = e => {
        const wrap = document.getElementById('featImgWrap');
        let img = wrap.querySelector('.feat-img-preview');
        if (!img) {
            img = document.createElement('img');
            img.className = 'feat-img-preview'; img.id = 'featImgPreview'; img.alt = '';
            wrap.prepend(img);
            const ph = wrap.querySelector('.feat-img-placeholder');
            if (ph) ph.style.display='none';
        }
        img.src = e.target.result;
        document.getElementById('removeFeatImg').value = '0';
    };
    r.readAsDataURL(f);
};
window.removeFeatImg = function() {
    document.getElementById('existingFeatImg').value = '';
    document.getElementById('removeFeatImg').value   = '1';
    const img = document.getElementById('featImgPreview');
    if (img) {
        const ph = document.createElement('div');
        ph.className = 'feat-img-placeholder'; ph.textContent = '🖼️ Kein Bild';
        img.replaceWith(ph);
    }
};
</script>
<?php else: ?>
<script>
function confirmBulk(form) {
    const a = form.querySelector('select[name=bulk_action]').value;
    const n = window._postsBulkIds ? window._postsBulkIds.size : 0;
    if (!a) { showPostsNotice('warning', 'Bitte zuerst eine Bulk-Aktion wählen.'); return false; }
    if (!n) { showPostsNotice('warning', 'Bitte zuerst mindestens einen Beitrag auswählen.'); return false; }

    const labels = {
        publish: 'veröffentlichen',
        draft: 'als Entwurf setzen',
        trash: 'in den Papierkorb verschieben',
        delete: 'endgültig löschen'
    };

    cmsConfirm(n + ' Beitrag/Beiträge wirklich ' + (labels[a] || 'bearbeiten') + '?', function() {
        form.submit();
    }, 'Sammelaktion bestätigen');
    return false;
}
</script>
<?php endif; ?>

<?php
renderAdminLayoutEnd();

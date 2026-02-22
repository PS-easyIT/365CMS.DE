<?php
/**
 * Admin: Beiträge (Blog-Posts)
 *
 * Vollständige CRUD-Verwaltung für Blog-Beiträge.
 * Views: list | edit (create/update) | categories
 *
 * @package CMSv2\Admin
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;
use CMS\Security;
use CMS\Database;

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
        'text' => htmlspecialchars(urldecode($_GET['msg']), ENT_QUOTES, 'UTF-8'),
    ];
}

// ── Stammdaten ─────────────────────────────────────────────────────────────────
$categories = $db->get_results(
    "SELECT * FROM {$prefix}post_categories ORDER BY name ASC"
) ?: [];

$counts = [];
foreach (['all','published','draft','trash'] as $s) {
    $where = $s === 'all' ? "status != 'trash'" : "status = '$s'";
    $counts[$s] = (int)$db->get_var("SELECT COUNT(*) FROM {$prefix}posts WHERE $where");
}

$csrf = $security->generateToken('admin_posts');

require_once __DIR__ . '/partials/admin-menu.php';
renderAdminLayoutStart('Beiträge', 'posts');
?>

<?php foreach ($messages as $m):
    $cls = match($m['type']){'success'=>'notice-success','error'=>'notice-error',default=>'notice-info'};
?>
<div class="notice <?php echo $cls; ?>"><?php echo htmlspecialchars($m['text'], ENT_QUOTES, 'UTF-8'); ?></div>
<?php endforeach; ?>

<?php /* ===============================================================
        KATEGORIEN-ANSICHT
   =============================================================== */
if ($view === 'categories'): ?>

<div class="posts-header">
    <h2 style="margin:0;">🏷️ Post-Kategorien</h2>
    <a href="<?php echo SITE_URL; ?>/admin/posts" class="btn-sm btn-secondary">← Zu Beiträgen</a>
</div>
<div style="display:grid;grid-template-columns:1fr 1fr;gap:1.5rem;align-items:start;">

    <?php
    $editCat = null;
    if (!empty($_GET['cat_id'])) {
        $editCat = $db->get_row("SELECT * FROM {$prefix}post_categories WHERE id=?", [(int)$_GET['cat_id']]);
    }
    ?>
    <div class="post-card">
        <h3><?php echo $editCat ? '✏️ Kategorie bearbeiten' : '➕ Neue Kategorie'; ?></h3>
        <form method="post" action="<?php echo SITE_URL; ?>/admin/posts?view=categories">
            <input type="hidden" name="_csrf"    value="<?php echo $csrf; ?>">
            <input type="hidden" name="_action"  value="save_category">
            <?php if ($editCat): ?><input type="hidden" name="cat_id" value="<?php echo (int)$editCat->id; ?>"><?php endif; ?>
            <div class="field-group">
                <label>Name *</label>
                <input type="text" name="cat_name" value="<?php echo htmlspecialchars($editCat->name ?? '', ENT_QUOTES); ?>" required>
            </div>
            <div class="field-group">
                <label>Slug</label>
                <input type="text" name="cat_slug" value="<?php echo htmlspecialchars($editCat->slug ?? '', ENT_QUOTES); ?>" placeholder="auto-generiert">
            </div>
            <div class="field-group">
                <label>Beschreibung</label>
                <textarea name="cat_desc"><?php echo htmlspecialchars($editCat->description ?? '', ENT_QUOTES); ?></textarea>
            </div>
            <div style="display:flex;gap:.5rem;">
                <button type="submit" class="btn-sm btn-primary btn-lg"><?php echo $editCat ? '💾 Speichern' : '➕ Erstellen'; ?></button>
                <?php if ($editCat): ?><a href="<?php echo SITE_URL; ?>/admin/posts?view=categories" class="btn-sm btn-secondary btn-lg">Abbrechen</a><?php endif; ?>
            </div>
        </form>
    </div>

    <div class="post-card">
        <h3>📋 Alle Kategorien (<?php echo count($categories); ?>)</h3>
        <?php if (empty($categories)): ?>
            <p style="color:#94a3b8;font-size:.875rem;">Noch keine Kategorien vorhanden.</p>
        <?php else: ?>
        <table class="posts-table">
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
                    <a href="?view=categories&cat_id=<?php echo (int)$cat->id; ?>" class="btn-sm btn-secondary">✏️</a>
                    <form method="post" action="<?php echo SITE_URL; ?>/admin/posts?view=categories" style="display:inline;" onsubmit="return confirm('Kategorie löschen?');">
                        <input type="hidden" name="_csrf"    value="<?php echo $csrf; ?>">
                        <input type="hidden" name="_action"  value="delete_category">
                        <input type="hidden" name="cat_id"   value="<?php echo (int)$cat->id; ?>">
                        <button type="submit" class="btn-sm btn-danger">🗑️</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
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
<div class="posts-header">
    <h2 style="margin:0;"><?php echo $isNew ? '➕ Neuer Beitrag' : '✏️ Beitrag bearbeiten'; ?></h2>
    <a href="<?php echo SITE_URL; ?>/admin/posts" class="btn-sm btn-secondary">← Alle Beiträge</a>
</div>

<form method="post"
      action="<?php echo SITE_URL; ?>/admin/posts?view=edit<?php echo $editId ? '&id='.$editId : ''; ?>"
      enctype="multipart/form-data" id="postEditForm">
    <input type="hidden" name="_csrf"   value="<?php echo $csrf; ?>">
    <input type="hidden" name="_action" value="save_post">
    <input type="hidden" name="post_id" value="<?php echo (int)$pd['id']; ?>">

    <div class="post-edit-layout">

        <!-- Haupt-Spalte -->
        <div class="post-edit-main">

            <div class="post-card">
                <div class="field-group" style="margin-bottom:.7rem;">
                    <label for="post_title">Titel *</label>
                    <input type="text" id="post_title" name="post_title"
                           value="<?php echo htmlspecialchars($pd['title'], ENT_QUOTES); ?>"
                           placeholder="Beitragstitel…" required
                           style="font-size:1.05rem;font-weight:600;"
                           oninput="updateSlugPreview(this.value)">
                </div>
                <div class="field-group" style="margin-bottom:0;">
                    <label for="post_slug">Slug / Permalink</label>
                    <input type="text" id="post_slug" name="post_slug"
                           value="<?php echo htmlspecialchars($pd['slug'], ENT_QUOTES); ?>"
                           placeholder="auto-generiert">
                    <div class="slug-row"><?php echo SITE_URL; ?>/blog/<strong id="slugPreviewVal"><?php echo htmlspecialchars($pd['slug'], ENT_QUOTES); ?></strong></div>
                </div>
            </div>

            <div class="post-card" style="padding:0;overflow:hidden;">
                <div style="padding:.65rem 1.1rem;background:#f8fafc;border-bottom:1px solid #e2e8f0;font-size:.85rem;font-weight:700;color:#374151;">📝 Inhalt</div>
                <textarea id="post_content" name="post_content" style="display:none;"><?php echo htmlspecialchars($pd['content'], ENT_QUOTES); ?></textarea>
                <div id="suneditorWrap"></div>
            </div>

            <div class="post-card">
                <h3>📄 Auszug (Teaser)</h3>
                <textarea name="post_excerpt" rows="3" placeholder="Kurze Zusammenfassung – max. 300 Zeichen…"><?php echo htmlspecialchars($pd['excerpt'], ENT_QUOTES); ?></textarea>
                <div class="field-hint">Wird in Beitragsübersichten verwendet.</div>
            </div>

            <div class="post-card">
                <h3>🔍 SEO</h3>
                <div class="field-group">
                    <label>Meta-Titel <span style="font-weight:400;color:#94a3b8;">(leer = Beitragstitel)</span></label>
                    <input type="text" name="meta_title" value="<?php echo htmlspecialchars($pd['meta_title'], ENT_QUOTES); ?>" placeholder="max. 70 Zeichen" maxlength="70">
                </div>
                <div class="field-group" style="margin-bottom:0;">
                    <label>Meta-Beschreibung <span style="font-weight:400;color:#94a3b8;">(leer = Auszug)</span></label>
                    <textarea name="meta_description" rows="2" placeholder="max. 165 Zeichen" maxlength="165"><?php echo htmlspecialchars($pd['meta_description'], ENT_QUOTES); ?></textarea>
                </div>
            </div>

        </div><!-- /.post-edit-main -->

        <!-- Seiten-Spalte -->
        <div class="post-edit-side">

            <div class="post-card">
                <h3>📋 Status & Sichtbarkeit</h3>
                <div class="field-group">
                    <label>Status</label>
                    <select name="post_status" id="post_status" onchange="togglePublishedAt(this.value)">
                        <option value="draft"     <?php echo $pd['status']==='draft'     ?'selected':''; ?>>📝 Entwurf</option>
                        <option value="published" <?php echo $pd['status']==='published' ?'selected':''; ?>>✅ Veröffentlicht</option>
                        <option value="trash"     <?php echo $pd['status']==='trash'     ?'selected':''; ?>>🗑️ Papierkorb</option>
                    </select>
                </div>
                <div class="field-group" id="publishedAtField" style="<?php echo $pd['status']!=='published'?'display:none;':''; ?>">
                    <label>Veröffentlicht am</label>
                    <input type="datetime-local" name="published_at"
                           value="<?php echo $pd['published_at'] ? date('Y-m-d\TH:i', strtotime($pd['published_at'])) : date('Y-m-d\TH:i'); ?>">
                </div>
                <div style="display:flex;flex-direction:column;gap:.45rem;margin-top:.9rem;">
                    <button type="submit" class="btn-sm btn-primary btn-lg">💾 Speichern</button>
                    <?php if (!$isNew): ?>
                    <a href="<?php echo SITE_URL; ?>/blog/<?php echo htmlspecialchars($pd['slug']); ?>" target="_blank"
                       class="btn-sm btn-secondary" style="text-align:center;">👁️ Vorschau</a>
                    <button type="submit" form="deletePostForm" class="btn-sm btn-danger" style="width:100%;">🗑️ In Papierkorb</button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="post-card">
                <h3>🏷️ Kategorie & Tags</h3>
                <div class="field-group">
                    <label>Kategorie</label>
                    <select name="post_category">
                        <option value="">– keine –</option>
                        <?php foreach ($categories as $cat): ?>
                        <option value="<?php echo (int)$cat->id; ?>" <?php echo (string)$pd['category_id']===(string)$cat->id?'selected':''; ?>>
                            <?php echo htmlspecialchars($cat->name, ENT_QUOTES); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <div class="field-hint"><a href="<?php echo SITE_URL; ?>/admin/posts?view=categories" style="color:#2563eb;">+ Kategorien verwalten</a></div>
                </div>
                <div class="field-group" style="margin-bottom:0;">
                    <label>Tags <span style="font-weight:400;color:#94a3b8;">(kommagetrennt)</span></label>
                    <input type="text" name="post_tags" value="<?php echo htmlspecialchars($pd['tags'], ENT_QUOTES); ?>" placeholder="php, tutorial, news">
                </div>
            </div>

            <div class="post-card">
                <h3>🖼️ Beitragsbild</h3>
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
                <div style="display:flex;gap:.4rem;margin-top:.4rem;flex-wrap:wrap;">
                    <label class="btn-sm btn-secondary" style="cursor:pointer;">
                        📁 Bild wählen
                        <input type="file" id="featImgInput" name="featured_image" accept="image/*" style="display:none;" onchange="previewFeatImg(this)">
                    </label>
                    <?php if (!empty($pd['featured_image'])): ?>
                    <button type="button" class="btn-sm btn-danger" onclick="removeFeatImg()">✕ Entfernen</button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="post-card">
                <h3>💬 Kommentare</h3>
                <label style="display:flex;align-items:center;gap:.5rem;font-size:.875rem;cursor:pointer;">
                    <input type="checkbox" name="allow_comments" value="1" <?php echo $pd['allow_comments'] ? 'checked' : ''; ?>>
                    Kommentare erlauben
                </label>
            </div>

        </div><!-- /.post-edit-side -->
    </div><!-- /.post-edit-layout -->
</form>

<?php if (!$isNew): ?>
<form id="deletePostForm" method="post" action="<?php echo SITE_URL; ?>/admin/posts" style="display:none;" onsubmit="return confirm('In Papierkorb?');">
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

<div class="posts-header">
    <h2 style="margin:0;">✏️ Beiträge</h2>
    <div style="display:flex;gap:.5rem;">
        <a href="<?php echo SITE_URL; ?>/admin/posts?view=categories" class="btn-sm btn-secondary">🏷️ Kategorien</a>
        <a href="<?php echo SITE_URL; ?>/admin/posts?view=edit" class="btn-sm btn-primary">➕ Neuer Beitrag</a>
    </div>
</div>

<div class="posts-tabs">
    <?php foreach (['all'=>'Alle','published'=>'Veröffentlicht','draft'=>'Entwürfe','trash'=>'Papierkorb'] as $s=>$label): ?>
    <a href="<?php echo SITE_URL; ?>/admin/posts?status=<?php echo $s; ?><?php echo $search?'&search='.urlencode($search):''; ?>"
       class="posts-tab <?php echo $status===$s?'active':''; ?>">
        <?php echo $label; ?><span class="badge"><?php echo $counts[$s]; ?></span>
    </a>
    <?php endforeach; ?>
</div>

<form method="get" action="<?php echo SITE_URL; ?>/admin/posts">
    <input type="hidden" name="view"   value="list">
    <input type="hidden" name="status" value="<?php echo htmlspecialchars($status); ?>">
    <div class="posts-toolbar">
        <div class="posts-search">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Beiträge suchen…">
            <button type="submit">🔍 Suchen</button>
        </div>
        <?php if ($search): ?>
        <a href="<?php echo SITE_URL; ?>/admin/posts?status=<?php echo $status; ?>" class="btn-sm btn-secondary">✕ Filter löschen</a>
        <?php endif; ?>
    </div>
</form>

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
        <button type="submit" class="btn-sm btn-secondary" onclick="return confirmBulk(this.form)">Anwenden</button>
        <span style="color:#94a3b8;font-size:.8rem;"><?php echo $total; ?> Beitrag<?php echo $total!==1?'e':''; ?></span>
    </div>

    <?php if (empty($posts)): ?>
    <div class="post-card" style="text-align:center;padding:3rem;color:#94a3b8;">
        <div style="font-size:3rem;margin-bottom:1rem;">📭</div>
        <p style="font-size:.9225rem;">
            <?php echo $search ? 'Keine Treffer für <strong>'.htmlspecialchars($search,ENT_QUOTES).'</strong>' : 'Noch keine Beiträge.'; ?>
        </p>
        <?php if (!$search): ?>
        <a href="<?php echo SITE_URL; ?>/admin/posts?view=edit" class="btn-sm btn-primary btn-lg" style="margin-top:.75rem;display:inline-flex;">➕ Ersten Beitrag erstellen</a>
        <?php endif; ?>
    </div>
    <?php else: ?>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:auto;">
        <table class="posts-table">
            <thead><tr>
                <th class="col-check"><input type="checkbox" onchange="document.querySelectorAll('#bulkForm input[name=\'bulk_ids[]\']').forEach(c=>c.checked=this.checked)"></th>
                <th class="col-img"></th>
                <th>Titel</th>
                <th class="col-cat">Kategorie</th>
                <th class="col-status">Status</th>
                <th class="col-views">👁️</th>
                <th class="col-date">Datum</th>
                <th class="col-actions"></th>
            </tr></thead>
            <tbody>
            <?php foreach ($posts as $p):
                $smap  = ['published'=>['Veröffentlicht','status-published'],'draft'=>['Entwurf','status-draft'],'trash'=>['Papierkorb','status-trash']];
                $sbadge = $smap[$p->status] ?? [$p->status,''];
                $dval  = ($p->status==='published' && $p->published_at)
                       ? date('d.m.Y H:i', strtotime($p->published_at))
                       : date('d.m.Y H:i', strtotime($p->updated_at));
            ?>
            <tr>
                <td class="col-check"><input type="checkbox" name="bulk_ids[]" value="<?php echo (int)$p->id; ?>"></td>
                <td class="col-img">
                    <?php if (!empty($p->featured_image)): ?>
                        <img src="<?php echo htmlspecialchars($p->featured_image,ENT_QUOTES); ?>" class="post-thumb" alt="">
                    <?php else: ?>
                        <div class="post-thumb-ph">🖼️</div>
                    <?php endif; ?>
                </td>
                <td>
                    <a href="<?php echo SITE_URL; ?>/admin/posts?view=edit&id=<?php echo (int)$p->id; ?>"
                       style="font-weight:600;color:#1e293b;text-decoration:none;">
                        <?php echo htmlspecialchars($p->title, ENT_QUOTES); ?>
                    </a>
                    <div style="font-size:.74rem;color:#94a3b8;margin-top:.12rem;">
                        <?php echo htmlspecialchars($p->author_name??'Unbekannt', ENT_QUOTES); ?>
                        &nbsp;·&nbsp;<code style="font-size:.7rem;">/blog/<?php echo htmlspecialchars($p->slug, ENT_QUOTES); ?></code>
                    </div>
                </td>
                <td class="col-cat" style="font-size:.8rem;"><?php echo $p->category_name ? htmlspecialchars($p->category_name,ENT_QUOTES) : '<span style="color:#cbd5e1;">—</span>'; ?></td>
                <td class="col-status"><span class="status-badge <?php echo $sbadge[1]; ?>"><?php echo $sbadge[0]; ?></span></td>
                <td class="col-views" style="color:#64748b;font-size:.8rem;"><?php echo number_format((int)$p->views); ?></td>
                <td class="col-date" style="font-size:.78rem;color:#64748b;"><?php echo $dval; ?></td>
                <td class="col-actions" style="white-space:nowrap;">
                    <a href="<?php echo SITE_URL; ?>/blog/<?php echo htmlspecialchars($p->slug, ENT_QUOTES); ?>" target="_blank" class="btn-sm btn-secondary" title="Ansehen">👁️</a>
                    <a href="<?php echo SITE_URL; ?>/admin/posts?view=edit&id=<?php echo (int)$p->id; ?>" class="btn-sm btn-secondary" title="Bearbeiten">✏️</a>
                    <?php if ($p->status !== 'trash'): ?>
                    <button type="button" class="btn-sm btn-danger" onclick="deletePost(<?php echo (int)$p->id; ?>, 'trash', 'In Papierkorb?')">🗑️</button>
                    <?php else: ?>
                    <button type="button" class="btn-sm btn-danger" onclick="deletePost(<?php echo (int)$p->id; ?>, 'permanent', 'ENDGÜLTIG löschen?')">☠️</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="posts-pagination">
        <?php if ($page > 1): ?><a href="<?php echo $buildUrl(['p'=>$page-1]); ?>">‹</a><?php endif; ?>
        <?php for ($i=max(1,$page-3); $i<=min($totalPages,$page+3); $i++): ?>
            <?php if ($i===$page): ?>
                <span class="cp"><?php echo $i; ?></span>
            <?php else: ?>
                <a href="<?php echo $buildUrl(['p'=>$i]); ?>"><?php echo $i; ?></a>
            <?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?><a href="<?php echo $buildUrl(['p'=>$page+1]); ?>">›</a><?php endif; ?>
        <span style="color:#94a3b8;font-size:.78rem;margin-left:.4rem;"><?php echo $page; ?>/<?php echo $totalPages; ?></span>
    </div>
    <?php endif; ?>
    <?php endif; // posts empty check ?>
</form>

<form id="listDeleteForm" method="post" action="<?php echo SITE_URL; ?>/admin/posts" style="display:none;">
    <input type="hidden" name="_csrf"       value="<?php echo $csrf; ?>">
    <input type="hidden" name="_action"     value="delete_post">
    <input type="hidden" name="post_id"     id="listDeleteId" value="">
    <input type="hidden" name="delete_mode" id="listDeleteMode" value="">
</form>
<script>
function deletePost(id, mode, msg) {
    if (confirm(msg)) {
        document.getElementById('listDeleteId').value = id;
        document.getElementById('listDeleteMode').value = mode;
        document.getElementById('listDeleteForm').submit();
    }
}
</script>

<?php endif; // end views ?>

<!-- ── Editor + JS (Edit-Modus) ──────────────────────────────────────────────── -->
<?php if ($view === 'edit'): ?>
<link  rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/suneditor/css/suneditor.min.css">
<script src="<?php echo SITE_URL; ?>/assets/suneditor/suneditor.min.js"></script>
<script src="<?php echo SITE_URL; ?>/assets/suneditor/lang/de.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const ta = document.getElementById('post_content');
    const editor = SUNEDITOR.create(ta, {
        buttonList: [
            ['undo','redo'],['font','fontSize','formatBlock'],
            ['bold','underline','italic','strike'],['fontColor','hiliteColor'],
            ['align','horizontalRule','list','lineHeight'],
            ['table','link','image','video'],
            ['fullScreen','showBlocks','codeView'],['removeFormat']
        ],
        height: 460,
        lang: SUNEDITOR_LANG['de'],
    });

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
    const n = form.querySelectorAll('input[name="bulk_ids[]"]:checked').length;
    if (!a) { alert('Bitte zuerst eine Aktion wählen.'); return false; }
    if (!n) { alert('Keine Beiträge ausgewählt.'); return false; }
    if (a === 'delete') return confirm(n + ' Beitrag/Beiträge ENDGÜLTIG löschen?');
    return true;
}
</script>
<?php endif; ?>

<?php
renderAdminLayoutEnd();

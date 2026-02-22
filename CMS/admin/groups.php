<?php
/**
 * Admin - Gruppen & Rollenverwaltung (vollständig ausgebaut)
 *
 * @package CMSv2\Admin
 */

declare(strict_types=1);

if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../config.php';
}

use CMS\Security;
use CMS\Database;
use CMS\Auth;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL . '/admin/login');
    exit;
}

$security = Security::instance();
$db       = Database::instance();
$prefix   = $db->prefix;

$messages = [];
$activeTab = in_array($_GET['tab'] ?? '', ['groups', 'roles']) ? $_GET['tab'] : 'groups';

// ╔══════════════════════════════════════════════════════════════════════════╗
// ║  GRUPPEN – ACTIONS                                                       ║
// ╚══════════════════════════════════════════════════════════════════════════╝

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['_action'] ?? '';

    // Create Group
    if ($action === 'create_group') {
        if (!$security->verifyToken($_POST['_csrf'] ?? '', 'group_management')) {
            $messages[] = ['type' => 'error', 'text' => 'Sicherheitscheck fehlgeschlagen.'];
        } else {
            $name        = trim($_POST['name']        ?? '');
            $slug        = trim($_POST['slug']        ?? '');
            $description = trim($_POST['description'] ?? '');
            $plan_id     = (int)($_POST['plan_id']    ?? 0);
            $is_active   = isset($_POST['is_active']) ? 1 : 0;

            if (empty($name)) {
                $messages[] = ['type' => 'error', 'text' => 'Gruppenname ist Pflichtfeld.'];
            } else {
                if (empty($slug)) {
                    $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $name));
                }
                $existing = $db->get_var("SELECT id FROM {$prefix}user_groups WHERE slug=?", [$slug]);
                if ($existing) {
                    $messages[] = ['type' => 'error', 'text' => 'Slug bereits vergeben.'];
                } else {
                    $db->execute(
                        "INSERT INTO {$prefix}user_groups (name, slug, description, plan_id, is_active, created_at) VALUES (?,?,?,?,?,NOW())",
                        [$name, $slug, $description, $plan_id ?: null, $is_active]
                    );
                    header('Location: ' . SITE_URL . '/admin/groups?tab=groups&msg=group_created');
                    exit;
                }
            }
        }
    }

    // Edit Group
    if ($action === 'edit_group') {
        if (!$security->verifyToken($_POST['_csrf'] ?? '', 'group_management')) {
            $messages[] = ['type' => 'error', 'text' => 'Sicherheitscheck fehlgeschlagen.'];
        } else {
            $gid         = (int)($_POST['group_id']   ?? 0);
            $name        = trim($_POST['name']        ?? '');
            $description = trim($_POST['description'] ?? '');
            $plan_id     = (int)($_POST['plan_id']    ?? 0);
            $is_active   = isset($_POST['is_active']) ? 1 : 0;

            if ($gid < 1 || empty($name)) {
                $messages[] = ['type' => 'error', 'text' => 'Ungültige Eingaben.'];
            } else {
                $db->execute(
                    "UPDATE {$prefix}user_groups SET name=?, description=?, plan_id=?, is_active=?, updated_at=NOW() WHERE id=?",
                    [$name, $description, $plan_id ?: null, $is_active, $gid]
                );
                header('Location: ' . SITE_URL . '/admin/groups?tab=groups&msg=group_updated');
                exit;
            }
        }
    }

    // Delete Group
    if ($action === 'delete_group') {
        if (!$security->verifyToken($_POST['_csrf'] ?? '', 'group_management')) {
            $messages[] = ['type' => 'error', 'text' => 'Sicherheitscheck fehlgeschlagen.'];
        } else {
            $gid = (int)($_POST['group_id'] ?? 0);
            if ($gid > 0) {
                $db->execute("DELETE FROM {$prefix}user_group_members WHERE group_id=?", [$gid]);
                $db->execute("DELETE FROM {$prefix}user_groups WHERE id=?", [$gid]);
                header('Location: ' . SITE_URL . '/admin/groups?tab=groups&msg=group_deleted');
                exit;
            }
        }
    }

    // Add Member to Group
    if ($action === 'add_member') {
        if (!$security->verifyToken($_POST['_csrf'] ?? '', 'group_management')) {
            $messages[] = ['type' => 'error', 'text' => 'Sicherheitscheck fehlgeschlagen.'];
        } else {
            $gid = (int)($_POST['group_id'] ?? 0);
            $uid = (int)($_POST['user_id']  ?? 0);
            if ($gid > 0 && $uid > 0) {
                $db->execute(
                    "INSERT IGNORE INTO {$prefix}user_group_members (user_id, group_id, joined_at) VALUES (?,?,NOW())",
                    [$uid, $gid]
                );
                header('Location: ' . SITE_URL . '/admin/groups?tab=groups&view=detail&id=' . $gid . '&msg=member_added');
                exit;
            }
        }
    }

    // Remove Member from Group
    if ($action === 'remove_member') {
        if (!$security->verifyToken($_POST['_csrf'] ?? '', 'group_management')) {
            $messages[] = ['type' => 'error', 'text' => 'Sicherheitscheck fehlgeschlagen.'];
        } else {
            $gid = (int)($_POST['group_id'] ?? 0);
            $uid = (int)($_POST['user_id']  ?? 0);
            if ($gid > 0 && $uid > 0) {
                $db->execute("DELETE FROM {$prefix}user_group_members WHERE user_id=? AND group_id=?", [$uid, $gid]);
                header('Location: ' . SITE_URL . '/admin/groups?tab=groups&view=detail&id=' . $gid . '&msg=member_removed');
                exit;
            }
        }
    }

    // ── ROLLEN ACTIONS ───────────────────────────────────────────────────────

    $allCaps = ['manage_posts','manage_pages','manage_users','manage_plugins',
                'manage_themes','manage_settings','view_analytics','manage_media'];

    // Create Role
    if ($action === 'create_role') {
        if (!$security->verifyToken($_POST['_csrf'] ?? '', 'role_management')) {
            $messages[] = ['type' => 'error', 'text' => 'Sicherheitscheck fehlgeschlagen.'];
        } else {
            $name         = trim($_POST['name']         ?? '');
            $display_name = trim($_POST['display_name'] ?? '');
            $description  = trim($_POST['description']  ?? '');
            $caps         = array_intersect((array)($_POST['capabilities'] ?? []), $allCaps);

            if (empty($name)) {
                $messages[] = ['type' => 'error', 'text' => 'Rollenname ist Pflichtfeld.'];
            } elseif (!preg_match('/^[a-z0-9_]+$/', $name)) {
                $messages[] = ['type' => 'error', 'text' => 'Rollenname darf nur a-z, 0-9 und _ enthalten.'];
            } else {
                $existing = $db->get_var("SELECT id FROM {$prefix}roles WHERE name=?", [$name]);
                if ($existing) {
                    $messages[] = ['type' => 'error', 'text' => 'Rollenname bereits vergeben.'];
                } else {
                    $db->execute(
                        "INSERT INTO {$prefix}roles (name, display_name, description, capabilities, created_at) VALUES (?,?,?,?,NOW())",
                        [$name, $display_name ?: $name, $description, json_encode($caps)]
                    );
                    header('Location: ' . SITE_URL . '/admin/groups?tab=roles&msg=role_created');
                    exit;
                }
            }
        }
    }

    // Edit Role
    if ($action === 'edit_role') {
        if (!$security->verifyToken($_POST['_csrf'] ?? '', 'role_management')) {
            $messages[] = ['type' => 'error', 'text' => 'Sicherheitscheck fehlgeschlagen.'];
        } else {
            $rid          = (int)($_POST['role_id']      ?? 0);
            $display_name = trim($_POST['display_name']  ?? '');
            $description  = trim($_POST['description']   ?? '');
            $caps         = array_intersect((array)($_POST['capabilities'] ?? []), $allCaps);

            if ($rid < 1) {
                $messages[] = ['type' => 'error', 'text' => 'Ungültige Rollen-ID.'];
            } else {
                $db->execute(
                    "UPDATE {$prefix}roles SET display_name=?, description=?, capabilities=?, updated_at=NOW() WHERE id=?",
                    [$display_name, $description, json_encode($caps), $rid]
                );
                header('Location: ' . SITE_URL . '/admin/groups?tab=roles&msg=role_updated');
                exit;
            }
        }
    }

    // Delete Role
    if ($action === 'delete_role') {
        if (!$security->verifyToken($_POST['_csrf'] ?? '', 'role_management')) {
            $messages[] = ['type' => 'error', 'text' => 'Sicherheitscheck fehlgeschlagen.'];
        } else {
            $rid = (int)($_POST['role_id'] ?? 0);
            // Core roles protected
            $coreRoles = ['admin', 'member', 'editor'];
            $roleName  = $db->get_var("SELECT name FROM {$prefix}roles WHERE id=?", [$rid]);
            if ($rid > 0 && !in_array($roleName, $coreRoles)) {
                $db->execute("DELETE FROM {$prefix}roles WHERE id=?", [$rid]);
                header('Location: ' . SITE_URL . '/admin/groups?tab=roles&msg=role_deleted');
                exit;
            } else {
                $messages[] = ['type' => 'error', 'text' => 'Core-Rollen können nicht gelöscht werden.'];
            }
        }
    }
}

// ── URL messages ─────────────────────────────────────────────────────────────
if (isset($_GET['msg'])) {
    $msgMap = [
        'group_created'  => ['success', '✅ Gruppe erstellt.'],
        'group_updated'  => ['success', '✅ Gruppe aktualisiert.'],
        'group_deleted'  => ['success', '🗑️ Gruppe gelöscht.'],
        'member_added'   => ['success', '✅ Mitglied hinzugefügt.'],
        'member_removed' => ['success', '🗑️ Mitglied entfernt.'],
        'role_created'   => ['success', '✅ Rolle erstellt.'],
        'role_updated'   => ['success', '✅ Rolle aktualisiert.'],
        'role_deleted'   => ['success', '🗑️ Rolle gelöscht.'],
    ];
    if (isset($msgMap[$_GET['msg']])) {
        $messages[] = ['type' => $msgMap[$_GET['msg']][0], 'text' => $msgMap[$_GET['msg']][1]];
    }
}

// ── Data ──────────────────────────────────────────────────────────────────────
$groups         = $db->get_results(
    "SELECT g.*, (SELECT COUNT(*) FROM {$prefix}user_group_members m WHERE m.group_id=g.id) AS member_count
     FROM {$prefix}user_groups g ORDER BY g.name"
);
$roles          = $db->get_results("SELECT * FROM {$prefix}roles ORDER BY name");
$allCaps        = ['manage_posts','manage_pages','manage_users','manage_plugins',
                   'manage_themes','manage_settings','view_analytics','manage_media'];
$capsLabels     = [
    'manage_posts'    => '📝 Beiträge',
    'manage_pages'    => '📄 Seiten',
    'manage_users'    => '👥 Benutzer',
    'manage_plugins'  => '🔌 Plugins',
    'manage_themes'   => '🎨 Themes',
    'manage_settings' => '⚙️ Einstellungen',
    'view_analytics'  => '📊 Analytics',
    'manage_media'    => '🖼️ Medien',
];

$csrfGroup = $security->generateToken('group_management');
$csrfRole  = $security->generateToken('role_management');

// Detail-Ansicht
$viewMode     = $_GET['view'] ?? 'list';
$detailId     = (int)($_GET['id'] ?? 0);
$editGroupId  = ($viewMode === 'detail' && $detailId > 0) ? $detailId : 0;
$editRoleId   = (int)($_GET['edit_role'] ?? 0);

require_once __DIR__ . '/partials/admin-menu.php';
$_layoutTitle = $activeTab === 'roles' ? 'Rollen & Rechte' : 'Gruppen';
$_layoutSlug  = $activeTab === 'roles' ? 'roles' : 'groups';
renderAdminLayoutStart($_layoutTitle, $_layoutSlug);
?>

<?php foreach ($messages as $m):
    $cls = $m['type'] === 'success' ? 'notice-success' : 'notice-error';
?>
<div class="notice <?php echo $cls; ?>"><?php echo htmlspecialchars($m['text'], ENT_QUOTES, 'UTF-8'); ?></div>
<?php endforeach; ?>

<?php if ($activeTab === 'groups'): ?>
<?php /* ================================================================
        GRUPPEN-TAB
   ================================================================ */

// ── DETAIL-VIEW einer Gruppe ──────────────────────────────────────────────
if ($editGroupId > 0):
    $grp = $db->get_row("SELECT * FROM {$prefix}user_groups WHERE id=?", [$editGroupId]);
    if (!$grp):
        header('Location: ' . SITE_URL . '/admin/groups?tab=groups');
        exit;
    endif;
    $members    = $db->get_results(
        "SELECT u.id, u.username, u.display_name, u.role, m.joined_at
         FROM {$prefix}user_group_members m
         JOIN {$prefix}users u ON u.id=m.user_id
         WHERE m.group_id=? ORDER BY u.username",
        [$editGroupId]
    );
    $nonMembers = $db->get_results(
        "SELECT id, username, display_name FROM {$prefix}users
         WHERE id NOT IN (SELECT user_id FROM {$prefix}user_group_members WHERE group_id=?)
         ORDER BY username",
        [$editGroupId]
    );
?>
<div class="posts-header">
    <h2 style="margin:0;">📂 <?php echo htmlspecialchars($grp->name, ENT_QUOTES); ?></h2>
    <a href="<?php echo SITE_URL; ?>/admin/groups?tab=groups" class="btn-sm btn-secondary">← Alle Gruppen</a>
</div>

<div class="post-edit-layout">
    <div class="post-edit-main">
        <!-- Gruppe bearbeiten -->
        <div class="post-card">
            <h3>✏️ Gruppe bearbeiten</h3>
            <form method="post" action="<?php echo SITE_URL; ?>/admin/groups?tab=groups&view=detail&id=<?php echo $editGroupId; ?>">
                <input type="hidden" name="_csrf"    value="<?php echo $csrfGroup; ?>">
                <input type="hidden" name="_action"  value="edit_group">
                <input type="hidden" name="group_id" value="<?php echo $editGroupId; ?>">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                    <div class="field-group" style="grid-column:1/-1;">
                        <label>Name *</label>
                        <input type="text" name="name" value="<?php echo htmlspecialchars($grp->name, ENT_QUOTES); ?>" required>
                    </div>
                    <div class="field-group" style="grid-column:1/-1;">
                        <label>Beschreibung</label>
                        <textarea name="description"><?php echo htmlspecialchars($grp->description ?? '', ENT_QUOTES); ?></textarea>
                    </div>
                    <div class="field-group">
                        <label>Plan-ID <span style="font-weight:400;color:#94a3b8;">(optional)</span></label>
                        <input type="number" name="plan_id" value="<?php echo (int)($grp->plan_id ?? 0) ?: ''; ?>" placeholder="0">
                    </div>
                    <div class="field-group">
                        <label style="cursor:pointer;display:flex;align-items:center;gap:.4rem;margin-top:1.4rem;">
                            <input type="checkbox" name="is_active" <?php echo $grp->is_active ? 'checked' : ''; ?>> Aktiv
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn-sm btn-primary">💾 Speichern</button>
            </form>
        </div>

        <!-- Mitglieder -->
        <div class="post-card">
            <h3>👥 Mitglieder (<?php echo count($members); ?>)</h3>
            <?php if (empty($members)): ?>
            <p style="color:#94a3b8;font-size:.875rem;">Noch keine Mitglieder in dieser Gruppe.</p>
            <?php else: ?>
            <div class="member-list">
                <?php foreach ($members as $m):
                    $colors   = ['#2563eb','#7c3aed','#db2777','#059669','#d97706','#dc2626'];
                    $ac       = $colors[abs(crc32($m->username)) % count($colors)];
                    $roleLbl  = ['admin' => '🔑 Admin', 'editor' => '✏️ Editor', 'member' => '👤 Member'][$m->role] ?? $m->role;
                ?>
                <div class="member-row">
                    <div style="display:flex;align-items:center;gap:.5rem;">
                        <div class="user-avatar-sm" style="background:<?php echo $ac; ?>"><?php echo strtoupper(substr($m->username, 0, 1)); ?></div>
                        <div>
                            <span style="font-weight:600;"><?php echo htmlspecialchars($m->username, ENT_QUOTES); ?></span>
                            <?php if (!empty($m->display_name) && $m->display_name !== $m->username): ?>
                            <span style="color:#94a3b8;font-size:.78rem;"> · <?php echo htmlspecialchars($m->display_name, ENT_QUOTES); ?></span>
                            <?php endif; ?>
                            <span style="color:#94a3b8;font-size:.74rem;margin-left:.3rem;"><?php echo $roleLbl; ?></span>
                        </div>
                    </div>
                    <div style="display:flex;align-items:center;gap:.4rem;">
                        <span style="font-size:.72rem;color:#94a3b8;">seit <?php echo date('d.m.Y', strtotime($m->joined_at)); ?></span>
                        <form method="post" action="<?php echo SITE_URL; ?>/admin/groups" style="display:inline;">
                            <input type="hidden" name="_csrf"     value="<?php echo $csrfGroup; ?>">
                            <input type="hidden" name="_action"   value="remove_member">
                            <input type="hidden" name="group_id"  value="<?php echo $editGroupId; ?>">
                            <input type="hidden" name="user_id"   value="<?php echo (int)$m->id; ?>">
                            <button type="submit" class="btn-sm btn-danger" style="padding:.2rem .4rem;"
                                    onclick="return confirm('Mitglied aus Gruppe entfernen?')">✕</button>
                        </form>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if (!empty($nonMembers)): ?>
            <div style="margin-top:1rem;padding-top:.75rem;border-top:1px solid #f1f5f9;">
                <h3 style="margin:0 0 .6rem;font-size:.8rem;">Mitglied hinzufügen</h3>
                <form method="post" action="<?php echo SITE_URL; ?>/admin/groups" style="display:flex;gap:.5rem;flex-wrap:wrap;">
                    <input type="hidden" name="_csrf"    value="<?php echo $csrfGroup; ?>">
                    <input type="hidden" name="_action"  value="add_member">
                    <input type="hidden" name="group_id" value="<?php echo $editGroupId; ?>">
                    <select name="user_id" style="flex:1;min-width:200px;padding:.35rem .6rem;border:1px solid #cbd5e1;border-radius:6px;font-size:.85rem;">
                        <option value="">Benutzer wählen…</option>
                        <?php foreach ($nonMembers as $nm): ?>
                        <option value="<?php echo (int)$nm->id; ?>">
                            <?php echo htmlspecialchars($nm->username, ENT_QUOTES); ?>
                            <?php if (!empty($nm->display_name) && $nm->display_name !== $nm->username): ?>
                             (<?php echo htmlspecialchars($nm->display_name, ENT_QUOTES); ?>)
                            <?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn-sm btn-success">➕ Hinzufügen</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="post-edit-side">
        <div class="post-card">
            <h3>ℹ️ Info</h3>
            <div style="font-size:.8rem;display:flex;flex-direction:column;gap:.25rem;color:#64748b;">
                <span>ID: #<?php echo (int)$grp->id; ?></span>
                <span>Slug: <code style="background:#f1f5f9;padding:1px 4px;border-radius:3px;"><?php echo htmlspecialchars($grp->slug, ENT_QUOTES); ?></code></span>
                <span>Erstellt: <?php echo date('d.m.Y', strtotime($grp->created_at)); ?></span>
                <span>Status: <span class="status-badge <?php echo $grp->is_active ? 'status-active' : 'status-inactive'; ?>"><?php echo $grp->is_active ? 'Aktiv' : 'Inaktiv'; ?></span></span>
            </div>
            <div style="margin-top:1rem;">
                <form method="post" action="<?php echo SITE_URL; ?>/admin/groups" id="deleteGroupForm">
                    <input type="hidden" name="_csrf"    value="<?php echo $csrfGroup; ?>">
                    <input type="hidden" name="_action"  value="delete_group">
                    <input type="hidden" name="group_id" value="<?php echo $editGroupId; ?>">
                </form>
                <button type="submit" form="deleteGroupForm" class="btn-sm btn-danger" style="width:100%;"
                        onclick="return confirm('Gruppe und alle Mitgliedschaften löschen?')">🗑️ Gruppe löschen</button>
            </div>
        </div>
    </div>
</div>

<?php else: // Gruppen-Liste ?>

<div class="posts-header">
    <h2 style="margin:0;">👥 Gruppen</h2>
</div>

<?php if (empty($groups)): ?>
<div class="post-card" style="text-align:center;padding:3rem;color:#94a3b8;">
    <div style="font-size:3rem;margin-bottom:1rem;">👥</div>
    <p>Noch keine Gruppen vorhanden.</p>
</div>
<?php else: ?>
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:auto;margin-bottom:1.5rem;">
    <table class="posts-table">
        <thead><tr>
            <th>Name</th>
            <th style="width:130px;">Slug</th>
            <th style="width:80px;text-align:center;">Mitglieder</th>
            <th style="width:90px;text-align:center;">Status</th>
            <th style="width:130px;text-align:right;"></th>
        </tr></thead>
        <tbody>
        <?php foreach ($groups as $g): ?>
        <tr>
            <td>
                <a href="<?php echo SITE_URL; ?>/admin/groups?tab=groups&view=detail&id=<?php echo (int)$g->id; ?>"
                   style="font-weight:600;color:#1e293b;text-decoration:none;">
                    <?php echo htmlspecialchars($g->name, ENT_QUOTES); ?>
                </a>
                <?php if (!empty($g->description)): ?>
                <div style="font-size:.74rem;color:#94a3b8;"><?php echo htmlspecialchars(substr($g->description, 0, 60), ENT_QUOTES) . (strlen($g->description) > 60 ? '…' : ''); ?></div>
                <?php endif; ?>
            </td>
            <td style="font-size:.78rem;font-family:monospace;color:#64748b;"><?php echo htmlspecialchars($g->slug, ENT_QUOTES); ?></td>
            <td style="text-align:center;font-size:.8rem;color:#64748b;"><?php echo (int)($g->member_count ?? 0); ?></td>
            <td style="text-align:center;">
                <span class="status-badge <?php echo $g->is_active ? 'status-active' : 'status-inactive'; ?>">
                    <?php echo $g->is_active ? 'Aktiv' : 'Inaktiv'; ?>
                </span>
            </td>
            <td style="text-align:right;white-space:nowrap;">
                <a href="<?php echo SITE_URL; ?>/admin/groups?tab=groups&view=detail&id=<?php echo (int)$g->id; ?>"
                   class="btn-sm btn-secondary">✏️ Details</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Neue Gruppe erstellen -->
<div class="post-card">
    <h3>➕ Neue Gruppe erstellen</h3>
    <form method="post" action="<?php echo SITE_URL; ?>/admin/groups?tab=groups">
        <input type="hidden" name="_csrf"   value="<?php echo $csrfGroup; ?>">
        <input type="hidden" name="_action" value="create_group">
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
            <div class="field-group">
                <label>Name *</label>
                <input type="text" name="name" required placeholder="z.B. Premium-Mitglieder"
                       oninput="this.form.slug.value=this.value.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/(^-|-$)/g,'')">
            </div>
            <div class="field-group">
                <label>Slug <span style="font-weight:400;color:#94a3b8;">(auto)</span></label>
                <input type="text" name="slug" placeholder="premium-mitglieder" pattern="[a-z0-9\-]+">
            </div>
            <div class="field-group" style="grid-column:1/-1;">
                <label>Beschreibung</label>
                <textarea name="description" placeholder="Optionale Beschreibung…"></textarea>
            </div>
            <div class="field-group">
                <label>Plan-ID <span style="font-weight:400;color:#94a3b8;">(optional)</span></label>
                <input type="number" name="plan_id" placeholder="0" min="0">
            </div>
            <div class="field-group">
                <label style="cursor:pointer;display:flex;align-items:center;gap:.4rem;margin-top:1.4rem;">
                    <input type="checkbox" name="is_active" checked> Aktiv
                </label>
            </div>
        </div>
        <button type="submit" class="btn-sm btn-primary">✅ Gruppe erstellen</button>
    </form>
</div>

<?php endif; // detail vs. list ?>

<?php elseif ($activeTab === 'roles'): ?>
<?php /* ================================================================
        ROLLEN-TAB
   ================================================================ */
$allCapsForView = ['manage_posts','manage_pages','manage_users','manage_plugins',
                   'manage_themes','manage_settings','view_analytics','manage_media'];
?>

<div class="posts-header">
    <h2 style="margin:0;">🔑 Rollen</h2>
</div>

<?php if (!empty($roles)): ?>
<div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:auto;margin-bottom:1.5rem;">
    <table class="posts-table">
        <thead><tr>
            <th>Rolle</th>
            <th style="width:140px;">Interner Name</th>
            <th>Beschreibung</th>
            <th>Capabilities</th>
            <th style="width:120px;text-align:right;"></th>
        </tr></thead>
        <tbody>
        <?php
        $coreRoles = ['admin', 'member', 'editor'];
        foreach ($roles as $role):
            $caps     = json_decode($role->capabilities ?? '[]', true) ?? [];
            $isCore   = in_array($role->name, $coreRoles);
        ?>
        <tr>
            <td>
                <span style="font-weight:600;"><?php echo htmlspecialchars($role->display_name, ENT_QUOTES); ?></span>
                <?php if ($isCore): ?>
                <span style="background:#fef3c7;color:#92400e;padding:1px 5px;border-radius:4px;font-size:.68rem;margin-left:4px;">Core</span>
                <?php endif; ?>
            </td>
            <td style="font-family:monospace;font-size:.8rem;color:#64748b;"><?php echo htmlspecialchars($role->name, ENT_QUOTES); ?></td>
            <td style="font-size:.82rem;color:#64748b;"><?php echo htmlspecialchars($role->description ?? '', ENT_QUOTES); ?></td>
            <td>
                <div style="display:flex;flex-wrap:wrap;gap:.2rem;">
                    <?php if (empty($caps)): ?>
                    <span style="color:#94a3b8;font-size:.78rem;">Keine</span>
                    <?php else: ?>
                    <?php foreach ($caps as $cap): ?>
                    <span class="cap-pill"><?php echo htmlspecialchars($capsLabels[$cap] ?? $cap, ENT_QUOTES); ?></span>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </td>
            <td style="text-align:right;white-space:nowrap;">
                <a href="<?php echo SITE_URL; ?>/admin/groups?tab=roles&edit_role=<?php echo (int)$role->id; ?>"
                   class="btn-sm btn-secondary">✏️</a>
                <?php if (!$isCore): ?>
                <button type="button" class="btn-sm btn-danger"
                        onclick="deleteRole(<?php echo (int)$role->id; ?>)">🗑️</button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- Rolle bearbeiten oder neu erstellen -->
<?php
$editRole = $editRoleId > 0 ? $db->get_row("SELECT * FROM {$prefix}roles WHERE id=?", [$editRoleId]) : null;
$editCaps = $editRole ? (json_decode($editRole->capabilities ?? '[]', true) ?? []) : [];
$isEditCore = $editRole ? in_array($editRole->name, $coreRoles) : false;
?>

<div class="post-card">
    <h3><?php echo $editRole ? '✏️ Rolle bearbeiten: ' . htmlspecialchars($editRole->display_name, ENT_QUOTES) : '➕ Neue Rolle erstellen'; ?></h3>

    <?php if ($editRole && $isEditCore): ?>
    <div class="notice" style="background:#fefce8;color:#854d0e;border:1px solid #fde047;margin-bottom:.9rem;">
        ⚠️ Core-Rollen (admin, member, editor) werden vom System verwaltet. Capabilities können geändert werden.
    </div>
    <?php endif; ?>

    <form method="post" action="<?php echo SITE_URL; ?>/admin/groups?tab=roles<?php echo $editRole ? '&edit_role=' . (int)$editRole->id : ''; ?>">
        <input type="hidden" name="_csrf"    value="<?php echo $csrfRole; ?>">
        <input type="hidden" name="_action"  value="<?php echo $editRole ? 'edit_role' : 'create_role'; ?>">
        <?php if ($editRole): ?>
        <input type="hidden" name="role_id"  value="<?php echo (int)$editRole->id; ?>">
        <?php endif; ?>

        <div class="post-edit-layout">
            <div class="post-edit-main">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                    <?php if (!$editRole): ?>
                    <div class="field-group">
                        <label>Interner Name * <span style="font-weight:400;color:#94a3b8;">(nur a-z, 0-9, _)</span></label>
                        <input type="text" name="name" required placeholder="z.B. moderator" pattern="[a-z0-9_]+">
                    </div>
                    <?php endif; ?>
                    <div class="field-group" <?php echo !$editRole ? '' : 'style="grid-column:1/-1;"'; ?>>
                        <label>Anzeigename *</label>
                        <input type="text" name="display_name" required
                               value="<?php echo htmlspecialchars($editRole->display_name ?? '', ENT_QUOTES); ?>"
                               placeholder="z.B. Moderator">
                    </div>
                    <div class="field-group" style="grid-column:1/-1;">
                        <label>Beschreibung</label>
                        <textarea name="description" placeholder="Optionale Beschreibung…"><?php echo htmlspecialchars($editRole->description ?? '', ENT_QUOTES); ?></textarea>
                    </div>
                </div>

                <div>
                    <label style="display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:.5rem;">Capabilities</label>
                    <div class="caps-grid">
                        <?php foreach ($allCapsForView as $cap): ?>
                        <label class="cap-check">
                            <input type="checkbox" name="capabilities[]" value="<?php echo $cap; ?>"
                                   <?php echo in_array($cap, $editCaps) ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($capsLabels[$cap] ?? $cap, ENT_QUOTES); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <div class="post-edit-side">
                <div class="post-card">
                    <h3>⚙️ Aktionen</h3>
                    <div style="display:flex;flex-direction:column;gap:.45rem;">
                        <button type="submit" class="btn-sm btn-primary btn-lg" style="width:100%;">
                            <?php echo $editRole ? '💾 Speichern' : '✅ Rolle erstellen'; ?>
                        </button>
                        <?php if ($editRole): ?>
                        <a href="<?php echo SITE_URL; ?>/admin/groups?tab=roles" class="btn-sm btn-secondary" style="width:100%;justify-content:center;">
                            ✕ Abbrechen
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<form id="deleteRoleForm" method="post" action="<?php echo SITE_URL; ?>/admin/groups?tab=roles" style="display:none;">
    <input type="hidden" name="_csrf"    value="<?php echo $csrfRole; ?>">
    <input type="hidden" name="_action"  value="delete_role">
    <input type="hidden" name="role_id"  id="deleteRoleId" value="">
</form>
<script>
function deleteRole(id) {
    if (confirm('Rolle wirklich löschen?')) {
        document.getElementById('deleteRoleId').value = id;
        document.getElementById('deleteRoleForm').submit();
    }
}
</script>

<?php endif; // tab ?>

<?php renderAdminLayoutEnd(); ?>

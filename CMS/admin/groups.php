<?php
declare(strict_types=1);

/**
 * Admin - Gruppen & Rollenverwaltung (vollständig ausgebaut)
 *
 * @package CMSv2\Admin
 */

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
$search  = trim($_GET['search'] ?? '');
$gWhere  = '';
$gParams = [];
if (!empty($search)) {
    $gWhere  = 'WHERE (g.name LIKE ? OR g.slug LIKE ? OR g.description LIKE ?)';
    $gParams = ["%{$search}%", "%{$search}%", "%{$search}%"];
}
$groups = $db->get_results(
    "SELECT g.*, (SELECT COUNT(*) FROM {$prefix}user_group_members m WHERE m.group_id=g.id) AS member_count
     FROM {$prefix}user_groups g {$gWhere} ORDER BY g.name",
    $gParams
);
$roles   = $db->get_results("SELECT * FROM {$prefix}roles ORDER BY name");
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
$viewMode     = in_array($_GET['view'] ?? '', ['list', 'detail', 'new']) ? ($_GET['view'] ?? 'list') : 'list';
$detailId     = (int)($_GET['id'] ?? 0);
$editGroupId  = ($viewMode === 'detail' && $detailId > 0) ? $detailId : 0;
$editRoleId   = (int)($_GET['edit_role'] ?? 0);

require_once __DIR__ . '/partials/admin-menu.php';
$_layoutTitle = $activeTab === 'roles' ? 'Rollen & Rechte' : 'Gruppen';
$_layoutSlug  = $activeTab === 'roles' ? 'roles' : 'groups';
renderAdminLayoutStart($_layoutTitle, $_layoutSlug);
?>

<?php foreach ($messages as $m):
    $cls = $m['type'] === 'success' ? 'alert-success' : 'alert-error';
?>
<div class="alert <?php echo $cls; ?>"><?php echo htmlspecialchars($m['text'], ENT_QUOTES, 'UTF-8'); ?></div>
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
<div class="admin-page-header">
    <div>
        <h2>📂 <?php echo htmlspecialchars($grp->name, ENT_QUOTES); ?></h2>
    </div>
    <div class="header-actions">
        <a href="<?php echo SITE_URL; ?>/admin/groups?tab=groups" class="btn btn-secondary btn-sm">← Alle Gruppen</a>
    </div>
</div>

<div style="display:grid;grid-template-columns:3fr 1fr;gap:1.5rem;align-items:start;">
    <div class="grid-main">
        <!-- Gruppe bearbeiten -->
        <div class="admin-card">
            <h3>✏️ Gruppe bearbeiten</h3>
            <form method="post" action="<?php echo SITE_URL; ?>/admin/groups?tab=groups&view=detail&id=<?php echo $editGroupId; ?>">
                <input type="hidden" name="_csrf"    value="<?php echo $csrfGroup; ?>">
                <input type="hidden" name="_action"  value="edit_group">
                <input type="hidden" name="group_id" value="<?php echo $editGroupId; ?>">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                    <div class="form-group" style="grid-column:1/-1;">
                        <label>Name *</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($grp->name, ENT_QUOTES); ?>" required>
                    </div>
                    <div class="form-group" style="grid-column:1/-1;">
                        <label>Beschreibung</label>
                        <textarea name="description" class="form-control"><?php echo htmlspecialchars($grp->description ?? '', ENT_QUOTES); ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Plan-ID <span style="font-weight:400;color:#94a3b8;">(optional)</span></label>
                        <input type="number" name="plan_id" class="form-control" value="<?php echo (int)($grp->plan_id ?? 0) ?: ''; ?>" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label style="cursor:pointer;display:flex;align-items:center;gap:.4rem;margin-top:1.4rem;">
                            <input type="checkbox" name="is_active" <?php echo $grp->is_active ? 'checked' : ''; ?>> Aktiv
                        </label>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-sm">💾 Speichern</button>
            </form>
        </div>

        <!-- Mitglieder -->
        <div class="admin-card">
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
                        <form method="post" action="<?php echo SITE_URL; ?>/admin/groups" id="removeMemberForm_<?php echo (int)$m->id; ?>" style="display:none;">
                            <input type="hidden" name="_csrf"     value="<?php echo $csrfGroup; ?>">
                            <input type="hidden" name="_action"   value="remove_member">
                            <input type="hidden" name="group_id"  value="<?php echo $editGroupId; ?>">
                            <input type="hidden" name="user_id"   value="<?php echo (int)$m->id; ?>">
                        </form>
                        <button type="button" class="btn btn-danger btn-sm"
                                onclick="openMemberRemoveModal('removeMemberForm_<?php echo (int)$m->id; ?>', <?php echo json_encode($m->username ?? ''); ?>)">✕</button>
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
                    <select name="user_id" class="form-control" style="flex:1;min-width:200px;">
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
                    <button type="submit" class="btn btn-primary btn-sm">➕ Hinzufügen</button>
                </form>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="grid-side">
        <div class="admin-card">
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
                <button type="button" class="btn btn-danger" style="width:100%;"
                        onclick="openModal('groupDetailDeleteModal')">🗑️ Gruppe löschen</button>
            </div>
        </div>
    </div>
</div>

<!-- Mitglied entfernen – Bestätigungs-Modal -->
<div id="memberRemoveModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:480px;">
        <div class="modal-header">
            <h3>Mitglied entfernen</h3>
            <button class="modal-close" onclick="closeModal('memberRemoveModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p>Soll <strong id="memberRemoveName"></strong> wirklich aus dieser Gruppe entfernt werden?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('memberRemoveModal')">Abbrechen</button>
            <button type="button" class="btn btn-danger" id="memberRemoveConfirmBtn">✕ Entfernen</button>
        </div>
    </div>
</div>

<!-- Gruppe löschen (Detail) – Bestätigungs-Modal -->
<div id="groupDetailDeleteModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:480px;">
        <div class="modal-header">
            <h3>Gruppe löschen</h3>
            <button class="modal-close" onclick="closeModal('groupDetailDeleteModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p>Soll diese Gruppe und <strong>alle zugehörigen Mitgliedschaften</strong> wirklich gelöscht werden?</p>
            <p style="color:#ef4444;font-size:.875rem;">⚠️ Diese Aktion kann nicht rückgängig gemacht werden.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('groupDetailDeleteModal')">Abbrechen</button>
            <button type="button" class="btn btn-danger" onclick="document.getElementById('deleteGroupForm').submit()">🗑️ Endgültig löschen</button>
        </div>
    </div>
</div>

<script>
function openMemberRemoveModal(formId, username) {
    document.getElementById('memberRemoveName').textContent = username;
    document.getElementById('memberRemoveConfirmBtn').onclick = function() {
        document.getElementById(formId).submit();
    };
    openModal('memberRemoveModal');
}
</script>

<?php elseif ($viewMode === 'new'): // Neue Gruppe anlegen ?>

<div class="admin-page-header">
    <div>
        <h2>➕ Neue Gruppe anlegen</h2>
    </div>
    <div class="header-actions">
        <a href="<?php echo SITE_URL; ?>/admin/groups?tab=groups" class="btn btn-secondary btn-sm">← Alle Gruppen</a>
    </div>
</div>

<form method="post" action="<?php echo SITE_URL; ?>/admin/groups?tab=groups">
    <input type="hidden" name="_csrf"   value="<?php echo $csrfGroup; ?>">
    <input type="hidden" name="_action" value="create_group">

    <div style="display:grid;grid-template-columns:3fr 1fr;gap:1.5rem;align-items:start;">
        <div class="grid-main">
            <div class="admin-card">
                <h3>📂 Gruppendetails</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                    <div class="form-group">
                        <label>Name *</label>
                        <input type="text" name="name" class="form-control" required placeholder="z.B. Premium-Mitglieder"
                               oninput="this.form.slug.value=this.value.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/(^-|-$)/g,'')">
                    </div>
                    <div class="form-group">
                        <label>Slug <span style="font-weight:400;color:#94a3b8;">(auto)</span></label>
                        <input type="text" name="slug" class="form-control" placeholder="premium-mitglieder" pattern="[a-z0-9\-]+">
                    </div>
                    <div class="form-group" style="grid-column:1/-1;">
                        <label>Beschreibung</label>
                        <textarea name="description" class="form-control" placeholder="Optionale Beschreibung…"></textarea>
                    </div>
                    <div class="form-group">
                        <label>Plan-ID <span style="font-weight:400;color:#94a3b8;">(optional)</span></label>
                        <input type="number" name="plan_id" class="form-control" placeholder="0" min="0">
                        <small class="form-text">Verknüpft diese Gruppe mit einem Abo-Paket.</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid-side">
            <div class="admin-card">
                <h3>⚙️ Status</h3>
                <div class="form-group">
                    <label style="cursor:pointer;display:flex;align-items:center;gap:.4rem;">
                        <input type="checkbox" name="is_active" checked> Gruppe aktiv
                    </label>
                    <small class="form-text">Inaktive Gruppen sind für Members nicht sichtbar.</small>
                </div>
                <div style="margin-top:1rem;">
                    <button type="submit" class="btn btn-primary" style="width:100%;">✅ Gruppe erstellen</button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php else: // Gruppen-Liste ?>

<div class="admin-page-header">
    <div>
        <h2>📂 Gruppen</h2>
    </div>
    <div class="header-actions">
        <a href="<?php echo SITE_URL; ?>/admin/groups?tab=groups&view=new" class="btn btn-primary btn-sm">➕ Neue Gruppe</a>
    </div>
</div>

<form method="get" action="<?php echo SITE_URL; ?>/admin/groups">
    <input type="hidden" name="tab" value="groups">
    <div style="display:flex;gap:.75rem;align-items:center;margin-bottom:1.5rem;">
        <div style="display:flex;gap:.5rem;flex:1;">
            <input type="text" name="search" class="form-control" value="<?php echo htmlspecialchars($search); ?>" placeholder="Suche nach Name oder Slug…">
            <button type="submit" class="btn btn-secondary">🔍</button>
        </div>
        <?php if ($search): ?>
        <a href="<?php echo SITE_URL; ?>/admin/groups?tab=groups" class="btn btn-secondary btn-sm">✕ Filter löschen</a>
        <?php endif; ?>
    </div>
</form>

<?php if (empty($groups)): ?>
<div class="empty-state">
    <div style="font-size:3rem;margin-bottom:1rem;">📂</div>
    <p><?php echo $search
        ? 'Keine Treffer für <strong>' . htmlspecialchars($search, ENT_QUOTES) . '</strong>'
        : 'Noch keine Gruppen vorhanden.'; ?></p>
</div>
<?php else: ?>
<div class="users-table-container">
    <table class="users-table">
        <thead><tr>
            <th>Name</th>
            <th style="width:160px;">Slug</th>
            <th style="width:80px;text-align:center;">Mitglieder</th>
            <th style="width:90px;text-align:center;">Status</th>
            <th style="width:130px;text-align:right;"></th>
        </tr></thead>
        <tbody>
        <?php
        $grpColors = ['#2563eb','#7c3aed','#db2777','#059669','#d97706','#dc2626'];
        foreach ($groups as $g):
            $gc = $grpColors[abs(crc32($g->slug ?? $g->name)) % count($grpColors)];
        ?>
        <tr>
            <td>
                <div style="display:flex;align-items:center;gap:.6rem;">
                    <div style="width:32px;height:32px;border-radius:8px;background:<?php echo $gc; ?>;
                                display:flex;align-items:center;justify-content:center;
                                color:#fff;font-size:.85rem;font-weight:700;flex-shrink:0;">
                        <?php echo strtoupper(substr($g->name, 0, 1)); ?>
                    </div>
                    <div>
                        <a href="<?php echo SITE_URL; ?>/admin/groups?tab=groups&view=detail&id=<?php echo (int)$g->id; ?>"
                           style="font-weight:600;color:#1e293b;text-decoration:none;">
                            <?php echo htmlspecialchars($g->name, ENT_QUOTES); ?>
                        </a>
                        <?php if (!empty($g->description)): ?>
                        <div style="font-size:.74rem;color:#94a3b8;"><?php echo htmlspecialchars(mb_substr($g->description, 0, 70), ENT_QUOTES) . (mb_strlen($g->description) > 70 ? '…' : ''); ?></div>
                        <?php endif; ?>
                    </div>
                </div>
            </td>
            <td style="font-size:.78rem;font-family:monospace;color:#64748b;"><?php echo htmlspecialchars($g->slug, ENT_QUOTES); ?></td>
            <td style="text-align:center;font-size:.8rem;color:#64748b;"><?php echo (int)($g->member_count ?? 0); ?></td>
            <td style="text-align:center;">
                <span class="status-badge <?php echo $g->is_active ? 'active' : 'inactive'; ?>">
                    <?php echo $g->is_active ? 'Aktiv' : 'Inaktiv'; ?>
                </span>
            </td>
            <td style="text-align:right;white-space:nowrap;">
                <a href="<?php echo SITE_URL; ?>/admin/groups?tab=groups&view=detail&id=<?php echo (int)$g->id; ?>"
                   class="btn btn-secondary btn-sm" title="Details &amp; Bearbeiten">✏️</a>
                <button type="button" class="btn btn-danger btn-sm" title="Löschen"
                        onclick="deleteGroup(<?php echo (int)$g->id; ?>, '<?php echo htmlspecialchars(addslashes($g->name), ENT_QUOTES); ?>')">🗑️</button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<form id="deleteGroupForm" method="post" action="<?php echo SITE_URL; ?>/admin/groups?tab=groups" style="display:none;">
    <input type="hidden" name="_csrf"    value="<?php echo $csrfGroup; ?>">
    <input type="hidden" name="_action"  value="delete_group">
    <input type="hidden" name="group_id" id="deleteGroupId" value="">
</form>

<!-- Gruppe löschen (Liste) – Bestätigungs-Modal -->
<div id="groupDeleteModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:480px;">
        <div class="modal-header">
            <h3>Gruppe löschen</h3>
            <button class="modal-close" onclick="closeModal('groupDeleteModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p>Soll die Gruppe <strong id="groupDeleteName"></strong> und alle zugehörigen Mitgliedschaften wirklich gelöscht werden?</p>
            <p style="color:#ef4444;font-size:.875rem;">⚠️ Diese Aktion kann nicht rückgängig gemacht werden.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('groupDeleteModal')">Abbrechen</button>
            <button type="button" class="btn btn-danger" onclick="document.getElementById('deleteGroupForm').submit()">🗑️ Endgültig löschen</button>
        </div>
    </div>
</div>

<script>
function deleteGroup(id, name) {
    document.getElementById('deleteGroupId').value = id;
    document.getElementById('groupDeleteName').textContent = name;
    openModal('groupDeleteModal');
}
</script>

<?php endif; // detail vs. list / new ?>

<?php elseif ($activeTab === 'roles'): ?>
<?php /* ================================================================
        ROLLEN-TAB
   ================================================================ */
$allCapsForView = ['manage_posts','manage_pages','manage_users','manage_plugins',
                   'manage_themes','manage_settings','view_analytics','manage_media'];
?>

<div class="admin-page-header">
    <div>
        <h2>🔑 Rollen</h2>
    </div>
</div>

<?php if (!empty($roles)): ?>
<div class="users-table-container" style="margin-bottom:1.5rem;">
    <table class="users-table">
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
                   class="btn btn-secondary btn-sm">✏️</a>
                <?php if (!$isCore): ?>
                <button type="button" class="btn btn-danger btn-sm"
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

<div class="admin-card">
    <h3><?php echo $editRole ? '✏️ Rolle bearbeiten: ' . htmlspecialchars($editRole->display_name, ENT_QUOTES) : '➕ Neue Rolle erstellen'; ?></h3>

    <?php if ($editRole && $isEditCore): ?>
    <div class="alert" style="background:#fefce8;color:#854d0e;border-left:4px solid #fde047;margin-bottom:.9rem;">
        ⚠️ Core-Rollen (admin, member, editor) werden vom System verwaltet. Capabilities können geändert werden.
    </div>
    <?php endif; ?>

    <form method="post" action="<?php echo SITE_URL; ?>/admin/groups?tab=roles<?php echo $editRole ? '&edit_role=' . (int)$editRole->id : ''; ?>">
        <input type="hidden" name="_csrf"    value="<?php echo $csrfRole; ?>">
        <input type="hidden" name="_action"  value="<?php echo $editRole ? 'edit_role' : 'create_role'; ?>">
        <?php if ($editRole): ?>
        <input type="hidden" name="role_id"  value="<?php echo (int)$editRole->id; ?>">
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:3fr 1fr;gap:1.5rem;align-items:start;">
            <div class="grid-main">
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                    <?php if (!$editRole): ?>
                    <div class="form-group">
                        <label>Interner Name * <span style="font-weight:400;color:#94a3b8;">(nur a-z, 0-9, _)</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="z.B. moderator" pattern="[a-z0-9_]+">
                    </div>
                    <?php endif; ?>
                    <div class="form-group" <?php echo !$editRole ? '' : 'style="grid-column:1/-1;"'; ?>>
                        <label>Anzeigename *</label>
                        <input type="text" name="display_name" class="form-control" required
                               value="<?php echo htmlspecialchars($editRole->display_name ?? '', ENT_QUOTES); ?>"
                               placeholder="z.B. Moderator">
                    </div>
                    <div class="form-group" style="grid-column:1/-1;">
                        <label>Beschreibung</label>
                        <textarea name="description" class="form-control" placeholder="Optionale Beschreibung…"><?php echo htmlspecialchars($editRole->description ?? '', ENT_QUOTES); ?></textarea>
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

            <div class="grid-side">
                <div class="admin-card">
                    <h3>⚙️ Aktionen</h3>
                    <div style="display:flex;flex-direction:column;gap:.45rem;">
                        <button type="submit" class="btn btn-primary" style="width:100%;">
                            <?php echo $editRole ? '💾 Speichern' : '✅ Rolle erstellen'; ?>
                        </button>
                        <?php if ($editRole): ?>
                        <a href="<?php echo SITE_URL; ?>/admin/groups?tab=roles" class="btn btn-secondary btn-sm" style="width:100%;justify-content:center;">
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
<!-- Rolle löschen – Bestätigungs-Modal -->
<div id="roleDeleteModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:480px;">
        <div class="modal-header">
            <h3>Rolle löschen</h3>
            <button class="modal-close" onclick="closeModal('roleDeleteModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p>Soll diese Rolle wirklich gelöscht werden?</p>
            <p style="color:#ef4444;font-size:.875rem;">⚠️ Diese Aktion kann nicht rückgängig gemacht werden.</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="closeModal('roleDeleteModal')">Abbrechen</button>
            <button type="button" class="btn btn-danger" onclick="document.getElementById('deleteRoleForm').submit()">🗑️ Endgültig löschen</button>
        </div>
    </div>
</div>

<script>
function deleteRole(id) {
    document.getElementById('deleteRoleId').value = id;
    openModal('roleDeleteModal');
}
</script>

<?php endif; // tab ?>

<?php renderAdminLayoutEnd(); ?>


<?php
/**
 * Admin - Benutzerverwaltung (vollständig ausgebaut)
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

$security        = Security::instance();
$db              = Database::instance();
$prefix          = $db->prefix;
$current_user_id = (int)($_SESSION['user_id'] ?? 0);

$messages = [];

// ── Create User ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'create_user') {
    if (!$security->verifyToken($_POST['_csrf'] ?? '', 'users_create')) {
        $messages[] = ['type' => 'error', 'text' => 'Sicherheitscheck fehlgeschlagen.'];
    } else {
        $username     = trim($_POST['username']     ?? '');
        $email        = trim($_POST['email']        ?? '');
        $password     = $_POST['password']          ?? '';
        $display_name = trim($_POST['display_name'] ?? '');
        $role         = in_array($_POST['role'] ?? '', ['admin', 'member', 'editor']) ? $_POST['role'] : 'member';

        if (empty($username) || empty($email) || empty($password)) {
            $messages[] = ['type' => 'error', 'text' => 'Benutzername, E-Mail und Passwort sind Pflichtfelder.'];
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $messages[] = ['type' => 'error', 'text' => 'Ungültige E-Mail-Adresse.'];
        } elseif (strlen($password) < 6) {
            $messages[] = ['type' => 'error', 'text' => 'Passwort muss mindestens 6 Zeichen haben.'];
        } else {
            $existing = $db->get_var("SELECT id FROM {$prefix}users WHERE username=? OR email=?", [$username, $email]);
            if ($existing) {
                $messages[] = ['type' => 'error', 'text' => 'Benutzername oder E-Mail bereits vergeben.'];
            } else {
                $db->execute(
                    "INSERT INTO {$prefix}users (username, email, password, display_name, role, status, created_at) VALUES (?,?,?,?,?,?,NOW())",
                    [$username, $email, password_hash($password, PASSWORD_BCRYPT), $display_name ?: $username, $role, 'active']
                );
                header('Location: ' . SITE_URL . '/admin/users?msg=created');
                exit;
            }
        }
    }
}

// ── Edit User ────────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'edit_user') {
    if (!$security->verifyToken($_POST['_csrf'] ?? '', 'users_edit')) {
        $messages[] = ['type' => 'error', 'text' => 'Sicherheitscheck fehlgeschlagen.'];
    } else {
        $eid          = (int)($_POST['user_id'] ?? 0);
        $email        = trim($_POST['email']        ?? '');
        $display_name = trim($_POST['display_name'] ?? '');
        $role         = in_array($_POST['role'] ?? '', ['admin', 'member', 'editor']) ? $_POST['role'] : 'member';
        $status       = in_array($_POST['status'] ?? '', ['active', 'inactive', 'banned']) ? $_POST['status'] : 'active';
        $new_password = $_POST['new_password'] ?? '';
        $groups       = array_map('intval', (array)($_POST['groups'] ?? []));

        if ($eid < 1 || empty($email)) {
            $messages[] = ['type' => 'error', 'text' => 'Ungültige Eingaben.'];
        } else {
            $upd = "UPDATE {$prefix}users SET email=?, display_name=?, role=?, status=?, updated_at=NOW()";
            $params = [$email, $display_name, $role, $status];
            if (!empty($new_password) && strlen($new_password) >= 6) {
                $upd .= ', password=?';
                $params[] = password_hash($new_password, PASSWORD_BCRYPT);
            }
            $upd .= ' WHERE id=?';
            $params[] = $eid;
            $db->execute($upd, $params);

            // Gruppen aktualisieren
            $db->execute("DELETE FROM {$prefix}user_group_members WHERE user_id=?", [$eid]);
            foreach ($groups as $gid) {
                if ($gid > 0) {
                    $db->execute(
                        "INSERT IGNORE INTO {$prefix}user_group_members (user_id, group_id, joined_at) VALUES (?,?,NOW())",
                        [$eid, $gid]
                    );
                }
            }

            header('Location: ' . SITE_URL . '/admin/users?msg=updated');
            exit;
        }
    }
}

// ── Delete User ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'delete_user') {
    if (!$security->verifyToken($_POST['_csrf'] ?? '', 'users_delete')) {
        $messages[] = ['type' => 'error', 'text' => 'Sicherheitscheck fehlgeschlagen.'];
    } else {
        $did = (int)($_POST['user_id'] ?? 0);
        if ($did > 0 && $did !== $current_user_id) {
            $db->execute("DELETE FROM {$prefix}user_group_members WHERE user_id=?", [$did]);
            $db->execute("DELETE FROM {$prefix}users WHERE id=?", [$did]);
            header('Location: ' . SITE_URL . '/admin/users?msg=deleted');
            exit;
        }
    }
}

// ── Bulk Action ──────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'bulk') {
    if ($security->verifyToken($_POST['_csrf'] ?? '', 'users_bulk')) {
        $bulk_ids    = array_map('intval', (array)($_POST['bulk_ids'] ?? []));
        $bulk_ids    = array_filter($bulk_ids, fn($id) => $id !== $current_user_id);
        $bulk_action = $_POST['bulk_action'] ?? '';
        if (!empty($bulk_ids)) {
            $phs = implode(',', array_fill(0, count($bulk_ids), '?'));
            match ($bulk_action) {
                'ban'         => $db->execute("UPDATE {$prefix}users SET status='banned' WHERE id IN ({$phs})", array_values($bulk_ids)),
                'activate'    => $db->execute("UPDATE {$prefix}users SET status='active' WHERE id IN ({$phs})",  array_values($bulk_ids)),
                'make_admin'  => $db->execute("UPDATE {$prefix}users SET role='admin' WHERE id IN ({$phs})",    array_values($bulk_ids)),
                'make_member' => $db->execute("UPDATE {$prefix}users SET role='member' WHERE id IN ({$phs})",   array_values($bulk_ids)),
                'delete'      => (function() use ($db, $prefix, $phs, $bulk_ids) {
                    $db->execute("DELETE FROM {$prefix}user_group_members WHERE user_id IN ({$phs})", array_values($bulk_ids));
                    $db->execute("DELETE FROM {$prefix}users WHERE id IN ({$phs})",                   array_values($bulk_ids));
                })(),
                default => null
            };
        }
        header('Location: ' . SITE_URL . '/admin/users?msg=bulk_done');
        exit;
    }
}

// ── URL messages ─────────────────────────────────────────────────────────────
if (isset($_GET['msg'])) {
    $msgMap = [
        'created'   => ['success', '✅ Benutzer erfolgreich erstellt.'],
        'updated'   => ['success', '✅ Benutzer erfolgreich aktualisiert.'],
        'deleted'   => ['success', '🗑️ Benutzer gelöscht.'],
        'bulk_done' => ['success', '✅ Aktion ausgeführt.'],
    ];
    if (isset($msgMap[$_GET['msg']])) {
        $messages[] = ['type' => $msgMap[$_GET['msg']][0], 'text' => $msgMap[$_GET['msg']][1]];
    }
}

// ── View / Filter ─────────────────────────────────────────────────────────────
$view       = $_GET['view']   ?? 'list';
$editUserId = (int)($_GET['id'] ?? 0);
$roleFilter = in_array($_GET['role'] ?? '', ['admin', 'member', 'editor', 'banned']) ? $_GET['role'] : 'all';
$search     = trim($_GET['search'] ?? '');
$perPage    = 25;
$page       = max(1, (int)($_GET['p'] ?? 1));

$roleCounts = [];
foreach (['all', 'admin', 'member', 'editor'] as $r) {
    $roleCounts[$r] = (int)$db->get_var(
        $r === 'all'
            ? "SELECT COUNT(*) FROM {$prefix}users WHERE status != 'banned'"
            : "SELECT COUNT(*) FROM {$prefix}users WHERE role=? AND status!='banned'",
        $r === 'all' ? [] : [$r]
    );
}
$roleCounts['banned'] = (int)$db->get_var("SELECT COUNT(*) FROM {$prefix}users WHERE status='banned'");

$statTotal  = (int)$db->get_var("SELECT COUNT(*) FROM {$prefix}users");
$statAdmin  = (int)$db->get_var("SELECT COUNT(*) FROM {$prefix}users WHERE role='admin'");
$statActive = (int)$db->get_var("SELECT COUNT(*) FROM {$prefix}users WHERE status='active'");
$statNew7   = (int)$db->get_var("SELECT COUNT(*) FROM {$prefix}users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)");

$csrfCreate = $security->generateToken('users_create');
$csrfEdit   = $security->generateToken('users_edit');
$csrfDelete = $security->generateToken('users_delete');
$csrfBulk   = $security->generateToken('users_bulk');

require_once __DIR__ . '/partials/admin-menu.php';
renderAdminLayoutStart('Benutzer', 'users');
?>
<style>
.posts-header{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:.75rem;margin-bottom:1.5rem;}
.posts-tabs{display:flex;gap:0;border-bottom:2px solid #e2e8f0;margin-bottom:1.5rem;}
.posts-tab{padding:.5rem 1rem;font-size:.875rem;color:#64748b;text-decoration:none;border-bottom:2px solid transparent;margin-bottom:-2px;transition:color .15s;}
.posts-tab.active{color:#2563eb;border-bottom-color:#2563eb;font-weight:600;}
.posts-tab:hover{color:#1d4ed8;}
.posts-tab .badge{display:inline-flex;align-items:center;justify-content:center;min-width:20px;height:20px;padding:0 5px;background:#e2e8f0;border-radius:10px;font-size:.7rem;font-weight:700;margin-left:.3rem;}
.posts-tab.active .badge{background:#dbeafe;color:#1d4ed8;}
.posts-toolbar{display:flex;align-items:center;gap:.75rem;flex-wrap:wrap;margin-bottom:1rem;}
.posts-search{display:flex;gap:.5rem;flex:1;max-width:380px;}
.posts-search input{flex:1;padding:.4rem .65rem;border:1px solid #cbd5e1;border-radius:6px;font-size:.875rem;}
.posts-search button{padding:.4rem .85rem;background:#2563eb;color:#fff;border:none;border-radius:6px;cursor:pointer;font-size:.875rem;}
.posts-table{width:100%;border-collapse:collapse;font-size:.875rem;}
.posts-table th{background:#f8fafc;padding:.55rem .7rem;text-align:left;font-weight:600;color:#374151;border-bottom:2px solid #e2e8f0;white-space:nowrap;}
.posts-table td{padding:.6rem .7rem;border-bottom:1px solid #f1f5f9;vertical-align:middle;}
.posts-table tr:hover td{background:#f8fafc;}
.btn-sm{padding:.3rem .65rem;font-size:.8rem;border-radius:5px;border:none;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:.2rem;transition:background .14s;}
.btn-primary{background:#2563eb;color:#fff;}.btn-primary:hover{background:#1d4ed8;}
.btn-secondary{background:#f1f5f9;color:#374151;border:1px solid #e2e8f0;}.btn-secondary:hover{background:#e2e8f0;}
.btn-danger{background:#fee2e2;color:#b91c1c;}.btn-danger:hover{background:#fecaca;}
.btn-success{background:#dcfce7;color:#15803d;}.btn-success:hover{background:#bbf7d0;}
.btn-lg{padding:.55rem 1.2rem;font-size:.9375rem;}
.bulk-bar{display:flex;align-items:center;gap:.5rem;margin-bottom:.75rem;}
.bulk-bar select{padding:.32rem .6rem;border:1px solid #cbd5e1;border-radius:6px;font-size:.8125rem;}
.notice{padding:.65rem .9rem;border-radius:7px;margin-bottom:.9rem;font-size:.875rem;}
.notice-success{background:#dcfce7;color:#15803d;border:1px solid #86efac;}
.notice-error{background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5;}
.status-badge{display:inline-flex;align-items:center;gap:.2rem;padding:.2rem .5rem;border-radius:99px;font-size:.74rem;font-weight:600;}
.role-admin{background:#fef3c7;color:#92400e;}
.role-member{background:#dbeafe;color:#1e40af;}
.role-editor{background:#ede9fe;color:#6d28d9;}
.status-active{background:#dcfce7;color:#15803d;}
.status-inactive{background:#f1f5f9;color:#475569;}
.status-banned{background:#fee2e2;color:#b91c1c;}
.user-avatar{width:36px;height:36px;border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:700;font-size:.9rem;flex-shrink:0;}
.stat-cards{display:grid;grid-template-columns:repeat(auto-fit,minmax(160px,1fr));gap:1rem;margin-bottom:1.5rem;}
.stat-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:1rem 1.2rem;}
.stat-card-num{font-size:1.75rem;font-weight:800;line-height:1;}
.stat-card-lbl{font-size:.8rem;color:#64748b;margin-top:.2rem;}
.post-card{background:#fff;border:1px solid #e2e8f0;border-radius:10px;padding:1.2rem;}
.post-card h3{font-size:.8375rem;font-weight:700;color:#374151;margin:0 0 .9rem;padding-bottom:.45rem;border-bottom:1px solid #f1f5f9;}
.post-edit-layout{display:grid;grid-template-columns:1fr 260px;gap:1.5rem;align-items:start;}
@media(max-width:880px){.post-edit-layout{grid-template-columns:1fr;}}
.post-edit-main,.post-edit-side{display:flex;flex-direction:column;gap:1.2rem;}
.field-group{margin-bottom:.9rem;}
.field-group label{display:block;font-size:.8rem;font-weight:600;color:#374151;margin-bottom:.3rem;}
.field-group input,.field-group select{width:100%;padding:.4rem .6rem;border:1px solid #cbd5e1;border-radius:6px;font-size:.875rem;box-sizing:border-box;background:#fff;color:#1e293b;}
.field-hint{font-size:.74rem;color:#94a3b8;margin-top:.2rem;}
.posts-pagination{display:flex;align-items:center;gap:.3rem;margin-top:1.2rem;flex-wrap:wrap;}
.posts-pagination a,.posts-pagination span{padding:.3rem .65rem;border-radius:5px;font-size:.8rem;text-decoration:none;border:1px solid #e2e8f0;color:#374151;}
.posts-pagination a:hover{background:#f1f5f9;}
.posts-pagination .cp{background:#2563eb;color:#fff;border-color:#2563eb;}
</style>

<?php foreach ($messages as $m):
    $cls = $m['type'] === 'success' ? 'notice-success' : 'notice-error';
?>
<div class="notice <?php echo $cls; ?>"><?php echo htmlspecialchars($m['text'], ENT_QUOTES, 'UTF-8'); ?></div>
<?php endforeach; ?>

<?php
/* ================================================================
   EDIT-ANSICHT
   ================================================================ */
if ($view === 'edit' && $editUserId > 0):
    $eu = $db->get_row("SELECT * FROM {$prefix}users WHERE id=?", [$editUserId]);
    if (!$eu):
        header('Location: ' . SITE_URL . '/admin/users');
        exit;
    endif;

    $euGroups  = $db->get_results(
        "SELECT g.id, g.name FROM {$prefix}user_group_members m
         JOIN {$prefix}user_groups g ON g.id=m.group_id
         WHERE m.user_id=?", [(int)$eu->id]
    );
    $allGroups = $db->get_results("SELECT id, name FROM {$prefix}user_groups WHERE is_active=1 ORDER BY name");
?>
<div class="posts-header">
    <h2 style="margin:0;">✏️ Benutzer bearbeiten</h2>
    <a href="<?php echo SITE_URL; ?>/admin/users" class="btn-sm btn-secondary">← Alle Benutzer</a>
</div>

<form method="post" action="<?php echo SITE_URL; ?>/admin/users?view=edit&id=<?php echo (int)$eu->id; ?>" id="editUserForm">
    <input type="hidden" name="_csrf"   value="<?php echo $csrfEdit; ?>">
    <input type="hidden" name="_action" value="edit_user">
    <input type="hidden" name="user_id" value="<?php echo (int)$eu->id; ?>">

    <div class="post-edit-layout">
        <div class="post-edit-main">
            <div class="post-card">
                <h3>👤 Benutzerdaten</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                    <div class="field-group">
                        <label>Benutzername</label>
                        <input type="text" value="<?php echo htmlspecialchars($eu->username, ENT_QUOTES); ?>" disabled style="background:#f8fafc;color:#94a3b8;">
                        <div class="field-hint">Benutzername kann nicht geändert werden.</div>
                    </div>
                    <div class="field-group">
                        <label>E-Mail *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($eu->email, ENT_QUOTES); ?>" required>
                    </div>
                    <div class="field-group" style="grid-column:1/-1;">
                        <label>Anzeigename</label>
                        <input type="text" name="display_name" value="<?php echo htmlspecialchars($eu->display_name ?? '', ENT_QUOTES); ?>" placeholder="Anzeigename">
                    </div>
                </div>
            </div>

            <div class="post-card">
                <h3>🔑 Passwort ändern</h3>
                <div class="field-group">
                    <label>Neues Passwort <span style="font-weight:400;color:#94a3b8;">(leer = unverändert)</span></label>
                    <input type="password" name="new_password" placeholder="min. 6 Zeichen" minlength="6" autocomplete="new-password">
                </div>
                <div class="field-group" style="margin-bottom:0;">
                    <label>Passwort bestätigen</label>
                    <input type="password" id="pwConfirm" placeholder="Passwort wiederholen" autocomplete="new-password">
                    <div class="field-hint" id="pwHint"></div>
                </div>
            </div>

            <div class="post-card">
                <h3>👥 Gruppen</h3>
                <?php if (empty($allGroups)): ?>
                <p style="color:#94a3b8;font-size:.875rem;">Noch keine Gruppen vorhanden. <a href="<?php echo SITE_URL; ?>/admin/groups">Gruppen verwalten →</a></p>
                <?php else: ?>
                <div style="display:flex;flex-wrap:wrap;gap:.5rem;margin-bottom:.75rem;">
                    <?php foreach ($allGroups as $grp):
                        $isMember = array_filter($euGroups, fn($g) => (int)$g->id === (int)$grp->id);
                    ?>
                    <label style="display:flex;align-items:center;gap:.35rem;font-size:.875rem;cursor:pointer;padding:.3rem .6rem;border:1px solid #e2e8f0;border-radius:6px;background:<?php echo $isMember ? '#dbeafe' : '#f8fafc'; ?>;">
                        <input type="checkbox" name="groups[]" value="<?php echo (int)$grp->id; ?>"
                               <?php echo $isMember ? 'checked' : ''; ?>>
                        <?php echo htmlspecialchars($grp->name, ENT_QUOTES); ?>
                    </label>
                    <?php endforeach; ?>
                </div>
                <div class="field-hint">Gruppen-Mitgliedschaften werden nach dem Speichern aktualisiert.</div>
                <?php endif; ?>
            </div>
        </div>

        <div class="post-edit-side">
            <div class="post-card">
                <h3>⚙️ Status & Rolle</h3>
                <div class="field-group">
                    <label>Rolle</label>
                    <select name="role" <?php echo (int)$eu->id === $current_user_id ? 'disabled' : ''; ?>>
                        <option value="member" <?php echo ($eu->role ?? '') === 'member' ? 'selected' : ''; ?>>👤 Member</option>
                        <option value="editor" <?php echo ($eu->role ?? '') === 'editor' ? 'selected' : ''; ?>>✏️ Editor</option>
                        <option value="admin"  <?php echo ($eu->role ?? '') === 'admin'  ? 'selected' : ''; ?>>🔑 Administrator</option>
                    </select>
                    <?php if ((int)$eu->id === $current_user_id): ?>
                    <div class="field-hint">Eigene Rolle kann nicht geändert werden.</div>
                    <?php endif; ?>
                </div>
                <div class="field-group">
                    <label>Status</label>
                    <select name="status" <?php echo (int)$eu->id === $current_user_id ? 'disabled' : ''; ?>>
                        <option value="active"   <?php echo ($eu->status ?? 'active') === 'active'   ? 'selected' : ''; ?>>✅ Aktiv</option>
                        <option value="inactive" <?php echo ($eu->status ?? '') === 'inactive' ? 'selected' : ''; ?>>⏸️ Inaktiv</option>
                        <option value="banned"   <?php echo ($eu->status ?? '') === 'banned'   ? 'selected' : ''; ?>>🚫 Gesperrt</option>
                    </select>
                </div>
                <div style="font-size:.76rem;color:#94a3b8;border-top:1px solid #f1f5f9;padding-top:.6rem;display:flex;flex-direction:column;gap:.15rem;margin-top:.5rem;">
                    <span>ID: #<?php echo (int)$eu->id; ?></span>
                    <span>Erstellt: <?php echo date('d.m.Y H:i', strtotime($eu->created_at)); ?></span>
                    <?php if (!empty($eu->last_login)): ?>
                    <span>Letzter Login: <?php echo date('d.m.Y H:i', strtotime($eu->last_login)); ?></span>
                    <?php endif; ?>
                </div>
                <div style="display:flex;flex-direction:column;gap:.45rem;margin-top:.9rem;">
                    <button type="submit" class="btn-sm btn-primary btn-lg" style="width:100%;">💾 Speichern</button>
                    <?php if ((int)$eu->id !== $current_user_id): ?>
                    <button type="submit" form="deleteUserForm" class="btn-sm btn-danger"
                            onclick="return confirm('Benutzer wirklich löschen?')" style="width:100%;">🗑️ Löschen</button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</form>

<?php if ((int)$eu->id !== $current_user_id): ?>
<form id="deleteUserForm" method="post" action="<?php echo SITE_URL; ?>/admin/users" style="display:none;">
    <input type="hidden" name="_csrf"   value="<?php echo $csrfDelete; ?>">
    <input type="hidden" name="_action" value="delete_user">
    <input type="hidden" name="user_id" value="<?php echo (int)$eu->id; ?>">
</form>
<?php endif; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const pwField   = document.querySelector('input[name="new_password"]');
    const pwConfirm = document.getElementById('pwConfirm');
    const pwHint    = document.getElementById('pwHint');
    function checkPw() {
        if (!pwField.value) { pwHint.textContent = ''; return; }
        if (pwField.value !== pwConfirm.value) {
            pwHint.textContent = '⚠️ Passwörter stimmen nicht überein.';
            pwHint.style.color = '#b91c1c';
        } else {
            pwHint.textContent = '✅ Passwörter stimmen überein.';
            pwHint.style.color = '#15803d';
        }
    }
    if (pwField)   pwField.addEventListener('input', checkPw);
    if (pwConfirm) pwConfirm.addEventListener('input', checkPw);
});
</script>

<?php
/* ================================================================
   NEUER BENUTZER
   ================================================================ */
elseif ($view === 'new'):
?>
<div class="posts-header">
    <h2 style="margin:0;">➕ Neuer Benutzer</h2>
    <a href="<?php echo SITE_URL; ?>/admin/users" class="btn-sm btn-secondary">← Alle Benutzer</a>
</div>

<form method="post" action="<?php echo SITE_URL; ?>/admin/users">
    <input type="hidden" name="_csrf"   value="<?php echo $csrfCreate; ?>">
    <input type="hidden" name="_action" value="create_user">

    <div class="post-edit-layout">
        <div class="post-edit-main">
            <div class="post-card">
                <h3>👤 Benutzerdaten</h3>
                <div style="display:grid;grid-template-columns:1fr 1fr;gap:.75rem;">
                    <div class="field-group">
                        <label>Benutzername *</label>
                        <input type="text" name="username" required placeholder="z.B. max123" autocomplete="off">
                    </div>
                    <div class="field-group">
                        <label>E-Mail *</label>
                        <input type="email" name="email" required placeholder="user@example.com">
                    </div>
                    <div class="field-group">
                        <label>Passwort * <span style="font-weight:400;color:#94a3b8;">(min. 6 Zeichen)</span></label>
                        <input type="password" name="password" required minlength="6" placeholder="••••••" autocomplete="new-password">
                    </div>
                    <div class="field-group">
                        <label>Anzeigename</label>
                        <input type="text" name="display_name" placeholder="Max Mustermann">
                    </div>
                </div>
            </div>
        </div>
        <div class="post-edit-side">
            <div class="post-card">
                <h3>⚙️ Rolle</h3>
                <div class="field-group">
                    <label>Rolle</label>
                    <select name="role">
                        <option value="member">👤 Member</option>
                        <option value="editor">✏️ Editor</option>
                        <option value="admin">🔑 Administrator</option>
                    </select>
                </div>
                <div style="margin-top:.9rem;">
                    <button type="submit" class="btn-sm btn-primary btn-lg" style="width:100%;">✅ Erstellen</button>
                </div>
            </div>
        </div>
    </div>
</form>

<?php
/* ================================================================
   LISTEN-ANSICHT
   ================================================================ */
else:
    $whereParts = [];
    $params     = [];
    if ($roleFilter === 'banned') {
        $whereParts[] = "u.status = 'banned'";
    } elseif ($roleFilter !== 'all') {
        $whereParts[] = "u.role = ?";
        $params[]     = $roleFilter;
        $whereParts[] = "u.status != 'banned'";
    } else {
        $whereParts[] = "u.status != 'banned'";
    }
    if (!empty($search)) {
        $whereParts[] = "(u.username LIKE ? OR u.email LIKE ? OR u.display_name LIKE ?)";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
        $params[] = "%{$search}%";
    }
    $where      = $whereParts ? ' WHERE ' . implode(' AND ', $whereParts) : '';
    $total      = (int)$db->get_var("SELECT COUNT(*) FROM {$prefix}users u" . $where, $params);
    $totalPages = max(1, (int)ceil($total / $perPage));
    $page       = min($page, $totalPages);
    $offset     = ($page - 1) * $perPage;

    $users = $db->get_results(
        "SELECT u.*, (SELECT COUNT(*) FROM {$prefix}user_group_members m WHERE m.user_id=u.id) AS group_count
         FROM {$prefix}users u" . $where . " ORDER BY u.created_at DESC LIMIT {$perPage} OFFSET {$offset}",
        $params
    );

    $buildUrl = fn(array $extra = []) =>
        SITE_URL . '/admin/users?' . http_build_query(array_merge(
            ['role' => $roleFilter],
            $search ? ['search' => $search] : [],
            $extra
        ));
?>

<!-- Stat-Karten -->
<div class="stat-cards">
    <div class="stat-card"><div class="stat-card-num" style="color:#2563eb;"><?php echo $statTotal; ?></div><div class="stat-card-lbl">Gesamt</div></div>
    <div class="stat-card"><div class="stat-card-num" style="color:#f59e0b;"><?php echo $statAdmin; ?></div><div class="stat-card-lbl">Administratoren</div></div>
    <div class="stat-card"><div class="stat-card-num" style="color:#22c55e;"><?php echo $statActive; ?></div><div class="stat-card-lbl">Aktiv</div></div>
    <div class="stat-card"><div class="stat-card-num" style="color:#8b5cf6;"><?php echo $statNew7; ?></div><div class="stat-card-lbl">Neue (7 Tage)</div></div>
</div>

<div class="posts-header">
    <h2 style="margin:0;">👥 Benutzer</h2>
    <a href="<?php echo SITE_URL; ?>/admin/users?view=new" class="btn-sm btn-primary">➕ Neuer Benutzer</a>
</div>

<div class="posts-tabs">
    <?php foreach (['all' => 'Alle', 'admin' => 'Administratoren', 'member' => 'Members', 'editor' => 'Editoren', 'banned' => 'Gesperrt'] as $r => $lbl): ?>
    <a href="<?php echo SITE_URL; ?>/admin/users?role=<?php echo $r; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>"
       class="posts-tab <?php echo $roleFilter === $r ? 'active' : ''; ?>">
        <?php echo $lbl; ?><span class="badge"><?php echo $roleCounts[$r] ?? 0; ?></span>
    </a>
    <?php endforeach; ?>
</div>

<form method="get" action="<?php echo SITE_URL; ?>/admin/users">
    <input type="hidden" name="role" value="<?php echo htmlspecialchars($roleFilter); ?>">
    <div class="posts-toolbar">
        <div class="posts-search">
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Suche nach Name, E-Mail…">
            <button type="submit">🔍</button>
        </div>
        <?php if ($search): ?>
        <a href="<?php echo SITE_URL; ?>/admin/users?role=<?php echo $roleFilter; ?>" class="btn-sm btn-secondary">✕ Filter löschen</a>
        <?php endif; ?>
    </div>
</form>

<form method="post" action="<?php echo SITE_URL; ?>/admin/users" id="bulkForm">
    <input type="hidden" name="_csrf"   value="<?php echo $csrfBulk; ?>">
    <input type="hidden" name="_action" value="bulk">
    <div class="bulk-bar">
        <select name="bulk_action">
            <option value="">Aktion wählen…</option>
            <option value="activate">Aktivieren</option>
            <option value="ban">Sperren</option>
            <option value="make_member">Rolle → Member</option>
            <option value="make_admin">Rolle → Admin</option>
            <option value="delete">Endgültig löschen</option>
        </select>
        <button type="submit" class="btn-sm btn-secondary"
                onclick="return confirm('Aktion auf ausgewählte Benutzer anwenden?')">Anwenden</button>
        <span style="color:#94a3b8;font-size:.8rem;"><?php echo $total; ?> Benutzer</span>
    </div>

    <?php if (empty($users)): ?>
    <div class="post-card" style="text-align:center;padding:3rem;color:#94a3b8;">
        <div style="font-size:3rem;margin-bottom:1rem;">👤</div>
        <p><?php echo $search
            ? 'Keine Treffer für <strong>' . htmlspecialchars($search, ENT_QUOTES) . '</strong>'
            : 'Keine Benutzer gefunden.'; ?></p>
    </div>
    <?php else: ?>
    <div style="background:#fff;border:1px solid #e2e8f0;border-radius:10px;overflow:auto;">
        <table class="posts-table">
            <thead><tr>
                <th style="width:30px;">
                    <input type="checkbox" onchange="document.querySelectorAll('#bulkForm input[name=\'bulk_ids[]\']').forEach(c=>c.checked=this.checked)">
                </th>
                <th>Benutzer</th>
                <th style="width:200px;">E-Mail</th>
                <th style="width:100px;">Rolle</th>
                <th style="width:90px;">Status</th>
                <th style="width:80px;text-align:center;">Gruppen</th>
                <th style="width:130px;">Registriert</th>
                <th style="width:110px;text-align:right;"></th>
            </tr></thead>
            <tbody>
            <?php foreach ($users as $u):
                $colors      = ['#2563eb','#7c3aed','#db2777','#059669','#d97706','#dc2626'];
                $avatarColor = $colors[abs(crc32($u->username)) % count($colors)];
                $roleLbl     = ['admin' => '🔑 Admin', 'editor' => '✏️ Editor', 'member' => '👤 Member'][$u->role] ?? $u->role;
                $statusLbl   = ['active' => 'Aktiv', 'inactive' => 'Inaktiv', 'banned' => 'Gesperrt'][$u->status ?? 'active'] ?? ($u->status ?? 'active');
            ?>
            <tr>
                <td><input type="checkbox" name="bulk_ids[]" value="<?php echo (int)$u->id; ?>"
                           <?php echo (int)$u->id === $current_user_id ? 'disabled' : ''; ?>></td>
                <td>
                    <div style="display:flex;align-items:center;gap:.6rem;">
                        <div class="user-avatar" style="background:<?php echo $avatarColor; ?>">
                            <?php echo strtoupper(substr($u->username, 0, 1)); ?>
                        </div>
                        <div>
                            <a href="<?php echo SITE_URL; ?>/admin/users?view=edit&id=<?php echo (int)$u->id; ?>"
                               style="font-weight:600;color:#1e293b;text-decoration:none;">
                                <?php echo htmlspecialchars($u->username, ENT_QUOTES); ?>
                                <?php if ((int)$u->id === $current_user_id): ?>
                                <span style="background:#dcfce7;color:#166534;padding:1px 5px;border-radius:4px;font-size:.68rem;margin-left:4px;">Sie</span>
                                <?php endif; ?>
                            </a>
                            <?php if (!empty($u->display_name) && $u->display_name !== $u->username): ?>
                            <div style="font-size:.74rem;color:#94a3b8;"><?php echo htmlspecialchars($u->display_name, ENT_QUOTES); ?></div>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td style="font-size:.82rem;color:#64748b;"><?php echo htmlspecialchars($u->email, ENT_QUOTES); ?></td>
                <td><span class="status-badge role-<?php echo htmlspecialchars($u->role ?? 'member'); ?>"><?php echo $roleLbl; ?></span></td>
                <td><span class="status-badge status-<?php echo htmlspecialchars($u->status ?? 'active'); ?>"><?php echo $statusLbl; ?></span></td>
                <td style="text-align:center;font-size:.8rem;color:#64748b;"><?php echo (int)($u->group_count ?? 0); ?></td>
                <td style="font-size:.78rem;color:#64748b;"><?php echo date('d.m.Y', strtotime($u->created_at)); ?></td>
                <td style="text-align:right;white-space:nowrap;">
                    <a href="<?php echo SITE_URL; ?>/admin/users?view=edit&id=<?php echo (int)$u->id; ?>"
                       class="btn-sm btn-secondary" title="Bearbeiten">✏️</a>
                    <?php if ((int)$u->id !== $current_user_id): ?>
                    <button type="button" class="btn-sm btn-danger" title="Löschen"
                            onclick="deleteUser(<?php echo (int)$u->id; ?>)">🗑️</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($totalPages > 1): ?>
    <div class="posts-pagination">
        <?php if ($page > 1): ?><a href="<?php echo $buildUrl(['p' => $page - 1]); ?>">‹</a><?php endif; ?>
        <?php for ($i = max(1, $page - 3); $i <= min($totalPages, $page + 3); $i++): ?>
            <?php if ($i === $page): ?><span class="cp"><?php echo $i; ?></span>
            <?php else: ?><a href="<?php echo $buildUrl(['p' => $i]); ?>"><?php echo $i; ?></a><?php endif; ?>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?><a href="<?php echo $buildUrl(['p' => $page + 1]); ?>">›</a><?php endif; ?>
        <span style="color:#94a3b8;font-size:.78rem;margin-left:.4rem;"><?php echo $page; ?>/<?php echo $totalPages; ?></span>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</form>

<form id="deleteUserForm" method="post" action="<?php echo SITE_URL; ?>/admin/users" style="display:none;">
    <input type="hidden" name="_csrf"   value="<?php echo $csrfDelete; ?>">
    <input type="hidden" name="_action" value="delete_user">
    <input type="hidden" name="user_id" id="deleteUserId" value="">
</form>

<?php endif; ?>

<script>
function deleteUser(id) {
    if (confirm('Benutzer wirklich löschen?')) {
        document.getElementById('deleteUserId').value = id;
        document.getElementById('deleteUserForm').submit();
    }
}
</script>

<?php renderAdminLayoutEnd(); ?>

<?php
declare(strict_types=1);

/**
 * RBAC – Rollen & Rechte (Role-Based Access Control)
 *
 * Verwaltet nur Rollen und Admin-Capabilities.
 * Plugin-Erweiterungen registrieren eigene Rechte eigenständig.
 * Gruppen werden in groups.php verwaltet.
 *
 * @package CMSv2\Admin
 */

if (!defined('SITE_URL')) {
    require_once __DIR__ . '/../config.php';
}

use CMS\Auth;
use CMS\Database;
use CMS\Security;

if (!Auth::instance()->isAdmin()) {
    header('Location: ' . SITE_URL . '/admin');
    exit;
}

$security = Security::instance();
$db       = Database::instance();
$prefix   = $db->prefix;

// ═══════════════════════════════════════════════════════════════════════════════
// AUTO-MIGRATION: Neue Spalten hinzufügen (idempotent, sicher)
// ═══════════════════════════════════════════════════════════════════════════════
$migrations = [
    "ALTER TABLE {$prefix}roles ADD COLUMN IF NOT EXISTS member_dashboard_access TINYINT(1) NOT NULL DEFAULT 1",
    "ALTER TABLE {$prefix}roles ADD COLUMN IF NOT EXISTS sort_order INT NOT NULL DEFAULT 0",
];
foreach ($migrations as $sql) {
    try { $db->execute($sql, []); } catch (\Throwable $e) { /* Spalte existiert bereits */ }
}

// ═══════════════════════════════════════════════════════════════════════════════
// 6 STANDARD-ROLLEN SICHERSTELLEN
// ═══════════════════════════════════════════════════════════════════════════════
$_seedRoles = [
    // name          display_name         description                                                     caps_json                                                                                            mda  sort
    ['admin',        'Administrator',     'Vollzugriff auf Admin-Bereich',                               '["manage_posts","manage_pages","manage_users","manage_plugins","manage_themes","manage_settings","view_analytics","manage_media"]', 1, 1],
    ['editor',       'Editor',            'Beitraege, Seiten und Medien verwalten, Member-Zugang',       '["manage_posts","manage_pages","manage_media"]',                                                    1, 2],
    ['member',       'Mitglied',          'Standard-Mitglieder-Zugang',                                  '[]',                                                                                                1, 3],
    ['moderator',    'Moderator',         'Beitraege moderieren und Analytics einsehen',                 '["manage_posts","manage_pages","view_analytics"]',                                                  1, 4],
    ['contributor',  'Beitragender',      'Eigene Beitraege erstellen, eingeschraenkter Member-Zugang',  '["manage_posts"]',                                                                                  1, 5],
    ['viewer',       'Beobachter',        'Nur-Lesen-Zugang, kein Admin-Bereich',                        '[]',                                                                                                0, 6],
];
foreach ($_seedRoles as [$rName, $rDisplay, $rDesc, $rCaps, $rMda, $rSort]) {
    $exists = $db->get_var("SELECT id FROM {$prefix}roles WHERE name=?", [$rName]);
    if (!$exists) {
        $db->execute(
            "INSERT INTO {$prefix}roles (name, display_name, description, capabilities, member_dashboard_access, sort_order) VALUES (?,?,?,?,?,?)",
            [$rName, $rDisplay, $rDesc, $rCaps, $rMda, $rSort]
        );
    }
}

// ═══════════════════════════════════════════════════════════════════════════════
// KONSTANTEN
// ═══════════════════════════════════════════════════════════════════════════════
$coreRoles  = ['admin', 'editor', 'member'];
$extraRoles = ['moderator', 'contributor', 'viewer'];
$allAdminCaps = [
    'manage_posts'    => '📝 Beiträge',
    'manage_pages'    => '📄 Seiten',
    'manage_users'    => '👥 Benutzer',
    'manage_plugins'  => '🔌 Plugins',
    'manage_themes'   => '🎨 Themes',
    'manage_settings' => '⚙️ Einstellungen',
    'view_analytics'  => '📊 Analytics',
    'manage_media'    => '🖼️ Medien',
];
$messages = [];


// ═══════════════════════════════════════════════════════════════════════════════
// POST-HANDLER
// ═══════════════════════════════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['_action'] ?? '';

    // ── ROLLEN ──────────────────────────────────────────────────────────────────
    if (in_array($action, ['create_role', 'edit_role'])) {
        if (!$security->verifyToken($_POST['_csrf'] ?? '', 'rbac_role')) {
            $messages[] = ['type' => 'error', 'text' => 'Sicherheitscheck fehlgeschlagen.'];
        } else {
            $rid          = (int)($_POST['role_id']        ?? 0);
            $name         = preg_replace('/[^a-z0-9_]/', '', strtolower(trim($_POST['name'] ?? '')));
            $displayName  = trim($_POST['display_name']    ?? '');
            $description  = trim($_POST['description']     ?? '');
            $capsRaw      = (array)($_POST['capabilities'] ?? []);
            $caps         = array_values(array_intersect($capsRaw, array_keys($allAdminCaps)));
            $mda = isset($_POST['member_dashboard_access']) ? 1 : 0;

            if ($action === 'create_role') {
                if (empty($name) || empty($displayName)) {
                    $messages[] = ['type' => 'error', 'text' => 'Name und Anzeigename sind Pflichtfelder.'];
                } elseif (in_array($name, $coreRoles)) {
                    $messages[] = ['type' => 'error', 'text' => 'Dieser Name ist für Core-Rollen reserviert.'];
                } elseif ($db->get_var("SELECT id FROM {$prefix}roles WHERE name=?", [$name])) {
                    $messages[] = ['type' => 'error', 'text' => 'Rollenname "' . htmlspecialchars($name, ENT_QUOTES) . '" bereits vergeben.'];
                } else {
                    $maxSort = (int)$db->get_var("SELECT COALESCE(MAX(sort_order),0)+1 FROM {$prefix}roles");
                    $db->execute(
                        "INSERT INTO {$prefix}roles (name, display_name, description, capabilities, member_dashboard_access, sort_order) VALUES (?,?,?,?,?,?)",
                        [$name, $displayName, $description, json_encode($caps), $mda, $maxSort]
                    );
                    header('Location: ' . SITE_URL . '/admin/rbac?msg=role_created');
                    exit;
                }
            } else { // edit_role
                if ($rid < 1 || empty($displayName)) {
                    $messages[] = ['type' => 'error', 'text' => 'Ungültige Eingaben.'];
                } else {
                    $db->execute(
                        "UPDATE {$prefix}roles SET display_name=?, description=?, capabilities=?, member_dashboard_access=? WHERE id=?",
                        [$displayName, $description, json_encode($caps), $mda, $rid]
                    );
                    header('Location: ' . SITE_URL . '/admin/rbac?msg=role_updated');
                    exit;
                }
            }
        }
    }

    if ($action === 'delete_role') {
        if (!$security->verifyToken($_POST['_csrf'] ?? '', 'rbac_role')) {
            $messages[] = ['type' => 'error', 'text' => 'Sicherheitscheck fehlgeschlagen.'];
        } else {
            $rid      = (int)($_POST['role_id'] ?? 0);
            $roleName = $rid > 0 ? $db->get_var("SELECT name FROM {$prefix}roles WHERE id=?", [$rid]) : '';
            if ($rid > 0 && !in_array($roleName, $coreRoles)) {
                $db->execute("DELETE FROM {$prefix}roles WHERE id=?", [$rid]);
                header('Location: ' . SITE_URL . '/admin/rbac?msg=role_deleted');
                exit;
            } else {
                $messages[] = ['type' => 'error', 'text' => 'Core-Rollen können nicht gelöscht werden.'];
            }
        }
    }

}

// ─── URL-Meldungen ────────────────────────────────────────────────────────────
if (isset($_GET['msg'])) {
    $msgMap = [
        'role_created' => ['success', '✅ Rolle erstellt.'],
        'role_updated' => ['success', '✅ Rolle gespeichert.'],
        'role_deleted' => ['success', '🗑️ Rolle gelöscht.'],
    ];
    if (isset($msgMap[$_GET['msg']])) {
        $messages[] = ['type' => $msgMap[$_GET['msg']][0], 'text' => $msgMap[$_GET['msg']][1]];
    }
}

// ─── Daten laden ──────────────────────────────────────────────────────────────
$roles  = $db->get_results("SELECT * FROM {$prefix}roles ORDER BY sort_order, name") ?: [];
$csrfRole   = $security->generateToken('rbac_role');
$roleEditId = (int)($_GET['edit_role'] ?? 0);

require_once __DIR__ . '/partials/admin-menu.php';
renderAdminLayoutStart('RBAC – Rollen & Rechte', 'rbac');
?>

<?php foreach ($messages as $msg): ?>
<div class="alert alert-<?php echo $msg['type'] === 'success' ? 'success' : 'error'; ?>">
    <?php echo htmlspecialchars($msg['text'], ENT_QUOTES, 'UTF-8'); ?>
</div>
<?php endforeach; ?>

<?php /* =========================================================
   ROLLEN
   ========================================================= */ ?>

<div class="admin-page-header">
    <div>
        <h2>🔑 Rollen & Rechte</h2>
        <p>Definiere Admin-Capabilities und Member-Dashboard-Zugang pro Rolle. Plugins registrieren eigene Rechte eigenständig.</p>
    </div>
</div>

<!-- Rollen-Übersicht -->
<div class="admin-card" style="margin-bottom:1.5rem;">
    <h3>📋 Alle Rollen</h3>
    <div class="users-table-container">
        <table class="users-table">
            <thead>
                <tr>
                    <th>Rolle</th>
                    <th style="width:130px;">Typ</th>
                    <th>Admin-Capabilities</th>
                    <th style="width:90px;text-align:center;">Member-DB</th>
                    <th style="width:100px;text-align:right;"></th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($roles as $role):
                $caps        = json_decode($role->capabilities ?? '[]', true) ?: [];
                $isCore      = in_array($role->name, $coreRoles);
                $isExtra     = in_array($role->name, $extraRoles);
                $isEditing   = ($roleEditId === (int)$role->id);
            ?>
            <tr <?php echo $isEditing ? 'style="background:#eff6ff;"' : ''; ?>>
                <td>
                    <span style="font-weight:700;"><?php echo htmlspecialchars($role->display_name, ENT_QUOTES); ?></span>
                    <div style="font-size:.74rem;color:#94a3b8;font-family:monospace;"><?php echo htmlspecialchars($role->name, ENT_QUOTES); ?></div>
                    <?php if (!empty($role->description)): ?>
                    <div style="font-size:.75rem;color:#64748b;margin-top:.2rem;"><?php echo htmlspecialchars(mb_substr($role->description, 0, 80), ENT_QUOTES); ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($isCore): ?>
                    <span style="background:#fef3c7;color:#92400e;padding:2px 8px;border-radius:20px;font-size:.72rem;font-weight:600;">⭐ Core</span>
                    <?php elseif ($isExtra): ?>
                    <span style="background:#dbeafe;color:#1e40af;padding:2px 8px;border-radius:20px;font-size:.72rem;font-weight:600;">✦ Erweitert</span>
                    <?php else: ?>
                    <span style="background:#f1f5f9;color:#64748b;padding:2px 8px;border-radius:20px;font-size:.72rem;font-weight:600;">Benutzerdefiniert</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (empty($caps)): ?>
                    <span style="color:#cbd5e1;font-size:.78rem;">Keine</span>
                    <?php else: ?>
                    <div style="display:flex;flex-wrap:wrap;gap:.2rem;">
                        <?php foreach ($caps as $cap): ?>
                        <span class="rbac-cap-pill"><?php echo htmlspecialchars($allAdminCaps[$cap] ?? $cap, ENT_QUOTES); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </td>
                <td style="text-align:center;">
                    <?php if ($role->member_dashboard_access ?? 1): ?>
                    <span class="status-badge active" style="font-size:.7rem;">✅ Ja</span>
                    <?php else: ?>
                    <span class="status-badge inactive" style="font-size:.7rem;">✕ Nein</span>
                    <?php endif; ?>
                </td>

                <td style="text-align:right;white-space:nowrap;">
                    <a href="<?php echo SITE_URL; ?>/admin/rbac?tab=roles&edit_role=<?php echo (int)$role->id; ?>#role-form"
                       class="btn btn-secondary btn-sm" title="Bearbeiten">✏️</a>
                    <?php if (!$isCore): ?>
                    <button type="button" class="btn btn-danger btn-sm" title="Löschen"
                            onclick="rbacDeleteRole(<?php echo (int)$role->id; ?>, <?php echo json_encode($role->display_name); ?>)">🗑️</button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Rolle erstellen / bearbeiten -->
<?php
$editRole    = $roleEditId > 0 ? $db->get_row("SELECT * FROM {$prefix}roles WHERE id=?", [$roleEditId]) : null;
$editCaps    = $editRole ? (json_decode($editRole->capabilities  ?? '[]',   true) ?: []) : [];
$editMda     = $editRole ? (int)($editRole->member_dashboard_access ?? 1) : 1;
$isEditCore  = $editRole && in_array($editRole->name, $coreRoles);
?>
<div class="admin-card" id="role-form">
    <h3><?php echo $editRole ? '✏️ Rolle bearbeiten: <em>' . htmlspecialchars($editRole->display_name, ENT_QUOTES) . '</em>' : '➕ Neue Rolle erstellen'; ?></h3>

    <?php if ($isEditCore): ?>
    <div class="alert" style="background:#fefce8;color:#854d0e;border-left:4px solid #fde047;margin-bottom:1rem;padding:.75rem 1rem;">
        ⚠️ Core-Rolle – der interne Name kann nicht geändert werden. Capabilities und Member-Einstellungen können angepasst werden.
    </div>
    <?php endif; ?>

    <form method="post" action="<?php echo SITE_URL; ?>/admin/rbac?tab=roles<?php echo $editRole ? '&edit_role=' . (int)$editRole->id : ''; ?>#role-form">
        <input type="hidden" name="_csrf"   value="<?php echo $csrfRole; ?>">
        <input type="hidden" name="_action" value="<?php echo $editRole ? 'edit_role' : 'create_role'; ?>">
        <?php if ($editRole): ?>
        <input type="hidden" name="role_id" value="<?php echo (int)$editRole->id; ?>">
        <?php endif; ?>

        <div style="display:grid;grid-template-columns:1fr 1.5fr 1fr;gap:1.5rem;align-items:start;">

            <!-- Spalte 1: Basis -->
            <div>
                <div class="admin-card" style="margin:0;">
                    <h3>📋 Basisdaten</h3>
                    <?php if (!$editRole): ?>
                    <div class="form-group">
                        <label class="form-label">Interner Name <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="name" class="form-control" required placeholder="z.B. supervisor" pattern="[a-z0-9_]+"
                               title="Nur a-z, 0-9 und Unterstrich">
                        <small class="form-text">Nur a–z, 0–9, _ · unveränderlich nach Erstellung</small>
                    </div>
                    <?php else: ?>
                    <div class="form-group">
                        <label class="form-label">Interner Name</label>
                        <input type="text" class="form-control" value="<?php echo htmlspecialchars($editRole->name, ENT_QUOTES); ?>" disabled style="background:#f8fafc;color:#94a3b8;">
                    </div>
                    <?php endif; ?>
                    <div class="form-group">
                        <label class="form-label">Anzeigename <span style="color:#ef4444;">*</span></label>
                        <input type="text" name="display_name" class="form-control" required
                               value="<?php echo htmlspecialchars($editRole->display_name ?? '', ENT_QUOTES); ?>" placeholder="z.B. Supervisor">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Beschreibung</label>
                        <textarea name="description" class="form-control" rows="3" placeholder="Optionale Beschreibung…"><?php echo htmlspecialchars($editRole->description ?? '', ENT_QUOTES); ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Spalte 2: Admin-Capabilities -->
            <div>
                <div class="admin-card" style="margin:0;">
                    <h3>🛡️ Admin-Capabilities</h3>
                    <p style="font-size:.8rem;color:#64748b;margin-bottom:.75rem;">Bestimmt welche Admin-Bereiche zugänglich sind. Keiner = reine Member/Frontend-Rolle.</p>
                    <div class="rbac-caps-grid">
                        <?php foreach ($allAdminCaps as $capKey => $capLabel): ?>
                        <label class="rbac-cap-check">
                            <input type="checkbox" name="capabilities[]" value="<?php echo $capKey; ?>"
                                   <?php echo in_array($capKey, $editCaps) ? 'checked' : ''; ?>>
                            <?php echo htmlspecialchars($capLabel, ENT_QUOTES); ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Spalte 3: Member-Einstellungen -->
            <div style="display:flex;flex-direction:column;gap:1rem;">
                <div class="admin-card" style="margin:0;">
                    <h3>🖥️ Member-Dashboard</h3>
                    <label class="dw-toggle-row" style="cursor:pointer;">
                        <div>
                            <div class="dw-toggle-label">Member-Dashboard nutzen</div>
                            <div class="dw-toggle-hint">Rolle darf den Members-Bereich verwenden</div>
                        </div>
                        <label class="dw-toggle">
                            <input type="checkbox" name="member_dashboard_access" <?php echo $editMda ? 'checked' : ''; ?>>
                            <span class="dw-toggle-slider"></span>
                        </label>
                    </label>
                </div>



                <div class="admin-card" style="margin:0;">
                    <div style="display:flex;flex-direction:column;gap:.45rem;">
                        <button type="submit" class="btn btn-primary" style="width:100%;">
                            <?php echo $editRole ? '💾 Änderungen speichern' : '✅ Rolle erstellen'; ?>
                        </button>
                        <?php if ($editRole): ?>
                        <a href="<?php echo SITE_URL; ?>/admin/rbac?tab=roles#role-form" class="btn btn-secondary" style="width:100%;text-align:center;">✕ Abbrechen</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>

<!-- Rolle löschen Modal -->
<div id="rbacRoleDeleteModal" class="modal" style="display:none;">
    <div class="modal-content" style="max-width:460px;">
        <div class="modal-header"><h3>Rolle löschen</h3><button class="modal-close" onclick="closeModal('rbacRoleDeleteModal')">&times;</button></div>
        <div class="modal-body">
            <p>Soll die Rolle <strong id="rbacRoleDeleteName"></strong> endgültig gelöscht werden?</p>
            <p style="color:#ef4444;font-size:.875rem;">⚠️ Diese Aktion kann nicht rückgängig gemacht werden.</p>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary" onclick="closeModal('rbacRoleDeleteModal')">Abbrechen</button>
            <button class="btn btn-danger" onclick="document.getElementById('rbacDeleteRoleForm').submit()">🗑️ Löschen</button>
        </div>
    </div>
</div>
<form id="rbacDeleteRoleForm" method="post" action="<?php echo SITE_URL; ?>/admin/rbac?tab=roles" style="display:none;">
    <input type="hidden" name="_csrf"    value="<?php echo $csrfRole; ?>">
    <input type="hidden" name="_action"  value="delete_role">
    <input type="hidden" name="role_id"  id="rbacDeleteRoleId" value="">
</form>
<?php /* =========================================================
   CSS
   ========================================================= */ ?>
<style>
/* ── Caps & Plugin Pills ── */
.rbac-cap-pill {
    background: #eff6ff;
    color: #1d4ed8;
    border: 1px solid #bfdbfe;
    padding: 1px 7px;
    border-radius: 20px;
    font-size: .72rem;
    font-weight: 600;
    display: inline-block;
}

/* ── Caps Grid ── */
.rbac-caps-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: .3rem .5rem;
}
.rbac-cap-check {
    display: flex;
    align-items: center;
    gap: .35rem;
    font-size: .82rem;
    padding: .35rem .5rem;
    border-radius: 6px;
    cursor: pointer;
    border: 1px solid transparent;
    transition: background .12s;
}
.rbac-cap-check:hover { background: #f1f5f9; }
.rbac-cap-check input { accent-color: #3b82f6; }




</style>

<?php /* =========================================================
   JAVASCRIPT
   ========================================================= */ ?>
<script>
// ── Modal-Hilfsfunktionen ────────────────────────────────────────────────────
function openModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'flex';
}
function closeModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
}
window.addEventListener('click', e => {
    document.querySelectorAll('.modal').forEach(m => {
        if (e.target === m) m.style.display = 'none';
    });
});

// ── Rollen: Lösch-Bestätigung ────────────────────────────────────────────────
function rbacDeleteRole(id, name) {
    document.getElementById('rbacDeleteRoleId').value = id;
    document.getElementById('rbacRoleDeleteName').textContent = name;
    openModal('rbacRoleDeleteModal');
}

</script>

<?php renderAdminLayoutEnd(); ?>

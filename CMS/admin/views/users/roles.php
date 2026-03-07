<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * View: Rollen & Rechte – Berechtigungsmatrix
 *
 * @var array $data  Rollen, Capabilities, Permissions, RoleCounts
 * @var string $csrfToken
 */

$roles        = $data['roles'] ?? [];
$capabilities = $data['capabilities'] ?? [];
$permissions  = $data['permissions'] ?? [];
$roleCounts   = $data['roleCounts'] ?? [];

$roleLabels = [
    'admin'  => 'Administrator',
    'editor' => 'Editor',
    'author' => 'Autor',
    'member' => 'Mitglied',
];

$groupLabels = [
    'pages'    => 'Seiten',
    'posts'    => 'Beiträge',
    'media'    => 'Medien',
    'users'    => 'Benutzer',
    'themes'   => 'Themes',
    'plugins'  => 'Plugins',
    'settings' => 'Einstellungen',
    'comments' => 'Kommentare',
];

$capLabels = [
    'view'      => 'Anzeigen',
    'create'    => 'Erstellen',
    'edit'      => 'Bearbeiten',
    'delete'    => 'Löschen',
    'publish'   => 'Veröffentlichen',
    'upload'    => 'Hochladen',
    'moderate'  => 'Moderieren',
    'activate'  => 'Aktivieren',
    'customize' => 'Anpassen',
    'install'   => 'Installieren',
    'settings'  => 'Einstellungen',
    'system'    => 'System',
    'roles'     => 'Rollen',
];
?>

<div class="container-xl">
    <!-- Header -->
    <div class="page-header d-flex align-items-center mb-4">
        <div>
            <h2 class="page-title">Rollen & Rechte</h2>
            <div class="text-muted mt-1">Berechtigungsmatrix für alle Benutzerrollen</div>
        </div>
    </div>

    <!-- Role Overview Cards -->
    <div class="row row-deck row-cards mb-4">
        <?php foreach ($roles as $role): ?>
            <div class="col-sm-6 col-lg-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center">
                            <div class="subheader"><?php echo htmlspecialchars($roleLabels[$role] ?? $role); ?></div>
                        </div>
                        <div class="h1 mb-0 mt-2"><?php echo (int)($roleCounts[$role] ?? 0); ?></div>
                        <div class="text-muted">Benutzer</div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Permissions Matrix -->
    <form method="post">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
        <input type="hidden" name="action" value="save_permissions">

        <div class="card">
            <div class="card-header d-flex align-items-center justify-content-between">
                <h3 class="card-title">Berechtigungsmatrix</h3>
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-device-floppy" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2"/><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M14 4l0 4l-6 0l0 -4"/></svg>
                    Speichern
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table table-striped">
                    <thead>
                        <tr>
                            <th style="min-width: 200px;">Berechtigung</th>
                            <?php foreach ($roles as $role): ?>
                                <th class="text-center" style="min-width: 120px;">
                                    <?php echo htmlspecialchars($roleLabels[$role] ?? $role); ?>
                                    <div class="small text-muted"><?php echo (int)($roleCounts[$role] ?? 0); ?> Benutzer</div>
                                </th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($capabilities as $group => $caps): ?>
                            <!-- Group Header -->
                            <tr class="bg-light">
                                <td colspan="<?php echo count($roles) + 1; ?>">
                                    <strong><?php echo htmlspecialchars($groupLabels[$group] ?? $group); ?></strong>
                                    <button type="button" class="btn btn-sm btn-ghost-secondary ms-2 toggle-group" data-group="<?php echo htmlspecialchars($group); ?>">
                                        Alle umschalten
                                    </button>
                                </td>
                            </tr>
                            <?php foreach ($caps as $cap):
                                $capParts = explode('.', $cap);
                                $shortCap = end($capParts);
                            ?>
                                <tr>
                                    <td>
                                        <span class="text-muted me-1"><?php echo htmlspecialchars($groupLabels[$group] ?? $group); ?> →</span>
                                        <?php echo htmlspecialchars($capLabels[$shortCap] ?? $shortCap); ?>
                                    </td>
                                    <?php foreach ($roles as $role): ?>
                                        <td class="text-center">
                                            <?php if ($role === 'admin'): ?>
                                                <input type="hidden" name="permissions[admin][<?php echo htmlspecialchars($cap); ?>]" value="1">
                                                <input type="checkbox" class="form-check-input" checked disabled title="Admin hat immer alle Rechte">
                                            <?php else: ?>
                                                <input type="checkbox"
                                                       class="form-check-input cap-checkbox"
                                                       name="permissions[<?php echo htmlspecialchars($role); ?>][<?php echo htmlspecialchars($cap); ?>]"
                                                       value="1"
                                                       data-group="<?php echo htmlspecialchars($group); ?>"
                                                       data-role="<?php echo htmlspecialchars($role); ?>"
                                                       <?php echo !empty($permissions[$role][$cap]) ? 'checked' : ''; ?>>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-device-floppy" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M6 4h10l4 4v10a2 2 0 0 1 -2 2h-12a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2"/><path d="M12 14m-2 0a2 2 0 1 0 4 0a2 2 0 1 0 -4 0"/><path d="M14 4l0 4l-6 0l0 -4"/></svg>
                    Berechtigungen speichern
                </button>
            </div>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.toggle-group').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var group = this.dataset.group;
            var checkboxes = document.querySelectorAll('.cap-checkbox[data-group="' + group + '"]');
            var allChecked = Array.from(checkboxes).every(function(cb) { return cb.checked; });
            checkboxes.forEach(function(cb) { cb.checked = !allChecked; });
        });
    });
});
</script>

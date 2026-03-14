<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Users – Gruppen View
 *
 * Erwartet: $data, $alert, $csrfToken
 */

$groups  = $data['groups'] ?? [];
$siteUrl = defined('SITE_URL') ? SITE_URL : '';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">Benutzer & Gruppen</div>
                <h2 class="page-title">Gruppen</h2>
            </div>
            <div class="col-auto ms-auto">
                <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#groupModal" onclick="document.getElementById('groupId').value='';document.getElementById('groupName').value='';document.getElementById('groupDesc').value='';document.getElementById('groupModalTitle').textContent='Neue Gruppe'">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                    Neue Gruppe
                </button>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <?php if (!empty($alert)): ?>
            <?php $alertData = $alert; $alertMarginClass = 'mb-3'; require __DIR__ . '/../partials/flash-alert.php'; ?>
        <?php endif; ?>

        <div class="row row-cards">
            <?php if (empty($groups)): ?>
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="empty">
                                <p class="empty-title">Keine Gruppen vorhanden</p>
                                <p class="empty-subtitle text-secondary">Erstellen Sie eine neue Gruppe, um Benutzer zu organisieren.</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($groups as $group): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-3">
                                    <span class="avatar bg-blue-lt me-3">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 13a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M8 21v-1a2 2 0 0 1 2 -2h4a2 2 0 0 1 2 2v1"/><path d="M15 5a2 2 0 1 0 4 0a2 2 0 0 0 -4 0"/><path d="M17 10h2a2 2 0 0 1 2 2v1"/><path d="M5 5a2 2 0 1 0 4 0a2 4 0 0 0 -4 0"/><path d="M3 13v-1a2 2 0 0 1 2 -2h2"/></svg>
                                    </span>
                                    <div>
                                        <h3 class="card-title mb-0"><?php echo htmlspecialchars($group->name ?? ''); ?></h3>
                                        <div class="text-secondary small"><?php echo (int)($group->member_count ?? 0); ?> Mitglieder</div>
                                    </div>
                                </div>
                                <?php if (!empty($group->description)): ?>
                                    <p class="text-secondary mb-0"><?php echo htmlspecialchars($group->description); ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="card-footer d-flex gap-2">
                                <button class="btn btn-outline-primary btn-sm" onclick="editGroup(<?php echo (int)$group->id; ?>, '<?php echo htmlspecialchars(addslashes($group->name ?? ''), ENT_QUOTES); ?>', '<?php echo htmlspecialchars(addslashes($group->description ?? ''), ENT_QUOTES); ?>')">Bearbeiten</button>
                                <button class="btn btn-outline-danger btn-sm" onclick="deleteGroup(<?php echo (int)$group->id; ?>, '<?php echo htmlspecialchars(addslashes($group->name ?? ''), ENT_QUOTES); ?>')">Löschen</button>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>
</div>

<!-- Gruppe erstellen/bearbeiten Modal -->
<div class="modal modal-blur fade" id="groupModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <form method="post" class="modal-content">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" id="groupId">
            <div class="modal-header">
                <h5 class="modal-title" id="groupModalTitle">Neue Gruppe</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label required" for="groupName">Name</label>
                    <input type="text" class="form-control" id="groupName" name="name" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="groupDesc">Beschreibung</label>
                    <textarea class="form-control" id="groupDesc" name="description" rows="3"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn" data-bs-dismiss="modal">Abbrechen</button>
                <button type="submit" class="btn btn-primary">Speichern</button>
            </div>
        </form>
    </div>
</div>

<!-- Delete Form -->
<form id="deleteGroupForm" method="post" class="d-none">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    <input type="hidden" name="action" value="delete">
    <input type="hidden" name="id" id="deleteGroupId">
</form>

<script>
function editGroup(id, name, desc) {
    document.getElementById('groupId').value = id;
    document.getElementById('groupName').value = name;
    document.getElementById('groupDesc').value = desc;
    document.getElementById('groupModalTitle').textContent = 'Gruppe bearbeiten';
    new bootstrap.Modal(document.getElementById('groupModal')).show();
}
function deleteGroup(id, name) {
    cmsConfirm({
        title: 'Gruppe löschen',
        message: 'Gruppe "' + name + '" wirklich löschen?',
        confirmText: 'Löschen',
        onConfirm: function() {
            document.getElementById('deleteGroupId').value = id;
            document.getElementById('deleteGroupForm').submit();
        }
    });
}
</script>

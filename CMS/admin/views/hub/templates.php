<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$templates = $data['templates'] ?? [];
$baseTemplateOptions = $data['baseTemplateOptions'] ?? [];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">Hub-Site Verwaltung</div>
                <h2 class="page-title">Templates</h2>
            </div>
            <div class="col-auto ms-auto">
                <a href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites?action=template-edit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                    Neues Template
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <ul class="nav nav-tabs mb-4">
            <li class="nav-item"><a class="nav-link" href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites">Content</a></li>
            <li class="nav-item"><a class="nav-link active" href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites?action=templates">Templates</a></li>
        </ul>

        <?php if (!empty($alert)): ?>
            <div class="alert alert-<?php echo $alert['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible mb-3" role="alert">
                <?php echo htmlspecialchars((string)$alert['message']); ?>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></a>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <div>
                    <h3 class="card-title mb-1">Template-Bibliothek</h3>
                    <div class="text-secondary small">Hier passt du zentrale Layout-, Meta-, Link- und Design-Vorgaben an. Diese Konfiguration gehört bewusst nicht in die Hub-Site-Erstellung.</div>
                </div>
            </div>
            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Template</th>
                            <th>Basis-Layout</th>
                            <th>Nutzung</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($templates === []): ?>
                        <tr><td colspan="4" class="text-center text-secondary py-4">Noch keine Templates vorhanden.</td></tr>
                    <?php else: ?>
                        <?php foreach ($templates as $template): ?>
                            <tr>
                                <td>
                                    <a href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites?action=template-edit&key=<?php echo rawurlencode((string)$template['key']); ?>" class="text-reset font-weight-medium">
                                        <?php echo htmlspecialchars((string)$template['label']); ?>
                                    </a>
                                    <div class="text-secondary small"><code><?php echo htmlspecialchars((string)$template['key']); ?></code></div>
                                    <?php if (!empty($template['summary'])): ?>
                                        <div class="text-secondary small mt-1"><?php echo htmlspecialchars((string)$template['summary']); ?></div>
                                    <?php endif; ?>
                                </td>
                                <td><span class="badge bg-azure-lt"><?php echo htmlspecialchars((string)($baseTemplateOptions[$template['base_template']] ?? $template['base_template'])); ?></span></td>
                                <td><?php echo (int)($template['usage_count'] ?? 0); ?> Hub-Sites</td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-ghost-secondary btn-icon btn-sm" data-bs-toggle="dropdown">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/><circle cx="12" cy="5" r="1"/></svg>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <a class="dropdown-item" href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/hub-sites?action=template-edit&key=<?php echo rawurlencode((string)$template['key']); ?>">Bearbeiten / umbenennen</a>
                                            <button class="dropdown-item" onclick="duplicateTemplate('<?php echo htmlspecialchars((string)$template['key'], ENT_QUOTES); ?>')">Kopieren</button>
                                            <div class="dropdown-divider"></div>
                                            <button class="dropdown-item text-danger" onclick="deleteTemplate('<?php echo htmlspecialchars((string)$template['key'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars((string)$template['label'], ENT_QUOTES); ?>')">Löschen</button>
                                        </div>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<form id="duplicateTemplateForm" method="post" class="d-none">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    <input type="hidden" name="action" value="duplicate-template">
    <input type="hidden" name="key" id="duplicateTemplateKey">
</form>

<form id="deleteTemplateForm" method="post" class="d-none">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    <input type="hidden" name="action" value="delete-template">
    <input type="hidden" name="key" id="deleteTemplateKey">
</form>

<script>
function duplicateTemplate(key) {
    document.getElementById('duplicateTemplateKey').value = key;
    document.getElementById('duplicateTemplateForm').submit();
}

function deleteTemplate(key, name) {
    cmsConfirm({
        title: 'Template löschen',
        message: 'Template <strong>' + name + '</strong> wirklich löschen?',
        confirmText: 'Löschen',
        confirmClass: 'btn-danger',
        onConfirm: function() {
            document.getElementById('deleteTemplateKey').value = key;
            document.getElementById('deleteTemplateForm').submit();
        }
    });
}
</script>

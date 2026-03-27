<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

$privacyData   = $data['privacy'] ?? [];
$deletionData  = $data['deletion'] ?? [];
$privacyStats  = $privacyData['stats'] ?? [];
$deletionStats = $deletionData['stats'] ?? [];
$privacyRows   = $privacyData['requests'] ?? [];
$deletionRows  = $deletionData['requests'] ?? [];

$statusLabels = [
    'pending' => 'Wartend',
    'processing' => 'In Bearbeitung',
    'completed' => 'Abgeschlossen',
    'rejected' => 'Abgelehnt',
];

$statusBadges = [
    'pending' => 'bg-warning',
    'processing' => 'bg-blue',
    'completed' => 'bg-success',
    'rejected' => 'bg-danger',
];

$dataRequestsConfig = [
    'rejectModalId' => 'rejectDataRequestModal',
    'defaultRejectTitle' => 'Anfrage ablehnen',
];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row g-2 align-items-center">
            <div class="col">
                <div class="page-pretitle">Recht</div>
                <h2 class="page-title">Auskunft &amp; Löschen</h2>
                <div class="text-secondary mt-1">DSGVO-Anfragen für Auskunft und Löschung auf einer zentralen Arbeitsseite.</div>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">
        <?php
        $alertData = $alert ?? [];
        $alertDismissible = false;
        $alertMarginClass = 'mb-4';
        require __DIR__ . '/../partials/flash-alert.php';
        ?>

        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Auskunft gesamt</div><div class="h1 mb-0"><?php echo (int)($privacyStats['total'] ?? 0); ?></div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Löschanträge gesamt</div><div class="h1 mb-0"><?php echo (int)($deletionStats['total'] ?? 0); ?></div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">Offen</div><div class="h1 mb-0 text-warning"><?php echo (int)(($privacyStats['pending'] ?? 0) + ($deletionStats['pending'] ?? 0)); ?></div></div></div></div>
            <div class="col-sm-6 col-lg-3"><div class="card"><div class="card-body"><div class="subheader">In Bearbeitung</div><div class="h1 mb-0 text-primary"><?php echo (int)(($privacyStats['processing'] ?? 0) + ($deletionStats['processing'] ?? 0)); ?></div></div></div></div>
        </div>

        <div class="row row-cards">
            <div class="col-12 col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Datenschutz-Auskunft</h3>
                    </div>
                    <div class="card-body border-bottom">
                        <div class="alert alert-info mb-0"><strong>DSGVO Art. 15:</strong> Betroffene Personen haben das Recht auf Auskunft über die verarbeiteten personenbezogenen Daten. Ziel: Bearbeitung innerhalb eines Monats.</div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name / E-Mail</th>
                                    <th>Status</th>
                                    <th>Erstellt</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($privacyRows)): ?>
                                    <?php
                                    $emptyStateColspan = 5;
                                    $emptyStateMessage = 'Keine Auskunftsanfragen vorhanden.';
                                    $emptyStateSubtitle = 'Sobald Anfragen eingehen, werden sie hier zentral zur Bearbeitung aufgelistet.';
                                    $emptyStateIcon = 'default';
                                    require __DIR__ . '/../partials/empty-table-row.php';
                                    ?>
                                <?php else: ?>
                                    <?php foreach ($privacyRows as $row): ?>
                                        <tr>
                                            <td><?php echo (int)($row['id'] ?? 0); ?></td>
                                            <td>
                                                <div><?php echo htmlspecialchars((string)($row['name'] ?? '-')); ?></div>
                                                <div class="text-secondary small"><?php echo htmlspecialchars((string)($row['email'] ?? '')); ?></div>
                                            </td>
                                            <td><span class="badge <?php echo htmlspecialchars($statusBadges[$row['status'] ?? ''] ?? 'bg-secondary'); ?>"><?php echo htmlspecialchars($statusLabels[$row['status'] ?? ''] ?? (string)($row['status'] ?? '')); ?></span></td>
                                            <td class="text-secondary small"><?php echo htmlspecialchars((string)($row['created_at'] ?? '')); ?></td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-ghost-secondary btn-icon btn-sm" data-bs-toggle="dropdown"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/></svg></button>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <?php if (($row['status'] ?? '') === 'pending'): ?>
                                                            <form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>"><input type="hidden" name="scope" value="privacy"><input type="hidden" name="action" value="process"><input type="hidden" name="id" value="<?php echo (int)($row['id'] ?? 0); ?>"><button class="dropdown-item">Bearbeitung starten</button></form>
                                                        <?php endif; ?>
                                                        <?php if (($row['status'] ?? '') === 'processing'): ?>
                                                            <form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>"><input type="hidden" name="scope" value="privacy"><input type="hidden" name="action" value="complete"><input type="hidden" name="id" value="<?php echo (int)($row['id'] ?? 0); ?>"><button class="dropdown-item text-success">Abschließen &amp; exportieren</button></form>
                                                        <?php endif; ?>
                                                        <?php if (in_array(($row['status'] ?? ''), ['pending', 'processing'], true)): ?>
                                                            <button type="button" class="dropdown-item text-warning js-open-data-request-reject-modal" data-request-scope="privacy" data-request-id="<?php echo (int)($row['id'] ?? 0); ?>" data-request-title="Auskunftsanfrage ablehnen">Ablehnen</button>
                                                        <?php endif; ?>
                                                        <?php if (in_array(($row['status'] ?? ''), ['completed', 'rejected'], true)): ?>
                                                            <form method="post" data-confirm-message="Anfrage endgültig löschen?" data-confirm-title="Anfrage löschen" data-confirm-text="Löschen" data-confirm-class="btn-danger" data-confirm-status-class="bg-danger"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>"><input type="hidden" name="scope" value="privacy"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int)($row['id'] ?? 0); ?>"><button class="dropdown-item text-danger">Löschen</button></form>
                                                        <?php endif; ?>
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

            <div class="col-12 col-xl-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Löschanträge</h3>
                    </div>
                    <div class="card-body border-bottom">
                        <div class="alert alert-warning mb-0"><strong>DSGVO Art. 17:</strong> Löschungen müssen geprüft, dokumentiert und – sofern zulässig – fristgerecht umgesetzt werden.</div>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-vcenter card-table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Name / E-Mail</th>
                                    <th>Status</th>
                                    <th>Erstellt</th>
                                    <th class="w-1"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($deletionRows)): ?>
                                    <?php
                                    $emptyStateColspan = 5;
                                    $emptyStateMessage = 'Keine Löschanträge vorhanden.';
                                    $emptyStateSubtitle = 'Offene Löschanträge werden hier mit Status und Bearbeitungsaktionen angezeigt.';
                                    $emptyStateIcon = 'default';
                                    require __DIR__ . '/../partials/empty-table-row.php';
                                    ?>
                                <?php else: ?>
                                    <?php foreach ($deletionRows as $row): ?>
                                        <tr>
                                            <td><?php echo (int)($row['id'] ?? 0); ?></td>
                                            <td>
                                                <div><?php echo htmlspecialchars((string)($row['name'] ?? '-')); ?></div>
                                                <div class="text-secondary small"><?php echo htmlspecialchars((string)($row['email'] ?? '')); ?></div>
                                            </td>
                                            <td><span class="badge <?php echo htmlspecialchars($statusBadges[$row['status'] ?? ''] ?? 'bg-secondary'); ?>"><?php echo htmlspecialchars($statusLabels[$row['status'] ?? ''] ?? (string)($row['status'] ?? '')); ?></span></td>
                                            <td class="text-secondary small"><?php echo htmlspecialchars((string)($row['created_at'] ?? '')); ?></td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-ghost-secondary btn-icon btn-sm" data-bs-toggle="dropdown"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/></svg></button>
                                                    <div class="dropdown-menu dropdown-menu-end">
                                                        <?php if (($row['status'] ?? '') === 'pending'): ?>
                                                            <form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>"><input type="hidden" name="scope" value="deletion"><input type="hidden" name="action" value="process"><input type="hidden" name="id" value="<?php echo (int)($row['id'] ?? 0); ?>"><button class="dropdown-item">Prüfung starten</button></form>
                                                        <?php endif; ?>
                                                        <?php if (($row['status'] ?? '') === 'processing'): ?>
                                                            <form method="post" data-confirm-message="Benutzerdaten werden anonymisiert. Fortfahren?" data-confirm-title="Löschung durchführen" data-confirm-text="Anonymisieren" data-confirm-class="btn-danger" data-confirm-status-class="bg-danger"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>"><input type="hidden" name="scope" value="deletion"><input type="hidden" name="action" value="execute"><input type="hidden" name="id" value="<?php echo (int)($row['id'] ?? 0); ?>"><button class="dropdown-item text-danger">Löschung durchführen</button></form>
                                                        <?php endif; ?>
                                                        <?php if (in_array(($row['status'] ?? ''), ['pending', 'processing'], true)): ?>
                                                            <button type="button" class="dropdown-item text-warning js-open-data-request-reject-modal" data-request-scope="deletion" data-request-id="<?php echo (int)($row['id'] ?? 0); ?>" data-request-title="Löschantrag ablehnen">Ablehnen</button>
                                                        <?php endif; ?>
                                                        <?php if (in_array(($row['status'] ?? ''), ['completed', 'rejected'], true)): ?>
                                                            <form method="post" data-confirm-message="Antrag endgültig löschen?" data-confirm-title="Antrag löschen" data-confirm-text="Löschen" data-confirm-class="btn-danger" data-confirm-status-class="bg-danger"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>"><input type="hidden" name="scope" value="deletion"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int)($row['id'] ?? 0); ?>"><button class="dropdown-item text-danger">Löschen</button></form>
                                                        <?php endif; ?>
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
    </div>
</div>

<div class="modal modal-blur fade" id="rejectDataRequestModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                <input type="hidden" name="scope" id="rejectScope" value="privacy">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="id" id="rejectRequestId" value="0">
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalTitle">Anfrage ablehnen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Begründung</label>
                    <textarea name="reject_reason" class="form-control" rows="3" required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-warning">Ablehnen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script type="application/json" id="data-requests-config"><?php echo json_encode($dataRequestsConfig, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?></script>

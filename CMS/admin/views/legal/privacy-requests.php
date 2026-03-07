<?php
declare(strict_types=1);
if (!defined('ABSPATH')) exit;

/** @var array $data */
$d        = $data ?? [];
$requests = $d['requests'] ?? [];
$stats    = $d['stats'] ?? [];
$statusLabels = ['pending' => 'Wartend', 'processing' => 'In Bearbeitung', 'completed' => 'Abgeschlossen', 'rejected' => 'Abgelehnt'];
$statusBadges = ['pending' => 'bg-warning', 'processing' => 'bg-blue', 'completed' => 'bg-success', 'rejected' => 'bg-danger'];
?>

<!-- KPI-Karten -->
<div class="row row-deck row-cards mb-4">
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Anfragen gesamt</div>
                <div class="h1 mb-0"><?php echo (int)($stats['total'] ?? 0); ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Wartend</div>
                <div class="h1 mb-0 text-warning"><?php echo (int)($stats['pending'] ?? 0); ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="subheader">In Bearbeitung</div>
                <div class="h1 mb-0 text-primary"><?php echo (int)($stats['processing'] ?? 0); ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-3">
        <div class="card">
            <div class="card-body">
                <div class="subheader">Abgeschlossen</div>
                <div class="h1 mb-0 text-success"><?php echo (int)($stats['completed'] ?? 0); ?></div>
            </div>
        </div>
    </div>
</div>

<div class="alert alert-info mb-4">
    <strong>DSGVO Art. 15 – Auskunftsrecht:</strong> Betroffene Personen haben das Recht, Auskunft über die sie betreffenden personenbezogenen Daten zu erhalten. Anfragen müssen innerhalb eines Monats bearbeitet werden.
</div>

<!-- Anfragen-Tabelle -->
<div class="card">
    <div class="card-header">
        <h3 class="card-title">Auskunftsanfragen</h3>
    </div>
    <div class="table-responsive">
        <table class="table table-vcenter card-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Name / E-Mail</th>
                    <th>Benutzer</th>
                    <th>Status</th>
                    <th>Erstellt</th>
                    <th>Bearbeitet</th>
                    <th class="w-1"></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($requests)): ?>
                <tr><td colspan="7" class="text-muted text-center">Keine Anfragen vorhanden</td></tr>
                <?php else: ?>
                <?php foreach ($requests as $r): ?>
                <tr>
                    <td><?php echo (int)$r['id']; ?></td>
                    <td>
                        <div><?php echo htmlspecialchars($r['name'] ?? '-'); ?></div>
                        <div class="text-muted small"><?php echo htmlspecialchars($r['email']); ?></div>
                    </td>
                    <td><?php echo htmlspecialchars($r['username'] ?? '-'); ?></td>
                    <td><span class="badge <?php echo $statusBadges[$r['status']] ?? 'bg-secondary'; ?>"><?php echo htmlspecialchars($statusLabels[$r['status']] ?? $r['status']); ?></span></td>
                    <td class="text-muted"><?php echo htmlspecialchars($r['created_at'] ?? ''); ?></td>
                    <td class="text-muted"><?php echo htmlspecialchars($r['completed_at'] ?? '-'); ?></td>
                    <td>
                        <div class="dropdown">
                            <button class="btn btn-ghost-secondary btn-icon btn-sm" data-bs-toggle="dropdown">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 19m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/><path d="M12 5m-1 0a1 1 0 1 0 2 0a1 1 0 1 0 -2 0"/></svg>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <?php if ($r['status'] === 'pending'): ?>
                                <form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>"><input type="hidden" name="action" value="process"><input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>"><button class="dropdown-item">Bearbeitung starten</button></form>
                                <?php endif; ?>
                                <?php if ($r['status'] === 'processing'): ?>
                                <form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>"><input type="hidden" name="action" value="complete"><input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>"><button class="dropdown-item text-success">Abschließen & Daten exportieren</button></form>
                                <?php endif; ?>
                                <?php if (in_array($r['status'], ['pending', 'processing'], true)): ?>
                                <a href="#" class="dropdown-item text-warning" onclick="rejectRequest(<?php echo (int)$r['id']; ?>)">Ablehnen</a>
                                <?php endif; ?>
                                <?php if (in_array($r['status'], ['completed', 'rejected'], true)): ?>
                                <form method="post"><input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?php echo (int)$r['id']; ?>"><button class="dropdown-item text-danger" onclick="return confirm('Anfrage endgültig löschen?')">Löschen</button></form>
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

<!-- Ablehnen-Modal -->
<div class="modal modal-blur fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <form method="post">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="id" id="rejectId" value="0">
                <div class="modal-header">
                    <h5 class="modal-title">Anfrage ablehnen</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Begründung</label>
                        <textarea name="reject_reason" class="form-control" rows="3" required></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn me-auto" data-bs-dismiss="modal">Abbrechen</button>
                    <button type="submit" class="btn btn-warning">Ablehnen</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function rejectRequest(id) {
    document.getElementById('rejectId').value = id;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>

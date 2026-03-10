<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Comments – Listenansicht (Moderation)
 *
 * Erwartet: $data (aus CommentsModule::getListData())
 *           $alert, $csrfToken
 */

$comments = $data['comments'] ?? [];
$counts   = $data['counts'] ?? [];
$status   = $data['status'] ?? 'all';

$commentField = static function (mixed $comment, string $key, mixed $default = ''): mixed {
    if (is_array($comment)) {
        return $comment[$key] ?? $default;
    }

    if (is_object($comment) && isset($comment->{$key})) {
        return $comment->{$key};
    }

    return $default;
};

$tabs = [
    'all'      => ['label' => 'Alle',         'count' => $counts['all'] ?? 0],
    'pending'  => ['label' => 'Ausstehend',   'count' => $counts['pending'] ?? 0],
    'approved' => ['label' => 'Freigegeben',  'count' => $counts['approved'] ?? 0],
    'spam'     => ['label' => 'Spam',         'count' => $counts['spam'] ?? 0],
    'trash'    => ['label' => 'Papierkorb',   'count' => $counts['trash'] ?? 0],
];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">Seiten &amp; Beiträge</div>
                <h2 class="page-title">Kommentare</h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <?php
        $alertData = $alert ?? [];
        $alertDismissible = true;
        $alertMarginClass = 'mb-3';
        require __DIR__ . '/../partials/flash-alert.php';
        ?>

        <!-- KPIs -->
        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto"><span class="bg-primary text-white avatar"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 20l1.3 -3.9c-2.324 -3.437 -1.426 -7.872 2.1 -10.374c3.526 -2.501 8.59 -2.296 11.845 .48c3.255 2.777 3.695 7.266 1.029 10.501c-2.666 3.235 -7.615 4.215 -11.574 2.293l-4.7 1"/></svg></span></div>
                            <div class="col">
                                <div class="font-weight-medium"><?php echo (int)($counts['all'] ?? 0); ?></div>
                                <div class="text-secondary">Gesamt</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto"><span class="bg-warning text-white avatar"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/><path d="M12 16h.01"/></svg></span></div>
                            <div class="col">
                                <div class="font-weight-medium"><?php echo (int)($counts['pending'] ?? 0); ?></div>
                                <div class="text-secondary">Ausstehend</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto"><span class="bg-success text-white avatar"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg></span></div>
                            <div class="col">
                                <div class="font-weight-medium"><?php echo (int)($counts['approved'] ?? 0); ?></div>
                                <div class="text-secondary">Freigegeben</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto"><span class="bg-danger text-white avatar"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12"/><path d="M6 6l12 12"/></svg></span></div>
                            <div class="col">
                                <div class="font-weight-medium"><?php echo (int)($counts['spam'] ?? 0); ?></div>
                                <div class="text-secondary">Spam</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab-Navigation -->
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs">
                    <?php foreach ($tabs as $key => $tab): ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $status === $key ? 'active' : ''; ?>"
                               href="<?php echo htmlspecialchars(SITE_URL); ?>/admin/comments<?php echo $key !== 'all' ? '?status=' . $key : ''; ?>">
                                <?php echo htmlspecialchars($tab['label']); ?>
                                <?php if ($tab['count'] > 0): ?>
                                    <span class="badge bg-<?php echo $key === 'pending' ? 'warning' : ($key === 'spam' ? 'danger' : 'secondary'); ?> ms-1"><?php echo (int)$tab['count']; ?></span>
                                <?php endif; ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>

            <!-- Bulk-Aktionen -->
            <div class="card-body py-2 d-none" id="bulkBar">
                <form method="post" id="bulkForm">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                    <input type="hidden" name="action" value="bulk">
                    <div class="d-flex align-items-center gap-2">
                        <span class="text-secondary"><strong id="selectedCount">0</strong> ausgewählt</span>
                        <select name="bulk_action" class="form-select form-select-sm w-auto">
                            <option value="">Aktion wählen…</option>
                            <option value="approve">Freigeben</option>
                            <option value="spam">Als Spam</option>
                            <option value="trash">Papierkorb</option>
                            <option value="delete">Endgültig löschen</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary">Anwenden</button>
                    </div>
                </form>
            </div>

            <!-- Tabelle -->
            <div class="table-responsive comments-table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th class="w-1"><input class="form-check-input" type="checkbox" id="selectAll"></th>
                            <th>Autor</th>
                            <th>Kommentar</th>
                            <th>Beitrag</th>
                            <th>Status</th>
                            <th>Datum</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($comments)): ?>
                        <?php
                        $emptyStateColspan = 7;
                        $emptyStateMessage = 'Keine Kommentare in dieser Ansicht.';
                        $emptyStateSubtitle = 'Passen Sie den Statusfilter an oder warten Sie auf neue Moderationsfälle.';
                        $emptyStateIcon = 'comments';
                        require __DIR__ . '/../partials/empty-table-row.php';
                        ?>
                    <?php else: ?>
                        <?php foreach ($comments as $c): ?>
                            <?php
                            $cId        = (int)$commentField($c, 'id', 0);
                            $cAuthor    = (string)$commentField($c, 'author', '');
                            $cEmail     = (string)$commentField($c, 'author_email', '');
                            $cContent   = (string)$commentField($c, 'content', '');
                            $cStatus    = (string)$commentField($c, 'status', '');
                            $cDate      = (string)$commentField($c, 'post_date', '');
                            $cPostTitle = (string)$commentField($c, 'post_title', '');
                            $cPostSlug  = (string)$commentField($c, 'post_slug', '');
                            $cInitials  = strtoupper(mb_substr(trim($cAuthor), 0, 2));
                            ?>
                            <tr>
                                <td>
                                    <input class="form-check-input row-check" type="checkbox" name="ids[]" value="<?php echo $cId; ?>" form="bulkForm">
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-sm me-2"><?php echo htmlspecialchars($cInitials !== '' ? $cInitials : 'KO'); ?></span>
                                        <div>
                                            <div class="font-weight-medium"><?php echo htmlspecialchars($cAuthor); ?></div>
                                            <div class="text-secondary small"><?php echo htmlspecialchars($cEmail); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width:300px;"><?php echo htmlspecialchars(mb_substr(strip_tags($cContent), 0, 120)); ?></div>
                                </td>
                                <td>
                                    <?php if ($cPostTitle): ?>
                                        <a href="<?php echo htmlspecialchars(SITE_URL); ?>/blog/<?php echo htmlspecialchars($cPostSlug); ?>" class="text-reset" target="_blank" rel="noopener noreferrer">
                                            <?php echo htmlspecialchars($cPostTitle); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-secondary">–</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    $statusBadges = [
                                        'approved' => 'bg-success-lt',
                                        'pending'  => 'bg-warning-lt',
                                        'spam'     => 'bg-danger-lt',
                                        'trash'    => 'bg-secondary-lt',
                                    ];
                                    $statusLabels = [
                                        'approved' => 'Freigegeben',
                                        'pending'  => 'Ausstehend',
                                        'spam'     => 'Spam',
                                        'trash'    => 'Papierkorb',
                                    ];
                                    ?>
                                    <span class="badge <?php echo $statusBadges[$cStatus] ?? 'bg-secondary-lt'; ?>">
                                        <?php echo htmlspecialchars($statusLabels[$cStatus] ?? $cStatus); ?>
                                    </span>
                                </td>
                                <td class="text-secondary"><?php echo $cDate ? date('d.m.Y H:i', strtotime($cDate)) : '–'; ?></td>
                                <td>
                                    <div class="dropdown">
                                        <button class="btn btn-ghost-secondary btn-icon btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/><circle cx="12" cy="5" r="1"/></svg>
                                        </button>
                                        <div class="dropdown-menu dropdown-menu-end">
                                            <?php if ($cStatus !== 'approved'): ?>
                                                <button class="dropdown-item" onclick="commentAction(<?php echo $cId; ?>, 'approved')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                                                    Freigeben
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($cStatus !== 'pending'): ?>
                                                <button class="dropdown-item" onclick="commentAction(<?php echo $cId; ?>, 'pending')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/><path d="M12 16h.01"/></svg>
                                                    Ausstehend
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($cStatus !== 'spam'): ?>
                                                <button class="dropdown-item text-warning" onclick="commentAction(<?php echo $cId; ?>, 'spam')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon text-warning" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M5.7 5.7l12.6 12.6"/></svg>
                                                    Spam
                                                </button>
                                            <?php endif; ?>
                                            <?php if ($cStatus !== 'trash'): ?>
                                                <button class="dropdown-item text-danger" onclick="commentAction(<?php echo $cId; ?>, 'trash')">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon text-danger" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                                                    Papierkorb
                                                </button>
                                            <?php else: ?>
                                                <button class="dropdown-item text-danger" onclick="deleteComment(<?php echo $cId; ?>)">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon text-danger" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                                                    Endgültig löschen
                                                </button>
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

<!-- Aktions-Formular (hidden) -->
<form id="actionForm" method="post" class="d-none">
    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
    <input type="hidden" name="action" id="actionType" value="">
    <input type="hidden" name="id" id="actionId" value="">
    <input type="hidden" name="new_status" id="actionStatus" value="">
</form>

<script>
function commentAction(id, newStatus) {
    document.getElementById('actionType').value = 'status';
    document.getElementById('actionId').value = id;
    document.getElementById('actionStatus').value = newStatus;
    document.getElementById('actionForm').submit();
}

function deleteComment(id) {
    cmsConfirm({
        title: 'Kommentar löschen',
        message: 'Kommentar endgültig löschen? Dies kann nicht rückgängig gemacht werden.',
        confirmText: 'Löschen',
        confirmClass: 'btn-danger',
        onConfirm: function() {
            document.getElementById('actionType').value = 'delete';
            document.getElementById('actionId').value = id;
            document.getElementById('actionForm').submit();
        }
    });
}

// Select-All & Bulk
(function() {
    var selectAll = document.getElementById('selectAll');
    var bulkBar   = document.getElementById('bulkBar');
    var countEl   = document.getElementById('selectedCount');
    var tableWrap = document.querySelector('.comments-table-responsive');

    function updateBulk() {
        var checked = document.querySelectorAll('.row-check:checked').length;
        countEl.textContent = checked;
        bulkBar.classList.toggle('d-none', checked === 0);
    }

    function setDropdownOverflow(open) {
        if (!tableWrap) {
            return;
        }

        tableWrap.style.overflow = open ? 'visible' : '';
    }

    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('.row-check').forEach(function(cb) { cb.checked = selectAll.checked; });
            updateBulk();
        });
    }
    document.querySelectorAll('.row-check').forEach(function(cb) {
        cb.addEventListener('change', updateBulk);
    });

    document.querySelectorAll('.comments-table-responsive .dropdown').forEach(function(dropdown) {
        dropdown.addEventListener('show.bs.dropdown', function() {
            setDropdownOverflow(true);
        });

        dropdown.addEventListener('hidden.bs.dropdown', function() {
            setDropdownOverflow(false);
        });
    });
})();
</script>

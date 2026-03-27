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
$canModerate = (bool)($data['canModerate'] ?? false);
$canDelete = (bool)($data['canDelete'] ?? false);
$canBulkActions = $canModerate || $canDelete;
$showActionColumn = $canModerate || $canDelete;
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

$commentField = static function (mixed $comment, string $key, mixed $default = ''): mixed {
    if (is_array($comment)) {
        return $comment[$key] ?? $default;
    }

    if (is_object($comment) && isset($comment->{$key})) {
        return $comment->{$key};
    }

    return $default;
};

$formatCommentDate = static function (string $date): string {
    $timestamp = strtotime($date);

    return $timestamp !== false ? date('d.m.Y H:i', $timestamp) : '–';
};

$buildCommentExcerpt = static function (string $content): string {
    $plainText = trim(preg_replace('/\s+/u', ' ', strip_tags($content)) ?? '');

    return mb_substr($plainText, 0, 120);
};

$resolveCommentPostUrl = static function (string $url): string {
    $url = trim($url);
    if ($url === '') {
        return '';
    }

    if (filter_var($url, FILTER_VALIDATE_URL) !== false) {
        return $url;
    }

    return str_starts_with($url, '/') ? $url : '';
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
            <?php if ($canBulkActions): ?>
                <div class="card-body py-2 d-none" id="bulkBar">
                    <form method="post" id="bulkForm">
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                        <input type="hidden" name="action" value="bulk">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-secondary"><strong id="selectedCount">0</strong> ausgewählt</span>
                            <select name="bulk_action" class="form-select form-select-sm w-auto">
                                <option value="">Aktion wählen…</option>
                                <?php if ($canModerate): ?>
                                    <option value="approve">Freigeben</option>
                                    <option value="spam">Als Spam</option>
                                    <option value="trash">Papierkorb</option>
                                <?php endif; ?>
                                <?php if ($canDelete): ?>
                                    <option value="delete">Endgültig löschen</option>
                                <?php endif; ?>
                            </select>
                            <button type="submit" class="btn btn-sm btn-primary">Anwenden</button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Tabelle -->
            <div class="table-responsive comments-table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <?php if ($canBulkActions): ?>
                                <th class="w-1"><input class="form-check-input" type="checkbox" id="selectAll"></th>
                            <?php endif; ?>
                            <th>Autor</th>
                            <th>Kommentar</th>
                            <th>Beitrag</th>
                            <th>Status</th>
                            <th>Datum</th>
                            <?php if ($showActionColumn): ?>
                                <th class="w-1"></th>
                            <?php endif; ?>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($comments)): ?>
                        <?php
                        $emptyStateColspan = 5 + ($canBulkActions ? 1 : 0) + ($showActionColumn ? 1 : 0);
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
                            $cPostUrl   = $resolveCommentPostUrl((string)$commentField($c, 'post_url', ''));
                            $cInitials  = strtoupper(mb_substr(trim($cAuthor), 0, 2));
                            ?>
                            <tr>
                                <?php if ($canBulkActions): ?>
                                    <td>
                                        <input class="form-check-input row-check" type="checkbox" name="ids[]" value="<?php echo $cId; ?>" form="bulkForm">
                                    </td>
                                <?php endif; ?>
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
                                    <div class="text-truncate" style="max-width:300px;"><?php echo htmlspecialchars($buildCommentExcerpt($cContent)); ?></div>
                                </td>
                                <td>
                                    <?php if ($cPostTitle && $cPostUrl !== ''): ?>
                                        <a href="<?php echo htmlspecialchars($cPostUrl); ?>" class="text-reset" target="_blank" rel="noopener noreferrer">
                                            <?php echo htmlspecialchars($cPostTitle); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-secondary">–</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo $statusBadges[$cStatus] ?? 'bg-secondary-lt'; ?>">
                                        <?php echo htmlspecialchars($statusLabels[$cStatus] ?? $cStatus); ?>
                                    </span>
                                </td>
                                <td class="text-secondary"><?php echo htmlspecialchars($formatCommentDate($cDate)); ?></td>
                                <?php if ($showActionColumn): ?>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-ghost-secondary btn-icon btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/><circle cx="12" cy="5" r="1"/></svg>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <?php if ($canModerate && $cStatus !== 'approved'): ?>
                                                    <button type="button" class="dropdown-item js-comment-action" data-comment-id="<?php echo $cId; ?>" data-comment-action="status" data-comment-status="approved">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                                                        Freigeben
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($canModerate && $cStatus !== 'pending'): ?>
                                                    <button type="button" class="dropdown-item js-comment-action" data-comment-id="<?php echo $cId; ?>" data-comment-action="status" data-comment-status="pending">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/><path d="M12 16h.01"/></svg>
                                                        Ausstehend
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($canModerate && $cStatus !== 'spam'): ?>
                                                    <button type="button" class="dropdown-item text-warning js-comment-action" data-comment-id="<?php echo $cId; ?>" data-comment-action="status" data-comment-status="spam">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon text-warning" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M5.7 5.7l12.6 12.6"/></svg>
                                                        Spam
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($canModerate && $cStatus !== 'trash'): ?>
                                                    <button type="button" class="dropdown-item text-danger js-comment-action" data-comment-id="<?php echo $cId; ?>" data-comment-action="status" data-comment-status="trash">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon text-danger" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                                                        Papierkorb
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($canDelete && $cStatus === 'trash'): ?>
                                                    <button type="button" class="dropdown-item text-danger js-comment-delete" data-comment-id="<?php echo $cId; ?>">
                                                        <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon text-danger" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/></svg>
                                                        Endgültig löschen
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                <?php endif; ?>
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

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
$tabs = $data['tabs'] ?? [];
$summaryCards = is_array($data['summaryCards'] ?? null) ? $data['summaryCards'] : [];
$canModerate = (bool)($data['canModerate'] ?? false);
$canDelete = (bool)($data['canDelete'] ?? false);
$canBulkActions = $canModerate || $canDelete;
$showActionColumn = $canModerate || $canDelete;

$renderCommentIcon = static function (string $icon): string {
    return match ($icon) {
        'alert' => '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4"/><path d="M10.363 3.591l-8.106 13.534a1.914 1.914 0 0 0 1.636 2.871h16.214a1.914 1.914 0 0 0 1.636 -2.87l-8.106 -13.536a1.914 1.914 0 0 0 -3.274 0z"/><path d="M12 16h.01"/>',
        'check' => '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/>',
        'ban' => '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 12m-9 0a9 9 0 1 0 18 0a9 9 0 1 0 -18 0"/><path d="M5.7 5.7l12.6 12.6"/>',
        'trash' => '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 7l16 0"/><path d="M10 11l0 6"/><path d="M14 11l0 6"/><path d="M5 7l1 12a2 2 0 0 0 2 2h8a2 2 0 0 0 2 -2l1 -12"/><path d="M9 7v-3a1 1 0 0 1 1 -1h4a1 1 0 0 1 1 1v3"/>',
        default => '<path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 20l1.3 -3.9c-2.324 -3.437 -1.426 -7.872 2.1 -10.374c3.526 -2.501 8.59 -2.296 11.845 .48c3.255 2.777 3.695 7.266 1.029 10.501c-2.666 3.235 -7.615 4.215 -11.574 2.293l-4.7 1"/>',
    };
};

$actionClass = static function (string $variant): string {
    return match ($variant) {
        'warning' => 'dropdown-item text-warning',
        'danger' => 'dropdown-item text-danger',
        default => 'dropdown-item',
    };
};
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
            <?php foreach ($summaryCards as $card): ?>
                <div class="col-sm-6 col-lg-3">
                    <div class="card card-sm">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col-auto"><span class="avatar <?php echo htmlspecialchars((string) ($card['avatar_class'] ?? 'bg-secondary text-white')); ?>"><svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><?php echo $renderCommentIcon((string) ($card['icon'] ?? 'comments')); ?></svg></span></div>
                                <div class="col">
                                    <div class="font-weight-medium"><?php echo (int) ($card['count'] ?? 0); ?></div>
                                    <div class="text-secondary"><?php echo htmlspecialchars((string) ($card['label'] ?? '')); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Tab-Navigation -->
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs">
                    <?php foreach ($tabs as $tab): ?>
                        <?php
                        $tabStatus = (string)($tab['status'] ?? 'all');
                        $tabCount = (int)($tab['count'] ?? 0);
                        $tabUrl = (string)($tab['url'] ?? (htmlspecialchars(SITE_URL) . '/admin/comments'));
                        $tabBadgeClass = (string)($tab['badge_class'] ?? 'bg-secondary');
                        ?>
                        <li class="nav-item">
                            <a class="nav-link <?php echo $status === $tabStatus ? 'active' : ''; ?>"
                               href="<?php echo htmlspecialchars($tabUrl); ?>">
                                <?php echo htmlspecialchars((string)($tab['label'] ?? '')); ?>
                                <?php if ($tabCount > 0): ?>
                                    <span class="badge <?php echo htmlspecialchars($tabBadgeClass); ?> ms-1"><?php echo $tabCount; ?></span>
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
                            $cId        = (int)($c['id'] ?? 0);
                            $cAuthor    = (string)($c['author'] ?? '');
                            $cEmail     = (string)($c['author_email'] ?? '');
                            $cStatus    = (string)($c['status'] ?? '');
                            $cPostTitle = (string)($c['post_title'] ?? '');
                            $cPostUrl   = (string)($c['post_url'] ?? '');
                            ?>
                            <tr>
                                <?php if ($canBulkActions): ?>
                                    <td>
                                        <input class="form-check-input row-check" type="checkbox" name="ids[]" value="<?php echo $cId; ?>" form="bulkForm">
                                    </td>
                                <?php endif; ?>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-sm me-2"><?php echo htmlspecialchars((string)($c['author_initials'] ?? 'KO')); ?></span>
                                        <div>
                                            <div class="font-weight-medium"><?php echo htmlspecialchars($cAuthor); ?></div>
                                            <div class="text-secondary small"><?php echo htmlspecialchars($cEmail); ?></div>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="text-truncate" style="max-width:300px;"><?php echo htmlspecialchars((string)($c['excerpt'] ?? '')); ?></div>
                                </td>
                                <td>
                                    <?php if ($cPostTitle && !empty($c['has_post_link'])): ?>
                                        <a href="<?php echo htmlspecialchars($cPostUrl); ?>" class="text-reset" target="_blank" rel="noopener noreferrer">
                                            <?php echo htmlspecialchars($cPostTitle); ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-secondary">–</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <span class="badge <?php echo htmlspecialchars((string)($c['status_badge'] ?? 'bg-secondary-lt')); ?>">
                                        <?php echo htmlspecialchars((string)($c['status_label'] ?? $cStatus)); ?>
                                    </span>
                                </td>
                                <td class="text-secondary"><?php echo htmlspecialchars((string)($c['formatted_date'] ?? '–')); ?></td>
                                <?php if ($showActionColumn): ?>
                                    <td>
                                        <div class="dropdown">
                                            <button class="btn btn-ghost-secondary btn-icon btn-sm" data-bs-toggle="dropdown" aria-expanded="false">
                                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><circle cx="12" cy="12" r="1"/><circle cx="12" cy="19" r="1"/><circle cx="12" cy="5" r="1"/></svg>
                                            </button>
                                            <div class="dropdown-menu dropdown-menu-end">
                                                <?php foreach ((array) ($c['actions'] ?? []) as $action): ?>
                                                    <?php
                                                    $actionType = (string) ($action['type'] ?? 'status');
                                                    $buttonClass = $actionClass((string) ($action['variant'] ?? 'default'));
                                                    $iconName = (string) ($action['icon'] ?? 'check');
                                                    $actionCommentId = (int) ($action['comment_id'] ?? $cId);
                                                    $actionStatus = (string) ($action['status'] ?? '');
                                                    $isDeleteAction = $actionType === 'delete';
                                                    ?>
                                                    <form method="post"
                                                          class="m-0"
                                                          <?php if ($isDeleteAction): ?>data-confirm-title="Kommentar löschen" data-confirm-message="Kommentar wirklich löschen? Dies kann nicht rückgängig gemacht werden." data-confirm-text="Löschen" data-confirm-class="btn-danger" data-confirm-status-class="bg-danger"<?php endif; ?>>
                                                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                                                        <input type="hidden" name="action" value="<?php echo $isDeleteAction ? 'delete' : 'status'; ?>">
                                                        <input type="hidden" name="id" value="<?php echo $actionCommentId; ?>">
                                                        <?php if (!$isDeleteAction): ?>
                                                            <input type="hidden" name="new_status" value="<?php echo htmlspecialchars($actionStatus); ?>">
                                                        <?php endif; ?>
                                                        <button type="submit" class="<?php echo htmlspecialchars($buttonClass); ?> w-100 text-start">
                                                            <svg xmlns="http://www.w3.org/2000/svg" class="icon dropdown-item-icon<?php echo str_contains($buttonClass, 'text-') ? ' ' . trim(str_replace('dropdown-item', '', $buttonClass)) : ''; ?>" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><?php echo $renderCommentIcon($iconName); ?></svg>
                                                            <?php echo htmlspecialchars((string) ($action['label'] ?? 'Aktion')); ?>
                                                        </button>
                                                    </form>
                                                <?php endforeach; ?>
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


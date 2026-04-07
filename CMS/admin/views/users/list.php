<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Users – Listenansicht
 *
 * Erwartet: $data, $alert, $csrfToken
 */

$users   = $data['users'] ?? [];
$stats   = $data['stats'] ?? [];
$availableRoles = $data['availableRoles'] ?? [];
$availableStatuses = $data['availableStatuses'] ?? [];
$filter  = $data['filter'] ?? [];
$total   = $data['total'] ?? 0;
$curPage = $data['page'] ?? 1;
$pages   = $data['pages'] ?? 1;
$siteUrl = defined('SITE_URL') ? SITE_URL : '';

$roleColors = [
    'admin' => 'red',
    'editor' => 'blue',
    'author' => 'green',
    'member' => 'secondary',
];

$getRoleColor = static function (string $role) use ($roleColors): string {
    return $roleColors[$role] ?? 'azure';
};
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <div class="page-pretitle">Benutzer & Gruppen</div>
                <h2 class="page-title">Benutzer</h2>
            </div>
            <div class="col-auto ms-auto">
                <a href="<?php echo htmlspecialchars($siteUrl); ?>/admin/users?action=edit" class="btn btn-primary">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 5l0 14"/><path d="M5 12l14 0"/></svg>
                    Neuer Benutzer
                </a>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <?php if (!empty($alert)): ?>
            <?php $alertData = $alert; $alertMarginClass = 'mb-3'; require __DIR__ . '/../partials/flash-alert.php'; ?>
        <?php endif; ?>

        <!-- KPI-Karten -->
        <div class="row row-deck row-cards mb-4">
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto"><span class="bg-primary text-white avatar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 7m-4 0a4 4 0 1 0 8 0a4 4 0 1 0 -8 0"/><path d="M3 21v-2a4 4 0 0 1 4 -4h4a4 4 0 0 1 4 4v2"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/><path d="M21 21v-2a4 4 0 0 0 -3 -3.85"/></svg>
                            </span></div>
                            <div class="col"><div class="font-weight-medium"><?php echo (int)($stats['total_users'] ?? 0); ?></div><div class="text-secondary">Gesamt</div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto"><span class="bg-green text-white avatar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M5 12l5 5l10 -10"/></svg>
                            </span></div>
                            <div class="col"><div class="font-weight-medium"><?php echo (int)($stats['active_users'] ?? 0); ?></div><div class="text-secondary">Aktiv</div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto"><span class="bg-yellow text-white avatar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 9v4"/><path d="M12 16h.01"/><path d="M3 12a9 9 0 1 0 18 0a9 9 0 0 0 -18 0"/></svg>
                            </span></div>
                            <div class="col"><div class="font-weight-medium"><?php echo (int)($stats['inactive_users'] ?? 0); ?></div><div class="text-secondary">Inaktiv</div></div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-lg-3">
                <div class="card card-sm">
                    <div class="card-body">
                        <div class="row align-items-center">
                            <div class="col-auto"><span class="bg-red text-white avatar">
                                <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M18 6l-12 12"/><path d="M6 6l12 12"/></svg>
                            </span></div>
                            <div class="col"><div class="font-weight-medium"><?php echo (int)($stats['banned_users'] ?? 0); ?></div><div class="text-secondary">Gesperrt</div></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Tabelle -->
        <div class="card">
            <div class="card-header">
                <div class="row w-100 g-2 align-items-center">
                    <div class="col-auto">
                        <select class="form-select form-select-sm js-users-filter-role" data-users-base-url="<?php echo htmlspecialchars($siteUrl . '/admin/users', ENT_QUOTES); ?>">
                            <option value="">Alle Rollen</option>
                            <?php foreach ($availableRoles as $role => $label): ?>
                                <option value="<?php echo htmlspecialchars((string)$role); ?>" <?php if (($filter['role'] ?? '') === $role) echo 'selected'; ?>><?php echo htmlspecialchars((string)$label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-auto">
                        <select class="form-select form-select-sm js-users-filter-status" data-users-base-url="<?php echo htmlspecialchars($siteUrl . '/admin/users', ENT_QUOTES); ?>">
                            <option value="">Alle Status</option>
                            <?php foreach ($availableStatuses as $status => $label): ?>
                                <option value="<?php echo htmlspecialchars((string)$status); ?>" <?php if (($filter['status'] ?? '') === $status) echo 'selected'; ?>><?php echo htmlspecialchars((string)$label); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col">
                        <form method="get" action="<?php echo htmlspecialchars($siteUrl); ?>/admin/users" class="d-flex gap-2 js-users-search-form" data-users-base-url="<?php echo htmlspecialchars($siteUrl . '/admin/users', ENT_QUOTES); ?>">
                            <input type="hidden" name="role" value="<?php echo htmlspecialchars($filter['role']); ?>">
                            <input type="hidden" name="status" value="<?php echo htmlspecialchars($filter['status']); ?>">
                            <input type="text" class="form-control form-control-sm js-users-search-input" name="q" value="<?php echo htmlspecialchars($filter['search']); ?>" placeholder="Suchen…">
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Suchen</button>
                        </form>
                    </div>
                    <div class="col-auto">
                        <span class="text-secondary"><?php echo $total; ?> Benutzer</span>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-vcenter card-table">
                    <thead>
                        <tr>
                            <th>Benutzer</th>
                            <th>E-Mail</th>
                            <th>Rolle</th>
                            <th>Status</th>
                            <th>Registriert</th>
                            <th class="w-1"></th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($users)): ?>
                        <?php
                        $emptyStateColspan = 6;
                        $emptyStateMessage = 'Keine Benutzer gefunden.';
                        $emptyStateSubtitle = 'Prüfen Sie Filter oder Suche – die serverseitige Liste liefert aktuell keine Einträge.';
                        $emptyStateIcon = 'users';
                        require __DIR__ . '/../partials/empty-table-row.php';
                        ?>
                    <?php else: ?>
                        <?php foreach ($users as $user): ?>
                            <?php
                            $userId = (int)($user->id ?? 0);
                            $username = trim((string)($user->username ?? ''));
                            $displayName = trim((string)($user->display_name ?? ''));
                            $email = trim((string)($user->email ?? ''));
                            $role = trim((string)($user->role ?? 'member'));
                            $status = trim((string)($user->status ?? 'inactive'));
                            $createdAt = trim((string)($user->created_at ?? ''));
                            $roleLabel = (string)($availableRoles[$role] ?? ucfirst($role));
                            $statusLabel = match ($status) {
                                'active' => 'Aktiv',
                                'inactive' => 'Inaktiv',
                                'banned' => 'Gesperrt',
                                default => $status !== '' ? ucfirst($status) : '–',
                            };
                            $statusClass = match ($status) {
                                'active' => 'bg-green-lt text-green',
                                'inactive' => 'bg-yellow-lt text-yellow',
                                'banned' => 'bg-red-lt text-red',
                                default => 'bg-secondary-lt text-secondary',
                            };
                            $initials = strtoupper(substr($username !== '' ? $username : 'U', 0, 2));
                            ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="avatar avatar-sm me-2 bg-<?php echo htmlspecialchars($getRoleColor($role)); ?> text-white"><?php echo htmlspecialchars($initials); ?></span>
                                        <div>
                                            <a href="<?php echo htmlspecialchars($siteUrl); ?>/admin/users?action=edit&id=<?php echo $userId; ?>" class="text-reset fw-medium"><?php echo htmlspecialchars($username !== '' ? $username : 'Unbekannt'); ?></a>
                                            <?php if ($displayName !== ''): ?>
                                                <div class="text-secondary small"><?php echo htmlspecialchars($displayName); ?></div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </td>
                                <td><?php echo htmlspecialchars($email !== '' ? $email : '–'); ?></td>
                                <td><span class="badge bg-azure-lt"><?php echo htmlspecialchars($roleLabel); ?></span></td>
                                <td><span class="badge <?php echo htmlspecialchars($statusClass); ?>"><?php echo htmlspecialchars($statusLabel); ?></span></td>
                                <td class="text-secondary"><?php echo htmlspecialchars($createdAt !== '' ? substr($createdAt, 0, 10) : '–'); ?></td>
                                <td>
                                    <div class="d-flex gap-2 justify-content-end">
                                        <a href="<?php echo htmlspecialchars($siteUrl); ?>/admin/users?action=edit&id=<?php echo $userId; ?>" class="btn btn-sm btn-outline-primary">Bearbeiten</a>
                                        <form
                                            method="post"
                                            action="<?php echo htmlspecialchars($siteUrl); ?>/admin/users"
                                            class="d-inline"
                                            data-confirm-title="Benutzer deaktivieren?"
                                            data-confirm-message="Der Benutzer wird auf inaktiv gesetzt und kann sich nicht mehr normal anmelden. Wirklich fortfahren?"
                                            data-confirm-text="Benutzer deaktivieren"
                                            data-confirm-class="btn-danger"
                                            data-confirm-status-class="bg-danger"
                                        >
                                            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken ?? ''); ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?php echo $userId; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">Löschen</button>
                                        </form>
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


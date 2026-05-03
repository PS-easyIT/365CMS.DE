<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Users – Edit / Create View
 *
 * Erwartet: $data, $csrfToken, $alert
 */

$user  = $data['user'] ?? null;
$isNew = $data['isNew'] ?? true;
$availableRoles = $data['availableRoles'] ?? [];
$availableStatuses = $data['availableStatuses'] ?? [];
$usersAdminPath = '/admin/users';
$userId = (int)($user->id ?? 0);
$userName = trim((string)($user->username ?? ''));
$requestUriRaw = (string) ($_SERVER['REQUEST_URI'] ?? '');
$requestPath = (string) parse_url($requestUriRaw, PHP_URL_PATH);
$requestQuery = (string) parse_url($requestUriRaw, PHP_URL_QUERY);
if ($requestPath === '') {
    $requestPath = $usersAdminPath;
}
$currentRequestUri = $requestPath . ($requestQuery !== '' ? '?' . $requestQuery : '');

$roleColors = [
    'admin' => 'red',
    'editor' => 'blue',
    'author' => 'green',
    'member' => 'secondary',
];
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="<?php echo htmlspecialchars($usersAdminPath); ?>" class="btn btn-ghost-secondary btn-sm me-2">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6"/></svg>
                    Zurück
                </a>
            </div>
            <div class="col">
                <div class="page-pretitle">Benutzer & Gruppen</div>
                <h2 class="page-title"><?php echo $isNew ? 'Neuer Benutzer' : 'Benutzer bearbeiten'; ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="page-body">
    <div class="container-xl">

        <?php if (!empty($alert)): ?>
            <?php $alertData = $alert; $alertMarginClass = 'mb-4'; require __DIR__ . '/../partials/flash-alert.php'; ?>
        <?php endif; ?>

        <form method="post" action="<?php echo htmlspecialchars($currentRequestUri, ENT_QUOTES); ?>" id="userForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <?php if (!$isNew): ?>
                <input type="hidden" name="id" value="<?php echo $userId; ?>">
            <?php endif; ?>

            <div class="row">
                <div class="col-lg-8">
                    <!-- Account-Daten -->
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Kontodaten</h3></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label required" for="username">Benutzername</label>
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user->username ?? ''); ?>" required minlength="3" maxlength="50" pattern="[A-Za-z0-9_]+" autocomplete="username">
                                    <span class="form-hint">3–50 Zeichen, nur Buchstaben, Zahlen und Unterstrich.</span>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required" for="email">E-Mail</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user->email ?? ''); ?>" required maxlength="190" autocomplete="email">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label <?php echo $isNew ? 'required' : ''; ?>" for="password">Passwort</label>
                                    <input type="password" class="form-control" id="password" name="password" <?php echo $isNew ? 'required' : ''; ?> minlength="12" autocomplete="new-password">
                                    <span class="form-hint">Mindestens 12 Zeichen sowie Groß-/Kleinbuchstaben, Ziffer und Sonderzeichen.</span>
                                    <?php if (!$isNew): ?>
                                        <span class="form-hint">Leer lassen, um das Passwort nicht zu ändern.</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Persönliche Daten -->
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Persönliche Daten</h3></div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label" for="first_name">Vorname</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user->meta['first_name'] ?? ''); ?>" maxlength="120" autocomplete="given-name">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="last_name">Nachname</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user->meta['last_name'] ?? ''); ?>" maxlength="120" autocomplete="family-name">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4">
                    <!-- Rolle & Status -->
                    <div class="card mb-3">
                        <div class="card-header"><h3 class="card-title">Rolle & Status</h3></div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label" for="role">Rolle</label>
                                <select class="form-select" id="role" name="role">
                                    <?php foreach ($availableRoles as $role => $label): ?>
                                        <option value="<?php echo htmlspecialchars((string)$role); ?>" <?php if (($user->role ?? 'member') === $role) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars((string)$label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="status">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <?php foreach ($availableStatuses as $status => $label): ?>
                                        <option value="<?php echo htmlspecialchars((string)$status); ?>" <?php if (($user->status ?? 'active') === $status) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars((string)$label); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="d-grid gap-2">
                                <button type="submit" name="action" value="save" class="btn btn-primary w-100">
                                    <?php echo $isNew ? 'Benutzer erstellen' : 'Speichern'; ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <?php if (!$isNew): ?>
                    <!-- Info -->
                    <div class="card">
                        <div class="card-header"><h3 class="card-title">Info</h3></div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-5">Registriert</dt>
                                <dd class="col-7"><?php echo !empty($user->created_at) ? date('d.m.Y H:i', strtotime($user->created_at)) : '–'; ?></dd>
                                <dt class="col-5">Letzter Login</dt>
                                <dd class="col-7"><?php echo !empty($user->last_login) ? date('d.m.Y H:i', strtotime($user->last_login)) : '–'; ?></dd>
                            </dl>
                        </div>
                    </div>

                    <div class="card border-danger-subtle mt-3">
                        <div class="card-header"><h3 class="card-title text-danger mb-0">Benutzer dauerhaft löschen</h3></div>
                        <div class="card-body">
                            <p class="text-secondary mb-3">Der Benutzer wird <strong>vollständig entfernt</strong>. Diese Aktion ist dauerhaft und kann nicht rückgängig gemacht werden.</p>
                            <button
                                type="button"
                                class="btn btn-danger w-100"
                                data-user-delete-id="<?php echo $userId; ?>"
                                data-user-delete-name="<?php echo htmlspecialchars($userName !== '' ? $userName : 'Benutzer', ENT_QUOTES); ?>"
                            >Benutzer löschen</button>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </form>

        <?php if (!$isNew): ?>
            <form id="deleteUserForm" method="post" action="<?php echo htmlspecialchars($usersAdminPath, ENT_QUOTES); ?>" class="d-none">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?php echo $userId; ?>">
            </form>
        <?php endif; ?>

    </div>
</div>

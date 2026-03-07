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
$siteUrl = defined('SITE_URL') ? SITE_URL : '';
?>

<div class="page-header d-print-none">
    <div class="container-xl">
        <div class="row align-items-center">
            <div class="col-auto">
                <a href="<?php echo htmlspecialchars($siteUrl); ?>/admin/users" class="btn btn-ghost-secondary btn-sm me-2">
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
            <div class="alert alert-<?php echo $alert['type'] === 'success' ? 'success' : 'danger'; ?> alert-dismissible" role="alert">
                <div><?php echo htmlspecialchars($alert['message']); ?></div>
                <a class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></a>
            </div>
        <?php endif; ?>

        <form method="post" action="<?php echo htmlspecialchars($siteUrl); ?>/admin/users" id="userForm">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">
            <input type="hidden" name="action" value="save">
            <?php if (!$isNew): ?>
                <input type="hidden" name="id" value="<?php echo (int)$user->id; ?>">
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
                                    <input type="text" class="form-control" id="username" name="username" value="<?php echo htmlspecialchars($user->username ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required" for="email">E-Mail</label>
                                    <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($user->email ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label <?php echo $isNew ? 'required' : ''; ?>" for="password">Passwort</label>
                                    <input type="password" class="form-control" id="password" name="password" <?php echo $isNew ? 'required' : ''; ?> autocomplete="new-password">
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
                                    <input type="text" class="form-control" id="first_name" name="first_name" value="<?php echo htmlspecialchars($user->meta['first_name'] ?? ''); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label" for="last_name">Nachname</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" value="<?php echo htmlspecialchars($user->meta['last_name'] ?? ''); ?>">
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
                                    <option value="member" <?php if (($user->role ?? 'member') === 'member') echo 'selected'; ?>>Mitglied</option>
                                    <option value="author" <?php if (($user->role ?? '') === 'author') echo 'selected'; ?>>Autor</option>
                                    <option value="editor" <?php if (($user->role ?? '') === 'editor') echo 'selected'; ?>>Editor</option>
                                    <option value="admin" <?php if (($user->role ?? '') === 'admin') echo 'selected'; ?>>Administrator</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label" for="status">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="active" <?php if (($user->status ?? 'active') === 'active') echo 'selected'; ?>>Aktiv</option>
                                    <option value="inactive" <?php if (($user->status ?? '') === 'inactive') echo 'selected'; ?>>Inaktiv</option>
                                    <option value="banned" <?php if (($user->status ?? '') === 'banned') echo 'selected'; ?>>Gesperrt</option>
                                </select>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-primary w-100">
                                <?php echo $isNew ? 'Benutzer erstellen' : 'Speichern'; ?>
                            </button>
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
                    <?php endif; ?>
                </div>
            </div>
        </form>

    </div>
</div>

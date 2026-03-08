<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/bootstrap.php';

$controller->handleProfileRequest();

$pageTitle = 'Profil';
$pageKey = 'profile';
$pageAssets = [];
$user = $controller->getCurrentUser();
$meta = \CMS\Services\MemberService::getInstance()->getUserMeta($controller->getUserId());
$profileFields = (array)($settings['profile_fields'] ?? []);
$profileCompletion = $controller->getProfileCompletion();

include __DIR__ . '/partials/header.php';
?>
<div class="row g-4">
    <div class="col-lg-8">
        <form class="card" method="post" action="">
            <input type="hidden" name="action" value="profile_save">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($controller->csrfToken('profile_save'), ENT_QUOTES) ?>">
            <div class="card-header">
                <h3 class="card-title">Persönliche Angaben</h3>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" for="first_name">Vorname</label>
                        <input class="form-control" id="first_name" name="first_name" type="text" value="<?= htmlspecialchars((string)($meta['first_name'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="last_name">Nachname</label>
                        <input class="form-control" id="last_name" name="last_name" type="text" value="<?= htmlspecialchars((string)($meta['last_name'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="email">E-Mail</label>
                        <input class="form-control" id="email" name="email" type="email" value="<?= htmlspecialchars((string)($user->email ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="phone">Telefon</label>
                        <input class="form-control" id="phone" name="phone" type="text" value="<?= htmlspecialchars((string)($meta['phone'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="company">Unternehmen</label>
                        <input class="form-control" id="company" name="company" type="text" value="<?= htmlspecialchars((string)($meta['company'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="position">Position</label>
                        <input class="form-control" id="position" name="position" type="text" value="<?= htmlspecialchars((string)($meta['position'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="website">Website</label>
                        <input class="form-control" id="website" name="website" type="url" value="<?= htmlspecialchars((string)($meta['website'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="location">Ort</label>
                        <input class="form-control" id="location" name="location" type="text" value="<?= htmlspecialchars((string)($meta['location'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="social">Social / Profil-Link</label>
                        <input class="form-control" id="social" name="social" type="url" value="<?= htmlspecialchars((string)($meta['social'] ?? '')) ?>">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="avatar">Avatar-URL</label>
                        <input class="form-control" id="avatar" name="avatar" type="url" value="<?= htmlspecialchars((string)($meta['avatar'] ?? '')) ?>">
                    </div>
                    <div class="col-12">
                        <label class="form-label" for="bio">Kurzbiografie</label>
                        <textarea class="form-control" id="bio" name="bio" rows="6"><?= htmlspecialchars((string)($meta['bio'] ?? '')) ?></textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">Profil speichern</button>
            </div>
        </form>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-body text-center">
                <?php if ($controller->getAvatarUrl() !== ''): ?>
                    <span class="avatar avatar-xl mb-3" style="background-image: url('<?= htmlspecialchars($controller->getAvatarUrl(), ENT_QUOTES) ?>')"></span>
                <?php else: ?>
                    <span class="avatar avatar-xl mb-3"><?= htmlspecialchars($controller->getInitials()) ?></span>
                <?php endif; ?>
                <h3 class="m-0 mb-1"><?= htmlspecialchars($controller->getDisplayName()) ?></h3>
                <div class="text-secondary"><?= htmlspecialchars((string)($user->email ?? '')) ?></div>
            </div>
        </div>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title">Profilstatus</h3>
                <span class="badge bg-primary-lt"><?= (int)($profileCompletion['percentage'] ?? 0) ?>%</span>
            </div>
            <div class="card-body">
                <div class="progress mb-3">
                    <div class="progress-bar bg-primary" style="width: <?= (int)($profileCompletion['percentage'] ?? 0) ?>%"></div>
                </div>
                <ul class="list-unstyled mb-0">
                    <?php foreach ($profileFields as $field): ?>
                        <?php $hasValue = trim((string)($meta[$field] ?? '')) !== ''; ?>
                        <li class="d-flex justify-content-between py-1">
                            <span><?= htmlspecialchars(ucwords(str_replace('_', ' ', (string)$field))) ?></span>
                            <span class="badge <?= $hasValue ? 'bg-green-lt text-green' : 'bg-secondary-lt text-secondary' ?>">
                                <?= $hasValue ? 'Erledigt' : 'Offen' ?>
                            </span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php';

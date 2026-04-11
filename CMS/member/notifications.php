<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

require_once __DIR__ . '/includes/bootstrap.php';

$controller->handleNotificationsRequest();

$pageTitle = 'Benachrichtigungen';
$pageKey = 'notifications';
$pageAssets = [];
$memberService = \CMS\Services\MemberService::getInstance();
$preferences = $memberService->getNotificationPreferences($controller->getUserId());
$notificationCenter = $controller->getNotificationCenterConfig();
$notificationCenterEnabled = !array_key_exists('center_enabled', $notificationCenter) || !empty($notificationCenter['center_enabled']);
$notificationEmptyText = trim((string)($notificationCenter['empty_text'] ?? 'Zurzeit liegen keine Benachrichtigungen vor.'));
$recentNotifications = $controller->getNotificationCenterNotifications(20);

include __DIR__ . '/partials/header.php';
?>
<div class="row g-4">
    <div class="col-lg-7">
        <form class="card" method="post" action="">
            <input type="hidden" name="action" value="notifications_save">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($controller->csrfToken('notifications_save'), ENT_QUOTES) ?>">
            <div class="card-header"><h3 class="card-title">Einstellungen</h3></div>
            <div class="card-body">
                <div class="row g-3">
                    <?php $checkboxes = [
                        'email_notifications' => 'Allgemeine E-Mail-Benachrichtigungen',
                        'email_updates' => 'Produkt- und System-Updates',
                        'email_security' => 'Sicherheitsrelevante Meldungen',
                        'email_marketing' => 'Marketing-E-Mails',
                        'browser_notifications' => 'Browser-Benachrichtigungen',
                        'desktop_notifications' => 'Desktop-Hinweise',
                        'mobile_notifications' => 'Mobile Benachrichtigungen',
                        'notify_new_features' => 'Hinweise auf neue Funktionen',
                        'notify_promotions' => 'Aktionen und Angebote',
                    ]; ?>
                    <?php foreach ($checkboxes as $key => $label): ?>
                        <div class="col-md-6">
                            <label class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="<?= htmlspecialchars($key) ?>" value="1" <?= !empty($preferences[$key]) ? 'checked' : '' ?>>
                                <span class="form-check-label"><?= htmlspecialchars($label) ?></span>
                            </label>
                        </div>
                    <?php endforeach; ?>
                    <div class="col-md-6">
                        <label class="form-label" for="notification_frequency">Frequenz</label>
                        <select class="form-select" id="notification_frequency" name="notification_frequency">
                            <?php foreach (['immediate' => 'Sofort', 'daily' => 'Täglich', 'weekly' => 'Wöchentlich'] as $value => $label): ?>
                                <option value="<?= htmlspecialchars($value) ?>" <?= (($preferences['notification_frequency'] ?? 'immediate') === $value) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-footer text-end">
                <button type="submit" class="btn btn-primary">Einstellungen speichern</button>
            </div>
        </form>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Letzte Meldungen</h3></div>
            <div class="list-group list-group-flush list-group-hoverable">
                <?php if (!$notificationCenterEnabled): ?>
                    <div class="card-body text-secondary">Das In-App Notification Center ist derzeit deaktiviert.</div>
                <?php elseif ($recentNotifications === []): ?>
                    <div class="card-body text-secondary"><?= htmlspecialchars($notificationEmptyText !== '' ? $notificationEmptyText : 'Zurzeit liegen keine Benachrichtigungen vor.') ?></div>
                <?php else: ?>
                    <?php foreach ($recentNotifications as $notification): ?>
                        <div class="list-group-item">
                            <div class="fw-medium"><?= htmlspecialchars((string)($notification->title ?? $notification->message ?? 'Benachrichtigung')) ?></div>
                            <?php if (!empty($notification->message) && !empty($notification->title)): ?>
                                <div class="text-secondary small"><?= htmlspecialchars((string)$notification->message) ?></div>
                            <?php endif; ?>
                            <div class="text-secondary small mt-1"><?= htmlspecialchars((string)($notification->created_at ?? '')) ?></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/partials/footer.php';

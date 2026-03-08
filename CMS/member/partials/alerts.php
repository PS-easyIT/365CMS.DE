<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$controller = $controller ?? \CMS\MemberArea\MemberController::instance();
$flash = $controller->consumeFlash();

if ($flash === null) {
    return;
}

$type = (string)($flash['type'] ?? 'info');
$message = (string)($flash['message'] ?? '');
$payload = is_array($flash['payload'] ?? null) ? $flash['payload'] : [];
$backupCodes = is_array($payload['backup_codes'] ?? null) ? $payload['backup_codes'] : [];
?>
<div class="alert alert-<?= htmlspecialchars($type) ?> alert-dismissible" role="alert">
    <div class="d-flex flex-column gap-2">
        <div><?= htmlspecialchars($message) ?></div>
        <?php if ($backupCodes !== []): ?>
            <div class="small text-secondary">Bitte speichere diese Backup-Codes sicher. Sie werden nur einmal vollständig angezeigt.</div>
            <div class="member-backup-codes">
                <?php foreach ($backupCodes as $code): ?>
                    <code><?= htmlspecialchars((string)$code) ?></code>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
    <a class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></a>
</div>

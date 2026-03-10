<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

$alertData = is_array($alertData ?? null) ? $alertData : [];
$alertMessage = trim((string) ($alertData['message'] ?? ''));
if ($alertMessage === '') {
    return;
}

$alertType = trim((string) ($alertData['type'] ?? 'info'));
$alertDismissible = (bool) ($alertDismissible ?? true);
$alertMarginClass = trim((string) ($alertMarginClass ?? 'mb-4'));
$alertTypeMap = [
    'success' => 'success',
    'danger' => 'danger',
    'error' => 'danger',
    'warning' => 'warning',
    'info' => 'info',
    'secondary' => 'secondary',
];
$alertClass = $alertTypeMap[$alertType] ?? 'info';
?>
<div class="alert alert-<?php echo htmlspecialchars($alertClass, ENT_QUOTES, 'UTF-8'); ?><?php echo $alertDismissible ? ' alert-dismissible' : ''; ?> <?php echo htmlspecialchars($alertMarginClass, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
    <div class="d-flex">
        <div><?php echo htmlspecialchars($alertMessage, ENT_QUOTES, 'UTF-8'); ?></div>
    </div>
    <?php if ($alertDismissible): ?>
        <a class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></a>
    <?php endif; ?>
</div>

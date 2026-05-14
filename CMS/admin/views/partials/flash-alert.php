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
$alertDetails = is_array($alertData['error_details'] ?? null) ? $alertData['error_details'] : [];
$alertListDetails = array_values(array_filter(array_map(
    static fn ($detail): string => trim((string) $detail),
    is_array($alertData['details'] ?? null) ? $alertData['details'] : []
), static fn (string $detail): bool => $detail !== ''));
$reportPayload = is_array($alertData['report_payload'] ?? null) ? $alertData['report_payload'] : [];
$errorCode = trim((string)($alertDetails['code'] ?? ($reportPayload['error_code'] ?? '')));
$errorData = is_array($alertDetails['data'] ?? null) ? $alertDetails['data'] : (is_array($reportPayload['error_data'] ?? null) ? $reportPayload['error_data'] : []);
$errorContext = is_array($alertDetails['context'] ?? null) ? $alertDetails['context'] : (is_array($reportPayload['context'] ?? null) ? $reportPayload['context'] : []);
$backTo = (string)($_SERVER['REQUEST_URI'] ?? '/admin/diagnose');
$reportToken = \CMS\Security::instance()->generateToken('admin_error_report');
$reportTarget = '/admin/error-report';
$reportTitle = trim((string)($reportPayload['title'] ?? 'Fehlerreport'));
$reportSource = trim((string)($reportPayload['source_url'] ?? $backTo));
$alertTypeMap = [
    'success' => 'success',
    'danger' => 'danger',
    'error' => 'danger',
    'warning' => 'warning',
    'info' => 'info',
    'secondary' => 'secondary',
];
$alertClass = $alertTypeMap[$alertType] ?? 'info';
$maskInlineSecrets = static function (string $value): string {
    $value = preg_replace('/([?&](?:token|csrf_token|nonce|key|secret|password|pass)=)[^&\s]+/i', '$1***', $value) ?? $value;
    $value = preg_replace('/\b(Bearer\s+)[A-Za-z0-9._~+\/-]+=*/i', '$1***', $value) ?? $value;
    $value = preg_replace('/(password|passwd|secret|token|credential|api[_-]?key)(\s*[=:]\s*)\S+/i', '$1$2***', $value) ?? $value;

    return trim(preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value) ?? '');
};
$redactAlertValue = static function (mixed $value, string $key = '') use (&$redactAlertValue, $maskInlineSecrets): mixed {
    if (preg_match('/(password|passwd|secret|token|nonce|csrf|auth|mfa|key|credential|cookie|session)/i', $key) === 1) {
        return '***';
    }

    if (is_array($value)) {
        $redacted = [];
        foreach ($value as $childKey => $childValue) {
            $redacted[$childKey] = $redactAlertValue($childValue, (string)$childKey);
        }

        return $redacted;
    }

    if (is_string($value)) {
        return $maskInlineSecrets($value);
    }

    if ($value instanceof \Throwable) {
        return $value::class;
    }

    return $value;
};
$formatAlertJson = static function (array $value): string {
    $json = json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    return is_string($json) ? $json : '[nicht serialisierbar]';
};
$errorData = $redactAlertValue($errorData, 'error_data');
$errorData = is_array($errorData) ? $errorData : [];
$errorContext = $redactAlertValue($errorContext, 'context');
$errorContext = is_array($errorContext) ? $errorContext : [];
?>
<div class="alert alert-<?php echo htmlspecialchars($alertClass, ENT_QUOTES, 'UTF-8'); ?><?php echo $alertDismissible ? ' alert-dismissible' : ''; ?> <?php echo htmlspecialchars($alertMarginClass, ENT_QUOTES, 'UTF-8'); ?>" role="alert">
    <div class="d-flex">
        <div class="w-100">
            <div><?php echo htmlspecialchars($alertMessage, ENT_QUOTES, 'UTF-8'); ?></div>

            <?php if ($alertListDetails !== []): ?>
                <ul class="mb-0 mt-2 small ps-3">
                    <?php foreach ($alertListDetails as $detail): ?>
                        <li><?php echo htmlspecialchars($detail, ENT_QUOTES, 'UTF-8'); ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

            <?php if ($errorCode !== '' || $errorData !== [] || $errorContext !== []): ?>
                <div class="mt-3 small">
                    <?php if ($errorCode !== ''): ?>
                        <div><strong>Code:</strong> <code><?php echo htmlspecialchars($errorCode, ENT_QUOTES, 'UTF-8'); ?></code></div>
                    <?php endif; ?>
                    <?php if ($errorData !== []): ?>
                        <div class="mt-2"><strong>Daten:</strong></div>
                        <pre class="bg-body-tertiary border rounded p-2 mb-0 mt-1"><?php echo htmlspecialchars($formatAlertJson($errorData), ENT_QUOTES, 'UTF-8'); ?></pre>
                    <?php endif; ?>
                    <?php if ($errorContext !== []): ?>
                        <div class="mt-2"><strong>Kontext:</strong></div>
                        <pre class="bg-body-tertiary border rounded p-2 mb-0 mt-1"><?php echo htmlspecialchars($formatAlertJson($errorContext), ENT_QUOTES, 'UTF-8'); ?></pre>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($reportPayload !== []): ?>
                <form method="post" action="<?php echo htmlspecialchars($reportTarget, ENT_QUOTES, 'UTF-8'); ?>" class="mt-3 d-flex flex-wrap gap-2 align-items-center">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($reportToken, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="back_to" value="<?php echo htmlspecialchars($backTo, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="title" value="<?php echo htmlspecialchars($reportTitle, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="message" value="<?php echo htmlspecialchars($alertMessage, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="error_code" value="<?php echo htmlspecialchars($errorCode, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="source_url" value="<?php echo htmlspecialchars($reportSource, ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="error_data_json" value="<?php echo htmlspecialchars((string)json_encode($errorData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'); ?>">
                    <input type="hidden" name="context_json" value="<?php echo htmlspecialchars((string)json_encode($errorContext, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES, 'UTF-8'); ?>">
                    <button type="submit" class="btn btn-sm btn-outline-light">Report erstellen</button>
                    <span class="text-muted">Legt einen nachvollziehbaren Fehlerreport im Adminbereich an.</span>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php if ($alertDismissible): ?>
        <a class="btn-close" data-bs-dismiss="alert" aria-label="Schließen"></a>
    <?php endif; ?>
</div>

<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

use CMS\Security;

function cms_admin_post_action_shell_flash(string $sessionKey, array $payload): void
{
    $_SESSION[$sessionKey] = [
        'type' => ($payload['type'] ?? 'danger') === 'success' ? 'success' : 'danger',
        'message' => trim((string) ($payload['message'] ?? 'Aktion konnte nicht verarbeitet werden.')),
        'details' => is_array($payload['details'] ?? null) ? $payload['details'] : [],
    ];
}

function cms_admin_post_action_shell_redirect(string $targetUrl): never
{
    header('Location: ' . $targetUrl);
    exit;
}

$postActionShellConfig = is_array($postActionShellConfig ?? null) ? $postActionShellConfig : [];
$accessChecker = $postActionShellConfig['access_checker'] ?? null;
$redirectResolver = $postActionShellConfig['redirect_resolver'] ?? null;
$handler = $postActionShellConfig['handler'] ?? null;

if (!is_callable($accessChecker) || !is_callable($redirectResolver) || !is_callable($handler)) {
    throw new RuntimeException('Post-Action-Shell erwartet callable access_checker-, redirect_resolver- und handler-Konfigurationen.');
}

$accessDeniedUrl = trim((string) ($postActionShellConfig['access_denied_url'] ?? '/'));
$csrfAction = (string) ($postActionShellConfig['csrf_action'] ?? 'admin_post_action');
$alertSessionKey = (string) ($postActionShellConfig['alert_session_key'] ?? 'admin_alert');
$invalidTokenMessage = (string) ($postActionShellConfig['invalid_token_message'] ?? 'Sicherheitstoken ungültig.');
$unknownActionMessage = (string) ($postActionShellConfig['unknown_action_message'] ?? 'Aktion konnte nicht verarbeitet werden.');

$redirectUrl = trim((string) $redirectResolver($_POST ?? [], $_SERVER ?? []));
if ($redirectUrl === '') {
    $redirectUrl = '/';
}

if (!(bool) $accessChecker($postActionShellConfig, $redirectUrl)) {
    cms_admin_post_action_shell_redirect($accessDeniedUrl);
}

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
    cms_admin_post_action_shell_redirect($redirectUrl);
}

if (!Security::instance()->verifyToken((string) ($_POST['csrf_token'] ?? ''), $csrfAction)) {
    cms_admin_post_action_shell_flash($alertSessionKey, ['type' => 'danger', 'message' => $invalidTokenMessage]);
    cms_admin_post_action_shell_redirect($redirectUrl);
}

$result = $handler($_POST, $_SERVER ?? [], $redirectUrl);

cms_admin_post_action_shell_flash($alertSessionKey, [
    'type' => !empty($result['success']) ? 'success' : 'danger',
    'message' => (string) ($result['message'] ?? $result['error'] ?? $unknownActionMessage),
    'details' => $result['details'] ?? [],
]);

cms_admin_post_action_shell_redirect($redirectUrl);
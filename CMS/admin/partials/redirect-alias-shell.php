<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function cms_admin_redirect_alias_shell_normalize_target(mixed $target): string
{
    $resolvedTarget = trim((string) $target);
    if ($resolvedTarget === '') {
        return SITE_URL;
    }

    if (preg_match('~^https?://~i', $resolvedTarget) === 1) {
        return $resolvedTarget;
    }

    return SITE_URL . '/' . ltrim($resolvedTarget, '/');
}

$adminRedirectAliasConfig = is_array($adminRedirectAliasConfig ?? null) ? $adminRedirectAliasConfig : [];
$accessChecker = $adminRedirectAliasConfig['access_checker'] ?? null;

if (!is_callable($accessChecker)) {
    throw new RuntimeException('Redirect-Alias-Shell erwartet eine callable access_checker-Konfiguration.');
}

$targetUrl = cms_admin_redirect_alias_shell_normalize_target($adminRedirectAliasConfig['target_url'] ?? SITE_URL);
$fallbackUrl = cms_admin_redirect_alias_shell_normalize_target($adminRedirectAliasConfig['fallback_url'] ?? SITE_URL);
$redirectUrl = (bool) $accessChecker($adminRedirectAliasConfig) ? $targetUrl : $fallbackUrl;

header('Location: ' . $redirectUrl);
exit;
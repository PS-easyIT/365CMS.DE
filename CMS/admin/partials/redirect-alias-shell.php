<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function cms_admin_redirect_alias_shell_normalize_target(mixed $target): string
{
    $resolvedTarget = trim((string) $target);
    if ($resolvedTarget === '') {
        return '/';
    }

    if (preg_match('~^https?://~i', $resolvedTarget) === 1) {
        return $resolvedTarget;
    }

    $query = (string) parse_url($resolvedTarget, PHP_URL_QUERY);
    $fragment = (string) parse_url($resolvedTarget, PHP_URL_FRAGMENT);
    $path = (string) parse_url($resolvedTarget, PHP_URL_PATH);

    if ($path === '') {
        $path = '/';
    }

    $normalizedTarget = '/' . ltrim($path, '/');
    if ($normalizedTarget === '//') {
        $normalizedTarget = '/';
    }

    if ($query !== '') {
        $normalizedTarget .= '?' . $query;
    }

    if ($fragment !== '') {
        $normalizedTarget .= '#' . $fragment;
    }

    return $normalizedTarget;
}

$adminRedirectAliasConfig = is_array($adminRedirectAliasConfig ?? null) ? $adminRedirectAliasConfig : [];
$accessChecker = $adminRedirectAliasConfig['access_checker'] ?? null;

if (!is_callable($accessChecker)) {
    throw new RuntimeException('Redirect-Alias-Shell erwartet eine callable access_checker-Konfiguration.');
}

$targetUrl = cms_admin_redirect_alias_shell_normalize_target($adminRedirectAliasConfig['target_url'] ?? '/');
$fallbackUrl = cms_admin_redirect_alias_shell_normalize_target($adminRedirectAliasConfig['fallback_url'] ?? '/');
$redirectUrl = (bool) $accessChecker($adminRedirectAliasConfig) ? $targetUrl : $fallbackUrl;

header('Location: ' . $redirectUrl);
exit;
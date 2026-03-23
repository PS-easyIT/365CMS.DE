<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

function meridian_is_external_url(string $url): bool
{
    $trimmed = trim($url);
    if ($trimmed === '' || !preg_match('#^https?://#i', $trimmed)) {
        return false;
    }

    $targetHost = strtolower((string)(parse_url($trimmed, PHP_URL_HOST) ?? ''));
    $siteHost = strtolower((string)(parse_url((string)SITE_URL, PHP_URL_HOST) ?? ''));

    if ($targetHost === '' || $siteHost === '') {
        return true;
    }

    return $targetHost !== $siteHost;
}

function meridian_normalize_internal_path(string $url): string
{
    $trimmed = trim($url);
    if ($trimmed === '') {
        return '';
    }

    if (preg_match('#^(mailto:|tel:|sms:|javascript:)#i', $trimmed)) {
        return '';
    }

    if (meridian_is_external_url($trimmed)) {
        return '';
    }

    if (str_starts_with($trimmed, '#')) {
        return '#';
    }

    $path = (string)(parse_url($trimmed, PHP_URL_PATH) ?? '');
    if ($path === '') {
        return '/';
    }

    $normalized = '/' . ltrim($path, '/');

    return rtrim($normalized, '/') ?: '/';
}

function meridian_route_exists(string $url): bool
{
    $trimmed = trim($url);
    if ($trimmed === '') {
        return false;
    }

    if (preg_match('#^(mailto:|tel:|sms:)#i', $trimmed)) {
        return true;
    }

    if (meridian_is_external_url($trimmed) || str_starts_with($trimmed, '#')) {
        return true;
    }

    $path = meridian_normalize_internal_path($trimmed);
    if ($path === '') {
        return false;
    }

    $staticRoutes = [
        '/',
        '/blog',
        '/search',
        '/login',
        '/register',
        '/logout',
        '/contact',
        '/kontakt',
        '/impressum',
        '/datenschutz',
        '/forgot-password',
        '/sitemap.xml',
        '/robots.txt',
    ];

    if (in_array($path, $staticRoutes, true)) {
        return true;
    }

    if (str_starts_with($path, '/admin') || str_starts_with($path, '/api')) {
        return false;
    }

    if ($path === '/member' || str_starts_with($path, '/member/')) {
        return true;
    }

    $slug = trim($path, '/');
    if ($slug === '') {
        return true;
    }

    $postSlug = class_exists('CMS\\Services\\PermalinkService')
        ? \CMS\Services\PermalinkService::getInstance()->extractPostSlugFromPath($path)
        : (str_starts_with($path, '/blog/') ? trim(substr($path, strlen('/blog/')), '/') : null);

    if ($postSlug !== null && $postSlug !== '') {
        try {
            $db = \CMS\Database::instance();
            $row = $db->get_row(
                "SELECT id FROM {$db->getPrefix()}posts WHERE slug = ? AND " . cms_post_publication_where() . " LIMIT 1",
                [$postSlug]
            );

            return $row !== null;
        } catch (\Throwable $e) {
            return false;
        }
    }

    try {
        $page = \CMS\PageManager::instance()->getPageBySlug($slug);
        if ($page !== null && ($page['status'] ?? '') === 'published') {
            return true;
        }
    } catch (\Throwable $e) {
    }

    try {
        return \CMS\Services\SiteTableService::getInstance()->getHubPageBySlug($slug, 'de') !== null;
    } catch (\Throwable $e) {
        return false;
    }
}

function meridian_filter_navigation_items(array $items): array
{
    $filtered = [];

    foreach ($items as $item) {
        if (!is_array($item)) {
            continue;
        }

        $children = meridian_filter_navigation_items($item['children'] ?? []);
        $url = (string)($item['href'] ?? $item['url'] ?? '');
        $hasValidUrl = $url !== '' && meridian_route_exists($url);

        if (!$hasValidUrl && $children === []) {
            continue;
        }

        if (!$hasValidUrl && $children !== []) {
            $fallbackUrl = (string)($children[0]['href'] ?? $children[0]['url'] ?? '#');
            $item['url'] = $fallbackUrl;
            unset($item['href']);
        }

        $item['children'] = $children;
        $filtered[] = $item;
    }

    return $filtered;
}

function meridian_first_available_route(array $candidates): string
{
    foreach ($candidates as $candidate) {
        $url = (string)$candidate;
        if ($url !== '' && meridian_route_exists($url)) {
            return $url;
        }
    }

    return '';
}

function meridian_footer_about_links(): array
{
    $links = [];

    $aboutUrl = meridian_first_available_route(['/about', '/ueber-uns', '/ueber']);
    if ($aboutUrl !== '') {
        $links[] = ['label' => 'Über uns', 'url' => $aboutUrl];
    }

    $contactUrl = meridian_first_available_route(['/contact', '/kontakt']);
    if ($contactUrl !== '') {
        $links[] = ['label' => 'Kontakt', 'url' => $contactUrl];
    }

    foreach ([
        ['label' => 'Impressum', 'url' => '/impressum'],
        ['label' => 'Datenschutz', 'url' => '/datenschutz'],
    ] as $link) {
        if (meridian_route_exists($link['url'])) {
            $links[] = $link;
        }
    }

    return $links;
}

function meridian_nav_menu(string $location, string $currentPath = ''): void
{
    $items = [];
    try {
        $items = \CMS\ThemeManager::instance()->getMenu($location);
    } catch (\Throwable $e) {
    }

    $items = meridian_filter_navigation_items($items);

    if (empty($items)) {
        return;
    }

    $siteUrl = defined('SITE_URL') ? SITE_URL : '';

    $renderItems = function (array $list) use (&$renderItems, $siteUrl, $currentPath): void {
        foreach ($list as $item) {
            $label = htmlspecialchars($item['label'] ?? '', ENT_QUOTES, 'UTF-8');
            $url = $item['url'] ?? '#';
            $fullUrl = str_starts_with($url, 'http') ? $url : rtrim($siteUrl, '/') . '/' . ltrim($url, '/');
            $target = ($item['target'] ?? '_self') === '_blank' ? ' target="_blank" rel="noopener"' : '';

            $path = parse_url($fullUrl, PHP_URL_PATH) ?: '/';
            $isActive = $currentPath !== '' && rtrim($currentPath, '/') === rtrim($path, '/');
            $activeClass = $isActive ? ' active' : '';

            $children = $item['children'] ?? [];
            $hasChild = !empty($children);

            if ($hasChild) {
                echo '<div class="nav-group' . $activeClass . '">' . "\n";
                echo '  <a href="' . htmlspecialchars($fullUrl, ENT_QUOTES, 'UTF-8') . '"' . $target . '>' . $label . ' <svg viewBox="0 0 10 10"><polyline points="2,3 5,7 8,3"/></svg></a>' . "\n";
                echo "  <div class=\"nav-dropdown\">\n";
                $renderItems($children);
                echo "  </div>\n";
                echo "</div>\n";
                continue;
            }

            echo '<a href="' . htmlspecialchars($fullUrl, ENT_QUOTES, 'UTF-8') . '" class="nav-link' . $activeClass . '"' . $target . '>' . $label . '</a>' . "\n";
        }
    };

    $renderItems($items);
}

function meridian_is_logged_in(): bool
{
    try {
        return \CMS\Auth::instance()->isLoggedIn();
    } catch (\Throwable $e) {
        return false;
    }
}

function meridian_get_flash(): ?array
{
    if (session_status() === PHP_SESSION_NONE) {
        return null;
    }

    foreach (['success', 'error', 'info', 'warning'] as $type) {
        if (isset($_SESSION[$type])) {
            $message = $_SESSION[$type];
            unset($_SESSION[$type]);

            return ['type' => $type, 'message' => $message];
        }
    }

    return null;
}

function meridian_setting(string $section, string $key, mixed $default = null): mixed
{
    try {
        return \CMS\Services\ThemeCustomizer::instance()->get($section, $key, $default);
    } catch (\Throwable $e) {
        return $default;
    }
}

function meridian_member_area_url(string $path = 'dashboard'): string
{
    $normalized = trim($path, '/');
    $target = $normalized === '' ? 'dashboard' : $normalized;

    return rtrim((string)SITE_URL, '/') . '/member/' . $target;
}

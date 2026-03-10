<?php
declare(strict_types=1);

/**
 * Admin Partial: HTML <head> + CSS
 *
 * Erwartet vor dem include:
 *   $pageTitle   – string  Seitentitel (z. B. "Dashboard")
 *   $pageAssets  – array   Optionale zusätzliche CSS/JS-Dateien
 *
 * @package CMSv2\Admin
 */

if (!defined('ABSPATH')) {
    exit;
}

$pageTitle  = $pageTitle ?? 'Admin';
$pageAssets = $pageAssets ?? [];
$siteUrl    = defined('SITE_URL') ? SITE_URL : '';
$siteName   = function_exists('cms_get_site_name') ? cms_get_site_name() : (defined('SITE_NAME') ? SITE_NAME : '365CMS');

\CMS\CacheManager::instance()->sendResponseHeaders('private');
?>
<!doctype html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="robots" content="noindex,nofollow">
    <title><?= htmlspecialchars($pageTitle) ?> – <?= htmlspecialchars($siteName) ?> Admin</title>

    <!-- Tabler Core CSS -->
    <link rel="stylesheet" href="<?= htmlspecialchars(cms_asset_url('tabler/css/tabler.min.css'), ENT_QUOTES) ?>">

    <!-- 365CMS Admin Overrides -->
    <link rel="stylesheet" href="<?= htmlspecialchars(cms_asset_url('css/admin.css'), ENT_QUOTES) ?>">

    <?php
    // Zusätzliche Stylesheets aus $pageAssets['css']
    if (!empty($pageAssets['css'])):
        foreach ($pageAssets['css'] as $css): ?>
            <link rel="stylesheet" href="<?= htmlspecialchars($css) ?>">
        <?php endforeach;
    endif;
    ?>

    <?php \CMS\Hooks::doAction('admin_head'); ?>
</head>
<body class="layout-fluid">
    <div class="page">

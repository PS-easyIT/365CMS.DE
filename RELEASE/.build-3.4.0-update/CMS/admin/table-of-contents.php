<?php
declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

/**
 * Table of Contents – Entry Point
 * Route: /admin/table-of-contents
 */

use CMS\Auth;

const CMS_ADMIN_TOC_CAPABILITY = 'manage_settings';

$sectionPageConfig = [
    'section' => 'table-of-contents',
    'route_path' => '/admin/table-of-contents',
    'view_file' => __DIR__ . '/views/toc/settings.php',
    'page_title' => 'Inhaltsverzeichnis',
    'active_page' => 'table-of-contents',
    'csrf_action' => 'admin_toc',
    'module_file' => __DIR__ . '/modules/toc/TocModule.php',
    'module_factory' => static fn (): TocModule => new TocModule(),
    'data_loader' => static fn (TocModule $module): array => [
        'settings' => $module->getSettings(),
    ],
    'access_checker' => static fn (): bool => Auth::instance()->isAdmin() && Auth::instance()->hasCapability(CMS_ADMIN_TOC_CAPABILITY),
    'invalid_token_message' => 'Sicherheitstoken ungültig.',
    'unknown_action_message' => 'Aktion konnte nicht verarbeitet werden.',
    'post_handler' => static function (TocModule $module, string $section, array $post): array {
        if (($post['action'] ?? '') !== 'save') {
            return ['success' => false, 'error' => 'Unbekannte Aktion.'];
        }

        return $module->saveSettings($post);
    },
];

require __DIR__ . '/partials/section-page-shell.php';

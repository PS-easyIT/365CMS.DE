<?php
/**
 * elFinder Connector (Admin)
 *
 * @package CMSv2\Admin
 */

declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/config.php';
require_once CORE_PATH . 'autoload.php';

use CMS\Auth;

if (!defined('ABSPATH')) {
    exit;
}

if (!Auth::instance()->isAdmin()) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'forbidden']);
    exit;
}

$vendorAutoload = dirname(ABSPATH) . '/ASSETS/autoload.php';
if (file_exists($vendorAutoload)) {
    require_once $vendorAutoload;
}

if (!class_exists('elFinderConnector') || !class_exists('elFinder')) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'elFinder classes not available']);
    exit;
}

/**
 * Zugriffskontrolle: Dotfiles verstecken/sperren.
 */
function cmsElfinderAccess(string $attr, string $path, $data, $volume, ?bool $isDir, string $relpath): ?bool
{
    $basename = basename($path);
    if ($basename !== '' && $basename[0] === '.' && strlen($relpath) !== 1) {
        return !($attr === 'read' || $attr === 'write');
    }

    return null;
}

$opts = [
    'roots' => [
        [
            'id'            => 'cms_uploads',
            'driver'        => 'LocalFileSystem',
            'path'          => rtrim(UPLOAD_PATH, '/\\') . DIRECTORY_SEPARATOR,
            'URL'           => rtrim(UPLOAD_URL, '/') . '/',
            'uploadDeny'    => ['all'],
            'uploadAllow'   => [
                'image',
                'video',
                'audio',
                'application/pdf',
                'text/plain',
                'application/zip',
                'application/x-zip-compressed',
                'application/msword',
                'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'application/vnd.ms-excel',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
            'uploadOrder'   => ['deny', 'allow'],
            'accessControl' => 'cmsElfinderAccess',
            'winHashFix'    => DIRECTORY_SEPARATOR !== '/',
        ],
    ],
];

$connector = new elFinderConnector(new elFinder($opts));
$connector->run();
